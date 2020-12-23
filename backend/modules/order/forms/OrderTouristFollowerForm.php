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
class OrderTouristFollowerForm extends \common\models\order\OrderTourist
{

    public function rules()
    {
        return [
            [['follower_id', 'is_test'], 'required'],
            [['follower_id', 'is_test'], 'integer'],
//            [['seller_remark'], 'string', 'max' => 5000],
        ];
    }

}