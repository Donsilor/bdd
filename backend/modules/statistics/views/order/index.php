<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '游客订单');
$this->params['breadcrumbs'][] = ['label' => $this->title];

$params = Yii::$app->request->queryParams;
$params = $params ? "&".http_build_query($params) : '';

$status = [
    '1' => '未付款',
    '2' => '已销售',
    '3' => '已关闭',
];

$platform_ids = [
    '0' => '全部',
    '1' => 'PC端',
    '2' => '移动端',
];

?>

<div class="row">
    <div class="col-sm-12">

        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li class="pull-right">
                    <div class="box-header box-tools">
                        <?= Html::a('导出Excel','index?action=export'.$params) ?>
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="box-body top-form">
                    <div class="row col-sm-12">
                        <div class="col-sm-3">
                            时间：<br/>
                            <?= DateRangePicker::widget([    // 日期组件
                                'model' => $searchModel,
                                'attribute' => 'datetime',
                                'value' => '',
                                'options' => ['readonly' => false, 'class' => 'form-control',],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd',
                                    'locale' => [
                                        'separator' => '/',
                                    ],
                                    'endDate' => date('Y-m-d', time()),
                                    'todayHighlight' => true,
                                    'autoclose' => true,
                                    'todayBtn' => 'linked',
                                    'clearBtn' => true,
                                ],
                            ]);
                            ?>
                        </div>
                        <div class="col-sm-3">
                            <?= $searchModel->model->getAttributeLabel('platform_group') ?>：<br/>
                            <?= Html::activeCheckboxList($searchModel, 'platform_group', \common\enums\OrderFromEnum::groups(), [
                                'prompt' => '全部',
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                    </div>
                    <div class="row col-sm-12">
                        <div class="col-sm-3">
                            <?= $searchModel->model->getAttributeLabel('platform_id') ?>：<br/>
                            <?= Html::activeRadioList($searchModel, 'platform_id', $platform_ids, [
                                'class' => 'form-control',
                            ]);
                            ?>
                        </div>
                    </div>
                </div>
                <div class="active tab-pane">
                    <?= GridView::widget([
                        'id'=>'grid',
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        //重新定义分页样式
                        'tableOptions' => ['class' => 'table table-hover'],
                        'columns' => [
                            [
                                'header' => '序号',
                                'class'=>'yii\grid\SerialColumn',
                                'contentOptions' => [
                                    'class' => 'limit-width',
                                ],
                            ],
                            [
                                'label' => '站点地区',
                                'attribute' => 'platform_group',
                                'value' => function($model) {
                                    return \common\enums\OrderFromEnum::getValue($model['platform_group'], 'groups');
                                },
                                'filter' =>
                                    Html::activeCheckboxList($searchModel, 'platform_group', \common\enums\OrderFromEnum::groups(),[
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'platform_id', [
                                        'class' => 'hidden',
                                    ]) .
                                    Html::activeTextInput($searchModel, 'datetime', [
                                        'class' => 'hidden',
                                    ])
                            ],
                            [
                                'label' => '客户端',
                                'attribute' => 'platform_id',
                                'value' => function($model) {
                                    return \common\enums\OrderFromEnum::getValue($model['platform_id']);
                                },
                                'filter' => false
                            ],
                            [
                                'label' => '状态',
                                'attribute' => 'status',
                                'filter' => Html::activeDropDownList($searchModel, 'status', $status, [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'value' => function($model) use($status) {
                                    return $status[$model['status']]??'';
                                }
                            ],
                            [
                                'label' => '订单总数量',
                                'value' => function($model) use($searchModel) {
                                    return $model->getOrderCount($searchModel);
                                }
                            ],
                            [
                                'label' => '订单总额（CNY）',
                                'value' => function($model) use($searchModel) {
                                    return $model->getOrderMoneySum($searchModel);
                                }
                            ],
                            [
                                'label' => '各产品线总额（CNY）',
                                'format' => 'raw',
                                'value' => function($model) use($searchModel) {
                                    $typeList = Yii::$app->services->goodsType->getTypeList();
                                    $data = $model->getOrderProductTypeGroupData($searchModel);

                                    $value = '';
                                    if($data) {
                                        foreach ($data as $datum) {
                                            $value .= sprintf("%s：%.2f<br />", $typeList[$datum['goods_type']]??'未知', $datum['sum']);
                                        }
                                    }
                                    return $value;
                                }
                            ],
                            [
                                'label' => '商品总数量',
                                'value' => function($model) use($searchModel) {
                                    return $model->getOrderProductCount($searchModel);
                                }
                            ],
                            [
                                'label' => '各产品线商品总数量',
                                'format' => 'raw',
                                'value' => function($model) use($searchModel) {
                                    $typeList = Yii::$app->services->goodsType->getTypeList();
                                    $data = $model->getOrderProductTypeGroupData($searchModel);

                                    $value = '';
                                    if($data) {
                                        foreach ($data as $datum) {
                                            $value .= sprintf("%s：%d<br />", $typeList[$datum['goods_type']]??'未知', $datum['count']);
                                        }
                                    }
                                    return $value;
                                }
                            ],
//                            [
//                                'label' => '产品线',
//                                'attribute' => 'type_id',
//                                'filter' => Html::activeDropDownList($searchModel, 'type_id', Yii::$app->services->goodsType->getTypeList(), [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]),
//                                'value' => function($model) {
//                                    $list = Yii::$app->services->goodsType->getTypeList();
//                                    return $list[$model['type_id']]??'';
//                                }
//                            ],
//                            [
//                                'label' => '销量',
//                                'attribute' => 'count',
//                                'value' => function($model) {
//                                    return $model['count']?:0;
//                                }
//                            ],
//                            [
//                                'label' => '加购物车量',
//                                'attribute' => 'cart_count',
//                                'value' => function($model) {
//                                    return $model['cart_count']?:0;
//                                }
//                            ],
                        ],
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    (function ($) {

        /**
         * 头部文本框触发列表过滤事件
         */
        $(".top-form input,select").change(function () {
            var $input = $(".top-form input[name='" + $(this).attr('name') + "']");

            let type = $input.attr('type');

            if(type==='checkbox') {
                $input.each(function (i, v) {
                    let checkbox = $(".filters input[name='" + $(this).attr('name') + "']").eq(i).attr("checked", $(this).prop("checked"));
                    if($input.length===i+1) {
                        checkbox.trigger('change');
                    }
                });
            }
            else {
                $(".filters input[name='" + $(this).attr('name') + "']").val($(this).val()).trigger('change');
            }
        });

    })(window.jQuery);
</script>