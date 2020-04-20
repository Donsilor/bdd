<?php

namespace services\common;

use common\enums\OrderTouristStatusEnum;
use common\models\order\OrderTourist;
use Yii;
use common\enums\PayEnum;
use common\components\Service;
use common\models\common\PayLog;
use common\helpers\StringHelper;
use common\models\forms\PayForm;
use common\helpers\ArrayHelper;
use common\models\order\Order;
use common\enums\OrderStatusEnum;
use common\enums\PayStatusEnum;
use common\models\order\OrderAccount;
use common\helpers\AmountHelper;
use yii\web\UnprocessableEntityHttpException;
use common\helpers\Url;

/**
 * Class PayService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class PayService extends Service
{
    /**
     * 通过 TypeId 获取支付
     * @param $typeId
     * @param array $config
     * @return mixed|null
     */
    public function getPayByType($typeId, $config=[])
    {
        $payType = PayEnum::$payTypeAction[$typeId]?:null;

        if($payType && method_exists(Yii::$app->pay, $payType)) {
            return call_user_func([Yii::$app->pay, $payType], $config);
        }
        return null;
    }

    /**
     * @param PayForm $payForm
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function wechat(PayForm $payForm, $baseOrder)
    {
        // 生成订单
        $order = [
            'body' => $baseOrder['body'], // 内容
            'out_trade_no' => $baseOrder['out_trade_no'], // 订单号
            'total_fee' => $baseOrder['total_fee'],
            'notify_url' => $payForm->notifyUrl, // 回调地址
        ];

        //  判断如果是js支付
        if ($payForm->tradeType == 'js') {
            $order['open_id'] = '';
        }

        //  判断如果是刷卡支付
        if ($payForm->tradeType == 'pos') {
            $order['auth_code'] = '';
        }

        // 交易类型
        $tradeType = $payForm->tradeType;
        return Yii::$app->pay->wechat->$tradeType($order);
    }

    /**
     * @param PayForm $payForm
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function alipay(PayForm $payForm, $baseOrder)
    {
        // 配置
        $config = [
            'notify_url' => $payForm->notifyUrl, // 支付通知回调地址
            'return_url' => $payForm->returnUrl, // 买家付款成功跳转地址
        ];

        // 生成订单
        $order = [
            'out_trade_no' => $baseOrder['out_trade_no'],
            'total_amount' => $baseOrder['total_fee'] ,
            'subject' => $baseOrder['body'],
        ];

        // 交易类型
        $tradeType = $payForm->tradeType;
        return [
            'config' => Yii::$app->pay->alipay($config)->$tradeType($order)
        ];
    }

    /**
     * @param PayForm $payForm
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function globalAlipay(PayForm $payForm, $baseOrder)
    {
        // 配置
        $config = [
            'notify_url' => $payForm->notifyUrl, // 支付通知回调地址
            'return_url' => $payForm->returnUrl, // 买家付款成功跳转地址
        ];

        // 生成订单
        $order = [
            'out_trade_no' => $baseOrder['out_trade_no'],

            //转换成支付货币
            'total_fee' => $baseOrder['total_fee'],
            'subject' => $baseOrder['body'],
            'currency' => $baseOrder['currency'],
        ];

        // 交易类型
        $tradeType = $payForm->tradeType;
        return [
            'config' => Yii::$app->pay->globalAlipay($config)->$tradeType($order)
        ];
    }
    /**
     * Paypal支付
     * @param PayForm $payForm
     * @param unknown $baseOrder
     * @return NULL[]
     */
    public function paypal(PayForm $payForm, $baseOrder)
    {
        // 配置
        $config = [
            'notify_url' => $payForm->notifyUrl, // 支付通知回调地址
            'return_url' => $payForm->returnUrl, // 买家付款成功跳转地址
        ];

        // 生成订单
        $order = [
            'out_trade_no' => $baseOrder['out_trade_no'],

            //转换成支付货币
            'total_amount' => $baseOrder['total_fee'],
            'subject' => $baseOrder['body'],
            'currency' => $baseOrder['currency'],
        ];
        // 交易类型
        $tradeType = $payForm->tradeType;
        return [
            'config' => Yii::$app->pay->paypal($config)->$tradeType($order)
        ];
    }

    /**
     * Paypal支付
     * @param PayForm $payForm
     * @param unknown $baseOrder
     * @return NULL[]
     */
    public function paydollar(PayForm $payForm, $baseOrder)
    {
        //成功，失败返回URL
        $cancelUrl = Url::buildUrl($payForm->returnUrl,['success'=>'false']);
        $returnUrl = Url::buildUrl($payForm->returnUrl,['success'=>'true']);

        // 配置
        $config = [
            'success_url' => $returnUrl,
            'fail_url' => $cancelUrl,
            'cancel_url' => $cancelUrl,
        ];

        // 生成订单
        $order = [
            'order_ref' => $baseOrder['out_trade_no'],

            //转换成支付货币
            'amount' => $baseOrder['total_fee'],
//            'subject' => $baseOrder['body'],
            'curr_code' => $baseOrder['currency'],//货币
            'lang' => Yii::$app->language,
        ];
        // 交易类型
        $tradeType = $payForm->tradeType;
        return [
            'config' => Yii::$app->pay->paydollar($config)->$tradeType($order)
        ];
    }

    /**
     * @param PayForm $payForm
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function union(PayForm $payForm, $baseOrder)
    {
        // 配置
        $config = [
            'notify_url' => $payForm->notifyUrl, // 支付通知回调地址
            'return_url' => $payForm->returnUrl, // 买家付款成功跳转地址
        ];

        // 生成订单
        $order = [
            'orderId' => $baseOrder['out_trade_no'], //Your order ID
            'txnTime' => date('YmdHis'), //Should be format 'YmdHis'
            'orderDesc' => $baseOrder['body'], //Order Title
            'txnAmt' => $baseOrder['total_fee'], //Order Total Fee
        ];

        // 交易类型
        $tradeType = $payForm->tradeType;
        return Yii::$app->pay->union($config)->$tradeType($order);
    }

    /**
     * @param PayForm $payForm
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function miniProgram(PayForm $payForm, $baseOrder)
    {
        // 设置appid
        Yii::$app->params['wechatPaymentConfig'] = ArrayHelper::merge(Yii::$app->params['wechatPaymentConfig'], [
            'app_id' => Yii::$app->debris->config('miniprogram_appid')
        ]);

        $orderData = [
            'trade_type' => 'JSAPI',
            'body' => $baseOrder['body'],
            // 'detail' => '支付详情',
            'notify_url' => $payForm->notifyUrl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'out_trade_no' => $baseOrder['out_trade_no'], // 支付
            'total_fee' => $baseOrder['total_fee'],
            'openid' => '', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];

        $payment = Yii::$app->wechat->payment;
        $result = $payment->order->unify($orderData);
        return $payment->jssdk->sdkConfig($result['prepay_id']);
    }

    /**
     * 获取订单支付日志编号
     *
     * @param int $payFee 单位分
     * @param string $orderSn 关联订单号
     * @param int $orderGroup 订单组别 如果有自己的多种订单类型请去\common\models\common\PayLog里面增加对应的常量
     * @param int $payType 支付类型 1:微信;2:支付宝;3:银联;4:微信小程序
     * @param string $tradeType 支付方式
     * @return string
     */
    public function getOutTradeNo($totalFee, string $orderSn, int $payType, $tradeType = 'JSAPI', $orderGroup = 1,$currency = null,$exchangeRate = null)
    {

        $payModel = new PayLog();
        $payModel->out_trade_no = StringHelper::randomNum(time());
        $payModel->total_fee = $totalFee;
        $payModel->order_sn = $orderSn;
        $payModel->order_group = $orderGroup;
        $payModel->pay_type = $payType;
        $payModel->trade_type = $tradeType;
        $payModel->currency = $currency;
        $payModel->exchange_rate = $exchangeRate;
        $payModel->save();

        return $payModel->out_trade_no;
    }

    /**
     * 支付通知回调
     *
     * @param PayLog $log
     * @param string $paymentType 支付类型
     * @return bool
     */
    public function notify(PayLog $log, $paymentType)
    {
        $log->ip = Yii::$app->request->userIP;
        $log->save();

        switch ($log->order_group) {
            case PayEnum::ORDER_GROUP :   
                if($log->pay_status == 1 && ($order = Order::find()->where(['order_sn'=>$log->order_sn, 'order_status'=>OrderStatusEnum::ORDER_UNPAID])->one())) {
                    $time = time();
                    $pay_amount = $log->total_fee;

                    $update = [
                        'pay_sn'=>$log->out_trade_no,
                        'api_pay_time'=>$time,
                        'payment_type' =>$log->pay_type,
                        'payment_time' =>$time,
                        'payment_status'=>PayStatusEnum::PAID,
                        'order_status'=>OrderStatusEnum::ORDER_PAID
                    ];

                    $result = Order::updateAll($update, ['id' => $order->id, 'order_status'=>OrderStatusEnum::ORDER_UNPAID]);

                    if($result) {
                        $accountUpdata = [
                             'pay_amount'=> $pay_amount,                            
                        ];
                        OrderAccount::updateAll($accountUpdata,['order_id'=>$order->id]);
                        
                        //订单发送邮件
                        \Yii::$app->services->order->sendOrderNotification($order->id);
                    }
                    else {
                        throw new \Exception('Order 更新失败'.$log->order_sn);
                    }
                }
                 /*else {
                    throw new \Exception('Order 无需更新'.$log->order_sn);
                }*/
                // TODO 处理订单
                return true;
                break;
            case PayEnum::ORDER_TOURIST :
                if($log->pay_status == 1 && ($orderTourist = OrderTourist::find()->where(['order_sn'=>$log->order_sn, 'status'=>OrderTouristStatusEnum::ORDER_UNPAID])->one())) {

                    //保存游客支付订单状态
                    $orderTourist->status = OrderTouristStatusEnum::ORDER_PAID;
                    $orderTourist->pay_amount = $log->total_fee;

                    $update = [
                        'status' => OrderTouristStatusEnum::ORDER_PAID,
                        'pay_amount' => $log->total_fee
                    ];

                    $result = OrderTourist::updateAll($update, ['id' => $orderTourist->id, 'status'=>OrderTouristStatusEnum::ORDER_UNPAID]);

                    if($result) {
                        //同步游客订单到标准订单
                        \Yii::$app->services->orderTourist->sync($orderTourist, $log);
                    }
                    else {
                        throw new \Exception('OrderTourist 更新失败'.$log->order_sn);
                    }
                }
                /*else {
                    throw new \Exception('OrderTourist 无需更新'.$log->order_sn);
                }*/
                return true;
                break;
            case PayEnum::ORDER_GROUP_RECHARGE :
                // TODO 处理充值信息
                return true;
                break;
        }
    }

    /**
     * @param $outTradeNo
     * @return array|null|\yii\db\ActiveRecord|PayLog
     */
    public function findByOutTradeNo($outTradeNo)
    {
        return PayLog::find()
            ->where(['out_trade_no' => $outTradeNo])
            ->one();
    }
}