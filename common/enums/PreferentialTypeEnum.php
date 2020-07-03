<?php

namespace common\enums;

/**
 * 阶梯折扣类型
 *
 * Class PreferentialTypeEnum
 * @package addons\TinyShop\common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class PreferentialTypeEnum extends BaseEnum
{
    const MONEY = 1;
    const DISCOUNT = 2;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::MONEY => '优惠券',
            self::DISCOUNT => '折扣',
        ];
    }
}