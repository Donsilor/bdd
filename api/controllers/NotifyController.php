<?php

namespace api\controllers;

use common\models\common\PayLog;
use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\WechatHelper;
use common\helpers\AmountHelper;

/**
 * 支付回调
 *
 * Class NotifyController
 * @package frontend\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class NotifyController extends Controller
{
    protected $payment;

    /**
     * 关闭csrf
     *
     * @var bool
     */
    public $enableCsrfValidation = false;

    /**
     * EasyWechat支付回调 - 微信
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function actionEasyWechat()
    {
        $this->payment = 'wechat';

        $response = Yii::$app->wechat->payment->handlePaidNotify(function ($message, $fail) {
            // 记录写入文件日志
            $logPath = $this->getLogPath('wechat');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            /////////////  建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////

            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                if ($this->pay($message)) {
                    return true;
                }
            }

            return $fail('处理失败，请稍后再通知我');
        });

        return $response;
    }

    /**
     * EasyWechat支付回调 - 小程序
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function actionMiniProgram()
    {
        $this->payment = 'wechat';

        // 微信支付参数配置
        Yii::$app->params['wechatPaymentConfig'] = ArrayHelper::merge(Yii::$app->params['wechatPaymentConfig'],
            ['app_id' => Yii::$app->debris->config('miniprogram_appid')]
        );

        $response = Yii::$app->wechat->payment->handlePaidNotify(function ($message, $fail) {
            $logPath = $this->getLogPath('miniprogram');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            if ($message['return_code'] === 'SUCCESS') {
                if ($this->pay($message)) {
                    return true;
                }
            }

            return $fail('处理失败，请稍后再通知我');
        });

        return $response;
    }

    /**
     * 公用支付回调 - 支付宝
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAlipay()
    {
        $this->payment = 'ali';

        $response = Yii::$app->pay->alipay([
            'ali_public_key' => Yii::$app->debris->config('alipay_notification_cert_path'),
        ])->notify();

        try {
            if ($response->isPaid()) {
                $message = Yii::$app->request->post();
                $message['pay_fee'] = $message['total_amount'];
                $message['transaction_id'] = $message['trade_no'];
                $message['mch_id'] = $message['auth_app_id'];

                // 日志记录
                $logPath = $this->getLogPath('alipay');
                FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

                if ($this->pay($message)) {
                    die('success');
                }
            }

            die('fail');
        } catch (\Exception $e) {
            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            die('fail'); // 通知响应
        }
    }

    public function actionGlobalalipay()
    {
        $this->payment = 'ali';

        try {
            $response = Yii::$app->pay->globalAlipay()->notify();

            if ($response->isPaid()) {
                $message = Yii::$app->request->post();
                $message['pay_fee'] = $message['total_amount'];
                $message['transaction_id'] = $message['trade_no'];
                $message['mch_id'] = $message['auth_app_id'];

                // 日志记录
                $logPath = $this->getLogPath('Globalalipay');
                FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

                if ($this->pay($message)) {
                    die('success');
                }
            }

            die('fail');
        } catch (\Exception $e) {
            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            die('fail'); // 通知响应
        }
    }

    public function actionPaydollar()
    {
        $this->payment = 'Paydollar';

        try {
            $response = Yii::$app->pay->globalAlipay()->notify();

            if ($response->isPaid()) {
                $message = Yii::$app->request->post();
                $message['pay_fee'] = $message['total_amount'];
                $message['transaction_id'] = $message['trade_no'];
                $message['mch_id'] = $message['auth_app_id'];

                // 日志记录
                $logPath = $this->getLogPath('Paydollar');
                FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

                if ($this->pay($message)) {
                    die('ok');
                }
            }

            die('fail');
        } catch (\Exception $e) {
            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            die('fail'); // 通知响应
        }
    }

    public function actionPaypal()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $result = [
//            'verification_status' => 'SUCCESS'//成功
            'verification_status' => 'FAILURE'//失败
        ];

        $data = Yii::$app->request->post();

        if(empty($data)) {
            return $result;
        }

        //买家付款事件
        if(!empty($data['event_type']) && $data['event_type']=='PAYMENTS.PAYMENT.CREATED') {
            $paymentId = $data['resource']['id'];

            $model = PayLog::find()->where(['transaction_id'=>$paymentId])->one();

            if(!$model) {
                return exit(Json::encode($result));
            }

            try {
                
                $notify = Yii::$app->pay->Paypal()->notify(['model'=>$model]);

                if ($notify) {

                    $message = [];//= Yii::$app->request->post();
                    $message['out_trade_no'] = $model->out_trade_no;
                    $message['pay_fee'] = $model->total_fee;
                    // 日志记录
                    $logPath = $this->getLogPath('paypal_hooks');
                    FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

                    if ($this->pay($message)) {
                        $result['verification_status'] = 'SUCCESS';
                    }
                }
            } catch (\Exception $e) {
                // 记录报错日志
                $logPath = $this->getLogPath('error');
                FileHelper::writeLog($logPath, $e->getMessage());
            }
        }

        return exit(Json::encode($result));
    }

//    public function actionPaypal()
//    {
//        if(empty($_GET['success']) || $_GET['success'] !== 'true') {
//            die('fail');
//        }
//
//        $paymentId = Yii::$app->request->get('paymentId');
//
//        $model = PayLog::find()->where(['transaction_id'=>$paymentId])->one();
//
//        if(!$model) {
//            exit('fail');
//        }
//
//        try {
//            $result = Yii::$app->pay->Paypal()->notify(['model'=>$model]);
//
//            if ($result) {
//
//                $message = [];//= Yii::$app->request->post();
//                $message['out_trade_no'] = $model->out_trade_no;
//
//                // 日志记录
//                $logPath = $this->getLogPath('paypal');
//                FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));
//
//                if ($this->pay($message)) {
//                    die('success');
//                }
//            }
//
//        } catch (\Exception $e) {
//            // 记录报错日志
//            $logPath = $this->getLogPath('error');
//            FileHelper::writeLog($logPath, $e->getMessage());
//            die('fail'); // 通知响应
//        }
//    }

    /**
     * 公用支付回调 - 微信
     *
     * @return bool|string
     */
    public function actionWechat()
    {
        $this->payment = 'wechat';

        $response = Yii::$app->pay->wechat->notify();
        if ($response->isPaid()) {
            $message = $response->getRequestData();
            $logPath = $this->getLogPath('wechat');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            //pay success 注意微信会发二次消息过来 需要判断是通知还是回调
            if ($this->pay($message)) {
                return WechatHelper::success();
            }

            return WechatHelper::fail();
        } else {
            return WechatHelper::fail();
        }
    }

    /**
     * 公用支付回调 - 银联
     */
    public function actionUnion()
    {
        $this->payment = 'union';

        $response = Yii::$app->pay->union->notify();
        if ($response->isPaid()) {
            //pay success
        } else {
            //pay fail
        }
    }

    /**
     * @param $message
     * @return bool
     */
    protected function pay($message)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!($payLog = Yii::$app->services->pay->findByOutTradeNo($message['out_trade_no']))) {
                throw new UnprocessableEntityHttpException('找不到支付信息');
            };

            // 支付完成
            if ($payLog->pay_status == StatusEnum::ENABLED) {
                return true;
            };

            $payLog->attributes = $message;
            $payLog->pay_status = StatusEnum::ENABLED;
            $payLog->pay_time = time();
            if (!$payLog->save()) {
                throw new UnprocessableEntityHttpException('日志修改失败');
            }

            // 业务回调
            Yii::$app->services->pay->notify($payLog, $this->payment);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();

            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            return false;
        }
    }

    /**
     * @param $type
     * @return string
     */
    protected function getLogPath($type)
    {
        return Yii::getAlias('@runtime') . "/pay-logs/" . date('Y_m_d') . '/' . $type . '.txt';
    }
}