<?php

use yii\grid\GridView;
use common\helpers\Html;
use common\enums\WhetherEnum;
use common\helpers\DebrisHelper;
use common\helpers\Url;

$this->title = '短信日志';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="<?= Url::to(['sms-log/index']) ?>"> 短信日志</a>
                </li>
                <li>
                    <a href="<?= Url::to(['email-log/index']) ?>"> 邮件日志</a>
                </li>
            </ul>


<!--            <div class="box-header">-->
<!--                <h3 class="box-title">--><?//= $this->title; ?><!--</h3>-->
<!--                <div class="box-tools">-->
<!--                    --><?//= Html::linkButton(['stat'], '<i class="fa fa-area-chart"></i> 异常发送报表统计', [
//                        'data-toggle' => 'modal',
//                        'data-target' => '#ajaxModalMax',
//                    ]) ?>
<!--                </div>-->
<!--            </div>-->
            <!-- /.box-header -->
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    //重新定义分页样式
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        'id',
                        'mobile',
                        [
                            'attribute' => 'code',
                            'headerOptions' => ['class' => 'col-md-1'],
                        ],
                        // 'content',
//                        [
//                            'attribute' => 'usage',
//                            'headerOptions' => ['class' => 'col-md-1'],
//                        ],
                        [
                            'attribute' => 'usage',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'value' => function($model) {
                                return \common\models\common\SmsLog::$usageExplain[$model->usage]??$model->usage;
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'usage', \common\models\common\SmsLog::$usageExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            )
                        ],
                        [
                            'attribute' => 'used',
                            'value' => function ($model, $key, $index, $column) {
                                return Html::whether($model->used);
                            },
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'used', WhetherEnum::getMap(), [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            )
                        ],
                        [
                            'attribute' => 'use_time',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return empty($model->use_time) ? '---' : date('Y-m-d H:i:s', $model->use_time);
                            },
                        ],
                        [
                            'attribute' => 'error_code',
                            'filter' => false, //不显示搜索框
                        ],
                        [
                            'attribute' => 'error_msg',
                            'filter' => false, //不显示搜索框
                        ],
                        [
                            'label' => '位置信息',
                            'value' => function ($model) {
                                $str = [];
                                $str[] = DebrisHelper::analysisIp($model->ip);
                                $str[] = DebrisHelper::long2ip($model->ip);
                                return implode($str, '</br>');
                            },
                            'format' => 'raw',
                        ],
                        [
                            'label' => '创建时间',
                            'attribute' => 'created_at',
                            'filter' => false, //不显示搜索框
                            'format' => ['date', 'php:Y-m-d H:i:s'],
                        ],
                        [
                            'header' => "操作",
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::linkButton(['view', 'id' => $model->id], '查看详情', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div>
    </div>
</div>