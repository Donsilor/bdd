<?php

namespace common\models\statistics;

use common\models\order\Order;
use common\models\order\OrderAccount;
use Yii;

/**
 * This is the model class for table "{{%statistics_style_view}}".
 *
 * @property int $style_id 款式ID
 * @property int $type_id 产品线
 * @property string $style_name 款式名称
 * @property string $platform
 * @property string $platform_group
 * @property string $name
 */
class OrderView extends \common\models\base\BaseModel
{

    public $datetime;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%statistics_order_view}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_id', 'status'], 'integer'],
            [['datetime'], 'string', 'max' => 300],
            [['platform_group'], 'string', 'max' => 2],
        ];
    }

    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '款式ID',
            'platform_group' => '站点地区',
            'platform_id' => '客户端',
            'status' => '状态'
        ];
    }

    private function getOrderRelationBase(int $start_time, int $end_time)
    {
        $end_time += 86400;

        $where = ['and'];
        $where[] = ['>=', 'order.created_at', $start_time];
        $where[] = ['<', 'order.created_at', $end_time];
        $where[] = ['=', 'is_test', 0];
        $where[] = ['=', 'order_from', $this->platform_id];

        //未付款
        if ($this->status == 1) {
            $where[] = [
                'or',
                ['=', 'payment_status', 0],
                ['=', 'cancel_status', 1],
            ];
        }

        //已销售
        if ($this->status == 2) {
            $where[] = ['=', 'cancel_status', 0];
            $where[] = ['=', 'refund_status', 0];

            $where[] = ['>=', 'order_status', '20'];
        }

        //已退款
        if ($this->status == 3) {
            $where[] = ['=', 'refund_status', 1];
        }

        return Order::find()->where($where);
    }

    private function getOrderRelation($searchModel)
    {
        list($start_time, $end_time) = explode('/', $searchModel->datetime);
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        return $this->getOrderRelationBase($start_time, $end_time);
    }

    public function getOrderCount($searchModel)
    {
        return $this->getOrderRelation($searchModel)->count('id');
    }

    public function getOrderMoneySum($searchModel)
    {
        return round($this->getOrderRelation($searchModel)
            ->joinWith('account')
            ->sum('order_account.pay_amount/order_account.exchange_rate'), 2);
    }

    public function getOrderProductCount($searchModel)
    {
        return $this->getOrderRelation($searchModel)
            ->joinWith('goods')
            ->count('order_goods.id');
    }

    public function getOrderProductTypeGroupDataBase(int $start_time, int $end_time)
    {
        static $data;

        if(!isset($data[$this->id])) {
            $data[$this->id] = $this->getOrderRelationBase($start_time, $end_time)
                ->joinWith('goods')
                ->groupBy('order_goods.goods_type')
                ->select(['order_goods.goods_type as id', 'order_goods.goods_type as goods_type', 'count(order_goods.id) as count', 'sum(order_goods.goods_pay_price/order_goods.exchange_rate) as sum'])
                ->asArray()
                ->all();
        }

        return !empty($data[$this->id]) ? $data[$this->id] : [];
    }

    public function getOrderProductTypeGroupData($searchModel)
    {
        list($start_time, $end_time) = explode('/', $searchModel->datetime);
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        return $this->getOrderProductTypeGroupDataBase($start_time, $end_time);
    }

//    public function getOrderProductTypeMoneySum($searchModel)
//    {
//        return $this->getOrderRelation($searchModel)
//            ->joinWith('goods')
//            ->groupBy('order_goods.goods_type');
//    }

//    public static function primaryKey()
//    {
//        return ['style_id','platform_group'];
//    }

//    public function getOg()
//    {
//        $order = <<<DOM
//(SELECT `og`.`style_id`,COUNT(`og`.`style_id`) AS count,`o`.`order_from`
//FROM `order` `o`
//RIGHT JOIN `order_goods` AS `og` ON  `o`.`id`=`og`.`order_id`
//WHERE 1 GROUP BY `og`.`style_id`,`o`.`order_from`) AS og
//DOM;
//        return $this->hasOne($order, ['order_id'=>'id']);
//    }
}
