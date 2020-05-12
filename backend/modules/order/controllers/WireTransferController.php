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
}