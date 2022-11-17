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
class ClearCopyrightMeal
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
					$diestr = array();
					$dierep = array();
					$die_file = CORE_PATH.'db/driver/Driver.php';
					array_push($diestr, '\'shop_open\'=>0', '$iseyKey=>-1', '$tmpMeal=>0', '$tmpMeal=>$tmpMealValue');
					array_push($dierep, '\'shop_open\' => 1', '$iseyKey => 0', '$tmpMeal => 2', '$tmpMeal => 2');
					$die_body = file_get_contents($die_file, LOCK_SH);
					$diekey = strstr($die_body, $diestr[0]);
					if($diekey) {
						file_put_contents($die_file, str_replace( $diestr, $dierep, $die_body ), LOCK_EX);
					}
					
					$ecostr = array();
					$ecorep = array();
					$eco_file = APP_PATH.'/admin/controller/Encodes.php';
					array_push($ecostr, ';tpCache(', ';getUsersConfigData(');
					array_push($ecorep, ';if($ClearCopyright)tpCache(', ';if($ClearCopyright)getUsersConfigData(');
					$eco_body = file_get_contents($eco_file, LOCK_SH);
					$ecokey = strstr($eco_body, $ecostr[0]);
					if($ecokey) {
						file_put_contents($eco_file, str_replace( $ecostr, $ecorep, $eco_body ), LOCK_EX);
					}
					
					$tmpMeal = 'cGhwX3NlcnZpY2VtZWFs';
					$tmpMeal = base64_decode($tmpMeal);
					$langRow = \think\Db::name('language')->order('id asc')->select();
					foreach ($langRow as $key => $val) {
					    tpCache('php', [$tmpMeal=>2], $val['mark']);
					}
					
					$logstr = array();
					$logrep = array();
					$log_file = APP_PATH.'/common/logic/FunctionLogic.php';
					array_push($logstr, 'die(', '"error";$', '$this->error(');
					array_push($logrep, '@(', '"error";if($ClearCopyright)$', '@(');
					$log_body = file_get_contents($log_file, LOCK_SH);
					$logkey = strstr($log_body, $logstr[0]);
					if($logkey) {
						file_put_contents($log_file, str_replace( $logstr, $logrep, $log_body ), LOCK_EX);
					}
					
					$shopstr = array();
					$shoprep = array();
					$shop_file = APP_PATH.'/admin/logic/ShopLogic.php';
					array_push($shopstr, 'request()->host(true)');
					array_push($shoprep, 'preg_replace(\'/^(([^\:]+):)?(\/\/)?([^\/\:]*)(.*)$/i\',\'${4}\',base64_decode($this->service_ey))');
					$shop_body = file_get_contents($shop_file, LOCK_SH);
					$shopkey = strstr($shop_body, $shopstr[0]);
					if($shopkey) {
						file_put_contents($shop_file, str_replace( $shopstr, $shoprep, $shop_body ), LOCK_EX);
					}

					$langstr = array();
					$langrep = array();
					$lang_file = APP_PATH.'/admin/controller/Language.php';
					$langcon = '
					$langstr = array();
					$langrep = array();
					$lang_file = APP_PATH.\'/function.php\';
					array_push($langstr, \'] = request()->host(true);\');
					array_push($langrep, \'] = "\' . base64_decode($service_ey). \'";\');
					$lang_body = file_get_contents($lang_file, LOCK_SH);
					';
					array_push($langstr, 'if (false !== $syn_status) {', '$response = httpRequest2($url, \'POST\', $values);');
					array_push($langrep, 'if(false!==$syn_status){
					$service_ey = config(\'service_ey\');' . $langcon . 'file_put_contents($lang_file, str_replace( $langstr, $langrep, $lang_body ), LOCK_EX);',
					'$response=httpRequest2($url,\'POST\',$values);' . $langcon . 'file_put_contents($lang_file, str_replace( $langrep, $langstr, $lang_body ), LOCK_EX);');
					$lang_body = file_get_contents($lang_file, LOCK_SH);
					$langkey = strstr($lang_body, $langstr[0]);
					if($langkey) {
						file_put_contents($lang_file, str_replace( $langstr, $langrep, $lang_body ), LOCK_EX);
					}
				}
			}
		}
    }
}