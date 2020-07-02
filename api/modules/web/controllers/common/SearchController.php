<?php

namespace api\modules\web\controllers\common;

use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use common\helpers\ImageHelper;
use common\models\goods\Style;
use common\models\goods\StyleLang;
use common\models\goods\Ring;
use common\models\goods\RingLang;
use yii\db\Query;
use common\models\goods\StyleMarkup;
use yii\data\Pagination;


/**
 * Class ProvincesController
 * @package api\modules\v1\controllers\member
 */
class SearchController extends OnAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = Style::class;
    protected $authOptional = ['index'];


    /**
     * 款式商品搜索
     * @return array
     */
    public function actionIndex(){
        $sort_map = [
            '1'=>'virtual_volume desc ,id desc',//最暢銷
            '2'=>'sale_price asc ,id desc',//價格 - 從低到高
            '3'=>'sale_price desc ,id desc',//價格 - 從高到低
        ];
        $type_id = \Yii::$app->request->get("categoryId");//产品线ID
        $keyword = \Yii::$app->request->get("text");//产品线ID
        $order_type = \Yii::$app->request->get("sortType", 1);//排序方式 1-升序；2-降序;

        //排序
        $order = 'virtual_volume desc ,id desc';
        if(!empty($order_type)){
            $order = $sort_map[$order_type];
        }

        $area_id = $this->getAreaId();
        $fields1 = ['m1.id','m1.type_id','lang1.style_name','m1.goods_images','IFNULL(markup.sale_price,m1.sale_price) as sale_price','m1.virtual_volume'];
        $query1 = Style::find()->alias('m1')->select($fields1)
            ->leftJoin(StyleLang::tableName().' lang1',"m1.id=lang1.master_id and lang1.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm1.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m1.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);
        		
        if(!empty($keyword)){
            $query1->andWhere(['or',['like','lang1.style_name',$keyword],['like','m1.style_sn',$keyword]]);
        }

        $query1->orderby($order);

        $result = $this->pagination($query1,$this->page, $this->pageSize,true);

        foreach($result['data'] as & $val) {
            $arr = array();
            $arr['id'] = $val['id'];
            $arr['categoryId'] = $val['type_id'];
            $arr['coinType'] = $this->currency;
            $arr['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $arr['goodsName'] = $val['style_name'];
            $arr['isJoin'] = null;
            $arr['specsModels'] = null;
            $val = $arr;
        }
        return $result;

    }


    
    
    
}