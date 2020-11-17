<?php

namespace backend\modules\order\forms;

use common\models\goods\Style;
use common\models\order\OrderComment;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class OrderCommentEditForm extends OrderComment
{
    public $style_sn;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_comment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_destroy', 'order_id', 'style_id', 'type_id', 'status', 'admin_id', 'is_import', 'member_id', 'platform', 'ip_area_id', 'updated_at', 'grade'], 'integer'],
            [['style_sn', 'platform', 'created_at', 'username', 'grade'], 'required'],
            [['content', 'remark'], 'string', 'max' => 200],
            [['ip'], 'string', 'max' => 255],
            [['ip_location'], 'string', 'max' => 255],
            [['created_at'], 'datetime'],
            [['images'], 'safe'],
            ['style_sn', 'validateStyleSn'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'order_id' => Yii::t('app', '订单ID'),
            'style_sn' => Yii::t('app', '款号'),
            'type_id' => Yii::t('app', '产品线ID'),
            'style_id' => Yii::t('app', '款式ID'),
            'status' => Yii::t('app', '状态'),
            'admin_id' => Yii::t('app', '管理员ID'),
            'remark' => Yii::t('app', '审核回复'),
            'is_import' => Yii::t('app', '是否导入'),
            'grade' => Yii::t('app', '评价星级'),
            'content' => Yii::t('app', '评价内容'),
            'images' => Yii::t('app', '评价图片'),
            'member_id' => Yii::t('app', '客户ID'),
            'username' => Yii::t('app', '虚拟用户名'),
            'platform' => Yii::t('app', '站点'),
            'ip' => Yii::t('app', 'Ip'),
            'ip_area_id' => Yii::t('app', 'Ip Area ID'),
            'ip_location' => Yii::t('app', 'Ip Location'),
            'created_at' => Yii::t('app', '评价时间'),
            'updated_at' => Yii::t('app', '更新'),
            'is_destroy' => Yii::t('app', '是否删除'),
        ];
    }

    public function validateStyleSn($attribute)
    {
        if($this->hasErrors()) {
            return;
        }

        $style = Style::findOne(['style_sn'=>$this->$attribute]);

        if(!$style->id) {
            $this->addError($attribute, '款号不正确');
        }

        $this->style_id = $style->id;
        $this->type_id = $style->type_id;
    }

    public function afterValidate()
    {
        parent::afterValidate(); // TODO: Change the autogenerated stub

        $this->created_at = strtotime($this->created_at);
        $this->updated_at = $this->created_at;
        $this->status = 1;
        $this->is_import = 1;
        $this->admin_id = Yii::$app->user->getIdentity()->id;
        $this->images && is_array($this->images) && ($this->images = implode(",", $this->images));
    }

    public function behaviors()
    {
        return [];
    }
}