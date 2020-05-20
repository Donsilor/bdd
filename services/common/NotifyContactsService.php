<?php


namespace services\common;


use common\components\Service;

class NotifyContactsService extends Service
{
    //订单付款成功
    static public function orderPaySuccess($orderSn, $orderSn2)
    {
        var_dump($orderSn);
        var_dump($orderSn2);
    }

    //创建订单
    static public function createOrder($orderSn)
    {
        var_dump($orderSn);
    }

    //付款
    static public function orderPay($orderSn)
    {
        var_dump($orderSn);
    }


    //游客创建订单
    static public function touristCreateOrder($orderSn)
    {
        var_dump($orderSn);
    }

    //游客付款
    static public function touristOrderPay($orderSn)
    {
        var_dump($orderSn);
    }

}