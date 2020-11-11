<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use backend\modules\order\forms\OrderAddressForm;
use backend\modules\order\forms\OrderAuditForm;
use backend\modules\order\forms\OrderCancelForm;
use backend\modules\order\forms\OrderFollowerForm;
use backend\modules\order\forms\OrderRefundForm;
use common\enums\CurrencyEnum;
use common\enums\ExpressEnum;
use common\enums\InvoiceElectronicEnum;
use common\enums\LanguageEnum;
use common\enums\OrderFromEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayEnum;
use common\enums\PayStatusEnum;
use common\helpers\ExcelHelper;
use common\helpers\Html;
use common\helpers\ResultHelper;
use common\helpers\Url;
use common\models\common\EmailLog;
use common\models\goods\Goods;
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
use common\models\order\OrderLog;
use common\models\pay\WireTransfer;
use Omnipay\Common\Message\AbstractResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use services\order\OrderLogService;
use Yii;
use common\components\Curd;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\order\Order;
use Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use common\enums\FollowStatusEnum;
use common\enums\AuditStatusEnum;
use backend\modules\order\forms\DeliveryForm;
use common\enums\DeliveryStatusEnum;

use kartik\mpdf\Pdf;
use yii\web\Response;
use function Clue\StreamFilter\fun;
use function foo\func;

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
        $orderStatus2 = Yii::$app->request->queryParams['SearchModel']['order_status']??"";

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
            ],

        ]);

        $searchModel->setAttributes(['is_test' => 0]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['created_at', 'address.mobile', 'address.email', 'order_status', 'discount_type']);

        //订单状态
        if ($orderStatus !== -1) {
            if($orderStatus==11) {
                $dataProvider->query->andWhere('common_pay_wire_transfer.id is not null');
            }
            elseif($orderStatus==1) {
                $orderStatus2 = $orderStatus;
            }
            else {
                $dataProvider->query->andWhere(['=', 'order_status', $orderStatus]);
            }
        }

        //订单状态
        if($orderStatus2!="") {
            if($orderStatus2==1) {
                $dataProvider->query->andWhere(['=', 'refund_status', $orderStatus2]);
                $dataProvider->query->andWhere(['=', 'order_status', 0]);
            }
            elseif($orderStatus2==0) {
                $dataProvider->query->andWhere(['=', 'order_status', $orderStatus2]);
                $dataProvider->query->andWhere(['<>', 'refund_status', 1]);
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

        if(!empty(Yii::$app->request->queryParams['SearchModel']['discount_type'])) {
            $discountType = explode(',', Yii::$app->request->queryParams['SearchModel']['discount_type']);
            $where = ['or'];

            if(in_array('card', $discountType)) {
                $where[] = ['>', 'order_account.card_amount', 0];
            }

            if(in_array('coupon', $discountType)) {
                $where[] = ['>', 'order_account.coupon_amount', 0];
            }

            if(in_array('discount', $discountType)) {
                $where[] = ['>', 'order_account.discount_amount', 0];
            }

            if(count($where)>1)
                $dataProvider->query->andWhere($where);
        }

        //导出
        if($this->export){
            $query = Yii::$app->request->queryParams;
            unset($query['action']);
            if(empty(array_filter($query))){
                $returnUrl = Yii::$app->request->referrer;
                return $this->message('导出条件不能为空', $this->redirect($returnUrl), 'warning');
            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;

            if($this->export=='export-invoice-file') {
                return $this->getExportInvoiceFile($list);
            }

            elseif($this->export=='order') {
                return $this->getExport($list);
            }

            elseif ($this->export=='goods') {
                return $this->getExportGoods($list);

            }
        }

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public $export = null;
    public function actionExport() {
        $this->export = 'order';
        return $this->actionIndex();
    }

    public function actionExportInvoiceFile() {
        $this->export = 'export-invoice-file';
        return $this->actionIndex();
    }

    public function actionExportGoods() {
        $this->export = 'goods';
        return $this->actionIndex();
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

        $logistics = Yii::$app->services->order->getOrderLogisticsInfo($model, false);

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
            'logistics' => $logistics
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

        $id = explode(',', $id);

        $model = $this->findModel($id[0]);

        // ajax 校验
        $this->activeFormValidate($model);
        if (Yii::$app->request->isPost) {

            foreach ($id as $item) {
                Yii::$app->services->order->changeOrderStatusCancel($item, $order['cancel_remark']??'', 'admin', Yii::$app->getUser()->id);
            }

            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer), 'success');
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

                    if(in_array($paylog->pay_type, [1, 2])) {
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

            Yii::$app->services->order->changeOrderStatusRefund($id, $order['refund_remark']??'', $order['refund_status']);


            $returnUrl = Yii::$app->request->referrer;
            return $this->redirect($returnUrl);
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

        $id = explode(',', $id);

        $model = $this->findModel($id[0]);

        // ajax 校验
        $this->activeFormValidate($model);
        if (Yii::$app->request->isPost) {

            foreach ($id as $item) {
                Yii::$app->services->order->changeOrderStatusFollower($item, Yii::$app->request->post());
            }

            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer), 'success');
        }

        $where = [];
        $where['order_sn'] = $model->order_sn;
        $where['action_name'] = 'FOLLOWER';

        $orderLog = OrderLog::find()->where($where)->all();

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'orderLog' => $orderLog
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

            if($model->delivery_status != DeliveryStatusEnum::SEND) {
                $model->delivery_status = DeliveryStatusEnum::SEND;
                $model->order_status = OrderStatusEnum::ORDER_SEND;//已发货

                //发货日志
                OrderLogService::deliver($model);
            }

            //订单发送邮件
            if($model->address->load(Yii::$app->request->post()) && $model->send_now) {
                Yii::$app->services->order->sendOrderExpressEmail($model);
            }

            $returnUrl = Yii::$app->request->referrer;

            $model->address->save();
            $result = $model->save();

            return $result ? $this->redirect($returnUrl) : $this->message($this->getError($model), $this->redirect($returnUrl), 'error');
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

        $id = explode(',', $id);

        $model = $this->findModel($id[0]);

        // ajax 校验
        $this->activeFormValidate($model);

        if (Yii::$app->request->isPost) {

            try {
                foreach ($id as $item) {
                    Yii::$app->services->order->changeOrderStatusAudit($item, $order['audit_status'], $order['audit_remark']??'');
                }
            } catch (Exception $exception) {
                $this->message($exception->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
            }

            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer), 'success');
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

                    if(in_array($paylog->pay_type, [
                        PayEnum::PAY_TYPE_CARD,
                        PayEnum::PAY_TYPE_WIRE_TRANSFER,
                        PayEnum::PAY_TYPE_ALI,
                        PayEnum::PAY_TYPE_WECHAT
                    ])) {
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


    public  function actionEditSendPaidEmail() {
        $order_id = Yii::$app->request->get('order_id');

        $model = $this->findModel($order_id);

        $model->address->setScenario(OrderAddress::SEND_PAID_EMAIL);

        $this->activeFormValidate($model->address);
        if ($model->address->load(Yii::$app->request->post())) {

            //更新发送次数
            $data = [];
            $data['send_paid_email_time'] = new Expression("send_paid_email_time+(1)");

            Order::updateAll($data,['id'=>$model->id]);

            $model->order_status = OrderStatusEnum::ORDER_PAID;

            Yii::$app->services->order->sendOrderNotificationByOrder($model);

            OrderLogService::sendPaidEmail($model, [[
                '收件邮箱' => $model->address->email
            ]]);

            return $this->message("发送成功", $this->redirect(Yii::$app->request->referrer), 'success');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'order_id' => $order_id
        ]);
    }


    public  function actionSendOrderExpressEmail() {
        $order_id = Yii::$app->request->get('order_id');

        $model = $this->findModel($order_id);

        $this->activeFormValidate($model->address);
        if ($model->address->load(Yii::$app->request->post())) {

            Yii::$app->services->order->sendOrderExpressEmail($model);

            $model->address->save();

            return $this->message("发送成功", $this->redirect(Yii::$app->request->referrer), 'success');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'order_id' => $order_id
        ]);
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
    public function actionEleInvoicePdf() {
        $order_id = Yii::$app->request->get('order_id');
        if(!$order_id) {
            return ResultHelper::json(422, '非法调用');
        }
        $result = Yii::$app->services->orderInvoice->getEleInvoiceInfo($order_id, null, 'pdf');
        $content = $this->renderPartial($result['template'],['result'=>$result]);
        if(!Yii::$app->request->get('is_pdf', true)) return $content;
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
        $result = Yii::$app->services->orderInvoice->getEleInvoiceInfo($order_id, null, 'pdf');
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

        //订单编号，收货人；详细地址，城市，省，邮编，区域，收件人电话，
        //2.详细地址，邮编和地址需要拆分
        //2,1联系方式：需要拆分：电话和邮箱

        $header = [
            ['下单时间', 'created_at' , 'date', 'Y-m-d'],
            ['订单编号', 'order_sn', 'text'],
            ['收货人', 'address.realname', 'text'],
            ['详细地址', 'address.address_details', 'text'],
            ['城市', 'address.city_name', 'text'],
            ['省', 'address.province_name', 'text'],
            ['邮编', 'id', 'function', function($row) {
                return $row->address->zip_code;
            }],
            ['区域', 'address.country_name', 'text'],
            ['联系方式：电话', 'id', 'function', function($row){
                $model = OrderAddress::find()->where(['order_id'=>$row->id])->one();
                $html = "";
                if($model->mobile) {
                    $html .= $model->mobile_code.'-'.$model->mobile;
                }
                return $html;
            }],
            ['联系方式：邮箱', 'id', 'function', function($row){
                $model = OrderAddress::find()->where(['order_id'=>$row->id])->one();
                $html = "";
                if($model->email) {
                    if(!empty($html)) {
                        $html .= "\r\n  ";
                    }
                    $html .= $model->email;
                }
                return $html;
            }],
            ['订单总金额', 'account.order_amount', 'text'],
            ['物流公司', 'account.order_amount', 'function', function($row) {
                return \Yii::$app->services->express->getExressName($row->express_id);
            }],
            ['物流单号', 'express_no', 'text'],
            ['发货时间', 'delivery_time', 'function', function($row) {
                if($row->delivery_status) {
                    return Yii::$app->formatter->asDatetime($row->delivery_time);
                }
                return '';
            }],
            ['折扣金额', 'account.discount_amount', 'text'],
            ['优惠券金额', 'account.coupon_amount', 'text'],
            ['购物卡金额', 'account.card_amount', 'text'],
            ['实付金额', 'account.pay_amount', 'function', function($row) {
                $pay_amount = $row->account->pay_amount;
                if($row->account->currency==CurrencyEnum::TWD) {
                    $pay_amount = sprintf("%.2f", intval($pay_amount));
                }
                return $pay_amount;
            }],
            ['货币', 'account.currency', 'text'],
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
                return $row->refund_status == OrderStatusEnum::ORDER_REFUND_YES ?'已关闭':\common\enums\OrderStatusEnum::getValue($row->order_status);
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

    public function getExportInvoiceFile($list) {
        if(!is_array($list) || empty($list)) {
            return $this->message("数据为空", $this->redirect(Yii::$app->request->referrer), 'error');
        }

        $service = Yii::$app->services->orderInvoice;

        $filename = sprintf("invoice-zip/发票压缩包-%s.zip", time());
        $zip = new \ZipArchive();
        $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包

        foreach ($list as $item) {
            $file= $service->generatePdfFile($this, $item);

            if(file_exists($file)) {
                $zip->addFile($file, basename($file));   //向压缩包中添加文件
            }

        }
        $zip->close();  //关闭压缩包

        //下载文件
        if(file_exists($filename)) {
            return $this->redirect(Url::to(Url::base() . '/' . $filename));
        }

        return $this->message("压缩包文件不存在", $this->redirect(Yii::$app->request->referrer), 'error');
    }

    public function actionEditAddress()
    {
        $id = Yii::$app->request->get('id', null);

        $model = $this->findModel($id);

        $orderOld = $model->getAttributes();
        $orderOld += $model->address->getAttributes();

        // ajax 校验
        $this->activeFormValidate($model, $model->address);
        if ($model->load(Yii::$app->request->post()) &&
            $model->address->load(Yii::$app->request->post())) {

            $model->link('address', $model->address);

            $orderNew = $model->getAttributes();
            $orderNew += $model->address->getAttributes();

            $result = $model->save();

            OrderLogService::changeAddress($model, [$orderNew, $orderOld]);

            return $result
                ? $this->redirect(['view', 'id'=>$id])
                : $this->message($this->getError($model), $this->redirect(['index', 'id'=>$id]), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionArea()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pid = Yii::$app->request->get('pid', null);
        $type_id = Yii::$app->request->get('type_id', null);

        $str = "-- 请选择 --";
        $model = Yii::$app->services->area->getDropDown($pid);
        if ($type_id == 1 && !$pid) {
            return Html::tag('option', '-- 请选择 --', ['value' => '']);
        } elseif ($type_id == 2 && !$pid) {
            return Html::tag('option', '-- 请选择 --', ['value' => '']);
        } elseif ($type_id == 2 && $model) {
            $str = "-- 请选择 --";
        }

        $str = Html::tag('option', $str, ['value' => '']);
        foreach ($model as $value => $name) {
            $str .= Html::tag('option', Html::encode($name), ['value' => $value]);
        }

        return $str;
    }

    private function fun($specs, $attrs=[]) {
        $cs = $sc = $xk = $zs = $zsdx = $zssc = '';
        foreach ($specs as $spec) {

            switch ($spec['attr_id']) {
                case 10: //成色
                    $cs = $spec['attr_value'];
                    break;
                case 38: //手寸
                    $sc = $spec['attr_value'];
                    break;
                case 49: //镶口
                    $xk = $spec['attr_value'];
                    break;
                case 56: //主石类型
                    $zs = $spec['attr_value'];
                    break;
                case 59: //主石大小
                    $zsdx = $spec['attr_value'];
                    break;
                case 61:
                case 62:
                    $goods = Yii::$app->services->goods->getGoodsInfo($spec['value_id']);
                    $val = $this->fun($goods['lang']['goods_spec']);

                    foreach ($val as $key => $value) {
                        $$key = $$key ? $$key.'/'.$value : $value;
                    }

                    if(isset($attrs[$goods['id']])) {
                        $attr2 = \Yii::$app->services->goods->formatGoodsAttr($attrs[$goods['id']], $goods['type_id']);
                        foreach ($attr2 as $vo2) {
                            $zssc = $zssc ? $zssc.'/'.implode('/', $vo2['value']) : implode('/', $vo2['value']);
                        }
                    }

                    break;
                default:
            }
        }

        return [
            'cs' => $cs,
            'sc' => $sc,
            'xk' => $xk,
            'zs' => $zs,
            'zsdx' => $zsdx,
            'zssc' => $zssc,
        ];
    }
    
    //序号，订单号，客户姓名，客户邮箱，客户手机号，是否测试，订单状态，支付状态，下单时间，客户备注，快递公司，快递单号，订单总金额，优惠后金额
    //
    //
    //商品款号，商品名称，成色，手寸，镶口，主石类型，主石大小，商品原价，商品实际成交价
    public function actionExportExcelInvoice()
    {
        $order_id = Yii::$app->request->get('order_id');
        if(!$order_id){
            return ResultHelper::json(422, '非法调用');
        }
        $result = Yii::$app->services->orderInvoice->getEleInvoiceInfo($order_id, LanguageEnum::EN_US);
        
        $template = Yii::getAlias('@storage') . '/backend/excels/order_invoice.xlsx';
        
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A2', 'Address: '.$result['sender_address']);
        $sheet->setCellValue('B4', '');
        $sheet->setCellValue('F4', $result['order_sn']);
        $sheet->setCellValue('B5', $result['realname']);
        $sheet->setCellValue('F5', date('d-m-Y',time()));
        $sheet->setCellValue('B6', $result['mobile']);
        $sheet->setCellValue('F6', Yii::t('pay', \common\enums\PayEnum::getValue($result['payment_type'], "payTypeName"), [], $result['language']));
        $sheet->setCellValue('B7', $result['address_details']);
        $sheet->setCellValue('F7', $result['zip_code']);
        
        $startRowNum = 9;
        $rowsNum = count($result['order_goods']);
        $row=$key = 0;
        $sum = 0;
        
        if($rowsNum > 1) {
            $sheet->insertNewRowBefore($startRowNum+1, $rowsNum - 1);
        }
        
        $html = <<<DOM
%s
%s %s
DOM;
        
        foreach ($result['order_goods'] as $key => $val) {
            $row = $startRowNum + $key;
            $sheet->setCellValue("A{$row}", $key+1);
            $sheet->setCellValue("B{$row}", $val['goods_sn']);
            //            $sheet->setCellValue("C{$row}", '');
            $sheet->setCellValue("D{$row}", $val['goods_name']);

            $attrs = [];
            if($val['cart_goods_attr']) {
                $cart_goods_attr = \GuzzleHttp\json_decode($val['cart_goods_attr'], true);
                if(!empty($cart_goods_attr) && is_array($cart_goods_attr))
                foreach ($cart_goods_attr as $k => $item) {
                    $key = $item['goods_id']??0;
                    $attrs[$key][$item['config_id']] = $item['config_attr_id'];
                }
            }

            $value = '';
            $goods_spec = '';
            if($val['goods_type']==19) {
                $value1 = '';
                $value2 = '';
                $goods_spec = '';
                $goods_spec1 = '';
                $goods_spec2 = '';
                if($val['goods_spec']) {
                    $val['goods_spec'] = \Yii::$app->services->goods->formatGoodsSpec($val['goods_spec']);
                    foreach ($val['goods_spec'] as $vo) {
                        if($vo['attr_id']==61) {
                            $goods = Yii::$app->services->goods->getGoodsInfo($vo['value_id']);
                            
                            foreach ($goods['lang']['goods_spec'] as $spec) {
                                $goods_spec1 .= $spec['attr_name'].":".$spec['attr_value']." ";
                            }

                            if(isset($attrs[$goods['id']])) {
                                $cart_goods_attr2 = \Yii::$app->services->goods->formatGoodsAttr($attrs[$goods['id']], $goods['type_id']);
                                foreach ($cart_goods_attr2 as $vo2) {
                                    $goods_spec1 .= $vo2['attr_name'].":".implode(',', $vo2['value'])." ";
                                }
                            }
                            
                            $value1 .= sprintf($html,
                                    $vo['attr_name'] . '：' . $goods['goods_name'],
                                    $goods['goods_sn'],
                                    $goods_spec1
                                    );
                            continue;
                        }
                        if($vo['attr_id']==62) {
                            $goods = Yii::$app->services->goods->getGoodsInfo($vo['value_id']);
                            
                            foreach ($goods['lang']['goods_spec'] as $spec) {
                                $goods_spec2 .= $spec['attr_name'].":".$spec['attr_value']." ";
                            }

                            if(isset($attrs[$goods['id']])) {
                                $cart_goods_attr2 = \Yii::$app->services->goods->formatGoodsAttr($attrs[$goods['id']], $goods['type_id']);
                                foreach ($cart_goods_attr2 as $vo2) {
                                    $goods_spec2 .= $vo2['attr_name'].":".implode(',', $vo2['value'])." ";
                                }
                            }
                            
                            $value2 .= sprintf($html,
                                    $vo['attr_name'] . '：' . $goods['goods_name'],
                                    $goods['goods_sn'],
                                    $goods_spec2
                                    );
                            continue;
                        }
                        $goods_spec .= $vo['attr_name'].":".$vo['attr_value']." ";
                    }
                }
                $value .= sprintf($html,
                        '',
                        '',
                        $goods_spec
                        );
                
                $value .= $value1;
                $value .= $value2;
            }
            else {
                if($val['goods_spec']) {
                    $val['goods_spec'] = \Yii::$app->services->goods->formatGoodsSpec($val['goods_spec']);
                    foreach ($val['goods_spec'] as $vo) {
                        $goods_spec .= $vo['attr_name'].":".$vo['attr_value']." ";
                    }
                }

                if(isset($attrs[0])) {
                    $val['cart_goods_attr'] = \Yii::$app->services->goods->formatGoodsAttr($attrs[0], $val['goods_type']);
                    foreach ($val['cart_goods_attr'] as $vo) {
                        $goods_spec .= $vo['attr_name'].":".implode(',', $vo['value'])." ";
                    }
                }

                $value = $goods_spec;
            }
            
            $sheet->setCellValue("E{$row}", $value);
            $sheet->setCellValue("F{$row}", $val['goods_price']. " ".$val['currency']);
            $sheet->setCellValue("G{$row}", $val['goods_num']);
            $sheet->setCellValue("H{$row}", $val['goods_price']*$val['goods_num'] . " ".$val['currency']);
        }
        
        $row++;
        $sheet->setCellValue("H{$row}", $result['coupon_amount'] . " ".$val['currency']);
        $row++;
        $sheet->setCellValue("H{$row}", $result['gift_card_amount'] . " ".$val['currency']);
        $row++;
        $sheet->setCellValue("G{$row}", ($key+1));
        $sheet->setCellValue("H{$row}", $result['order_pay_amount'] . " ".$val['currency']);
        
        // 清除之前的错误输出
        ob_end_clean();
        ob_start();
        
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;");
        header("Content-Disposition: inline;filename=invoice-{$result['order_sn']}.xlsx");
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        
        $spreadsheet->disconnectWorksheets();
        
        unset($spreadsheet);
        ob_end_flush();
        
        exit();
    }
    public function getExportGoods($_list) {

        $list = [];
        $specList = [];
        foreach ($_list as $order) {
            foreach ($order->goods as $goods) {
                $list[] = $goods;

                $specs = \Yii::$app->services->goods->formatGoodsSpec($goods->goods_spec);

                $attrs = [];
                if($goods->cart_goods_attr) {
                    $cart_goods_attr = \GuzzleHttp\json_decode($goods->cart_goods_attr, true);
                    if(!empty($cart_goods_attr) && is_array($cart_goods_attr))
                    foreach ($cart_goods_attr as $k => $item) {

                        $key = $item['goods_id']??$goods->goods_id;
                        $attrs[$key][$item['config_id']] = $item['config_attr_id'];

                    }
                }

                $zssc = '';
                foreach ($attrs as $key => $attr) {
                    if($key==$goods->goods_id) {
                        $attr2 = \Yii::$app->services->goods->formatGoodsAttr($attr, $goods->goods_type);
                        foreach ($attr2 as $vo2) {
                            $zssc = $zssc ? $zssc.'/'.implode('/', $vo2['value']) : implode('/', $vo2['value']);
                        }
                    }
                }

                $specList[$goods->id] = $this->fun($specs, $attrs);

                if($zssc) {
                    $specList[$goods->id]['zssc'] = $zssc;
                }

            }
        }

        $header = [
            ['序号', 'id'],
            ['订单号', 'id', 'function', function ($row) {
                return $row->order->order_sn;
            }],
            ['客户姓名', 'id', 'function', function ($row) {
                return $row->order->address->realname;
            }],
            ['客户邮箱', 'id', 'function', function ($row) {
                return $row->order->address->email;
            }],
            ['客户手机号', 'id', 'function', function ($row) {
                $html = "";
                if($row->order->address->mobile) {
                    $html .= $row->order->address->mobile_code.'-'.$row->order->address->mobile;
                }
                return $html;
            }],
            ['是否测试', 'id', 'function', function ($row) {
                return OrderStatusEnum::getValue($row->order->is_test, 'testStatus');
            }],
            ['订单状态', 'id', 'function', function ($row) {
                return OrderStatusEnum::getValue($row->order->order_status);
            }],
            ['支付状态', 'id', 'function', function ($row) {
                return \common\enums\PayStatusEnum::getValue($row->order->payment_status);
            }],
            ['下单时间', 'id', 'function', function ($row) {
                return Yii::$app->formatter->asDatetime($row->order->created_at);
            }],
            ['客户备注', 'id', 'function', function ($row) {
                return $row->order->buyer_remark;
            }],
            ['快递公司', 'id', 'function', function ($row) {
                return \Yii::$app->services->express->getExressName($row->order->express_id);
            }],
            ['快递单号', 'id', 'function', function ($row) {
                return $row->order->express_no;
            }],
            ['订单总金额', 'id', 'function', function ($row) {
                return $row->order->account->currency . ' ' . $row->order->account->order_amount;
            }],
            ['优惠后金额', 'id', 'function', function ($row) {
                $pay_amount = $row->order->account->pay_amount;
                if($row->order->account->currency == CurrencyEnum::TWD) {
                    $pay_amount = sprintf("%.2f", intval($pay_amount));
                }
                return $row->order->account->currency . ' ' . $pay_amount;
            }],

            ['商品款号', 'id', 'function', function ($row) {
                return $row->goods_sn;
            }],
            ['商品名称', 'id', 'function', function ($row) {
                return $row->goods_name;
            }],


            ['成色', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['cs'];
            }],
            ['手寸', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['sc'];
            }],
            ['镶口', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['xk'];
            }],
            ['主石类型', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['zs'];
            }],
            ['主石大小', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['zsdx'];
            }],
            ['主石色彩', 'id', 'function', function ($row) use($specList) {
                return $specList[$row->id]['zssc'];
            }],
            ['商品原价', 'id', 'function', function ($row) {
                return $row->order->account->currency . ' ' . $row->goods_price;
            }],
            ['商品实际成交价', 'id', 'function', function ($row) {
                $goods_pay_price = $row->goods_pay_price;
                if($row->order->account->currency == CurrencyEnum::TWD) {
                    $goods_pay_price = sprintf("%.2f", intval($goods_pay_price));
                }
                return $row->order->account->currency . ' ' . $goods_pay_price;
            }],
        ];

        return ExcelHelper::exportData($list, $header, '订单商品统计导出_' . date('YmdHis',time()));
    }
}

