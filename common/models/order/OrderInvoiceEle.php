<?php

namespace common\models\order;

use Yii;

/**
 * This is the model class for table "order_invoice_ele".
 *
 * @property int $id
 * @property int $order_id
 * @property int $invoice_date 发票日期
 * @property string $sender_name 发件人
 * @property string $sender_address 发件人地址
 * @property string $shipper_name 托运人姓名
 * @property string $shipper_address 托运人地址
 * @property string $express_company_name 运输公司
 * @property string $express_no 国际空运单号
 * @property int $delivery_time 发货时间
 * @property int $updated_at 修改时间
 */
class OrderInvoiceEle extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_invoice_ele';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id'], 'unique'],
            [['order_id', 'invoice_date', 'delivery_time', 'updated_at'], 'integer'],
            [['sender_name', 'shipper_name'], 'string', 'max' => 50],
            [['sender_address', 'shipper_address'], 'string', 'max' => 255],
            [['express_company_name', 'express_no'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'invoice_date' => '发票日期',
            'sender_name' => '发件人',
            'sender_address' => '发件人地址',
            'shipper_name' => '托运人',
            'shipper_address' => '托运人地址',
            'express_company_name' => '运输公司',
            'express_no' => '国际空运单号',
            'delivery_time' => '发货时间',
            'create_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }
}
