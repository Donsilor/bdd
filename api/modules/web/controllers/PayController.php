<?php

namespace api\modules\web\controllers;

use api\modules\web\forms\WireTransferForm;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\PayStatusEnum;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\models\common\PayLog;
use common\models\order\Order;
use common\models\order\OrderTourist;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Paydollar\Message\AuthorizeResponse;
use services\order\OrderLogService;
use Yii;
use api\controllers\OnAuthController;
use common\enums\PayEnum;
use common\helpers\Url;
use common\models\forms\PayForm;
use common\helpers\ResultHelper;
use yii\db\Exception;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;
use function GuzzleHttp\Psr7\parse_query;
use common\helpers\AmountHelper;
 
/**
 * 公用支付生成
 *
 * Class PayController
 * @package api\modules\v1\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class PayController extends OnAuthController
{
    protected $authOptional = ['verify'];

    /**
     * @var PayForm
     */
    public $modelClass = PayForm::class;

    public function actionCollectionAccountInfo()
    {
        $configJson = Yii::$app->debris->config('pay_collection_account_info');
        $configs = \Qiniu\json_decode($configJson, true);

        switch ($this->language) {
            case 'en-US':
                $bankNameKey = 'bank_name_en';
                break;
            case 'zh-TW':
                $bankNameKey = 'bank_name_tw';
                break;
            default:
                $bankNameKey = 'bank_name_cn';
        }

        foreach ($configs as &$config) {
            $config['bank_name'] = $config[$bankNameKey]?:'';
            unset($config['bank_name_en']);
            unset($config['bank_name_tw']);
            unset($config['bank_name_cn']);
        }

        return $configs;
    }

    public function actionWireTransfer()
    {
        $this->modelClass = WireTransferForm::class;

        try {
            $trans = \Yii::$app->db->beginTransaction();

            $result = $this->add();

            $payForm = new PayForm();
            $payForm->orderId = $result['order_id'];
            $payForm->coinType = $this->getCurrency();
            $payForm->payType = PayEnum::PAY_TYPE_WIRE_TRANSFER;
            $payForm->memberId = $this->member_id;

            //验证支付订单数据
            if (!$payForm->validate()) {
                throw new \Exception($this->getError($payForm), 500);
            }

            $pay = $payForm->getConfig();

            $result->out_trade_no = $pay['out_trade_no'];

            //验证支付订单数据
            if (!$result->save(false)) {
                throw new UnprocessableEntityHttpException($this->getError($result));
            }

            OrderLogService::wireTransfer($result);

            $trans->commit();
        } catch (\Exception $exception) {
            $trans->rollBack();

            throw $exception;
        }

        return $result;
    }

    /**
     * 生成支付参数
     *
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        /* @var $model PayForm */
        $model = new $this->modelClass();
        $model->attributes = Yii::$app->request->post();
        $model->memberId = $this->member_id;

        //支付宝，非人民币业务使用国际版
        if($model->payType == PayEnum::PAY_TYPE_ALI && $model->coinType != 'CNY'){
            $model->payType = PayEnum::PAY_TYPE_GLOBAL_ALIPAY;
        }
        if (isset(PayEnum::$payTypeAction[$model->payType])) {
            $model->notifyUrl = Url::removeMerchantIdUrl('toFront', ['notify/' . PayEnum::$payTypeAction[$model->payType]]);
        }
        if (!$model->validate()) {
            return ResultHelper::api(422, $this->getError($model));
        }
        try {            
            $trans = \Yii::$app->db->beginTransaction();
            $config = $model->getConfig();
            $trans->commit();            
            return $config;
        }catch (\Exception $e) {
            
            $trans->rollBack();
            \Yii::$app->services->actionLog->create('用户创建支付单号',$e->getMessage());
            throw  $e;
        }
    }

    /**
     * 通过回跳URL参数，查找支付记录
     * @param $query
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getPayModelByReturnUrlQuery($query)
    {
        $where = [];

        //paypal
        if(!empty($query['paymentId'])) {
            $where['transaction_id'] = $query['paymentId'];
        }

        //alipay
        if(!empty($query['out_trade_no'])) {
            $where['out_trade_no'] = $query['out_trade_no'];
        }

        //alipay
        if(!empty($query['Ref'])) {
            $where['out_trade_no'] = $query['Ref'];
        }

        if(!empty($where) && ($model = PayLog::find()->where($where)->one())) {
            return $model;
        }

        return null;
    }

    /**
     * 无登录验证
     * @return array
     */
    public function actionVerify()
    {
        
        ignore_user_abort(true);
        set_time_limit(300);
        //返回结果
        $result = [
            'verification_status' => 'false'
        ];

        //获取操作实例
        $returnUrl = Yii::$app->request->post('return_url', null);

        $urlInfo = parse_url($returnUrl);
        $query = parse_query($urlInfo['query']);
        //记录验证日志
        $orderSn = $query['order_sn']??($query['orderId']??'');
        
        //获取支付记录模型
        /**
         * @var $model PayLog
         */
        $model = $this->getPayModelByReturnUrlQuery($query);

        if(empty($model)) {
            Yii::$app->services->actionLog->create('用户支付校验','订单号：'.$orderSn."<br/>支付状态：查询支付记录失败");
            $result['verification_status'] = 'failed';
            return $result;
        }
        $logMessage = "订单号：".$model->order_sn.'<br/>支付编号：'.$model->out_trade_no;

        //验证是否支付成功
        if(
            //普通订单已支付成功
            $model->order_group==PayEnum::ORDER_GROUP && Order::find()->where(['order_sn'=>$model->order_sn, 'order_status'=>OrderStatusEnum::ORDER_PAID])->count('id') ||
            //游客订单已支付成功
            $model->order_group==PayEnum::ORDER_TOURIST && OrderTourist::find()->where(['order_sn'=>$model->order_sn, 'status'=>OrderTouristStatusEnum::ORDER_PAID])->count('id')
        ) {
            $logMessage .= "<br/>订单支付状态：已支付";
            Yii::$app->services->actionLog->create('用户支付校验',$logMessage);

            $result['verification_status'] = 'completed';
            return $result;
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            
            //判断订单状态
            if ($model->pay_status == PayStatusEnum::PAID) {
                $transaction->rollBack();
                
                $logMessage .= "<br/>支付状态：已支付";
                Yii::$app->services->actionLog->create('用户支付校验',$logMessage); 
                
                $result['verification_status'] = 'completed';                
                return $result;
            }          
          
            $update = [
                'pay_fee' => $model->total_fee,
                'pay_status' => PayStatusEnum::PAID,
                'pay_time' => time(),
            ];
            $updated = PayLog::updateAll($update, ['pay_status'=>PayStatusEnum::UNPAID, 'id'=>$model->id]);

            if(!$updated) {
                throw new \Exception('该笔订单已支付~！');
            }

            $model->refresh();

            //更新订单状态
            Yii::$app->services->pay->notify($model, null);
           
            $response = Yii::$app->services->pay->getPayByType($model->pay_type)->verify(['model'=>$model]);
            $payCode = $response->getCode() ?? 'error';
            //支付成功
            if($response->isPaid()) {

                $result['verification_status'] = 'completed';

                $transaction->commit();

                \Yii::$app->services->order->sendOrderNotification($model->order_sn);
            }
            else {
                if($payCode == 'pending') {
                    $result['verification_status'] = 'pending';
                }
                elseif(in_array($payCode, ['failed', 'denied', 'nopayer'])) {
                    //支付失败，失败被拒绝，无支付返回支付失败
                    $result['verification_status'] = 'failed';
                }
                else {
                    $result['verification_status'] = $payCode;
                }
                $transaction->rollBack();
            }
            
            $logMessage .= "<br/>支付状态： ".($payCode ? $payCode : 'wating');
            Yii::$app->services->actionLog->create('用户支付校验',$logMessage,$response);
        } catch (\Exception $e) {
            $transaction->rollBack();

            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            Yii::$app->services->actionLog->create('用户支付校验','Exception：'.$e->getMessage());
            //服务器错误的时候，返回订单处理中
            $result['verification_status'] = 'pending';
        }
        return $result;
    }

    /**
     * 此方法复制于 NotifyController.php
     * @param $payLog
     * @return bool
     */
    protected function pay($payLog)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {

            $payLog->pay_status = StatusEnum::ENABLED;
            $payLog->pay_time = time();
            if (!$payLog->save()) {
                throw new UnprocessableEntityHttpException('支付记录保存失败');
            }

            // 业务回调
            Yii::$app->services->pay->notify($payLog, null);

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
        return Yii::getAlias('@runtime') . "/pay-logs/" . date('Y-m/d') . '/' . $type . '.log';
    }
}