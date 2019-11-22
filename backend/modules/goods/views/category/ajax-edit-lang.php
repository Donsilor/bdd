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

        <ul class="nav nav-tabs">
            <?php foreach (\Yii::$app->params['languages'] as $lang_key=>$lang_name){?>
                <li class="<?php echo Yii::$app->language==$lang_key?"active":"" ?>">
                    <a href="#tab_<?php echo $lang_key?>" data-toggle="tab" aria-expanded="false"><?php echo $lang_name?></a>
                </li>
            <?php }?>
        </ul>

        <div class="tab-content">
            <?= $form->field($model, 'pid')->dropDownList($cateDropDownList) ?>
            <?php $newLangModel = $model->langModel();?>
            <?php
            foreach (\Yii::$app->params['languages'] as $lang_key=>$lang_name){
                $is_new = true;
                ?>
                <?php foreach ($model->langs as $langModel) {?>
                    <?php if($lang_key == $langModel->language){?>
                        <!-- 编辑-->
                        <div class="tab-pane<?php echo Yii::$app->language==$lang_key?" active":"" ?>" id="tab_<?= $lang_key?>">
                            <?= $form->field($langModel, 'cat_name')->textInput(['name'=>Html::langInputName($langModel,$lang_key,"cat_name")]) ?>
                            <?= $form->field($langModel, 'meta_title')->textInput(['name'=>Html::langInputName($langModel,$lang_key,"meta_title")]) ?>
                            <?= $form->field($langModel, 'meta_word')->textInput(['name'=>Html::langInputName($langModel,$lang_key,"meta_word")]) ?>
                            <?= $form->field($langModel, 'meta_desc')->textArea(['name'=>Html::langInputName($langModel,$lang_key,"meta_desc"),'rows'=>'3']) ?>
                        </div>
                        <!-- /.tab-pane -->
                        <?php $is_new = false; break;?>
                    <?php }?>
                <?php }?>
                <?php if($is_new == true){?>
                    <!-- 新增 -->
                    <div class="tab-pane<?php echo Yii::$app->language==$lang_key?" active":"" ?>" id="tab_<?= $lang_key?>">
                        <?= $form->field($newLangModel, 'cat_name')->textInput(['name'=>Html::langInputName($newLangModel,$lang_key,"cat_name")]) ?>
                        <?= $form->field($newLangModel, 'meta_title')->textInput(['name'=>Html::langInputName($newLangModel,$lang_key,"meta_title")]) ?>
                        <?= $form->field($newLangModel, 'meta_word')->textInput(['name'=>Html::langInputName($newLangModel,$lang_key,"meta_word")]) ?>
                        <?= $form->field($newLangModel, 'meta_desc')->textArea(['name'=>Html::langInputName($newLangModel,$lang_key,"meta_desc"),'rows'=>'3']) ?>
                    </div>
                    <!-- /.tab-pane -->
                <?php }?>
            <?php }?>
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