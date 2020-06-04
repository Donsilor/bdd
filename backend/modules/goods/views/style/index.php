<?php

use common\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use common\helpers\ImageHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
$goods_title = Yii::t('goods', $typeModel['type_name'].'商品列表');
$this->title = Yii::t('goods', $typeModel['type_name'].'管理');
$this->params['breadcrumbs'][] = $this->title;
$type_id = Yii::$app->request->get('type_id',0);
$params = Yii::$app->request->queryParams;
$params = $params ? "&".http_build_query($params) : '';
$export_param = http_build_query($searchModel);
?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="<?= Url::to(['style/index?type_id='.$type_id]) ?>"> <?= Html::encode($this->title) ?></a></li>
                <li><a href="<?= Url::to(['goods/index?type_id='.$type_id]) ?>"> <?= Html::encode($goods_title) ?></a></li>
                <li class="pull-right">
                    <div class="box-header box-tools">
                        <?= Html::a('导出Excel','index?action=export'.$params) ?>
                    </div>
                </li>
                <li class="pull-right">
                	<div class="box-header box-tools">
                        <?php if($type_id==19) { ?>
                            <a class="btn btn-primary btn-xs openIframe1" href="<?php echo Url::to(['select-style'])?>"><i class="icon ion-plus"></i>创建</a>
                        <?php } else { ?>
                            <?= Html::create(['edit-lang','type_id'=>$type_id]) ?>
                        <?php } ?>
                    </div>
                </li>

            </ul>
            <div class="box-body table-responsive">
    <?php echo Html::batchButtons(false)?>         
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-hover'],
        'showFooter' => true,//显示footer行
        'id'=>'grid',            
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false,
            ],
            [
                'class'=>'yii\grid\CheckboxColumn',
                'name'=>'id',  //设置每行数据的复选框属性
                'headerOptions' => ['width'=>'30'],
            ],
            [
                'attribute' => 'id',
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],            
            ],
            [
                'attribute' => 'lang.language',
                 'value' => function ($model) {
                    return \common\enums\LanguageEnum::getValue($model->lang->language);
                 },
                 'filter' => Html::activeDropDownList($searchModel, 'language',\common\enums\LanguageEnum::getMap(), [
                        'prompt' => '默认',
                        'class' => 'form-control',
                ]),
                'headerOptions' => ['width'=>'110'],
            ], 
            [
                'attribute' => 'style_image',
                'value' => function ($model) {
                    return ImageHelper::fancyBox($model->style_image);
                },
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],                
            ],
                
            [
                //'headerOptions' => ['width'=>'200'],
                'attribute' => 'lang.style_name',
                'value' => 'lang.style_name',
                'filter' => Html::activeTextInput($searchModel, 'style_name', [
                        'class' => 'form-control',
                ]),
                'format' => 'raw',                
            ],
            [
                'attribute' => 'style_sn',
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'120'],
            ],
            
            [
                    'attribute' => 'type_id',
                    'value' => "type.type_name",
                    'filter' => Html::activeDropDownList($searchModel, 'type_id',Yii::$app->services->goodsType->getGrpDropDown($type_id,0), [
                        'prompt' => '全部',
                        'class' => 'form-control',
                    ]),
                    'format' => 'raw',
                    'headerOptions' => ['width'=>'120'],
            ],       
            [
                'attribute' => 'sale_price',
                'value' => "sale_price",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'100'],
            ],
