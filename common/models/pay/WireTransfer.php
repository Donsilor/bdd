<?php

namespace common\models\pay;

use common\models\order\Order;
use Yii;

/**
 * This is the model class for table "{{%common_pay_wire_transfer}}".
 *
 * @property int $id ID
 * @property int $order_id 订单ID
 * @property int $member_id 买家id
 * @property string $account 收款账号
 * @property string $account_name 户名
 * @property string $opening_bank 开户行
 * @property string $bank_name 银行名称
 * @property string $payment_serial_number 付款流水号(客户)
 * @property string $payment_voucher 付款凭证图片(客户)
 * @property string $payment_amount 付款金额(客户)
 * @property string $collection_voucher 收款凭证(出纳)
 * @property string $collection_amount 收款金额(出纳)
 * @property int $collection_status 收款确认(出纳)：0=待确认，1=确认，2=异常，10=关闭
 * @property int $status 收款审核(会计)：0=待确认，1=确认
 * @property string $out_trade_no 商户订单号
 * @property int $created_at 添加时间
 * @property int $updated_at 编辑时间
 */
class WireTransfer extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%common_pay_wire_transfer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'account', 'account_name', 'opening_bank', 'bank_name', 'payment_voucher'], 'required'],
            [['order_id', 'member_id', 'collection_status', 'status', 'created_at', 'updated_at'], 'integer'],
            [['payment_amount', 'collection_amount'], 'number'],
            [['account', 'account_name', 'payment_serial_number'], 'string', 'max' => 50],
            [['opening_bank', 'bank_name'], 'string', 'max' => 80],
            [['payment_voucher', 'collection_voucher'], 'string', 'max' => 255],
            [['out_trade_no'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '订单ID',
            'member_id' => '买家id',
            'account' => '收款账号',
            'account_name' => '户名',
            'opening_bank' => '开户行',
            'bank_name' => '银行名称',
            'payment_serial_number' => '付款流水号(客户)',
            'payment_voucher' => '付款凭证图片(客户)',
            'payment_amount' => '付款金额(客户)',
            'collection_voucher' => '收款凭证(出纳)',
            'collection_amount' => '收款金额(出纳)',
            'collection_status' => '收款确认(出纳)：0=待确认，1=确认，2=异常，10=关闭',
            'status' => '收款审核(会计)：0=待确认，1=确认',
            'out_trade_no' => '商户订单号',
            'created_at' => '添加时间',
            'updated_at' => '编辑时间',
        ];
    }

    /**
     * 对应订单付款信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id'=>'order_id']);
    }
}
