<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use common\helpers\ImageHelper;

$id = $searchModel->id;
$goods_name = $searchModel->goods_name;
$goods_sn = $searchModel->goods_sn;
$cert_id = $searchModel->cert_id;
$sale_price = $searchModel->sale_price;
$carat = $searchModel->carat;
$status = $searchModel->status;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('goods_diamond', '裸钻管理');
$this->params['breadcrumbs'][] = $this->title;
//$cert_type = \common\enums\DiamondEnum::getCertTypeList();

$yesOrNo = \common\enums\StatusEnum::getYesOrNo();
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools"  style="right: 100px;">
                    <?= Html::create(['edit-lang']) ?>
                    <?= Html::a('导出Excel','export?goods_name='.$goods_name.'&id='.$id.'&goods_sn='.$goods_sn.'&cert_id='.$cert_id.'&sale_price='.$sale_price.'&carat='.$carat.'&status='.$status) ?>
                </div>

            </div>
            <div class="box-body table-responsive">
    <?php echo Html::batchButtons(false)?>         
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-hover'],
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
                'value' => 'id',
                'filter' => Html::activeTextInput($searchModel, 'id', [
                    'class' => 'form-control',
                ]),
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute' => 'hk_status',
                'value' => function ($model) {
                    return \common\enums\StatusEnum::getValue($model->hk_status, 'getYesOrNo');
                },
                'filter' => Html::activeDropDownList($searchModel, 'hk_status', $yesOrNo, [
                    'prompt' => '全部',
                    'class' => 'form-control',
                ]),
                'headerOptions' => ['width'=>'110'],
            ],
            [
                'attribute' => 'cn_status',
                'value' => function ($model) {
                    return \common\enums\StatusEnum::getValue($model->cn_status, 'getYesOrNo');
                },
                'filter' => Html::activeDropDownList($searchModel, 'cn_status', $yesOrNo, [
                    'prompt' => '全部',
                    'class' => 'form-control',
                ]),
                'headerOptions' => ['width'=>'110'],
            ],
            [
                'attribute' => 'us_status',
                'value' => function ($model) {
                    return \common\enums\StatusEnum::getValue($model->us_status, 'getYesOrNo');
                },
                'filter' => Html::activeDropDownList($searchModel, 'us_status', $yesOrNo, [
                    'prompt' => '全部',
                    'class' => 'form-control',
                ]),
                'headerOptions' => ['width'=>'110'],
            ],
            [
                'attribute' => 'goods_image',
                'value' => function ($model) {
                    return ImageHelper::fancyBox($model->goods_image);
                },
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute' => 'lang.goods_name',
                'value' => 'lang.goods_name',
                'filter' => Html::activeTextInput($searchModel, 'goods_name', [
                    'class' => 'form-control',
                ]),
                'format' => 'raw',
                'headerOptions' => ['width'=>'300'],
            ],
            [
                'attribute' => 'goods_sn',
                'value' => 'goods_sn',
                'filter' => Html::activeTextInput($searchModel, 'goods_sn', [
                    'class' => 'form-control',
                ]),
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
            //'goods_image',
            //'goods_num',
//            [
//                'attribute' => 'cert_type',
//                'format' => 'raw',
//                'headerOptions' => ['class' => 'col-md-1'],
//                'value' => function ($model){
//                    return $cert_type[$model->cert_type];
//                },
//                'filter' => Html::activeDropDownList($searchModel, 'cert_type',$cert_type, [
//                    'prompt' => '全部',
//                    'class' => 'form-control',
//                ]),
//            ],
            [
                'attribute' => 'cert_id',
                'value' => 'cert_id',
                'filter' => Html::activeTextInput($searchModel, 'cert_id', [
                    'class' => 'form-control',
                    'style' =>'width:100px'
                ]),
                'format' => 'raw',
            ],

            [
                'attribute' => 'sale_price',
                'filter' => true,
                'format' => 'raw',
            ],

            //'cost_price',
            [
                'attribute' => 'carat',
                'filter' => true,
                'format' => 'raw',
            ],

            //'clarity',
            //'cut',
            //'color',
            //'shape',
            //'depth_lv',
            //'table_lv',
            //'symmetry',
            //'polish',
            //'fluorescence',
            //'source_id',
            //'source_discount',
            //'is_stock',
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
            //'created_at',
            //'updated_at',
            [
                'attribute' => 'created_at',
                'filter' => \kartik\daterange\DateRangePicker::widget([    // 日期组件
                    'model' => $searchModel,
                    'attribute' => 'created_at',
                    'value' => $searchModel->created_at,
                    'options' => ['readonly' => true,'class'=>'form-control','style'=>'background-color:#fff;'],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'locale' => [
                            'separator' => '/',
                            'cancelLabel'=> '清空',
                        ],
                        'endDate' => date('Y-m-d',time()),
                        'todayHighlight' => true,
                        'autoclose' => true,
                        'todayBtn' => 'linked',
                        'clearBtn' => true,
                    ],
                ]),
                'value' => function ($model) {
                    return Yii::$app->formatter->asDatetime($model->created_at);
                },
                'format' => 'raw',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {status} {view} {show_log}',
                'buttons' => [
                'edit' => function($url, $model, $key){
                        return Html::edit(['edit-lang', 'id' => $model->id, 'returnUrl' => Url::getReturnUrl()]);
                },
               'status' => function($url, $model, $key){
                        return Html::status($model['status']);
                  },
                'delete' => function($url, $model, $key){
                        return Html::delete(['delete', 'id' => $model->id]);
                },
                'view'=> function($url, $model, $key){
                    return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/diamond-details/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
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
<script>
    (function ($) {

        $("[data-krajee-daterangepicker]").on("cancel.daterangepicker", function () {
            $(this).val("").trigger("change");
        });

    })(window.jQuery);
</script>