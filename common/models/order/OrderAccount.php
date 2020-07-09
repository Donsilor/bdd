<?php

namespace common\models\order;

use Yii;

/**
 * This is the model class for table "{{%order_account}}".
 *
 * @property int $order_id 订单ID
 * @property int $merchant_id 商户ID
 * @property string $order_amount 订单总金额
 * @property string $goods_amount 商品总金额
 * @property string $discount_amount 优惠金额
 * @property string $coupon_amount 优惠金额
 * @property string $card_amount 优惠金额
 * @property string $pay_amount 实付款
 * @property string $paid_amount 实付款
 * @property string $refund_amount 退款金额
 * @property string $shipping_fee 运费
 * @property string $tax_fee 税费
 * @property string $safe_fee 保险费
 * @property string $other_fee 附加费
 * @property string $currency 附加费
 * @property string $paid_currency 实际支付货币
 */
class OrderAccount extends \common\models\base\BaseModel
{

    public function behaviors()
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_account}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id', 'merchant_id'], 'integer'],
            [['order_amount', 'goods_amount', 'discount_amount', 'paid_amount', 'coupon_amount', 'card_amount', 'pay_amount', 'refund_amount', 'shipping_fee', 'tax_fee', 'safe_fee', 'other_fee','exchange_rate'], 'number'],
            [['order_id'], 'unique'],
            [['currency', 'paid_currency'], 'string','max'=>5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [                
            'order_id' => '订单ID',
            'merchant_id' => '商户ID',
            'order_amount' => \Yii::t('order','订单总金额'),
            'goods_amount' => \Yii::t('order','商品总金额'),
            'discount_amount' => \Yii::t('order','折扣金额'),
            'coupon_amount' => \Yii::t('order','优惠券金额'),
            'card_amount' => \Yii::t('order','购物卡金额'),
            'pay_amount' => \Yii::t('order','实付款'),
            'paid_amount' => \Yii::t('order','实际付款'),
            'refund_amount' => \Yii::t('order','退款金额'),
            'shipping_fee' => \Yii::t('order','运费'),
            'tax_fee' => \Yii::t('order','税费'),
            'safe_fee' => \Yii::t('order','保险费'),
            'other_fee' => \Yii::t('order','附加费'),
            'exchange_rate'=> \Yii::t('common','汇率'),
            'currency'=> \Yii::t('common','订单货币'),
            'paid_currency'=> \Yii::t('common','实际支付货币'),
        ];
    }
}
