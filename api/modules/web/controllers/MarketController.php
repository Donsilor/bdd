<?php


namespace api\modules\web\controllers;


use api\controllers\ActiveController;
use common\enums\StatusEnum;
use common\helpers\ImageHelper;
use common\models\goods\Style;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
use common\models\market\MarketSpecials;
use services\market\CouponService;

class MarketController extends ActiveController
{

    /**
     * @var MarketSpecials
     */
    public $modelClass = MarketSpecials::class;

    protected $authOptional = ['index'];

    public function actionIndex()
    {
        $id = \Yii::$app->request->get('id', null);

        $model = $this->modelClass::findOne($id);

        if(!$model) {
            return null;
        }

        $result = $model->toArray();

        $ids = [];

        if(!empty($result['recommend_attach']) && is_array($result['recommend_attach'])) {
            foreach ($result['recommend_attach'] as $recommend_attach) {
                $ids = array_merge($ids, $recommend_attach);
            }
        }

        $area_id = $this->getAreaId();
        $fields = ['m.id','m.type_id','lang.style_name','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['m.id'=>$ids])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);

        $list = $query->all();

        $data = [];

        foreach($list as $val) {
            $arr = array();
            $arr['id'] = $val['id'];
            $arr['categoryId'] = $val['type_id'];
            $arr['coinType'] = $this->getCurrencySign();
            $arr['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $arr['goodsName'] = $val['style_name'];
            $arr['isJoin'] = null;
            $arr['showType'] = 2;
            $arr['specsModels'] = null;

            $arr['coupon'] = [
                'type_id' => $val['type_id'],//产品线ID
                'style_id' => $val['id'],//款式ID
                'price' => $arr['salePrice'],//价格
                'num' =>1,//数量
            ];

            $data[$val['id']] = $arr;
        }

        CouponService::getCouponByList($this->getAreaId(), $data);

        $recommends = [];

        if(!empty($result['recommend_attach']) && is_array($result['recommend_attach'])) {
            foreach ($result['recommend_attach'] as $key => $recommend_attach) {
                foreach ($recommend_attach as $style_id) {
                    if(isset($data[$style_id])) {
                        $recommends[$key] = $data[$style_id];
                    }
                }
            }
        }

        $result['recommend'] = $recommends;

        return $result;
    }
}