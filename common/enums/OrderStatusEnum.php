<?php

namespace common\enums;

/**
 * 订单状态
 * Class OrderStatusEnum
 * @package common\enums * 
 */
class OrderStatusEnum extends BaseEnum
{    
    const ORDER_UNPAID = 10;
    const ORDER_PAID = 20;
    const ORDER_CONFIRM = 30;
    const ORDER_SEND = 40;
    const ORDER_FINISH = 50;    
    const ORDER_CANCEL = 0;
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
                self::ORDER_UNPAID => \Yii::t('common','待付款'),
                self::ORDER_PAID => \Yii::t('common','已付款'),
                self::ORDER_CONFIRM => \Yii::t('common','待发货'),
                self::ORDER_SEND => \Yii::t('common','已发货'),
                self::ORDER_FINISH => \Yii::t('common','已完成'),
                self::ORDER_CANCEL => \Yii::t('common','已取消'),
        ];
    }   
    
}