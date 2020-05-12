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
        <h4 class="modal-title">电汇审核</h4>
    </div>

    <div class="modal-body">
        <div class="form-group field-wiretransfer-collection_amount">
            <div class="col-sm-2 text-right">
                <label class="control-label" for="wiretransfer-collection_amount">付款凭证</label>
            </div>
            <div class="col-sm-10">
                <?= common\helpers\ImageHelper::fancyBox($model->payment_voucher); ?>
                <div class="help-block"></div>
            </div>
        </div>
        <div class="form-group field-wiretransfer-collection_amount">
            <div class="col-sm-2 text-right">
                <label class="control-label" for="wiretransfer-collection_amount">付款流水号</label>
            </div>
            <div class="col-sm-10">
                <input type="text" class="form-control" value="<?= $model->payment_serial_number; ?>" readonly="true">
                <div class="help-block"></div>
            </div>
        </div>
        <?= $form->field($model, 'collection_amount')->textInput()->label('收款金额'); ?>
    </div>
    </div>



    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>