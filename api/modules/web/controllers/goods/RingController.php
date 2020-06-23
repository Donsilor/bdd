<?php

namespace api\modules\web\controllers\goods;

use common\enums\StatusEnum;
use common\helpers\ImageHelper;
use common\models\goods\Ring;
use common\models\goods\RingLang;
use common\models\goods\RingRelation;
use common\models\goods\Style;
use api\controllers\OnAuthController;
use common\helpers\ResultHelper;
use common\models\goods\StyleLang;
use common\models\goods\StyleMarkup;
use yii\base\Exception;
use yii\db\Expression;
use common\models\goods\AttributeIndex;

/**
 * Class ProvincesController
 * @package api\modules\web\controllers\goods
 */
class RingController extends OnAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = Ring::class;
    protected $authOptional = ['search','web-site','detail','guess-list'];

    /**
     * 款式商品搜索
     * @return array
     */

    public function actionSearch(){
        $sort_map = [
            "sale_price"=>'m.sale_price',//价格
            "sale_volume"=>'virtual_volume',//销量
        ];
        //$type_id = \Yii::$app->request->get("type_id", 12);//产品线ID
        $order_param = \Yii::$app->request->post("orderParam");//排序参数
        $order_type = \Yii::$app->request->post("orderType", 1);//排序方式 1-升序；2-降序;

        //排序
        $order = 'virtual_volume desc ,m.id desc';
        if(!empty($order_param)){
          $order_type = $order_type == 1? "asc": "desc";
          $order = $sort_map[$order_param]. " ".$order_type .",m.id desc";
        }


        $fields = ['m.id','m.ring_sn','lang.ring_name','m.ring_images','m.sale_price','m.ring_style','m.status','m.virtual_volume'];
        $query = Ring::find()->alias('m')->select($fields)
            ->innerJoin(RingLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'");

       $query->where(['m.status'=>StatusEnum::ENABLED]);

        //筛选条件
        $ring_style = \Yii::$app->request->post("styleValue");//对戒款式
        $begin_price = \Yii::$app->request->post("beginPrice",0);//开始价格
        $end_price = \Yii::$app->request->post("endPrice");//结束价格
        $material = \Yii::$app->request->post("materialValue");//成色Id
        if($ring_style){
            $query->andWhere(['=','m.ring_style', $ring_style]);
        }
        if($begin_price && $end_price){
            $begin_price = $this->exchangeAmount($begin_price,0, 'CNY', $this->getCurrency());
            $end_price = $this->exchangeAmount($end_price,0, 'CNY', $this->getCurrency());
            $query->andWhere(['between','m.sale_price', $begin_price, $end_price]);
        }

        if(is_numeric($material) && $material > 0){
            $query->innerJoin(RingRelation::tableName()." r", 'r.ring_id = m.id')
                ->innerJoin(AttributeIndex::tableName().' at','at.style_id=r.style_id')
                ->andWhere(['at.attr_value_id'=>$material,'m.status'=>StatusEnum::ENABLED]);
        }

        //排序
        if($order != '')  $query->orderby($order);
        $query->distinct('m.id');

        $result = $this->pagination($query,$this->page,$this->pageSize);

        foreach($result['data'] as & $val) {
            $arr = array();
            $arr['coinType'] = $this->getCurrencySign();
            $arr['id'] = $val['id'];
            $arr['ringCode'] = $val['ring_sn'];
            $arr['ringImg'] = ImageHelper::goodsThumbs($val['ring_images'],'mid');
            $arr['ringStyle'] = $val['ring_style'];
            $arr['name'] = $val['ring_name'];
            $arr['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $arr['status'] = $val['status'];
            $val = $arr;
        }
        return $result;

    }



    //訂婚戒指--活动页
    public function actionWebSite(){
        //对戒
        $type_id = 19;
        $limit = 4;
        $language = $this->language;
        $order = 'goods_clicks desc';

        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id, $limit, $order, $fields ,$language);

        $ring_web_site = array();
        $ring_web_site['moduleTitle'] = \Yii::t('common','最畅销订婚戒指');
        $ring_web_site['id'] = $type_id;
        foreach ($style_list as $val) {
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->getCurrencySign();
            $moduleGoods['ringCode'] = $val['ring_sn'];
            $moduleGoods['ringImg'] = ImageHelper::goodsThumbs($val['goods_images'],'big');
            $moduleGoods['name'] = $val['ring_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $ring_web_site['moduleGoods'][] = $moduleGoods;
        }


        $where = ['a.attr_id'=>26, 'a.attr_value_id'=>41];
        $man_web_site = $this->getAdvertStyle($where);
        $man_web_site['moduleTitle'] = \Yii::t('common','自由搭配 爱我所爱');
        $man_web_site['recommendInfo'] = 'Go for the traditional, classic wedding band, or dare to be different with a unique alternative metal wedding ring made from cobalt, tantalum or titanium.';
        $man_web_site['title'] = \Yii::t('common','男士结婚戒指');
        $man_web_site['id'] = $type_id;

        $where = ['a.attr_id'=>26, 'a.attr_value_id'=>42];
        $woman_web_site = $this->getAdvertStyle($where);
        $woman_web_site['moduleTitle'] = \Yii::t('common','自由搭配 爱我所爱');
        $woman_web_site['recommendInfo'] = 'Go for the traditional, classic wedding band, or dare to be different with a unique alternative metal wedding ring made from cobalt, tantalum or titanium.';
        $woman_web_site['title'] = \Yii::t('common','女士结婚戒指');
        $woman_web_site['id'] = $type_id;


        $result = array();
        $result['webSite'][0] = $ring_web_site;
        $result['webSite'][1] = $woman_web_site;
        $result['webSite'][2] = $man_web_site;
        $result['advert'] = array(
            'dsDesc' => '訂婚戒指——banner全屏',
            'dsImg' => '/adt/image1566979883784.png',
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
        $id = \Yii::$app->request->post("id");
        $backend = \Yii::$app->request->post("backend");

        if(empty($id)) {
            return ResultHelper::api(422,"id不能为空");
        }
        $field = ['m.id','m.status','m.ring_sn','lang.ring_name','lang.ring_body','lang.meta_title','lang.meta_desc','lang.meta_word','m.ring_sn',
            'm.ring_images','m.sale_price','m.ring_style'];
        $query = $result = Ring::find()->alias('m')->select($field)
            ->leftJoin(RingLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$this->language."'")
            ->where(['m.id'=>$id]);
        if($backend != 1){
            $query->andWhere(['m.status'=>StatusEnum::ENABLED]);
        }
        $model = $query->one();

        if(empty($model)) {
            return ResultHelper::api(422,"对戒信息不存在");
        }
        $ring = array();
        $ring['id'] = $model->id;
        $ring['name'] = $model->lang->ring_name;
        $ring['ringImg'] = ImageHelper::goodsThumbs($model->ring_images,'big');
        $ring['ringCode'] = $model->ring_sn;
        $ring['salePrice'] = $this->exchangeAmount($model->sale_price,0);
        $ring['coinType'] = $this->getCurrencySign();
        $ring['status'] = $model->status;
        $ring['metaDesc'] = $model->lang->meta_desc;
        $ring['metaTitle'] = $model->lang->meta_title;
        $ring['metaWord'] = $model->lang->meta_word;
        $ring['ringStyle'] = $model->ring_style;
        try{
            $goodsModels = array();
            $searchGoodsModels = array();

            $style_ids = RingRelation::find()
                ->where(['ring_id'=>$id])->asArray()->select(['style_id'])->all();
            foreach ($style_ids as $style_id){
                $style_id = $style_id['style_id'];
                $style = \Yii::$app->services->goods->formatStyleGoodsById($style_id, $this->language);
                $style['goodsImages'] = ImageHelper::goodsThumbs($style['goodsImages'],'mid');
                $goodsModels[] = $style;
                $searchGoods = array();
                $searchGoods['categoryId'] = $style['categoryId'];
                $searchGoods['coinType'] = $this->getCurrencySign();
                $searchGoods['goodsImages'] = $style['goodsImages'];
                $searchGoods['goodsName'] = $style['goodsName'];
                $searchGoods['id'] = $style['id'];
                $searchGoods['isJoin'] = null;
                $searchGoods['salePrice'] = $style['salePrice'];
                $searchGoods['specsModels'] = null;
                $searchGoodsModels[] = $searchGoods;
            }

            $ring['goodsModels'] = $goodsModels;
            $ring['searchGoodsModels'] = $searchGoodsModels;
            $model->goods_clicks = new Expression("goods_clicks+1");
            $model->virtual_clicks = new Expression("virtual_clicks+1");
            $model->save(false);//更新浏览量
            return $ring;
        }catch (Exception $e){
            $error = $e->getMessage();
            return ResultHelper::api(422, $error);
        }

    }


    //获取商品信息
    public function getAdvertStyle($where=null){
        $area_id = $this->getAreaId(); 
        $type_id = 2;
        $limit = 3;
        $order = 'goods_clicks desc';
        $fields =  ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $language = $this->language;
        $query = Style::find()->alias('m')
            ->leftJoin(StyleLang::tableName().' lang',"m.id=lang.master_id and lang.language='".$language."'")
            ->leftJoin(StyleMarkup::tableName().' markup', 'm.id=markup.style_id and markup.area_id='.$area_id)
            ->leftJoin(AttributeIndex::tableName().' a','a.style_id=m.id')
            ->where(['m.status'=>StatusEnum::ENABLED,'m.type_id'=>$type_id])
            ->andWhere(['or',['=','markup.status',1],['IS','markup.status',new \yii\db\Expression('NULL')]]);

        if($where){
            $query->andWhere($where);
        }
        $style_list = $query->limit($limit)->orderBy($order)->asArray()->select($fields)->all();
        $result = array();
        foreach ($style_list as $val){
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->getCurrencySign();
            $moduleGoods['goodsCode'] = $val['style_sn'];
            $moduleGoods['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'big');
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price'],0);
            $result['moduleGoods'][] = $moduleGoods;
        }
        return $result;
    }





}