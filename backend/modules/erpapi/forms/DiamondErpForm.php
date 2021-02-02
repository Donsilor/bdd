<?php

namespace backend\modules\erpapi\forms;


use common\enums\AttrIdEnum;
use common\enums\LanguageEnum;
use common\models\goods\Diamond;
use common\models\goods\DiamondLang;
use yii\web\UnprocessableEntityHttpException;
use \common\enums\DiamondEnum;

class DiamondErpForm extends Diamond
{
    public function beforeValidate()
    {
        $cert_type_set = false;
        foreach (DiamondEnum::getCertTypeList() as $key => $typeOption) {
            if ($this->cert_type == $typeOption) {
                $this->cert_type = (string)$key;

                $cert_type_set = true;
            }
        }
        if(!$cert_type_set) {
            $this->cert_type = "0";
        }

        $clarity_set = false;
        foreach (DiamondEnum::getClarityList() as $key => $clarityOption) {
            if ($this->clarity == $clarityOption) {
                $this->clarity = (string)$key;

                $clarity_set = true;
            }
        }
        if(!$clarity_set) {
            $this->clarity = "0";
        }

        $cut_set = false;
        foreach (DiamondEnum::getCutList() as $key => $cutOption) {
            if ($this->cut == $cutOption) {
                $this->cut = (string)$key;

                $cut_set = true;
            }
        }
        if(!$cut_set) {
            $this->cut = "0";
        }

        $color_set = false;
        foreach (DiamondEnum::getColorList() as $key => $colorOption) {
            if ($this->color == $colorOption) {
                $this->color = (string)$key;
                $color_set = true;
            }
        }
        if(!$color_set) {
            $this->color = "0";
        }

        $shape_set = false;
        foreach (DiamondEnum::getShapeList() as $key => $shapeOption) {
            if ($this->shape == $shapeOption) {
                $this->shape = (int)$key;
                $shape_set = true;
            }
        }
        if(!$shape_set) {
            $this->shape = 0;
        }

        $symmetry_set = false;
        foreach (DiamondEnum::getSymmetryList() as $key => $symmetryOption) {
            if ($this->symmetry == $symmetryOption) {
                $this->symmetry = (string)$key;
                $symmetry_set = true;
            }
        }
        if(!$symmetry_set) {
            $this->symmetry = "0";
        }

        $polish_set = false;
        foreach (DiamondEnum::getPolishList() as $key => $polishOption) {
            if ($this->polish == $polishOption) {
                $this->polish = (string)$key;
                $polish_set = true;
            }
        }
        if(!$polish_set) {
            $this->polish = "0";
        }

        $fluorescence_set = false;
        foreach (DiamondEnum::getFluorescenceList() as $key => $fluorescenceOption) {
            if ($this->fluorescence == $fluorescenceOption) {
                $this->fluorescence = (string)$key;
                $fluorescence_set = true;
            }
        }
        if(!$fluorescence_set) {
            $this->fluorescence = "0";
        }

        $this->source_discount = 0;

        $this->updated_at = time();
        $this->user_id = \Yii::$app->getUser()->identity->getId();

        if (empty($this->created_at)) {
            $this->created_at = time();

            $this->sale_price = $this->sale_price != 0 ? $this->sale_price : 0.01;
            $this->market_price = $this->market_price != 0 ? $this->market_price : 0.01;
            $this->cost_price = $this->market_price != 0 ? $this->market_price : 0.01;

            $this->sale_policy = [
                "1" => [
                    "area_id" => "1",
                    "area_name" => "中国",
                    "sale_price" => $this->sale_price,
                    "markup_rate" => "1",
                    "markup_value" => "0",
                    "status" => "{$this->status}"
                ],
                "2" => [
                    "area_id" => "2",
                    "area_name" => "香港",
                    "sale_price" => $this->sale_price,
                    "markup_rate" => "1",
                    "markup_value" => "0",
                    "status" => "{$this->status}"
                ],
                "3" => [
                    "area_id" => "3",
                    "area_name" => "澳门",
                    "sale_price" => $this->sale_price,
                    "markup_rate" => "1",
                    "markup_value" => "0",
                    "status" => "{$this->status}"
                ],
                "4" => [
                    "area_id" => "4",
                    "area_name" => "台湾",
                    "sale_price" => $this->sale_price,
                    "markup_rate" => "1",
                    "markup_value" => "0",
                    "status" => "{$this->status}"
                ],
                "99" => [
                    "area_id" => "99",
                    "area_name" => "国外",
                    "sale_price" => $this->sale_price,
                    "markup_rate" => "1",
                    "markup_value" => "0",
                    "status" => "{$this->status}"
                ]
            ];
        }

        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub

        $langs = DiamondLang::find()->where(['master_id' => $this->id])->all();

        if(count($langs) === 0) {
            foreach (LanguageEnum::getMap() as $lang_key => $item) {
                $langModel = $this->langModel();
                $langModel->setAttributes([
                    'goods_name' => $this->getErpTitle($lang_key),
                    'goods_desc' => '',
                    'goods_body' => '',
                    'mobile_body' => '',
                    'meta_title' => '',
                    'meta_word' => '',
                    'meta_desc' => '',
                ]);

                $langModel->master_id = $this->id;
                $langModel->language = $lang_key;

                if (false === $langModel->save()) {
                    throw new UnprocessableEntityHttpException('langModel 保存失败2');//$this->getError($langModel));
                }
            }
        }
//        else {
//            foreach ($langs as $langModel) {
//                $langModel['goods_name'] = $this->getErpTitle($langModel->language);
//                if (false === $langModel->save()) {
//                    throw new UnprocessableEntityHttpException('langModel 保存失败1');//$this->getError($langModel));
//                }
//            }
//        }
    }

    private function getErpTitle($language)
    {
//        【BDD简体名称】0.4ct 圆形 H色 VS2净度 GIA（同ERP原始名称）
//        【BDD繁体名称】0.4ct 圓形 H色 VS2淨度 GIA
//        【BDD英文名称】0.4ct Round H/Colour VS2/Clarity GIA
        switch ($language) {
            case LanguageEnum::EN_US :
                $tmp = "%sct %s %s/Colour %s/Clarity %s";
                break;
            case LanguageEnum::ZH_HK :
                $tmp = "%sct %s %s色 %s淨度 %s";
                break;
            default :
                $tmp = "%sct %s %s色 %s净度 %s";
        }


        $shapeList = \Yii::$app->services->goodsAttribute->getValuesByAttrId(AttrIdEnum::SHAPE, 1, $language);
        $colorList =  \Yii::$app->services->goodsAttribute->getValuesByAttrId(AttrIdEnum::COLOR, 1, $language);
        $clarityList = \Yii::$app->services->goodsAttribute->getValuesByAttrId(AttrIdEnum::CLARITY, 1, $language);
        $certTypeList = \Yii::$app->services->goodsAttribute->getValuesByAttrId(AttrIdEnum::CERT_TYPE, 1, $language);

        return sprintf($tmp,
            $this->carat,
            $shapeList[$this->shape]??'',
            $colorList[$this->color]??'',
            $clarityList[$this->clarity]??'',
            $certTypeList[$this->cert_type]??''
        );
    }
}