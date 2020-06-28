<?php

use common\enums\StatusEnum;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\PreferentialTypeEnum;
use common\enums\AreaEnum;
use services\goods\TypeService;

$this->title = '活动专题管理';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
                <div class="box-tools">
                    <?= Html::create(['ajax-edit-lang'], '创建', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ])?>
                </div>
            </div>
            <div class="box-body table-responsive">

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    //重新定义分页样式
                    'tableOptions' => ['class' => 'table table-hover rf-table'],
                    'options' => [
                        'id' => 'grid',
                    ],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false, // 不显示#
                        ],
                        'id',
                        [
                            'attribute' => 'lang.title',
                            'filter' => Html::activeTextInput($searchModel, 'lang.title', [
                                'class' => 'form-control',
                            ]),
                            'format' => 'raw',
                            'value' => function ($model) {
                                return Html::edit(['ajax-edit-lang','id' => $model->id], $model->lang->title, [
                                    'data-toggle' => 'modal',
                                    'data-target' => '#ajaxModalLg',
                                    'style'=>"text-decoration:underline;color:#3c8dbc",
                                    'class'=>''
                                ]);
                            },
                        ],
                        [
                            'label' => '时间',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'created_at', [
                                '1'=>'未开始',
                                '2'=>'进行中',
                                '3'=>'已结束',
                            ], [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                $html = '';
                                $html .= '开始时间：' . Yii::$app->formatter->asDatetime($model->start_time) . "<br>";
                                $html .= '结束时间：' . Yii::$app->formatter->asDatetime($model->end_time) . "<br>";
                                $html .= '有效状态：' . Html::timeStatus($model->start_time, $model->end_time);

                                return $html;
                            },
                        ],
                        [
                            'label' => '活动站点地区',
                            'value' => function($model) {
                                $value = [];
                                foreach ($model->coupons as $conpon) {
                                    $value = array_merge($value, $conpon->area_attach);
                                }

                                $html = [];
                                foreach (AreaEnum::getMap() as $key => $item) {
                                    if(in_array($key, $value))
                                        $html[] = $item;
                                }

                                return implode('/', $html);
                            },
                            'filter' => false,
                        ],
                        [
                            'label' => '活动类型',
                            'attribute' => 'type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'type', PreferentialTypeEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return "<span class='label label-primary'>" . PreferentialTypeEnum::getValue($model->type) . "</span>";
                            },
                        ],
                        [
                            'label' => '活动产品',
                            'value' => function($model) {
                                $_value = '';

                                if($model->product_range==1) {
                                    $_value .= '特定产品';
                                }

                                $value = [];
                                foreach ($model->coupons as $conpon) {
                                    $value = array_merge($value, $conpon->goods_type_attach);
                                }

                                //产品线列表
                                $typeList = TypeService::getTypeList();

                                $html = [];
                                foreach ($typeList as $key => $item) {
                                    if(in_array($key, $value))
                                        $html[] = $item;
                                }

                                return $_value . ($_value&&$html?'/':'') . implode('/', $html);
                            }
                        ],
                        [
                            'label' => '折扣率',
//                            'attribute' => 'specials.lang.title',
                            'value' => function($model) {
                                $value = [];
                                foreach ($model->coupons as $conpon) {
                                    if($conpon->type!=2) {
                                        continue;
                                    }
                                    $value[] = $conpon->discount;
                                }
                                return implode('/', $value);
                            }
                        ],
                        [
                            'label' => '优惠券',
                            'value' => function($model) {
                                $value = [];
                                foreach ($model->coupons as $conpon) {
                                    if($conpon->type!=1) {
                                        continue;
                                    }
                                    $value[] = $conpon->money."(满{$conpon->at_least})";
                                }
                                return implode('/', $value);
                            }
                        ],
                        [
                            'label' => '优惠券数量',
                            'value' => function($model) {
                                $value = 0;
                                foreach ($model->coupons as $conpon) {
                                    $value += $conpon->count;
                                }
                                return $value;
                            }
                        ],
                        [
                            'label' => '活动产品数量',
                        ],
                        [
                            'label' => '添加时间',
                            'attribute' => 'created_at',
                            'value' => function ($model) {
                                return Yii::$app->formatter->asDatetime($model->created_at);
                            },
                            'filter' => false,
                        ],
                        [
                            'label' => '添加人',
                            'attribute' => 'user.username',
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status', StatusEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function($model) {
                                return Html::status($model->status);
                            },
                        ],
                        [
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{goods} {coupon}',
                            'buttons' => [
                                'goods' => function ($url, $model, $key) {
                                    return Html::linkButton([
                                        $model->product_range==1?'goods/index':'goods-type/index',
                                        'SearchModel[specials_id]' => $model['id'],
                                    ], '活动商品');
                                },
                                'coupon' => function ($url, $model, $key) {
                                    return Html::linkButton([
                                        'coupon/index',
                                        'SearchModel[specials_id]' => $model['id'],
                                    ], '折扣设置');
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::delete(['delete', 'id' => $model->id]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

