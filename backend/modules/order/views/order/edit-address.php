<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\helpers\Html;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['edit-address', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">客户信息修改</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model->address, 'lastname')->textInput() ?>
        <?= $form->field($model->address, 'firstname')->textInput() ?>
        <?= $form->field($model->address, 'mobile')->textInput() ?>
        <?= $form->field($model->address, 'email')->textInput() ?>

        <?= $form->field($model->address, 'country_id')->dropDownList(Yii::$app->services->area->getDropDown(), [
            'prompt' => '-- 请选择 --',
            'onchange' => 'widget_provinces(this, 1,"' . Html::getInputId($model->address, 'province_id') . '","' . Html::getInputId($model->address, 'city_id') . '")',
        ]); ?>

        <?= $form->field($model->address, 'province_id')->dropDownList(Yii::$app->services->area->getDropDown($model->address->country_id), [
        'prompt' => '-- 请选择 --',
        'onchange' => 'widget_provinces(this,2,"' . Html::getInputId($model->address, 'city_id') . '","' . Html::getInputId($model->address, 'city_id') . '")',
        ]); ?>

        <?= $form->field($model->address, 'city_id')->dropDownList(Yii::$app->services->area->getDropDown($model->address->province_id), [
        'prompt' => '-- 请选择 --',
        ]) ?>

        <?= $form->field($model->address, 'address_details')->textarea() ?>
        <?= $form->field($model->address, 'zip_code')->textInput() ?>
        <?= $form->field($model, 'buyer_remark')->textarea() ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>


<script>
    function widget_provinces(obj, type_id, cityId, areaId) {
        $(".form-group.field-" + areaId).hide();
        var pid = $(obj).val();
        $.ajax({
            type :"get",
            url : "area",
            dataType : "json",
            data : {type_id:type_id, pid:pid},
            success: function(data) {
                if (type_id == 2) {
                    $(".form-group.field-"+areaId).show();
                }

                $("select#"+cityId+"").html(data);
            }
        });
    }
</script>
