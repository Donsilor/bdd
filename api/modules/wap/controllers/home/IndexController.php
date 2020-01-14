<?php

namespace api\modules\wap\controllers\home;

use api\controllers\OnAuthController;
use common\models\goods\Style;


/**
 * Class ProvincesController
 * @package api\modules\v1\controllers\member
 */
class IndexController extends OnAuthController
{

    /**
     * @var Provinces
     */
    public $modelClass = Style::class;
    protected $authOptional = ['web-site'];

    //商品推荐
    public function actionWebSite(){
        $type_id = 2;
        $limit = 6;
        $language = $this->language;
        $order = 'sale_volume desc';
        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','m.sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id,$limit,$order, $fields ,$language);
        $webSite = array();
        $webSite['moduleTitle'] = '首页珠宝饰品推广位';
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

        $advert_list = \Yii::$app->services->advert->getTypeAdvertImage(0,2, $language);
        $advert = array();
        foreach ($advert_list as $val){
            $advertImgModelList = array();
            $advertImgModelList['addres'] = $val['adv_url'];
            $advertImgModelList['image'] = $val['adv_image'];
            $advertImgModelList['title'] = $val['title'];


            $advert['advertImgModelList'][] = $advertImgModelList;
        }
//        $advert['dsDesc'] = '移動端首頁';
//        $advert['dsImg'] = 'adt/image1559614371406.png';
//        $advert['dsName'] = '移動端首页——banner全屏';
//        $advert['tdName'] = '移動端首页——banner全屏（1图链接新窗口）';
//        $advert['dsShowType'] = 1;
        $advert['tdOpenType'] = 1;


        $result = array();
        $result['webSite'][0] = $webSite;
        $result['advert'][0] = $advert;
        return $result;

    }

    
    
}