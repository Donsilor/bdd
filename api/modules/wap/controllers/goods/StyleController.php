<?php

namespace api\modules\wap\controllers\goods;

use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use common\models\goods\Style;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
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
            "sale_volume"=>'sale_volume',//销量
        ];
        $type_id = \Yii::$app->request->get("categoryId");//产品线ID
        if(!$type_id){
            return ResultHelper::api(422, '产品线不能为空');
        }
        $order_param = \Yii::$app->request->get("sortBy");//排序参数
        $order_type = \Yii::$app->request->get("sortType", 1);//排序方式 1-升序；2-降序;
        $ev = \Yii::$app->request->get("ev");  //属性帅选

        //排序
        $order = '';
        if(!empty($order_param)){
            $order_type = $order_type == 1? "asc": "desc";
            $order = $sort_map[$order_param]. " ".$order_type;
        }

        $area_id = \Yii::$app->params['area_id'];
        $fields = ['m.id','lang.style_name','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.status=1 and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])->orderby($order);


        if($type_id) {
            $query ->andWhere(['m.type_id'=>$type_id]);
        }


        $params = explode('^',$ev);
        $params = array_filter($params);//删除空成员

        if(!(empty($params))){
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
                        $min_price = $this->exchangeAmount($min_price,2, 'CNY', $this->getCurrency());
                        $query->andWhere(['>','IFNULL(markup.sale_price,m.sale_price)',$min_price]);
                    }
                    if(is_numeric($max_price) && $max_price>0){
                        $max_price = $this->exchangeAmount($max_price,2, 'CNY', $this->getCurrency());
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
                        $marry_style_man_attr = \Yii::$app->attr->valueList(55);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }
                }elseif ($param_name == 'marry_style_wom'){
                    $attr_id = 54;
                    if($param_value == -1){
                        $marry_style_man_attr = \Yii::$app->attr->valueList(54);
                        $param_value = array_column($marry_style_man_attr,'id');
                    }
                }else{
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
                $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value_id in ({$config_values_str})");

            }
//            echo $subQuery->createCommand()->getSql();exit;
//            return $subQuery->asArray()->all();
            $query->andWhere(['in','m.id',$subQuery]);

        }
//        echo $query->createCommand()->getSql();exit;
        $result = $this->pagination($query,$this->page, $this->pageSize);

        foreach($result['data'] as & $val) {
            $arr = array();
            $arr['id'] = $val['id'];
            $arr['categoryId'] = $type_id;
            $arr['coinType'] = $this->getCurrencySign();
            $arr['goodsImages'] = $val['goods_images'];
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price']);
            $arr['goodsName'] = $val['style_name'];
            $arr['isJoin'] = null;
            $arr['showType'] = 2;
            $arr['specsModels'] = null;
            $val = $arr;
        }
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
        $webSite['moduleTitle'] = '最畅销订婚戒指';
        foreach ($style_list as $val){
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->getCurrencySign();
            $moduleGoods['goodsCode'] = $val['style_sn'];
            $moduleGoods['goodsImages'] = $val['goods_images'];
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price']);
            $webSite['moduleGoods'][] = $moduleGoods;
        }
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
            $area_id = \Yii::$app->params['area_id'];
            $style = \Yii::$app->services->goods->formatStyleGoodsById($id, $this->language);
            $recommend_style = Style::find()->alias('m')
                ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
                ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.status=1 and markup.area_id='.$area_id)
                ->where(['and',['m.status'=>StatusEnum::ENABLED],['<>','m.id',$id],['=','m.type_id',$model->type_id]])
                ->orderBy('m.goods_clicks desc')
                ->select(['m.id','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price','lang.style_name'])
                ->limit(4)->all();

            foreach ($recommend_style as $val){
                $recommend = array();
                $recommend['id'] = $val->id;
                $recommend['goodsName'] = $val->lang->style_name;
                $recommend['categoryId'] = $model->type_id;
                $recommend['salePrice'] = $this->exchangeAmount($val->sale_price);
                $recommend['goodsImages'] = $val->goods_images;
                $recommend['isJoin'] = null;
                $recommend['specsModels'] = null;
                $recommend['coinType'] = $this->getCurrencySign();
                $style['recommends'][] = $recommend;
            }


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