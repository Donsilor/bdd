<?php

namespace common\models\goods;

use Yii;

/**
 * This is the model class for table "goods_style_lang".
 *
 * @property int $id 商品公共表id
 * @property int $master_id 款式ID
 * @property string $language 语言类型
 * @property string $style_name 款式名称
 * @property string $style_desc 商品广告词
 * @property string $style_image 商品主图
 * @property string $style_attr 商品属性
 * @property string $style_custom 商品自定义属性
 * @property string $goods_body 商品内容
 * @property string $mobile_body 手机端商品描述
 * @property string $meta_title SEO标题
 * @property string $meta_word SEO关键词
 * @property string $meta_desc SEO描述
 */
class StyleLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'goods_style_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['master_id'], 'integer'],
            [['style_name', 'style_image'], 'required'],
            [['style_attr', 'style_custom', 'goods_body', 'mobile_body'], 'string'],
            [['language'], 'string', 'max' => 5],
            [['style_name'], 'string', 'max' => 50],
            [['style_desc'], 'string', 'max' => 150],
            [['style_image'], 'string', 'max' => 100],
            [['meta_title', 'meta_word', 'meta_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('goods', 'ID'),
            'master_id' => Yii::t('goods', 'Master ID'),
            'language' => Yii::t('goods', 'Language'),
            'style_name' => Yii::t('goods', 'Style Name'),
            'style_desc' => Yii::t('goods', 'Style Desc'),
            'style_image' => Yii::t('goods', 'Style Image'),
            'style_attr' => Yii::t('goods', 'Style Attr'),
            'style_custom' => Yii::t('goods', 'Style Custom'),
            'goods_body' => Yii::t('goods', 'Goods Body'),
            'mobile_body' => Yii::t('goods', 'Mobile Body'),
            'meta_title' => Yii::t('goods', 'Meta Title'),
            'meta_word' => Yii::t('goods', 'Meta Word'),
            'meta_desc' => Yii::t('goods', 'Meta Desc'),
        ];
    }
}