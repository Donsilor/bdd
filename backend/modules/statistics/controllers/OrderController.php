<?php


namespace backend\modules\statistics\controllers;


use backend\controllers\BaseController;

class OrderController extends BaseController
{

    public function actionIndex()
    {
        return $this->render($this->action->id, [
//            'dataProvider' => $dataProvider,
//            'searchModel' => $searchModel,
        ]);
    }
}