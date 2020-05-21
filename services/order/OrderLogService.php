<?php


namespace services\order;


use common\components\Service;
use common\enums\LanguageEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayStatusEnum;
use common\enums\WireTransferEnum;
use common\models\api\AccessToken;
use common\models\backend\Member;
use common\models\order\OrderLog;
use yii\console\Request;


class OrderLogService extends Service
{
    //客户提交电汇支付
    static public function wireTransferAudit($order, $data=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        $attr['data'][] = [
            '收款金额' => $order->wireTransfer->collection_amount,
            '收款凭证' => $order->wireTransfer->collection_voucher,
            '审核状态' => WireTransferEnum::getValue($order->wireTransfer->collection_status),
        ];

        //状态变更
        $attr['log_msg'] = '电汇审核';

        if($order->wireTransfer->collection_status==WireTransferEnum::CONFIRM) {
            $attr['log_msg'] .= "\r\n[订单状态]：“待付款”变更为“待发货”;";
            $attr['log_msg'] .= "\r\n[支付状态]：“未付款”变更为“已付款”;";
        }

        return self::log($attr);
    }
    //客户提交电汇支付
    static public function wireTransfer($order, $data=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        $attr['data'][] = [
            '收款账号' => $order->wireTransfer->account,
            '支付交易号' => $order->wireTransfer->payment_serial_number,
            '支付凭证' => $order->wireTransfer->payment_voucher,
        ];

        //状态变更
        $attr['log_msg'] = '客户提交电汇支付';
        $attr['log_msg'] .= "\r\n[订单状态]：“未支付”;";
        return self::log($attr);
    }

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
            'payment_status' => PayStatusEnum::getValue($order->payment_status),
        ];

        //状态变更
        $attr['log_msg'] = '订单创建';
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
        $attr['log_msg'] = '订单取消';
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CANCEL));
        $attr['log_msg'] .= sprintf("\r\n[订单备注]：“%s”;", $order['cancel_remark']);

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
        $attr['log_msg'] = '订单支付';
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
        $attr['log_msg'] = '订单审核';
        $attr['log_msg'] .= sprintf("\r\n[审核备注]：“%s“;", $order['audit_remark']);

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
        $attr['log_msg'] = '订单完成';
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
        $attr['log_msg'] = '订单发货';
        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CONFIRM), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_SEND));

        return self::log($attr);
    }

    //订单跟进
    static public function follower($order, $attr=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        if(empty($data)) {
            $follower = Member::findOne($order['follower_id']);
            $attr['data'][] = [
                'follower_id' => $follower['username']??$order['follower_id'],
                'seller_remark' => $order['seller_remark'],
//                'followed_time' => \Yii::$app->formatter->asDatetime($order['followed_time']),
            ];
        }
        else {
            $attr['data'] = $data;
        }

        //状态变更
        $attr['log_msg'] = '订单跟进';
//        $attr['log_msg'] .= sprintf("\r\n[订单状态]：“%s”变更为“%s“;", OrderStatusEnum::getValue(OrderStatusEnum::ORDER_UNPAID), OrderStatusEnum::getValue(OrderStatusEnum::ORDER_CANCEL));

        return self::log($attr);
    }

    static public function eleInvoiceSend($order, $attr=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        //状态变更
        $attr['log_msg'] = '电子发票发送';
        return self::log($attr);
    }

    static public function eleInvoiceEdit($order, $data=[])
    {
        $attr['action_name'] = strtoupper(__FUNCTION__);
        $attr['order_sn'] = $order['order_sn'];

        $express = \Yii::$app->services->express->getDropDown();

        foreach ($data[1] as $field => &$value) {
            if($field=='express_id') {
                if(!empty($data[0][$field])) {
                    $data[0][$field] = $express[$data[0][$field]] ?? $data[0][$field];
                }
                $value = $express[$value]??$value;
            }
            else if($field=='language') {
                if(!empty($data[0][$field])) {
                    $data[0][$field] = LanguageEnum::getValue($data[0][$field]??'');
                }
                $value = LanguageEnum::getValue($value);
            }

            if(strpos($field,'_date')!==false || strpos($field,'_time')!==false || strpos($field,'_at')!==false) {
                $value = $value?date('Y-m-d', $value):'';
            }
        }

        $attr['data'] = $data;

        //状态变更
        $attr['log_msg'] = '电子发票编辑';
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
        else {
            $attributes['log_role'] = 'tourist';
            $attributes['log_user'] = '游客';
        }

        $attributes['log_time'] = time();

        $attributes['data'] = $attributes['data']?:[[]];

        $log = new OrderLog();
        $log->setAttributes($attributes);

        return $log->save();
    }

}