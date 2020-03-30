<?php

namespace services\market;

use common\components\Service;
use common\enums\AreaEnum;
use common\enums\OrderStatusEnum;
use common\enums\OrderTouristStatusEnum;
use common\enums\PayStatusEnum;
use common\models\goods\Goods;
use common\models\goods\Style;
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

    /**
     * 生成优惠数据
     * @param MarketCoupon $model
     * @throws Exception
     */
    static public function generatedData(MarketCoupon $model)
    {
        $where = [
            'specials_id' => $model->specials_id,
            'coupon_id' => $model->id
        ];

        //验证并生成产品数据
        $ids = [];
        foreach ($model->goods_attach as $goodsSn) {

            $goodsData = Style::find()->where(['style_sn'=>$goodsSn])->select(['id', 'type_id'])->one();

            if(empty($goodsData)) {
                throw new Exception(sprintf('[%s]产品未找到~！', $goodsSn));
            }

            $data = [
                'goods_type' => $goodsData->type_id,
                'style_id' => $goodsData->id
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

    //所有进行中优惠信息列表
    static public function getCouponList($areaId, $type=null, $timeStatus=null)
    {
        static $data = [];

        $key = $areaId.'-'.$type.'-'.$timeStatus;
        if(isset($data[$key])) {
            return $data[$key];
        }

        $where = [
            'and'
        ];

        //券的类型
        if(!empty($type)) {
            $where[] = [
                'market_coupon.type' => $type
            ];
        }

        $where[] = [
            'market_coupon_area.area_id' => $areaId,
            'market_coupon.status' => 1,
            'market_specials.status' => 1,
        ];

        $time = time();
        if($timeStatus==1) {
            //未开始
            $where[] = ['>', 'market_specials.start_time', $time];
        } elseif($timeStatus==2) {
            //时行中
            $where[] = ['<=', 'market_specials.start_time', $time];
            $where[] = ['>=', 'market_specials.end_time', $time];
        } elseif($timeStatus==3) {
            //已结束
            $where[] = ['<', 'market_specials.end_time', $time];
        }

        $data[$key] = MarketCoupon::find()
            ->leftJoin('market_specials', 'market_coupon.specials_id=market_specials.id')
            ->leftJoin('market_coupon_area', 'market_coupon.id=market_coupon_area.coupon_id')
            ->where($where)
            ->all();

        return $data[$key];
    }

    //根据活动类型，地区，产品线，款式获取优惠信息
    static public function getCouponBy()
    {

    }


    //根据活动地区，产品线，款式
}