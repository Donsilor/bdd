<?php

namespace services\order;

use common\enums\LogisticsEnum;
use common\models\market\MarketCouponDetails;
use services\market\CouponService;

use common\enums\AuditStatusEnum;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\order\OrderCart;
use common\models\order\OrderInvoice;
use Omnipay\Common\Message\AbstractResponse;
use services\goods\TypeService;
use services\market\CardService;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;
use common\models\order\OrderGoods;
use common\models\order\Order;
use common\models\member\Address;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\enums\PayStatusEnum;
use common\models\member\Member;
use common\enums\OrderStatusEnum;
use common\enums\StatusEnum;
use common\models\common\PayLog;
use common\enums\PayEnum;

/**
 * Class OrderService
 * @package services\order
 */
class OrderService extends OrderBaseService
{
    public function getOrderLogisticsInfo($order)
    {
        //如保数据库有信息，则直接返回

        $message = null;
        try {

            if(!is_object($order)) {
                return '$order错误';
            }

            if(empty($order->express_no)) {
                return '物流号为空';
            }

            $company = LogisticsEnum::getValue($order->express_id);

            /**
             * @var $logistics \Finecho\Logistics\Order
             */
            $logistics = \Yii::$app->logistics->kd100($order->express_no, $company, true);

            $result = $logistics->toArray();

            if(isset($result['original']))
                unset($result['original']);

        } catch (\Finecho\Logistics\Exceptions\InquiryErrorException $e) {
            //查询错误
            $message = $e->getMessage();
            $result = null;
        } catch (\Finecho\Logistics\Exceptions\HttpException $e) {
            //网络错误,
            $message = $e->getMessage();
            $result = '网络错误，请稍后再试';
        } catch (\Finecho\Logistics\Exceptions\InvalidArgumentException $e) {
            //参数错误
            $message = $e->getMessage();
            $result = '错误';
        } catch (\Exception $e) {
            //错误
            $message = $e->getMessage();
            $result = '错误';
        }

        if($message) {
            \Yii::$app->services->actionLog->create('查询物流信息错误', $message, [
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
                'express_id' => $order->express_id,
                'express_company' => $company,
                'express_no' => $order->express_no,
            ]);
        }

        return $result;
    }

