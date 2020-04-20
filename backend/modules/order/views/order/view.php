<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\widgets\langbox\LangBox;
use yii\base\Widget;
use common\widgets\skutable\SkuTable;
use common\helpers\Url;
use common\helpers\ArrayHelper;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model common\models\order\order */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('order', '详情');
$this->params['breadcrumbs'][] = ['label' => Yii::t('order', '订单'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//
?>
    <div class="box-body nav-tabs-custom">
        <h2 class="page-header">订单详情</h2>
        <div class="tab-content">
            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> 订单信息&nbsp; <span class="label label-primary"><?= \common\enums\OrderStatusEnum::getValue($model->order_status) ?></span>
                    </li>
                </ul>
                <div class="box-body col-lg-12" style="margin-left:9px">
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('order_sn') ?> ：</label>                        
                            <?= $model->order_sn ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('language') ?>：</label>
                            <?= \common\enums\LanguageEnum::getValue($model->language) ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('account.currency') ?>：</label>
                            <?= $model->account->currency ?>(<?= $model->account->exchange_rate ?>)
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.realname') ?>：</label>
                            <?= $model->address->realname ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('payment_type') ?>：</label>
                            <?= \common\enums\PayEnum::getValue($model->payment_type) ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('order_status') ?>：</label>
                            <?= \common\enums\OrderStatusEnum::getValue($model->order_status) ?>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-lg-4">
                        	<label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.mobile') ?>：</label>
                            <?= $model->address->mobile_code ?>-<?= $model->address->mobile ?>
                         </div>

                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('pay_sn') ?>：</label>
                            <?= $model->pay_sn?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('payment_status') ?>：</label>
                            <?= \common\enums\PayStatusEnum::getValue($model->payment_status)?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('member.username') ?>：</label>
                            <?= $model->member->username ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('order_from') ?>：</label>
                            <?= \common\enums\OrderFromEnum::getValue($model->order_from) ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('status') ?>：</label>
                            <?= \common\enums\AuditStatusEnum::getValue($model->status) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.country_name') ?> ：</label>
                            <?= $model->address->country_name ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('ip') ?>：</label>
                            <?= $model->ip ?>(<?= \common\enums\AreaEnum::getValue($model->ip_area_id) ?>)
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('follower_id') ?>：</label>
                            <?= $model->follower ? $model->follower->username:'' ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.city_name') ?>：</label>
                            <?= $model->address->city_name ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('ip_location') ?>：</label>
                            <?= $model->ip_location ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('created_at') ?>：</label>
                            <?= Yii::$app->formatter->asDatetime($model->created_at); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.address_details') ?>：</label>
                            <?= $model->address->address_details ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.zip_code') ?>：</label>
                            <?= $model->address->zip_code ?>
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.email') ?>：</label>
                            <?= $model->address->email ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4"><?= $model->getAttributeLabel('buyer_remark') ?> ：</label>
                            <?= $model->buyer_remark ?>
                        </div>
                        <div class="col-lg-4">
                        </div>
                        <div class="col-lg-4">
                            <label class="text-right col-lg-4">是否使用购物卡：</label>
                            <?= $model->cards?'是':'否' ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_2">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> 发票信息 </li>
                </ul>
                <div class="box-body col-lg-12" style="margin-left:9px">
                    <?php if($model->invoice) {?>
                        <div class="row">

                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-5 text-right"><?= $model->getAttributeLabel('invoice.invoice_type') ?>：</label></div>
                                    <div class="col-lg-7"><?= \common\enums\InvoiceTypeEnum::getValue($model->invoice->invoice_type) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><?= $model->getAttributeLabel('invoice.invoice_title') ?>：</label> </div>
                                    <div class="col-lg-7"><?= $model->invoice->invoice_title ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><?= $model->getAttributeLabel('invoice.tax_number') ?>：</label></div>
                                    <div class="col-lg-7"><?= $model->invoice->tax_number ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><?= $model->getAttributeLabel('invoice.is_electronic') ?>：</label></div>
                                    <div class="col-lg-7"><?= \common\enums\InvoiceElectronicEnum::getValue($model->invoice->is_electronic) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><?= $model->getAttributeLabel('invoice.email') ?>：</label></div>
                                    <div class="col-lg-7"><?= $model->invoice->email ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right">电子凭证：</label></div>
                                    <div class="col-lg-7"><?= $model->invoice->invoiceEle ? '已修改': '未修改' ?></div>
                                </div>
                            </div>

                            <div class="col-lg-6">

                                <div class="row" style="margin-top:20px; ">
                                    <?= Html::edit(['ele-invoice-ajax-edit', 'invoice_id' => $model->invoice->id, 'language'=>$model->language,'returnUrl' => Url::getReturnUrl()],'编辑', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',
                                        'style'=>'height:25px;font-size:10px;'
                                    ])?>
                                </div>
                                <div class="row" style="margin-top:20px; ">
                                    <?= Html::a('预览',['ele-invoice-pdf?order_id='.$model->id],  [
                                        'class' => 'btn btn-info btn-sm','target'=>'blank',
                                        'style'=>'height:25px;font-size:10px;'
                                    ])?>

                                </div>
                                <div class="row" style="margin-top:20px; ">
                                    <?= Html::button('发送('.$model->invoice->send_num.')',['class'=>'btn btn-sm btn-success ele-invoice-send','url'=>Yii::$app->homeUrl."/order/order/ele-invoice-send",'style'=>'height:25px;font-size:10px;'])?>
                                </div>
                                <div class="row" style="margin-top:20px; ">
                                    <?= Html::button('打印',['class'=>'btn btn-primary btn-sm','style'=>'height:25px;font-size:10px;'])?>
                                </div>


                            </div>
                        </div>
                    <?php } else {?>
                    	不开发票
                    <?php }?>
                </div>
            </div>
            <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_3">
                <ul class="nav nav-tabs pull-right">
                    <li class="pull-left header"><i class="fa fa-th"></i> 商品信息</li>
                </ul>
                <div class="box-body table-responsive col-lg-12">
                         <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'tableOptions' => ['class' => 'table table-hover'],
                            'columns' => [
                                [
                                    'class' => 'yii\grid\SerialColumn',
                                    'visible' => false,
                                ],
                                [
                                    'attribute' => 'goods_image',
                                    'value' => function ($model) {
                                        return common\helpers\ImageHelper::fancyBox($model->goods_image);
                                    },
                                    'filter' => false,
                                    'format' => 'raw',
                                    'headerOptions' => ['width'=>'80'],
                                ],
                                [
                                    'label' => '商品清单',
                                    'value' => function ($model) {
                                        $html = <<<DOM
<div class="row">
    
    <div class="col-lg-8">%s<br/>SKU：%s&nbsp;%s</div>
</div>
DOM;
                                        $goods_spec = '';
                                        if($model->goods_spec){
                                            $model->goods_spec = \Yii::$app->services->goods->formatGoodsSpec($model->goods_spec);
                                            foreach ($model->goods_spec as $vo){
                                                $goods_spec .= $vo['attr_name'].":".$vo['attr_value']."&nbsp;";
                                            }
                                        }
                                        return sprintf($html,
                                            $model->goods_name,
                                            $model->goods_sn,
                                            $goods_spec
                                        );
                                    },
                                    'filter' => false,
                                    'format' => 'html',
                                    'headerOptions' => ['width' => '500'],
                                ],
                                'goods_num',
                                [
                                    'attribute'=>'goods_price',
                                    'value' => function($model) {
                                        return $model->currency . " " . \common\helpers\AmountHelper::rateAmount($model->goods_price, 1, 2, ',');
                                    }
                                ],
                                [
                                    'label' => '优惠金额',
                                    'attribute'=>'goods_price',
                                    'value' => function($model) {
                                        return $model->currency ." "."0";
                                    }
                                ],
                                [
                                    'attribute'=>'goods_pay_price',
                                    'value' => function($model) {
                                        return $model->currency . " " . \common\helpers\AmountHelper::rateAmount($model->goods_pay_price, 1, 2, ',');
                                    }
                                ],
                            ]
                        ]); ?>
                 </div>
                <div class="box-body col-lg-12">
                    <div class="row">
                        <div class="col-lg-6">
                            <?php  if($model->express_id){?>
                            <div class="row">
                                <div class="col-lg-3 text-right"><label><?= $model->getAttributeLabel('express_id') ?>：</label></div>
                                <div class="col-lg-9"><?= \Yii::$app->services->express->getExressName($model->express_id);?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 text-right"><label><?= $model->getAttributeLabel('express_no') ?>：</label></div>
                                <div class="col-lg-9"><?= $model->express_no ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 text-right"><label><?= $model->getAttributeLabel('delivery_time') ?>：</label></div>
                                <div class="col-lg-9"><?= date('Y-m-d H:i:s',$model->delivery_time)?></div>
                            </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-lg-3 text-right">
                                    <label><?= $model->getAttributeLabel('seller_remark') ?>：</label></div>
                                <div class="col-lg-9"><?= $model->seller_remark ?></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.shipping_fee') ?>
                                        ：</label></div>
                                <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->shipping_fee, 1, 2, ',') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.discount_amount') ?>
                                        ：</label></div>
                                <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->discount_amount, 1, 2, ',') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.tax_fee') ?>
                                        ：</label></div>
                                <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->tax_fee, 1, 2, ',') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.safe_fee') ?>
                                        ：</label></div>
                                <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->safe_fee, 1, 2, ',') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.order_amount') ?>
                                        ：</label></div>
                                <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->order_amount, 1, 2, ',') ?></div>
                            </div>
                            <?php
                            $cardUseAmount = 0;
                            foreach($model->cards as $n => $card) {
                                if($card->type!=2) {
                                    continue;
                                }
                                $cardUseAmount = bcadd($cardUseAmount, $card->use_amount, 2);
                            ?>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label>购物卡<?= $n+1 ?>：</label></div>
                                <div class="col-lg-7"><?= $card->currency ?>&nbsp;<?= $card->use_amount ?>&nbsp;（<?= $card->card->sn ?> <?= $card->status==0?'已解绑':'' ?>）</div>
                            </div>
                            <?php
                            }
                            ?>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label style="font-weight:bold">应付款：</label></div>
                                <?php
                                $receivable = bcadd($model->account->order_amount, $cardUseAmount, 2) + $model->account->discount_amount;
                                ?>
                                <div class="col-lg-7 text-red"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::formatAmount($receivable, 2, true) ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label style="font-weight:bold"><?= $model->getAttributeLabel('account.pay_amount') ?>：</label></div>
                                <div class="col-lg-7 text-red"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->pay_amount, 1, 2, ',') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-5 text-right"><label style="font-weight:bold">参考支付RMB金额：</label></div>
                                <div class="col-lg-7 text-red"><?= \Yii::$app->services->currency->getSign() ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->pay_amount, 1/$model->account->exchange_rate, 2, ',') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="text-center">
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
        </div>
    </div>

<script>

    $(document).on("click", ".ele-invoice-send", function (e) {
        var that = $(this);
        var postUrl = that.attr('url');
        that.attr('class','btn btn-sm btn-default').attr('disabled',true);
        $.ajax({
            type: "post",
            url: postUrl,
            dataType: "json",
            data: {order_id:<?= $model->id?>},
            success: function (data) {
                if (parseInt(data.code) !== 200) {
                    rfMsg(data.message);
                } else {
                    that.text('发送（' + data.data.send_num + ')')
                    rfMsg('发送成功');
                    // that.attr('class','btn btn-sm btn-success ele-invoice-send').attr('disabled',false);
                    console.log(data)
                }
            }
        });
        return false;
    });


</script>