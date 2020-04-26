<?php

namespace Omnipay\Paydollar\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Paydollar\Helper;

/**
 * Class ClientPurchaseResponse
 * @package Omnipay\Paydollar\Message
 */
class ClientQueryResponse extends AbstractResponse
{
    public function isPaid()
    {
        return $this->getCode()=='completed';
    }

    /**
     * Response code
     *
     * @return null|string A response code from the payment gateway //标准化订单状态： completed, partially_refunded, pending, refunded, denied, failed, nopayer
     */
    public function getCode()
    {
        $key = key($this->data);
        if(is_int($key)) {
            foreach ($this->data as $datum) {
                if(isset($datum['prc']) && $datum['prc']=='0') {
                    $data = $datum;
                    break;
                }
            }
            if(empty($data)) {
                $data = $this->data[0]??[];
            }
        }
        else {
            $data = $this->data;
        }

        if(!isset($data['prc'])) {
            return 'nopayer';
        }

        switch($data['prc'])
        {
            case '0':
                return 'completed';
            case '1':
                return 'denied';
            case '3':
                return 'nopayer';
            default:
                return 'failed';
        }
    }

    public function isSuccessful()
    {
        return isset($this->data['resultCode']) && $this->data['resultCode'] == '0';
    }
}
