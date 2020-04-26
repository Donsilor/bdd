<?php

namespace api\modules\web\controllers\goods;

use common\enums\StatusEnum;
use common\helpers\ImageHelper;
use common\models\goods\Diamond;
use common\models\goods\DiamondLang;
use api\controllers\OnAuthController;
use common\helpers\ResultHelper;
use common\models\goods\StyleMarkup;
use yii\db\Expression;


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
    protected $authOptional = ['search','detail','web-site'];

    /**
     * 款式商品搜索
     * @return array
     */

    public function actionSearch(){
        $sort_map = [
            "sale_price"=>'sale_price',//价格
            "carat"=>'m.carat',//石重
            "clarity"=>'m.clarity',//净度
            "cut"=>'m.cut',//切割
            "color"=>'m.color',//颜色
        ];
        $params_map = [
            'shape'=>'m.shape',//形状
            'sale_price'=>'IFNULL(markup.sale_price,m.sale_price)',//销售价
            'carat'=>'m.carat',//石重
            'cut'=>'m.cut',//切工
            'color'=>'m.color',//颜色
            'clarity'=>'m.clarity',//净度
            'polish'=>'m.polish',//光澤--抛光
            'symmetry'=>'m.symmetry',//对称
            'card'=>'m.cert_type',//证书类型
            'depth'=>'m.depth_lv',//深度
            'table'=>'m.table_lv',//石面--台宽
            'fluorescence'=>'m.fluorescence',//荧光
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


        $fields = ['m.id','m.goods_id','m.style_id','m.goods_sn','lang.goods_name','m.goods_image','IFNULL(markup.sale_price,m.sale_price) as sale_price'
                    ,'m.carat','m.cert_id','m.depth_lv','m.table_lv','m.clarity','m.cert_type','m.color'
                    ,'m.cut','m.fluorescence','m.polish','m.shape','m.symmetry'];

        $area_id = $this->getAreaId(); 
        $query = Diamond::find()->alias('m')->select($fields)
            ->leftJoin(DiamondLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.style_id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
            ->orderby($order);

        $params = \Yii::$app->request->post("params");  //属性帅选
        if(!empty($params)){
            foreach ($params as $param){
                $value_type = $param['valueType'];
                $param_name = $param['paramName'];
                if($value_type == 1){
                    $config_values = $param['configValues'];
                    $query->andWhere(['in',$params_map[$param_name], $config_values]);
                }else if($value_type == 2){
                    $begin_value = $param['beginValue'];
                    $end_value = $param['endValue'];
                    if($param_name == 'sale_price'){
                        $begin_value = $this->exchangeAmount($begin_value,0, 'CNY', $this->getCurrency());
                        $end_value = $this->exchangeAmount($end_value,0, 'CNY', $this->getCurrency());
                    }

                    $query->andWhere(['between',$params_map[$param_name], $begin_value, $end_value]);
                }
            }
        }
        $result = $this->pagination($query,$this->page,$this->pageSize);
        foreach($result['data'] as & $val) {
            $specsModels = array();
            $arr = array();
            $arr['categoryId'] = $type_id;
            $arr['coinType'] = $this->getCurrencySign();

            $arr['id'] = $val['style_id'];
            $arr['goodsImages'] = ImageHelper::goodsThumbs($val['goods_image'],'mid');

            $arr['goodsName'] = $val['goods_name'];
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $arr['isJoin'] = null;
            $specsModels['SKU'] = $val['goods_sn'];
            $specsModels['clarity'] = \Yii::$app->attr->valueName($val['clarity']);
            $specsModels['carat'] = $val['carat'];
            $specsModels['card'] = \Yii::$app->attr->valueName($val['cert_type']);
            $specsModels['cardNo'] = $val['cert_id'];
            $specsModels['depth'] = $val['depth_lv'];
            $specsModels['table'] = $val['table_lv'];
            $specsModels['color'] = \Yii::$app->attr->valueName($val['color']);
            $specsModels['cut'] = \Yii::$app->attr->valueName($val['cut']);
            $specsModels['fluorescence'] = \Yii::$app->attr->valueName($val['fluorescence']);
            $specsModels['polish'] = \Yii::$app->attr->valueName($val['polish']);
            $specsModels['shape'] = \Yii::$app->attr->valueName($val['shape']);
            $specsModels['symmetry'] = \Yii::$app->attr->valueName($val['symmetry']);
            $arr['specsModels'] = $specsModels;
            $val = $arr;

        }
        return $result;

    }





    /**
     * 款式商品详情
     * @return mixed|NULL|number[]|string[]|NULL[]|array[]|NULL[][]|unknown[][][]|string[][][]|mixed[][][]|\common\helpers\unknown[][][]
     */
    public function actionDetail()
    {
//        $type_id = 15;
        $area_id = $this->getAreaId(); 
        $id = \Yii::$app->request->post("goodsId");
        $backend = \Yii::$app->request->post("backend");
        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $query = Diamond::find()->alias('m')
            ->leftJoin(DiamondLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.style_id=markup.style_id and markup.area_id='.$area_id)
            ->select(['m.*','IFNULL(markup.sale_price,m.sale_price) as sale_price','lang.goods_name', 'lang.meta_title','lang.meta_word','lang.meta_desc'])
            ->where(['m.style_id'=>$id])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);
        if($backend != 1){
            $query->andWhere(['m.status'=>StatusEnum::ENABLED]);
        }
        $model = $query->one();
        if(empty($model)) {
            return ResultHelper::api(422,"裸钻信息不存在或者已下架");
        }
        $type_id = $model->type_id;
        $diamond_array = $query->asArray()->one();


        $diamond = array();
        $diamond['id'] = $model->style_id;
        $diamond['categoryId'] = 1;
        $diamond['coinType'] = $this->getCurrencySign();
        $diamond['goodsName'] = $model->lang->goods_name;
        $diamond['goodsCode'] = $model->goods_sn;
        $diamond['salePrice'] = $this->exchangeAmount($model->sale_price,0);
        $diamond['goods3ds'] = $model->goods_3ds;
        $diamond['goodsGiaImage'] = ImageHelper::goodsThumbs($model->goods_gia_image,'big');
        $diamond['goodsImages'] = ImageHelper::goodsThumbs($model->goods_image,'big').",".ImageHelper::goodsThumbs($model->parame_images,'big');
        $diamond['goodsDesc'] = $model->lang->goods_desc;
        $diamond['goodsMod'] = 2;
        $diamond['goodsServices'] = $model->sale_services;
        $sale_services = explode(',',$model->sale_services);
        if(!empty($sale_services)){
            $goodsServicesJsons = \Yii::$app->services->goodsAttribute->getAttrValuesByValueIds($sale_services);
        }else{
            $goodsServicesJsons = [];
        }
        $diamond['goodsServicesJsons'] = $goodsServicesJsons;
        $diamond['goodsStatus'] = $model->status == 1 ? 2 : 1;
        $diamond['htmlUrl'] = '';
        $diamond['qrCode'] = '';
        $diamond['materials'] = null;
        $diamond['recommends'] = null;
        $diamond['sizes'] = null;
        $diamond['templateId'] = null;
        $diamond['metaDesc'] = $model->lang->meta_desc;
        $diamond['metaTitle'] = $model->lang->meta_title;
        $diamond['metaWord'] = $model->lang->meta_word;
        $diamond['details'] = array(
          [
              'id' => $model->goods_id,
              'barCode' => null,
              'categoryId' => $type_id,
              'goodsDetailsCode' => $model->goods_sn,
              'goodsId' => $model->style_id,
              'stock' => $model->goods_num,
              'retailMallPrice' => $this->exchangeAmount($model->sale_price,0),
              'productNumber' => null,
              'warehouse' => null,
              'material' => null,
              'size' => null,


          ]
        );


        $diamond_attr = array(
            '5'=>'carat',
            '2'=>'clarity',
            '4'=>'cut',
            '7'=>'color',
            '6'=>'shape',
            '28'=>'polish',
            '29'=>'symmetry',
            '8'=>'fluorescence',
            '48'=>'cert_type',
            '31'=>'cert_id',
            '32'=>'depth_lv',
            '36'=>'table_lv',
            '34'=>'length',
            '35'=>'width'
        );
        $diamond_attr_keys = array_keys($diamond_attr);
        $specs = array();
        $goods_attribute_spec = \Yii::$app->services->goodsAttribute->getAttrListByTypeId($type_id,1, $this->language);
        $goods_attribute_spec =  $goods_attribute_spec[1]; //基础属性

//        return $diamond_array;
        foreach ($goods_attribute_spec as $value){
            $arrt_id = $value['id'];
            if(in_array($arrt_id,$diamond_attr_keys)){
                if($value['input_type'] == 1){
                    $configAttrId = null;
                    $configAttrVal = $diamond_array[$diamond_attr[$arrt_id]];
                }else{
                    $configAttrId = $diamond_array[$diamond_attr[$arrt_id]];
                    $configAttrVal = \Yii::$app->attr->valueName($configAttrId);
                }
                $specs[] = [
                    'categoryId'=>$type_id,
                    'configAttrId'=>$configAttrId,
                    'configAttrVal'=>$configAttrVal,
                    'configId'=>$arrt_id,
                    //'configInputType'=>1,
                    'configName'=>$value['attr_name'],
                    'queryColumn'=>$diamond_attr[$value['id']],
                    'goodsId'=>$model->id,
                ];
            }

        }
        $diamond['specs'] = $specs;

        $model->goods_clicks = new Expression("goods_clicks+1");
        $model->virtual_clicks = new Expression("virtual_clicks+1");
        $model->save(false);//更新浏览量
        return $diamond;
    }


    //訂婚戒指--活动页
    public function actionWebSite(){
        $type_id = 2;
        $limit = 5;
        $language = $this->language;
        $order = 'sale_volume desc';
        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id,$limit,$order, $fields ,$language);
        $webSite = array();
        $webSite['moduleTitle'] = \Yii::t('common','最畅销订婚戒指');

        $type_id = 12; //不要改动
        $webSite['type'] = $type_id;

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
            'dsDesc' => '钻石——banner全屏',
            'dsImg' => '/adt/image1566005633802.png',
            'dsName' => '鑽石——banner全屏',
            'dsShowType' => 1,
            'tdOpenType' => 1,
            'tdStatus' => 1,
        );
        return $result;

    }



}