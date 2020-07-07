<?php
use common\widgets\webuploader\Files;
use yii\widgets\ActiveForm;
use common\helpers\Url;
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">基本信息</h4>
    </div>
    <div class="modal-body">
        <?php echo Html::langTab('tab')?>
        <div class="tab-content">
            <?php if($model->isNewRecord){ ?>
                <?= $form->field($model, 'page_name')->textInput(['maxlength' => true]) ?>
            <?php }else{ ?>
                <?= $form->field($model, 'page_name')->textInput(['maxlength' => true, 'readonly' => 'true']) ?>
            <?php } ?>
            <?= $form->field($model, 'platform')->dropDownList(\common\enums\OrderFromEnum::getMap(), [
                'prompt' => '请选择',
                'class' => 'form-control',
            ]) ?>
            <?= $form->field($model, 'route')->textInput(['maxlength' => true]) ?>
            <?php
            echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                'fields'=>
                    [
                        'meta_title'=>['type'=>'textInput'],
                        'meta_word'=>['type'=>'textArea','options'=>['rows'=>'4']],
                        'meta_desc'=>['type'=>'textArea','options'=>['rows'=>'4']],
                    ]]);
            ?>
        </div>
        <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>