<?php

namespace services\common;

use Yii;
use common\helpers\DebrisHelper;
use common\enums\StatusEnum;
use common\components\Service;
use common\models\common\ActionLog;
use common\helpers\ArrayHelper;
use common\enums\SubscriptionActionEnum;
use common\enums\SubscriptionReasonEnum;
use common\enums\MessageLevelEnum;
use Zhuzhichao\IpLocationZh\Ip;
use common\models\common\SmsLog;

/**
 * Class ActionLogService
 * @package services\sys
 * @author jianyan74 <751393839@qq.com>
 */
class ActionLogService extends Service
{
    /**
     * 行为日志
     *
     * @param $behavior
     * @param $remark
     * @param bool $noRecordData
     * @param $url
     * @throws \yii\base\InvalidConfigException
     */
    public function create($behavior, $remark, $noRecordData = true, $url = '', $level = '')
    {
        empty($url) && $url = DebrisHelper::getUrl();

        $ip = Yii::$app->request->userIP;
        $model = new ActionLog();
        $model->behavior = $behavior;
        $model->remark = $remark;
        $model->user_id = Yii::$app->services->backend->getUserId();
        $model->url = $url;
        $model->app_id = Yii::$app->id;
        $model->get_data = Yii::$app->request->get();
        $model->post_data = $noRecordData == true ? Yii::$app->request->post() : [];
        // $model->post_data = $noRecordData == true ? file_get_contents("php://input") : [];
        $model->header_data = ArrayHelper::toArray(Yii::$app->request->headers);
        $model->method = Yii::$app->request->method;
        $model->module = Yii::$app->controller->module->id;
        $model->controller = Yii::$app->controller->id;
        $model->action = Yii::$app->controller->action->id;
        $model->device = Yii::$app->debris->detectVersion();
        $model->ip = ip2long($ip);
        $model->ip = (string) $model->ip;
        // ip转地区
        if (!empty($ip) && ip2long($ip) && ($ipData = Ip::find($ip))) {
            $model->country = $ipData[0];
            $model->provinces = $ipData[1];
            $model->city = $ipData[2];
        }        
        $model->save(); 
        
        //短信提醒 begin
        $route = $model->controller.'/'.$model->action;
        $errorSmsNoice  = Yii::$app->params['errorSmsNoice']??[]; 
        if(!empty($errorSmsNoice['open']) && in_array($route,$errorSmsNoice['routes']) ) {
            $key = md5(Yii::$app->id.':'.$route.':'.$model->user_id);
            if(!Yii::$app->cache->get($key)){
                foreach ($errorSmsNoice['mobiles'] as $mobile){
                    Yii::$app->services->sms->queue(true)->send($mobile,SmsLog::USAGE_ERROR_NOTICE,['username'=>'管理员','sitename'=>'BDD官网','action'=>'['.$model->behavior.']','code'=>$model->id]);
                }
                Yii::$app->cache->set($key,$model->behavior,600);
            }
       }
       //短信提醒 end
        if (!empty($level)) {
            // 创建订阅消息
            $actions = [
                MessageLevelEnum::INFO => SubscriptionActionEnum::BEHAVIOR_INFO,
                MessageLevelEnum::WARNING => SubscriptionActionEnum::BEHAVIOR_WARNING,
                MessageLevelEnum::ERROR => SubscriptionActionEnum::BEHAVIOR_ERROR,
            ];

            Yii::$app->services->backendNotify->createRemind(
                $model->id,
                SubscriptionReasonEnum::BEHAVIOR_CREATE,
                $actions[$level],
                $model['user_id'],
                MessageLevelEnum::getValue($level) . "行为：$url"
            );
        }
        return $model;
    }

    /**
     * @param $app_id
     * @param $user_id
     * @param int $limit
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findByAppId($app_id, $user_id, $limit = 12)
    {
        return ActionLog::find()
            ->where(['app_id' => $app_id, 'user_id' => $user_id, 'status' => StatusEnum::ENABLED])
            ->andWhere(['in', 'behavior', ['login', 'logout']])
            ->limit($limit)
            ->orderBy('id desc')
            ->asArray()
            ->all();
    }
}