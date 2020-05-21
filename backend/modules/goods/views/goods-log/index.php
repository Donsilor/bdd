<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\daterange\DateRangePicker;
use yii\grid\GridView;
use common\helpers\ImageHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->title = '日志';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-sm-12">
        <div class="box">
            <div class="box-body table-responsive">  
    <?php //echo Html::batchButtons()?>                  
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-hover'],
        'showFooter' => true,//显示footer行
        'id'=>'grid',            
        'columns' => [
            [
                    'class' => 'yii\grid\SerialColumn',
                    'visible' => false,
            ],
            [
                    'class'=>'yii\grid\CheckboxColumn',
                    'name'=>'id',  //设置每行数据的复选框属性
                    'headerOptions' => ['width'=>'30'],
            ],
            [
                    'attribute' => 'id',
                    'filter' => true,
                    'format' => 'raw',
                    'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute' => 'log_msg',
                'value' => "log_msg",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'400'],
            ],
            [
                'attribute' => 'log_time',
                'filter' => DateRangePicker::widget([    // 日期组件
                    'model' => $searchModel,
                    'attribute' => 'log_time',
                    'value' => '',
                    'options' => ['readonly' => true, 'class' => 'form-control',],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'locale' => [
                            'separator' => '/',
                        ],
                        'endDate' => date('Y-m-d', time()),
                        'todayHighlight' => true,
                        'autoclose' => true,
                        'todayBtn' => 'linked',
                        'clearBtn' => true,
                    ],
                ]),
                'value' => function ($model) {
                    return Yii::$app->formatter->asDatetime($model->log_time);
                },
                'format' => 'raw',
                'headerOptions' => ['width'=>'200'],
            ],
            [
                'attribute' => 'log_role',
                'value' => "log_role",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'100'],
            ],
            [
                'attribute' => 'log_user',
                'value' => "log_user",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'100'],
            ]
        ]
      ]);
    ?>
            </div>
        </div>
    </div>
</div>
