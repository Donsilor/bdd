<?php


namespace services\order;


use common\components\Service;
use common\enums\OrderStatusEnum;
use common\models\api\AccessToken;
use common\models\backend\Member;
use common\models\order\OrderLog;
use yii\console\Request;


class OrderLogService extends Service
{
    //创建订单
    static public function create($order)
    {
        //收货人+手机号+邮箱+ip归属城市 +客户留言
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];
        $attr['data'][] = [
            'realname' => $order->address->realname,
            'mobile' => $order->address->mobile_code.$order->address->mobile,
            'email' => $order->address->email,
            'ip_location' => $order['ip_location'],
            'buyer_remark' => $order['buyer_remark'],
            '测试' => 'fasdf',
        ];

        //状态变更
        $attr['log_msg'] = '订单创建，订单号：'.$attr['order_sn'];
        //$attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CANCEL));

        return self::log($attr);
    }



    //取消订单
    static public function cancel($order, $data=[])
    {
        $attr = [];
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];
        $attr['data'] = $data;

        //状态变更
        $attr['log_msg'] = '订单取消，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CANCEL));

        return self::log($attr);
    }

    //订单支付
    static public function pay($order, $data=[])
    {
        $attr = [];
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];
        $attr['data'] = $data;

        //状态变更
        $attr['log_msg'] = '订单支付，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_PAID));

        return self::log($attr);
    }

    //订单审核
    static public function audit($order, $data=[])
    {
        $attr = [];
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];
        $attr['data'] = $data;

        //状态变更
        $attr['log_msg'] = '订单审核，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[审核状态]：“未审核”变更为“已审核“;");

        return self::log($attr);
    }

    //订单完成
    static public function finish($order, $data=[])
    {
        $attr = [];
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];
        $attr['data'] = $data;

        //状态变更
        $attr['log_msg'] = '订单完成，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_SEND), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_FINISH));

        return self::log($attr);
    }

    //订单发货
    static public function deliver($order, $data=[])
    {
        $attr = [];
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        if(empty($data)) {
            $express = \Yii::$app->services->express->getDropDown();
            $attr['data'][] = [
                'express_id' => $express[$order['express_id']]??$order['express_id'],
                'express_no' => $order['express_no'],
                'delivery_time' => \Yii::$app->formatter->asDatetime($order['delivery_time']),
            ];
        }
        else {
            $attr['data'] = $data;
        }

        //状态变更
        $attr['log_msg'] = '订单发货，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CONFIRM), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_SEND));

        return self::log($attr);
    }

    //订单跟进
    static public function follower($order, $attr=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        //状态变更
        $attr['log_msg'] = '订单跟进，订单号：'.$attr['order_sn'];
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CANCEL));

        return self::log($attr);
    }

    static public function log($attributes)
    {
        if(\Yii::$app->request instanceof Request) {
            $attributes['log_role'] = 'system';
            $attributes['log_user'] = 'system';
        }
        elseif ($user = \Yii::$app->getUser()->identity) {
            if($user instanceof AccessToken) {
                $attributes['log_role'] = 'buyer';
                $attributes['log_user'] = $user->member->username;
            }
            elseif($user instanceof Member) {
                $attributes['log_role'] = 'admin';
                $attributes['log_user'] = $user->username;
            }
            else {
                $attributes['log_role'] = 'log_role';
                $attributes['log_user'] = 'log_user';
            }
        }

        $attributes['log_time'] = time();
        $attributes['data'] = $attributes['data']?:[[]];

        $log = new OrderLog();
        $log->setAttributes($attributes);

        return $log->save();
    }

}