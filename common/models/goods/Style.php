<?php

namespace common\models\goods;

use Yii;

/**
 * This is the model class for table "goods_style".
 *
 * @property int $id 款式ID
 * @property string $style_sn 款式编号
 * @property int $cat_id 产品分类
 * @property int $type_id 产品线
 * @property int $merchant_id 商户ID
 * @property string $style_image 商品主图
 * @property string $style_attr 款式属性
 * @property string $style_custom 款式自定义属性
 * @property string $goods_body 商品内容
 * @property string $mobile_body 手机端商品描述
 * @property string $sale_price 销售价
 * @property string $market_price 市场价
 * @property string $cost_price 成本价
 * @property int $storage_alarm 库存报警值
 * @property int $is_invoice 是否开具增值税发票 1是，0否
 * @property int $is_recommend 商品推荐 1是，0否，默认为0
 * @property int $is_lock 商品锁定 0未锁，1已锁
 * @property int $supplier_id 供应商id
 * @property int $status 款式状态 0下架，1正常，-1删除
 * @property int $verify_status 商品审核 1通过，0未通过，10审核中
 * @property string $verify_remark 审核失败原因
 * @property int $created_at 商品添加时间
 * @property int $updated_at
 */
class Style extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'goods_style';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cat_id', 'type_id', 'merchant_id', 'storage_alarm', 'is_invoice', 'is_recommend', 'is_lock', 'supplier_id', 'status', 'verify_status', 'created_at', 'updated_at'], 'integer'],
            [['merchant_id', 'style_image', 'style_attr', 'style_custom', 'goods_body', 'mobile_body', 'sale_price', 'market_price', 'supplier_id', 'status', 'verify_status'], 'required'],
            [['style_attr', 'style_custom', 'goods_body', 'mobile_body'], 'string'],
            [['sale_price', 'market_price', 'cost_price'], 'number'],
            [['style_sn'], 'string', 'max' => 50],
            [['style_image'], 'string', 'max' => 100],
            [['verify_remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('goods', 'ID'),
            'style_sn' => Yii::t('goods', 'Style Sn'),
            'cat_id' => Yii::t('goods', 'Cat ID'),
            'type_id' => Yii::t('goods', 'Type ID'),
            'merchant_id' => Yii::t('goods', 'Merchant ID'),
            'style_image' => Yii::t('goods', 'Style Image'),
            'style_attr' => Yii::t('goods', 'Style Attr'),
            'style_custom' => Yii::t('goods', 'Style Custom'),
            'goods_body' => Yii::t('goods', 'Goods Body'),
            'mobile_body' => Yii::t('goods', 'Mobile Body'),
            'sale_price' => Yii::t('goods', 'Sale Price'),
            'market_price' => Yii::t('goods', 'Market Price'),
            'cost_price' => Yii::t('goods', 'Cost Price'),
            'storage_alarm' => Yii::t('goods', 'Storage Alarm'),
            'is_invoice' => Yii::t('goods', 'Is Invoice'),
            'is_recommend' => Yii::t('goods', 'Is Recommend'),
            'is_lock' => Yii::t('goods', 'Is Lock'),
            'supplier_id' => Yii::t('goods', 'Supplier ID'),
            'status' => Yii::t('goods', 'Status'),
            'verify_status' => Yii::t('goods', 'Verify Status'),
            'verify_remark' => Yii::t('goods', 'Verify Remark'),
            'created_at' => Yii::t('goods', 'Created At'),
            'updated_at' => Yii::t('goods', 'Updated At'),
        ];
    }
    
    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new StyleLang();
    }
    /**
     * 关联语言一对多
     * @return \yii\db\ActiveQuery
     */
    public function getLangs()
    {
        return $this->hasMany(StyleLang::class,['master_id'=>'id']);
        
    }
    /**
     * 关联语言一对一
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(StyleLang::class, ['master_id'=>'id'])->alias('lang')->where(['lang.language'=>Yii::$app->language]);
    }
    /**
     * 关联产品线分类一对一
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(TypeLang::class, ['master_id'=>'type_id'])->alias('type')->where(['type.language'=>Yii::$app->language]);
    }
    /**
     * 款式分类一对一
     * @return \yii\db\ActiveQuery
     */
    public function getCate()
    {
        return $this->hasOne(CategoryLang::class, ['master_id'=>'cat_id'])->alias('cate')->where(['cate.language'=>Yii::$app->language]);
    }
}
