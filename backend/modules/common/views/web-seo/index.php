<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('web_seo', 'SEO');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::create(['ajax-edit-lang'], '创建', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ])?>
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
            'id',
            [
                'label' => '站点',
                'value' => function($model) {
                    if(empty($model->platforms)) {
                        return '';
                    }

                    $value = [];
                    foreach ($model->platforms as $platform) {
                        $groupName = \common\enums\OrderFromEnum::platformToGroupName($platform);
                        $value[$groupName] = $groupName;
                    }
                    return implode(',', $value);
                },
                'filter' => Html::activeDropDownList($searchModel, 'platform_10', \common\enums\OrderFromEnum::groups(), [
                    'prompt' => '全部',
                    'class' => 'form-control',
                    'style' => 'width:78px;'
                ]),
            ],
            [
                'label' => '客户端',
                'value' => function($model) {
                    if(empty($model->platforms)) {
                        return '';
                    }

                    $value = [];
                    foreach ($model->platforms as $platform) {
                        $value[] = \common\enums\OrderFromEnum::getValue($platform);
                    }
                    return implode(',', $value);
                },
                'filter' => Html::activeDropDownList($searchModel, 'platforms', \common\enums\OrderFromEnum::getMap(), [
                    'prompt' => '全部',
                    'class' => 'form-control',
                    'style' => 'width:78px;'
                ]),
            ],
            [
                'attribute' => 'page_name',
            ],
            [
                'attribute' => 'lang.meta_title',
            ],
            [
                'label' => '路由',
                'attribute' => 'route',
            ],
            [
                'label' => '修改时间',
                'value' => function($model) {
                    return Yii::$app->formatter->asDatetime($model->updated_at);
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {status} {delete}',
                'buttons' => [
                'edit' => function($url, $model, $key){
                    return Html::edit(['ajax-edit-lang','id' => $model->id], '编辑', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ]);
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
