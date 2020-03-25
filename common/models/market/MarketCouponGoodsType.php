<?php

namespace common\models\market;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "market_coupon_goods_type".
 *
 * @property int $id
 * @property int $specials_id 活动ID
 * @property int $coupon_id 优惠券id
 * @property int $goods_type 产品线ID
 */
class MarketCouponGoodsType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_coupon_goods_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['specials_id', 'coupon_id', 'goods_type'], 'required'],
            [['specials_id', 'coupon_id', 'goods_type'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'specials_id' => '活动ID',
            'coupon_id' => '优惠券id',
            'goods_type' => '产品线ID',
        ];
    }
}
