<?php

namespace services\goods;

use common\models\goods\RingRelation;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
use Yii;
use common\components\Service;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\models\goods\Style;


/**
 * Class TypeService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class StyleService extends Service
{

    public function getStyle($id,$language = null){
        if(empty($language)){
            $language = Yii::$app->language;
        }
        $model = Style::find()->alias('a')
            ->andWhere(['a.id'=>$id])
            ->leftJoin('{{%goods_style_lang}} b',  'b.master_id = a.id and b.language = "'.$language.'"')
            ->select(['a.*', 'b.style_name'])
            ->asArray()
            ->one();
        return $model;
    }


    public function getRelationByRing($ring_id){
        $model = RingRelation::find()
            ->where(['ring_id'=>$ring_id])
            ->asArray()
            ->all();
        return $model;
    }

    //获取对戒库存
    public function getRingStorage($id){
        $model = RingRelation::find()->alias('r')
            ->where(['r.ring_id'=>$id])
            ->leftJoin('{{%goods_style}} s','s.id = r.style_id')
            ->select('s.goods_storage')
            ->asArray()
            ->all();
        if(empty($model)) return 0;
        $ring_storage_list = array_column($model,'goods_storage');
        return min($ring_storage_list);
    }



    //获取商品信息
    public function getStyleList($type_id=null, $limit=null, $order=null, $fields=['*'],$language=null ){
        $area_id = $this->getAreaId(); 
        if(empty($language)){
            $language = Yii::$app->params['language'];
        }
        $query = Style::find()->alias('m')
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);
        if(!empty($type_id)){
            $query->andWhere(['m.type_id'=>$type_id]);
        }
        if(!empty($limit)){
            $query->limit($limit);
        }
        if($order){
            $query->orderBy($order);
        }
        $result = $query->asArray()->select($fields)->all();
        return $result;
    }





}