<?php


namespace services\statistics;


use common\components\Service;
use common\models\statistics\OrderSale;
use yii\web\UnprocessableEntityHttpException;

class OrderSaleService extends Service
{
    //生成统计数据(检查更新数据,当天，数天，当月，数月)
    public function gen()
    {

    }

    //获取数据（开始时间，结束时间）
    public function getData($start_time, $end_time)
    {

    }

    //删除缓存数据
    public function delCache()
    {
        OrderSale::deleteAll(['is_cache'=>1]);
    }

    /**
     * 写入数据
     * @param $data
     * @throws UnprocessableEntityHttpException
     */
    public function insertData($data)
    {
        $orderSale = new OrderSale($data);
        $result = $orderSale->save();

        if(!$result) {
            throw new UnprocessableEntityHttpException($this->getError($orderSale));
        }
    }
}