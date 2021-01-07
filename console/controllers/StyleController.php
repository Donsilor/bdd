<?php

namespace console\controllers;

use common\enums\AreaEnum;
use common\enums\StatusEnum;
use common\models\common\EmailLog;
use common\models\common\SmsLog;
use common\models\goods\Diamond;
use common\models\goods\Style;
use common\models\market\MarketCard;
use common\models\order\Order;
use services\market\CardService;
use services\order\OrderLogService;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use console\forms\CardForm;
use function GuzzleHttp\json_decode;

/**
 * 购物卡命令
 * Class CardController
 * @package console\controllers
 */
class StyleController extends Controller
{

    /**
     * 导出购物卡
     */
    public function actionStatus()
    {
        $areaEnum = AreaEnum::getMap();
        //请输要操作的地区[中国，香港，燠门，台湾，国外]，多个用逗号隔开
        $msg = "";
        foreach ($areaEnum as $id => $name) {
            $msg .= "{$name}：{$id}\r\n";
        }
        $msg .= "请输入地区对应的代码，多个用逗号隔开：";

        $areaName = \yii\helpers\BaseConsole::input($msg);
        $areaName = str_replace(['，', ' '], [',', ''], $areaName);
        $areaName = explode(',', $areaName);

        foreach ($areaName as $id) {
            if (!isset($areaEnum[$id])) {
                exit($id . " 不是一个正确的地区名称！");
            }
        }

        //请输入上下架状态[上架，下架]
        $statusArray = [
            'no' => '下架',
            'yes' => '上架',
        ];
        $status = \yii\helpers\BaseConsole::input("上架：yes\r\n下架：no\r\n请输入上下架状态：");

        if (!isset($statusArray[$status])) {
            exit($status . " 不是一个正确的状态代码！");
        }

        $status = ($status == 'yes' ? 1 : 0);

        //请输入操作的款号，多个用逗号隔开
        $styleSn = \yii\helpers\BaseConsole::input("请输入操作的款号，多个用逗号隔开：");

        $styleSn = str_replace(['，', ' '], [',', ''], $styleSn);
        $styleSn = explode(',', $styleSn);

        $styles = Style::find()->where(['style_sn' => $styleSn])->all();

        $count = count($styles);
        if (count($styleSn) != $count) {
            $styleSn2 = [];
            foreach ($styles as $style) {
                $styleSn2[] = $style->style_sn;
            }

            $diff = array_diff($styleSn, $styleSn2);

            if (empty($diff)) {
                exit("存在重复款号，你认真检查数据是否正确！");
            }

            exit(sprintf("[%s] 未找到！", implode(',', $diff)));
        }

        $trans = \Yii::$app->db->beginTransaction();
        try {
            Console::startProgress(0, $count);

            $progress = 0;

            foreach ($styles as $n => $style) {
                $time = time();

                //第5秒更新一次进度
                if($progress!= $time && $time%6==0) {
                    $progress = $time;
                    Console::updateProgress($n, $count);
                }

                $old_style_info = $style->toArray();

                $styleSalepolicy = json_decode($style->style_salepolicy, true);

                foreach ($styleSalepolicy as $key => $salepolicy) {
                    if (in_array($salepolicy['area_id'], $areaName)) {
                        $styleSalepolicy[$key]['status'] = $status;
                    }
                }

                $style->style_salepolicy = $styleSalepolicy;

                $style->save();

                //记录日志
                \Yii::$app->services->goods->recordGoodsLog($style, $old_style_info);

                //商品更新
                \Yii::$app->services->goods->syncStyleToGoods($style->id);
            }

            $trans->commit();
        } catch (\Exception $exception) {
            $trans->rollBack();

            throw $exception;
        }

        Console::updateProgress($count, $count);
        Console::endProgress();

        BaseConsole::output('操作完成');
    }

