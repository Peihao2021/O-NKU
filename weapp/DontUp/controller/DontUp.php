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

namespace weapp\DontUp\controller;

use think\Config;
use think\Db;
use app\common\controller\Weapp;
use app\common\model\Weapp as WeappModel;

/**
 * 插件的控制器
 */
class DontUp extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 实例化对象
     */
    private $db;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件后台管理
     */
    public function index()
    {
        // 获取插件数据
        $row = WeappModel::get(array('code' => $this->weappInfo['code']));

        if ($this->request->isPost()) {
            // 获取post参数
            $inc_type = input('inc_type/s', 'DontUp-plus');
            $param = $this->request->only('captcha');
            $config = json_decode($row->data, true);

			$config['captcha'][$inc_type]['is_on'] = $param['captcha'][$inc_type]['is_on'];
			$config['captcha'][$inc_type]['yunplugin_hide'] = $param['captcha'][$inc_type]['yunplugin_hide'];
			$config['captcha'][$inc_type]['api_hide'] = $param['captcha'][$inc_type]['api_hide'];
			$config['captcha'][$inc_type]['sys_hide'] = $param['captcha'][$inc_type]['sys_hide'];
			if (isset($config['captcha'][$inc_type]['config'])) {
				$config['captcha'][$inc_type]['config'] = array_merge($config['captcha'][$inc_type]['config'], $param['captcha'][$inc_type]['config']);
			} else {
				$config['captcha'][$inc_type]['config'] = $param['captcha'][$inc_type]['config'];
			}
            // 转json赋值
            $row->data = json_encode($config);
            // 更新数据
            $r = $row->save();

            if ($r !== false) {
				$pay_file = APP_PATH.'/admin/template/index/welcome.htm';
				$seo_file = APP_PATH.'/admin/template/system/web2.htm';
				if (1 == $param['captcha'][$inc_type]['is_on']) {
					file_put_contents($pay_file, str_replace( 'if (2 == res.data.code) {', 'if(2==res.data.code){$("#td_upgrade_msg").html("稳定版");return false;', file_get_contents($pay_file, LOCK_SH) ), LOCK_EX);
					file_put_contents($seo_file, str_replace( ['{eq name="upgrade" value="true"}', '{if condition="$php_servicemeal >= 2"}'], ['{eq name="upgrade" value="0"}', '{if condition="$php_servicemeal >= 10"}'], file_get_contents($seo_file, LOCK_SH) ), LOCK_EX);
				} else {
					file_put_contents($pay_file, str_replace( 'if(2==res.data.code){$("#td_upgrade_msg").html("稳定版");return false;', 'if (2 == res.data.code) {', file_get_contents($pay_file, LOCK_SH) ), LOCK_EX);
					file_put_contents($seo_file, str_replace( ['{eq name="upgrade" value="0"}', '{if condition="$php_servicemeal >= 10"}'], ['{eq name="upgrade" value="true"}', '{if condition="$php_servicemeal >= 2"}'], file_get_contents($seo_file, LOCK_SH) ), LOCK_EX);
				}
				$rolestr = array(); //权限管理
				$rolerep = array(); //权限管理
				$indexstr = array(); //首页快捷导航
				$indexrep = array(); //首页快捷导航
				$switch_map = APP_PATH.'/admin/common.php';
				$switch_model = APP_PATH.'/admin/template/index/switch_map.htm';
				if (1 == $param['captcha'][$inc_type]['api_hide']) {
					array_push($rolestr, '$auth_rule_list = group_same_key($auth_rules, \'menu_id\');');
					array_push($rolerep, '$auth_rule_list=group_same_key($auth_rules,\'menu_id\');$hideapi=array_column($auth_rule_list[2001],\'name\');$apikey=array_search(\'接口配置\',$hideapi);unset($auth_rule_list[2001][$apikey]);');
					array_push($indexstr, 'foreach ($quickMenu as $key => $val) {', '$this->assign(\'menuList\',$menuList);');
					array_push($indexrep, 'foreach($quickMenu as $key=>$val){if($val[\'action\']===\'api_conf\'){unset($quickMenu[$key]);continue;}', 'foreach($menuList as $key=>$val){if($val[\'action\']===\'api_conf\'){unset($menuList[$key]);}}$this -> assign(\'menuList\', $menuList);');
					file_put_contents($switch_map, str_replace( '$bool_flag = 1;', '$bool_flag=1;$arr=explode("@",strtolower($str));if($arr[1]=="api_conf"||$arr[1]=="pay_api_index"){$bool_flag=false;}', file_get_contents($switch_map, LOCK_SH) ), LOCK_EX);
				} else {
					array_push($rolestr, '$auth_rule_list=group_same_key($auth_rules,\'menu_id\');$hideapi=array_column($auth_rule_list[2001],\'name\');$apikey=array_search(\'接口配置\',$hideapi);unset($auth_rule_list[2001][$apikey]);');
					array_push($rolerep, '$auth_rule_list = group_same_key($auth_rules, \'menu_id\');');
					array_push($indexstr, 'foreach($quickMenu as $key=>$val){if($val[\'action\']===\'api_conf\'){unset($quickMenu[$key]);continue;}', 'foreach($menuList as $key=>$val){if($val[\'action\']===\'api_conf\'){unset($menuList[$key]);}}$this -> assign(\'menuList\', $menuList);');
					array_push($indexrep, 'foreach ($quickMenu as $key => $val) {', '$this->assign(\'menuList\',$menuList);');
					file_put_contents($switch_map, str_replace( '$bool_flag=1;$arr=explode("@",strtolower($str));if($arr[1]=="api_conf"||$arr[1]=="pay_api_index"){$bool_flag=false;}', '$bool_flag = 1;', file_get_contents($switch_map, LOCK_SH) ), LOCK_EX);
				}
				if (1 == $param['captcha'][$inc_type]['sys_hide']) {
					array_push($rolestr, '$this->assign(\'auth_rule_list\', $auth_rule_list);');
					array_push($rolerep, '$hidemod=array_column($auth_rule_list[2004],\'name\');$modkey=array_search(\'模块开关\',$hidemod);unset($auth_rule_list[2004][$modkey]);if(tpCache(\'global\')[\'web_citysite_open\']==0){$citykey=array_search(\'城市分站\',$hidemod);unset($auth_rule_list[2004][$citykey]);}$this->assign(\'auth_rule_list\',$auth_rule_list);');
					file_put_contents($switch_map, str_replace( 'if (0 < intval($role_id)) {', '$arr=explode("@",strtolower($str));if($arr[1]=="switch_map"||$arr[1]=="switch_map_0"){$bool_flag=false;}if(0<intval($role_id)){', file_get_contents($switch_map, LOCK_SH) ), LOCK_EX);
					file_put_contents($switch_model, str_replace( '<div class="on-off_btns">', '<style>.bodystyle>.on-off_panel:first-child{display:none;}</style><div class="on-off_btns hidemodel">', file_get_contents($switch_model, LOCK_SH) ), LOCK_EX);
				} else {
					array_push($rolestr, '$hidemod=array_column($auth_rule_list[2004],\'name\');$modkey=array_search(\'模块开关\',$hidemod);unset($auth_rule_list[2004][$modkey]);if(tpCache(\'global\')[\'web_citysite_open\']==0){$citykey=array_search(\'城市分站\',$hidemod);unset($auth_rule_list[2004][$citykey]);}$this->assign(\'auth_rule_list\',$auth_rule_list);');
					array_push($rolerep, '$this->assign(\'auth_rule_list\', $auth_rule_list);');
					file_put_contents($switch_map, str_replace( '$arr=explode("@",strtolower($str));if($arr[1]=="switch_map"||$arr[1]=="switch_map_0"){$bool_flag=false;}if(0<intval($role_id)){', 'if (0 < intval($role_id)) {', file_get_contents($switch_map, LOCK_SH) ), LOCK_EX);
					file_put_contents($switch_model, str_replace( '<style>.bodystyle>.on-off_panel:first-child{display:none;}</style><div class="on-off_btns hidemodel">', '<div class="on-off_btns">', file_get_contents($switch_model, LOCK_SH) ), LOCK_EX);
				}
				
				if (1 == $param['captcha'][$inc_type]['config']['is_hide']) {
					array_push($rolestr, '$this->assign(\'plugins\', $plugins);');
					array_push($rolerep, '$hideplu=array_column($plugins,\'data\');$plucode=array_column($plugins,\'code\');foreach($hideplu as $key=>$val){if(strpos($val,\'"is_hide":"1"\')!==false){unset($plugins[$plucode[$key]]);}}$this -> assign(\'plugins\',$plugins);');
				} else {
					array_push($rolestr, '$hideplu=array_column($plugins,\'data\');$plucode=array_column($plugins,\'code\');foreach($hideplu as $key=>$val){if(strpos($val,\'"is_hide":"1"\')!==false){unset($plugins[$plucode[$key]]);}}$this -> assign(\'plugins\',$plugins);');
					array_push($rolerep, '$this->assign(\'plugins\', $plugins);');
				}
				
				$admin_con = APP_PATH.'/admin/controller/Admin.php';
				$auth_con = APP_PATH.'/admin/controller/AuthRole.php';
				$c_index = APP_PATH.'/admin/controller/Index.php';
				file_put_contents($admin_con, str_replace( $rolestr, $rolerep, file_get_contents($admin_con, LOCK_SH) ), LOCK_EX);
				file_put_contents($auth_con, str_replace( $rolestr, $rolerep, file_get_contents($auth_con, LOCK_SH) ), LOCK_EX);
				file_put_contents($c_index, str_replace( $indexstr, $indexrep, file_get_contents($c_index, LOCK_SH) ), LOCK_EX);
				
				adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                $this->success("操作成功!", weapp_url('DontUp/DontUp/index', ['inc_type'=>$inc_type]));
            }
            $this->error("操作失败!");
        }

        $inc_type = input('param.inc_type/s', 'DontUp-plus');

        // 获取配置JSON信息转数组
        $config = json_decode($row->data, true);
        $baseConfig = Config::get("captcha");

		if (isset($config['captcha'][$inc_type])) {
			$row = $config['captcha'][$inc_type];
		} else {
			$baseConfig[$inc_type]['config'] = !empty($config['captcha']['default']) ? $config['captcha']['default'] : $baseConfig['default'];
			$row = $baseConfig[$inc_type];
		}

        $this->assign('row', $row);
        $this->assign('inc_type', $inc_type);
        return $this->fetch($inc_type);
    }

}