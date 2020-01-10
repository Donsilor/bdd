<?php

namespace common\components\payment;

use common\models\common\PayLog;
use Yii;
use Omnipay\Omnipay;
use Omnipay\Paypal\Responses\AopTradeAppPayResponse;
use Omnipay\Paypal\Responses\AopTradePreCreateResponse;
use Omnipay\Paypal\Responses\AopTradeWapPayResponse;

/**
 * Class PaypalPay
 * @package common\components\payment
 */
class PaypalPay
{
    protected $config;

    const PC = 'Paypal_Page';
    const APP = 'Paypal_App';
    const F2F = 'Paypal_F2F';
    const WAP = 'Paypal_Wap';

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 实例化类
     *
     * @param string $type
     * @return \Omnipay\Paypal\AbstractPaypalGateway
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    private function create($type = self::PC)
    {
        /* @var $gateway \Omnipay\Paypal\PageGateway */
        $gateway = Omnipay::create($type);

        //配置
        $gateway->initialize($this->config);

        return $gateway;
    }

    /**
     * 电脑网站支付
     *
     * @param $config
     *
     * 参数说明
     * $config = [
     *     'subject'      => 'test',
     *     'out_trade_no' => date('YmdHis') . mt_rand(1000, 9999),
     *     'total_amount' => '0.01',
     * ]
     *
     * @return string
     */
    public function pc($order, $debug = false)
    {
        //支付记录填充支付单号
        $model = PayLog::find()->where(['out_trade_no' => $order['out_trade_no']])->one();

        if(!$model) {
            exit(1);
        }

        $gateway = $this->create(self::PC);

        $request = $gateway->purchase($order);

        //返回URL
        $payment = $request->send();

        $model->transaction_id = $payment->getId();
        $model->save();

        /**
         * 直接跳转
         * return $response->redirect();
         */
        return $debug == true ? '' : $payment->getApprovalLink();
    }

    /**
     * 退款
     *
     * $info = [
     *     'out_trade_no' => 'The existing Order ID',
     *     'trade_no' => 'The Transaction ID received in the previous request',
     *     'refund_amount' => 18.4,
     *     'out_request_no' => date('YmdHis') . mt_rand(1000, 9999)
     *  ]
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function refund(array $info)
    {
        $gateway = $this->create();
        /**
         * 退款
         */
        $request = $gateway->refund();

        $response = $request->setBizContent($info)->send();

        return $response->getData();
    }

    /**
     * 异步/同步通知
     */
    public function notify($info)
    {
        $gateway = $this->create();

        /**
         * 确认订单
         */
        $request = $gateway->completePurchase($info);

        $response = $request->send();
var_dump($response);exit;
        return $response;
    }

    public function d()
    {

    }
}
