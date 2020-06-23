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
class OrderFollowerForm extends \common\models\order\Order
{

    public function rules()
    {
        return [
            [['follower_id', 'seller_remark'], 'required'],
            [['follower_id'], 'integer'],
            [['seller_remark'], 'string', 'max' => 500],
        ];
    }

}