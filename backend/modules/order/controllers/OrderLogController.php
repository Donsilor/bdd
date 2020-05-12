<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use common\enums\OrderLogEnum;
use common\models\order\Order;
use common\models\order\OrderLog;
use services\order\OrderLogService;
use Yii;
use common\components\Curd;
use common\models\base\SearchModel;
use yii\web\NotFoundHttpException;

/**
 * Default controller for the `order` module
 */
class OrderLogController extends BaseController
{

    /**
     * @var OrderLog
     */
    public $modelClass = OrderLog::class;

    /**
     * Renders the index view for the module
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $id = Yii::$app->request->get('id', null);

        $model = Order::findOne($id);

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => 1000,
            'relations' => []
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        //创建时间过滤
//        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
//            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
//            $dataProvider->query->andFilterWhere(['between', 'created_at', strtotime($start_date), strtotime($end_date) + 86400]);
//        }

        $dataProvider->query->andWhere(['order_sn'=>$model->order_sn]);

        return $this->render($this->action->id, [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}

