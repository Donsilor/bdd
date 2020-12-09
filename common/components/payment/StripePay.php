<?php


namespace common\components\payment;


use common\models\common\PayLog;
use DigiTickets\Stripe\CheckoutGateway;
use Omnipay\Omnipay;
use Yii;
use yii\helpers\ArrayHelper;


class StripePay
{

    const PC = "\\DigiTickets\Stripe\CheckoutGateway";

    protected $config;

    /**
     * StripePay constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param $type
     * @return \Omnipay\Common\GatewayInterface
     */
    private function create($type)
    {
        $gateway = Omnipay::create($type);
        $gateway->setApiKey($this->config['apiKey']);

        return $gateway;
    }

    public function pc($order, $debug = false)
    {
        //支付记录填充支付单号
        $model = PayLog::find()->where(['out_trade_no' => $order['transactionId']])->one();

        if(!$model) {
            exit(1);
        }

        $gateway = $this->create(self::PC);

        $request = $gateway->purchase($order);

        //返回URL
        $payment = $request->send();

        $sessionId = $payment->getSessionID();

        $model->transaction_id = $sessionId;
        $model->save();

        return $sessionId;
    }

    public function wap($order, $debug = false)
    {
        return $this->pc($order, $debug);
    }

    /**
     * 异步/同步通知
     */
    public function notify($query)
    {
        $model = $query['model'];

        $gateway = $this->create(self::PC);

        $order = [
            'transactionReference' => sprintf('{"sessionId":"%s"}', $model->transaction_id)
        ];

        $request = $gateway->completePurchase($order);

        $response = $request->send();

        return $response;
    }

    /**
     * 通过回跳URL验证支付是否成功
     * @param $query
     * @return \Omnipay\Common\Message\ResponseInterface
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function verify($query)
    {
        $model = $query['model'];

        $gateway = $this->create(self::PC);

        $order = [
            'transactionReference' => sprintf('{"sessionId":"%s"}', $model->transaction_id)
        ];

        $request = $gateway->completePurchase($order);

        $response = $request->send();

        return $response;
    }
}