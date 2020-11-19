<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ]
]);

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    <h4 class="modal-title">创建评价<span class="red" style="font-size: 8px;">*请仔细填写，创建完成后将即时展示</span></h4>
</div>
<div class="modal-body">
    <?= $form->field($model, 'style_sn')->textInput() ?>
    <?= $form->field($model, 'platform')->dropDownList(\common\enums\OrderFromEnum::getMap(), [
        'prompt' => '请选择',
        'class' => 'form-control',
    ]) ?>
    <?= $form->field($model, 'created_at')->widget(\kartik\datetime\DateTimePicker::class, [
        'language' => 'zh-CN',
        'options' => [
            'value' => \common\helpers\StringHelper::intToDate($model->created_at),
        ],
        'pluginOptions' => [
            'format' => 'yyyy-mm-dd hh:ii:ss',
            'todayHighlight' => true,//今日高亮
            'autoclose' => true,//选择后自动关闭
            'todayBtn' => true,//今日按钮显示
        ],
    ]); ?>
    <?= $form->field($model, 'username')->textInput() ?>
    <?= $form->field($model, 'grade')->dropDownList([1=>1,2=>2,3=>3,4=>4,5=>5], [
        'prompt' => '请选择',
        'class' => 'form-control',
    ]) ?>
    <?= $form->field($model, 'content')->textarea()->hint('至多输入200个字符') ?>
    <?= $form->field($model, "images")->widget(common\widgets\webuploader\Files::class, [
        'config' => [
            'pick' => [
                'multiple' => true,
            ],
        ]
    ]); ?>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
    <button class="btn btn-primary" type="submit">保存</button>
</div>
<?php ActiveForm::end(); ?>