<?php

namespace common\components;

use Yii;
use common\enums\CacheEnum;
use common\models\goods\AttributeValue;
use common\models\goods\AttributeValueLang;
use common\enums\StatusEnum;

/**
 * Class Attribute
 * @package common\components
 * @author gaopeng
 */
class Attr
{
    /**
     * 返回属性名称
     *
     * @param string $name 字段名称
     * @param bool $noCache true 不从缓存读取 false 从缓存读取
     * @return bool|string
     */
    public function attrName($attr_id, $language = null,$noCache = false,$merchant_id = '')
    {
        if($language == null) {
            $language = \Yii::$app->params['language'];
        }
        $result = $this->getAttr($attr_id , $noCache);
        return $result['info'][$language]['attr_name']??'';
    }
    /**
     * 属性值列表
     * @param unknown $attr_id
     * @param unknown $language
     * @param string $noCache
     * @return array
     */
    public function valueList($attr_id, $language = null,$noCache = false,$merchant_id = '')
    {
        if($language == null) {
            $language = \Yii::$app->params['language'];
        }
        $result = $this->getAttr($attr_id , $noCache);
        return $result['value_list'][$language]??[];
    }   
    
    /**
     * 返回属性值名称
     * @param unknown $value_id
     * @param unknown $language
     * @param string $noCache
     * @return string
     */
    public function valueName($value_id, $language = null,$noCache = false , $merchant_id = '')
    {
        if($language == null) {
            $language = \Yii::$app->params['language'];
        }
        $result = $this->getAttrValue($value_id,$noCache);
        return $result[$language]['value_name']??'';
    }
    /**
     * 查询属性及其属性值
     * @param unknown $attr_id
     * @param string $noCache
     * @return array
     */
    public function getAttr($attr_id , $noCache = false , $merchant_id = '')
    {
        $cacheKey = CacheEnum::getPrefix('goodsAttr',$merchant_id).':'.$attr_id;
        if (!($info = Yii::$app->cache->get($cacheKey)) || $noCache == true) {
            $info = ['info','value_list'];
            $models = AttributeValueLang::find()
                ->select(['master_id','language','attr_name'])
                ->where(['master_id'=>$attr_id])
                ->asArray()->all();
            
            foreach ($models as $row) {
                $info['info'][$row['language']] = [
                        'attr_id'=>$row['master_id'],
                        'attr_name'=>$row['attr_name']
                ];
            }
            $models = AttributeValue::find()->alias("val")
                ->leftJoin(AttributeValueLang::tableName()." lang","val.id=lang.master_id")
                ->select(['val.id',"lang.attr_value_name"])
                ->where(['val.attr_id'=>$attr_id,'val.status'=>StatusEnum::ENABLED])
                ->orderBy('val.sort asc,val.id asc')
                ->asArray()->all();
            
            $value_list = [];
            foreach ($models as $row) {
                $value_list[$row['language']][] = [
                    'value_id'=>$row['id'],
                    'value_name'=>$row['attr_value_name'],
                ];
            }
            $info['value_list'] = $value_list;
            
            $duration = (int) rand(3600*24,3600*24+3600);//防止缓存穿透
            // 设置缓存
            Yii::$app->cache->set($cacheKey, $info,$duration);
        }
        return $info;
    }
    /**
     * 查询属性值名称
     * @param unknown $value_id
     * @param string $noCache
     * @return array
     */
    public function getAttrValue($value_id , $noCache = false , $merchant_id = '')
    {
        $cacheKey = CacheEnum::getPrefix('goodsAttrValue',$merchant_id).':'.$value_id;
        if (!($info = Yii::$app->cache->get($cacheKey)) || $noCache == true) {            
            
            $models = AttributeValue::find()->alias("val")
                ->leftJoin(AttributeValueLang::tableName()." lang","val.id=lang.master_id")
                ->select(['val.id',"lang.attr_value_name",'lang.language'])
                ->where(['val.id'=>$value_id])
                ->asArray()->all();
            
            $info = [];
            foreach ($models as $row) {
                $info[$row['language']] = [
                        'attr_value_id'=>$row['id'],
                        'attr_value_name'=>$row['attr_value_name'],
                ];
            }
            $duration = (int) rand(3600*24,3600*24+3600);//防止缓存穿透
            // 设置缓存
            Yii::$app->cache->set($cacheKey, $info,$duration);
        }
       
        return $info;
    }  
    
}