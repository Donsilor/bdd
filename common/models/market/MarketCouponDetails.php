<?php

namespace common\models\market;

use Yii;

/**
 * This is the model class for table "market_coupon_details".
 *
 * @property int $id 优惠券id
 * @property int $merchant_id 店铺Id
 * @property int $coupon_id 优惠券类型id
 * @property int $coupon_type 优惠券类型 1:满减;2:折扣
 * @property string $coupon_code 优惠券编码
 * @property string $coupon_money 面额
 * @property int $coupon_discount 折扣
 * @property int $coupon_status 优惠券状态 0未领用 1已领用（未使用） 2已使用 3已过期
 * @property int $owner_id 领用人
 * @property int $use_order_id 优惠券使用订单id
 * @property int $create_order_id 创建订单id(优惠券只有是完成订单发放的优惠券时才有值)
 * @property string $at_least 满多少元使用 0代表无限制
 * @property int $get_type 获取方式1订单2.首页领取
 * @property int $fetch_time 领取时间
 * @property int $use_time 使用时间
 * @property int $start_time 有效期开始时间
 * @property int $end_time 有效期结束时间
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
            [['merchant_id', 'coupon_id', 'coupon_type', 'coupon_discount', 'coupon_status', 'owner_id', 'use_order_id', 'create_order_id', 'get_type', 'fetch_time', 'use_time', 'start_time', 'end_time', 'status', 'created_at', 'updated_at'], 'integer'],
            [['coupon_money', 'at_least'], 'number'],
            [['coupon_code'], 'string', 'max' => 100],
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
            'coupon_id' => '优惠券类型id',
            'coupon_type' => '优惠券类型 1:满减;2:折扣',
            'coupon_code' => '优惠券编码',
            'coupon_money' => '面额',
            'coupon_discount' => '折扣',
            'coupon_status' => '优惠券状态 0未领用 1已领用（未使用） 2已使用 3已过期',
            'owner_id' => '领用人',
            'use_order_id' => '优惠券使用订单id',
            'create_order_id' => '创建订单id(优惠券只有是完成订单发放的优惠券时才有值)',
            'at_least' => '满多少元使用 0代表无限制',
            'get_type' => '获取方式1订单2.首页领取',
            'fetch_time' => '领取时间',
            'use_time' => '使用时间',
            'start_time' => '有效期开始时间',
            'end_time' => '有效期结束时间',
            'status' => '状态 1有效 0无效',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
