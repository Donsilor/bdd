<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use backend\modules\order\forms\OrderAuditForm;
use backend\modules\order\forms\OrderCancelForm;
use backend\modules\order\forms\OrderFollowerForm;
use backend\modules\order\forms\OrderRefundForm;
use common\enums\CurrencyEnum;
use common\enums\InvoiceElectronicEnum;
use common\enums\OrderFromEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayEnum;
use common\enums\PayStatusEnum;
use common\helpers\ExcelHelper;
use common\helpers\ResultHelper;
use common\models\common\EmailLog;
use common\models\market\MarketCard;
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
use common\models\pay\WireTransfer;
use Omnipay\Common\Message\AbstractResponse;
use services\order\OrderLogService;
use Yii;
use common\components\Curd;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\order\Order;
use Exception;
use yii\helpers\ArrayHelper;
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
                'follower' => ['username'],
                'wireTransfer' => ['collection_status'],
            ]
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at', 'address.mobile', 'address.email', 'order_status']);

        //订单状态
        if ($orderStatus !== -1) {
            if($orderStatus==11) {
                $dataProvider->query->andWhere('common_pay_wire_transfer.id is not null');
            }
            else {
                $dataProvider->query->andWhere(['=', 'order_status', $orderStatus]);
            }
        }

        //订单状态
        $orderStatus2 = Yii::$app->request->queryParams['SearchModel']['order_status']??"";
        if($orderStatus2!="") {
            if($orderStatus2==1) {
                $dataProvider->query->andWhere(['=', 'refund_status', $orderStatus2]);
                $dataProvider->query->andWhere(['=', 'order_status', 0]);
            }
            elseif($orderStatus2==0) {
                $dataProvider->query->andWhere(['=', 'order_status', $orderStatus2]);
                $dataProvider->query->andWhere(['=', 'refund_status', 0]);
            }
            else {
                $dataProvider->query->andWhere(['=', 'order_status', $orderStatus2]);
            }
        }

        //站点地区
        $sitesAttach = \Yii::$app->getUser()->identity->sites_attach;
        if(is_array($sitesAttach)) {
            $orderFroms = [];

            foreach ($sitesAttach as $site) {
                $orderFroms = array_merge($orderFroms, OrderFromEnum::platformsForGroup($site));
            }

            $dataProvider->query->andWhere(['in', 'order.order_from', $orderFroms]);
        }

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


        //导出
        if(Yii::$app->request->get('action') === 'export'){
            $query = Yii::$app->request->queryParams;
            unset($query['action']);
            if(empty(array_filter($query))){
                return $this->message('导出条件不能为空', $this->redirect(['index']), 'warning');
            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;
            $this->getExport($list);
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
     * 取消一个订单
     */
    public function actionEditCancel()
    {
        $id = Yii::$app->request->get('id', null);
        $order = Yii::$app->request->post('OrderCancelForm', []);

        $this->modelClass = OrderCancelForm::class;

        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if (Yii::$app->request->isPost) {

            Yii::$app->services->order->changeOrderStatusCancel($id, $order['cancel_remark']??'', 'admin', Yii::$app->getUser()->id);

            return $this->redirect(['index']);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionCancel()
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

                //判断订单是否已付款状态
                if($model->order_status !== OrderStatusEnum::ORDER_UNPAID) {
                    throw new Exception(sprintf('[%d]不是待付款状态', $id));
                }

                $isPay = false;
                //查验订单是否有多笔支付
                foreach ($model->paylogs as $paylog) {
                    if($paylog->pay_type==PayEnum::PAY_TYPE_CARD) {
                        continue;
                    }

                    //获取支付类
                    $pay = Yii::$app->services->pay->getPayByType($paylog->pay_type);

                    /**
                     * @var $state AbstractResponse
                     */
                    $state = $pay->verify(['model'=>$paylog, 'isVerify'=>true]);

                    if(in_array($state->getCode(), ['null'])) {
                        throw new Exception(sprintf('[%d]订单支付[%s]验证出错，请重试', $id, $paylog->out_trade_no));
                    }
                    elseif(in_array($state->getCode(), ['completed','pending', 'payer']) || $paylog->pay_status==PayStatusEnum::PAID) {
                        throw new Exception(sprintf('[%d]订单存在支付[%s]', $id, $paylog->out_trade_no));
                    }
                }

                //更新订单状态
                \Yii::$app->services->order->changeOrderStatusCancel($model->id,"管理员取消订单", 'admin', Yii::$app->getUser()->id);
            }
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return ResultHelper::json(422, '取消失败！'.$e->getMessage());
        }

        return ResultHelper::json(200, '取消成功', [], true);



    }

    /**
     * 订单退款
     */
    public function actionEditRefund()
    {
        $id = Yii::$app->request->get('id', null);
        $order = Yii::$app->request->post('OrderRefundForm', []);

        $this->modelClass = OrderRefundForm::class;

        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);

        if (Yii::$app->request->isPost) {

            Yii::$app->services->order->changeOrderStatusRefund($id, $order['refund_remark']??'', 'admin', Yii::$app->getUser()->id);

            return $this->redirect(['index']);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }



    /**
     * 跟进
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionEditFollower()
    {
        $this->modelClass = OrderFollowerForm::class;

        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);

        $sellerRemark = $model->seller_remark;

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            
            $model->followed_status = $model->follower_id ? FollowStatusEnum::YES : FollowStatusEnum::NO;

            OrderLogService::follower($model);

            if(!empty($sellerRemark)) {
                $model->seller_remark = $sellerRemark . "\r\n--------------------\r\n" . $model->seller_remark;
            }

            $result = $model->save();

            return $result
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

            //发货日志
            OrderLogService::deliver($model);

            //订单发送邮件
            \Yii::$app->services->order->sendOrderNotification($id);
            return $result ? $this->redirect(['index']):$this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionEditAudit()
    {
        $id = Yii::$app->request->get('id', null);
        $order = Yii::$app->request->post('OrderAuditForm', []);

        $this->modelClass = OrderAuditForm::class;

        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);

        if (Yii::$app->request->isPost) {

            try {
                Yii::$app->services->order->changeOrderStatusAudit($id, $order['audit_status'], $order['audit_remark']??'');
            } catch (Exception $exception) {
                $this->message($exception->getMessage(), $this->redirect(['index']), 'error');
            }

            return $this->redirect(['index']);
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

                    if($paylog->pay_type==PayEnum::PAY_TYPE_CARD || $paylog->pay_type==PayEnum::PAY_TYPE_WIRE_TRANSFER) {
                        $isPay = true;
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
                OrderLogService::audit($model);
            }
            $trans->commit();
        } catch (Exception $e) {
            $trans->rollBack();
            return ResultHelper::json(422, '审核失败！'.$e->getMessage());
        }
        
        return ResultHelper::json(200, '审核成功', [], true);
    }



    public  function actionEleInvoiceAjaxEdit(){
        $order_id = Yii::$app->request->get('order_id');
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        $language = Yii::$app->request->get('language');


        $this->modelClass = OrderInvoiceEle::class;
        $model = $this->findModel($order_id);
        $model->order_id = $order_id;

        $oldModelData = $model->getAttributes();

        $model->language = $model->language ? $model->language : $language ;
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {

            $modelData = $model->getDirtyAttributes([
                'language',
                'invoice_date',
                'sender_name',
                'sender_address',
                'express_id',
                'express_no',
                'delivery_time',
                'email'
            ]);

            if(false === $model->save()){
                throw new Exception($this->getError($model));
            }
         // return $this->redirect($returnUrl);

            $order = Order::findOne($order_id);

            OrderLogService::eleInvoiceEdit($order,[$modelData, $oldModelData]);

            return $this->message("保存成功", $this->redirect($returnUrl), 'success');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'order_id' => $order_id,
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
        return $content;
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
//                'SetHeader'=>[$subject],
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

        $order = Order::findOne($invoice_model->order_id);

        OrderLogService::eleInvoiceSend($order);

        return ResultHelper::json(200,'发送成功',['send_num'=>$send_num]);
    }





    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getExport($list)
    {
        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['下单时间', 'created_at' , 'date', 'Y-m-d'],
            ['订单编号', 'order_sn', 'text'],
            ['收货人', 'address.realname', 'text'],
            ['联系方式', 'id', 'function', function($row){
                $model = OrderAddress::find()->where(['order_id'=>$row->id])->one();
                $html = "";
                if($model->mobile) {
                    $html .= $model->mobile_code.'-'.$model->mobile;
                }
                if($model->email) {
                    if(!empty($html)) {
                        $html .= "\r\n  ";
                    }
                    $html .= $model->email;
                }
                return $html;
            }],
            ['订单总金额', 'account.order_amount', 'text'],
            ['实付金额', 'account.pay_amount', 'text'],
            ['货币', 'account.currency', 'text'],
            ['是否使用购物卡', 'id', 'function',function($model){
                $row = MarketCardDetails::find()->where(['order_id'=>$model->id])->one();
                return $row ? "是" : "否";
            }],
            ['购物卡号', 'id', 'function',function($model){
                $rows = MarketCardDetails::find()->alias('card_detail')
                    ->leftJoin(MarketCard::tableName()." card",'card.id=card_detail.card_id')
                    ->where(['card_detail.status'=>StatusEnum::ENABLED , 'card_detail.order_id'=>$model->id])
                    ->asArray()->select(['sn','batch'])->all();
                if($rows){
                    return join(';',array_column($rows,'sn'));
                }
                return '';
            }],
            ['批次名称', 'id', 'function',function($model){
                $rows = MarketCardDetails::find()->alias('card_detail')
                    ->leftJoin(MarketCard::tableName()." card",'card.id=card_detail.card_id')
                    ->where(['card_detail.status'=>StatusEnum::ENABLED ,'card_detail.order_id'=>$model->id])
                    ->asArray()->select(['sn','batch'])->all();
                if($rows){
                    return join(';',array_column($rows,'batch'));
                }
                return '';
            }],
            ['是否游客订单', 'is_tourist', 'function',function($model){
                return $model->is_tourist == 1 ? "是" : "否";
            }],
            ['归属地区', 'ip_area_id', 'function',function($model){
                return \common\enums\AreaEnum::getValue($model->ip_area_id);

            }],
            ['支付状态', 'payment_status', 'function',function($model){
                return \common\enums\PayStatusEnum::getValue($model->payment_status);
            }],
            ['支付方式', 'payment_type', 'function',function($model){
                if($model->payment_type){
                    return \common\enums\PayEnum::getValue($model->payment_type);
                }
                return '';

            }],
            ['订单状态', 'order_status', 'function',function($row){
                return \common\enums\OrderStatusEnum::getValue($row->order_status);
            }],
            ['订单来源', 'order_from', 'function',function($model){
                if($model->order_from){
                    return \common\enums\OrderFromEnum::getValue($model->order_from);
                }
                return '';

            }],
            ['退款状态', 'refund_status', 'function',function($row){
                return '';
            }],
            ['跟进人', 'id', 'function',function($model){
                $row = \common\models\backend\Member::find()->where(['id'=>$model->follower_id])->one();
                if($row){
                    return $row->username;
                }
                return '';

            }],
            ['跟进状态', 'followed_status', 'function',function($model){
                return \common\enums\FollowStatusEnum::getValue($model->followed_status);
            }],
            ['订单备注', 'seller_remark', 'text'],



        ];


        return ExcelHelper::exportData($list, $header, '订单数据导出_' . date('YmdHis',time()));
    }




}

