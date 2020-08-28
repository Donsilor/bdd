<?php

namespace api\modules\web\controllers\member;

use common\enums\OrderFromEnum;
use common\helpers\ImageHelper;
use common\models\goods\Ring;
use common\models\goods\RingLang;
use common\models\order\OrderCart;
use api\modules\web\forms\CartForm;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
use services\market\CouponService;
use yii\base\Exception;
use yii\web\UnprocessableEntityHttpException;

/**
 * 购物车
 *
 * Class SiteController
 * @package api\modules\v1\controllers
 */
class CartController extends UserAuthController
{

    /**
     * @var OrderCart
     */
    public $modelClass = OrderCart::class;
    
    protected $authOptional = ['local'];

    /**
     * 购物车列表     
     */
    public function actionIndex()
    {
        $id = \Yii::$app->request->get('id');
        
        $query = $this->modelClass::find()->where(['member_id'=>$this->member_id,'status'=>1]);

        if(!empty($id) && $id = explode(',',$id)) {
            $query->andWhere(['id'=>$id]);
        }
        $models = $query->all();
        $cart_list = array();
        foreach ($models as $model) {
            
            $goods = \Yii::$app->services->goods->getGoodsInfo($model->goods_id,$model->goods_type);
            if(empty($goods)) {
                \Yii::$app->services->actionLog->create('用户购物车列表',"商品查询失败",$model->toArray());
                continue;
            }

            $sale_price = $this->exchangeAmount($goods['sale_price'],0);
            $cart = array();
            $cart['id'] = $model->id;
            $cart['userId'] = $this->member_id;
            $cart['goodsId'] = $goods['style_id'];
            $cart['goodsDetailsId'] = $model->goods_id;
            $cart['goodsCount'] = $model->goods_num;
            $cart['createTime'] = $model->created_at;
            $cart['collectionId'] = null;
            $cart['collectionStatus'] = null;
            $cart['localSn'] = null;
            $cart['groupType'] = $model->group_type;
            $cart['goodsType'] = $model->goods_type;
            $cart['groupId'] = $model->group_id;
            $cart['goodsAttr'] = $model->goods_attr?@\GuzzleHttp\json_decode($model->goods_attr, true):[];

            $cart['coupon'] = [
                'type_id' => $model->goods_type,//产品线ID
                'style_id' => $goods['style_id'],//款式ID
                'price' => $sale_price,//价格
                'num' =>1,//数量
            ];

            $cart['ring'] = $goods['ring'];

            $simpleGoodsEntity = [
                    "goodId"=>$goods['style_id'],
                    "goodsDetailsId"=>$model->goods_id,
                    "categoryId"=>$model->goods_type,
                    "goodsName"=>$goods['goods_name'],
                    "goodsCode"=>$goods['goods_sn'],
                    "goodsImages"=>ImageHelper::goodsThumbs($goods['goods_image'],'small'),
                    "goodsStatus"=>$goods['status']==1?2:0,
                    "totalStock"=>$goods['goods_storage'],
                    "salePrice"=>$sale_price,
                    "coinType"=>$this->currencySign,
                    'detailConfig'=>[],
                    'baseConfig'=>[]
            ];
            //return $goods['goods_attr'];
            if(!empty($goods['lang']['goods_attr'])) {
                $baseConfig = [];
                foreach ($goods['lang']['goods_attr'] as $vo){
                    $baseConfig[] = [
                            'configId' =>$vo['id'],
                            'configAttrId' =>0,
                            'configVal' =>$vo['attr_name'],
                            'configAttrIVal' =>implode('/',$vo['value']),
                    ];                    
                }
                $simpleGoodsEntity['baseConfig'] = $baseConfig;
            }
            if(!empty($goods['lang']['goods_spec'])) {
                $detailConfig = [];
                foreach ($goods['lang']['goods_spec'] as $vo){
                    
                    $detailConfig[] = [
                            'configId' =>$vo['attr_id'],
                            'configAttrId' =>0,
                            'configVal' =>$vo['attr_name'],
                            'configAttrIVal' =>$vo['attr_value'],
                    ];
                    
                }
                $simpleGoodsEntity['detailConfig'] = $detailConfig;
            }            
            $simpleGoodsEntity['simpleGoodsDetails'] = [
                    "id"=>$model->id,
                    "goodsId"=>$goods['style_id'],
                    "goodsDetailsCode"=>$goods["goods_sn"],
                    "stock"=>$goods["goods_storage"],
                    "retailPrice"=>$sale_price,
                    "retailMallPrice"=>$sale_price,
                    "coinType"=>$this->getCurrencySign(),
            ];

            if($model->group_type == 1){ //对戒
                $ring = Ring::find()->alias('r')
                    ->where(['r.id'=>$model->group_id])
                    ->innerJoin(RingLang::tableName().' lang','r.id=lang.master_id')
                    ->select(['r.id','r.ring_style as ringStyle','r.sale_price','r.status','r.ring_images as ringImg','r.ring_sn as ringCode','lang.ring_name as name'])->asArray()->one();
                $ring['coinType'] = $this->getCurrencySign();
                $ring['simpleGoodsEntity'] = $simpleGoodsEntity;
                $ring['salePrice'] = $this->exchangeAmount($ring['sale_price'],0);
                $cart['ringsSimpleGoodsEntity'] = $ring;

            }else{
                $cart['simpleGoodsEntity'] = $simpleGoodsEntity;
            }

         
            $cart_list[] = $cart;
        }
        CouponService::getCouponByList($this->getAreaId(), $cart_list, false);
        return $cart_list;
    }
    /**
     * 添加购物车商品
     */
    public function actionAdd()
    {
        $addType = \Yii::$app->request->post("addType");
        $goodsCartList = \Yii::$app->request->post('goodsCartList');        
        try{
            $trans = \Yii::$app->db->beginTransaction();
            if(empty($goodsCartList)){
                throw new \Exception("goodsCartList不能为空",500);
            }
            $cart_list = [];
            foreach ($goodsCartList as  $cartGoods){
                $cartGoods['add_type'] = $addType;
                $model = new CartForm();
                $model->attributes = $cartGoods;
                if (!$model->validate()) {
                    // 返回数据验证失败
                    throw new \Exception($this->getError($model),500);
                }                
                $goods = \Yii::$app->services->goods->getGoodsInfo($model->goods_id,$model->goods_type);
                if(!$goods || $goods['status'] != 1) {
                    throw new UnprocessableEntityHttpException("商品不是售卖状态");
                }
    
                $cart = new OrderCart();
                $cart->attributes = $model->toArray();
                $cart->merchant_id = $this->merchant_id;
                $cart->member_id = $this->member_id;
    
                $cart->goods_type = $goods['type_id'];
                $cart->goods_price = $goods['sale_price'];
                $cart->goods_spec = $goods['goods_spec'];//商品规格
                $cart->goods_attr = json_encode($model['goods_attr']);//商品属性

                //款式
                $cart->style_id = $goods['style_id'];

                //平台组
                $cart->platform_group = OrderFromEnum::platformToGroup($this->platform);

                if (!$cart->save()) {
                    throw new \Exception($this->getError($cart),500);
                } 
                $cart_list[] = $cart->toArray();
                
            }
            $trans->commit();
            
            return $cart_list;
        } catch (Exception $e){
            
            $trans->rollBack();
            
            \Yii::$app->services->actionLog->create('用户添加购物车',"Exception:".$e->getMessage());
            
            throw $e;
        }
       
    }
    /**
     * 购物车商品数量
     */
    public function actionCount()
    {
        return $this->modelClass::find()->where(['member_id'=>$this->member_id,'status'=>1])->count();
    }
    /**
     * 编辑购物车
     * @return mixed|NULL
     */
    public function actionEdit()
    {
        return $this->edit(['goods_num'])->toArray(['id','goods_num']);
    }   
    
