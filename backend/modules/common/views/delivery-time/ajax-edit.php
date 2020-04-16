<?php
use common\widgets\webuploader\Files;
use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\helpers\Html;




$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit','id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n<span style='color: #bac1c6'>{hint}</span>\n{error}</div>",
    ]
]);
?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">基本信息</h4>
    </div>
    <div class="modal-body">

        <div class="tab-content">
            <?= $form->field($model, 'area_id')->widget(\kartik\select2\Select2::class, [
                'data' => common\enums\AreaEnum::getMap(),
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);?>
            <?= $form->field($model, 'futures_time')->hint('格式：XX-XX')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'stock_time')->hint('格式：XX-XX')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'status')->radioList(\common\enums\StatusEnum::getMap()); ?>



        </div>
        <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>