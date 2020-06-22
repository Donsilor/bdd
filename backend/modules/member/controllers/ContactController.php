<?php

namespace backend\modules\member\controllers;

use common\models\backend\Member;
use Yii;
use common\models\member\Contact;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use common\helpers\ExcelHelper;

/**
* Contact
*
* Class ContactController
* @package backend\modules\member\controllers
*/
class ContactController extends BaseController
{
    use Curd;

    /**
    * @var Contact
    */
    public $modelClass = Contact::class;


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
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams,['created_at','book_time','follower_id']);

        $created_at = $searchModel->created_at;
        if (!empty($created_at)) {
            $dataProvider->query->andFilterWhere(['>=','created_at', strtotime(explode('/', $searchModel->created_at)[0])]);//起始时间
            $dataProvider->query->andFilterWhere(['<','created_at', (strtotime(explode('/', $searchModel->created_at)[1]) + 86400)] );//结束时间
        }

        $book_time = $searchModel->book_time;
        if (!empty($book_time)) {
            $dataProvider->query->andFilterWhere(['>=','book_time', explode('/', $searchModel->book_time)[0]]);//起始时间
            $dataProvider->query->andFilterWhere(['<','book_time', date('Y-m-d',strtotime("+1 day",strtotime(explode('/', $searchModel->book_time)[1])))] );//结束时间
        }

        if($searchModel->follower_id) {
            $followerIds = Member::find()->where(['username'=>$searchModel->follower_id])->select(['id']);
            $dataProvider->query->andWhere(['in', 'follower_id', $followerIds]);
        }


        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionInfo()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);
        return $this->render('info', [
            'model' => $model,
        ]);
    }



    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExport()
    {
        $telphone = Yii::$app->request->get('telphone',null);
        $status = Yii::$app->request->get('status', null);
        $created_at = Yii::$app->request->get('created_at', null);
        $book_time = Yii::$app->request->get('book_time', null);
        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['ID', 'id'],
            ['姓名', 'all_name', 'text'],
            ['电话', 'telphone', 'text'],
            ['Ip地址', 'ip_location', 'text'],
            ['预约时间', 'book_time', 'text'],
            ['留言时间', 'created_at', 'date', 'Y-m-d'],
            ['留言内容', 'content', 'text'],
            ['跟进状态', 'status', 'selectd', [0 => '未跟进', 1 => '已跟进']],
            ['备注', 'remark', 'text'],
            ['留言类别', 'type_id', 'selectd', [1 => '订婚戒指', 2 => '结婚对戒',3 => '时尚饰品']],

        ];
        $searchModel = Contact::find();

        if($telphone){
            $searchModel->andFilterWhere(['telphone'=>$telphone]);
        }
        if($status){
            $searchModel->andFilterWhere(['status'=>$status]);
        }
        if (!empty($created_at)) {
            $created_at_array = explode('/', $created_at);
            $searchModel->andFilterWhere(['between','created_at', strtotime($created_at_array[0]), strtotime($created_at_array[1])]);
        }

        if (!empty($book_time)) {
            $book_time_array = explode('/', $book_time);
            $searchModel->andFilterWhere(['between','book_time', $book_time_array[0], $book_time_array[1]]);
        }

        $list = $searchModel
            ->orderBy('created_at desc')
            ->select(['id','concat(`first_name`,`last_name`) as all_name','telphone','ip_location','book_time','created_at','content','status','type_id','remark'])
            ->asArray()
            ->all();


        return ExcelHelper::exportData($list, $header, '预约数据导出_' . time());
    }
}
