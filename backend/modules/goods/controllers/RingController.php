<?php

namespace backend\modules\goods\controllers;

use common\enums\StatusEnum;
use common\models\goods\RingRelation;
use common\models\goods\Style;
use services\goods\RingService;
use services\goods\StyleService;
use Yii;
use common\models\goods\Ring;
use common\components\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use common\helpers\ResultHelper;
use yii\db\Exception;
use common\helpers\ArrayHelper;

/**
* Ring
*
* Class RingController
* @package backend\modules\goods\controllers
*/
class RingController extends BaseController
{
    use Curd;

    /**
    * @var Ring
    */
    public $modelClass = Ring::class;
    public $enableCsrfValidation = false;
    public $noAuthOptional = ['select-style','get-style','is-have'];
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
            ->search(Yii::$app->request->queryParams, ['ring_name','language']);
        $this->setLocalLanguage($searchModel->language);
        $dataProvider->query->joinWith(['lang']);
        $dataProvider->query->andFilterWhere(['like', 'lang.ring_name',$searchModel->ring_name]);

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
        $id = Yii::$app->request->get('id', null);
        $returnUrl = Yii::$app->request->get('returnUrl',['index']);
        //$trans = Yii::$app->db->beginTransaction();
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->editRingRelation($model->id);
            $this->editLang($model,true);

            RingService::syncRingToGoods($model->id);

            return $this->redirect($returnUrl);
        }
        $style_ids = array();
        if($id){
            $ring_relations = Yii::$app->services->goodsStyle->getRelationByRing($id);
            $style_ids = array_column($ring_relations,'style_id');
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'style_ids' => $style_ids,

        ]);
    }
    
    /**
     * 对戒编辑
     * @param unknown $ring_id
     * @throws Exception
     */
    public function editRingRelation($ring_id)
    {

        $relationClassName  = 'RingRelation';
        $posts = Yii::$app->request->post($relationClassName);
        try{
            $style_ids = array();
            if(!empty($posts['style_id']) && is_array($posts['style_id'])){
                $style_ids = $posts['style_id'];
            }
            $ringRelationRows = RingRelation::find()->where(['ring_id'=>$ring_id])->asArray()->all();
            $old_style_ids =array();
            if(!empty($ringRelationRows)){
                $old_style_ids= array_column($ringRelationRows, 'style_id');
            }
            //删除的商品解绑、解锁
            $uplock_style_ids = array_diff($old_style_ids,$style_ids);
            if($uplock_style_ids){
                RingRelation::deleteAll(['and','ring_id'=>$ring_id,['in','style_id',$uplock_style_ids]]);
                Style::updateAll(['ring_id'=>0],['in','id',$uplock_style_ids]);
            }
            //新增商品的id，需要上锁
            $unlock_style_ids = array_diff($style_ids,$old_style_ids);
            if($unlock_style_ids){
                //数据入库
                foreach ($unlock_style_ids as $style_id){
                    $relationModel = new RingRelation();
                    $relationModel->ring_id  = $ring_id;
                    $relationModel->style_id = $style_id;
                    $relationModel->save();
                }
                // Style::updateAll(['is_lock'=>1],['and',['in','id', $unlock_style_ids],['is_lock'=>0]]);
                Style::updateAll(['ring_id'=>$ring_id],['in','id', $unlock_style_ids]);
            }
        }catch (Exception $e){
            throw $e;
        }
    }
    /**
     * 添加商品时查询戒指数据
     * @return string[]|array[]|string
     */
    public function actionSelectStyle()
    {

        $request = Yii::$app->request;
        if($request->isPost)
        {
            $post = Yii::$app->request->post();
            if(!isset($post['style_id']) || empty($post['style_id'])){
                return ResultHelper::json(422, '请选择商品');
            }else{
                $style_id = $post['style_id'];
            }
            return ResultHelper::json(200, '保存成功',['style_id'=>$style_id]);
        }


        $searchModel = new SearchModel([
            'model' => Style::class,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams,['style_name']);
        $dataProvider->query->andWhere(['>=', 'status', StatusEnum::DISABLED]);
       //戒指分类
        $dataProvider->query->andFilterWhere(['=', 'type_id',2]);
        $dataProvider->query->andFilterWhere(['=', 'ring_id',0]);

        $dataProvider->query->joinWith(['lang']);

        $dataProvider->query->andFilterWhere(['like', 'lang.style_name',$searchModel->style_name]);
        return $this->render('select-style', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    //编辑时获取单个戒指数据
    public function actionGetStyle(){
        $request = Yii::$app->request;

        if($request->isPost)
        {
            $post = Yii::$app->request->post();
//            return ResultHelper::json(200, '保存成功',['model'=>$post]);
            if(!isset($post['style_id']) || empty($post['style_id'])){
                return ResultHelper::json(422, '参数错误');
            }
            $style_id = $post['style_id'];
            $model = Yii::$app->services->goodsStyle->getStyle($style_id);
            $data['id'] = $model['id'];
            $data['style_name'] = $model['style_name'];
            $data['style_sn'] = $model['style_sn'];
            $data['sale_price'] = $model['sale_price'];
            $data['goods_storage'] = $model['goods_storage'];


            return ResultHelper::json(200, '保存成功',$data);
        }

    }


    //查询是否存在
    public function actionIsHave(){
        $request = Yii::$app->request;

        if($request->isPost)
        {
            $post = Yii::$app->request->post();
//            return ResultHelper::json(200, '保存成功',['model'=>$post]);
            if(!isset($post['style_ids']) || empty($post['style_ids'])){
                return ResultHelper::json(422, '参数错误');
            }
            $style_ids = $post['style_ids'];
            $ring_id = $post['ring_id'];

            $list = RingRelation::find()->alias('r')
                ->leftJoin(Style::tableName()." s", 's.id=r.style_id')
                ->where(['in','r.style_id',$style_ids])
                ->andWhere(['<>','r.ring_id',$ring_id])
                ->asArray()
                ->select(['s.id','s.style_sn'])
                ->all();
            $strs = '';
            foreach ($list as $value){
                $strs .= "款号:".$value['style_sn']." + ID:".$value['id']."; ";
            }
            $data['strs'] = $strs;
            $data['count'] = count($list);

            return ResultHelper::json(200, 'ok',$data);
        }

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
        $model = $this->findModel($id);
        if ($model->delete()) {
            if(method_exists($model,'langModel')){
                $langModel = $model->langModel();
                $langModel->deleteAll(['in','master_id',[$id]]);

                $relationsModel = $model->getRelations();
                $relationsModel->deleteAll(['in','ring_id',[$id]]);
            }
            return $this->message("删除成功", $this->redirect(['index']));
        }

        return $this->message("删除失败", $this->redirect(['index']), 'error');
    }

}
