<?php

namespace api\modules\web\controllers\member;

use \api\controllers\UserAuthController;
use common\enums\CouponStatusEnum;
use common\models\market\MarketCouponDetails;

class CouponController extends UserAuthController
{
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

        $query->orderBy('id DESC');

        $result = $this->pagination($query, $this->page, $this->pageSize,false);

        $couponList = [];
        foreach ($result['data'] as $datum) {
            $couponList[] = [
                'specialsName' => $datum->specials->lang->title,//活动名
                'couponCode' => $datum->coupon_code,//券编码
                'money' => $datum->coupon->money,//金额
                'couponStatus' => $datum->coupon_status,//状态
                'orderSn' => $datum->order_sn,//订单编号
                'atLeast' => $datum->coupon->at_least,//满多少钱使用
                'startTime' => $datum->specials->start_time,//开始时间
                'endTime' => $datum->specials->end_time,//结束时间
            ];
        }
        $result['data'] = $couponList;
        return $result;
    }

}
