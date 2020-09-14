<?php

namespace backend\modules\order\forms;
use common\enums\AuditStatusEnum;
use common\enums\OrderStatusEnum;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
/**
 * 发货表单
 * Class DeliveryForm
 * @package backend\forms
  */
class OrderRefundForm extends \common\models\order\Order
{

    public function rules()
    {
        return [
            [['id','refund_status', 'refund_remark'], 'required'],
            [['id','refund_status'], 'in', 'range' => array_keys(OrderStatusEnum::refundStatus())],
            [['refund_remark'], 'string', 'max' => 500],
            ['refund_status', 'validateRefundStatus']
        ];
    }

    public function validateRefundStatus($attribute)
    {
    }
}