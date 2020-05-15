<?php

namespace api\modules\web\forms;

use common\helpers\ResultHelper;
use common\models\market\MarketCouponDetails;
use yii\base\Model;

/**
 * 创建订单
 * Class OrderCreateForm
 * @package api\modules\v1\forms
 */
class OrderCreateForm extends Model
{
    public $carts;
    public $buyer_address_id;
    public $buyer_remark;
    public $order_amount;
    public $coupon_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['carts','buyer_address_id','order_amount'], 'required'],
            [['buyer_address_id', 'coupon_id'], 'integer'],
            [['order_amount'], 'number'],
            [['buyer_remark'], 'string','max'=>500],
            [['buyer_address_id'], 'validateCurrency'],
            [['carts'], 'validateCarts'],
            ['coupon_id', 'validateCouponId'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'carts' => 'carts',
            'coupon_id' => 'coupon_id',
            'order_amount' => 'order_amount',
            'buyer_address_id' => 'buyer_address_id',
            'buyer_remark' => '订单备注',
        ];
    }

    public function validateCouponId($attribute)
    {
        $where = [];
        $where['member_id'] = \Yii::$app->getUser()->identity->member->id;
        $where['coupon_status'] = 1;
        $where['coupon_id'] = (int)$this->coupon_id;

        if($this->coupon_id && !MarketCouponDetails::find()->where($where)->count()) {
            $this->addError($attribute, $attribute.' 是无效的优惠券');
        }
    }

    /**
     * 校验购物车ID
     * @param unknown $attribute
     * @return boolean
     */
    public function validateCarts($attribute)
    {
        $value = $this->$attribute;
        if(!is_array($value)) {
            $this->addError($attribute, $attribute.' 必需是数组');
        }

        foreach ($value as $cart) {
            if(!is_numeric($cart['cart_id'])) {
                $this->addError($attribute, $attribute.'校验失败');
                return false;
            }
        }

        return true;
        
    }

    public function validateCurrency($attribute)
    {
        $currency = strtoupper(\Yii::$app->params['currency']);
        if(in_array($currency, ['CNY'])) {
            $this->addError($attribute, \Yii::t('payment','PAYMENT_NOT_SUPPORT_RMB'));
            return false;
        }
        return true;
    }

}
