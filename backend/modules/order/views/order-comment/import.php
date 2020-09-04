<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => false,
    'fieldConfig' => [
        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">导入评价</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'file')->fileInput()->hint("<br /><br />1、请严格按照以下模板文件导入评价<br />2、点击<a href='/backend/downloads/comments/orderComment.xlsx' class='red'>下载模板</a><br />") ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">提交</button>
    </div>
<?php ActiveForm::end(); ?>