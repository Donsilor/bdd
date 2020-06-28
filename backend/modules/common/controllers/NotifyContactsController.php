<?php


namespace backend\modules\common\controllers;


use backend\controllers\BaseController;
use backend\modules\market\forms\CardFrom;
use common\components\Curd;
use common\enums\AppEnum;
use common\models\base\SearchModel;
use common\models\order\Order;
use Yii;
use common\models\common\MenuLang;
use common\models\common\NotifyContacts;
use yii\data\ActiveDataProvider;

class NotifyContactsController extends BaseController
{
    use Curd;

    /**
     * @var \yii\db\ActiveRecord
     */
    public $modelClass = NotifyContacts::class;


    /**
     * Lists all Tree models.
     * @return mixed
     */
    public function actionIndex()
    {
        $SearchModel = Yii::$app->request->get('SearchModel');

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['email', 'mobile'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => 1000,
            'relations' => [
                'user' => ['username'],
            ]
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams?:['SearchModel'=>$SearchModel]);

        return $this->render($this->action->id, [
            'type_id' => $SearchModel['type_id']??1,
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * ajax编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');

        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        $model = $this->findModel($id);

        if(!$model->type_id) {
            $model->type_id = Yii::$app->request->get('type_id');
        }

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $model->user_id = \Yii::$app->getUser()->identity->id;
            return $model->save()
                ? $this->redirect($returnUrl)
                : $this->message($this->getError($model), $this->redirect($returnUrl), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

}