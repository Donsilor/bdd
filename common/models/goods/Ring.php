<?php

namespace common\models\goods;

use common\models\base\BaseModel;
use Yii;

/**
 * This is the model class for table "{{%goods_ring}}".
 *
 * @property string $id 主键ID
 * @property string $ring_name 对戒名称
 * @property string $ring_sn 对戒编码
 * @property string $ring_image 对戒封面图片
 * @property string $qr_code 对戒二维码
 * @property int $ring_salenum 对戒销量
 * @property int $ring_style 对戒款式（1-金典系列，2-排镶系列）
 * @property string $sale_price 销售价格
 * @property int $status 是否启用（1-是，0-否, -1）
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class Ring extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_ring}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','ring_salenum', 'ring_style', 'status', 'created_at', 'updated_at','sale_volume','virtual_volume','virtual_clicks','goods_clicks','type_id','style_id','goods_id','goods_storage'], 'integer'],
            [['sale_price'], 'number'],
            [['ring_sn','sale_price'],'required'],
            ['sale_price','compare','compareValue' => 0, 'operator' => '>'],
            ['market_price','compare','compareValue' => 0, 'operator' => '>'],
            ['cost_price','compare','compareValue' => 0, 'operator' => '>'],
            ['market_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            ['sale_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            ['cost_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            [['ring_sn'],'string', 'max' => 100],
            [['qr_code'], 'string', 'max' => 200],
            [['ring_images'],'parseRingImages'],
            [['ring_name','language','ring_images','style_salepolicy'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $currency = \Yii::$app->params['currency'];
        return [
            'id' => 'ID',
            'ring_name' => '对戒名称',
            'ring_sn' => '对戒款号',
            'ring_images' => '商品图片',
            'qr_code' => '对戒二维码',
            'ring_salenum' => '对戒销量',
            'ring_style' => '对戒款式',
            'sale_price' => Yii::t('goods', '销售价')."({$currency})",
            'market_price' => Yii::t('goods', '市场价')."({$currency})",
            'cost_price' => Yii::t('goods', '成本价')."({$currency})",
            'status' => '上架状态',
            'sale_volume' => Yii::t('goods', '销量'),
            'virtual_volume' => Yii::t('goods', '虚拟销量'),
            'goods_clicks' => Yii::t('goods', '浏览量'),
            'virtual_clicks' => Yii::t('goods', '虚拟浏览量'),
            'ring_3ds' => '360°主图',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',

            'type_id' => '产品线ID',
            'style_id' => '款式ID',
            'goods_id' => '商品ID',
            'goods_storage' => '商品库存',
            'style_salepolicy' => '',


//ADD COLUMN `type_id` INT(10) UNSIGNED NULL DEFAULT '19' COMMENT '产品线ID',
//ADD COLUMN `style_id` INT(10) NULL DEFAULT NULL COMMENT '款式ID',
//ADD COLUMN `goods_id` INT(10) NULL DEFAULT NULL COMMENT '商品ID',
//ADD COLUMN `goods_storage` INT(10) UNSIGNED NULL DEFAULT '10000' COMMENT '商品库存',
//ADD COLUMN `goods_salepolicy` TEXT(65535) NULL DEFAULT NULL COMMENT '';
        ];
    }

    /**
     * 款式图库
     */
    public function parseRingImages()
    {
        if(is_array($this->ring_images)){
            $this->ring_images = implode(',',$this->ring_images);
        }
        return $this->ring_images;
    }


    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new RingLang();
    }

    public function getLangs()
    {
        return $this->hasMany(RingLang::class,['master_id'=>'id']);

    }

    /**
     * 关联语言一对一
     * @param string $languge
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        $query = $this->hasOne(RingLang::class, ['master_id'=>'id'])->alias('lang')->where(['lang.language' => Yii::$app->params['language']]);
        return $query;
    }

    public function getRelations()
    {
        return $this->hasMany(RingRelation::class,['ring_id'=>'id']);

    }


}
