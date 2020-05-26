<?php

namespace backend\modules\goods\controllers;

use common\enums\AreaEnum;
use common\enums\FrameEnum;
use common\enums\StatusEnum;
use common\helpers\ExcelHelper;
use common\helpers\Html;
use common\helpers\ImageHelper;
use common\models\goods\StyleMarkup;
use Yii;
use common\models\goods\Style;
use common\components\Curd;
use common\models\base\SearchModel;

use backend\controllers\BaseController;
use yii\base\Exception;
use common\helpers\ResultHelper;
use common\helpers\ArrayHelper;


/**
* Style
*
* Class StyleController
* @package backend\modules\goods\controllers
*/
class StyleController extends BaseController
{
    use Curd;

    /**
    * @var Style
    */
    public $modelClass = Style::class;


    /**
    * 首页
    *
    * @return string
    * @throws \yii\web\NotFoundHttpException
    */
    public function actionIndex()
    {
        $type_id = Yii::$app->request->get('type_id',0);
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);
        $typeModel = Yii::$app->services->goodsType->getAllTypesById($type_id,null);
        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams,['style_name','language']);
        //切换默认语言
        $this->setLocalLanguage($searchModel->language);
        if($typeModel){
            $dataProvider->query->andFilterWhere(['in', 'type_id',$typeModel['ids']]);
        }
        $dataProvider->query->joinWith(['lang']);
        $dataProvider->query->andFilterWhere(['like', 'lang.style_name',$searchModel->style_name]);


        //导出
        if(Yii::$app->request->get('action') === 'export'){
            $query = Yii::$app->request->queryParams;
            unset($query['action']);
            if(empty(array_filter($query))){
                return $this->message('导出条件不能为空', $this->redirect(['index']), 'warning');
            }
            $dataProvider->setPagination(false);
            $list = $dataProvider->models;
            $this->getExport($list,$type_id);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,  
            'typeModel'  =>$typeModel,  
        ]);
    }
    
    /**
     * 编辑/创建 多语言
     *
     * @return mixed
     */
    public function actionEditLang()
    {
        $id = Yii::$app->request->get('id', null);
        $type_id = Yii::$app->request->get('type_id', 0);
        $returnUrl = Yii::$app->request->get('returnUrl',['index','type_id'=>$type_id]);
        $model = $this->findModel($id);
        
        $status = $model ? $model->status:0;
        $old_style_info = $model->toArray();
        if ($model->load(Yii::$app->request->post())) {
            
            try{
                $trans = Yii::$app->db->beginTransaction();
                if($model->status == 1 && $status == 0){
                    $model->onsale_time = time();
                }                
                if(false === $model->save()){
                    throw new Exception($this->getError($model));
                }                
                $this->editLang($model);
                
                $trans->commit();                
            }catch (Exception $e){
                $trans->rollBack();
                $error = $e->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id,'type_id'=>$type_id]), 'error');
            }

            if(!empty($id)){
                //记录日志
                \Yii::$app->services->goods->recordGoodsLog($model, $old_style_info);
            }

            //商品更新
            \Yii::$app->services->goods->syncStyleToGoods($model->id);
            return $this->message("保存成功", $this->redirect($returnUrl), 'success');
        }
        return $this->render($this->action->id, [
                'model' => $model,
        ]);
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
        $status = $model ? $model->status :0;
        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status']);
        
        if($model->status ==1 && $status == 0){
            $model->onsale_time = time();
        }
        if (!$model->save(false)) {
            return ResultHelper::json(422, $this->getError($model));
        }

        //记录日志
        \Yii::$app->services->goods->recordGoodsStatus($model, Yii::$app->request->get('status'));
        return ResultHelper::json(200, '修改成功');
    }
    
    public function actionTest($id)
    {
        $model = $this->modelClass::findOne($id);
        $res = \Yii::$app->services->goods->formatStyleAttrs($model,true);
        echo '<pre/>';
        print_r($res);
        exit;
    }


    /**
     * 导出Excel
     *
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getExport($list,$type_id)
    {
        // [名称, 字段名, 类型, 类型规则]
        switch ($type_id){
            case 2:
                $name = "戒指";
                break;
            case 3:
                $name = "饰品";
                break;
            case 12:
                $name = "戒托";
                break;
            default:$name = "商品";
        }
        $header = [
            ['ID', 'id' , 'text'],
            ['商品名称', 'lang.style_name', 'text'],
            ['款式编号', 'style_sn', 'text'],
            ['产品线', 'type_id', 'function', function($model){
                return $model->type->type_name ?? '';
            }],
            ['销售价(CNY)', 'sale_price', 'text'],
            ['库存', 'goods_storage', 'text'],
            ['中国上架状态', 'status', 'function', function($model){
                $styleMarkup = StyleMarkup::find()->where(['style_id'=>$model->id ,'area_id' => AreaEnum::China])->one();
                return FrameEnum::getValue($styleMarkup->status ?? FrameEnum::DISABLED);
            }],
            ['香港上架状态', 'status', 'function', function($model){
                $styleMarkup = StyleMarkup::find()->where(['style_id'=>$model->id ,'area_id' => AreaEnum::HongKong])->one();
                return FrameEnum::getValue($styleMarkup->status ?? FrameEnum::DISABLED);
            }],
            ['澳门上架状态', 'status', 'function', function($model){
                $styleMarkup = StyleMarkup::find()->where(['style_id'=>$model->id ,'area_id' => AreaEnum::MaCao])->one();
                return FrameEnum::getValue($styleMarkup->status ?? FrameEnum::DISABLED);
            }],
            ['台湾上架状态', 'status', 'function', function($model){
                $styleMarkup = StyleMarkup::find()->where(['style_id'=>$model->id ,'area_id' => AreaEnum::TaiWan])->one();
                return FrameEnum::getValue($styleMarkup->status ?? FrameEnum::DISABLED);
            }],
            ['国外上架状态', 'status', 'function', function($model){
                $styleMarkup = StyleMarkup::find()->where(['style_id'=>$model->id ,'area_id' => AreaEnum::Other])->one();
                return FrameEnum::getValue($styleMarkup->status ?? FrameEnum::DISABLED);
            }],

            ['前端地址','id','function',function($model){
                if($model->type_id == 2){
                    return \Yii::$app->params['frontBaseUrl'].'/ring/wedding-rings/'.$model->id.'?goodId='.$model->id.'&ringType=single';
                }elseif ($model->type_id == 12){
                    return \Yii::$app->params['frontBaseUrl'].'/ring/engagement-rings/'.$model->id.'?goodId='.$model->id.'&ringType=engagement';
                }elseif ($model->type_id == 4){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/necklace/'.$model->id.'?goodId='.$model->id;
                }elseif ($model->type_id == 5){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/pendant/'.$model->id.'?goodId='.$model->id;
                }elseif ($model->type_id == 6){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/studEarring/'.$model->id.'?goodId='.$model->id;
                }elseif ($model->type_id == 7){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/earring/'.$model->id.'?goodId='.$model->id;
                }elseif ($model->type_id == 8){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/braceletLine/'.$model->id.'?goodId='.$model->id;
                }elseif ($model->type_id == 9){
                    return \Yii::$app->params['frontBaseUrl'].'/jewellery/bracelet/'.$model->id.'?goodId='.$model->id;
                }
            }]
        ];


        return ExcelHelper::exportData($list, $header, $name.'数据导出_' . date('YmdHis',time()));
    }
}
