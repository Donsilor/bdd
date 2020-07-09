<?php

namespace common\models\market;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "market_coupon_area".
 *
 * @property int $id ID
 * @property int $specials_id 活动ID
 * @property int $coupon_id 优惠券ID
 * @property int $area_id 地区ID
 */
class MarketCouponArea extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_coupon_area';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['specials_id', 'coupon_id', 'area_id'], 'required'],
            [['specials_id', 'coupon_id', 'area_id'], 'integer'],
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
            'coupon_id' => '优惠券ID',
            'area_id' => '地区ID',
        ];
    }
}
