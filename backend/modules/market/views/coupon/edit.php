<?php

use common\models\market\MarketSpecials;
use yii\widgets\ActiveForm;
use common\enums\WhetherEnum;
use common\enums\StatusEnum;
use common\helpers\StringHelper;
use kartik\datetime\DateTimePicker;
use common\enums\PreferentialTypeEnum;
use common\helpers\Html;
use common\enums\AreaEnum;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => '优惠劵', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                ],
            ]); ?>
            <div class="box-body">
                <div class="col-lg-12">
                    <div class="row form-group">
                        <div class="col-sm-2 text-right">
                            <label class="control-label">优惠券类型</label>
                        </div>
                        <div class="col-sm-10">
                            <?= PreferentialTypeEnum::getValue($specials->type); ?>
                        </div>
                    </div>
                    <?php if($specials->product_range==1) { ?>
                        <?php $model->goods_attach = empty($model->goods_attach)?'':implode(',', $model->goods_attach);  ?>
                        <?= $form->field($model, 'goods_attach')->textarea(['rows'=>3])->hint(' 0代表无限制'); ?>
                    <?php } else { ?>
                        <?= $form->field($model, 'goods_type_attach')->checkboxList(\services\goods\TypeService::getTypeList()); ?>
                    <?php } ?>
                    <?= $form->field($model, 'area_attach')->checkboxList(AreaEnum::getMap()); ?>
                    <?= $form->field($model, 'count')->textInput(); ?>
                    <div id="money" class="<?= $specials->type == PreferentialTypeEnum::DISCOUNT ? 'hide' : ''; ?>">
                        <?= $form->field($model, 'at_least')->textInput()->hint(' 0代表无限制'); ?>
                        <?= $form->field($model, 'money')->textInput(); ?>
                    </div>
                    <div id="discount" class="<?= $specials->type == PreferentialTypeEnum::MONEY ? 'hide' : ''; ?>">
                        <?= $form->field($model, 'discount')->textInput()->hint('百分比，范围(1-100)'); ?>
                    </div>
                </div>
            </div>
            <div class="box-footer text-center">
                <button class="btn btn-primary" type="submit">保存</button>
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>

</script>