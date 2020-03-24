<?php

namespace common\models\market;

use common\models\goods\GoodsTypeLang;
use Yii;
use yii\db\JsonExpression;

/**
 * This is the model class for table "market_specials".
 *
 * @property int $id 活动Id
 * @property int $merchant_id 商户ID
 * @property string $title 活动名称
 * @property string $describe 活动描述
 * @property JsonExpression $areas 活动地区
 * @property int $type 优惠券类型 1:满减;2:折扣
 * @property int $start_time 开始时间
 * @property int $end_time 结束时间
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
            [['merchant_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
//            [['describe', 'areas'], 'required'],
//            [['describe', 'areas'], 'string'],
            [['title'], 'string', 'max' => 80],
            [['start_time', 'end_time'], 'safe'],
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
            'areas' => '活动地区',
            'type' => '优惠券类型',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'status' => '启用状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new MarketSpecialsLang();
    }

    public function getLangs()
    {
        return $this->hasMany(MarketSpecialsLang::class,['master_id'=>'id']);

    }
}
