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
            </ul>
            <div class="tab-content">
                <div class="row nav-tabs-custom tab-pane tab0 active" id="tab_1">
                    <ul class="nav nav-tabs pull-right">
                        <li class="pull-left header"><i class="fa fa-th"></i> 详情信息
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
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('currency') ?>：</label>
                                <?= $model->currency ?>(<?= $model->exchange_rate ?>)
                            </div>
                        </div>
                        <div class="row">

                            <div class="col-lg-4">
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('status') ?>：</label>
                                <?= \common\enums\PayStatusEnum::getValue($model->status)?>
                            </div>
                            <div class="col-lg-4">
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('created_at') ?>：</label>
                                <?= Yii::$app->formatter->asDatetime($model->created_at); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('ip') ?>：</label>
                                <?= $model->ip ?>(<?= \common\enums\AreaEnum::getValue($model->ip_area_id) ?>)
                            </div>
                            <div class="col-lg-4">
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('ip_location') ?>：</label>
                                <?= $model->ip_location ?>
                            </div>
                            <div class="col-lg-4">
                                <label class="text-right col-lg-4"><?= $model->getAttributeLabel('order_from') ?>：</label>
                                <?= \common\enums\OrderFromEnum::getValue($model->order_from) ?>
                            </div>
                        </div>
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
                                        <div class="col-lg-7"><?= $model->invoice->email ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php } else {?>
                            <div class="col-lg-6">
                                <div class="row">
                                    不开发票
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
                                            return $model->order->currency . " " . \common\helpers\AmountHelper::rateAmount($model->goods_price, 1, 2, ',');
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
                                            return $model->order->currency ." " . $value;
                                        }
                                    ],
                                    [
                                        'attribute'=>'goods_pay_price',
                                        'value' => function($model) {
                                            return $model->order->currency . " " . \common\helpers\AmountHelper::rateAmount($model->goods_pay_price, 1, 2, ',');
                                        }
                                    ],
                                ]
                            ]); ?>
                     </div>
                    <div class="box-body col-lg-12">
                        <div class="row">
                            <div class="col-lg-6">


                            </div>
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label>商品件数
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $dataProvider->getTotalCount() ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('goods_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->goods_amount, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('shipping_fee') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->shipping_fee, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('tax_fee') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->tax_fee, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('safe_fee') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->safe_fee, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('order_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->order_amount, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label><?= $model->getAttributeLabel('discount_amount') ?>
                                            ：</label></div>
                                    <div class="col-lg-7"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(-$model->discount_amount, 1, 2, ',') ?></div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-5 text-right"><label style="font-weight:bold">应付款：</label></div>
                                    <?php
                                    $pay_amount = $model->pay_amount;
                                    if($model->currency == CurrencyEnum::TWD) {
                                        $pay_amount = sprintf("%.2f", intval($pay_amount));
                                    }
                                    ?>
                                    <div class="col-lg-7 text-red"><?= $model->currency ?>&nbsp;<?= \common\helpers\AmountHelper::formatAmount($pay_amount, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label style="font-weight:bold"><?= $model->getAttributeLabel('paid_currency') ?>：</label></div>
                                    <div class="col-lg-7 text-red"><?= $model->paid_currency ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount($model->paid_amount, 1, 2, ',') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-5 text-right"><label style="font-weight:bold">参考支付RMB金额：</label></div>
                                    <div class="col-lg-7 text-red"><?= \Yii::$app->services->currency->getSign() ?>&nbsp;<?= \common\helpers\AmountHelper::rateAmount(!$model->status?0:$model->pay_amount, 1/$model->exchange_rate, 2, ',') ?></div>
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
    </div>
</div>
