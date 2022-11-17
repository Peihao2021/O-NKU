<?php

namespace weapp\Likearticle\behavior\admin;

/**
 * 行为扩展
 */
class LikearticleBehavior
{
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {
        !isset(self::$moduleName) && self::$moduleName = request()->module();
        !isset(self::$controllerName) && self::$controllerName = request()->controller();
        !isset(self::$actionName) && self::$actionName = request()->action();
        !isset(self::$method) && self::$method = strtoupper(request()->method());
    }

    /**
     * 模块初始化
     * @param array $params 传入参数
     * @access public
     */
    public function moduleInit(&$params)
    {

    }

    /**
     * 操作开始执行
     * @param array $params 传入参数
     * @access public
     */
    public function actionBegin(&$params)
    {

    }

    /**
     * 视图内容过滤
     * @param array $params 传入参数
     * @access public
     */
    public function viewFilter(&$params)
    {

    }

    /**
     * 应用结束
     * @param array $params 传入参数
     * @access public
     */
    public function appEnd(&$params)
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        if ('POST' == self::$method) {
            $seo_keywords = !empty($_POST['seo_keywords']) ? $_POST['seo_keywords'] : '';
            if (!empty($seo_keywords)) {
                return true;
            }

            $ctlArr = \think\Db::name('channeltype')->field('id,ctl_name,ifsystem')
                ->where('nid','NOTIN', ['guestbook','single'])
                ->cache(true,EYOUCMS_CACHE_TIME,"channeltype")
                ->getAllWithIndex('ctl_name');
            $actArr = ['add','edit'];
            if (!empty($ctlArr[self::$controllerName]) && in_array(self::$actionName, $actArr)) {

                $data = \think\Db::name('weapp')->where([
                        'code'      => 'Likearticle',
                        'status'    => 1,
                    ])->getField('data');
                if (!empty($data)) {
                    $weappData = unserialize($data);
                    if ('add' == self::$actionName && 1 == intval($weappData['addarchives_status'])) {
                        return true;
                    } else if ('edit' == self::$actionName && 1 == intval($weappData['editarchives_status'])) {
                        return true;
                    }
                }

                // 根据标题自动提取相关的关键字
                $title = !empty($_POST['title']) ? trim($_POST['title']) : '';
                if (!empty($title)) {
                    $saveData = [
                        'update_time'   => getTime(),
                    ];
                    /*获取文档内容*/
                    $channeltypeRow = $ctlArr[self::$controllerName];
                    $fieldname = 'content';
                    if (empty($channeltypeRow['ifsystem'])) { // 自定义模型
                        $fieldname = \think\Db::name('channelfield')->where([
                                'channel_id'    => $channeltypeRow['id'],
                                'dtype'         => 'htmltext',
                                'ifeditable'    => 1,
                                'status'        => 1,
                            ])
                            ->order('id asc')
                            ->getField('name');
                    }
                    $content = !empty($_POST['addonFieldExt'][$fieldname]) ? $_POST['addonFieldExt'][$fieldname] : '';
                    /*--end*/
                    // SEO关键词 - 适用所有模型
                    $likearticleLogic= new \weapp\Likearticle\logic\LikearticleLogic;
                    $saveData['seo_keywords'] = $likearticleLogic->getSplitword($title, $content);
                    \think\Db::name('archives')->where('aid',$_POST['aid'])->update($saveData);
                }
            }
        }
        /*--end*/
    }
}