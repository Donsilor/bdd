<?php

namespace console\controllers;

use common\enums\AreaEnum;
use common\enums\StatusEnum;
use common\models\common\EmailLog;
use common\models\common\SmsLog;
use common\models\market\MarketCard;
use common\models\order\Order;
use services\market\CardService;
use services\order\OrderLogService;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use console\forms\CardForm;

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
        //请输要操作的地区[中国，香港，燠门，台湾，国外]，多个用逗号隔开
        $areaName = \yii\helpers\BaseConsole::input(sprintf("请输要操作的地区[%s]，多个用逗号隔开：", implode(',',AreaEnum::getMap())));

        //请输入上下架状态[上架，下架]
        $status = \yii\helpers\BaseConsole::input("请输入上下架状态[上架，下架]：");

        //请输入操作的款号，多个用逗号隔开
        $styleSn = \yii\helpers\BaseConsole::input("请输入操作的款号，多个用逗号隔开：");

//        $allArgs = func_get_args();
//        $batchs = implode(',', $allArgs);
//
//        $batchs = str_replace(['，', ' '], [',', ''], $batchs);
//        $batchs = explode(',', $batchs);
//
//        $batchList = [];
//        foreach ($batchs as $batch) {
//            if(empty($batch)) {
//                continue;
//            }
//            $batchList[] = $batch;
//            BaseConsole::output($batch.'：'.MarketCard::find()->where(['batch'=>$batch])->count('id').' 条记录');
//        }
//
//        if(!$this->confirm("请确定批次号记录条数是否正确?")) {
//            exit("退出");
//        }
//
//        $fileName = \yii\helpers\BaseConsole::input("请输入导出文件名：");
//
//        $cardList = MarketCard::find()->where(['batch'=>$batchList])->all();
//        $count = count($cardList);
//        Console::startProgress(0, $count);
//
//        $progress = 0;
//
//        foreach ($cardList as $n => $card) {
//            $time = time();
//
//            //第5秒更新一次进度
//            if($progress!= $time && $time%6==0) {
//                $progress = $time;
//                Console::updateProgress($n, $count);
//            }
//
//            if(empty($card->password)) {
//                $password = CardService::generatePw();
//                $card->setPassword($password);
//                if(!$card->save()) {
//                    throw new \Exception($card->sn . ' 生成卡密失败，请重试！');
//                }
//            }
//            else {
//                $password = $card->getPassword();
//            }
//
//            $cardInfo = sprintf('%s,%s,%s',$card['batch'], $card['sn'], $password);
//
//            file_put_contents($fileName, $cardInfo . "\r\n",FILE_APPEND);
//        }
//        Console::updateProgress($count, $count);
//        Console::endProgress();
//
//        BaseConsole::output('导出完成');
    }
}