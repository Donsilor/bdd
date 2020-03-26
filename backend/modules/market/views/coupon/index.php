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
                    <?= Html::create([
                        'edit',
                        'specials_id' => $searchModel->specials_id,
                    ]) ?>
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
                                if($model->specials->product_range==1) {
                                    return '特定产品';
                                }

                                //产品线列表
                                $typeList = \services\goods\TypeService::getTypeList();

                                $html = [];
                                foreach ($model->goods_type_attach as $item) {
                                    $html[$item] = $typeList[$item];
                                }

                                return implode('/', $html);
                            }
                        ],
                        [
                            'label' => '活动地区',
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
                                }
                                else {
                                    $value = '基础价 * '.($model->discount/100);
                                }
                                return $value;
                            }
                        ],
                        [
                            'label' => '优惠券总数量',
                            'attribute' => 'count',
                            'filter' => false,
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
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model['id']]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

