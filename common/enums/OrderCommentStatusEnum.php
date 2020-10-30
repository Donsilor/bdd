<?php

namespace common\enums;

/**
 * 状态枚举
 *
 * Class StatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class OrderCommentStatusEnum extends BaseEnum
{
    const VIRTUAL_NO = 0;
    const VIRTUAL_YES = 1;

    const PASS = 1;
    const PENDING = 0;
    const FAIL = -1;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::PENDING => '待审核',
            self::PASS => '审核通过',
            self::FAIL => '审核不通过',
        ];
    }

    public static function virtualStatus()
    {
        return [
            self::VIRTUAL_NO => '真实',
            self::VIRTUAL_YES => '虚拟',
        ];
    }
}