<?php

namespace common\enums;

/**
 *
 * Class WireTransferEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class WireTransferEnum extends BaseEnum
{
    const PENDING = 0;//Pending
    const CONFIRM = 1;//confirm
    const ABNORMAL = 2;//abnormal
    const CANCEL = 10;//abnormal

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::PENDING => '待审核',
            self::CONFIRM => '审核通过',
            self::ABNORMAL => '异常',
            self::CANCEL => '审核不通过',
        ];
    }

}