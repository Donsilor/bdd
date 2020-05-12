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
                <label class="control-label" for="wiretransfer-collection_amount">订单号</label>
            </div>
            <div class="col-sm-10">
                <input type="text" class="form-control" value="<?= $model->order->order_sn; ?>" readonly="true">
                <div class="help-block"></div>
            </div>
        </div>
        <div class="form-group field-wiretransfer-collection_amount">
            <div class="col-sm-2 text-right">
                <label class="control-label" for="wiretransfer-collection_amount">订单金额</label>
            </div>
            <div class="col-sm-10">
                <input type="text" class="form-control" value="<?= $model->order->account->order_amount; ?>" readonly="true">
                <div class="help-block"></div>
            </div>
        </div>
        <div class="form-group field-wiretransfer-collection_amount">
            <div class="col-sm-2 text-right">
                <label class="control-label" for="wiretransfer-collection_amount">应付金额</label>
            </div>
            <div class="col-sm-10">
                <input type="text" class="form-control" value="<?= $model->order->account->order_amount; ?>" readonly="true">
                <div class="help-block"></div>
            </div>
        </div>
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
        <?= $form->field($model, "collection_voucher")->widget(common\widgets\webuploader\Files::class, [
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
        ])->label('收款凭证'); ?>
        <?= $form->field($model, 'collection_amount')->textInput()->label('收款金额')->hint('收款金额必需与应收款金额一至才能确认支付'); ?>
        <?= $form->field($model, 'collection_status')->dropDownList(\common\enums\WireTransferEnum::getMap())->label('审核状态') ?>
    </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>