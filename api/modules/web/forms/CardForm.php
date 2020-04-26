<?php

namespace api\modules\web\forms;


use common\models\market\MarketCard;
use yii\base\Model;

/**
 * Class CardForm
 * @package api\modules\web\forms
 */
class CardForm extends Model
{
    public $sn;//卡号
    public $pw;//卡密码
    public $card;//卡对象
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sn','pw'], 'required'],
            ['pw', 'validatePassword'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'sn' => 'sn',
            'pw' => 'pw',
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
