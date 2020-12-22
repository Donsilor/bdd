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
    public $goods_attr;//商品属性
    public $createTime;
    public $lettering;

    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id','goods_type','goods_num','createTime'], 'required'],
            [['goods_id','goods_type','goods_num','group_type','group_id','coupon_id','createTime'], 'number'],
            ['goods_attr', 'validateGoodsAttr'],
            ['lettering', 'string'],
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
            'goods_attr' => 'goods_attr',
            'lettering' => 'lettering',
            'createTime' => 'createTime'
        ];
    }

    public function validateGoodsAttr($attribute)
    {
        if (!$this->hasErrors() && is_array($this->goods_attr) && !empty($this->goods_attr)) {
            foreach ($this->goods_attr as $item) {
                if(!isset($item['config_id']) || !isset($item['config_attr_id'])) {
                    $this->addError($attribute, '验证错误');
                    return;
                }

                $attr = \Yii::$app->services->goodsAttribute->getValuesByAttrId($item['config_id']);

                if(!$attr || !isset($attr[$item['config_attr_id']])) {
                    $this->addError($attribute, '验证错误');
                    return;
                }
            }
        }
    }

    public function getSign()
    {
        return md5(sprintf('ip:[%s],createTime:[%s],goods_type:[%s],goods_id:[%s]', \Yii::$app->request->userIP, $this->createTime, $this->goods_type, $this->goods_id));
    }
}
