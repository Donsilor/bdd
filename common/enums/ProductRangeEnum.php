<?php

namespace common\enums;

/**
 * 阶梯折扣类型
 *
 * Class PreferentialTypeEnum
 * @package addons\TinyShop\common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class ProductRangeEnum extends BaseEnum
{
    const GOODS = 1;
    const GOODSTYPE = 2;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::GOODS => '特定商品',
            self::GOODSTYPE => '产品线',
        ];
    }
}