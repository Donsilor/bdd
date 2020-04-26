<?php

namespace common\enums;

/**
 * Class CardDetailStatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class CardTypeEnum extends BaseEnum
{
    const RECHARGE = 1;
    const CONSUME = 2;
    const DEFROZEN = 3;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::RECHARGE => '余额调整',
            self::CONSUME => '客户购物消费',
            self::DEFROZEN => '购物取消解冻',
        ];
    }

}