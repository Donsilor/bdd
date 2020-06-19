<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use kartik\datetime\DateTimePicker;


$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ele-invoice-ajax-edit','order_id' => $order_id,'returnUrl'=>$returnUrl]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span></button>
        <h4 class="modal-title">基本信息</h4>
    </div>
    <div class="modal-body">
            <?= $form->field($model, 'language')->widget(kartik\select2\Select2::class, [
                'data' => common\enums\LanguageEnum::getMap(),
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],

            ]);?>
            <?= $form->field($model, 'invoice_date')->widget(DateTimePicker::class, [
                'language' => 'zh-CN',
                'options' => [
                    'value' => $model->isNewRecord ? date('Y-m-d') : date('Y-m-d', $model->invoice_date),
                ],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,//今日高亮
                    'autoclose' => true,//选择后自动关闭
                    'todayBtn' => true,//今日按钮显示
                ]
            ]);?>

            <?= $form->field($model, 'sender_name')->textInput(); ?>
            <?= $form->field($model, 'sender_address')->textArea(); ?>
            <?= $form->field($model, 'express_id')->widget(kartik\select2\Select2::class, [
                'data' => Yii::$app->services->express->getDropDown(),
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);?>
            <?= $form->field($model, 'express_no')->textInput(); ?>
            <?= $form->field($model, 'delivery_time')->widget(DateTimePicker::class, [
                'language' => 'zh-CN',
                'options' => [
                    'value' => $model->isNewRecord ? date('Y-m-d') : date('Y-m-d', $model->delivery_time),
                ],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,//今日高亮
                    'autoclose' => true,//选择后自动关闭
                    'todayBtn' => true,//今日按钮显示
                ]
            ]);?>
         <?= $form->field($model, 'email')->textInput(); ?>

        <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
    <div class="modal-footer">
        <?= $form->field($model,'order_id')->hiddenInput()->label(false)?>
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>