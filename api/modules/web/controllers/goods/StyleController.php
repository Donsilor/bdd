<?php

namespace api\modules\web\controllers\goods;

use common\enums\OrderFromEnum;
use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use common\helpers\ImageHelper;
use common\models\goods\Goods;
use common\models\goods\Style;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
use common\models\member\Member;
use common\models\order\OrderComment;
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
    protected $authOptional = ['search','web-site','detail','guess-list','comment'];


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
        $style_id = \Yii::$app->request->post("styleId");//产品线ID
        if(!$type_id && !$style_id) {
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
        if(!$style_id) {
            if(is_array($type_id)) {
                $query ->andWhere(['in','m.type_id',$type_id]);
            }else{
                $query ->andWhere(['m.type_id'=>$type_id]);
            }
        }
        else {
            if(is_array($style_id)) {
                $query ->andWhere(['in','m.id',$style_id]);
            } else {
                $query ->andWhere(['m.id'=>$style_id]);
            }
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

                    $where = ['or'];
                    $where[1] = ['and'];
                    $where2 = ['and'];
                    if(is_numeric($min_price)){
                        $min_price = $this->exchangeAmount($min_price,0, 'CNY', $this->getCurrency());
                        $where[1][] = ['>','IFNULL(markup.sale_price,m.sale_price)',$min_price];
                        $where2[] = ['>','IFNULL(goods_markup.sale_price,goods.sale_price)',$min_price];
                    }
                    if(is_numeric($max_price) && $max_price>0){
                        $max_price = $this->exchangeAmount($max_price,0, 'CNY', $this->getCurrency());
                        $where[1][] = ['<=','IFNULL(markup.sale_price,m.sale_price)',$max_price];
                        $where2[] = ['<=','IFNULL(goods_markup.sale_price,goods.sale_price)',$max_price];
                    }
                    $subQuery2 = Goods::find()
                        ->innerJoinWith('markup')
                        ->select(['goods.style_id'])
                        ->where($where2)
                        ->distinct("goods.style_id");
                    $where[2] = ['in', 'm.id', $subQuery2];
                    $query->andWhere($where);
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

            $arr['coupon'] = [
                'type_id' => $val['type_id'],//产品线ID
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

            $moduleGoods['coupon'] = [
                'type_id' => $type_id,//产品线ID
                'style_id' => $val['id'],//款式ID
                'price' => $moduleGoods['salePrice'],//价格
                'num' =>1,//数量
            ];

            $webSite['moduleGoods'][] = $moduleGoods;
        }

        CouponService::getCouponByList($this->getAreaId(), $webSite['moduleGoods']);

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

    public function actionComment()
    {
        $type_id = \Yii::$app->request->get('type_id', 0);
        $style_id = \Yii::$app->request->get('style_id', 0);

        $where = ['or'];
//        if($this->member_id)
//            $where[3]['member_id'] = $this->member_id;
        $where[1]['m.status'] = 1;
        $where[1]['platform'] = OrderFromEnum::platformToPlatforms($this->platform);
        $where[2]['m.status'] = 1;
        $where[2]['platform'] = null;

        $query = OrderComment::find()->alias('m')->select(["IFNULL(m.username, member.username) as username", 'm.images', 'm.content', 'm.grade', 'm.remark', 'm.ip', 'm.ip_location', 'm.created_at'])
            ->leftJoin(Member::tableName().' member', 'm.member_id=member.id')
            ->orderBy('m.id desc')
            ->andWhere($where)
            ->andWhere(['style_id'=>$style_id, 'type_id'=>$type_id, 'is_destroy'=>0]);

        $result = $this->pagination($query, $this->page, $this->pageSize);

        foreach ($result['data'] as &$datum) {
            $datum['username'] = $this->gr_asterisk($datum['username']);
            $datum['ip'] = preg_replace('/(\d+)([.])(\d+)([.])(\d+)([.])(\d+)/', '$1.*.*.$7', $datum['ip']);
        }

        return $result;
    }

    /**
     * 添加星号
     * @param string $str 目标字符串
     * @param int $l 左侧留存长度
     * @param int $r 右侧留存长度
     * @param int $chr_len 星号数目
     * @param string $chr 星号或者其他自定义的字符
     * @return mixed 返回
     */
    public function gr_asterisk($str = '',$l = 2,$r = 4,$chr_len = 3,$chr = '*') {
        if (empty($str)) {
            return false;
        }
        $len_min = $l + $r;
        if (mb_strlen($str, 'utf-8') <= $len_min) {
            return str_repeat($chr, $chr_len);
        }

        $new_str = mb_substr($str, 0, $l, 'utf-8') . str_repeat($chr, $chr_len) . mb_substr($str, mb_strlen($str, 'utf-8') - $r, $r);
        return $new_str;
    }
    
    // 最近访问端口
    public function actionRecentBrowsing()
    {

        $typeId = \Yii::$app->request->get('type_id');
        $styleId = \Yii::$app->request->get('style_id');
        $time = time();

        $redis = \Yii::$app->redis;
        $key = sprintf("member:id_%s:recant_browsing", $this->member_id);

        $_data = [];
        if($data_json = $redis->get($key)) {
            $_data = \GuzzleHttp\json_decode($data_json, true);
        }

        if(!empty($typeId) && !empty($styleId)) {
            $_data[] = [
                'c' => $typeId,
                's' => $styleId,
                't' => $time
            ];
        }

        $_tmps = [];
        $data = [];
        while (count($data) < 6 && ($_tmp = array_pop($_data)))
        {
            $k = sprintf("%s-%s", $_tmp['c'], $_tmp['s']);
            if(!isset($data[$k])) {
                $data[$k] = $_tmp;
                $_tmps[] = $_tmp;
            }
        }

        $redis->set($key, \GuzzleHttp\json_encode($_tmps));

        $area_id = $this->getAreaId();
        $fields = ['m.id','m.type_id','lang.style_name','m.goods_images','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $query = Style::find()->alias('m')->select($fields)
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->where(['m.status'=>StatusEnum::ENABLED])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]])
            ->orderby('m.id desc');

        $wheres = ['or'];
        foreach ($data as $datum) {
            $where = ['and'];
            $where[] = ['=', 'm.type_id', $datum['c']];
            $where[] = ['=', 'm.id', $datum['s']];
            $wheres[] = $where;
        }
        $query ->andWhere($wheres);

        $_result = $this->pagination($query, $this->page, $this->pageSize);

        $_result2 = [];
        foreach($_result['data'] as & $val) {
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

            $k = sprintf("%s-%s", $arr['categoryId'], $arr['id']);
            $_result2[$k] = $arr;
        }

        CouponService::getCouponByList($this->getAreaId(), $_result2);

        $result = [];
        foreach ($data as $k => $datum) {
            if(isset($_result2[$k]))
                $result[] = $_result2[$k];
        }

        //效验异常情况
        if(count($result)!=count($data)) {
            $redis->del($key);
        }

        //不返回第一条数据
        if(!empty($typeId) && !empty($styleId)) {
            unset($result[0]);
        }

        return $result;
    }
}