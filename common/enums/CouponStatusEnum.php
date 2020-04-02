<?php

namespace common\enums;

/**
 * 订单状态
 * Class OrderStatusEnum
 * @package common\enums *
 */
class CouponStatusEnum extends BaseEnum
{
    const COUPON_PENDING = 0;
    const COUPON_FETCH = 1;
    const COUPON_USE = 2;
    const COUPON_TIMEOUT = 3;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::COUPON_PENDING => '未领取',
            self::COUPON_FETCH => '已领取',
            self::COUPON_USE => '已使用',
            self::COUPON_TIMEOUT => '已过期',
        ];
    }

}