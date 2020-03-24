<?php

namespace common\models\market;

use Yii;

/**
 * This is the model class for table "market_specials_area".
 *
 * @property int $id 活动Id
 * @property int $master_id 商户ID
 * @property int $area_id 所属地区
 * @property array $banner_image Banner图片
 * @property string $label_image 标签图片
 * @property int $status 启用状态;[0:禁用;1启用]
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 */
class MarketSpecialsArea extends \common\models\base\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'market_specials_area';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['master_id', 'area_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['area_id'], 'required'],
            [['banner_image'], 'safe'],
            [['label_image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '活动Id',
            'master_id' => '商户ID',
            'area_id' => '所属地区',
            'banner_image' => 'Banner图片',
            'label_image' => '标签图片',
            'status' => '启用状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }
}
