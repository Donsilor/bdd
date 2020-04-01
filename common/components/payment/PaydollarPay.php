<?php


namespace common\components\payment;


use common\models\common\PayLog;
use Omnipay\Omnipay;

class PaydollarPay
{
    protected $config;

    const PC = 'Paydollar_Client';
    const APP = 'Paydollar_Client';
    const F2F = 'Paydollar_Client';
    const WAP = 'Paydollar_Client';

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 实例化类
     *
     * @param string $type
     * @return \Omnipay\Paydollar\ClientGateway
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    private function create($type = self::PC)
    {
        /* @var $gateway \Omnipay\Paydollar\ClientGateway */
        $gateway = Omnipay::create($type);

        //配置
        $gateway->initialize($this->config);

        return $gateway;
    }

    /**
     * 电脑网站支付
     *
     * @param $order
     *
     * 参数说明
     * $order = [
     *     'subject'      => 'test',
     *     'out_trade_no' => date('YmdHis') . mt_rand(1000, 9999),
     *     'total_amount' => '0.01',
     * ]
     *
     * @return string
     */
    public function pc($order, $debug = false)
    {

        $gateway = $this->create(self::WAP);

        $request = $gateway->purchase($order);

        //返回URL
        $payment = $request->send();

        /**
         * 直接跳转
         * return $response->redirect();
         */
        return $debug == true ? '' : $payment->getRedirectUrl();
    }

    public function wap($order, $debug = false)
    {

        $gateway = $this->create(self::PC);

        $request = $gateway->purchase($order);

        //返回URL
        $payment = $request->send();

        /**
         * 直接跳转
         * return $response->redirect();
         */
        return $debug == true ? '' : $payment->getRedirectUrl();
    }

    /**
     * 异步/同步通知
     * @param array $info
     * @return \Omnipay\Common\Message\ResponseInterface
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function notify($info=[])
    {
        $gateway = $this->create();
        $request = $gateway->completePurchase();
        $request->setRequestParams(array_merge(\Yii::$app->request->post(), \Yii::$app->request->get(), $info)); // Optional
        return $request->send();
    }

    /**
     * 验证支付是否成功
     * @param array $info
     * @return \Omnipay\Common\Message\ResponseInterface
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function verify($info=[])
    {
        $params = [
            'orderRef'       => ($info['model'])->out_trade_no,
        ];

        $gateway = $this->create();

        $request = $gateway->query($params);

        return $request->send();
    }
}