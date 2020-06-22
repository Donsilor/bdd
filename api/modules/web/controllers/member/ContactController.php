<?php

namespace api\modules\web\controllers\member;

use api\modules\web\forms\ContactForm;
use common\helpers\ResultHelper;
use common\models\member\Contact;
use common\models\member\Member;
use wsl\ip2location\Ip2Location;
use Yii;
use api\controllers\OnAuthController;
use yii\helpers\ArrayHelper;

/**
 * Class ProvincesController
 * @package api\modules\v1\controllers\member
 * @author jianyan74 <751393839@qq.com>
 */
class ContactController extends OnAuthController
{
    /**
     * @var Provinces
     */
    public $modelClass = Contact::class;
    protected $authOptional = ['index','add','update'];
    /**
     * 根据邮箱获取留言
     *
     * @param int $pid
     * @return array|yii\data\ActiveDataProvider
     */



    public function actionIndex()
    {
        $username = Yii::$app->request->get('username');
        if(!$username){
            return ResultHelper::api(400, '用户名不能为空');
        }
        $page = Yii::$app->request->get('page',1);
        $page_size = Yii::$app->request->get('page_size',5); //每页显示数
        $query = $this->modelClass::find()->alias('b')
            ->leftJoin(Member::tableName().' m','m.id = b.member_id')
            ->where(['m.email'=>$username])
            ->select(['b.title','from_unixtime(b.created_at,"%Y.%m.%d")  created_at','b.content','m.email'])
            ->orderby('b.created_at desc');

        $result = $this->pagination($query,$page,$page_size);
        return $result;
    }

    /**
     * 添加留言
     *
     * @param int $pid
     * @return array|yii\data\ActiveDataProvider
     */
    public function actionAdd()
    {

        $model = new ContactForm();
        $model->attributes = Yii::$app->request->post();

        if (!$model->validate()) {
            return ResultHelper::api(422, $this->getError($model));
        }
        //提交
        $model->book_time = $model->book_date." ".$model->book_time;
        $model->member_id = $this->member_id;
        $model->language = $this->getLanguage();
        $ip = Yii::$app->request->getUserIP();
        $model->ip = $ip;
        //根据ip获取城市
        list(,$address) = Yii::$app->ipLocation->getLocation($ip);

        $model->ip_location = $address;
        $contact = new $this->modelClass();
        $contact->attributes = ArrayHelper::toArray($model);
        $contact->platform = $this->platform;

        if (!$contact->save()) {
            return ResultHelper::api(422, $this->getError($contact));
        }
        return $contact;

    }




}