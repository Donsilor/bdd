<?php

namespace common\models\common;

use Yii;

/**
 * This is the model class for table "{{%web_seo}}".
 *
 * @property int $id 主键
 * @property string $platform 平台
 * @property string $page_name 页面名称
 * @property string $route 页面名称
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class WebSeo extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%common_web_seo}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['page_name', 'platform', 'route'], 'required'],
            [['page_name'], 'requiredPageName'],
            [['created_at', 'updated_at'], 'safe'],
            [['page_name'], 'string', 'max' => 100],
            [['route'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'platform' => '客户端',
            'page_name' => '页面',
            'route' => '路由',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }


   //认证page_name
    public function requiredPageName($attribute, $params){
        if(!ctype_alnum($this->page_name)){
            $this->addError($attribute, '名称必须是字母与数字组成');
        }

    }


    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new WebSeoLang();
    }

    public function getLangs()
    {
        return $this->hasMany(WebSeoLang::class,['master_id'=>'id']);

    }

    /**
     * 关联语言一对一
     * @param string $languge
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        $query = $this->hasOne(WebSeoLang::class, ['master_id'=>'id']);
        return $query;
    }
}
