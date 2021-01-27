<?php

namespace backend\modules\erpapi\controllers;

use backend\modules\erpapi\forms\DiamondErpForm;
use common\enums\AttrIdEnum;
use common\helpers\ArrayHelper;
use common\models\goods\Style;
use Yii;
use common\models\goods\Diamond;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use common\helpers\ResultHelper;
use yii\base\Exception;
use common\helpers\ExcelHelper;
use common\models\goods\DiamondLang;

/**
 * Diamond
 *
 * Class DiamondController
 * @package backend\modules\erpapi\controllers
 */
class DiamondController extends BaseController
{

    /**
     * @var Diamond
     */
    public $modelClass = Diamond::class;
    public $enableCsrfValidation = false;

    /**
     * 编辑/创建 多语言
     *
     * @return mixed
     */
    public function actionEdit()
    {
        $param = Yii::$app->request->post();

        $goodsSn = Yii::$app->request->post('goods_sn');

        $model = DiamondErpForm::findOne(['goods_sn' => $goodsSn]);
        if(empty($model)) {
            $model = new DiamondErpForm();
            $model->loadDefaultValues();
        }
        else {
            unset($param['status']);
            unset($param['sale_price']);
            unset($param['market_price']);
            unset($param['cost_price']);
        }

        $old_diamond_info = $model->toArray();

        $old_diamond_info['langs'] = [];
        foreach ($model->langs as $lang) {
            $old_diamond_info['langs'][$lang->id] = $lang->toArray();
        }
        $model->setAttributes($param);
        $model->type_id = 15;

        try {
            $trans = Yii::$app->db->beginTransaction();

            if (false === $model->save()) {
                throw new Exception($this->getError($model));
            }

            //记录日志
            \Yii::$app->services->goods->recordGoodsLog($model, $old_diamond_info);
//
//            //同步裸钻数据到goods
            \Yii::$app->services->diamond->syncDiamondToGoods($model->id);

            $trans->commit();
        } catch (Exception $e) {

            $trans->rollBack();

            \Yii::error($e->getMessage());

            return 'error';
        }

        return 'success';
    }


    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExport()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'relations' => [
                'lang' => ['goods_name'],
            ],
            'pageSize' => $this->pageSize
        ]);
        //print_r(Yii::$app->request->get());exit;
        $param['SearchModel'] = Yii::$app->request->get();
        $dataProvider = $searchModel->search($param);
        $dataProvider->setPagination(false);
        $list = $dataProvider->models;
//        print_r($list);exit;

        // [名称, 字段名, 类型, 类型规则]
        $header = [
            ['ID', 'id'],
            ['名称', 'goods_name', 'text'],
            ['编码', 'goods_sn', 'text'],
            ['证书类型', 'cert_type', 'selectd', \common\enums\DiamondEnum::getCertTypeList()],
            ['证书号', 'cert_id', 'text'],
            ['库存', 'goods_num', 'text'],
            ['成本价', 'cost_price', 'text'],
            ['市场价', 'market_price', 'text'],
            ['销售价', 'sale_price', 'text'],
            ['石重', 'carat', 'text'],
            ['净度', 'clarity', 'selectd', \common\enums\DiamondEnum::getClarityList()],
            ['切工', 'cut', 'selectd', \common\enums\DiamondEnum::getCutList()],
            ['颜色', 'color', 'selectd', \common\enums\DiamondEnum::getColorList()],
            ['形状', 'shape', 'selectd', \common\enums\DiamondEnum::getShapeList()],
            ['荧光', 'fluorescence', 'selectd', \common\enums\DiamondEnum::getFluorescenceList()],
            ['切割深度(%)', 'depth_lv', 'text'],
            ['台宽(%)', 'table_lv', 'text'],
            ['对称', 'symmetry', 'selectd', \common\enums\DiamondEnum::getSymmetryList()],
            ['抛光', 'polish', 'selectd', \common\enums\DiamondEnum::getPolishList()],
            ['石底层', 'stone_floor', 'text'],
            ['长度', 'length', 'text'],
            ['宽度', 'width', 'text'],
            ['长宽比(%)', 'aspect_ratio', 'text'],
            ['售后服务', 'sale_services', 'text'],
            ['货品来源', 'source_id', 'text'],
            ['来源折扣', 'source_discount', 'text'],
//            ['库存类型', 'is_stock', 'text'],
            ['上架时间', 'onsale_time', 'date', 'Y-m-d'],
            ['上架状态', 'status', 'selectd', \common\enums\StatusEnum::getMap()],
            ['创建时间', 'created_at', 'date', 'Y-m-d H:i:s'],
            ['更新时间', 'updated_at', 'date', 'Y-m-d H:i:s'],
            ['前端地址', 'id', 'function', function ($model) {
                return \Yii::$app->params['frontBaseUrl'] . '/diamond-details/' . $model->id . '?goodId=' . $model->id;
            }]

        ];


        return ExcelHelper::exportData($list, $header, '裸钻数据导出_' . time());
    }
}
