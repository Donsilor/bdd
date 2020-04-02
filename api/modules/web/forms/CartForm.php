<?php

namespace api\modules\web\forms;


use yii\base\Model;

/**
 * Class CartForm
 * @package api\modules\web\forms
 */
class CartForm extends Model
{
    public $add_type;
    public $goods_id;//商品ID
    public $goods_type;//商品类型(产品线ID)
    public $goods_num;//商品数量
    public $group_type;
    public $group_id;
    public $coupon_id;//折扣券ID
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
             [['goods_id','goods_type','goods_num'], 'required'],
             [['goods_id','goods_type','goods_num','group_type','group_id','coupon_id'], 'number'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'goods_id' => 'goods_id',
            'goods_type' => 'goods_type',
            'goods_num' => 'goods_num',
            'group_type' => 'group_type',
            'group_id' => 'group_id',
            'coupon_id' => 'coupon_id',
        ];
    }
    
    
}
