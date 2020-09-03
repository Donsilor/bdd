<?php


namespace api\modules\web\forms;

use common\models\order\Order;
use Yii;
use common\models\order\OrderComment;
use yii\validators\ImageValidator;
use yii\validators\UrlValidator;

class OrderCommentForm extends OrderComment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'type_id', 'style_id', 'status', 'admin_id', 'is_import', 'member_id', 'platform', 'ip_area_id', 'created_at', 'updated_at'], 'integer'],
            [['platform', 'member_id', 'order_id', 'type_id', 'style_id', 'grade', 'content'], 'required'],
            [['ip', 'ip_location', 'remark', 'content'], 'string', 'max' => 255],
            [['content'], 'safe'],
            ['order_id', 'validateOrderId'],
            [['style_id', 'type_id'], 'validateStyleId'],
            ['images', 'validateImages'],
            ['grade', 'in', 'range' => [0,1,2,3,4,5,]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'order_id' => Yii::t('app', '订单ID'),
            'type_id' => Yii::t('app', '产品线ID'),
            'style_id' => Yii::t('app', '款式ID'),
            'grade' => Yii::t('app', '评价等级'),
            'content' => Yii::t('app', '评价内容'),
            'images' => Yii::t('app', '评价图片'),
            'member_id' => Yii::t('app', '客户ID'),
            'platform' => Yii::t('app', '平台'),
//            'is_import' => Yii::t('app', '是否导入'),
//            'ip' => Yii::t('app', 'Ip'),
//            'ip_area_id' => Yii::t('app', 'Ip Area ID'),
//            'ip_location' => Yii::t('app', 'Ip Location'),
        ];
    }

    public function validateOrderId($attribute)
    {
        $where = [];
        $where['id'] = $this->getAttribute($attribute);
        $where['member_id'] = $this->member_id;
        if($this->hasErrors()) {
            return;
        }

        if(!Order::findOne($where)) {
            $this->addError($attribute, '订单ID错误');
        }
    }

    public function validateStyleId($attribute)
    {
        if($this->hasErrors()) {
            return;
        }

        $goods = \Yii::$app->services->goods->getGoodsInfo($this->style_id, $this->type_id);

        if(!($goods && $goods['type_id']==$this->type_id)) {
            $this->addError($attribute, '错品信息错误');
        }
    }

    public function validateImages($attribute)
    {
        $values = $this->getAttribute($attribute);
        if($this->hasErrors() || empty($value)) {
            return;
        }

//        $values = explode(',', $values);

        if(!is_array($values)) {
            $this->addError($attribute, '不是数组');
        }

        $validator = new UrlValidator();

        foreach ($values as $value) {
            if(!$validator->validate($value)) {
                $this->addError($attribute, '验证失败');
            }
        }
    }

    public function beforeSave($insert)
    {
        if(!$insert) {
            return parent::beforeSave($insert); // TODO: Change the autogenerated stub
        }

        $this->images = implode(",", $this->images);

//        $this->ip = \Yii::$app->request->userIP;  //用户ip
//        list($this->ip_area_id,$this->ip_location) = \Yii::$app->ipLocation->getLocation($this->ip);

       return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }
}