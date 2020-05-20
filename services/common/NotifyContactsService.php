<?php


namespace services\common;


use common\components\Service;
use common\models\common\NotifyContacts;

class NotifyContactsService extends Service
{
    //获取被通知人信息
    static public function getNotifyContactsInfo($typeId)
    {
        $data = NotifyContacts::find()->where(['type_id'=>$typeId])->all();

        $mobiles = [];
        $emails = [];
        foreach ($data as $datum) {
            if($datum['email_switch']) {
                $emails[] = $datum['email'];
            }
            if($datum['mobile_switch']) {
                $mobiles[] = $datum['mobile'];
            }
        }

        return ['mobiles'=>$mobiles, 'emails'=>$emails];
    }

    //订单付款成功时执行
    static public function orderPaySuccess($orderSn)
    {
        var_dump($orderSn);
    }


    //创建订单时执行
    static public function createOrder($orderSn)
    {
        var_dump($orderSn);
    }

    //游客创建订单时执行
    static public function touristCreateOrder($orderSn)
    {
        var_dump($orderSn);
    }


    //创建订单付款时执行
    static public function createOrderPay($orderSn)
    {
        var_dump($orderSn);
    }

    //创建游客订单付款时执行
    static public function createTouristOrderPay($orderSn)
    {
        var_dump($orderSn);
    }

}