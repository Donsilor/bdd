<?php

namespace api\modules\web\controllers\member;

use api\controllers\OnAuthController;
use api\modules\web\forms\CartForm;
use common\enums\PayEnum;
use common\helpers\ResultHelper;
use common\helpers\Url;
use common\models\forms\PayForm;
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

    public $modelClass = Order::class;

    protected $authOptional = ['create'];

    /**
     * 创建订单
     * {@inheritDoc}
     */
    public function actionCreate()
    {
        $goodsCartList = \Yii::$app->request->post('goodsCartList');
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

        try {
            $trans = \Yii::$app->db->beginTransaction();

            //创建订单
            $orderId = \Yii::$app->services->orderTourist->createOrder($cart_list);

            //调用支付接口
            $payForm = new PayForm();
            $payForm->attributes = \Yii::$app->request->post();
            $payForm->orderId = $orderId;//订单ID
            $payForm->payType = 6;//支付方式使用paypal
            $payForm->memberId = 0;//支付方式使用paypal
            $payForm->notifyUrl = Url::removeMerchantIdUrl('toFront', ['notify/' . PayEnum::$payTypeAction[$payForm->payType]]);//支付通知URL,paypal不需要,加上只是为了数据的完整性
            $payForm->orderGroup = PayEnum::ORDER_TOURIST;//游客订单

            //验证支付订单数据
            if (!$payForm->validate()) {
                throw new UnprocessableEntityHttpException($this->getError($payForm));
            }
            $config = $payForm->getConfig();

            $trans->commit();

            return $config;
        } catch (Exception $e) {
            $trans->rollBack();
            throw $e;
        }
    }

    /**
     * 订单详情
     * @return array
     */
    public function actionDetail()
    {
        $order_id = \Yii::$app->request->get('orderId');
        if (!$order_id) {
            return ResultHelper::api(422, '参数错误:orderId不能为空');
        }
        $order = Order::find()->where(['id' => $order_id, 'member_id' => $this->member_id])->one();
        if (!$order) {
            return ResultHelper::api(422, '此订单不存在');
        }
        $currency = $order->account->currency;
        $exchange_rate = $order->account->exchange_rate;

        $orderGoodsList = OrderGoods::find()->where(['order_id' => $order_id])->all();
        $orderDetails = array();
        foreach ($orderGoodsList as $key => $orderGoods) {

            $orderDetail = [
                'id' => $orderGoods->id,
                'orderId' => $order->id,
                'groupId' => null,
                'groupType' => null,
                'goodsId' => $orderGoods->style_id,
                'goodsDetailId' => $orderGoods->goods_id,
                'goodsCode' => $orderGoods->goods_sn,
                'categoryId' => $orderGoods->goods_type,
                'goodsName' => $orderGoods->lang ? $orderGoods->lang->goods_name : $orderGoods->goods_name,
                'goodsPrice' => $orderGoods->goods_price,
                'detailType' => 1,
                'detailSpecs' => null,
                'deliveryCount' => 1,
                'detailCount' => 1,
                'createTime' => $orderGoods->created_at,
                'joinCartTime' => $orderGoods->created_at,
                'goodsImages' => $orderGoods->goods_image,
                'mainGoodsCode' => null,
                'ringName' => "",
                'ringImg' => "",
                'baseConfig' => null
            ];
            if (!empty($orderGoods->goods_attr)) {
                $goods_attr = \Yii::$app->services->goods->formatGoodsAttr($orderGoods->goods_attr, $orderGoods->goods_type);
                $baseConfig = [];
                foreach ($goods_attr as $vo) {
                    $baseConfig[] = [
                        'configId' => $vo['id'],
                        'configAttrId' => 0,
                        'configVal' => $vo['attr_name'],
                        'configAttrIVal' => implode('/', $vo['value']),
                    ];
                }
                $orderDetail['baseConfig'] = $baseConfig;
            }
            if (!empty($orderGoods->goods_spec)) {
                $detailSpecs = [];
                $goods_spec = \Yii::$app->services->goods->formatGoodsSpec($orderGoods->goods_spec);
                foreach ($goods_spec as $vo) {
                    $detailSpecs[] = [
                        'name' => $vo['attr_name'],
                        'value' => $vo['attr_value'],
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
            'zipCode' => $order->address->zip_code,
        );

        $order = array(
            'id' => $order->id,
            'address' => $address,
            'addressId' => $address['id'],
            'afterMail' => $order->address->email,
            'coinCode' => $currency,
            'allSend' => 1,
            'isInvoice' => 2,
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
            'otherFee' => $order->account->other_fee,
            'safeFee' => $order->account->safe_fee,
            'taxFee' => $order->account->tax_fee,
            'userId' => $order->member_id,
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
        $cartIds = \Yii::$app->request->get("cartIds");
        $addressId = \Yii::$app->request->get("addressId");
        if (empty($cartIds)) {
            return ResultHelper::api(422, "cartIds不能为空");
        }
        $taxInfo = \Yii::$app->services->order->getOrderAccountTax($cartIds, $this->member_id, $addressId);
        return [
            'logisticsFee' => $taxInfo['shipping_fee'],
            'orderAmount' => $taxInfo['order_amount'],
            'productAmount' => $taxInfo['goods_amount'],
            'safeFee' => $taxInfo['safe_fee'],
            'taxFee' => $taxInfo['tax_fee'],
            'planDays' => $taxInfo['plan_days'],
            'currency' => $taxInfo['currency'],
            'exchangeRate' => $taxInfo['exchange_rate']
        ];
    }

}