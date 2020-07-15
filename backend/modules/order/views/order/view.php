<?php

use common\enums\CurrencyEnum;
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

$this->title = Yii::t('order', '客户订单');
$this->params['breadcrumbs'][] = ['label' => Yii::t('order', '订单'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//
?>
<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="<?= Url::to(['order/view', 'id'=>$model->id]) ?>"> <?= Html::encode($this->title) ?></a>
                </li>
                <li>
                    <a href="<?= Url::to(['order-log/index', 'id'=>$model->id]) ?>"> <?= Html::encode('日志记录') ?></a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
                    <ul class="nav nav-tabs pull-right">
                        <li class="pull-left header"><i class="fa fa-th"></i> 详情信息&nbsp; <span class="label label-primary"><?= $model->refund_status?'已关闭':\common\enums\OrderStatusEnum::getValue($model->order_status) ?></span>
                        </li>
                        <li class="pull-right header">
                            <span class="label">
                                <?= Html::edit(['edit-address', 'id' => $model->id], '编辑', [
                                'data-toggle' => 'modal',
                                'data-target' => '#ajaxModal',
                                'class'=>'btn btn-info btn-sm'
                                ]); ?>
                            </span>
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
                                <?= $model->refund_status?'已关闭':\common\enums\OrderStatusEnum::getValue($model->order_status) ?>
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
                                <?= $model->member->username ?? '' ?>
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
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('address.province_name') ?>，<?= $model->getAttributeLabel('address.city_name') ?>：</label>
                                <?= $model->address->province_name ?>，<?= $model->address->city_name ?>
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
                        <li class="pull-left header"><i class="fa fa-th"></i> 电汇信息 </li>
                    </ul>
                    <div class="box-body col-lg-12" style="margin-left:9px">
                        <?php if($model->wireTransfer) {?>
                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= '付款凭证' ?>：</label></div>
                                        <div class="col-lg-7"><?= common\helpers\ImageHelper::fancyBox($model->wireTransfer->payment_voucher); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('wireTransfer.account') ?>：</label></div>
                                        <div class="col-lg-7"><?= $model->wireTransfer->account ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= '支付交易号' ?>：</label> </div>
                                        <div class="col-lg-7"><?= $model->wireTransfer->payment_serial_number ?></div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= '收款凭证' ?>：</label></div>
                                        <div class="col-lg-7"><?= common\helpers\ImageHelper::fancyBox($model->wireTransfer->collection_voucher); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= '审核状态' ?>：</label></div>
                                        <div class="col-lg-7"><?= common\enums\WireTransferEnum::getValue($model->wireTransfer->collection_status); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label></label></div>
                                        <div class="col-lg-7"></div>
                                    </div>
                                </div>
                            </div>
                        <?php } else {?>
                            不是电汇支付订单
                        <?php }?>
                    </div>
                </div>
                <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_2">
                    <ul class="nav nav-tabs pull-right">
                        <li class="pull-left header"><i class="fa fa-th"></i> 发票/发货单信息 </li>
                    </ul>
                    <div class="box-body col-lg-12" style="margin-left:9px">
                        <?php if($model->invoice) {?>
                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('invoice.invoice_type') ?>：</label></div>
                                        <div class="col-lg-7"><?= \common\enums\InvoiceTypeEnum::getValue($model->invoice->invoice_type) ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('invoice.invoice_title') ?>：</label> </div>
                                        <div class="col-lg-7"><?= $model->invoice->invoice_title ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('invoice.tax_number') ?>：</label></div>
                                        <div class="col-lg-7"><?= $model->invoice->tax_number ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('invoice.is_electronic') ?>：</label></div>
                                        <div class="col-lg-7"><?= \common\enums\InvoiceElectronicEnum::getValue($model->invoice->is_electronic) ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('invoice.email') ?>：</label></div>
                                        <div class="col-lg-7"><?= $model->invoice->invoiceEle && $model->invoice->invoiceEle->email ? $model->invoice->invoiceEle->email : $model->invoice->email ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label>电子凭证：</label></div>
                                        <div class="col-lg-7"><?= $model->invoice->invoiceEle ? '已修改': '未修改' ?></div>
                                    </div>
                                </div>

                                <div class="col-lg-6">

                                    <div class="row" style="margin-top:0px; ">
                                        <?= Html::edit(['ele-invoice-ajax-edit', 'order_id' => $model->id, 'language'=>$model->language,'returnUrl' => Url::getReturnUrl()],'编辑', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModalLg',
                                            'style'=>'height:25px;font-size:10px;'
                                        ])?>
                                    </div>
                                    <div class="row" style="margin-top:15px; ">
                                        <?= Html::a('预览',['ele-invoice-pdf','order_id'=>$model->id],  [
                                            'class' => 'btn btn-info btn-sm','target'=>'blank',
                                            'style'=>'height:25px;font-size:10px;'
                                        ])?>

                                    </div>
                                    <div class="row" style="margin-top:15px; ">
                                        <?= Html::button('发送('.$model->invoice->send_num.')',['class'=>'btn btn-sm btn-success ele-invoice-send','url'=>Yii::$app->homeUrl."/order/order/ele-invoice-send",'style'=>'height:25px;font-size:10px;'])?>
                                    </div>
                                    <!--                                <div class="row" style="margin-top:20px; ">-->
                                    <!--                                    --><?//= Html::button('打印',['class'=>'btn btn-primary btn-sm','style'=>'height:25px;font-size:10px;'])?>
                                    <!--                                </div>-->

                                    <?php if($model->order_status>=\common\enums\OrderStatusEnum::ORDER_PAID) { ?>
                                        <?= Html::edit(['edit-send-paid-email', 'order_id' => $model->id,'returnUrl' => Url::getReturnUrl()],sprintf('发送付款邮件(%d)', $model->send_paid_email_time), [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModalLg',
                                        ])?>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } else {?>
                        <div class="col-lg-6">
                                <div class="row">
                                    不开发票
                                </div>
                                <br/>

                                <div class="row">
                                    <?= Html::a('预览',['ele-invoice-pdf','order_id'=>$model->id],  [
                                        'class' => 'btn btn-info btn-sm','target'=>'blank',
                                    ])?>
                                    <?= Html::edit(['ele-invoice-ajax-edit', 'order_id' => $model->id, 'language'=>$model->language,'returnUrl' => Url::getReturnUrl()],'编辑', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',

                                    ])?>

                                    <?php if($model->order_status>=\common\enums\OrderStatusEnum::ORDER_PAID) { ?>
                                    <?= Html::edit(['edit-send-paid-email', 'order_id' => $model->id,'returnUrl' => Url::getReturnUrl()],sprintf('发送付款邮件(%d)', $model->send_paid_email_time), [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModalLg',
                                    ])?>
                                    <?php } ?>


                                </div>


                        </div>

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
        <div class="row" style="margin: 10px -15px;">
        
        <div class="col-lg-11">%s<br/>SKU：%s&nbsp;%s</div>
        </div>
DOM;
                                            $value = '';
                                            if($model->goods_type==19) {
                                                $value1 = '';
                                                $value2 = '';
                                                $goods_spec = '';
                                                $goods_spec1 = '';
                                                $goods_spec2 = '';
                                                if($model->goods_spec) {
                                                    $model->goods_spec = \Yii::$app->services->goods->formatGoodsSpec($model->goods_spec);
                                                    foreach ($model->goods_spec as $vo) {
                                                        if($vo['attr_id']==61) {
                                                            $goods = Yii::$app->services->goods->getGoodsInfo($vo['value_id']);

                                                            foreach ($goods['lang']['goods_spec'] as $spec) {
                                                                $goods_spec1 .= $spec['attr_name'].":".$spec['attr_value']."&nbsp;";
                                                            }

                                                            $value1 .= sprintf($html,
                                                                $vo['attr_name'] . '：' . $goods['goods_name'],
                                                                $goods['goods_sn'],
                                                                $goods_spec1
                                                            );
                                                            continue;
                                                        }
                                                        if($vo['attr_id']==62) {
                                                            $goods = Yii::$app->services->goods->getGoodsInfo($vo['value_id']);

                                                            foreach ($goods['lang']['goods_spec'] as $spec) {
                                                                $goods_spec2 .= $spec['attr_name'].":".$spec['attr_value']."&nbsp;";
                                                            }

                                                            $value2 .= sprintf($html,
                                                                $vo['attr_name'] . '：' . $goods['goods_name'],
                                                                $goods['goods_sn'],
                                                                $goods_spec2
                                                            );
                                                            continue;
                                                        }
                                                        $goods_spec .= $vo['attr_name'].":".$vo['attr_value']."&nbsp;";
                                                    }
                                                }
                                                $value .= sprintf($html,
                                                    '对戒名：' . $model->goods_name,
                                                    $model->goods_sn,
                                                    $goods_spec
                                                );

                                                $value .= $value1;
                                                $value .= $value2;
                                            }
                                            else {
                                                $goods_spec = '';
                                                if($model->goods_spec){
                                                    $model->goods_spec = \Yii::$app->services->goods->formatGoodsSpec($model->goods_spec);
                                                    foreach ($model->goods_spec as $vo){
                                                        $goods_spec .= $vo['attr_name'].":".$vo['attr_value']."&nbsp;";
                                                    }
                                                }
                                                $value .= sprintf($html,
                                                    $model->goods_name,
                                                    $model->goods_sn,
                                                    $goods_spec
                                                );
                                            }

                                            return $value;
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
                                            $value = \common\helpers\AmountHelper::rateAmount($model->goods_price-$model->goods_pay_price, 1, 2, ',');
                                            if($value>0.01) {
                                                $value .= sprintf(" （%s[%s]）", $model->coupon->specials->lang->title, \common\enums\PreferentialTypeEnum::getValue($model->coupon->type));
                                            }
                                            return $model->currency ." " . $value;
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
                                    <div class="col-lg-9">

                                            <?php
                                            $remark = trim($model->seller_remark);
                                            if($model->audit_remark) {
                                                $remark && ($remark .= "\r\n--------------------\r\n");
                                                $remark .= '[审核备注]：'.trim($model->audit_remark);
                                            }
                                            if($model->refund_remark) {
                                                $remark && ($remark .= "\r\n--------------------\r\n");
                                                $remark .= '[退款备注]：'.trim($model->refund_remark);
                                            }
                                            if($model->cancel_remark) {
                                                $remark && ($remark .= "\r\n--------------------\r\n");
                                                $remark .= '[取消备注]：'.trim($model->cancel_remark);
                                            }
                                            ?>
                                        <pre><?= $remark ?></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label>商品件数
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $dataProvider->getTotalCount() ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.goods_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->goods_amount, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.shipping_fee') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->shipping_fee, 1, 2, ',') ?></div>
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
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.discount_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(-$model->account->discount_amount, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('account.coupon_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(-$model->account->coupon_amount, 1, 2, ',') ?></div>
                                </div>
                                <?php
                                $cardUseAmount = 0;
                                foreach($model->cards as $n => $card) {
                                    if($card->type!=2) {
                                        continue;
                                    }
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
                                    $receivable = $model->account->pay_amount;//bcadd($model->account->order_amount, $cardUseAmount, 2) + $model->account->discount_amount;
                                    ?>
                                    <?php if($model->account->currency == \common\enums\CurrencyEnum::CNY) { ?>
                                        <div class="col-lg-7 text-red"><?= \common\enums\CurrencyEnum::HKD ?>&nbsp;<?= \common\helpers\AmountHelper::formatAmount(\Yii::$app->services->currency->exchangeAmount($model->account->pay_amount, 2, \common\enums\CurrencyEnum::HKD, CurrencyEnum::CNY), 2, ',') ?> (<?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::formatAmount($model->account->pay_amount, 2, ',') ?>)</div>
                                    <?php } else { ?>
                                        <div class="col-lg-7 text-red"><?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::formatAmount($model->account->pay_amount, 2, ',') ?></div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label style="font-weight:bold"><?= $model->getAttributeLabel('account.paid_currency') ?>：</label></div>
                                    <?php if($model->account->currency == \common\enums\CurrencyEnum::CNY) { ?>
                                        <div class="col-lg-7 text-red"><?= $model->account->paid_currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->paid_amount, 1, 2, ',') ?> (<?= $model->account->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(\Yii::$app->services->currency->exchangeAmount($model->account->paid_amount, 2, $model->account->currency, $model->account->paid_currency), 1, 2, ',') ?>)</div>
                                    <?php } else { ?>
                                        <div class="col-lg-7 text-red"><?= $model->account->paid_currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->account->paid_amount, 1, 2, ',') ?></div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label style="font-weight:bold">参考支付RMB金额：</label></div>
                                    <div class="col-lg-7 text-red"><?= \Yii::$app->services->currency->getSign() ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(!$model->payment_status?0:$model->account->pay_amount, 1/$model->account->exchange_rate, 2, ',') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_4">
                    <ul class="nav nav-tabs pull-right">
                        <li class="pull-left header"><i class="fa fa-th"></i> 物流信息 </li>
                    </ul>
                    <div class="box-body col-lg-12" style="margin-left:9px">
                        <?php if(is_array($logistics)) { ?>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= '快递公司' ?>：</label></div>
                                    <div class="col-lg-7"><?= $logistics['company'] ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= '运单号' ?>：</label></div>
                                    <div class="col-lg-7"><?= $logistics['no'] ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label>最新状态：</label></div>
                                    <div class="col-lg-7"><?= $logistics['display_status'] ?></div>
                                </div>
                                <?php foreach ($logistics['abstract_status'] as $key => $status) { ?>
                                    <div class="row">
                                        <div class="col-lg-5 text-right"><label><?= \common\enums\LogisticsEnum::getValue($key, 'abstractStatus') ?>：</label></div>
                                        <div class="col-lg-7"><?= $status?'是':'' ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-lg-5">
                                <?php foreach ($logistics['list'] as $logistic) { ?>
                                    <div class="row" style="margin: 10px;">
                                        <div class="col-lg-5 text-right"><label><?= $logistic['datetime'] ?>：</label></div>
                                        <div class="col-lg-7"><?= $logistic['remark'] ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } elseif(is_null($logistics)) {?>
                            没有物流信息
                        <?php } else {?>
                            <?= $logistics ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-center">
                    <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                </div>
            </div>
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