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
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::create(['import'], '导入评价', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ])?>
                </div>
            </div>
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
                                'label' => '评价人',
                                'filter' => Html::activeTextInput($searchModel, 'member.username', [
                                    'class' => 'form-control',
                                ]),
                                'value' => function($model) {
                                    $username = $model->username;
                                    if(empty($username) && $model->member_id) {
                                        $userInfo = \common\models\member\Member::findOne($model->member_id);
                                        $username = $userInfo->username;
                                    }
                                    return $username;
                                }
                            ],
                            [
                                'label' => '产品名称',
                                'value' => function($row) {
                                    return $row->style->lang->style_name??'';
                                }
                            ],
                            [
                                'label' => '款号',
                                'filter' => Html::activeTextInput($searchModel, 'style.style_sn', [
                                    'class' => 'form-control',
                                ]),
                                'value' => function($row) {
                                    return $row->style->style_sn??'';
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
                                'attribute' => 'platform',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'platform', \common\enums\OrderFromEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return \common\enums\OrderFromEnum::getValue($model->platform);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'label' => '评价星级',
                                'attribute' => 'grade',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeDropDownList($searchModel, 'grade', [0,1,2,3,4,5], [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function ($model) {
                                    return $model->grade;
                                },
                            ],
                            [
                                'label' => '评价内容',
                                'attribute' => 'content',
                                'value' => function ($model) {
                                    return $model->content;
                                },
                            ],
                            [
                                'attribute' => 'images',
                                'value' => function ($model) {
                                    $images = explode(',', $model->images);
                                    $value = '';
                                    foreach ($images as $image) {
                                        $value .= common\helpers\ImageHelper::fancyBox($image);
                                    }
                                    return $value;
                                },
                                'filter' => false,
                                'format' => 'raw',
                                'headerOptions' => ['width'=>'80'],
                            ],
                            [
                                'label' => '评价时间',
                                'attribute' => 'created_at',
                                'headerOptions' => ['class' => 'col-md-1'],
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
                                    return Yii::$app->formatter->asDatetime($model->created_at, 'Y-MM-d');
                                },
                                'format' => 'raw',
                            ],
                            [
                                'filter' => false,
                                'label' => '审核人',
                                'attribute' => 'admin_id',
                                'value' => function ($model) {
                                    $row = \common\models\backend\Member::find()->where(['id'=>$model->admin_id])->one();
                                    if($row){
                                        return $row->username;
                                    }
                                    return '';
                                },
                            ],
                            [
                                'label' => '创建人',
                                'attribute' => 'username',
                                'value' => function($model) {
                                    if($model->is_import) {
                                        if($row = \common\models\backend\Member::find()->where(['id'=>$model->admin_id])->one()){
                                            return $row->username;
                                        }
                                    }
                                    return '';
                                }
                            ],
                            [
                                'label' => '评价类型',
                                'attribute' => 'is_import',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'value' => function($model) {
                                    return \common\enums\OrderCommentStatusEnum::getValue($model->is_import, 'virtualStatus');
                                },
                                'filter' => Html::activeDropDownList($searchModel, 'is_import', \common\enums\OrderCommentStatusEnum::virtualStatus(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
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
                                    'prompt' => '所有',
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
                                'template' => '{audit} {destroy}',
                                'buttons' => [
                                    'audit' => function ($url, $model, $key) {
                                        if($model->status == \common\enums\OrderCommentStatusEnum::PENDING) {
                                            return Html::edit(['edit-audit', 'id' => $model->id], '审核', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'class'=>'btn bg-green btn-sm'
                                            ]);
                                        }
                                        return null;
                                    },
                                    'destroy' => function ($url, $model, $key) {
                                        if($model->status == \common\enums\OrderCommentStatusEnum::PASS) {
                                            return Html::delete(['destroy', 'id' => $model->id]);
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
    //     $("[data-krajee-daterangepicker]").on("cancel.daterangepicker", function () {
    //         $(this).val("").trigger("change");
    //     });
    //
    //
    // })(window.jQuery);
</script>