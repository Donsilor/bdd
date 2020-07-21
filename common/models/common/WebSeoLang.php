<?php

namespace common\models\common;

use Yii;

/**
 * This is the model class for table "{{%web_seo_lang}}".
 *
 * @property int $id 主键
 * @property int $master_id
 * @property string $language
 * @property string $meta_title 页面标题
 * @property string $meta_desc Mata说明
 * @property string $meta_word Mata关键字
 */
class WebSeoLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%common_web_seo_lang}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['master_id', 'language'], 'required'],
            [['master_id'], 'integer'],
            [['language'], 'string', 'max' => 5],
            [['meta_title'], 'string', 'max' => 100],
            [['meta_desc', 'meta_word'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'master_id' => Yii::t('web_seo_lang', 'Master ID'),
            'language' => Yii::t('web_seo_lang', 'Language'),
            'meta_title' => 'title',
            'meta_desc' => 'description',
            'meta_word' => 'keywords',
        ];
    }
}
