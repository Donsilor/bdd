<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use common\enums\CurrencyEnum;
use common\enums\InvoiceElectronicEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayEnum;
use common\enums\PayStatusEnum;
use common\helpers\ExcelHelper;
use common\helpers\ResultHelper;
use common\models\common\EmailLog;
use common\models\market\MarketCardDetails;
use common\models\member\Address;
use common\models\member\Member;
use common\models\order\OrderAccount;
use common\models\order\OrderAddress;
use common\models\order\OrderCart;
use common\models\order\OrderGoods;
use common\models\order\OrderGoodsLang;
use common\models\order\OrderInvoice;
use common\models\order\OrderInvoiceEle;
use Omnipay\Common\Message\AbstractResponse;
use Yii;
use common\components\Curd;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\order\Order;
use Exception;
use yii\web\NotFoundHttpException;
use common\enums\FollowStatusEnum;
use common\enums\AuditStatusEnum;
use backend\modules\order\forms\DeliveryForm;
use common\enums\DeliveryStatusEnum;

use kartik\mpdf\Pdf;
/**
 * Default controller for the `order` module
 */
class OrderController extends BaseController
{
    use Curd;

    /**
     * @var Order
     */
    public $modelClass = Order::class;

    /**
     * Renders the index view for the module
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $orderStatus = Yii::$app->request->get('order_status', -1);

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
                'account' => ['order_amount'],
                'address' => ['country_name', 'city_name', 'country_id', 'city_id', 'realname', 'mobile', 'email'],
                'member' => ['username', 'realname', 'mobile', 'email'],
                'follower' => ['username']
            ]
        ]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at', 'address.mobile', 'address.email']);

        //订单状态
        if ($orderStatus !== -1)
            $dataProvider->query->andWhere(['=', 'order_status', $orderStatus]);

        // 数据状态
        $dataProvider->query->andWhere(['>=', 'order.status', StatusEnum::DISABLED]);

        // 联系人搜索
        if(!empty(Yii::$app->request->queryParams['SearchModel']['address.mobile'])) {
            $where = [];
            $where[] = 'or';
            $where[] = ['like', 'order_address.mobile', Yii::$app->request->queryParams['SearchModel']['address.mobile']];
            $where[] = ['like', 'order_address.email', Yii::$app->request->queryParams['SearchModel']['address.mobile']];

            $dataProvider->query->andWhere($where);
        }

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'order.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 详情展示页
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView()
    {
        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);

        $dataProvider = null;
        if (!is_null($id)) {
            $searchModel = new SearchModel([
                'model' => OrderGoods::class,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => 1000,
            ]);

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            $dataProvider->query->andWhere(['=', 'order_id', $id]);

            $dataProvider->setSort(false);
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 跟进
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionEditFollower()
    {
        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            
            $model->followed_status = $model->follower_id ? FollowStatusEnum::YES : FollowStatusEnum::NO;
            
            return $model->save()
                ? $this->redirect(['index'])
                : $this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 发货 跟进
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionEditDelivery()
    {
        $id = Yii::$app->request->get('id');

        $model = DeliveryForm::find()->where(['id'=>$id])->one();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
//            $model->delivery_time = time();
            $model->delivery_status = DeliveryStatusEnum::SEND;
            $model->order_status = OrderStatusEnum::ORDER_SEND;//已发货
            $result = $model->save();
            //订单发送邮件
            \Yii::$app->services->order->sendOrderNotification($id);
            return $result ? $this->redirect(['index']):$this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 批量审核
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionAjaxBatchAudit()
    {
        $ids = Yii::$app->request->post("ids", []);
        $trans = Yii::$app->db->beginTransaction();

        try {
            if(empty($ids) || !is_array($ids)) {
                throw new Exception('提交数据异常');
            }

            foreach ($ids as $id) {
                $model = $this->modelClass::findOne($id);
                if(!$model) {
                    throw new Exception(sprintf('[%d]数据未找到', $id));
                }

                //判断订单是否待审核状态
                if($model->status !== AuditStatusEnum::PENDING) {
                    throw new Exception(sprintf('[%d]不是待审核状态', $id));
                }

                //判断订单是否已付款状态
                if($model->order_status !== OrderStatusEnum::ORDER_PAID) {
                    throw new Exception(sprintf('[%d]不是已付款状态', $id));
                }

                $isPay = false;
                //查验订单是否有多笔支付
                foreach ($model->paylogs as $paylog) {
                    if($paylog->pay_status != PayStatusEnum::PAID) {
                          continue;
                    }

                    if($paylog->pay_type==PayEnum::PAY_TYPE_CARD) {
                        continue;
                    }

                    //获取支付类
                    $pay = Yii::$app->services->pay->getPayByType($paylog->pay_type);

                    /**
                     * @var $state AbstractResponse
                     */
                    $state = $pay->verify(['model'=>$paylog, 'isVerify'=>true]);

