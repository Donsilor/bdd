<?php

namespace common\models\order;

use common\helpers\StringHelper;
use Yii;

/**
 * This is the model class for table "order_invoice_ele".
 *
 * @property int $id
 * @property int $order_id
 * @property int $invoice_date 发票日期
 * @property string $sender_name 发件人
 * @property string $sender_area 发件人地址
 * @property string $sender_address 发件人地址
 * @property string $shipper_name 托运人姓名
 * @property string $shipper_address 托运人地址
 * @property string $express_id 运输公司
 * @property string $express_no 国际空运单号
 * @property int $delivery_time 发货时间
 * @property int $updated_at 修改时间
 */
class OrderInvoiceEle extends \common\models\base\BaseModel
{
    public $platforms_group;

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
            [['order_id'], 'integer'],
            [['invoice_date','express_id','language', 'delivery_time','created_at','platforms_group'],'safe'],
            [['sender_name', 'shipper_name','email'], 'string', 'max' => 50],
            [['sender_area', 'sender_address', 'shipper_address'], 'string', 'max' => 255],
            [['express_no'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'language'=> '语言',
            'order_id' => '订单ID',
            'invoice_date' => '发票日期',
            'sender_name' => '发货人',
            'sender_area' => '发货地区',
            'sender_address' => '发货人地址',
            'shipper_name' => '进口商',
            'shipper_address' => '进口商地址',
            'express_id' => '运输公司',
            'express_no' => '国际空运单号',
            'delivery_time' => '发货时间',
            'email' => '接收邮箱',
            'create_at' => '创建时间',
            'updated_at' => '修改时间',
            'platforms_group' => '发货站点地区',
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {

        $this->invoice_date = StringHelper::dateToInt($this->invoice_date);
        $this->delivery_time = StringHelper::dateToInt($this->delivery_time);

        return parent::beforeSave($insert);
    }


    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getExpress()
    {
        return $this->hasOne(\common\models\common\Express::class, ['id'=>'express_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
