<?php

namespace common\models\statistics;


/**
 * This is the model class for table "{{%statistics_style_view}}".
 *
 * @property int $id ID
 * @property int $type
 * @property string $datetime
 * @property string $platform_group
 * @property string $platform_id
 * @property string $sale_amount
 * @property string $type_sale_amount
 * @property string $is_cache
 */
class OrderSale extends \common\models\base\BaseModel
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%statistics_order_sale}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'datetime', 'platform_group', 'platform_id', 'sale_amount', 'is_cache'], 'required'],
            [['id', 'datetime', 'platform_id', 'is_cache'], 'integer'],
            [['sale_amount'], 'number'],
            [['platform_group'], 'string', 'max' => 2],
            [['type_sale_amount'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '类型',
            'datetime' => '时间',
            'platform_group' => '站点地区',
            'platform_id' => '站点',
            'sale_amount' => '销售金额',
            'type_sale_amount' => '产品线销售金额',
            'is_cache' => '是否缓存',
        ];
    }
}
