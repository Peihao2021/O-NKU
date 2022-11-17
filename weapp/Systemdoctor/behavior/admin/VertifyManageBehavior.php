<?php

namespace weapp\Systemdoctor\behavior\admin;

use think\Config;
use app\common\model\Weapp;

/**
 * 行为扩展
 */
class VertifyManageBehavior
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
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $ctlActArr = array(
            'Admin@login',
            'Admin@vertify',
        );
        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        if (in_array($ctlActStr, $ctlActArr)) {
            // 获取插件信息
            $weapp = Weapp::get(array('code' => 'Systemdoctor'));

            // 判断插件是否启用
            if (intval($weapp->status) !== 1) {
                return true;
            }

            // 获取插件配置信息
            $row = json_decode($weapp->data, true);
            $baseConfig = Config::get("captcha");
            if (!empty($row)) {
                foreach ($row['captcha'] as $key => $val) {
                    if ('default' == $key) {
                        $baseConfig[$key] = array_merge($baseConfig[$key], $val);
                    } else {
                        $baseConfig[$key]['is_on'] = $val['is_on'];
                        $baseConfig[$key]['config'] = array_merge($baseConfig['default'], $val['config']);
                    }
                }
            }
            // var_dump($baseConfig);exit;
            Config::set('captcha', $baseConfig);
        }

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

    }
}