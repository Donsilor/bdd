<?php

namespace backend\modules\market\controllers;

//use addons\TinyShop\merchant\forms\CouponTypeForm;
use backend\controllers\BaseController;
use common\components\Curd;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\market\MarketSpecials;

/**
 * Default controller for the `market` module
 */
class SpecialsController extends BaseController
{
    use Curd;

    /**
     * @var MarketSpecials
     */
    public $modelClass = MarketSpecials::class;

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
            'partialMatchAttributes' => ['title'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['merchant_id' => $this->getMerchantId()]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}
