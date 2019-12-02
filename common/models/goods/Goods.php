<?php

namespace common\models\goods;

use Yii;
use common\models\base\BaseModel;

/**
 * This is the model class for table "goods".
 *
 * @property int $id 商品id(SKU)
 * @property int $style_id 款式id
 * @property string $goods_sn 商品编号
 * @property int $goods_type 商品类型
 * @property string $goods_image 商品主图
 * @property int $merchant_id 商户ID
 * @property int $cat_id 商品分类id
 * @property int $cat_id1 一级分类id
 * @property int $cat_id2 二级分类id
 * @property string $sale_price 商品价格
 * @property string $market_price 市场价
 * @property string $promotion_price 促销价格
 * @property int $promotion_type 促销类型 0无促销，1抢购，2限时折扣
 * @property int $storage_alarm 库存报警值
 * @property int $goods_clicks 商品点击数量
 * @property int $goods_salenum 销售数量
 * @property int $goods_collects 收藏数量
 * @property int $goods_comments 评价数
 * @property int $goods_stars 好评星级
 * @property int $goods_storage 商品库存
 * @property int $status 商品状态 0下架，1上架，10违规（禁售）
 * @property int $verify_status 商品审核 1通过，0未通过，10审核中
 * @property string $verify_remark
 * @property int $created_at 商品添加时间
 * @property int $updated_at 商品编辑时间
 */
class Goods extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'goods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['style_id', 'cat_id', 'cat_id1', 'cat_id2', 'status', 'verify_status', 'created_at', 'updated_at'], 'required'],
            [['style_id', 'goods_type', 'merchant_id', 'cat_id', 'cat_id1', 'cat_id2', 'promotion_type', 'storage_alarm', 'goods_clicks', 'goods_salenum', 'goods_collects', 'goods_comments', 'goods_stars', 'goods_storage', 'status', 'verify_status', 'created_at', 'updated_at'], 'integer'],
            [['sale_price', 'market_price', 'promotion_price'], 'number'],
            [['goods_sn'], 'string', 'max' => 50],
            [['goods_image', 'verify_remark'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('goods', 'ID'),
            'style_id' => Yii::t('goods', 'Style ID'),
            'goods_sn' => Yii::t('goods', 'Goods Sn'),
            'goods_type' => Yii::t('goods', 'Goods Type'),
            'goods_image' => Yii::t('goods', 'Goods Image'),
            'merchant_id' => Yii::t('goods', 'Merchant ID'),
            'cat_id' => Yii::t('goods', 'Cat ID'),
            'cat_id1' => Yii::t('goods', 'Cat Id1'),
            'cat_id2' => Yii::t('goods', 'Cat Id2'),
            'sale_price' => Yii::t('goods', 'Sale Price'),
            'market_price' => Yii::t('goods', 'Market Price'),
            'promotion_price' => Yii::t('goods', 'Promotion Price'),
            'promotion_type' => Yii::t('goods', 'Promotion Type'),
            'storage_alarm' => Yii::t('goods', 'Storage Alarm'),
            'goods_clicks' => Yii::t('goods', 'Goods Clicks'),
            'goods_salenum' => Yii::t('goods', 'Goods Salenum'),
            'goods_collects' => Yii::t('goods', 'Goods Collects'),
            'goods_comments' => Yii::t('goods', 'Goods Comments'),
            'goods_stars' => Yii::t('goods', 'Goods Stars'),
            'goods_storage' => Yii::t('goods', 'Goods Storage'),
            'status' => Yii::t('goods', 'Status'),
            'verify_status' => Yii::t('goods', 'Verify Status'),
            'verify_remark' => Yii::t('goods', 'Verify Remark'),
            'created_at' => Yii::t('goods', 'Created At'),
            'updated_at' => Yii::t('goods', 'Updated At'),
        ];
    }
}