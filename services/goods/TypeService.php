<?php

namespace services\goods;

use Yii;
use common\components\Service;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\models\goods\Type;


/**
 * Class TypeService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class TypeService extends Service
{
    
    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getDropDown($pid = null,$language = null)
    {
        if(empty($language)){
            $language = Yii::$app->language;
        }
        $query = Type::find()->alias('a')
                    ->where(['status' => StatusEnum::ENABLED])
                    ->andWhere(['merchant_id' => Yii::$app->services->merchant->getId()]);
        
        if($pid !== null){
            $query->andWhere(['a.pid'=>$pid]);
        }
        
        $models =$query->leftJoin('{{%goods_type_lang}} b', 'b.master_id = a.id and b.language = "'.$language.'"')
            ->select(['a.*', 'b.type_name'])
            ->orderBy('sort asc,created_at asc')
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($models);
        return ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models,'id','type_name'), 'id', 'type_name');
    }
}