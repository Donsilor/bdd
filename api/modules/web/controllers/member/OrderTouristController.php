<?php

namespace api\modules\web\controllers\member;

use api\controllers\OnAuthController;
use api\modules\web\forms\CartForm;
use common\enums\CurrencyEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\PayEnum;
use common\helpers\ResultHelper;
use common\helpers\Url;
use common\models\forms\PayForm;
use common\models\order\OrderTourist;
use yii\base\Exception;
use common\models\order\Order;
use api\modules\web\forms\OrderCreateForm;
use common\models\order\OrderGoods;
use yii\web\UnprocessableEntityHttpException;

/**
 * 游客订单
 *
 * Class OrderTouristController
 * @package api\modules\v1\controllers
 */
class OrderTouristController extends OnAuthController
{

    public $modelClass = OrderTourist::class;

    protected $authOptional = ['create', 'tax', 'detail'];

    /**
     * 创建订单
     * {@inheritDoc}
     */
    public function actionCreate()
    {
        $orderSn = \Yii::$app->request->post('orderSn');
        $payType = \Yii::$app->request->post('payType', 6);
        $goodsCartList = \Yii::$app->request->post('goodsCartList');
        $invoiceInfo = \Yii::$app->request->post('invoice');

        if(empty($orderSn)) {
            if (empty($goodsCartList)) {
                return ResultHelper::api(422, "goodsCartList不能为空");
            }            
            //验证产品数据
            $cart_list = array();
            foreach ($goodsCartList as $cartGoods) {
                $model = new CartForm();
                $model->attributes = $cartGoods;
                if (!$model->validate()) {
                    // 返回数据验证失败
                    throw new UnprocessableEntityHttpException($this->getError($model));
                }
                $cart_list[] = $model->toArray();
            }
            //$orderId = \Yii::$app->services->orderTourist->createOrder($cart_list, $invoiceInfo);
        }
        $order_from = $this->platform;
        try {            
            $trans = \Yii::$app->db->beginTransaction();
            if(empty($orderSn)) {
                //创建订单
                $orderId = \Yii::$app->services->orderTourist->createOrder($cart_list, $invoiceInfo,$order_from);
            }
            else {
                //按单号支付
                $order = OrderTourist::find()->where(['order_sn'=>$orderSn])->one();
                
                if(!$order) {
                    throw new UnprocessableEntityHttpException('系统忙，请稍后再试~！');
                }

                if($order->status==OrderTouristStatusEnum::ORDER_PAID) {
                    throw new UnprocessableEntityHttpException(\Yii::t('payment', 'ORDER_PAID'));
                }
                
                $orderId = $order->id;
            }

            //调用支付接口
            $payForm = new PayForm();
            $payForm->attributes = \Yii::$app->request->post();
            $payForm->orderId = $orderId;//订单ID
            $payForm->payType = $payType;//支付方式使用paypal
            $payForm->memberId = 0;//支付方式使用paypal
            $payForm->notifyUrl = Url::removeMerchantIdUrl('toFront', ['notify/' . PayEnum::$payTypeAction[$payForm->payType]]);//支付通知URL,paypal不需要,加上只是为了数据的完整性
            $payForm->orderGroup = PayEnum::ORDER_TOURIST;//游客订单

            //验证支付订单数据
            if (!$payForm->validate()) {
                throw new UnprocessableEntityHttpException($this->getError($payForm));
            }
            $config = $payForm->getConfig();
            $config['orderId'] = $orderId;

            $trans->commit();

            return $config;
        } catch (\Exception $e) {
            $trans->rollBack();
            \Yii::$app->services->actionLog->create('游客创建订单',$e->getMessage());
            throw $e;
        }
    }

