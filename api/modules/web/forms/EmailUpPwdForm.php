<?php

namespace api\modules\web\forms;

use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\models\member\Member;
use common\models\api\AccessToken;
use common\models\common\EmailLog;
use common\models\validators\EmailCodeValidator;

/**
 * Class UpPwdForm
 * @package api\modules\v1\forms
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
                ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的邮箱地址'],
                [['password_repetition'], 'compare', 'compareAttribute' => 'password'],// 验证新密码和重复密码是否相等
                ['group', 'in', 'range' => AccessToken::$ruleGroupRnage],
                ['password', 'validateEmail'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
                'email' => '邮箱地址',
                'password' => '密码',
                'password_repetition' => '重复密码',
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
            $this->_user = Member::findOne(['email' => $this->email, 'status' => StatusEnum::ENABLED]);
        }
        
        return $this->_user;
    }
}