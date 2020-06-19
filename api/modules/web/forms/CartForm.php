<?php

namespace api\modules\web\forms;


use yii\base\Model;

/**
 * Class CartForm
 * @package api\modules\web\forms
 */
class CartForm extends Model
{
    public $add_type;
    public $goods_id;//商品ID
    public $goods_type;//商品类型(产品线ID)
    public $goods_num;//商品数量
    public $group_type;
    public $group_id;
    public $createTime;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
             [['goods_id','goods_type','goods_num','createTime'], 'required'],
             [['goods_id','goods_type','goods_num','group_type','group_id','createTime'], 'number'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
                'goods_id' => 'goods_id',
                'goods_type' => 'goods_type',
                'goods_num' => 'goods_num', 
                'group_type' => 'group_type',
                'group_id' => 'group_id',
                'createTime' => 'createTime'
        ];
    }

    public function getSign()
    {
        return md5(sprintf('ip:[%s],createTime:[%s],goods_type:[%s],goods_id:[%s]', \Yii::$app->request->userIP, $this->createTime, $this->goods_type, $this->goods_id));
    }
}
