<?php

namespace common\models\market;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "market_card_goods_type".
 *
 * @property int $id
 * @property string $batch 批次
 * @property int $goods_type 产品线ID
 */
class MarketCardGoodsType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_card_goods_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['batch', 'goods_type'], 'required'],
            [['goods_type'], 'integer'],
            [['batch'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'batch' => '批次',
            'goods_type' => '产品线ID',
        ];
    }
}
