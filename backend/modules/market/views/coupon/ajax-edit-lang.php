<?php

use yii\widgets\ActiveForm;
use common\enums\PreferentialTypeEnum;
use common\enums\AreaEnum;
use common\helpers\Url;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => '优惠劵', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    <h4 class="modal-title">基本信息</h4>
</div>
<?php $form = ActiveForm::begin([
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit','id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ],
]); ?>
<div class="modal-body">
    <div class="row form-group">
        <div class="col-sm-2 text-right">
            <label class="control-label">活动类型</label>
        </div>
        <div class="col-sm-10">
            <?= PreferentialTypeEnum::getValue($specials->type); ?>
        </div>
    </div>
    <?php if($specials->product_range==1) { ?>
        <?php $model->goods_attach = empty($model->goods_attach)?'':implode(',', $model->goods_attach);  ?>
        <?= $form->field($model, 'goods_attach')->textarea(['rows'=>3])->hint('款号之间用英文状态下,隔开。'); ?>
    <?php } else { ?>
        <?= $form->field($model, 'goods_type_attach')->checkboxList(\services\goods\TypeService::getTypeList()); ?>
    <?php }
    $areaEnum = AreaEnum::getMap();
    unset($areaEnum[3]);
    ?>
    <?= $form->field($model, 'area_attach')->checkboxList($areaEnum); ?>
    <?= $form->field($model, 'count')->textInput(); ?>
    <div id="money" class="<?= $specials->type == PreferentialTypeEnum::DISCOUNT ? 'hide' : ''; ?>">
        <?= $form->field($model, 'at_least')->textInput()->hint(' 0代表无限制'); ?>
        <?= $form->field($model, 'money')->textInput(); ?>
    </div>
    <div id="discount" class="<?= $specials->type == PreferentialTypeEnum::MONEY ? 'hide' : ''; ?>">
        <?= $form->field($model, 'discount')->textInput()->hint('百分比，范围(1-100)'); ?>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
    <button class="btn btn-primary" type="submit">保存</button>
</div>
<?php ActiveForm::end(); ?>
