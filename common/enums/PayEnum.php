<?php

namespace common\enums;

/**
 * Class PayEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class PayEnum extends BaseEnum
{
    const ORDER_GROUP = 'default';
    const ORDER_TOURIST = 'order_tourist';
    const ORDER_GROUP_GOODS = 'goods';
    const ORDER_GROUP_RECHARGE = 'recharge';
    
    /**
     * 订单组别说明
     *
     * @var array
     */
    public static $orderGroupExplain = [
        self::ORDER_GROUP => '统一支付',
        self::ORDER_TOURIST => '游客订单支付',
        self::ORDER_GROUP_GOODS => '订单商品',
        self::ORDER_GROUP_RECHARGE => '充值',
    ];
    
    const PAY_TYPE = 0;
    const PAY_TYPE_WECHAT = 1;
    const PAY_TYPE_ALI = 2;
    const PAY_TYPE_UNION = 3;
    const PAY_TYPE_MINI_PROGRAM = 4;
    const PAY_TYPE_USER_MONEY = 5;
    const PAY_TYPE_PAYPAL = 6;
    const PAY_TYPE_PAYPAL_1 = 61;
    const PAY_TYPE_GLOBAL_ALIPAY = 7;
    const PAY_TYPE_PAYDOLLAR = 8;
    const PAY_TYPE_PAYDOLLAR_1 = 81;
    const PAY_TYPE_PAYDOLLAR_2 = 82;
    const PAY_TYPE_PAYDOLLAR_3 = 83;
    const PAY_TYPE_CARD = 10;
    const PAY_TYPE_OFFLINE = 100;
    
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
           // self::PAY_TYPE_WECHAT => '微信',
           // self::PAY_TYPE_ALI => '支付宝',
           // self::PAY_TYPE_GLOBAL_ALIPAY => '支付宝国际版',
           // self::PAY_TYPE_UNION => '银联',
            self::PAY_TYPE_PAYPAL => 'Paypal',
            self::PAY_TYPE_PAYPAL_1 => 'Paypal Card',
            self::PAY_TYPE_PAYDOLLAR => 'Paydollar',
            self::PAY_TYPE_PAYDOLLAR_1 => 'Union',
            self::PAY_TYPE_PAYDOLLAR_2 => 'AliPay',
            self::PAY_TYPE_PAYDOLLAR_3 => 'Wechat',
            self::PAY_TYPE_CARD => 'Card',
            //self::PAY_TYPE_MINI_PROGRAM => '小程序',
            // self::PAY_TYPE_USER_MONEY => '余额',
            //self::PAY_TYPE_OFFLINE => '线下',
            self::PAY_TYPE => '待支付',
        ];
    }
    /**
     * 支付类型
     *
     * @var array
     */
    public static $payTypeExplain = [
        self::PAY_TYPE_WECHAT => '微信',
        self::PAY_TYPE_ALI => '支付宝',
        self::PAY_TYPE_GLOBAL_ALIPAY => '支付宝国际版',
        self::PAY_TYPE_UNION => '银联',
        self::PAY_TYPE_PAYPAL => 'Paypal',
        self::PAY_TYPE_PAYPAL_1 => 'Paypal Card',
        self::PAY_TYPE_PAYDOLLAR => 'Paydollar',
        self::PAY_TYPE_PAYDOLLAR_1 => 'Paydollar 银联',
        self::PAY_TYPE_PAYDOLLAR_2 => 'Paydollar 支付宝',
        self::PAY_TYPE_PAYDOLLAR_3 => 'Paydollar 微信',
        self::PAY_TYPE_CARD => 'CARD',
        self::PAY_TYPE_MINI_PROGRAM => '小程序',
        self::PAY_TYPE_USER_MONEY => '余额',
        self::PAY_TYPE_OFFLINE => '线下',
        self::PAY_TYPE => '待支付',
    ];
    
    /**
     * @var array
     */
    public static $payTypeAction = [
        self::PAY_TYPE_WECHAT => 'wechat',
        self::PAY_TYPE_ALI => 'alipay',
        self::PAY_TYPE_GLOBAL_ALIPAY => 'globalAlipay',
        self::PAY_TYPE_UNION => 'union',
        self::PAY_TYPE_MINI_PROGRAM => 'miniProgram',
        self::PAY_TYPE_PAYPAL => 'paypal',
        self::PAY_TYPE_PAYPAL_1 => 'paypal',
        self::PAY_TYPE_PAYDOLLAR => 'Paydollar',
        self::PAY_TYPE_PAYDOLLAR_1 => 'Paydollar',
        self::PAY_TYPE_PAYDOLLAR_2 => 'Paydollar',
        self::PAY_TYPE_PAYDOLLAR_3 => 'Paydollar',
    ];
}