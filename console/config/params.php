<?php
return [
    'adminEmail' => 'admin@example.com',
    'smsNotice'=>[
            'open'=>true,
            'userName'=>'管理员',
            'siteName'=>'BDD测试任务',
            'mobiles'=>['15989407534'],
            'routes'=>[                    
                    'order/order-timeout-cancel',
                    'order/sync-paypal-phone',
            ]
    ],//错误日志短信提醒 
];
