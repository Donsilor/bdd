<?php

namespace api\modules\wap\controllers\member;

use common\helpers\ImageHelper;
use common\models\goods\Ring;
use common\models\goods\RingLang;
use common\models\order\OrderCart;
use api\modules\wap\forms\CartForm;
use common\helpers\ResultHelper;
use api\controllers\UserAuthController;
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
    
    public $modelClass = OrderCart::class;
    
    protected $authOptional = [];

    /**
     * 购物车列表     
     */
    public function actionIndex()
    {
        $id = \Yii::$app->request->get('id');
        
        $query = $this->modelClass::find()->where(['member_id'=>$this->member_id]);

        if(!empty($id) && $id = explode(',',$id)) {
            $query->andWhere(['id'=>$id]);
        }
        $models = $query->all();
        $cart_list = array();
        foreach ($models as $model) {
            
            $goods = \Yii::$app->services->goods->getGoodsInfo($model->goods_id,$model->goods_type);
            if(empty($goods)) {
                continue;
            }
            if($model->goods_type == \Yii::$app->params['goodsType.diamond']) {
                $model->goods_type  = 1;//裸钻
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
            $simpleGoodsEntity = [
                    "goodId"=>$goods['style_id'],
                    "goodsDetailsId"=>$model->goods_id,
                    "categoryId"=>$model->goods_type,
                    "goodsName"=>$goods['goods_name'],
                    "goodsCode"=>$goods['goods_sn'],
                    "goodsImages"=>ImageHelper::goodsThumbs($goods['goods_image'],'mid'),
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
                    ->select(['r.id','r.ring_style as ringStyle','r.sale_price as salePrice','r.status','r.ring_images as ringImg','r.ring_sn as ringCode','lang.ring_name as name'])->asArray()->one();
                $ring['coinType'] = $this->getCurrencySign();
                $ring['simpleGoodsEntity'] = $simpleGoodsEntity;
                $cart['ringsSimpleGoodsEntity'] = $ring;
            }else{
                $cart['simpleGoodsEntity'] = $simpleGoodsEntity;
            }

         
            $cart_list[] = $cart;
        }
        return $cart_list;
    }
    /**
     * 添加购物车商品
     */
    public function actionAdd()
    {
        $addType = \Yii::$app->request->post("addType");
        $goodsCartList = \Yii::$app->request->post('goodsCartList');
        if(empty($goodsCartList)){
            return ResultHelper::api(422,"goodsCartList不能为空");
        }
        try{
            $trans = \Yii::$app->db->beginTransaction();
            $cart_list = [];
            foreach ($goodsCartList as  $cartGoods){
                $cartGoods['add_type'] = $addType;
                $model = new CartForm();
                $model->attributes = $cartGoods;
                if (!$model->validate()) {
                    // 返回数据验证失败
                    throw new UnprocessableEntityHttpException($this->getError($model));
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
                $cart->goods_spec = json_encode($goods['goods_spec']);//商品规格
                
                if (!$cart->save()) {
                    throw new UnprocessableEntityHttpException($this->getError($cart));
                } 
                $cart_list[] = $cart->toArray();
                
            }
            $trans->commit();
            
            return $cart_list;
        } catch (Exception $e){
            
            $trans->rollBack();
            
            throw $e;
        }
       
    }
    /**
     * 购物车商品数量
     */
    public function actionCount()
    {
        return $this->modelClass::find()->where(['member_id'=>$this->member_id])->count();
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
            return ResultHelper::api(422, "id不能为空");
        }        
        if($id == -1) {
            //清空购物车
            $num = $this->modelClass::deleteAll(['member_id'=>$this->member_id]);
        }else {  
            if(!is_array($id)) {
                $id = explode(',', $id);
            }
            $num = $this->modelClass::deleteAll(['member_id'=>$this->member_id,'id'=>$id]);
        }
        return ['num'=>$num];
    } 
     
    
}