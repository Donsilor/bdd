<?php

namespace api\modules\web\controllers\member;

use api\modules\web\forms\CardForm;
use api\modules\web\forms\OrderCommentForm;
use common\enums\CurrencyEnum;
use common\enums\PayEnum;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
use common\models\market\MarketCouponDetails;
use common\helpers\Url;
use common\models\forms\PayForm;
use common\models\member\Member;
use common\models\order\OrderComment;
use services\market\CardService;
use services\order\OrderLogService;
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
            $orderInfo = [
                'id' =>$order->id,
                'orderNO' =>$order->order_sn,
                'orderStatus'=> $order->order_status,
                'refundStatus'=> $order->refund_status,
                'wireTransferStatus'=> !empty($order->wireTransfer)?$order->wireTransfer->collection_status:null,
                'orderAmount'=> $order->account->order_amount,
                'payAmountHKD'=> \Yii::$app->services->currency->exchangeAmount($order->account->pay_amount, 2, CurrencyEnum::HKD, $order->account->currency),
                'productAmount'=> $order->account->goods_amount,
                'preferFee' => $order->account->discount_amount, //优惠金额
                'payAmount' => $order->account->pay_amount,//支付金额
                'coinCode'=> $order->account->currency,
                'payChannel'=>$order->payment_type,
                'orderTime' =>$order->created_at,
                'details'=>[],
                'paymentType'=>$order->payment_type,
                'isComment' => OrderComment::find()->where(['order_id' => $order->id])->count() > 0,
            ];
           $orderGoodsList = OrderGoods::find()->where(['order_id'=>$order->id])->all();
           foreach ($orderGoodsList as $key =>$orderGoods) {

               $couponInfo = [];

               if($_couponInfo = $orderGoods->coupon) {
                   $couponInfo['type'] = $_couponInfo['type'];
               }

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
                   'goodsPayPrice'=>$orderGoods->goods_pay_price,
                   'couponInfo' => $couponInfo,
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
                   'goodsAttr'=>$orderGoods->cart_goods_attr?@\GuzzleHttp\json_decode($orderGoods->cart_goods_attr, true):[],
                   'baseConfig'=>null,
                   'ring'=>[]
               ];
               $orderDetail['goodsAttr'] = \Yii::$app->services->goodsAttribute->getCartGoodsAttr($orderDetail['goodsAttr']);
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
                   $ring = [];
                   foreach ($goods_spec as $vo){
                       $detailSpecs[] = [
                               'name' =>$vo['attr_name'],
                               'value' =>$vo['attr_value'],
                       ];
                       if(in_array($vo['attr_id'], ['61', '62'])) {
                           $ring[] = \Yii::$app->services->goods->getGoodsInfo($vo['value_id']);
                       }
                   }
                   $orderDetail['detailSpecs'] = json_encode($detailSpecs);
                   $orderDetail['ring'] = $ring;
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
            $model->order_from = $this->platform;
            $invoiceInfo = \Yii::$app->request->post('invoice');
            $coupon_id = (int)\Yii::$app->request->post('coupon_id',0);
            if(!$model->validate()) {
                throw new \Exception($this->getError($model),500);
            }

            $cards = \Yii::$app->request->post('card',[]);
            foreach ($cards as $card) {
                $cardForm = new CardForm();
                $cardForm->setAttributes($card);
                $cardForm->area_attach = $this->getAreaId();

                if(!$cardForm->validate()) {
                    throw new \Exception($this->getError($cardForm),500);
                }
            }

            $result = \Yii::$app->services->order->createOrder($model->carts, $this->member_id, $model->buyer_address_id, $model->toArray(), $invoiceInfo, $coupon_id, $cards);

            //如果订单金额为0
            if($result['pay_amount']==0) {
                //自动 支付
                //调用支付接口
                $payForm = new PayForm();
                $payForm->orderId = $result['order_id'];
                $payForm->coinType = $this->getCurrency();
                $payForm->payType = PayEnum::PAY_TYPE_CARD;
                $payForm->memberId = $this->member_id;

                //验证支付订单数据
                if (!$payForm->validate()) {
                    throw new \Exception($this->getError($payForm),500);
                }

                $pay = $payForm->getConfig();
            }

            $trans->commit();
            //订单发送邮件
            \Yii::$app->services->order->sendOrderNotification($result['order_id']);

            $payAmountHKD = \Yii::$app->services->currency->exchangeAmount($result['pay_amount'], 2, CurrencyEnum::HKD, $this->getCurrency());

            return [
                "coinType" => $result['currency'],
                "orderAmount"=> $result['order_amount'],
                "payAmount"=> $result['pay_amount'],
                "payAmountHKD"=> $payAmountHKD,
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
            return ResultHelper::api(500, '参数错误:orderId不能为空');
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

            $couponInfo = [];

            if($_couponInfo = $orderGoods->coupon) {
                $couponInfo['type'] = $_couponInfo['type'];
            }

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
                'goodsPayPrice'=>$orderGoods->goods_pay_price,
                'couponInfo' => $couponInfo,
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
                'goodsAttr'=>$orderGoods->cart_goods_attr?@\GuzzleHttp\json_decode($orderGoods->cart_goods_attr, true):[],
                'baseConfig'=>null,
                'ring'=>[]
            ];
            $orderDetail['goodsAttr'] = \Yii::$app->services->goodsAttribute->getCartGoodsAttr($orderDetail['goodsAttr']);
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

                $ring = [];
                foreach ($goods_spec as $vo){
                    $detailSpecs[] = [
                        'name' =>$vo['attr_name'],
                        'value' =>$vo['attr_value'],
                    ];
                    if(in_array($vo['attr_id'], ['61', '62'])) {
                        $ring[] = \Yii::$app->services->goods->getGoodsInfo($vo['value_id']);;
                    }
                }

                $orderDetail['detailSpecs'] = json_encode($detailSpecs);

                $orderDetail['ring'] = $ring;
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
            $express['companyName'] = $order->express ? $order->express->lang->express_name : '';
            $express['delivery_time'] = date('Y-m-d',$order->delivery_time);

            $express['logistics'] = \Yii::$app->services->order->getOrderLogisticsInfo($order);
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
            'refundStatus'=> $order->refund_status,
            'wireTransferStatus'=> !empty($order->wireTransfer)?$order->wireTransfer->collection_status:null,
            'orderTime' => $order->created_at,
            'orderType' => $order->order_type,            
            'payChannel' => $order->payment_type,
            'productCount' => count($orderDetails),
            'productAmount' => $order->account->goods_amount,            
            'logisticsFee' => $order->account->shipping_fee,
            'discountAmount' => $order->account->discount_amount, //优惠金额
            'couponAmount' => $order->account->coupon_amount, //优惠金额
            'orderAmount' => $order->account->order_amount,
            'payAmount' => $order->account->pay_amount,//支付金额
            //'payAmount' => bcadd($order->account->order_amount, $cardsUseAmount, 2) - $order->account->discount_amount,
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
            return ResultHelper::api(500, '请参入正确的订单号');
        }
        $order = Order::find()->where(['id'=>$order_id,'member_id'=>$this->member_id])->one();
        if(!$order){
            return ResultHelper::api(422, '此订单不存在');
        }
        if($order->order_status > OrderStatusEnum::ORDER_UNPAID){
            return ResultHelper::api(422, '订单取消失败');
        }
        try {

            $trans = \Yii::$app->db->beginTransaction();
            \Yii::$app->services->order->changeOrderStatusCancel($order_id,"用户取消订单", 'buyer',$this->member_id);
            $trans->commit();

        } catch (\Exception $e) {

            $trans->rollBack();
            \Yii::$app->services->actionLog->create('用户取消订单',"Exception:".$e->getMessage());
            return ResultHelper::api(422, '订单取消失败');
        }
        return 'success';
    }


    /**
     * 确认收货
     * @return mixed|NULL|string
     */
    public function actionConfirmReceipt(){
        
        $order_id = \Yii::$app->request->post('orderId');
        
        if(!$order_id) {
            return ResultHelper::api(500, '请参入正确的订单号');
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
            $order->refresh();
            OrderLogService::finish($order);
            return 'success';
        }else{
            return ResultHelper::api(500, '确认收货失败');
        }

    }    
    /**
     * 订单金额税费信息
     * @return array
     */
    public function actionTax()
    {
        $carts = \Yii::$app->request->post("carts");
        $addressId = \Yii::$app->request->post("addressId");

        $where = [];
        $where['member_id'] = $this->member_id;
        $where['coupon_status'] = 1;

        if(empty($carts)) {
            return ResultHelper::api(422,"carts不能为空");
        }

        $coupon_id = (int)\Yii::$app->request->post("coupon_id");
        if($coupon_id && !MarketCouponDetails::find()->where(array_merge($where, ['coupon_id'=>$coupon_id]))->count()) {
            return ResultHelper::api(422, '无效的优惠券');
        }

        $cards = \Yii::$app->request->post('cards', []);
        if(!empty($cards)) {
            foreach ($cards as $card) {
                $model = new CardForm();
                $model->setAttributes($card);
                $model->area_attach = $this->getAreaId();

                if (!$model->validate()) {
                    return ResultHelper::api(422, $this->getError($model));
                }
            }
        }

        $taxInfo = \Yii::$app->services->order->getOrderAccountTax($carts, $this->member_id, $addressId, $coupon_id, $cards);

        $myCoupons = [];

        $where = [];
        $where['member_id'] = $this->member_id;
        $where['coupon_status'] = 1;
        $where['coupon_id'] = array_keys($taxInfo['coupons']);
        $conpouList = MarketCouponDetails::find()->where($where)->select('coupon_id')->distinct('coupon_id')->asArray()->all();

        foreach ($conpouList as $item) {
            $myCoupons[] = $item['coupon_id'];
        }

        return [
            'logisticsFee' => $taxInfo['shipping_fee'],
            'orderAmount'  => $taxInfo['order_amount'],
            'productAmount' => $taxInfo['goods_amount'],
            'discountAmount' => $taxInfo['discount_amount'],
            'couponAmount' => $taxInfo['coupon_amount'],
            'cardsUseAmount'=> $taxInfo['card_amount'],
            'payAmount'=> $taxInfo['pay_amount'],
            'safeFee'=> $taxInfo['safe_fee'],
            'taxFee'  => $taxInfo['tax_fee'],
            'planDays' => $taxInfo['plan_days'],
            'currency' => $taxInfo['currency'],
            'exchangeRate'=> $taxInfo['exchange_rate'],
            'coupons' => $taxInfo['coupons'],
            'myCoupons' => $myCoupons,
            'cards'=> $taxInfo['cards'],
        ];
    }

    public function actionComment()
    {

        try {

            $trans = \Yii::$app->db->beginTransaction();

            $datas = \Yii::$app->request->post('params');

            $result = [];

            $attr = [
                'member_id' => $this->member_id,
                'platform' => $this->platform,
                'ip' => \Yii::$app->request->userIP
            ];

            list($attr['ip_area_id'],$attr['ip_location']) = \Yii::$app->ipLocation->getLocation($attr['ip']);

            if(empty($datas)) {
                throw new UnprocessableEntityHttpException('提交数据为空');
            }

            foreach ($datas as $data) {
                $model = new OrderCommentForm();
                $model->setAttributes($data);
                $model->setAttributes($attr);

                if(!$model->save()) {
                    throw new UnprocessableEntityHttpException($this->getError($model));
                }

                $result[] = $model;
            }

            $trans->commit();

        } catch (\Exception $e) {

            $trans->rollBack();

            throw $e;
        }

        return $result;
    }

    public function actionCommentList()
    {
        $order_id = \Yii::$app->request->get('order_id', 0);

        $where = [];
        $where['member_id'] = $this->member_id;
        $where['order_id'] = $order_id;

        $query = OrderComment::find()->alias('m')->select(['member.username', 'm.type_id', 'm.style_id', 'm.images', 'm.content', 'm.grade', 'm.remark', 'm.ip', 'm.ip_location', 'm.created_at'])
            ->leftJoin(Member::tableName().' member', 'm.member_id=member.id')
            ->orderBy('m.id desc')
            ->andWhere($where);
//            ->andWhere(['style_id'=>$style_id, 'type_id'=>$type_id]);

        $result = $this->pagination($query, $this->page, $this->pageSize);

        foreach ($result['data'] as &$datum) {
            $datum['username'] = $this->gr_asterisk($datum['username']);
            $datum['ip'] = preg_replace('/(\d+)([.])(\d+)([.])(\d+)([.])(\d+)/', '$1.*.*.$7', $datum['ip']);
        }

        return $result;
    }

    /**
     * 添加星号
     * @param string $str 目标字符串
     * @param int $l 左侧留存长度
     * @param int $r 右侧留存长度
     * @param int $chr_len 星号数目
     * @param string $chr 星号或者其他自定义的字符
     * @return mixed 返回
     */
    public function gr_asterisk($str = '',$l = 2,$r = 4,$chr_len = 3,$chr = '*') {
        if (empty($str)) {
            return false;
        }
        $len_min = $l + $r;
        if (mb_strlen($str, 'utf-8') <= $len_min) {
            return str_repeat($chr, $chr_len);
        }

        $new_str = mb_substr($str, 0, $l, 'utf-8') . str_repeat($chr, $chr_len) . mb_substr($str, mb_strlen($str, 'utf-8') - $r, $r);
        return $new_str;
    }


}