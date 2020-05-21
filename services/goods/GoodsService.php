<?php

namespace services\goods;
use Yii;
use common\components\Attr;
use common\components\Service;
use common\enums\AreaEnum;
use common\enums\IsStockEnum;
use common\models\goods\Attribute;
use common\models\goods\GoodsMarkup;
use common\models\goods\Style;
use common\enums\InputTypeEnum;
use common\models\goods\Goods;
use common\helpers\ArrayHelper;
use common\models\goods\GoodsLang;
use common\enums\StatusEnum;
use common\models\goods\AttributeIndex;
use common\enums\AttrTypeEnum;
use common\models\goods\StyleLang;
use common\models\goods\Diamond;
use common\models\goods\GoodsLog;
use common\models\goods\DiamondLang;
use common\enums\DiamondEnum;
use common\enums\FrameEnum;
use common\models\goods\StyleMarkup;
use services\market\CouponService;
use function GuzzleHttp\json_encode;
use yii\db\Expression;


/**
 * Class GoodsService
 * @package services\common
 */
class GoodsService extends Service
{    
    
    /**
     * 款式信息 分解 同步到goods
     * @param int $style_id
     * @param Goods $goodsModel
     */
    public function syncStyleToGoods($style_id){
        
        $styleModel = Style::find()->where(['id'=>$style_id])->one();
        $spec_array = json_decode($styleModel->style_spec,true);
        if(!empty($spec_array['c'])){
            $goods_list = $spec_array['c'];
            //$specb_list = $spec_array['b'];
        }else{
            $goods_list = [
                 [
                     'goods_sn' =>$styleModel->style_sn,
                     'sale_price' =>$styleModel->sale_price,
                     'cost_price' =>$styleModel->cost_price,
                     'market_price' =>$styleModel->market_price,
                     'goods_storage' =>$styleModel->goods_storage,
                     'status' =>$styleModel->status,  
                 ]                
            ];
        }
        $default_data = $this->formatStyleAttrs($styleModel,true);
        //款式商品属性索引表 更新入库
        $attr_index_list = $default_data['attr_index']??[];
        AttributeIndex::deleteAll(['style_id'=>$styleModel->id]);
        foreach ($attr_index_list as $attributes){
              $model = new AttributeIndex();
              $attributes['style_id'] = $styleModel->id;
              $attributes['type_id'] = $styleModel->type_id;
              $model->attributes = $attributes;
              $model->save(false);
        }
        //先标注所有sku已删除
        Goods::updateAll(['status'=>StatusEnum::DELETE],['style_id'=>$style_id]);
        //商品更新
        $sale_prices= [];
        foreach ($goods_list as $key=>$goods){
            //禁用没有填写商品编号的，过滤掉
            if(empty($goods['goods_sn']) && empty($goods['status'])){
                continue;
            }
            $goodsModel = Goods::find()->where(['style_id'=>$style_id,'spec_key'=>$key])->one();
            if(!$goodsModel || empty($goods['goods_sn'])) {
                //新增
                $goodsModel = new Goods();
            }
            $goodsModel->style_id = $styleModel->id;//款式ID
            $goodsModel->type_id  = $styleModel->type_id;//产品线ID
            $goodsModel->goods_image  = $styleModel->style_image;//商品默认图片
            $goodsModel->goods_sn = $goods['goods_sn'];//商品编码            
            $goodsModel->sale_price = $goods['sale_price']??0;//销售价 
            $goodsModel->market_price = $goods['market_price']??0; //成本价
            $goodsModel->cost_price = $goods['cost_price']??0;//成本价
            $goodsModel->goods_storage = $goods['goods_storage']??0;//库存
            $goodsModel->status = $goods['status'] ?? StatusEnum::DISABLED;//上下架状态 
            $goodsModel->spec_key = $key;
            
            if(!empty($default_data['style_spec_b'][$key])){
                $goods_specs = $default_data['style_spec_b'][$key];
                $goodsModel->goods_spec = json_encode($goods_specs['spec_keys']);
            }

            $goodsModel->save(false);  
            $sale_prices[] = $goodsModel->sale_price;

        }
        //更新最小价格
        $min_sale_price = min($sale_prices);
        if($min_sale_price > 1) {
            $styleModel->sale_price = $min_sale_price;
        }
        $styleModel->save(false);
        //计算更新商品加价销售价
        \Yii::$app->services->salepolicy->syncGoodsMarkup($style_id);
    }
    /**
    * 款式属性格式化
    * @param Style $styleModel  款式model实例
    * @param string $is_attrindex 是否属性索引 
    * @param string $language 语言
    * @return array[]|string[][]|unknown[][]|\common\helpers\unknown[][]|unknown[]
    */
    public function formatStyleAttrs($styleModel, $is_attrindex= false , $language= null)
    {
        $type_id = $styleModel->type_id;
        $style_attr = json_decode($styleModel->style_attr,true);
        $style_spec = json_decode($styleModel->style_spec,true);
        $spec_array = array();
        if(!empty($style_attr)) {
            $spec_array['style_attr'] = $style_attr;
        }
        if(!empty($style_spec['a'])) {
            $spec_array['style_spec_a'] = $style_spec['a'];
        }
        if(!empty($style_spec['b'])) {
            $spec_array['style_spec_b'] = $style_spec['b'];
        }
        if(!empty($style_spec['c'])) {
            $spec_array['style_spec_c'] = $style_spec['c'];
        }
        $format_data = [];
        foreach ($spec_array as $key =>$spec){
            
            if($key == 'style_spec_b' || $key == 'style_spec_c'){
                $format_data[$key] = $spec;
                continue;
            }else {
                $attr_ids = array_keys($spec);
                $attr_list = \Yii::$app->services->goodsAttribute->getSpecAttrList($attr_ids,$type_id,1,$language);
                foreach ($attr_list as $attr){
                    $attr_id = $attr['id'];
                    $is_text = InputTypeEnum::isText($attr['input_type']);
                    $is_single = InputTypeEnum::isSingle($attr['input_type']);
                    //$attr['is_text'] = $is_text;
                    //$attr['is_single'] = $is_single;
                    $attr['value_id'] = 0;
                    $attr['value'] = $spec[$attr_id];
                    $attr['all'] = [];
                    if(!$is_text){
                        $attr['value_id'] = $spec[$attr_id];//属性值ID列表
                        $attr['value'] = \Yii::$app->services->goodsAttribute->getValuesByValueIds($attr['value_id'],$language);
                        $attr['all'] = \Yii::$app->services->goodsAttribute->getValuesByAttrId($attr_id,StatusEnum::ENABLED,$language);
                    }
                    $format_data[$key][$attr['id']] = $attr;
                }
            }

        }
        $style_spec_a = $format_data['style_spec_a'] ??[];
        $style_spec_b = $spec_array['style_spec_b'] ??[];
        if(!empty($style_spec_a)) {
            //处理style_spec_b
            $attr_map = array_column($style_spec_a,'attr_name','id');
            $value_map  = array_column($style_spec_a,'all','id');
            $value_map = ArrayHelper::multiToArray($value_map);
            foreach ($style_spec_b as $key=>$spec){
                $attr_ids = explode(',',$spec['ids']);
                $value_ids = explode(',',$spec['vids']);
                $spec_name = [];
                $spec_value = [];
                foreach ($attr_ids as $attr_id){                
                    $spec_name[$attr_id] = $attr_map[$attr_id]??'';
                }                
                foreach ($value_ids as $k=>$value_id){
                    $spec_value[$value_id] = $value_map[$value_id]??'';
                }
                $spec_keys = array_combine($attr_ids,$value_ids);
                $spec_names = array_combine($spec_name, $spec_value);
                $format_data['style_spec_b'][$key] = [
                       'spec_name'=>$spec_name,
                       'spec_value'=>$spec_value,
                       'spec_keys'=> $spec_keys,
                       'spec_names'=> $spec_names,
                ];
            }
        }
        if($is_attrindex == true) {
            //属性索引
            $format_data['attr_index'] = $this->formatGoodsAttrIndex($format_data);
        }
        return $format_data;
    } 
    /**
     * 款式属性索引格式化
     * @param array $data
     */
    public function formatGoodsAttrIndex($data)
    {
        $index_list = [];
        if(!empty($data['style_attr']) && is_array($data['style_attr'])) {
            foreach ($data['style_attr'] as $attr){
                $attr_list = [];
                if(is_array($attr['value'])){
                    foreach ($attr['value'] as $val_id=>$val_name){
                        $index_list[] = [
                                'attr_name'=>$attr['attr_name'],
                                'attr_id' =>$attr['id'],
                                'attr_type'=>$attr['attr_type'],
                                'attr_value_id'=>$val_id,
                                'attr_value'=> null,
                        ];
                    }                    
                }else if(trim($attr['value']) != ''){
                    $index_list[] = [
                            'attr_name'=>$attr['attr_name'],
                            'attr_id' =>$attr['id'],
                            'attr_type'=>$attr['attr_type'],
                            'attr_value_id'=>$attr['value_id'],
                            'attr_value'=>$attr['value'],
                    ];
                }
            }
            
        }
        
        if(!empty($data['style_spec_b']) && is_array($data['style_spec_b'])) {
            foreach ($data['style_spec_b'] as $key=>$attr){
                $goods = $data['style_spec_c'][$key];
                if(empty($goods['status']) || empty($goods['goods_sn'])){
                    continue;
                }
                if(is_array($attr['spec_keys'])){
                    foreach ($attr['spec_keys'] as $attr_id=>$val_id){
                        $index_list['spec_'.$attr_id.'_'.$val_id] = [
                                'attr_name'=>$attr['spec_name'][$attr_id],
                                'attr_id' =>$attr_id,
                                'attr_type'=>AttrTypeEnum::TYPE_SALE,
                                'attr_value_id'=>$val_id,
                                'attr_value'=> null,
                        ];
                    }                    
                }
            }
            
        }
        
        return $index_list;
    }
    /**
     * 查询商品详情
     * @param unknown $goods_id
     * @param number $goods_type
     */
    public function getGoodsInfo($goods_id , $goods_type = 0, $format_attr = true, $language= null, $area_id = null)
    {
        if(!$language) {
            $language = \Yii::$app->params['language'];
        }
        if(!$area_id) {
            $area_id = $this->getAreaId(); 
        }
        //如果是裸钻
        if($goods_type == \Yii::$app->params['goodsType.diamond']) {
            $goods = Diamond::find()->alias('g')
                        ->select(['g.*','IFNULL(m.sale_price,g.sale_price) as sale_price','if(markup.status=0 or m.status =0,0,g.status) as status','g.goods_sn as style_sn','lang.goods_name','lang.goods_body','g.goods_num as goods_storage'])
                        ->innerJoin(DiamondLang::tableName().' lang',"g.id=lang.master_id and lang.language='{$language}'")
                        ->leftJoin(StyleMarkup::tableName().' markup', 'g.style_id=markup.style_id and markup.area_id='.$area_id)
                        ->leftJoin(GoodsMarkup::tableName().' m','g.goods_id=m.goods_id and m.area_id='.$area_id)
                        ->where(['g.goods_id'=>$goods_id])
                        ->asArray()->one();
            if($goods) {
                 $goods_attr = [
                        DiamondEnum::CARAT=>$goods['carat'],
                        DiamondEnum::COLOR=>$goods['color'],
                        DiamondEnum::CLARITY=>$goods['clarity'],
                        DiamondEnum::CUT=>$goods['cut']                                
                ];
                $goods['goods_attr'] = json_encode($goods_attr); 
                $goods['goods_spec'] = null;
                $goods['id'] = $goods['goods_id'];
            }
            
        }else {
            $query = Goods::find()->alias('g')
            ->select(['g.*','sl.style_name as goods_name','IFNULL(m.sale_price,g.sale_price) as sale_price','if(markup.status=0 or m.status =0 or g.status =0,0,s.status) as status','s.style_sn','s.status as style_status','sl.goods_body','s.style_attr as goods_attr'])
                    ->innerJoin(Style::tableName()." s","g.style_id=s.id")
                    ->innerJoin(StyleLang::tableName()." sl","s.id=sl.master_id and sl.language='{$language}'")
                    ->leftJoin(StyleMarkup::tableName().' markup', 's.id=markup.style_id and markup.area_id='.$area_id)
                    ->leftJoin(GoodsMarkup::tableName().' m','g.id=m.goods_id and m.area_id='.$area_id)
                    ->where(['g.id'=>$goods_id]);
            
            $goods = $query->asArray()->one();
            $goods['status'] = $goods['style_status'] == StatusEnum::ENABLED ? $goods['status']:StatusEnum::DISABLED;
       }  
       
       if(!empty($goods) && $format_attr == true ) { 
           
           $goods['lang'] = [
                 'goods_attr' => $this->formatGoodsAttr($goods['goods_attr'], $goods['type_id'],$language),
                 'goods_spec' => $this->formatGoodsSpec($goods['goods_spec'], $language)
           ];
       }
       return $goods;
    }
    /**
     * 格式化商品属性
     * @param array $goods_attr
     * @param array $goods_type
     * @param string $language
     * @return array
     */
    public function formatGoodsAttr($goods_attr, $goods_type, $language = null) 
    {
        if(!$language) {
            $language = \Yii::$app->params['language'];
        }
        
        if(!is_array($goods_attr)) {
            $goods_attr = json_decode($goods_attr,true);
        }
        if(!is_array($goods_attr)) {
            return [];
        }
        $attr_ids = array_keys($goods_attr);
        $attr_list = \Yii::$app->services->goodsAttribute->getSpecAttrList($attr_ids,$goods_type,StatusEnum::ENABLED,$language);
        
        $attr_data = [];
        foreach ($attr_list as $attr){
            $attr_id = $attr['id'];
            $value = $goods_attr[$attr_id];
            if($value == ""){
                continue;
            }
            $is_text = InputTypeEnum::isText($attr['input_type']);
            $is_single = InputTypeEnum::isSingle($attr['input_type']);            
            if(!$is_text){
                if(is_array($value)) {
                    foreach ($value as $value_id) {
                        $attr['value'][$value_id] = \Yii::$app->attr->valueName($value_id ,$language);
                    }
                }else {
                    $attr['value'][$value] = \Yii::$app->attr->valueName($value ,$language);
                }
            } else {
                $attr['value'] = [$attr_id=>$value];
            }
            $attr_data[$attr_id] = $attr;
        }
        return $attr_data;  
    }
    /**
     * 获取商品规格属性
     * @param unknown $goods_spec
     * @param unknown $language
     * @return unknown[][]|mixed[][]|boolean[][]|string[][]
     */
    public function formatGoodsSpec($goods_spec, $language = null)
    {
        if(!is_array($goods_spec)) {
            $goods_spec = json_decode($goods_spec,true);
        }
        if(!is_array($goods_spec)) {
            return [];
        }
        $spec_data = [];
        foreach ($goods_spec as $attr_id=>$value_id){
            $attr_name = \Yii::$app->attr->attrName($attr_id ,$language);
            $value_name = \Yii::$app->attr->valueName($value_id ,$language);
            $spec_data[] = [
                    'attr_id'=>$attr_id,
                    'value_id'=>$value_id,
                    'attr_name'=>$attr_name,
                    'attr_value'=>$value_name,
            ];
        }
        return $spec_data;
    }
    /**
     * 规格属性合并
     * @param unknown $goods_attr
     * @param unknown $goods_spec
     * @param unknown $goods_type
     * @param unknown $language
     */
    public function formatGoodsAttrAndSpec($goods_attr, $goods_spec, $goods_type, $language = null) 
    {
        $data = array();
        $goods_attr = $this->formatGoodsAttr($goods_attr, $goods_type,$language);
        foreach ($goods_attr as $vo) {
            $data['goods_attr'][$vo['id']] = [
                'attr_id' =>$vo['id'],
                'value_id'=>implode("/", array_keys($vo['value'])),
                'attr_name'=>$vo['attr_name'],
                'attr_value'=>implode("/", $vo['value'])                    
            ];
        }
        $data['goods_spec'] = $this->formatGoodsSpec($goods_spec,$language);
        return $data;
    }
    
