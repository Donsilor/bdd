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
            'sn' => '购物卡',
            'pw' => '购物卡',
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
                return;
            }

            $time = time();

            //验证开始时间 || 验证结束时间
            if($card->start_time > $time || $card->end_time < $time) {
                $this->addError($attribute, '超出使用时间限制');
                return;
            }

            //验证使用期限
            if($card->max_use_time && $card->first_use_time && ($card->first_use_time+$card->max_use_time) < $time) {
                $this->addError($attribute, '超出使用时间限制');
                return;
            }
        }
    }

    public function validateAreaAttach($attribute)
    {
        if (!$this->hasErrors()) {
            /* @var $user MarketCard */
            $card = $this->getCard();
            if (!$card || !empty($card->area_attach) && is_array($card->area_attach) && !in_array($this->area_attach, $card->area_attach)) {
                $this->addError($attribute, '不在可使用地区');
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
