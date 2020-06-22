<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\member\Contact */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('web_seo', 'Contact');
$this->params['breadcrumbs'][] = ['label' => Yii::t('web_seo', 'Contacts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <div class="box-body">
                <div class="col-sm-12">
                    <div class="form-group field-contact-language">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-language"><?=Yii::t('language', '语言类型')?>: </label></div>
                        <div class="col-sm-11">
                            <?= common\enums\LanguageEnum::getMap()[$model->language]; ?>
                            <div class="help-block"></div>
                        </div>

                    </div>
<!--                    <div class="form-group field-contact-member_id">-->
<!--                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-member_id"><?//=Yii::t('member_id', '会员ID')?></label></div>-->
<!--                        <div class="col-sm-11">-->
<!--                            --><?//=$model->memeber_id;?>
<!--                        </div>-->
<!--                    </div>-->
                    <div class="form-group field-contact-first_name">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-first_name"><?=Yii::t('first_name', '名')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->first_name;?>
                            <div class="help-block"></div>
                        </div>

                     </div>
                    <div class="form-group field-contact-last_name">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-last_name"><?=Yii::t('last_name', '姓')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->last_name;?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-telphone ">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-telphone"><?=Yii::t('telphone', '电话')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->telphone?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>

                    <div class="form-group field-contact-city ">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-telphone"><?=Yii::t('city', '所属城市')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->city?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-book_time ">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-telphone"><?=Yii::t('book_time', '预约时间')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->book_time?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-type_id">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-type_id"><?=Yii::t('type_id', '留言类别')?>: </label></div>
                        <div class="col-sm-11">
                            <?= common\enums\ContactEnum::getMap()[$model->type_id]?? '&nbsp; '; ?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-content">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-content"><?=Yii::t('content', '留言内容')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->content?: '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-status">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-status"><?=Yii::t('status', '状态')?></label></div>
                        <div class="col-sm-11">
                            <?=\common\enums\FollowStatusEnum::getValue($model->followed_status)?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>
                    <div class="form-group field-contact-created_at">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-created_at"><?=Yii::t('created_at', '留言时间')?></label></div>
                        <div class="col-sm-11">
                            <?=date('Y-m-d H:i:s',$model->created_at)?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>

                    <div class="form-group field-contact-content">
                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-remark"><?=Yii::t('remark', '备注')?>: </label></div>
                        <div class="col-sm-11">
                            <?=$model->remark?? '&nbsp; ';?>
                            <div class="help-block"></div>
                        </div>
                    </div>
<!--                    <div class="form-group field-contact-updated_at">-->
<!--                        <div class="col-sm-1 text-right"><label class="control-label" for="contact-updated_at">--><?//=Yii::t('updated_at', '更新时间')?><!--</label></div>-->
<!--                        <div class="col-sm-11">-->
<!--                            --><?//=date('Y-m-d H:i:s',$model->updated_at);?>
<!--                        </div>-->
<!--                    </div>-->
                </div>
            </div>
        </div>
    </div>
</div>
