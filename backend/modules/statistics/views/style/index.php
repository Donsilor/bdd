<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '游客订单');
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <div class="tab-content">
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
                                'label' => '款号',
                                'attribute' => 'style_id',
                            ],
                            [
                                'label' => '商品名称',
                                'attribute' => 'style_name',
                            ],
                            [
                                'label' => '产品线',
                                'attribute' => 'type_id',
                            ],
                            [
                                'label' => '站点地区',
                                'attribute' => 'platform_group',
                                'value' => function($model) {
                                    return \common\enums\OrderFromEnum::getValue($model['platform_group'], 'groups');
                                }
                            ],
                            [
                                'label' => '销量',
                                'attribute' => 'count',
                            ],
                            [
                                'label' => '加购物车量',
//                                'attribute' => 'cart_count',
                            ],
                        ],
                    ]);
                  ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function audit(id) {
        let _id = [];
        if(id===undefined) {
            _id= []
        }
        else {
            _id.push(id)
        }
    }

    (function ($) {
        /**
         * 头部文本框触发列表过滤事件
         */
        $(".top-form input,select").change(function () {
            $(".filters input[name='" + $(this).attr('name') + "']").val($(this).val()).trigger('change');
        });


    })(window.jQuery);
</script>