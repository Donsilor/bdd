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
            [['order_id'], 'required'],
            [['order_id', 'merchant_id', 'member_id', 'country_id', 'province_id', 'city_id',  'created_at', 'updated_at'], 'integer'],
            [['realname'], 'string', 'max' => 200],
            [['email'], 'string', 'max' => 150],
            [['country_name', 'province_name'], 'string', 'max' => 30],
            [['city_name'], 'string', 'max' => 100],
            [['address_details'], 'string', 'max' => 300],
            [['zip_code'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 20],
            [['mobile_code'], 'string', 'max' => 10],
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