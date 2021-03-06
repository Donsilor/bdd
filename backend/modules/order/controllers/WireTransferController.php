<?php


namespace backend\modules\order\controllers;


use backend\controllers\BaseController;
use backend\modules\order\forms\WireTransferForm;
use backend\modules\order\forms\WireTransferForm2;
use common\enums\AuditStatusEnum;
use common\enums\OrderStatusEnum;
use common\enums\PayStatusEnum;
use common\enums\StatusEnum;
use common\enums\WireTransferEnum;
use common\models\base\SearchModel;
use common\models\common\PayLog;
use common\models\order\Order;
use common\models\pay\WireTransfer;
use services\order\OrderLogService;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class WireTransferController extends BaseController
{

    /**
     * @var Order
     */
    public $modelClass = WireTransfer::class;

    public function actionIndex()
    {
        $queryParams = Yii::$app->request->queryParams;
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
            'relations' => [
                'orderTourist' => 'is_test'
            ]
        ]);

        if(!isset($queryParams['SearchModel']['orderTourist.is_test'])) {
            $queryParams['SearchModel']['orderTourist.is_test'] = 0;
        }

        $searchModel->setAttributes(['orderTourist.is_test' => 0]);

        $dataProvider = $searchModel->search($queryParams, []);

        if(!Yii::$app->request->get('test'))
            $dataProvider->query->andWhere(['=', 'common_pay_wire_transfer.member_id', 0]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * ajax编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit()
    {
//        $returnUrl = \Yii::$app->request->get('returnUrl',['index']);

        $returnUrl = \Yii::$app->getRequest()->getReferrer();

        $where = [];

        $id = Yii::$app->request->get('id', null);
        $orderSn = Yii::$app->request->get('order_sn', null);

        if($id) {
            $where['id'] = $id;
        }
        if($orderSn) {
            $where['order_sn'] = $orderSn;
        }

        $model = WireTransferForm::findOne($where);

        // ajax 校验
        $this->activeFormValidate($model);

        $audit_status = $model->order->audit_status;

        if ($model->load(\Yii::$app->request->post())) {

            try {
                $trans = \Yii::$app->db->beginTransaction();

                if(!$model->save()) {
                    throw new \Exception($this->getError($model));
                }


                if($model->collection_status==WireTransferEnum::CONFIRM) {
                    //支付记录确认
                    $payModel = PayLog::findOne(['out_trade_no'=>$model['out_trade_no']]);

                    $update = [
                        'pay_fee' => $payModel->total_fee,
                        'pay_status' => PayStatusEnum::PAID,
                        'pay_time' => time(),
                    ];

                    $payModel->setAttributes($update);

                    if(!$payModel->save()) {
                        throw new \Exception($this->getError($payModel));
                    }

                    //更新订单状态
                    Yii::$app->services->pay->notify($payModel, null);


                    //更新订单审核状态
                    $model->order->status = AuditStatusEnum::PASS;
                    $model->order->audit_status = OrderStatusEnum::ORDER_AUDIT_YES;
                    $model->order->order_status = OrderStatusEnum::ORDER_CONFIRM;//已审核，代发货

                    if(false  === $model->order->save()) {
                        throw new \Exception($this->getError($model));
                    }
                }

                OrderLogService::audit($model->order, [[
                    'audit_status'=>OrderStatusEnum::getValue($model->order->audit_status, 'auditStatus')
                ], [
                    'audit_status'=>OrderStatusEnum::getValue($audit_status, 'auditStatus')
                ]]);

                $trans->commit();
            } catch (\Exception $exception) {

                $trans->rollBack();
                return $this->message($exception->getMessage(), $this->redirect($returnUrl), 'error');
            }

            $this->redirect($returnUrl);
        }

        //\Yii::$app->cache->set('actionAjaxEdit-'.\Yii::$app->getUser()->id, true);

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * ajax编辑/创建 (游客电汇支付)
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit2()
    {
//        $returnUrl = \Yii::$app->request->get('returnUrl',['index']);

        $returnUrl = \Yii::$app->getRequest()->getReferrer();

        $where = [];

        $id = Yii::$app->request->get('id', null);
        $orderId = Yii::$app->request->get('order_id', null);

        if($id) {
            $where['id'] = $id;
        }
        if($orderId) {
            $where['order_id'] = $orderId;
        }

        $model = WireTransferForm2::findOne($where);

        // ajax 校验
        $this->activeFormValidate($model);

//        $audit_status = $model->order->audit_status;

        if ($model->load(\Yii::$app->request->post())) {

            try {
                $trans = \Yii::$app->db->beginTransaction();

                if(!$model->save()) {
                    throw new \Exception($this->getError($model));
                }


                if($model->collection_status==WireTransferEnum::CONFIRM) {
                    //支付记录确认
                    $payModel = PayLog::findOne(['out_trade_no'=>$model['out_trade_no']]);

                    $update = [
                        'pay_fee' => $payModel->total_fee,
                        'pay_status' => PayStatusEnum::PAID,
                        'pay_time' => time(),
                    ];

                    $payModel->setAttributes($update);

                    if(!$payModel->save()) {
                        throw new \Exception($this->getError($payModel));
                    }

                    //更新订单状态
                    Yii::$app->services->pay->notify($payModel, null);

                    $order = $model->orderTourist->order;
                    //更新订单审核状态
                    $order->status = AuditStatusEnum::PASS;
                    $order->audit_status = OrderStatusEnum::ORDER_AUDIT_YES;
                    $order->order_status = OrderStatusEnum::ORDER_CONFIRM;//已审核，代发货

                    if(false  === $order->save()) {
                        throw new \Exception($this->getError($model));
                    }

                    OrderLogService::audit($order, [[
                        'audit_status'=>OrderStatusEnum::getValue($order->audit_status, 'auditStatus')
                    ], [
                        'audit_status'=>OrderStatusEnum::getValue(OrderStatusEnum::ORDER_AUDIT_NO, 'auditStatus')
                    ]]);
                }

                $trans->commit();
            } catch (\Exception $exception) {

                $trans->rollBack();
                return $this->message($exception->getMessage(), $this->redirect($returnUrl), 'error');
            }

            return $this->message("操作成功", $this->redirect($returnUrl), 'success');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}