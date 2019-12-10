<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model common\models\goods\Ring */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('goods_ring', 'Ring');
$this->params['breadcrumbs'][] = ['label' => Yii::t('goods_ring', 'Rings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$model->ring_images = $model->ring_images?explode(',', $model->ring_images):null;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        
                    ],
                ]); ?>
                <div class="col-sm-12  nav-tabs-custom">
                    <?php echo Html::langTab('tab')?>
                    <div class="tab-content">
                        <?php
                        echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                            'fields'=>
                                [
                                    'ring_name'=>['type'=>'textInput'],

                                ]]);
                        ?>
                        <div class="form-group field-ring-ring_sn">
                                <?= Html::create(['select-style'], '添加商品', [
                                    'class' => 'btn btn-primary btn-xs openIframe1',
                                ])?>
                                <div class="help-block"></div>
    
                                <table class="table table-hover"><thead>
                                    <tr>    
                                        <th>商品名称</th>
                                        <th>款式编号</th>
    
                                        <th>销售价</th>
                                        <th>商品库存</th>
    
                                        <th class="action-column">操作</th>
                                    </tr>
    
                                    </thead>
                                    <tbody id="style_table">
                                    </tbody></table>                           
                        </div>    
                        <?= $form->field($model, 'ring_sn')->textInput(['maxlength' => true]) ?>
    
                        <?= $form->field($model, 'ring_images')->widget(common\widgets\webuploader\Files::class, [
                            'type' => 'images',
                            'theme' => 'default',
                            'themeConfig' => [],
                            'config' => [
                                'pick' => [
                                    'multiple' => true,
                                ],
    
                            ]
                        ]); ?>
    
                        <?= $form->field($model, 'ring_style')->widget(kartik\select2\Select2::class, [
                            'data' => common\enums\SeriesEnum::getMap(),
                            'options' => ['placeholder' => '请选择'],
                            'pluginOptions' => [
                                'allowClear' => true
                            ],

                        ]);?>
                        <?= $form->field($model, 'sale_price')->textInput(['maxlength' => true]) ?>
                        <?= $form->field($model, 'status')->radioList(\common\enums\FrameEnum::getMap()) ?>
					</div><!-- ./tab-content -->
                    <div class="box-header with-border">
                        <h4 class="box-title">搜索引擎优化</h4>
                    </div>

                    <div class="col-sm-12 nav-tabs-custom">

                        <?php echo Html::langTab('tab1')?>
                        <div class="tab-content">
                            <?php
                            echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab1',
                                'fields'=>
                                    [
                                        'meta_title'=>['type'=>'textInput'],
                                        'meta_word'=>['type'=>'textInput'],
                                        'meta_desc'=>['type'=>'textArea','options'=>['rows'=>'3']]
                                    ]]);
                            ?>
                        </div>
                    </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>


<script>
    var style_ids=<?php echo json_encode($style_ids);?>;
    var style_arr=eval(style_ids);
    console.log(style_arr);
    getStyles(style_ids);

    /* 打一个新窗口 */
    $(document).on("click", ".openIframe1", function (e) {

        var tr_length = $("#style_table").children('tr').length;
        if(tr_length >1){
            layer.msg("商品已满两件，不可再添加");
            return false;
        }
        var title = $(this).data('title');
        var width = $(this).data('width');
        var height = $(this).data('height');
        var offset = $(this).data('offset');
        var href = $(this).attr('href');

        if (title == undefined) {
            title = '基本信息';
        }

        if (width == undefined) {
            width = '80%';
        }

        if (height == undefined) {
            height = '80%';
        }

        if (offset == undefined) {
            offset = "10%";
        }

        openIframe1(title, width, height, href, offset);
        e.preventDefault();
        return false;
    });
    // 打一个新窗口
    function openIframe1(title, width, height, content, offset) {
        layer.open({
            type: 2,
            title: title,
            shade: 0.3,
            offset: offset,
            shadeClose: true,
            btn: ['保存', '关闭'],
            yes: function (index, layero) {
                var body = layer.getChildFrame('body', index);
                var form = body.find('#w0');
                var postUrl = form.attr('action');
                console.log(postUrl);
                $.ajax({
                    type: "post",
                    url: postUrl,
                    dataType: "json",
                    data: form.serialize(),
                    success: function (data) {
                        if (parseInt(data.code) !== 200) {
                            rfMsg(data.message);
                        } else {
                            console.log(data.data.style_id);
                            getStyle(data.data.style_id);

                            layer.close(index);

                        }
                    }
                });
            },
            btn2: function () {
                layer.closeAll();
            },
            area: [width, height],
            content: content
        });

        return false;
    }

    function getStyles(style_ids) {
        for(var i = 0; i < style_ids.length; i++){
            getStyle(style_ids[i]);
        }

    }

    function getStyle(style_id) {
        $.ajax({
            type: "post",
            url: 'get-style',
            dataType: "json",
            data: {style_id:style_id},
            success: function (data) {
                if (parseInt(data.code) !== 200) {
                    rfMsg(data.message);
                } else {

                    console.log(data.data);
                    var data = data.data

                    var hav = true;
                    $("#style_table input[name='RingRelation[style_id][]]'").each(function(){
                        if($(this).val() == data.id){
                            hav = false;
                        }
                    });
                    if(hav == false){
                        layer.msg("此商品已经添加");
                        return false;
                    }

                    var tr = "<tr><input type='hidden' name='RingRelation[style_id][]' value='" + data.id + "'/>"
                        +"<td>" + data.style_name + "</td>"
                        +"<td>" + data.style_sn + "</td>"
                        +"<td>" + data.sale_price + "</td>"
                        +"<td>" + data.goods_storage + "</td>"
                        +'<td><a class="btn btn-danger btn-sm deltr" href="#" >删除</a></td>'
                        + "</tr>";
                    $("#style_table").append(tr);

                    $(document).on('click','.deltr',function(){
                        //当前元素的父级的父级的元素（一行，移除
                        $(this).parents("tr").remove();
                    })

                }
            }
        });

    }

</script>