                    //当前这笔订单的付款
                    if($paylog->out_trade_no == $model->pay_sn) {
                        $isPay = $state->isPaid();
                    }
                    elseif(in_array($state->getCode(), ['null'])) {
                        throw new Exception(sprintf('[%d]订单支付[%s]验证出错，请重试', $id, $paylog->out_trade_no));
                    }
                    /*elseif(in_array($state->getCode(), ['completed','pending', 'payer']) || $paylog->pay_status==PayStatusEnum::PAID) {
                        throw new Exception(sprintf('[%d]订单存在多笔支付[%s]', $id, $paylog->out_trade_no));
                    }*/
                    elseif($state->isPaid()) {
                        throw new Exception(sprintf('[%d]订单存在多笔支付[%s]', $id, $paylog->out_trade_no));
                    }
                }

                if(!$isPay) {
                    throw new Exception(sprintf('[%d]订单支付状态验证失败', $id));
                }

                //更新订单审核状态
                $model->status = AuditStatusEnum::PASS;
                $model->order_status = OrderStatusEnum::ORDER_CONFIRM;//已审核，代发货
                
                if(false  === $model->save()) {
                    throw new Exception($this->getError($model));
                }                
                //订单日志
            }
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return ResultHelper::json(422, '审核失败！'.$e->getMessage());
        }
        
        return ResultHelper::json(200, '审核成功', [], true);
    }



    public  function actionEleInvoiceAjaxEdit(){
        $invoice_id = Yii::$app->request->get('invoice_id');
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        $language = Yii::$app->request->get('language');


        $this->modelClass = OrderInvoiceEle::class;
        $model = $this->findModel($invoice_id);
        $model->language = $model->language ? $model->language : $language ;
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if(false === $model->save()){
                throw new Exception($this->getError($model));
            }
         // return $this->redirect($returnUrl);
            return $this->message("保存成功", $this->redirect($returnUrl), 'success');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'invoice_id'=>$invoice_id,
            'returnUrl'=>$returnUrl
        ]);

    }

   //电子发票预览（PDF）
    public function actionEleInvoicePdf(){
        $order_id = Yii::$app->request->get('order_id');
        if(!$order_id){
            return ResultHelper::json(422, '非法调用');
        }
        $result = Yii::$app->services->orderInvoice->getEleInvoiceInfo($order_id);
        $content = $this->renderPartial($result['template'],['result'=>$result]);
        $usage = 'order-invoice';
        $usageExplains = EmailLog::$usageExplain;
        $subject  = $usageExplains[$usage]??'';
        $subject = Yii::t('mail', $subject,[],$result['language']);
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
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
                'SetHeader'=>[$subject],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        return $pdf->render();

    }

    //电子发票发送（PDF）
    public function actionEleInvoiceSend(){
        $order_id = Yii::$app->request->post('order_id');
        if(!$order_id){
            return ResultHelper::json(422, '非法调用');
        }
        $result = Yii::$app->services->orderInvoice->getEleInvoiceInfo($order_id);
        if($result['is_electronic'] != InvoiceElectronicEnum::YES){
            return ResultHelper::json(422, '此订单没有电子发票');
        }
        if($result['payment_status'] != PayStatusEnum::PAID || $result['order_status'] < OrderStatusEnum::ORDER_PAID ){
            return ResultHelper::json(422, '此订单没有支付');
        }

        if($result['send_num'] > 5){
            return ResultHelper::json(422, '发送次数已经超过5次');
        }


        $content = $this->renderPartial($result['template'],['result'=>$result]);

        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_DOWNLOAD,
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
                'SetHeader'=>['中文'],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        $usage = 'order-invoice';
        $file = [
            'file_content' => $pdf->render(),
            'file_ext' => 'pdf',
            'contentType' => 'application/pdf'
        ];

        \Yii::$app->services->mailer->queue(false)->send($result['email'],$usage,['code'=>$order_id,'file'=>$file],$result['language']);

        $invoice_model = OrderInvoice::find()
        ->where(['order_id'=>$order_id])
        ->one();
        $send_num = $invoice_model->send_num + 1;
        $invoice_model->send_num = $send_num;
        $invoice_model->save();
        return ResultHelper::json(200,'发送成功',['send_num'=>$send_num]);
    }


    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExport()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'relations' => [
                'account' => ['order_amount'],
                'address' => ['country_name', 'city_name', 'country_id', 'city_id', 'realname', 'mobile', 'email'],
                'member' => ['username', 'realname', 'mobile', 'email'],
                'follower' => ['username']
            ]
        ]);
        $param_get = Yii::$app->request->get();
        $orderStatus = $param_get['order_status_get'];
        unset($param_get['order_status_get']);
        //print_r(array_filter($param_get));exit;
        $param['SearchModel'] = array_filter($param_get);
        $dataProvider = $searchModel->search($param , ['created_at', 'address.mobile', 'address.email']);
        //订单状态
        if ($orderStatus !== -1)
            $dataProvider->query->andWhere(['=', 'order_status', $orderStatus]);

        // 数据状态
        $dataProvider->query->andWhere(['>=', 'order.status', StatusEnum::DISABLED]);

        // 联系人搜索
        if(!empty(Yii::$app->request->queryParams['SearchModel']['address.mobile'])) {
            $where = [];
            $where[] = 'or';
            $where[] = ['like', 'order_address.mobile', Yii::$app->request->queryParams['SearchModel']['address.mobile']];
            $where[] = ['like', 'order_address.email', Yii::$app->request->queryParams['SearchModel']['address.mobile']];

            $dataProvider->query->andWhere($where);
        }

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'order.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        $dataProvider->setPagination(false);
        $dataProvider->db->quoteSql();exit;
        $list = $dataProvider->models;
        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['下单时间', 'created_at' , 'date', 'Y-m-d'],
            ['订单编号', 'order_sn', 'text'],
            ['收货人', 'address.realname', 'text'],
            ['联系方式', 'id', 'function', function($row){
                $model = OrderAddress::find()->where(['order_id'=>$row->id])->one();
                $html = '';
                if($model->mobile) {
                    $html .= $model->mobile_code.'-'.$model->mobile;
                }
                if($model->email) {
                    if(!empty($html)) {
                        $html .= '<br/>';
                    }
                    $html .= $model->email;
                }
                return $html;
            }],
            ['订单总金额', 'account.order_amount', 'text'],
            ['实付金额', 'account.pay_amount', 'text'],
            ['货币', 'account.currency', 'text'],
            ['是否使用购物卡', 'id', 'function',function($row){
                $model = MarketCardDetails::find()->where(['order_id'=>$row->id])->one();
                return $model ? "是" : "否";
            }],
            ['归属地区', 'ip_area_id', 'function',function($row){
                return \common\enums\AreaEnum::getValue($row->ip_area_id);

            }],
            ['支付状态', 'payment_status', 'function',function($row){
               if($row->payment_status){
                   return \common\enums\PayStatusEnum::getValue($row->payment_status);
               }
               return '';
            }],
            ['支付方式', 'payment_type', 'function',function($row){
               if($row->payment_type){
                   return \common\enums\PayEnum::getValue($row->payment_type);
               }
               return '';

            }],
            ['订单状态', 'order_status', 'function',function($row){
                return \common\enums\OrderStatusEnum::getValue($row->order_status);
            }],
            ['退款状态', 'refund_status', 'function',function($row){
                return '';
            }],
            ['跟进人', 'id', 'function',function($row){
                if(!empty($row->follower_id)){
                    $model = \common\models\backend\Member::find()->where(['id'=>$row->follower_id])->one();
                    return $model->username;
                }
                return '';

            }],
            ['跟进状态', 'followed_status', 'function',function($row){
                return \common\enums\FollowStatusEnum::getValue($row->followed_status);
            }],
            ['订单备注', 'seller_remark', 'text'],



        ];


        return ExcelHelper::exportData($list, $header, '订单数据导出_' . time());
    }



}

