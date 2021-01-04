<?php


namespace services\statistics;


use common\components\Service;
use common\models\statistics\OrderSale;
use common\models\statistics\OrderView;
use yii\web\UnprocessableEntityHttpException;

class OrderSaleService extends Service
{
    //生成统计数据(检查更新数据,当天，数天，当月，数月)
    public function generate()
    {

    }

    //获取数据（开始时间，结束时间）
    public function getData($start_time, $end_time)
    {
        $where = [];
        $list = OrderView::find()->where($where)->asArray()->all();

        $result = [];
        foreach ($list as $item) {
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $result[] = $this->getOneData($item);
        }
        return $result;
    }

    public function getOneData($param)
    {
        return null;
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