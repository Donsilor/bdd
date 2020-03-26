<?php

use common\models\market\MarketSpecials;
use yii\widgets\ActiveForm;
use common\enums\WhetherEnum;
use common\enums\StatusEnum;
use common\helpers\StringHelper;
use kartik\datetime\DateTimePicker;
use common\enums\PreferentialTypeEnum;
use common\enums\ProductRangeEnum;
use common\helpers\Html;
use common\enums\AreaEnum;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => '优惠劵', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                ],
            ]); ?>
            <div class="box-body">
                <div class="col-lg-12">
                    <div class="row b">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-5">
                            <?php echo Html::langTab('tab')?>
                        </div>
                    </div>

                    <div class="tab-content">
                    <?php echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                        'fields'=>
                            [
                                'title'=>['type'=>'textInput'],
                                'describe'=> ['type'=>'textArea','options'=>['rows'=>'3']],
                            ]]);
                    ?>
                    </div>
                    <div class="row">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-5">
                            <?= $form->field($model, 'start_time', [
                                'template' => "{label}{input}\n{hint}\n{error}",
                            ])->widget(DateTimePicker::class, [
                                'language' => 'zh-CN',
                                'options' => [
                                    'value' => StringHelper::intToDate($model->start_time),
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii',
                                    'todayHighlight' => true,//今日高亮
                                    'autoclose' => true,//选择后自动关闭
                                    'todayBtn' => true,//今日按钮显示
                                ],
                            ]); ?>
                        </div>
                        <div class="col-sm-5">
                            <?= $form->field($model, 'end_time', [
                                'template' => "{label}{input}\n{hint}\n{error}",
                            ])->widget(DateTimePicker::class, [
                                'language' => 'zh-CN',
                                'options' => [
                                    'value' => StringHelper::intToDate($model->end_time),
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii',
                                    'todayHighlight' => true,//今日高亮
                                    'autoclose' => true,//选择后自动关闭
                                    'todayBtn' => true,//今日按钮显示
                                ],
                            ]); ?>
                        </div>
                    </div>
                    <?= $form->field($model, 'area_attach')->checkboxList(AreaEnum::getMap()); ?>
                    <?= $form->field($model, "banner_image")->widget(common\widgets\webuploader\Files::class, [
                        'config' => [
                            'pick' => [
                                'multiple' => true,
                            ],
                            /* 'formData' => [
                                    'drive' => 'oss',// 默认本地 支持 qiniu/oss 上传
                                    'thumb' => [
                                            [
                                                    'width' => 800,
                                                    'height' => 800,
                                            ]
                                    ]
                            ], */
                        ]
                    ]); ?>
                    <?= $form->field($model, "label_image")->widget(common\widgets\webuploader\Files::class, [
                        'config' => [
                            'pick' => [
                                'multiple' => false,
                            ],
                            /* 'formData' => [
                                    'drive' => 'oss',// 默认本地 支持 qiniu/oss 上传
                                    'thumb' => [
                                            [
                                                    'width' => 800,
                                                    'height' => 800,
                                            ]
                                    ]
                            ], */
                        ]
                    ]); ?>
                    <?= $form->field($model, 'type')->radioList(PreferentialTypeEnum::getMap()); ?>
                    <?= $form->field($model, 'product_range')->radioList(ProductRangeEnum::getMap()); ?>
                    <?= $form->field($model, 'status')->radioList(StatusEnum::getMap()); ?>
                </div>
            </div>
            <div class="box-footer text-center">
                <button class="btn btn-primary" type="submit">保存</button>
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>

</script>