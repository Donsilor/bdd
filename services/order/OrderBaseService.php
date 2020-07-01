<?php


namespace services\order;


use common\components\Service;
use common\enums\CurrencyEnum;
use common\enums\ExpressEnum;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\models\common\DeliveryTime;
use common\models\common\EmailLog;
use common\models\common\SmsLog;
use common\models\market\MarketCard;
use common\models\order\Order;
use common\models\order\OrderLog;
use common\models\order\OrderTourist;
use common\models\order\OrderTouristDetails;
use services\goods\TypeService;
use services\market\CouponService;
use yii\web\UnprocessableEntityHttpException;

class OrderBaseService extends Service
{
    /**
     * 发送订单邮件通知
     * @param unknown $order_id
     */
    public function sendOrderNotification($order_id)
    {
        $order = Order::find()->where(['or',['id'=>$order_id],['order_sn'=>$order_id]])->one();

        if($order->is_tourist) {
            if(RegularHelper::verify('email',$order->member->email)) {
                if($order->refund_status) {
                    //退款通知
                    $usage = EmailLog::$orderStatusMap['refund'] ?? '';
                }
                else {
                    $usage = EmailLog::$orderStatusMap[$order->order_status] ?? '';
                }
                if($usage && $order->address->email) {
                    \Yii::$app->services->mailer->queue(true)->send($order->address->email,$usage,['code'=>$order->id],$order->language);
                }
            }
        }elseif(RegularHelper::verify('email',$order->member->username)) {
            if($order->refund_status) {
                //退款通知
                $usage = EmailLog::$orderStatusMap['refund'] ?? '';
            }
            else {
                $usage = EmailLog::$orderStatusMap[$order->order_status] ?? '';
            }
            if($usage && $order->address->email) {
                \Yii::$app->services->mailer->queue(true)->send($order->address->email,$usage,['code'=>$order->id],$order->language);
            }
        }elseif($order->address->mobile){
            if($order->order_status == OrderStatusEnum::ORDER_SEND) {
                $params = [
                    'code' =>$order->id,
                    'order_sn' =>$order->order_sn,
                    'express_name' => \Yii::$app->services->express->getExressName($order->express_id),
                    'express_no' =>$order->express_no,
                    'company_name'=>'BDD Co.',
                    'company_email' => 'admin@bddco.com'
                ];
                if($order->refund_status) {
                    //退款通知短信
                    $usage = SmsLog::USAGE_ORDER_REFUND_NOTICE;
                }
                else {
                    $usage = SmsLog::USAGE_ORDER_SEND;
                }
                \Yii::$app->services->sms->queue(true)->send($order->address->mobile,$usage,$params,$order->language);
            }
            elseif($order->refund_status) {
                $params = [
                    'code' =>$order->id,
                    'order_sn' =>$order->order_sn,
                    'express_name' => \Yii::$app->services->express->getExressName($order->express_id),
                    'express_no' =>$order->express_no,
                    'company_name'=>'BDD Co.',
                    'company_email' => 'admin@bddco.com'
                ];
                //退款通知短信
                $usage = SmsLog::USAGE_ORDER_REFUND_NOTICE;
                \Yii::$app->services->sms->queue(true)->send($order->address->mobile,$usage,$params);
            }
        }
    }

    /**
     * 添加订单日志
     * @param unknown $order_id
     * @param unknown $log_msg
     * @param unknown $log_role
     * @param unknown $log_user
     * @param string $order_status
     */
    public function addOrderLog($order_id, $log_msg, $log_role, $log_user, $order_status = false)
    {
        if($order_status === false) {
            $order = Order::find()->select(['id','order_status'])->where(['id'=>$order_id])->one();
            $order_status = $order->order_status ?? 0;
        }
        $log = new OrderLog();
        $log->order_id = $order_id;
        $log->log_msg = $log_msg;
        $log->log_role = $log_role;
        $log->log_user = $log_user;
        $log->order_status = $order_status;
        $log->log_time = time();
        $log->save(false);
    }

