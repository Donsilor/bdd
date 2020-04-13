<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use common\helpers\ImageHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->title = Yii::t('card', '购物卡发放列表');
$card_title = Yii::t('card',  '购物卡使用列表');
$this->params['breadcrumbs'][] = $this->title;
$type_id = Yii::$app->request->get('type_id', 0);
?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="<?= Url::to(['card/index']) ?>"> <?= Html::encode($this->title) ?></a>
                </li>
                <li>
                    <a href="<?= Url::to(['card-details/index']) ?>"> <?= Html::encode($card_title) ?></a>
                </li>
                <li class="pull-right">
                    <div class="box-header box-tools">
                        <?= Html::create(['ajax-edit'], '生成购物卡', [
                            'data-toggle' => 'modal',
                            'data-target' => '#ajaxModalLg',
                        ])?>
                    </div>
                </li>
            </ul>
            <div class="box-body table-responsive">
                <?php echo Html::batchButtons(false) ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-hover'],
                    'showFooter' => true,//显示footer行
                    'id' => 'grid',
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],
//                        [
//                            'class' => 'yii\grid\CheckboxColumn',
//                            'name' => 'id',  //设置每行数据的复选框属性
//                            'headerOptions' => ['width' => '30'],
//                        ],
                        [
                            'label' => '序号',
                            'attribute' => 'id',
                            'filter' => true,
                            'format' => 'raw',
                            //'headerOptions' => ['width' => '80'],
                        ],
                        [
                            'label' => '发卡时间',
                            'value' => function($model) {
                                return Yii::$app->formatter->asDatetime($model->created_at);
                            }
                        ],
                        [
                            'label' => '卡号',
                            'filter' => false,
                            'attribute' => 'sn',
                            'format' => 'raw',
                            'value' => function($model) {
                                return Html::a($model->sn, ['view', 'id' => $model->id], ['style'=>"text-decoration:underline;color:#3c8dbc"]);
                            }
                        ],
                        [
                            'label' => '总金额',
                            'filter' => false,
                            'attribute' => 'amount',
                        ],
                        [
                            'label' => '余额',
                            'filter' => false,
                            'attribute' => 'balance',
                        ],
                        [
                            'label' => '有效时间',
                            'format' => 'raw',
                            'value' => function($model) {
                                return Yii::$app->formatter->asDatetime($model->start_time, 'Y-M-d')."<br />".Yii::$app->formatter->asDatetime($model->end_time-1, 'Y-M-d');
                            }
                        ],
                        [
                            'label' => '使用范围',
                            'filter' => Html::activeDropDownList($searchModel, 'goods_type_attach', \services\goods\TypeService::getTypeList(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]),
                            'value' => function($model) {
                                $typeList = \services\goods\TypeService::getTypeList();
                                $val = [];
                                foreach ($model->goods_type_attach as $goods_type) {
                                    $val[] = $typeList[$goods_type];
                                }
                                return implode('/', $val);
                            }
                        ],
                        [
                            'label' => '操作人',
                            'attribute' => 'user.username',
                        ],
                        [
                            'label' => '购物卡状态',
                            'attribute' => 'status',
                            'format' => 'raw',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'value' => function ($model) {
                                return \common\enums\StatusEnum::getValue($model->status);
                            },
                            'filter' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{edit} {status}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit-lang', 'id' => $model->id, 'type_id' => Yii::$app->request->get('type_id'), 'returnUrl' => Url::getReturnUrl()]);
                                },

                                'status' => function ($url, $model, $key) {
                                    return Html::status($model['status']);
                                }
                            ]
                        ]
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>
