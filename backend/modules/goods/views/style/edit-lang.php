<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\widgets\langbox\LangBox;
use yii\base\Widget;
use common\widgets\skutable\SkuTable;
use common\helpers\Url;
use common\helpers\ArrayHelper;
use common\enums\StatusEnum;
use common\helpers\AmountHelper;
use common\enums\AreaEnum;
use common\models\goods\Goods;

/* @var $this yii\web\View */
/* @var $model common\models\goods\Style */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('goods', 'Style');
$this->params['breadcrumbs'][] = ['label' => Yii::t('goods', 'Styles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$model->style_attr = $model->style_attr?json_decode($model->style_attr,true):[];
$model->style_spec = $model->style_spec?json_decode($model->style_spec,true):[];
//查询goods表数据，覆盖 style_spec['c']
if(!empty($model->style_spec['c'])) {
    $style_spec = $model->style_spec;
    foreach ($style_spec['c'] as $spec_key => $spec_val){
        $goods = Goods::find()->select(['id','goods_sn','sale_price','cost_price','market_price','goods_storage','status'])->where(['spec_key'=>$spec_key,'style_id'=>$model->id])->one();
        if($goods) {
            $spec_val['goods_id'] = $goods->id??'';
            $spec_val['goods_sn'] = $goods->goods_sn??'';
            $spec_val['sale_price'] = $goods->sale_price??'';
            $spec_val['cost_price'] = $goods->cost_price??'';
            $spec_val['market_price'] = $goods->market_price??'';
            $spec_val['goods_storage'] = $goods->goods_storage??'';
            $spec_val['status'] = $goods->status;
            $style_spec['c'][$spec_key] = $spec_val;
        }
    }
}else {    
    $style_spec['c'] = [];
}
$model->style_spec = $style_spec;
//
?>
<?php $form = ActiveForm::begin([
        'id' => $model->formName(),
        'enableAjaxValidation' => true,
        'validationUrl' => Url::to(['ajax-edit-lang', 'id' => $model['id']]),       
]); ?>
<style type="text/css">
    .content-header .rfHeaderFont:nth-child(2) {
        display: none;
    }
</style>
<div class="box-body nav-tabs-custom">
     <h2 class="page-header">商品发布</h2>
     <?php 
     $tab_list = [0=>'全部',1=>'基础信息',2=>'商品属性',3=>'图文信息',4=>'SEO优化'];
     if(AreaEnum::isMarkupRate()) {
         $tab_list[5] = '地区价格';
     }
     ?>
     <?php echo Html::tab($tab_list,0,'tab')?>
     <div class="tab-content">     
       <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[1]??'';?></li>
            </ul>
            <div class="box-body" style="margin-left:9px">
                <?php 
                $type_id = Yii::$app->request->get("type_id");
                $_type_id = Yii::$app->request->get("_type_id",$type_id);
                $model->type_id = $model->type_id?? $_type_id;
                ?> 
                 <div class="row">
                 <div class="col-lg-4">         
        			<?= $form->field($model, 'type_id')->dropDownList(\Yii::$app->services->goodsType->getGrpDropDown($type_id),[
        			        'prompt' => '请选择',
        			        'onchange'=>"location.href='?_type_id='+this.value+'&type_id={$type_id}'",
        			        'disabled'=>$model->isNewRecord?null:'disabled',
        			]) ?> 
    			</div>
    			<div class="col-lg-4"><?= $form->field($model, 'style_sn')->textInput(['maxlength'=>true]) ?></div>
                </div>                
                <div class="row">
                    <div class="col-lg-4"><?= $form->field($model, 'sale_volume')->textInput(['maxlength'=>true,'disabled'=>true]) ?></div>
                    <div class="col-lg-4"><?= $form->field($model, 'virtual_volume')->textInput(['maxlength'=>true]) ?></div>
                </div>
                <div class="row">
                    <div class="col-lg-4"><?= $form->field($model, 'goods_clicks')->textInput(['maxlength'=>true,'disabled'=>true]) ?></div>
                    <div class="col-lg-4"><?= $form->field($model, 'virtual_clicks')->textInput(['maxlength'=>true]) ?></div>
                </div>                                  
    			<div class="nav-tabs-custom ">
    		        <?php echo Html::langTab("tab1")?>    			      
        			<div class="tab-content" style="padding-left:10px"> 
        				<?php 
                			echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab1','fields'=>[
                			        'style_name'=>['type'=>'textInput','options'=>['maxlength' => true],'label'=>Yii::t("common","商品名称")],
                			        'style_desc'=>['type'=>'textArea','options'=>['maxlength' => true],'label'=>Yii::t("common","商品描述")]
                			]]);
            			?>
        			</div>
    		    </div>
                <div class="row">
                    <div class="col-lg-3"><?= $form->field($model, 'status')->radioList(\common\enums\FrameEnum::getMap()) ?></div>
                </div>

    		    <!-- ./nav-tabs-custom -->
            </div>
        <!-- ./box-body -->
      </div>            
      <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_2">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[2]??'';?></li>
            </ul>
            <div class="box-body col-lg-12">
               <?php               
                $attr_list_all = \Yii::$app->services->goodsAttribute->getAttrListByTypeId($model->type_id);
                $type_sale = common\enums\AttrTypeEnum::TYPE_SALE;
                if(!isset($attr_list_all[$type_sale])){
                    $attr_list_all[$type_sale] = [];
                }
                foreach ($attr_list_all as $attr_type=>$attr_list){
                    ?>
                    <div class="box-header with-border">
                    	<h3 class="box-title"><?= common\enums\AttrTypeEnum::getValue($attr_type)?></h3>
                	</div>
                    <div class="box-body" style="margin-left:10px">
                      <?php
                      //如果是销售属性
                      if($attr_type == common\enums\AttrTypeEnum::TYPE_SALE){
                          ?>
                            <div class="row">
                                <div class="col-lg-4"><?= $form->field($model, 'sale_price')->textInput(['maxlength'=>true]) ?></div>
                                <div class="col-lg-4"><?=  $form->field($model, 'cost_price')->textInput(['maxlength'=>true]) ?></div>
                                <div class="col-lg-4"><?= $form->field($model, 'market_price')->textInput(['maxlength'=>true]) ?></div>
                            </div> 
   							<div class="row">
   							    <div class="col-lg-4"><?=  $form->field($model, 'goods_storage')->textInput(['maxlength'=>true]) ?></div>
   							    
                            </div> 
                          <?php 
                          $data = [];

                          $INPUT_STYLE_GOODS_LIST = false;
                          $styles = [];
                          foreach ($attr_list as $k=>$attr){
                              if($attr['input_type']==\common\enums\InputTypeEnum::INPUT_STYLE_GOODS_LIST) {
                                  $INPUT_STYLE_GOODS_LIST = true;

                                  $goodsIds = $model->style_spec['a'][$attr['id']]??[];

                                  $goodsInfo = Goods::findOne($goodsIds[0]??null);

                                  $values = [];
                                  $styleInfo = Yii::$app->services->goods->formatStyleGoodsById($goodsInfo['style_id']??array_pop($attrStyleIds));

                                  $styles[] = $styleInfo['id'];

                                  $attr_require = null;
                                  foreach($styleInfo['specs'] as $spec) {
                                      if($spec['configId']==26) {
                                          $attr_require = $spec['configAttrVal'];
                                      }
                                  }

                                  $sizes = [];
                                  if(!empty($styleInfo['sizes']) && is_array($styleInfo['sizes'])) {
                                      foreach ($styleInfo['sizes'] as $size) {
                                          $sizes[$size['id']] = $size['name'];
                                      }
                                  }

                                  $materials = [];
                                  if(!empty($styleInfo['materials']) && is_array($styleInfo['materials'])) {
                                      foreach ($styleInfo['materials'] as $material) {
                                          $materials[$material['id']] = $material['name'];
                                      }
                                  }

                                  $carats = [];
                                  if(!empty($styleInfo['carats']) && is_array($styleInfo['carats'])) {
                                      foreach($styleInfo['carats'] as $carat) {
                                          $carats[$carat['id']] = $carat['name'];
                                      }
                                  }

                                  foreach ($styleInfo['details'] as $detail) {
                                      $goodsDetailsCode = $detail['goodsDetailsCode'] . '(' . ($materials[$detail['material']]??'') . '，' . ($sizes[$detail['size']]??'') . '，' . ($carats[$detail['carat']]??'') . ')';
                                      $values[$detail['id']] = $goodsDetailsCode;
                                  }

                                  $data[] = [
                                      'id'=>$attr['id'],
                                      'name'=>$attr['attr_name'] . '.' . $attr_require,
                                      'value'=>$values,
                                      'current'=>$model->style_spec['a'][$attr['id']]??[]
                                  ];
                              }
                              else {
                                  $values = Yii::$app->services->goodsAttribute->getValuesByAttrId($attr['id']);
                                  $data[] = [
                                      'id'=>$attr['id'],
                                      'name'=>$attr['attr_name'],
                                      'value'=>$values,
                                      'current'=>$model->style_spec['a'][$attr['id']]??[]
                                  ];
                              }
                          }

                          if($INPUT_STYLE_GOODS_LIST) {

                          ?>
<table class="table table-hover" style="margin-bottom: 18px;">
    <thead>
        <tr>
            <th>适用人群</th>
            <th>商品名称</th>
            <th>款式编号</th>

            <th>销售价</th>
            <th>商品库存</th>

            <th class="action-column"></th>
        </tr>
    </thead>
    <tbody id="style_table">
    </tbody>
</table>
<script>
    function getStyle(style_id) {
        $.ajax({
            type: "post",
            url: 'get-style',
            dataType: "json",
            data: {style_id: style_id},
            success: function (data) {
                if (parseInt(data.code) !== 200) {
                    rfMsg(data.message);
                } else {

                    var data = data.data

                    var hav = true;

                    $("input[name*='RingRelation[style_id][]']").each(function () {
                        if ($(this).val() == data.id) {
                            hav = false;
                        }
                    });
                    if (hav == false) {
                        layer.msg("此商品已经添加");
                        return false;
                    }

                    var tr = "<tr><input type='hidden' name='RingRelation[style_id][]' value='" + data.id + "'/>"
                        +"<td>" + data.attr_require + "</td>"
                        + "<td>" + data.style_name + "</td>"
                        + "<td>" + data.style_sn + "</td>"
                        + "<td>" + data.sale_price + "</td>"
                        + "<td>" + data.goods_storage + "</td>"
                        + '<td></td>'
                        + "</tr>";
                    $("#style_table").append(tr);

                }
            }
        });
    }

    $(function () {
        getStyle(<?= $styles[0] ?>);
        getStyle(<?= $styles[1] ?>);
    });
</script>
                          <?php

                          }

                          if(!empty($data)){
                             echo common\widgets\skutable\SkuTable::widget(['form' => $form,'model' => $model,'data' =>$data,'name'=>'Style[style_spec]']);
                             ?>
                             <script type="text/javascript">
                                 $(function(){  
                                  	$('form#Style').on('submit', function (e) {
                                		var r = checkSkuInputData();
                                    	if(!r){
                                        	e.preventDefault();
                                    	}
                                    });
                                 });
                             </script>
                             <?php 
                          }
                      }else{                              
                              foreach ($attr_list as $k=>$attr){ 
                                  $attr_field = $attr['is_require']==1?'attr_require':'attr_custom';                                  
                                  $attr_field_name = "{$attr_field}[{$attr['id']}]";                                  
                                  $model->{$attr_field} = $model->style_attr;//$style_attr[$attr['id']]??'';
                                  //通用属性值列表
                                  $attr_values = Yii::$app->services->goodsAttribute->getValuesByAttrId($attr['id']);                                  
                                  switch ($attr['input_type']){
                                      case common\enums\InputTypeEnum::INPUT_TEXT :{
                                          $input = $form->field($model,$attr_field_name)->textInput()->label($attr['attr_name']);
                                          break;
                                      }
                                      case common\enums\InputTypeEnum::INPUT_RADIO :{
                                          $input = $form->field($model,$attr_field_name)->radioList($attr_values)->label($attr['attr_name']);
                                          break;
                                      }
                                      case common\enums\InputTypeEnum::INPUT_MUlTI :{
                                          $input = $form->field($model,$attr_field_name)->checkboxList($attr_values)->label($attr['attr_name']);
                                          break;
                                      }
                                      default:{
                                          $input = $form->field($model,$attr_field_name)->dropDownList($attr_values,['prompt'=>'请选择'])->label($attr['attr_name']);
                                          break;
                                      }
                                  }//end switch
                      ?>
                           <?php 
                           $collLg = 4;
                           /* if($attr_type == common\enums\AttrTypeEnum::TYPE_SERVER){
                                $collLg = 12;
                           } */?>
                              <?php if ($k % 3 ==0){ ?><div class="row"><?php }?>
    							<div class="col-lg-<?=$collLg?>"><?= $input ?></div>
                              <?php if(($k+1) % 3 == 0 || ($k+1) == count($attr_list)){?></div><?php }?>
                      <?php 
                              }//end foreach $attr_list
                              $show_storage = empty($attr_list)?true:false; 
                       }?>
                    </div>
                    <!-- ./box-body -->
                    <?php 
                }//end foreach $attr_list_all
                ?>  
           </div>  
      	 <!-- ./box-body -->          
      </div>    
    
      <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_3">
            <ul class="nav nav-tabs pull-right">
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[3]??'';?></li>
            </ul>
            <div class="box-body">
            	<div class="row col-lg-9">
                  <?php $model->goods_images = !empty($model->goods_images)?explode(',', $model->goods_images):null;?>      
                  <?= $form->field($model, 'goods_images')->widget(common\widgets\webuploader\Files::class, [
                        'config' => [
                            'pick' => [
                                'multiple' => true,
                            ],
                            /* 'formData' => [
                                    'drive' => 'oss',// 默认本地 支持 qiniu/oss 上传
                                    'thumb' => [
                                            [
                                                    'width' => 800,
                                                    'height' => 800,
                                            ]
                                    ]
                            ], */
                        ]
                  ]); ?>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <?= $form->field($model, 'style_3ds')->textInput(['maxlength' => true, 'id'=>'ds3']) ?>
                    </div>
                    <div class="col-lg-4">
                        <?= Html::button('预览',['class'=>'btn btn-info btn-sm','style'=>'margin-top:25px;','onclick'=>"view_3ds()"]) ?>
                    </div>

                </div>
                <div class="row nav-tabs-custom col-lg-10">
    		        <?php echo Html::langTab("tab_body")?>    			      
        			<div class="tab-content" style="padding-left:10px"> 
        				<?php 
                			echo LangBox::widget(['form'=>$form,'model'=>$model,'tab'=>'tab_body','fields'=>[
                			        'goods_body'=>[
                			            //'label'=>Yii::t("common","商品介绍"),
            			                'type'=>'widget',
            			                'class'=> \common\widgets\ueditor\UEditor::class,
            			                'options'=>[
            			                         /* 'formData' => [
            			                                //'drive' => 'qiniu', // 默认本地 支持qiniu/oss/cos 上传
            			                                'poster' => false, // 上传视频时返回视频封面图，开启此选项需要安装 ffmpeg 命令
            			                                'thumb' => [
            			                                        [
        			                                                'width' => 800,
        			                                                'height' => 800,
            			                                        ]
            			                                ]
            			                        ],  */
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
              <li class="pull-left header"><i class="fa fa-th"></i> <?= $tab_list[4]??'';?></li>
            </ul>
            <div class="box-body nav-tabs-custom none-shadow" style="margin-left:10px">
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
                  <div class="box-header with-border">
                    	<h3 class="box-title">款式地区价格</h3>
                  </div>
        		  <div class="tab-content">            
                    <?php 
                    $styleAreaColomns = [
                        [
                            'name' => 'area_id',
                            'title'=>"地区ID",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:60px'
                            ]
                        ],
                        [
                            'name' =>'area_name',
                            'title'=>"地区",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:80px'
                            ]
                        ],                        
                        [
                            'name' => "sale_price",
                            'title'=>"地区销售价",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                            ]
                        ],
                        [
                            'name' => "base_price",
                            'title'=>"基础销售价",
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
                            'name' => "is_onsale",
                            'title'=>"地区状态",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                            ]
                        ],
                        [
                            'name' => "status",
                            'title'=>"状态",
                            'enableError'=>false,
                            'type'  => 'dropDownList',
                            'options' => [
                                'class' => 'input-priority',
                                'style'=>'width:80px'
                            ],
                            'defaultValue' => StatusEnum::ENABLED,
                            'items' => common\enums\StatusEnum::getMap()
                        ],
                    ];
                    $styleSalepolicy = json_decode($model->style_salepolicy,true) ??[]; 
                    $styleAreaValues = []; 
                    foreach (AreaEnum::getMap() as $area_id=>$area_name) {                        
                        $area = $styleSalepolicy[$area_id] ?? [];
                        $markup_rate  = isset($area['markup_rate']) ? abs($area['markup_rate']):1;
                        $markup_value =  isset($area['markup_value']) ? $area['markup_value']:0;
                        $base_price = $model->sale_price;
                        $sale_price = AmountHelper::calcMarkupPrice($model->sale_price,$markup_rate,$markup_value,2);
                        $styleAreaValues[$area_id] = [
                                'area_id' =>$area_id,
                                'area_name'=>$area_name,                                
                                'sale_price'=>$sale_price,
                                'base_price'=>$base_price,
                                'markup_rate' => $markup_rate,
                                'markup_value'=> $markup_value,
                                'is_onsale'=>!isset($area['status']) || $area['status']==1 ? "上架":"下架",
                                'status'=> isset($area['status']) ? $area['status']:1,
                        ]; 
                    }
                    ?>                    
                    <?= unclead\multipleinput\MultipleInput::widget([                            
                            'name' => "Style[style_salepolicy]",
                            'removeButtonOptions'=>['label'=>'','class'=>''], 
                            'value' => $styleAreaValues,
                            'columns' => $styleAreaColomns
                    ]) ?>
               </div>
               <!-- ./tab-content 款号-->
                
                <?php 
                if($model->id && !empty($model->style_spec['c'])) {
                    
                    $goodsAreaColomns = [
                        [
                            'name' => 'area_id',
                            'title'=>"地区ID",
                            'enableError'=>false,
                            //'type'  => 'checkBox',
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:60px'
                            ]
                        ],
                        [
                            'name' =>'area_name',
                            'title'=>"地区",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:70px'
                            ]
                        ],
                        [
                            'name' => "goods_sn",
                            'title'=>"商品编码",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:200px'
                             ]
                        ],                        
                        [
                            'name' => "sale_price",
                            'title'=>"地区销售价",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                            ]
                        ],
                        [
                            'name' => "base_price",
                            'title'=>"基础销售价",
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
                                'class' => 'input-priority',
                                'readonly' =>'true',
                            ]
                        ],
                        [
                            'name' => "markup_value",
                            'title'=>"固定值",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                            ]
                        ],
                        [
                            'name' => "is_onsale",
                            'title'=>"地区状态",
                            'enableError'=>false,
                            'options' => [
                                'class' => 'input-priority',
                                'readonly' =>'true',
                                'style'=>'width:80px'
                            ]
                        ],
                        [
                            'name' => "status",
                            'title'=>"状态",
                            'enableError'=>false,
                            'type'  => 'dropDownList',
                            'options' => [
                                'class' => 'input-priority',
                                'style'=>'width:80px'
                            ],
                            'defaultValue' => StatusEnum::ENABLED,
                            'items' => common\enums\StatusEnum::getMap()
                        ],
                    ];
                    $goods_salepolicy = json_decode($model->goods_salepolicy,true) ?? [];
                ?>
                <div class="box-header with-border">
                	<h3 class="box-title">商品地区价格</h3>
                </div> 
                <div class="tab-content">
				<div class="nav-tabs-custom ">
    		        <?php echo Html::areaTab("areaTab1")?>    			      
        			<div class="tab-content" style="padding-left:10px"> 
        				 <?php 
        				 foreach ($styleAreaValues as $area_id =>$styleArea){
        				     $goodsAreaValues = [];
        				     foreach ($model->style_spec['c'] as $goods){
        				         if($goods['status'] != StatusEnum::ENABLED || !isset($goods['goods_id'])){
        				            continue;   
        				         }
        				         $goods_id = $goods['goods_id'];
        				         if(!empty($goods_salepolicy[$area_id][$goods_id])) {
        				             $goodsArea = $goods_salepolicy[$area_id][$goods_id];
        				             $markup_rate  = isset($styleArea['markup_rate']) ? $styleArea['markup_rate']:1;
        				             $markup_value =  isset($styleArea['markup_value']) ? $styleArea['markup_value']:0;
        				             //$markup_rate  = $goodsArea['markup_rate'];
        				             //$markup_value =  $goodsArea['markup_value'];
        				         }else{        				            
        				             $markup_rate  = isset($styleArea['markup_rate']) ? $styleArea['markup_rate']:1;
        				             $markup_value =  isset($styleArea['markup_value']) ? $styleArea['markup_value']:0;
              				         $goodsArea['status'] = isset($styleArea['markup_rate']) ? $styleArea['markup_rate']:1;
        				         }
    
        				         $base_price = $goods['sale_price'];
        				         $sale_price = AmountHelper::calcMarkupPrice($base_price,$markup_rate,$markup_value,2);
            				     $goodsAreaValues[$goods['goods_id']] = [
            				         'area_id' =>$area_id,
            				         'area_name'=>$styleArea['area_name'],
            				         'goods_sn'=>$goods['goods_sn'],            				         
            				         'sale_price'=>$sale_price,
            				         'base_price'=>$base_price,
            				         'markup_rate' => $markup_rate,
            				         'markup_value'=> $markup_value,
            				         'is_onsale'=> $styleArea['status']==1 && $goodsArea['status']==1 ? "上架":"下架",
            				         'status'=> $goodsArea['status'],
            				     ];
        				     }
        				    // print_r($goodsAreaValues);
        				     ?>
        				     
        				     <div class="tab-pane<?php echo $area_id == AreaEnum::China ?" active":"" ?>" id="<?= 'areaTab1_'.$area_id?>">
        				     <?= unclead\multipleinput\MultipleInput::widget([
        				         'name' => "Style[goods_salepolicy][{$area_id}]",
        				         'removeButtonOptions'=>['label'=>'','class'=>''],
        				         'value' => $goodsAreaValues,
        				         'columns' => $goodsAreaColomns
                                ]);
        				     ?>
        				     </div>
        				     <?php 
        				 }
        				 ?>
        			</div>
		        </div>
                </div>  
    		    <!-- ./tab-content 商品-->
                <?php                 
                }
                ?>
        	  
            </div>
            <!-- ./box-body -->
      </div>
      <!-- ./row -->
      <?php }?>
    </div>
    <div class="modal-footer">
        <div class="col-sm-12 text-center">
            <button class="btn btn-primary" type="submit">保存</button>
            <span class="btn btn-white" onclick="history.go(-1)">返回</span>
        </div>
	</div>
