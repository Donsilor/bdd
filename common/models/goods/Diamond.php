<?php

namespace common\models\goods;

use Yii;

/**
 * This is the model class for table "{{%goods_diamond}}".
 *
 * @property int $id
 * @property string $goods_sn 商品编码
 * @property string $goods_image 主图
 * @property int $goods_num 商品数量
 * @property string $cert_type 证书类型
 * @property string $cert_id 证书号
 * @property string $sale_policy 销售政策
 * @property string $market_price 市场价
 * @property string $sale_price 销售价
 * @property string $cost_price
 * @property double $carat 石重
 * @property string $clarity 净度
 * @property string $cut 切工
 * @property string $color 颜色
 * @property int $shape 形状
 * @property string $depth_lv 深度比
 * @property string $table_lv 台宽比
 * @property string $symmetry 对称
 * @property string $polish 抛光
 * @property string $fluorescence 荧光
 * @property int $source_id 货品来源 0手动录制
 * @property string $source_discount 来源折扣
 * @property int $is_stock 库存类型 1现货 0期货
 * @property int $status 状态：1上架 0下架
 * @property int $created_at
 * @property int $updated_at
 * @property int $hk_status 地区状态
 * @property int $cn_status 地区状态
 * @property int $us_status 地区状态
 */
class Diamond extends \yii\db\ActiveRecord
{

    public $hk_status;
    public $cn_status;
    public $us_status;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_diamond}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','goods_num', 'shape', 'source_id', 'is_stock', 'status', 'created_at', 'updated_at','onsale_time','type_id','goods_id','sale_volume','virtual_volume','virtual_clicks','goods_clicks', 'hk_status', 'cn_status', 'us_status'], 'integer'],
            [['sale_price','source_id','shape','goods_num','goods_sn', 'carat', 'clarity', 'cut', 'color', 'symmetry', 'polish', 'fluorescence'], 'required'],
            [['goods_num','market_price', 'sale_price', 'cost_price', 'carat', 'source_discount','length','width','aspect_ratio'], 'number'],
            ['sale_price','compare','compareValue' => 0, 'operator' => '>'],
            ['market_price','compare','compareValue' => 0, 'operator' => '>'],
            ['cost_price','compare','compareValue' => 0, 'operator' => '>'],
            ['goods_num','compare','compareValue' => 0, 'operator' => '>'],
            ['market_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            ['sale_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            ['cost_price','compare','compareValue' => 1000000000, 'operator' => '<'],
            [['goods_sn'], 'string', 'max' => 60],
            [['goods_image'], 'string', 'max' => 100],
            [['cert_type', 'cut', 'color', 'symmetry', 'polish', 'fluorescence'], 'string', 'max' => 10],
            [['cert_id'], 'string', 'max' => 30],
            [['clarity'], 'string', 'max' => 40],
            [['depth_lv', 'table_lv'], 'string', 'max' => 20],
            [['cert_id'], 'unique'],
            [['parame_images'],'parseParameImages'],
            [['sale_services'],'parseSaleServices'],
            [['sale_policy'],'parseSalePolicy'],//销售政策
            [['goods_name','language'],'safe'],
            [['cert_type', 'cert_id'], 'unique', 'targetAttribute' => ['cert_type', 'cert_id']],
        ];
    }

    /**
 * 款式图库
 */
    public function parseParameImages()
    {
        if(is_array($this->parame_images)){
            $this->parame_images = implode(',',$this->parame_images);
        }
        return $this->parame_images;
    }

    /**
     * 售后服务
     */
    public function parseSaleServices()
    {
        if(is_array($this->sale_services)){
            $this->sale_services = implode(',',$this->sale_services);
        }
        return $this->sale_services;
    }

    /**
     * 销售政策（地区价格）
     */
    public function parseSalePolicy()
    {
        if(is_array($this->sale_policy)){
            $this->sale_policy = json_encode($this->sale_policy);
        }
        return $this->sale_policy;
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $currency = \Yii::$app->params['currency'];
        return [
            'id' => "ID",
            'goods_sn' => '商品编码',
            'goods_image' => '主图',
            'goods_num' => '库存',
            'cert_type' => '证书类型',
            'cert_id' => '证书号',
            'sale_price' => Yii::t('goods', '销售价')."({$currency})",
            'market_price' => Yii::t('goods', '市场价')."({$currency})",
            'cost_price' => Yii::t('goods', '成本价')."({$currency})",
            'carat' => '石重',
            'clarity' => '净度',
            'cut' => '切工',
            'color' => '颜色',
            'shape' => '形状',
            'depth_lv' => '切割深度(%)',
            'table_lv' => '台宽(%)',            
            'symmetry' => '对称',
            'polish' => '抛光',
            'stone_floor' => '石底层',
            'length' => '长度',
            'width' => '宽度',
            'aspect_ratio' => '长宽比(%)',
            'sale_policy' =>'销售政策',
            'sale_services' => '售后服务',
            'goods_3ds' => '360°主图',
            'parame_images' => '参数示意图',
            'goods_gia_image' => 'GIA证书图片',
            'fluorescence' => '荧光',
            'source_id' => '货品来源 ',
            'source_discount' => '来源折扣',
            'is_stock' => '库存类型',
            'onsale_time' => '上架时间',
            'sale_volume' => Yii::t('goods', '销量'),
            'virtual_volume' => Yii::t('goods', '虚拟销量'),
            'goods_clicks' => Yii::t('goods', '浏览量'),
            'virtual_clicks' => Yii::t('goods', '虚拟浏览量'),
            'status' => '上架状态',
            'created_at' => Yii::t('common', '创建时间'),
            'updated_at' => Yii::t('common', '更新时间'),

            'hk_status' => '香港上架',
            'cn_status' => '大陆上架',
            'us_status' => '美国上架',
        ];
    }


    /**
     * 语言扩展表
     * @return \common\models\goods\AttributeLang
     */
    public function langModel()
    {
        return new DiamondLang();
    }

    public function getLangs()
    {
        return $this->hasMany(DiamondLang::class,['master_id'=>'id']);

    }

    /**
     * 关联语言一对一
     * @param string $languge
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        $query = $this->hasOne(DiamondLang::class, ['master_id'=>'id'])->alias('lang')->where(['lang.language' => Yii::$app->params['language']]);
        return $query;
    }
}
