<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['edit-follower', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
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
//            'options' => ['placeholder' => '请选择'],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);?>
        <?php $model->seller_remark = ''; ?>
        <?= $form->field($model, 'is_test')->dropDownList(\common\enums\OrderStatusEnum::testStatus())->label('是否测试') ?>
        <?= $form->field($model, 'seller_remark')->textarea() ?>
    </div>
    <div class="modal-body">
        <table class="table table-hover">
            <thead>
            <tr>
                <td width="160px;">时间</td>
                <td>跟进人</td>
                <td>备注</td>
            </tr>
            </thead>
            <tbody>
            <?php if($orderLog) {
            foreach ($orderLog as $item) { ?> <tr>
                <td><?= Yii::$app->formatter->asDatetime($item->log_time) ?></td>
                <td><?= $item->log_user ?></td>
                <td><?= $item->data[0]['seller_remark']??'' ?></td>
            </tr>
            <?php }} else { ?> <tr>
                <td colspan="3">无跟进信息</td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>