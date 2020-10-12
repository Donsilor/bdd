<?php

namespace console\controllers;

use common\enums\AreaEnum;
use common\enums\StatusEnum;
use common\models\common\EmailLog;
use common\models\common\SmsLog;
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
}