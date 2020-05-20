<?php

namespace api\modules\wap\controllers\member;

use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
use common\models\member\Member;
use services\order\OrderLogService;
use yii\base\Exception;
use common\models\order\Order;
use api\modules\wap\forms\OrderCreateForm;
use common\enums\OrderStatusEnum;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\models\order\OrderGoods;

/**
 * 用户订单
 *
 * Class SiteController
 * @package api\modules\v1\controllers
 */
class OrderController extends UserAuthController
{
    
    public $modelClass = Order::class;
    
    protected $authOptional = [];
    
    
    public function actionIndex()
    {
        $orderStatus = \Yii::$app->request->get('orderStatus',-1);
        
        $query = Order::find()->select(["order.*",'account.*','address.*'])
                    ->leftJoin(OrderAccount::tableName().' account','account.order_id=order.id')
                    ->leftJoin(OrderAddress::tableName().' address','address.order_id=order.id')
                    ->where(['order.member_id'=>$this->member_id]);
        
        if($orderStatus && in_array($orderStatus,OrderStatusEnum::getKeys())) {
            if($orderStatus == 30) {
                $or = ['or'];
                $or[] = ['=','order_status', 20];
                $or[] = ['=','order_status', 30];
                $query->andWhere($or);
            }
            else {
                $query->andWhere(['=','order_status', $orderStatus]);
            }
        }

        $query->orderBy('id DESC');
        
        $result = $this->pagination($query, $this->page, $this->pageSize);
        
        $currencySign = $this->getCurrencySign();
        $order_list = array();
        foreach ($result['data'] as $orderRow) {
            $order_id = $orderRow['id'];
            $order = [
                'id' =>$order_id,
                'orderNO' =>$orderRow['order_sn'],
                'orderStatus'=> $orderRow['order_status'],
                'orderAmount'=> $this->exchangeAmount($orderRow['order_amount']),
                'productAmount'=> $this->exchangeAmount($orderRow['goods_amount']),
                'coinCode'=> $currencySign,
                'payChannel'=>$orderRow['payment_type'],
                'orderTime' =>$orderRow['created_at'],
                'details'=>[],  
           ];
           $orderGoodsList = OrderGoods::find()->where(['order_id'=>$order_id])->asArray()->all();
           foreach ($orderGoodsList as $key =>$goodsRow) {
               $id = $goodsRow['id'];
               $goods_id   = $goodsRow['goods_id'];
               $goods_type = $goodsRow['goods_type'];
               $goods = \Yii::$app->services->goods->getGoodsInfo($goods_id, $goods_type,false);
               $orderDetail = [
                       'id' => $id,
                       'orderId'=>$order_id,
                       'groupId'=>null,
                       'groupType'=>null,
                       'goodsId' => $goods['style_id'],
                       'goodsDetailId' =>$goods_id,
                       'goodsCode' => $goodsRow['goods_sn'],
                       'categoryId'=>$goods_type,
                       'goodsName' => $goodsRow['goods_name'],
                       'goodsPrice'=>$this->exchangeAmount($goodsRow['goods_price']),
                       'detailType'=>1,
                       'detailSpecs'=>null,
                       'deliveryCount'=>1,
                       'detailCount' => 1,
                       'createTime' => $orderRow['created_at'],
                       'joinCartTime'=>$orderRow['created_at'],
                       'goodsImages'=>ImageHelper::goodsThumbs($goodsRow['goods_image'],'mid'),
                       'mainGoodsCode'=>null,
                       'ringName'=>"",
                       'ringImg'=>"",
                       'baseConfig'=>null
               ];
               if(!empty($goods['goods_attr'])) {
                   $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($goodsRow['goods_attr'], $goods_type);
                   $baseConfig = [];
                   foreach ($goods_attr as $vo){
                       $baseConfig[] = [
                               'configId' =>$vo['id'],
                               'configAttrId' =>0,
                               'configVal' =>$vo['attr_name'],
                               'configAttrIVal' =>implode('/',$vo['value']),
                       ];
                   }
                   $orderDetail['baseConfig'] = $baseConfig;
               }
               if(!empty($goods['goods_spec'])) {
                   $detailSpecs = [];
                   $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($goodsRow['goods_spec']);
                   foreach ($goods_spec as $vo){                       
                       $detailSpecs[] = [
                               'name' =>$vo['attr_name'],
                               'value' =>$vo['attr_value'],                               
                       ];
                   }
                   $orderDetail['detailSpecs'] = json_encode($detailSpecs);
               }
               $order['details'][] = $orderDetail;
           }
           $order_list[] = $order;
        }
        $result['data'] = $order_list;        
        return $result;
    }
    /**
     * 创建订单
     * {@inheritDoc}
     */
    public function actionCreate()
    {
        try{
            
            $trans = \Yii::$app->db->beginTransaction();
            
            $model = new OrderCreateForm();
            $model->attributes = \Yii::$app->request->post();
            if(!$model->validate()) {
                return ResultHelper::api(422,$this->getError($model));
            }
            $ressult = \Yii::$app->services->order->createOrder($model->cart_ids, $this->member_id, $model->buyer_address_id,$model->toArray());
            $trans->commit();
            return [
                    "coinType" => $ressult['currency'],
                    "orderAmount"=> $ressult['order_amount'],
                    "orderId" => $ressult['order_id'],
            ];            
        }catch(Exception $e) {
            
            $trans->rollBack();
            throw $e;
        }
    }
    
