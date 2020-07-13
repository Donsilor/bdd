<?php

namespace console\controllers;

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
class CardController extends Controller
{

    public function actionTest()
    {
        $order = \Yii::$app->logistics->kd100('SF1074705590534', '顺丰');
        echo $order->getCode(); // 状态码
        echo("\r\n");
        echo $order->getMsg(); // 状态信息
        echo("\r\n");
        echo $order->getCompany(); // 物流公司简称
        echo("\r\n");
        echo $order->getNo(); // 物流单号
        echo("\r\n");
        echo $order->getStatus(); // 当前物流单状态
        echo("\r\n");
    }

    /**
     * 导入购物卡
     */
    public function actionImport($fileName)
    {
        if(!file_exists($fileName)) {
            exit("文件是不存在的");
        }

        $handle = fopen($fileName, 'r');

        if(!$handle) {
            exit("读取文件失败");
        }

        $trans = \Yii::$app->db->beginTransaction();

        try {

            $cards = [];

            $i = 0;
            while (($data = fgetcsv($handle)) != false) {
                $i++;

                if($i==1) {
                    $title1 = '批次,卡号,密码,金额,开始时间,结束时间,使用范围（中文，多个用“|”隔开，同一批次的使用范围必需一至）';
                    $title2 = implode(',', $data);

                    if($title1!=$title2) {
                        BaseConsole::output('请在第一行使用标准的标题名称：'.$title1);
                        exit('现在第一行的标题是：'.$title2);
                    }

                    continue;
                }

                $cardForm = new CardForm();

                $cardForm->batch = $data[0];
                $cardForm->sn = $data[1];
                $cardForm->password = $data[2];
                $cardForm->amount = $data[3];
                $cardForm->start_time = $data[4];
                $cardForm->end_time = $data[5];
                $cardForm->goods_types = $data[6];

                if(!$cardForm->validate()) {
                    $error = sprintf('第[%d]行：' . \Yii::$app->debris->analyErr($cardForm->getFirstErrors()), $i);
                    throw new \Exception($error);
                }

                $cards[] = $cardForm->toArray();
            }

            $count = \Yii::$app->services->card->importCards($cards);

            $trans->commit();

            exit('导入成功：'.$count.' 条');

        } catch (\Exception $exception) {
            $trans->rollBack();

            BaseConsole::output($exception->getMessage());
            exit('error');
        }
    }

    /**
     * 导出购物卡
     */
    public function actionExport()
    {
        $allArgs = func_get_args();
        $batchs = implode(',', $allArgs);

        $batchs = str_replace(['，', ' '], [',', ''], $batchs);
        $batchs = explode(',', $batchs);

        $batchList = [];
        foreach ($batchs as $batch) {
            if(empty($batch)) {
                continue;
            }
            $batchList[] = $batch;
            BaseConsole::output($batch.'：'.MarketCard::find()->where(['batch'=>$batch])->count('id').' 条记录');
        }

        if(!$this->confirm("请确定批次号记录条数是否正确?")) {
            exit("退出");
        }

        $fileName = \yii\helpers\BaseConsole::input("请输入导出文件名：");

        $cardList = MarketCard::find()->where(['batch'=>$batchList])->all();
        $count = count($cardList);
        Console::startProgress(0, $count);

        $progress = 0;

        foreach ($cardList as $n => $card) {
            $time = time();

            //第5秒更新一次进度
            if($progress!= $time && $time%6==0) {
                $progress = $time;
                Console::updateProgress($n, $count);
            }

            if(empty($card->password)) {
                $password = CardService::generatePw();
                $card->setPassword($password);
                if(!$card->save()) {
                    throw new \Exception($card->sn . ' 生成卡密失败，请重试！');
                }
            }
            else {
                $password = $card->getPassword();
            }

            $cardInfo = sprintf('%s,%s,%s',$card['batch'], $card['sn'], $password);

            file_put_contents($fileName, $cardInfo . "\r\n",FILE_APPEND);
        }
        Console::updateProgress($count, $count);
        Console::endProgress();

        BaseConsole::output('导出完成');
    }
}