    /**
     * 创建订单
     * @param array $cart_ids
     * @param int $buyer_id
     * @param int $buyer_address_id
     * @param array $order_info
     * @param array $invoice_info
     * @param int $coupon_id
     */
    public function createOrder($cart_ids, $buyer_id, $buyer_address_id, $order_info, $invoice_info, $coupon_id=0, $cards=[])
    {
        if($coupon_id) {
            $where = [
                'coupon_id' => $coupon_id,
                'member_id' => $buyer_id,
                'coupon_status' => 1,
            ];
            if(!($couponDetails = MarketCouponDetails::findOne($where))) {
                throw new UnprocessableEntityHttpException("优惠券已失效");
            }
        }

//        $buyer = Member::find()->where(['id'=>$buyer_id])->one();

        $orderAccountTax = $this->getOrderAccountTax($cart_ids, $buyer_id, $buyer_address_id, $coupon_id, $cards);

        if(empty($orderAccountTax['buyerAddress'])) {
            throw new UnprocessableEntityHttpException("收货地址不能为空");
        }
        $languages = $this->getLanguages();
        if(empty($orderAccountTax['orderGoodsList'])) {
            throw new UnprocessableEntityHttpException("商品信息为空");
        }
        $order_amount = $orderAccountTax['order_amount'];
        $buyerAddress = $orderAccountTax['buyerAddress'];
        $orderGoodsList   = $orderAccountTax['orderGoodsList'];
        $currency = $orderAccountTax['currency'];
        $exchange_rate = $orderAccountTax['exchange_rate'];
        //订单
        $order = new Order();
        $order->attributes = $order_info;
        $order->language   = $this->getLanguage();
        $order->member_id = $buyer_id;
        $order->order_sn  = $this->createOrderSn();
        $order->payment_status = PayStatusEnum::UNPAID;
        $order->order_status = OrderStatusEnum::ORDER_UNPAID;
        $order->ip = \Yii::$app->request->userIP;  //用户下单ip
        $order->is_invoice = empty($invoice_info)?0:1;//是否开发票
        list($order->ip_area_id,$order->ip_location) = \Yii::$app->ipLocation->getLocation($order->ip);
        if(false === $order->save()){
            throw new UnprocessableEntityHttpException($this->getError($order));
        }
        //插入order_sync
        $sql = "insert into order_sync(order_id) values({$order->id})";
        \Yii::$app->db->createCommand($sql)->execute();
        if($coupon_id) {
            //使用优惠券
            //CouponService::incrMoneyUse($coupon_id, 1);

            $data = [
                'coupon_code' => '',
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
                'coupon_status' => 2,
                'use_time' => time(),
            ];

            $where = [
                'id' => $couponDetails->id,
                'member_id' => $buyer_id,
                'coupon_status' => 1,
            ];

            if(!MarketCouponDetails::updateAll($data, $where)) {
                throw new UnprocessableEntityHttpException("优惠券使用失败");
            }
        }

        //订单商品       
        foreach ($orderGoodsList as $goods) {
            if(!empty($goods['coupon_id']) && !empty($goods['coupon']['discount'])) {
                //使用折扣券
                $coupon = $goods['coupon'];
                CouponService::incrDiscountUse($goods['coupon_id'], $coupon['type_id'], $coupon['style_id'], $coupon['num']);
            }

            $orderGoods = new OrderGoods();
            $orderGoods->attributes = $goods;
            $orderGoods->order_id = $order->id;
            $orderGoods->exchange_rate = $exchange_rate;
            $orderGoods->currency = $currency;
            if(false === $orderGoods->save()) {
                throw new UnprocessableEntityHttpException($this->getError($orderGoods));
            }

             //订单商品明细
            foreach (array_keys($languages) as $language){
                $goods = \Yii::$app->services->goods->getGoodsInfo($orderGoods->goods_id,$orderGoods->goods_type,false,$language);
                if($language == $this->getLanguage()) {
                    if(empty($goods) || $goods['status'] != 1) {
                        throw new UnprocessableEntityHttpException("订单中部分商品已下架,请重新下单");
                    }
    
                    //验证库存
                    if($orderGoods->goods_num > $goods['goods_storage']) {
                        throw new UnprocessableEntityHttpException("订单中部分商品已下架,请重新下单");
                    }
                }

                $langModel = $orderGoods->langModel();
                $langModel->master_id = $orderGoods->id;
                $langModel->language = $language;
                $langModel->goods_name = $goods['goods_name'];
                $langModel->goods_body = $goods['goods_body'];                
                if(false === $langModel->save()){
                    throw new UnprocessableEntityHttpException($this->getError($langModel));
                }
            }
        }
        //金额校验
        if($order_info['order_amount'] != $order_amount) {
            throw new UnprocessableEntityHttpException("订单金额校验失败：订单金额有变动，请刷新页面查看");
        }
                
        $orderAccount = new OrderAccount();
        $orderAccount->attributes = $orderAccountTax;
        $orderAccount->order_id = $order->id;
        if(false === $orderAccount->save()){
            throw new UnprocessableEntityHttpException($this->getError($orderAccount));
        }
        //订单地址
        $orderAddress = new OrderAddress();
        $orderAddress->attributes = $buyerAddress->toArray();
        $orderAddress->order_id   = $order->id;

        if(false === $orderAddress->save()) {
            throw new UnprocessableEntityHttpException($this->getError($orderAddress));
        }

        //购物券消费
        CardService::consume($order->id, $orderAccountTax['cards']);

        //如果有发票信息
        if(!empty($invoice_info)) {
            $invoice = new OrderInvoice();
            $invoice->attributes = $invoice_info;
            $invoice->order_id   = $order->id;
            if(false === $invoice->save()) {
                throw new UnprocessableEntityHttpException($this->getError($invoice));
            }
        }

        //订单日志
//        $log_msg = "创建订单,订单编号：".$order->order_sn;
//        $log_role = 'buyer';
//        $log_user = $buyer->username;
//        $this->addOrderLog($order->id, $log_msg, $log_role, $log_user,$order->order_status);
        OrderLogService::create($order);

        //创建订单
        \Yii::$app->services->job->notifyContacts->createOrder($order->order_sn);

        //清空购物车
        OrderCart::updateAll(['status'=>0], ['id'=>$cart_ids,'member_id'=>$buyer_id]);
        
        return [
            "currency" => $currency,
            "order_amount"=> $order_amount,
            "pay_amount"=> $orderAccountTax['pay_amount'],
            "card_amount"=> $orderAccountTax['card_amount'],
            "order_id" => $order->id,
        ];
    }
    /**
     * 获取订单金额，税费信息
     * @param array $carts
     * @param int $buyer_id
     * @param int $buyer_address_id
     * @param int $coupon_id
     * @param array $cards
     * @throws UnprocessableEntityHttpException
     * @return array
     */
    public function getOrderAccountTax($carts, $buyer_id, $buyer_address_id, $coupon_id=0, $cards=[])
    {
        if(empty($carts) || !is_array($carts)) {
            throw new UnprocessableEntityHttpException("[carts]参数错误");
        }

        $cartIds = [];
        $discounts = [];

        foreach ($carts as $cart) {
            if(empty($cart['cart_id'])) {
                throw new UnprocessableEntityHttpException("[carts]参数错误");
            }

            $cartIds[] = $cart['cart_id'];

            if(!empty($cart['coupon_id'])) {
                $discounts[$cart['cart_id']] = $cart['coupon_id'];
            }
        }
        
        $cartList = OrderCart::find()->where(['member_id'=>$buyer_id,'id'=>$cartIds])->asArray()->all();

        if(empty($cartList)) {
            throw new UnprocessableEntityHttpException("您的购物车商品不存在");
        }

        foreach ($cartList as &$item) {
            $item['coupon_id'] = $discounts[$item['id']]??0;
        }

        $result = $this->getCartAccountTax($cartList, $coupon_id, $cards);

        $result['buyerAddress'] = Address::find()->where(['id'=>$buyer_address_id,'member_id'=>$buyer_id])->one();;

        return $result;

    }
    /**
     * 获取订单支付金额
     * @param unknown $order_id
     * @param unknown $member_id
     */
    public function getOrderAccount($order_id, $member_id = 0) 
    {        
        $query = Order::find()->select(['order.order_sn','order.order_status','account.*'])
            ->innerJoin(OrderAccount::tableName().' account',"order.id=account.order_id")
            ->where(['order.id'=>$order_id]);
        
        if($member_id) {
            $query->andWhere(['=','order.member_id',$member_id]);
        }
        return $query->asArray()->one();
    }
    /**
     * 取消订单
     * @param int $order_id 订单ID
     * @param string $remark 操作备注
     * @param string $log_role 用户角色
     * @param string $log_user 用户名
     * @return boolean
     */
    public function changeOrderStatusCancel($order_id,$remark, $log_role, $log_user)
    {
        $order = Order::find()->where(['id'=>$order_id])->one();
        if($order->order_status !== OrderStatusEnum::ORDER_UNPAID) {
            return true;
        }
        $order_goods_list = OrderGoods::find()->select(['id','goods_id','goods_type','goods_num'])->where(['order_id'=>$order_id])->all();
        foreach ($order_goods_list as $goods) {
            //\Yii::$app->services->goods->updateGoodsStorageForOrder($goods->goods_id, $goods->goods_num, $goods->goods_type);
        }
        //更改订单状态
        $order->cancel_remark = $remark;

        if($log_role=='admin')
            $order->cancel_status = OrderStatusEnum::ORDER_CANCEL_YES;
//        $order->seller_remark = $remark;
        $order->order_status = OrderStatusEnum::ORDER_CANCEL;
        $order->save(false);

        //解冻购物卡
        CardService::deFrozen($order_id);

        //订单日志
        OrderLogService::cancel($order);
    }

