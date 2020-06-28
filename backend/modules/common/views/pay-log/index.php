<?php

use yii\grid\GridView;
use common\helpers\Html;
use common\enums\StatusEnum;
use common\enums\PayEnum;
use common\helpers\AmountHelper;

$this->title = '支付日志';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
            </div>
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    //重新定义分页样式
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        'id',
                        'out_trade_no',
                        [
                            'label' => '支付金额',
                            'value' => function ($model) {
                                $total_fee = $model->total_fee > 0 ? $model->total_fee  : 0;
                                $pay_fee = $model->pay_fee > 0 ? $model->pay_fee  : 0;
                                $str = '应付金额：' . AmountHelper::outputAmount($total_fee,2,$model->currency) . '<br>';
                                $str .= '实际支付：' . AmountHelper::outputAmount($pay_fee,2,$model->currency);
                                return $str;
                            },
                            'format' => 'raw',
                        ],
                        [
                            'label' => '支付来源',
                            'attribute'=>'order_sn',
                            'value' => function ($model) {
                                $str = '订单编号：' . $model->order_sn . '<br>';
                                $str .= '订单类型：' . $model->order_group. '<br>';
                                if($model->pay_time) {
                                    $str .= '支付时间：' . Yii::$app->formatter->asDatetime($model->pay_time) . '<br>';
                                }
                                return $str;
                            },
                            'filter' => Html::activeTextInput($searchModel, 'order_sn', [
                                'class' => 'form-control',
                            ]),
                            'format' => 'raw',
                        ],
                        [
                            'label' => '支付类型',
                            'value' => function ($model) {
                                return PayEnum::getValue($model->pay_type);
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'pay_type', PayEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'format' => 'raw',
                        ],
                        [
                            'label' => '状态',                            
                            'value' => function ($model) {
                                if ($model->pay_status == StatusEnum::ENABLED) {
                                    return '<span class="label label-primary">支付成功</span>';
                                } else {
                                    return '<span class="label label-danger">未支付</span>';
                                }
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'pay_status', \common\enums\PayStatusEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]),
                            'format' => 'raw',
                        ],
//                        [
//                            'attribute' => 'created_at',
//                            'filter' => false, //不显示搜索框
//                            'format' => ['date', 'php:Y-m-d H:i:s'],
//                        ],
                        [
                            'attribute' => 'created_at',
                            'filter' => \kartik\daterange\DateRangePicker::widget([    // 日期组件
                                'model' => $searchModel,
                                'attribute' => 'created_at',
                                'value' => $searchModel->created_at,
                                'options' => ['readonly' => true,'class'=>'form-control','style'=>'background-color:#fff;'],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd',
                                    'locale' => [
                                        'separator' => '/',
                                        'cancelLabel'=> '清空',
                                    ],
                                    'endDate' => date('Y-m-d',time()),
                                    'todayHighlight' => true,
                                    'autoclose' => true,
                                    'todayBtn' => 'linked',
                                    'clearBtn' => true,
                                ],
                            ]),
                            'value' => function ($model) {
                                return Yii::$app->formatter->asDatetime($model->created_at);
                            },
                            'format' => 'raw',
                        ],
                        [
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::linkButton(['view', 'id' => $model->id], '查看详情', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>

            </div>
        </div>
    </div>
</div>

<script>

    (function ($) {

        $("[data-krajee-daterangepicker]").on("cancel.daterangepicker", function () {
            $(this).val("").trigger("change");
        });

    })(window.jQuery);
</script>
