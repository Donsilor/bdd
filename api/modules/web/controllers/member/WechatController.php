<?php


namespace api\modules\web\controllers\member;


use api\controllers\UserAuthController;
use GuzzleHttp\Client;
use yii\web\UnprocessableEntityHttpException;

class WechatController extends UserAuthController
{

    public $modelClass = '';

    public function actionUserInfo()
    {
        try {
            $params = [];
            $params['appid'] = \Yii::$app->debris->config('wechat_appid');
            $params['secret'] = \Yii::$app->debris->config('wechat_appsecret');
            $params['code'] = \Yii::$app->request->get('code', '');

            $url = "https://api.weixin.qq.com/sns/oauth2/access_token";

            $http = new Client();
            $response = $http->get($url, ['query'=>$params]);
            $result = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $exception) {
            $result = [
                'errcode' => '',
                'errmsg' => $exception->getMessage()
            ];
        }

        if(isset($result['openid'])) {
            return ['openid'=>$result['openid']];
        }

        \Yii::$app->services->actionLog->create('获取微信openid','用户：'.\Yii::$app->getUser()->identity->member->username.'获取openid错误', $result);

        throw new UnprocessableEntityHttpException('系统忙，请稍后再试');
    }
}