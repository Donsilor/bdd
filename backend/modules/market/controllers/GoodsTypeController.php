<?php

namespace backend\modules\market\controllers;

//use addons\TinyShop\merchant\forms\CouponTypeForm;
use backend\controllers\BaseController;
use common\components\Curd;
use common\enums\AreaEnum;
use common\enums\LanguageEnum;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\market\MarketCoupon;
use common\models\market\MarketCouponGoodsType;
use services\goods\TypeService;
use services\market\CouponService;
use yii\base\Exception;

/**
 * Default controller for the `market` module
 */
class GoodsTypeController extends BaseController
{

    /**
     * @var MarketCouponGoodsType
     */
    public $modelClass = MarketCouponGoodsType::class;

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
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
        ]);

        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
//        $dataProvider->query
//            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}
