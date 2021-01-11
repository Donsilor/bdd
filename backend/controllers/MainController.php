<?php

namespace backend\controllers;

use common\enums\OrderFromEnum;
use common\models\statistics\OrderSale;
use Yii;
use backend\forms\ClearCache;
use function Clue\StreamFilter\fun;

/**
 * 主控制器
 *
 * Class MainController
 * @package backend\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class MainController extends BaseController
{
    /**
     * 系统首页
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->renderPartial($this->action->id, [
        ]);
    }

    /**
     * 子框架默认主页
     *
     * @return string
     */
    public function actionSystem()
    {
        //刷新列表
        \Yii::$app->services->OrderSale->generate();

        $typeList = Yii::$app->services->goodsType->getTypeList();

        $type = Yii::$app->request->get('type', 2);
        $where = [];
        $where['type'] = $type;
        $date = OrderSale::find()->where($where)->all();

        $list = [];
        foreach ($date as $item) {
            if(!isset($list[$item['datetime']])) {
                $list[$item['datetime']] = [
                    'datetime' => date($type == 2 ? "Y-m" : "Y-m-d", $item['datetime']),

                    'name_all' => '全部站点',
                    'sale_amount_all' => 0,
                    'type_sale_amount_all' => [],

                    'name_hk' => '香港',
                    'sale_amount_hk' => 0,
                    'type_sale_amount_hk' => [],

                    'name_cn' => '大陆',
                    'sale_amount_cn' => 0,
                    'type_sale_amount_cn' => [],

                    'name_tw' => '台湾',
                    'sale_amount_tw' => 0,
                    'type_sale_amount_tw' => [],

                    'name_us' => '美国',
                    'sale_amount_us' => 0,
                    'type_sale_amount_us' => [],
                ];
            }

            $tmp = &$list[$item['datetime']];

            $tmp['sale_amount_all'] = bcadd($tmp['sale_amount_all'], $item['sale_amount'], 2);

            if(isset($item['type_sale_amount']) && is_array($item['type_sale_amount'])) {

                foreach ($item['type_sale_amount'] as $key => $value) {
                    $key = $typeList[$key]??null;
                    if(!isset($tmp['type_sale_amount_all'][$key])) {
                        $tmp['type_sale_amount_all'][$key] = 0;
                    }

                    $tmp['type_sale_amount_all'][$key] = bcadd($tmp['type_sale_amount_all'][$key], $value, 2);
                }
            }

            if($item['platform_group'] == OrderFromEnum::GROUP_HK) {
                $tmp['sale_amount_hk'] = bcadd($tmp['sale_amount_hk'], $item['sale_amount'], 2);

                if(isset($item['type_sale_amount']) && is_array($item['type_sale_amount'])) {
                    foreach ($item['type_sale_amount'] as $key => $value) {
                        $key = $typeList[$key]??null;
                        if(!isset($tmp['type_sale_amount_hk'][$key])) {
                            $tmp['type_sale_amount_hk'][$key] = 0;
                        }

                        $tmp['type_sale_amount_hk'][$key] = bcadd($tmp['type_sale_amount_hk'][$key], $value, 2);
                    }
                }
            }

            if($item['platform_group'] == OrderFromEnum::GROUP_CN) {
                $tmp['sale_amount_cn'] = bcadd($tmp['sale_amount_cn'], $item['sale_amount'], 2);

                if(isset($item['type_sale_amount']) && is_array($item['type_sale_amount'])) {
                    foreach ($item['type_sale_amount'] as $key => $value) {
                        $key = $typeList[$key]??null;
                        if(!isset($tmp['type_sale_amount_cn'][$key])) {
                            $tmp['type_sale_amount_cn'][$key] = 0;
                        }

                        $tmp['type_sale_amount_cn'][$key] = bcadd($tmp['type_sale_amount_cn'][$key], $value, 2);
                    }
                }
            }

            if($item['platform_group'] == OrderFromEnum::GROUP_TW) {
                $tmp['sale_amount_tw'] = bcadd($tmp['sale_amount_tw'], $item['sale_amount'], 2);

                if(isset($item['type_sale_amount']) && is_array($item['type_sale_amount'])) {
                    foreach ($item['type_sale_amount'] as $key => $value) {
                        $key = $typeList[$key]??null;
                        if(!isset($tmp['type_sale_amount_tw'][$key])) {
                            $tmp['type_sale_amount_tw'][$key] = 0;
                        }

                        $tmp['type_sale_amount_tw'][$key] = bcadd($tmp['type_sale_amount_tw'][$key], $value, 2);
                    }
                }
            }

            if($item['platform_group'] == OrderFromEnum::GROUP_US) {
                $tmp['sale_amount_us'] = bcadd($tmp['sale_amount_us'], $item['sale_amount'], 2);

                if(isset($item['type_sale_amount']) && is_array($item['type_sale_amount'])) {
                    foreach ($item['type_sale_amount'] as $key => $value) {
                        $key = $typeList[$key]??null;
                        if(!isset($tmp['type_sale_amount_us'][$key])) {
                            $tmp['type_sale_amount_us'][$key] = 0;
                        }

                        $tmp['type_sale_amount_us'][$key] = bcadd($tmp['type_sale_amount_us'][$key], $value, 2);
                    }
                }
            }
        }

        return $this->render($this->action->id, [
            'list' => array_values($list),
            'type' => $type
        ]);
    }

    /**
     * 清理缓存
     *
     * @return string
     */
    public function actionClearCache()
    {
        $model = new ClearCache();
        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->message('清理成功', $this->refresh())
                : $this->message($this->getError($model), $this->refresh(), 'error');
        }

        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }
}