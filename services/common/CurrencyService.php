<?php

namespace services\common;

use Yii;
use common\enums\CacheEnum;
use common\models\common\Currency;
use common\enums\StatusEnum;
use yii\web\UnprocessableEntityHttpException;
use yii\base\Component;


/**
 * Class LogService
 * @package services\common
 */
class CurrencyService extends Component
{
    public $currencies ;
    /**
     *  获取货币符号
     * 
     * @param string $name 字段名称
     * @param bool $noCache true 不从缓存读取 false 从缓存读取
     * @return bool|string
     */
    public function getSign($code = null, $noCache = false)
    {
        if($code === null) {
            $code  = \Yii::$app->params['currency'];
        }
        $info = $this->getCurrencyInfo($code , $noCache);
        return $info['sign'] ?? \Yii::$app->params['currencySign'];
    }
    /**
     * 汇率
     * @param unknown $code
     * @param string $noCache
     * @return mixed
     */
    public function getRate($code = null,$noCache = false)
    {
        if($code === null) {
            $code  = \Yii::$app->params['currency'];
        }
        $info = $this->getCurrencyInfo($code , $noCache);
        return $info['rate'] ?? 1;
    }
    /**
     * 查询货币详情
     * @param unknown $attr_id
     * @param string $noCache
     * @return array
     */
    public function getCurrencyInfo($code , $noCache = false , $merchant_id = '')
    {   
        $code = strtoupper($code);
        $currencies = $this->getCurrencyList($noCache , $merchant_id);
        return $currencies[$code]??[];
    }
    /**
     * 查询货币列表
     * @param unknown $value_id
     * @param string $noCache
     * @return array
     */
    public function getCurrencyList($noCache = false , $merchant_id = '')
    {
        $cacheKey = CacheEnum::getPrefix('currency',$merchant_id);
        if($this->currencies) {
            return $this->currencies;
        }         
        if (!($currencies = Yii::$app->cache->get($cacheKey)) || $noCache == true) {
            
            $models = Currency::find()->select(['code','name','sign','rate','refer_rate'])->where(['status'=>StatusEnum::ENABLED])->asArray()->all();
            
            $currencies = [];
            foreach ($models as $row) {
                $currencies[$row['code']] = [
                        'name'=>$row['name'],
                        'code'=>strtoupper($row['code']),
                        'sign'=>$row['sign'],
                        'rate'=>$row['rate'],
                ];
            }
            $duration = (int) rand(3600,4000);//防止缓存穿透
            // 设置缓存
            Yii::$app->cache->set($cacheKey, $currencies,$duration);
           
        }
        $this->currencies = $currencies;
        
        return $currencies;
    }
    /**
     * 货币金额转换(从A货币转到B货币)
     * 
     * @param number $amount
     * @param number $format
     * @param string $toCurrency 为空表示转到当前货币
     * @param string $fromCurrency 为空表示从基础货币(RMB)开始
     * @throws UnprocessableEntityHttpException
     * @return array
     */
    public function exchangeAmount($amount ,$format = 2, $toCurrency = null, $fromCurrency = null, $toRate = null)
    {
        if($toCurrency == null) {
            $toCurrency = \Yii::$app->params['currency'];
        }
        $toInfo = $this->getCurrencyInfo($toCurrency);
        if(empty($toInfo['rate']) || $toInfo['rate'] <= 0) {
            throw new UnprocessableEntityHttpException("Currency rate is wrong!");
        }
        if($fromCurrency == null) {
            $fromRate = 1;
        } else {
            $fromRate = $this->getRate($fromCurrency);
        }
        if($toRate == null ){
            $toRate = $toInfo['rate'] ?? 1;
        }        
        $amount = bcmul(bcdiv($amount,$fromRate,5),$toRate,$format+1);        
        return round($amount,$format);
    }
    /**
     * 货币代号
     * @param string $toCurrency 为空表示转到当前货币
     * @return mixed
     */
    public function getCurrency($code = null)
    {
        return \Yii::$app->params['currency'];
    }
    /**
     * 基础货币代号
     * @return mixed
     */
    public function getBaseCurrency()
    {
        return \Yii::$app->params['currencyBase'];
    }
    /**
     * 货币转换成 基础货币
     * @param unknown $amount
     * @param number $format
     * @param unknown $fromCurrency
     * @return array
     */
    public function toBaseAmount($amount,$format = 2,$fromCurrency = null)
    {
        $toCurrency = $this->getBaseCurrency();
        
        if($fromCurrency == null) {
            $fromCurrency = $this->getCurrency();
        } elseif ($fromCurrency == $toCurrency) {
            return $amount; 
        }
        return $this->exchangeAmount($amount,$format,$toCurrency,$fromCurrency);
    }       

}