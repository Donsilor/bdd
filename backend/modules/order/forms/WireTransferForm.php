<?php

namespace backend\modules\order\forms;
use common\enums\AuditStatusEnum;
use common\models\order\Order;
use common\models\order\OrderAccount;
use common\models\pay\WireTransfer;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
/**
 * 发货表单
 * Class DeliveryForm
 * @package backend\forms
  */
class WireTransferForm extends WireTransfer
{

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'account', 'account_name', 'opening_bank', 'bank_name', 'payment_voucher', 'collection_amount', 'collection_status'], 'required'],
            [['order_id', 'member_id', 'collection_status', 'status', 'created_at', 'updated_at'], 'integer'],
            [['payment_amount', 'collection_amount'], 'number'],
            [['account', 'account_name', 'payment_serial_number'], 'string', 'max' => 50],
            [['opening_bank', 'bank_name'], 'string', 'max' => 80],
            [['payment_voucher', 'collection_voucher'], 'string', 'max' => 255],
            [['out_trade_no'], 'string', 'max' => 32],
            [['collection_amount','collection_status'], 'validateCollectionAmount'],
            [['collection_status'], 'validateCollectionStatus'],
        ];
    }

    public function validateCollectionAmount($attribute)
    {
        if($this->collection_status == 1 && OrderAccount::findOne($this->order_id)->pay_amount!=$this->collection_amount) {
            $this->addError($attribute, '审核通过时，收款金额必需等于订单金额');
        }
    }

    public function validateCollectionStatus($attribute)
    {
        if(self::findOne($this->id)->collection_status==1) {
            $this->addError($attribute, '已审核通过的订单，不能重复提交');
        }
    }

    
}