<?php

namespace api\modules\wap\forms;

use common\enums\StatusEnum;
use common\models\member\Member;
use common\models\api\AccessToken;

/**
 * Class UpPwdForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class UpPwdForm extends \common\models\forms\LoginForm
{
    public $member_id;
    public $original_password;
    public $password;
    public $password_repetition;
    public $group = 'front';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['member_id','original_password', 'password', 'password_repetition'], 'required'],
            [['password'], 'string', 'min' => 6],
            [['password_repetition'], 'compare', 'compareAttribute' => 'password','message'=>'两次输入密码不一致'],// 验证新密码和重复密码是否相等
            ['group', 'in', 'range' => AccessToken::$ruleGroupRnage],
            ['original_password', 'validateOriginalPassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'member_id' => '会员ID',
            'original_password' => '原始密码',
            'password' => '新密码',
            'password_repetition' => '确认新密码',
            'group' => '类型',
            'code' => '验证码',
        ];
    }
    /**
     * 原始密码验证
     *
     * @param $attribute
     */
    public function validateOriginalPassword($attribute)
    {
        if (!$this->hasErrors()) {
            /* @var $user \common\models\base\User */
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->original_password)) {
                $this->addError($attribute, '原始密码错误');
            }
        }
    }
    /**
     * @return Member|mixed|null
     */
    public function getUser()
    {
        if ($this->_user == false) {
            $this->_user = Member::findOne(['id' => $this->member_id, 'status' => StatusEnum::ENABLED, 'is_tourist'=>0]);
        }

        return $this->_user;
    }
}