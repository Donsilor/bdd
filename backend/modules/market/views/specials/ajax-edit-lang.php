<?php

use yii\widgets\ActiveForm;
use common\enums\StatusEnum;
use common\helpers\StringHelper;
use kartik\datetime\DateTimePicker;
use common\enums\PreferentialTypeEnum;
use common\enums\ProductRangeEnum;
use common\helpers\Html;
use common\helpers\Url;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => '优惠劵', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    <h4 class="modal-title">基本信息</h4>
</div>
<?php $form = ActiveForm::begin([
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit','id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ],
]); ?>
<div class="modal-body">
    <div class="col-lg-12">
        <?php echo Html::langTab('tab')?>
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
        <?= $form->field($model, "banner_image")->widget(common\widgets\webuploader\Files::class, [
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
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
    <button class="btn btn-primary" type="submit">保存</button>
</div>
<?php ActiveForm::end(); ?>
