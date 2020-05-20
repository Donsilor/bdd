<?php


namespace common\enums;


class NotifyContactsEnum
{
    const TYPE_ORDER = 1;//订单
    const TYPE_STOCK = 2;//库存
    const TYPE_ABNORMAL = 3;//异常

    static public function Type()
    {
        return [
            self::TYPE_ORDER => '订单短信通知',
//            self::TYPE_STOCK => '产品库存预警',
            self::TYPE_ABNORMAL => '异常订单/产品通知',
        ];
    }

}