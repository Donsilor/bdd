<?php

namespace common\enums;

/**
 * 货币枚举
 *
 * Class CurrencyEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class CurrencyEnum extends BaseEnum
{
    const CNY = 'CNY';
    const HKD = 'HKD';
    const USD = 'USD';
    const TWD = 'TWD';
    
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::CNY=> '人民币',
            self::HKD=> '港币',
            self::USD=> '美元',
            self::TWD=> '新台币',
        ];
    }
}