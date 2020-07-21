<?php
return [
    'name' => 'RageFrame',
    'version' => '2.4.21',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'zh-CN',
    'sourceLanguage' => 'zh-cn',
    'timeZone' => 'Asia/Shanghai',
    'bootstrap' => [
        'queue', // 队列系统
        'common\components\Init', // 加载默认的配置
    ],
    'components' => [
        'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=192.168.1.235;dbname=rageframe2',
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
                'tablePrefix' => '',
                'attributes' => [
                        // PDO::ATTR_STRINGIFY_FETCHES => false, // 提取的时候将数值转换为字符串
                        // PDO::ATTR_EMULATE_PREPARES => false, // 启用或禁用预处理语句的模拟
                ],
                // 'enableSchemaCache' => true, // 是否开启缓存, 请了解其中机制在开启，不了解谨慎
                // 'schemaCacheDuration' => 3600, // 缓存时间
                // 'schemaCache' => 'cache', // 缓存名称
        ],
        'cache' => [
             'class' => 'yii\redis\Cache',
        ],        
        /** ------ 格式化时间 ------ **/
        'formatter' => [
            'dateFormat' => 'yyyy-MM-dd',
            'datetimeFormat' => 'yyyy-MM-dd HH:mm:ss',
            'decimalSeparator' => ',',
            'thousandSeparator' => ' ',
            'currencyCode' => 'CNY',
        ],
        /** ------ 服务层 ------ **/
        'services' => [
            'class' => 'services\Application',
        ],
        /** ------ redis配置 ------ **/
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
        ],
        /** ------ 网站碎片管理 ------ **/
        'debris' => [
            'class' => 'common\components\Debris',
        ],
        'attr' => [
            'class' => 'common\components\Attr',
        ],
        /** ------ 访问设备信息 ------ **/
        'mobileDetect' => [
            'class' => 'Detection\MobileDetect',
        ],
        /** ------ 队列设置 ------ **/
        'queue' => [
            'class' => yii\queue\redis\Queue::class,
            'as log' => yii\queue\LogBehavior::class,
            'redis' => 'redis', // 连接组件或它的配置
            'channel' => 'queue', // Queue channel key
            'ttr' => 1200, // Max time for job execution
            'attempts' => 3,  // Max number of attempts
        ],
        /** ------ 公用支付 ------ **/
        'pay' => [
            'class' => 'common\components\Pay',
        ],
        /** ------ 上传组件 ------ **/
        'uploadDrive' => [
            'class' => 'common\components\UploadDrive',
        ],
        /** ------ 快递查询 ------ **/
        'logistics' => [
            'class' => 'common\components\Logistics',
        ],
        /** ------ 二维码 ------ **/
        'qr' => [
            'class' => '\Da\QrCode\Component\QrCodeComponent',
            // ... 您可以在这里配置组件的更多属性
        ],
        'ipLocation' => [
             'class'=> 'common\components\IpLocation'   
        ],    
        /** ------ 微信SDK ------ **/
        'wechat' => [
            'class' => 'jianyan\easywechat\Wechat',
            'userOptions' => [],  // 用户身份类参数
            'sessionParam' => 'wechatUser', // 微信用户信息将存储在会话在这个密钥
            'returnUrlParam' => '_wechatReturnUrl', // returnUrl 存储在会话中
            'rebinds' => [
                'cache' => 'common\components\WechatCache',
            ]
        ],            
        /** ------ i18n 国际化 ------ **/
        'i18n' => [
                'translations' => [
                        '*' => [
                                'class' => 'yii\i18n\PhpMessageSource',
                                'basePath' => '@app/languages',
                                'fileMap' => [

                                ],
                        ],
                ],
        ],
    ],
    'modules' => [
        /** ------ 插件模块 ------ **/
        'addons' => [
            'class' => 'common\components\AddonsModule',
        ],
    ],
];
