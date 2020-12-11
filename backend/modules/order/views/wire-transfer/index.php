<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '电汇管理');
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <div class="tab-content">
                <div class="active tab-pane">
                    <?= GridView::widget([
                        'id'=>'grid',
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        //重新定义分页样式
                        'tableOptions' => ['class' => 'table table-hover'],
                        'columns' => [
                            [
                                'class'=>'yii\grid\CheckboxColumn',
                                'name'=>'id',  //设置每行数据的复选框属性
                                'headerOptions' => ['width'=>'30'],
                            ],
                            [
                                'attribute' => 'id',
                            ],
                            [
                                'attribute' => 'order.order_sn'
                            ],
                            [
                                'label' => '订单金额',
                                'attribute' => 'order.account.order_amount',
                                'value' => function($model)
                                {
                                    return \common\helpers\AmountHelper::outputAmount($model->order->account->order_amount, 2, $model->order->account->currency);
                                }
                            ],
//                            [
//                                'label' => '应支付金额',
//                                'attribute' => 'order.account.pay_amount',
//                                'value' => function($model) {
//                                    $cardUseAmount = \services\market\CardService::getUseAmount($model->order_id);
//                                    $receivable = bcsub(bcsub($model->order->account->order_amount, $cardUseAmount, 2), $model->order->account->discount_amount, 2);
//                                    return \common\helpers\AmountHelper::outputAmount($receivable, 2, $model->order->account->currency);
//                                }
//                            ],
                            [
                                'attribute' => 'order.order_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => false,
//                                'filter' => Html::activeDropDownList($searchModel, 'order_status', common\enums\OrderStatusEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
                                'value' => function ($model) {
                                    return common\enums\OrderStatusEnum::getValue($model->order->order_status);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '收款账号',
                                'attribute' => 'account'
                            ],
                            [
                                'label' => '审核状态',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'collection_status',\common\enums\WireTransferEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\WireTransferEnum::getValue($model->collection_status);
                                },
                                'format' => 'raw',
                            ],
//                            [
//                                'label' => '出纳审核状态'
//                            ],
//                            [
//                                'label' => '会计审核状态'
//                            ],
                            [
                                'attribute' => 'created_at',
                                'filter' => false,
//                                'filter' => DateRangePicker::widget([    // 日期组件
//                                    'model' => $searchModel,
//                                    'attribute' => 'created_at',
//                                    'value' => '',
//                                    'options' => ['readonly' => true, 'class' => 'form-control',],
//                                    'pluginOptions' => [
//                                        'format' => 'yyyy-mm-dd',
//                                        'locale' => [
//                                            'separator' => '/',
//                                        ],
//                                        'endDate' => date('Y-m-d', time()),
//                                        'todayHighlight' => true,
//                                        'autoclose' => true,
//                                        'todayBtn' => 'linked',
//                                        'clearBtn' => true,
//                                    ],
//                                ]),
                                'value' => function ($model) {
                                    return Yii::$app->formatter->asDatetime($model->created_at);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'header' => "操作",
                                //'headerOptions' => ['class' => 'col-md-1'],
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{audit}',
                                'buttons' => [
                                    'audit' => function ($url, $model, $key) {
                                        if($model->collection_status == 1) {
                                            //会计审核
                                            return Html::edit(['ajax-edit', '33id' => 123], '会计审核', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModalLg',
                                            ]);
                                        }
                                        elseif (1) {
                                            //出纳审核
                                            return Html::edit(['ajax-edit', 'id'=>$model->id], '审核', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModalLg',
                                            ]);
                                        }
                                        return null;
                                    },
                                ],
                            ],
                        ],
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // function audit(id) {
    //     let _id = [];
    //     if(id===undefined) {
    //         _id= []
    //     }
    //     else {
    //         _id.push(id)
    //     }
    // }
    //
    // (function ($) {
    //     /**
    //      * 头部文本框触发列表过滤事件
    //      */
    //     $(".top-form input,select").change(function () {
    //         $(".filters input[name='" + $(this).attr('name') + "']").val($(this).val()).trigger('change');
    //     });
    //
    //
    // })(window.jQuery);
</script>