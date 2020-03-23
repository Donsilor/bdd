<?php

use common\models\market\MarketSpecials;
use yii\widgets\ActiveForm;
use common\enums\WhetherEnum;
use common\enums\StatusEnum;
use common\helpers\StringHelper;
use kartik\datetime\DateTimePicker;
use common\enums\PreferentialTypeEnum;

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
                    <?= $form->field($model, 'title')->textInput(); ?>
                    <?= $form->field($model, 'type')->radioList(PreferentialTypeEnum::getMap()); ?>


                    <div class="row">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-5">
                            <?= $form->field($model, 'get_start_time', [
                                'template' => "{label}{input}\n{hint}\n{error}",
                            ])->widget(DateTimePicker::class, [
                                'language' => 'zh-CN',
                                'options' => [
                                    'value' => StringHelper::intToDate($model->get_start_time),
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii',
                                    'todayHighlight' => true,//今日高亮
                                    'autoclose' => true,//选择后自动关闭
                                    'todayBtn' => true,//今日按钮显示
                                ],
                            ]); ?>
                        </div>
                        <div class="col-sm-5">
                            <?= $form->field($model, 'get_end_time', [
                                'template' => "{label}{input}\n{hint}\n{error}",
                            ])->widget(DateTimePicker::class, [
                                'language' => 'zh-CN',
                                'options' => [
                                    'value' => StringHelper::intToDate($model->get_end_time),
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii',
                                    'todayHighlight' => true,//今日高亮
                                    'autoclose' => true,//选择后自动关闭
                                    'todayBtn' => true,//今日按钮显示
                                ],
                            ]); ?>
                        </div>
                    </div>
                    <?= $form->field($model, 'status')->radioList(StatusEnum::getMap()); ?>
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
    $("input[name='CouponTypeForm[range_type]']").click(function () {
        var val = $(this).val();
        if (parseInt(val) === 1) {
            $('#productIds').addClass('hide');
        } else {
            $('#productIds').removeClass('hide');
        }
    });

    $("input[name='CouponTypeForm[term_of_validity_type]']").click(function () {
        var val = $(this).val();
        if (parseInt(val) === 1) {
            $('#time').addClass('hide');
            $('#fixed_term').removeClass('hide');
        } else {
            $('#time').removeClass('hide');
            $('#fixed_term').addClass('hide');
        }
    });

    $("input[name='CouponTypeForm[type]']").click(function () {
        var val = $(this).val();
        if (parseInt(val) === 2) {
            $('#money').addClass('hide');
            $('#discount').removeClass('hide');
        } else {
            $('#money').removeClass('hide');
            $('#discount').addClass('hide');
        }
    })
</script>