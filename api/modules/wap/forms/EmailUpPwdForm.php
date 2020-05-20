<?php

namespace api\modules\wap\forms;

use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\models\member\Member;
use common\models\api\AccessToken;
use common\models\common\EmailLog;
use common\models\validators\EmailCodeValidator;

/**
 * Class UpPwdForm
 * @package api\modules\wap\forms
 * @author jianyan74 <751393839@qq.com>
 */
class EmailUpPwdForm extends \common\models\forms\LoginForm
{
    public $email;
    public $password;
    public $password_repetition;
    public $code;
    public $group = 'front';
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
                [['email', 'group', 'code', 'password', 'password_repetition'], 'required'],
                [['password'], 'string', 'min' => 6],
                ['code', EmailCodeValidator::class, 'usage' => EmailLog::USAGE_UP_PWD],
                ['email', 'match', 'pattern' => RegularHelper::email(), 'message' => '请输入正确的邮箱地址'],
                [['password_repetition'], 'compare', 'compareAttribute' => 'password','message'=>'两次输入密码不一致'],// 验证新密码和重复密码是否相等
                ['group', 'in', 'range' => AccessToken::$ruleGroupRnage],
                ['password', 'validateEmail'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
                'email' => '邮箱地址',
                'password' => '密码',
                'password_repetition' => '确认密码',
                'group' => '类型',
                'code' => '验证码',
        ];
    }
    
    /**
     * @param $attribute
     */
    public function validateEmail($attribute)
    {
        if (!$this->getUser()) {
            $this->addError($attribute, '找不到用户');
        }
    }
    
    /**
     * @return Member|mixed|null
     */
    public function getUser()
    {
        if ($this->_user == false) {
            $this->_user = Member::findOne(['email' => $this->email, 'status' => StatusEnum::ENABLED, 'is_tourist'=>0]);
        }
        
        return $this->_user;
    }
}