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
                            'label' => '类型',
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