    /**
     * 订单详情
     * @return array
     */
    public function actionDetail()
    {
        $order_sn = \Yii::$app->request->get('order_sn');
        if (!$order_sn) {
            return ResultHelper::api(422, '参数错误:orderId不能为空');
        }
        $order = OrderTourist::find()->where(['order_sn' => $order_sn])->one();
        if (!$order) {
            return ResultHelper::api(422, '此订单不存在');
        }
//        $currency = $order->account->currency;
//        $exchange_rate = $order->account->exchange_rate;
//
//        $orderGoodsList = OrderGoods::find()->where(['order_id' => $order->id])->all();
//        $orderDetails = array();
//        foreach ($orderGoodsList as $key => $orderGoods) {
//            $orderDetail = [
//                'id' => $orderGoods->id,
//                'orderId' => $order->id,
//                'groupId' => null,
//                'groupType' => null,
//                'goodsId' => $orderGoods->style_id,
//                'goodsDetailId' => $orderGoods->goods_id,
//                'goodsCode' => $orderGoods->goods_sn,
//                'categoryId' => $orderGoods->goods_type,
//                'goodsName' => $orderGoods->lang ? $orderGoods->lang->goods_name : $orderGoods->goods_name,
//                'goodsPrice' => $orderGoods->goods_price,
//                'detailType' => 1,
//                'detailSpecs' => null,
//                'deliveryCount' => 1,
//                'detailCount' => 1,
//                'createTime' => $orderGoods->created_at,
//                'joinCartTime' => $orderGoods->created_at,
//                'goodsImages' => $orderGoods->goods_image,
//                'mainGoodsCode' => null,
//                'ringName' => "",
//                'ringImg' => "",
//                'baseConfig' => null
//            ];
//            if (!empty($orderGoods->goods_attr)) {
//                $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($orderGoods->goods_attr, $orderGoods->goods_type);
//                $baseConfig = [];
//                foreach ($goods_attr as $vo) {
//                    $baseConfig[] = [
//                        'configId' => $vo['id'],
//                        'configAttrId' => 0,
//                        'configVal' => $vo['attr_name'],
//                        'configAttrIVal' => implode('/', $vo['value']),
//                    ];
//                }
//                $orderDetail['baseConfig'] = $baseConfig;
//            }
//            if (!empty($orderGoods->goods_spec)) {
//                $detailSpecs = [];
//                $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($orderGoods->goods_spec);
//                foreach ($goods_spec as $vo) {
//                    $detailSpecs[] = [
//                        'name' => $vo['attr_name'],
//                        'value' => $vo['attr_value'],
//                    ];
//                }
//                $orderDetail['detailSpecs'] = json_encode($detailSpecs);
//            }
//            $orderDetails[] = $orderDetail;
//        }
//
//
//        $address = array(
//            'id' => $order->id,
//            'orderId' => $order->id,
//            'address' => $order->address->address_details,
//            'cityId' => $order->address->city_id,
//            'cityName' => $order->address->city_name,
//            'countryId' => $order->address->country_id,
//            'countryName' => $order->address->country_name,
//            'firstName' => $order->address->firstname,
//            'lastName' => $order->address->lastname,
//            'realName' => $order->address->realname,
//            'provinceId' => $order->address->province_id,
//            'provinceName' => $order->address->province_name,
//            'userAccount' => $order->member->username,
//            'userId' => $order->member_id,
//            'userMail' => $order->address->email,
//            'userTel' => $order->address->mobile,
//            'userTelCode' => $order->address->mobile_code,
//            'zipCode' => $order->address->zip_code,
//        );
        $orderDetails = array();
        foreach ($order->details as $detail) {
            $orderDetail = [
                'goodsId' => $detail['style_id']
            ];
            $orderDetails[] = $orderDetail;
        }

        $order = array(
            'id' => $order->id,
//            'address' => $address,
//            'addressId' => $address['id'],
//            'afterMail' => $order->address->email,
            'coinCode' => $order->currency,
            'allSend' => 1,
            'isInvoice' => 2,
            'orderNo' => $order->order_sn,
            'orderStatus' => $order->status,
            'orderTime' => $order->created_at,
            'payAmount' => $order->pay_amount,
//            'orderType' => $order->order_type,
            'payChannel' => 6,
//            'productCount' => count($orderDetails),
            'preferFee' => $order->discount_amount, //优惠金额
            'productAmount' => $order->goods_amount,
            'logisticsFee' => $order->shipping_fee,
            'orderAmount' => $order->order_amount,
            'otherFee' => $order->other_fee,
            'safeFee' => $order->safe_fee,
            'taxFee' => $order->tax_fee,
//            'userId' => $order->member_id,
            'details' => $orderDetails
        );

        return $order;

    }

    /**
     * 订单金额税费信息
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionTax()
    {
        $goodsCartList = \Yii::$app->request->post('goodsCartList');
        if (empty($goodsCartList)) {
            return ResultHelper::api(422, "goodsCartList不能为空");
        }
		
        //验证产品数据
        $cartList = array();
        foreach ($goodsCartList as $cartGoods) {
            $model = new CartForm();
            $model->attributes = $cartGoods;
            if (!$model->validate()) {
                // 返回数据验证失败
                throw new UnprocessableEntityHttpException($this->getError($model));
            }
            $cartList[] = $model->toArray();
        }
        
        try {
            $taxInfo = \Yii::$app->services->orderTourist->getCartAccountTax($cartList);

            $taxInfo['order_amount_HKD'] = \Yii::$app->services->currency->exchangeAmount($taxInfo['order_amount'], 2, CurrencyEnum::HKD, $this->getCurrency());
        } catch (\Exception $e) {
            \Yii::$app->services->actionLog->create('游客订单金额汇总','Exception:'.$e->getMessage());
            throw $e;
        }

        return $taxInfo;
    }

}