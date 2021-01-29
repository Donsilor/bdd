<?php

namespace common\models\order;

use common\models\goods\Style;
use common\models\market\MarketCoupon;
use Yii;

/**
 * This is the model class for table "{{%order_goods}}".
 *
 * @property int $id 订单商品表索引id
 * @property int $merchant_id 商户ID
 * @property int $order_id 订单id
 * @property int $goods_id --商品id
 * @property string $style_id 款式id
 * @property string $goods_sn 商品编号
 * @property int $goods_type 产品线（商品类型）
 * @property string $goods_name 商品名称
 * @property string $goods_price 商品价格
 * @property int $goods_num 商品数量
 * @property string $goods_image 商品图片
 * @property string $goods_pay_price 商品实际成交价
 * @property int $coupon_id
 * @property string $goods_spec 商品规格
 * @property string $goods_attr 商品属性
 * @property string $cart_goods_attr 商品属性
 * @property float $exchange_rate 交易汇率
 * @property string $currency 货币
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $lettering 刻字
 */
class OrderGoods extends \common\models\base\BaseModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'order_id','style_id', 'goods_id', 'goods_type', 'goods_num', 'coupon_id', 'created_at', 'updated_at'], 'integer'],
            [['order_id', 'goods_id'], 'required'],
            [['goods_price', 'goods_pay_price','exchange_rate'], 'number'],
            [['goods_attr', 'lettering'], 'string'],
            [['goods_sn'], 'string', 'max' => 50],
            [['goods_image'], 'string', 'max' => 100],
            [['goods_spec','goods_name'], 'string', 'max' => 300],
            [['cart_goods_attr','goods_attr'], 'string', 'max' => 1024],
            [['currency'], 'string','max'=>5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户ID',
            'order_id' => '订单id',
            'goods_id' => '--商品id',
            'style_id' => '款式id',
            'goods_sn' => '商品编号',
            'goods_type' => '产品线',
            'goods_name' => '商品名称',
            'goods_price' => '商品价格',
            'goods_num' => '商品数量',
            'goods_image' => '商品图片',
            'goods_pay_price' => '商品实际成交价',
            'member_id' => '买家ID',
            'coupon_id' => '促销活动ID',
            'exchange_rate' => '交易汇率',
            'currency' => '货币',
            'goods_spec' => '商品规格',
            'goods_attr' => 'Goods Attr',
            'cart_goods_attr' => 'Goods Attr',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'lettering' => Yii::t('app', '刻字')
        ];
    }
    
    public function langModel()
    {
        return new OrderGoodsLang();
    }
    /**
     * 语言包
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(OrderGoodsLang::class, ['master_id'=>'id'])->alias('lang')->where(['lang.language' => Yii::$app->params['language']]);
    }

    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getCoupon()
    {
        return $this->hasOne(MarketCoupon::class, ['id'=>'coupon_id']);
    }

    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id'=>'order_id']);
    }


    /**
     * 对应款式模型
     * @return \yii\db\ActiveQuery
     */
    public function getStyle()
    {
        return $this->hasOne(Style::class, ['id'=>'style_id']);
    }
}
