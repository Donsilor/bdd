<?php

use common\helpers\Html;
use yii\grid\GridView;
use common\enums\PreferentialTypeEnum;

$this->title = '优惠管理';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
                <div class="box-tools">
                    <?= Html::create(['ajax-edit-lang', 'specials_id' => $searchModel->specials_id], '创建', [
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
                            'label' => '活动名称',
                            'attribute' => 'specials.lang.title',
                        ],
                        [
                            'label' => '活动类型',
                            'attribute' => 'type',
                            'format' => 'raw',
                            'filter' => false,
//                            'filter' => Html::activeDropDownList($searchModel, 'type', PreferentialTypeEnum::getMap(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
                            'value' => function ($model) {
                                return "<span class='label label-primary'>" . PreferentialTypeEnum::getValue($model->type) . "</span>";
                            },
                        ],
                        [
                            'label' => '活动产品',
                            'value' => function($model) {
                                $value = '';

                                if($model->specials->product_range==1) {
                                    $value .= '特定产品';
                                }

                                if(is_array($model->goods_type_attach)) {
                                    //产品线列表
                                    $typeList = \services\goods\TypeService::getTypeList();

                                    $html = [];
                                    foreach ($model->goods_type_attach as $item) {
                                        $html[$item] = $typeList[$item];
                                    }
                                    $value .= ($value?'/':'') . implode('/', $html);
                                }

                                return $value;
                            }
                        ],
                        [
                            'label' => '活动站点地区',
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
                            'label' => '优惠券金额/折扣设置',
                            'value' => function($model) {
                                if($model->type==1) {
                                    $value = '-'.$model->money.'元';
                                    $value .= ' (满'.$model->at_least.'元使用）';
                                }
                                else {
                                    $value = '基础价 * '.($model->discount/100);
                                }
                                return $value;
                            }
                        ],
                        [
                            'label' => '优惠券数量',
                            'attribute' => 'count',
                            'filter' => false,
                        ],
                        [
                            'label' => '已领取数量',
//                            'attribute' => 'count',
//                            'filter' => false,
                            'value' => function($model) {
                                if($model->type==1) {
                                    return $model->get_count;
                                }
                                else {
                                    return '';
                                }
//                                return $model->getReceiveCount();
                            }
                        ],
                        [
                            'label' => '已使用数量',
//                            'attribute' => 'count',
//                            'filter' => false,
                            'value' => function($model) {
                                if($model->type==PreferentialTypeEnum::MONEY) {
                                    return $model->get_count;
                                }
                                if($model->type==PreferentialTypeEnum::DISCOUNT) {
                                    return $model->getUseCount();
                                }
                            }
                        ],
                        [
                            'label' => '添加人',
                            'attribute' => 'user.username',
                        ],
                        [
                            'label' => '更新时间',
                            'attribute' => 'updated_at',
                            'value' => function ($model) {
                                return Yii::$app->formatter->asDatetime($model->updated_at);
                            },
                            'filter' => false,
                        ],
                        [
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{goods} {edit} {status}',
                            'buttons' => [
                                'goods' => function ($url, $model, $key) {
                                    return Html::linkButton([
                                        !empty($model->goods_attach)?'goods/index':'goods-type/index',
                                        'SearchModel[specials_id]' => $model['specials_id'],
                                        'SearchModel[coupon_id]' => $model['id'],
                                    ], '活动产品');
                                },
                                'edit' => function($url, $model, $key) {
                                    return Html::edit(['ajax-edit-lang','id' => $model->id], '编辑', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

