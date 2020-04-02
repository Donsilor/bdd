<?php

namespace backend\modules\market\controllers;

//use addons\TinyShop\merchant\forms\CouponTypeForm;
use backend\controllers\BaseController;
use common\components\Curd;
use common\enums\AreaEnum;
use common\enums\LanguageEnum;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\market\MarketSpecials;
use services\market\CouponService;
use yii\base\Exception;

/**
 * Default controller for the `market` module
 */
class SpecialsController extends BaseController
{
    use Curd;

    /**
     * @var MarketSpecials
     */
    public $modelClass = MarketSpecials::class;

    public function actionTest()
    {

        $areaId = $this->getAreaId();
        try {
            $data = CouponService::fetchCoupon(10, 4);
        } catch (\Exception $exception) {
            $data=$exception->getMessage();
        }

        var_dump($data);exit;
    }

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        //$data = CouponService::getCouponList(1,2,3);var_dump($data);exit;

        $searchParams = \Yii::$app->request->get('SearchModel');

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [
                'lang.title'
            ], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
                'lang' => ['title'],
            ]
        ]);

        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams, ['created_at']);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);

        if(!empty($searchParams['created_at'])) {
            $time = time();

            //1=未开始，2=进行中，3=已结束
            if($searchParams['created_at']==1) {
                $dataProvider->query->andWhere(['>', 'start_time', $time]);
            }

            if($searchParams['created_at']==2) {
                $dataProvider->query
                    ->andWhere(['<=', 'start_time', $time])
                    ->andWhere(['>=', 'end_time', $time]);
            }

            if($searchModel['created_at']==3) {
                $dataProvider->query
                    ->andWhere(['<', 'end_time', $time]);
            }
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed
     */
    public function actionAjaxEditLang()
    {
        $id = \Yii::$app->request->get('id', null);
        $returnUrl = \Yii::$app->request->get('returnUrl',['index']);
        $model = $this->findModel($id);
        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();
            $lang = \Yii::$app->request->post('MarketSpecialsLang');
            $area = \Yii::$app->request->post('MarketSpecialsArea');

            $trans = \Yii::$app->db->beginTransaction();

            try {
                $model->load($post);

                //添加人
                if(empty($model->user_id)) {
                    $model->user_id = \Yii::$app->user->id;
                }

                if(false === $model->save()) {
                    throw new Exception($this->getError($model));
                }

                //保存语言
                foreach (LanguageEnum::getKeys() as $key) {
                    $langModel = $model->getLangOne($key);

                    $langModel->setAttributes($lang[$key]);
                    if(false === $langModel->save()){
                        throw new Exception($this->getError($langModel));
                    }
                }

                //保存地区
//                foreach (AreaEnum::getKeys() as $key) {
//                    $AreaModel = $model->getAreaOne($key);
//                    $AreaModel->setAttributes($area[$key]);
//                    if(false === $AreaModel->save()){
//                        throw new Exception($this->getError($AreaModel));
//                    }
//                }

                $trans->commit();
            } catch (\Exception $exception) {
                $trans->rollBack();
                $error = $exception->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id]), 'error');
            }

            return $this->redirect($returnUrl);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}
