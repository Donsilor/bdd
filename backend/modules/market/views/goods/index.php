<?php

use common\helpers\Html;
use yii\grid\GridView;
use common\enums\PreferentialTypeEnum;

$this->title = '活动产品';
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
                            'attribute' => 'specials.lang.title',
                        ],
                        [
                            'label' => '商品图',
                            'attribute' => 'goods.goods_image',
                            'value' => function ($model) {
                                return \common\helpers\ImageHelper::fancyBox($model->goods->goods_image);
                            },
                            'filter' => false,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'80'],
                        ],
                        [
                            'attribute' => 'coupon.area_attach',
                            'value' => function($model) {
                                if(empty($model->coupon->area_attach)) {
                                    return '';
                                }

                                $value = [];
                                foreach ($model->coupon->area_attach as $areaId) {
                                    $value[] = \common\enums\AreaEnum::getValue($areaId);
                                }
                                return implode('/', $value);
                            }
                        ],
                        [
                            'label' => '活动类型',
                            'attribute' => 'coupon.type',
                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', PreferentialTypeEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
                            'value' => function ($model) {
                                return "<span class='label label-primary'>" . PreferentialTypeEnum::getValue($model->coupon->type) . "</span>";
                            },
                        ],
                        [
                            'label' => '优惠券金额/折扣设置',
                            'value' => function($model) {
                                if($model->coupon->type==1) {
                                    $value = '-'.$model->coupon->money.'元';
                                }
                                else {
                                    $value = '基础价 * '.($model->coupon->discount/100);
                                }
                                return $value;
                            }
                        ],
                        [
                            'label' => '优惠券总数量',
                            'attribute' => 'coupon.count',
                        ],
                        [
                            'label' => '添加时间',
                            'attribute' => 'created_at',
                            'value' => function ($model) {
                                return Yii::$app->formatter->asDatetime($model->coupon->created_at);
                            },
                            'filter' => false,
                        ],
                        [
                            'label' => '添加人',
                            'attribute' => 'coupon.user.username',
                        ],
//                        [
//                            'label' => '类型',
//                            'attribute' => 'type',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', PreferentialTypeEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return "<span class='label label-primary'>" . PreferentialTypeEnum::getValue($model->type) . "</span>";
//                            },
//                        ],
//                        [
//                            'label' => '时间',
//                            'format' => 'raw',
//                            'value' => function ($model) {
//                                $html = '';
//                                $html .= '开始时间：' . Yii::$app->formatter->asDatetime($model->start_time) . "<br>";
//                                $html .= '结束时间：' . Yii::$app->formatter->asDatetime($model->end_time) . "<br>";
//                                $html .= '有效状态：' . Html::timeStatus($model->start_time, $model->end_time);
//
//                                return $html;
//                            },
//                        ],
//                        [
//                            'header' => "操作",
//                            'class' => 'yii\grid\ActionColumn',
//                            'template' => '{goods} {coupon} {edit}',
//                            'buttons' => [
//                                'goods' => function ($url, $model, $key) {
//                                    return Html::linkButton([
//                                        'goods/index',
//                                        'specials_id' => $model['id'],
//                                    ], '活动产品');
//                                },
//                                'coupon' => function ($url, $model, $key) {
//                                    return Html::linkButton([
//                                        'coupon/index',
//                                        'specials_id' => $model['id'],
//                                    ], '优惠管理');
//                                },
////                                'status' => function ($url, $model, $key) {
////                                    return Html::status($model->status);
////                                },
//                                'edit' => function ($url, $model, $key) {
//                                    return Html::edit(['edit', 'id' => $model['id']]);
//                                },
//                                'delete' => function ($url, $model, $key) {
//                                    return Html::delete(['delete', 'id' => $model->id]);
//                                },
//                            ],
//                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

