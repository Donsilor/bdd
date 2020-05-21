<?php

namespace common\models\goods;

use Yii;

/**
 * This is the model class for table "{{goods_log}}".
 *
 * @property int $id ID
 * @property int $type_id 产品线
 * @property int $goods_id 商品ID
 * @property string $log_msg 操作内容
 * @property int $log_time 操作时间
 * @property string $log_role 操作角色(buyer, system)
 * @property string $log_user 操作人
 */
class GoodsLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{goods_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_id', 'goods_id', 'log_time', 'log_role'], 'required'],
            [['type_id', 'goods_id', 'log_time'], 'integer'],
            [['log_msg'], 'string'],
            [['log_role'], 'string', 'max' => 10],
            [['log_user'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_id' => '产品线',
            'goods_id' => '商品ID',
            'log_msg' => '操作内容',
            'log_time' => '操作时间',
            'log_role' => '操作角色(buyer, system)',
            'log_user' => '操作人',
        ];
    }
}
