<?php

namespace backend\modules\goods\controllers;

use common\enums\LanguageEnum;
use Yii;
use common\models\goods\Attribute;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use common\models\goods\AttributeValue;
use yii\base\Exception;
/**
* Attribute
*
* Class AttributeController
* @package backend\modules\goods\controllers
*/
class AttributeController extends BaseController
{
    use Curd;

    /**
    * @var Attribute
    */
    public $modelClass = Attribute::class;


    /**
    * 首页
    *
    * @return string
    * @throws \yii\web\NotFoundHttpException
    */
    public function actionIndex()
    {
    	//Yii::$app->language = 'zh-TW';
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
            ->search(Yii::$app->request->queryParams,['attr_name','language','remark']);
        
        $dataProvider->query->andWhere(['>','status',-1]); 
        $dataProvider->query->joinWith(['lang']);
        $dataProvider->query->andFilterWhere(['like', 'lang.attr_name',$searchModel->attr_name]) ;
        $dataProvider->query->andFilterWhere(['like', 'lang.remark',$searchModel->remark]) ;
       
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
        if ($model->load(Yii::$app->request->post())) {
            $is_new = $model->isNewRecord;            
            try{
                $trans = Yii::$app->db->beginTransaction();
                if(false === $model->save()){
                    throw new Exception($this->getError($model));
                }
                $this->editLang($model);
                
                $trans->commit();
                return $is_new ?
                    $this->message("添加成功", $this->redirect(['edit-lang','id'=>$model->id]), 'success'):
                    $this->message("保存成功", $this->redirect($returnUrl), 'success');
            }catch (Exception $e){
                $trans->rollBack();
                $error = $e->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id]), 'error');
            }
        }
        
        $dataProvider = null;
        if(isset($id)){
            $searchModel = new SearchModel([
                'model' => AttributeValue::class,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'sort'=>SORT_ASC,
                    'id' => SORT_DESC
                ],
                'pageSize' =>1000,
            ]);
            
            $dataProvider = $searchModel
              ->search(Yii::$app->request->queryParams);

            $dataProvider->query->with(['lang' => function($query) {
                $query->where(['language' => LanguageEnum::ZH_CN]);
            }]);
            $dataProvider->query->with(['lang2' => function($query) {
                $query->where(['language' => LanguageEnum::ZH_HK]);
            }]);
            $dataProvider->query->with(['lang3' => function($query) {
                $query->where(['language' => LanguageEnum::EN_US]);
            }]);
            $dataProvider->query->andWhere(['attr_id'=>$id]);
            $dataProvider->query->andWhere(['>','status',-1]);            
            
            
            $dataProvider->setSort(false);
        }
        return $this->render($this->action->id, [
            'model' => $model,
            'dataProvider'=>$dataProvider,
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
        $model = $this->findModel($id);
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $is_new = $model->isNewRecord;             
            try{
                $trans = Yii::$app->db->beginTransaction();
                if(false === $model->save()){
                    throw new Exception($this->getError($model));
                }
                $this->editLang($model);                
                $trans->commit();
                
                return $is_new ?
                    $this->message("添加成功", $this->redirect(['edit-lang','id'=>$model->id]), 'success'):
                    $this->message("保存成功", $this->redirect(['index']), 'success');
            }catch (Exception $e){
                $trans->rollBack();
                $error = $e->getMessage();
                \Yii::error($error);
                return $this->message("保存失败:".$error, $this->redirect([$this->action->id,'id'=>$model->id]), 'error');
            }            
        }
        
        return $this->renderAjax($this->action->id, [
                'model' => $model,
        ]);
    }
    
    /**
     * 删除
     * @param unknown $id
     * @return mixed|string
     */
    /* public function actionDelete($id)
    {
        if ($model = $this->findModel($id)) {
            $model->status = -1;
            $model->save();
            return $this->message("删除成功", $this->redirect(['index', 'id' => $model->id]));
        }
        
        return $this->message("删除失败", $this->redirect(['index', 'id' => $model->id]), 'error');
    } */
}
