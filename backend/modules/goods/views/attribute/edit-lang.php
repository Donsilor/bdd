<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\goods\Attribute */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('attribute', 'Attribute');
$this->params['breadcrumbs'][] = ['label' => Yii::t('attribute', 'Attributes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>                
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        //'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="nav-tabs-custom">
                     <?php echo Html::langTab("tab")?>           
    		 		 <div class="tab-content">
                       <?= common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                                'fields'=>
                                [
                                    'attr_name'=>['type'=>'textInput'],                                  
                                    'remark'=>['type'=>'textArea','options'=>[]]                            
                                ]]);
                	    ?>
                	    <?= $form->field($model, 'image')->widget(common\widgets\webuploader\Files::class, [
                            'config' => [
                                'pick' => [
                                    'multiple' => false,
                                ],
                            ]
                        ]); ?>
                        <?php if(Yii::$app->user->identity->username=='admin') {?>
                        <?= $form->field($model, 'erp_id')->textInput()?>
                        <?php }?>
                        <?= $form->field($model, 'status')->radioList(common\enums\StatusEnum::getMap())?>
                        <?= $form->field($model, 'sort')->textInput() ?>                    
                    </div>  
                </div>                
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <span class="btn btn-white" onclick="location.href='<?= Url::to(['attribute/index'])?>'">返回</span>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">属性值列表</h3>
                <div class="box-tools">
                    <?= Html::create(['attribute-value/ajax-edit-lang', 'attr_id' => $model->id], '添加属性值', [
                            'data-toggle' => 'modal',
                            'data-target' => '#ajaxModalLg',
                    ]); ?>
                </div>
            </div>                
            <div class="box-body">
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
                'attribute' => 'image',
                'value' => function ($model) {
                     return common\helpers\ImageHelper::fancyBox($model->image);
                 },
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute'=>'lang.attr_value_name',
            ], 
            [
                'attribute' => 'sort',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model, $key, $index, $column){
                    return  Html::sort($model->sort,['data-url'=>Url::to(['attribute-value/ajax-update'])]);
                }
            ],
            [
                'attribute'=>'erp_id',
                'value' => function ($model) {
                    return $model->erp_id;
                },
                'format' => 'raw',
                'visible' => Yii::$app->user->identity->username == 'admin'
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\StatusEnum::getValue($model->status);
                }
            ],            
            [
                'attribute'=>'updated_at',
                'value' => function ($model) {
                    return Yii::$app->formatter->asDatetime($model->updated_at);
                },
                'format' => 'raw',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {status} {delete}',
                'buttons' => [
                'edit' => function($url, $model, $key){                
                    return Html::edit(['attribute-value/ajax-edit-lang','id' => $model->id], '编辑', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModalLg',
                    ]);
                },
               'status' => function($url, $model, $key){
                        return Html::status($model->status,['data-url'=>Url::to(['attribute-value/ajax-update'])]);
                },
                'delete' => function($url, $model, $key){
                        return Html::delete(['attribute-value/delete', 'id' => $model->id]);
                },
                ]
            ]
    ]
    ]); ?>		


            </div> 
               
        </div>
    </div>
</div>
</div>
