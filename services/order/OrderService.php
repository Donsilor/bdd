<?php

namespace services\order;

use common\models\market\MarketCouponDetails;
use common\models\order\OrderCart;
use common\models\order\OrderInvoice;
use services\market\CouponService;
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
     * @param int $coupon_details_id
     */
    public function createOrder($cart_ids,$buyer_id, $buyer_address_id, $order_info, $invoice_info, $coupon_details_id=0)
    {
        $coupon_id = 0;
        if($coupon_details_id) {
            $where = [
                'id' => $coupon_details_id,
                'owner_id' => $coupon_details_id,
            ];
            if($couponDetails = MarketCouponDetails::findOne($where)) {
                $coupon_id = $couponDetails->coupon_id;
            }
            else {
                throw new UnprocessableEntityHttpException("收货地址不能为空");
            }
        }

        $buyer = Member::find()->where(['id'=>$buyer_id])->one();

        $orderAccountTax = $this->getOrderAccountTax($cart_ids, $buyer_id, $buyer_address_id, $coupon_id);

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

        if($coupon_details_id) {
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
                'id' => $coupon_details_id,
                'owner_id' => $buyer_id,
                'coupon_status' => 1,
            ];

            MarketCouponDetails::updateAll($data, $where);
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
                if(empty($goods) || $goods['status'] != 1) {
                    continue;
                }

                //验证库存
                if($orderGoods->goods_num>$goods['goods_storage']) {
                    throw new UnprocessableEntityHttpException(sprintf("[%s]商品库存不足", $goods['goods_sn']));
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
     * @param int $coupon_id
     * @throws UnprocessableEntityHttpException
     * @return array
     */
    public function getOrderAccountTax($cart_ids, $buyer_id, $buyer_address_id, $coupon_id=0)
    {
        if(empty($cart_ids) || !is_array($cart_ids)) {
            throw new UnprocessableEntityHttpException("[cart_ids]参数错误");
        }

        $cartIds = [];
        $discounts = [];

        foreach ($cart_ids as $cart) {
            if(empty($cart['cart_id'])) {
                throw new UnprocessableEntityHttpException("[cart_id]参数错误");
            }

            $cartIds[] = $cart['cart_id'];

            if(!empty($cart['discount'])) {
                $discounts[$cart['cart_id']] = $cart['discount'];
            }
        }
        
        $cart_list = OrderCart::find()->where(['member_id'=>$buyer_id,'id'=>$cartIds])->all();

        if(empty($cart_list)) {
            throw new UnprocessableEntityHttpException("订单商品查询失败");
        }

        foreach ($cart_list as &$item) {
            $item['coupon_id'] = $discounts[$item['id']]??0;
        }

        $buyerAddress = Address::find()->where(['id'=>$buyer_address_id,'member_id'=>$buyer_id])->one();

        $result = $this->getCartAccountTax($cart_list, $coupon_id);

        $result['buyerAddress'] = $buyerAddress;

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
        //订单日志
        $this->addOrderLog($order_id, $remark, $log_role, $log_user,$order->order_status);
    }
          
    
}