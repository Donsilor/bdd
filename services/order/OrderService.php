<?php

namespace services\order;

use common\enums\PayEnum;
use common\models\common\PayLog;
use common\models\order\OrderCart;
use common\models\order\OrderInvoice;
use PayPal\Api\Payment;
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
     */
    public function createOrder($cart_ids,$buyer_id, $buyer_address_id, $order_info, $invoice_info)
    {
        $buyer = Member::find()->where(['id'=>$buyer_id])->one();
        
        if($cart_ids && !is_array($cart_ids)) {
            $cart_ids = explode(',', $cart_ids);
        }
        $orderAccountTax = $this->getOrderAccountTax($cart_ids, $buyer_id, $buyer_address_id);

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
        //订单商品       
        foreach ($orderGoodsList as $goods) {

            $orderGoods = new OrderGoods();
            $orderGoods->attributes = $goods;
            $orderGoods->order_id = $order->id;
            $orderGoods->exchange_rate = $exchange_rate;
            $orderGoods->currency = $currency;
            if(false === $orderGoods->save()){
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
        $log_msg = "创建订单,订单编号：".$order->order_sn;
        $log_role = 'buyer';
        $log_user = $buyer->username;
        $this->addOrderLog($order->id, $log_msg, $log_role, $log_user,$order->order_status);
        //清空购物车
        OrderCart::deleteAll(['id'=>$cart_ids,'member_id'=>$buyer_id]);
        
        return [
                "currency" => $currency,
                "order_amount"=> $order_amount,
                "order_id" => $order->id,
        ];
    }
    /**
     * 获取订单金额，税费信息
     * @param unknown $cart_ids
     * @param unknown $buyer_id
     * @param unknown $buyer_address_id
     * @param number $promotion_id
     * @throws UnprocessableEntityHttpException
     * @return array
     */
    public function getOrderAccountTax($cart_ids, $buyer_id, $buyer_address_id, $promotion_id = 0)
    {
        if($cart_ids && !is_array($cart_ids)) {
            $cart_ids = explode(',', $cart_ids);
        }
        $cart_list = OrderCart::find()->where(['member_id'=>$buyer_id,'id'=>$cart_ids])->all();
        if(empty($cart_list)) {
            throw new UnprocessableEntityHttpException("您的购物车商品不存在");
        }
        $buyerAddress = Address::find()->where(['id'=>$buyer_address_id,'member_id'=>$buyer_id])->one();
        $orderGoodsList = [];
        $goods_amount = 0;
        foreach ($cart_list as $cart) {
            
            $goods = \Yii::$app->services->goods->getGoodsInfo($cart->goods_id,$cart->goods_type,false);
            if(empty($goods) || $goods['status'] != StatusEnum::ENABLED) {
                continue;
            }
            $sale_price = $this->exchangeAmount($goods['sale_price'],0);
            $goods_amount += $sale_price;
            $orderGoodsList[] = [
                    'goods_id' => $cart->goods_id,
                    'goods_sn' => $goods['goods_sn'],
                    'style_id' => $goods['style_id'],
                    'style_sn' => $goods['style_sn'],
                    'goods_name' => $goods['goods_name'],
                    'goods_price' => $sale_price,
                    'goods_pay_price' => $sale_price,
                    'goods_num' => $cart->goods_num,
                    'goods_type' => $cart->goods_type,
                    'goods_image' => $goods['goods_image'],
                    'promotions_id' => 0,
                    'goods_attr' =>$goods['goods_attr'],
                    'goods_spec' =>$goods['goods_spec'],
            ];
        }
        //金额
        $discount_amount = 0;//优惠金额 
        $shipping_fee = 0;//运费 
        $tax_fee = 0;//税费 
        $safe_fee = 0;//保险费 
        $other_fee = 0;//其他费用 
        
        $order_amount = $goods_amount + $shipping_fee + $tax_fee + $safe_fee + $other_fee;//订单总金额 

        return [
                'shipping_fee' => $shipping_fee,
                'order_amount'  => $order_amount,           
                'goods_amount' => $goods_amount,
                'safe_fee' => $safe_fee,
                'tax_fee'  => $tax_fee,
                'discount_amount'=>$discount_amount,                
                'currency' => $this->getCurrency(),
                'exchange_rate'=>$this->getExchangeRate(),
                'plan_days' =>\Yii::$app->services->orderTourist->getDeliveryTimeByGoods($orderGoodsList),
                'buyerAddress'=>$buyerAddress,
                'orderGoodsList'=>$orderGoodsList,
        ];
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
        //订单日志
        $this->addOrderLog($order_id, $remark, $log_role, $log_user,$order->order_status);
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
        
        $payLog = PayLog::find()->where(['order_sn'=>$order->order_sn,'pay_type'=>PayEnum::PAY_TYPE_PAYPAL,'pay_status'=>PayStatusEnum::PAID])->one();
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