<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use kartik\datetime\DateTimePicker;


$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ele-invoice-ajax-edit','order_id' => $order_id,'returnUrl'=>$returnUrl]),
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
            <?= $form->field($model, 'language')->widget(kartik\select2\Select2::class, [
                'data' => common\enums\LanguageEnum::getMap(),
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],

            ]);?>
            <?= $form->field($model, 'invoice_date')->widget(DateTimePicker::class, [
                'language' => 'zh-CN',
                'options' => [
                    'value' => $model->isNewRecord ? date('Y-m-d') : date('Y-m-d', $model->invoice_date),
                ],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,//今日高亮
                    'autoclose' => true,//选择后自动关闭
                    'todayBtn' => true,//今日按钮显示
                ]
            ]);?>

            <?= $form->field($model, 'sender_name')->textInput(); ?>
            <?= $form->field($model, 'platforms_group')->radioList(\common\enums\OrderFromEnum::groups(), ['value'=>\common\enums\OrderFromEnum::platformToGroup($model->order->order_from)]); ?>
            <?= $form->field($model, 'sender_area')->textInput(); ?>
            <?= $form->field($model, 'sender_address')->textArea(); ?>
            <?= $form->field($model, 'express_id')->widget(kartik\select2\Select2::class, [
                'data' => Yii::$app->services->express->getDropDown(),
                'options' => ['placeholder' => '请选择'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);?>
            <?= $form->field($model, 'express_no')->textInput(); ?>
            <?= $form->field($model, 'delivery_time')->widget(DateTimePicker::class, [
                'language' => 'zh-CN',
                'options' => [
                    'value' => $model->isNewRecord ? date('Y-m-d') : date('Y-m-d', $model->delivery_time),
                ],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,//今日高亮
                    'autoclose' => true,//选择后自动关闭
                    'todayBtn' => true,//今日按钮显示
                ]
            ]);?>
         <?= $form->field($model, 'email')->textInput(); ?>

        <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
    <div class="modal-footer">
        <?= $form->field($model,'order_id')->hiddenInput()->label(false)?>
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
<?php ActiveForm::end(); ?>

<script>
    var sendAddress = <?= \GuzzleHttp\json_encode(Yii::$app->services->orderInvoice->sendAddressInfo()); ?>;

    (function ($) {
        var language = $("select[name='OrderInvoiceEle[language]']");
        var platforms_group = $("input[name='OrderInvoiceEle[platforms_group]']");
        var sender_area = $("#orderinvoiceele-sender_area");
        var sender_address = $("#orderinvoiceele-sender_address");

        function addressInit() {
            //初使化数据
            if(sender_area.val()==="" && sender_address.val()==="") {
                let platforms_group2 = $("input[name='OrderInvoiceEle[platforms_group]']:checked").val();
                let sendAddressInfo = sendAddress[platforms_group2][language.val()];
                sender_area.val(sendAddressInfo['name']);
                sender_address.val(sendAddressInfo['detailed']);
            }
        }

        //切换站点地区
        platforms_group.click(function () {
            //初使化数据
            if(sender_area.val()!=="" || sender_address.val()!=="") {
                rfMsg("请先清空“发货地区”和“发货人地址”信息！");
                return false;
            }
            addressInit()
        });

        //
        addressInit();

    })(window.jQuery);
</script>
