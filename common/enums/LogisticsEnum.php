<?php

namespace common\enums;

/**
 * Class AppEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class LogisticsEnum extends BaseEnum
{
    const EMS = 1;
    const SFEXPRESS = 2;
    const FEDEXIN = 3;
    const DHL = 4;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::EMS => 'EMS',
            self::SFEXPRESS => '顺丰',
            self::FEDEXIN => 'Fedex',
            self::DHL => 'DHL国内件',
        ];
    }
}