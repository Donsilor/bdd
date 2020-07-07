<?php

namespace api\modules\web\controllers\common;

use common\helpers\ResultHelper;
use common\models\common\WebSeo;
use common\models\common\WebSeoLang;
use wsl\ip2location\Ip2Location;
use Yii;
use api\controllers\OnAuthController;

/**
 * Class ProvincesController
 * @package api\modules\v1\controllers\member
 * @author jianyan74 <751393839@qq.com>
 */
class SeoController extends OnAuthController
{
    /**
     * @var Provinces
     */
    public $modelClass = WebSeo::class;
    protected $authOptional = ['index','area'];
    /**
     * 根据分类ID获取广告图
     *
     * @param int $pid
     * @return array|yii\data\ActiveDataProvider
     */
    public function actionIndex()
    {
        $type = Yii::$app->request->get('type');
        if($type == null) return ResultHelper::api(422, '缺省参数');

        $model = $this->getSeoInfo($type);

        if(!$model) {
            $model = $this->getSeoInfo('default');
        }

        return $model;
    }

    private function getSeoInfo($type)
    {
        $language = Yii::$app->params['language'];
        $model = $this->modelClass::find()->alias('m');

        if(is_numeric($type)) {
            $model = $model->where(['m.id'=>$type]);
        }
        else {
            $model = $model->where(['m.page_name'=>$type, 'platform'=>$this->platform]);
        }

        $model = $model->leftJoin(WebSeoLang::tableName().' lang','lang.master_id = m.id and lang.language =  "'.$language.'"')
            ->select(['lang.*'])
            ->asArray()
            ->one();
        return $model;
    }


    public function actionArea(){
        $ipLocation = new Ip2Location();
//        $locationModel = $ipLocation->getLocation('8.8.8.8');
//        $ip = Yii::$app->request->getUserIP();
//        $ip = Yii::$app->request->userIP;
        $ip = '122.9.255.255';
        $locationModel = $ipLocation->getLocation($ip);
        print_r($locationModel->toArray());
    }


}