    /**
     * 获取款和对应的商品 （前端api专用）
     * @param unknown $style_id
     * @param unknown $language
     * @return 
     */
    public function formatStyleGoodsById($style_id, $language = null, $area_id=null){

        $ip = \Yii::$app->request->userIP;
        if(empty($area_id)){
            $area_id = $this->getAreaId(); 
        }

        if(empty($language)){
            $language = \Yii::$app->params['language'];
        }
        $style_spec_array = [
            '10' =>array(
                 'attr_name'=>'materials',
                 'key_name'=>'material',
             ),//成色
            '38'=>array(
                'attr_name'=>'sizes',
                'key_name'=>'size',
            ),  // 尺寸
            '59'=>array(
                'attr_name'=>'carats',
                'key_name'=>'carat',
            ),  // 主石大小
        ];
        $query = Style::find()->alias('m')
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.id'=>$style_id])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);
        $style_model =  $query->one();
        $format_style_attrs = $this->formatStyleAttrs($style_model);
//        return $format_style_attrs;
        $model = $query ->select(['m.id','m.style_sn','m.status','m.goods_images','m.type_id','m.style_3ds','m.style_image','IFNULL(markup.sale_price,m.sale_price) as sale_price','lang.goods_body','lang.style_name','lang.meta_title','lang.meta_word','lang.meta_desc'])
            ->asArray()->one();

        //规格属性
        $style = array();
        $style['id'] = $model['id'];
        $style['goodsName'] = $model['style_name'];
        $style['goodsCode'] = $model['style_sn'];
        $style['goodsImages'] = $model['goods_images'];
        $style['salePrice'] = $this->exchangeAmount($model['sale_price'],0);
        $style['coinType'] = $this->getCurrencySign();
        $style['goods3ds'] = $model['style_3ds'];
        $style['goodsDesc'] = $model['goods_body'];
        $style['categoryId'] = $model['type_id'];
        $style['goodsGiaImage'] = null;
        $style['goodsMod'] = $model['type_id'] == 12 ? 1: 2;
        $style['goodsStatus'] = $model['status']== 1? 2:1;
        $style['htmlUrl'] = null;
        $style['metaDesc'] = $model['meta_desc'];
        $style['metaTitle'] = $model['meta_title'];
        $style['metaWord'] = $model['meta_word'];
        $style['qrCode'] = '';
        $style['recommends'] = null;
        $style['templateId'] = null;
        $style['ip'] = $ip;
        $style['area_id'] = $area_id;

//        if(isset($format_style_attrs['style_spec_a'])){
//            $format_style_spec = $format_style_attrs['style_spec_a'];
//            foreach ($style_spec_array as $key => $val){
//                if(isset($format_style_spec[$key]['value'])){
//                    foreach ($format_style_spec[$key]['value'] as $k => $v){
//                        $attr = array();
//                        $attr['id'] = $k;
//                        $attr['image'] = \Yii::$app->services->goodsAttribute->getAttrImageByValueId($k);
//                        $attr['name'] = $v;
//                        $style[$val['attr_name']][] = $attr;
//                    }
//                }else{
//                    $style[$val['attr_name']] = null;
//                }
//            }
//        }

        //基础属性
        if(isset($format_style_attrs['style_attr'])){
            $style_attr = $format_style_attrs['style_attr'];
            foreach ($style_attr as $attr){
                //对售後服務特殊处理
                if($attr['id'] == 52){
                    if(isset($attr['value_id']) && is_array($attr['value_id'])){
                        $style['goodsServices'] = join(',', $attr['value_id']);
                    }else{
                        continue;
                    }
                    $style['goodsServicesJsons'] = \Yii::$app->services->goodsAttribute->getAttrValuesByValueIds($attr['value_id']);
                    continue;
                }

                $attr_value = $attr['value'];
                $attr_value_id = $attr['value_id'];
                if(empty($attr_value)) {
                    continue;
                }
                if(is_array($attr_value)){
                    $attr_value = implode('|',$attr_value);
                }
                if(is_array($attr_value_id)){
                    $attr_value_id = implode('|',$attr_value_id);
                }
                $style['specs'][] = [
                    'categoryId'=>$attr['attr_type'],
                    'configAttrId'=>$attr_value_id,
                    'configId'=>$attr['id'],
                    //'configInputType'=>$attr['input_type'],
                    'configName'=>$attr['attr_name'],
                    'configAttrVal'=>$attr_value,
                    'goodsId'=>$style_id,
                   // 'configRequired'=>$attr['is_require'],
                   //'queryColumn'=>null,
                   //'id'=>'',
                   //'sort'=>null
                ];
            }
        }

        //商品

        $goods_array = Goods::find()->alias('g')
            ->leftJoin(GoodsMarkup::tableName().' markup', 'g.id=markup.goods_id and markup.area_id='.$area_id)
            ->where(['g.style_id'=>$style_id ,'g.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
            ->select(['g.id','type_id','goods_sn','IFNULL(markup.sale_price,g.sale_price) as sale_price','goods_storage','warehouse','goods_spec'])
            ->asArray()
            ->all();
        $details = array();
        $totalStock = 0;

        $check_goods_spec_ids = array();
        foreach ($goods_array  as $key => $val){
            $goods_spec = json_decode($val['goods_spec']);
            $goods_spec = (array)$goods_spec;
            foreach ($style_spec_array as $k => $v){
                if(isset($goods_spec[$k])){
                    $goods_spec_id = (int)$goods_spec[$k];

                    if(!in_array($goods_spec_id,$check_goods_spec_ids)){
                        $check_goods_spec_ids[] = $goods_spec_id;
                        $attr = array();
                        $attr['id'] = $goods_spec_id;
                        $attr['image'] = \Yii::$app->services->goodsAttribute->getAttrImageByValueId($goods_spec_id);
                        $attr['name'] = \Yii::$app->attr->valueName($goods_spec_id);
                        $style[$v['attr_name']][] = $attr;
                    }


                    $details[$key][$v['key_name']] = $goods_spec_id;
                }else{
                    $details[$key][$v['key_name'] ]= null;
                }
            }


//            if(!empty($goods_spec)){
//                foreach ($goods_spec as $k=>$v){
//                    $details[$key][$style_spec_array[$k]['key_name']] = (int)$v;
//                }
//            }

            $details[$key]['barCode'] = null;
            $details[$key]['productNumber'] = null;
            $details[$key]['stock'] = $val['goods_storage'];
            $details[$key]['warehouse'] = $val['warehouse'];
            $details[$key]['categoryId'] = $model['type_id'];
            $details[$key]['goodsDetailsCode'] = $val['goods_sn'];
            $details[$key]['retailMallPrice'] = (float)$this->exchangeAmount($val['sale_price'],0);
            $details[$key]['retailPrice'] = null;
            $details[$key]['goodsId'] = $style_id;
            $details[$key]['id'] = $val['id'];
            $totalStock += $val['goods_storage'];

        }

        //对尺寸进行排序
        if(isset($style['sizes'])){
            $names = array_column($style['sizes'],'name');
            array_multisort($names,SORT_ASC,$style['sizes']);
        }




        $style['details'] = $details;
        $style['totalStock'] = $totalStock;

        $style['coupon'] = CouponService::getCouponByStyleInfo($this->getAreaId(), $style['categoryId'], $style['id'], $style['salePrice']);

        return $style;

    }    
    /**
     * 下单商品库存更改
     * @param unknown $goods_id  商品ID
     * @param unknown $quantity  变化数量
     * @param unknown $for_sale 销售
     */
    public function updateGoodsStorageForOrder($goods_id,$quantity,$goods_type)
    {        
        if($goods_type == \Yii::$app->params['goodsType.diamond']){
            \Yii::$app->services->diamond->updateGoodsStorageForOrder($goods_id, $quantity);
        }else {
            $data = [
                'goods_storage'=> new Expression("goods_storage+({$quantity})"),
                'sale_volume'  =>new Expression("sale_volume-({$quantity})")
            ];            
            Goods::updateAll($data,['id'=>$goods_id]);
            Style::updateAll($data,['in','id',Goods::find()->select(['style_id'])->where(['id'=>$goods_id])]);
        }
    }

    /**
     * 操作日志
     * @param unknown $old_goods_info  操作前数据
     * @param unknown $new_goods_info  操作后数据
     */
    public function recordGoodsLog($new_goods_info, $old_goods_info){
        $not_labdata = ['onsale_time','sale_services','goods_3ds','parame_images','goods_gia_image', 'updated_at', 'goods_storage', 'style_attr', 'goods_images'];
        $new_goods = ArrayHelper::toArray($new_goods_info);
        $diff_info = array_diff_assoc($new_goods, $old_goods_info);
        $diamondModel = new Diamond();
        $styleModel = new Style();
        $goodsLogModel = new GoodsLog();
        $log_msg = "";
        if(!empty($diff_info)){
            $diamond_attrLab = $diamondModel->attributeLabels();
            $style_attrLab = $styleModel->attributeLabels();
            foreach ($diff_info as $k => $new_val) {
                if(in_array($k, $not_labdata)) continue;
                if(isset($diamond_attrLab[$k])) {
                    $lab = $diamond_attrLab[$k];
                }else {
                    $lab = isset($style_attrLab[$k])?$style_attrLab[$k]:"";
                }
                $old_val = $old_goods_info[$k];
                if('status' == $k ){
                    $old_val = FrameEnum::getValue($old_val);
                    $new_val = FrameEnum::getValue($new_val);
                }
                if('is_stock' == $k){
                    $old_val = IsStockEnum::getValue($old_val);
                    $new_val = isStockEnum::getValue($new_val);
                }
                if('cert_type' == $k){
                    $cert_type = DiamondEnum::getCertTypeList();
                    $old_val = isset($cert_type[$old_val])?$cert_type[$old_val]:'';
                    $new_val = isset($cert_type[$new_val])?$cert_type[$new_val]:'';
                }
                if('shape' == $k){
                    $shape = DiamondEnum::getshapeList();
                    $old_val = isset($shape[$old_val])?$shape[$old_val]:'';
                    $new_val = isset($shape[$new_val])?$shape[$new_val]:'';
                }
                if('color' == $k){
                    $color = DiamondEnum::getcolorList();
                    $old_val = isset($color[$old_val])?$color[$old_val]:'';
                    $new_val = isset($color[$new_val])?$color[$new_val]:'';
                }
                if('clarity' == $k){
                    $clarity = DiamondEnum::getclarityList();
                    $old_val = isset($clarity[$old_val])?$clarity[$old_val]:'';
                    $new_val = isset($clarity[$new_val])?$clarity[$new_val]:'';
                }
                if('cut' == $k){
                    $cut = DiamondEnum::getcutList();
                    $old_val = isset($cut[$old_val])?$cut[$old_val]:'';
                    $new_val = isset($cut[$new_val])?$cut[$new_val]:'';
                }
                if('polish' == $k){
                    $polish = DiamondEnum::getpolishList();
                    $old_val = isset($polish[$old_val])?$polish[$old_val]:'';
                    $new_val = isset($polish[$new_val])?$polish[$new_val]:'';
                }
                if('symmetry' == $k){
                    $symmetry = DiamondEnum::getsymmetryList();
                    $old_val = isset($symmetry[$old_val])?$symmetry[$old_val]:'';
                    $new_val = isset($symmetry[$new_val])?$symmetry[$new_val]:'';
                }
                if('fluorescence' == $k){
                    $fluorescence = DiamondEnum::getfluorescenceList();
                    $old_val = isset($fluorescence[$old_val])?$fluorescence[$old_val]:'';
                    $new_val = isset($fluorescence[$new_val])?$fluorescence[$new_val]:'';
                }
                if('stone_floor' == $k){
                    $stone_floor = DiamondEnum::getstonefloorList();
                    $old_val = isset($stone_floor[$old_val])?$stone_floor[$old_val]:'';
                    $new_val = isset($stone_floor[$new_val])?$stone_floor[$new_val]:'';
                }
                if(in_array($k, array('style_spec', 'sale_policy', 'style_salepolicy', 'goods_salepolicy'))){
                    $old_val = ArrayHelper::toArray(\Qiniu\json_decode($old_val));
                    $new_val = ArrayHelper::toArray(\Qiniu\json_decode($new_val));
                    $area_arr = AreaEnum::getMap();
                    if('style_spec' == $k){
                        $old_val = $old_val['c'];
                        $new_val = $new_val['c'];
                    }
                    $old_arr = [];
                    if(!empty($old_val)){
                        foreach ($old_val as $i => $item) {
                            unset($item['sale_price']);
                            $old_arr[$i] = json_encode($item);
                        }
                    }
                    $new_arr = [];
                    if(!empty($new_val)){
                        foreach ($new_val as $i => $item) {
                            unset($item['sale_price']);
                            $new_arr[$i] = json_encode($item);
                        }
                    }
                    $diff_val = array_diff_assoc($new_arr, $old_arr);
                    $diff_arr = ArrayHelper::toArray($diff_val);
                    $diff_val_fan = array_diff_assoc($old_arr, $new_arr);
                    $diff_arr_fan = ArrayHelper::toArray($diff_val_fan);
                    $diff_arr_all = array_merge($diff_arr, $diff_arr_fan);
                    if('goods_salepolicy' == $k){
                        foreach ($diff_arr as $i => $item) {
                            $old_item = ArrayHelper::toArray(\Qiniu\json_decode($old_arr[$i]));
                            $new_item = ArrayHelper::toArray(\Qiniu\json_decode($new_arr[$i]));
                            $oldinfo = [];
                            if(!empty($old_item)){
                                foreach ($old_item as $j => $info) {
                                    unset($info['markup_rate'], $info['markup_value']);
                                    $oldinfo[$j] = json_encode($info);
                                }
                            }
                            $newinfo = [];
                            if(!empty($new_item)){
                                foreach ($new_item as $j => $info) {
                                    unset($info['markup_rate'], $info['markup_value']);
                                    $newinfo[$j] = json_encode($info);
                                }
                            }

                            $diff_data = array_diff_assoc($newinfo, $oldinfo);
                            $log_msg_goods = '';
                            if(!empty($diff_data)){
                                foreach ($diff_data as $goods_id => $josn_info) {
                                    $area_status = \Qiniu\json_decode($josn_info);
                                    $goods_sn = Goods::find()->select('goods_sn')->where(['id' => $goods_id])->asArray()->all();
                                    if(empty($goods_sn)){
                                        continue;
                                    }
                                    if(!isset($new_item[$goods_id]) && isset($old_item[$goods_id])){
                                        $log_msg_goods .= $area_arr[$area_status->area_id] . "：货号" . $goods_sn[0]['goods_sn'] . " 删除,";
                                        continue;
                                    }
                                    if(!isset($old_item[$goods_id]) && isset($new_item[$goods_id])){
                                        $log_msg_goods .= $area_arr[$area_status->area_id] . "：货号" . $goods_sn[0]['goods_sn'] . " 新增,";
                                        continue;
                                    }
                                    $new_stauts = isset($new_item[$goods_id]['status'])?$new_item[$goods_id]['status']:'';
                                    $old_stauts = isset($old_item[$goods_id]['status'])?$old_item[$goods_id]['status']:'';
                                    if ($new_stauts != $old_stauts) {
                                        $log_msg_goods .= $area_arr[$area_status->area_id] . "：货号" . $goods_sn[0]['goods_sn'] . " 状态：\"" . StatusEnum::getValue($old_stauts) . "\" 变更为 " . StatusEnum::getValue($new_stauts) . ",";
                                    }
                                }
                            }
                            if($log_msg_goods != ''){
                                $log_msg.="[商品地区价格]：".$log_msg_goods;
                            }
                        }
                    }else{
                        if(!empty($diff_arr_all)) $log_msg .= "[".$lab."]：";
                        $log_goods_add= '';
                        $log_goods_del= '';
                        if(!empty($diff_arr_all)){
                            foreach ($diff_arr_all as $i => $item) {
                                $obj = \Qiniu\json_decode($item);
                                if(!isset($old_val[$i]) && isset($new_val[$i])){
                                    $log_goods_add.=$new_val[$i]['goods_sn'].",";
                                    continue;
                                }
                                if(!isset($new_val[$i]) && isset($old_val[$i])){
                                    $log_goods_del.=$old_val[$i]['goods_sn'].",";
                                    continue;
                                }
                                $log_msg_lsit = '';
                                $new_status = isset($new_val[$i]['status'])?$new_val[$i]['status']:'';
                                $old_status = isset($old_val[$i]['status'])?$old_val[$i]['status']:'';
                                if('sale_policy' == $k || 'style_salepolicy' == $k){
                                    $new_markup_rate = isset($new_val[$i]['markup_rate'])?$new_val[$i]['markup_rate']:'';
                                    $old_markup_rate = isset($old_val[$i]['markup_rate'])?$old_val[$i]['markup_rate']:'';
                                    $new_markup_value = isset($new_val[$i]['markup_value'])?$new_val[$i]['markup_value']:'';
                                    $old_markup_value = isset($old_val[$i]['markup_value'])?$old_val[$i]['markup_value']:'';
                                    if($new_markup_rate != $old_markup_rate){
                                        $log_msg_lsit.="加价率：\"".$old_markup_rate."\" 变更为 ".$new_markup_rate.",";
                                    }
                                    if($new_markup_value != $old_markup_value){
                                        $log_msg_lsit.="固定值：\"".$old_markup_value."\" 变更为 ".$new_markup_value.",";
                                    }
                                    if($new_status != $old_status){
                                        $log_msg_lsit.="状态：\"".StatusEnum::getValue($old_status)."\" 变更为 ".StatusEnum::getValue($new_status).",";
                                    }
                                    if($log_msg_lsit != ''){
                                        $log_msg.= $k == 'sale_policy' ? $obj->area_name."：" : $area_arr[$obj->area_id]."：";
                                        $log_msg.= '【'.rtrim($log_msg_lsit, ',');
                                    }
                                }else{
                                    $new_sale_price = isset($new_val[$i]['sale_price'])?$new_val[$i]['sale_price']:'';
                                    $old_sale_price = isset($old_val[$i]['sale_price'])?$old_val[$i]['sale_price']:'';
                                    $new_goods_storage = isset($new_val[$i]['goods_storage'])?$new_val[$i]['goods_storage']:'';
                                    $old_goods_storage = isset($old_val[$i]['goods_storage'])?$old_val[$i]['goods_storage']:'';
                                    if($new_sale_price != $old_sale_price){
                                        $log_msg_lsit.="销售价：\"".$old_sale_price."\" 变更为 ".$new_sale_price.",";
                                    }
                                    if($new_goods_storage != $old_goods_storage){
                                        $log_msg_lsit.="库存：\"".$old_goods_storage."\" 变更为 ".$new_goods_storage.",";
                                    }
                                    if($new_status != $old_status){
                                        $log_msg_lsit.="状态：\"".StatusEnum::getValue($old_status)."\" 变更为 ".StatusEnum::getValue($new_status).",";
                                    }
                                    if($log_msg_lsit != ''){
                                        $log_msg.='【'.$obj->goods_sn."：".rtrim($log_msg_lsit, ',');
                                    }
                                }
                            }
                        }
                        if($log_goods_add != ''){
                            $log_msg.="新增库存编号：".rtrim($log_goods_add, ',')."；";
                        }
                        if($log_goods_del != ''){
                            $log_msg.="删除库存编号：".rtrim($log_goods_del, ',')."；";
                        }
                    }
                    if($log_msg != '') {
                        $log_msg = rtrim($log_msg,',')."；";
                        $log_msg = rtrim($log_msg,'；')."；";
                    }
                }else{
                    $log_msg.="[".$lab."]：\"".$old_val."\" 变更为：".$new_val."；";
                }
            }
        }
        if(!empty($log_msg)){
            $goodsLogModel->type_id = $new_goods_info->type_id;
            $goodsLogModel->goods_id = $new_goods_info->id;
            $goodsLogModel->log_msg = $log_msg;
            $goodsLogModel->log_time = time();
            $goodsLogModel->log_role = 'buyer';
            $goodsLogModel->log_user = Yii::$app->user->identity->username;
            $goodsLogModel->save();
        }
    }

    /**
     * 货品状态变更日志
     * @param unknown $goods_info  商品信息
     * @param unknown $new_status  变化状态
     */
    function recordGoodsStatus($goods_info, $new_status){

        $log_msg = "[上架状态]：变更为 ".FrameEnum::getValue($new_status);
        $goodsLogModel = new GoodsLog();
        $goodsLogModel->type_id = $goods_info->type_id;
        $goodsLogModel->goods_id = $goods_info->id;
        $goodsLogModel->log_msg = $log_msg;
        $goodsLogModel->log_time = time();
        $goodsLogModel->log_role = 'buyer';
        $goodsLogModel->log_user = Yii::$app->user->identity->username;
        $goodsLogModel->save();
    }
}