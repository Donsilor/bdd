<?php

namespace common\models\market;

use Yii;

/**
 * This is the model class for table "market_coupon_details".
 *
 * @property int $id 优惠券id
 * @property int $merchant_id 店铺Id
 * @property int $specials_id 活动ID
 * @property int $coupon_id 优惠券类型id
 * @property string $coupon_code 优惠券编码
 * @property int $coupon_status 优惠券状态 0未领用 1已领用（未使用） 2已使用 3已过期
 * @property int $member_id 领用人
 * @property int $order_id 优惠券使用订单id
 * @property string $order_sn 订单编号
 * @property int $get_type 获取方式1订单2.首页领取
 * @property int $fetch_time 领取时间
 * @property int $use_time 使用时间
 * @property int $status 状态 1有效 0无效
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class MarketCouponDetails extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_coupon_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'specials_id', 'coupon_id', 'coupon_status', 'member_id', 'order_id', 'get_type', 'fetch_time', 'use_time', 'status', 'created_at', 'updated_at'], 'integer'],
            [['coupon_code'], 'string', 'max' => 100],
            [['order_sn'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '优惠券id',
            'merchant_id' => '店铺Id',
            'specials_id' => '活动ID',
            'coupon_id' => '优惠券类型id',
            'coupon_code' => '优惠券编码',
            'coupon_status' => '优惠券状态 0未领用 1已领用（未使用） 2已使用 3已过期',
            'member_id' => '领用人',
            'order_id' => '优惠券使用订单id',
            'order_sn' => '订单编号',
            'get_type' => '获取方式1订单2.首页领取',
            'fetch_time' => '领取时间',
            'use_time' => '使用时间',
            'status' => '状态 1有效 0无效',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
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
}
