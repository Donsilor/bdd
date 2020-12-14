<?php

namespace common\models\order;

use common\models\pay\WireTransfer;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%order_tourist}}".
 *
 * @property int $id 主键
 * @property int $order_sn 商户ID
 * @property int $merchant_id 商户ID
 * @property int $store_id 店铺id
 * @property int $tourist_key 游客的KEY
 * @property string $order_amount 订单金额
 * @property string $goods_amount 商品总金额
 * @property string $discount_amount 优惠金额
 * @property string $pay_amount 实际支付金额
 * @property string $refund_amount 退款金额
 * @property string $shipping_fee 运费
 * @property string $tax_fee 税费
 * @property string $safe_fee 保险费
 * @property string $other_fee 附加费
 * @property string $currency 货币
 * @property double $exchange_rate 汇率
 * @property string $ip 下单时IP
 * @property int $order_from 订单来源
 * @property int $ip_area_id IP所在区域
 * @property string $ip_location IP位置
 * @property int $status 状态：0未支付，1已支付，2已同步到标准订单
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $paid_amount 实付款
 * @property string $paid_currency 实际支付货币
 * @property string $buyer_remark 买家留言
 */
class OrderTourist extends \common\models\base\BaseModel
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
//                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_tourist}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'store_id', 'tourist_key', 'status', 'created_at', 'updated_at', 'ip_area_id','order_from'], 'integer'],
            [['order_amount', 'goods_amount', 'discount_amount', 'pay_amount', 'refund_amount', 'shipping_fee', 'tax_fee', 'safe_fee', 'other_fee', 'exchange_rate', 'paid_amount'], 'number'],
            [['currency', 'paid_currency'], 'string', 'max' => 3],
            [['order_sn'], 'string', 'max' => 20],
            [['language'], 'safe'],
            [['ip'], 'string', 'max' => 30],
            [['buyer_remark'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', '主键'),
            'order_sn' => Yii::t('app', '订单编号'),
            'merchant_id' => Yii::t('app', '商户ID'),
            'store_id' => Yii::t('app', '店铺id'),
            'tourist_key' => Yii::t('app', '游客的KEY'),
            'order_amount' => Yii::t('app', '订单金额'),
            'goods_amount' => Yii::t('app', '商品总金额'),
            'discount_amount' => Yii::t('app', '折扣金额'),
            'pay_amount' => Yii::t('app', '实际支付金额'),
            'refund_amount' => Yii::t('app', '退款金额'),
            'shipping_fee' => Yii::t('app', '运费'),
            'tax_fee' => Yii::t('app', '税费'),
            'safe_fee' => Yii::t('app', '保险费'),
            'other_fee' => Yii::t('app', '附加费'),
            'currency' => Yii::t('app', '货币'),
            'exchange_rate' => Yii::t('app', '汇率'),
            'language' => Yii::t('app', '下单时语言'),
            'ip' => Yii::t('app', '下单时IP'),
            'ip_area_id' => Yii::t('app', '归属地区'),
            'ip_location' => Yii::t('app', 'IP位置'),
            'status' => Yii::t('app', '状态'),
            'order_from' => "订单来源",
            'created_at' => Yii::t('app', '下单时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'paid_amount' => \Yii::t('order','实际付款'),
            'paid_currency'=> \Yii::t('common','实际支付货币'),
            'buyer_remark' => '客户留言',
        ];
    }
    
    /**
     * 对应多个商品
     * @return \yii\db\ActiveQuery
     */
    public function getDetails()
    {
        return $this->hasMany(OrderTouristDetails::class,['order_tourist_id'=>'id']);
    }

    /**
     * 对应订单付款信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(OrderTouristInvoice::class, ['order_tourist_id'=>'id']);
    }

    /**
     * 对应订单付款信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(OrderTouristAddress::class, ['order_tourist_id'=>'id']);
    }

    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getWireTransfer()
    {
        return $this->hasOne(WireTransfer::class, ['order_id'=>'id'])->where(['common_pay_wire_transfer.member_id'=>0]);
    }

    /**
     * 对应的订单
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['order_sn'=>'order_sn']);
    }

}
