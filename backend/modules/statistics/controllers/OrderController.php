<?php


namespace backend\modules\statistics\controllers;


use backend\controllers\BaseController;
use common\models\base\SearchModel;
use common\models\statistics\OrderView;
use Yii;

class OrderController extends BaseController
{


    /**
     * @var OrderView
     */
    public $modelClass = OrderView::class;

    public function actionIndex()
    {
//        $time = time();

//        $start_time = 0;
//        $end_time = $time;

//        $order = <<<DOM
//(SELECT `og`.`style_id`,COUNT(`og`.`style_id`) AS count,(CASE `o`.`order_from` WHEN 10 THEN 'HK'
//                        WHEN 11 THEN 'HK'
//                        WHEN 20 THEN 'CN'
//                        WHEN 21 THEN 'CN'
//                        WHEN 30 THEN 'US'
//                        WHEN 31 THEN 'US'
//              END) AS order_from
//FROM `order` `o`
//RIGHT JOIN `order_goods` AS `og` ON  `o`.`id`=`og`.`order_id`
//WHERE `o`.`created_at` BETWEEN :start_time and :end_time and order_status>10 GROUP BY `og`.`style_id`,CASE `o`.`order_from` WHEN 10 THEN 'HK' WHEN 11 THEN 'HK' WHEN 20 THEN 'CN' WHEN 21 THEN 'CN' WHEN 30 THEN 'US' WHEN 31 THEN 'US' END) AS og
//DOM;

//        $orderCart = <<<DOM
//(SELECT COUNT(`oc`.`style_id`) as count,oc.style_id,oc.platform_group FROM `order_cart` oc WHERE `created_at` BETWEEN :start_time and :end_time GROUP BY `oc`.`style_id`, `oc`.`platform_group`) as oc
//DOM;

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'pageSize' => $this->pageSize,
            'relations' => []
        ]);

        $queryParams = Yii::$app->request->queryParams;

        //站点地区
//        if(isset($queryParams['SearchModel']['platform_group']) && !empty($queryParams['SearchModel']['platform_group'])) {
//            $queryParams['SearchModel']['platform_group'] = implode(',', $queryParams['SearchModel']['platform_group']);
//        }

        //时间
//        if(isset($queryParams['SearchModel']['datetime']) && !empty($queryParams['SearchModel']['datetime'])) {
//            list($start_time, $end_time) = explode('/', $queryParams['SearchModel']['datetime']);
//            $start_time = strtotime($start_time);
//            $end_time = strtotime($end_time)+86400;
//        }

        $dataProvider = $searchModel->search($queryParams, ['datetime']);

//        $dataProvider->query->leftJoin($order, 'statistics_style_view.style_id=og.style_id and og.order_from=statistics_style_view.platform_group', ['start_time'=>$start_time, 'end_time'=>$end_time]);
//        $dataProvider->query->leftJoin($orderCart, 'statistics_style_view.style_id=oc.style_id and oc.platform_group=statistics_style_view.platform_group', ['start_time'=>$start_time, 'end_time'=>$end_time]);

        $dataProvider->query->select(['statistics_order_view.*']);

//        $dataProvider->query->andWhere(['or', ['>','og.count','0'], ['>','oc.count','0']]);

        $dataProvider->query->asArray();

//        $dataProvider->setSort([
//            'attributes' => [
//                'count',
//                'cart_count',
//                'style_id',
//                'type_id',
//                'style_name',
//                'platform_group',
//                'name',
//            ],
//            'defaultOrder' => [
//                'style_id' => 'desc',
//                'platform_group' => 'desc',
//            ],
//        ]);

        //导出
//        if(Yii::$app->request->get('action') === 'export'){
//            $query = Yii::$app->request->queryParams;
//            unset($query['action']);
//            if(empty(array_filter($query))){
//                return $this->message('导出条件不能为空', $this->redirect(['index']), 'warning');
//            }
//            $dataProvider->setPagination(false);
//            $list = $dataProvider->models;
//            $this->getExport($list);
//        }

//        $searchModel->platform_group = Yii::$app->request->queryParams['SearchModel']['platform_group']??[];

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}