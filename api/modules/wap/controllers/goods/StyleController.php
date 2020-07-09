<?php

namespace api\modules\wap\controllers\goods;

use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use common\helpers\ImageHelper;
use common\models\goods\Goods;
use common\models\goods\Style;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
use services\market\CouponService;
use yii\db\Exception;
use yii\db\Expression;
use common\models\goods\AttributeIndex;

/**
 * Class ProvincesController
 * @package api\modules\v1\controllers\member
 */
class StyleController extends OnAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = Style::class;
    protected $authOptional = ['search','web-site','detail','guess-list'];


    /**
     * 款式商品搜索
     * @return array
     */
    public function actionSearch(){
        $sort_map = [
            "sale_price"=>'sale_price',//价格
            "sale_volume"=>'virtual_volume',//销量
        ];
        $type_id = \Yii::$app->request->get("categoryId");//产品线ID
        if(!$type_id){
            return ResultHelper::api(422, '产品线不能为空');
        }
        $order_param = \Yii::$app->request->get("sortBy");//排序参数
        $order_type = \Yii::$app->request->get("sortType", 1);//排序方式 1-升序；2-降序;
        $ev = \Yii::$app->request->get("ev");  //属性帅选

        //排序
        $order = 'virtual_volume desc ,id desc';
        if(!empty($order_param)){
            $order_type = $order_type == 1 ? "asc": "desc";
            $order = $sort_map[$order_param]. " ".$order_type . ",id desc";
        }

        $area_id = $this->getAreaId(); 
        $fields = ['m.id','lang.style_name','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
            ->orderby($order);


        if(is_array($type_id)) {
            $query ->andWhere(['in','m.type_id',$type_id]);
        }else{
            $query ->andWhere(['m.type_id'=>$type_id]);
        }


        $params = explode('^',$ev);
        $params = array_filter($params);//删除空成员

        if(!(empty($params))){
            $ringStyleQuery = null;
            $subQuery = AttributeIndex::find()->alias('a')->select(['a.style_id'])->distinct("a.style_id");
            $k = 0;
            foreach ($params as $param){
                $param_arr = explode('=',$param);
                $param_name = $param_arr[0];
                $param_value = $param_arr[1];
                //价格不是属性,直接查询主表
                if($param_name == 'sale_price'){
                    $param_sale_price_arr = explode('-',$param_value);
                    $min_price = $param_sale_price_arr[0];
                    $max_price = $param_sale_price_arr[1];
                    if(is_numeric($min_price)){
                        $min_price = $this->exchangeAmount($min_price,0, 'CNY', $this->getCurrency());
                        $query->andWhere(['>','IFNULL(markup.sale_price,m.sale_price)',$min_price]);
                    }
                    if(is_numeric($max_price) && $max_price>0){
                        $max_price = $this->exchangeAmount($max_price,0, 'CNY', $this->getCurrency());
                        $query->andWhere(['<=','IFNULL(markup.sale_price,m.sale_price)',$max_price]);
                    }
                    continue;
                }

                if($param_name == 'engaged_style'){ //订婚戒指款式
                    $attr_id = 40;

                }elseif ($param_name == 'material'){  //成色
                    $attr_id = 10;
                }elseif ($param_name == 'marry_style_man'){
                    $attr_id = 55;
                    if($param_value == -1){
                        continue;
                        $marry_style_man_attr = \Yii::$app->attr->valueList(55);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }
                }elseif ($param_name == 'marry_style_wom'){
                    $attr_id = 54;
                    if($param_value == -1){
                        continue;
                        $marry_style_man_attr = \Yii::$app->attr->valueList(54);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }
                }elseif($param_name == 'gender'){
                    $attr_id = 26;
                    if($param_value == -1){
                        continue;
                        $marry_style_man_attr = \Yii::$app->attr->valueList(26);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }else{
                        //通用款在男戒、女戒里显示
                        $param_value = [$param_value,43];
                    }

                }elseif($param_name == 'theme'){
                    $attr_id = 60;
                    if($param_value == -1){
                        continue;
                        $marry_style_man_attr = \Yii::$app->attr->valueList(26);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }

                }
                elseif($param_name == 'ring_style'){
                    $attr_id = 39;
                    if($param_value == -1){
                        continue;
                    }
                }
                else{
                    continue;
                }
                if(!is_array($param_value)){
                    $param_value = array($param_value);
                }
                $k++;
                $alias = "a".$k; //别名
                $on = "{$alias}.style_id = a.style_id and {$alias}.attr_id = $attr_id ";
                $config_values = array_merge(array_diff($param_value, array(-1)));
                if(empty($config_values)) continue;
                $config_values_str = join(',',$config_values);

                if($param_name == 'ring_style') {
                    $where = [];
                    $where['attr_id'] = $attr_id;
                    $where['attr_value_id'] = $config_values;
                    $ringStyleQuery = AttributeIndex::find()->alias($alias)->select(['style_id'])->where($where)->distinct("style_id");
                }
                else {
                    $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value_id in ({$config_values_str})");
                }

            }
//            echo $subQuery->createCommand()->getSql();exit;
//            return $subQuery->asArray()->all();

            if($type_id==19) {
                $subQuery2 = Goods::find()
                    ->alias('goods')
                    ->rightJoin(AttributeIndex::tableName().' as ai', 'ai.attr_value_id=goods.id')
                    ->andWhere(['in','goods.style_id', $subQuery])
                    ->andWhere(['in','ai.attr_id',[61,62]])
                    ->select(['ai.style_id'])
                    ->distinct("ai.style_id");

                $query->andWhere(['in','m.id',$subQuery2]);

                if($ringStyleQuery) {
                    $query->andWhere(['in','m.id',$ringStyleQuery]);
                }
            }
            else {
                $query->andWhere(['in','m.id',$subQuery]);
            }

        }
//        echo $query->createCommand()->getSql();exit;
        $result = $this->pagination($query,$this->page, $this->pageSize);

        foreach($result['data'] as & $val) {
            $arr = array();
            $arr['id'] = $val['id'];
            $arr['categoryId'] = $type_id;
            $arr['coinType'] = $this->getCurrencySign();
            $arr['goodsImages'] =ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $arr['goodsName'] = $val['style_name'];
            $arr['isJoin'] = null;
            $arr['showType'] = 2;
            $arr['specsModels'] = null;

            $arr['coupon'] = [
                'type_id' => $type_id,//产品线ID
                'style_id' => $val['id'],//款式ID
                'price' => $arr['salePrice'],//价格
                'num' =>1,//数量
            ];

            $val = $arr;
        }
        CouponService::getCouponByList($this->getAreaId(), $result['data']);
        return $result;

    }


    //訂婚戒指--活动页
    public function actionWebSite(){
        $type_id = 12;
        $limit = 6;
        $language = $this->language;
        $order = 'sale_volume desc';
        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id,$limit,$order, $fields ,$language);
        $webSite = array();
        $webSite['moduleTitle'] = \Yii::t('common','最畅销订婚戒指');
        foreach ($style_list as $val){
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->getCurrencySign();
            $moduleGoods['goodsCode'] = $val['style_sn'];
            $moduleGoods['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price'],0);

            $moduleGoods['coupon'] = [
                'type_id' => $type_id,//产品线ID
                'style_id' => $val['id'],//款式ID
                'price' => $moduleGoods['salePrice'],//价格
                'num' =>1,//数量
            ];

            $webSite['moduleGoods'][] = $moduleGoods;
        }

        CouponService::getCouponByList($this->getAreaId(), $webSite['moduleGoods']);

        $advert_list = \Yii::$app->services->advert->getTypeAdvertImage(0,3, $language);
        $advert = array();
        foreach ($advert_list as $val){
            $advertImgModelList = array();
            $advertImgModelList['addres'] = $val['adv_url'];
            $advertImgModelList['image'] = $val['adv_image'];
            $advertImgModelList['title'] = $val['title'];


            $advert['advertImgModelList'][] = $advertImgModelList;
        }
        $advert['tdOpenType'] = 1;


        $activity_list = \Yii::$app->services->advert->getTypeAdvertImage(0,4, $language);
        $activity = array();
        foreach ($activity_list as $val){
            $moduleActivity = array();
            $moduleActivity['wapUrl'] = $val['adv_url'];
            $moduleActivity['wapImage'] = $val['adv_image'];
            $moduleActivity['title'] = $val['title'];
            $moduleActivity['showType'] = 2;


            $activity['moduleActivity'][] = $moduleActivity;
        }
        $activity['moduleTitle'] = '精选订婚钻戒';



        $result = array();
        $result['webSite'][0] = $webSite;
        $result['advert'][0] = $advert;
        $result['webSite'][1] = $activity;
        return $result;

    }



    /**
     * 款式商品详情
     * @return mixed|NULL|number[]|string[]|NULL[]|array[]|NULL[][]|unknown[][][]|string[][][]|mixed[][][]|\common\helpers\unknown[][][]
     */
    public function actionDetail()
    {
        $id = \Yii::$app->request->get("goodsId");
        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $model = Style::find()->where(['id'=>$id,'status'=>StatusEnum::ENABLED])->one();
        if(empty($model)) {
            return ResultHelper::api(422,"商品信息不存在或者已经下架");
        }
        try{
            $area_id = $this->getAreaId(); 
            $style = \Yii::$app->services->goods->formatStyleGoodsById($id, $this->language);
            $style['goodsImages'] = ImageHelper::goodsThumbs($style['goodsImages'],'big');
            $recommend_style = Style::find()->alias('m')
                ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
                ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
                ->where(['and',['m.status'=>StatusEnum::ENABLED],['<>','m.id',$id],['=','m.type_id',$model->type_id]])
                ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
                ->orderBy('m.goods_clicks desc')
                ->select(['m.id','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price','lang.style_name'])
                ->limit(4)->all();

            foreach ($recommend_style as $val){
                $recommend = array();
                $recommend['id'] = $val->id;
                $recommend['goodsName'] = $val->lang->style_name;
                $recommend['categoryId'] = $model->type_id;
                $recommend['salePrice'] = $this->exchangeAmount($val->sale_price,0);
                $recommend['goodsImages'] = ImageHelper::goodsThumbs($val->goods_images,'mid');
                $recommend['isJoin'] = null;
                $recommend['specsModels'] = null;
                $recommend['coinType'] = $this->getCurrencySign();

                $recommend['coupon'] = [
                    'type_id' => $model->type_id,//产品线ID
                    'style_id' => $val->id,//款式ID
                    'price' => $recommend['salePrice'],//价格
                    'num' =>1,//数量
                ];

                $style['recommends'][] = $recommend;
            }

            CouponService::getCouponByList($this->getAreaId(), $style['recommends']);

            $model->goods_clicks = new Expression("goods_clicks+1");
            $model->virtual_clicks = new Expression("virtual_clicks+1");
            $model->save(false);//更新浏览量
            return $style;


        }catch (Exception $e){
            $error = $e->getMessage();
            return ResultHelper::api(422, $error);
        }

    }

    
    
    
}