    /**
     * 删除购物车商品
     */
    public function actionDel()
    {
        $id = \Yii::$app->request->post("id");
        if(!$id) {
            return ResultHelper::api(500, "id不能为空");
        }        
        if($id == -1) {
            //清空购物车
            $num = $this->modelClass::updateAll(['status'=>0], ['member_id'=>$this->member_id]);
        }else {
            if(!is_array($id)) {
                $id = explode(',', $id);
            }
            $num = $this->modelClass::updateAll(['status'=>0], ['member_id'=>$this->member_id,'id'=>$id]);
        }
        return ['num'=>$num];
    }

    /**
     * 本地购物车数据置换
     */
    public function actionLocal()
    {
        $addType = \Yii::$app->request->post("addType");
        $goodsCartList = \Yii::$app->request->post('goodsCartList');
        if(empty($goodsCartList)){
            \Yii::$app->services->actionLog->create('游客购物车列表',"goodsCartList参数错误");
            return ResultHelper::api(500,"goodsCartList不能为空");
        }

        $cart_list = array();
        foreach ($goodsCartList as  $cartGoods) {
            $cartGoods['add_type'] = $addType;
            $model = new CartForm();
            $model->attributes = $cartGoods;
            if (!$model->validate()) {
                // 返回数据验证失败
                $error = $this->getError($model);
                \Yii::$app->services->actionLog->create('游客购物车列表',$error);
                return ResultHelper::api(500,$error);
            }

            $goods = \Yii::$app->services->goods->getGoodsInfo($model->goods_id,$model->goods_type);
            if(empty($goods)) {
                \Yii::$app->services->actionLog->create('游客购物车列表',"查询商品失败",$model->toArray());
                return ResultHelper::api(500,"查询商品失败");
            }

            $sign = $model->getSign();
            if(!OrderCart::find()->where(['sign'=>$sign])->count('id')) {
                $_cart = new OrderCart();
                $_cart->attributes = $model->toArray();
                $_cart->merchant_id = $this->merchant_id;
                $_cart->member_id = $this->member_id;

                $_cart->goods_type = $goods['type_id'];
                $_cart->goods_price = $goods['sale_price'];
                $_cart->goods_spec = json_encode($goods['goods_spec']);//商品规格

                //款式
                $_cart->style_id = $goods['style_id'];

                //平台组
                $_cart->platform_group = OrderFromEnum::platformToGroup($this->platform);
                $_cart->sign = $sign;

                try {
                    $_cart->save(false);
                } catch (\Exception $exception) {
                    // TODO
                }
            }

            $sale_price = $this->exchangeAmount($goods['sale_price'],0);
            $cart = array();
            //$cart['id'] = $model->id;
            $cart['userId'] = $this->member_id;
            $cart['goodsId'] = $goods['style_id'];
            $cart['goodsDetailsId'] = $model->goods_id;
            $cart['goodsCount'] = $model->goods_num;
            $cart['createTime'] = $cartGoods['createTime'];
            $cart['collectionId'] = null;
            $cart['collectionStatus'] = null;
            $cart['localSn'] = $cart['createTime'];
            $cart['groupType'] = $model->group_type;
            $cart['goodsType'] = $model->goods_type;
            $cart['groupId'] = $model->group_id;
            $cart['goodsAttr'] = $model->goods_attr;

            $cart['coupon'] = [
                'type_id' => $model->goods_type,//产品线ID
                'style_id' => $goods['style_id'],//款式ID
                'price' => $sale_price,//价格
                'num' =>1,//数量
            ];

            $cart['ring'] = $goods['ring'];

            $simpleGoodsEntity = [
                "goodId"=>$goods['style_id'],
                "goodsDetailsId"=>$model->goods_id,
                "categoryId"=>$model->goods_type,
                "goodsName"=>$goods['goods_name'],
                "goodsCode"=>$goods['goods_sn'],
                "goodsImages"=>ImageHelper::goodsThumbs($goods['goods_image'],'small'),
                "goodsStatus"=>$goods['status']==1?2:0,
                "totalStock"=>$goods['goods_storage'],
                "salePrice"=>$sale_price,
                "coinType"=>$this->currencySign,
                'detailConfig'=>[],
                'baseConfig'=>[]
            ];
            //return $goods['goods_attr'];
            if(!empty($goods['lang']['goods_attr'])) {
                $baseConfig = [];
                foreach ($goods['lang']['goods_attr'] as $vo){
                    $baseConfig[] = [
                        'configId' =>$vo['id'],
                        'configAttrId' =>0,
                        'configVal' =>$vo['attr_name'],
                        'configAttrIVal' =>implode('/',$vo['value']),
                    ];
                }
                $simpleGoodsEntity['baseConfig'] = $baseConfig;
            }
            if(!empty($goods['lang']['goods_spec'])) {
                $detailConfig = [];
                foreach ($goods['lang']['goods_spec'] as $vo){
                    $detailConfig[] = [
                        'configId' =>$vo['attr_id'],
                        'configAttrId' =>$vo['value_id'],
                        'configVal' =>$vo['attr_name'],
                        'configAttrIVal' =>$vo['attr_value'],
                    ];

                }
                $simpleGoodsEntity['detailConfig'] = $detailConfig;
            }
            $simpleGoodsEntity['simpleGoodsDetails'] = [
                //"id"=>$model->id,
                "goodsId"=>$goods['style_id'],
                "goodsDetailsCode"=>$goods["goods_sn"],
                "stock"=>$goods["goods_storage"],
                "retailPrice"=>$sale_price,
                "retailMallPrice"=>$sale_price,
                "coinType"=>$this->getCurrencySign(),
            ];

            if($model->group_type == 1) { //对戒
                $ring = Ring::find()->alias('r')
                    ->where(['r.id'=>$model->group_id])
                    ->innerJoin(RingLang::tableName().' lang','r.id=lang.master_id')
                    ->select(['r.id','r.ring_style as ringStyle','r.sale_price','r.status','r.ring_images as ringImg','r.ring_sn as ringCode','lang.ring_name as name'])->asArray()->one();
                $ring['coinType'] = $this->getCurrencySign();
                $ring['simpleGoodsEntity'] = $simpleGoodsEntity;
                $ring['salePrice'] = $this->exchangeAmount($ring['sale_price'],0);
                $cart['ringsSimpleGoodsEntity'] = $ring;

            }else{
                $cart['simpleGoodsEntity'] = $simpleGoodsEntity;
            }

            $cart_list[] = $cart;
        }

        CouponService::getCouponByList($this->getAreaId(), $cart_list);
        return $cart_list;
    }
     
    
}