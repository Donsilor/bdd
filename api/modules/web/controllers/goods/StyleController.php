<?php

namespace api\modules\web\controllers\goods;

use common\enums\StatusEnum;
use Yii;
use api\controllers\OnAuthController;
use common\models\goods\Style;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\helpers\ImageHelper;
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
            "sale_price"=>'m.sale_price',//价格
            "sale_volume"=>'m.sale_volume',//销量
        ];
        $type_id = \Yii::$app->request->post("categoryId");//产品线ID
        if(!$type_id){
            return ResultHelper::api(422, '产品线不能为空');
        }
        $order_param = \Yii::$app->request->post("orderParam");//排序参数
        $order_type = \Yii::$app->request->post("orderType", 1);//排序方式 1-升序；2-降序;

        //排序
        $order = '';
        if(!empty($order_param)){
            $order_type = $order_type == 1? "asc": "desc";
            $order = $sort_map[$order_param]. " ".$order_type;
        }

        $fields = ['m.id','lang.style_name','m.goods_images','m.sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->where(['m.status'=>StatusEnum::ENABLED])->orderby($order);

        $params = \Yii::$app->request->post("params");  //属性帅选

//        $params = json_decode($params);
        if(!empty($params)){

            $subQuery = AttributeIndex::find()->alias('a')->select(['a.style_id'])->distinct("a.style_id");
            if($type_id) {
                $query ->andWhere(['m.type_id'=>$type_id]);
            }

            $k = 0;
            foreach ($params as $param){
                $value_type = $param['valueType'];

                $param_name = $param['paramName'];
                //价格不是属性,直接查询主表
                if($param_name == 'sale_price'){
                    $min_price = $param['beginValue'];
                    $max_price = $param['endValue'];
                    if(is_numeric($min_price)){
                        $query->andWhere(['>','m.sale_price',$min_price]);
                    }
                    if(is_numeric($max_price) && $max_price>0){
                        $query->andWhere(['<=','m.sale_price',$max_price]);
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
                    $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value_id in ({$config_values_str})");
                }else if($value_type == 2){
                    $begin_value = $param['beginValue'];
                    $end_value = $param['endValue'];
                    $subQuery->innerJoin(AttributeIndex::tableName().' '.$alias, $on." and {$alias}.attr_value > {$begin_value} and {$alias}.attr_value <= {$end_value}");
                }
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
            $arr['coinType'] = $this->currencySign;
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
        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','m.sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id,$limit,$order, $fields ,$language);
        $webSite = array();
        $webSite['moduleTitle'] = '最暢銷訂婚戒指';
        foreach ($style_list as $val){
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->currency;
            $moduleGoods['goodsCode'] = $val['style_sn'];
            $moduleGoods['goodsImages'] = $val['goods_images'];
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price']);
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
        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $model = Style::find()->where(['id'=>$id])->one();
        if(empty($model)) {
            return ResultHelper::api(422,"商品信息不存在");
        }
        try{
            $style = \Yii::$app->services->goods->formatStyleGoodsById($id, $this->language);
            $recommend_style = Style::find()->alias('m')
                ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
                ->where(['and',['m.status'=>StatusEnum::ENABLED],['<>','m.id',$id],['=','m.type_id',$model->type_id]])
                ->orderBy('m.goods_clicks desc')
                ->select(['m.id','m.goods_images','m.sale_price','lang.style_name'])
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
                $recommend['coinType'] = $this->currencySign;
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