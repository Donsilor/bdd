<?php

namespace common\models\statistics;

use common\models\order\OrderAccount;
use Yii;

/**
 * This is the model class for table "{{%statistics_style_view}}".
 *
 * @property int $style_id 款式ID
 * @property int $type_id 产品线
 * @property string $style_name 款式名称
 * @property string $platform
 * @property string $platform_group
 * @property string $name
 */
class StyleView extends \common\models\base\BaseModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%statistics_style_view}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['style_id', 'type_id'], 'integer'],
            [['style_name'], 'string', 'max' => 300],
            [['platform_group', 'name'], 'string', 'max' => 2],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'style_id' => '款式ID',
            'type_id' => '产品线',
            'style_name' => '款式名称',
            'platform_group' => 'Platform Group',
            'name' => 'Name',
        ];
    }

    public static function primaryKey()
    {
        return ['style_id','platform_group'];
    }

//    public function getOg()
//    {
//        $order = <<<DOM
//(SELECT `og`.`style_id`,COUNT(`og`.`style_id`) AS count,`o`.`order_from`
//FROM `order` `o`
//RIGHT JOIN `order_goods` AS `og` ON  `o`.`id`=`og`.`order_id`
//WHERE 1 GROUP BY `og`.`style_id`,`o`.`order_from`) AS og
//DOM;
//        return $this->hasOne($order, ['order_id'=>'id']);
//    }
}
