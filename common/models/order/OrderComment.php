<?php

namespace common\models\order;

use common\models\base\BaseModel;
use Yii;

/**
 * This is the model class for table "{{%order_comment}}".
 *
 * @property int $id 订单评论
 * @property int $order_id
 * @property int $style_id
 * @property int $status 状态：0待审核，1审核通过，-1审核不通过
 * @property int $admin_id 审核管理员ID
 * @property int $is_import 是否导入：0否，1是
 * @property string $content 评价内容
 * @property int $user_id 用户ID
 * @property int $from 来路站点
 * @property string $ip 客户IP
 * @property int $ip_area_id
 * @property string $ip_location
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class OrderComment extends BaseModel
{
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
            [['order_id', 'style_id', 'status', 'admin_id', 'is_import', 'user_id', 'from', 'ip_area_id', 'created_at', 'updated_at'], 'integer'],
            [['style_id', 'created_at', 'updated_at'], 'required'],
            [['content'], 'string', 'max' => 45],
            [['ip'], 'string', 'max' => 10],
            [['ip_location'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'order_id' => Yii::t('app', 'Order ID'),
            'style_id' => Yii::t('app', 'Style ID'),
            'status' => Yii::t('app', 'Status'),
            'admin_id' => Yii::t('app', 'Admin ID'),
            'is_import' => Yii::t('app', 'Is Import'),
            'content' => Yii::t('app', 'Content'),
            'user_id' => Yii::t('app', 'User ID'),
            'from' => Yii::t('app', 'From'),
            'ip' => Yii::t('app', 'Ip'),
            'ip_area_id' => Yii::t('app', 'Ip Area ID'),
            'ip_location' => Yii::t('app', 'Ip Location'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
