<?php

namespace backend\modules\market\controllers;

use backend\controllers\BaseController;
use common\helpers\ExcelHelper;
use common\models\base\SearchModel;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\order\Order;
use yii\web\Controller;

/**
 * Default controller for the `market` module
 */
class CardDetailsController extends BaseController
{
    /**
     * @var MarketCard
     */
    public $modelClass = MarketCardDetails::class;

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
                'card' => ['sn', 'batch'],
                'order' => ['order_sn'],
            ]
        ]);
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams, ['created_at']);

        $dataProvider->query->andWhere(['<>', 'type', 1]);

        #过滤不显示的批次
        $dataProvider->query->andWhere(['not in', 'market_card.batch', \Yii::$app->services->card->getTestBatch()]);

        //创建时间过滤
        if (!empty(\Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', \Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'market_card_details.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        //导出
        if ($this->export) {
            $query = \Yii::$app->request->queryParams;
            unset($query['action']);
            if (empty(array_filter($query))) {
                $returnUrl = \Yii::$app->request->referrer;
                return $this->message('导出条件不能为空', $this->redirect($returnUrl), 'warning');
            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;

            return $this->getExport($list);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public $export = null;

    public function actionExport()
    {
        $this->export = true;
        return $this->actionIndex();
    }

    private function getExport($list)
    {
        //序号	使用时间	批次	卡号	订单号	余额变动	购物卡总金额 （CNY）	剩余金额 （CNY）	费用类型	费用状态
        $header = [
            ['序号', 'id', 'text'],
            ['使用时间', 'created_at', 'date', 'Y-m-d H:i:s'],
            ['批次', 'card.batch', 'text'],
            ['卡号', 'card.sn', 'text'],
            ['订单号', 'order.order_sn', 'text'],
            ['余额变动', 'use_amount_cny', 'function', function($model) {
                return $model->currency . ' ' . $model->use_amount . ' ( CNY ' . $model->use_amount_cny . ')';
            }],
            ['购物卡总金额（CNY）', 'card.amount', 'text'],
            ['剩余金额（CNY）', 'balance', 'text'],
            ['费用类型', 'type', 'function', function($model) {
                return \common\enums\CardTypeEnum::getValue($model->type);
            }],
            ['费用状态', 'status', 'function', function($model) {
                return \common\enums\CardDetailStatusEnum::getValue($model->status);
            }],
        ];

        return ExcelHelper::exportData($list, $header, '购物卡使用记录数据导出_' . date('YmdHis', time()));
    }
}
