<?php

namespace common\models\market;

use Yii;

/**
 * This is the model class for table "market_specials_lang".
 *
 * @property int $id 活动Id
 * @property int $master_id 商户ID
 * @property string $language 语言类型
 * @property string $title 活动名称
 * @property string $describe 活动描述
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class MarketSpecialsLang extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_specials_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['master_id', 'created_at', 'updated_at'], 'integer'],
            [['language', 'title', 'describe'], 'required'],
            [['language'], 'string', 'max' => 5],
            [['title'], 'string', 'max' => 80],
            [['describe'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '活动Id',
            'master_id' => '商户ID',
            'language' => '语言类型',
            'title' => '活动名称',
            'describe' => '活动描述',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }
}
