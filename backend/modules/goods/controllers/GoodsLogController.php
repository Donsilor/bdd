<?php

namespace backend\modules\goods\controllers;

use Yii;
use common\models\base\SearchModel;
use common\components\Curd;
use backend\controllers\BaseController;
use common\models\goods\GoodsLog;
/**
 * Attribute
 *
 * Class AttributeController
 * @package backend\modules\goods\controllers
 */
class GoodsLogController extends BaseController
{
    use Curd;
    
    /**
     * @var Attribute
     */
    public $modelClass = GoodsLog::class;
    
    
    /**
     * 日志列表
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $type_id = Yii::$app->request->get('type_id');
        $goods_id = Yii::$app->request->get('id');
        $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => ['log_msg'], // 模糊查询
                'defaultOrder' => [
                     'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize,
                'relations' => [
                     
                ]
        ]);
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['log_time']);
        
        //$dataProvider->query->andWhere(['>','status',-1]);
        $dataProvider->query->andWhere(['=','type_id',$type_id]);
        $dataProvider->query->andWhere(['=','goods_id',$goods_id]);
        $dataProvider->query->orderBy(['id'=>SORT_DESC]);

        //时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['log_time'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['log_time']);
            $dataProvider->query->andFilterWhere(['between', 'log_time', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
        ]);
    }

}
