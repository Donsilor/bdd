<?php

namespace api\modules\web\controllers\member;

use \api\controllers\UserAuthController;
use common\enums\CouponStatusEnum;
use common\models\common\Provinces;
use common\models\market\MarketCoupon;
use common\models\market\MarketCouponDetails;
use common\models\member\Contact;
use services\market\CouponService;
use yii\web\UnprocessableEntityHttpException;

class CouponController extends UserAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = MarketCouponDetails::class;

    /**
     * 我的优惠券列表
     */
    public function actionIndex()
    {
        $couponStatus = \Yii::$app->request->get('coupon_status',-1);

        $query = MarketCouponDetails::find()->where(['member_id'=>$this->member_id]);

        if($couponStatus && in_array($couponStatus, CouponStatusEnum::getKeys())) {
            $query->andWhere(['coupon_status'=>$couponStatus]);
        }

        $query->leftJoin('market_coupon', 'market_coupon.id=market_coupon_details.coupon_id');
        $query->leftJoin('market_specials', 'market_specials.id=market_coupon_details.specials_id');

        $query->andWhere(['market_coupon.status'=>1]);
        //$query->andWhere(['>', 'market_specials.end_time', time()]);

        $query->orderBy('id DESC');

        $result = $this->pagination($query, $this->page, $this->pageSize,false);

        $couponList = [];
        foreach ($result['data'] as $datum) {

            $couponList[] = [
                'specialsName' => $datum->specials->lang->title,//活动名
                'couponCode' => $datum->coupon_code,//券编码
                'money' => $this->exchangeAmount($datum->coupon->money),//金额
                'moneyCn' => $datum->coupon->money,//金额
                'GoodsType' => $datum->coupon->getGoodsType(),//金额
                'couponId' => $datum->coupon->id,//优惠券ID
                'isGoods' => !empty($datum->coupon->goods_attach),
                'couponStatus' => $datum->coupon_status,//状态
                'orderSn' => $datum->order_sn,//订单编号
                'atLeast' => $this->exchangeAmount($datum->coupon->at_least),//满多少钱使用
                'atLeastCn' => $datum->coupon->at_least,//满多少钱使用
                'startTime' => $datum->specials->start_time,//开始时间
                'endTime' => $datum->specials->end_time,//结束时间
            ];
        }
        $result['data'] = $couponList;
        return $result;
    }

    /**
     * 领取优惠券
     */
    public function actionFetch()
    {
        try {
            $coupon_id = \Yii::$app->request->post('coupon_id');

            $couponDetails = CouponService::fetchCoupon($coupon_id, $this->member_id);

        } catch (\Exception $exception) {
            throw $exception;
        }

        return [
            'id' => $couponDetails->id,
        ];
    }

}
