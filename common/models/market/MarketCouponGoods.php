<?php

namespace common\models\market;

use common\models\goods\Goods;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "market_coupon_goods".
 *
 * @property int $id
 * @property int $specials_id 活动ID
 * @property int $coupon_id 优惠券id
 * @property int $style_id 款式ID
 */
class MarketCouponGoods extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_coupon_goods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['specials_id', 'coupon_id', 'style_id'], 'required'],
            [['specials_id', 'coupon_id', 'style_id'], 'integer'],
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
            'style_id' => '款式ID',
        ];
    }

    public function getCoupon()
    {
        return $this->hasOne(MarketCoupon::class,['id'=>'coupon_id']);
    }

    public function getSpecials()
    {
        return $this->hasOne(MarketSpecials::class,['id'=>'specials_id']);
    }

    public function getGoods()
    {
        return $this->hasOne(Goods::class,['style_id'=>'style_id']);
    }
}
