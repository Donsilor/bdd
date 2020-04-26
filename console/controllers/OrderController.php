<?php

namespace console\controllers;

use yii\console\Controller;
use common\enums\OrderStatusEnum;
use common\models\order\Order;
use yii\helpers\Console;
use common\enums\PayStatusEnum;
use common\models\order\OrderAddress;
use common\enums\PayEnum;


/**
 * 订单任务处理
 * Class CommendController
 * @package console\controllers
 */
class OrderController extends Controller
{
    /**
     * 订单超时自动取消
     * @param number $minute
     */
    public function actionOrderTimeoutCancel()
    {
        echo 'Start order timeout cancel ------'.PHP_EOL;
        $total = 0;
        try {
            for($page = 1 ; $page <= 100; $page ++) {
                $order_list = Order::find()->select(['id','order_sn'])
                    ->where(['order_status'=>OrderStatusEnum::ORDER_UNPAID])
                    ->andWhere(['<','created_at',time() - 24*3600])
                    ->limit(100)->all();
                
                if(empty($order_list)) {
                    break;
                }
                foreach ($order_list as $order) {
                    $total++;
                    \Yii::$app->services->order->changeOrderStatusCancel($order->id, '超期未支付系统自动关闭订单', 'system','system');
                    echo 'Order No:'.$order->order_sn.PHP_EOL;
                }
            }
        }catch (\Exception $e) {
            \Yii::$app->services->actionLog->sendNoticeSms('批量超时订单取消',"order/order-timeout-cancel",[],3600);
            throw $e;
        }        
        echo 'Order cancel total:'.$total.PHP_EOL;
        echo 'End------'.PHP_EOL;
    }
    /**
     * 同步订单手机号
     * @param string $batch
     */
    public function actionSyncPaypalPhone($batch = 'default')
    {
        $date = date('Y-m-d H:i:s');
        Console::output("Sync Start[$batch][{$date}]-------------------");        
        try {            
            for($page = 1 ; $page <= 100; $page ++) {
                $order_list = Order::find()
                    ->select(['order.id','order.order_sn'])
                    ->innerJoin(OrderAddress::tableName().' address','address.order_id=order.id')
                    ->where(['order.is_tourist'=>1,'payment_type'=>PayEnum::PAY_TYPE_PAYPAL])
                    ->andWhere(['address.mobile' => ['',null]])
                    ->limit(100)
                    ->all();
                if(empty($order_list)) {
                    break;
                }
                foreach ($order_list as $order){
                    $key = "sync-paypal-phone:{$batch}:{$order->id}";
                    \Yii::$app->cache->getOrSet($key, function () use($order) {                        
                        try {
                            \Yii::$app->services->order->syncPayPalPhone($order->id);
                            Console::output('success:'.$order->order_sn);
                        } catch (\Exception $exception) {
                            Console::output('fail:'.$order->order_sn);
                            Console::output($exception->getMessage());
                        }                
                  },60);
                }
            }
        }catch (\Exception $e) {
            \Yii::$app->services->actionLog->sendNoticeSms('批量同步Paypal手机',"order/sync-paypal-phone",[],3600);
            throw $e;
        }
        Console::output('Sync End----------------------------------------------------');
    }
}