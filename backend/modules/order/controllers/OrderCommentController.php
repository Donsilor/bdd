<?php


namespace backend\modules\order\controllers;

use common\components\Curd;
use Yii;
use backend\controllers\BaseController;
use common\models\base\SearchModel;
use common\models\order\OrderComment;
use common\models\order\OrderTourist;

class OrderCommentController extends BaseController
{
    use Curd;

    /**
     * @var OrderTourist
     */
    public $modelClass = OrderComment::class;


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

        //站点地区
//        $sitesAttach = \Yii::$app->getUser()->identity->sites_attach;
//        if(is_array($sitesAttach)) {
//            $orderFroms = [];
//
//            foreach ($sitesAttach as $site) {
//                $orderFroms = array_merge($orderFroms, OrderFromEnum::platformsForGroup($site));
//            }
//
//            $dataProvider->query->andWhere(['in', 'order_tourist.order_from', $orderFroms]);
//        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }



    public function actionEditAudit()
    {
        $id = Yii::$app->request->get('id', null);
        $order = Yii::$app->request->post('OrderAuditForm', []);

//        $this->modelClass = OrderAuditForm::class;

        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);

        if (Yii::$app->request->isPost) {

//            try {
//                Yii::$app->services->order->changeOrderStatusAudit($id, $order['audit_status'], $order['audit_remark']??'');
//            } catch (\Exception $exception) {
//                $this->message($exception->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
//            }

            return $this->redirect(Yii::$app->request->referrer);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}