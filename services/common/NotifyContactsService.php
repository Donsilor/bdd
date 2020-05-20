<?php


namespace services\common;


use common\components\Service;

class NotifyContactsService extends Service
{
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