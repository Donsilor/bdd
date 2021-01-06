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

        //获取上次缓存更新时间
        $lastTime = $this->getLastTime();

        //更新周期 24小时
        if ($lastTime + 3600 * 24 > time()) {
            return null;
        }

        //删除缓存
        $this->delCache();

        //获取最后时间
        $start_time = $this->getLastTime();

        //当天时间
        $time = strtotime(date('Y-m-d', time()));

        //生成日数据
        for ($start_time = $start_time + 86400; $start_time <= $time; $start_time += 86400) {
            $end_time = $start_time + 86400;

            $list = $this->getData($start_time, $end_time);

            foreach ($list as $item) {
                $item['type'] = 1;
                $item['datetime'] = $start_time;
                $item['is_cache'] = ($start_time > $time - 86400 * 31) ? 1 : 0;//缓存最近31天的数据，

                $this->insertData($item);
            }
        }

        //获取最后时间
        $start_time = $this->getLastTime(2);

        //生成月数据
        while (True) {
            $start_time = strtotime("+1month", $start_time);
            $end_time = strtotime("+1month", $start_time);

            $list = $this->getData($start_time, $end_time);

            foreach ($list as $item) {
                $item['type'] = 2;
                $item['datetime'] = $start_time;
                $item['is_cache'] = ($end_time > $time - 86400 * 31) ? 1 : 0;//缓存最近31天的数据，

                $this->insertData($item);
            }

            if($end_time > $time) {
                break;
            }
        }

    }

    private function getLastTime($type = 1)
    {
        $result = OrderSale::find()->select('datetime')->where(['type' => $type])->orderBy('id desc')->one();
        return $result['datetime'] ?? strtotime('2020-01-01 00:00:00');
    }

    //获取数据（开始时间，结束时间）
    private function getData($start_time, $end_time)
    {
        $where = [];
        $where['status'] = 2;
        $list = OrderView::find()->where($where)->all();

        $result = [];
        foreach ($list as $item) {
            $result[] = $this->getOneData($item, $start_time, $end_time);
        }
        return $result;
    }

    //获取一个数据
    private function getOneData($item, $start_time, $end_time)
    {
        $sale_amount = 0;
        $type_sale_amount = [];

        $list = $item->getOrderProductTypeGroupDataBase($start_time, $end_time);
        foreach ($list as $data) {
            $sale_amount += $data['sum'];

            if (!isset($type_sale_amount[$data['id']])) {
                $type_sale_amount[$data['id']] = 0;
            }

            $type_sale_amount[$data['id']] += $data['sum'];
        }

        return [
            'platform_group' => $item->platform_group,
            'platform_id' => $item->platform_id,
            'sale_amount' => $sale_amount,
            'type_sale_amount' => $type_sale_amount,
        ];
    }

    //删除缓存数据
    private function delCache()
    {
        OrderSale::deleteAll(['is_cache' => 1]);
    }

    /**
     * 写入数据
     * @param $data
     * @throws UnprocessableEntityHttpException
     */
    private function insertData($data)
    {
        $orderSale = new OrderSale($data);
        $result = $orderSale->save();

        if (!$result) {
            throw new UnprocessableEntityHttpException($this->getError($orderSale));
        }
    }
}