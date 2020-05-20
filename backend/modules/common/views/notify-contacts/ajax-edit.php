<?php
use common\widgets\webuploader\Files;
use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\enums\StatusEnum;
use common\helpers\Html;
$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit','id' => $model['id']]),
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
        <?= $form->field($model, 'area_attach')->checkboxList(\common\enums\AreaEnum::getMap()); ?>
        <?= $form->field($model, 'realname')->textInput()->label('被通知人'); ?>
        <?= $form->field($model, 'mobile')->textInput(); ?>
        <?= $form->field($model, 'mobile_switch')->dropDownList(StatusEnum::getMap())->label('启用短信通知') ?>
        <?= $form->field($model, 'email')->textInput(); ?>
        <?= $form->field($model, 'email_switch')->dropDownList(StatusEnum::getMap())->label('启用邮箱通知') ?>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>