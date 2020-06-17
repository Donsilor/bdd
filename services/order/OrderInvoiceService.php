<?php

namespace services\order;

use common\components\Service;
use common\enums\CurrencyEnum;
use common\enums\OrderFromEnum;
use common\helpers\ResultHelper;
use common\models\order\OrderCart;
use common\models\order\OrderGoodsLang;
use common\models\order\OrderInvoice;
use common\models\order\OrderInvoiceEle;
use services\market\CardService;
use yii\web\UnprocessableEntityHttpException;
use common\models\order\OrderGoods;
use common\models\order\Order;
use common\models\member\Address;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\enums\PayStatusEnum;
use common\models\common\EmailLog;
use common\models\member\Member;
use common\helpers\RegularHelper;
use common\models\common\SmsLog;
use common\enums\OrderStatusEnum;
use common\enums\ExpressEnum;
use common\enums\StatusEnum;
use common\models\order\OrderLog;

/**
 * Class OrderService
 * @package services\order
 */
class OrderInvoiceService extends OrderBaseService
{

//大陆：网址：https://wap.bddco.cn/
//https://www.bddco.cn/  [0755 25169121 / e-service@bddco.com
//
//
//香港：https://wap.bddco.com/
//https://www.bddco.com/     [+852 21653905 / service@bddco.com
//
//
//美国：https://us.bddco.com/
//https://wap-us.bddco.com/   [+852 21653905 / service@bddco.com

    private $siteInfo = [
        OrderFromEnum::WEB_HK => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_HK => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::WEB_CN => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_CN => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::WEB_US => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_US => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3905',
            'email' => 'service@bddco.com',
        ],
    ];

    public function getEleInvoiceInfo($order_id){
        $order = Order::find()
            ->where(['id'=>$order_id])
            ->one();
        if(empty($order)) {
            throw new UnprocessableEntityHttpException("订单不存在");
        }
        $language = $order->language;
        $result = array(
            'invoice_date' => $order->delivery_time,
            'sender_name' => '',
            'sender_address'=> '',
            'shipper_name' => '',
            'shipper_address' => '',
            'order_sn' => $order->order_sn,
            'payment_type' => $order->payment_type,
            'realname' => $order->address->realname,
            'address_details' => $order->address->address_details,
            'express_no' => $order->express_no,
            'express_company_name' => '',
            'delivery_time' => $order->delivery_time,
            'country' => $order->address->country_name,
            'currency' => $order->account->currency,
            'order_amount' => $order->account->order_amount,
            'email' => $order->invoice->email ?? '',
            'is_electronic' => $order->invoice->is_electronic ?? '', //是否电子发票
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'send_num' => $order->invoice->send_num ?? 0,
            'gift_card_amount' => CardService::getUseAmount($order_id),
        );
        $result['order_paid_amount'] = bcsub($result['order_amount'],$result['gift_card_amount'],2);

        $order_invoice_exe_model = OrderInvoiceEle::find()
            ->where(['order_id'=>$order->id])
            ->one();
        if($order_invoice_exe_model){
            $order_invoice_exe = $order_invoice_exe_model->toArray();
            $result['invoice_date'] = $order_invoice_exe['invoice_date'] ? $order_invoice_exe['invoice_date'] : $result['invoice_date'];
            $result['sender_name'] = $order_invoice_exe['sender_name'] ? $order_invoice_exe['sender_name'] : $result['sender_name'];
            $result['sender_address'] = $order_invoice_exe['sender_address'] ? $order_invoice_exe['sender_address'] : $result['sender_address'];
            $result['shipper_name'] = $order_invoice_exe['shipper_name'] ? $order_invoice_exe['shipper_name'] : $result['shipper_name'];
            $result['shipper_address'] = $order_invoice_exe['shipper_address'] ? $order_invoice_exe['shipper_address'] : $result['shipper_address'];
            $result['delivery_time'] = $order_invoice_exe['delivery_time'] ? $order_invoice_exe['delivery_time'] : $result['delivery_time'];
            $result['delivery_time'] = $order_invoice_exe['delivery_time'] ? $order_invoice_exe['delivery_time'] : $result['delivery_time'];
            $result['email'] = $order_invoice_exe['email'] ? $order_invoice_exe['email'] : $result['email'];
            $language = $order_invoice_exe['language'] ? $order_invoice_exe['language'] : $language;

            \Yii::$app->params['language'] = $language; //设置语言
            $result['express_company_name'] = $order_invoice_exe['express_id'] ? $order_invoice_exe_model->express->lang->express_name : '';
            $result['express_no'] = $order_invoice_exe['express_no'] ? $order_invoice_exe['express_no'] : $result['express_no'];
        }


        //因为可能会重置语言，故把根据订单获取快递放到这里
        if(empty($result['express_company_name'])){
            $result['express_company_name'] = $order->express_id ? $order->express->lang->express_name : '';
        }

        $result['language'] = $language;

        //商品明细
        $order_goods = OrderGoods::find()->alias('m')
            ->leftJoin(OrderGoodsLang::tableName().'lang','m.id=lang.master_id and lang.language="'.$language.'"')
            ->where(['order_id'=>$order_id])
            ->select(['lang.goods_name','m.goods_sn','m.goods_num','m.goods_pay_price','m.currency'])
            ->asArray()
            ->all();
        $result['order_goods'] = $order_goods;



        //不同语言差异代码
        if($language == 'en-US'){
            $result['invoice_date'] = $result['invoice_date'] ? date('d-m-Y',$result['invoice_date']):date('d-m-Y',time());
            $result['delivery_time'] = $result['delivery_time'] ? date('d-m-Y',$result['delivery_time']):'';

            $city_name = $order->address->city_name ? ','.$order->address->city_name : '';
            $province_name = $order->address->province_name ? ','.$order->address->province_name : '';
            $country_name = $order->address->country_name ? ','.$order->address->country_name : '';
            $result['address_details'] = $order->address->address_details .$city_name.$province_name.$country_name ;
        }else{
            $result['invoice_date'] = $result['invoice_date'] ? date('d-M-Y',$result['invoice_date']):date('d-M-Y',time());
            $result['delivery_time'] = $result['delivery_time'] ? date('Y-m-d',$result['delivery_time']):'';

            $city_name = $order->address->city_name ? $order->address->city_name . ' ' : '';
            $province_name = $order->address->province_name ? $order->address->province_name . ' ' : '';
            $country_name = $order->address->country_name ? $order->address->country_name .' ' : '';
            $result['address_details'] = $country_name . $province_name . $city_name . $order->address->address_details ;
        }

        switch ($language){
            case 'en-US':
                $result['template'] = 'ele-invoice-us.php';
                break;
            case 'zh-CN':
                $result['template'] = 'ele-invoice-zh.php';
                break;
            default: $result['template'] = 'ele-invoice.php';

        }

        //站点信息
        $result['siteInfo'] = $this->siteInfo[$order->order_from]??[];

        return $result;
    }
    
}