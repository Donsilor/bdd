<?php

namespace common\enums;

use Yii;

/**
 * Class CacheEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class CacheEnum
{
    /**
     * @return array
     */
    protected static function getMap(): array
    {
        $merchant_id = Yii::$app->services->merchant->getId();

        return [
            'config' => $merchant_id, // 公用参数
            'addonsConfig' => $merchant_id, // 插件配置
            'apiAccessToken' => $merchant_id, // 用户信息记录
            'wechatFansStat' => $merchant_id, // 粉丝统计缓存
            'addons' => '', // 插件
            'provinces' => '', // 省市区
            'area'=>'', //国家地区
            'ipBlacklist' => '', // ip黑名单
            'actionBehavior' => '', // 需要被记录的行为
            'actionBehavior' => '', // 需要被记录的行为
            'goodsAttr'=>$merchant_id,//商品属性
            'goodsAttrValue'=>$merchant_id,//商品属性值            
            'currency'=>$merchant_id, //货币汇率
        ];
    }

    /**
     * @param $key
     * @param string $prefix
     * @return string
     */
    public static function getPrefix($key, $prefix = '')
    {
        if (empty($prefix)) {
            $prefix = static::getMap()[$key] ?? '';
        }
        $cachePrefix = \Yii::$app->params['cachePrefix'];  
        return $cachePrefix . $prefix . $key;
    }
}