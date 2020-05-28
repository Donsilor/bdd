<?php


namespace common\enums;


use common\models\common\EmailLog;
use common\models\common\SmsLog;

class NotifyContactsEnum extends BaseEnum
{
    const TYPE_ORDER = 1;//订单
    const TYPE_STOCK = 2;//库存
    const TYPE_ABNORMAL = 3;//异常

    static public function type()
    {
        return [
            self::TYPE_ORDER => '订单短信通知',
//            self::TYPE_STOCK => '产品库存预警',
//            self::TYPE_ABNORMAL => '异常订单/产品通知',
        ];
    }

    static public function usageForEmail()
    {
        return [
            self::TYPE_ORDER => EmailLog::USAGE_ORDER_PAY_SUCCESS,
//            self::TYPE_STOCK => '产品库存预警',
            self::TYPE_ABNORMAL => EmailLog::USAGE_ORDER_ABNORMAL_NOTICE,
        ];
    }

    static public function usageForMobile()
    {
        return [
            self::TYPE_ORDER => SmsLog::USAGE_ORDER_PAY_SUCCESS,
//            self::TYPE_STOCK => '产品库存预警',
            self::TYPE_ABNORMAL => SmsLog::USAGE_ORDER_ABNORMAL_NOTICE,
        ];
    }

    public static function getMap(): array
    {
        return [
            self::TYPE_ORDER => '订单短信通知',
//            self::TYPE_STOCK => '产品库存预警',
            self::TYPE_ABNORMAL => '异常订单/产品通知',
        ];
    }
}