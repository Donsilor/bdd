<?php

namespace backend\modules\market\forms;

use common\models\market\MarketCard;

class CardFrom extends MarketCard
{

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['count', 'amount','start_time', 'end_time', 'goods_type_attach'], 'required'],
            [['count', 'amount'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'count' => '生成数量',
            'amount' => '金额',
            'goods_type_attach' => '产品线',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
        ];
    }

    public function afterValidate()
    {
        $this->setAttribute('start_time' , strtotime($this->start_time));
        $this->setAttribute('end_time' , strtotime($this->end_time .' +1 day'));
        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }
}