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
class OrderAuditForm extends \common\models\order\Order
{

    public function rules()
    {
        return [
            [['id','audit_status'], 'required'],
            [['id','audit_status'], 'integer'],
            [['audit_remark'], 'string', 'max' => 500],
            [['audit_status', 'refund_remark'], 'validateAuditStatus']
        ];
    }

    public function validateAuditStatus($attribute)
    {
        if($this->audit_status == OrderStatusEnum::ORDER_AUDIT_NO && empty($this->audit_remark)) {
            $this->addError($attribute,"审核不通过必需填写备注");
            return false;
        }
    }
}