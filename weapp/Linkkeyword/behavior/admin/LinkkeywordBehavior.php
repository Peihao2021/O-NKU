<?php

namespace weapp\Linkkeyword\behavior\admin;

use weapp\Linkkeyword\model\LinkkeywordModel;

class LinkkeywordBehavior
{

    protected static $actionName;

    protected static $controllerName;

    protected static $moduleName;

    protected static $method;

    /**
     * 构造方法
     *
     * @param Request $request Request对象
     *
     * @access public
     */
    public function __construct()
    {
        ! isset(self::$moduleName) && self::$moduleName = request()->module();
        ! isset(self::$controllerName) && self::$controllerName = request()->controller();
        ! isset(self::$actionName) && self::$actionName = request()->action();
        ! isset(self::$method) && self::$method = strtoupper(request()->method());
    }

    /**
     * 模块初始化
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function moduleInit(&$params)
    {

    }

    /**
     * 操作开始执行
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function actionBegin(&$params)
    {

    }

    /**
     * 视图内容过滤
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function viewFilter(&$params)
    {

    }

    /**
     * 应用结束
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function appEnd(&$params)
    {
        if ('POST' != self::$method) {
            return true;
        }

        $modCtlActArr = array(
            'linkkeyword@linkkeyword@add',
            'linkkeyword@linkkeyword@edit',
            'linkkeyword@linkkeyword@del',
            'admin@weapp@disable',
            'admin@weapp@enable',
            'admin@weapp@install',
            'admin@weapp@uninstall',
        );
        $table     = request()->param('table', 0);
        $ctlActStr = strtolower(self::$moduleName.'@'.self::$controllerName.'@'.self::$actionName);
        if ('admin@weapp@execute' == $ctlActStr) {
            $ctlActStr = strtolower(request()->param('sm').'@'.request()->param('sc').'@'.request()->param('sa'));
        }
        if ( in_array($ctlActStr, $modCtlActArr) || ($ctlActStr == strtolower('admin@Index@changeTableVal') && $table == LinkkeywordModel::TABEL_NAME) ) {
            delFile(HTML_ROOT); //清除页面缓存
        }
    }
}