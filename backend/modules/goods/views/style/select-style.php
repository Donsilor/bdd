<?php

use yii\grid\GridView;
use yii\widgets\ActiveForm;
use common\helpers\Html;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

//$this->title = Yii::t('goods', 'Styles');
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">

        <?php $form = ActiveForm::begin([]); ?>
        <div class="form-group field-ring-ring_style">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>适用人群</th>
                        <th>商品名称</th>
                        <th>款式编号</th>

                        <th>销售价</th>
                        <th>商品库存</th>

                        <th class="action-column">操作</th>
                    </tr>
                </thead>
                <tbody id="style_table">
                </tbody>
            </table>
        </div>
        <?php ActiveForm::end(); ?>

        <div class="box">
            <div class="box-body table-responsive">
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
                            'header' => '',
                            'class'=>yii\grid\CheckboxColumn::class,
                            'name'=>'id',  //设置每行数据的复选框属性
                            'headerOptions' => ['width'=>'30'],

                        ],
                        [
                            'attribute' => 'id',
                            'filter' => Html::activeTextInput($searchModel, 'id', [
                                'class' => 'hide',
                            ]),
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'50'],
                        ],
                        [
                            'attribute' => 'lang.style_name',
                            'value' => 'lang.style_name',
                            'filter' => Html::activeTextInput($searchModel, 'style_name', [
                                'class' => 'form-control',
                                'style' =>'width:100px'
                            ]),
                            'format' => 'raw',

                        ],
                        [
                            'label' => '适用人群',
                            'attribute' => 'lang.style_name',
                            'filter' => false,
                            'format' => 'raw',
                            'value' => function($mobile) {
                                static $values = [];

                                if(empty($values)) {
                                    $values = Yii::$app->services->goodsAttribute->getValuesByAttrId(26);
                                }

                                if(!is_array($mobile->style_attr))
                                $styleAttr = is_array($mobile->style_attr)?$mobile->style_attr:\Qiniu\json_decode($mobile->style_attr, true);
                                return $values[$styleAttr['26']]??'';
                            },
                        ],
                        [
                            'attribute' => 'style_sn',
                            'filter' => true,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'150'],
                        ],

                        [
                            'attribute' => 'type_id',
                            'value' => "type.type_name",
                            'filter' => true,
                            'format' => 'raw',
                            'headerOptions' => ['width'=>'100'],
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
                            'attribute' => 'sale_price',
                            'value' => "sale_price",
                            'filter' => false,
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'goods_storage',
                            'value' => "goods_storage",
                            'filter' => false,
                            'format' => 'raw',
                        ],


                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>


<script>
    $(function() {

        $(".content-header").hide();

        var $input = $("input[name='SearchModel[id]']");

        $.each($input.val().split('|'), function (i, v) {
            if(v!="") {
                showStyle(v);
            }
        });

        $('input[name="id[]"]').change(function() {
            // $("#style_id").val($(this).val());
            // alert($(this).val());
            if(($(this)[0]).checked) {
                addStyle($(this).eq(0).val());
            }
            else {
                delStyle($(this).val());
            }
        });

        function addStyle(style_id) {

            var hav = true;

            var styleIds = $input.val().split('|');

            $.each(styleIds, function(i, v) {
                if(v == style_id) {
                    hav = false;
                }
                if(v=="") {
                    styleIds.pop(i);
                }
            });

            if(hav === false) {
                //delStyle(style_id);
                layer.msg("此商品已经添加");
                return false;
            }

            if(styleIds.length > 1) {
                //delStyle(style_id);
                layer.msg("最多只能选两个商品");
                return false;
            }

            styleIds.push(style_id);
            $input.val(styleIds.join("|"));

            showStyle(style_id);
        }

        function showStyle(style_id) {
            $.ajax({
                type: "post",
                url: 'get-style',
                dataType: "json",
                data: {style_id:style_id},
                success: function (data) {
                    if (parseInt(data.code) !== 200) {
                        rfMsg(data.message);
                    } else {

                        var data = data.data

                        var tr = $("<tr>"
                            +"<td>" + data.attr_require + "</td>"
                            +"<td>" + data.style_name + "</td>"
                            +"<td>" + data.style_sn + "</td>"
                            +"<td>" + data.sale_price + "</td>"
                            +"<td>" + data.goods_storage + "</td>"
                            +'<td><a class="btn btn-danger btn-sm deltr" href="#" data-styleId="'+data.id+'">删除</a></td>'
                            + "</tr>");

                        tr.find(".deltr").click(function() {
                            delStyle($(this).attr("data-styleId"));
                        });

                        $("#style_table").append(tr);

                    }
                }
            });
        }

        function delStyle(style_id) {
            //取消数据保存
            var styleIds = $input.val().split('|');

            $.each(styleIds, function(i, v) {
                if(v == style_id) {
                    styleIds.pop(i);
                }
            });

            $input.val(styleIds.join("|"));

            //取消选中
            $('input[name="id[]"]:checked').each(function(i, va) {
                if($(this).val()==style_id) {
                    $(this).prop("checked", false);
                }
            });

            //删除显示
            $("#style_table").find("a[data-styleId='"+style_id+"']").parents("tr").remove();
        }
    });

</script>
