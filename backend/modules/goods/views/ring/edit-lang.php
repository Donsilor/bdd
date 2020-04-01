<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use common\helpers\Url;
use common\widgets\langbox\LangBox;


/* @var $this yii\web\View */
/* @var $model common\models\goods\Ring */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('goods_ring', 'Ring');
$this->params['breadcrumbs'][] = ['label' => Yii::t('goods_ring', 'Rings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?php $form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit-lang', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "{label}{input}{error}",

    ],


]); ?>
<div class="box-body nav-tabs-custom">
    <h2 class="page-header">对戒发布</h2>
    <?php $tab_list = [0=>'全部',1=>'基础信息',2=>'款式分类',3=>'图文信息',4=>'SEO优化'];?>
    <?php echo Html::tab($tab_list,0,'tab')?>
    <div class="tab-content">
        <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
            <ul class="nav nav-tabs pull-right">
                <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[1]??'' ?></li>
            </ul>
            <div class="box-body nav-tabs-custom col-lg-9" style="margin-left:9px">
                <?php echo Html::langTab('tab')?>
                <div class="tab-content">
                    <?php
                    echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab',
                        'fields'=>
                            [
                                'ring_name'=>['type'=>'textInput'],

                            ]]);
                    ?>
                    <div class="form-group field-ring-ring_style">
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

                    <?= $form->field($model, 'ring_sn')->textInput(['maxlength' => true])->label('对戒编码(女戒/男戒）') ?>
                    <div class="row">
                        <div class="col-lg-3"><?= $form->field($model, 'status')->radioList(\common\enums\FrameEnum::getMap()) ?></div>
                    </div>
                </div>
                <!-- ./box-body -->
            </div>
        </div>
            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_2">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[2]??'' ?></li>
                </ul>
                <div class="box-body" style="margin-left:10px">
                    <?= $form->field($model, 'ring_style')->widget(kartik\select2\Select2::class, [
                        'data' => common\enums\SeriesEnum::getMap(),
                        'options' => ['placeholder' => '请选择'],
                        'pluginOptions' => [
                            'allowClear' => true
                        ],

                    ]);?>

                    <div class="row">
                        <div class="col-lg-4">
                            <?= $form->field($model, 'cost_price')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-lg-4">
                            <?= $form->field($model, 'sale_price')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-lg-4">
                            <?= $form->field($model, 'market_price')->textInput(['maxlength' => true]) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            <?= $form->field($model, 'sale_volume')->textInput(['maxlength'=>true,'disabled'=>true]) ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'virtual_volume')->textInput(['maxlength'=>true]) ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'goods_clicks')->textInput(['maxlength'=>true,'disabled'=>true]) ?>
                        </div>
                        <div class="col-lg-3">
                            <?= $form->field($model, 'virtual_clicks')->textInput(['maxlength'=>true]) ?>
                        </div>
                    </div>


                </div>
                <!-- ./box-body -->
            </div>

            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_3">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[3]??'' ?></li>
                </ul>
                <div class="box-body col-lg-9">

                    <div class="row">
                        <div class="col-lg-4">
                            <?= $form->field($model, 'ring_3ds')->textInput(['maxlength' => true, 'id'=>'ds3']) ?>
                        </div>
                        <div class="col-lg-5">
                            <?= Html::button('预览',['class'=>'btn btn-info btn-sm','style'=>'margin-top:25px;','onclick'=>"view_3ds()"]) ?>
                        </div>

                    </div>

                    <?php $model->ring_images = !empty($model->ring_images)?explode(',', $model->ring_images):null;?>
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
                    <div class="row nav-tabs-custom">
        		        <?php echo Html::langTab("tab_body")?>    			      
            			<div class="tab-content " style="padding-left:10px"> 
            				<?php 
                    			echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab_body','fields'=>[
                    			        'ring_body'=>[
                    			            //'label'=>Yii::t("common","商品介绍"),
                			                'type'=>'widget',
                			                'class'=> \common\widgets\ueditor\UEditor::class,
                			                'options'=>[
                			                        'formData' => [
                			                                /* 'drive' => 'qiniu', // 默认本地 支持qiniu/oss/cos 上传

                			                                'poster' => false, // 上传视频时返回视频封面图，开启此选项需要安装 ffmpeg 命令
                			                                'thumb' => [
                			                                        [
            			                                                'width' => 800,
            			                                                'height' => 800,
                			                                        ]
                			                                ] */
                			                        ],
                			                ],//end options            			                                			                
                    			        ],//end goods_body
                    			]]);
                			?>
            			</div>                  
                    </div> <!-- ./nav-tabs-custom -->
                </div>
                <!-- ./box-body -->
            </div>
            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_4">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[4]??'' ?></li>
                </ul>
                <div class="box-body nav-tabs-custom none-shadow col-lg-9" style="margin-left:10px">
                    <?php echo Html::langTab("tab4")?>
                    <div class="tab-content">
                        <?php
                        echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab4',
                            'fields'=>
                                [
                                    'meta_title'=>['type'=>'textInput'],
                                    'meta_word'=>['type'=>'textInput'],
                                    'meta_desc'=>['type'=>'textArea','options'=>[]]
                                ]]);
                        ?>
                    </div>
                    <!-- ./tab-content -->
                </div>
                <!-- ./box-body -->
            </div>
            <!-- ./row -->


        <div class="modal-footer">
            <div class="col-sm-12 text-center">
                <button class="btn btn-primary" type="submit">保存</button>
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
        </div>
    </div>
</div>
    <?php ActiveForm::end(); ?>

    <script type="text/javascript">
        $(function(){
            $('form#Ring').on('submit', function (e) {
                var count = 0;
                var style_arr = [];
                $("input[name*='RingRelation[style_id][]']").each(function(){
                    if($(this).val != ''){
                        count += 1;
                    }
                    style_arr.push($(this).val());
                });

                $.unique(style_arr); //去重
                var leng = style_arr.length;
                if(leng != count){
                    rfError("商品添加有重复");
                    e.preventDefault();
                    return
                }

                if(count == 0){
                    $(".openIframe1").addClass('btn-danger');
                    $('body,html').animate({scrollTop: $('.openIframe1').offset().top}, 500);
                    rfError("请选添加商品");
                    e.preventDefault();
                    return
                }else if(count == 1){
                    $("#ring-status input[value=0]").attr('checked','true');
                    // appConfirm('只有一个商品，默认下架','ss', function (value) {
                    //     switch (value) {
                    //         case "defeat":
                    //             $('form#Ring').submit();
                    //             return true;
                    //             break;
                    //         default:
                    //     }
                    // });
                    $("form#Ring").data('yiiActiveForm').validated = true;
                    if (!confirm('只有一款商品，系统默认下架状态！')){
                        e.preventDefault();
                        return
                    }
                }

                $.ajax({
                    type: "post",
                    url: 'is-have',
                    dataType: "json",
                    async: false,
                    data: {style_ids:style_arr,ring_id:<?= $model->id?? 0 ?>},
                    success: function (data) {
                        if (parseInt(data.code) !== 200) {
                            rfMsg(data.message);
                        } else {
                            console.log(data.data.count);
                            if(data.data.count > 0){
                                rfError(data.data.strs + "已经被其他对戒添加");
                                e.preventDefault();
                                return
                            }

                        }
                    }
                });


            })
        });
    </script>
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

                        $("input[name*='RingRelation[style_id][]']").each(function(){
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