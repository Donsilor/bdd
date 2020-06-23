<?php


namespace services\goods;


use common\components\Service;
use common\enums\StatusEnum;
use common\models\goods\Goods;
use common\models\goods\Ring;
use common\models\goods\Style;
use common\models\goods\StyleLang;
use yii\base\Exception;

class RingService extends Service
{
    static public function syncRingToGoods($ringId)
    {
        $ring = Ring::findOne($ringId);

        if(!$ring) {
            throw new \Exception('数据不存在');
        }

        $style = Style::findOne(['style_sn'=>$ring->ring_sn]);
        if(!$style) {
            $style = new Style();
            $style->style_sn = $ring->ring_sn;
        }

        $style->type_id = $ring->type_id;
        $style->style_3ds = $ring->ring_3ds;
//        $style->style_image = $ring->ring_images;
        $style->goods_images = $ring->ring_images;

        $style->sale_price = $ring->sale_price;
        $style->cost_price = $ring->cost_price;
        $style->market_price = $ring->market_price;
        $style->sale_volume = $ring->sale_volume;
        $style->goods_clicks = $ring->goods_clicks;
        $style->virtual_clicks = $ring->virtual_clicks;
        $style->status = $ring->status;

        $style->goods_storage = $ring->goods_storage;;//库存
        $style->style_salepolicy = $ring->style_salepolicy;

        if(false === $style->save()) {
            throw new Exception(\Yii::$app->debris->analyErr($style->getFirstErrors()));
        }

        foreach ($ring->langs as $lang) {
            $styleLang = StyleLang::findOne(['master_id'=>$style->id, 'language'=>$lang->language]);
            if(!$styleLang) {
                $styleLang = new StyleLang();
                $styleLang->master_id= $style->id;
                $styleLang->language= $lang->language;
            }

            $styleLang->style_desc = '';//$lang->goods_desc;
            $styleLang->style_name = $lang->ring_name;
            $styleLang->goods_body = $lang->ring_body;

            $styleLang->meta_title = $lang->meta_title;
            $styleLang->meta_word = $lang->meta_word;
            $styleLang->meta_desc = $lang->meta_desc;

            $styleLang->save(false);
        }

        //先标注所有sku已删除
        Goods::updateAll(['status'=>StatusEnum::DELETE],['style_id'=>$style->id]);

        $goods = Goods::find()->where(['goods_sn'=>$ring->ring_sn,'style_id'=>$style->id])->one();
        if(!$goods) {
            $goods = new Goods();
            $goods->goods_sn = $ring->ring_sn;
            $goods->style_id = $style->id;
        }

        $goods->type_id = $ring->type_id;
//        $goods->goods_image = $ring->ring_images;
        $goods->goods_storage = $style->style_salepolicy;
        $goods->sale_price = $ring->sale_price;
        $goods->cost_price = $ring->cost_price;
        $goods->market_price = $ring->market_price;
        $goods->sale_volume = $ring->sale_volume;
        $goods->goods_clicks = $ring->goods_clicks;
        $goods->status = $ring->status;
        if(false === $goods->save()) {
            throw new Exception(\Yii::$app->debris->analyErr($goods->getFirstErrors()));
        }

        //更新裸钻
        $ring->style_id = $style->id;
        $ring->goods_id = $goods->id;
        $ring->save(false);

        \Yii::$app->services->salepolicy->syncGoodsMarkup($style->id);
    }
}