<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'api',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [ // 版本1
            'class' => 'api\modules\v1\Module',
        ],
        'v2' => [ // 版本2
            'class' => 'api\modules\v2\Module',
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
            ]
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'as beforeSend' => 'api\behaviors\BeforeSend',
        ],
        'user' => [
            'identityClass' => 'common\models\api\AccessToken',
            'enableAutoLogin' => true,
            'enableSession' => false,// 显示一个HTTP 403 错误而不是跳转到登录界面
            'loginUrl' => null,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/' . date('Y-m/d') . '.log',
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'message/error',
        ],
        'urlManager' => [
                'enablePrettyUrl' => true,
                'enableStrictParsing' => true,
                'showScriptName' => false,                    
                'rules' => [
                        ['class' => 'yii\rest\UrlRule', 'controller' => 'v1/*'],
                        'POST v1/<module>/<action>' => 'v1/<module>/<action>',
                        'GET v1/<module>/<action>' => 'v1/<module>/<action>',
                        'POST v1/<module>/<controller>/<action>' => 'v1/<module>/<controller>/<action>',
                        'GET v1/<module>/<controller>/<action>' => 'v1/<module>/<controller>/<action>',                        
                ],
        ],
    ],
    'params' => $params,
];
