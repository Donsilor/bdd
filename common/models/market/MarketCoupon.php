<?php

namespace common\models\market;

use common\models\backend\Member;
use common\models\goods\Goods;
use common\models\goods\GoodsType;
use common\models\goods\Style;
use services\goods\TypeService;
use Yii;
use yii\base\Exception;

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
            [['area_attach', 'count'], 'required'],
            [['at_least'], 'required', 'on'=>['edit-1-1', 'edit-2-1']],
            [['money'], 'required', 'on'=>['edit-1-1', 'edit-2-1']],
            [['discount'], 'required', 'on'=>['edit-1-2', 'edit-2-2']],
            [['goods_attach'], 'required', 'on'=>['edit-1-1', 'edit-1-2']],
            [['goods_type_attach'], 'required', 'on'=>['edit-2-1', 'edit-2-2']],
            [['goods_attach'], 'validateGoodsAttach'],
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

    //验证活动款号（goods_attach）
    public function validateGoodsAttach($attribute)
    {
        if(!empty($this->goods_attach) && !is_array($this->goods_attach)) {
            $goodsAttach = explode(',', $this->goods_attach);
            foreach ($goodsAttach as $goodsSn) {

                $goodsData = Style::find()->where(['style_sn' => $goodsSn])->select(['id', 'type_id'])->one();

                if (empty($goodsData)) {
                    $this->addError($attribute, sprintf('[%s]产品未找到~！', $goodsSn));
                }
            }
        }
    }

    public function beforeSave($insert)
    {
        $this->area_attach = empty($this->area_attach) ? [] : $this->area_attach;
        $this->goods_type_attach = empty($this->goods_type_attach) ? [] : $this->goods_type_attach;

        if(empty($this->goods_attach)) {
            $this->goods_attach = [];
        }
        else {
            if(!is_array($this->goods_attach)) {
                $this->goods_attach = array_unique(explode(',', $this->goods_attach));

            }
        }

        //$this->goods_attach = empty($this->goods_attach) ? [] : explode(',', $this->goods_attach);
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    //添加人
    public function getUser()
    {
        return $this->hasOne(Member::class,['id'=>'user_id']);
    }

    public function getSpecials()
    {
        return $this->hasOne(MarketSpecials::class,['id'=>'specials_id']);
    }

    /**
     * 已领取数量
     * @return mixed
     */
    public function getUseCount()
    {
        return MarketCouponDetails::find()->where(['coupon_id'=>$this->id, 'coupon_status'=>2])->count('id');
    }

    /**
     * 已领取数量
     * @return mixed
     */
    public function getReceiveCount()
    {
        return MarketCouponDetails::find()->where(['coupon_id'=>$this->id, 'coupon_status'=>0])->count('id');
    }

    public function getGoodsType()
    {
        static $goodsTypes = [];

        if(!empty($goodsTypes[$this->id])) {
            return $goodsTypes[$this->id];
        }

        foreach (TypeService::getTypeList() as $key => $item) {
            if(in_array($key, $this->goods_type_attach)) {
                $goodsTypes[$this->id][$key] = $item;
            }
        }
        return $goodsTypes[$this->id];
    }
}
