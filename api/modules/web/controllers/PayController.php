<?php

namespace api\modules\web\controllers;

use common\enums\PayStatusEnum;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\models\common\PayLog;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Paydollar\Message\AuthorizeResponse;
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
        $orderId = $query['order_sn']??($query['orderId']??'');
        Yii::$app->services->actionLog->create('用户支付校验','订单号:'.$orderId);
        //获取支付记录模型
        /**
         * @var $model PayLog
         */
        $model = $this->getPayModelByReturnUrlQuery($query);

        if(empty($model)) {
            $result['verification_status'] = 'failed';
            return $result;
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {            
            //判断订单支付状态
            if ($model->pay_status == PayStatusEnum::PAID) {
                $transaction->rollBack();
                Yii::$app->services->actionLog->create('用户支付校验','支付结果: completed');
                
                $result['verification_status'] = 'completed';                
                return $result;
            }            
            
            //记录验证日志  
            Yii::$app->services->actionLog->create('用户支付校验','支付单号:'.($model->out_trade_no));            
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

            /**
             * @var $response AbstractResponse
             */
            $response = Yii::$app->services->pay->getPayByType($model->pay_type)->verify(['model'=>$model]);

            //支付成功
            if($response->isPaid()) {

                $result['verification_status'] = 'completed';

                $transaction->commit();
            }
            else {
                if($response->getCode() == 'pending') {
                    $result['verification_status'] = 'pending';
                }
                elseif(in_array($response->getCode(), ['failed', 'denied', 'nopayer'])) {
                    //支付失败，失败被拒绝，无支付返回支付失败
                    $result['verification_status'] = 'failed';
                }
                else {
                    $result['verification_status'] = $response->getCode();
                }
                $transaction->rollBack();
            }
            Yii::$app->services->actionLog->create('用户支付校验','支付结果:'.($response->getCode()));
        } catch (\Exception $e) {
            $transaction->rollBack();

            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            Yii::$app->services->actionLog->create('用户支付校验','Exception:'.$e->getMessage());
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
        return Yii::getAlias('@runtime') . "/pay-logs/" . date('Y-m-d') . '/' . $type . '.log';
    }
}