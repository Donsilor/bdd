<?php

namespace services\order;

use common\components\Service;
use common\enums\CurrencyEnum;
use common\enums\LanguageEnum;
use common\enums\OrderFromEnum;
use common\helpers\ResultHelper;
use common\models\order\OrderCart;
use common\models\order\OrderGoodsLang;
use common\models\order\OrderInvoice;
use common\models\order\OrderInvoiceEle;
use kartik\mpdf\Pdf;
use services\market\CardService;
use yii\base\Controller;
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

    private $sendAddress = [
        OrderFromEnum::GROUP_HK => [
            LanguageEnum::ZH_HK => [
                'name' => '香港',
                'detailed' => '中環亞畢諾道3號環球貿易中心23樓04室',
            ],
            LanguageEnum::ZH_CN => [
                'name' => '香港',
                'detailed' => '中环亚毕诺道3号环球贸易中心23楼04室',
            ],
            LanguageEnum::EN_US => [
                'name' => 'Hong Kong',
                'detailed' => 'Unit 2304, 23/F,Universal Trade Centre,3 Arbuthnot Road,Central, Hong Kong',
            ],
        ],

        OrderFromEnum::GROUP_TW => [
            LanguageEnum::ZH_HK => [
                'name' => '深圳',
                'detailed' => '深圳市羅湖區東曉街道獨樹社區布心路3008號IBC商務珠寶大廈A座',
            ],
            LanguageEnum::ZH_CN => [
                'name' => '深圳',
                'detailed' => '深圳市罗湖区东晓街道独树社区布心路3008号IBC商务珠宝大厦A座',
            ],
            LanguageEnum::EN_US => [
                'name' => 'Shenzhen',
                'detailed' => 'Building A, IBC Business Jewelry Building, No. 3008, Buxin Road, Dushu Community, Dongxiao Street, Luohu District, Shenzhen',
            ],
        ],

        OrderFromEnum::GROUP_CN => [
            LanguageEnum::ZH_HK => [
                'name' => '深圳',
                'detailed' => '深圳市羅湖區東曉街道獨樹社區布心路3008號IBC商務珠寶大廈A座',
            ],
            LanguageEnum::ZH_CN => [
                'name' => '深圳',
                'detailed' => '深圳市罗湖区东晓街道独树社区布心路3008号IBC商务珠宝大厦A座',
            ],
            LanguageEnum::EN_US => [
                'name' => 'Shenzhen',
                'detailed' => 'Building A, IBC Business Jewelry Building, No. 3008, Buxin Road, Dushu Community, Dongxiao Street, Luohu District, Shenzhen',
            ],
        ],

        OrderFromEnum::GROUP_US => [
            LanguageEnum::ZH_HK => [
                'name' => '深圳',
                'detailed' => '深圳市羅湖區東曉街道獨樹社區布心路3008號IBC商務珠寶大廈A座',
            ],
            LanguageEnum::ZH_CN => [
                'name' => '深圳',
                'detailed' => '深圳市罗湖区东晓街道独树社区布心路3008号IBC商务珠宝大厦A座',
            ],
            LanguageEnum::EN_US => [
                'name' => 'Shenzhen',
                'detailed' => 'Building A, IBC Business Jewelry Building, No. 3008, Buxin Road, Dushu Community, Dongxiao Street, Luohu District, Shenzhen',
            ],
        ],
    ];

    private $siteInfo = [
        OrderFromEnum::WEB_HK => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_HK => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::WEB_CN => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_CN => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::WEB_US => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_US => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::WEB_TW => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
        OrderFromEnum::MOBILE_TW => [
            'webSite' => 'https://www.bddco.com/',
            'tel' => '2165 3908',
            'email' => 'service@bddco.com',
        ],
    ];

    public function getEleInvoiceInfo($order) {
        if(!($order instanceof Order)) {
            $order = Order::find()
                ->where(['id'=>$order])
                ->one();
        }

        if(empty($order)) {
            throw new UnprocessableEntityHttpException("订单不存在");
        }
        \Yii::$app->params['language'] = $language = 'en-US';// $order->language;

        $result = array(
            'invoice_date' => $order->delivery_time,
            'sender_name' => '',
            'sender_area'=> '',
            'sender_address'=> '',
            'shipper_name' => '',
            'shipper_address' => '',
            'order_sn' => $order->order_sn,
            'payment_type' => $order->payment_type,
            'realname' => $order->address->realname,
            'mobile' => $order->address->mobile_code . '-' . $order->address->mobile,
            'address_details' => $order->address->address_details,
            'zip_code' => $order->address->zip_code,
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
            //'gift_card_amount' => CardService::getUseAmount($order_id),
        );
        $result['coupon_amount'] = bcadd($order->account->coupon_amount, $order->account->discount_amount, 2);
        $result['gift_card_amount'] = $order->account->card_amount;
        $result['order_paid_amount'] = $order->account->paid_amount;//bcsub($result['order_amount'],$result['gift_card_amount'],2);
        $result['order_pay_amount'] = $order->account->pay_amount;//bcsub($result['order_amount'],$result['gift_card_amount'],2);

        $order_invoice_exe_model = OrderInvoiceEle::find()
            ->where(['order_id'=>$order->id])
            ->one();
        if($order_invoice_exe_model){
            $order_invoice_exe = $order_invoice_exe_model->toArray();
            $result['invoice_date'] = $order_invoice_exe['invoice_date'] ? $order_invoice_exe['invoice_date'] : $result['invoice_date'];
            $result['sender_name'] = $order_invoice_exe['sender_name'] ? $order_invoice_exe['sender_name'] : $result['sender_name'];
            $result['sender_area'] = $order_invoice_exe['sender_area'] ? $order_invoice_exe['sender_area'] : $result['sender_address'];
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

        $sendAddressInfo = $this->getSendAddressByOrder($order, $language);

        $result['sender_area'] = $result['sender_area']?:($sendAddressInfo['name']??'');
        $result['sender_address'] = $result['sender_address']?:($sendAddressInfo['detailed']??'');


        //因为可能会重置语言，故把根据订单获取快递放到这里
        if(empty($result['express_company_name'])){
            $result['express_company_name'] = $order->express_id ? $order->express->lang->express_name : '';
        }

        $result['language'] = $language;

        //商品明细
        $order_goods = OrderGoods::find()->alias('m')
            ->leftJoin(OrderGoodsLang::tableName().'lang','m.id=lang.master_id and lang.language="'.$language.'"')
            ->where(['order_id'=>$order_id])
            ->select(['lang.goods_name','m.goods_sn','m.goods_num','m.goods_pay_price','m.goods_price','m.currency'])
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

        switch ($language) {
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



        //$result['sendAddressInfo'] = \GuzzleHttp\json_encode($result['sendAddressInfo']);

        return $result;
    }

    public function sendAddressInfo()
    {
        return $this->sendAddress;
    }

    private function getSendAddressByOrder($order, $language=null)
    {
        $orderFrom = $order->order_from;

        $platformGroup = OrderFromEnum::platformToGroup($orderFrom);

        $sendAddress = $this->sendAddress[$platformGroup]??[];

        $language = $language ? $language : $order->language;

        return $sendAddress[$language]??[];
    }

    /**
     * 生成PDF文件
     * @param $order
     */
    public function generatePdfFile($view, $order) {
        $result = $this->getEleInvoiceInfo($order);

        $content = $view->render($result['template'], ['result'=>$result]);

        $file = \Yii::getAlias('@storage') . sprintf('/backend/pdfInvoice/%s.pdf', $result['order_sn']);

        $pdf = new Pdf([
            //
            'filename' => $file,
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_FILE,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => \Yii::getAlias('@webroot').'/resources/css/invoice.css',
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:18px}',
            // set mPDF properties on the fly
            'options' => [
                'title' => '中文',
                'autoLangToFont' => true,    //这几个配置加上可以显示中文
                'autoScriptToLang' => true,  //这几个配置加上可以显示中文
                'autoVietnamese' => true,    //这几个配置加上可以显示中文
                'autoArabic' => true,        //这几个配置加上可以显示中文
            ],
            // call mPDF methods on the fly
            'methods' => [
//                'SetHeader'=>[$subject],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        return $pdf->render();
    }
    
}