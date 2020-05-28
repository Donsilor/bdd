<?php

namespace common\models\common;

use Yii;
use common\behaviors\MerchantBehavior;

/**
 * This is the model class for table "{{%common_sms_log}}".
 *
 * @property int $id
 * @property string $merchant_id 商户id
 * @property string $member_id 用户id
 * @property string $mobile 手机号码
 * @property string $code 验证码
 * @property string $content 内容
 * @property int $error_code 报错code
 * @property string $error_msg 报错信息
 * @property string $error_data 报错日志
 * @property string $usage 用途
 * @property int $used 是否使用[0:未使用;1:已使用]
 * @property int $use_time 使用时间
 * @property int $status 状态(-1:已删除,0:禁用,1:正常)
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 */
class SmsLog extends \common\models\base\BaseModel
{
    use MerchantBehavior;

    const USAGE_LOGIN = 'login';
    const USAGE_REGISTER = 'register';
    const USAGE_UP_PWD = 'up-pwd';
    const USAGE_ORDER_SEND = 'order-send';
    const USAGE_ERROR_NOTICE = 'error-notice';
    const USAGE_ORDER_REFUND_NOTICE = 'order-refund-notice';
    const USAGE_WIRE_TRANSFER_ORDER_NOTICE = 'wire-transfer-order-notice';
    const USAGE_ORDER_PAY_SUCCESS = 'order-pay-success-notice';
    const USAGE_ORDER_ABNORMAL_NOTICE = 'order-abnormal-notice';

    /**
     * @var array
     */
    public static $usageExplain = [
        self::USAGE_LOGIN => '登录确认验证码',
        self::USAGE_REGISTER => '用户注册验证码',
        self::USAGE_UP_PWD => '修改密码验证码',
        self::USAGE_ORDER_SEND => '订单发货通知',
        self::USAGE_ERROR_NOTICE => '系统异常提醒',
        self::USAGE_ORDER_REFUND_NOTICE => '订单退款通知',
        self::USAGE_WIRE_TRANSFER_ORDER_NOTICE => '电汇订单通知',
        self::USAGE_ORDER_PAY_SUCCESS => '付款成功通知',
        self::USAGE_ORDER_ABNORMAL_NOTICE => '订单异常通知',
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%common_sms_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'member_id', 'error_code', 'used', 'mobile', 'code', 'use_time', 'status', 'created_at', 'updated_at'], 'integer'],
            [['error_data'], 'string'],
            [['usage'], 'string', 'max' => 20],
            [['ip'], 'string', 'max' => 30],
            [['content'], 'string', 'max' => 500],
            [['error_msg'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户',
            'member_id' => '用户',
            'email' => '手机号码',
            'code' => '验证码',
            'content' => '内容',
            'error_code' => '状态Code',
            'error_msg' => '状态说明',
            'error_data' => '具体信息',
            'usage' => '用途',
            'used' => '是否使用',
            'use_time' => '使用时间',
            'ip' => 'ip',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        /* if (!$this->ip) {
            $this->ip = Yii::$app->request->userIP;
        } */

        return parent::beforeSave($insert);
    }
}
