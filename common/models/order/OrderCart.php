<?php

namespace common\models\order;

use Yii;
use common\models\base\BaseModel;

/**
 * This is the model class for table "{{%order_cart}}".
 *
 * @property int $id 购物车id
 * @property int $merchant_id 商户ID
 * @property int $member_id 买家id
 * @property int $store_id 店铺id
 * @property int $goods_id 商品id
 * @property int $goods_type 产品线ID
 * @property string $goods_price 商品价格
 * @property int $goods_num 购买商品数量
 * @property int $group_id 分组ID
 * @property int $group_type 分组类型
 * @property string $goods_spec 商品规格
 * @property string $goods_attr 商品规格
 * @property int $status 状态
 * @property int $style_id 款式ID
 * @property string $platform_group 订单来源
 * @property string $sign 游客订单签名
 * @property string $lettering 刻字
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class OrderCart extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_cart}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'member_id', 'store_id', 'goods_id', 'goods_type', 'goods_num', 'group_id', 'group_type', 'style_id',  'status', 'created_at', 'updated_at'], 'integer'],
            [['goods_price'], 'number'],
            [['goods_spec', 'goods_attr', 'platform_group', 'sign', 'lettering'], 'string'],
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
            'member_id' => '买家id',
            'store_id' => '店铺id',
            'goods_id' => '商品id',
            'goods_type' => '产品线ID',
            'goods_price' => '商品价格',
            'goods_num' => '商品数量',
            'group_id' => '分组ID',
            'group_type' => '分组类型',
            'goods_spec' => '商品规格',
            'goods_attr' => '商品规格',
            'status' => '状态',
            'style_id' => '款式ID',
            'platform_group' => '订单来源',
            'sign' => '签名',
            'lettering' => '刻字',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
