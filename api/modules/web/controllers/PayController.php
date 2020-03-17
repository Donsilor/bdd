<?php

namespace api\modules\web\controllers;

use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\models\common\PayLog;
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
        }catch (Exception $e) {
            $trans->rollBack();
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

        try {
            $urlInfo = parse_url($returnUrl);
            $query = parse_query($urlInfo['query']);

            //获取支付记录模型
            $model = $this->getPayModelByReturnUrlQuery($query);

            if(empty($model)) {
                throw new \Exception('未找到订单数据');
            }

            //判断订单支付状态
            if ($model->pay_status == StatusEnum::ENABLED) {
                $result['verification_status'] = 'true';

                return $result;
            };

            //验证是否支付
            $notify = Yii::$app->services->pay->getPayByType($model->pay_type)->verify(['model'=>$model]);

            if($notify==='completed') {
                //操作成功，则返回 true .
                if ($this->pay($model)) {
                    $result['verification_status'] = 'completed';
                }
                else {
                    throw new \Exception('数据库操作异常');
                }
            }
            elseif($notify==='pending') {
                $result['verification_status'] = 'pending';
            }
            elseif($notify==='denied') {
                $result['verification_status'] = 'denied';
            }
            else {
                $result['verification_status'] = 'null';
            }
        } catch (\Exception $e) {
            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());

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
        return Yii::getAlias('@runtime') . "/pay-logs/" . date('Y_m_d') . '/' . $type . '.txt';
    }
}