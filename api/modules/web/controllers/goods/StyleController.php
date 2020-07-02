<?php

namespace api\modules\web\controllers\goods;

use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use common\helpers\ImageHelper;
use common\models\goods\Goods;
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
            "sale_volume"=>'m.virtual_volume',//销量
        ];
        $type_id = \Yii::$app->request->post("categoryId");//产品线ID
        if(!$type_id){
            return ResultHelper::api(422, '产品线不能为空');
        }
        $order_param = \Yii::$app->request->post("orderParam");//排序参数
        $order_type = \Yii::$app->request->post("orderType", 1);//排序方式 1-升序；2-降序;

        //排序
        $order = 'm.virtual_volume desc ,m.id desc';
        if(!empty($order_param)){
            $order_type = $order_type == 1? "asc": "desc";
            $order = $sort_map[$order_param]. " ".$order_type .",m.id desc";
        }


        $area_id = $this->getAreaId(); 
        $fields = ['m.id','m.type_id','lang.style_name','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
            ->orderby($order);

        $params = \Yii::$app->request->post("params");  //属性帅选

//        $params = json_decode($params);
        if(is_array($type_id)) {
            $query ->andWhere(['in','m.type_id',$type_id]);
        }else{
            $query ->andWhere(['m.type_id'=>$type_id]);
        }

        if(!empty($params)){

            $ringStyleQuery = null;
            $subQuery = AttributeIndex::find()->alias('a')->select(['a.style_id'])->distinct("a.style_id");

            $k = 0;
            foreach ($params as $param){
                $value_type = $param['valueType'];

                $param_name = $param['paramName'];
                //价格不是属性,直接查询主表
                if($param_name == 'sale_price'){
                    $min_price = $param['beginValue'];
                    $max_price = $param['endValue'];
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
                if(isset($param['paramId']) && is_numeric($param['paramId'])){
                    $attr_id = $param['paramId'];
                    $k++;
                    $alias = "a".$k; //别名
                    $on = "{$alias}.style_id = a.style_id and {$alias}.attr_id = $attr_id ";
                }else{
                    continue;
                }


                if($value_type == 1){
                    $config_values = $param['configValues'];
                    $config_values = array_merge(array_diff($config_values, array(-1)));
                    if(empty($config_values)) continue;
                    $config_values_str = join(',',$config_values);

                    if($param_name == "ring_style") {
                        $where = [];
                        $where['attr_id'] = $attr_id;
                        $where['attr_value_id'] = $config_values;
                        $ringStyleQuery = AttributeIndex::find()->alias($alias)->select(['style_id'])->where($where)->distinct("style_id");
                    }
                    else {
                        $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value_id in ({$config_values_str})");
                    }
                }else if($value_type == 2){
                    $begin_value = $param['beginValue'];
                    $end_value = $param['endValue'];
                    $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value > {$begin_value} and {$alias}.attr_value <= {$end_value}");
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
            $arr['categoryId'] = $val['type_id'];
            $arr['coinType'] = $this->getCurrencySign();
            $arr['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
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
        $type_id = 2;
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
            $moduleGoods['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'big');
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $webSite['moduleGoods'][] = $moduleGoods;
        }
        $result = array();
        $result['webSite'] = $webSite;
        $result['advert'] = array(
            'dsDesc' => '訂婚戒指——banner全屏',
            'dsImg' => '/adt/image1566979840127.png',
            'dsName' => '訂婚戒指——banner全屏',
            'dsShowType' => 1,
            'tdOpenType' => 1,
            'tdStatus' => 1,
        );
        return $result;

    }



    /**
     * 款式商品详情
     * @return mixed|NULL|number[]|string[]|NULL[]|array[]|NULL[][]|unknown[][][]|string[][][]|mixed[][][]|\common\helpers\unknown[][][]
     */
    public function actionDetail()
    {
        $id = \Yii::$app->request->post("goodsId");
        $area_id = $this->getAreaId();
        $backend = \Yii::$app->request->post("backend");
        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $query = Style::find()->where(['id'=>$id]);
        if($backend != 1){
            $query->andWhere(['status'=>StatusEnum::ENABLED]);
        }
        $model = $query->one();
        if(empty($model)) {
            return ResultHelper::api(422,"商品信息不存在或者已经下架");
        }
        try{

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