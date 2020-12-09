<?php

namespace common\components;

use common\components\payment\GlobalAlipayPay;
use common\components\payment\PaydollarPay;
use common\components\payment\PaypalPay;
use common\components\payment\StripePay;
use Yii;
use yii\base\Component;
use common\components\payment\AliPay;
use common\components\payment\UnionPay;
use common\components\payment\WechatPay;
use common\helpers\ArrayHelper;

/**
 * 支付组件
 *
 * Class Pay
 * @package common\components
 * @property \common\components\payment\WechatPay $wechat
 * @property \common\components\payment\AliPay $alipay
 * @property \common\components\payment\UnionPay $union
 * @author jianyan74 <751393839@qq.com>
 */
class Pay extends Component
{
    /**
     * 公用配置
     *
     * @var
     */
    protected $rfConfig;

    public function init()
    {
        $this->rfConfig = Yii::$app->debris->configAll();

        parent::init();
    }

    /**
     * 支付宝支付
     *
     * @param array $config
     * @return AliPay
     * @throws \yii\base\InvalidConfigException
     */
    public function alipay(array $config = [])
    {
        return new AliPay(ArrayHelper::merge([
            'app_id' => $this->rfConfig['alipay_appid'],
            'notify_url' => '',
            'return_url' => '',
            'ali_public_key' => $this->rfConfig['alipay_cert_path'],
            // 加密方式： ** RSA2 **
            'private_key' => $this->rfConfig['alipay_key_path'],
            'sandbox' => !empty($this->rfConfig['alipay_sandbox'])
        ], $config));
    }

    /**
     * 支付宝国际版
     * @param array $config
     * @return GlobalAlipayPay
     */
    public function globalAlipay(array $config = [])
    {
        return new GlobalAlipayPay(ArrayHelper::merge([
            'partner' => $this->rfConfig['global_alipay_partner'],
            'key' => $this->rfConfig['global_alipay_key'],
            'sign_type' => $this->rfConfig['global_alipay_sign_type'],
            'private_key' => $this->rfConfig['global_alipay_private_key'],
            'alipay_public_key' => $this->rfConfig['global_alipay_alipay_public_key'],
            'return_url' => '',
            'notify_url' => '',
            'environment' => !empty($this->rfConfig['global_alipay_sandbox']) ? 'sandbox' : '',
        ], $config));
    }

    /**
     * 微信支付
     *
     * @param array $config
     * @return WechatPay
     */
    public function wechat(array $config = [])
    {
        return new WechatPay(ArrayHelper::merge([
            'app_id' => $this->rfConfig['wechat_appid'], // 公众号 APPID
            'mch_id' => $this->rfConfig['wechat_mchid'],
            'api_key' => $this->rfConfig['wechat_api_key'],
            'cert_client' => $this->rfConfig['wechat_cert_path'], // optional，退款等情况时用到
            'cert_key' => $this->rfConfig['wechat_key_path'],// optional，退款等情况时用到
        ], $config));
    }

    /**
     * 银联支付
     *
     * @param array $config
     * @return UnionPay
     * @throws \yii\base\InvalidConfigException
     */
    public function union(array $config = [])
    {
        return new UnionPay(ArrayHelper::merge([
            'mch_id' => $this->rfConfig['union_mchid'],
            'notify_url' => '',
            'return_url' => '',
            'cert_id' => $this->rfConfig['union_cert_id'],
            'private_key' => $this->rfConfig['union_private_key'],
        ], $config));
    }

    /**
     * @param array $config
     * @return PaypalPay
     */
    public function paypal(array $config = [])
    {
        return new PaypalPay(ArrayHelper::merge([
            //'app_id' => $this->rfConfig['alipay_appid'],
            'notify_url' => '',
            'return_url' => '',

            'client_id' => $this->rfConfig['paypal_client_id'],
            'client_secret' => $this->rfConfig['paypal_client_secret'],
            'sandbox' => !empty($this->rfConfig['paypal_sandbox'])
        ], $config));
    }

    /**
     * @param array $config
     * @return PaydollarPay
     */
    public function paydollar(array $config = [])
    {
        return new PaydollarPay(ArrayHelper::merge([
            //'app_id' => $this->rfConfig['alipay_appid'],
            'notify_url' => '',
            'return_url' => '',

            'merchant_id' => $this->rfConfig['paydollar_merchant_id'],
            'security' => $this->rfConfig['paydollar_secure_hash_secret'],
            'sandbox' => !empty($this->rfConfig['paydollar_sandbox']),
            'loginId' => $this->rfConfig['paydollar_login_id'],
            'password' => $this->rfConfig['paydollar_password']
        ], $config));
    }

    public function stripe(array $config = [])
    {
        $apiKey = $this->rfConfig['stripe_api_key']??null;

        if($apiKey) {
            $config['apiKey'] = $apiKey;
        }

        return new StripePay(ArrayHelper::merge([
            'apiKey' => 'sk_test_51Hh91GEg2ty3UyHNkc6aYtw29SoM4qSqlttaOQpwj5oMD9RJUKyZAYSWBxau3LZwbLULayfTsad1lGLcrhulVClK009rLNjmU7',
        ], $config));
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (\Exception $e) {
            if ($this->$name()) {
                return $this->$name([]);
            } else {
                throw $e->getPrevious();
            }
        }
    }
}