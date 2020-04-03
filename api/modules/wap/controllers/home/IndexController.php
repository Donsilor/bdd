<?php

namespace api\modules\wap\controllers\home;

use api\controllers\OnAuthController;
use common\helpers\ImageHelper;
use common\models\goods\Style;
use services\market\CouponService;


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
        $fields = ['m.id', 'm.goods_images', 'm.style_sn','lang.style_name','IFNULL(markup.sale_price,m.sale_price) as sale_price'];
        $style_list = \Yii::$app->services->goodsStyle->getStyleList($type_id,$limit,$order, $fields ,$language);
        $webSite = array();
        $webSite['moduleTitle'] = \Yii::t('common','首页珠宝饰品推广位');
        foreach ($style_list as $val){
            $moduleGoods = array();
            $moduleGoods['id'] = $val['id'];
            $moduleGoods['categoryId'] = $type_id;
            $moduleGoods['coinType'] = $this->currency;
            $moduleGoods['goodsCode'] = $val['style_sn'];
            $moduleGoods['goodsImages'] = ImageHelper::goodsThumbs($val['goods_images'],'mid');
            $moduleGoods['goodsName'] = $val['style_name'];
            $moduleGoods['salePrice'] = $this->exchangeAmount($val['sale_price']);

            $moduleGoods['coupon'] = [
                'type_id' => $type_id,//产品线ID
                'style_id' => $val['id'],//款式ID
                'price' => $moduleGoods['salePrice'],//价格
                'num' =>1,//数量
            ];

            $webSite['moduleGoods'][] = $moduleGoods;
        }

        CouponService::getCouponByList($this->getAreaId(), $webSite['moduleGoods']);

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