<?php

namespace backend\modules\goods\controllers;

use common\models\goods\CategoryLang;
use Yii;
use common\components\Curd;
use common\models\goods\Category;
use yii\data\ActiveDataProvider;
use backend\controllers\BaseController;

/**
 * 商品分类
 *
 * Class ArticleCateController
 * @package addons\RfArticle\backend\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class CategoryController extends BaseController
{
    use Curd;

    /**
     * @var CategoryController
     */
    public $modelClass = Category::class;

    /**
     * Lists all Tree models.
     * @return mixed
     */
    public function actionIndex()
    {
        $query = Category::find()->alias('a')
            ->orderBy('sort asc, created_at asc')
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->leftJoin('{{%goods_category_lang}} b', 'b.master_id = a.id and b.language = "'.Yii::$app->language.'"')
            ->select(['a.*', 'b.cat_name']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);

        $dataProvider->query->with(['lang'=>function($query){
            $query->where(['language'=>Yii::$app->language]);
        }]);

        return $this->render('index', [
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * @return mixed|string|\yii\console\Response|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEditLang()
    {
        $request = Yii::$app->request;
        $id = $request->get('id');
        $model = $this->findModel($id);


        $model->pid = $request->get('pid', null) ?? $model->pid; // 父id

        // ajax 验证
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $trans = Yii::$app->db->beginTransaction();
            $res = $model->save();
            $resl = $this->editLang($model,true);
            $resl = true;

            if($res && $resl){
                $trans->commit();
                $this->redirect(['index']);
            }else{
                $trans->rollBack();
                $this->message($this->getError($model), $this->redirect(['index']), 'error');
            }

        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'cateDropDownList' => Category::getDropDownForEdit($id),
        ]);
    }
}