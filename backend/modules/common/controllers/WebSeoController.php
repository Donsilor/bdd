<?php

namespace backend\modules\common\controllers;

use common\enums\OrderFromEnum;
use Yii;
use common\models\common\WebSeo;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
* WebSeo
*
* Class WebSeoController
* @package backend\modules\setting\controllers
*/
class WebSeoController extends BaseController
{
    use Curd;

    /**
    * @var WebSeo
    */
    public $modelClass = WebSeo::class;


    /**
    * 首页
    *
    * @return string
    * @throws \yii\web\NotFoundHttpException
    */
    public function actionIndex()
    {
        $search = Yii::$app->request->get('SearchModel', []);

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
                'lang' => ['meta_title'],
            ]
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams, ['platform_10', 'platforms', 'lang.meta_title']);

        $dataProvider->query->andWhere(['common_web_seo_lang.language'=>Yii::$app->language]);

        if(!empty($search['platform_10'])) {
            $where = ['or'];
            $platforms = OrderFromEnum::platformsForGroup($search['platform_10']);
            foreach ($platforms as $platform) {
                $key = 'platform_' . $platform;
                $where[][$key] = 1;
            }
            $dataProvider->query->andWhere($where);
        }

        if(!empty($search['platforms'])) {
            $key = 'platform_' . $search['platforms'];
            $dataProvider->query->andWhere([$key => 1]);
        }

        if(!empty($search['lang.meta_title'])) {
            $dataProvider->query->andWhere(['common_web_seo_lang.meta_title' => $search['lang.meta_title']]);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * ajax编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEditLang()
    {
        $id = Yii::$app->request->get('id');
        //$trans = Yii::$app->db->beginTransaction();
        $model = $this->findModel($id);
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if($model->save()){
                //多语言编辑
                $this->editLang($model,true);
                return $this->redirect(Yii::$app->request->referrer);
            }else{
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}
