<?php

namespace common\models\order;

use common\models\market\MarketCoupon;
use Yii;

/**
 * This is the model class for table "{{%order_tourist_details}}".
 *
 * @property int $id 主键
 * @property int $order_tourist_id 游客订单ID
 * @property string $style_id 款式编号
 * @property int $goods_id 商品id
 * @property string $goods_sn 商品编号
 * @property int $goods_type 产品线ID
 * @property string $goods_name 商品名称
 * @property string $goods_price 商品价格
 * @property string $goods_pay_price 商品实际成交价
 * @property int $goods_num 购买商品数量
 * @property string $goods_image 商品图片
 * @property int $coupon_id 促销活动ID（抢购ID/限时折扣ID/优惠套装ID）与goods_type搭配使用
 * @property int $group_id 分组ID
 * @property int $group_type 分组类型 1对戒 2定制 0单品
 * @property string $goods_spec 商品规格
 * @property string $goods_attr
 * @property string $cart_goods_attr
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $lettering 刻字
 */
class OrderTouristDetails extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_tourist_details}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_tourist_id'], 'required'],
            [['order_tourist_id', 'goods_id','style_id', 'goods_type', 'goods_num', 'coupon_id', 'group_id', 'group_type', 'created_at', 'updated_at'], 'integer'],
            [['goods_price', 'goods_pay_price'], 'number'],
            [['goods_spec', 'cart_goods_attr', 'goods_attr', 'lettering'], 'string'],
            [['goods_sn'], 'string', 'max' => 50],
            [['goods_name'], 'string', 'max' => 300],
            [['goods_image'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', '主键'),
            'order_tourist_id' => Yii::t('app', '游客订单ID'),
            'style_id' => Yii::t('app', '款式编号'),
            'goods_id' => Yii::t('app', '商品id'),
            'goods_sn' => Yii::t('app', '商品编号'),
            'goods_type' => Yii::t('app', '产品线ID'),
            'goods_name' => Yii::t('app', '商品名称'),
            'goods_price' => Yii::t('app', '商品价格'),
            'goods_pay_price' => '商品实际成交价',
            'goods_num' => Yii::t('app', '购买商品数量'),
            'goods_image' => Yii::t('app', '商品图片'),
            'coupon_id' => Yii::t('app', '促销活动ID（抢购ID/限时折扣ID/优惠套装ID）与goods_type搭配使用'),
            'group_id' => Yii::t('app', '分组ID'),
            'group_type' => Yii::t('app', '分组类型 1对戒 2定制 0单品'),
            'goods_spec' => Yii::t('app', '商品规格'),
            'goods_attr' => Yii::t('app', 'Goods Attr'),
            'cart_goods_attr' => Yii::t('app', 'Cart Goods Attr'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'lettering' => Yii::t('app', '刻字')
        ];
    }

    /**
     * 对应订单商品信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(OrderTourist::class,['id'=>'order_tourist_id']);
    }

    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getCoupon()
    {
        return $this->hasOne(MarketCoupon::class, ['id'=>'coupon_id']);
    }
}
