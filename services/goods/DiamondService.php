<?php

namespace services\goods;
use common\components\Service;
use common\models\goods\Diamond;
use yii\db\Expression;
use common\models\goods\Style;
use common\models\goods\Goods;
use common\models\goods\StyleLang;
use yii\base\Exception;
use common\enums\StatusEnum;



/**
 * Class DiamondService
 * @package services\common
 */
class DiamondService extends Service
{
    /**
     * 更改基本库存
     * @param unknown $goods_id
     * @param unknown $quantity
     * @param unknown $for_sale 是否销售库存
     */
    public function updateGoodsStorageForOrder($goods_id,$quantity)
    {
        $data = [
            //'goods_num'=> new Expression("goods_num+({$quantity})"),
            'sale_volume'=> new Expression("sale_volume-({$quantity})")
        ];
        Diamond::updateAll($data,['goods_id'=>$goods_id]);
    }   
    /**
     * 创建款号(style表)
     * @param unknown $diamond_id
     */
    public function syncDiamondToGoods($diamond_id)
    {
        $diamond = Diamond::find()->where(['id'=>$diamond_id])->one();
        $style = Style::find()->where(['style_sn'=>$diamond->goods_sn])->one();
        if(!$style) {
            $style = new Style();
            $style->style_sn = $diamond->goods_sn;            
        }
        $style->type_id = $diamond->type_id;
        $style->style_image = $diamond->goods_image;
        $style->goods_images = $diamond->parame_images;
        $style->style_3ds = $diamond->goods_3ds;
        $style->style_salepolicy = $diamond->sale_policy;
        $style->goods_storage = $diamond->goods_num;
        $style->sale_price = $diamond->sale_price;
        $style->cost_price = $diamond->cost_price;
        $style->market_price = $diamond->market_price;
        $style->sale_volume = $diamond->sale_volume;
        $style->goods_clicks = $diamond->goods_clicks;
        $style->virtual_clicks = $diamond->virtual_clicks;
        $style->status = $diamond->status;
        if(false === $style->save()) {
            throw new Exception($this->getError($style));
        }
        $style_id = $style->id;
        
        foreach ($diamond->langs as $lang){
            $styleLang = StyleLang::find()->where(['master_id'=>$style_id,'language'=>$lang->language])->one();
            if(!$styleLang) {
                $styleLang = new StyleLang();
                $styleLang->master_id= $style_id;
                $styleLang->language= $lang->language;                
            }
            $styleLang->style_name = $lang->goods_name;
            $styleLang->style_desc = $lang->goods_desc;
            $styleLang->goods_body = $lang->goods_body;
            $styleLang->meta_title = $lang->meta_title;
            $styleLang->meta_word = $lang->meta_word;
            $styleLang->meta_desc = $lang->meta_desc;
            $styleLang->save(false);
        }
        //先标注所有sku已删除
        Goods::updateAll(['status'=>StatusEnum::DELETE],['style_id'=>$style_id]);
        
        $goods = Goods::find()->where(['goods_sn'=>$diamond->goods_sn,'style_id'=>$style->id])->one();        
        if(!$goods) {
            $goods = new Goods();
            $goods->goods_sn = $diamond->goods_sn;
            $goods->style_id = $style->id;
        }
        $goods->type_id = $diamond->type_id;
        $goods->goods_image = $diamond->goods_image;
        $goods->goods_storage = $diamond->goods_num;
        $goods->sale_price = $diamond->sale_price;
        $goods->cost_price = $diamond->cost_price;
        $goods->market_price = $diamond->market_price;
        $goods->sale_volume = $diamond->sale_volume;
        $goods->goods_clicks = $diamond->goods_clicks;
        $goods->status = $diamond->status;
        if(false === $goods->save()) {
            throw new Exception($this->getError($goods));
        }
        $goods_id = $goods->id;
        //echo $style->id , $goods->id;exit;
        //更新裸钻
        $diamond->style_id = $style_id;
        $diamond->goods_id = $goods_id;
        $diamond->save(false);
        
        \Yii::$app->services->salepolicy->syncGoodsMarkup($style_id);        
    }
    

}