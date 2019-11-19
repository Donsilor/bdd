<?php

namespace addons\RfDevTool\oauth2\controllers;

use Yii;
use common\controllers\AddonsController;

/**
 * 默认控制器
 *
 * Class DefaultController
 * @package addons\RfDevTool\oauth2\controllers
 */
class BaseController extends AddonsController
{
    /**
    * @var string
    */
    public $layout = "@addons/RfDevTool/oauth2/views/layouts/main";
}