</div>

<?php ActiveForm::end(); ?>
<script type="text/javascript">
$(function(){ 
	$(document).on("click",'.control-label',function(){
         var checked = false; 
		 if(!$(this).hasClass('checked')){
			 checked = true;
			 $(this).addClass('checked');
		 }else{
			 $(this).removeClass('checked');
		 }

         $(this).parent().find("input[type*='checkbox']").prop("checked",checked);
	});
	//批量商品编码复制
	$(document).on("click",'.batch-goods_sn',function(){
		var hasEdit = false;
		var fromValue = $("#style-style_sn").val();
		if(fromValue ==""){
             alert("<?= Yii::t("goods","请先填写款式编号")?>");
             return false;
		}
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
			var skuValue = $(this).find(".setsku-goods_sn").val();
        	if(skuValue != '' && skuValue != fromValue){
        		hasEdit = true;
        		return ;
        	}
        });
        if(hasEdit === true){
           	 if(!confirm("<?= Yii::t("goods","商品编码已修改过,是否覆盖")?>?")){
               	return false;
           	 }
        }
    	$("#skuTable tr[class*='sku_table_tr']").each(function(i){
        	if($(this).find(".setsku-status").val() == 1){
        	    i++;
        	    if(i<10) {
        	        i = "0" + i;
                }
        		$(this).find(".setsku-goods_sn").val(fromValue + "-" +i);
        	}
        });

	});

	$(document).on("click",'.batch-market_price',function(){
		var hasEdit = false;
		var fromValue = $("#style-market_price").val();
		if(fromValue ==""){
             alert("<?= Yii::t("goods","请先填写市场价")?>");
             return false;
		}
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
			var skuValue = $(this).find(".setsku-market_price").val();
        	if(skuValue != '' && skuValue != fromValue){
        		hasEdit = true;
        		return ;
        	}
        });
        if(hasEdit === true){
           	 if(!confirm("<?= Yii::t("goods","市场价已修改过,是否覆盖")?>?")){
               	return false;
           	 }
        }
    	$("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find(".setsku-status").val() == 1){
        		$(this).find(".setsku-market_price").val(fromValue);
        	}
        });
	});
	//销售价批量填充
	$(document).on("click",'.batch-sale_price',function(){
		var hasEdit = false;
		var fromValue = $("#style-sale_price").val();
		if(fromValue ==""){
             alert("<?= Yii::t("goods","请先填写销售价")?>");
             return false;
		}
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
			var skuValue = $(this).find(".setsku-sale_price").val();
        	if(skuValue != '' && skuValue != fromValue){
        		hasEdit = true;
        		return ;
        	}
        });
        if(hasEdit === true){
           	 if(!confirm("<?= Yii::t("goods","销售价已修改过,是否覆盖")?>?")){
               	return false;
           	 }
        }
    	$("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find(".setsku-status").val() == 1){
        		$(this).find(".setsku-sale_price").val(fromValue);
        	}
        });
	});
	//成本价批量填充
	$(document).on("click",'.batch-cost_price',function(){
		var hasEdit = false;
		var fromValue = $("#style-cost_price").val();
		if(fromValue ==""){
             alert("<?= Yii::t("goods","请先填写成本价")?>");
             return false;
		}
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
			var skuValue = $(this).find(".setsku-cost_price").val();
        	if(skuValue != '' && skuValue != fromValue){
        		hasEdit = true;
        		return ;
        	}
        });
        if(hasEdit === true){
           	 if(!confirm("<?= Yii::t("goods","销售价已修改过,是否覆盖")?>?")){
               	return false;
           	 }
        }
    	$("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find(".setsku-status").val() == 1){
        		$(this).find(".setsku-cost_price").val(fromValue);
        	}
        });
	});
	//库存批量填充
	$(document).on("click",'.batch-goods_storage',function(){
		var hasEdit = false;
		var fromValue = $("#style-goods_storage").val();		
		if(fromValue = prompt("<?= Yii::t("goods","请输入库存数量")?>","10")){
			var r = /^\+?[1-9][0-9]*$/;
			if(!r.test(fromValue)) {
                 alert("<?= Yii::t("goods","库存数量不合法")?>");
                 return false;
			}
		}else {
            return false; 
		}
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
			var skuValue = $(this).find(".setsku-goods_storage").val();
        	if(skuValue != '' && skuValue != fromValue){
        		hasEdit = true;
        		return ;
        	}
        });
        if(hasEdit === true){
           	 if(!confirm("<?= Yii::t("goods","商品库存已修改过,是否覆盖")?>?")){
               	return false;
           	 }
        }
    	$("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find(".setsku-status").val() == 1){
        		$(this).find(".setsku-goods_storage").val(fromValue);
        	}
        });
        goodsStroageSum();
	});
	$(document).on("blur",'.setsku-goods_storage',function(){
    	goodsStroageSum();
	});
	$(document).on("click",'.sku-status',function(){
    	goodsStroageSum();
	});	
	function goodsStroageSum(){
		var total = 0;
		$("#skuTable tr[class*='sku_table_tr']").each(function(){
        	if($(this).find(".setsku-status").val() == 1){
        		var storage = $(this).find(".setsku-goods_storage").val();
        		if(parseInt(storage)){
        			total += parseInt(storage);
        		}
        	}
        }); 
		$("#style-goods_storage").val(total).attr('readonly',true);
        return total; 
	}
	/*
	//基础信息销售价计算
	function salePriceCalc(){
		var priceList = [];
		var minPrice = 0;
		var hasOne = false;	
		$("#skuTable tr[class*='sku_table_tr']").each(function(){			
        	if($(this).find(".setsku-status").val() == 1 && (salePrice = $(this).find(".setsku-sale_price").val())){
        		priceList.push(salePrice);
        	}
        }); 
        if(!priceList){
        	$("#style-sale_price").val().attr('readonly',false);
            return minPrice;
        }
        priceList.sort(function(v1,v2){return v1-v2;});  
        minPrice = priceList[0];  
		$("#style-sale_price").val(minPrice).attr('readonly',true);
        return minPrice; 
	}
	//基础信息销售价计算
	function marketPriceCalc(){
		var priceList = [];
		var maxPrice = 0;
		$("#skuTable tr[class*='sku_table_tr']").each(function(){			
        	if($(this).find(".setsku-status").val() == 1 && (price = $(this).find(".setsku-market_price").val())){
        		priceList.push(price);
        	}
        }); 
        if(!priceList){
        	$("#style-market_price").val().attr('readonly',false);
            return minPrice;
        }
        priceList.sort(function(v1,v2){return v2-v1;});  
        minPrice = priceList[0];  
		//$("#style-market_price").val(minPrice).attr('readonly',true);
        return minPrice; 
	} */

    $('input[type="text"],textarea').change(function () {
        $(this).val($(this).val().trim());
    });
});
</script>