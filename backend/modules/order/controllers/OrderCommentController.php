<?php


namespace backend\modules\order\controllers;

use backend\modules\order\forms\OrderCommentForm;
use backend\modules\order\forms\UploadCommentForm;
use common\components\Curd;
use common\helpers\ExcelHelper;
use Yii;
use backend\controllers\BaseController;
use common\models\base\SearchModel;
use common\models\order\OrderComment;
use common\models\order\OrderTourist;
use yii\web\UploadedFile;

class OrderCommentController extends BaseController
{
    use Curd;

    /**
     * @var OrderTourist
     */
    public $modelClass = OrderComment::class;


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
            'relations' => [
                'member' => ['username'],
                'style' => ['style_sn'],
            ]
        ]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at']);

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'order_comment.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        //站点地区
//        $sitesAttach = \Yii::$app->getUser()->identity->sites_attach;
//        if(is_array($sitesAttach)) {
//            $orderFroms = [];
//
//            foreach ($sitesAttach as $site) {
//                $orderFroms = array_merge($orderFroms, OrderFromEnum::platformsForGroup($site));
//            }
//
//            $dataProvider->query->andWhere(['in', 'order_tourist.order_from', $orderFroms]);
//        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }



    public function actionEditAudit()
    {
        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);
        $model->admin_id = Yii::$app->user->getIdentity()->id;

        // ajax 校验
        $this->activeFormValidate($model);

        if ($model->load(Yii::$app->request->post())) {
            if(!$model->save())
            return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            return $this->redirect(Yii::$app->request->referrer);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionImport()
    {
        $model = new UploadCommentForm();

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($file = $model->upload()) {

                $data = ExcelHelper::import($file, 2);

                try {
                    $trans = Yii::$app->db->beginTransaction();

                    foreach ($data as $key => $datum) {
                        if(empty(trim($datum[0]))) {
                            continue;
                        }

                        $created_at = $datum[9]??'';
                        $comment = new OrderCommentForm();
                        $comment->setAttributes([
                            'type_id' => $datum[1]??'',
                            'style_id' => $datum[2]??'',
                            'grade' => $datum[3]??'',
                            'content' => $datum[4]??'',
                            'images' => $datum[5]??'',
                            'username' => $datum[6]??'',
                            'platform' => $datum[7]??'',
                            'remark' => $datum[8]??'',
                            'created_at' => $created_at,
                            'updated_at' => $created_at,
                            'is_import' => 1,
                        ]);

                        if(!$comment->save()) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, $this->getError($comment)));
                        }
                    }

                    $trans->commit();
                } catch (\Exception $exception) {
                    $trans->rollBack();
                    print_r($exception->getMessage());exit;
                    return $this->message($exception->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
                }

                return $this->redirect(Yii::$app->request->referrer);
            }

            return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}