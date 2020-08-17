<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '订单');
$this->params['breadcrumbs'][] = ['label' => $this->title];

$order_status = Yii::$app->request->get('order_status', -1);
$params = Yii::$app->request->queryParams;
$export_param = http_build_query($searchModel)."&order_status={$order_status}";

$OrderStatusEnum = common\enums\OrderStatusEnum::getMap();

unset($OrderStatusEnum[common\enums\OrderStatusEnum::ORDER_FINISH]);

$OrderStatusEnum['1'] = '已退款';
$OrderStatusEnum[common\enums\OrderStatusEnum::ORDER_PAID] = '已付款/待审核';

?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li<?php if (Yii::$app->request->get('order_status', -1) == -1) echo ' class="active"' ?>><a href="<?= Url::to(['order/index']) ?>"> 全部（<?= \common\models\order\Order::getCountByOrderStatus() ?>）</a></li>
                <li<?php if (Yii::$app->request->get('order_status', -1) == 11) echo ' class="active"' ?>><a href="<?= Url::to(['order/index', 'order_status'=>11]) ?>" class="red"> 电汇（<?= \common\models\order\Order::getCountByOrderStatus(11) ?>）</a></li>
                <?php foreach ($OrderStatusEnum as $statusValue => $statusName) { ?>
                    <li<?php if (Yii::$app->request->get('order_status', -1) == $statusValue) echo ' class="active"' ?>>
                        <a href="<?= Url::to(['order/index', 'order_status' => $statusValue]) ?>"><?= $statusName ?>（<?= \common\models\order\Order::getCountByOrderStatus($statusValue) ?>）</a>
                    </li>
                <?php } ?>
                <li class="pull-right">
                    <div class="box-header box-tools">
                        <?= Html::a('导出Excel',['export']+$params) ?>
                    </div>
                </li>

            </ul>

            <div class="tab-content">
                <div class="box-body top-form">
                    <div class="row col-sm-12">
                        <div class="col-sm-3">
                            <?= $searchModel->model->getAttributeLabel('payment_type') ?>：<br/>
                            <?= Html::activeDropDownList($searchModel, 'payment_type', \common\enums\PayEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                        <div class="col-sm-3">
                            <?= $searchModel->model->getAttributeLabel('language') ?>：<br/>
                            <?= Html::activeDropDownList($searchModel, 'language', \common\enums\LanguageEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                        <div class="col-sm-3">
                            ip归属地区：<br/>
                            <?= Html::activeDropDownList($searchModel, 'ip_area_id', \common\enums\AreaEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                    </div>
                    <div class="row col-sm-12">
<!--                        <div class="col-sm-3">-->
<!--                            --><?//= $searchModel->model->getAttributeLabel('refund_status') ?><!--：<br/>-->
<!--                            --><?//= Html::activeDropDownList($searchModel, 'refund_status', OrderStatusEnum::refundStatus(), [
//                                'prompt' => '全部',
//                                'class' => 'form-control',
//                            ]);
//                            ?>
<!--                        </div>-->
                        <div class="col-sm-3">
                            <?= $searchModel->model->getAttributeLabel('discount_type') ?>：<br/>
                            <?= Html::activeCheckboxList($searchModel, 'discount_type', [
                                'coupon' => '优惠券',
                                'discount' => '折扣',
                                'card' => '购物卡',
                            ], [
                                'prompt' => '全部',
                                'class' => 'form-control',
                                'value' => explode(',', $searchModel->discount_type)
                            ]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
                <div class="active tab-pane">
                    <?php $widgetData = [
                        'id'=>'grid',
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        //重新定义分页样式
                        'tableOptions' => ['class' => 'table table-hover'],
                        'columns' => [
//                            [
//                                'class' => 'yii\grid\SerialColumn',
//                                'visible' => false, // 不显示#
//                            ],
                            [
                                'class'=>'yii\grid\CheckboxColumn',
                                'name'=>'id',  //设置每行数据的复选框属性
                                'headerOptions' => ['width'=>'30'],
                            ],
                            [
                                'attribute' => 'id',
                                'filter' =>
                                    Html::activeTextInput($searchModel, 'member.email', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'payment_type', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'language', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'ip_area_id', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'refund_status', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'discount_type', [
                                        'class' => 'hidden',
                                    ])
                            ],
                            [
                                'attribute' => 'created_at',
                                'filter' => DateRangePicker::widget([    // 日期组件
                                    'model' => $searchModel,
                                    'attribute' => 'created_at',
                                    'value' => '',
                                    'options' => ['readonly' => true, 'class' => 'form-control', 'style'=>'background-color:#fff;width:100px;'],
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
                                    return Html::a($model->order_sn, ['view', 'id' => $model->id], ['style'=>"text-decoration:underline;color:#3c8dbc", 'class'=>'openContab']);
                                }
                            ],
                            [
                                'attribute' => 'address.realname',
                                'filter' => Html::activeTextInput($searchModel, 'address.realname', [
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return $model->address['realname'];
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '联系方式',
                                'attribute' => 'address.mobile',
                                'filter' => Html::activeTextInput($searchModel, 'address.mobile', [
                                    'class' => 'form-control',
                                ]),
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $html = '';
                                    if($model->address->mobile) {
                                        $html .= $model->address->mobile_code.'-'.$model->address->mobile;
                                    }
                                    if($model->address->email) {
                                        if(!empty($html)) {
                                            $html .= '<br/>';
                                        }
                                        $html .= $model->address->email;
                                    }
                                    return $html;
                                }
                            ],
                            [
                                'attribute' => 'account.order_amount',
                                'filter' => Html::activeTextInput($searchModel, 'account.order_amount', [
                                        'class' => 'form-control',
                                ]),
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if($model->account->paid_currency && $model->account->paid_currency != $model->account->currency) {
                                        $amount = \Yii::$app->services->currency->exchangeAmount($model->account->order_amount, 2, $model->account->paid_currency, $model->account->currency);
                                        return sprintf('(%s)%s', $model->account->paid_currency, $amount);
                                    }
                                    else {
                                        return sprintf('(%s)%s', $model->account->currency, $model->account->order_amount);
                                    }
                                }
                            ],
                            [
                                'label' => '优惠后金额',
                                'value' => function ($model) {
                                    if($model->account->paid_currency) {
                                        return sprintf('(%s)%s', $model->account->paid_currency, $model->account->paid_amount);
                                    } else {
                                        return sprintf('(%s)%s', $model->account->currency, $model->account->pay_amount);//bcsub($model->account->order_amount-$model->account->coupon_amount-$model->account->card_amount, $model->account->discount_amount, 2));
                                    }
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
                                'attribute' => 'payment_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'payment_status', \common\enums\PayStatusEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    $str = \common\enums\PayStatusEnum::getValue($model->payment_status);
                                    if($model->payment_type) {
                                        if($model->payment_type==11) {
                                            $str   .= '<br/><span class="red">'.(\common\enums\PayEnum::getValue($model->payment_type)).'</span>';
                                        }
                                        else {
                                            $str   .= '<br/>'.(\common\enums\PayEnum::getValue($model->payment_type));
                                        }
                                    }
                                    return $str;                                   
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'order_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'order_status', (common\enums\OrderStatusEnum::getMap()+['1'=>'已关闭']), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return $model->refund_status == OrderStatusEnum::ORDER_REFUND_YES ?'已关闭':common\enums\OrderStatusEnum::getValue($model->order_status);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'refund_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'refund_status', OrderStatusEnum::refundStatus(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return OrderStatusEnum::getValue($model->refund_status, 'refundStatus');
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
//                            [
//                                'label' => '审核状态',
//                                'headerOptions' => ['class' => 'col-md-1'],
//                                'filter' => Html::activeDropDownList($searchModel, 'audit_status',common\enums\OrderStatusEnum::auditStatus(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
//                                'value' => function ($model) {
//                                    $value = common\enums\OrderStatusEnum::getValue($model->audit_status, 'auditStatus');
//                                    return $value?:'未审核';
//                                },
//                                'format' => 'raw',
//                            ],
                            [
                                'label' => '客户备注',
                                'filter' => false,
                                'value' => function($model) {
                                    return \common\helpers\StringHelper::truncate($model->buyer_remark, 15);
                                }
                            ],
                            [
                                'header' => "操作",
                                //'headerOptions' => ['class' => 'col-md-1'],
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{audit} {delivery} {follower} {cancel} {refund} {wiretransfer}',
                                'buttons' => [
                                    'follower' => function ($url, $model, $key) {
                                        return Html::edit(['edit-follower', 'id' => $model->id], '跟进', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class'=>'btn btn-default btn-sm'
                                        ]);                                        
                                    },
                                    'audit' => function ($url, $model, $key) {
                                        if($model->order_status == \common\enums\OrderStatusEnum::ORDER_PAID) {
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
                                    'delivery' => function ($url, $model, $key) {
                                        if($model->order_status == \common\enums\OrderStatusEnum::ORDER_CONFIRM) {
                                            return  Html::edit(['edit-delivery', 'id' => $model->id], '发货', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'class'=>'btn btn-success btn-sm'
                                            ]);
                                        }
                                        return null;
                                    },
                                    'cancel' => function($url, $model, $key) {
                                        if($model->order_status != \common\enums\OrderStatusEnum::ORDER_UNPAID) {
                                            return null;
                                        }

                                        return Html::edit(['edit-cancel', 'id' => $model->id], '取消', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class'=>'btn btn-danger btn-sm'
                                        ]);

                                    },
                                    'refund' => function($url, $model, $key) {
                                        if($model->order_status <= \common\enums\OrderStatusEnum::ORDER_UNPAID) {
                                            return null;
                                        }
                                        return Html::edit(['edit-refund', 'id' => $model->id], '退款', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class'=>'btn btn-danger btn-sm'
                                        ]);
                                    },
                                    'wiretransfer' => function($url, $model, $key) {
                                        if(!$model->wireTransfer || $model->wireTransfer->collection_status==1 || Yii::$app->request->get('order_status', -1)==10) {
                                            return null;
                                        }

                                        //出纳审核
                                        return Html::edit(['wire-transfer/ajax-edit', 'order_id'=>$model->id], '审核', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModalLg',
                                        ]);
                                    }
                                ],
                            ],
                        ],
                    ];

                    if(Yii::$app->request->get('order_status')==11) {
                        array_splice($widgetData['columns'], 12, 0, [[
                            'label' => '电汇审核状态',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'filter' => Html::activeDropDownList($searchModel, 'wireTransfer.collection_status',\common\enums\WireTransferEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]),
                            'value' => function ($model) {
                                return common\enums\WireTransferEnum::getValue($model->wireTransfer->collection_status);
                            },
                            'format' => 'raw',
                        ]]);
                    }
                ?>
                <?= GridView::widget($widgetData);?>
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
            let name = $(this).attr('name');
            let val = '';
            if($(this).attr('type')==="checkbox") {
                let vals = [];
                $(".top-form input[name='"+$(this).attr('name')+"']").each(function (i, v) {
                    if($(v).prop("checked")) {
                        vals.push($(v).val());
                    }
                });
                name = name.substr(0, name.length-2);
                val = vals.join(',')
            }
            else {
                val = $(this).val();
            }
            $(".filters input[name='" + name + "']").val(val).trigger('change');
        });

        $("[data-krajee-daterangepicker]").on("cancel.daterangepicker", function () {
            $(this).val("").trigger("change");
        });

    })(window.jQuery);
</script>