<?php

namespace common\models\order;

use common\models\common\PayLog;
use common\models\market\MarketCardDetails;
use common\models\member\Member;
use Yii;

/**
 * This is the model class for table "{{%order}}".
 *
 * @property int $id 订单索引id
 * @property int $merchant_id 商户ID
 * @property string $language 下单时语言
 * @property string $order_sn 订单编号
 * @property string $pay_sn 支付单号
 * @property int $member_id 买家id
 * @property string $payment_type 支付方式
 * @property int $payment_status 支付状态
 * @property int $payment_time 支付时间
 * @property int $finished_time 订单完成时间
 * @property int $evaluation_status 评价状态 0未评价，1已评价，2已过期未评价
 * @property int $evaluation_again_status 追加评价状态 0未评价，1已评价，2已过期未评价
 * @property int $order_status 订单状态
 * @property int $refund_status 退款状态:0是无退款,1是部分退款,2是全部退款
 * @property int $cancel_status 退款状态:0是无退款,1是部分退款,2是全部退款
 * @property string $refund_remark 退款状态:0是无退款,1是部分退款,2是全部退款
 * @property string $cancel_remark 退款状态:0是无退款,1是部分退款,2是全部退款
 * @property string $express_id 快递类型
 * @property string $express_no 快递单号
 * @property int $delivery_status 发货状态
 * @property int $delivery_time 发货时间
 * @property int $order_from 订单来源 1：web 2：mobile
 * @property int $order_type 订单类型(1普通订单,2预定订单,3门店自提订单)
 * @property int is_tourist 游客订单
 * @property int $api_pay_time 在线支付动作时间,只要向第三方支付平台提交就会更新
 * @property string $trade_no 外部交易订单号
 * @property string $buyer_remark 买家留言
 * @property string $seller_remark 商家备注
 * @property int $ip ip地址
 * @property int $ip_area_id IP所在区域
 * @property int $status 状态 1已审核 0待审核 -1取消
 * @property int $created_at 订单生成时间
 * @property int $updated_at 更新时间
 */
class Order extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id','ip_area_id','payment_type','payment_status', 'payment_time', 'member_id', 'finished_time', 'evaluation_status', 'evaluation_again_status', 'order_status', 'refund_status', 'cancel_status', 'order_from', 'order_type', 'is_tourist', 'is_invoice','api_pay_time', 'status', 'created_at', 'updated_at', 'follower_id','followed_status' ,'followed_time', 'express_id','delivery_time','delivery_status', 'refund_status'], 'integer'],
            [['language'], 'safe'],
            [['order_sn','pay_sn'], 'string', 'max' => 20],
            [['express_no', 'trade_no'], 'string', 'max' => 50],
            [['ip', 'ip_location'], 'safe'],
            [['buyer_remark', 'seller_remark', 'refund_remark', 'cancel_remark'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户ID',
            'language' => '订单语言',
            'order_sn' => '订单编号',
            'pay_sn' => '支付单号',
            'member_id' => '客户',
            'payment_type' => '支付方式',
            'payment_status'=>'支付状态',    
            'payment_time' => '支付时间',
            'finished_time' => '订单完成时间',
            'evaluation_status' => '评价状态',
            'evaluation_again_status' => '追加评价状态',
            'order_status' => '订单状态',
            'refund_status' => '退款状态',
            'refund_remark' => '退款备注',
            'cancel_remark' => '取消备注',
            'cancel_status' => '退款状态',
            'express_id' => '快递方式',
            'express_no' => '快递单号',
            'delivery_status' => '发货状态',
            'delivery_time' => '发货时间',
            'order_from' => '订单来源',
            'order_type' => '订单类型',
            'is_tourist' => '是否游客订单',
            'is_invoice' => '是否开发票',
            'api_pay_time' => 'Api支付时间',
            'trade_no' => '外部单号',
            'buyer_remark' => '客户留言',
            'seller_remark' => '订单备注',
            'follower_id' => '跟进人',
            'followed_status' => '跟进状态',
            'followed_time' => '跟进时间',            
            'ip' => 'IP',
            'ip_area_id' => '归属地区',
            'ip_location' => 'IP位置',
            'status' => '审核状态',
            'created_at' => '下单时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 获取不同状态的数据行数
     * @param $orderStatus
     * @return int
     */
    static public function getCountByOrderStatus($orderStatus=null)
    {
        $where = [];

        if(!is_null($orderStatus))
            $where['order_status'] = $orderStatus;

        return (int)self::find()->where($where)->count('id');
    }

    /**
     * 对应订单付款信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(OrderAccount::class, ['order_id'=>'id']);
    }

    /**
     * 对应订单付款信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(OrderInvoice::class, ['order_id'=>'id']);
    }

    /**
     * 对应订单地址模型
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(OrderAddress::class, ['order_id'=>'id']);
    }

    /**
     * 对应买家模型
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id'=>'member_id']);
    }

    /**
     * 对应跟进人（管理员）模型
     * @return \yii\db\ActiveQuery
     */
    public function getFollower()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id'=>'follower_id']);
    }

    /**
     * 对应订单商品信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getGoods()
    {
        return $this->hasMany(OrderGoods::class,['order_id'=>'id']);
    }
    /**
     * 对应快递模型
     * @return \yii\db\ActiveQuery
     */
    public function getExpress()
    {
        return $this->hasOne(\common\models\common\Express::class, ['id'=>'express_id']);
	}

    /**
     * 对应订单商品信息模型
     * @return \yii\db\ActiveQuery
     */
    public function getPaylogs()
    {
        return $this->hasMany(PayLog::class,['order_sn'=>'order_sn']);
    }

    /**
     * 对应订单购物卡记录
     * @return \yii\db\ActiveQuery
     */
    public function getCards()
    {
        return $this->hasMany(MarketCardDetails::class, ['order_id'=>'id']);
    }


}
