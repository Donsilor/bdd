<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('advert', '广告位');
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
                        'data-target' => '#ajaxModal',
                    ])?>
                </div>
            </div>
            <div class="box-body table-responsive">

        <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false,
            ],

            'id',
            [
                'attribute'=>'lang.adv_name'
            ],
            [
                'attribute'=>'adv_type',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\SettingEnum::$advTypeAction[$model->adv_type];
                }
            ],
            [
                'attribute'=>'尺寸',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return $model->adv_width ."*".$model->adv_height;
                }
            ],
            //'adv_width',

            [
                'attribute'=>'show_type',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\SettingEnum::$showTypeActionSimple[$model->show_type];
                }
            ],
            //'open_type',
            //'remark',
            //'status',
            //'created_at',
            //'updated_at',
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {detail} {status} {delete}',
                'buttons' => [
                    'edit' => function ($url, $model, $key) {
                        return Html::edit(['ajax-edit-lang','id' => $model->id], '编辑', [
                            'data-toggle' => 'modal',
                            'data-target' => '#ajaxModalLg',
                        ]);
                    },
                    'detail'=>function($url, $model, $key){
                        return Html::linkButton(['advert-images/index', 'adv_id' => $model->id], '图片');

                    },

                   'status' => function($url, $model, $key){
                            return Html::status($model['status']);
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
