<?php


namespace backend\modules\statistics\controllers;


use backend\controllers\BaseController;
use common\helpers\ExcelHelper;
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
        $time = time();

        $start_time = strtotime(date('Y-m-01'));
        $end_time = $time;

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'pageSize' => 100,
            'relations' => []
        ]);

        $queryParams = Yii::$app->request->queryParams;

        //站点地区
        if(isset($queryParams['SearchModel']['platform_group']) && !empty($queryParams['SearchModel']['platform_group'])) {
            $queryParams['SearchModel']['platform_group'] = implode(',', $queryParams['SearchModel']['platform_group']);
        }

        //PC与移动
        if(isset($queryParams['SearchModel']['platform_id'])) {
            $platform_ids = [
                '1' => '10,20,30,40',
                '2' => '11,21,31,41',
            ];

            if(isset($platform_ids[$queryParams['SearchModel']['platform_id']])) {
                $queryParams['SearchModel']['platform_id'] = $platform_ids[$queryParams['SearchModel']['platform_id']];
            }
            else {
                unset($queryParams['SearchModel']['platform_id']);
            }
        }

        //时间
        if(isset($queryParams['SearchModel']['datetime']) && !empty($queryParams['SearchModel']['datetime'])) {
            list($start_time, $end_time) = explode('/', $queryParams['SearchModel']['datetime']);
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
        }

        $dataProvider = $searchModel->search($queryParams, ['datetime']);

//        $dataProvider->query->select(['statistics_order_view.*']);

        $searchModel->platform_group = Yii::$app->request->queryParams['SearchModel']['platform_group']??[];
        $searchModel->platform_id = Yii::$app->request->queryParams['SearchModel']['platform_id']??'';
        $searchModel->datetime = date('Y-m-d', $start_time) . '/' . date('Y-m-d', $end_time);

        //导出
        if (Yii::$app->request->get('action') === 'export') {
//            $query = Yii::$app->request->queryParams;
//            unset($query['action']);
//            if (empty(array_filter($query))) {
//                return $this->message('导出条件不能为空', $this->redirect(['index']), 'warning');
//            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;
            $this->getExport($list, $searchModel);
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function getExport($list, $searchModel)
    {
        $status = [
            '1' => '未付款',
            '2' => '已销售',
            '3' => '已关闭',
        ];

        $typeMap = [];
        foreach ($list as $item) {
            //时间段
            //站点地区
            //客户端
            //订单状态
            //订单总数量
            //订单总额
            //{}
            //商品总量
            //{}
            $data = $item->getOrderProductTypeGroupData($searchModel);

            $typeMap[$item->id] = [];
            foreach ($data as $datum) {
                $typeMap[$item->id][$datum['goods_type']] = $datum;
            }
        }

        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['时间段', 'id', 'function', function($row) use($searchModel) {
                return $searchModel->datetime;
            }],
            ['站点地区', 'platform_group', 'function', function($row) {
                return \common\enums\OrderFromEnum::getValue($row['platform_group'], 'groups');
            }],
            ['客户端', 'platform_id', 'function', function($row) {
                return \common\enums\OrderFromEnum::getValue($row['platform_id']);
            }],
            ['订单状态', 'platform_group', 'function', function($row) use ($status) {
                return $status[$row['status']]??'';
            }],
            ['订单总数量', 'id', 'function', function($row) use($searchModel) {
                return $row->getOrderCount($searchModel);
            }],

            ['订单总额（CNY）', 'id', 'function', function($row) use($searchModel) {
                return $row->getOrderMoneySum($searchModel);
            }],
            ['戒指', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][2]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['对戒', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][19]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['裸钻', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][15]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['戒托', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][12]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['项链', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][4]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['吊坠', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][5]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['手链', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][8]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['手镯', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][9]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['耳钉', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][6]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['耳环', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][7]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['挂件', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][17]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['摆件', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][16]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],
            ['其他', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][18]??[];
                return sprintf("%.2f", $typeData['sum']??0);
            }],

            ['商品总量', 'id', 'function', function($row) use($searchModel) {
                return $row->getOrderProductCount($searchModel);
            }],
            ['戒指', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][2]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['对戒', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][19]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['裸钻', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][15]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['戒托', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][12]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['项链', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][4]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['吊坠', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][5]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['手链', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][8]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['手镯', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][9]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['耳钉', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][6]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['耳环', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][7]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['挂件', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][17]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['摆件', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][16]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
            ['其他', 'id', 'function', function($row) use($typeMap) {
                $typeData = $typeMap[$row->id][18]??[];
                return sprintf("%d", $typeData['count']??0);
            }],
        ];

        return ExcelHelper::exportData($list, $header, '订单统计导出_' . date('YmdHis',time()));
    }
}