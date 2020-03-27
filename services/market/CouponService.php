<?php

namespace services\market;

use common\components\Service;
use common\enums\AreaEnum;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\PayStatusEnum;
use common\models\goods\Goods;
use common\models\market\MarketCoupon;
use common\models\market\MarketCouponArea;
use common\models\market\MarketCouponGoods;
use common\models\market\MarketCouponGoodsType;
use common\models\member\Member;
use common\models\order\Order;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\models\order\OrderCart;
use common\models\order\OrderGoods;
use common\models\order\OrderInvoice;
use common\models\order\OrderTourist;
use common\models\order\OrderTouristDetails;
use common\models\order\OrderTouristInvoice;
use PayPal\Api\PayerInfo;
use PayPal\Api\Payment;
use PayPal\Api\ShippingAddress;
use services\goods\TypeService;
use yii\base\Exception;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class CouponService
 * @package services\market
 */
class CouponService extends Service
{
    static public function generatedData(MarketCoupon $model)
    {
        $where = [
            'specials_id' => $model->specials_id,
            'coupon_id' => $model->id
        ];

        //验证并生成产品数据
        $ids = [];
        foreach ($model->goods_attach as $goodsSn) {

            $goodsData = Goods::find()->where(['goods_sn'=>$goodsSn])->select(['style_id', 'type_id'])->one();

            if(empty($goodsData)) {
                throw new Exception(sprintf('[%s]产品未找到~！', $goodsSn));
            }

            $data = [
                'goods_type' => $goodsData->type_id,
                'style_id' => $goodsData->style_id
            ];

            if(($goods = MarketCouponGoods::find()->where(array_merge($where, $data))->one())) {

                //如果商品存在，则维护商品数据
                if($goods->exclude) {
                    $goods->exclude = 0;
                    $goods->save();
                }

                $ids[] = $goods->id;
                continue;
            }
            else {

                $goods = new MarketCouponGoods();
                $goods->setAttributes($where);
                $goods->setAttributes($data);
                $goods->count = $model->count;

                if(!$goods->save()) {
                    throw new Exception(sprintf('[%d]产品保存失败~！', $goodsSn));
                }
                $ids[] = $goods->id;
            }
        }

        //商品排除
        MarketCouponGoods::updateAll(['exclude'=>1], array_merge(['and'], [$where], [['NOT IN', 'id', $ids]]));

        //验证并生成产品线数据
        $typeList = TypeService::getTypeList();
        foreach ($model->goods_type_attach as $goodsTypeId) {
            if(!isset($typeList[$goodsTypeId])) {
                throw new Exception(sprintf('[%d]产品线未找到~！', $goodsTypeId));
            }

            if(MarketCouponGoodsType::find()->where(array_merge($where, ['goods_type'=>$goodsTypeId]))->count('id')) {
                continue;
            }

            $goodsType = new MarketCouponGoodsType();
            $goodsType->setAttributes($where);
            $goodsType->setAttributes(['goods_type'=>$goodsTypeId]);
            if(!$goodsType->save()) {
                throw new Exception(sprintf('[%d]产品线保存失败~！', $goodsTypeId));
            }
        }

        //产品线排除
        MarketCouponGoodsType::deleteAll(array_merge(['and'], [$where], [['NOT IN', 'goods_type', $model->goods_type_attach]]));

        //启用产品线对应的商品
        MarketCouponGoods::updateAll(['exclude'=>0], array_merge(['and'], [$where], [['IN', 'goods_type', $model->goods_type_attach]]));

        //生成地区数据
        foreach ($model->area_attach as $areaId) {
            if(empty(AreaEnum::getValue($areaId))) {
                throw new Exception(sprintf('[%d]地区未找到~！', $areaId));
            }

            if(MarketCouponArea::find()->where(array_merge($where, ['area_id'=>$areaId]))->count('id')) {
                continue;
            }

            $area = new MarketCouponArea();
            $area->setAttributes($where);
            $area->setAttributes(['area_id'=>$areaId]);
            if(!$area->save()) {
                throw new Exception(sprintf('[%d]地区保存失败~！', $areaId));
            }
        }

        //地区排除
        MarketCouponArea::deleteAll(array_merge(['and'], [$where], [['NOT IN', 'area_id', $model->area_attach]]));
    }
}