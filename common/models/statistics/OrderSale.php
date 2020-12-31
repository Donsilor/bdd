<?php

namespace common\models\statistics;

use common\models\order\Order;
use common\models\order\OrderAccount;
use Yii;

/**
 * This is the model class for table "{{%statistics_style_view}}".
 *
 * @property int $id ID
 * @property int $type
 * @property string $date
 * @property string $platform_group
 * @property string $platform_id
 * @property string $sale_amount
 * @property string $type_sale_amount
 * @property string $is_cache
 */
class OrderSale extends \common\models\base\BaseModel
{

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
            [['type', 'date', 'platform_group', 'platform_id', 'sale_amount', 'type_sale_amount', 'is_cache'], 'required'],
            [['id', 'platform_id', 'is_cache'], 'integer'],
            [['sale_amount'], 'number'],
            [['datetime'], 'datetime'],
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
            'date' => '时间',
            'platform_group' => '站点地区',
            'platform_id' => '站点',
            'sale_amount' => '销售金额',
            'type_sale_amount' => '产品线销售金额',
            'is_cache' => '是否缓存',
        ];
    }
}
