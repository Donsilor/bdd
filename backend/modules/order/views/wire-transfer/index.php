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
                                'attribute' => 'orderTourist.order_sn'
                            ],
                            [
                                'label' => '订单总金额',
                                'attribute' => 'orderTourist.order_amount',
                                'value' => function($model)
                                {
                                    return \common\helpers\AmountHelper::outputAmount($model->orderTourist->order_amount, 2, $model->orderTourist->currency);
                                }
                            ],
                            [
                                'label' => '收货人',
                                'attribute' => 'orderTourist.address.realname',
                            ],
                            [
                                'label' => '联系方式',
                                'attribute' => 'orderTourist.address.mobile',
                                'format' => 'raw',
                                'value' => function($model) {
                                    return $model->orderTourist->address->mobile . '<br />' . $model->orderTourist->address->email;
                                }
                            ],
                            [
                                'attribute' => 'orderTourist.order_from',
                                'headerOptions' => ['class' => 'col-md-1'],
//                                'filter' => Html::activeDropDownList($searchModel, 'orderTourist.order_from', \common\enums\OrderFromEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
                                'value' => function ($model) {
                                    return \common\enums\OrderFromEnum::getValue($model->orderTourist->order_from);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '客户备注',
                                'attribute' => 'orderTourist.buyer_remark',
                            ],
//                            [
//                                'attribute' => 'orderTourist.order_status',
//                                'headerOptions' => ['class' => 'col-md-1'],
//                                'filter' => false,
////                                'filter' => Html::activeDropDownList($searchModel, 'order_status', common\enums\OrderStatusEnum::getMap(), [
////                                    'prompt' => '全部',
////                                    'class' => 'form-control',
////                                ]),
//                                'value' => function ($model) {
//                                    return common\enums\OrderStatusEnum::getValue($model->orderTourist->order_status);
//                                },
//                                'format' => 'raw',
//                            ],
//
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
                            [
                                'header' => "操作",
                                //'headerOptions' => ['class' => 'col-md-1'],
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{audit}',
                                'buttons' => [
                                    'audit' => function ($url, $model, $key) {
                                        if($model->collection_status != \common\enums\WireTransferEnum::CONFIRM) {
                                            return Html::edit(['ajax-edit2', 'id'=>$model->id], '审核', [
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