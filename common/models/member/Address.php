<?php

namespace common\models\member;

use Yii;
use common\behaviors\MerchantBehavior;
use common\helpers\RegularHelper;
use common\enums\StatusEnum;

/**
 * This is the model class for table "{{%member_address}}".
 *
 * @property int $id 主键
 * @property string $merchant_id 商户id
 * @property string $member_id 用户id
 * @property string $province_id 省id
 * @property string $city_id 市id
 * @property string $area_id 区id
 * @property string $address_name 地址
 * @property string $address_details 详细地址
 * @property int $is_default 默认地址
 * @property string $zip_code 邮编
 * @property string $realname 真实姓名
 * @property string $home_phone 家庭号码
 * @property string $mobile 手机号码
 * @property int $status 状态(-1:已删除,0:禁用,1:正常)
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 */
class Address extends \common\models\base\BaseModel
{
    use MerchantBehavior;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%member_address}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['province_id', 'city_id',  'address_details'], 'required'],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号'],
            ['email', 'match', 'pattern' => RegularHelper::email(), 'message' => '请输入正确的邮箱'],
            [['merchant_id', 'member_id','country_id', 'province_id', 'city_id', 'area_id', 'is_default', 'zip_code', 'status', 'created_at', 'updated_at'], 'integer'],
            [['address_name', 'address_details'], 'string', 'max' => 200],
            [['realname'], 'string', 'max' => 200],
            [['firstname','lastname'], 'string', 'max' => 100],
            [['email','country_name','province_name','area_name'], 'string', 'max' => 60],
            [['city_name'], 'string', 'max' => 100],
            [['home_phone', 'mobile'], 'string', 'max' => 20],
            [['mobile_code'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户',
            'member_id' => '用户',
            'country_id' => '国家',
            'province_id' => '省',
            'city_id' => '市',
            'area_id' => '区',
            'address_name' => '地址',
            'country_name' => '国家',
            'province_name' => '省份',
            'city_name' => '城市',
            'area_name' => '县级',
            'address_details' => '详细地址',
            'is_default' => '默认地址',
            'zip_code' => '邮编',
            'firstname' => '名子',
            'lastname' => '姓氏',
            'realname' => '真实姓名',
            'home_phone' => '电话',
            'mobile' => '手机号码',
            'mobile_code' => '手机区号',
            'email' => '邮箱',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * 关联用户
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        //更新地区名称
        $country = Yii::$app->services->area->getArea($this->country_id);
        $province = Yii::$app->services->area->getArea($this->province_id);
        $city = Yii::$app->services->area->getArea($this->city_id);

        $this->country_name = $country['name']?? '';
        $this->province_name = $province['name']?? '';
        $this->city_name = $city['name']?? '';

        if(RegularHelper::verify('chineseCharacters',$this->lastname.''.$this->firstname)){
            $realname  = $this->lastname.''.$this->firstname;
        }else {
            $realname  = $this->firstname.' '.$this->lastname;
        }        
        if(trim($realname) != '' && $realname != $this->realname){
            $this->realname = $realname;
        }
        if ($this->is_default == StatusEnum::ENABLED) {
            self::updateAll(['is_default' => StatusEnum::DISABLED], ['member_id' => $this->member_id, 'is_default' => StatusEnum::ENABLED]);
        }
        return parent::beforeSave($insert);
    }
}
