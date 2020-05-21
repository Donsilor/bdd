<?php

namespace common\models\common;

use Yii;

/**
 * This is the model class for table "{{%common_notify_contacts}}".
 *
 * @property int $id
 * @property int $type_id
 * @property string $realname 真实姓名
 * @property string $email 邮箱
 * @property int $email_switch 邮箱通知开关：0=关，1=开
 * @property string $mobile 手机号码
 * @property int $mobile_switch 短信通知开关：0=关，1=开
 * @property int $user_id 添加人
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class NotifyContacts extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%common_notify_contacts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email_switch', 'mobile_switch', 'type_id', 'created_at', 'updated_at'], 'integer'],
            [['user_id', 'type_id', 'goods_type_attach', 'realname'], 'required'],
            [['area_attach', 'goods_type_attach'], 'safe'],
            [['realname'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 60],
            ['email', 'email'],
            [['mobile'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_id' => 'Type ID',
            'area_attach' => '地区',
            'goods_type_attach' => '产品线',
            'realname' => '真实姓名',
            'email' => '邮箱',
            'email_switch' => '邮箱通知开关：0=关，1=开',
            'mobile' => '手机号码',
            'mobile_switch' => '短信通知开关：0=关，1=开',
            'user_id' => '添加人',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * 对应订单购物卡记录
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id'=>'user_id']);
    }
}
