<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '游客订单');
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
                                'attribute' => 'is_test',
                                'headerOptions' => [
                                    'class' => 'col-md-1',
                                    'style' => 'width:80px;'
                                ],
                                'filter' => Html::activeDropDownList($searchModel, 'is_test', OrderStatusEnum::testStatus(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                    'style' => 'width:78px;'
                                ]),
                                'value' => function ($model) {
                                    if($model->is_test) {
                                        $value = "<span class='red'>";
                                        $value .= \common\enums\OrderStatusEnum::getValue($model->is_test, 'testStatus');
                                        $value .= "</span>";
                                        return $value;
                                    }
                                    return '';
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'order_sn',
                                'filter' => Html::activeTextInput($searchModel, 'order_sn', [
                                    'class' => 'form-control',
                                ]),
                                'format' => 'raw',
                                'value' => function($model) {
                                    return Html::a($model->order_sn, ['view', 'id' => $model->id], ['style'=>"text-decoration:underline;color:#3c8dbc"]);
                                }
                            ],
                            [
                                'attribute' => 'order_amount',
                                'value' => function ($model) {
                                    return sprintf('(%s)%s', $model->currency, $model->order_amount);
                                }
                            ],
                            [
                                'label' => '优惠后金额',
                                'value' => function ($model) {
                                    $order_amount = bcsub($model->order_amount, $model->discount_amount, 2);

                                    if($model->currency == \common\enums\CurrencyEnum::TWD) {
                                        $order_amount = sprintf('%.2f', intval($order_amount));
                                    }

                                    return sprintf('(%s)%s', $model->currency, $order_amount);
                                }
                            ],
                            [
                                'attribute' => 'order_from',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'order_from', \common\enums\OrderFromEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\OrderFromEnum::getValue($model->order_from);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'ip',
                                'value' => function ($model) {
                                    return $model->ip."(".$model->ip_location.")";
                                },
                            ],
                            [
                                'attribute' => 'ip_area_id',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'ip_area_id', \common\enums\AreaEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\AreaEnum::getValue($model->ip_area_id);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'status', ['未支付', '已支付'], [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return array_get(['未支付', '已支付'], $model->status);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '跟进状态',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'followed_status',common\enums\FollowStatusEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    $value = common\enums\FollowStatusEnum::getValue($model->followed_status);
                                    $value .= $model->follower ? "<br />" . $model->follower->username : '';
                                    return $value;
                                },
                                'format' => 'raw',
                            ],
                            [
                                'header' => "操作",
                                //'headerOptions' => ['class' => 'col-md-1'],
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{follower}',
                                'buttons' => [
                                    'follower' => function ($url, $model, $key) {
                                        return Html::edit(['edit-follower', 'id' => $model->id], '跟进', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class'=>'btn btn-default btn-sm'
                                        ]);
                                    }
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