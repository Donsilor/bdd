<?php

namespace common\models\order;

use common\helpers\RegularHelper;
use Yii;

/**
 * This is the model class for table "order_invoice".
 *
 * @property int $id
 * @property int $order_id 订单ID
 * @property int $invoice_type 发票类型：1=企业，2=个人
 * @property string $invoice_title 发票抬头
 * @property string $tax_number 纳税人识别号
 * @property int $is_electronic 是否电子发票：0=不是，1=是
 * @property string $email 接收电子发票的邮箱
 */
class OrderInvoice extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{order_invoice}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'invoice_type', 'invoice_title', 'is_electronic'], 'required'],
            [['order_id', 'invoice_type', 'is_electronic'], 'integer'],
            [['invoice_title'], 'string', 'max' => 80],
            [['tax_number'], 'string', 'max' => 50],
            [['invoice_type'], 'validateTaxNumber'],
            [['invoice_title','tax_number'], 'safe'],
            [['email'], 'string', 'max' => 60],
            ['email', 'match', 'pattern' => RegularHelper::email(), 'message' => \Yii::t('order','请输入正确的发票接收邮箱')],
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
            'invoice_type' => \Yii::t('order','抬头类型'),
            'invoice_title' => \Yii::t('order','发票抬头'),
            'tax_number' => \Yii::t('order','纳税人识别号'),
            'is_electronic' => \Yii::t('order','发票类型'),
            'email' => \Yii::t('order','接收邮箱'),
        ];
    }

    public function validateTaxNumber($attribute)
    {
        $invoiceType = intval($this->invoice_type);
        if(!in_array($invoiceType, [1, 2])) {
            $this->addError($attribute, \Yii::t('order','发票类型的值超出范围'));
        }
        if($invoiceType===1 && empty($this->tax_number)) {
            $this->addError($attribute, \Yii::t('order','企业发票税号不能为空'));
        }
    }

    /**
     * 对应订单电子发票扩展信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceEle()
    {
        return $this->hasOne(OrderInvoiceEle::class, ['invoice_id'=>'id']);
    }
}
