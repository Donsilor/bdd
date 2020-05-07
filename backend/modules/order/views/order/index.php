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
$params = $params ? "&".http_build_query($params) : '';
$export_param = http_build_query($searchModel)."&order_status={$order_status}";



?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li<?php if (Yii::$app->request->get('order_status', -1) == -1) echo ' class="active"' ?>><a href="<?= Url::to(['order/index']) ?>"> 全部（<?= \common\models\order\Order::getCountByOrderStatus() ?>）</a></li>
                <?php foreach (common\enums\OrderStatusEnum::getMap() as $statusValue => $statusName) { ?>
                    <li<?php if (Yii::$app->request->get('order_status', -1) == $statusValue) echo ' class="active"' ?>>
                        <a href="<?= Url::to(['order/index', 'order_status' => $statusValue]) ?>"><?= $statusName ?>（<?= \common\models\order\Order::getCountByOrderStatus($statusValue) ?>）</a>
                    </li>
                <?php } ?>
                <li class="pull-right">
                    <div class="box-header box-tools">
                        <?= Html::a('导出Excel','index?action=export'.$params) ?>
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
                            <?= $searchModel->model->getAttributeLabel('order_from') ?>：<br/>
                            <?= Html::activeDropDownList($searchModel, 'order_from', \common\enums\OrderFromEnum::getMap(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                    </div>
                </div>
                <div class="active tab-pane">
                    <?= GridView::widget([
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
                                    Html::activeTextInput($searchModel, 'order_from', [
                                        'class' => 'hidden',
                                    ])
                            ],
                            [
                                'attribute' => 'created_at',
                                'filter' => DateRangePicker::widget([    // 日期组件
                                    'model' => $searchModel,
                                    'attribute' => 'created_at',
                                    'value' => '',
                                    'options' => ['readonly' => false, 'class' => 'form-control',],
                                    'pluginOptions' => [
                                        'format' => 'yyyy-mm-dd',
                                        'locale' => [
                                            'separator' => '/',
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
                                    return sprintf('(%s)%s', $model->account->currency, $model->account->order_amount);
                                }
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
                                'attribute' => 'payment_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'payment_status', \common\enums\PayStatusEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    $str = \common\enums\PayStatusEnum::getValue($model->payment_status);
                                    if($model->payment_type) {                                        
                                        $str   .= '<br/>'.(\common\enums\PayEnum::getValue($model->payment_type));
                                    }
                                    return $str;                                   
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'order_status',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'order_status', common\enums\OrderStatusEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return common\enums\OrderStatusEnum::getValue($model->order_status);
                                },
                                'format' => 'raw',
                            ],
//                            [
//                                'attribute' => 'status',
//                                'headerOptions' => ['class' => 'col-md-1'],
//                                'filter' => Html::activeDropDownList($searchModel, 'status', common\enums\AuditStatusEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
//                                'value' => function ($model) {
//                                    return common\enums\AuditStatusEnum::getValue($model->status);
//                                },
//                                'format' => 'raw',
//                            ],
                            
                            [
                                'label' => '跟进人',
                                'filter' => Html::activeTextInput($searchModel, 'follower.username', [
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return $model->follower ? $model->follower->username : null;
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
                                     return common\enums\FollowStatusEnum::getValue($model->followed_status);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'header' => "操作",
                                //'headerOptions' => ['class' => 'col-md-1'],
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{audit} {delivery} {follower}',
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
                                            return Html::batchAudit(['ajax-batch-audit'], '审核', [
                                                //'class'=>'label bg-green'
                                            ]);
                                        }                                        
                                    },
                                    'delivery' => function ($url, $model, $key) {                     
                                        if($model->order_status == \common\enums\OrderStatusEnum::ORDER_CONFIRM) {
                                            return  Html::edit(['edit-delivery', 'id' => $model->id], '发货', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'class'=>'btn btn-success btn-sm'
                                            ]);
                                        }
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


    })(window.jQuery);
</script>