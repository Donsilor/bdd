<?php

namespace api\modules\web\forms;

use Yii;
use yii\base\Model;
use common\helpers\RegularHelper;
use common\models\common\SmsLog;
use common\models\member\Member;

/**
 * Class SmsCodeForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class SmsCodeForm extends Model
{
    /**
     * @var
     */
    public $mobile;

    /**
     * @var
     */
    public $usage;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['mobile', 'usage'], 'required'],
            [['usage'], 'in', 'range' => array_keys(SmsLog::$usageExplain)],
            ['mobile', 'match', 'pattern' => RegularHelper::chinaMobile(), 'message' => '请输入正确的手机号'],
            ['mobile', 'validateMobile'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'mobile' => '手机号码',
            'usage' => '用途',
        ];
    }
    /**
     * 验证手机号码
     * @param unknown $attribute
     * @return boolean
     */
    public function validateMobile($attribute)
    {
        $count = Member::find()->where(['mobile'=>$this->attributes, 'is_tourist'=>0])->count();
        if($this->usage == SmsLog::USAGE_UP_PWD || $this->usage == SmsLog::USAGE_LOGIN) {
             if(!$count){
                 $this->addError($attribute,"手机号未绑定账号");
                 return false;
             }
        }else if($this->usage == SmsLog::USAGE_REGISTER){
            if($count){
                $this->addError($attribute,"手机号已绑定过账号");
                return false;
            }
        }
        return true;
    }
    /**
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function send()
    {
        $code = rand(1000, 9999);
        return Yii::$app->services->sms->send($this->mobile, $this->usage, ['code'=>$code]);
    }
}