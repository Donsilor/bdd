<?php

namespace api\modules\web\forms;

use common\enums\StatusEnum;
use common\models\api\AccessToken;
use common\models\member\Member;

/**
 * Class LoginForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class LoginForm extends \common\models\forms\LoginForm
{
    public $group = 'front';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password', 'group'], 'required'],
            ['password', 'validatePassword'],
            ['group', 'in', 'range' => AccessToken::$ruleGroupRnage]
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => \Yii::t('member','登录帐号'),
            'password' => \Yii::t('member','登录密码'),
            'group' => \Yii::t('member','组别'),
        ];
    }

    /**
     * 用户登录
     *
     * @return mixed|null|static
     */
    public function getUser()
    {
        if ($this->_user == false) {
            // email 登录
            if (strpos($this->username, "@")) {
                $this->_user = Member::findOne(['email' => $this->username, 'status' => StatusEnum::ENABLED, 'is_tourist'=>0]);
            } else {
                $this->_user = Member::findByUsername($this->username);
            }
        }

        return $this->_user;
    }





}
