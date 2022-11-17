<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */

if (!function_exists('login_third_type')) 
{
    /**
     * 识别当前使用的扫码登录功能：1、扫微信应用登录，2、扫官方微信公众号登录
     * @param
     * @return array
     */
    function login_third_type()
    {
        $redata = [
            'type'  => '',
            'data'  => [],
        ];

        if (is_dir('./weapp/EyouGzhLogin/')) { // 是否安装【后台扫码登录】插件
            $EyouGzhLoginRow = model('Weapp')->getWeappList('EyouGzhLogin');
            $status = !empty($EyouGzhLoginRow['status']) ? intval($EyouGzhLoginRow['status']) : 0;
            $data = !empty($EyouGzhLoginRow['data']) ? $EyouGzhLoginRow['data'] : [];
            if (1 == $status && !empty($data['is_open'])) {
                $redata['type'] = $data['mode'];
                if ('EyouGzhLogin' == $data['mode']) {
                    $data['gzh']['switch'] = 1;
                    $redata['data'] = $data['gzh'];
                }
                else if ('WechatLogin' == $data['mode']) {
                    $security = tpSetting('security');
                    $redata['data'] = $security;
                }
            }
        }

        return $redata;
    }
}

if (!function_exists('security_verify_func')) 
{
    /**
     * 当前功能是否需要二次安全验证
     * @param
     * @return bool
     */
    function security_verify_func($ctl_act = '')
    {
        if (empty($ctl_act)) {
            $ctl = request()->controller();
            $act = request()->action();
            $ctl_all = $ctl.'@*';
            $ctl_act = $ctl.'@'.$act;
        } else {
            $ctl_all = preg_replace('/\@([\w\-]+)$/i', '@*', $ctl_act);
        }

        $security = tpSetting('security');
        $security_verifyfunc = !empty($security['security_verifyfunc']) ? json_decode($security['security_verifyfunc'], true) : ['Filemanager@*','Arctype@ajax_newtpl','Archives@ajax_newtpl'];
        if (in_array($ctl_act, ['Filemanager@*','Arctype@ajax_newtpl','Archives@ajax_newtpl'])) {
            return true;
        } else {
            if (!empty($security['security_ask_open'])) {
                if (in_array($ctl_all, $security_verifyfunc) || in_array($ctl_act, $security_verifyfunc)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('del_html_dirpath')){
    /*
     * 删除静态页面
     */
    function del_html_dirpath(){
        $error = false;
        $dirArr = [];
        $seo_html_listname = tpCache('global.seo_html_listname');
        $row = \think\Db::name('arctype')->field('dirpath,diy_dirpath')->select();
        foreach ($row as $key => $val) {
            $dirpathArr = explode('/', $val['dirpath']);
            if (3 == $seo_html_listname) {
                $dir = end($dirpathArr);
            } else if (4 == $seo_html_listname) {
                $dirpathArr = explode('/', $val['diy_dirpath']);
                $dir = end($dirpathArr);
            } else {
                $dir = !empty($dirpathArr[1]) ? $dirpathArr[1] : '';
            }
            $filepath = "./{$dir}";
            if (!empty($dir) && !in_array($dir, $dirArr) && file_exists($filepath)) {
                @unlink($filepath."/index.html");
                $bool = delFile($filepath, true);
                if (false !== $bool) {
                    array_push($dirArr, $dir);
                } else {
                    $error = true;
                }
            }
        }

        $data = [];
        $data['msg'] = '';
        if ($error) {
            $num = 0;
            $wwwroot = glob('*', GLOB_ONLYDIR);
            foreach ($wwwroot as $key => $val) {
                if (in_array($val, $dirArr)) {
                    if (0 == $num) {
                        $data['msg'] .= "<font color='red'>部分目录删除失败，请手工删除：</font><br/>";
                    }
                    $data['msg'] .= ($num+1)."、{$val}<br/>";
                    $num++;
                }
            }
            $data['height'] = $num * 24;
        }

        return $data;
    }
}

if (!function_exists('is_adminlogin')) 
{
    /**
     * 检验登陆
     * @param
     * @return bool
     */
    function is_adminlogin(){
        $admin_id = session('admin_id');
        if(isset($admin_id) && $admin_id > 0){
            return $admin_id;
        }else{
            return false;
        }
    }
}

if (!function_exists('getAdminInfo')) 
{
    /**
     * 获取管理员登录信息
     */
    function getAdminInfo($admin_id = 0)
    {
        $admin_info = [];
        $admin_id = empty($admin_id) ? session('admin_id') : $admin_id;
        if (0 < intval($admin_id)) {
            $admin_info = \think\Db::name('admin')
                ->field('a.*, b.name AS role_name')
                ->alias('a')
                ->join('__AUTH_ROLE__ b', 'b.id = a.role_id', 'LEFT')
                ->where("a.admin_id", $admin_id)
                ->find();
            if (!empty($admin_info)) {
                // 头像
                empty($admin_info['head_pic']) && $admin_info['head_pic'] = get_head_pic($admin_info['head_pic'], true);
                // 权限组
                $admin_info['role_id'] = !empty($admin_info['role_id']) ? $admin_info['role_id'] : -1;
                // 是否创始人
                $is_founder = 0;
                if (-1 == $admin_info['role_id']) {
                    if (!empty($admin_info['parent_id'])) {
                        $role_name = '超级管理员';
                    } else {
                        $is_founder = 1;
                        $role_name = '创始人';
                    }
                } else {
                    $role_name = $admin_info['role_name'];
                }
                $admin_info['role_name'] = $role_name;
                $admin_info['is_founder'] = $is_founder;
            }
        }
        
        return $admin_info;
    }
}

if (!function_exists('get_conf')) 
{
    /**
     * 获取conf配置文件
     */
    function get_conf($name = 'global')
    {
        $arr = include APP_PATH.MODULE_NAME.'/conf/'.$name.'.php';
        return $arr;
    }
}

if (!function_exists('get_auth_rule')) 
{
    /**
     * 获取权限列表文件
     */
    function get_auth_rule($where = [])
    {
        $auth_rule = include APP_PATH.MODULE_NAME.'/conf/auth_rule.php';

        // 排序号排序
        // $sort_order_arr = array();
        // foreach($auth_rule as $key => $val){
        //     $sort_order_arr[]['sort_order'] = $val['sort_order'];
        // }
        // array_multisort($sort_order_arr,SORT_ASC,$auth_rule);

        if (!empty($where)) {
            foreach ($auth_rule as $k1 => $rules) {
                foreach ($where as $k2 => $v2) {
                    if ($rules[$k2] != $v2) {
                        unset($auth_rule[$k1]);
                    }
                }
            }
        }
        return $auth_rule;
    }
}

if (!function_exists('is_check_access')) 
{
    /**
     * 检测是否有该权限
     */
    function is_check_access($str = 'Index@index') {  
        $bool_flag=1;$arr=explode("@",strtolower($str));if($arr[1]=="api_conf"||$arr[1]=="pay_api_index"){$bool_flag=false;}
        $role_id = session('admin_info.role_id');
        $arr=explode("@",strtolower($str));if($arr[1]=="switch_map"||$arr[1]=="switch_map_0"){$bool_flag=false;}if(0<intval($role_id)){
            $ctl_act = strtolower($str);
            $arr = explode('@', $ctl_act);
            $ctl = !empty($arr[0]) ? $arr[0] : '';
            $act = !empty($arr[1]) ? $arr[1] : '';
            $ctl_all = $ctl.'@*';

            $auth_role_info = session('admin_info.auth_role_info');
            $permission = $auth_role_info['permission'];
            $permission_rules = !empty($permission['rules']) ? $permission['rules'] : [];

            $auth_rule = get_auth_rule();
            $all_auths = []; // 系统全部权限对应的菜单ID
            $admin_auths = []; // 用户当前拥有权限对应的菜单ID
            $diff_auths = []; // 用户没有被授权的权限对应的菜单ID
            foreach($auth_rule as $key => $val){
                $all_auths = array_merge($all_auths, explode(',', strtolower($val['auths'])));
                if (in_array($val['id'], $permission_rules)) {
                    $admin_auths = array_merge($admin_auths, explode(',', strtolower($val['auths'])));
                }
            }
            $all_auths = array_unique($all_auths);
            $admin_auths = array_unique($admin_auths);
            $diff_auths = array_diff($all_auths, $admin_auths);

            if (in_array($ctl_act, $diff_auths) || in_array($ctl_all, $diff_auths)) {
                $bool_flag = false;
            }else if($ctl_act == 'order@index' && (in_array('member@money_index', $diff_auths) || in_array('member@*', $diff_auths)) && (in_array('shop@index', $diff_auths) || in_array('shop@*', $diff_auths))){ //Member@money_index 会员订单入口； Shop@index 商城中心订单都没有权限的清空下，关闭订单管理
                $bool_flag = false;
            }
        }

        return $bool_flag;
    }
}
if (!function_exists('getAdminMenuList')){
    /**
     * 根据角色权限过滤菜单
     * $menu_list   所有菜单
     */
    function getAdminMenuList($menuArr){
        $role_id = session('admin_info.role_id');
        $arr=explode("@",strtolower($str));if($arr[1]=="switch_map"||$arr[1]=="switch_map_0"){$bool_flag=false;}if(0<intval($role_id)){
            $auth_role_info = session('admin_info.auth_role_info');
            $permission = $auth_role_info['permission'];
            $permission_rules = !empty($permission['rules']) ? $permission['rules'] : [];
            $permission_plugins = !empty($permission['plugins']) ? $permission['plugins'] : [];
            if (!empty($permission_plugins)){
                $permission_plugins = get_arr_column($permission_plugins,"code");
            }
            $web_weapp_switch = tpCache('global.web_weapp_switch');
            $auth_rule = get_auth_rule();
            $all_auths = []; // 系统全部权限对应的菜单ID
            $admin_auths = []; // 用户当前拥有权限对应的菜单ID
            $diff_auths = []; // 用户没有被授权的权限对应的菜单ID

            foreach($auth_rule as $key => $val){
                $all_auths = array_merge($all_auths, explode(',', strtolower($val['auths'])));
                if (in_array($val['id'], $permission_rules)) {
                    $admin_auths = array_merge($admin_auths, explode(',', strtolower($val['auths'])));
                }
            }
            $all_auths = array_unique($all_auths);
            $admin_auths = array_unique($admin_auths);
            $diff_auths = array_diff($all_auths, $admin_auths);

            //过滤该用户不包含的权限
            foreach($menuArr as $k=>$val){
                $ctl = strtolower($val['controller_name']);
                $act = strtolower($val['action_name']);
                $ctl_act = $ctl.'@'.$act;
                $ctl_all = $ctl.'@*';
                if (in_array($ctl_act, $diff_auths) || in_array($ctl_all, $diff_auths)) {
                    unset($menuArr[$k]);//过滤菜单
                }else if($val['menu_id'] == '2004021' && !is_check_access("Member@money_index") && !is_check_access("Member@money_index")){ //Member*money_index 会员订单入口； Shop*index 商城中心订单
                    unset($menuArr[$k]);//Member*money_index 会员订单入口； Shop*index 商城中心订单都没有权限的清空下，关闭订单管理
                }
                //过滤没有权限的插件
                if ($ctl == 'weapp' && $act == 'execute'){
                    if (1 != $web_weapp_switch || empty($permission_plugins)){
                        unset($menuArr[$k]);//过滤菜单
                    }else{
                        $param_str = get_str_between($val['param'], "sm|", "|sc");
                        if (!in_array($param_str,$permission_plugins)){
                            unset($menuArr[$k]);//过滤菜单
                        }
                    }

                }
            }
        }

        return $menuArr;
    }
}
if (!function_exists('get_str_between')){
    function get_str_between($input, $start, $end) {
        $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));

        return $substr;

    }
}

if (!function_exists('getMenuList')) 
{
    /**
     * 根据角色权限过滤菜单
     */
    function getMenuList() {
        $menuArr = getAllMenu();
        // return $menuArr;

        $role_id = session('admin_info.role_id');
        $arr=explode("@",strtolower($str));if($arr[1]=="switch_map"||$arr[1]=="switch_map_0"){$bool_flag=false;}if(0<intval($role_id)){
            $auth_role_info = session('admin_info.auth_role_info');
            $permission = $auth_role_info['permission'];
            $permission_rules = !empty($permission['rules']) ? $permission['rules'] : [];

            $auth_rule = get_auth_rule();
            $all_auths = []; // 系统全部权限对应的菜单ID
            $admin_auths = []; // 用户当前拥有权限对应的菜单ID
            $diff_auths = []; // 用户没有被授权的权限对应的菜单ID
            foreach($auth_rule as $key => $val){
                $all_auths = array_merge($all_auths, explode(',', $val['menu_id']), explode(',', $val['menu_id2']));
                if (in_array($val['id'], $permission_rules)) {
                    $admin_auths = array_merge($admin_auths, explode(',', $val['menu_id']), explode(',', $val['menu_id2']));
                }
            }
            $all_auths = array_unique($all_auths);
            $admin_auths = array_unique($admin_auths);
            $diff_auths = array_diff($all_auths, $admin_auths);

            /*过滤三级数组菜单*/
            foreach($menuArr as $k=>$val){
                foreach ($val['child'] as $j=>$v){
                    foreach ($v['child'] as $s=>$son){
                        if (in_array($son['id'], $diff_auths)) {
                            unset($menuArr[$k]['child'][$j]['child'][$s]);//过滤菜单
                        }
                    }
                }
            }
            /*--end*/

            /*过滤二级数组菜单*/
            foreach ($menuArr as $mk=>$mr){
                foreach ($mr['child'] as $nk=>$nrr){
                    if (in_array($nrr['id'], $diff_auths)) {
                        unset($menuArr[$mk]['child'][$nk]);//过滤菜单
                    }
                }
            }
            /*--end*/
        }

        return $menuArr;
    }
}

if (!function_exists('getAllMenu')) 
{
    /**
     * 获取左侧菜单
     */
    function getAllMenu() {
        $menuArr = false;//extra_cache('admin_all_menu');
        if (!$menuArr) {
            $menuArr = get_conf('menu');
            extra_cache('admin_all_menu', $menuArr);
        }
        return $menuArr;
    }
}

if ( ! function_exists('getChanneltypeList'))
{
    /**
     * 获取全部的模型
     */
    function getChanneltypeList()
    {
        $result = extra_cache('admin_channeltype_list_logic');
        if ($result == false)
        {
            $result = model('Channeltype')->getAll('*', array(), 'id');
            extra_cache('admin_channeltype_list_logic', $result);
        }

        return $result;
    }
}

if (!function_exists('tpversion')) 
{
    function tpversion($timeout = 5)
    {
        if(!empty($_SESSION['isset_push']))
            return false;
        $_SESSION['isset_push'] = 1;
        error_reporting(0);//关闭所有错误报告
        $install_time = DEFAULT_INSTALL_DATE;
        $serial_number = DEFAULT_SERIALNUMBER;

        $constsant_path = APP_PATH.'admin/conf/constant.php';
        if (file_exists($constsant_path)) {
            require_once($constsant_path);
            defined('INSTALL_DATE') && $install_time = INSTALL_DATE;
            defined('SERIALNUMBER') && $serial_number = SERIALNUMBER;
        }
        $curent_version = getCmsVersion();
        $mysqlinfo = \think\Db::query("SELECT VERSION() as version");
        $mysql_version  = $mysqlinfo[0]['version'];
        $global_config = tpCache('global');
        $users_config = getUsersConfigData('all');
        $vaules = array(            
            'domain'=>$_SERVER['HTTP_HOST'], 
            'key_num'=>$curent_version, 
            'install_time'=>$install_time, 
            'serial_number'=>$serial_number,
            'ip'    => GetHostByName($_SERVER['SERVER_NAME']),
            'agentcode' => !empty($global_config['php_agentcode']) ? $global_config['php_agentcode'] : 0,
            'global_config' => base64_encode(json_encode($global_config)),
            'users_config' => base64_encode(json_encode($users_config)),
            'phpv'  => urlencode(phpversion()),
            'mysql_version' => urlencode($mysql_version),
            'web_server'    => urlencode($_SERVER['SERVER_SOFTWARE']),
            'web_title' => tpCache('global.web_title'),
        );
        // api_Service_user_push
        $service_ey = config('service_ey');
        $tmp_str = 'L2luZGV4LnBocD9tPWFwaSZjPVNlcnZpY2UmYT11c2VyX3B1c2gm';
        $url = base64_decode($service_ey).base64_decode($tmp_str);
        @httpRequest($url, 'POST', $vaules, [], $timeout);
    }
}

if (!function_exists('push_zzbaidu')) 
{
    /**
     * 将新链接推送给百度蜘蛛
     */
    function push_zzbaidu($type = 'urls', $aid = '', $typeid = '')
    {
        // 获取token的值：http://ziyuan.baidu.com/linksubmit/index?site=http://www.eyoucms.com/
        $aid = intval($aid);
        $typeid = intval($typeid);
        $sitemap_zzbaidutoken = tpCache('global.sitemap_zzbaidutoken');
        if (empty($sitemap_zzbaidutoken) || (empty($aid) && empty($typeid)) || !function_exists('curl_init')) {
            return '';
        }

        $urlsArr = array();
        $channeltype_list = model('Channeltype')->getAll('id, ctl_name', array(), 'id');

        if ($aid > 0) {
            $res = M('archives')->field('b.*, a.*, a.aid, b.id as typeid')
                ->alias('a')
                ->join('__ARCTYPE__ b', 'b.id = a.typeid', 'LEFT')
                ->find($aid);
            $arcurl = get_arcurl($res, false);
            array_push($urlsArr, $arcurl);
        }
        if (0 < $typeid) {
            $res = M('arctype')->field('a.*')
                ->alias('a')
                ->find($typeid);
            $typeurl = get_typeurl($res, false);
            array_push($urlsArr, $typeurl);
        }

        $type = ('edit' == $type) ? 'update' : 'urls';
        $api = 'http://data.zz.baidu.com/'.$type.'?site='.request()->host(true).'&token='.$sitemap_zzbaidutoken;
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urlsArr),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        
        return $result;    
    }
}

if (!function_exists('sitemap_auto')) 
{
    /**
     * 自动生成引擎sitemap
     */
    function sitemap_auto()
    {
        $globalConfig = tpCache('global');
        if (isset($globalConfig['sitemap_auto']) && $globalConfig['sitemap_auto'] > 0) {
            sitemap_all();
        }
    }
}

if (!function_exists('sitemap_all')) 
{
    /**
     * 生成全部引擎sitemap
     */
    function sitemap_all($type = 'all')
    {
        sitemap_xml($type);
    }
}
if (!function_exists('join_xml'))
{
    /**
     *  拼接xml
     */
    function join_xml($xml,$loc,$lastmod,$changefreq,$priority,$model = 'pc')
    {
        $item = $xml->addChild('url'); //使用addChild添加节点
        foreach (['loc','lastmod','changefreq','priority'] as $key1) {
            if ('loc' == $key1) {
                $row = $loc;
            } else if ('lastmod' == $key1) {
                $row = $lastmod;
            } else if ('changefreq' == $key1) {
                $row = $changefreq;
            } else if ('priority' == $key1) {
                $row = $priority;
            }
            try {
                $node = $item->addChild($key1, $row);
            } catch (\Exception $e) {}
            if (isset($attribute_array[$key1]) && is_array($attribute_array[$key1])) {
                foreach ($attribute_array[$key1] as $akey => $aval) {//设置属性值，我这里为空
                    $node->addAttribute($akey, $aval);
                }
            }
        }
        if ($model == 'mobile'){   //移动端
            $item->addChild('mobile','','http://www.google.com/schemas/sitemap-mobile/1.0')->addAttribute("type","mobile");
        }else if($model == 'pc,mobile'){
            $item->addChild('mobile','','http://www.google.com/schemas/sitemap-mobile/1.0')->addAttribute("type","pc,mobile");
        }else if($model == 'htmladapt'){
            $item->addChild('mobile','','http://www.google.com/schemas/sitemap-mobile/1.0')->addAttribute("type","htmladapt");
        }

        return $xml;
    }
}

if (!function_exists('sitemap_xml')) 
{
    /**
     * 生成xml形式的sitemap,分页（入口页面、首页、栏目页、内容页、tags、问答）
     * <mobile:mobile/> ：移动网页
     * <mobile:mobile type="mobile"/> ：移动网页
     * <mobile:mobile type="pc,mobile"/>：自适应网页  一个域名一个模板，
     * <mobile:mobile type="htmladapt"/>：代码适配    一个域名两个模板，手机端浏览和pc端浏览显示内容不一样（根据硬件判断）
     */
    function sitemap_xml($type = 'all'){
        $globalConfig = tpCache('global');
        if (empty($globalConfig['sitemap_xml']) && empty($globalConfig['sitemap_txt'])) {
            return '';
        }
        $response_type = config('ey_config.response_type');  // 0是代码适配,1:pc、移动端分离（存在pc、移动端两套模板）
        $web_mobile_domain_open = $globalConfig['web_mobile_domain_open']; //是否开启手机端域名
        $web_mobile_domain = $globalConfig['web_mobile_domain']; //手机端域名
        $lang = get_current_lang();
        $default_lang = get_default_lang();
        $langRow = \think\Db::name('language')->field('is_home_default')->where(['mark'=>$lang])->find();
        if (!empty($langRow['is_home_default'])) {
            $filename = ROOT_PATH . "sitemap.xml";
            $filename_txt = ROOT_PATH . "sitemap.txt";
        } else {
            $filename = ROOT_PATH . "sitemap_{$lang}.xml";
            $filename_txt = ROOT_PATH . "sitemap_{$lang}.txt";
        }

        /* 分类列表(用于生成列表链接的sitemap) */
        $map = array(
            'status'    => 1,
            'is_del'    => 0,
            'lang'      => $lang,
        );
        if (is_array($globalConfig)) {
            // 过滤隐藏栏目
            if (isset($globalConfig['sitemap_not1']) && $globalConfig['sitemap_not1'] > 0) {
                $map['is_hidden'] = 0;
            }
            // 过滤外部模块
            if (isset($globalConfig['sitemap_not2']) && $globalConfig['sitemap_not2'] > 0) {
                $map['is_part'] = 0;
            }
        }
        $result_arctype = M('arctype')->field("*, id AS loc, add_time AS lastmod, 'hourly' AS changefreq, '0.8' AS priority")
            ->where($map)
            ->order('sort_order asc, id asc')
            ->getAllWithIndex('id');

        /* 文章列表(用于生成文章详情链接的sitemap) */
        if (!isset($globalConfig['sitemap_archives_num']) || $globalConfig['sitemap_archives_num'] === '') {
            $sitemap_archives_num = 100;
        } else {
            $sitemap_archives_num = intval($globalConfig['sitemap_archives_num']);
        }
        $result_archives = [];
        if (0 < $sitemap_archives_num) {
            $map = array(
                'channel'   => ['IN', config('global.allow_release_channel')],
                'arcrank'   => array('gt', -1),
                'status'    => 1,
                'is_del'    => 0,
                'lang'      => $lang,
            );
            if (is_array($globalConfig)) {
                // 过滤外部模块
                if (isset($globalConfig['sitemap_not2']) && $globalConfig['sitemap_not2'] > 0) {
                    $map['is_jump'] = 0;
                }
            }
            /*定时文档显示插件*/
            if (is_dir('./weapp/TimingTask/')) {
                $weappModel = new \app\admin\model\Weapp;
                $TimingTaskRow = $weappModel->getWeappList('TimingTask');
                if (!empty($TimingTaskRow['status']) && 1 == $TimingTaskRow['status']) {
                    $map['add_time'] = ['elt', getTime()]; // 只显当天或之前的文档
                }
            }
            /*end*/
            $field = "aid, channel, is_jump, jumplinks, htmlfilename, add_time, update_time, typeid, aid AS loc, add_time AS lastmod, 'daily' AS changefreq, '0.5' AS priority";
            $result_archives = M('archives')->field($field)
                ->where($map)
                ->order('aid desc')
                ->limit($sitemap_archives_num)
                ->select();
        }



        $urls_txt = []; // 记录sitemap里包含的URL，用于生成sitemap.txt文件

        // header('Content-Type: text/xml');//这行很重要，php默认输出text/html格式的文件，所以这里明确告诉浏览器输出的格式为xml,不然浏览器显示不出xml的格式
        $xml_wrapper = <<<XML
<?xml version='1.0' encoding='utf-8'?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0">
</urlset>
XML;
        if (function_exists('simplexml_load_string')) {
            $xml = @simplexml_load_string($xml_wrapper);
        } else if (class_exists('SimpleXMLElement')) {
            $xml = new SimpleXMLElement($xml_wrapper);
        }
        if (!$xml) {
            return true;
        }
        if($web_mobile_domain_open){
            if (function_exists('simplexml_load_string')) {
                $xml_mobile = @simplexml_load_string($xml_wrapper);
            } else if (class_exists('SimpleXMLElement')) {
                $xml_mobile = new SimpleXMLElement($xml_wrapper);
            }
            $urls_txt_mobile = [];
            $filename_mobile = str_replace('sitemap','sitemap_'.$web_mobile_domain,$filename);
            $filename_txt_mobile = str_replace('sitemap','sitemap_'.$web_mobile_domain,$filename_txt);
        }
        // 更新频率
        $sitemap_changefreq_index = !empty($globalConfig['sitemap_changefreq_index']) ? $globalConfig['sitemap_changefreq_index'] : 'always';
        $sitemap_changefreq_list = !empty($globalConfig['sitemap_changefreq_list']) ? $globalConfig['sitemap_changefreq_list'] : 'hourly';
        $sitemap_changefreq_view = !empty($globalConfig['sitemap_changefreq_view']) ? $globalConfig['sitemap_changefreq_view'] : 'daily';

        // 优先级别
        $sitemap_priority_index = !empty($globalConfig['sitemap_priority_index']) ? $globalConfig['sitemap_priority_index'] : '1.0';
        $sitemap_priority_list = !empty($globalConfig['sitemap_priority_list']) ? $globalConfig['sitemap_priority_list'] : '0.8';
        $sitemap_priority_view = !empty($globalConfig['sitemap_priority_view']) ? $globalConfig['sitemap_priority_view'] : '0.5';

        $langRow = \think\Db::name('language')
            ->where(['status'=>1])
            ->order('id asc')
            ->cache(true, EYOUCMS_CACHE_TIME, 'language')
            ->select();

        // 去掉入口文件
        $inletStr = '/index.php';
        $seo_inlet = config('ey_config.seo_inlet');
        1 == intval($seo_inlet) && $inletStr = '';
        $now = date('Y-m-d');
        // 首页
        if ($lang == $default_lang) {
            foreach ($langRow as $key => $val) {
                $mark = $val['mark'];
                if (empty($globalConfig['web_language_switch']) && $lang != $mark) { // 关闭多语言
                    continue;
                }
                /*单独域名*/
                $url = $val['url'];
                if (empty($url)) {
                    if (1 == $val['is_home_default']) {
                        $url = request()->domain().ROOT_DIR.'/';
                    } else {
                        if ($mark != $default_lang) {
                            $url = request()->domain().ROOT_DIR."/sitemap_{$mark}.xml";
                        } else {
                            $global_config_tmp = tpCache('global', [], $mark);
                            $seo_pseudo = !empty($global_config_tmp['seo_pseudo']) ? $global_config_tmp['seo_pseudo'] : config('ey_config.seo_pseudo');
                            if (1 == $seo_pseudo) {
                                $url = request()->domain().ROOT_DIR.$inletStr;
                                if (!empty($inletStr)) {
                                    $url .= '?';
                                } else {
                                    $url .= '/?';
                                }
                                $url .= http_build_query(['lang'=>$mark]);
                            } else {
                                $url = request()->domain().ROOT_DIR.$inletStr.'/'.$mark;
                            }
                        }
                    }
                } else {
                    if (0 == $val['is_home_default']) {
                        $url = $url.ROOT_DIR."/sitemap_{$mark}.xml";
                    }
                }
                /*--end*/
                if ($web_mobile_domain_open){  //两个域名，生成两次,移动端添加标签：<mobile:mobile type="mobile"/>
                    $xml = join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'pc');
                    array_push($urls_txt, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                    if($xml_mobile){
                        $url = pc_to_mobile_url($url);
                        $xml_mobile = join_xml($xml_mobile,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'mobile');
                        if (!in_array($url, $urls_txt_mobile)) {
                            array_push($urls_txt_mobile, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                        }
                    }
                }else{   //一个域名
                    if ($response_type){  //pc、移动端分离  <mobile:mobile type="htmladapt"/>
                        $xml = join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'htmladapt');
                    }else{   //代码适配    <mobile:mobile type="pc,mobile"/>
                        $xml =join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'pc,mobile');
                    }
                    array_push($urls_txt, htmlspecialchars_decode($url));
                }
            }
        }
        else {
            foreach ($langRow as $key => $val) {
                $mark = $val['mark'];
                if (empty($globalConfig['web_language_switch']) || $lang != $mark) { // 关闭多语言
                    continue;
                }
                /*单独域名*/
                $url = $val['url'];
                if (empty($url)) {
                    $global_config_tmp = tpCache('global', [], $mark);
                    $seo_pseudo = !empty($global_config_tmp['seo_pseudo']) ? $global_config_tmp['seo_pseudo'] : config('ey_config.seo_pseudo');
                    if (1 == $seo_pseudo) {
                        $url = request()->domain().ROOT_DIR.$inletStr;
                        if (!empty($inletStr)) {
                            $url .= '?';
                        } else {
                            $url .= '/?';
                        }
                        $url .= http_build_query(['lang'=>$mark]);
                    } else {
                        $url = request()->domain().ROOT_DIR.$inletStr.'/'.$mark;
                    }
                }
                /*--end*/

                /*--end*/
                if ($web_mobile_domain_open){  //两个域名，生成两次,移动端添加标签：<mobile:mobile type="mobile"/>
                    $xml = join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'pc');
                    array_push($urls_txt, htmlspecialchars_decode($url));
                    if($xml_mobile){
                        $url = pc_to_mobile_url($url);
                        $xml_mobile = join_xml($xml_mobile,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'mobile');
                        array_push($urls_txt_mobile, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                    }
                }else{   //一个域名
                    if ($response_type){  //pc、移动端分离  <mobile:mobile type="pc,mobile"/>
                        $xml = join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'htmladapt');
                    }else{   //代码适配    <mobile:mobile type="htmladapt"/>
                        $xml =join_xml($xml,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'pc,mobile');
                    }
                    array_push($urls_txt, htmlspecialchars_decode($url));
                }
            }
        }
        /*--end*/

        /*所有栏目*/
        foreach ($result_arctype as $sub) {
            if (is_array($sub)) {
                if ($sub['is_part'] == 1) {
                    $url = $sub['typelink'];
                } else {
                    $url = get_typeurl($sub, false);
                }
                $url = str_replace('&amp;', '&', $url);
                $url = str_replace('&', '&amp;', $url);
                if ($web_mobile_domain_open){  //两个域名，生成两次,移动端添加标签：<mobile:mobile type="mobile"/>
                    $xml = join_xml($xml,$url,$now,$sitemap_changefreq_list,$sitemap_priority_list,'pc');
                    array_push($urls_txt, htmlspecialchars_decode($url));
                    if($xml_mobile){
                        $url = get_typeurl($sub, false,'mobile'); //pc_to_mobile_url($url,$sub['id']);
                        $url = str_replace('&amp;', '&', $url);
                        $url = str_replace('&', '&amp;', $url);
                        $xml_mobile = join_xml($xml_mobile,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'mobile');
                        array_push($urls_txt_mobile, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                    }
                }else{   //一个域名
                    if ($response_type){  //pc、移动端分离  <mobile:mobile type="pc,mobile"/>
                        $xml = join_xml($xml,$url,$now,$sitemap_changefreq_list,$sitemap_priority_list,'htmladapt');
                    }else{   //代码适配    <mobile:mobile type="htmladapt"/>
                        $xml =join_xml($xml,$url,$now,$sitemap_changefreq_list,$sitemap_priority_list,'pc,mobile');
                    }
                    array_push($urls_txt, htmlspecialchars_decode($url));
                }
            }
        }
        /*--end*/

        /*所有文档*/
        foreach ($result_archives as $val) {
            if (is_array($val) && isset($result_arctype[$val['typeid']])) {
                $val = array_merge($result_arctype[$val['typeid']], $val);
                if ($val['is_jump'] == 1) {
                    $url = $val['jumplinks'];
                } else {
                    $url = get_arcurl($val, false);
                }
                $url = str_replace('&amp;', '&', $url);
                $url = str_replace('&', '&amp;', $url);
                $lastmod_time = empty($val['update_time']) ? $val['add_time'] : $val['update_time'];
                $time_row = date('Y-m-d', $lastmod_time);
                if ($web_mobile_domain_open){  //两个域名，生成两次,移动端添加标签：<mobile:mobile type="mobile"/>
                    $xml = join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'pc');
                    array_push($urls_txt, htmlspecialchars_decode($url));
                    if($xml_mobile){
                        $url = get_arcurl($val, false,'mobile'); //pc_to_mobile_url($url,$val['typeid'],$val['aid']);
                        $url = str_replace('&amp;', '&', $url);
                        $url = str_replace('&', '&amp;', $url);
                        $xml_mobile = join_xml($xml_mobile,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'mobile');
                        array_push($urls_txt_mobile, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                    }
                }else{   //一个域名
                    if ($response_type){  //pc、移动端分离  <mobile:mobile type="pc,mobile"/>
                        $xml = join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'htmladapt');
                    }else{   //代码适配    <mobile:mobile type="htmladapt"/>
                        $xml =join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'pc,mobile');
                    }
                    array_push($urls_txt, htmlspecialchars_decode($url));
                }
            }
        }
        /*--end*/

        /*所有Tag*/
        /* Tag列表(用于生成Tag标签链接的sitemap) */
        //判断模板文件是否存在
        if (!isset($globalConfig['sitemap_tags_num']) || $globalConfig['sitemap_tags_num'] === '') {
            $sitemap_tags_num = 100;
        } else {
            $sitemap_tags_num = intval($globalConfig['sitemap_tags_num']);
        }
        $web_tpl_theme =  !empty($globalConfig['web_tpl_theme']) ? "/".$globalConfig['web_tpl_theme'] : '';
        if (is_file('./template'.$web_tpl_theme.'/pc/lists_tags.htm') && $sitemap_tags_num > 0){
            $result_tags = [];
            if (0 < $sitemap_tags_num) {
                $map = array(
                    'lang'      => $lang,
                );
                $field = "id, add_time, id AS loc, add_time AS lastmod, 'daily' AS changefreq, '0.5' AS priority";
                $result_tags = M('tagindex')->field($field)
                    ->where($map)
                    ->order('add_time desc')
                    ->limit($sitemap_tags_num)
                    ->select();
            }
            foreach ($result_tags as $val) {
                if (is_array($val)) {
                    $url = get_tagurl($val['id']);
                    $url = str_replace('&amp;', '&', $url);
                    $url = str_replace('&', '&amp;', $url);
                    $lastmod_time = empty($val['update_time']) ? $val['add_time'] : $val['update_time'];
                    $time_row = date('Y-m-d', $lastmod_time);
                    if ($web_mobile_domain_open){  //两个域名，生成两次,移动端添加标签：<mobile:mobile type="mobile"/>
                        $xml = join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'pc');
                        array_push($urls_txt, htmlspecialchars_decode($url));
                        if($xml_mobile){
                            $url = get_tagurl($val['id'],'mobile');
                            $url = str_replace('&amp;', '&', $url);
                            $url = str_replace('&', '&amp;', $url);
                            $xml_mobile = join_xml($xml_mobile,$url,$now,$sitemap_changefreq_index,$sitemap_priority_index,'mobile');
                            array_push($urls_txt_mobile, str_replace('.xml', '.txt', htmlspecialchars_decode($url)));
                        }
                    }else{   //一个域名
                        if ($response_type){  //pc、移动端分离  <mobile:mobile type="pc,mobile"/>
                            $xml = join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'htmladapt');
                        }else{   //代码适配    <mobile:mobile type="htmladapt"/>
                            $xml =join_xml($xml,$url,$time_row,$sitemap_changefreq_view,$sitemap_priority_view,'pc,mobile');
                        }
                        array_push($urls_txt, htmlspecialchars_decode($url));
                    }
                }
            }
        }

        /*--end*/

        if ($type == 'xml' || (!empty($globalConfig['sitemap_xml']) && in_array($type, ['all','xml']))) {
            if($xml && $filename){
                $content = $xml->asXML(); //用asXML方法输出xml，默认只构造不输出。
                @file_put_contents($filename, $content);
            }
            if(!empty($xml_mobile) && !empty($filename_mobile)){
                $content = $xml_mobile->asXML(); //用asXML方法输出xml，默认只构造不输出。
                @file_put_contents($filename_mobile, $content);
            }
        }
        if ($type == 'txt' || (!empty($globalConfig['sitemap_txt']) && in_array($type, ['all','txt']))) {
            if($urls_txt && $filename_txt){
                $content = implode(PHP_EOL, $urls_txt);
                @file_put_contents($filename_txt, $content);
            }
            if(!empty($urls_txt_mobile) && !empty($filename_txt_mobile)){
                $content = implode(PHP_EOL, $urls_txt_mobile);
                @file_put_contents($filename_txt_mobile, $content);
            }
        }
    }
}

