<?php

namespace services\order;

use common\models\market\MarketCouponDetails;
use services\market\CouponService;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\order\OrderCart;
use common\models\order\OrderInvoice;
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
            if($goods['coupon_id'] && $goods['coupon']['discount']) {
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
            
            //\Yii::$app->services->goods->updateGoodsStorageForOrder($orderGoods->goods_id,-$orderGoods->goods_num, $orderGoods->goods_type);
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

        //清空购物车
        OrderCart::deleteAll(['id'=>$cart_ids,'member_id'=>$buyer_id]);
        
        return [
            "currency" => $currency,
            "order_amount"=> $order_amount,
            "pay_amount"=> $orderAccountTax['pay_amount'],
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
        $order->seller_remark = $remark;
        $order->order_status = OrderStatusEnum::ORDER_CANCEL;
        $order->save(false);
        //解冻购物卡
        CardService::deFrozen($order_id);
        //订单日志
        //$this->addOrderLog($order_id, $remark, $log_role, $log_user,$order->order_status);
        OrderLogService::cancel($order);
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