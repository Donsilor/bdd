<?php

namespace backend\modules\statistics\controllers;


use backend\controllers\BaseController;
use common\enums\OrderFromEnum;
use common\models\base\SearchModel;
use common\models\goods\AttributeSpec;
use common\models\goods\Style;
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
WHERE 1 GROUP BY `og`.`style_id`,`o`.`order_from`) AS og
DOM;

        $orderCart = <<<DOM
(SELECT COUNT(`oc`.`style_id`) as count,oc.style_id,oc.platform_group FROM `order_cart` oc WHERE 1 GROUP BY `oc`.`style_id`, `oc`.`platform_group`) as oc
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
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider->query->leftJoin($order, 'statistics_style_view.style_id=og.style_id and og.order_from=statistics_style_view.platform_group');
        $dataProvider->query->leftJoin($orderCart, 'statistics_style_view.style_id=oc.style_id and oc.platform_group=statistics_style_view.platform_group');

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

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}