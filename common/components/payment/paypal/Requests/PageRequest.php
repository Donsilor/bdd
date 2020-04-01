<?php


namespace Omnipay\Paypal\Requests;


use common\helpers\FileHelper;
use Omnipay\Common\Message\AbstractRequest;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Omnipay\Paypal\PaypalLog;

class PageRequest extends AbstractPaypalRequest
{

    /**
     * @param $value
     * @return PageRequest
     */
    public function setCurrency($value)
    {
        return $this->setParameter('currency', $value);
    }

    /**
     * @param $value
     * @return PageRequest
     */
    public function setTotalAmount($value)
    {
        return $this->setParameter('totalAmount', $value);
    }

    /**
     * @param $value
     * @return PageRequest
     */
    public function setOutTradeNo($value)
    {
        return $this->setParameter('outTradeNo', $value);
    }

    /**
     * 获取数据
     * @inheritDoc
     */
    public function getData()
    {
        $returnUrl = $this->getReturnUrl();

        $cancelUrl = sprintf('%s%s%s', $returnUrl, (strpos($returnUrl,'?')?'&':'?'), 'success=false');
        $returnUrl = sprintf('%s%s%s', $returnUrl, (strpos($returnUrl,'?')?'&':'?'), 'success=true');

        $this->setCancelUrl($cancelUrl);
        $this->setReturnUrl($returnUrl);
    }

    /**
     * 发送数据
     * @param $data getData的返回结果
     * @return \Omnipay\Common\Message\ResponseInterface|Payment
     * @throws \Exception
     */
    public function sendData($data)
    {
        $clientId = $this->getParameter('clientId');
        $clientSecret = $this->getParameter('clientSecret');

        $subject = $this->getParameter('subject');
        $currency = $this->getParameter('currency');
        $totalAmount = $this->getParameter('totalAmount');
        $outTradeNo = $this->getParameter('outTradeNo');

        $returnUrl = $this->getParameter('returnUrl');
        $cancelUrl = $this->getParameter('cancelUrl');
        //参数
        PaypalLog::writeLog('['.$outTradeNo.'] Request Parameters ='.var_export($this->getParameters(),true),'create-'.date('Y-m-d').'.log');
        
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // URL
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl);

        //设置金额
        $amount = new Amount();
        $amount->setCurrency($currency)
            ->setTotal($totalAmount);

        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setPurchaseOrder($outTradeNo);

        $apiContext = $this->getApiContext($clientId, $clientSecret);

        try {
            $payment = new Payment();
            $payment->setIntent("sale")
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions(array($transaction));

            $payment->create($apiContext);
            //日志
            PaypalLog::writeLog('['.$outTradeNo.'] payment->state='.$payment->state,'create-'.date('Y-m-d').'.log');
            if($payment->failure_reason) {
                PaypalLog::writeLog('['.$outTradeNo.'] payment->failure_reason='.$payment->failure_reason,'create-'.date('Y-m-d').'.log');
            }
            
        } catch (\Exception $e) {
            PaypalLog::writeLog('['.$outTradeNo.'] Exception：'.$e->getCode().'|'.$e->getMessage(),'create-'.date('Y-m-d').'.log');
            throw new \Exception('paypal创建订单异常~！');
        }

        // TODO: Implement sendData() method.
        return $payment;
    }
}