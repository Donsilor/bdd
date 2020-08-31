<?php

namespace common\models\forms;

use common\enums\PayStatusEnum;
use common\models\common\PayLog;
use common\models\order\OrderTourist;
use Omnipay\Common\Message\AbstractResponse;
use services\market\CardService;
use Yii;
use yii\base\Model;
use yii\web\UnprocessableEntityHttpException;
use common\enums\PayEnum;
use common\enums\OrderStatusEnum;
use common\models\order\Order;
use common\enums\CurrencyEnum;
use common\helpers\Url;

/**
 * Class PayForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class PayForm extends Model
{
    public $orderGroup = 'default';
    public $payType;
    public $tradeType = 'default';
    public $data; // json数组
    public $memberId;
    public $returnUrl;
    public $notifyUrl;
    public $orderId;
    public $coinType;
    public $openid;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['orderGroup', 'payType', 'tradeType', 'memberId','coinType'], 'required'],
            [['orderGroup'], 'in', 'range' => array_keys(PayEnum::$orderGroupExplain)],
            [['payType'], 'in', 'range' => array_keys(PayEnum::$payTypeExplain)],
            [['notifyUrl', 'returnUrl','coinType','openid'], 'string'],
            [['tradeType'], 'verifyTradeType'],
            [['orderId'],'integer']
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'orderGroup' => '订单组别',
            'data' => '组别对应数据',
            'payType' => '支付类别',
            'tradeType' => '交易类别',
            'memberId' => '用户id',
            'returnUrl' => '跳转地址',
            'notifyUrl' => '回调地址',
            'coinType' => '货币',
            'openid' => 'openId',
        ];
    }

    /**
     * 校验交易类型
     */
    public function verifyTradeType($attribute)
    {
        switch ($this->payType) {
            case PayEnum::PAY_TYPE :
                break;
            case PayEnum::PAY_TYPE_WECHAT :
                if (!in_array($this->tradeType, ['native', 'app', 'js', 'pos', 'mweb'])) {
                    $this->addError($attribute, '微信交易类型不符');
                }
                break;
            case PayEnum::PAY_TYPE_ALI :
                if (!in_array($this->tradeType, ['pc', 'app', 'f2f', 'wap'])) {
                    $this->addError($attribute, '支付宝交易类型不符');
                }
                break;
            case PayEnum::PAY_TYPE_MINI_PROGRAM :
                break;
            case PayEnum::PAY_TYPE_UNION :
                if (!in_array($this->tradeType, ['app', 'html'])) {
                    $this->addError($attribute, '银联交易类型不符');
                }
                break;
            case PayEnum::PAY_TYPE_PAYPAL :
                if (!in_array($this->tradeType, ['pc', 'wap'])) {
                    $this->addError($attribute, 'PayPal交易类型不符');
                }
//                if($this->coinType == CurrencyEnum::CNY) {
//                    $this->addError($attribute, \Yii::t('payment', 'PAYPAL_NOT_SUPPORT_RMB'));
//                }
                break;
            case PayEnum::PAY_TYPE_GLOBAL_ALIPAY :
                if (!in_array($this->tradeType, ['pc', 'wap'])) {
                    $this->addError($attribute, 'GlobalAlipay交易类型不符');
                }
                break;
            case PayEnum::PAY_TYPE_PAYDOLLAR :
            case PayEnum::PAY_TYPE_PAYDOLLAR_1 :
            case PayEnum::PAY_TYPE_PAYDOLLAR_2 :
            case PayEnum::PAY_TYPE_PAYDOLLAR_3 :
                $name = \Yii::t('payment', PayEnum::getValue($this->payType));
//                if(in_array($this->coinType,[CurrencyEnum::CNY,CurrencyEnum::USD])) {
//                    $this->addError($attribute, sprintf(\Yii::t('payment', 'PAYDOLLAR_NOT_SUPPORT_RMB_AND_USD'), $name));
//                }
                break;
                
        }
    }

    /**
     * @return array
     * @throws UnprocessableEntityHttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function getConfig()
    {
        $baseOrder = $this->getBaseOrderInfo();

        if(!in_array($this->payType, [PayEnum::PAY_TYPE_WECHAT])) {
            $this->notifyUrl = Url::buildUrl($this->notifyUrl,['bdd_out_trade_no'=>$baseOrder['out_trade_no']]);
            $this->returnUrl = Url::buildUrl($this->returnUrl,['bdd_out_trade_no'=>$baseOrder['out_trade_no']]);
        }

        //如果订单金额为零，则直接更新订单状态。否则调用支付接口
        if($this->payType == PayEnum::PAY_TYPE_CARD) {
            $totalFee = $baseOrder['total_fee'];

            if($baseOrder['currency']==CurrencyEnum::TWD) {
                $totalFee = intval($totalFee);
            }

            if($totalFee==0) {
                $model = PayLog::findOne(['out_trade_no'=>$baseOrder['out_trade_no']]);

                $update = [
                    'pay_fee' => $model->total_fee,
                    'pay_status' => PayStatusEnum::PAID,
                    'pay_time' => time(),
                ];

                $model->setAttributes($update);

                if(!$model->save()) {
                    throw new UnprocessableEntityHttpException(\Yii::t('payment', '系统繁忙，请重试'));
                }

                //更新订单状态
                Yii::$app->services->pay->notify($model, null);

                return ['payStatus' => $model->pay_status];// $model->toArray(['pay_status']);
            }
            else {
                throw new UnprocessableEntityHttpException(\Yii::t('payment', '请选择支付方式'));
            }
        }
        if($this->payType == PayEnum::PAY_TYPE_WIRE_TRANSFER) {
            return $baseOrder;
        }
        $action = PayEnum::$payTypeAction[$this->payType];
        return Yii::$app->services->pay->$action($this, $baseOrder);
    }

    /**
     * 获取支付基础信息
     *
     * @param $type
     * @param $data
     * @return array
     */
    protected function getBaseOrderInfo()
    {
        //$data = $this->data;        
        switch ($this->orderGroup) {
            case PayEnum::ORDER_GROUP :

                $order = Order::find()->where(['id'=>$this->orderId,'member_id'=>$this->memberId])->one();
                if(empty($order) || $order->order_status != OrderStatusEnum::ORDER_UNPAID) {
                    if($order && $order->order_status === OrderStatusEnum::ORDER_PAID) {
                        throw new UnprocessableEntityHttpException(\Yii::t('payment', 'ORDER_PAID'));
                    }
                    else {
                        throw new UnprocessableEntityHttpException(\Yii::t('payment', 'ORDER_STATUS_CHANGED'));
                    }
                }

                //验证重复支付
                if(!empty($order->paylogs)) {
                    foreach ($order->paylogs as $paylog) {
                        if($paylog->pay_type==PayEnum::PAY_TYPE_CARD) {
                            continue;
                        }

                        if(in_array($paylog->pay_type, [1, 2])) {
                            continue;
                        }

                        //获取支付类
                        $pay = Yii::$app->services->pay->getPayByType($paylog->pay_type);

                        /**
                         * @var $state AbstractResponse
                         */
                        $state = $pay->verify(['model'=>$paylog, 'isVerify'=>true]);

                        if(in_array($state->getCode(), ['null'])) {
                            throw new UnprocessableEntityHttpException(\Yii::t('payment','ORDER_PAYMENT_VERIFICATION_ERROR'));
                        }
                        elseif(in_array($state->getCode(), ['completed', 'pending', 'payer']) || $paylog->pay_status==PayStatusEnum::PAID) {
                            //此订单正在付款中
                            throw new UnprocessableEntityHttpException(\Yii::t('payment','ORDER_BEING_PAID'));
                        }
                    }
                }

                //获取购物卡使用金额
                $cardUseAmount = CardService::getUseAmount($order->id);

                // TODO 查询订单获取订单信息
                $orderSn = $order->order_sn;
                $totalFee = $order->account->pay_amount;//bcsub($order->account->order_amount - $order->account->discount_amount, $cardUseAmount, 2);
                $currency = $order->account->currency;
                $exchangeRate = $order->account->exchange_rate;
                
                Order::updateAll(['payment_type'=>$this->payType],['id'=>$order->id]);//更改订单支付方式

                $order = [
                    'body' => "商品",
                    'total_fee' => $totalFee,
                    'currency' => $currency,
                    'exchange_rate'=>$exchangeRate
                ];

                Yii::$app->services->job->notifyContacts->createOrderPay($orderSn);
                break;
            case PayEnum::ORDER_TOURIST :
                // 游客订单支付
                $order = OrderTourist::find()->where(['id'=>$this->orderId])->one();

                $this->returnUrl = str_replace('{order_sn}', $order->order_sn, $this->returnUrl);

                $orderSn = $order->order_sn;
                $totalFee = $order->pay_amount;//bcsub($order->order_amount, $order->discount_amount, 2);
                $currency = $order->currency;
                $exchangeRate = $order->exchange_rate;
                $order = [
                    'body' => "商品",
                    'total_fee' => $totalFee,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate
                ];

                Yii::$app->services->job->notifyContacts->createTouristOrderPay($orderSn);
                break;
            case PayEnum::ORDER_GROUP_GOODS :
                // TODO 查询充值生成充值订单
                $orderSn = '';
                $totalFee = '';
                $order = [
                    'body' => '',
                    'total_fee' => $totalFee,
                ];
                break;
        }

        // 也可直接查数据库对应的关联ID，这样子一个订单只生成一个支付操作ID 增加下单率
        // Yii::$app->services->pay->findByOutTradeNo($order->out_trade_no);

//        $toCurrency = CurrencyEnum::HKD;

//        if($order['currency'] == CurrencyEnum::CNY) {
//            $order['total_fee'] = \Yii::$app->services->currency->exchangeAmount($totalFee, 2, $toCurrency, CurrencyEnum::CNY);
//            $order['currency'] = $toCurrency;
//        }

        $order['out_trade_no'] = Yii::$app->services->pay->getOutTradeNo(
            $order['total_fee'],
            $orderSn,
            $this->payType,
            $this->tradeType,
            $this->orderGroup,
            $order['currency'],
            $exchangeRate
        );

        // 必须返回 body、total_fee、out_trade_no
        return $order;
    }
}