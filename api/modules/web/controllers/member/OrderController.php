<?php

namespace api\modules\web\controllers\member;

use api\modules\web\forms\CardForm;
use common\enums\PayEnum;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
use common\helpers\Url;
use common\models\forms\PayForm;
use common\models\member\Member;
use services\market\CardService;
use yii\base\Exception;
use common\models\order\Order;
use api\modules\web\forms\OrderCreateForm;
use common\enums\OrderStatusEnum;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\models\order\OrderGoods;
use services\order\OrderService;
use yii\web\UnprocessableEntityHttpException;

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
        
        $query = Order::find()->where(['member_id'=>$this->member_id]);        
        if($orderStatus && in_array($orderStatus,OrderStatusEnum::getKeys())) {
            if($orderStatus == OrderStatusEnum::ORDER_CONFIRM){
                $orderStatus = [OrderStatusEnum::ORDER_CONFIRM,OrderStatusEnum::ORDER_PAID];
            }            
            $query->andWhere(['order_status'=>$orderStatus]);            
        }
        $query->orderBy('id DESC');
        
        $result = $this->pagination($query, $this->page, $this->pageSize,false);

        $orderList = array();
        foreach ($result['data'] as $order) {
            $exchange_rate = $order->account->exchange_rate;
            $currency = $order->account->currency;
            $orderInfo = [
                'id' =>$order->id,
                'orderNO' =>$order->order_sn,
                'orderStatus'=> $order->order_status,
                'orderAmount'=> $order->account->order_amount,
                'productAmount'=> $order->account->goods_amount,
                'coinCode'=> $currency,
                'payChannel'=>$order->payment_type,
                'orderTime' =>$order->created_at,
                'details'=>[],
                'paymentType'=>$order->payment_type,
            ];
           $orderGoodsList = OrderGoods::find()->where(['order_id'=>$order->id])->all();
           foreach ($orderGoodsList as $key =>$orderGoods) {
               $orderDetail = [
                       'id' => $orderGoods->id,
                       'orderId'=>$order->id,
                       'groupId'=>null,
                       'groupType'=>null,
                       'goodsId' => $orderGoods->style_id,
                       'goodsDetailId' =>$orderGoods->goods_id,
                       'goodsCode' => $orderGoods->goods_sn,
                       'categoryId'=>$orderGoods->goods_type,
                       'goodsName' => $orderGoods->lang ? $orderGoods->lang->goods_name: $orderGoods->goods_name,
                       'goodsPrice'=>$orderGoods->goods_price,
                       'detailType'=>1,
                       'detailSpecs'=>null,
                       'deliveryCount'=>1,
                       'detailCount' => 1,
                       'createTime' => $orderGoods->created_at,
                       'joinCartTime'=>$orderGoods->created_at,
                       'goodsImages'=>ImageHelper::goodsThumbs($orderGoods->goods_image,'small'),
                       'mainGoodsCode'=>null,
                       'ringName'=>"",
                       'ringImg'=>"",
                       'baseConfig'=>null
               ];
               if(!empty($orderGoods->goods_attr)) {
                   $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($orderGoods->goods_attr, $orderGoods->goods_type);
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
               if(!empty($orderGoods->goods_spec)) {
                   $detailSpecs = [];
                   $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($orderGoods->goods_spec);
                   foreach ($goods_spec as $vo){                       
                       $detailSpecs[] = [
                               'name' =>$vo['attr_name'],
                               'value' =>$vo['attr_value'],                               
                       ];
                   }
                   $orderDetail['detailSpecs'] = json_encode($detailSpecs);
               }
               $orderInfo['details'][] = $orderDetail;
           }
           $orderList[] = $orderInfo;
        }
        $result['data'] = $orderList;        
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
            $invoiceInfo = \Yii::$app->request->post('invoice');
            if(!$model->validate()) {
                return ResultHelper::api(422,$this->getError($model));
            }

            $cards = \Yii::$app->request->post('card',[]);
            foreach ($cards as $card) {
                $cardForm = new CardForm();
                $cardForm->setAttributes($card);

                if(!$cardForm->validate()) {
                    return ResultHelper::api(422, $this->getError($cardForm));
                }
            }

            $result = \Yii::$app->services->order->createOrder($model->cart_ids, $this->member_id, $model->buyer_address_id,$model->toArray(),$invoiceInfo);

            //购物券消费
            CardService::consume($result['order_id'], $cards);

            //购物券使用金额
            $cardUseAmount = CardService::getUseAmount($result['order_id']);

            //如果订单金额为0
            if($result['order_amount']-$cardUseAmount==0) {
                //自动 支付
                //调用支付接口
                $payForm = new PayForm();
                $payForm->orderId = $result['order_id'];
                $payForm->coinType = $this->getCurrency();
                $payForm->payType = PayEnum::PAY_TYPE_CARD;
                $payForm->memberId = $this->member_id;

                //验证支付订单数据
                if (!$payForm->validate()) {
                    throw new UnprocessableEntityHttpException($this->getError($payForm));
                }

                $pay = $payForm->getConfig();
            }

            $trans->commit();
            //订单发送邮件
            \Yii::$app->services->order->sendOrderNotification($result['order_id']);
            return [
                "coinType" => $result['currency'],
                "orderAmount"=> $result['order_amount']-$cardUseAmount,
                "orderId" => $result['order_id'],
                "payStatus" => $pay['payStatus']??0,
            ];            
        }catch(Exception $e) {            
            $trans->rollBack();
            //记录日志
            \Yii::$app->services->actionLog->create('用户创建订单',$e->getMessage());
            throw $e;
        }
    }
    
    public function actionEdit()
    {
        return $this->edit();
    }
    /**
     * 订单详情
     * @return array
     */
    public function actionDetail()
    {
        $order_id = \Yii::$app->request->get('orderId');
        if(!$order_id) {
            return ResultHelper::api(422, '参数错误:orderId不能为空');
        }      
        $order = Order::find()->where(['id'=>$order_id,'member_id'=>$this->member_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }    
        $currency = $order->account->currency;
        $exchange_rate = $order->account->exchange_rate;
        
        $orderGoodsList = OrderGoods::find()->where(['order_id'=>$order_id])->all();
        $orderDetails = array();
        foreach ($orderGoodsList as $key =>$orderGoods) {

            $orderDetail = [
                'id' => $orderGoods->id,
                'orderId'=>$order->id,
                'groupId'=>null,
                'groupType'=>null,
                'goodsId' => $orderGoods->style_id,
                'goodsDetailId' =>$orderGoods->goods_id,
                'goodsCode' => $orderGoods->goods_sn,
                'categoryId'=>$orderGoods->goods_type,
                'goodsName' => $orderGoods->lang ? $orderGoods->lang->goods_name : $orderGoods->goods_name,
                'goodsPrice'=>$orderGoods->goods_price,
                'detailType'=>1,
                'detailSpecs'=>null,
                'deliveryCount'=>1,
                'detailCount' => 1,
                'createTime' => $orderGoods->created_at,
                'joinCartTime'=>$orderGoods->created_at,
                'goodsImages'=>ImageHelper::goodsThumbs($orderGoods->goods_image,'small'),
                'mainGoodsCode'=>null,
                'ringName'=>"",
                'ringImg'=>"",
                'baseConfig'=>null
            ];
            if(!empty($orderGoods->goods_attr)) {
                $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($orderGoods->goods_attr, $orderGoods->goods_type);
                $baseConfig = [];
                foreach ($goods_attr as $vo) {
                    $baseConfig[] = [
                        'configId' =>$vo['id'],
                        'configAttrId' =>0,
                        'configVal' =>$vo['attr_name'],
                        'configAttrIVal' =>implode('/',$vo['value']),
                    ];
                }
                $orderDetail['baseConfig'] = $baseConfig;
            }
            if(!empty($orderGoods->goods_spec)) {
                $detailSpecs = [];
                $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($orderGoods->goods_spec);
                foreach ($goods_spec as $vo){
                    $detailSpecs[] = [
                        'name' =>$vo['attr_name'],
                        'value' =>$vo['attr_value'],
                    ];
                }
                $orderDetail['detailSpecs'] = json_encode($detailSpecs);
            }
            $orderDetails[] = $orderDetail;
        }


        $address = array(
                'id' => $order->id,
                'orderId' => $order->id,
                'address' => $order->address->address_details,
                'cityId' => $order->address->city_id,
                'cityName' => $order->address->city_name,
                'countryId' => $order->address->country_id,
                'countryName' => $order->address->country_name,
                'firstName' => $order->address->firstname,
                'lastName' => $order->address->lastname,
                'realName' => $order->address->realname,
                'provinceId' => $order->address->province_id,
                'provinceName' => $order->address->province_name,
                'userAccount' => $order->member->username,
                'userId' => $order->member_id,
                'userMail' => $order->address->email,
                'userTel' => $order->address->mobile,
                'userTelCode' => $order->address->mobile_code,
                'zipCode'=> $order->address->zip_code,
        );

        if($order->invoice) {
            $invoiceInfo = array(
                'invoiceType' => $order->invoice->invoice_type,
                'invoiceTitle' => $order->invoice->invoice_title,
                'taxNumber' => $order->invoice->tax_number,
                'isElectronic' => $order->invoice->is_electronic,
                'email' => $order->invoice->email,
            );
        }else{
            $invoiceInfo = [];
        }

        $cardsUseAmount = 0;
        $cards = [];
        if($order->cards) {
            foreach ($order->cards as $card) {
                if($card->type!=2) {
                    continue;
                }
                $cards[] = [
                    'sn' => $card->card['sn'],
                    'useAmount' => $card['use_amount'],
                    'status' => $card['status'],
                ];
                $cardsUseAmount = bcadd($cardsUseAmount, $card['use_amount'], 2);
            }
        }

        //快递信息
        if($order->order_status >= OrderStatusEnum::ORDER_SEND){
            $express = array();
            $express['expressNo'] = $order->express_no;
            $express['companyName'] = $order->express->lang->express_name;
            $express['delivery_time'] = date('Y-m-d',$order->delivery_time);

        }

        $order = array(
            'id' => $order->id,
            'address' => $address,
            'addressId' => $address['id'],
            'afterMail' => $order->address->email,
            'coinCode' => $currency,
            'allSend' => 1,            
            'isInvoice'=> 2,            
            'orderNo' => $order->order_sn,
            'orderStatus' => $order->order_status,
            'orderTime' => $order->created_at,
            'orderType' => $order->order_type,            
            'payChannel' => $order->payment_type,
            'productCount' => count($orderDetails),
            'preferFee' => $order->account->discount_amount, //优惠金额
            'productAmount' => $order->account->goods_amount,
            'logisticsFee' => $order->account->shipping_fee,
            'orderAmount' => $order->account->order_amount,
            'payAmount' => bcadd($order->account->order_amount, $cardsUseAmount, 2) - $order->account->discount_amount,
            'otherFee' => $order->account->other_fee,
            'safeFee' => $order->account->safe_fee,
            'taxFee' => $order->account->tax_fee,
            'userId' => $order->member_id,
            'details' => $orderDetails,
            'invoice' => $invoiceInfo,
            'cards' => $cards,
            'express'=>empty($express)? null : $express
        );


        return $order;

    }
    /**
     * 订单取消
     * @return mixed|NULL|string
     */
    public function actionCancel(){
        $order_id = \Yii::$app->request->post('orderId');
        if(!$order_id) {
            return ResultHelper::api(422, '请参入正确的订单号');
        }
        $order = Order::find()->where(['id'=>$order_id,'member_id'=>$this->member_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }
        if($order->order_status > OrderStatusEnum::ORDER_UNPAID){
            return ResultHelper::api(422, '此订单不是待付款状态，不能取消');
        }
        try {

            $trans = \Yii::$app->db->beginTransaction();
            \Yii::$app->services->order->changeOrderStatusCancel($order_id,"用户取消订单", 'buyer',$this->member_id);
            $trans->commit();

        } catch (\Exception $exception) {

            $trans->rollBack();

            return ResultHelper::api(422, '取消订单失败');
        }
//        $res = Order::updateAll(['order_status'=>OrderStatusEnum::ORDER_CANCEL],['id'=>$order_id,'order_status'=>OrderStatusEnum::ORDER_UNPAID]);
//        if($res){
            return 'success';
//        }else{
//            return ResultHelper::api(422, '取消订单失败');
//        }

    }


    /**
     * 确认收货
     * @return mixed|NULL|string
     */
    public function actionConfirmReceipt(){
        $order_id = \Yii::$app->request->post('orderId');
        if(!$order_id) {
            return ResultHelper::api(422, '请参入正确的订单号');
        }
        $order = Order::find()->where(['id'=>$order_id,'member_id'=>$this->member_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }
        if($order->order_status > OrderStatusEnum::ORDER_SEND){
            return ResultHelper::api(422, '此订单不是已发货状态');
        }
        $res = Order::updateAll(['order_status'=>OrderStatusEnum::ORDER_FINISH],['id'=>$order_id,'order_status'=>OrderStatusEnum::ORDER_SEND]);
        if($res){
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
        $cartIds = \Yii::$app->request->post("cartIds");
        $addressId = \Yii::$app->request->post("addressId");
        if(empty($cartIds)) {
            return ResultHelper::api(422,"cartIds不能为空");
        }
        $cards = \Yii::$app->request->post('cards', []);
        if(!empty($cards)) {
            foreach ($cards as $card) {
                $model = new CardForm();
                $model->setAttributes($card);

                if(!$model->validate()) {
                    return ResultHelper::api(422, $this->getError($model));
                }
            }
        }

        $taxInfo = \Yii::$app->services->order->getOrderAccountTax($cartIds, $this->member_id, $addressId, $cards);
        return [
            'logisticsFee' => $taxInfo['shipping_fee'],
            'orderAmount'  => $taxInfo['order_amount'],
            'productAmount' => $taxInfo['goods_amount'],
            'safeFee'=> $taxInfo['safe_fee'],
            'taxFee'  => $taxInfo['tax_fee'],
            'planDays' => $taxInfo['plan_days'],
            'currency' => $taxInfo['currency'],
            'exchangeRate'=> $taxInfo['exchange_rate'],
            'cards'=> $taxInfo['cards'],
            'cardsUseAmount'=> $taxInfo['cards_use_amount'],
            'payAmount'=> bcsub($taxInfo['order_amount'] ,$taxInfo['cards_use_amount'], 2) - $taxInfo['discount_amount']
        ];
    }
    
}