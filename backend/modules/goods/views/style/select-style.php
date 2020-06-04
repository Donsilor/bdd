<?php

use yii\grid\GridView;
use yii\widgets\ActiveForm;
use common\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

//$this->title = Yii::t('goods', 'Styles');
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body table-responsive">
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
                            'class'=>'yii\grid\RadioButtonColumn',
                            'name'=>'id',  //设置每行数据的复选框属性
                            'headerOptions' => ['width'=>'30'],

                        ],
                        [
                            'attribute' => 'id',
                            'filter' => true,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'50'],
                        ],

                        [
                            'attribute' => 'lang.style_name',
                            'value' => 'lang.style_name',
                            'filter' => Html::activeTextInput($searchModel, 'style_name', [
                                'class' => 'form-control',
                                'style' =>'width:100px'
                            ]),
                            'format' => 'raw',

                        ],
                        [
                            'attribute' => 'style_sn',
                            'filter' => true,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'150'],
                        ],

                        [
                            'attribute' => 'type_id',
                            'value' => "type.type_name",
                            'filter' => true,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'100'],
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
                            'attribute' => 'sale_price',
                            'value' => "sale_price",
                            'filter' => false,
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'goods_storage',
                            'value' => "goods_storage",
                            'filter' => false,
                            'format' => 'raw',
                        ],


                    ]
                ]); ?>
            </div>
            <?php $form = ActiveForm::begin([]); ?>
            <input type="hidden" id="style_id" name="style_id" value=""/>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>


<script>
    $(function(){

        $(".content-header").hide();

        $("#grid").find('input[name="id"]').change(function(){
            $("#style_id").val($(this).val());
        });
    });

</script>
