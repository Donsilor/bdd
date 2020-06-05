<?php

namespace api\modules\web\controllers\member;

use api\controllers\UserAuthController;
use common\enums\OrderFromEnum;
use common\models\member\Address;

/**
 * 收货地址
 *
 * Class AddressController
 * @package api\modules\v1\controllers\member
 * @property \yii\db\ActiveRecord $modelClass
 * @author jianyan74 <751393839@qq.com>
 */
class AddressController extends UserAuthController
{
    /**
     * @var Address
     */
    public $modelClass = Address::class;
    

    public function actionIndex()
    {
        $fields = [
            "id",
            "firstname",
            "lastname",
            'realname',
            "address_name",
            'country_name',
            'province_name',
            'city_name',
            "address_details",
            "country_id",
            "province_id",
            "city_id",
            "member_id",
            "email",
            "mobile",
            "mobile_code",
            "zip_code",
            "is_default",
        ];
        $models = $this->modelClass::find()->select($fields)->where(['member_id'=>$this->member_id])->orderBy('is_default desc, id desc')->all();
        foreach ($models as &$model){
            $model['country_name'] = \Yii::$app->services->area->getAreaName($model['country_id']);
            $model['province_name'] = \Yii::$app->services->area->getAreaName($model['province_id']);
            $model['city_name'] = \Yii::$app->services->area->getAreaName($model['city_id']);
            $model['platforms'] = OrderFromEnum::countryIdToPlatforms($models['country_id']);
        }

        return $models;
        
    }
    /**
     * 详情
     * @return mixed|\api\controllers\NULL|\yii\db\ActiveRecord
     */
    public function actionInfo()
    { 
        return $this->info();
    }
    /**
     * 添加
     * @return mixed|\api\controllers\NULL|\yii\db\ActiveRecord
     */
    public function actionAdd()
    {
        return $this->add();
    }
    /**
     * 编辑
     * @return mixed|\api\controllers\NULL|array
     */
    public function actionEdit()
    {
        return $this->edit();
    }
    /**
     * 删除
     * @return mixed|\api\controllers\NULL|\api\controllers\unknown[]
     */
    public function actionDel()
    {
        $num = $this->del();
        return ['num'=>$num];
    }
    
}
