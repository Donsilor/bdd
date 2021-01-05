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

        //更新周期 2小时
        if ($lastTime + 3600 * 2 > time()) {
            return null;
        }

        //删除缓存
        $this->delCache();

        //获取最后时间
        $lastTime = $this->getLastTime();

        //当天时间
        $time = strtotime(date('Y-m-d', time()));

        //生成日数据
        for ($i = $lastTime + 86400; $i <= $time; $i += 86400) {

        }

        //生成月数据

    }

    private function getLastTime()
    {
        $result = OrderSale::find()->select('datetime')->orderBy('id desc')->one();
        return $result['datetime'] ?? date('Y-m-d H:i:s', 0);
    }

    //获取数据（开始时间，结束时间）
    public function getData($start_time, $end_time)
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
    public function getOneData($item, $start_time, $end_time)
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
    public function delCache()
    {
        OrderSale::deleteAll(['is_cache' => 1]);
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

        if (!$result) {
            throw new UnprocessableEntityHttpException($this->getError($orderSale));
        }
    }
}