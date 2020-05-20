<?php

namespace api\modules\wap\forms;

use Yii;
use yii\base\Model;
use common\helpers\RegularHelper;
use common\models\common\EmailLog;
use common\models\member\Member;

/**
 * Class SmsCodeForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class EmailCodeForm extends Model
{
    /**
     * @var
     */
    public $email;
    
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
                [['email', 'usage'], 'required'],
                [['usage'], 'in', 'range' => array_keys(EmailLog::$usageExplain)],
                ['email', 'match', 'pattern' => RegularHelper::email(), 'message' => '请输入正确的邮箱地址'],
                ['email', 'validateEmail'],
        ];
    }
    
    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
                'email' => '邮箱',
                'usage' => '用途',
        ];
    }
    
    /**
     * 验证手机号码
     * @param unknown $attribute
     * @return boolean
     */
    public function validateEmail($attribute)
    {
        $count = Member::find()->where(['email'=>$this->attributes, 'is_tourist'=>0])->count();
        if($this->usage == EmailLog::USAGE_UP_PWD || $this->usage == EmailLog::USAGE_LOGIN) {
            if(!$count){
                $this->addError($attribute,"邮箱地址未绑定账号");
                return false;
            }
        }else if($this->usage == EmailLog::USAGE_REGISTER){
            if($count){
                $this->addError($attribute,"邮箱地址已绑定过账号");
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
        $code = rand(10000, 99999);
        return Yii::$app->services->mailer->send($this->email,$this->usage,['code'=>$code]);
    }
}