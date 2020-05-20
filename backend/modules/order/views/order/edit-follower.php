<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "{label}{input}{hint}",
    ]
]);
?>

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">跟进</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'follower_id')->widget(kartik\select2\Select2::class, [
            'data' => [Yii::$app->user->getIdentity()->id=>Yii::$app->user->getIdentity()->username],
            'options' => ['placeholder' => '请选择'],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);?>
        <?php $model->seller_remark = ''; ?>
        <?= $form->field($model, 'seller_remark')->textarea() ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>