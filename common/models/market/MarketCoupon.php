<?php

namespace common\models\market;

use common\models\backend\Member;
use Yii;

/**
 * This is the model class for table "market_coupon".
 *
 * @property int $id ID
 * @property int $specials_id 活动ID
 * @property int $type 优惠券类型 1:满减;2:折扣
 * @property string $money 发放面额
 * @property int $discount 折扣
 * @property int $count 发放数量
 * @property int $get_count 已领取数量
 * @property int $max_fetch 每人最大领取个数 0无限制
 * @property string $at_least 满多少元使用 0代表无限制
 * @property array $area_attach 活动地区
 * @property array $goods_attach 活动款号
 * @property array $goods_type_attach 产品线
 * @property int $status 状态[-1:删除;0:禁用;1启用]
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class MarketCoupon extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_coupon';
    }

    /**
     * {@inheritdoc}
     */
//    public function scenarios()
//    {
//        return [
//            'create' => ['name'],
//            'update'=>['name']
//        ];
//    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['specials_id','money', 'at_least', 'type', 'discount', 'count', 'get_count', 'max_fetch', 'status', 'created_at', 'updated_at'], 'integer'],
            [['area_attach', 'goods_attach', 'goods_type_attach'], 'safe'],
            [['area_attach', 'count', 'at_least'], 'required'],
            [['money'], 'required', 'on'=>['edit-1-1', 'edit-2-1']],
            [['discount'], 'required', 'on'=>['edit-1-2', 'edit-2-2']],
            [['goods_attach'], 'required', 'on'=>['edit-1-1', 'edit-1-2']],
            [['goods_type_attach'], 'required', 'on'=>['edit-2-1', 'edit-2-2']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'specials_id' => '活动ID',
            'type' => '优惠券类型 1:满减;2:折扣',
            'money' => '发放面额',
            'discount' => '折扣',
            'count' => '发放数量',
            'get_count' => '已领取数量',
            'max_fetch' => '每人最大领取个数 0无限制',
            'at_least' => '满多少元使用',
            'area_attach' => '活动地区',
            'goods_attach' => '活动款号',
            'goods_type_attach' => '产品线',
            'status' => '状态[-1:删除;0:禁用;1启用]',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    public function beforeSave($insert)
    {
        $this->area_attach = empty($this->area_attach) ? [] : $this->area_attach;
        $this->goods_type_attach = empty($this->goods_type_attach) ? [] : $this->goods_type_attach;
        $this->goods_attach = empty($this->goods_attach) ? [] : explode(',', $this->goods_attach);
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    //添加人
    public function getUser()
    {
        return $this->hasOne(Member::class,['id'=>'user_id']);
    }
}
