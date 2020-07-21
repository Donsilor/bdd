<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use kartik\datetime\DateTimePicker;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['edit-delivery', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "{label}{input}{hint}",
    ]
]);
?>

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">发货 跟进</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'express_id')->widget(kartik\select2\Select2::class, [
            'data' => Yii::$app->services->express->getDropDown(),
            'options' => ['placeholder' => '请选择'],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);?>
        <?= $form->field($model, 'express_no')->textInput()->hint('<span class="red">*注：请仔细核对单号，否则物流信息无法显示！</span>') ?>
        <?= $form->field($model, 'delivery_time')->widget(DateTimePicker::class, [
            'language' => 'zh-CN',
            'options' => [
                'value' => date('Y-m-d H:i:s',time()),
            ],
            'pluginOptions' => [
                'format' => 'yyyy-mm-dd hh:ii',
                'todayHighlight' => true,//今日高亮
                'autoclose' => true,//选择后自动关闭
                'todayBtn' => true,//今日按钮显示
            ]
        ]);?>
        <?= $form->field($model->address, 'email')->textInput()->label('收件邮箱') ?>
        <?= $form->field($model, 'send_now')->radioList(['0'=>'否', '1'=>'是'], ['value'=>0])->label('是否立即发送“发货邮件”') ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>
<script>
    $("#deliveryform-express_no").change(function (e) {
        $(this).val($(this).val().trim());
    });
</script>
