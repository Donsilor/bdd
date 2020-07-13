<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\widgets\langbox\LangBox;
use yii\base\Widget;
use common\widgets\skutable\SkuTable;
use common\helpers\Url;
use common\enums\AreaEnum;
use common\helpers\AmountHelper;

/* @var $this yii\web\View */
/* @var $model common\models\goods\Style */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('goods', '裸钻发布');
$this->params['breadcrumbs'][] = ['label' => Yii::t('goods', 'Styles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//
?>
<?php $form = ActiveForm::begin([
         'id' => $model->formName(),
        'enableAjaxValidation' => true,
        'validationUrl' => Url::to(['ajax-edit-lang', 'id' => $model['id']]),
        'fieldConfig' => [
            'template' => "{label}{input}{hint}",

        ],
]); ?>
<style type="text/css">
    .content-header .rfHeaderFont:nth-child(2) {
        display: none;
    }
</style>
<div class="box-body nav-tabs-custom">
     <h2 class="page-header"><?php echo Yii::t('goods', '裸钻发布');?></h2>
      <?php $tab_list = [0=>'全部',1=>'基础信息',2=>'商品属性',3=>'图文信息',4=>'SEO优化',5=>'地区价格'];?>
     <?php echo Html::tab($tab_list,0,'tab')?>
     <div class="tab-content">     
       <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[1]??''?></li>
            </ul>
            <div class="box-body col-lg-9" style="margin-left:9px">
                <div class="row">
                    <div class="col-lg-4">
                        <?= $form->field($model, 'cert_type')->dropDownList(\common\enums\DiamondEnum::getCertTypeList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'cert_id')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'goods_sn')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
                <div class="row">

                    <div class="col-lg-4">
                        <?= $form->field($model, 'goods_num')->textInput() ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'cost_price')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'sale_price')->textInput(['maxlength' => true]) ?>
                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <?= $form->field($model, 'market_price')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'sale_volume')->textInput(['maxlength'=>true,'disabled'=>true]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'virtual_volume')->textInput(['maxlength'=>true]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <?= $form->field($model, 'goods_clicks')->textInput(['maxlength'=>true,'disabled'=>true]) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= $form->field($model, 'virtual_clicks')->textInput(['maxlength'=>true]) ?>
                    </div>
                </div>


                <div class="row">
                    <div class="col-lg-3"><?= $form->field($model, 'status')->radioList(\common\enums\FrameEnum::getMap()) ?></div>
                    <div class="col-lg-3"><?= $form->field($model, 'is_stock')->radioList(\common\enums\IsStockEnum::getMap()) ?></div>
                </div>
                <div class="nav-tabs-custom ">
                    <?php echo Html::langTab("tab1")?>
                    <div class="tab-content" style="padding-left:10px">
                        <?php
                        echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab1','fields'=>[
                            'goods_name'=>['type'=>'textInput','options'=>['maxlength' => true]],
                            'goods_desc'=>['type'=>'textArea','options'=>['maxlength' => true]],

                        ]]);
                        ?>
                    </div>
                </div>
                <?php $model->sale_services = !empty($model->sale_services)?explode(',', $model->sale_services):null;?>
                <?= $form->field($model, 'sale_services')->checkboxList(common\enums\DiamondEnum::getSaleServicesList()) ?>

    		    <!-- ./nav-tabs-custom -->
            </div>
        <!-- ./box-body -->
      </div>            
      <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_2">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[2]??''?></li>
            </ul>
          <div class="box-body" style="margin-left:10px">
              <div class="row">
                  <div class="col-lg-4">
                      <?= $form->field($model, 'carat')->textInput()->hint('ct',['tag'=>'span','class'=>'unit']) ?>
                  </div>
                  <div class="col-lg-4">
                      <?= $form->field($model, 'shape')->dropDownList(\common\enums\DiamondEnum::getShapeList()) ?>
                  </div>

                  <div class="col-lg-4">
                      <?= $form->field($model, 'color')->dropDownList(\common\enums\DiamondEnum::getColorList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>
                  
              </div>
              <div class="row">
                  <div class="col-lg-4">
                      <?= $form->field($model, 'clarity')->dropDownList(\common\enums\DiamondEnum::getClarityList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>

                  <div class="col-lg-4">
                      <?= $form->field($model, 'cut')->dropDownList(\common\enums\DiamondEnum::getCutList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>

                  <div class="col-lg-4">
                      <?= $form->field($model, 'polish')->dropDownList(\common\enums\DiamondEnum::getPolishList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>


              </div>
              <div class="row">
                  <div class="col-lg-4">
                      <?= $form->field($model, 'symmetry')->dropDownList(\common\enums\DiamondEnum::getSymmetryList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>
                  <div class="col-lg-4">
                      <?= $form->field($model, 'fluorescence')->dropDownList(\common\enums\DiamondEnum::getFluorescenceList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div> 
                  <div class="col-lg-4">
                      <?= $form->field($model, 'stone_floor')->dropDownList(\common\enums\DiamondEnum::getStoneFloorList(),['prompt'=>Yii::t("common",'请选择')]) ?>
                  </div>                 

              </div>

              <div class="row">
              	  <div class="col-lg-4">
                      <?= $form->field($model, 'depth_lv')->textInput()->hint('%',['tag'=>'span','class'=>'unit']) ?>
                  </div>
			      <div class="col-lg-4">
                      <?= $form->field($model, 'table_lv')->textInput()->hint('%',['tag'=>'span','class'=>'unit']) ?>
                  </div>
                  <div class="col-lg-4">
                      <?= $form->field($model, 'aspect_ratio')->textInput()->hint('%',['tag'=>'span','class'=>'unit']) ?>
                  </div>
              </div>
              <div class="row">
                  <div class="col-lg-4">
                      <?= $form->field($model, 'length')->textInput(['maxlength' => true])->hint('mm',['tag'=>'span','class'=>'unit']) ?>
                  </div>
                  <div class="col-lg-4">
                      <?= $form->field($model, 'width')->textInput(['maxlength' => true])->hint('mm',['tag'=>'span','class'=>'unit']) ?>
                  </div>

              </div>



          </div>
      	 <!-- ./box-body -->          
      </div>    
    
      <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_3">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[3]??''?></li>
            </ul>
            <div class="box-body col-lg-9">
            <div class="row">
                <div class="col-lg-5">
                    <?= $form->field($model, 'goods_3ds')->textInput(['maxlength' => true, 'id'=>'ds3']) ?>
                </div>
                <div class="col-lg-5">
                    <?= Html::button('预览',['class'=>'btn btn-info btn-sm','style'=>'margin-top:25px;','onclick'=>"view_3ds()"]) ?>
                </div>

            </div>
              <?= $form->field($model, 'goods_image')->widget(common\widgets\webuploader\Files::class, [
                    'config' => [
                        'pick' => [
                            'multiple' => false,
                        ],

                    ]
                ]); ?>

              <?php $model->parame_images = !empty($model->parame_images)?explode(',', $model->parame_images):null;?>
              <?= $form->field($model, 'parame_images')->widget(common\widgets\webuploader\Files::class, [
                  'config' => [
                      'pick' => [
                          'multiple' => true,
                      ],
                      'formData' => [
                          //'drive' => 'local',// 默认本地 支持 qiniu/oss 上传
                      ],
                  ]
              ]); ?>
            <?= $form->field($model, 'goods_gia_image')->widget(common\widgets\webuploader\Files::class, [
                'type' => 'files',
                'config' => [
                    'pick' => [
                        'multiple' => false,
                    ],
                    'formData' => [
//                        'drive' => 'local',// 默认本地 支持 qiniu/oss 上传
                    ],
                ]
            ]); ?>
			
 			<div class="row nav-tabs-custom">
    		        <?php echo Html::langTab("tab_body")?>    			      
        			<div class="tab-content " style="padding-left:10px"> 
    				 <?php 
            			echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab_body','fields'=>[
            			        'goods_body'=>[
            			            //'label'=>Yii::t("common","商品介绍"),
        			                'type'=>'widget',
        			                'class'=> \common\widgets\ueditor\UEditor::class,
        			                'options'=>[
        			                        'formData' => [
        			                                /* //'drive' => 'qiniu', // 默认本地 支持qiniu/oss/cos 上传
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
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[4]??''?></li>
            </ul>
            <div class="box-body nav-tabs-custom none-shadow col-lg-9" style="margin-left:10px">
                 <?php echo Html::langTab("tab4")?>           
        		  <div class="tab-content">            
                    <?php 
                    echo common\widgets\langbox\LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab4',
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
      <?php if(AreaEnum::isMarkupRate())  {?>  
    <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_5">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[5]??'';?></li>
            </ul>
            <div class="box-body nav-tabs-custom none-shadow" style="margin-left:10px">
         
        		  <div class="tab-content">            
                    <?php 
                    $sale_policy = json_decode($model->sale_policy,true) ??[]; 
                    $areaValues = []; 
                    foreach (AreaEnum::getMap() as $area_id=>$area_name) {                        
                        $area = $sale_policy[$area_id] ?? [];
                        $markup_rate  = isset($area['markup_rate']) ? abs($area['markup_rate']):1;
                        $markup_value =  isset($area['markup_value']) ? $area['markup_value']:0;
                        $sale_price = AmountHelper::calcMarkupPrice($model->sale_price,$markup_rate,$markup_value,2);
                        $areaValues[$area_id] = [
                                'area_id' =>$area_id,
                                'area_name'=>$area_name,
                                'sale_price'=>$sale_price,
                                'markup_rate' => $markup_rate,
                                'markup_value'=> $markup_value,
                                'status'=> isset($area['status']) ? $area['status']:1,
                        ]; 
                    }    
                    $areaColomns = [
                            [
                                    'name' => 'area_id',
                                    'title'=>"地区ID",
                                    'enableError'=>false,
                                    'options' => [
                                            'class' => 'input-priority',
                                            'readonly' =>'true',
                                    ]
                            ],
                            [
                                    'name' =>'area_name',
                                    'title'=>"地区",
                                    'enableError'=>false,
                                    'options' => [
                                            'class' => 'input-priority',
                                            'readonly' =>'true',
                                    ]
                            ],
                            [
                                    'name' => "sale_price",
                                    'title'=>"加价销售价",
                                    'enableError'=>false,
                                    'options' => [
                                            'class' => 'input-priority',
                                            'readonly' =>'true',
                                    ]
                            ],
                            [
                                    'name' => "markup_rate",
                                    'title'=>"加价率",
                                    'enableError'=>false,
                                    'options' => [
                                            'class' => 'input-priority'
                                    ]
                            ],
                            [
                                    'name' => "markup_value",
                                    'title'=>"固定值",
                                    'enableError'=>false,
                                    'options' => [
                                            'class' => 'input-priority'
                                    ]
                            ],
                            [
                                    'name' => "status",
                                    'title'=>"状态",
                                    'enableError'=>false,
                                    'type'  => 'dropDownList',
                                    'options' => [
                                            'class' => 'input-priority'
                                    ],
                                    'defaultValue' => 1,
                                    'items' => common\enums\StatusEnum::getMap()
                            ],
                    ];
                    ?>
                    <?= unclead\multipleinput\MultipleInput::widget([                            
                            'name' => "Diamond[sale_policy]",
                            'removeButtonOptions'=>['label'=>'','class'=>''], 
                            'value' => $areaValues,
                            'columns' => $areaColomns
                    ]) ?>
            	  </div>  
    		    <!-- ./tab-content -->
            </div>
            <!-- ./box-body -->
            <?php }?>
      </div>
      <!-- ./row -->
    </div>
    <div class="modal-footer">
        <div class="col-sm-12 text-center">
            <button class="btn btn-primary" type="submit">保存</button>
            <span class="btn btn-white" onclick="$('.active.J_menuTab i', window.parent.document).click()">关闭</span>
        </div>
	</div>
</div>

<?php ActiveForm::end(); ?>

<script>
    //裸钻编号根据证书号获取
    $('input[name="Diamond[cert_id]"]').on('change',function (){
        $('input[name="Diamond[goods_sn]"]').val('DSN' + $(this).val());
    });


    // 商品名称根据石重、形状、颜色、净度、证书类型 设置
    $('input[name="Diamond[carat]"]').on('change',function (){
        setGoodsName();
    });

    $('select[name="Diamond[cert_type]"]').on('change',function (){
        setGoodsName();
    });
    $('select[name="Diamond[shape]"]').on('change',function (){
        setGoodsName();
    });
    $('select[name="Diamond[color]"]').on('change',function (){
        setGoodsName();
    });
    $('select[name="Diamond[clarity]"]').on('change',function (){
        setGoodsName();
    });

    function setGoodsName(){
        var carat = $('input[name="Diamond[carat]"]').val();
        var cert_type = $('select[name="Diamond[cert_type]"]').children('option:selected').val();
        var shape = $('select[name="Diamond[shape]"]').children('option:selected').val();
        var color = $('select[name="Diamond[color]"]').children('option:selected').val();
        var clarity = $('select[name="Diamond[clarity]"]').children('option:selected').val();
        var param_data = {carat:carat,cert_type:cert_type,shape:shape,color:color,clarity:clarity}

        $.ajax({
            type: "post",
            url: 'get-goods-name',
            dataType: "json",
            data: param_data,
            success: function (data) {
                if (parseInt(data.code) !== 200) {
                    // rfMsg(data.message);
                } else {
                    console.log(data.data);
                    $.each(data.data, function (key,value) {
                        $('input[name="DiamondLang['+ key +'][goods_name]"]').val(value);
                    })

                }
            }
        });
    }

    $('input[type="text"],textarea').change(function () {
        $(this).val($(this).val().trim());
    });

    $(function() {
        <?php if($model->goods_sn) { ?>
        $('.active.J_menuTab span', window.parent.document).text('<?= $model->goods_sn ?>');
        <?php } ?>
    });

</script>

