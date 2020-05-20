<?php

use common\helpers\Url;
use common\helpers\Html;
use \yii\grid\GridView;

$this->title = '前台菜单';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <?php foreach (\common\enums\NotifyContactsEnum::Type() as $key => $value){ ?>
                    <li class="<?php if ($key == $type_id ){ echo 'active' ;}?>"><a href="<?= Url::to(['index', 'SearchModel[type_id]' => $key]) ?>"> <?= $value ?></a></li>
                <?php } ?>
                <li class="pull-right">
                    <?= Html::create(['ajax-edit', 'type_id' => $type_id], '创建', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ]); ?>
                </li>
            </ul>
            <div class="tab-content">
                <div class="active tab-pane">
                    <?php $widgetData = [
                        'id'=>'grid',
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        //重新定义分页样式
                        'tableOptions' => ['class' => 'table table-hover'],
                        'columns' => [
                            [
                                'attribute' => 'id',
                                'filter' => false
                            ],
                            [
                                'attribute' => 'realname',
                            ],
                            [
                                'attribute' => 'mobile',
                            ],
                            [
                                'attribute' => 'email',
                            ],
                            [
                                'attribute' => 'mobile_switch',
                                'label' => '启用短信通知',
                                'format' => 'raw',
                                'filter' => Html::activeDropDownList($searchModel, 'mobile_switch', \common\enums\StatusEnum::getMap(), [
                                        'prompt' => '全部',
                                        'class' => 'form-control'
                                    ]
                                ),
                                'value' => function($model) {
                                    return \common\enums\StatusEnum::getValue($model->mobile_switch);
                                },
                            ],
                            [
                                'attribute' => 'email_switch',
                                'label' => '启用邮箱通知',
                                'format' => 'raw',
                                'filter' => Html::activeDropDownList($searchModel, 'email_switch', \common\enums\StatusEnum::getMap(), [
                                        'prompt' => '全部',
                                        'class' => 'form-control'
                                    ]
                                ),
                                'value' => function($model) {
                                    return \common\enums\StatusEnum::getValue($model->email_switch);
                                },
                            ],
                            [
                                'label' => '操作人',
                                'attribute' => 'user.username',
                                'headerOptions' => ['class' => 'col-md-1'],
                                'filter' => Html::activeTextInput($searchModel, 'user.username', [
                                    'class' => 'form-control',
                                ]),
                            ],
                            [
                                'label' => '添加时间',
                                'attribute' => 'created_at',
                                'value' => function($model) {
                                    return Yii::$app->formatter->asDatetime($model->created_at);
                                }
                            ],
                            [
                                'header' => "操作",
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{edit} {delete}',
                                'buttons' => [
                                    'edit' => function ($url, $model, $key) {
                                        return Html::edit(['ajax-edit','id' => $model->id], '编辑', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModalLg',
                                        ]);
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        return Html::delete(['delete', 'id' => $model->id]);
                                    },
                                ],
                            ],
                        ],
                    ];

                    ?>
                    <?= GridView::widget($widgetData);?>
                </div>
            </div>
        </div>
    </div>
</div>