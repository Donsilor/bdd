<?php


namespace services\common;


use common\components\Service;
use common\enums\AreaEnum;
use common\enums\NotifyContactsEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayStatusEnum;
use common\helpers\AmountHelper;
use common\models\common\EmailLog;
use common\models\common\NotifyContacts;
use common\models\common\PayLog;
use common\models\order\OrderTourist;
use Yii;
use common\models\order\Order;

class NotifyContactsService extends Service
{
    //获取被通知人信息
    public function getNotifyContactsInfo($typeId, $areaId=null)
    {
        $data = NotifyContacts::find()->where(['type_id'=>$typeId])->all();

        $mobiles = [];
        $emails = [];
        foreach ($data as $datum) {
            if(!is_array($datum['area_attach']) || $areaId && !in_array($areaId, $datum['area_attach'])) {
                continue;
            }

            if($datum['email_switch']) {
                $emails[] = $datum['email'];
            }
            if($datum['mobile_switch']) {
                $mobiles[] = $datum['mobile'];
            }
        }

        return ['mobiles'=>$mobiles, 'emails'=>$emails];
    }

    //提交发送
    public function submitSend($typeId, $params)
    {
        $ipAreaId = null;
        if(isset($params['ip_area_id'])) {
            $ipAreaId = $params['ip_area_id'];
        }

        $notifyContacts = $this->getNotifyContactsInfo($typeId, $ipAreaId);

        $usage = NotifyContactsEnum::getValue($typeId, 'usageForEmail');
        foreach ($notifyContacts['emails'] as $email) {
            \Yii::$app->services->mailer->queue(false)->send($email, $usage, $params, $this->language);
        }

        $usage = NotifyContactsEnum::getValue($typeId, 'usageForMobile');
        foreach ($notifyContacts['mobiles'] as $mobile) {
            \Yii::$app->services->sms->queue(false)->send($mobile, $usage, $params);
        }
    }

    //订单付款成功时执行
    public function orderPaySuccess($orderSn)
    {
        $order = Order::findOne(['order_sn'=>$orderSn]);

        if(!$order) {
            return;
        }

        $params = [];
        $params['code'] = $order->id;
        $params['order_sn'] = $order->order_sn;
        $params['create_date'] = date('Y-m-d H:i:s', $order->created_at);
        $params['order_status'] = OrderStatusEnum::getValue($order->order_status);
        $params['pay_status'] = PayStatusEnum::getValue($order->payment_status);
        $params['order_amount'] = AmountHelper::outputAmount($order->account->order_amount, 2, $order->account->currency);
        $params['ip_area_id'] = $order->ip_area_id;
        $params['area'] = AreaEnum::getValue($order->ip_area_id);

        return $this->submitSend(NotifyContactsEnum::TYPE_ORDER, $params);
    }


    //创建订单时执行（同IP重复下单）
    public function createOrder($orderSn)
    {
        $order = Order::findOne(['order_sn'=>$orderSn]);

        if(!$order) {
            return;
        }

        $where = ['and'];
        $where[] = ['=', 'ip', $order->ip];
        $where[] = ['>', 'created_at', time()-86400*7];

        $count = Order::find()->where($where)->count('id');

        $configCount = (int)Yii::$app->debris->config('order_notify_contacts_create_count');

        //同IP重复下单次数判断
        if($configCount<=$count) {
            $params = [];
            $params['code'] = $order->id;
            $params['order_sn'] = $order->order_sn;
            $params['create_date'] = date('Y-m-d H:i:s', $order->created_at);
            $params['order_status'] = OrderStatusEnum::getValue($order->order_status);
            $params['pay_status'] = PayStatusEnum::getValue($order->payment_status);
            $params['order_amount'] = AmountHelper::outputAmount($order->account->order_amount, 2, $order->account->currency);
            $params['action'] = __FUNCTION__;
            $params['count'] = $count;

            return $this->submitSend(NotifyContactsEnum::TYPE_ABNORMAL, $params);
        }
    }

    //游客创建订单时执行
    public function touristCreateOrder($orderSn)
    {
        //同IP重复下单
        $order = OrderTourist::findOne(['order_sn'=>$orderSn]);

        if(!$order) {
            return;
        }

        $where = ['and'];
        $where[] = ['=', 'ip', $order->ip];
        $where[] = ['>', 'created_at', time()-86400*7];

        $count = OrderTourist::find()->where($where)->count('id');

        $configCount = (int)Yii::$app->debris->config('order_notify_contacts_create_count');

        //同IP重复下单次数判断
        if($configCount < $count) {
            $params = [];
            $params['code'] = $order->id;
            $params['order_sn'] = $order->order_sn;
            $params['create_date'] = date('Y-m-d H:i:s', $order->created_at);
            $params['order_status'] = OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID);
            $params['pay_status'] = PayStatusEnum::getValue(PayStatusEnum::UNPAID);
            $params['order_amount'] = AmountHelper::outputAmount($order->order_amount, 2, $order->currency);
            $params['action'] = __FUNCTION__;
            $params['count'] = $count;

            return $this->submitSend(NotifyContactsEnum::TYPE_ABNORMAL, $params);
        }
    }


    //创建订单付款时执行
    public function createOrderPay($orderSn)
    {
        //同IP重复付款
        $order = Order::findOne(['order_sn'=>$orderSn]);

        if(!$order) {
            return;
        }

        $where = ['and'];
        $where[] = ['=', 'order_sn', $order->order_sn];
        $where[] = ['>', 'created_at', time()-86400*7];

        $count = PayLog::find()->where($where)->count('id');

        $configCount = (int)Yii::$app->debris->config('order_notify_contacts_pay_count');

        //同IP重复支付次数判断
        if($configCount<$count) {
            $params = [];
            $params['code'] = $order->id;
            $params['order_sn'] = $order->order_sn;
            $params['create_date'] = date('Y-m-d H:i:s', $order->created_at);
            $params['order_status'] = OrderStatusEnum::getValue($order->order_status);
            $params['pay_status'] = PayStatusEnum::getValue($order->payment_status);
            $params['order_amount'] = AmountHelper::outputAmount($order->account->order_amount, 2, $order->account->currency);
            $params['action'] = __FUNCTION__;
            $params['count'] = $count;

            return $this->submitSend(NotifyContactsEnum::TYPE_ABNORMAL, $params);
        }
    }

    //创建游客订单付款时执行
    public function createTouristOrderPay($orderSn)
    {
        //同IP重复付款
        $order = OrderTourist::findOne(['order_sn'=>$orderSn]);

        if(!$order) {
            return;
        }

        $where = ['and'];
        $where[] = ['=', 'order_sn', $order->order_sn];
        $where[] = ['>', 'created_at', time()-86400*7];

        $count = PayLog::find()->where($where)->count('id');

        $configCount = (int)Yii::$app->debris->config('order_notify_contacts_pay_count');

        //同IP重复支付次数判断
        if($configCount < $count) {
            $params = [];
            $params['code'] = $order->id;
            $params['order_sn'] = $order->order_sn;
            $params['create_date'] = date('Y-m-d H:i:s', $order->created_at);
            $params['order_status'] = OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID);
            $params['pay_status'] = PayStatusEnum::getValue(PayStatusEnum::UNPAID);
            $params['order_amount'] = AmountHelper::outputAmount($order->order_amount, 2, $order->currency);
            $params['action'] = __FUNCTION__;
            $params['count'] = $count;

            return $this->submitSend(NotifyContactsEnum::TYPE_ABNORMAL, $params);
        }
    }

}