    public function changeOrderStatusRefund($order_id,$remark, $log_role, $log_user)
    {
        $order = Order::find()->where(['id'=>$order_id])->one();
        if($order->order_status <= OrderStatusEnum::ORDER_UNPAID) {
            return true;
        }
        $order_goods_list = OrderGoods::find()->select(['id','goods_id','goods_type','goods_num'])->where(['order_id'=>$order_id])->all();
        foreach ($order_goods_list as $goods) {
            //\Yii::$app->services->goods->updateGoodsStorageForOrder($goods->goods_id, $goods->goods_num, $goods->goods_type);
        }
        //更改订单状态
//        $order->cancel_remark = $remark;
//        $order->seller_remark = $remark;
        $order->order_status = OrderStatusEnum::ORDER_CANCEL;
        $order->refund_remark = $remark;
        $order->refund_status = 1;
        $order->save(false);
        //解冻购物卡
        CardService::deFrozen($order_id);

        //退款通知
        \Yii::$app->services->order->sendOrderNotification($order->id);

        //订单日志
        //$this->addOrderLog($order_id, $remark, $log_role, $log_user,$order->order_status);
        OrderLogService::refund($order);

    }

    public function changeOrderStatusAudit($order_id, $status, $remark)
    {
        $model = Order::findOne($order_id);

        if(!$model) {
            throw new \Exception(sprintf('[%d]数据未找到', $order_id));
        }

        //判断订单是否已付款状态
        if($model->order_status !== OrderStatusEnum::ORDER_PAID) {
            throw new \Exception(sprintf('[%d]不是已付款状态', $order_id));
        }

        $audit_status = $model->audit_status;
        if($status==OrderStatusEnum::ORDER_AUDIT_NO) {
            //订单审核不通过
            $model->audit_status = OrderStatusEnum::ORDER_AUDIT_NO;
            $model->audit_remark = $remark;
            //$model->status = AuditStatusEnum::UNPASS;
            //$model->order_status = OrderStatusEnum::ORDER_CONFIRM;//已审核，代发货
        }
        else {
            $isPay = false;

            //查验订单是否有多笔支付
            foreach ($model->paylogs as $paylog) {

                if($paylog->pay_status != PayStatusEnum::PAID) {
                    continue;
                }

                //购物卡支付，电汇支付
                if(in_array($paylog->pay_type, [
                        PayEnum::PAY_TYPE_CARD,
                        PayEnum::PAY_TYPE_WIRE_TRANSFER,
                        PayEnum::PAY_TYPE_ALI,
                        PayEnum::PAY_TYPE_WECHAT
                    ])) {
                    $isPay = true;
                    continue;
                }

                //获取支付类
                $pay = \Yii::$app->services->pay->getPayByType($paylog->pay_type);

                /**
                 * @var $state AbstractResponse
                 */
                $state = $pay->verify(['model'=>$paylog, 'isVerify'=>true]);

                //当前这笔订单的付款
                if($paylog->out_trade_no == $model->pay_sn) {
                    $isPay = $state->isPaid();
                    continue;
                }
                elseif(in_array($state->getCode(), ['null'])) {
                    throw new \Exception(sprintf('[%d]订单支付[%s]验证出错，请重试', $order_id, $paylog->out_trade_no));
                }
                elseif(in_array($state->getCode(), ['completed','pending', 'payer']) || $paylog->pay_status==PayStatusEnum::PAID) {
                    throw new \Exception(sprintf('[%d]订单存在多笔支付[%s]', $order_id, $paylog->out_trade_no));
                }
//                elseif($state->isPaid()) {
//                    throw new \Exception(sprintf('[%d]订单存在多笔支付[%s]', $order_id, $paylog->out_trade_no));
//                }
            }

            if(!$isPay) {
                throw new \Exception(sprintf('[%d]订单支付状态验证失败', $order_id));
            }

            //更新订单审核状态
            $model->status = AuditStatusEnum::PASS;
            $model->order_status = OrderStatusEnum::ORDER_CONFIRM;//已审核，代发货
            $model->audit_status = OrderStatusEnum::ORDER_AUDIT_YES;
            $model->audit_remark = $remark;
        }

        if(false  === $model->save()) {
            throw new \Exception($this->getError($model));
        }

        //订单日志
        OrderLogService::audit($model, [[
            'audit_status'=>OrderStatusEnum::getValue($model->audit_status, 'auditStatus')
        ], [
            'audit_status'=>OrderStatusEnum::getValue($audit_status, 'auditStatus')
        ]]);

    }
    
