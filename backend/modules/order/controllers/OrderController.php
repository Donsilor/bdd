<?php

namespace backend\modules\order\controllers;

use backend\controllers\BaseController;
use common\enums\CurrencyEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayStatusEnum;
use common\helpers\ResultHelper;
use common\models\order\OrderGoods;
use common\models\order\OrderGoodsLang;
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
                'pageSize' => $this->pageSize,
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


    public function actionEleInvoice(){

        $order_id = Yii::$app->request->get("order_id");
        if(!$order_id){
            return ResultHelper::json(422, '非法调用');
        }
        $order = Order::find()
            ->where(['id'=>$order_id])
            ->one();
        $language = $order->language;
        $result = array(
            'invoice_date' => $order->delivery_time,
            'sender_name' => '',
            'sender_address'=> '',
            'shipper_name' => '',
            'shipper_address' => '',
            'realname' => $order->address->realname,
            'address_details' => $order->address->address_details,
            'express_no' => $order->express_no,
            'express_company_name' => $order->express_id ? $order->express->lang->express_name:'',
            'delivery_time' => $order->delivery_time,
            'country' => $order->address->country_name,
            'currency' => CurrencyEnum::getValue($order->account->currency),
            'order_amount' => $order->account->order_amount
        );


        $order_invoice_exe = OrderInvoiceEle::find()
            ->where(['order_id'=>$order_id])
            ->one();
        if($order_invoice_exe){
            $result['invoice_date'] = $order_invoice_exe['invoice_date'] ? $order_invoice_exe['invoice_date'] : $result['invoice_date'];
            $result['sender_name'] = $order_invoice_exe['sender_name'] ? $order_invoice_exe['sender_name'] : $result['sender_name'];
            $result['sender_address'] = $order_invoice_exe['sender_address'] ? $order_invoice_exe['sender_address'] : $result['sender_address'];
            $result['shipper_name'] = $order_invoice_exe['shipper_name'] ? $order_invoice_exe['shipper_name'] : $result['shipper_name'];
            $result['shipper_address'] = $order_invoice_exe['shipper_address'] ? $order_invoice_exe['shipper_address'] : $result['shipper_address'];
            $result['express_company_name'] = $order_invoice_exe['express_company_name'] ? $order_invoice_exe['express_company_name'] : $result['express_company_name'];
            $result['express_no'] = $order_invoice_exe['express_no'] ? $order_invoice_exe['express_no'] : $result['express_no'];
            $result['delivery_time'] = $order_invoice_exe['delivery_time'] ? $order_invoice_exe['delivery_time'] : $result['delivery_time'];
            $result['delivery_time'] = $order_invoice_exe['delivery_time'] ? $order_invoice_exe['delivery_time'] : $result['delivery_time'];
            $language = $order_invoice_exe['language'] ? $order_invoice_exe['language'] : $language;
        }


        //商品明细
        $order_goods = OrderGoods::find()->alias('m')
           ->leftJoin(OrderGoodsLang::tableName().'lang','m.id=lang.master_id and lang.language="'.$language.'"')
            ->where(['order_id'=>$order_id])
            ->select(['lang.goods_name','m.goods_num','m.goods_pay_price','m.currency'])
            ->asArray()
            ->all();
        $result['order_goods'] = $order_goods;



        //时间转换
        if($language == 'en-US'){
            $result['invoice_date'] = $result['invoice_date'] ? date('d-m-Y',$result['invoice_date']):date('d-m-Y',time());
            $result['delivery_time'] = $result['delivery_time'] ? date('d-m-Y',$result['delivery_time']):'';

            $city_name = $order->address->city_name ? ','.$order->address->city_name : '';
            $province_name = $order->address->province_name ? ','.$order->address->province_name : '';
            $country_name = $order->address->country_name ? ','.$order->address->country_name : '';
            $result['address_details'] = $order->address->address_details .$city_name.$province_name.$country_name ;
        }else{
            $result['invoice_date'] = $result['invoice_date'] ? date('Y-m-d',$result['invoice_date']):date('Y-m-d',time());
            $result['delivery_time'] = $result['delivery_time'] ? date('Y-m-d',$result['delivery_time']):'';

            $city_name = $order->address->city_name ? $order->address->city_name . ' ' : '';
            $province_name = $order->address->province_name ? $order->address->province_name . ' ' : '';
            $country_name = $order->address->country_name ? $order->address->country_name .' ' : '';
            $result['address_details'] = $country_name . $province_name . $city_name . $order->address->address_details ;
        }


        switch ($language){
            case 'en-US':
                $template = 'ele-invoice-us.php';
                break;
            case 'zh-CN':
                $template = 'ele-invoice-zh.php';
                break;
            default: $template = 'ele-invoice.php';


        }


        return $this->render($template, [
            'result'=>$result
        ]);
    }



    public  function actionEleInvoiceAjaxEdit(){
        $id = Yii::$app->request->get('id');
        $order_id = Yii::$app->request->get('order_id');
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);

        if(!$id){
            $model = OrderInvoiceEle::find()
                ->where(['order_id'=>$order_id])
                ->one();
            $id = $model->id;
        }

        $this->modelClass = OrderInvoiceEle::class;
        $model = $this->findModel($id);
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if(false === $model->save()){
                throw new Exception($this->getError($model));
            }
//            return $this->redirect($returnUrl);
            return $this->message("保存成功", $this->redirect($returnUrl), 'success');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'order_id'=>$order_id,
            'returnUrl'=>$returnUrl
        ]);

    }

    public function actionPdf(){
        $content = $this->renderPartial('aaa');

        // setup kartik\mpdf\Pdf component
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
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
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:18px}',
            // set mPDF properties on the fly
            'options' => ['title' => 'Krajee Report Title'],
            // call mPDF methods on the fly
            'methods' => [
                'SetHeader'=>['Krajee Report Header'],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        // return the pdf output as per the destination setting
        return $pdf->render();
    }

}

