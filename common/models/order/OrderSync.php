<?php

namespace common\models\order;

use Yii;

/**
 * This is the model class for table "order_sync".
 *
 * @property int $order_id
 * @property int $sync_platform 同步平台：1:ERP系统
 * @property int $sync_created 是否同步创建 1是 0否
 * @property int $sync_created_time 同步创建时间
 * @property int $sync_refund 是否同步退款 1是 0否
 * @property int $sync_refund_time 同步退款时间
 */
class OrderSync extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_sync';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'sync_platform'], 'required'],
            [['order_id', 'sync_platform', 'sync_created', 'sync_created_time', 'sync_refund', 'sync_refund_time'], 'integer'],
            [['order_id', 'sync_platform'], 'unique', 'targetAttribute' => ['order_id', 'sync_platform']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'sync_platform' => 'Sync Platform',
            'sync_created' => 'Sync Created',
            'sync_created_time' => 'Sync Created Time',
            'sync_refund' => 'Sync Refund',
            'sync_refund_time' => 'Sync Refund Time',
        ];
    }
}
