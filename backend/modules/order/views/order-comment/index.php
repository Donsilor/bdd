<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '评价管理');
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
                                'label' => '产品名称',
                                'value' => function($row) {
                                    return $row->style->lang->style_name;
                                }
                            ],
                            [
                                'label' => '款号',
                                'value' => function($row) {
                                    return $row->style->style_sn;
                                }
                            ],
//                            [
//                                'attribute' => 'order_sn',
//                                'filter' => Html::activeTextInput($searchModel, 'order_sn', [
//                                    'class' => 'form-control',
//                                ]),
//                                'format' => 'raw',
//                                'value' => function($model) {
//                                    return Html::a($model->order_sn, ['view', 'id' => $model->id], ['style'=>"text-decoration:underline;color:#3c8dbc"]);
//                                }
//                            ],
//                            [
//                                'attribute' => 'order_amount',
//                                'value' => function ($model) {
//                                    return sprintf('(%s)%s', $model->currency, $model->order_amount);
//                                }
//                            ],
                            [
                                'label' => '评价时间',
                                'attribute' => 'created_at',
                                'filter' => DateRangePicker::widget([    // 日期组件
                                    'model' => $searchModel,
                                    'attribute' => 'created_at',
                                    'value' => '',
                                    'options' => ['readonly' => true, 'class' => 'form-control','style'=>'background-color:#fff;'],
                                    'pluginOptions' => [
                                        'format' => 'yyyy-mm-dd',
                                        'locale' => [
                                            'separator' => '/',
                                            'cancelLabel'=> '清空',
                                        ],
                                        'endDate' => date('Y-m-d', time()),
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
                                'attribute' => 'from',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'from', \common\enums\OrderFromEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\OrderFromEnum::getValue($model->from);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '评价内容',
                                'attribute' => 'content',
                                'value' => function ($model) {
                                    return $model->content;
                                },
                            ],
                            [
                                'filter' => false,
                                'label' => '审核人',
                                'attribute' => 'admin_id',
                                'value' => function ($model) {
                                    return $model->admin_id;
                                },
                            ],
//                            [
//                                'attribute' => 'ip',
//                                'value' => function ($model) {
//                                    return $model->ip."(".$model->ip_location.")";
//                                },
//                            ],
//                            [
//                                'attribute' => 'ip_area_id',
//                                'headerOptions' => ['class' => 'col-md-1'],
//                                'filter' => Html::activeDropDownList($searchModel, 'ip_area_id', \common\enums\AreaEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
//                                'value' => function ($model) {
//                                    return \common\enums\AreaEnum::getValue($model->ip_area_id);
//                                },
//                                'format' => 'raw',
//                            ],
                            [
                                'attribute' => 'status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'status', \common\enums\OrderCommentStatusEnum::getMap(), [
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\OrderCommentStatusEnum::getValue($model->status);
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
                                        if($model->status == \common\enums\OrderStatusEnum::ORDER_PAID) {
//                                            return Html::batchAudit(['ajax-batch-audit'], '审核', [
                                            //'class'=>'label bg-green'
//                                            ]);
                                            return Html::edit(['edit-audit', 'id' => $model->id], '审核', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'class'=>'btn bg-green btn-sm'
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
    function audit(id) {
        let _id = [];
        if(id===undefined) {
            _id= []
        }
        else {
            _id.push(id)
        }
    }

    (function ($) {
        /**
         * 头部文本框触发列表过滤事件
         */
        $(".top-form input,select").change(function () {
            $(".filters input[name='" + $(this).attr('name') + "']").val($(this).val()).trigger('change');
        });

        $("[data-krajee-daterangepicker]").on("cancel.daterangepicker", function () {
            $(this).val("").trigger("change");
        });


    })(window.jQuery);
</script>