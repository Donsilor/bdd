<?php

namespace services\common;

use Yii;
use yii\helpers\Json;
use common\models\common\MenuCate;
use common\helpers\ArrayHelper;
use common\components\Service;
use common\models\common\Menu;
use common\enums\StatusEnum;
use common\enums\TypeEnum;
use common\helpers\StringHelper;
use common\helpers\Auth;
use common\helpers\TreeHelper;
use common\models\common\MenuLang;
use common\enums\AppEnum;

/**
 * Class MenuService
 * @package services\sys
 * @author jianyan74 <751393839@qq.com>
 */
class MenuService extends Service
{
    /**
     * @param string $addons_name 插件名称
     */
    public function delByAddonsName($addons_name)
    {
        Menu::deleteAll(['addons_name' => $addons_name]);
    }

    /**
     * @param MenuCate $cate
     */
    public function delByCate(MenuCate $cate)
    {
        Menu::deleteAll(['app_id' => $cate->app_id, 'addons_name' => $cate->addons_name]);
    }

    /**
     * @param array $menus
     * @param MenuCate $cate
     * @param int $pid
     * @param int $level
     * @param Menu $parent
     */
    public function createByAddons(array $menus, MenuCate $cate, $pid = 0, $level = 1, $parent = '')
    {
        // 重组数组
        $menus = ArrayHelper::regroupMapToArr($menus);

        foreach ($menus as $menu) {
            $model = new Menu();
            $model->attributes = $menu;
            // 增加父级
            !empty($parent) && $model->setParent($parent);
            $model->url = $menu['route'];
            $model->pid = $pid;
            $model->level = $level;
            $model->cate_id = $cate->id;
            $model->app_id = $cate->app_id;
            $model->addons_name = $cate->addons_name;
            $model->type = $cate->type;
            $model->save();

            if (isset($menu['child']) && !empty($menu['child'])) {
                $this->createByAddons($menu['child'], $cate, $model->id, $model->level + 1, $model);
            }
        }
    }

    /**
     * 获取下拉
     *
     * @param MenuCate $menuCate
     * @param string $id
     * @return array
     */
    public function getDropDown(MenuCate $menuCate, $app_id, $id = '', $language=null)
    {
        if(empty($language)){
            $language = Yii::$app->params['language'];;
        }
        $list = Menu::find()->alias('a')
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['app_id' => $app_id])
            ->andWhere(['type' => $menuCate->type])
            ->andWhere(['cate_id' => $menuCate->id])
            ->leftJoin(MenuLang::tableName().' b','b.master_id = a.id and b.language = "'.$language.'"')
            ->andFilterWhere(['b.addons_name' => $menuCate->addons_name])
            ->andFilterWhere(['<>', 'a.id', $id])

            ->select(['a.id', 'ifnull(b.title,a.title) as title', 'pid', 'level'])
            ->orderBy('cate_id asc, sort asc')
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($list);
        $data = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        return ArrayHelper::merge([0 => '顶级菜单'], $data);
    }
    /**
     * 前端菜单列表
     * @param unknown $languge
     * @return array
     */
    public function getFrontList($cate_id = null,$languge = null)
    {
        if(empty($language)){
            $language = Yii::$app->params['language'];
        }
        $query = Menu::find()->alias('a')->where(['status' => StatusEnum::ENABLED]);
        $query->leftJoin(MenuLang::tableName().' b','b.master_id = a.id and b.language = "'.$language.'"')
                ->select(['a.id','a.pid','a.url','a.level','ifnull(b.title,a.title) as title'])
                ->orderBy('cate_id asc, sort asc')
                ->andWhere(['app_id' => \Yii::$app->id]);
        if($cate_id >0) {
            $query->andWhere(['=','a.cate_id',$cate_id]);
        }
        $models = $query->orderBy('sort asc, a.id asc')->asArray()->all();        
        return $models;
    }
    
    
    /**
     * @return array
     */
    public function getOnAuthList()
    {
        $models = $this->findAll();
        // 获取权限信息
        $auth = [];
        if (!Yii::$app->services->auth->isSuperAdmin()) {
            $role = Yii::$app->services->authRole->getRole();
            $auth = Yii::$app->services->authRole->getAllAuthByRole($role);
        }

        foreach ($models as $key => &$model) {
            if (!empty($model['url'])) {
                $params = Json::decode($model['params']);
                (empty($params) || !is_array($params)) && $params = [];
                $model['fullUrl'][] = $model['type'] == TypeEnum::TYPE_ADDONS
                    ? 'addons/' . StringHelper::toUnderScore($model['addons_name']) . '/' . $model['url']
                    : $model['url'];

                foreach ($params as $param) {
                    if (!empty($param['key'])) {
                        $model['fullUrl'][$param['key']] = $param['value'];
                    }
                }
            } else {
                $model['fullUrl'] = '#';
            }

            // 系统菜单校验
            if ($model['type'] == TypeEnum::TYPE_DEFAULT && Auth::verify($model['url'], $auth) === false) {
                unset($models[$key]);
            }

            // 插件菜单校验
            if ($model['type'] == TypeEnum::TYPE_ADDONS) {
                $tmpUrl = $model['addons_name'] . ':' . $model['url'];
                if (Auth::verify($tmpUrl, $auth) === false) {
                    unset($models[$key]);
                }

                unset($tmpUrl);
            }
        }

        return ArrayHelper::itemsMerge($models);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findAll($language = null)
    {

        if(empty($language)){
            $language = Yii::$app->params['language'];
        }
        $query = Menu::find()->alias('a')->where(['status' => StatusEnum::ENABLED]);
        // 关闭开发模式
        if (empty(Yii::$app->debris->config('sys_dev', false, 1))) {
            $query->andWhere(['dev' => StatusEnum::DISABLED]);
        }
        $query->leftJoin(MenuLang::tableName().' b','b.master_id = a.id and b.language = "'.$language.'"')
              ->select(['a.*','ifnull(b.title,a.title) as title'])
              ->orderBy('cate_id asc, sort asc')
              ->andWhere(['app_id' => \Yii::$app->id])
              ->with(['cate' => function (\yii\db\ActiveQuery $query) {
                        return $query->andWhere(['app_id' => \Yii::$app->id]);
              }]); 
        $models = $query->orderBy('sort asc, a.id asc')->asArray()->all();        
        return $models;
    }
    
    /**
     * @param $tree
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findChildByID($tree, $id)
    {
        return Menu::find()
            ->where(['like', 'tree', $tree . TreeHelper::prefixTreeKey($id) . '%', false])
            ->select(['id', 'level', 'tree', 'pid'])
            ->asArray()
            ->all();
    }
}