    /**
     * 生成订单号
     * @param unknown $order_id
     * @param string $prefix
     */
    public function createOrderSn($prefix = 'BDD')
    {
        return $prefix.date('Ymd').mt_rand(3,9).str_pad(mt_rand(1, 99999),6,'1',STR_PAD_LEFT);
    }

    /**
     * @param array $cartList 购物车数据计算金额税费
     * @param int $coupon_id 活动优惠券ID
     * @param array $cards 活动优惠券ID
     * @return array
     * @throws UnprocessableEntityHttpException
     */
    public function getCartAccountTax($cartList, $coupon_id=0, $cards = [])
    {
        $orderGoodsList = [];

        //产品线金额
        $goodsTypeAmounts = [];

        //端口总价
        $goods_amount = 0;

        //折扣优惠总金额
        $discounts_amount = 0;

        //优惠券优惠金额
        $coupons_amount = 0;

        foreach ($cartList as $item) {
            $goods = \Yii::$app->services->goods->getGoodsInfo($item['goods_id'], $item['goods_type']);
            if(empty($goods) || $goods['status'] != StatusEnum::ENABLED) {
                continue;
            }

            //商品价格
            $sale_price = intval($this->exchangeAmount($goods['sale_price'],0));

            $orderGoods = [];
            $orderGoods['goods_id'] = $item['goods_id'];//商品ID
            $orderGoods['goods_sn'] = $goods['goods_sn'];//商品编号
            $orderGoods['style_id'] = $goods['style_id'];//商品ID
            $orderGoods['style_sn'] = $goods['style_sn'];//款式编码
            $orderGoods['goods_name'] = $goods['goods_name'];//价格
            $orderGoods['goods_price'] = $sale_price;//单位价格
            $orderGoods['goods_pay_price'] = $sale_price;//实际支付价格
            $orderGoods['goods_num'] = $item['goods_num'];//数量
            $orderGoods['goods_type'] = $goods['type_id'];//产品线
            $orderGoods['goods_image'] = $goods['goods_image'];//商品图片
            $orderGoods['coupon_id'] = $item['coupon_id']??0;//活动折扣券ID（折扣需要提交此ID）

            $orderGoods['group_id'] = $item['group_id'];//组ID
            $orderGoods['group_type'] = $item['group_type'];//分组类型

            $orderGoods['goods_attr'] = $goods['goods_attr'];//商品规格
            $orderGoods['goods_spec'] = $goods['goods_spec'];//商品规格

            if(!empty($item['group_type']) && (int)$item['group_type']===1) {
                $goods['type_id'] = 0;
                $goods['style_id'] = 0;
                $orderGoods['coupon_id'] = 0;
            }

            //用于活动获取活动信息的接口
            $orderGoods['coupon'] = [
                'type_id' => $goods['type_id'],
                'style_id' => $goods['style_id'],
                'price' => $sale_price,
                'num' => $item['goods_num'],
            ];

            $orderGoodsList[] = $orderGoods;
        }

        //执行优惠券接口。
        $coupons = CouponService::getCouponByList($this->getAreaId(), $orderGoodsList, false);

        //最终使用优惠券信息
        $couponInfo = null;

        if($coupon_id) {
            if(!isset($coupons[$coupon_id])) {
                throw new UnprocessableEntityHttpException("优惠券已失效");
            }
            else {
                //最终使用优惠券信息
                $couponInfo = $coupons[$coupon_id];

                //优惠商品价格总和
                $couponInfo['price_sum'] = $couponInfo['price'];

                //优惠端口优惠金额总和
                $couponInfo['money_sum'] = $couponInfo['money'];

                //优惠券优惠总金额
                $coupons_amount = $couponInfo['money'];
            }
        }

        foreach ($orderGoodsList as &$orderGoods) {
            $goodsPrice = intval($orderGoods['goods_price']);

            if($orderGoods['coupon_id']!=0) {
                //如果使用折扣券
                if(!isset($orderGoods['coupon']['discount']) || $orderGoods['coupon']['discount']['coupon_id']!=$orderGoods['coupon_id']) {
                    throw new UnprocessableEntityHttpException("折扣已失效");
                }

                //最终使用折扣券信息
                $discountInfo = $orderGoods['coupon']['discount'];

                //商品支付的折扣后价格
                $orderGoods['goods_pay_price'] = $discountInfo['price'];

                //计算折扣优惠总金额
                $discounts_amount = bcadd($discounts_amount, bcsub($goodsPrice, $orderGoods['goods_pay_price'], 2), 2);
            }
            elseif($coupon_id && isset($orderGoods['coupon']['money']) && isset($orderGoods['coupon']['money'][$coupon_id])) {
                $couponInfo['price_sum'] = intval($couponInfo['price_sum'] - $goodsPrice);

                if($couponInfo['price_sum'] > 0.01) {
                    //商品优惠金额
                    $coupon_money = bcmul($goodsPrice/$couponInfo['price'], $couponInfo['money'], 2);

                    $couponInfo['money_sum'] = bcsub($couponInfo['money_sum'], $coupon_money, 2);
                }
                else {
                    //商品优惠金额
                    $coupon_money = $couponInfo['money_sum'];
                }

                //商品优惠券后金额
                $orderGoods['goods_pay_price'] = bcsub($orderGoods['goods_pay_price'], $coupon_money, 2);

                //此商品可以使用优惠券
                $orderGoods['coupon_id'] = $coupon_id;
            }

            //计算产品线金额
            if(!isset($goodsTypeAmounts[$orderGoods['goods_type']])) {
                $goodsTypeAmounts[$orderGoods['goods_type']] = $orderGoods['goods_pay_price'];
            }
            else {
                $goodsTypeAmounts[$orderGoods['goods_type']] = bcadd($goodsTypeAmounts[$orderGoods['goods_type']], $orderGoods['goods_pay_price'], 2);
            }
            $goods_amount = bcadd($goods_amount, $goodsPrice, 2);
        }

        //所有卡共用了多少金额
        $cardsUseAmount = 0;

        if(!empty($cards)) {
            foreach ($cards as &$card) {

                //状态，是否过期，是否有余额
                $where = ['and'];
                $where[] = [
                    'sn' => $card['sn'],
                    'status' => 1,
                ];
                $where[] = ['<=', 'start_time', time()];
                $where[] = ['>', 'end_time', time()];

                $cardInfo = MarketCard::find()->where($where)->one();

                //验证状态
                if(!$cardInfo || $cardInfo->balance==0) {
                    continue;
                }

                //验证有效期

                $balance = $this->exchangeAmount($cardInfo->balance, 2);

                if($balance==0) {
                    continue;
                }

                $cardUseAmount = 0;

                foreach ($goodsTypeAmounts as $goodsType => &$goodsTypeAmount) {
                    if(!empty($cardInfo->goods_type_attach) && in_array($goodsType, $cardInfo->goods_type_attach) && $goodsTypeAmount > 0) {
                        if($goodsTypeAmount >= $balance) {
                            //购物卡余额不足时
                            $cardUseAmount = bcadd($cardUseAmount, $balance, 2);
                            $goodsTypeAmount = bcsub($goodsTypeAmount, $balance, 2);
                            $balance = 0;
                        }
                        else {
                            $cardUseAmount = bcadd($cardUseAmount, $goodsTypeAmount, 2);
                            $balance = bcsub($balance, $goodsTypeAmount, 2);
                            $goodsTypeAmount = 0;
                        }
                    }
                }

                //转人民币,如果余额为0，直接使用人民币余额，避免小数出错
                if($balance==0) {
                    $cardUseAmountCny = $cardInfo->balance;
                }
                else {
                    $cardUseAmountCny = \Yii::$app->services->currency->exchangeAmount($cardUseAmount,2, CurrencyEnum::CNY, \Yii::$app->params['currency']);
                }

                $card['id'] = $cardInfo->id;
                $card['goodsTypeAttach'] = $cardInfo->goods_type_attach;

                $card['useAmount'] = $cardUseAmount;
                $card['useAmountCny'] = $cardUseAmountCny;
                $card['balance'] = $this->exchangeAmount($cardInfo->balance);
                $card['balanceCny'] = $cardInfo->balance;
                $card['amount'] = $this->exchangeAmount($cardInfo->amount);
                $card['amountCny'] = $cardInfo->amount;

                //产品线语言获取
                $goodsTypes = [];
                foreach (TypeService::getTypeList() as $key => $item) {
                    if(in_array($key, $card['goodsTypeAttach'])) {
                        $goodsTypes[$key] = $item;
                    }
                }
                $card['goodsTypes'] = $goodsTypes;

                //所有获物卡作用金额求和
                $cardsUseAmount = bcadd($cardsUseAmount, $cardUseAmount, 2);
            }
        }

        //金额
        $shipping_fee = 0;//运费
        $tax_fee = 0;//税费
        $safe_fee = 0;//保险费
        $other_fee = 0;//其他费用

        $order_amount = $goods_amount + $shipping_fee + $tax_fee + $safe_fee + $other_fee;//订单总金额

        //保存订单信息
        $result = [];

        $result['shipping_fee'] = $shipping_fee;//运费
        $result['order_amount'] = $order_amount;//订单金额
        $result['goods_amount'] = $goods_amount;//商品总金额
        $result['safe_fee'] = $safe_fee;//保险费
        $result['tax_fee'] = $tax_fee;//税费
        $result['discount_amount'] = $discounts_amount;//折扣优惠总金额
        $result['coupon_amount'] = $coupons_amount;//优惠券优惠总金额
        $result['card_amount'] = $cardsUseAmount;//购物卡使用金额
        $result['pay_amount'] = bcsub(bcsub(bcsub($result['order_amount'], $result['discount_amount'], 2), $result['coupon_amount'], 2) ,$result['card_amount'], 2);
        $result['currency'] = $this->getCurrency();//货币
        $result['exchange_rate'] = $this->getExchangeRate();//汇率
        $result['other_fee'] = $other_fee;//附加费

        $result['plan_days'] = '1-2';//$this->getDeliveryTimeByGoods($orderGoodsList);;
        $result['orderGoodsList'] = $orderGoodsList;
        $result['coupons'] = $coupons;
        $result['coupon'] = $coupons[$coupon_id]??[];
        $result['cards'] = $cards;

        return $result;
	}

    /**
     * 预计下单送达时间
     * @param unknown $goods_id  商品ID
     * @param unknown $quantity  变化数量
     * @param unknown $for_sale 销售
     */
    public function getDeliveryTimeByGoods($goods_list){
        $plan_days = '5-12';
        $area_id = $this->getAreaId();
        $model = DeliveryTime::find()
            ->where(['area_id' => $area_id, 'status' => StatusEnum::ENABLED])
            ->asArray()
            ->one();
        if(!$model){
            return $plan_days;
        }

        //判断是期货还是现货
        $delivery_type = 'stock_time';
        foreach ($goods_list as $goods) {
            //产品线是裸钻或者戒托的是期货
            if(in_array($goods['goods_type'],[15,12])){
                $delivery_type = 'futures_time';
                continue;
            }
            $goods_attr = json_decode($goods['goods_attr'],true);
            if(($goods_attr['12']??null) != '194') {
                $delivery_type = 'futures_time';
                continue;
            }
        }


        $plan_days = $model[$delivery_type] ? $model[$delivery_type] : $plan_days;
        return $plan_days;
    }
}