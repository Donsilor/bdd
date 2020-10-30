<?php


namespace backend\modules\order\controllers;

use backend\modules\order\forms\OrderCommentForm;
use backend\modules\order\forms\UploadCommentForm;
use common\components\Curd;
use common\enums\OrderFromEnum;
use common\helpers\ExcelHelper;
use common\models\goods\Style;
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

                    $platforms = OrderFromEnum::getMap();
                    $platforms2 = [];
                    foreach ($platforms as $platform_id => $platform) {
                        $platforms2[$platform] = $platform_id;
                    }

                    foreach ($data as $key => $datum) {

                        $styleInfo = Style::findOne(['style_sn'=>$datum[1]]);
                        if(!$styleInfo) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, '款式信息未找到'));
                        }

                        if(!isset($platforms2[$datum[3]])) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, $datum[3].'站点不存在'));
                        }

                        $comment = new OrderCommentForm();
                        $comment->setAttributes([
                            'username' => $datum[0]??'',
                            'type_id' => $styleInfo['type_id'],
                            'style_id' => $styleInfo['id'],
                            'created_at' => $datum[2]??'',
                            'updated_at' => $datum[2]??'',
                            'platform' => $platforms2[$datum[3]]??'',
                            'grade' => $datum[4]??'',
                            'content' => $datum[5]??'',
                            'images' => $datum[6]??'',
                            'remark' => $datum[7]??'',
                            'is_import' => 1,
                            'status' => 1,
                        ]);

                        if(!$comment->save()) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, $this->getError($comment)));
                        }
                    }

                    $trans->commit();
                } catch (\Exception $exception) {
                    $trans->rollBack();
                    print_r($datum);
                    print_r($exception->getMessage());
                    exit;
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