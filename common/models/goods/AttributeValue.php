<?php

namespace common\models\goods;

use Yii;
use common\models\base\BaseModel;

/**
 * This is the model class for table "{{%goods_attribute_value}}".
 *
 * @property int $id 主键
 * @property int $attr_id
 * @property int $sort 属性排序(数字越小越前)
 * @property int $status 状态(-1删除,0禁用,1-正常)
 * @property int $created_at 创建时间
 * @property int $updated_at
 */
class AttributeValue extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_attribute_value}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'required'],
            [['attr_id','erp_id', 'sort', 'status', 'created_at', 'updated_at'], 'integer'],
            [['code'], 'string','max'=>15],
            [['image'], 'string','max'=>100],
            //[['id'],'defaultAttrValueCode'],
            [['code'],'unique', 'targetAttribute'=>['code','attr_id'],
                   'comboNotUnique' => '属性标识重复' //错误信息
            ],
        ];
    }
    /* public function defaultAttrValueCode(){
        if(!$this->attr_value_code) {
            $this->attr_value_code = $this->id;
        }
    } */

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('goods_attribute', 'ID'),
            'attr_id' => Yii::t('goods_attribute', 'Attr ID'),
            'erp_id' => Yii::t('goods_attribute', 'ERP属性值ID'),
            'code' => Yii::t('goods_attribute', '标识'),
            'image' => Yii::t('goods_attribute', '图标'),
            'sort' => Yii::t('common', '排序'),
            'status' => Yii::t('common', '状态'),
            'created_at' => Yii::t('common', '创建时间'),
            'updated_at' => Yii::t('common', '更新时间'),
        ];
    }
    
    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new AttributeValueLang();
    }
    /**
     * 关联语言一对多
     * @return \yii\db\ActiveQuery
     */
    public function getLangs()
    {
         return $this->hasMany(AttributeValueLang::class,['master_id'=>'id']);      
    }
    /**
     * 关联语言一对一
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(AttributeValueLang::class, ['master_id'=>'id']);
    }
}
