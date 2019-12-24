<?php

namespace api\modules\web\controllers\goods;

use api\modules\web\forms\AttrSpecForm;
use common\models\goods\Diamond;
use common\models\goods\DiamondLang;
use Yii;
use api\controllers\OnAuthController;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\helpers\ImageHelper;
use yii\db\Expression;
use common\models\goods\AttributeIndex;

/**
 * Class ProvincesController
 * @package api\modules\web\controllers\goods
 */
class DiamondController extends OnAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = Diamond::class;
    protected $authOptional = ['search','detail','guess-list'];

    /**
     * 款式商品搜索
     * @return array
     */

    public function actionSearch(){
        $sort_map = [
            "price"=>'d.sale_price',//价格
            "carat"=>'d.carat',//石重
            "clarity"=>'d.clarity',//净度
            "cut"=>'d.cut',//切割
            "color"=>'d.color',//颜色
        ];
        $params_map = [
            'shape'=>'d.shape',//形状
            'sale_price'=>'d.sale_price',//销售价
            'carat'=>'d.carat',//石重
            'cut'=>'d.cut',//切工
            'color'=>'d.color',//颜色
            'clarity'=>'d.clarity',//净度
            'polish'=>'d.polish',//光澤--抛光
            'symmetry'=>'d.symmetry',//对称
            'card'=>'d.cert_type',//证书类型
            'depth'=>'d.depth_lv',//深度
            'table'=>'d.table_lv',//石面--台宽
            'fluorescence'=>'d.fluorescence',//荧光
        ];

        $language = Yii::$app->params['language'];
        $language = \Yii::$app->request->get("language" , $language);//语言
        $type_id = \Yii::$app->request->get("categoryId");//产品线ID
        $page = \Yii::$app->request->get("currPage",1);//页码
        $page_size = \Yii::$app->request->get("pageSize",20);//每页大小
        $order_param = \Yii::$app->request->get("orderParam");//排序参数
        $order_type = \Yii::$app->request->get("orderType", 1);//排序方式 1-升序；2-降序;

        //排序
        $order = '';
        if(!empty($order_param)){
          $order_type = $order_type == 1? "asc": "desc";
          $order = $sort_map[$order_param]. " ".$order_type;
        }

        $fields = ['d.id','d.goods_sn','lang.goods_name','d.goods_image','d.sale_price'];
        $query = Diamond::find()->alias('d')->select($fields)
            ->leftJoin(DiamondLang::tableName().' lang',"d.id=lang.master_id and lang.language='".$language."'")
            ->orderby($order);

        $params = \Yii::$app->request->get("params");  //属性帅选
        $params = json_decode($params);
        if(!empty($params)){
            foreach ($params as $param){
                $value_type = $param->valueType;
                $param_name = $param->paramName;
                if($value_type == 1){
                    $config_values = $param->configValues;
                    $query->andWhere(['in',$params_map[$param_name], $config_values]);
                }else if($value_type == 2){
                    $begin_value = $param->beginValue;
                    $end_value = $param->endValue;
                    $query->andWhere(['between',$params_map[$param_name], $begin_value, $end_value]);
                }
            }
        }

        $result = $this->pagination($query,$page,$page_size);
        foreach($result['data'] as & $val) {
            $val['categoryId'] = $type_id;
            $val['coinType'] = 'CNY';
            $val['goodsName'] = $val['goods_name'];
            $val['isJoin'] = null;
            $val['salePrice'] = $val['sale_price'];
            $val['goodsImages'] = ImageHelper::thumb($val['goods_image']);
            $val['specsModels'] = '';
        }
        return $result;


    }




    public function actionSearch1()
    {
        $sort_map = [
            "1_0"=>'s.sale_price asc',//销售价
            "1_1"=>'s.sale_price desc',
            "2_0"=>'s.goods_clicks asc',//浏览量
            "2_1"=>'s.goods_clicks desc',
            "3_0"=>'s.onsale_time asc',//上架时间
            "3_1"=>'s.onsale_time desc',
            "4_0"=>'s.rank asc',//权重
            "4_1"=>'s.rank desc',
        ];

        $type_id = \Yii::$app->request->post("type_id");//产品线
        $keyword = \Yii::$app->request->post("keyword");//产品线
        $price_range   = \Yii::$app->request->post("price_range");//最低价格
        $attr_id  = \Yii::$app->request->post("attr_id");//属性
        $attr_value  = \Yii::$app->request->post("attr_value");//属性

        $sort = \Yii::$app->request->post("sort",'4_1');//排序
        $page = \Yii::$app->request->post("page",1);//页码
        $page_size = \Yii::$app->request->post("page_size",20);//每页大小

        $order = $sort_map[$sort] ?? '';

        $fields = ['s.id','s.style_sn','lang.style_name','s.style_image','s.sale_price','s.goods_clicks'];
        $query = Style::find()->alias('s')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"s.id=lang.master_id and lang.language='".\Yii::$app->language."'")
            ->orderby($order);

        if($type_id) {
            $query->andWhere(['=','s.type_id',$type_id]);
        }
        if($keyword) {
            $query->andWhere(['or',['like','lang.style_name',$keyword],['=','s.style_sn',$keyword]]);
        }
        if($price_range){
            $arr = explode('-',$price_range);
            if(count($arr) == 2 ){
                list($min_price,$max_price) = $arr;
                if(is_numeric($min_price)){
                    $query->andWhere(['>','s.sale_price',$min_price]);
                }

                if(is_numeric($max_price) && $max_price>0){
                    $query->andWhere(['<=','s.sale_price',$max_price]);
                }

            }
        }
        //print_r($attr_value);exit;
        //属性，属性值查询
        if($attr_id || $attr_value){
            $subQuery = AttributeIndex::find()->select(['style_id'])->distinct("style_id");
            if($type_id) {
                $subQuery->where(['type_id'=>$type_id]);
            }
            if($attr_id) {
                if(!is_array($attr_id)){
                    $attr_id = explode(',',$attr_id);
                }
                $subQuery->andWhere(['attr_value_id'=>$attr_id]);
            }
            if($attr_value && $attr_value = explode("|", $attr_value)){
                foreach ($attr_value as $k=>$val){
                    list($k,$v) = explode("@",$val);
                    $arr = explode("-",$v);
                    if(count($arr) ==1) {
                        $subQuery->andWhere(['attr_id'=>$k,'attr_value'=>$v]);
                    }else if(count($arr)==2){
                        $subQuery->andWhere(['and',['=','attr_id',$k],['between','attr_value',$arr[0], $arr[1]]]);
                    }
                }
            }
            $query->andWhere(['in','s.id',$subQuery]);
        }
        //echo $query->createCommand()->getSql();exit;
        $result = $this->pagination($query,$page,$page_size);

        foreach($result['data'] as & $val) {
            $val['currency'] = '$';
            $val['style_image'] = ImageHelper::thumb($val['style_image']);
        }

        return $result;

    }
    /**
     * 款式商品详情
     * @return mixed|NULL|number[]|string[]|NULL[]|array[]|NULL[][]|unknown[][][]|string[][][]|mixed[][][]|\common\helpers\unknown[][][]
     */
    public function actionDetail()
    {
        $id = \Yii::$app->request->get("id");
        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $model = Style::find()->where(['id'=>$id])->one();
        if(empty($model)) {
            return ResultHelper::api(422,"商品信息不存在");
        }
        $attr_data = \Yii::$app->services->goods->formatStyleAttrs($model);
        $attr_list = [];
        foreach ($attr_data['style_attr'] as $attr){
            $attr_value = $attr['value'];
            if(empty($attr_value)) {
                continue;
            }
            if(is_array($attr_value)){
                $attr_value = implode('/',$attr_value);
            }
            $attr_list[] = [
                'name'=>$attr['attr_name'],
                'value'=>$attr_value,
            ];
        }
        if($model->goods_images) {
            $goods_images = explode(",",$model->goods_images);
            $goods_images = [
                'big'=>ImageHelper::thumbs($goods_images),
                'thumb'=>ImageHelper::thumbs($goods_images),
            ];
        }else{
            $goods_images = [];
        }
        $info = [
            'id' =>$model->id,
            'type_id'=>$model->type_id,
            'style_name'=>$model->lang->style_name,
            'style_moq'=>1,
            'sale_price'=>$model->sale_price,
            'currency'=>'$',
            'goods_images'=>$goods_images,
            'goods_3ds'=>$model->style_3ds,
            'style_attrs' =>$attr_list,
        ];
        $model->goods_clicks = new Expression("goods_clicks+1");
        $model->virtual_clicks = new Expression("virtual_clicks+1");
        $model->save(false);//更新浏览量
        return $info;
    }
    /**
     * 猜你喜欢推荐列表
     */
    public function actionGuessList()
    {
        $style_id= \Yii::$app->request->get("style_id");
        if(empty($style_id)) {
            return ResultHelper::api(422,"style_id不能为空");
        }
        $model = Style::find()->where(['id'=>$style_id])->one();
        if(empty($model)) {
            return [];
        }
        $type_id = $model->type_id;
        $fields = ['s.id','s.style_sn','lang.style_name','s.style_image','s.sale_price','s.goods_clicks'];
        $query = Style::find()->alias('s')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"s.id=lang.master_id and lang.language='".\Yii::$app->language."'")
            ->andWhere(['s.type_id'=>$type_id])
            ->andWhere(['<>','s.id',$style_id])
            ->orderby("s.goods_clicks desc");
        $models = $query->limit(10)->asArray()->all();
        foreach ($models as &$model){
            $model['style_image'] = ImageHelper::thumb($model['style_image']);
            $model['currency'] = '$';
        }
        return $models;
    }



}