<?php

namespace backend\modules\statistics\controllers;


use backend\controllers\BaseController;
use common\enums\OrderFromEnum;
use common\enums\StatusEnum;
use common\helpers\ExcelHelper;
use common\models\base\SearchModel;
use common\models\goods\AttributeSpec;
use common\models\goods\Style;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\order\OrderAddress;
use common\models\order\OrderTourist;
use common\models\statistics\StyleView;
use yii\db\Query;
use yii\web\NotFoundHttpException;
use Yii;

class StyleController extends BaseController
{


    /**
     * @var StyleView
     */
    public $modelClass = StyleView::class;

//    public function _actionInde() {
//
//        $order = <<<DOM
//(SELECT `og`.`style_id`,COUNT(`og`.`style_id`) AS count,`o`.`order_from`
//FROM `order` `o`
//RIGHT JOIN `order_goods` AS `og` ON  `o`.`id`=`og`.`order_id`
//WHERE 1 GROUP BY `og`.`style_id`,`o`.`order_from`) AS og
//DOM;
//
//        $list = StyleView::find()->alias('ssv')
//            ->select(['ssv.style_id','ssv.type_id','ssv.platform','ssv.platform_group','ssv.name','og.count'])
//            ->leftJoin($order, 'ssv.platform=og.order_from AND ssv.style_id=og.style_id')
//            ->asArray()
//            ->orderBy(' og.count desc')
//
//            ->all();
//        foreach ($list as $item) {
//            var_dump($item);
//        }
//    }


    /**
     * Renders the index view for the module
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $time = time();

        $start_time = 0;
        $end_time = $time;

        $order = <<<DOM
(SELECT `og`.`style_id`,COUNT(`og`.`style_id`) AS count,(CASE `o`.`order_from` WHEN 10 THEN 'HK' 
                        WHEN 11 THEN 'HK' 
                        WHEN 20 THEN 'CN' 
                        WHEN 21 THEN 'CN' 
                        WHEN 30 THEN 'US' 
                        WHEN 31 THEN 'US'
              END) AS order_from
FROM `order` `o`
RIGHT JOIN `order_goods` AS `og` ON  `o`.`id`=`og`.`order_id`
WHERE `o`.`created_at` BETWEEN :start_time and :end_time GROUP BY `og`.`style_id`,`o`.`order_from`) AS og
DOM;

        $orderCart = <<<DOM
(SELECT COUNT(`oc`.`style_id`) as count,oc.style_id,oc.platform_group FROM `order_cart` oc WHERE `created_at` BETWEEN :start_time and :end_time GROUP BY `oc`.`style_id`, `oc`.`platform_group`) as oc
DOM;

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'style_id' => 'desc',
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
            ]
        ]);

        $queryParams = Yii::$app->request->queryParams;

        //站点地区
        if(isset($queryParams['SearchModel']['platform_group']) && !empty($queryParams['SearchModel']['platform_group'])) {
            $queryParams['SearchModel']['platform_group'] = implode(',', $queryParams['SearchModel']['platform_group']);
        }

        //时间
        if(isset($queryParams['SearchModel']['datetime']) && !empty($queryParams['SearchModel']['datetime'])) {
            list($start_time, $end_time) = explode('/', $queryParams['SearchModel']['datetime']);
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time)+86400;
        }

        $dataProvider = $searchModel->search($queryParams, ['datetime']);

        $dataProvider->query->leftJoin($order, 'statistics_style_view.style_id=og.style_id and og.order_from=statistics_style_view.platform_group', ['start_time'=>$start_time, 'end_time'=>$end_time]);
        $dataProvider->query->leftJoin($orderCart, 'statistics_style_view.style_id=oc.style_id and oc.platform_group=statistics_style_view.platform_group', ['start_time'=>$start_time, 'end_time'=>$end_time]);

        $dataProvider->query->select(['statistics_style_view.*','og.count','oc.count as cart_count']);
        $dataProvider->query->asArray();

        $dataProvider->setSort([
            'attributes' => [
                'count',
                'cart_count',
                'style_id',
                'type_id',
                'style_name',
                'platform_group',
                'name',
            ],
            'defaultOrder' => [
                'style_id' => 'desc',
            ],
        ]);

        $searchModel->platform_group = Yii::$app->request->queryParams['SearchModel']['platform_group']??[];

        //导出
        if(Yii::$app->request->get('action') === 'export'){
            $query = Yii::$app->request->queryParams;
            unset($query['action']);
            if(empty(array_filter($query))){
                return $this->message('导出条件不能为空', $this->redirect(['index']), 'warning');
            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;
            $this->getExport($list);
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function getExport($list)
    {
        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['款号', 'style_sn', 'text'],
            ['商品名称', 'style_sn', 'text'],
            ['产品线', 'type_id', 'function', function($row) {
                $list = Yii::$app->services->goodsType->getTypeList();
                return $list[$row['type_id']]??'';
            }],
            ['站点地区', 'platform_group', 'function', function($row) {
                return \common\enums\OrderFromEnum::getValue($row['platform_group'], 'groups');
            }],
            ['销量', 'count', 'function', function($row) {
                return $row['count']?:0;
            }],
            ['加购物车量', 'cart_count', 'function', function($row) {
                return $row['cart_count']?:0;
            }]
        ];

        return ExcelHelper::exportData($list, $header, '产品销量统计导出_' . date('YmdHis',time()));
    }
}