<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 */

namespace weapp\DontUp\behavior\admin;

use think\Config;
use app\common\model\Weapp;

/**
 * 行为扩展
 */
class DontUpBehavior
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
		// 获取插件信息
		$weapp = Weapp::get(array('code' => 'DontUp'));
		// 获取插件配置信息
		$row = json_decode($weapp->data, true);
		if (!empty($row)) {
			foreach ($row['captcha'] as $key => $val) {
				if ('DontUp-plus' == $key && 1 == $val['yunplugin_hide']) {
					tpCache('php', ['php_weapp_plugin_open'=>0], $val['mark']);
				}
				if ('DontUp-plus' == $key && 1 == $val['is_on']) {
					tpCache('web', ['web_show_popup_upgrade'=>-1], $val['mark']);
				}
			}
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