    public function actionEdit()
    {
        return $this->edit();
    }
    
    public function actionDetail()
    {
        $order_id = \Yii::$app->request->get('orderId');
        if($order_id == null) {
            return ResultHelper::api(422, '请参入正确的订单号');
        }
        $order = Order::find()->where(['order.member_id'=>$this->member_id, 'order.id'=>$order_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }

        $orderGoodsList = OrderGoods::find()->where(['order_id'=>$order_id])->asArray()->all();
        $order_goods_list = array();
        foreach ($orderGoodsList as $key =>$goodsRow) {
            $id = $goodsRow['id'];
            $goods_id   = $goodsRow['goods_id'];
            $goods_type = $goodsRow['goods_type'];
            $goods = \Yii::$app->services->goods->getGoodsInfo($goods_id, $goods_type,false);
            $orderDetail = [
                'id' => $id,
                'orderId'=>$order_id,
                'groupId'=>null,
                'groupType'=>null,
                'goodsId' => $goods['style_id'],
                'goodsDetailId' =>$goods_id,
                'goodsCode' => $goodsRow['goods_sn'],
                'categoryId'=>$goods_type,
                'goodsName' => $goodsRow['goods_name'],
                'goodsPrice'=>$this->exchangeAmount($goodsRow['goods_price']),
                'detailType'=>1,
                'detailSpecs'=>null,
                'deliveryCount'=>1,
                'detailCount' => 1,
                'createTime' => $order->created_at,
                'joinCartTime'=>$order->created_at, // 暂时随便设置
                'goodsImages'=>$goodsRow['goods_image'],
                'mainGoodsCode'=>null,
                'ringName'=>"",
                'ringImg'=>"",
                'baseConfig'=>null
            ];
            if(!empty($goods['goods_attr'])) {
                $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($goodsRow['goods_attr'], $goods_type);
                $baseConfig = [];
                foreach ($goods_attr as $vo){
                    $baseConfig[] = [
                        'configId' =>$vo['id'],
                        'configAttrId' =>0,
                        'configVal' =>$vo['attr_name'],
                        'configAttrIVal' =>implode('/',$vo['value']),
                    ];
                }
                $orderDetail['baseConfig'] = $baseConfig;
            }
            if(!empty($goods['goods_spec'])) {
                $detailSpecs = [];
                $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($goodsRow['goods_spec']);
                foreach ($goods_spec as $vo){
                    $detailSpecs[] = [
                        'name' =>$vo['attr_name'],
                        'value' =>$vo['attr_value'],
                    ];
                }
                $orderDetail['detailSpecs'] = json_encode($detailSpecs);
            }
            $order_goods_list[] = $orderDetail;
        }


        $address = array(
            'address' => $order->address->address_details,
            'cityId' => $order->address->city_id,
            'cityName' => $order->address->city_name,
            'countryId' => $order->address->country_id,
            'countryName' => $order->address->country_name,
            'firstName' => $order->address->firstname,
            'lastName' => $order->address->lastname,
            'id' => $order->id,
            'orderId' => $order->id,
            'provinceId' => $order->address->province_id,
            'provinceName' => $order->address->province_name,
            'userAccount' => $order->address->realname,
            'userId' => $order->address->member_id,
            'userMail' => $order->address->email,
            'userTel' => $order->address->mobile,
            'userTelCode' => $order->address->mobile_code,
            'zipCode'=> $order->address->zip_code,
        );

        $order = array(
            'address' => $address,
            'addressId' => $order->id,
            'afterMail' => $order->address->email,
            'coinCode' => $order->account->currency,
            'allSend' => 1,
            'id' => $order->id,
            'isInvoice'=> 2,
            'logisticsFee' => $this->exchangeAmount($order->account->shipping_fee),
            'orderAmount' => $this->exchangeAmount($order->account->order_amount),
            'orderNo' => $order->order_sn,
            'orderStatus' => $order->order_status,
            'orderTime' => $order->created_at,
            'orderType' => $order->order_type,
            'otherFee' => $this->exchangeAmount($order->account->other_fee),
            'payChannel' => $order->payment_type,
            'preferFee' => $this->exchangeAmount($order->account->discount_amount), //优惠金额
            'productAmount' => $this->exchangeAmount($order->account->goods_amount),
            'productCount' => count($order_goods_list),
            'safeFee' => $this->exchangeAmount($order->account->safe_fee),
            'taxFee' => $this->exchangeAmount($order->account->tax_fee),
            'userId' => $order->member_id,
            'details' => $order_goods_list
        );

        return $order;

    }

    public function actionCancel(){
        $order_id = \Yii::$app->request->post('orderId');
        if($order_id == null) {
            return ResultHelper::api(422, '请参入正确的订单号');
        }
        $order = Order::find()->where(['member_id'=>$this->member_id,'id'=>$order_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }
        if($order->order_status > OrderStatusEnum::ORDER_UNPAID){
            return ResultHelper::api(422, '此订单不是待付款状态，不能取消');
        }
        $res = Order::updateAll(['order_status'=>OrderStatusEnum::ORDER_CANCEL],['id'=>$order_id,'order_status'=>OrderStatusEnum::ORDER_UNPAID]);
        if($res){
            return 'success';
        }else{
            return ResultHelper::api(422, '取消订单失败');
        }

    }


    //确认收货
    public function actionConfirmReceipt(){
        $order_id = \Yii::$app->request->post('orderId');
        if($order_id == null) {
            return ResultHelper::api(422, '请参入正确的订单号');
        }
        $order = Order::find()->where(['member_id'=>$this->member_id,'id'=>$order_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }
        if($order->order_status > OrderStatusEnum::ORDER_SEND){
            return ResultHelper::api(422, '此订单不是已发货状态');
        }
        $res = Order::updateAll(['order_status'=>OrderStatusEnum::ORDER_FINISH],['id'=>$order_id,'order_status'=>OrderStatusEnum::ORDER_SEND]);
        if($res){
            $order->refresh();
            OrderLogService::finish($order);
            return 'success';
        }else{
            return ResultHelper::api(422, '取消订单失败');
        }

    }


    
    /**
     * 订单金额税费信息
     * @return array
     */
    public function actionTax()
    {
        $cartIds = \Yii::$app->request->get("cartIds");
        $addressId = \Yii::$app->request->get("addressId");
        if(empty($cartIds)) {
            return ResultHelper::api(422,"cartIds不能为空");
        }
        $taxInfo = \Yii::$app->services->order->getOrderAccountTax($cartIds, $this->member_id, $addressId); 
        return [
                'logisticsFee' => $this->exchangeAmount($taxInfo['shipping_fee']),
                'orderAmount'  => $this->exchangeAmount($taxInfo['order_amount']),
                'productAmount' => $this->exchangeAmount($taxInfo['goods_amount']),
                'safeFee'=> $this->exchangeAmount($taxInfo['safe_fee']),
                'taxFee'  => $this->exchangeAmount($taxInfo['tax_fee']),
                'planDays' => $taxInfo['plan_days'],
                'currency' => $taxInfo['currency'],
                'exchangeRate'=> $taxInfo['exchange_rate']
        ];
    }
    
}