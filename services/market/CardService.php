<?php

namespace services\market;

use common\components\Service;
use common\enums\CurrencyEnum;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\market\MarketCardGoodsType;
use common\models\order\Order;
use services\goods\TypeService;
use yii\db\Exception;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class CardService
 * @package services\market
 */
class CardService extends Service
{
    //调整金额

    //购物卡消费成功
    static public function setSuccess($orderId)
    {
        //判断订单状态
        if(!($orderInfo = Order::findone($orderId)) || $orderInfo->order_status!=20) {
            return null;
        }

        $where = [];
        $where['order_id'] = $orderId;
        $where['status'] = 2;
        $cards = MarketCardDetails::find()->where($where)->all();

        if(empty($cards)) {
            return null;
        }

        foreach ($cards as $card) {
            if(empty($card->card->first_use_time)) {
                $card->card->first_use_time = time();
                $card->card->save();
            }

            $card->status=1;
            $card->save();
        }
    }

    /**
     * 订单取消，解除冻结
     * @param $orderId
     * @return bool|null
     * @throws \Exception
     */
    static public function deFrozen($orderId)
    {
        //判断订单状态
        if(!($orderInfo = Order::findone($orderId)) || $orderInfo->order_status!=0) {
            return null;
        }

        $where = [];
        $where['order_id'] = $orderId;
        $where['status'] = 2;
        $cards = MarketCardDetails::find()->where($where)->all();

        if(empty($cards)) {
            return null;
        }

        $newCard = [];
        $newCard['ip'] = \Yii::$app->request->userIP??'127.0.0.1';
        list($newCard['ip_area_id'], $newCard['ip_location']) = \Yii::$app->ipLocation->getLocation($newCard['ip']);

        try {
            foreach ($cards as $card) {

                //添加解冻费用记录
                $newCard['card_id'] = $card['card_id'];
                $newCard['order_id'] = $card['order_id'];
                $newCard['currency'] = $card['currency'];
                $newCard['use_amount'] = abs($card['use_amount']);
                $newCard['use_amount_cny'] = abs($card['use_amount_cny']);
                $newCard['user_id'] = $card['user_id'];
                $newCard['member_id'] = $card['member_id'];
                $newCard['type'] = 3;
                $newCard['status'] = 1;

                //更新状态为取消
                if(!MarketCardDetails::updateAll(['status'=>0], ['status'=>2, 'id'=>$card->id])) {
                    throw new UnprocessableEntityHttpException("购物卡使用记录取消失败");
                }

                //购物卡返回金额
                $data = [];
                $data['balance'] = new Expression("balance+{$newCard['use_amount_cny']}");

                $where = ['and'];
                $where[] = ['id'=>$card['card_id']];
                if(!MarketCard::updateAll($data, $where)) {
                    throw new UnprocessableEntityHttpException("更新购物卡余额失败");
                }

                $marketCard = MarketCard::findOne($card['card_id']);

                $newCard['balance'] = $marketCard['balance'];

                $newCardObj = new MarketCardDetails();
                $newCardObj->setAttributes($newCard);
                if(!$newCardObj->save()) {
                    throw new UnprocessableEntityHttpException(\Yii::$app->debris->analyErr($newCardObj->getFirstErrors()));
                }
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
        return null;
    }

    //购物卡消费
    static public function consume($orderId, $cards)
    {
        if(empty($cards)) {
            return;
        }

        if(!($order = Order::findOne($orderId))) {
            return;
        }

        //已使用购物券的金额
        $cardsUseAmount = self::getUseAmount($order->id);

        //订单待支付金额=订单总额-优惠金额
        $amount = $order->account->order_amount - $order->account->discount_amount - $cardsUseAmount;

        //计算订单产品线金额
        $goodsTypeAmounts = [];

        //优惠券使用金额
        $couponUseAmount = 0;

        //订单折扣后金额
        $goodsPayPrice = 0;
        foreach ($order->goods as $goods) {
            if(!isset($goodsTypeAmounts[$goods['goods_type']])) {
                $goodsTypeAmounts[$goods['goods_type']] = $goods['goods_pay_price'];
            }
            else {
                $goodsTypeAmounts[$goods['goods_type']] = bcadd($goodsTypeAmounts[$goods['goods_type']], $goods['goods_pay_price'], 2);
            }
            $goodsPayPrice = bcadd($goodsPayPrice, $goods['goods_pay_price'], 2);

        }

        foreach ($cards as $card) {
            //状态，是否过期，是否有余额
            $where = ['and'];
            $where[] = [
                'sn' => $card['sn'],
                'status' => 1,
            ];
            $where[] = ['<=', 'start_time', time()];
            $where[] = ['>', 'end_time', time()];

            if(!($cardInfo = MarketCard::find()->where($where)->one()) || $cardInfo->balance==0) {
                continue;
            }

            $balance = \Yii::$app->services->currency->exchangeAmount($cardInfo->balance);

            $cardUseAmount = 0;

            foreach ($goodsTypeAmounts as $goodsType => &$goodsTypeAmount) {
                if(!empty($cardInfo->goods_type_attach) && in_array($goodsType, $cardInfo->goods_type_attach) && $goodsTypeAmount > 0) {
                    if($goodsTypeAmount >= $balance) {
                        //购物卡余额不足时
                        $cardUseAmount = bcadd($balance, $cardUseAmount, 2);
                        $goodsTypeAmount = bcsub($goodsTypeAmount, $balance, 2);
                        $balance = 0;
                    }
                    else {
                        $cardUseAmount = bcadd($cardUseAmount, $goodsTypeAmount, 2);
                        $balance = bcsub($balance, $goodsTypeAmount, 2);
                        $goodsTypeAmount = 0;
                    }
                }
            }

            if($cardUseAmount==0) {
                continue;
            }

            //转人民币,如果余额为0，直接使用人民币余额，避免小数出错
            if($balance==0) {
                $cardUseAmountCny = $cardInfo->balance;
            }
            else {
                $cardUseAmountCny = \Yii::$app->services->currency->exchangeAmount($cardUseAmount,2, CurrencyEnum::CNY, \Yii::$app->params['currency']);
            }

            //扣除购物卡余额
            $data = [];
            $data['balance'] = new Expression("balance-{$cardUseAmountCny}");

            $where = ['and'];
            $where[] = ['id'=>$cardInfo->id, 'status'=>1];
            $where[] = ['>=', 'balance', $cardUseAmountCny];
            if(!MarketCard::updateAll($data, $where)) {
                throw new UnprocessableEntityHttpException("购物卡金额不正确");
            }

            //冻结购物卡消费
            $cardDetail = new MarketCardDetails();
            $cardDetail->setAttributes([
                'card_id' => $cardInfo->id,
                'order_id' => $order->id,
                'balance' => bcsub($cardInfo->balance ,$cardUseAmountCny, 2),//余额
                'currency' => \Yii::$app->params['currency'],
                'use_amount' => -$cardUseAmount,
                'use_amount_cny' => -$cardUseAmountCny,
                'ip' => $order->ip,
                'member_id' => $order->member_id,
                'type' => 2,
                'status' => 2,
            ]);
            if(!$cardDetail->save()) {
                throw new UnprocessableEntityHttpException(\Yii::$app->debris->analyErr($cardDetail->getFirstErrors()));
            }

        }
    }

    //获取订单所购物卡金额
    static public function getUseAmount($orderId)
    {
        return abs(MarketCardDetails::find()->where(['order_id'=>$orderId,'status'=>[1,2]])->sum('use_amount'));
    }

    //生成卡密码
    static public function generatePw()
    {
        $rand = '111110000';

        $randL = strlen($rand);

        $enStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $intStr = '0123456789';
        $pw = '';

        for($i = 0; $i < $randL; $i++) {

            $randLength = strlen($rand)-1;
            $randStr = $rand{mt_rand(0, $randLength)};
            $rand = substr($rand, $randStr, $randLength);

            if($randStr) {
                $pw .= $intStr{mt_rand(0, 9)};
            }
            else {
                $pw .= $enStr{mt_rand(0, 25)};
            }
        }

        return $pw;
    }

    //生成卡号
    static public function generateSn()
    {
        return mt_rand(1, 9).str_pad(mt_rand(1, 9999999999),10,'0',STR_PAD_LEFT);
    }

    /**
     * 批量生成购物卡
     * @param array $card 基本数据
     * @param int $count
     * @throws \Exception
     */
    public function generateCards($card, $count=1)
    {
        $card['user_id'] = \Yii::$app->getUser()->id;

        $card['ip'] = \Yii::$app->request->userIP;
        list($card['ip_area_id'], $card['ip_location']) = \Yii::$app->ipLocation->getLocation($card['ip']);

        $goodsType = [
            'batch' => $card['batch']
        ];

        //保存产品线
        foreach ($card['goods_type_attach'] as $goods_type) {
            $goodsType['goods_type'] = $goods_type;
            $newGoodsType = new MarketCardGoodsType();
            $newGoodsType->setAttributes($goodsType);

            if(!$newGoodsType->save()) {
                throw new UnprocessableEntityHttpException($this->getError($newGoodsType));
            }
        }

        for ($i = 0; $i < $count; $i++) {
            if(!$this->generateCard($card)) {
                $i--;
            }
        }

        if(MarketCard::find()->where(['batch'=>$card['batch']])->count('id') > $count) {
            throw new UnprocessableEntityHttpException(sprintf("[%s]批次重复生成" , $card['batch']));
        }
    }

    public function importCards($cards)
    {
        $count = 0;
        $batchs = [];

        $_card['user_id'] = 1;//\Yii::$app->getUser()->id;

        $_card['ip'] = '127.0.0.1';//\Yii::$app->request->userIP;
        list($_card['ip_area_id'], $_card['ip_location']) = \Yii::$app->ipLocation->getLocation($_card['ip']);

        foreach ($cards as &$card) {
            if(!isset($batchs[$card['batch']])) {
                //创建批次地区记录
                $goodsTypeSAttach = [];
                $goodsTypes = explode('|', $card['goods_types']);
                $typeList = TypeService::getTypeList();

                $goodsType = [
                    'batch' => $card['batch']
                ];

                foreach ($typeList as $key => $value) {
                    if(in_array($value, $goodsTypes)) {
                        $goodsTypeSAttach[] = $key;

                        $goodsType['goods_type'] = $key;

                        if(MarketCardGoodsType::findOne($goodsType)) {
                            continue;
                        }

                        $newGoodsType = new MarketCardGoodsType();
                        $newGoodsType->setAttributes($goodsType);

                        if(!$newGoodsType->save()) {
                            throw new UnprocessableEntityHttpException($this->getError($newGoodsType));
                        }
                    }
                }

                $batchs[$card['batch']] = $goodsTypeSAttach;
            }

            $card['goods_type_attach'] = $batchs[$card['batch']];
            $card = array_merge($card, $_card);

            if($this->generateCard($card)) {
                $count++;
            }
        }

        if(count($cards)!=$count) {
            throw new UnprocessableEntityHttpException('有部份数据存在重新插入');
        }

        return $count;
    }

    /**
     * 生成购物卡
     * @param array $card 基本数据
     * @return bool
     * @throws \Exception
     */
    private function generateCard($card)
    {
        try {
            $pw = $card['password']??'';//self::generatePw();
            $card['sn'] = $card['sn']??self::generateSn();
            $card['balance'] = $card['amount'];

            $newCard = new MarketCard();
            $newCard->setAttributes($card);

            //设置密码
            if(!empty($card['password'])) {
                $newCard->setPassword($card['password']);
            }

            if(!$newCard->save()) {
                throw new UnprocessableEntityHttpException($this->getError($newCard));
            }

            $cardDetail = [];
            $cardDetail['card_id'] = $newCard->id;
            $cardDetail['balance'] = $card['balance'];
            $cardDetail['currency'] = CurrencyEnum::CNY;
            $cardDetail['use_amount'] = $card['balance'];
            $cardDetail['use_amount_cny'] = $card['balance'];
            $cardDetail['ip'] = $card['ip'];
            $cardDetail['ip_area_id'] = $card['ip_area_id'];
            $cardDetail['ip_location'] = $card['ip_location'];
            $cardDetail['user_id'] = $card['user_id'];
            $cardDetail['type'] = 1;
            $cardDetail['status'] = 1;

            $newCardDetail = new MarketCardDetails();
            $newCardDetail->setAttributes($cardDetail);
            if(!$newCardDetail->save()) {
                throw new UnprocessableEntityHttpException($this->getError($newCardDetail));
            }

            $result = true;

        } catch (\Exception $exception) {
            if($exception instanceof UnprocessableEntityHttpException) {
                throw $exception;
            }

            $result = false;
        }
        return $result;
    }

    //导出|导入数据
}