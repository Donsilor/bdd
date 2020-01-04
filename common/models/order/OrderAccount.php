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
 * @property string $pay_amount 实际支付金额
 * @property string $refund_amount 退款金额
 * @property string $shipping_fee 运费
 * @property string $tax_fee 税费
 * @property string $safe_fee 保险费
 * @property string $other_fee 附加费
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
            [['order_amount', 'goods_amount', 'discount_amount', 'pay_amount', 'refund_amount', 'shipping_fee', 'tax_fee', 'safe_fee', 'other_fee','currency_rate'], 'number'],
            [['order_id'], 'unique'],
            [['currency_code'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        
        $currencySign = \Yii::$app->services->currency->currencySign();
        
        return [                
            'order_id' => '订单ID',
            'merchant_id' => '商户ID',
            'order_amount' => \Yii::t('order','订单总金额')."({$currencySign})",
            'goods_amount' => \Yii::t('order','商品总金额')."({$currencySign})",
            'discount_amount' => \Yii::t('order','优惠金额')."({$currencySign})",
            'pay_amount' => \Yii::t('order','支付金额')."({$currencySign})",
            'refund_amount' => \Yii::t('order','退款金额')."({$currencySign})",
            'shipping_fee' => \Yii::t('order','运费')."({$currencySign})",
            'tax_fee' => \Yii::t('order','税费')."({$currencySign})",
            'safe_fee' => \Yii::t('order','保险费')."({$currencySign})",
            'other_fee' => \Yii::t('order','附加费')."({$currencySign})",
            'currency_rate'=> \Yii::t('common','汇率'),
            'currency_code'=> \Yii::t('common','货币'),
        ];
    }
}
