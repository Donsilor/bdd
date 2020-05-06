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
        <?= $form->field($model, 'batch')->textInput()->label('生成批次'); ?>
        <?= $form->field($model, 'count')->textInput()->label('生成数量'); ?>
        <?= $form->field($model, 'amount')->textInput()->label('购物卡金额'); ?>
        <?= $form->field($model, 'start_time')->widget('kartik\date\DatePicker', [
            'language' => 'zh-CN',
            'layout' => '{picker}{input}',
            'pluginOptions' => [
                'format' => 'yyyy-mm-dd',
                'todayHighlight' => true,// 今日高亮
                'autoclose' => true,// 选择后自动关闭
                'todayBtn' => true,// 今日按钮显示
            ],
            'options' => [
                'class' => 'form-control no_bor',
            ]
        ]); ?>
        <?= $form->field($model, 'end_time')->widget('kartik\date\DatePicker', [
            'language' => 'zh-CN',
            'layout' => '{picker}{input}',
            'pluginOptions' => [
                'format' => 'yyyy-mm-dd',
                'todayHighlight' => true,// 今日高亮
                'autoclose' => true,// 选择后自动关闭
                'todayBtn' => true,// 今日按钮显示
            ],
            'options' => [
                'class' => 'form-control no_bor',
            ]
        ]); ?>
        <?= $form->field($model, 'goods_type_attach')->checkboxList(\services\goods\TypeService::getTypeList())->label('使用范围'); ?>
    </div>
    </div>



    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>