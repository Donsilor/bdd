<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\behaviors\ActionLogBehavior;
use backend\forms\LoginForm;

/**
 * Class SiteController
 * @package backend\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class SiteApiController extends SiteController
{

    /**
     * 不验证令牌，增加验证IP
     * @var bool
     */
    public $enableCsrfValidation = false;

    /**
     * 登录
     *
     * @return string|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // 记录行为日志
            Yii::$app->services->actionLog->create('login', '账号登录', false);

            return 'success';
        }

        return 'error';
    }

}
