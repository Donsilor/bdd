<?php


namespace backend\modules\order\controllers;

use backend\modules\order\forms\OrderCommentEditForm;
use backend\modules\order\forms\OrderCommentForm;
use backend\modules\order\forms\UploadCommentForm;
use common\components\Curd;
use common\enums\OrderFromEnum;
use common\enums\StatusEnum;
use common\helpers\ExcelHelper;
use common\models\goods\Style;
use PhpOffice\PhpSpreadsheet\Shared\Date;
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

        $dataProvider->query->andWhere(['=', 'is_destroy', 0]);

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
                        if(empty($datum[1])) {
                            continue;
                        }

                        $styleInfo = Style::findOne(['style_sn'=>$datum[1]]);
                        if(!$styleInfo) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, '款式信息未找到'));
                        }

                        if(!isset($platforms2[$datum[3]])) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, $datum[3].'站点不存在'));
                        }

                        try {
                            $created_at = Date::excelToDateTimeObject($datum[2]??null)->format('Y-m-d H:i:s');
                        } catch (\Exception $exception) {
                            throw new \Exception(sprintf('第[%d]行，%s', $key, $datum[3].'评价时间错误'));
                        }

                        $comment = new OrderCommentForm();
                        $comment->setAttributes([
                            'admin_id' => Yii::$app->user->getIdentity()->id,
                            'username' => $datum[0]??'',
                            'type_id' => $styleInfo['type_id'],
                            'style_id' => $styleInfo['id'],
                            'created_at' => $created_at,
                            'updated_at' => $created_at,
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
                    unlink($file);
                    return $this->message($exception->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
                }

                return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer), 'success');
            }

            return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    /**
     * 伪删除
     *
     * @param $id
     * @return mixed
     */
    public function actionDestroy($id)
    {
        if (!($model = $this->modelClass::findOne($id))) {
            return $this->message("找不到数据", $this->redirect(['index']), 'error');
        }

        $model->is_destroy = StatusEnum::DELETE;
        if ($model->save()) {
            return $this->message("删除成功", $this->redirect(['index']));
        }

        return $this->message("删除失败", $this->redirect(['index']), 'error');
    }

    /**
     * ajax编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit()
    {
        $this->modelClass = OrderCommentEditForm::class;

        $id = Yii::$app->request->get('id');
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->message("保存成功", $this->redirect($returnUrl), 'success')
                : $this->message($this->getError($model), $this->redirect($returnUrl), 'error');
        }

        if($model->style_id) {
            $style = Style::findOne($model->style_id);
            $model->style_sn = $style->style_sn;
        }

        if(!empty($model->images)) {
            $model->images = explode(",", $model->images);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}