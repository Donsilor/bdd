<?php

namespace services\order;

use common\components\Service;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\PayStatusEnum;
use common\models\member\Member;
use common\models\order\Order;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\models\order\OrderCart;
use common\models\order\OrderGoods;
use common\models\order\OrderInvoice;
use common\models\order\OrderTourist;
use common\models\order\OrderTouristDetails;
use common\models\order\OrderTouristInvoice;
use PayPal\Api\PayerInfo;
use PayPal\Api\Payment;
use PayPal\Api\ShippingAddress;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class OrderTouristService
 * @package services\order
 */
class OrderTouristService extends OrderBaseService
{


    /**
     * @param $cartList
     */
    public function createOrder($cartList, $invoice_info,$order_from)
    {
        //IP区域ID与地址
        list($ip_area_id, $ip_location) = \Yii::$app->ipLocation->getLocation(\Yii::$app->request->userIP);

        $goods_amount = 0;
        $details = [];
        foreach ($cartList as $item) {
            $goods = \Yii::$app->services->goods->getGoodsInfo($item['goods_id'], $item['goods_type']);
            if(empty($goods) || $goods['status'] != 1) {
                throw new UnprocessableEntityHttpException("订单中部分商品已下架,请重新下单");
            }
            //验证库存
            if($goods['goods_storage'] <1) {
                throw new UnprocessableEntityHttpException("订单中部分商品已下架,请重新下单");
            }
            //商品价格
            $sale_price = $this->exchangeAmount($goods['sale_price'],0)*$item['goods_num'];
            $goods_amount += $sale_price;

            $detail = new OrderTouristDetails();
            $detail->style_id = $goods['style_id'];//商品ID
            $detail->goods_id = $item['goods_id'];//商品ID
            $detail->goods_sn = $goods['goods_sn'];//商品编号
            $detail->goods_type = $goods['type_id'];//产品线
            $detail->goods_name = $goods['goods_name'];//价格
            $detail->goods_price = $sale_price;//价格
            $detail->goods_num = $item['goods_num'];//数量
            $detail->goods_image = $goods['goods_image'];//商品图片
            $detail->promotions_id = 0;//$item['promotions_id'];//促销活动ID
            $detail->group_id = $item['group_id'];//组ID
            $detail->group_type = $item['group_type'];//分组类型
            $detail->goods_spec = $goods['goods_spec'];//商品规格
            $detail->goods_attr = $goods['goods_attr'];//商品规格

            $details[] = $detail;
        }

        //金额
        $discount_amount = 0;//优惠金额
        $shipping_fee = 0;//运费
        $tax_fee = 0;//税费
        $safe_fee = 0;//保险费
        $other_fee = 0;//其他费用

        $order_amount = $goods_amount + $shipping_fee + $tax_fee + $safe_fee + $other_fee;//订单总金额

        //保存订单信息
        $order = new OrderTourist();
        $order->order_sn  = $this->createOrderSn();//生成订单号
//        $order->merchant_id = null;//商铺ID
        $order->store_id = null;//店铺ID
        $order->tourist_key = null;//游客的KEY
        $order->order_from = $order_from; 
        $order->order_amount = $order_amount;//订单金额
        $order->goods_amount = $goods_amount;//商品总金额
        $order->discount_amount = $discount_amount;//优惠金额
        $order->pay_amount = 0;//实际支付金额
        $order->refund_amount = 0;//退款金额
        $order->shipping_fee = $shipping_fee;//运费
        $order->tax_fee = $tax_fee;//税费
        $order->safe_fee = $safe_fee;//保险费
        $order->other_fee = $other_fee;//附加费

        $order->currency = $this->getCurrency();//货币
        $order->exchange_rate = $this->getExchangeRate();//汇率
        $order->language   = $this->getLanguage();//语言
        $order->ip = \Yii::$app->request->userIP;  //用户下单ip

        $order->ip_location = $ip_location;
        $order->ip_area_id = $ip_area_id;

        $order->status = OrderTouristStatusEnum::ORDER_UNPAID;  //状态

        //保存订单
        if(false === $order->save()) {
            throw new UnprocessableEntityHttpException($this->getError($order));
        }

        foreach ($details as $detail) {
            //订单ID
            $detail->order_tourist_id = $order->id;

            //保存订单详情
            if(false === $detail->save()) {
                throw new UnprocessableEntityHttpException($this->getError($detail));
            }
        }

        //如果有发票信息
        if(!empty($invoice_info)) {
            $invoice = new OrderTouristInvoice();
            $invoice->attributes = $invoice_info;
            $invoice->order_tourist_id = $order->id;;
            if(false === $invoice->save()) {
                throw new UnprocessableEntityHttpException($this->getError($invoice));
            }
        }

        return $order->id;
    }

