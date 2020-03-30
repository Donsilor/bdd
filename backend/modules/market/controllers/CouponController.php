<?php

namespace backend\modules\market\controllers;

//use addons\TinyShop\merchant\forms\CouponTypeForm;
use backend\controllers\BaseController;
use common\components\Curd;
use common\enums\AreaEnum;
use common\enums\LanguageEnum;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\market\MarketCoupon;
use common\models\market\MarketSpecials;
use services\goods\TypeService;
use services\market\CouponService;
use yii\base\Exception;

/**
 * Default controller for the `market` module
 */
class CouponController extends BaseController
{
    use Curd;

    /**
     * @var MarketCoupon
     */
    public $modelClass = MarketCoupon::class;

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
//        $dataProvider->query
//            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);

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
    public function actionEdit()
    {
        $id = \Yii::$app->request->get('id', null);
        $specials_id = \Yii::$app->request->get('specials_id', null);
        $returnUrl = \Yii::$app->request->get('returnUrl',['index', 'specials_id'=>$specials_id]);

        $model = $this->findModel($id);

        $specials = MarketSpecials::findOne($model->specials_id??$specials_id);

        if(!$specials) {
            return $this->redirect($returnUrl);
        }

        $returnUrl = \Yii::$app->request->get('returnUrl',['index', 'SearchModel'=>['specials_id'=>$specials->id]]);

        $model->setScenario('edit-'.$specials->product_range.'-'.$specials->type);

        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();

            $trans = \Yii::$app->db->beginTransaction();

            try {
                $model->load($post);

                //添加人
                if(empty($model->user_id)) {
                    $model->user_id = \Yii::$app->user->id;
                    $model->specials_id = $specials->id;
                    $model->type = $specials->type;
                }

                if(false === $model->save()) {
                    throw new Exception($this->getError($model));
                }

                CouponService::generatedData($model);

                $trans->commit();
            } catch (\Exception $exception) {
                $trans->rollBack();
                $error = $exception->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id,'specials_id'=>$specials->id]), 'error');
            }

            return $this->redirect($returnUrl);
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'specials' => $specials,
        ]);
    }
}
