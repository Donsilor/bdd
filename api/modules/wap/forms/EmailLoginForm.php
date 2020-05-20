<?php

namespace api\modules\wap\forms;

use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\helpers\ResultHelper;
use common\models\member\Member;
use yii\base\Model;

/**
 * Class LoginForm
 * @package api\modules\wap\forms
 * @author jianyan74 <751393839@qq.com>
 */
class EmailLoginForm extends Model
{
    public $username;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['username', 'password', 'group'], 'required'],
            [['username'], 'required'],
            ['username', 'match', 'pattern' => RegularHelper::email(), 'message' => '请输入正确的邮箱'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => '邮箱',
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


    /**
     * 用户登录
     *
     * @return mixed|null|static
     */
    public function login(){
        $user = Member::findOne(['email' => $this->username, 'is_tourist'=>0]);
        if(!$user){
            $user = new Member();
            $user->email = $this->username;
        }
        if (!$user->save()) {
            return ResultHelper::api(422, '登陆失败');
        }
        return $user;
    }



}
