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
        <h4 class="modal-title">基本信息</h4>
    </div>

    <div class="modal-body">

        <?php echo Html::langTab('tab')?>

        <div class="tab-content">

            <?= $form->field($model, 'pid')->widget(kartik\select2\Select2::class, [
                'data' => $cateDropDownList,
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);?>
            <?php
            echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                'fields'=>
                    [
                        'type_name'=>['type'=>'textInput'],
                        'type_title'=> ['type'=>'textInput'],
                        'type_desc'=> ['type'=>'textArea','options'=>['rows'=>'3']],
                        'meta_title'=>['type'=>'textInput'],
                        'meta_word'=>['type'=>'textInput'],
                        'meta_desc'=>['type'=>'textArea','options'=>['rows'=>'3']]
                    ]]);
            ?>
        </div>


            <?= $form->field($model, 'image')->widget(Files::class, [
                'config' => [
                    // 可设置自己的上传地址, 不设置则默认地址
                    // 'server' => '',
                    'pick' => [
                        'multiple' => false,
                    ],
                ]
            ]); ?>



            <?= $form->field($model, 'sort')->textInput(); ?>
            <?= $form->field($model, 'status')->radioList(StatusEnum::getMap()); ?>
            <!-- /.tab-pane -->
        </div>
        <!-- /.tab-content -->
    </div>



    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>