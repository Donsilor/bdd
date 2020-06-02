<?php

namespace backend\modules\goods\controllers;

use common\models\goods\Goods;
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
//                \Yii::$app->services->goods->recordGoodsLog($model, $old_style_info);
            }

            //商品更新
            \Yii::$app->services->goods->syncStyleToGoods($model->id);
            return $this->message("保存成功", $this->redirect($returnUrl), 'success');
        }

        $attrStyleIds = [];
        if($type_id==19) {
            $attrStyleIds = Yii::$app->request->get('attr_style_ids', '');

            $attrStyleIds = explode('|', $attrStyleIds);

            if(empty($id) && count($attrStyleIds)!=2) {
                return;
            }
        }

        return $this->render($this->action->id, [
                'model' => $model,
            'attrStyleIds' => $attrStyleIds
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
}
