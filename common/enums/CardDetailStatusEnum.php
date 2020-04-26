<?php

namespace common\enums;

/**
 * Class CardDetailStatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class CardDetailStatusEnum extends BaseEnum
{
    const CANCEL = 0;
    const  SETTLE = 1;
    const FROZEN = 2;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::CANCEL => '取消',
            self::SETTLE => '成功',
            self::FROZEN => '冻结',
        ];
    }

}