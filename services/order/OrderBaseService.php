<?php


namespace services\order;


use common\components\Service;
use common\enums\ExpressEnum;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\helpers\RegularHelper;
use common\models\common\EmailLog;
use common\models\common\SmsLog;
use common\models\order\Order;
use common\models\order\OrderLog;
use common\models\order\OrderTourist;
use common\models\order\OrderTouristDetails;
use yii\web\UnprocessableEntityHttpException;

class OrderBaseService extends Service
{
    /**
     * 发送订单邮件通知
     * @param unknown $order_id
     */
    public function sendOrderNotification($order_id)
    {
        $order = Order::find()->where(['id'=>$order_id])->one();

        if($order->is_tourist) {
            if(RegularHelper::verify('email',$order->member->email)) {
                $usage = EmailLog::$orderStatusMap[$order->order_status] ?? '';
                if($usage && $order->address->email) {
                    \Yii::$app->services->mailer->send($order->address->email,$usage,['code'=>$order->id],$order->language);
                }
            }
            return;
        }

        if(RegularHelper::verify('email',$order->member->username)) {
            $usage = EmailLog::$orderStatusMap[$order->order_status] ?? '';
            if($usage && $order->address->email) {
                \Yii::$app->services->mailer->send($order->address->email,$usage,['code'=>$order->id],$order->language);
            }
        }else if($order->address->mobile){
            if($order->order_status == OrderStatusEnum::ORDER_SEND) {
                $params = [
                    'code' =>$order->id,
                    'express_name' => ExpressEnum::getValue($order->express_id),
                    'express_no' =>$order->express_no,
                    'company_name'=>'BDD Co.',
                    'company_email' => 'admin@bddco.com'
                ];
                \Yii::$app->services->sms->send($order->address->mobile,SmsLog::USAGE_ORDER_SEND,$params,$order->language);
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
     * @return array
     */
    public function getCartAccountTax($cartList)
    {
        $goods_amount = 0;
        $details = [];
        foreach ($cartList as $item) {
            $goods = \Yii::$app->services->goods->getGoodsInfo($item['goods_id'], $item['goods_type']);

            //商品价格
            $sale_price = $this->exchangeAmount($goods['sale_price']*$item['goods_num']);
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
            $detail->goods_spec = json_encode($goods['goods_spec']);//商品规格
            $detail->goods_attr = $goods['goods_attr'];//商品规格

            $details[] = $detail->toArray();
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

        $order->order_amount = $order_amount;//订单金额
        $order->goods_amount = $goods_amount;//商品总金额
        $order->discount_amount = $discount_amount;//优惠金额
        $order->shipping_fee = $shipping_fee;//运费
        $order->tax_fee = $tax_fee;//税费
        $order->safe_fee = $safe_fee;//保险费
        $order->other_fee = $other_fee;//附加费

        $order->currency = $this->getCurrency();//货币
        $order->exchange_rate = $this->getExchangeRate();//汇率
        $order->language   = $this->getLanguage();//语言

        $orderInfo = $order->toArray();
        $orderInfo['details'] = $details;

        return $orderInfo;
    }
}