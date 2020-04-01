<?php

namespace Omnipay\Paypal\Response;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Class AuthorizeResponse
 * @package Omnipay\Paydollar\Message
 */
class AuthorizeResponse extends AbstractResponse
{

    //判断是否已经付款
    public function isPaid()
    {
        return isset($this->data['result']) && $this->data['result'] == 'completed';
    }

    /**
     * Response code
     *
     * @return null|string A response code from the payment gateway //标准化订单状态： completed, partially_refunded, pending, refunded, denied, failed, nopayer
     */
    public function getCode()
    {
        return isset($this->data['result']) ? $this->data['result'] : null;
    }

    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return false;
    }
}
