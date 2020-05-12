<?php


namespace backend\modules\order\controllers;


use backend\controllers\BaseController;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\order\Order;
use common\models\pay\WireTransfer;
use Yii;

class WireTransferController extends BaseController
{

    /**
     * @var Order
     */
    public $modelClass = WireTransfer::class;

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
            'relations' => [
            ]
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, []);

        return $this->render($this->action->id, [
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
    public function actionAjaxEdit()
    {
        $returnUrl = \Yii::$app->request->get('returnUrl',['index']);

        $model = WireTransfer::findOne(Yii::$app->request->get('id', null));

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(\Yii::$app->request->post())) {

            if(!$model->validate()) {
                return $this->message($this->getError($model), $this->redirect($returnUrl), 'error');
            }

            $trans = \Yii::$app->db->beginTransaction();

            try {
                //\Yii::$app->services->card->generateCards($model->toArray(), $model->count);

                $trans->commit();

            } catch (\Exception $exception) {

                $trans->rollBack();
                return $this->message($exception->getMessage(), $this->redirect($returnUrl), 'error');
            }

            $this->redirect($returnUrl);
        }

        //\Yii::$app->cache->set('actionAjaxEdit-'.\Yii::$app->getUser()->id, true);

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}