    /**
     * 同步订单 手机号
     * @param int $order_id 订单ID
     * @throws \Exception
     */
    public function syncPayPalPhone($order_id)
    {
        $order = Order::find()->where(['id'=>$order_id])->one();
        if(!$order) {
            throw new \Exception('订单查询失败,order_id='.$order_id);
        }
        
        $payLog = PayLog::find()->where(['order_sn'=>$order->order_sn,'pay_type'=>[PayEnum::PAY_TYPE_PAYPAL,PayEnum::PAY_TYPE_PAYPAL_1],'pay_status'=>PayStatusEnum::PAID])->one();
        if(!$payLog) {
            throw new \Exception('非PayPal支付');
        }
        
        $pay = \Yii::$app->services->pay->getPayByType($payLog->pay_type);
        /**
         * @var $payment Payment
         */
        $payment = $pay->getPayment(['model'=>$payLog]);

        $payer = $payment->getPayer()->getPayerInfo();
        
        $phone = $payer->getPhone();
        $conuntryCode = $payer->getCountryCode();
        $mobileCodeMap = ['HK'=>'+852','C2'=>'+86','MO'=>'+853','TW'=>'+886','CN'=>'+86','US'=>'+1'];
        if($phone) {
            $address = OrderAddress::findOne(['order_id'=>$order->id]);
            $address->mobile = $phone;   
            $address->mobile_code = $mobileCodeMap[$conuntryCode]??'';
            if(!$address->save()) {
                throw new \Exception($this->getError($address));
            }
        }
        else {
            throw new \Exception('PayPal手机号为空');
        }
    }

    
}