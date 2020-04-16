<?php

namespace common\models\common;

use Yii;

/**
 * This is the model class for table "common_delivery_time".
 *
 * @property int $id
 * @property int $area_id 地区ID
 * @property string $futures_time 预计期货送达时间
 * @property string $stock_time 预计现货送达时间
 * @property int $status
 * @property int $create_time
 * @property int $update_time
 */
class DeliveryTime extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'common_delivery_time';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['area_id'], 'required'],
            [['area_id'], 'unique'],
            [['area_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['futures_time', 'stock_time'], 'string', 'max' => 20],
            ['futures_time', 'match', 'pattern' => '/^[1-9]\d*-{1}[1-9]\d*$/', 'message' => '请输入正确的格式'],
            ['stock_time', 'match', 'pattern' => '/^[1-9]\d*-{1}[1-9]\d*$/', 'message' => '请输入正确的格式'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'area_id' => '地区',
            'futures_time' => '期货时间（天）',
            'stock_time' => '现货时间（天）',
            'status' => '状态',
            'created_at' => '创建时间（天）',
            'updated_at' => '更新时间（天）',
        ];
    }
}