    /**
     * 导出购物卡
     */
    public function actionTwPrice()
    {
        //修改的地区
        $areaId = 4;

        //请输入操作的款号，多个用逗号隔开
        $typeId = \yii\helpers\BaseConsole::input("请输入操作产品线，多个用逗号隔开：");

        $typeId = str_replace(['，', ' '], [',', ''], $typeId);
        $typeId = explode(',', $typeId);

        $diamonds = [];
        if(in_array(15, $typeId)) {
            $diamonds = Diamond::find()->where(['status'=>1])->all();
            $typeId = array_merge(array_diff($typeId, [15]));
        }

        $styles = [];
        if(!empty($typeId)) {
            $styles = Style::find()->where(['type_id' => $typeId])->all();
        }

        $count = count($styles) + count($diamonds);

        $trans = \Yii::$app->db->beginTransaction();
        try {
            Console::startProgress(0, $count);

            $progress = 0;

            $n = 0;
            foreach ($styles as $style) {
                $time = time();

                //第5秒更新一次进度
                $n++;
                if($progress!= $time && $time%6==0) {
                    $progress = $time;
                    Console::updateProgress($n, $count);
                }

                $styleSalepolicy = json_decode($style->style_salepolicy, true);

                foreach ($styleSalepolicy as $key => $salepolicy) {
                    if ($salepolicy['area_id'] == $areaId) {
                        $styleSalepolicy[$key]['markup_value'] = 300;
                    }
                }

                $style->style_salepolicy = $styleSalepolicy;

                if(!$style->save()) {
                    var_dump($style->getErrors());
                    throw new \Exception($style->id . ' 失败，请重试！');
                }

                //商品更新
                \Yii::$app->services->goods->syncStyleToGoods($style->id);
            }

            foreach ($diamonds as $diamond) {
                $time = time();

                //第5秒更新一次进度
                $n++;
                if($progress!= $time && $time%6==0) {
                    $progress = $time;
                    Console::updateProgress($n, $count);
                }

                if(!empty($diamond->sale_policy)) {
                    $styleSalepolicy = json_decode($diamond->sale_policy, true);
                }
                else {
                    $styleSalepolicy = [
                        "1" => [
                            "area_id" => "1",
                            "area_name" => "中国",
                            "sale_price" => $diamond->sale_price,
                            "markup_rate" => "1",
                            "markup_value" => "0",
                            "status" => "1"
                        ],
                        "2" => [
                            "area_id" => "2",
                            "area_name" => "香港",
                            "sale_price" => $diamond->sale_price,
                            "markup_rate" => "1",
                            "markup_value" => "0",
                            "status" => "1"
                        ],
                        "3" => [
                            "area_id" => "3",
                            "area_name" => "澳门",
                            "sale_price" => $diamond->sale_price,
                            "markup_rate" => "1",
                            "markup_value" => "0",
                            "status" => "1"
                        ],
                        "4" => [
                            "area_id" => "4",
                            "area_name" => "台湾",
                            "sale_price" => $diamond->sale_price,
                            "markup_rate" => "1",
                            "markup_value" => "0",
                            "status" => "1"
                        ],
                        "99" => [
                            "area_id" => "99",
                            "area_name" => "国外",
                            "sale_price" => $diamond->sale_price,
                            "markup_rate" => "1",
                            "markup_value" => "0",
                            "status" => "1"
                        ]
                    ];
                }

                foreach ($styleSalepolicy as $key => $salepolicy) {
                    if ($salepolicy['area_id'] == $areaId) {
                        $styleSalepolicy[$key]['markup_value'] = 300;
                    }
                }

                $diamond->sale_policy = $styleSalepolicy;

                $diamond->cut = (string)$diamond->cut;
                $diamond->color = (string)$diamond->color;
                $diamond->symmetry = (string)$diamond->symmetry;
                $diamond->polish = (string)$diamond->polish;
                $diamond->fluorescence = (string)$diamond->fluorescence;
                $diamond->clarity = (string)$diamond->clarity;
                $diamond->depth_lv = (string)$diamond->depth_lv;
                $diamond->table_lv = (string)$diamond->table_lv;

                if(!$diamond->save()) {
                    var_dump($diamond->getErrors());
                    throw new \Exception($diamond->id . ' 失败，请重试！');
                }

                //商品更新
                \Yii::$app->services->diamond->syncDiamondToGoods($diamond->id);
            }

            $trans->commit();
        } catch (\Exception $exception) {
            $trans->rollBack();

            throw $exception;
        }

        Console::updateProgress($count, $count);
        Console::endProgress();

        BaseConsole::output('操作完成');
    }
}