<?php


namespace backend\modules\order\controllers;


use backend\controllers\BaseController;

class OrderCommentController extends BaseController
{
    public function actionIndex()
    {
        return $this->render($this->action->id, [
//            'dataProvider' => $dataProvider,
//            'searchModel' => $searchModel,
        ]);
    }
}