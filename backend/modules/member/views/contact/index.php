<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use kartik\daterange\DateRangePicker;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('web_seo', '客户留言');
$this->params['breadcrumbs'][] = $this->title;


$telphone = $searchModel->telphone;
$status = $searchModel->status;
$created_at = $searchModel->created_at;
$book_time = $searchModel->book_time;


$addon = <<< HTML
<span class="input-group-addon">
    <i class="glyphicon glyphicon-calendar"></i>
</span>
HTML;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <a href="<?= Url::to(['export?telphone='.$telphone.'&status='.$status.'&created_at='.$created_at.'&book_time='.$book_time])?>" class="blue">导出Excel</a>
                </div>
            </div>
            <div class="box-body table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-hover'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false,
            ],

            [
                'attribute' => 'id',
                'value' => 'id',
                'filter' =>false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'50'],
            ],


//            [
//                'attribute'=>'language',
//                'format' => 'raw',
//                'headerOptions' => ['class' => 'col-md-1'],
//                'value' => function ($model){
//                    return \Yii::$app->params['languages'][$model->language];
//                },
//                'filter' => Html::activeDropDownList($searchModel, 'language',\Yii::$app->params['languages'], [
//                    'prompt' => '全部',
//                    'class' => 'form-control'
//                ]),
//            ],
            //'member_id',
            [
                'attribute'=>'姓名',
                'filter' => false,
                'value'=>function($model){
                    return $model->first_name . $model->last_name;
                },
                'headerOptions' => ['width'=>'100'],
            ],

            [
                'attribute' => 'telphone',
                'value' => 'telphone',
                'filter' => Html::activeTextInput($searchModel, 'telphone', [
                    'class' => 'form-control',
                ]),
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
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
                'attribute' => 'ip',
                'value' => 'ip',
                'filter' => Html::activeTextInput($searchModel, 'ip', [
                    'class' => 'form-control',
                ]),
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
            [
                'attribute' => 'ip_location',
                'value' => 'ip_location',
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
            [
                'attribute'=>'book_time',
                'filter' => DateRangePicker::widget([    // 日期组件
                    'model' => $searchModel,
                    'attribute' => 'book_time',
                    'value' => $searchModel->created_at,
                    'options' => ['readonly' => true,'class'=>'form-control','style'=>'background-color:#fff;width:100px;'],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'locale' => [
                            'separator' => '/',
                        ],
                        'endDate' => date('Y-m-d',time()),
                        'todayHighlight' => true,
                        'autoclose' => true,
                        'todayBtn' => 'linked',
                        'clearBtn' => true,


                    ],

                ]),
                'value'=>'book_time',

            ],
            [
                'attribute' => 'created_at',
                'filter' => DateRangePicker::widget([    // 日期组件
                    'model' => $searchModel,
                    'attribute' => 'created_at',
                    'value' => $searchModel->created_at,
                    'options' => ['readonly' => true,'class'=>'form-control','style'=>'background-color:#fff;width:100px;'],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'locale' => [
                            'separator' => '/',
                        ],
                        'endDate' => date('Y-m-d',time()),
                        'todayHighlight' => true,
                        'autoclose' => true,
                        'todayBtn' => 'linked',
                        'clearBtn' => true,


                    ],

                ]),
                'value' => function ($model) {
                    return Yii::$app->formatter->asDate($model->created_at, 'Y-m-d H:i:s');
                },
                'format' => 'raw',

            ],
            [
                'attribute' => 'content',
                'value' => 'content',
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'200'],
            ],

            [
                'attribute' => 'followed_status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model) {
                    return \common\enums\FollowStatusEnum::getValue($model->followed_status??0);
                },
                'filter' => Html::activeDropDownList($searchModel, 'followed_status',\common\enums\FollowStatusEnum::getMap(), [
                    'prompt' => '全部',
                    'class' => 'form-control',
                ]),
            ],
            [
                'attribute' => 'follower_id',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model) {
                    $user = \common\models\backend\Member::findOne($model->follower_id);
                    return $user?$user->username:'';
                },
            ],
            [
                'attribute'=>'type_id',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\ContactEnum::getMap()[$model->type_id];
                },
                'filter' => false,
//                'filter' => Html::activeDropDownList($searchModel, 'type_id',\common\enums\ContactEnum::getMap(), [
//                    'prompt' => '全部',
//                    'class' => 'form-control'
//                ]),
            ],
            //'content:ntext',
            //'status',
            //'created_at',
            //'updated_at',
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit}{info}',
                'buttons' => [
                    'edit' => function ($url, $model, $key) {
                        return Html::edit(['ajax-edit','id' => $model->id], '跟进', [
                            'data-toggle' => 'modal',
                            'data-target' => '#ajaxModalLg',
                        ]);
                    },
                    'info' => function($url, $model, $key){
                        return Html::a('查看',['info', 'id' => $model->id],['class'=>'btn btn-info btn-sm']);
                    },
                   'status' => function($url, $model, $key){
                            return Html::status($model['status'],[],\common\enums\FollowStatusEnum::getMap());
                      },
                    'delete' => function($url, $model, $key){
                            return Html::delete(['delete', 'id' => $model->id]);
                    },
                ]
            ]
    ]
    ]); ?>
            </div>
        </div>
    </div>
</div>
