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

namespace weapp\ClearCopyright\behavior\admin;

use think\Config;
use app\common\model\Weapp;

/**
 * 行为扩展
 */
class ClearCopyrightBehavior
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
		$weapp = Weapp::get(array('code' => 'ClearCopyright'));
		// 获取插件配置信息
		$row = json_decode($weapp->data, true);
		if (!empty($row)) {
		    foreach ($row['captcha'] as $key => $val) {
				if ('ClearCopyright-plus' == $key && 1 == $val['is_on']) {
					$session_key2 = binaryJoinChar(config('binary.13'), 24);
					session($session_key2, 0);
					$iseyKey = array_join_string(array('I','X','dl','Yl9','pc','1','9','hd','XRo','b3','J0b','2t','lb','n4','='));
					$iseyKey = msubstr($iseyKey, 1, strlen($iseyKey) - 2);
					$langRow = \think\Db::name('language')->order('id asc')->select();
					foreach ($langRow as $key => $val) {
						tpCache('web', [$iseyKey=>0], $val['mark']);
					}
          
					$com_file = APP_PATH.'/common.php';
					$comstr = 'if ($v != $temp[$newK]) {';
					$comtip = 'if($v == -1 && $newK == \''.$iseyKey.'\') { continue; }if($v!=$temp[$newK]){';
					$com_body = file_get_contents($com_file, LOCK_SH);
					$comkey = strstr($com_body, $comstr);
					if($comkey) {
						file_put_contents($com_file, str_replace( $comstr, $comtip, $com_body ), LOCK_EX);
					}
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