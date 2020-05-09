<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\grid\GridView;

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
                            [
                                'label'=>'动作名',
                                'filter' => false,
                                'attribute'=>'action_name',
                                'value'=>function($model){
                                    return \common\enums\OrderLogEnum::getValue($model->action_name, 'actionName')?:$model->action_name;
                                }
                            ],
                            [
                                'label'=>'操作内容',
                                'filter' => false,
                                'format' => 'raw',
                                'attribute'=>'log_msg',
                                'value'=>function($model) {
                                    $value = '<pre>';
                                    $value .= $model->log_msg;

                                    if(!empty($model->data[0])) {
                                        foreach ($model->data[0] as $field => $datum) {

                                            $fieldName = \common\enums\OrderLogEnum::getValue($field, 'fieldName')?:$field;

                                            if(empty($model->data[1][$field])) {
                                                $value .= "\r\n";
                                                $value .= sprintf('[%s]：“%s”;', $fieldName, $datum);
                                            }
                                            else {
                                                $model->data[1][$field] = $model->data[1][$field]??'';
                                                if($model->data[1][$field] == $datum) {
                                                    continue;
                                                }
                                                $value .= "\r\n";
                                                $value .= sprintf('[%s]：“%s”变更为“%s“;', $fieldName, $model->data[1][$field], $datum);
                                            }
                                        }
                                    }

                                    $value .= '</pre>';
                                    return $value;
                                }
                            ],
                            [
                                'label'=>'操作类型',
                                'filter' => Html::activeDropDownList($searchModel, 'log_role', [
                                    'system'=>'系统',
                                    'admin'=>'管理员',
                                    'buyer'=>'客户',
                                    'tourist'=>'游客',
                                ], [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]),
                                'attribute'=>'log_role',
                                'value'=>function($model) {
                                    return array_get([
                                        'system'=>'系统',
                                        'admin'=>'管理员',
                                        'buyer'=>'客户',
                                        'tourist'=>'游客',
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