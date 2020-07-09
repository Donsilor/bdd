<?php

use yii\grid\GridView;
use common\helpers\Html;
use common\helpers\ImageHelper;

$this->title = '会员信息';
$this->params['breadcrumbs'][] = ['label' => $this->title];

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
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false, // 不显示#
                        ],
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'col-md-1'],
                        ],

                        [
                            'attribute' => 'username',
                            'filter' =>  Html::activeTextInput($searchModel, 'username', [
                                'class' => 'form-control',
                                'style' =>'width:100px'
                            ]),
                        ],
//                        'realname',
//                        'mobile',
//                        [
//                            'label' => '账户金额',
//                            'filter' => false, //不显示搜索框
//                            'value' => function ($model) {
//                                return "余额：" . $model->account->user_money . '<br>' .
//                                    "累积金额：" . $model->account->accumulate_money . '<br>' .
//                                    "积分：" . $model->account->user_integral . '<br>' .
//                                    "累计积分：" . $model->account->accumulate_integral;
//                            },
//                            'format' => 'raw',
//                        ],

                        [
                            'label' => '姓/名',
                            'filter' =>  Html::activeTextInput($searchModel, 'lastname', [
                                'class' => 'form-control',
                                'style' =>'width:100px'
                            ]),
                            'value' => function($model){
                                return $model->lastname .'/'. $model->firstname;
                            }
                        ],
                        [
                            'attribute' => 'first_ip',
                            'filter' => false,

                        ],

                        [
                            'label' => '注册地址',
                            'filter' =>  Html::activeTextInput($searchModel, 'first_ip_location', [
                                'class' => 'form-control',
                                'style' =>'width:100px'
                            ]),
                            'value' => 'first_ip_location'
                        ],
//                        [
//                            'label' => '注册时间',
//                            'attribute' => 'created_at',
//                            'filter' => false,
//                            'value' => function($model) {
//                                return Yii::$app->getFormatter()->asDatetime($model->created_at);
//                            }
//                        ],
                        [
                            'label' => '注册时间',
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
                            'label' => '用户信息',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                if($model->marriage === 1){
                                    $marriage = '已婚';
                                }elseif($model->marriage === 2 ){
                                    $marriage = '未婚';
                                }else{
                                    $marriage = '保密';
                                }
                                return
                                    "电话：" . $model->mobile . '<br>' .
                                    "邮箱：" . $model->email . '<br>'.
                                    "性别：" .\common\enums\GenderEnum::getValue($model->gender) . '<br>' .
                                    "出生日期：" . $model->birthday . '<br>'.
                                    "婚姻狀況：" . $marriage . '<br>';

                            },
                            'format' => 'raw',
                        ],

                        [
                            'label'=>'是否购买',
                            'value'=>function($model){
                                $count = \common\models\order\Order::find()->where(['and',['member_id'=>$model->id],['>=','order_status',\common\enums\OrderStatusEnum::ORDER_PAID]])->count();
                                return $count > 0 ? "是":"否";
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'is_buy',['1'=>'是','2'=>'否'], [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]),
                        ],
                        [
                            'label'=>'游客',
                            'value'=>function($model) {
                                return \common\enums\MemberEnum::getValue($model->is_tourist, 'isTourist');
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'is_tourist',\common\enums\MemberEnum::isTourist(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]),
                        ],
                        /*
                        [
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '  {status} ',
                            'buttons' => [
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::linkButton(['ajax-edit', 'id' => $model->id], '账号密码', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                                },
                                'address' => function ($url, $model, $key) {
                                    return Html::linkButton(['address/index', 'member_id' => $model->id], '收货地址');
                                },
                                'recharge' => function ($url, $model, $key) {
                                    return Html::linkButton(['recharge', 'id' => $model->id], '充值', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                                },
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id]);
                                },
                                'status' => function ($url, $model, $key) {
                                    return Html::status($model->status);
                                },
                                'destroy' => function ($url, $model, $key) {
                                    return Html::delete(['destroy', 'id' => $model->id]);
                                },
                            ],
                        ],*/
                    ],
                ]); ?>
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