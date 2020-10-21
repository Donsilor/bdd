<?php

namespace backend\modules\order\forms;
use common\enums\AuditStatusEnum;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
/**
 * 发货表单
 * Class DeliveryForm
 * @package backend\forms
  */
class DeliveryForm extends \common\models\order\Order
{
    const ONDELIVERY = 'no_delivery';

    public $send_now;
//    public $on_delivery;

    public function scenarios()
    {
        return [
            'no_delivery' => ['no_delivery'],
            'default' => ['id','express_id', 'express_no','delivery_time','delivery_status', 'send_now','order_status'],
        ];
    }

    public function rules()
    {
        return [
                [['id','express_id', 'express_no','delivery_time'], 'required'],
                [['order_status','updated_at','delivery_status', 'send_now'], 'integer'],
                [['express_no'], 'validateOrderStatus'],
            [['id','no_delivery'], 'required', 'on'=>['no_delivery']],
            [['id','no_delivery'], 'integer', 'on'=>['no_delivery']],
        ];
    }
    /**
     * 订单状态校验
     * @param unknown $attribute
     * @return boolean
     */
    public function validateOrderStatus($attribute)
    {
        if($this->status != AuditStatusEnum::PASS) {
            $this->addError($attribute,"订单还未审核，不能发货");
            return false;
        }
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at','delivery_time'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at','delivery_time'],
                ],
            ],
        ];
    }
    
}