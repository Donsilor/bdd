<?php

namespace api\modules\web\controllers\member;

use api\modules\web\forms\CardForm;
use api\modules\web\forms\CardValidateForm;
use common\enums\AreaEnum;
use common\helpers\ImageHelper;
use common\models\forms\PayForm;
use common\models\goods\Ring;
use common\models\goods\RingLang;
use common\models\market\MarketCard;
use common\models\market\MarketCardDetails;
use common\models\order\Order;
use common\models\order\OrderCart;
use api\modules\web\forms\CartForm;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
use services\goods\TypeService;
use services\market\CardService;
use yii\base\Exception;
use yii\web\UnprocessableEntityHttpException;

/**
 * 购物卡
 *
 * Class CardController
 * @package api\modules\v1\controllers
 */
class CardController extends UserAuthController
{
    
    public $modelClass = MarketCardDetails::class;
    
    protected $authOptional = ['index'];

    /**
     * 购物车列表     
     */
    public function actionIndex()
    {
        $ord = Order::findOne(1832);

        $order = \Yii::$app->services->order->getOrderLogisticsInfo($ord);

        return $order;
    }

    /**
     * 验证购物卡
     */
    public function actionVerify()
    {

        $post = \Yii::$app->request->post();

        $model = new CardValidateForm();
        $model->setAttributes($post);
        $model->area_attach = $this->getAreaId();

        if(!$model->validate()) {
            return ResultHelper::api(422, $this->getError($model));
        }

        $card = $model->getCard();
        if(empty($card->area_attach)) {
            $area_names = AreaEnum::getMap();
        }
        else {
            $area_names = [];
            foreach ($card->area_attach as $area_attach) {
                $area_names[] = AreaEnum::getValue($area_attach);
            }
        }

        $data = [
            'sn' => $model->getCard()->sn,
            'currency' => $this->getCurrency(),
            'amount' => $this->exchangeAmount($model->getCard()->amount),
            'amountCny' => $model->getCard()->amount,
            'balanceCny' => $model->getCard()->balance,
            'goodsTypeAttach' => $model->getCard()->goods_type_attach,
            'balance' => $this->exchangeAmount($model->getCard()->balance),
            'startTime' => $model->getCard()->start_time,
            'endTime' => $model->getCard()->end_time,
            'firstUseTime' => $model->getCard()->first_use_time,
            'maxUseTime' => $model->getCard()->max_use_time,
            'maxUseDay' => round($model->getCard()->max_use_time/86400),
            'limitedUseTime' => $model->getCard()->max_use_time && $model->getCard()->first_use_time ? $model->getCard()->max_use_time+$model->getCard()->first_use_time:null,
            'areaNames' =>  array_values($area_names)
        ];

        $goodsTypes = [];
        foreach (TypeService::getTypeList() as $key => $item) {
            if(in_array($key, $data['goodsTypeAttach'])) {
                $goodsTypes[$key] = $item;
            }
        }
        $data['goodsTypes'] = $goodsTypes;

        return $data;
    }
}