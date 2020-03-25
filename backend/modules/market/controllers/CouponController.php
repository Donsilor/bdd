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
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);

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
        $returnUrl = \Yii::$app->request->get('returnUrl',['index']);
        $model = $this->findModel($id);
        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();

            $trans = \Yii::$app->db->beginTransaction();

            try {
                $model->load($post);
                if(false === $model->save()) {
                    throw new Exception($this->getError($model));
                }

                CouponService::generatedData($model);

                $trans->commit();
            } catch (\Exception $exception) {
                $trans->rollBack();
                $error = $exception->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id]), 'error');
            }

            return $this->redirect($returnUrl);
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }
}
