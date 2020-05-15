<?php

namespace common\enums;

/**
 * 订单状态
 * Class OrderStatusEnum
 * @package common\enums * 
 */
class OrderStatusEnum extends BaseEnum
{
    //取消状态
    const ORDER_CANCEL_YES = 1;
    const ORDER_CANCEL_NO = 0;

    //退款状态refund
    const ORDER_REFUND_YES = 1;
    const ORDER_REFUND_NO = 0;

    //
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

    //取消
    public static function cancelStatus()
    {
        return [
            self::ORDER_CANCEL_YES => \Yii::t('common','是'),
            self::ORDER_CANCEL_NO => \Yii::t('common','否'),
        ];
    }

    //退款
    public static function refundStatus()
    {
        return [
            self::ORDER_REFUND_YES => \Yii::t('common','已退款'),
            self::ORDER_REFUND_NO => \Yii::t('common','未退款'),
        ];
    }
    
}