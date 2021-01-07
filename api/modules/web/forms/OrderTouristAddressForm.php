<?php

namespace api\modules\web\forms;

namespace api\modules\web\forms;


use common\enums\OrderFromEnum;
use common\models\order\OrderTouristAddress;

class OrderTouristAddressForm extends OrderTouristAddress
{
    public $platform;
    public $country_id;

    public function rules()
    {
        return [
            [['country_id'], 'required'],
            [['country_id'], 'integer'],
            [['country_id'], 'validateCountryId'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'platform' => '国家区域',
            'country_id' => '国家区域',
        ];
    }

    public function validateCountryId($attribute)
    {
        $platforms = OrderFromEnum::countryIdToPlatforms($this->country_id);
        if(!in_array($this->platform, $platforms)) {
            $this->addError($attribute, \Yii::t('address', '您选择的配送地址跟当前所在地区不一致，请重新选择或联系客服处理！'));
        }
    }
}