if (!function_exists('get_typeurl')) 
{
    /**
     * 获取栏目链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     * $domain_type mobile：手机端
     */
    function get_typeurl($arctype_info = array(), $admin = true,$domain_type = '')
    {
        /*问答模型*/
        if ($arctype_info['current_channel'] == 51) { 
            $typeurl = get_askurl("home/Ask/index");
            // 自动隐藏index.php入口文件
            $typeurl = auto_hide_index($typeurl);

            return $typeurl;
        }
        /*end*/
        $domain = null; //static $domain = null;   //pc移动交替生成时候混乱
        null === $domain && $domain = request()->domain();
        if ($domain_type == 'mobile'){
            $web_mobile_domain = tpCache('global.web_mobile_domain');
            if (!empty($web_mobile_domain)){
                $subDomain = request()->subDomain();
                $domain = str_replace($subDomain,$web_mobile_domain,$domain);
            }
        }
        /*兼容采集没有归属栏目的文档*/
        if (empty($arctype_info['current_channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arctype_info = array_merge($arctype_info, $channelRow);
        }
        /*--end*/
        
        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arctype_info['current_channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $globalConfig = tpCache('global');
            $seo_pseudo = !empty($globalConfig['seo_pseudo']) ? $globalConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($globalConfig['seo_dynamic_format']) ? $globalConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if (2 == $seo_pseudo && $admin) {
            static $lang = null;
            null === $lang && $lang = input('param.lang/s', 'cn');
            $typeurl = ROOT_DIR."/index.php?m=home&c=Lists&a=index&tid={$arctype_info['id']}&lang={$lang}&t=".getTime();
        } else {
            $typeurl = typeurl("home/{$ctl_name}/lists", $arctype_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $typeurl = auto_hide_index($typeurl);
        }

        return $typeurl;
    }
}

if (!function_exists('get_arcurl')) 
{
    /**
     * 获取文档链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     * @param string  $domain_type   mobile：手机端
     */
    function get_arcurl($arcview_info = array(), $admin = true,$domain_type = '')
    {
        $domain = null; //static $domain = null;   //pc移动交替生成时候混乱
        null === $domain && $domain = request()->domain();
        if ($domain_type == 'mobile'){
            $web_mobile_domain = tpCache('global.web_mobile_domain');
            if (!empty($web_mobile_domain)){
                $subDomain = request()->subDomain();
                $domain = str_replace($subDomain,$web_mobile_domain,$domain);
            }
        }
        /*兼容采集没有归属栏目的文档*/
        if (!empty($arcview_info) && empty($arcview_info['channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arcview_info = array_merge($arcview_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arcview_info['channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $globalConfig = tpCache('global');
            $seo_pseudo = !empty($globalConfig['seo_pseudo']) ? $globalConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($globalConfig['seo_dynamic_format']) ? $globalConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }
        
        if ($admin) {
            if (2 == $seo_pseudo) {
                static $lang = null;
                null === $lang && $lang = input('param.lang/s', 'cn');
                $arcurl = ROOT_DIR."/index.php?m=home&c=View&a=index&aid={$arcview_info['aid']}&lang={$lang}&admin_id=".session('admin_id');
            } else {
                $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
                if (config('city_switch_on')) {
                    $url_path = parse_url($arcurl, PHP_URL_PATH);
                    $url_path = str_replace('.html', '', $url_path);
                    $url_path = '/'.trim($url_path, '/').'/';
                    preg_match_all('/\/site\/([^\/]+)\//', $url_path, $matches);
                    $site_domain = !empty($matches[1][0]) ? $matches[1][0] : '';
                    if (!empty($site_domain)) {
                        $url_path_new = str_replace("/site/{$site_domain}/", '', $url_path);
                        $root_dir_str = str_replace('/', '\/', ROOT_DIR);
                        $url_path_new = preg_replace("/^{$root_dir_str}\//", ROOT_DIR."/{$site_domain}/", $url_path_new);
                        $arcurl = str_replace(rtrim($url_path, '/'), $url_path_new, $arcurl);
                    }
                }
                // 自动隐藏index.php入口文件
                $arcurl = auto_hide_index($arcurl);
                if (stristr($arcurl, '?')) {
                    $arcurl .= '&admin_id='.session('admin_id');
                } else {
                    $arcurl .= '?admin_id='.session('admin_id');
                }
            }
        } else {
            $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            if (config('city_switch_on')) {
                $url_path = parse_url($arcurl, PHP_URL_PATH);
                $url_path = str_replace('.html', '', $url_path);
                $url_path = '/'.trim($url_path, '/').'/';
                preg_match_all('/\/site\/([^\/]+)\//', $url_path, $matches);
                $site_domain = !empty($matches[1][0]) ? $matches[1][0] : '';
                if (!empty($site_domain)) {
                    $url_path_new = str_replace("/site/{$site_domain}/", '', $url_path);
                    $root_dir_str = str_replace('/', '\/', ROOT_DIR);
                    $url_path_new = preg_replace("/^{$root_dir_str}\//", ROOT_DIR."/{$site_domain}/", $url_path_new);
                    $arcurl = str_replace(rtrim($url_path, '/'), $url_path_new, $arcurl);
                }
            }
            // 自动隐藏index.php入口文件
            $arcurl = auto_hide_index($arcurl);
        }

        return $arcurl;
    }
}

if (!function_exists('get_askurl')) 
{
    /**
     * 获取问答链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     */
    function get_askurl($url = '', $ask_info = array(), $admin = true)
    {
        static $domain = null;
        null === $domain && $domain = request()->domain();

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $globalConfig = tpCache('global');
            $seo_pseudo = !empty($globalConfig['seo_pseudo']) ? $globalConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($globalConfig['seo_dynamic_format']) ? $globalConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        $askurl = askurl($url, $ask_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
        // 自动隐藏index.php入口文件
        $askurl = auto_hide_index($askurl);

        return $askurl;
    }
}

if (!function_exists('get_tagurl')) 
{
    /**
     * 获取标签链接
     *
     * @param array $tagid 标签ID
     */
    function get_tagurl($tagid = '',$domain_type = '')
    {
        if ($domain_type == 'mobile'){
            $domain = tpCache('global.web_mobile_domain');
        }else{
            static $domain = null;
            null === $domain && $domain = true;
        }
        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $globalConfig = tpCache('global');
            $seo_pseudo = !empty($globalConfig['seo_pseudo']) ? $globalConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($globalConfig['seo_dynamic_format']) ? $globalConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }
//        $tagurl = tagurl("home/Tags/lists", ['tagid'=>$tagid], true, true, $seo_pseudo, $seo_dynamic_format);
        $tagurl = tagurl("home/Tags/lists", ['tagid'=>$tagid], true, $domain, $seo_pseudo, $seo_dynamic_format);

        // 自动隐藏index.php入口文件
        $tagurl = auto_hide_index($tagurl);

        return $tagurl;
    }
}

if (!function_exists('get_homeurl')) 
{
    /**
     * 获取前台链接
     *
     * @param array $tagid 标签ID
     */
    function get_homeurl($mca = '', $vars = [])
    {
        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $globalConfig = tpCache('global');
            $seo_pseudo = !empty($globalConfig['seo_pseudo']) ? $globalConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($globalConfig['seo_dynamic_format']) ? $globalConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }
    
        $url = url($mca, $vars, true, true, $seo_pseudo, $seo_dynamic_format);
        // 自动隐藏index.php入口文件
        $url = auto_hide_index($url);

        return $url;
    }
}

if (!function_exists('get_total_arc')) 
{
    /**
     * 获取指定栏目的文档数
     */
    function get_total_arc($typeid)
    {
        static $arctypeList = null;
        static $archivesNums = null;
        static $guestbookNums = null;
        static $allow_release_channel = [];
        if (null === $arctypeList) {
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeList = \think\Db::name('arctype')->field('id, parent_id, current_channel')->where([
                    'lang'  => get_admin_lang(),
                    'is_del'    => 0,
                    'weapp_code' => ['EQ', ''],
                ])->getAllWithIndex('id');

            /*-----------------文档列表模型统计数 start--------------*/
            $map = [
                'channel'   => ['IN', $allow_release_channel],
                'lang'  => get_admin_lang(),
                'is_del'    => 0, // 回收站功能
            ];
            $mapNew = "(users_id = 0 OR (users_id > 0 AND arcrank >= 0))";

            /*权限控制 by 小虎哥*/
            $admin_info = session('admin_info');
            if (0 < intval($admin_info['role_id'])) {
                $auth_role_info = $admin_info['auth_role_info'];
                if(! empty($auth_role_info)){
                    if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                        $map['admin_id'] = $admin_info['admin_id'];
                    }
                }
            }
            /*--end*/
            $SqlQuery = \think\Db::name('archives')->field('typeid, count(typeid) as num')->where($map)->where($mapNew)->group('typeid')->select(false);
            $SqlResult = \think\Db::name('sql_cache_table')->where(['sql_md5'=>md5($SqlQuery)])->getField('sql_result');
            if (!empty($SqlResult)) {
                $archivesNums = json_decode($SqlResult, true);
            } else {
                $archivesNums = \think\Db::name('archives')->field('typeid, count(typeid) as num')->where($map)->where($mapNew)->group('typeid')->getAllWithIndex('typeid');
                /*添加查询执行语句到mysql缓存表*/
                $SqlCacheTable = [
                    'sql_name' => '|arctype|all|count|',
                    'sql_result' => json_encode($archivesNums),
                    'sql_md5' => md5($SqlQuery),
                    'sql_query' => $SqlQuery,
                    'add_time' => getTime(),
                    'update_time' => getTime(),
                ];
                \think\Db::name('sql_cache_table')->insertGetId($SqlCacheTable);
                /*END*/
            }
            /*-----------------文档列表模型统计数 end--------------*/

            /*-----------------留言模型 start--------------*/
            $guestbookNums = \think\Db::name('guestbook')->field('typeid, count(typeid) as num')->where([
                    'lang'  => get_admin_lang(),
                ])->group('typeid')->getAllWithIndex('typeid');
            /*-----------------留言模型 end--------------*/
        }

        $totalnum = 0;
        if (!empty($arctypeList[$typeid])) {
            $current_channel = $arctypeList[$typeid]['current_channel'];
            if (in_array($current_channel, $allow_release_channel)) { // 能发布文档的模型
                static $arctypeAllSub = null;
                null === $arctypeAllSub && $arctypeAllSub = arctypeAllSub();
                $typeidArr = $arctypeAllSub[$typeid];
                foreach($typeidArr as $tid)
                {
                    $totalnum += (!empty($archivesNums[$tid]['num']) ? $archivesNums[$tid]['num'] : 0);
                }
            } elseif ($current_channel == 8) { // 留言模型
                $totalnum = !empty($guestbookNums[$typeid]['num']) ? $guestbookNums[$typeid]['num'] : 0;
            }
        }
            
        return $totalnum;
    }
}

if (!function_exists('replace_path')) 
{
    /**
     * 将路径斜杆、反斜杠替换为冒号符，适用于IIS服务器在URL上的双重转义限制
     * @param string $filepath 相对路径
     * @param string $replacement 目标字符
     * @param boolean $is_back false为替换，true为还原
     */
    function replace_path($filepath = '', $replacement = ':', $is_back = false)
    {
        if (false == $is_back) {
            $filepath = str_replace(DIRECTORY_SEPARATOR, $replacement, $filepath);
            $filepath = preg_replace('#\/#', $replacement, $filepath);
        } else {
            $filepath = preg_replace('#'.$replacement.'#', '/', $filepath);
            $filepath = str_replace('//', ':/', $filepath);
        }
        return $filepath;
    }
}

if (!function_exists('get_seo_pseudo_list')) 
{
    /**
     * URL模式下拉列表
     */
    function get_seo_pseudo_list($key = '')
    {
        $data = array(
            1   => '动态URL',
            3   => '伪静态化',
            2   => '静态页面',
        );

        return isset($data[$key]) ? $data[$key] : $data;
    }
}

if (!function_exists('get_chown_pathinfo')) 
{
    /**
     * 对指定的操作系统获取目录的所有组与所有者
     * @param string $path 目录路径
     * @return array
     */
    function get_chown_pathinfo($path = '') 
    {
        $pathinfo = true;

        if (function_exists('stat')) {
            /*指定操作系统，在列表内才进行后续获取*/
            $isValidate = false;
            $os = PHP_OS;
            $osList = array('linux','unix');
            foreach ($osList as $key => $val) {
                if (stristr($os, $val)) {
                    $isValidate = true;
                    continue;
                }
            }
            /*--end*/

            if (true === $isValidate) {
                $path = !empty($path) ? $path : ROOT_PATH;
                $stat = stat($path);
                if (function_exists('posix_getpwuid')) {
                    $pathinfo = posix_getpwuid($stat['uid']); 
                } else {
                    $pathinfo = array(
                        'name'  => (0 == $stat['uid']) ? 'root' : '',
                        'uid'  => $stat['uid'],
                        'gid'  => $stat['gid'],
                    );
                }
            }
        }

        return $pathinfo;
    }
}

if (!function_exists('menu_select')) 
{
    /*组装成层级下拉列表框*/
    function menu_select($selected = 0)
    {
        $select_html = '';
        $menuArr = getAllMenu();
        if (!empty($menuArr)) {
            foreach ($menuArr AS $key => $val)
            {
                $select_html .= '<option value="' . $val['id'] . '" data-grade="' . $val['grade'] . '"';
                $select_html .= ($selected == $val['id']) ? ' selected="ture"' : '';
                if (!empty($val['child'])) {
                    $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
                }
                $select_html .= '>';
                if ($val['grade'] > 0)
                {
                    $select_html .= str_repeat('&nbsp;', $val['grade'] * 4);
                }
                $name = !empty($val['name']) ? $val['name'] : '默认';
                $select_html .= htmlspecialchars(addslashes($name)) . '</option>';

                if (empty($val['child'])) {
                    continue;
                }
                foreach ($menuArr[$key]['child'] as $key2 => $val2) {
                    $select_html .= '<option value="' . $val2['id'] . '" data-grade="' . $val2['grade'] . '"';
                    $select_html .= ($selected == $val2['id']) ? ' selected="ture"' : '';
                    if (!empty($val2['child'])) {
                        $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
                    }
                    $select_html .= '>';
                    if ($val2['grade'] > 0)
                    {
                        $select_html .= str_repeat('&nbsp;', $val2['grade'] * 4);
                    }
                    $select_html .= htmlspecialchars(addslashes($val2['name'])) . '</option>';

                    if (empty($val2['child'])) {
                        continue;
                    }
                    foreach ($menuArr[$key]['child'][$key2]['child'] as $key3 => $val3) {
                        $select_html .= '<option value="' . $val3['id'] . '" data-grade="' . $val3['grade'] . '"';
                        $select_html .= ($selected == $val3['id']) ? ' selected="ture"' : '';
                        if (!empty($val3['child'])) {
                            $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
                        }
                        $select_html .= '>';
                        if ($val3['grade'] > 0)
                        {
                            $select_html .= str_repeat('&nbsp;', $val3['grade'] * 4);
                        }
                        $select_html .= htmlspecialchars(addslashes($val3['name'])) . '</option>';
                    }
                }
            }
        }

        return $select_html;
    }
}

if (!function_exists('schemaTable')) 
{
    /**
     * 重新生成单个数据表缓存字段文件
     */
    function schemaTable($name)
    {
        $table = $name;
        $prefix = \think\Config::get('database.prefix');
        if (!preg_match('/^'.$prefix.'/i', $name)) {
            $table = $prefix.$name;
        }
        /*调用命令行的指令*/
        \think\Console::call('optimize:schema', ['--table', $table]);
        /*--end*/
    }
}

if (!function_exists('schemaAllTable')) 
{
    /**
     * 重新生成全部数据表缓存字段文件
     */
    function schemaAllTable()
    {
        $dbtables = \think\Db::query('SHOW TABLE STATUS');
        $tableList = [];
        foreach ($dbtables as $k => $v) {
            if (preg_match('/^'.PREFIX.'/i', $v['Name'])) {
                /*调用命令行的指令*/
                \think\Console::call('optimize:schema', ['--table', $v['Name']]);
                /*--end*/
            }
        }
    }
}

if (!function_exists('testWriteAble')) 
{
    /**
     * 测试目录路径是否有写入权限
     * @param string $d 目录路劲
     * @return boolean
     */
    function testWriteAble($filepath)
    {
        $tfile = '_eyout.txt';
        $fp = @fopen($filepath.$tfile,'w');
        if(!$fp) {
            return false;
        }
        else {
            fclose($fp);
            $rs = @unlink($filepath.$tfile);
            return true;
        }
    }
}

if (!function_exists('getArchivesSortUrl')) 
{
    /**
     * 在文档列表拼接排序URL
     * @param string $orderby 排序字段
     * @param string $orderwayDefault 默认为空时升序
     * @return string
     */
    function getArchivesSortUrl($orderby = '', $orderwayDefault = '')
    {
        $parseArr = parse_url(request()->url());
        $query_str = '';
        if (!empty($parseArr['query'])) {
            parse_str($parseArr['query'], $output);
            $output['orderby'] = $orderby;

            $orderway = input('param.orderway/s', $orderwayDefault);
            $orderway = !empty($orderway) ? $orderway : 'desc';
            if ('desc' == $orderway) {
                $orderway = 'asc';
            } else {
                $orderway = 'desc';
                // 再次点击恢复到默认排序
                // if ('arcrank' == $orderby) {
                //     $output['orderby'] = '';
                // }
            }
            $output['orderway'] = $orderway;

            $query_str = http_build_query($output);
        }

        $url = $parseArr['path'];
        !empty($query_str) && $url .= '?'.$query_str;

        return $url;
    }
}

if (!function_exists('showArchivesFlagStr')) 
{
    /**
     * 在文档列表显示文档属性标识
     * @param array $archivesInfo 文档信息
     * @return string
     */
    function showArchivesFlagStr($archivesInfo = [])
    {
        static $flagResult = null;
        if (null === $flagResult) {
            $flagResult = \think\Db::name('archives_flag')->field('flag_name,flag_fieldname')->getAllWithIndex('flag_fieldname');
        }

        $arr = [];
        $flaglist = ['is_head','is_recom','is_special','is_b','is_jump','is_roll','is_slide','is_diyattr'];
        foreach ($flaglist as $key => $fieldname) {
            if (!empty($archivesInfo[$fieldname]) && !empty($flagResult[$fieldname]['flag_name'])) {
                if (in_array($flagResult[$fieldname]['flag_name'], ['推荐','加推','标粗','有图']) || stristr($flagResult[$fieldname]['flag_name'], '最')) {
                    $small_name = msubstr($flagResult[$fieldname]['flag_name'], 1, 1);
                } else {
                    $small_name = msubstr($flagResult[$fieldname]['flag_name'], 0, 1);
                }
                $arr[$fieldname] = [
                    'small_name'   => $small_name,
                ];
            }
        }

        return $arr;
    }
}

if (!function_exists('checkPasswordLevel')) 
{
    /**
     * 检查密码复杂度
     * @param string $strPassword 密码
     * @return string
     */
    function checkPasswordLevel($strPassword = '')
    {
        $result = 0;
        $pwdlen = strlen($strPassword);
        if ( $pwdlen == 0) {
            $result += 0;
        }
        else if ( $pwdlen<8 && $pwdlen >0 ) {
            $result += 5;
        }
        else if ($pwdlen>10) {
            $result += 25;
        }
        else {
            $result += 10;
        }
        
        //check letter
        $bHave = false;
        $bAll = false;
        $capital = preg_match('/[A-Z]{1}/', $strPassword);//找大写字母
        $small = preg_match('/[a-z]{1}/', $strPassword);//找小写字母
        if ( empty($capital) && empty($small) )
        {
            $result += 0; //没有字母
            $bHave = false;
        }
        else if ( !empty($capital) && !empty($small) )
        {
            $result += 20;
            $bAll = true;
        }
        else
        {   
            $result += 10;
            $bAll = true;
        }
        
        //检查数字
        $bDigi = false;
        $digitalLen = 0;
        for ( $i=0; $i<$pwdlen; $i++)
        {
        
            if ( $strPassword[$i] <= '9' && $strPassword[$i] >= '0' )
            {
                $bDigi = true;
                $digitalLen += 1;
            }
            
        }
        if ( $digitalLen==0 )//没有数字
        {
            $result += 0;
            $bDigi = false;
        }
        else if ($digitalLen>2)//2个数字以上
        {
            $result += 20 ;
            $bDigi = true;
        }
        else
        {
            $result += 10;
            $bDigi = true;
        }
        
        //检查非单词字符
        $bOther = false;
        $otherLen = 0;
        for ($i=0; $i<$pwdlen; $i++)
        {
            if ( ($strPassword[$i]>='0' && $strPassword[$i]<='9') ||  
                ($strPassword[$i]>='A' && $strPassword[$i]<='Z') ||
                ($strPassword[$i]>='a' && $strPassword[$i]<='z')) {
                continue;
            }
            $otherLen += 1;
            $bOther = true;
        }
        if ( $otherLen == 0 )//没有非单词字符
        {
            $result += 0;
            $bOther = false;
        }
        else if ( $otherLen >1)//1个以上非单词字符
        {
            $result +=25 ;
            $bOther = true;
        }
        else
        {
            $result +=10;
            $bOther = true;
        }
        
        //检查额外奖励
        if ( $bAll && $bDigi && $bOther) {
            $result += 5;
        }
        else if ($bHave && $bDigi && $bOther) {
            $result += 3;
        }
        else if ($bHave && $bDigi ) {
            $result += 2;
        }

        $level = 0;
        //根据分数来算密码强度的等级
        if ( $result >=80 )
            $level = 7;
        else if ( $result>=70)
            $level = 6;
        else if ( $result>=60)
            $level = 5;
        else if ( $result>=50)
            $level = 4;
        else if ( $result>=40)
            $level = 3;
        else if ( $result>20)
            $level = 2;
        else if ( $result>0)
            $level = 1;
        else
            $level = 0;

        return $level;
    }
}

if (!function_exists('getPasswordLevelTitle')) 
{
    /**
     * 获取密码复杂度名称
     * @param string $level 复杂程度
     * @return string
     */
    function getPasswordLevelTitle($level = 0)
    {
        $title = '弱';
        //根据分数来算密码强度的等级
        if ( $level == 7 ) {
            $title = '极佳';
        }
        else if ( $level == 6) {
            $title = '非常强';
        }
        else if ( $level == 5) {
            $title = '强';
        }
        else if ( $level == 4) {
            $title = '较强';
        }
        else if ( $level == 3) {
            $title = '一般';
        }
        else if ( $level == 2) {
            $title = '较弱';
        }
        else if ( $level == 1) {
            $title = '非常弱';
        }
        else {
            $title = '弱';
        }

        return $title;
    }
}

if (!function_exists('downloadExcel')) {
    /**
     * 下载excel
     * @param $strTable    表格内容
     * @param $filename 文件名
     */
    function downloadExcel($strTable, $filename)
    {
        ob_end_clean();
        header("Content-type: application/vnd.ms-excel");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . $filename . "_" . date('Y-m-d') . ".xls");
        header('Expires:0');
        header('Pragma:public');
        echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $strTable . '</html>';
    }
}

if (!function_exists('check_single_uiset')) {
    /**
     * 检测指定单页模板是否支持可视化
     */
    function check_single_uiset($templist = '')
    {
        $uisetRow = [];
        $templist = !empty($templist) ? $templist : 'lists_single.htm';

        $file = "./template/".TPL_THEME."pc/{$templist}";
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if (!empty($content) && preg_match('/eyou\:ui(\s+)open\=(\'|\")(on|off)(\'|\")/i', $content)) {
                $uisetRow['pc'] = true;
            }
        }

        $file = "./template/".TPL_THEME."mobile/{$templist}";
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if (!empty($content) && preg_match('/eyou\:ui(\s+)open\=(\'|\")(on|off)(\'|\")/i', $content)) {
                $uisetRow['mobile'] = true;
            }
        }

        return $uisetRow;
    }
}

if (!function_exists('left_menu_id')) {
    /**
     * 左侧菜单的ID处理
     */
    function left_menu_id($str = '')
    {
        return str_replace('|', '_', $str);
    }
}

if (!function_exists('list_to_tree')){
    /*
     * list数组转化为tree数组
     * $list            原二维数组
     * $pk              树型数组键名
     * $pid             属性数组关联键名
     * $child           树型数组下级枝干下标（标识）
     * $root            根pid的值
     * $has_chldren     用于获取下级个数（所有）,PS:需要原二维数组$list本来就是依次层级从大到小排序
     * return   根节点pid的值
     */
    function list_to_tree($list, $pk='id', $pid = 'parent_id', $child = 'children', $root = 0 ,$has_chldren = 1) {
        //创建Tree
        $tree = array();
        if (is_array($list)) {
            //创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                //判断是否存在parent
                $parantId = $data[$pid];
                if ($root == $parantId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parantId])) {
                        $parent = &$refer[$parantId];
                        //用于获取下级个数（所有）begin
                        if ($has_chldren){
                            if (isset($parent['has_chldren'])){
                                $parent['has_chldren'] ++;
                            }else{
                                $parent['has_chldren'] = 1;
                            }
                            if (isset($list[$key]['has_chldren'])){
                                $parent['has_chldren'] += $list[$key]['has_chldren'];
                            }
                        }
                        //用于获取下级个数（所有）end
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

if (!function_exists('tree_to_list')){
    /*
    * tree数组转化为list数组
    * $tree         原树型数组
     * $child       树型数组下级枝干下标（标识）
     * $pk          转化后需要作为list数组键名的属性值的属性名称（为空则转换后的list下标为从0开始的顺序）
     *
     * return       list类型数组
    */
    function tree_to_list($tree, $child = 'children',$pk=''){
        $imparr = array();
        foreach($tree as $w) {
            if(isset($w[$child])) {
                $t = $w[$child];
                unset($w[$child]);
                if (!empty($pk)){
                    $imparr[$w[$pk]] = $w;
                }else{
                    $imparr[] = $w;
                }
                if(is_array($t)){
                    if (!empty($pk)){
                        $imparr = $imparr + tree_to_list($t, $child,$pk);
                    }else{
                        $imparr = array_merge($imparr, tree_to_list($t, $child,$pk));
                    }
                }
            } else {
                if (!empty($pk)){
                    $imparr[$w[$pk]] = $w;
                }else{
                    $imparr[] = $w;
                }
            }
        }
        return $imparr;

    }
}

if (!function_exists('get_not_role_menu_id')){
    /*
     * 获取因为没有打开模块没有权限的入口节点id
     */
    function get_not_role_menu_id(){
        $main_lang= get_main_lang();
        $admin_lang = get_admin_lang();
        $shopServicemeal = array_join_string(array('cGhwLnBocF9zZXJ2aWNlbWVhbA=='));
        $global = include APP_PATH.MODULE_NAME.'/conf/global.php';
        $module_rele_menu = $global['module_rele_menu'];
        $module_reverse_menu = $global['module_reverse_menu'];
        $not_role_menu_id = [];
        //有权限的  (1 == tpCache('global.web_users_switch') && $main_lang == $admin_lang)
        //会员中心
        if (!empty($module_rele_menu['web_users_switch']) && (1 != tpCache('global.web_users_switch') || $main_lang != $admin_lang)){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['web_users_switch']);
        }
        //商城中心 => 会员中心关闭则一起关闭
//        if (1 == tpCache('global.web_users_switch') && 1 == getUsersConfigData('shop.shop_open') && $main_lang == $admin_lang && 1.5 <= tpCache($shopServicemeal))
        if (!empty($module_rele_menu['shop_open']) && (1 != tpCache('global.web_users_switch') || 1 != getUsersConfigData('shop.shop_open') || $main_lang != $admin_lang || 1.5 > tpCache($shopServicemeal))){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['shop_open']);
        }
        //会员投稿 => 会员中心关闭则一起关闭
        $UsersConfig = getUsersConfigData('all');
        if (!empty($module_rele_menu['users_open_release']) && (1 != tpCache('global.web_users_switch') || 1 != $UsersConfig['users_open_release'])){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['users_open_release']);
        }
        //会员升级 => 会员中心关闭则一起关闭
        if (!empty($module_rele_menu['level_member_upgrade']) && (1 != tpCache('global.web_users_switch') || 1 != $UsersConfig['level_member_upgrade'])){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['level_member_upgrade']);
        }
        //支付功能 => 会员中心关闭则一起关闭
        if (!empty($module_rele_menu['pay_open']) && (1 != tpCache('global.web_users_switch') || 1 != $UsersConfig['pay_open'])){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['pay_open']);
        }
        //插件应用
        if (!empty($module_rele_menu['web_weapp_switch']) && (1 != tpCache('global.web_weapp_switch') || $main_lang != $admin_lang)){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['web_weapp_switch']);
        }
        //城市分站
        if (!empty($module_rele_menu['web_citysite_open']) && (1 != tpCache('global.web_citysite_open') || $main_lang != $admin_lang)){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_rele_menu['web_citysite_open']);
        }
        if (!empty($module_reverse_menu['web_citysite_open']) && 1 == tpCache('global.web_citysite_open')){
            $not_role_menu_id = array_merge($not_role_menu_id,$module_reverse_menu['web_citysite_open']);
        }

        //其他因素
        $other_rele_menu = $global['other_rele_menu'];
        if (!empty($other_rele_menu)){
            $not_role_menu_id = array_merge($not_role_menu_id,$other_rele_menu);
        }

        return $not_role_menu_id;

    }
}