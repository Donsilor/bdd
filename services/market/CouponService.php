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
            'coupon_id' => $model->id,
        ];

        //清除旧数据
        MarketCouponArea::deleteAll($where);
        MarketCouponGoods::deleteAll($where);
        MarketCouponGoodsType::deleteAll($where);

        //验证并生成产品线数据
        $typeList = TypeService::getTypeList();
        foreach ($model->goods_type_attach as $goodsTypeId) {
            if(!isset($typeList[$goodsTypeId])) {
                throw new Exception(sprintf('[%d]产品线未找到~！', $goodsTypeId));
            }

            $goodsType = new MarketCouponGoodsType();
            $goodsType->setAttributes($where);
            $goodsType->setAttributes(['goods_type'=>$goodsTypeId]);
            if(!$goodsType->save()) {
                throw new Exception(sprintf('[%d]产品线保存失败~！', $goodsTypeId));
            }
        }

        //验证并生成产品数据
        foreach ($model->goods_attach as $goodsSn) {

            $goodsData = Goods::find()->where(['goods_sn'=>$goodsSn])->select(['style_id'])->one();

            if(empty($goodsData)) {
                throw new Exception(sprintf('[%s]产品未找到~！', $goodsSn));
            }

            $goods = new MarketCouponGoods();
            $goods->setAttributes($where);
            $goods->setAttributes($goodsData->toArray());
            if(!$goods->save()) {
                throw new Exception(sprintf('[%d]产品保存失败~！', $goodsSn));
            }
        }

        //生成地区数据
        foreach ($model->area_attach as $areaId) {
            if(empty(AreaEnum::getValue($areaId))) {
                throw new Exception(sprintf('[%d]地区未找到~！', $areaId));
            }

            $area = new MarketCouponArea();
            $area->setAttributes($where);
            $area->setAttributes(['area_id'=>$areaId]);
            if(!$area->save()) {
                throw new Exception(sprintf('[%d]地区保存失败~！', $areaId));
            }
        }
    }
}