<?php

use common\helpers\Html;
use yii\grid\GridView;
use common\enums\PreferentialTypeEnum;

$this->title = '活动专题管理';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
                <div class="box-tools">
                    <?= Html::create(['edit']) ?>
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
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', PreferentialTypeEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return "<span class='label label-primary'>" . PreferentialTypeEnum::getValue($model->type) . "</span>";
//                            },
                        ],
                        [
                            'label' => '时间',
                            'format' => 'raw',
                            'value' => function ($model) {
                                $html = '';
                                $html .= '开始时间：' . Yii::$app->formatter->asDatetime($model->start_time) . "<br>";
                                $html .= '结束时间：' . Yii::$app->formatter->asDatetime($model->end_time) . "<br>";
                                $html .= '有效状态：' . Html::timeStatus($model->start_time, $model->end_time);

                                return $html;
                            },
                        ],
                        [
                            'attribute' => 'area_attach',
                            'value' => function($model) {
                                if(empty($model->area_attach)) {
                                    return '';
                                }

                                $value = [];
                                foreach ($model->area_attach as $areaId) {
                                    $value[] = \common\enums\AreaEnum::getValue($areaId);
                                }
                                return implode('/', $value);
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
                                if($model->product_range==1) {
                                    return '特定产品';
                                }

                                $value = [];
                                foreach ($model->coupons as $conpon) {
                                    $value = array_merge($value, $conpon->goods_type_attach);
                                }

                                //产品线列表
                                $typeList = \services\goods\TypeService::getTypeList();

                                $html = [];
                                foreach ($value as $item) {
                                    $html[$item] = $typeList[$item];
                                }

                                return implode('/', $html);
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
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{goods} {coupon} {edit} {status}',
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
                                'status' => function ($url, $model, $key) {
                                    return Html::status($model->status);
                                },
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model['id']]);
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

