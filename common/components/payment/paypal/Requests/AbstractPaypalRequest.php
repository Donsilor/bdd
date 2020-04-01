<?php


namespace Omnipay\Paypal\Requests;


use Omnipay\Common\Message\AbstractRequest;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Omnipay\Paypal\PaypalLog;

abstract class AbstractPaypalRequest extends AbstractRequest
{

    /**
     * @param $value
     * @return AbstractPaypalRequest
     */
    public function setIsVerify($value)
    {
        return $this->setParameter('isVerify', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalRequest
     */
    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalRequest
     */
    public function setClientSecret($value)
    {
        return $this->setParameter('clientSecret', $value);
    }

    /**
     * @param $value
     * @return AbstractPaypalRequest
     */
    public function setSandbox($value)
    {
        return $this->setParameter('sandbox', $value);
    }

    /**
     * Helper method for getting an APIContext for all calls
     * @param string $clientId Client ID
     * @param string $clientSecret Client Secret
     * @return ApiContext
     */
    public function getApiContext($clientId, $clientSecret)
    {

        // #### SDK configuration
        // Register the sdk_config.ini file in current directory
        // as the configuration source.
        /*
        if(!defined("PP_CONFIG_PATH")) {
            define("PP_CONFIG_PATH", __DIR__);
        }
        */


        // ### Api context
        // Use an ApiContext object to authenticate
        // API calls. The clientId and clientSecret for the
        // OAuthTokenCredential class can be retrieved from
        // developer.paypal.com

        $sandbox = $this->getParameter('sandbox');

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $clientSecret
            )
        );

        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration
        
        PaypalLog::mkDirs(PaypalLog::logPath());        
        $apiContext->setConfig(
            array(
                'mode' => $sandbox ? 'sandbox' : 'live',
                'log.LogEnabled' => true,
                'log.FileName' => PaypalLog::logPath().'/debug-'.date('Y-m-d').'.log',
                'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                //'cache.FileName' => '/PaypalCache' // for determining paypal cache directory
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
                //'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
            )
        );

        // Partner Attribution Id
        // Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
        // To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
        // $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');

        return $apiContext;
    }

}