//            [
//                'attribute' => 'sale_volume',
//                'value' => "sale_volume",
//                'filter' => true,
//                'format' => 'raw',
//                'headerOptions' => ['width'=>'80'],
//            ],
            [
                'attribute' => 'goods_storage',
                'value' => "goods_storage",
                'filter' => true,
                'format' => 'raw',
                'headerOptions' => ['width'=>'80'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'col-md-1'],
                'value' => function ($model){
                    return \common\enums\FrameEnum::getValue($model->status);
                },
                'filter' => Html::activeDropDownList($searchModel, 'status',\common\enums\FrameEnum::getMap(), [
                    'prompt' => '全部',
                    'class' => 'form-control',                        
                ]),
            ],            
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{edit} {view} {status} {show_log}',
                'buttons' => [
                'edit' => function($url, $model, $key){
                    return Html::edit(['edit-lang','id' => $model->id,'type_id'=>Yii::$app->request->get('type_id'),'returnUrl' => Url::getReturnUrl()]);
                },
               'status' => function($url, $model, $key){
                        return Html::status($model['status']);
                },
                'delete' => function($url, $model, $key){
                        return Html::delete(['delete', 'id' => $model->id]);
                },
                'view'=> function($url, $model, $key){
                   if($model->type_id == 2){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/ring/wedding-rings/'.$model->id.'?goodId='.$model->id.'&ringType=single&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 12){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/ring/engagement-rings/'.$model->id.'?goodId='.$model->id.'&ringType=engagement&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 4){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/necklace/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 5){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/pendant/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 6){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/studEarring/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 7){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/earring/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 8){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/braceletLine/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }elseif ($model->type_id == 9){
                       return Html::a('预览', \Yii::$app->params['frontBaseUrl'].'/jewellery/bracelet/'.$model->id.'?goodId='.$model->id.'&backend=1',['class'=>'btn btn-info btn-sm','target'=>'_blank']);
                   }

                },
                'show_log' => function($url, $model, $key){
                    return Html::linkButton(['goods-log/index','id' => $model->id, 'type_id' => $model->type_id, 'returnUrl' => Url::getReturnUrl()], '日志');
                },
                ]
            ]
    ]
    ]); ?>
            </div>
        </div>
    </div>
</div>

<script>
    //var style_ids=<?php //echo json_encode($style_ids);?>//;
    // var style_arr=eval(style_ids);
    // console.log(style_arr);
    // getStyles(style_ids);


    /* 打一个新窗口 */
    $(document).on("click", ".openIframe1", function (e) {

        // var tr_length = $("#style_table").children('tr').length;
        // if(tr_length >1){
        //     layer.msg("商品已满两件，不可再添加");
        //     return false;
        // }
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
                // var body = layer.getChildFrame('body', index);
                // var form = body.find('#w0');
                // var postUrl = form.attr('action');
                console.log(postUrl);
                // $.ajax({
                //     type: "post",
                //     url: postUrl,
                //     dataType: "json",
                //     data: form.serialize(),
                //     success: function (data) {
                //         if (parseInt(data.code) !== 200) {
                //             rfMsg(data.message);
                //         } else {
                //             console.log(data.data.style_id);
                //             getStyle(data.data.style_id);
                //
                //             layer.close(index);
                //
                //         }
                //     }
                // });
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
        // for(var i = 0; i < style_ids.length; i++){
        //     getStyle(style_ids[i]);
        // }

    }

    function getStyle(style_id) {
        // $.ajax({
        //     type: "post",
        //     url: 'get-style',
        //     dataType: "json",
        //     data: {style_id:style_id},
        //     success: function (data) {
        //         if (parseInt(data.code) !== 200) {
        //             rfMsg(data.message);
        //         } else {
        //
        //             console.log(data.data);
        //             var data = data.data
        //
        //             var hav = true;
        //
        //             $("input[name*='RingRelation[style_id][]']").each(function(){
        //                 if($(this).val() == data.id){
        //                     hav = false;
        //                 }
        //             });
        //             if(hav == false){
        //                 layer.msg("此商品已经添加");
        //                 return false;
        //             }
        //
        //             var tr = "<tr><input type='hidden' name='RingRelation[style_id][]' value='" + data.id + "'/>"
        //                 +"<td>" + data.style_name + "</td>"
        //                 +"<td>" + data.style_sn + "</td>"
        //                 +"<td>" + data.sale_price + "</td>"
        //                 +"<td>" + data.goods_storage + "</td>"
        //                 +'<td><a class="btn btn-danger btn-sm deltr" href="#" >删除</a></td>'
        //                 + "</tr>";
        //             $("#style_table").append(tr);
        //
        //             $(document).on('click','.deltr',function(){
        //                 //当前元素的父级的父级的元素（一行，移除
        //                 $(this).parents("tr").remove();
        //             })
        //
        //         }
        //     }
        // });

    }



</script>
