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
class OrderCancelForm extends \common\models\order\Order
{
    public $cancel_status = 0;

    public function rules()
    {
        return [
            [['id','cancel_status', 'cancel_remark'], 'required'],
            [['id','cancel_status'], 'integer'],
            [['cancel_remark'], 'string', 'max' => 500],
            ['cancel_status', 'validateCancelStatus']
        ];
    }

    public function validateCancelStatus($attribute)
    {
        if($this->cancel_status != OrderStatusEnum::ORDER_CANCEL_YES) {
            $this->addError($attribute,"请选择是否取消订单");
            return false;
        }
    }

//    public function behaviors()
//    {
//        return [
//            [
//                'class' => TimestampBehavior::class,
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at','delivery_time'],
//                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at','delivery_time'],
//                ],
//            ],
//        ];
//    }
    
}