    /**
     * 同步一个游客订单到标准订单
     * @param $orderTourist
     * @param $payLog
     * @throws UnprocessableEntityHttpException|void
     */
    public function sync($orderTourist, $payLog) {
        
        $logMessage = "订单号：".$payLog->order_sn."<br/>支付编号：".$payLog->out_trade_no;
        
        //IP区域ID与地址
        list($ip_area_id, $ip_location) = \Yii::$app->ipLocation->getLocation($orderTourist->ip);
        //获取支付信息
        $pay = \Yii::$app->services->pay->getPayByType($payLog->pay_type);
        $payment = $pay->getPayment(['model'=>$payLog]);
        if($payment) {
            $logMessage .= "<br/>同步状态：初始化";
            \Yii::$app->services->actionLog->create('同步游客订单',$logMessage,$payment->toArray());
        } else {
            $logMessage .= "<br/>同步状态：支付对象失败";
            \Yii::$app->services->actionLog->create('同步游客订单',$logMessage,$payLog->toArray());
        }
        /** @var PayerInfo $payerInfo */
        $payerInfo = $payment->getPayer()->getPayerInfo();

        /** @var ShippingAddress $shippingAddressInfo */
        $shippingAddressInfo = $payerInfo->getShippingAddress();

        //用户信息处理
        $username = '游客-'.$payerInfo->getPayerId();
        if(!($member = Member::findByUsername($username))) {
            //创建用户信息
            $member = new Member();
            $member->attributes = [
                'username' => '游客-'.$payerInfo->getPayerId(),
                'password_hash' => 'password_hash',
                'firstname' => $payerInfo->getFirstName(),
                'lastname' => $payerInfo->getLastName(),
                'realname' => $shippingAddressInfo->getRecipientName(),
                'email' => $payerInfo->getEmail(),
                'last_ip' => $orderTourist->ip,
                'first_ip' => $orderTourist->ip,
                'first_ip_location' => $ip_location,
//            'mobile' => $payerInfo->getPhone()
            ];
            if(false === $member->save()) {
                throw new UnprocessableEntityHttpException($this->getError($member));
            }
        }

        //订单信息
        $order = new Order();
        $order->attributes = [
            'merchant_id' => $orderTourist->merchant_id,
            'language' => $orderTourist->language,
            'order_sn' => $orderTourist->order_sn,
            'pay_sn' => $payLog->out_trade_no,
            'member_id' => $member->id,
            'payment_type' => $payLog->pay_type,
            'payment_status' => PayStatusEnum::PAID,
            'payment_time' => $payLog->pay_time,
//            'finished_time' => '',
//            'evaluation_status' => '',
//            'evaluation_again_status' => '',
            'order_status' => OrderStatusEnum::ORDER_PAID,
//            'refund_status' => '',
//            'express_id' => '',
//            'express_no' => '',
//            'delivery_status' => '',
//            'delivery_time' => '',
//            'receive_type' => '',
              'order_from' => $orderTourist->order_from,
//            'order_type' => '',
            'is_tourist' => 1,//游客订单
            'is_invoice' => empty($orderTourist->invoice)?0:1,//是否开发票
            'api_pay_time' => $payLog->pay_time,
//            'trade_no' => '',
            'buyer_remark' => '',
//            'seller_remark' => '',
//            'follower_id' => '',
//            'followed_time' => '',
//            'followed_status' => '',
            'ip' => $orderTourist->ip,
            'ip_location' => $ip_location,
            'ip_area_id' => $ip_area_id,
//            'status' => '',
        ];
        if(false === $order->save()) {
            throw new UnprocessableEntityHttpException($this->getError($order));
        }

        //订单地址信息
        $orderAddress = new OrderAddress();
        $orderAddress->attributes = [
            'order_id' => $order->id,
            'merchant_id' => $orderTourist->merchant_id,
            'member_id' => $member->id,
//            'country_id' => '',
//            'province_id' => '',
//            'city_id' => '',
            'firstname' => $payerInfo->getFirstName(),
            'lastname' => $payerInfo->getLastName(),
            'realname' => $shippingAddressInfo->getRecipientName(),
            'country_name' => $shippingAddressInfo->getCountryCode(),
            'province_name' => $shippingAddressInfo->getState(),
            'city_name' => $shippingAddressInfo->getCity(),
            'address_details' => $shippingAddressInfo->getLine1() . ' ' . $shippingAddressInfo->getLine2(),
            'zip_code' => $shippingAddressInfo->getPostalCode(),
            'mobile' => $payerInfo->getPhone(),
//            'mobile_code' => '',
            'email' => $payerInfo->getEmail(),
        ];
        if(false === $orderAddress->save()) {
            throw new UnprocessableEntityHttpException($this->getError($orderAddress));
        }

        //订单支付信息
        $orderAccount = new OrderAccount();
        $orderAccount->attributes = [
            'order_id' => $order->id,
            'merchant_id' => $orderTourist->merchant_id,
            'order_amount' => $orderTourist->order_amount,
            'goods_amount' => $orderTourist->goods_amount,
            'discount_amount' => $orderTourist->discount_amount,
            'pay_amount' => $orderTourist->pay_amount,
            'refund_amount' => $orderTourist->refund_amount,
            'shipping_fee' => $orderTourist->shipping_fee,
            'tax_fee' => $orderTourist->tax_fee,
            'safe_fee' => $orderTourist->safe_fee,
            'other_fee' => $orderTourist->other_fee,
            'exchange_rate' => $orderTourist->exchange_rate,
            'currency' => $orderTourist->currency,
        ];
        if(false === $orderAccount->save()) {
            throw new UnprocessableEntityHttpException($this->getError($orderAccount));
        }

        //
        $languages = $this->getLanguages();

        //保存订单商品信息
        foreach ($orderTourist->details as $detail) {
            $orderTouristDetails = new OrderGoods();
            $orderTouristDetails->attributes = [
                'merchant_id' => $orderTourist->merchant_id,
                'order_id' => $order->id,
                'style_id' => $detail->style_id,
                'goods_id' => $detail->goods_id,
                'goods_sn' => $detail->goods_sn,
                'goods_type' => $detail->goods_type,
                'goods_name' => $detail->goods_name,
                'goods_price' => $detail->goods_price,
                'goods_num' => $detail->goods_num,
                'goods_image' => $detail->goods_image,
                'goods_pay_price' => $detail->goods_price,
                'promotions_id' => $detail->promotions_id,
                'goods_spec' => $detail->goods_spec,
                'goods_attr' => $detail->goods_attr,
                'currency' => $orderTourist->currency,
                'exchange_rate' => $orderTourist->exchange_rate,
            ];
            if(false === $orderTouristDetails->save()) {
                throw new UnprocessableEntityHttpException($this->getError($orderTouristDetails));
            }

            foreach (array_keys($languages) as $language) {
                $goods = \Yii::$app->services->goods->getGoodsInfo($orderTouristDetails->goods_id,$orderTouristDetails->goods_type,false,$language);
                if(empty($goods) || $goods['status'] != 1) {
                    continue;
                }

                //验证库存
                if($orderTouristDetails->goods_num>$goods['goods_storage']) {
                    throw new UnprocessableEntityHttpException(sprintf("[%s]商品库存不足", $goods['goods_sn']));
                }

                $langModel = $orderTouristDetails->langModel();
                $langModel->master_id = $orderTouristDetails->id;
                $langModel->language = $language;
                $langModel->goods_name = $goods['goods_name'];
                $langModel->goods_body = $goods['goods_body'];
                if(false === $langModel->save()){
                    throw new UnprocessableEntityHttpException($this->getError($langModel));
                }
            }
        }

        //如果有发票信息
        if(!empty($orderTourist->invoice)) {
            $invoice = new OrderInvoice();
            $invoice->attributes = $orderTourist->invoice->toArray();
            $invoice->order_id   = $order->id;
            if(false === $invoice->save()) {
                throw new UnprocessableEntityHttpException($this->getError($invoice));
            }
        }

        //订单日志
        $log_msg = "创建订单,订单编号：".$order->order_sn;
        $log_role = 'buyer';
        $log_user = $member->username;
        $this->addOrderLog($order->id, $log_msg, $log_role, $log_user,$order->order_status);

        //订单发送邮件
        $this->sendOrderNotification($order->id);
    }
}