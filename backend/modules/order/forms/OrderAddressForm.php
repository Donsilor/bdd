<?php

namespace backend\modules\order\forms;
use common\enums\AuditStatusEnum;
use common\enums\OrderStatusEnum;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
/**
 * 发货表单
 * Class OrderAddressFormForm
 * @package backend\forms
  */
class OrderAddressForm extends \common\models\order\Order
{

    public function rules()
    {
        return [
            [['id','refund_status', 'refund_remark'], 'required'],
            [['id','refund_status'], 'integer'],
            [['refund_remark'], 'string', 'max' => 500],
            ['refund_status', 'validateRefundStatus']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'order_id' => '订单ID',
            'mobile' => '手机号码',
            'mobile_code' => '手机区号',
            'email' => '邮箱地址',
            'country_id' => '国家区域',
            'province_id' => '省份',
            'city_id' => '城市市',
            'country_name' => '国家',
            'province_name' => '省份',
            'city_name' => '城市',
            'address_details' => '详细地址',
            'zip_code' => '邮编',
            'realname' => '收货人',
            'buyer_remark' => '买家留言',
        ];
    }

    public function validateRefundStatus($attribute)
    {
        if($this->refund_status != OrderStatusEnum::ORDER_REFUND_YES) {
            $this->addError($attribute,"请选择是否退款");
            return false;
        }
    }
}