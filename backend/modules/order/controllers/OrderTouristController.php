<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use backend\modules\order\forms\OrderTouristFollowerForm;
use common\enums\OrderFromEnum;
use common\enums\OrderStatusEnum;
use common\helpers\ResultHelper;
use common\models\order\OrderGoods;
use common\models\order\OrderTouristDetails;
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

        $searchModel->setAttributes(['is_test' => 0]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at']);

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        //站点地区
        $sitesAttach = \Yii::$app->getUser()->identity->sites_attach;
        if(is_array($sitesAttach)) {
            $orderFroms = [];

            foreach ($sitesAttach as $site) {
                $orderFroms = array_merge($orderFroms, OrderFromEnum::platformsForGroup($site));
            }

            $dataProvider->query->andWhere(['in', 'order_tourist.order_from', $orderFroms]);
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 详情展示页
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView()
    {
        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);

        $dataProvider = null;
        if (!is_null($id)) {
            $searchModel = new SearchModel([
                'model' => OrderTouristDetails::class,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => 1000,
            ]);

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            $dataProvider->query->andWhere(['=', 'order_tourist_id', $id]);

            $dataProvider->setSort(false);
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }



    /**
     * 跟进
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionEditFollower()
    {
        $this->modelClass = OrderTouristFollowerForm::class;

        $id = Yii::$app->request->get('id', null);

        $id = explode(',', $id);

        $model = $this->findModel($id[0]);

        // ajax 校验
        $this->activeFormValidate($model);
        if (Yii::$app->request->isPost) {

            foreach ($id as $item) {
                Yii::$app->services->orderTourist->changeOrderStatusFollower($item, Yii::$app->request->post());
            }

            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer), 'success');
        }

//        $where = [];
//        $where['order_sn'] = $model->order_sn;
//        $where['action_name'] = 'FOLLOWER';
//
//        $orderLog = OrderLog::find()->where($where)->all();

        return $this->renderAjax($this->action->id, [
            'model' => $model,
//            'orderLog' => $orderLog
        ]);
    }
}

