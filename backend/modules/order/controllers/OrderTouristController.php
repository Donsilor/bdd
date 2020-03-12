<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use common\enums\OrderStatusEnum;
use common\helpers\ResultHelper;
use common\models\order\OrderGoods;
use Yii;
use common\components\Curd;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\order\OrderTourist;
use Exception;
use yii\web\NotFoundHttpException;
use common\enums\FollowStatusEnum;
use common\enums\AuditStatusEnum;
use backend\modules\order\forms\DeliveryForm;
use common\enums\DeliveryStatusEnum;

/**
 * Default controller for the `order` module
 */
class OrderTouristController extends BaseController
{
    use Curd;

    /**
     * @var OrderTourist
     */
    public $modelClass = OrderTourist::class;

    /**
     * Renders the index view for the module
     * @return string
     * @throws NotFoundHttpException
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
            'relations' => []
        ]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at']);

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}

