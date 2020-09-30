<?php

namespace api\modules\web\forms;


use common\enums\AreaEnum;
use common\models\market\MarketCard;
use yii\base\Model;

/**
 * Class CardForm
 * @package api\modules\web\forms
 */
class CardValidateForm extends Model
{
    public $sn;//卡号
    public $pw;//卡密码
    public $card;//卡对象
    public $area_attach;//地区
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sn','pw', 'area_attach'], 'required'],
            ['pw', 'validatePassword'],
            ['area_attach', 'validateAreaAttach'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'sn' => 'sn',
            'pw' => 'pw',
            'area_attach' => '地区',
        ];
    }

    public function validatePassword($attribute)
    {
        if (!$this->hasErrors()) {
            /* @var $user MarketCard */
            $card = $this->getCard();
            if (!$card || !$card->validatePassword($this->pw)) {
                $this->addError($attribute, '验证错误');
            }
        }
    }

    public function validateAreaAttach($attribute)
    {
//        if (!$this->hasErrors()) {
//            /* @var $user MarketCard */
//            $card = $this->getCard();
//            if (!$card || !empty($card->area_attach) && is_array($card->area_attach) && !in_array($this->area_attach, $card->area_attach)) {
//                if(empty($card->area_attach)) {
//                    $area_names = AreaEnum::getMap();
//                }
//                else {
//                    $area_names = [];
//                    foreach ($card->area_attach as $area_attach) {
//                        $area_names[] = AreaEnum::getValue($area_attach);
//                    }
//                }
//
//
//                $this->addError($attribute, sprintf('该购物卡仅限[%s]站点使用', implode(',', $area_names)));
//            }
//        }
    }

    /**
     * @return MarketCard|null
     */
    public function getCard() {
        if(!$this->card) {
            $this->card = MarketCard::findOne(['sn'=>$this->sn]);
        }
        return $this->card;
    }
}
