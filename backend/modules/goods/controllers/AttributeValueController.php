<?php

namespace backend\modules\goods\controllers;

use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;
use Yii;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use common\models\goods\AttributeValue;

/**
 * Attribute
 *
 * Class AttributeController
 * @package backend\modules\goods\controllers
 */
class AttributeValueController extends BaseController
{
  use Curd;
  
  /**
   * @var Attribute
   */
  public $modelClass = AttributeValue::class;
  
  
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
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query->with(['lang']);
        $dataProvider->query->where(['>','status','-1']);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
  }
  /**
   * ajax编辑/创建
   *
   * @return mixed|string|\yii\web\Response
   * @throws \yii\base\ExitException
   */
  public function actionAjaxEditLang()
  {
      $id = Yii::$app->request->get('id');
      $attr_id = Yii::$app->request->get('attr_id');
      //$trans = Yii::$app->db->beginTransaction();
      $model = $this->findModel($id);
      if(isset($attr_id)){
          $model->attr_id = $attr_id;
      }
      // ajax 校验
      $this->activeFormValidate($model);
      if ($model->load(Yii::$app->request->post())) {
        if($model->save()){
            //多语言编辑
            $this->editLang($model,true);
            
            //更新属性值到attribute_lang.attr_values;
            Yii::$app->services->goodsAttribute->updateAttrValues($model->attr_id);
            
            return $this->redirect(['attribute/edit-lang?id='.$model->attr_id]);
        }else{
            return $this->message($this->getError($model), $this->redirect(['index']), 'error');
        }
      }
      
      return $this->renderAjax($this->action->id, [
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

        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status','erp_id']);
        if (!$model->save(false)) {
            return ResultHelper::json(422, $this->getError($model));
        }
        return ResultHelper::json(200, '修改成功');
    }


  /**
   * 删除
   *
   * @param $id
   * @return mixed
   * @throws \Throwable
   * @throws \yii\db\StaleObjectException
   */
  public function actionDelete($id)
  {
      if ($model = $this->findModel($id)) {
          $model->status = -1;
          $model->save(false);
          
          //更新属性值到attribute_lang.attr_values;
          Yii::$app->services->goodsAttribute->updateAttrValues($model->attr_id);
          return $this->message("删除成功", $this->redirect(['attribute/edit-lang?id='.$model->attr_id]));
      }
      
      return $this->message("删除失败", $this->redirect(['attribute/edit-lang?id='.$model->attr_id]), 'error');
  }
}
