<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;
use common\enums\OrderStatusEnum;
use kartik\daterange\DateRangePicker;

$this->title = Yii::t('order', '客户订单');
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<div class="row">
    <div class="col-sm-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li>
                    <a href="<?= Url::to(['order/view', 'id'=>$model->id]) ?>"> <?= Html::encode($this->title) ?></a>
                </li>
                <li class="active">
                    <a href="<?= Url::to(['order-log/index', 'id'=>$model->id]) ?>"> <?= Html::encode('日志记录') ?></a>
                </li>
            </ul>
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
                                'class'=>'yii\grid\CheckboxColumn',
                                'name'=>'id',  //设置每行数据的复选框属性
                                'headerOptions' => ['width'=>'30'],
                            ],
                            [
                                'label'=>'序号',
                                'filter' => false,
                                'attribute' => 'id',
                            ],
                            [
                                'label'=>'操作时间',
                                'filter' => false,
                                'attribute'=>'log_time',
                                'value'=>function($model){
                                    return Yii::$app->formatter->asDatetime($model->log_time);
                                }
                            ],
//                            [
//                                'label'=>'操作模块',
//                                'filter' => false,
//                                'attribute'=>'log_time',
//                            ],
                            [
                                'label'=>'操作内容',
                                'filter' => false,
                                'attribute'=>'log_msg',
                            ],
                            [
                                'label'=>'操作类型',
                                'filter' => Html::activeDropDownList($searchModel, 'log_role', [
                                    'system'=>'系统',
                                    '管理员'=>'管理员',
                                    'buyer'=>'客户',
                                ], [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'attribute'=>'log_role',
                                'value'=>function($model) {
                                    return array_get([
                                        'system'=>'系统',
                                        '管理员'=>'管理员',
                                        'buyer'=>'客户',
                                    ], $model->log_role);
                                }
                            ],
                            [
                                'label'=>'操作人',
                                'filter' => false,
                                'attribute'=>'log_user',
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