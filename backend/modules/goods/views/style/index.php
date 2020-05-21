<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use common\helpers\ImageHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
$goods_title = Yii::t('goods', $typeModel['type_name'].'商品列表');
$this->title = Yii::t('goods', $typeModel['type_name'].'管理');
$this->params['breadcrumbs'][] = $this->title;
$type_id = Yii::$app->request->get('type_id',0);
?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="<?= Url::to(['style/index?type_id='.$type_id]) ?>"> <?= Html::encode($this->title) ?></a></li>
                <li><a href="<?= Url::to(['goods/index?type_id='.$type_id]) ?>"> <?= Html::encode($goods_title) ?></a></li>
                <li class="pull-right">
                	<div class="box-header box-tools">
                    <?= Html::create(['edit-lang','type_id'=>$type_id]) ?>
                    </div>
                </li>
            </ul>
            <div class="box-body table-responsive">
    <?php echo Html::batchButtons(false)?>         
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
                'attribute' => 'lang.language',
                 'value' => function ($model) {
                    return \common\enums\LanguageEnum::getValue($model->lang->language);
                 },
                 'filter' => Html::activeDropDownList($searchModel, 'language',\common\enums\LanguageEnum::getMap(), [
                        'prompt' => '默认',
                        'class' => 'form-control',
                ]),
                'headerOptions' => ['width'=>'110'],
            ], 
            [
                'attribute' => 'style_image',
                'value' => function ($model) {
                    return ImageHelper::fancyBox($model->style_image);
                },
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],                
            ],
                
            [
                //'headerOptions' => ['width'=>'200'],
                'attribute' => 'lang.style_name',
                'value' => 'lang.style_name',
                'filter' => Html::activeTextInput($searchModel, 'style_name', [
                        'class' => 'form-control',
                ]),
                'format' => 'raw',                
            ],
            [
                'attribute' => 'style_sn',
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
            
            [
                    'attribute' => 'type_id',
                    'value' => "type.type_name",
                    'filter' => Html::activeDropDownList($searchModel, 'type_id',Yii::$app->services->goodsType->getGrpDropDown($type_id,0), [
                        'prompt' => '全部',
                        'class' => 'form-control',
                    ]),
                    'format' => 'raw',
                    'headerOptions' => ['width'=>'120'],
            ],       
            [
                'attribute' => 'sale_price',
                'value' => "sale_price",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'100'],
            ],
//            [
//                'attribute' => 'sale_volume',
//                'value' => "sale_volume",
//                'filter' => true,
//                'format' => 'raw',
//                'headerOptions' => ['width'=>'80'],
//            ],
            [
                'attribute' => 'goods_storage',
                'value' => "goods_storage",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\FrameEnum::getValue($model->status);
                },
                'filter' => Html::activeDropDownList($searchModel, 'status',\common\enums\FrameEnum::getMap(), [
                    'prompt' => '全部',
                    'class' => 'form-control',                        
                ]),
            ],            
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {view} {status} {show_log}',
                'buttons' => [
                'edit' => function($url, $model, $key){
                    return Html::edit(['edit-lang','id' => $model->id,'type_id'=>Yii::$app->request->get('type_id'),'returnUrl' => Url::getReturnUrl()]);
                },
               'status' => function($url, $model, $key){
                        return Html::status($model['status']);
                },
                'delete' => function($url, $model, $key){
                        return Html::delete(['delete', 'id' => $model->id]);
                },
                'view'=> function($url, $model, $key){
                   if($model->type_id == 2){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/ring/wedding-rings/'.$model->id.'?goodId='.$model->id.'&ringType=single&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 12){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/ring/engagement-rings/'.$model->id.'?goodId='.$model->id.'&ringType=engagement&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 4){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/necklace/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 5){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/pendant/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 6){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/studEarring/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 7){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/earring/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 8){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/braceletLine/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 9){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/bracelet/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }

                },
                'show_log' => function($url, $model, $key){
                    return Html::linkButton(['goods-log/index','id' => $model->id, 'type_id' => $model->type_id, 'returnUrl' => Url::getReturnUrl()], '日志');
                },
                ]
            ]
    ]
    ]); ?>
            </div>
        </div>
    </div>
</div>
