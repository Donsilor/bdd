<?php

namespace backend\modules\goods\controllers;

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
* @package backend\modules\goods\controllers
*/
class DiamondController extends BaseController
{
    use Curd;

    /**
    * @var Diamond
    */
    public $modelClass = Diamond::class;
    public $enableCsrfValidation = false;


    /**
    * 首页
    *
    * @return string
    * @throws \yii\web\NotFoundHttpException
    */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams, ['goods_name','language','created_at']);
        $this->setLocalLanguage($searchModel->language);
        $dataProvider->query->joinWith(['lang']);
        $dataProvider->query->andFilterWhere(['like', 'lang.goods_name',$searchModel->goods_name]);

        //创建时间过滤
        if (!empty(Yii::$app->request->queryParams['SearchModel']['created_at'])) {
            list($start_date, $end_date) = explode('/', Yii::$app->request->queryParams['SearchModel']['created_at']);
            $dataProvider->query->andFilterWhere(['between', 'goods_diamond.created_at', strtotime($start_date), strtotime($end_date) + 86400]);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
    /**
     * 编辑/创建 多语言
     *
     * @return mixed
     */
    public function actionEditLang()
    {
        $id = Yii::$app->request->get('id');
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        
        $model = $this->findModel($id);
        $status = $model ? $model->status:0;
        $old_diamond_info = $model->toArray();
        if ($model->load(Yii::$app->request->post())) { 
            $model->type_id = 15;
            try{
                $trans = Yii::$app->db->beginTransaction();
                if($model->status == 1 && $status == 0){
                    $model->onsale_time = time();
                }
                if(false === $model->save()){
                    throw new Exception($this->getError($model));
                }

                //同步款式库的状态
                Style::updateAll(['status'=>$model->status,'virtual_clicks'=>$model->virtual_clicks,'virtual_volume'=>$model->virtual_volume],['id'=>$model->style_id]);

                $this->editLang($model);

                if(!empty($id)){
                    //记录日志
                    \Yii::$app->services->goods->recordGoodsLog($model, $old_diamond_info);
                }

                //同步裸钻数据到goods
                \Yii::$app->services->diamond->syncDiamondToGoods($model->id);
                
                $trans->commit();
            }catch (Exception $e){
                $trans->rollBack();
                $error = $e->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id]), 'error');
            }
            
            return $this->message("保存成功", $this->redirect([$this->action->id,'id'=>$model->id]), 'success');
        }
        
        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }
    
    /**
     * 生成商品名称
     * @return string[]|array[]
     */
    public function actionGetGoodsName(){
        $carat = Yii::$app->request->post('carat',null);
        $cert_type_id = Yii::$app->request->post('cert_type',null);
        $shape_id = Yii::$app->request->post('shape',null);
        $color_id = Yii::$app->request->post('color',null);
        $clarity_id = Yii::$app->request->post('clarity',null);

        $carat_str = '';
        if(!empty($carat)){
            $carat_str .= $carat.'ct';
        }
        $languages = Yii::$app->params['languages'];
        $ids = array($cert_type_id,$shape_id,$color_id,$clarity_id);
        $data = array();
        foreach ($languages as $key=>$val){
            $goods_name = $carat_str;
            $language = $key;
            $attr_arr = \Yii::$app->services->goodsAttribute->getAttributeByValueIds($ids, $language);
            if(isset($attr_arr[AttrIdEnum::SHAPE])){
                $goods_name .= ' '.$attr_arr[AttrIdEnum::SHAPE];
            }
            if(isset($attr_arr[AttrIdEnum::COLOR])){
                $goods_name .= ' '.$attr_arr[AttrIdEnum::COLOR].'色';
            }
            if(isset($attr_arr[AttrIdEnum::CLARITY])){
                $goods_name .= ' '.$attr_arr[AttrIdEnum::CLARITY].'净度';
            }
            if(isset($attr_arr[AttrIdEnum::CERT_TYPE])){
                $goods_name .= ' '.$attr_arr[AttrIdEnum::CERT_TYPE];
            }
            $data[$language] = $goods_name;
        }

        return ResultHelper::json(200, '保存成功',$data);

    }

    /**
     * ajax更新排序/状态
     *
     * @param $id
     * @return array
     */
    public function actionAjaxUpdate($id)
    {
        if (!($model = $this->modelClass::findOne($id))) {
            return ResultHelper::json(404, '找不到数据');
        }

        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status']);
        if (!$model->save(false)) {
            return ResultHelper::json(422, $this->getError($model));
        }else{
            Style::updateAll(['status'=>$model->status],['id'=>$model->style_id]);
        }
        //记录日志
        \Yii::$app->services->goods->recordGoodsStatus($model, Yii::$app->request->get('status'));
        return ResultHelper::json(200, '修改成功');
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
            'relations'=>[
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
            ['证书类型', 'cert_type', 'selectd',\common\enums\DiamondEnum::getCertTypeList()],
            ['证书号', 'cert_id', 'text'],
            ['库存', 'goods_num', 'text'],
            ['成本价', 'cost_price', 'text'],
            ['市场价', 'market_price', 'text'],
            ['销售价', 'sale_price', 'text'],
            ['石重', 'carat', 'text'],
            ['净度', 'clarity', 'selectd',\common\enums\DiamondEnum::getClarityList()],
            ['切工', 'cut', 'selectd',\common\enums\DiamondEnum::getCutList()],
            ['颜色', 'color', 'selectd',\common\enums\DiamondEnum::getColorList()],
            ['形状', 'shape', 'selectd',\common\enums\DiamondEnum::getShapeList()],
            ['荧光', 'fluorescence', 'selectd',\common\enums\DiamondEnum::getFluorescenceList()],
            ['切割深度(%)', 'depth_lv', 'text'],
            ['台宽(%)', 'table_lv', 'text'],
            ['对称', 'symmetry', 'selectd',\common\enums\DiamondEnum::getSymmetryList()],
            ['抛光', 'polish', 'selectd',\common\enums\DiamondEnum::getPolishList()],
            ['石底层', 'stone_floor', 'text'],
            ['长度', 'length', 'text'],
            ['宽度', 'width', 'text'],
            ['长宽比(%)', 'aspect_ratio', 'text'],
            ['售后服务', 'sale_services', 'text'],
            ['货品来源', 'source_id', 'text'],
            ['来源折扣', 'source_discount', 'text'],
//            ['库存类型', 'is_stock', 'text'],
            ['上架时间', 'onsale_time', 'date', 'Y-m-d'],
            ['上架状态', 'status', 'selectd',\common\enums\StatusEnum::getMap()],
            ['创建时间', 'created_at', 'date', 'Y-m-d H:i:s'],
            ['更新时间', 'updated_at', 'date', 'Y-m-d H:i:s'],
            ['前端地址','id','function',function($model){
                return \Yii::$app->params['frontBaseUrl'].'/diamond-details/'.$model->id.'?goodId='.$model->id;
            }]

        ];


        return ExcelHelper::exportData($list, $header, '裸钻数据导出_' . time());
    }
}
