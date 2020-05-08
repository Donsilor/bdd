<?php

namespace common\enums;

/**
 * 支付状态
 * Class PayStatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class PayStatusEnum extends BaseEnum
{
    const PAID = 1;
    const UNPAID = 0;    
    
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
                self::PAID => \Yii::t('common','已支付'),
                self::UNPAID => \Yii::t('common','未支付'),
        ];
    }



    /**
     * @return array
     */
    public static function refund(): array
    {
        return [
            0 => \Yii::t('common','已支付'),
            1 => \Yii::t('common','已退款'),
        ];
    }
    
}