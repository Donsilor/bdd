<?php


namespace Omnipay\Paypal;


use Omnipay\Common\AbstractGateway;


abstract class AbstractPaypalGateway extends AbstractGateway
{

    /**
     * @param $value
     * @return AbstractPaypalGateway
     */
    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalGateway
     */
    public function setIsVerify($value)
    {
        return $this->setParameter('isVerify', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalGateway
     */
    public function setClientSecret($value)
    {
        return $this->setParameter('clientSecret', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalGateway
     */
    public function setSandbox($value)
    {
        return $this->setParameter('sandbox', $value);
    }

}