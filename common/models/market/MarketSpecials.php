<?php

namespace common\models\market;

use Yii;

/**
 * This is the model class for table "market_specials".
 *
 * @property int $id 活动Id
 * @property int $merchant_id 商户ID
 * @property string $title 活动名称
 * @property string $describe 活动描述
 * @property int $type 优惠券类型 1:满减;2:折扣
 * @property int $start_time 领取-开始时间
 * @property int $end_time 领取-结束时间
 * @property int $status 状态[-1:删除;0:禁用;1启用]
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class MarketSpecials extends \common\models\base\BaseModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_specials';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'type', 'get_start_time', 'get_end_time', 'term_type', 'term_start_time', 'term_end_time', 'term_days', 'status', 'created_at', 'updated_at'], 'integer'],
            [['title'], 'string', 'max' => 80],
            [['describe'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '活动Id',
            'merchant_id' => '商户ID',
            'title' => '活动名称',
            'describe' => '活动描述',
            'type' => '优惠券类型 1:满减;2:折扣',
            'get_start_time' => '领取-开始时间',
            'get_end_time' => '领取-结束时间',
            'term_type' => '期限类型  1固定时间 2领取之日起',
            'term_start_time' => '使用有效开始时间',
            'term_end_time' => '使用有效结束时间',
            'term_days' => '领取之日起N天内有效',
            'status' => '状态[-1:删除;0:禁用;1启用]',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }
}
