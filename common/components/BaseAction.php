<?php

namespace common\components;

use Yii;
use yii\base\Model;
use common\enums\AppEnum;
use yii\base\Exception;

/**
 * trait BaseAction
 * @package common\components
 * @author jianyan74 <751393839@qq.com>
 */
trait BaseAction
{
    protected $merchant_id;
    protected $member_id;
    /**
     * 默认分页
     *
     * @var int
     */
    protected $page = 1;
    protected $pageSize = 10;    
    
    //当前语言
    protected $language;
    //当前货币
    protected $currency;
    
    /**
     * 初始化通用参数
     */
    public function initParams()
    {
        $language = \Yii::$app->request->get("language");
        if($language) {
            $language = str_replace($language, '_', '-');
            \Yii::$app->language = $language;
            \Yii::$app->params['language'] = $language;
        }
        $currency = \Yii::$app->request->get("currency");
        if($currency) {
            \Yii::$app->params['currency'] = $currency;
        }        
        $this->language = \Yii::$app->params['language'];
        $this->currency = \Yii::$app->params['currency'];
    }
    /**
     * 商户id
     *
     * @return int
     */
    public function getMerchantId($constraint = false)
    {
        // 总后台不允许开启多商户
        if (Yii::$app->id == AppEnum::BACKEND) {
            return '';
        }

        if (false === $constraint && false === Yii::$app->params['merchantOpen']) {
            return '';
        }

        if (!$this->merchant_id) {
            $this->merchant_id = Yii::$app->services->merchant->getId();
        }

        return $this->merchant_id;
    }    

    /**
     * @param Model $model
     * @return string
     */
    public function getError(Model $model)
    {
        return $this->analyErr($model->getFirstErrors());
    }

    /**
     * 重载配置
     *
     * @param $merchant_id
     * @throws \yii\web\UnauthorizedHttpException
     */
    public function afreshLoad($merchant_id)
    {
        // 微信配置 具体可参考EasyWechat
        Yii::$app->params['wechatConfig'] = [];
        // 微信支付配置 具体可参考EasyWechat
        Yii::$app->params['wechatPaymentConfig'] = [];
        // 微信小程序配置 具体可参考EasyWechat
        Yii::$app->params['wechatMiniProgramConfig'] = [];
        // 微信开放平台第三方平台配置 具体可参考EasyWechat
        Yii::$app->params['wechatOpenPlatformConfig'] = [];
        // 微信企业微信配置 具体可参考EasyWechat
        Yii::$app->params['wechatWorkConfig'] = [];
        // 微信企业微信开放平台 具体可参考EasyWechat
        Yii::$app->params['wechatOpenWorkConfig'] = [];

        (new Init())->afreshLoad($merchant_id);
    }

    /**
     * 解析错误
     *
     * @param $fistErrors
     * @return string
     */
    protected function analyErr($firstErrors)
    {
        return Yii::$app->debris->analyErr($firstErrors);
    }

    /**
     * @param $model \yii\db\ActiveRecord|Model
     * @throws \yii\base\ExitException
     */
    protected function activeFormValidate($model)
    {
        if (Yii::$app->request->isAjax && !Yii::$app->request->isPjax) {
            if ($model->load(Yii::$app->request->post())) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                Yii::$app->response->data = \yii\widgets\ActiveForm::validate($model);
                Yii::$app->end();
            }
        }
    }

    /**
     * 错误提示信息
     *
     * @param string $msgText 错误内容
     * @param string $skipUrl 跳转链接
     * @param string $msgType 提示类型 [success/error/info/warning]
     * @return mixed
     */
    protected function message($msgText, $skipUrl, $msgType = null)
    {
        if (!$msgType || !in_array($msgType, ['success', 'error', 'info', 'warning'])) {
            $msgType = 'success';
        }
        //$msgText = Yii::t('message',$msgText);
        $msgText = $this->substr($msgText);
        Yii::$app->getSession()->setFlash($msgType, $msgText);
        return $skipUrl;
    }
    
    /**
     * 新增/编辑多语言
     * @param unknown $model
     * @param string $is_ajax
     */
    public function editLang(& $model,$is_ajax = false){
        
        $langModel = $model->langModel();
        $langClassName = substr(strrchr($langModel->className(), '\\'), 1);
        $langPosts = Yii::$app->request->post($langClassName);
        if(empty($langPosts)){
            return false;
        }
        foreach ($langPosts as $lang_key=>$lang_post){
            $is_new = true;
            foreach ($model->langs as $langModel){
                if($lang_key == $langModel->language){
                    $langModel->load([$langClassName =>$langPosts[$langModel->language]]);
                    $model->link('langs', $langModel);
                    $is_new = false;
                    break;
                }
            }
            if($is_new == true){
                $langModel = $model->langModel();
                $langModel->load([$langClassName =>$lang_post]);
                $langModel->master_id = $model->id;
                $langModel->language = $lang_key;
                if(false === $langModel->save()){
                    throw new Exception($this->getError($langModel));
                }
            }
        }
        
        return true;
    }
    
    public function substr($message,$length = 900)
    {
        return \yii\helpers\StringHelper::truncate($message, $length);
    }
}