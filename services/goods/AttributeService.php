<?php

namespace services\goods;

use Yii;
use common\components\Service;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\models\goods\Category;
use common\models\goods\AttributeValueLang;
use common\models\goods\Attribute;
use common\models\goods\AttributeLang;
use common\models\goods\AttributeValue;


/**
 * Class AttributeService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class AttributeService extends Service
{
    
    /**
     * 更新属性值
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function updateAttrValues($attr_id)
    {
        
         $sql = 'UPDATE goods_attribute_lang attr_lang,
             (
            	SELECT
            		val.attr_id,
            		val_lang.`language`,
            		GROUP_CONCAT(attr_value_name) AS attr_values
            	FROM
            		goods_attribute_value_lang val_lang
            	INNER JOIN goods_attribute_value val ON val_lang.master_id = val.id
            	WHERE
            		val.attr_id = '.$attr_id.' and val.`status`=1 
            	GROUP BY
            		val.attr_id,
            		val_lang.`language`
            ) t
            SET attr_lang.attr_values = t.attr_values
            WHERE
            	attr_lang.master_id = t.attr_id
            AND attr_lang.`language` = t.`language`
            AND attr_lang.master_id = '.$attr_id.';';
        
        return \Yii::$app->db->createCommand($sql)->execute();
    }
    /**
     * 根据产品线查询属性列表（数据行）
     * @param unknown $type_id
     * @param number $status
     * @param unknown $language
     * @return array
     */
    public function getAttrListByTypeId($type_id,$status = 1,$language = null)
    {
        if(empty($language)){
            $language = Yii::$app->language;
        }
        $query = Attribute::find()->alias("attr")
                    ->select(["attr.id","lang.attr_name",'attr.attr_type','attr.input_type','attr.is_require'])
                    ->innerJoin(AttributeLang::tableName().' lang',"attr.id=lang.master_id and lang.language='".$language."'")
                    ->where(['attr.type_id'=>$type_id]);
        if(is_numeric($status)){
            $query->andWhere(['=','attr.status',$status]);
        }
        
        $models = $query->orderBy("attr.sort asc")->asArray()->all();
        $attr_list = [];
        foreach ($models as $model){
            $attr_list[$model['attr_type']][] = $model;
        }
        ksort($attr_list);
        return $attr_list;
    }
    /**
     * 根据属性ID查询属性值列表
     * @param unknown $attr_id
     * @param unknown $language
     */
    public function getValuesByAttrId($attr_id,$status = 1,$language = null)
    {
        if(empty($language)){
            $language = Yii::$app->language;
        }
        $query = AttributeValue::find()->alias("val")
                    ->leftJoin(AttributeValueLang::tableName()." lang","val.id=lang.master_id and lang.language='".$language."'")
                    ->select(['val.id',"lang.attr_value_name"])
                    ->where(['val.attr_id'=>$attr_id]);        
        if(is_numeric($status)){
            $query->andWhere(['=','val.status',$status]);
        }
        $models = $query->orderBy('sort asc')->asArray()->all();
        
        return array_column($models,'attr_value_name','id');
    }
    /**
     * 根据属性值ID，查询属性值列表
     * @param unknown $ids
     * @param unknown $language
     * @return array
     */
    public function getValuesByValueIds($ids,$language = null)
    {
        if(empty($language)){
            $language = Yii::$app->language;
        }
        if(!is_array($ids)){
            $ids = explode(",",$ids);
        }
        $models = AttributeValueLang::find()
                    ->select(['master_id','attr_value_name'])
                    ->where(['in','master_id',$ids])
                    ->andWhere(['=','language',$language])
                    ->asArray()->all();
        return array_column($models, 'attr_value_name','master_id');
    }
}