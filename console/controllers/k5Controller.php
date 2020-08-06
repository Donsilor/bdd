<?php

namespace console\controllers;

use expresses\k5\ApiService;
use yii\console\Controller;

/**
 *
 * Class k5Controller
 * @package console\controllers
 */
class k5Controller extends Controller
{

    public function actionTest()
    {
        $api = new ApiService();
        $result = $api->searchOrderTracknumber([
            'OrderType' => 1,
            'CorpBillidDatas' => [
                ['CorpBillid'=>'7581001341']
            ]
        ]);
        var_dump($result);
    }

}