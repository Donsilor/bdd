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

    /**
     * @return array
     */
    public static function abstractStatus(): array
    {
        return [
            'has_active' => '是否已经有动态',
            'has_ended' => '动态是否已经截止',
            'has_signed' => '是否签收',
            'has_troubled' => '是否问题件',
            'has_returned' => '是否退回件',
        ];
    }
}