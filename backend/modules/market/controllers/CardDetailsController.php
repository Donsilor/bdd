<?php

namespace backend\modules\market\controllers;

use backend\controllers\BaseController;
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
                'card' => ['sn'],
                'order' => ['order_sn'],
            ]
        ]);
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams, ['created_at']);

        $dataProvider->query->andWhere(['<>', 'type', 1]);

        #过滤不显示的批次
        $batch = [
            '[2020/4/24]购物卡测试01批',
            '[2020/4/24]购物卡测试01批',
            '[2020/4/24]购物卡测试02批',
            '[2020/4/24]购物卡测试03批',
            '[2020/4/24]购物卡测试04',
            '[2020/5/14]购物卡测试05',
            '[2020/6/4]购物卡测试06-有效时长',
            '[2020/6/4]购物卡测试07-固定时间',
            '正式站-测试购物卡导入',
            '正式站-测试购物卡导入2'
        ];
        $dataProvider->query->andWhere(['not in', 'market_card.batch', $batch]);

        //创建时间过滤
        if (!empty(\Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', \Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'market_card_details.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}
