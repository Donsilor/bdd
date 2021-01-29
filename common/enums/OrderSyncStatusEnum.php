<?php

namespace common\enums;

/**
 * 订单同步状态枚举
 *
 * Class FollowStatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class OrderSyncStatusEnum extends BaseEnum
{
    const YES = 1;
    const NO = 0;
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::NO => '未同步',
            self::YES => '已同步',
        ];
    }
}