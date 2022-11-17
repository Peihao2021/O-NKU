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

// 关闭所有PHP错误报告
use think\Db;

error_reporting(0);

include_once EXTEND_PATH."function.php";

// 应用公共文件

if (!function_exists('switch_exception')) 
{
    // 模板错误提示
    function switch_exception() {
        $web_exception = tpCache('web.web_exception');
        if (!empty($web_exception)) {
            config('ey_config.web_exception', $web_exception);
            error_reporting(-1);
        }
    }
}

if (!function_exists('adminLog')) 
{
    /**
     * 管理员操作记录
     * @param $log_url 操作URL
     * @param $log_info 记录信息
     */
    function adminLog($log_info = ''){
        // 只保留最近一个月的操作日志
        try {
            $ajaxLogic = new \app\admin\logic\AjaxLogic;
            $ajaxLogic->del_adminlog();
            
            $admin_id = session('admin_id');
            $admin_id = !empty($admin_id) ? $admin_id : -1;
            $add['log_time'] = getTime();
            $add['admin_id'] = $admin_id;
            $add['log_info'] = htmlspecialchars($log_info);
            $add['log_ip'] = clientIP();
            $add['log_url'] = request()->baseUrl() ;
            M('admin_log')->add($add);
        } catch (\Exception $e) {
            
        }
    }
}

if (!function_exists('tpCache')) 
{
    /**
     * 获取缓存或者更新缓存，只适用于config表
     * @param string $config_key 缓存文件名称
     * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
     * @param array $options 缓存配置
     * @param string $lang 语言标识
     * @return array or string or bool
     */
    function tpCache($config_key,$data = array(), $lang = '', $options = null){
        $tableName = 'config';
        $table_db = \think\Db::name($tableName);

        $lang = !empty($lang) ? $lang : get_current_lang();
        $param = explode('.', $config_key);
        $cache_inc_type = "{$tableName}-{$lang}-{$param[0]}";
        if (empty($options)) {
            $options['path'] = CACHE_PATH.$lang.DS;
        }
        if(empty($data)){
            //如$config_key=shop_info则获取网站信息数组
            //如$config_key=shop_info.logo则获取网站logo字符串
            $config = cache($cache_inc_type,'',$options);//直接获取缓存文件
            if(empty($config)){
                //缓存文件不存在就读取数据库
                if ($param[0] == 'global') {
                    $param[0] = 'global';
                    $res = $table_db->where([
                        'lang'  => $lang,
                        'is_del'    => 0,
                    ])->select();
                } else {
                    $res = $table_db->where([
                        'inc_type'  => $param[0],
                        'lang'  => $lang,
                        'is_del'    => 0,
                    ])->select();
                }
                if($res){
                    foreach($res as $k=>$val){
                        $config[$val['name']] = $val['value'];
                    }
                    cache($cache_inc_type,$config,$options);
                }
                // write_global_params($lang, $options);
            }
            if(!empty($param) && count($param)>1){
                $newKey = strtolower($param[1]);
                return isset($config[$newKey]) ? $config[$newKey] : '';
            }else{
                return $config;
            }
        }else{
            //更新缓存
            $result =  $table_db->where([
                'inc_type'  => $param[0],
                'lang'  => $lang,
                'is_del'    => 0,
            ])->select();
            if($result){
                foreach($result as $val){
                    $temp[$val['name']] = $val['value'];
                }
                $add_data = array();
                foreach ($data as $k=>$v){
                    $newK = strtolower($k);
                    $newArr = array(
                        'name'=>$newK,
                        'value'=>trim($v),
                        'inc_type'=>$param[0],
                        'lang'  => $lang,
                        'update_time'   => getTime(),
                    );
                    if(!isset($temp[$newK])){
                        array_push($add_data, $newArr); //新key数据插入数据库
                    }else{
                        if($v == -1 && $newK == 'web_is_authortoken') { continue; }if($v!=$temp[$newK]){
                            $table_db->where([
                                'name'  => $newK,
                                'lang'  => $lang,
                            ])->save($newArr);//缓存key存在且值有变更新此项
                        }
                    }
                }
                if (!empty($add_data)) {
                    $table_db->insertAll($add_data);
                }
                //更新后的数据库记录
                $newRes = $table_db->where([
                    'inc_type'  => $param[0],
                    'lang'  => $lang,
                    'is_del'    => 0,
                ])->select();
                foreach ($newRes as $rs){
                    $newData[$rs['name']] = $rs['value'];
                }
            }else{
                if ($param[0] != 'global') {
                    foreach($data as $k=>$v){
                        $newK = strtolower($k);
                        $newArr[] = array(
                            'name'=>$newK,
                            'value'=>trim($v),
                            'inc_type'=>$param[0],
                            'lang'  => $lang,
                            'update_time'   => getTime(),
                        );
                    }
                    !empty($newArr) && $table_db->insertAll($newArr);
                }
                $newData = $data;
            }

            $result = false;
            $res = $table_db->where([
                'lang'  => $lang,
                'is_del'    => 0,
            ])->select();
            if($res){
                $global = array();
                foreach($res as $k=>$val){
                    $global[$val['name']] = $val['value'];
                }
                $result = cache("{$tableName}-{$lang}-global",$global,$options);
            } 

            if ($param[0] != 'global') {
                $result = cache($cache_inc_type,$newData,$options);
            }
            
            return $result;
        }
    }
}

if (!function_exists('tpSetting')) 
{
    /**
     * 获取缓存或者更新缓存，只适用于setting表
     * @param string $config_key 缓存文件名称
     * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
     * @param array $options 缓存配置
     * @param string $lang 语言标识
     * @return array or string or bool
     */
    function tpSetting($config_key,$data = array(), $lang = '', $options = null){
        $tableName = 'setting';
        $table_db = \think\Db::name($tableName);

        $lang = !empty($lang) ? $lang : get_current_lang();
        $param = explode('.', $config_key);
        $cache_inc_type = "{$tableName}-{$lang}-{$param[0]}";
        if (empty($options)) {
            $options['path'] = CACHE_PATH.$lang.DS;
        }
        if(empty($data)){
            //如$config_key=shop_info则获取网站信息数组
            //如$config_key=shop_info.logo则获取网站logo字符串
            $config = cache($cache_inc_type,'',$options);//直接获取缓存文件
            if(empty($config)){
                //缓存文件不存在就读取数据库
                if ($param[0] == 'global') {
                    $param[0] = 'global';
                    $res = $table_db->where([
                        'lang'  => $lang,
                    ])->select();
                } else {
                    $res = $table_db->where([
                        'inc_type'  => $param[0],
                        'lang'  => $lang,
                    ])->select();
                }
                if($res){
                    foreach($res as $k=>$val){
                        $config[$val['name']] = $val['value'];
                    }
                    cache($cache_inc_type,$config,$options);
                }
                // write_global_params($lang, $options);
            }
            if(!empty($param) && count($param)>1){
                $newKey = strtolower($param[1]);
                return isset($config[$newKey]) ? $config[$newKey] : '';
            }else{
                return $config;
            }
        }else{
            //更新缓存
            $result =  $table_db->where([
                'inc_type'  => $param[0],
                'lang'  => $lang,
            ])->select();
            if($result){
                foreach($result as $val){
                    $temp[$val['name']] = $val['value'];
                }
                $add_data = array();
                foreach ($data as $k=>$v){
                    $newK = strtolower($k);
                    $newArr = array(
                        'name'=>$newK,
                        'value'=>trim($v),
                        'inc_type'=>$param[0],
                        'lang'  => $lang,
                        'update_time'   => getTime(),
                    );
                    if(!isset($temp[$newK])){
                        array_push($add_data, $newArr); //新key数据插入数据库
                    }else{
                        if($v == -1 && $newK == 'web_is_authortoken') { continue; }if($v!=$temp[$newK]){
                            $table_db->where([
                                'name'  => $newK,
                                'lang'  => $lang,
                            ])->save($newArr);//缓存key存在且值有变更新此项
                        }
                    }
                }
                if (!empty($add_data)) {
                    $table_db->insertAll($add_data);
                }
                //更新后的数据库记录
                $newRes = $table_db->where([
                    'inc_type'  => $param[0],
                    'lang'  => $lang,
                ])->select();
                foreach ($newRes as $rs){
                    $newData[$rs['name']] = $rs['value'];
                }
            }else{
                if ($param[0] != 'global') {
                    foreach($data as $k=>$v){
                        $newK = strtolower($k);
                        $newArr[] = array(
                            'name'=>$newK,
                            'value'=>trim($v),
                            'inc_type'=>$param[0],
                            'lang'  => $lang,
                            'update_time'   => time(),
                        );
                    }
                    $table_db->insertAll($newArr);
                }
                $newData = $data;
            }

            $result = false;
            $res = $table_db->where([
                'lang'  => $lang,
            ])->select();
            if($res){
                $global = array();
                foreach($res as $k=>$val){
                    $global[$val['name']] = $val['value'];
                }
                $result = cache("{$tableName}-{$lang}-global",$global,$options);
            } 

            if ($param[0] != 'global') {
                $result = cache($cache_inc_type,$newData,$options);
            }
            
            return $result;
        }
    }
}

if (!function_exists('write_global_params')) 
{
    /**
     * 写入全局内置参数
     * @return array
     */
    function write_global_params($lang = '', $options = null)
    {
        empty($lang) && $lang = get_admin_lang();
        $webConfigParams = \think\Db::name('config')->where([
            'inc_type'  => 'web',
            'lang'  => $lang,
            'is_del'    => 0,
        ])->getAllWithIndex('name');
        $web_basehost = !empty($webConfigParams['web_basehost']) ? $webConfigParams['web_basehost']['value'] : ''; // 网站根网址
        $web_cmspath = !empty($webConfigParams['web_cmspath']) ? $webConfigParams['web_cmspath']['value'] : ''; // EyouCMS安装目录
        /*启用绝对网址，开启此项后附件、栏目连接、arclist内容等都使用http路径*/
        $web_multi_site = !empty($webConfigParams['web_multi_site']) ? $webConfigParams['web_multi_site']['value'] : '';
        if($web_multi_site == 1)
        {
            $web_mainsite = $web_basehost.$web_cmspath;
        }
        else
        {
            $web_mainsite = '';
        }
        /*--end*/
        /*CMS安装目录的网址*/
        $param['web_cmsurl'] = $web_mainsite;
        /*--end*/
        
        $web_tpl_theme = !empty($webConfigParams['web_tpl_theme']) ? $webConfigParams['web_tpl_theme']['value'] : ''; // 网站根网址
        !empty($web_tpl_theme) && $web_tpl_theme = '/'.trim($web_tpl_theme, '/');
        $param['web_templets_dir'] = '/template'.$web_tpl_theme; // 前台模板根目录
        $param['web_templeturl'] = $web_mainsite.$param['web_templets_dir']; // 前台模板根目录的网址
        $param['web_templets_pc'] = $web_mainsite.$param['web_templets_dir'].'/pc'; // 前台PC模板主题
        $param['web_templets_m'] = $web_mainsite.$param['web_templets_dir'].'/mobile'; // 前台手机模板主题
        $param['web_eyoucms'] = str_replace('#', '', '#h#t#t#p#:#/#/#w#w#w#.#e#y#o#u#c#m#s#.#c#o#m#'); // eyou网址

        /*将内置的全局变量(页面上没有入口更改的全局变量)存储到web版块里*/
        $inc_type = 'web';
        foreach ($param as $key => $val) {
            if (preg_match("/^".$inc_type."_(.)+/i", $key) !== 1) {
                $nowKey = strtolower($inc_type.'_'.$key);
                $param[$nowKey] = $val;
            }
        }
        tpCache($inc_type, $param, $lang, $options);
        /*--end*/
    }
}

if (!function_exists('write_html_cache')) 
{
    /**
     * 写入静态页面缓存
     */
    function write_html_cache($html = '', $result = []){
        $html_cache_status = config('HTML_CACHE_STATUS');
        $html_cache_arr = config('HTML_CACHE_ARR');
        if ($html_cache_status && !empty($html_cache_arr) && !empty($html)) {
            // 多站点/多语言
            $home_lang = 'cn';
            $home_site = '';
            $city_switch_on = config('city_switch_on');
            if (!empty($city_switch_on)) {
                $home_site = get_home_site();
            } else {
                $home_lang = get_home_lang();
            }

            $request = \think\Request::instance();
            $param = input('param.');

            /*URL模式是否启动页面缓存（排除admin后台、前台可视化装修、前台筛选）*/
            $uiset = input('param.uiset/s', 'off');
            $uiset = trim($uiset, '/');
            $url_screen_var = config('global.url_screen_var');
            $arcrank = !empty($result['arcrank']) ? intval($result['arcrank']) : 0;
            $admin_id = input('param.admin_id/d');
            if (isset($param[$url_screen_var]) || 'on' == $uiset || 'admin' == $request->module() || -1 == $arcrank || !empty($admin_id)) {
                return false;
            }
            $seo_pseudo = config('ey_config.seo_pseudo');
            if (2 == $seo_pseudo && !isMobile()) { // 排除普通动态模式
                return false;
            }
            /*--end*/

            if (1 == $seo_pseudo) {
                isset($param['tid']) && $param['tid'] = input('param.tid/d');
            } else {
                isset($param['tid']) && $param['tid'] = input('param.tid/s');
            }
            isset($param['page']) && $param['page'] = input('param.page/d');

            // aid唯一性的处理
            if (isset($param['aid'])) {
                if (strval(intval($param['aid'])) !== strval($param['aid'])) {
                    abort(404,'页面不存在');
                }
                $param['aid'] = intval($param['aid']);
            }

            $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
            $m_c_a_str = strtolower($m_c_a_str);
            //exit('write_html_cache写入缓存<br/>');
            foreach($html_cache_arr as $mca=>$val)
            {
                $mca = strtolower($mca);
                if($mca != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
                    continue;

                if (empty($val['filename'])) {
                    continue;
                }

                $cache_tag = ''; // 缓存标签
                $filename = '';
                // 组合参数  
                if(isset($val['p']))
                {
                    $tid = '';
                    if (in_array('tid', $val['p'])) {
                        $tid = $param['tid'];
                        if (strval(intval($tid)) != strval($tid)) {
                            $where = [
                                'dirname'   => $tid,
                            ];
                            if (empty($city_switch_on)) {
                                $where['lang'] =$home_lang;
                            }
                            $tid = \think\Db::name('arctype')->where($where)->getField('id');
                            $param['tid']   = $tid;
                        }
                    }

                    foreach ($val['p'] as $k=>$v) {
                        if (isset($param[$v])) {
                            if (preg_match('/\/$/i', $filename)) {
                                $filename .= $param[$v];
                            } else {
                                if (!empty($filename)) {
                                    $filename .= '_';
                                }
                                $filename .= $param[$v];
                            }
                        }
                    }
                    /*针对列表缓存的标签*/
                    !empty($tid) && $cache_tag = $tid;
                    /*--end*/
                    /*针对内容缓存的标签*/
                    $aid = input("param.aid/d");
                    !empty($aid) && $cache_tag = $aid;
                    /*--end*/
                }
                empty($filename) && $filename = 'index';
                $filename = md5($filename);

                /*子域名（移动端域名）*/
                $is_mobile_domain = false;
                $web_mobile_domain = config('tpcache.web_mobile_domain');
                $goto = $request->param('goto');
                $goto = trim($goto, '/');
                $subDomain = $request->subDomain();
                if ('m' == $goto || (!empty($subDomain) && $subDomain == $web_mobile_domain)) {
                    $is_mobile_domain = true;
                } else {
                    if (3 == $seo_pseudo) {
                        $pathinfo = $request->pathinfo();
                        if (!empty($pathinfo)) {
                            $s_arr = explode('/', $pathinfo);
                            if ('m' == $s_arr[0]) {
                                $is_mobile_domain = true;
                            }
                        }
                    }
                }
                /*end*/

                // 多站点
                !empty($home_site) && $home_site = DS.$home_site;

                // 缓存时间
                $web_cmsmode = 1;//tpCache('web.web_cmsmode');
                $response_type = config('ey_config.response_type');  // 0是代码适配,1:pc、移动端分离（存在pc、移动端两套模板）
                if (1 == intval($web_cmsmode)) { // 永久
                    $path = HTML_PATH.$val['filename'].DS.$home_lang.$home_site;
                    if ((isMobile() || $is_mobile_domain) && $response_type) {
                        $path .= "_mobile";
                    } else {
                        $path .= "_pc";
                    }
                    $filename = $path.'_html'.DS."{$filename}.html";
                    tp_mkdir(dirname($filename));
                    !empty($html) && file_put_contents($filename, $html);
                } else {
                    $path = HTML_PATH.$val['filename'].DS.$home_lang.$home_site;
                    if (isMobile()) {
                        $path .= "_mobile";
                    } else {
                        $path .= "_pc";
                    }
                    $path .= '_cache'.DS;
                    $web_htmlcache_expires_in = config('tpcache.web_htmlcache_expires_in');
                    $options = array(
                        'path'  => $path,
                        'expire'=> intval($web_htmlcache_expires_in),
                        'prefix'    => $cache_tag,
                    );
                    !empty($html) && html_cache($filename,$html,$options);
                }
            }
        }
    }
}

if (!function_exists('read_html_cache')) 
{
    /**
     * 读取静态页面缓存
     */
    function read_html_cache(){
        $html_cache_status = config('HTML_CACHE_STATUS');
        $html_cache_arr = config('HTML_CACHE_ARR');
        if ($html_cache_status && !empty($html_cache_arr)) {
            // 多站点/多语言
            $home_lang = 'cn';
            $home_site = '';
            $city_switch_on = config('city_switch_on');
            if (!empty($city_switch_on)) {
                $home_site = get_home_site();
            } else {
                $home_lang = get_home_lang();
            }

            $request = \think\Request::instance();
            $seo_pseudo = config('ey_config.seo_pseudo');
            $param = input('param.');

            /*前台筛选不进行页面缓存*/
            $url_screen_var = config('global.url_screen_var');
            if (isset($param[$url_screen_var])) {
                return false;
            }
            /*end*/

            if (1 == $seo_pseudo) {
                isset($param['tid']) && $param['tid'] = input('param.tid/d');
            } else {
                isset($param['tid']) && $param['tid'] = input('param.tid/s');
            }
            isset($param['page']) && $param['page'] = input('param.page/d');

            // aid唯一性的处理
            if (isset($param['aid'])) {
                if (strval(intval($param['aid'])) !== strval($param['aid'])) {
                    abort(404,'页面不存在');
                }
                $param['aid'] = intval($param['aid']);
            }

            $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
            $m_c_a_str = strtolower($m_c_a_str);
            //exit('read_html_cache读取缓存<br/>');
            foreach($html_cache_arr as $mca=>$val)
            {
                $mca = strtolower($mca);
                if($mca != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
                    continue;

                if (empty($val['filename'])) {
                    continue;
                }

                $cache_tag = ''; // 缓存标签
                $filename = '';
                // 组合参数  
                if(isset($val['p']))
                {
                    $tid = '';
                    if (in_array('tid', $val['p'])) {
                        $tid = !empty($param['tid']) ? $param['tid'] : '';
                        if (strval(intval($tid)) != strval($tid)) {
                            $where = [
                                'dirname'   => $tid,
                            ];
                            if (empty($city_switch_on)) {
                                $where['lang'] =$home_lang;
                            }
                            $tid = \think\Db::name('arctype')->where($where)->getField('id');
                            $param['tid']   = $tid;
                        }
                    }

                    foreach ($val['p'] as $k=>$v) {
                        if (isset($param[$v])) {
                            if (preg_match('/\/$/i', $filename)) {
                                $filename .= $param[$v];
                            } else {
                                if (!empty($filename)) {
                                    $filename .= '_';
                                }
                                $filename .= $param[$v];
                            }
                        }
                    }
                    /*针对列表缓存的标签*/
                    !empty($tid) && $cache_tag = $tid;
                    /*--end*/
                    /*针对内容缓存的标签*/
                    $aid = input("param.aid/d");
                    !empty($aid) && $cache_tag = $aid;
                    /*--end*/
                }
                empty($filename) && $filename = 'index';
                $filename = md5($filename);

                /*子域名（移动端域名）*/
                $is_mobile_domain = false;
                $web_mobile_domain = config('tpcache.web_mobile_domain');
                $goto = $request->param('goto');
                $goto = trim($goto, '/');
                $subDomain = $request->subDomain();
                if ('m' == $goto || (!empty($subDomain) && $subDomain == $web_mobile_domain)) {
                    $is_mobile_domain = true;
                } else {
                    if (3 == $seo_pseudo) {
                        $pathinfo = $request->pathinfo();
                        if (!empty($pathinfo)) {
                            $s_arr = explode('/', $pathinfo);
                            if ('m' == $s_arr[0]) {
                                $is_mobile_domain = true;
                            }
                        }
                    }
                }
                /*end*/

                // 多站点
                !empty($home_site) && $home_site = DS.$home_site;

                // 缓存时间
                $web_cmsmode = 1;//tpCache('web.web_cmsmode');
                $response_type = config('ey_config.response_type');  // 0是代码适配,1:pc、移动端分离（存在pc、移动端两套模板）
                if (1 == intval($web_cmsmode)) { // 永久
                    $path = HTML_PATH.$val['filename'].DS.$home_lang.$home_site;
                    if ((isMobile() || $is_mobile_domain) && $response_type) {
                        $path .= "_mobile";
                    } else {
                        $path .= "_pc";
                    }
                    $filename = $path.'_html'.DS."{$filename}.html";
                    if(is_file($filename) && file_exists($filename))
                    {
                        echo file_get_contents($filename);
                        exit();
                    }
                } else {
                    $path = HTML_PATH.$val['filename'].DS.$home_lang.$home_site;
                    if (isMobile()) {
                        $path .= "_mobile";
                    } else {
                        $path .= "_pc";
                    }
                    $path .= '_cache'.DS;
                    $web_htmlcache_expires_in = config('tpcache.web_htmlcache_expires_in');
                    $options = array(
                        'path'  => $path,
                        'expire'=> intval($web_htmlcache_expires_in),
                        'prefix'    => $cache_tag,
                    );
                    $html = html_cache($filename, '', $options);
                    // $html = $html_cache->get($filename);
                    if($html)
                    {
                        echo $html;
                        exit();
                    }
                }
            }
        }
    }
}
 
if (!function_exists('is_local_images')) 
{
    /**
     * 判断远程链接是否属于本地图片，并返回本地图片路径
     *
     * @param string $pic_url 图片地址
     * @param boolean $returnbool 返回类型，false 返回图片路径，true 返回布尔值
     */
    function is_local_images($pic_url = '', $returnbool = false)
    {
        if (stristr($pic_url, '//'.request()->host().'/')) {
            $picPath  = parse_url($pic_url, PHP_URL_PATH);
            $picPath = preg_replace('#^(/[/\w]+)?(/public/upload/|/public/static/|/uploads/|/weapp/)#i', '$2', $picPath);
            if (!empty($picPath) && file_exists('.'.$picPath)) {
                $pic_url = ROOT_DIR.$picPath;
                if (true == $returnbool) {
                    return $pic_url;
                }
            }
        }

        if (true == $returnbool) {
            return false;
        } else {
            return $pic_url;
        }
    }
}

if (!function_exists('get_head_pic')) 
{
    /**
     * 默认头像
     */
    function get_head_pic($pic_url = '', $is_admin = false, $sex = '保密')
    {
        if ($is_admin) {
            $default_pic = ROOT_DIR . '/public/static/admin/images/admint.png';
        } else {
            if ($sex == '女') {
                $default_pic = ROOT_DIR . '/public/static/common/images/dfgirl.png';
            } else {
                $default_pic = ROOT_DIR . '/public/static/common/images/dfboy.png';
            }
        }

        if (empty($pic_url)) {
            $pic_url = $default_pic;
        } else if (!is_http_url($pic_url)) {
            $pic_url = handle_subdir_pic($pic_url);
        } else if (is_http_url($pic_url)) {
            $pic_url = str_ireplace(['http://thirdqq.qlogo.cn','http://qzapp.qlogo.cn'], ['https://thirdqq.qlogo.cn','https://qzapp.qlogo.cn'], $pic_url);
        }

        return $pic_url;
    }
}
if (!function_exists('get_absolute_url'))
{
    /*
     * 本站url转为绝对链接
     * $is_absolute     是否无论开启都转换为绝对路径
     */
    function get_absolute_url($str, $type = 'default',$is_absolute = false)
    {
        if (!is_http_url($str)) {
            static $absolute_path_open = null;
            if ($is_absolute){
                $absolute_path_open = true;
            }else{
                null === $absolute_path_open && $absolute_path_open = tpCache('web.absolute_path_open'); //是否开启绝对链接
            }
            if (!empty($absolute_path_open)) {
                static $domain = null;
                if (null == $domain) {
                    $domain = preg_replace('/^(([^\:]+):)?(\/\/)?([^\/\:]*)(.*)$/i', '${1}${3}${4}', request()->domain());
                }
                $root_dir = $domain.ROOT_DIR;
                switch ($type) {
                    case 'html':
                        $str = preg_replace('#(.*)(\#39;|&quot;|"|\')(/[/\w]+)?(/public/upload/|/public/plugins|/public/static/|/uploads/)(.*)#iU', '$1$2'.$root_dir.'$4$5', $str);
                        break;
                    case 'url':
                        $str = $domain.$str;
                        break;
                    default:
                        if (preg_match('#^(/[/\w]+)?(/public/(upload|plugins|static)/|/uploads/|/weapp/)#i', $str)) {
                            $str = preg_replace('#^(/[/\w]+)?(/public/(upload|plugins|static)/|/uploads/|/weapp/)#i', $root_dir.'$2', $str);
                        } else {
                            $str = $domain.$str;
                        }
                        break;
                }
            }
        }

        return $str;
    }
}
if (!function_exists('get_default_pic'))
{
    /**
     * 图片不存在，显示默认无图封面
     * @param string $pic_url 图片路径
     * @param string|boolean $domain 完整路径的域名
     */
    function get_default_pic($pic_url = '', $domain = false)
    {
        if (is_http_url($pic_url)) {
            $pic_url = handle_subdir_pic($pic_url, 'img', $domain);
        }

        if (!is_http_url($pic_url)) {
            if (!$domain){
                static $absolute_path_open = null;
                null === $absolute_path_open && $absolute_path_open = tpCache('web.absolute_path_open'); //是否开启绝对链接
                if ($absolute_path_open  && request()->module() != 'admin'){
                    $domain = true;
                }
            }
            if (true === $domain) {
                $domain = request()->domain();
            } else if (false === $domain) {
                $domain = '';
            }
            
            $pic_url = preg_replace('#^(/[/\w]+)?(/public/upload/|/public/static/|/uploads/|/weapp/)#i', '$2', $pic_url); // 支持子目录
            $realpath = realpath(ROOT_PATH.trim($pic_url, '/'));
            if ( is_file($realpath) && file_exists($realpath) ) {
                $pic_url = $domain . ROOT_DIR . $pic_url;
            } else {
                $pic_url = $domain . ROOT_DIR . '/public/static/common/images/not_adv.jpg';
            }
        }

        return $pic_url;
    }
}

if (!function_exists('handle_subdir_pic')) 
{
    /**
     * 处理子目录与根目录的图片平缓切换
     * @param string $str 图片路径或html代码
     */
    function handle_subdir_pic($str = '', $type = 'img', $domain = false, $clear_root_dir = false)
    {
        static $request = null;
        if (null === $request) {
            $request = \think\Request::instance();
        }

        $root_dir = $add_root_dir = ROOT_DIR;
        if ($clear_root_dir == true && $domain == false) {
            $add_root_dir = '';
        }else{
            static $absolute_path_open = null;
            null === $absolute_path_open && $absolute_path_open = tpCache('web.absolute_path_open'); //是否开启绝对链接
            if ($absolute_path_open && request()->module() != 'admin'){
                $add_root_dir = $request->domain().$add_root_dir;
                $domain = false;
            }
        }
        switch ($type) {
            case 'img':
                if (!is_http_url($str) && !empty($str)) {
                    $str = preg_replace('#^(/[/\w]+)?(/public/upload/|/public/static/|/uploads/|/weapp/)#i', $add_root_dir.'$2', $str);
                }else if (is_http_url($str) && !empty($str)) {
                    $StrData = parse_url($str);
                    $strlen  = strlen($root_dir);
                    if (empty($StrData['scheme']) && $request->host(true) != $StrData['host']) {
                        $StrData['path'] = preg_replace('#^(/[/\w]+)?(/public/upload/|/uploads/|/public/static/)#i', '$2', $StrData['path']);
                        if (preg_match('#^(/public/upload/|/public/static/|/uploads/|/weapp/)#i', $StrData['path'])) {
                            // 插件列表
                            static $weappList = null;
                            if (null == $weappList) {
                                $weappList = \think\Db::name('weapp')->where([
                                    'status'    => 1,
                                ])->cache(true, EYOUCMS_CACHE_TIME, 'weapp')
                                ->getAllWithIndex('code');
                            }

                            if (!empty($weappList['Qiniuyun']) && 1 == $weappList['Qiniuyun']['status']) {
                                $qnyData = json_decode($weappList['Qiniuyun']['data'], true);
                                $weappConfig = json_decode($weappList['Qiniuyun']['config'], true);
                                if (!empty($weappConfig['version']) && 'v1.0.6' <= $weappConfig['version']) {
                                    $qiniuyunModel = new \weapp\Qiniuyun\model\QiniuyunModel;
                                    $str = $qiniuyunModel->handle_subdir_pic($qnyData, $StrData, $str);
                                } else {
                                    if ($qnyData['domain'] == $StrData['host']) {
                                        $tcp = !empty($qnyData['tcp']) ? $qnyData['tcp'] : '';
                                        switch ($tcp) {
                                            case '2':
                                                $tcp = 'https://';
                                                break;

                                            case '3':
                                                $tcp = '//';
                                                break;
                                            
                                            case '1':
                                            default:
                                                $tcp = 'http://';
                                                break;
                                        }
                                        $str = $tcp.$qnyData['domain'].$StrData['path'];
                                    }else{
                                        // 若切换了存储空间或访问域名，与数据库中存储的图片路径域名不一致时，访问本地路径，保证图片正常
                                        $str = $add_root_dir.$StrData['path'];
                                    }
                                }
                            }
                            else if (!empty($weappList['AliyunOss']) && 1 == $weappList['AliyunOss']['status']) {
                                $ossData = json_decode($weappList['AliyunOss']['data'], true);
                                $aliyunOssModel = new \weapp\AliyunOss\model\AliyunOssModel;
                                $str = $aliyunOssModel->handle_subdir_pic($ossData, $StrData, $str);
                            }
                            else if (!empty($weappList['Cos']) && 1 == $weappList['Cos']['status']) {
                                $cosData = json_decode($weappList['Cos']['data'], true);
                                $cosModel = new \weapp\Cos\model\CosModel;
                                $str = $cosModel->handle_subdir_pic($cosData, $StrData, $str);
                            }
                            else {
                                // 关闭
                                $str = $add_root_dir.$StrData['path'];
                            }
                        }
                    }
                }
                break;

            case 'html':
                $str = preg_replace('#(.*)(\#39;|&quot;|"|\')(/[/\w]+)?(/public/upload/|/public/plugins/|/uploads/)(.*)#iU', '$1$2'.$add_root_dir.'$4$5', $str);
                break;

            case 'soft':
                if (!is_http_url($str) && !empty($str)) {
                    $str = preg_replace('#^(/[/\w]+)?(/public/upload/soft/|/uploads/soft/)#i', $add_root_dir.'$2', $str);
                }
                break;

            case 'media':  //多媒体文件
                if (!is_http_url($str) && !empty($str)) {
                    $str = preg_replace('#^(/[/\w]+)?(/uploads/media/)#i', $add_root_dir.'$2', $str);
                }
                break;

            default:
                # code...
                break;
        }

        if (!empty($str) && !is_http_url($str)) {
            if (false !== $domain) {
                if (true === $domain) {
                    static $domain_new = null;
                    if (null === $domain_new) {
                        $domain_new = $request->domain();
                    }
                    $domain = $domain_new;
                }
                $str = $domain.$str;
            }
        }

        return $str;
    }
}

/**
 * 获取阅读权限
 */
if ( ! function_exists('get_arcrank_list'))
{
    function get_arcrank_list()
    {
        $result = \think\Db::name('arcrank')->where([
                'lang'  => get_admin_lang(),
            ])
            ->order('id asc')
            ->cache(true, EYOUCMS_CACHE_TIME, "arcrank")
            ->getAllWithIndex('rank');

        // 等级分类
        $LevelData = \think\Db::name('users_level')->field('level_name as `name`, level_value as `rank`')->order('level_value asc, level_id asc')->cache(true, EYOUCMS_CACHE_TIME, "users_level")->select();
        if (!empty($LevelData)) {
            $result = array_merge($result, $LevelData);
        }
        return $result;
    }
}

if (!function_exists('thumb_img')) 
{
    /**
     * 缩略图 从原始图来处理出来
     * @param type $original_img  图片路径
     * @param type $width     生成缩略图的宽度
     * @param type $height    生成缩略图的高度
     * @param type $thumb_mode    生成方式
     */
    function thumb_img($original_img = '', $width = '', $height = '', $thumb_mode = '')
    {
        // 缩略图配置
        static $thumbConfig = null;
        if (null === $thumbConfig) {
            @ini_set('memory_limit', '-1'); // 内存不限制，防止图片大小过大，导致缩略图处理失败，网站打不开
            $thumbConfig = tpCache('thumb');
        }
        $thumbextra = config('global.thumb');

        if (!empty($width) || !empty($height) || !empty($thumb_mode)) { // 单独在模板里调用，不受缩略图全局开关影响

        } else { // 非单独模板调用，比如内置的arclist\list标签里
            if (empty($thumbConfig['thumb_open'])) {
                return $original_img;
            }
        }

        // 缩略图优先级别高于七牛云，自动把七牛云的图片路径转为本地图片路径，并且进行缩略图
        $original_img = is_local_images($original_img);
        // 未开启缩略图，或远程图片
        if (is_http_url($original_img)) {
            return $original_img;
        } else if (empty($original_img)) {
            return ROOT_DIR.'/public/static/common/images/not_adv.jpg';
        }

        // 图片文件名
        $filename = '';
        $imgArr = explode('/', $original_img);    
        $imgArr = end($imgArr);
        $filename = preg_replace("/\.([^\.]+)$/i", "", $imgArr);
        $file_ext = preg_replace("/^(.*)\.([^\.]+)$/i", "$2", $imgArr);

        // 如果图片参数是缩略图，则直接获取到原图，并进行缩略处理
        if (preg_match('/\/uploads\/thumb\/\d{1,}_\d{1,}\//i', $original_img)) {
            $pattern = UPLOAD_PATH.'allimg/*/'.$filename;
            if (in_array(strtolower($file_ext), ['jpg','jpeg'])) {
                $pattern .= '.jp*g';
            } else {
                $pattern .= '.'.$file_ext;
            }
            $original_img_tmp = glob($pattern);
            if (!empty($original_img_tmp)) {
                $original_img = '/'.current($original_img_tmp);
            }
        } else {
            if ('bmp' == $file_ext && version_compare(PHP_VERSION,'7.2.0','<')) {
                return $original_img;
            }
        }
        // --end

//        $original_img1 = preg_replace('#^'.ROOT_DIR.'#i', '', handle_subdir_pic($original_img));
        $original_img1 = preg_replace('#^'.ROOT_DIR.'#i', '', handle_subdir_pic($original_img, 'img',false,true));

        $original_img1 = '.' . $original_img1; // 相对路径
        //获取图像信息
        $info = @getimagesize($original_img1);

        //检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            return $original_img;
        } else {
            if (!empty($info['mime']) && stristr($info['mime'], 'bmp') && version_compare(PHP_VERSION,'7.2.0','<')) {
                return $original_img;
            }
        }

        // 缩略图宽高度
        $is_auto_mode = 0;
        if (empty($width)) {
            if (is_numeric($thumbConfig['thumb_width']) && 0 == $thumbConfig['thumb_width']) {
                $width = !empty($info[0]) ? $info[0] : 1000000;
                $is_auto_mode = 1;
            } else {
                $width = !empty($thumbConfig['thumb_width']) ? $thumbConfig['thumb_width'] : $thumbextra['width'];
            }
        }
        if (empty($height)) {
            if (is_numeric($thumbConfig['thumb_height']) && 0 == $thumbConfig['thumb_height']) {
                $height = !empty($info[0]) ? $info[0] : 1000000;
                $is_auto_mode = 1;
            } else {
                $height = !empty($thumbConfig['thumb_height']) ? $thumbConfig['thumb_height'] : $thumbextra['height'];
            }
        }
        $width = intval($width);
        $height = intval($height);

        //判断缩略图是否存在
        $path = UPLOAD_PATH."thumb/{$width}_{$height}/";
        $img_thumb_name = "{$filename}";

        // 已经生成过这个比例的图片就直接返回了
        if (is_file($path . $img_thumb_name . '.jpg')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.jpg');
        if (is_file($path . $img_thumb_name . '.jpeg')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.jpeg');
        if (is_file($path . $img_thumb_name . '.gif')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.gif');
        if (is_file($path . $img_thumb_name . '.png')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.png');
        if (is_file($path . $img_thumb_name . '.bmp')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.bmp');
        if (is_file($path . $img_thumb_name . '.webp')) return get_absolute_url(ROOT_DIR.'/' . $path . $img_thumb_name . '.webp');

        if (!is_file($original_img1)) {
            return get_absolute_url(ROOT_DIR.'/public/static/common/images/not_adv.jpg');
        }

        try {
            vendor('topthink.think-image.src.Image');
            vendor('topthink.think-image.src.image.Exception');
            if(stristr($original_img1,'.gif'))
            {
                vendor('topthink.think-image.src.image.gif.Encoder');
                vendor('topthink.think-image.src.image.gif.Decoder');
                vendor('topthink.think-image.src.image.gif.Gif');               
            }           
            $image = \think\Image::open($original_img1);

            $img_thumb_name = $img_thumb_name . '.' . $image->type();
            // 生成缩略图
            !is_dir($path) && mkdir($path, 0777, true);
            // 填充颜色
            $thumb_color = !empty($thumbConfig['thumb_color']) ? $thumbConfig['thumb_color'] : $thumbextra['color'];
            // 生成方式参考 vendor/topthink/think-image/src/Image.php
            if (!empty($thumb_mode)) {
                $thumb_mode = intval($thumb_mode);
            } else {
                $thumb_mode = !empty($thumbConfig['thumb_mode']) ? $thumbConfig['thumb_mode'] : $thumbextra['mode'];
            }

            if (1 == $is_auto_mode) {
                $thumb_mode = 1;
            } else {
                1 == $thumb_mode && $thumb_mode = 6; // 按照固定比例拉伸
                2 == $thumb_mode && $thumb_mode = 2; // 填充空白
                if (3 == $thumb_mode) {
                    $img_width = $image->width();
                    $img_height = $image->height();
                    if ($width < $img_width && $height < $img_height) {
                        // 先进行缩略图等比例缩放类型，取出宽高中最小的属性值
                        $min_width = ($img_width < $img_height) ? $img_width : 0;
                        $min_height = ($img_width > $img_height) ? $img_height : 0;
                        if ($min_width > $width || $min_height > $height) {
                            if (0 < intval($min_width)) {
                                $scale = $min_width / min($width, $height);
                            } else if (0 < intval($min_height)) {
                                $scale = $min_height / $height;
                            } else {
                                $scale = $min_width / $width;
                            }
                            $s_width  = $img_width / $scale;
                            $s_height = $img_height / $scale;
                            $image->thumb($s_width, $s_height, 1, $thumb_color)->save($path . $img_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
                        }
                    }
                    $thumb_mode = 3; // 截减
                }
            }
            // 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
            $image->thumb($width, $height, $thumb_mode, $thumb_color)->save($path . $img_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
            //图片水印处理
            $water = tpCache('water');
            if($water['is_mark']==1 && $water['is_thumb_mark'] == 1 && $image->width()>$water['mark_width'] && $image->height()>$water['mark_height']){
                $imgresource = './' . $path . $img_thumb_name;
                if($water['mark_type'] == 'text'){
                    //$image->text($water['mark_txt'],ROOT_PATH.'public/static/common/font/hgzb.ttf',20,'#000000',9)->save($imgresource);
                    $ttf = ROOT_PATH.'public/static/common/font/hgzb.ttf';
                    if (file_exists($ttf)) {
                        $size = $water['mark_txt_size'] ? $water['mark_txt_size'] : 30;
                        $color = $water['mark_txt_color'] ?: '#000000';
                        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                            $color = '#000000';
                        }
                        $transparency = intval((100 - $water['mark_degree']) * (127/100));
                        $color .= dechex($transparency);
                        $image->open($imgresource)->text($water['mark_txt'], $ttf, $size, $color, $water['mark_sel'])->save($imgresource);
                        $return_data['mark_txt'] = $water['mark_txt'];
                    }
                }else{
                    /*支持子目录*/
                    $water['mark_img'] = preg_replace('#^(/[/\w]+)?(/public/upload/|/uploads/)#i', '$2', $water['mark_img']); // 支持子目录
                    /*--end*/
                    //$image->water(".".$water['mark_img'],9,$water['mark_degree'])->save($imgresource);
                    $waterPath = "." . $water['mark_img'];
                    if (eyPreventShell($waterPath) && file_exists($waterPath)) {
                        $quality = $water['mark_quality'] ? $water['mark_quality'] : 80;
                        $waterTempPath = dirname($waterPath).'/temp_'.basename($waterPath);
                        $image->open($waterPath)->save($waterTempPath, null, $quality);
                        $image->open($imgresource)->water($waterTempPath, $water['mark_sel'], $water['mark_degree'])->save($imgresource);
                        @unlink($waterTempPath);
                    }
                }
            }
            $img_url = ROOT_DIR.'/' . $path . $img_thumb_name;
            $img_url = get_absolute_url($img_url);

            return $img_url;

        } catch (think\Exception $e) {

            return $original_img;
        }
    }
}

if (!function_exists('get_controller_byct')) {
    /**
     * 根据模型ID获取控制器的名称
     * @return mixed
     */
    function get_controller_byct($current_channel)
    {
        $channeltype_info = model('Channeltype')->getInfo($current_channel);
        return $channeltype_info['ctl_name'];
    }
}

if (!function_exists('ui_read_bidden_inc')) {
    /**
     * 读取被禁止外部访问的配置文件
     * @param string $filename 文件路径
     * @return mixed
     */
    function ui_read_bidden_inc($filename)
    {
        $data = false;
        if (file_exists($filename)) {
            $data = @file($filename);
            $data = json_decode($data[1], true);
        }

        if (empty($data)) {
            // -------------优先读取配置文件，不存在才读取数据表
            $params = explode('/', $filename);
            $page = $params[count($params) - 1];
            $pagearr = explode('.', $page);
            reset($pagearr);
            $page = current($pagearr);
            $map = array(
                'page'   => $page,
                'theme_style'   => THEME_STYLE_PATH,
            );
            $result = M('ui_config')->where($map)->cache(true,EYOUCMS_CACHE_TIME,"ui_config")->select();
            if ($result) {
                $dataArr = array();
                foreach ($result as $key => $val) {
                    $k = "{$val['lang']}_{$val['type']}_{$val['name']}";
                    $dataArr[$k] = $val['value'];
                }
                $data = $dataArr;
            } else {
                $data = false;
            }
            //---------------end

            if (!empty($data)) {
                // ----------文件不存在，并写入文件缓存
                tp_mkdir(dirname($filename));
                $nowData = $data;
                $setting = "<?php die('forbidden'); ?>\n";
                $setting .= json_encode($nowData);
                $setting = str_replace("\/", "/",$setting);
                $incFile = fopen($filename, "w+");
                if ($incFile != false && fwrite($incFile, $setting)) {
                    fclose($incFile);
                }
                //---------------end
            }
        }
        
        return $data;
    }
}

if (!function_exists('ui_write_bidden_inc')) {
    /**
     * 写入被禁止外部访问的配置文件
     * @param array $arr 配置变量
     * @param string $filename 文件路径
     * @param bool $is_append false
     * @return mixed
     */
    function ui_write_bidden_inc($data, $filename, $is_append = false)
    {
        $data2 = $data;
        if (!empty($filename)) {

            // -------------写入数据表，同时写入配置文件
            reset($data2);
            $value = current($data2);
            $tmp_val = json_decode($value, true);
            $name = $tmp_val['id'];
            $type = $tmp_val['type'];
            $page = $tmp_val['page'];
            $lang = !empty($tmp_val['lang']) ? $tmp_val['lang'] : cookie(config('global.home_lang'));
            $idcode = $tmp_val['idcode'];
            if (empty($lang)) {
                $lang = model('language')->order('id asc')
                    ->limit(1)
                    ->cache(true, EYOUCMS_CACHE_TIME, 'language')
                    ->getField('mark');
            }
            $theme_style = THEME_STYLE_PATH;
            $md5key = md5($name.$page.$theme_style.$lang);
            $savedata = array(
                'md5key'    => $md5key,
                'theme_style'  => $theme_style,
                'page'  => $page,
                'type'  => $type,
                'name'  => $name,
                'value' => $value,
                'lang'  => $lang,
                'idcode'=> $idcode,
            );
            $map = array(
                'name'   => $name,
                'page'   => $page,
                'theme_style'   => $theme_style,
                'lang'   => $lang,
            );
            $count = M('ui_config')->where($map)->count('id');
            if ($count > 0) {
                $savedata['update_time'] = getTime();
                $r = M('ui_config')->where($map)->cache(true,EYOUCMS_CACHE_TIME,'ui_config')->update($savedata);
            } else {
                $savedata['add_time'] = getTime();
                $savedata['update_time'] = getTime();
                $r = M('ui_config')->insert($savedata);
                \think\Cache::clear('ui_config');
            }

            if ($r) {

                // ----------同时写入文件缓存
                tp_mkdir(dirname($filename));

                // 追加
                if ($is_append) {
                    $inc = ui_read_bidden_inc($filename);
                    if ($inc) {
                        $oldarr = (array)$inc;
                        $data = array_merge($oldarr, $data);
                    }
                }

                $setting = "<?php die('forbidden'); ?>\n";
                $setting .= json_encode($data);
                $setting = str_replace("\/", "/",$setting);
                $incFile = fopen($filename, "w+");
                if ($incFile != false && fwrite($incFile, $setting)) {
                    fclose($incFile);
                }
                //---------------end

                return true;
            }
        }

        return false;
    }
}

if (!function_exists('get_ui_inc_params')) {
    /**
     * 获取模板主题的美化配置参数
     * @return mixed
     */
    function get_ui_inc_params($page)
    {
        $e_page = $page;
        $filename = RUNTIME_PATH.'ui/'.THEME_STYLE_PATH.'/'.$e_page.'.inc.php';
        $inc = ui_read_bidden_inc($filename);

        return $inc;
    }
}

if (!function_exists('allow_release_arctype')) 
{
    /**
     * 允许发布文档的栏目列表
     */
    function allow_release_arctype($selected = 0, $allow_release_channel = array(), $selectform = true, $release_typeids = [],$users_release = false)
    {
        $release_typeids_pre = [];
        if ($users_release){
            $release_typeids_pre = $release_typeids;
            $topids = Db::name('arctype')->where(['lang' => get_current_lang()])->where('id','in',$release_typeids)->where('topid','>',0)->column('topid');
            $topid_arr = Db::name('arctype')->where(['lang' => get_current_lang()])->where('topid|id','in',$topids)->column('id');

            $release_typeids = array_merge($release_typeids,$topid_arr);
        }
        $where = [];

        $where['c.weapp_code'] = ''; // 回收站功能
        $where['c.lang']   = get_current_lang(); // 多语言 by 小虎哥
        $where['c.is_del'] = 0; // 回收站功能
        $current_channel = [51];
        $php_servicemeal = tpCache('php.php_servicemeal');
        if (1.5 > $php_servicemeal) {
            array_push($current_channel, 5);
        }
        $current_channel_str = implode(',', $current_channel);
        $where['c.current_channel'] = ['notin', $current_channel];

        /*权限控制 by 小虎哥*/
        $admin_info = session('admin_info');
        if (0 < intval($admin_info['role_id'])) {
            $auth_role_info = $admin_info['auth_role_info'];
            if(! empty($auth_role_info)){
                if(! empty($auth_role_info['permission']['arctype'])){
                    $where['c.id'] = array('IN', $auth_role_info['permission']['arctype']);
                }
            }
        }
        /*--end*/

        // 查询会员投稿指定的栏目
        if (!empty($release_typeids)) $where['c.id'] = ['IN', $release_typeids];

        // 默认选中的栏目
        if (!is_array($selected)) $selected = [$selected];

        $cacheKey = md5(json_encode($selected).json_encode($allow_release_channel).$selectform.json_encode($where));
        $select_html = cache($cacheKey);
        if (empty($select_html) || false == $selectform) {
            /*允许发布文档的模型*/
            $allow_release_channel = !empty($allow_release_channel) ? $allow_release_channel : config('global.allow_release_channel');

            /*所有栏目分类*/
            $where['c.status'] = 1;
            $fields = "c.id, c.parent_id, c.current_channel, c.typename, c.grade, '' as children";
            $res = $res2 = \think\Db::name('arctype')
                ->field($fields)
                ->alias('c')
                ->where($where)
                ->order('c.parent_id asc, c.sort_order asc, c.id')
                ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->select();
            /*--end*/
            if (empty($res)) {
                return '';
            }

            // 汇总每个栏目下的一级子栏目数量
            $arctypeSublist = [];
            foreach ($res as $key => $val) {
                if (!empty($val['parent_id'])) {
                    $arctypeSublist[$val['parent_id']][] = $val['id'];
                }
            }

            $grade_arr = [];
            $i = 0;
            foreach ($res as $key=>$val) { 
                $res[$key]['has_children'] = $res2[$key]['has_children'] = !empty($arctypeSublist[$val['id']]) ? count($arctypeSublist[$val['id']]) : 0;
                $grade_arr[] = $val['grade']; // 用一个空数组来承接字段
                $res2[$key]['new_sort_order'] = $i++; // 标记新的排序号
            }
            $max_grade = max($grade_arr); // 取最大的层级
            array_multisort($grade_arr, SORT_DESC, SORT_NUMERIC, $res2); // 按层级排序，从大到小

            $res3 = $new_sort_order_arr = [];
            foreach ($res2 as $key=>$val) { 
                $res3[$val['id']] = $val;
                $new_sort_order_arr[$val['id']] = $val['new_sort_order'];
            }

            /*过滤掉不允许发布的栏目（该栏目下包含不允许发布的栏目或没有下级）*/
            for ($i=0; $i <= $max_grade; $i++) {
                foreach ($res3 as $key => $val) {
                    if (!in_array($val['current_channel'], $allow_release_channel)) {
                        $tmp_val = $res3[$key];
                        if ( $tmp_val['has_children'] <= 0 ) {
                            unset($res3[$key]);
                            unset($new_sort_order_arr[$key]);
                            if (!empty($tmp_val['parent_id'])) {
                                if (!empty($res3[$tmp_val['parent_id']]['has_children'])) {
                                    $res3[$tmp_val['parent_id']]['has_children'] -= 1;
                                }
                            }
                        }
                    }
                }
            }
            /*--end*/
            //只有前台会员投稿走这个判断
            if ($users_release){
                duplicate_removal($res3,$new_sort_order_arr,$release_typeids_pre);
                if (empty($res3)){
                    return '';
                }
            }

            array_multisort($new_sort_order_arr, SORT_ASC, SORT_NUMERIC, $res3); // 按设置的最新排序号，从小到大
            /*所有栏目列表进行层次归类*/
            $arr = group_same_key($res3, 'parent_id');
            for ($i=0; $i <= $max_grade; $i++) {
                foreach ($arr as $key => $val) {
                    foreach ($arr[$key] as $key2 => $val2) {
                        if (!isset($arr[$val2['id']])) {
                            $arr[$key][$key2]['has_children'] = 0;
                            continue;
                        }
                        $val2['children'] = $arr[$val2['id']];
                        $arr[$key][$key2] = $val2;
                    }
                }
            }
            /*--end*/

            $nowArr = $arr[0];

            /*组装成层级下拉列表框*/
            $select_html = '';
            if (false == $selectform) {
                $select_html = $nowArr;
            } else if (true == $selectform) {

                handle_arctype_data($select_html, $nowArr, $selected, $allow_release_channel,$release_typeids_pre);
                cache($cacheKey, $select_html, null, 'arctype');
            }
        }

        return $select_html;
    }
}
if (!function_exists('duplicate_removal'))
{
    //用于会员投稿清除下级没有选中的多余栏目
    function duplicate_removal(&$data_arr = [],&$new_sort_order_arr = [],$ids = []){
        $circulate = false;
        foreach ($data_arr as $k => $v){
            if (0 == $v['has_children'] && !in_array($v['id'],$ids)){
                $circulate = true;
                if (!empty($data_arr[$v['parent_id']])){
                    $data_arr[$v['parent_id']]['has_children'] -= 1;
                }
                unset($data_arr[$k]);
                unset($new_sort_order_arr[$k]);
            }
        }
        if ($circulate){
            duplicate_removal($data_arr,$new_sort_order_arr,$ids);
        }

    }
}
if (!function_exists('handle_arctype_data'))
{
    // 处理栏目数据
    function handle_arctype_data(&$select_html = '', $nowArr = [], $selected = 0, $allow_release_channel = [],$release_typeids_pre = [])
    {
        foreach ($nowArr AS $key => $val)
        {
            //只有前台会员投稿走这个判断
            if (!empty($release_typeids_pre) && !in_array($val['id'], $release_typeids_pre) && $val['has_children'] == 0){
                continue;
            }
            $select_html .= '<option value="' . $val['id'] . '" data-grade="' . $val['grade'] . '" data-current_channel="' . $val['current_channel'] . '"';
            $select_html .= (in_array($val['id'], $selected)) ? ' selected="true"' : '';
            if ((!empty($allow_release_channel) && !in_array($val['current_channel'], $allow_release_channel)) || (!empty($release_typeids_pre) && !in_array($val['id'], $release_typeids_pre) ) ) {
                $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
            }
            $select_html .= '>';
            if ($val['grade'] > 0)
            {
                $select_html .= str_repeat('&nbsp;', $val['grade'] * 4);
            }
            $select_html .= htmlspecialchars_decode(addslashes($val['typename'])) . '</option>';

            if (empty($val['children'])) {
                continue;
            }
            handle_arctype_data($select_html, $val['children'], $selected, $allow_release_channel,$release_typeids_pre);
        }
    }
}

if (!function_exists('every_top_dirname_list')) 
{
    /**
     * 获取一级栏目的目录名称
     */
    function every_top_dirname_list() {
        $arctypeModel = new \app\common\model\Arctype();
        $result = $arctypeModel->getEveryTopDirnameList();
        
        return $result;
    }
}

if (!function_exists('getalltype')){
    /**
     * 获取当前栏目的所有上级栏目
     * $typeid  当前栏目id
     * $field   需要获取的某一列的值的集合
     */
    function getalltype($typeid, $field = '')
    {
        $parent_list = model('Arctype')->getAllPid($typeid); // 获取当前栏目的所有父级栏目

        if (!empty($field)){
            $parent_list = get_arr_column($parent_list,$field);
        }
        return $parent_list;
    }
}

if (!function_exists('gettoptype'))
{
    /**
     * 获取当前栏目的第一级栏目
     */
    function gettoptype($typeid, $field = 'typename')
    {
        $parent_list = model('Arctype')->getAllPid($typeid); // 获取当前栏目的所有父级栏目
        $result = current($parent_list); // 第一级栏目
        if (isset($result[$field]) && !empty($result[$field])) {
            return handle_subdir_pic($result[$field]); // 支持子目录
        } else {
            return '';
        }
    }
}

if (!function_exists('getparenttype')) 
{
    /**
     * 获取当前栏目的上级栏目
     */
    function getparenttype($typeid, $field = 'typename')
    {
        $parent_list = model('Arctype')->getAllPid($typeid); // 获取当前栏目的所有父级栏目
        if (!empty($parent_list)) {
            array_pop($parent_list);
        }
        $result = end($parent_list); // 上级栏目
        if (isset($result[$field]) && !empty($result[$field])) {
            return handle_subdir_pic($result[$field]); // 支持子目录
        } else {
            return '';
        }
    }
}

if (!function_exists('get_main_lang')) 
{
    /**
     * 获取主体语言（语言列表里最早的一条）
     */
    function get_main_lang()
    {
        static $main_lang = null;
        if (null === $main_lang) {
            $keys = 'common_get_main_lang';
            $main_lang = \think\Cache::get($keys);
            if (empty($main_lang) || (!empty($main_lang) && !preg_match('/^[a-z]{2}$/i', $main_lang))) {
                $main_lang = \think\Db::name('language')->order('id asc')
                    ->limit(1)
                    ->cache(true, EYOUCMS_CACHE_TIME, 'language')
                    ->getField('mark');
                \think\Cache::set($keys, $main_lang);
            }
            $main_lang = preg_replace('/([^a-z])/i', '', $main_lang);
        }

        return $main_lang;
    }
}

if (!function_exists('get_default_lang')) 
{
    /**
     * 获取默认语言
     */
    function get_default_lang()
    {
        static $default_lang = null;
        if (null === $default_lang) {
            $request = \think\Request::instance();
            if (!stristr($request->baseFile(), 'index.php')) {
                $default_lang = get_admin_lang();
            } else {
                $default_lang = \think\Config::get('ey_config.system_home_default_lang');
            }
        }

        return $default_lang;
    }
}

if (!function_exists('get_current_lang')) 
{
    /**
     * 获取当前默认语言
     */
    function get_current_lang()
    {
        static $current_lang = null;
        if (null === $current_lang) {
            $request = \think\Request::instance();
            if (!stristr($request->baseFile(), 'index.php')) {
                $current_lang = get_admin_lang();
            } else {
                $current_lang = get_home_lang();
            }
        }

        return $current_lang;
    }
}

if (!function_exists('get_admin_lang')) 
{
    /**
     * 获取后台当前语言
     */
    function get_admin_lang()
    {
        static $admin_lang = null;
        if (null === $admin_lang) {
            $keys = \think\Config::get('global.admin_lang');
            $admin_lang = \think\Cookie::get($keys);
            $admin_lang = addslashes($admin_lang);
            if (empty($admin_lang) || (!empty($admin_lang) && !preg_match('/^[a-z]{2}$/i', $admin_lang))) {
                $admin_lang = input('param.lang/s');
                empty($admin_lang) && $admin_lang = get_main_lang();
                \think\Cookie::set($keys, $admin_lang);
            }
            $admin_lang = preg_replace('/([^a-z])/i', '', $admin_lang);
        }

        return $admin_lang;
    }
}

if (!function_exists('get_home_lang')) 
{
    /**
     * 获取前台当前语言
     */
    function get_home_lang()
    {
        static $home_lang = null;
        if (null === $home_lang) {
            $keys = \think\Config::get('global.home_lang');
            $home_lang = \think\Cookie::get($keys);
            $home_lang = addslashes($home_lang);
            if (empty($home_lang) || (!empty($home_lang) && !preg_match('/^[a-z]{2}$/i', $home_lang))) {
                $home_lang = input('param.lang/s');
                if (empty($home_lang)) {
                    $home_lang = \think\Db::name('language')->where([
                            'is_home_default'   => 1,
                        ])->getField('mark');
                }
                \think\Cookie::set($keys, $home_lang);
            }
            $home_lang = preg_replace('/([^a-z])/i', '', $home_lang);
        }

        return $home_lang;
    }
}

if (!function_exists('is_language')) 
{
    /**
     * 是否多语言
     */
    function is_language()
    {
        static $value = null;
        if (null === $value) {
            $module = \think\Request::instance()->module();
            if (empty($module)) {
                $system_langnum = tpCache('system.system_langnum');
            } else {
                $system_langnum = config('ey_config.system_langnum');
            }

            if (1 < intval($system_langnum)) {
                $value = $system_langnum;
            } else {
                $value = false;
            }
        }

        return $value;
    }
}

if (!function_exists('switch_language')) 
{
    /**
     * 多语言切换（默认中文）
     *
     * @return void
     */
    function switch_language() 
    {
        static $execute_end = false;
        if (true === $execute_end) {
            return true;
        }

        static $request = null;
        if (null == $request) {
            $request = \think\Request::instance();
        }

        $pathinfo = $request->pathinfo();
        /*验证语言标识是否合法---PS：$request->param('site/s','')一定要放在$request->pathinfo()后面，非则会造成分页错误（链接带有"s"变量）*/
        $var_lang = $request->param('lang/s');
        $var_lang = trim($var_lang, '/');
        if (!empty($var_lang)) {
            if (!preg_match('/^([a-z]+)$/i', $var_lang)) {
                abort(404,'页面不存在');
            }
        }
        /*end*/

        $lang_switch_on = config('lang_switch_on');
        if (!$lang_switch_on) {
            return true;
        }

        static $language_db = null;
        if (null == $language_db) {
            $language_db = \think\Db::name('language');
        }

        $is_admin = false;
        if (!stristr($request->baseFile(), 'index.php')) {
            $is_admin = true;
            $langCookieVar = \think\Config::get('global.admin_lang');
        } else {
            $langCookieVar = \think\Config::get('global.home_lang');
        }
        \think\Lang::setLangCookieVar($langCookieVar);

        /*单语言执行代码 - 排序不要乱改，影响很大*/
        $langRow = $language_db->field('title,mark,is_home_default')
            ->order('id asc')
            ->cache(true, EYOUCMS_CACHE_TIME, 'language')
            ->select();
        if (1 >= count($langRow)) {
            $langRow = current($langRow);
            $lang = $langRow['mark'];
            \think\Config::set('cache.path', CACHE_PATH.$lang.DS);
            \think\Cookie::set($langCookieVar, $lang);
            cookie('site_info', null);
            return true;
        }
        /*--end*/

        $current_lang = '';
        /*兼容伪静态多语言切换*/
        if (!empty($pathinfo)) {
            $s_arr = explode('/', $pathinfo);
            if ('m' == $s_arr[0]) {
                $s_arr[0] = $s_arr[1];
            }
            $count = $language_db->where(['mark'=>$s_arr[0]])->cache(true, EYOUCMS_CACHE_TIME, 'language')->count();
            if (!empty($count)) {
                $current_lang = $s_arr[0];
            }
        }
        /*--end*/

        /*前后台默认语言*/
        if (empty($current_lang)) {
            if ($is_admin) {
                $current_lang = !empty($langRow[0]['mark']) ? $langRow[0]['mark'] : 'cn';
            } else {
                foreach ($langRow as $key => $val) {
                    if (1 == $val['is_home_default']) {
                        $current_lang = $val['mark'];
                        break;
                    }
                }
                empty($current_lang) && $current_lang = !empty($langRow[0]['mark']) ? $langRow[0]['mark'] : 'cn';
            }
        }
        /*end*/

        $lang = $request->param('lang/s', $current_lang);
        $lang = trim($lang, '/');
        if (!empty($lang)) {
            // 处理访问不存在的语言
            $lang = $language_db->where('mark',$lang)->cache(true, EYOUCMS_CACHE_TIME, 'language')->getField('mark');
        }
        if (empty($lang)) {
            if ($is_admin) {
                $lang = !empty($langRow[0]['mark']) ? $langRow[0]['mark'] : 'cn';
                // $lang = \think\Db::name('language')->order('id asc')->getField('mark');
            } else {
                abort(404,'页面不存在');
            }
        }
        $lang_info = [];
        foreach ($langRow as $key => $val) {
            if ($val['mark'] == $lang) {
                $lang_info['lang_title'] = $val['title'];
                /*单独域名*/
                $inletStr = (1 == config('ey_config.seo_inlet')) ? '' : '/index.php'; // 去掉入口文件
                $url = $val['url'];
                if (empty($url)) {
                    if (1 == $val['is_home_default']) {
                        $url = ROOT_DIR.'/'; // 支持子目录
                    } else {
                        $seoConfig = tpCache('seo', [], $val['mark']);
                        $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
                        if (1 == $seo_pseudo) {
                            $url = $request->domain().ROOT_DIR.$inletStr; // 支持子目录
                            if (!empty($inletStr)) {
                                $url .= '?';
                            } else {
                                $url .= '/?';
                            }
                            $url .= http_build_query(['lang'=>$val['mark']]);
                        } else {
                            $url = ROOT_DIR.$inletStr.'/'.$val['mark']; // 支持子目录
                        }
                    }
                }
                /*--end*/
                $lang_info['lang_url'] = $url;
                cookie('lang_info', $lang_info);
                break;
            }
        }
        \think\Config::set('cache.path', CACHE_PATH.$lang.DS);
        $pre_lang = \think\Cookie::get($langCookieVar);
        \think\Cookie::set($langCookieVar, $lang);
        if ($pre_lang != $lang) {
            if ($is_admin) {
                \think\Db::name('admin')->where('admin_id', \think\Session::get('admin_id'))->update([
                    'mark_lang' =>  $lang,
                    'update_time'   => getTime(),
                ]);
            }
        }

        $execute_end = true;
    }
}

if (!function_exists('get_default_site')) 
{
    /**
     * 获取默认城市站点
     */
    function get_default_site()
    {
        static $default_site = null;
        if (null === $default_site) {
            $default_site = \think\Config::get('ey_config.site_default_home');
            if (!empty($default_site)) {
                $default_site = \think\Db::name('citysite')->where(['id'=>$default_site])->getField('domain');
            }
        }

        return $default_site;
    }
}

if (!function_exists('get_home_site')) 
{
    /**
     * 获取前台当前城市站点
     */
    function get_home_site()
    {
        static $home_site = null;
        if (null === $home_site) {
            $home_site = input('param.site/s');
            if (empty($home_site)) {
                /*支持独立域名配置*/
                $subDomain = request()->subDomain();
                if (!empty($subDomain) && 'www' != $subDomain) {
                    $siteInfo = \think\Db::name('citysite')->where('domain',$subDomain)->cache(true, EYOUCMS_CACHE_TIME, 'citysite')->find();
                    if (!empty($siteInfo['is_open'])) {
                        $home_site = $siteInfo['domain'];
                    }
                }
                /*--end*/
                empty($home_site) && $home_site = get_default_site();
            }
            $home_site = preg_replace('/([^\w\-\_])/i', '', $home_site);
        }

        return $home_site;
    }
}

if (!function_exists('switch_citysite')) 
{
    /**
     * 多城市切换
     *
     * @return void
     */
    function switch_citysite() 
    {
        static $execute_end = false;
        if (true === $execute_end) {
            return true;
        }

        $request = \think\Request::instance();
        // 忽略后台
        if (!stristr($request->baseFile(), 'index.php')) {
            return true;
        }
        
        $pathinfo = $request->pathinfo();
        /*验证二级域名、路径标识是否合法--- PS：$request->param('site/s','')一定要放在$request->pathinfo()后面，非则会造成分页错误（链接带有"s"变量）*/
        $var_site = $request->param('site/s');
        $var_site = trim($var_site, '/');
        if (!empty($var_site)) {
            if (!preg_match('/^([\w\-\_]+)$/i', $var_site)) {
                abort(404,'页面不存在');
            }
        }
        /*end*/

        $lang_switch_on = config('lang_switch_on');
        $city_switch_on = config('city_switch_on');
        if ($lang_switch_on || !$city_switch_on) {
            return true;
        }

        static $citysite_db = null;
        if (null == $citysite_db) {
            $citysite_db = \think\Db::name('citysite');
        }

        $current_site = '';
        /*兼容伪静态多城市切换*/
        if (!empty($pathinfo)) {
            $s_arr = explode('/', $pathinfo);
            if ('m' == $s_arr[0]) {
                $s_arr[0] = $s_arr[1];
            }
            $count = $citysite_db->where(['domain'=>$s_arr[0]])->cache(true, EYOUCMS_CACHE_TIME, 'citysite')->count();
            if (!empty($count)) {
                $current_site = $s_arr[0];
            }
        }
        /*--end*/

        $site = $request->param('site/s', $current_site);
        $site = trim($site, '/');
        if (!empty($site)) {
            // 处理访问不存在的城市
            $siteInfo = $citysite_db->where('domain',$site)->cache(true, EYOUCMS_CACHE_TIME, 'citysite')->find();
            if (empty($siteInfo['domain'])) {
                abort(404,'页面不存在');
            } else {
                $site_default_home = tpCache('site.site_default_home');
                if ($siteInfo['id'] == $site_default_home) { // 设为默认站点跳转
                    header('Location: '.ROOT_DIR.'/');
                    exit;
                }
            }
        } else {
            $subDomain = $request->subDomain();
            $web_basehost = tpCache('web.web_basehost');
            $web_subDomain = $request->subDomain('', true, $web_basehost);
            if (!empty($subDomain) && !in_array($subDomain, ['www', $web_subDomain])) {
                $siteInfo = $citysite_db->where('domain',$subDomain)->cache(true, EYOUCMS_CACHE_TIME, 'citysite')->find();
                if (!empty($siteInfo['is_open'])) {
                    $site_default_home = tpCache('site.site_default_home');
                    if ($site_default_home == $siteInfo['id']) { // 设为默认站点跳转
                        $domain = preg_replace('/^(([^\:]+):)?(\/\/)?([^\/\:]*)(.*)$/i', '${4}', $web_basehost);
                        $url = $request->scheme().'://'.$domain.ROOT_DIR;
                        header('Location: '.$url);
                        exit;
                    } else {
                        $site = $siteInfo['domain'];
                    }
                } else {
                    abort(404,'页面不存在');
                }
            }
            empty($site) && $site = 'www';
        }

        \think\Config::set('cache.path', CACHE_PATH.$site.DS);
        $execute_end = true;
    }
}

if (!function_exists('getUsersConfigData')) 
{
    // 专用于获取users_config，会员配置表数据处理。
    // 参数1：必须传入，传入值不同，获取数据不同：
    // 例：获取配置所有数据，传入：all，
    // 获取分组所有数据，传入：分组标识，如：member，
    // 获取分组中的单个数据，传入：分组标识.名称标识，如：users.users_open_register
    // 参数2：data数据，为空则查询，否则为添加或修改。
    // 参数3：多语言标识，为空则获取当前默认语言。
    function getUsersConfigData($config_key,$data=array(),$lang='', $options = null){
        $tableName = 'users_config';
        $table_db = \think\Db::name($tableName);

        $lang = !empty($lang) ? $lang : get_current_lang();
        $param = explode('.', $config_key);
        $cache_inc_type = "{$tableName}-{$lang}-{$param[0]}";
        if (empty($options)) {
            $options['path'] = CACHE_PATH.$lang.DS;
        }
        if(empty($data)){
            //如$config_key=shop_info则获取网站信息数组
            //如$config_key=shop_info.logo则获取网站logo字符串
            $config = cache($cache_inc_type,'',$options);//直接获取缓存文件
            if(empty($config)){
                //缓存文件不存在就读取数据库
                if ($param[0] == 'all') {
                    $param[0] = 'all';
                    $res = $table_db->where([
                        'lang'  => $lang,
                    ])->select();
                } else {
                    $res = $table_db->where([
                        'inc_type'  => $param[0],
                        'lang'  => $lang,
                    ])->select();
                }
                if($res){
                    foreach($res as $k=>$val){
                        $config[$val['name']] = $val['value'];
                    }
                    cache($cache_inc_type,$config,$options);
                }
            }
            if(!empty($param) && count($param)>1){
                $newKey = strtolower($param[1]);
                return isset($config[$newKey]) ? $config[$newKey] : '';
            }else{
                return $config;
            }
        }else{
            //更新缓存
            $result =  $table_db->where([
                'inc_type'  => $param[0],
                'lang'  => $lang,
            ])->select();
            if($result){
                foreach($result as $val){
                    $temp[$val['name']] = $val['value'];
                }
                $add_data = array();
                foreach ($data as $k=>$v){
                    $newK = strtolower($k);
                    $newArr = array(
                        'name'=>$newK,
                        'value'=>trim($v),
                        'inc_type'=>$param[0],
                        'lang'  => $lang,
                        'update_time'   => getTime(),
                    );
                    if(!isset($temp[$newK])){
                        array_push($add_data, $newArr); //新key数据插入数据库
                    }else{
                        if($v == -1 && $newK == 'web_is_authortoken') { continue; }if($v!=$temp[$newK]){
                            $table_db->where([
                                'name'  => $newK,
                                'lang'  => $lang,
                            ])->save($newArr);//缓存key存在且值有变更新此项
                        }
                    }
                }
                if (!empty($add_data)) {
                    $table_db->insertAll($add_data);
                }
                //更新后的数据库记录
                $newRes = $table_db->where([
                    'inc_type'  => $param[0],
                    'lang'  => $lang,
                ])->select();
                foreach ($newRes as $rs){
                    $newData[$rs['name']] = $rs['value'];
                }
            }else{
                if ($param[0] != 'all') {
                    foreach($data as $k=>$v){
                        $newK = strtolower($k);
                        $newArr[] = array(
                            'name'=>$newK,
                            'value'=>trim($v),
                            'inc_type'=>$param[0],
                            'lang'  => $lang,
                            'update_time'   => getTime(),
                        );
                    }
                    !empty($newArr) && $table_db->insertAll($newArr);
                }
                $newData = $data;
            }

            $result = false;
            $res = $table_db->where([
                'lang'  => $lang,
            ])->select();
            if($res){
                $global = array();
                foreach($res as $k=>$val){
                    $global[$val['name']] = $val['value'];
                }
                $result = cache("{$tableName}-{$lang}-all",$global,$options);
            } 

            if ($param[0] != 'all') {
                $result = cache($cache_inc_type,$newData,$options);
            }
            
            return $result;
        }
    }
}

if (!function_exists('send_email')) 
{
    /**
     * 邮件发送
     * @param $to    接收人
     * @param string $subject   邮件标题
     * @param string $content   邮件内容(html模板渲染后的内容)
     * @param string $scene   使用场景
     * @throws Exception
     * @throws phpmailerException
     */
    function send_email($to='', $subject='', $data=array(), $scene=0, $smtp_config = []){
        // 实例化类库，调用发送邮件
        $emailLogic = new \app\common\logic\EmailLogic($smtp_config);
        $res = $emailLogic->send_email($to, $subject, $data, $scene);
        return $res;
    }
}

/**
 * 发送短信逻辑
 * @param unknown $scene
 */
function sendSms($scene, $sender, $params,$unique_id=0,$sms_config = [])
{
    $smsLogic = new \app\common\logic\SmsLogic($sms_config);
    return $smsLogic->sendSms($scene, $sender, $params, $unique_id);
}

if (!function_exists('get_region_list')){
    /**
     * 获得全部省份列表
     */
    function get_region_list()
    {
        $result = extra_cache('global_get_region_list');
        if ($result == false) {
            $result = \think\Db::name('region')->field('id, name')->getAllWithIndex('id');
            extra_cache('global_get_region_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_province_list')){
    /**
     * 获得全部省份列表
     */
    function get_province_list()
    {
        $result = extra_cache('global_get_province_list');
        if ($result == false) {
            $result = \think\Db::name('region')->field('id, name')
                ->where('level',1)
                ->getAllWithIndex('id');
            extra_cache('global_get_province_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_city_list')){
    /**
     * 获得全部城市列表
     */
    function get_city_list()
    {
        $result = extra_cache('global_get_city_list');
        if ($result == false) {
            $result = \think\Db::name('region')->field('id, name')
                ->where('level',2)
                ->getAllWithIndex('id');
            extra_cache('global_get_city_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_area_list')){
    /**
     * 获得全部地区列表
     */
    function get_area_list()
    {
        $result = extra_cache('global_get_area_list');
        if ($result == false) {
            $result = \think\Db::name('region')->field('id, name')
                ->where('level',3)
                ->getAllWithIndex('id');
            extra_cache('global_get_area_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_region_name')){
    /**
     * 根据地区ID获得区域名称
     */
    function get_region_name($id = 0)
    {
        $result = get_region_list();
        return empty($result[$id]['name']) ? '' : $result[$id]['name'];
    }
}

if (!function_exists('get_province_name')){
    /**
     * 根据地区ID获得省份名称
     */
    function get_province_name($id = 0)
    {
        $result = get_province_list();
        return empty($result[$id]['name']) ? '' : $result[$id]['name'];
    }
}

if (!function_exists('get_city_name')){
    /**
     * 根据地区ID获得城市名称
     */
    function get_city_name($id = 0)
    {
        $result = get_city_list();
        return empty($result[$id]['name']) ? '' : $result[$id]['name'];
    }
}

if (!function_exists('get_area_name')){
    /**
     * 根据地区ID获得县区名称
     */
    function get_area_name($id = 0)
    {
        $result = get_area_list();
        return empty($result[$id]['name']) ? '' : $result[$id]['name'];
    }
}

if (!function_exists('get_citysite_list')){
    /**
     * 获得城市站点的全部列表
     */
    function get_citysite_list()
    {
        $result = extra_cache('global_get_citysite_list');
        if (empty($result)) {
            $result = \think\Db::name('citysite')->field('id, name, level, parent_id, topid, domain, initial')
                ->where(['status'=>1])
                ->order("id asc")
                ->getAllWithIndex('id');
            extra_cache('global_get_citysite_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_site_province_list')){
    /**
     * 获得城市站点的全部省份列表
     */
    function get_site_province_list()
    {
        $result = extra_cache('global_get_site_province_list');
        if (empty($result)) {
            $result = \think\Db::name('citysite')->field('id, name, domain, parent_id')
                ->where(['level'=>1, 'status'=>1])
                ->order("sort_order asc, id asc")
                ->getAllWithIndex('id');

            extra_cache('global_get_site_province_list', $result);
        }
        return $result;
    }
}

if (!function_exists('get_site_city_list')){
    /**
     * 获得城市站点的全部城市列表
     */
    function get_site_city_list()
    {
        $result = extra_cache('global_get_site_city_list');
        if (empty($result)) {
            $result = \think\Db::name('citysite')->field('id, name, parent_id')
                ->where(['level'=>2, 'status'=>1])
                ->order("sort_order asc, id asc")
                ->getAllWithIndex('id');
            extra_cache('global_get_site_city_list', $result);
        }

        return $result;
    }
}

if (!function_exists('get_site_area_list')){
    /**
     * 获得城市站点的全部地区列表
     */
    function get_site_area_list()
    {
        $result = extra_cache('global_get_site_area_list');
        if (empty($result)) {
            $result = \think\Db::name('citysite')->field('id, name, parent_id')
                ->where(['level'=>3, 'status'=>1])
                ->order("sort_order asc, id asc")
                ->getAllWithIndex('id');
            extra_cache('global_get_site_area_list', $result);
        }

        return $result;
    }
}

if (!function_exists('AddOrderAction')) 
{
    /**
     * 添加订单操作表数据
     * 参数说明：
     * $OrderId       订单ID或订单ID数组
     * $UsersId       会员ID，若不为0，则ActionUsers为0
     * $ActionUsers   操作员ID，为0，表示会员操作，反之则为管理员ID
     * $OrderStatus   操作时，订单当前状态
     * $ExpressStatus 操作时，订单当前物流状态
     * $PayStatus     操作时，订单当前付款状态
     * $ActionDesc    操作描述
     * $ActionNote    操作备注
     * 返回说明：
     * return 无需返回
     */
    function AddOrderAction($OrderId,$UsersId,$ActionUsers='0',$OrderStatus='0',$ExpressStatus='0',$PayStatus='0',$ActionDesc='提交订单！',$ActionNote='会员提交订单成功！')
    {
        if (is_array($OrderId) && 4 == $OrderStatus) {
            // OrderId为数组并且订单状态为过期，则执行
            foreach ($OrderId as $key => $value) {
                $ActionData[] = [
                    'order_id'       => $value['order_id'],
                    'users_id'       => $UsersId,
                    'action_user'    => $ActionUsers,
                    'order_status'   => $OrderStatus,
                    'express_status' => $ExpressStatus,
                    'pay_status'     => $PayStatus,
                    'action_desc'    => $ActionDesc,
                    'action_note'    => $ActionNote,
                    'lang'           => get_home_lang(),
                    'add_time'       => getTime(),
                ];
            }
            // 批量添加
            M('shop_order_log')->insertAll($ActionData);
        }else{
            // OrderId不为数组，则执行
            $ActionData = [
                'order_id'       => $OrderId,
                'users_id'       => $UsersId,
                'action_user'    => $ActionUsers,
                'order_status'   => $OrderStatus,
                'express_status' => $ExpressStatus,
                'pay_status'     => $PayStatus,
                'action_desc'    => $ActionDesc,
                'action_note'    => $ActionNote,
                'lang'           => get_home_lang(),
                'add_time'       => getTime(),
            ];
            // 单条添加
            M('shop_order_log')->add($ActionData);
        }
    }
}

if (!function_exists('UsersMoneyAction')) 
{
    /**
     * 添加会员余额明细表
     * 参数说明：
     * $OrderCode  订单编号
     * $UsersId    会员ID
     * $UsersMoney 记录余额
     * $Cause      订单说明
     * 返回说明：
     * return 无需返回
     */
    function UsersMoneyAction($OrderCode = null, $UsersId = null, $UsersMoney = null, $Cause = '订单支付')
    {
        if (empty($OrderCode) || empty($UsersId) || empty($UsersMoney)) return false;
        $Time = getTime();
        /*使用余额支付时，同时添加一条记录到金额明细表*/
        $UsersNewMoney = sprintf("%.2f", $UsersId['users_money'] -= $UsersMoney);
        $MoneyData = [
            'users_id'     => $UsersId['users_id'],
            'money'        => $UsersMoney,
            'users_money'  => $UsersNewMoney,
            'cause'        => $Cause,
            'cause_type'   => 3,
            'status'       => 3,
            'pay_details'  => '',
            'order_number' => $OrderCode,
            'add_time'     => $Time,
            'update_time'  => $Time,
        ];
        M('users_money')->add($MoneyData);
        /* END */
    }
}

if (!function_exists('GetEamilSendData')) 
{
    /**
     * 获取邮箱发送数据
     * 参数说明：
     * $SmtpConfig 后台设置的邮箱配置信息
     * $users      会员数据
     * $OrderData  订单信息
     * $type       订单操作
     * $pay_method 支付方式
     * 返回说明：
     * return 邮箱发送所需参数
     */
    function GetEamilSendData($SmtpConfig = [], $users = [], $OrderData = [], $type = 1, $pay_method = null)
    {
        // 是否传入配置、用户信息、订单信息，缺一则返回结束
        if (empty($SmtpConfig) || empty($users) || empty($OrderData)) return false;
        
        // 根据类型判断场景是否开启并选择发送场景及地址
        if (in_array($type, [1])) {
            // 查询判断是否开启邮件订单提醒
            $send_scene = 5;
            $where = [
                'lang' => get_admin_lang(),
                'send_scene' => $send_scene
            ];
            $SmtpOpen = \think\Db::name('smtp_tpl')->where($where)->getField('is_open');
            // if (isset($SmtpConfig['smtp_shop_order_pay']) && 0 == $SmtpConfig['smtp_shop_order_pay']) return false;
            
            // 发送给后台，选择邮件配置中的邮箱地址
            $email = !empty($SmtpConfig['smtp_from_eamil']) ? $SmtpConfig['smtp_from_eamil'] : null;
        } else if (in_array($type, [2])) {
            $send_scene = 6;
            $where = [
                'lang' => get_admin_lang(),
                'send_scene' => $send_scene
            ];
            $SmtpOpen = \think\Db::name('smtp_tpl')->where($where)->getField('is_open');
            // if (isset($SmtpConfig['smtp_shop_order_send']) && 0 == $SmtpConfig['smtp_shop_order_send']) return false;
            
            // 发送给用户，选择用户的邮箱地址
            $email = !empty($users['email']) ? $users['email'] : null;
        }

        // 若未开启或邮箱地址不存在则返回结束
        if (empty($SmtpOpen) || empty($email)) return false;

        // 发送接口及内容拼装
        if (!empty($SmtpConfig['smtp_server']) && !empty($SmtpConfig['smtp_user']) && !empty($SmtpConfig['smtp_pwd'])) {
            $Result = [];
            switch ($type) {
                case '1':
                    $title = '订单支付';
                    break;
                case '2':
                    $title = '订单发货';
                    break;
            }
            $Result = [
                'url' => ROOT_DIR . '/index.php?m=user&c=Smtpmail&a=send_email&_ajax=1',
                'data' => [
                    'email' => $email,
                    'title' => $title,
                    'type'  => 'order_msg',
                    'scene' => $send_scene,
                    'data'  => [
                        'type' => $type,
                        'nickname' => !empty($users['nickname']) ? $users['nickname'] : $users['username'],
                        'pay_method' => $pay_method,
                        'order_id'   => !empty($OrderData['order_id']) ? $OrderData['order_id'] : '',
                        'order_code' => !empty($OrderData['order_code']) ? $OrderData['order_code'] : '',
                        'service_id' => !empty($OrderData['service_id']) ? $OrderData['service_id'] : ''
                    ],
                ]
            ];
            return $Result;
        }
        return false;
    }
}

if (!function_exists('GetMobileSendData')) 
{
    /**
     * 获取手机发送数据
     * 参数说明：
     * $SmtpConfig 后台设置的短信配置信息
     * $users      会员数据
     * $OrderData  订单信息
     * $type       订单操作
     * $pay_method 支付方式
     * 返回说明：
     * return 手机短信发送所需参数
     */
    function GetMobileSendData($SmsConfig = [], $users = [], $OrderData = [], $type = 1, $pay_method = null)
    {
        // 是否传入配置、用户信息、订单信息，缺一则返回结束
        if (empty($SmsConfig) || empty($users) || empty($OrderData)) return false;
            
        // 查询短信配置中的使用平台
        $sms_type = tpCache('sms.sms_type') ? tpCache('sms.sms_type') : 0;

        // 根据类型判断场景是否开启并选择发送场景及手机号
        if (in_array($type, [1])) {
            // 查询判断是否开启手机订单提醒
            $send_scene = 5;
            $where = [
                'sms_type' => $sms_type,
                'lang' => get_admin_lang(),
                'send_scene' => $send_scene
            ];
            $SmsOpen = \think\Db::name('sms_template')->where($where)->getField('is_open');
            
            // 发送给后台，选择邮件配置中的手机号
            $mobile = !empty($SmsConfig['sms_test_mobile']) ? $SmsConfig['sms_test_mobile'] : null;
        } else if (in_array($type, [2])) {
            $send_scene = 6;
            $where = [
                'sms_type' => $sms_type,
                'lang' => get_admin_lang(),
                'send_scene' => $send_scene
            ];
            $SmsOpen = \think\Db::name('sms_template')->where($where)->getField('is_open');
            
            // 发送给用户，选择用户的手机号
            $mobile = !empty($users['mobile']) ? $users['mobile'] : null;
            if (empty($mobile)) {
                $mobile = \think\Db::name('shop_order')->where('order_code', $OrderData['order_code'])->getField('mobile');
            }
        }

        // 若未开启或手机号不存在则返回结束
        if (empty($SmsOpen) || empty($mobile)) return false;

        // 发送接口及内容拼装
        if (($sms_type == 1 && !empty($SmsConfig['sms_appkey']) && !empty($SmsConfig['sms_secretkey'])) || ($sms_type == 2 && !empty($SmsConfig['sms_appkey_tx']) && !empty($SmsConfig['sms_appid_tx']))) {
            $Result = [];
            switch ($type) {
                case '1':
                    $title = '订单支付';
                    break;
                case '2':
                    $title = '订单发货';
                    break;
            }
            $Result = [
                'url' => ROOT_DIR . '/index.php?m=api&c=Ajax&a=SendMobileCode&_ajax=1',
                'data' => [
                    'mobile' => $mobile,
                    'scene' => $send_scene,
                    'title' => $title,
                    'type'  => 'order_msg',
                    'data'  => [
                        'type' => $type,
                        'nickname' => !empty($users['nickname']) ? $users['nickname'] : $users['username'],
                        'pay_method' => $pay_method,
                        'order_code' => !empty($OrderData['order_code']) ? $OrderData['order_code'] : '',
                    ],
                ]
            ];
            return $Result;
        }
        return false;
    }
}

if (!function_exists('download_file')) 
{
    /**
     * 下载文件
     * @param $down_path 文件路径
     * @param $file_mime 文件类型
     */
    function download_file($down_path = '', $file_mime = '', $file_name = '')
    {
        //设置脚本的最大执行时间，设置为0则无时间限制
        function_exists('set_time_limit') && set_time_limit(0);
        @ini_set('memory_limit','-1');
        @ini_set('max_execution_time', '0');

        $down_path = iconv("utf-8", "gb2312//IGNORE", $down_path);

        /*支持子目录*/
        $down_path = preg_replace('#^(/[/\w]+)?(/public/upload/soft/|/uploads/soft/)#i', '$2', $down_path);
        /*--end*/

        //文件名
        $filename = basename($down_path);
        if (!empty($file_name)) {
            $arr = explode('.', $filename);
            $ext = end($arr);
            $arr1 = explode('.', $file_name);
            unset($arr1[count($arr1) - 1]);
            $filename = implode('.', $arr1).'.'.$ext;
        }

        //文件大小
        preg_match("/^((\w)*:)?(\/\/).*$/", $down_path, $match);
        if (empty($match)) { // 本地文件
            $filesize = filesize('.'.$down_path);
        } else { // 远程文件
            $header_array = get_headers($down_path, true);
            $filesize = !empty($header_array['Content-Length']) ? $header_array['Content-Length'] : 0;
        }
        //告诉浏览器这是一个文件流格式的文件
        // header("Content-type: ".$file_mime);    
        //因为不知道文件是什么类型的，告诉浏览器输出的是字节流
        header('content-type:application/octet-stream');
        //请求范围的度量单位
        Header("Accept-Ranges: bytes");
        //Content-Length是指定包含于请求或响应中数据的字节长度
        Header("Accept-Length: " . $filesize);
        //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$filename该变量的值。
        Header("Content-Disposition: attachment; filename=" . basename($filename)); 
        
        //针对大文件，规定每次读取文件的字节数为2MB，直接输出数据
        $read_buffer = 1024 * 1024 * 2; // 2MB
        if (is_http_url($down_path)) {
            $file = fopen($down_path, 'rb');
        } else {
            $file = fopen('.' . $down_path, 'rb');
        }
        //总的缓冲的字节数
        $sum_buffer = 0;
        //只要没到文件尾，就一直读取
        while(!feof($file) && $sum_buffer < $filesize) {
            echo fread($file,$read_buffer);
            $sum_buffer += $read_buffer;
        }
    
        //关闭句柄
        fclose($file);
        exit;
    }
}

if (!function_exists('is_realdomain')) 
{
    /**
     * 简单判断当前访问的域名是否真实
     * @param string $domain 不带协议的域名
     * @return boolean
     */
    function is_realdomain($domain = '')
    {
        $is_real = false;
        $domain = !empty($domain) ? $domain : request()->host();
        if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', $domain) && 'localhost' != $domain && '127.0.0.1' != serverIP()) {
            $is_real = true;
        }

        return $is_real;
    }
}

if (!function_exists('img_style_wh')) 
{
    /**
     * 追加指定内嵌样式到编辑器内容的img标签，兼容图片自动适应页面
     */
    function img_style_wh($content = '', $title = '')
    {
        if (!empty($content)) {
            
            static $basicConfig = null;
            null === $basicConfig && $basicConfig = tpCache('basic');
            if (empty($basicConfig['basic_img_auto_wh']) && empty($basicConfig['basic_img_alt']) && empty($basicConfig['basic_img_title'])) {
                return $content;
            }

            preg_match_all('/<img.*(\/)?>/iUs', $content, $imginfo);
            $imginfo = !empty($imginfo[0]) ? $imginfo[0] : [];
            if (!empty($imginfo)) {
                $num = 1;
                $appendStyle = "max-width:100%!important;height:auto!important;";
                $title = preg_replace('/("|\')/i', '', $title);
                foreach ($imginfo as $key => $imgstr) {
                    $imgstrNew = $imgstr;
                    if (!stristr($imgstrNew, ' src=')) {
                        continue;
                    }
                    $imgname  = preg_replace("/<img(.*?)src(\s*)=(\s*)[\'|\"](.*?)([^\/\'\"]*)[\'|\"](.*?)[\/]?(\s*)>/i", '${5}', $imgstrNew);
                    $imgname = str_replace('/', '\/', $imgname);

                    // 是否开启图片大小自适应
                    if (!empty($basicConfig['basic_img_auto_wh'])) {
                        if (!stristr($imgstrNew, $appendStyle)) {
                            // 追加style属性
                            $imgstrNew = preg_replace('/style(\s*)=(\s*)[\'|\"]([^\'\"]*)?[\'|\"]/i', 'style="'.$appendStyle.'${3}"', $imgstrNew);
                            if (!preg_match('/<img(.*?)style(\s*)=(\s*)[\'|\"](.*?)[\'|\"](.*?)[\/]?(\s*)>/i', $imgstrNew)) {
                                // 新增style属性
                                $imgstrNew = str_ireplace('<img', "<img style=\"".$appendStyle."\" ", $imgstrNew);
                            }
                        }
                    } else {
                        $imgstrNew = str_ireplace([$appendStyle, $appendStyle], ['', ''], $imgstrNew);
                    }

                    // 移除img中多余的title属性
                    // $imgstrNew = preg_replace('/title(\s*)=(\s*)[\'|\"]([^\'\"]*)[\'|\"]/i', '', $imgstrNew);

                    // 追加alt属性
                    if (!empty($basicConfig['basic_img_alt'])) {
                        $altNew = $title."(图{$num})";
                        $imgstrNew = preg_replace('/alt(\s*)=(\s*)[\'|\"]('.$imgname.')?[\'|\"]/i', 'alt="'.$altNew.'"', $imgstrNew);
                        if (!preg_match('/<img(.*?)alt(\s*)=(\s*)[\'|\"](.*?)[\'|\"](.*?)[\/]?(\s*)>/i', $imgstrNew)) {
                            // 新增alt属性
                            $imgstrNew = str_ireplace('<img', "<img alt=\"{$altNew}\" ", $imgstrNew);
                        }
                    }

                    // 追加title属性
                    if (!empty($basicConfig['basic_img_title'])) {
                        $titleNew = $title."(图{$num})";
                        $imgstrNew = preg_replace('/title(\s*)=(\s*)[\'|\"]('.$imgname.')?[\'|\"]/i', 'title="'.$titleNew.'"', $imgstrNew);
                        if (!preg_match('/<img(.*?)title(\s*)=(\s*)[\'|\"](.*?)[\'|\"](.*?)[\/]?(\s*)>/i', $imgstrNew)) {
                            // 新增title属性
                            $imgstrNew = str_ireplace('<img', "<img title=\"{$titleNew}\" ", $imgstrNew);
                        }
                    }
                    
                    // 新的img替换旧的img
                    $content = str_ireplace($imgstr, $imgstrNew, $content);
                    $num++;
                }
            }
        }

        return $content;
    }
}

if (!function_exists('get_archives_data')) 
{
    /**
     * 查询文档主表信息和文档栏目表信息整合到一个数组中
     * @param string $array 产品数组信息
     * @param string $id 产品ID，购物车下单页传入aid，订单列表订单详情页传入product_id
     * @return return array_new
     */
    function get_archives_data($array = [], $id = '')
    {
        // 目前定义订单中心和评论中使用
        if (empty($array) || empty($id)) {
            return false;
        }

        static $array_new    = null;
        if (null === $array_new) {
            $aids         = get_arr_column($array, $id);
            $archivesList = \think\Db::name('archives')->field('*')->where('aid','IN',$aids)->select();
            $typeids      = get_arr_column($archivesList, 'typeid');
            $arctypeList  = \think\Db::name('arctype')->field('*')->where('id','IN',$typeids)->getAllWithIndex('id');
            
            foreach ($archivesList as $key2 => $val2) {
                $array_new[$val2['aid']] = array_merge($arctypeList[$val2['typeid']], $val2);
            }
        }

        return $array_new;
    }
}

if (!function_exists('SynchronizeQiniu')) 
{
    /**
     * 参数说明：
     * $images   本地图片地址
     * $Qiniuyun 七牛云插件配置信息
     * $is_tcp 是否携带协议
     * 返回说明：
     * return false 没有配置齐全
     * return true  同步成功
     */
    function SynchronizeQiniu($images,$Qiniuyun=null,$is_tcp=false)
    {
        static $Qiniuyun = null;
        // 若没有传入配信信息则读取数据库
        if (null == $Qiniuyun) {
            // 需要填写你的 Access Key 和 Secret Key
            $data     = M('weapp')->where('code','Qiniuyun')->field('data')->find();
            $Qiniuyun = json_decode($data['data'], true);
        }
        /*支持子目录*/
        $images = preg_replace('#^(/[/\w]+)?(/uploads/)#i', '$2', $images);
        // 配置为空则返回原图片路径
        if (empty($Qiniuyun) || empty($Qiniuyun['domain'])) {
            return ROOT_DIR.$images;
        }

        //引入七牛云的相关文件
        weapp_vendor('Qiniu.src.Qiniu.Auth', 'Qiniuyun');
        weapp_vendor('Qiniu.src.Qiniu.Storage.UploadManager', 'Qiniuyun');
        require_once ROOT_PATH.'weapp/Qiniuyun/vendor/Qiniu/autoload.php';

        // 配置信息
        $accessKey = $Qiniuyun['access_key'];
        $secretKey = $Qiniuyun['secret_key'];
        $bucket    = $Qiniuyun['bucket'];
        $domain    = $Qiniuyun['domain'];
        // 构建鉴权对象
        $auth      = new Qiniu\Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token     = $auth->uploadToken($bucket);
        // 要上传文件的本地路径
        $filePath  = realpath('.'.$images);
        // 上传到七牛后保存的文件名
        $key       = ltrim($images, '/');
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new Qiniu\Storage\UploadManager;
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        // list($ret, $err) = $uploadMgr->put($token, $key, $filePath);
        if (empty($err) || $err === null) {
            $tcp = '//';
            if ($is_tcp) {
                $tcp = !empty($Qiniuyun['tcp']) ? $Qiniuyun['tcp'] : '';
                switch ($tcp) {
                    case '2':
                        $tcp = 'https://';
                        break;

                    case '3':
                        $tcp = '//';
                        break;
                    
                    case '1':
                    default:
                        $tcp = 'http://';
                        break;
                }
            }
            $images = $tcp.$domain.'/'.ltrim($images, '/');
        }

        return [
            'state' => 'SUCCESS',
            'url'   => $images,
        ];
    }
}

if (!function_exists('SynImageObjectBucket')) 
{
    /**
     * 同步到第三方对象存储空间
     * 参数说明：
     * $images   本地图片地址
     * $weappList 插件列表
     */
    function SynImageObjectBucket($images = '', $weappList = [], $fileziyuan = [])
    {
        $result = [];
        if (empty($images)) {
            return $result;
        }

        /*支持子目录*/
        $images = preg_replace('#^(/[/\w]+)?(/uploads/|/public/static/)#i', '$2', $images);

        if (empty($weappList)) {
            $weappList = \think\Db::name('weapp')->field('code,data,status,config')->where([
                'status'    => 1,
            ])->cache(true, EYOUCMS_CACHE_TIME, 'weapp')
            ->getAllWithIndex('code');
        }

        if (!empty($weappList['Qiniuyun']) && 1 == $weappList['Qiniuyun']['status']) {
            // 同步图片到七牛云
            $weappConfig = json_decode($weappList['Qiniuyun']['config'], true);
            if (!empty($weappConfig['version']) && 'v1.0.6' <= $weappConfig['version']) {
                $qnyData = json_decode($weappList['Qiniuyun']['data'], true);
                $qiniuyunOssModel = new \weapp\Qiniuyun\model\QiniuyunModel;
                $ResultQny = $qiniuyunOssModel->Synchronize($qnyData, $images);
            } else {
                $ResultQny = SynchronizeQiniu($images);
            }
            // 数据覆盖
            if (!empty($ResultQny) && is_array($ResultQny)) {
                $result['local_save'] = !empty($qnyData['local_save']) ? $qnyData['local_save'] : '';
                $result['state'] = !empty($ResultQny['state']) ? $ResultQny['state'] : '';
                $result['url'] = !empty($ResultQny['url']) ? $ResultQny['url'] : '';
            }
        } else if (!empty($weappList['AliyunOss']) && 1 == $weappList['AliyunOss']['status']) {
            // 同步图片到OSS
            $ossData = json_decode($weappList['AliyunOss']['data'], true);
            $aliyunOssModel = new \weapp\AliyunOss\model\AliyunOssModel;
            $ResultOss = $aliyunOssModel->Synchronize($ossData, $images);
            // 数据覆盖
            if (!empty($ResultOss) && is_array($ResultOss)) {
                $result['local_save'] = !empty($ossData['local_save']) ? $ossData['local_save'] : '';
                $result['state'] = !empty($ResultOss['state']) ? $ResultOss['state'] : '';
                $result['url'] = !empty($ResultOss['url']) ? $ResultOss['url'] : '';
            }
        } else if (!empty($weappList['Cos']) && 1 == $weappList['Cos']['status']) {
            // 同步图片到COS
            $CosData = json_decode($weappList['Cos']['data'], true);
            $cosModel = new \weapp\Cos\model\CosModel;
            $ResultCos = $cosModel->Synchronize($CosData, $images);
            // 数据覆盖
            if (!empty($ResultCos) && is_array($ResultCos)) {
                $result['local_save'] = !empty($CosData['local_save']) ? $CosData['local_save'] : '';
                $result['url'] = !empty($ResultCos['url']) ? $ResultCos['url'] : '';
                $result['state'] = !empty($ResultCos['state']) ? $ResultCos['state'] : '';
            }
        }


        return is_array($result) ? $result : [];
    }
}

if (!function_exists('getWeappObjectBucket')) 
{
    /**
     * 获取第三方对象存储插件的配置信息
     * 参数说明：
     * $weappList 插件列表
     */
    function getWeappObjectBucket()
    {
        $data = [];

        $weappList = \think\Db::name('weapp')->field('code,data,status,config')->where([
            'status'    => 1,
        ])->cache(true, EYOUCMS_CACHE_TIME, 'weapp')
        ->getAllWithIndex('code');

        if (!empty($weappList['Qiniuyun']) && 1 == $weappList['Qiniuyun']['status']) {
            // 七牛云
            $data = json_decode($weappList['Qiniuyun']['data'], true);
        } else if (!empty($weappList['AliyunOss']) && 1 == $weappList['AliyunOss']['status']) {
            // OSS
            $data = json_decode($weappList['AliyunOss']['data'], true);
        } else if (!empty($weappList['Cos']) && 1 == $weappList['Cos']['status']) {
            // COS
            $data = json_decode($weappList['Cos']['data'], true);
        }

        return is_array($data) ? $data : [];
    }
}

if (!function_exists('getAllChild')) 
{   
    /**
     * 递归查询所有的子类
     * @param array $arctype_child_all 存放所有的子栏目
     * @param int $id 栏目ID 或 父栏目ID
     * @param int $type 1=栏目，2=文章
     */ 
    function getAllChild(&$arctype_child_all,$id,$type = 1){
        if($type == 1){
            $arctype_child = \think\Db::name('arctype')->where(['is_del'=>0,'status'=>1,'parent_id'=>$id])->getfield('id',true);
        }else{
            $where['is_del'] = 0;
            $where['status'] = 1;
            $where['parent_id'] = $id;
            $where['current_channel'] = array(array('neq',6),array('neq',8));
            $arctype_child = \think\Db::name('arctype')->where($where)->getfield('id',true); 
        }
        
        if(!empty($arctype_child)){
            $arctype_child_all = array_merge($arctype_child_all,$arctype_child);
            for($i=0;$i<count($arctype_child);$i++){
                getAllChild($arctype_child_all,$arctype_child[$i],$type);
            }
        }
    }
}
    
if (!function_exists('getAllChildByList')) 
{   
    /**
     * 生成栏目页面时获取同模型下级
     * @param array $arctype_child_all 存放所有的子栏目
     * @param int $id 栏目ID 或 父栏目ID
     * @param int $current_channel 当前栏目的模型ID
     */ 
    function getAllChildByList(&$arctype_child_all,$id,$current_channel){
        $arctype_child = \think\Db::name('arctype')->where(['is_del'=>0,'status'=>1,'parent_id'=>$id,'current_channel'=>$current_channel])->getfield('id',true);
        if(!empty($arctype_child)){
            $arctype_child_all = array_merge($arctype_child_all,$arctype_child);
            for($i=0;$i<count($arctype_child);$i++){
                getAllChild($arctype_child_all,$arctype_child[$i]);
            }
        }
    }
}

if (!function_exists('getAllChildArctype'))
{
    //递归查询所有的子类
    function getAllChildArctype(&$arctype_child_all,$id){
        $where['a.is_del'] = 0;
        $where['a.status'] = 1;
        $where['a.parent_id'] = $id;
        $arctype_child = \think\Db::name('arctype')->field('c.*, a.*, a.id as typeid')
            ->alias('a')
            ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
            ->where($where)
            ->select();
        if(!empty($arctype_child)){
            $arctype_child_all = array_merge($arctype_child,$arctype_child_all);
            for($i=0;$i<count($arctype_child);$i++){
                getAllChildArctype($arctype_child_all,$arctype_child[$i]['typeid']);
            }
        }
    }
}

if (!function_exists('getAllArctype'))
{
    /*
     * 递归查询所有栏目
     * $home_lang   语言
     * $id          栏目id    存在则获取指定的栏目，不存在获取全部
     * $parent      是否获取下级栏目    true：获取，false：不获取
     * $aid
     */
    function getAllArctype($home_lang,$id,$view_suffix,$parent = true,$aid = 0){
        $map = [];
        if (!empty($id)){
            if (is_array($id)) {
                $map['a.id'] = ['IN', $id];
            } else {
                $map['a.id'] = $id;
            }
        }

        $map['a.lang'] = $home_lang;
        $map['a.is_del'] = 0;
        $map['a.status'] = 1;
        $info = \think\Db::name('arctype')->field('c.*, a.*, a.id as typeid')
            ->alias('a')
            ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
            ->where($map)
            ->order("a.grade desc")
            ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
            ->select();
        if (!empty($id) && $parent && $aid == 0) { // $aid > 0 表示栏目生成不生成子栏目
            getAllChildArctype($info,$id);
        }
        $info = getAllArctypeCount($home_lang,$info,$id,$view_suffix,$aid);
        return $info;
    }
}

if (!function_exists('arctypeAllSub'))
{
    /**
     * 获取所有栏目，并每个栏目都包含所有子栏目，以及自己
     * @param  boolean $self [description]
     * @return [type]        [description]
     */
    function arctypeAllSub($typeid = 0, $self = true)
    {
        $lang = get_current_lang();
        $cacheKey = "common_arctypeAllSub_{$typeid}_{$self}_{$lang}";
        $data = cache($cacheKey);
        if (empty($data)) {
            $where = [];
            $where['c.lang']   = $lang; // 多语言 by 小虎哥
            $where['c.is_del'] = 0; // 回收站功能
            $where['c.status'] = 1;
            /*所有栏目分类*/
            $fields = "c.id, c.parent_id, c.current_channel, c.grade";
            $res = $res2 = \think\Db::name('arctype')
                ->field($fields)
                ->alias('c')
                ->where($where)
                ->order('c.grade desc, c.id asc')
                ->select();
            if (empty($res)) return [];

            $data = [];
            foreach ($res as $key => $val) {
                if (in_array($val['current_channel'], [51]) || !empty($val['weapp_code'])) {
                    continue;
                }
                
                // 当前栏目
                if (!isset($data[$val['id']])) {
                    $data[$val['id']] = [$val['id']];
                } else {
                    $data[$val['id']][] = $val['id'];
                    $data[$val['id']] = array_unique($data[$val['id']]);
                }

                // 父级栏目
                if (!isset($data[$val['parent_id']])) {
                    $data[$val['parent_id']] = [$val['parent_id']];
                } else {
                    $data[$val['parent_id']][] = $val['id'];
                }
                if (!empty($data[$val['id']])) {
                    $data[$val['parent_id']] = array_merge($data[$val['parent_id']], $data[$val['id']]);
                }
                $data[$val['parent_id']] = array_unique($data[$val['parent_id']]);
            }
            if (isset($data[0])) unset($data[0]);
            if (false === $self) {
                foreach ($data as $key => $val) {
                    $indx = array_search($key, $val);
                    if (false !== $indx) {
                        unset($val[$indx]);
                    }
                    if (!empty($val)) {
                        $val = array_merge($val);
                    }
                    $data[$key] = $val;
                }
            }

            cache($cacheKey, $data, null, "arctype");
        }

        return !empty($typeid) ? $data[$typeid] : $data;
    }
}

if (!function_exists('getAllArctypeCount'))
{
    /*
     * 获取所有栏目数据条数，所有aid集合
     * 获取需要生成的栏目页的静态文件的个数   缓存到channel_page_total
     */
    function getAllArctypeCount($home_lang,$info,$id = 0,$view_suffix = ".htm",$aid = 0)
    {
        /**
         * 这里统计每个栏目的文档数有两种方法
         * 1、当文档数量少于10W时，执行第一种方法，在循环外部查询一条sql统计出栏目的文档数
         * 2、当文档数量大于10W时，执行第二种方法，在循环里面每次执行一个统计当前栏目的文档数sql
         * @var integer
         */
        $method_mode = 1; // 默认是第一种方法
        $max_aid = \think\Db::name('archives')->max('aid'); // 取出文档的最大数量，已最大文档ID来大概计算
        if ($max_aid > 100000) {
            $method_mode = 2;
        }

        $map_arc = [];
        // 是否更新子栏目
        $seo_upnext = tpCache('seo.seo_upnext');
        $web_stypeid_open = tpCache('web.web_stypeid_open'); // 是否开启副栏目
        if (1 == $method_mode && $id) {
            if (empty($web_stypeid_open)) {
                $map_arc['typeid'] = array('in',get_arr_column($info,'typeid'));
            } else {
                $typeids_tmp = get_arr_column($info,'typeid');
                $typeids_tmp = implode(',', $typeids_tmp);
                $map_arc[] = \think\Db::raw(" ( typeid IN ({$typeids_tmp}) OR CONCAT(',', stypeid, ',') LIKE '%,{$id},%' ) ");
            }
        }
        // 可发布文档列表的频道模型
        static $new_channel = null;
        if (null === $new_channel) {
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeRow = \think\Db::name('arctype')->field('channeltype,current_channel')->select();
            foreach ($arctypeRow as $key => $val) {
                if (in_array($val['channeltype'], $allow_release_channel)) {
                    $new_channel[] = $val['channeltype'];
                }
                if (in_array($val['current_channel'], $allow_release_channel)) {
                    $new_channel[] = $val['current_channel'];
                }
            }
            $new_channel = array_unique($new_channel);
        }
        !empty($new_channel) && $map_arc['a.channel'] = ['IN', $new_channel];
        $map_arc['a.arcrank'] = ['egt', 0];
        $map_arc['a.status'] = 1;
        $map_arc['a.is_del'] = 0;
        $map_arc['a.lang'] = $home_lang;
        /*定时文档显示插件*/
        if (is_dir('./weapp/TimingTask/')) {
            $TimingTaskRow = model('Weapp')->getWeappList('TimingTask');
            if (!empty($TimingTaskRow['status']) && 1 == $TimingTaskRow['status']) {
                $map_arc['a.add_time'] = array('elt', getTime()); // 只显当天或之前的文档
            }
        }
        /*end*/

        if (1 == $method_mode) { // 方法1
            $count_type = [];
            $archivesList = \think\Db::name('archives')->alias('a')->field("typeid,stypeid")->where($map_arc)->order('typeid asc')->select();
            foreach ($archivesList as $key => $val) {
                if (!isset($count_type[$val['typeid']])) {
                    $count_type[$val['typeid']] = [
                        'typeid'    => $val['typeid'],
                        'count' => 1,
                    ];
                } else {
                    $count_type[$val['typeid']]['count']++;
                }

                // 开启副栏目
                if (!empty($web_stypeid_open) && !empty($val['stypeid'])) {
                    $stypeids = explode(',', $val['stypeid']);
                    $arr_index = array_search($val['typeid'], $stypeids);
                    if (is_numeric($arr_index) && 0 <= $arr_index) {
                        unset($stypeids[$arr_index]);
                    }
                    foreach ($stypeids as $_k => $_v) {
                        if (!isset($count_type[$_v])) {
                            $count_type[$_v] = [
                                'typeid'    => $_v,
                                'count' => 1,
                            ];
                        } else {
                            $count_type[$_v]['count']++;
                        }
                    }
                }
            }
        }

        $db = new \think\Db;
        $pagetotal = 0;
        $arctypeAllSub = arctypeAllSub(); // 获取所有栏目，并每个栏目都包含所有子栏目，以及自己
        $info2 = $tplData = [];
        $info = convert_arr_key($info,'typeid');
        foreach ($info as $k => $v) {
            //外链
            if ($v['is_part'] == 1 || 'ask' == $v['nid']) {
                $dir = ROOT_PATH . trim($v['dirpath'], '/');
                if (!empty($v['dirpath']) && true == is_dir($dir)) {//判断是否生成过文件夹,文件夹存在则删除
                    deldir_html($dir);
                }
                continue;
            }

            if (1 == $method_mode) { // 方法1
                if (!isset($info[$v['typeid']]['count'])){    //判断当前栏目的count是否已经存在
                    $v['count'] = 0;
                }else{
                    $v['count'] = intval($info[$v['typeid']]['count']);
                }
                if (isset($count_type[$v['typeid']])){    //存在当前栏目个数
                    $v['count'] += $count_type[$v['typeid']]['count'];
                }

                //判断是否存在上级目录，且当前栏目和上级栏目都不是单页，且当前栏目和上级栏目是相同模型，则，把当前栏目的aid和count赋值给父栏目
                if ($v['parent_id'] && !in_array($v['current_channel'], [6,8]) && isset($info[$v['parent_id']]) && $v['current_channel'] == $info[$v['parent_id']]['current_channel']){
                    if (isset($info[$v['parent_id']]['count'])) {
                        $info[$v['parent_id']]['count'] += intval($v['count']);
                    }else{
                        $info[$v['parent_id']]['count'] = intval($v['count']);
                    }
                }
            }

            /**
             * 判断是否需要更新子栏目
             * 1、更新子栏目，正常处理
             * 2、不更新子栏目
             * （1）、选择指定栏目的情况下，判断当前栏目是否为选择栏目，如果是，走正常流程，否则，去掉当前栏目
             * （2）、不选择指定栏目的情况下，判断当前栏目是否为顶级栏目，如果是，走正常流程，否则，去掉当前栏目
             */
            if (empty($seo_upnext) && ( (!empty($id) && $v['typeid'] != $id) || (empty($id) && !empty($v['parent_id'])) ) ){
                continue;
            }

            $tag_attr_arr = [];
            if (!isset($tplData[$v['templist']])) {
                $tpl = !empty($v['templist']) ? str_replace('.'.$view_suffix, '',$v['templist']) : 'lists_'. $v['nid'];
                $template_html = "./template/".TPL_THEME."pc/".$tpl.".htm";
                $content = file_get_contents($template_html);
                if ($content) {
                    preg_match_all('/\{eyou:list(\s+)?(.*)\}/i', $content, $matchs);
                    if (!empty($matchs[0][0])) {
                        $tag_attr = !empty($matchs[2][0]) ? $matchs[2][0] : '';
                        if (!empty($tag_attr)) {
                            $tag_attr = preg_replace('/([a-z]+)(\s*)=(\s*)([\'|\"]?)([^ \f\n\r\t\v\'\"]+)([\'|\"]?)/i', '${1}=\'${5}\'', $tag_attr); // 属性引导统一设置单引号
                            preg_match_all('/([0-9a-z_-]+)=\'([^\']+)\'/i', $tag_attr, $attr_matchs);
                            $attr_keys = !empty($attr_matchs[1]) ? $attr_matchs[1] : [];
                            $attr_vals = !empty($attr_matchs[2]) ? $attr_matchs[2] : [];
                            if (!empty($attr_keys)) {
                                foreach ($attr_keys as $_ak => $_av) {
                                    $tag_attr_arr[$_av] = $attr_vals[$_ak];
                                }
                                // 每页条数
                                if (!empty($tag_attr_arr['loop'])) $tag_attr_arr['pagesize'] = intval($tag_attr_arr['loop']);
                                $tag_attr_arr['pagesize'] = !empty($tag_attr_arr['pagesize']) ? intval($tag_attr_arr['pagesize']) : 10;
                                // 模型ID
                                if (!empty($tag_attr_arr['modelid'])) $tag_attr_arr['channelid'] = intval($tag_attr_arr['modelid']);
                                // 排序
                                if (empty($tag_attr_arr['ordermode'])) {
                                    if (!empty($tag['orderWay'])) {
                                        $tag_attr_arr['ordermode'] = $tag_attr_arr['orderWay'];
                                    } else {
                                        $tag_attr_arr['ordermode'] = !empty($tag_attr_arr['orderway']) ? $tag_attr_arr['orderway'] : 'desc';
                                    }
                                }
                            }
                        }
                        $tag_attr_arr['orderby'] = !empty($tag_attr_arr['orderby']) ? $tag_attr_arr['orderby'] : "";
                        $tag_attr_arr['ordermode'] = !empty($tag_attr_arr['ordermode']) ? $tag_attr_arr['ordermode'] : "desc";
                        $tplData[$v['templist']] = $tag_attr_arr;
                    } else {
                        $tplData[$v['templist']]['count'] = -1;
                    }
                }
            }
            $tplDataInfo = !empty($tplData[$v['templist']]) ? $tplData[$v['templist']] : [];

            if (2 == $method_mode) { // 方法2
                $map_arc2 = $map_arc;
                if (empty($web_stypeid_open)) { // 没开启副栏目
                    $map_arc2['a.typeid'] = array('in', $arctypeAllSub[$v['typeid']]);
                } else { // 开启副栏目
                    $stypeid_where = "";
                    $typeid_str = implode(',', $arctypeAllSub[$v['typeid']]);
                    foreach ($arctypeAllSub[$v['typeid']] as $_k => $_v) {
                        $stypeid_where .= " OR CONCAT(',', a.stypeid, ',') LIKE '%,{$_v},%' ";
                    }
                    $map_arc2[] = $db::raw(" (a.typeid IN ({$typeid_str}) {$stypeid_where}) ");
                }

                $v['count'] = 0;
                if (!in_array($v['current_channel'], [6,8])) {
                    $v['count'] = $db::name('archives')->alias('a')->where($map_arc2)->count('aid');
                }
            }

            if (in_array($v['current_channel'], [6,8])){
                $v['pagesize'] = 1;
                $v['pagetotal'] = 1;
                $pagetotal += $v['pagetotal'];
            }else{
                if (!empty($tplDataInfo)) {
                    $count = !empty($tplDataInfo['count']) ? $tplDataInfo['count'] : 0;
                    if (-1 == $count) {
                        $v['count'] = 1;
                    } else {
                        $pagesize = !empty($tplDataInfo['pagesize']) ? $tplDataInfo['pagesize'] : 0;
                        $channelid = !empty($tplDataInfo['channelid']) ? $tplDataInfo['channelid'] : 0;
                        if (!empty($channelid)) {
                            $map_arc['a.channel'] = $channelid;
                            if (isset($map_arc['a.typeid'])) {
                                unset($map_arc['a.typeid']);
                            }
                            if (isset($map_arc[0])) {
                                foreach ($map_arc as $_k => $_v) {
                                    if (is_numeric($_k) && stristr($_v, 'stypeid')) {
                                        unset($map_arc[$_k]);
                                    }
                                }
                            }
                            $v['count'] = $db::name('archives')->alias('a')->where($map_arc)->count();
                        }
                        if ($aid) {
                            $orderby = !empty($tplDataInfo['orderby']) ? $tplDataInfo['orderby'] : '';
                            $ordermode = !empty($tplDataInfo['ordermode']) ? $tplDataInfo['ordermode'] : 'desc';
                        }
                    }
                }
                $v['pagesize']  = !empty($pagesize) ? $pagesize : 10;
                $v['pagetotal'] = !empty($v['count']) ? (int)ceil($v['count'] / $v['pagesize']) : 1;
                $pagetotal += $v['pagetotal'];
            }
            $v['orderby'] = !empty($orderby) ? $orderby : "";
            $v['ordermode'] = !empty($ordermode) ? $ordermode : "desc";

            $info2[] = $v;
        }
        // file_put_contents ( ROOT_PATH."/log.txt", var_export($info2,true) . "\r\n", FILE_APPEND );
        // exit;
        return ["info"=>$info2, "pagetotal"=>$pagetotal];
    }
}

/**
 * 删除文件夹
 * @param $dir
 * @return bool
 */
if (!function_exists('deldir_html'))
{
    function deldir_html($dir = '')
    {
        //先删除目录下的文件：
        $fileArr = glob($dir.'/*.html');
        if (!empty($fileArr)) {
            foreach ($fileArr as $key => $val) {
                !empty($val) && @unlink($val);
            }
        }

        $fileArr = glob($dir.'/*');
        if(empty($fileArr)){ //目录为空
            rmdir($dir); // 删除空目录
        }
        return true;
    }
}

/*
 * 以下几个方法为生成静态时使用
 * 获取所有需要生成的文档的aid集合
 * $typeid  栏目id
 * $startid 起始ID（空或0表示从头开始）
 * $endid   结束ID（空或0表示直到结束ID）
 */

if (!function_exists('getAllArchivesAid'))
{
    function getAllArchivesAid($typeid = 0, $home_lang = '', $startid = 0,$endid = 0){
        empty($home_lang) && $home_lang = get_current_lang();
        $map = [];
        if (!empty($typeid)){
            $id_arr = [$typeid];
            getAllChild($id_arr,$typeid,2);
            $map['typeid'] = ['in',$id_arr];
        }
        if (!empty($startid) && !empty($endid)){
            $map['aid'] = ['between',[$startid,$endid]];
        }else if(!empty($startid)){
            $map['aid'] = ['egt',$startid];
        }else if(!empty($endid)){
            $map['aid'] = ['elt',$endid];
        }
        // 可发布文档列表的频道模型
        static $new_channel = null;
        if (null === $new_channel) {
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeRow = \think\Db::name('arctype')->field('channeltype,current_channel')->select();
            foreach ($arctypeRow as $key => $val) {
                if (in_array($val['channeltype'], $allow_release_channel)) {
                    $new_channel[] = $val['channeltype'];
                }
                if (in_array($val['current_channel'], $allow_release_channel)) {
                    $new_channel[] = $val['current_channel'];
                }
            }
            $new_channel = array_unique($new_channel);
        }
        !empty($new_channel) && $map['channel'] = ['IN', $new_channel];
        $map['arcrank'] = ['egt', 0];
        $map['status'] = 1;
        $map['is_del'] = 0;
        $map['lang'] = $home_lang;
        /*定时文档显示插件*/
        if (is_dir('./weapp/TimingTask/')) {
            $TimingTaskRow = model('Weapp')->getWeappList('TimingTask');
            if (!empty($TimingTaskRow['status']) && 1 == $TimingTaskRow['status']) {
                $map['add_time'] = array('elt', getTime()); // 只显当天或之前的文档
            }
        }
        /*end*/
        $row = \think\Db::name('archives')
            ->field('aid,typeid,channel')
            ->where($map)
            ->order('aid asc')
            ->select();
        $aid_arr = $typeid_arr = $channel_arr = [];
        foreach ($row as $key => $val) {
            array_push($aid_arr, $val['aid']);
            if (!in_array($val['typeid'], $typeid_arr)) {
                array_push($typeid_arr, $val['typeid']);
            }
            $channel_arr[$val['channel']][] = $val['aid'];
        }

        return [
            'aid_arr'   => $aid_arr,
            'typeid_arr'   => $typeid_arr, // 文档所涉及的栏目ID
            'channel_arr'   => $channel_arr, // 文档以模型ID分组
        ];
    }
}

if (!function_exists('getAllArchives'))
{
    //递归查询所有栏目
    function getAllArchives($home_lang,$id,$aid = ''){
        $map = [];
        if(!empty($aid)){
            if (is_array($aid)) {
                $map['a.aid'] = ['in',$aid];
            } else {
                $map['a.aid'] = $aid;
            }
        }else if (!empty($id)){
            $id_arr = [$id];
            getAllChild($id_arr,$id,2);
            $map['a.typeid'] = ['in',$id_arr];
        }

        // 可发布文档列表的频道模型
        $new_channel = cache("application_common_getAllArchives_new_channel");
        if(empty($new_channel)){
            $new_channel = [];
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeList = \think\Db::name('arctype')->field('channeltype,current_channel')->select();
            foreach ($arctypeList as $key => $val) {
                if (in_array($val['channeltype'], $allow_release_channel)) {
                    $new_channel[] = $val['channeltype'];
                }
                if (in_array($val['current_channel'], $allow_release_channel)) {
                    $new_channel[] = $val['current_channel'];
                }
            }
            $new_channel = array_unique($new_channel);
            cache("application_common_getAllArchives_new_channel", $new_channel, null, 'arctype');
        }
        !empty($new_channel) && $map['a.channel']  = ['IN', $new_channel];

        $map['a.is_jump'] = 0;
        $map['a.status'] = 1;
        $map['a.is_del'] = 0;
        $map['a.lang'] = $home_lang;
        $info = \think\Db::name('archives')->field('a.*')
            ->alias('a')
            ->where($map)
            ->select();
        $info = getAllContent($info);

        /*栏目信息*/
        $arctypeRow = cache("application_common_getAllArchives_arctypeRow");
        if(empty($arctypeRow)){
            $arctypeRow = \think\Db::name('arctype')->field('c.*, a.*, a.id as typeid')
                ->alias('a')
                ->where(['a.lang'=>$home_lang])
                ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
                ->getAllWithIndex('typeid');
            cache("application_common_getAllArchives_arctypeRow", $arctypeRow, null, 'arctype');
        }

        return [
            'info'          => $info,
            'arctypeRow'   => $arctypeRow,
        ];
    }
}

if (!function_exists('getPreviousArchives'))
{
    //获取上一条文章数据
    function getPreviousArchives($home_lang,$id,$aid = 0){
        $map = [];
        if(!empty($aid)){
            $map['a.aid'] = ['lt',$aid];
        }
        if (!empty($id)){
            $id_arr = [$id];
            getAllChild($id_arr,$id,2);
            $map['a.typeid'] = ['in',$id_arr];
        }
        // 可发布文档列表的频道模型
        static $new_channel = null;
        if (null === $new_channel) {
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeRow = \think\Db::name('arctype')->field('channeltype,current_channel')->select();
            foreach ($arctypeRow as $key => $val) {
                if (in_array($val['channeltype'], $allow_release_channel)) {
                    $new_channel[] = $val['channeltype'];
                }
                if (in_array($val['current_channel'], $allow_release_channel)) {
                    $new_channel[] = $val['current_channel'];
                }
            }
            $new_channel = array_unique($new_channel);
        }
        !empty($new_channel) && $map['a.channel']  = ['IN', $new_channel];
        $map['a.lang'] = $home_lang;
        $map['a.is_jump'] = 0;
        $map['a.is_del'] = 0;
        $map['a.status'] = 1;
        $info = \think\Db::name('archives')->field('a.*')
            ->alias('a')
            ->where($map)
            ->order("a.aid desc")
            ->limit(1)
            ->select();
        $info = getAllContent($info);

        /*栏目信息*/
        $arctypeRow = \think\Db::name('arctype')->field('c.*, a.*, a.id as typeid')
            ->alias('a')
            ->where(['a.lang'=>$home_lang])
            ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
            ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
            ->getAllWithIndex('typeid');

        return [
            'info'          => $info,
            'arctypeRow'   => $arctypeRow,
        ];
    }
}

if (!function_exists('getNextArchives'))
{
    //获取下一条文章数据
    function getNextArchives($home_lang,$id,$aid = 0){
        $map = [];
        if(!empty($aid)){
            $map['a.aid'] = ['gt',$aid];
        }
        if (!empty($id)){
            $id_arr = [$id];
            getAllChild($id_arr,$id,2);
            $map['a.typeid'] = ['in',$id_arr];
        }
        // 可发布文档列表的频道模型
        static $new_channel = null;
        if (null === $new_channel) {
            $allow_release_channel = config('global.allow_release_channel');
            $arctypeRow = \think\Db::name('arctype')->field('channeltype,current_channel')->select();
            foreach ($arctypeRow as $key => $val) {
                if (in_array($val['channeltype'], $allow_release_channel)) {
                    $new_channel[] = $val['channeltype'];
                }
                if (in_array($val['current_channel'], $allow_release_channel)) {
                    $new_channel[] = $val['current_channel'];
                }
            }
            $new_channel = array_unique($new_channel);
        }
        !empty($new_channel) && $map['a.channel']  = ['IN', $new_channel];
        $map['a.lang'] = $home_lang;
        $map['a.is_jump'] = 0;
        $map['a.is_del'] = 0;
        $map['a.status'] = 1;
        $info = \think\Db::name('archives')->field('a.*')
            ->alias('a')
            ->where($map)
            ->order("a.aid asc")
            ->limit(1)
            ->select();
        $info = getAllContent($info);

        /*栏目信息*/
        $arctypeRow = \think\Db::name('arctype')->field('c.*, a.*, a.id as typeid')
            ->alias('a')
            ->where(['a.lang'=>$home_lang])
            ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
            ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
            ->getAllWithIndex('typeid');

        return [
            'info'          => $info,
            'arctypeRow'   => $arctypeRow,
        ];
    }
}

if (!function_exists('getAllContent'))
{
    //获取指定文档列表的内容附加表字段值
    function getAllContent($archivesList = []){
        $contentList = [];
        $db = new \think\Db;
        $channeltype_list = config('global.channeltype_list');
        $arr = group_same_key($archivesList, 'channel');
        foreach ($arr as $nid => $list) {
            $table = array_search($nid, $channeltype_list);
            if (!empty($table)) {
                $aids = get_arr_column($list, 'aid');
                $row = $db::name($table.'_content')->field('*')
                    ->where(['aid'=>['IN', $aids]])
                    ->select();
                $result = [];
                foreach ($row as $_k => $_v) {
                    unset($_v['id']);
                    unset($_v['add_time']);
                    unset($_v['update_time']);
                    $result[$_v['aid']] = $_v;
                }

                $contentList += $result;
            }
        }

        $firstFieldData = current($contentList);
        foreach ($archivesList as $key => $val) {

            /*文档所属模型是不存在，或已被禁用*/
            $table = array_search($val['channel'], $channeltype_list);
            if (empty($table)) {
                unset($archivesList[$key]);
                continue;
            }
            /*end*/

            /*文档内容表没有记录的特殊情况*/
            if (!isset($contentList[$val['aid']])) {
                $contentList[$val['aid']] = [];
                if (!empty($firstFieldData)) {
                    foreach ($firstFieldData as $k2 => $v2) {
                        if (in_array($k2, ['aid'])) {
                            $contentList[$val['aid']][$k2] = $val[$k2];
                        } else {
                            $contentList[$val['aid']][$k2] = '';
                        }
                    }
                }
            }
            /*end*/
            $val = array_merge($val, $contentList[$val['aid']]);
            $archivesList[$key] = $val;
        }

        return $archivesList;
    }
}

if (!function_exists('getAllTags'))
{
    //递归查询所有栏目内容
    function getAllTags($aid_arr = []){
        $map = [];
        $info = [];
        if (!empty($aid_arr)){
            $map['aid'] = ['in',$aid_arr];
        }
        $result = \think\Db::name('taglist')->field("aid,tag")->where($map)->select();
        if ($result) {
            foreach ($result as $key => $val) {
                if (!isset($info[$val['aid']])) $info[$val['aid']] = array();
                array_push($info[$val['aid']], $val['tag']);
            }
        }

        return $info;
    }
}

if (!function_exists('getAllAttrInfo'))
{
    /**
     * 查询所有文档的其他页面内容
     * @param  array  $channel_aids_arr [以模型ID分组的文档ID]
     * @return [type]                   [description]
     */
    function getAllAttrInfo($channel_aids_arr = []){
        $info = [];
        foreach ($channel_aids_arr as $channel => $aids) {
            if (2 == $channel) {
                $info['product_img'] = model('ProductImg')->getProImg($aids);
                $info['product_attr'] = model('ProductAttr')->getProAttr($aids);
            } else if (3 == $channel) {
                $info['images_upload'] = model('ImagesUpload')->getImgUpload($aids);
            } else if (4 == $channel) {
                $info['download_file'] = model('DownloadFile')->getDownFile($aids);
            }
        }
        return $info;
    }
}

if (!function_exists('getOneAttrInfo'))
{
    /**
     * 与getAllAttrInfo方法结合使用
     * @param  array   $info [getAllAttrInfo方法返回的值]
     * @param  integer $aid  [文档ID]
     * @return [type]        [description]
     */
    function getOneAttrInfo($info = [], $aid = 0){
        $arr = [];

        if (isset($info['product_img'][$aid])) {
            $arr['product_img'][$aid] = $info['product_img'][$aid];
        }
        if (isset($info['product_attr'][$aid])) {
            $arr['product_attr'][$aid] = $info['product_attr'][$aid];
        }
        if (isset($info['images_upload'][$aid])) {
            $arr['images_upload'][$aid] = $info['images_upload'][$aid];
        }
        if (isset($info['download_file'][$aid])) {
            $arr['download_file'][$aid] = $info['download_file'][$aid];
        }

        return $arr;
    }
}

if (!function_exists('getOrderBy'))
{
    //根据tags-list规则，获取查询排序，用于标签文件 TagArclist / TagList
    function getOrderBy($orderby,$ordermode,$isrand=false){
        switch ($orderby) {
            case 'hot':
            case 'click':
                $orderby = "a.click {$ordermode}";
                break;
            case 'sales_num':
                $orderby = "a.sales_num {$ordermode}";
                break;
            case 'users_price':
                $orderby = "a.users_price {$ordermode}";
                break;
            case 'id': // 兼容写法
            case 'aid':
                $orderby = "a.aid {$ordermode}";
                break;

            case 'now':
            case 'new': // 兼容写法
            case 'pubdate': // 兼容写法
            case 'add_time':
                $orderby = "a.add_time {$ordermode}";
                break;

            case 'update_time':
                $orderby = "a.update_time {$ordermode}";
                break;

            case 'sortrank': // 兼容写法
            case 'weight': // 兼容写法
            case 'sort_order':
                $orderby = "a.sort_order {$ordermode}";
                break;

            case 'rand':
                if (true === $isrand) {
                    $orderby = "rand()";
                } else {
                    $orderby = "a.aid {$ordermode}";
                }
                break;

            default:
            {
                if (empty($orderby)) {
                    $orderby = 'a.sort_order asc, a.aid desc';
                } elseif (trim($orderby) != 'rand()') {
                    $orderbyArr = explode(',', $orderby);
                    foreach ($orderbyArr as $key => $val) {
                        $val = trim($val);
                        if (preg_match('/^([a-z]+)\./i', $val) == 0) {
                            $val = 'a.'.$val;
                            $orderbyArr[$key] = $val;
                        }
                    }
                    $orderby = implode(',', $orderbyArr);
                }
                break;
            }
        }

        return $orderby;
    }
}

if (!function_exists('getLocationPages'))
{
    /*
     * 获取当前文章属于栏目第几条
     */
    function getLocationPages($tid,$aid,$order){
        $map_arc = [];
        if (!empty($tid)){
            $id_arr = [$tid];
            getAllChild($id_arr,$tid,2);
            $map_arc['typeid'] = ['in',$id_arr];
        }
        $map_arc['is_del'] = 0;
        $map_arc['status'] = 1;
        $result = \think\Db::name('archives')->alias('a')->field("a.aid")->where($map_arc)->orderRaw($order)->select();

        foreach ($result as $key=>$val){
            if ($aid == $val['aid']){
                return $key + 1;
            }
        }
        return false;
    }
}

if (!function_exists('auto_hide_index')) 
{
    /**
     * URL中隐藏index.php入口文件（适用后台显示前台的URL）
     */
    function auto_hide_index($url, $seo_inlet = null) {
        static $web_adminbasefile = null;
        if (null === $web_adminbasefile) {
            $web_adminbasefile = tpCache('web.web_adminbasefile');
            $web_adminbasefile = !empty($web_adminbasefile) ? $web_adminbasefile : ROOT_DIR.'/login.php'; // 支持子目录
        }
        $url = str_replace($web_adminbasefile, ROOT_DIR.'/index.php', $url); // 支持子目录
        null === $seo_inlet && $seo_inlet = config('ey_config.seo_inlet');
        if (1 == $seo_inlet) {
            $url = str_replace('/index.php/', '/', $url);
        }
        return $url;
    }
}

if (!function_exists('getArchivesField')) 
{
    /**
     * 获取指定文档的字段值
     */
    function getArchivesField($aid = 0, $fieldName = 'aid') {
        $value = '';
        if (0 < intval($aid)) {
            if ('arcurl' == $fieldName) {
                $row = \think\Db::name('archives')->where(['aid'=>$aid])->find();
                $value = get_arcurl($row);
            } else {
                $value = \think\Db::name('archives')->where(['aid'=>$aid])->getField($fieldName);
                if ('litpic' == $fieldName) {
                    $value = handle_subdir_pic($value); // 支持子目录
                }
            }
        }

        return $value;
    }
}

if (!function_exists('GetUsersLatestData')) 
{
    /**
     * 获取登录的会员最新数据
     */
    function GetUsersLatestData($users_id = null) {
        $users_id = empty($users_id) ? session('users_id') : $users_id;
        if(!empty($users_id)) {
            /*读取的字段*/
            $field = 'b.*, b.discount as level_discount,a.*';
            /* END */

            /*查询数据*/
            $users = \think\Db::name('users')->field($field)
                ->alias('a')
                ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                ->where([
                    'a.users_id'        => $users_id,
                    'a.lang'            => get_home_lang(),
                    'a.is_activation'   => 1,
                    'a.is_del'          => 0,
                ])->find();
            // 会员不存在则返回空
            if (empty($users)) return false;
            /* END */

            /*会员数据处理*/
            // 去掉余额小数点多余的0
            $users['users_money'] = floatval($users['users_money']);
            // 头像处理
            $users['head_pic'] = get_head_pic(htmlspecialchars_decode($users['head_pic']), false, $users['sex']);
            // 昵称处理
            $users['nickname'] = empty($users['nickname']) ? $users['username'] : $users['nickname'];
            // 密码为空并且存在openid则表示微信注册登录，密码字段更新为0，可重置密码一次。
            $users['password'] = empty($users['password']) && !empty($users['thirdparty']) ? 1 : 1;
            // 删除登录密码及支付密码
            unset($users['paypwd']);
            // 级别处理
            $LevelData = [];
            if (intval($users['level_maturity_days']) >= 36600) {
                $users['maturity_code'] = 1;
                $users['maturity_date'] = '终身';
            }else if (0 == $users['open_level_time'] && 0 == $users['level_maturity_days']) {
                $users['maturity_code'] = 0;
                $users['maturity_date'] = '未升级会员';// 没有升级会员，置空
            }else{
                /*计算剩余天数*/
                $days = $users['open_level_time'] + (intval($users['level_maturity_days']) * 86400);
                // 取整
                $days = ceil(($days - getTime()) / 86400);
                if (0 >= $days) {
                    /*更新会员的级别*/
                    $LevelData = model('EyouUsers')->UpUsersLevelData($users_id);
                    /* END */
                    $users['maturity_code'] = 2;
                    $users['maturity_date'] = '未升级会员';// 会员过期，置空
                }else{
                    $users['maturity_code'] = 3;
                    $users['maturity_date'] = $days.' 天';
                }
                /* END */
            }
            /* END */
            
            // 合并数据
            $LatestData = array_merge($users, $LevelData);
            /*更新session*/
            session('users', $LatestData);
            session('users_id', $LatestData['users_id']);
            cookie('users_id', $LatestData['users_id']);
            /* END */
            // 返回数据
            return $LatestData;
        }else{
            // session中不存在会员ID则返回空
            session('users_id', null);
            session('users', null);
            cookie('users_id', null);
            return false;
        }
    }
}

if (!function_exists('GetTotalArc')) 
{
    /**
     * 统计栏目文章数
     */
    function GetTotalArc($typeid = 0)
    {
        if (empty($typeid)) {
            return 0;
        } else {
            $cache_key = "common-GetTotalArc-{$typeid}";
            $count = cache($cache_key);
            if (empty($count)) {
                $row = model('Arctype')->getHasChildren($typeid);
                if (empty($row)) return 0;

                $typeids = array_keys($row);
                $allow_release_channel = config('global.allow_release_channel');
                $condition = [
                    'typeid' => ['IN', $typeids],
                    'channel' => ['IN', $allow_release_channel],
                    'arcrank' => ['gt', -1],
                    'status' => 1,
                    'is_del' => 0,
                ];
                /*定时文档显示插件*/
                if (is_dir('./weapp/TimingTask/')) {
                    $TimingTaskRow = model('Weapp')->getWeappList('TimingTask');
                    if (!empty($TimingTaskRow['status']) && 1 == $TimingTaskRow['status']) {
                        $condition['add_time'] = ['elt', getTime()]; // 只显当天或之前的文档
                    }
                }
                /*end*/
                $count = \think\Db::name('archives')->where($condition)->count('aid');
                cache($cache_key, $count, null, 'archives');
            }

            return intval($count);
        }
    }
}

if (!function_exists('GetTagIndexRanking')) 
{
    /**
     * 统计栏目文章数
     */
    function GetTagIndexRanking($limit = 5, $field = 'id, tag')
    {
        $order = 'weekcc desc, monthcc desc';
        $limit = '0, ' . $limit;
        $list = \think\Db::name('tagindex')->field($field)->order($order)->limit($limit)->select();

        return $list;
    }
}

if (!function_exists('weapptaglib')) 
{
    /**
     * 通用 - 插件模板标签
     */
    function weapptaglib($weapp_code = '', $act = '', $vars = [])
    {
        $list = '';
        if (empty($weapp_code) || empty($act)) {
            return '';
        }
        
        $class = '\weapp\\'.$weapp_code.'\controller\\'.$weapp_code;
        $ctl = new $class();
        $list = $ctl->$act($vars);

        return $list;
    }
}

if (!function_exists('rand_username')) 
{
    /**
     * 生成随机用户名，确保唯一性
     */
    function rand_username($username = '', $prefix = 'U', $includenumber = 2)
    {
        if (empty($username)) {
            $username = $prefix . get_rand_str(6, 0, $includenumber);
        }
        $count = \think\Db::name('users')->where('username', $username)->count();
        if (!empty($count)) {
            $username = $prefix . get_rand_str(6, 0, $includenumber);
            return rand_username($username, $prefix, $includenumber);
        }

        return $username;
    }
}

if (!function_exists('pay_success_logic')) 
{
    /**
     * 支付成功的后置业务逻辑
     */
    function pay_success_logic($users_id = 0, $order_code = '', $pay_details = [], $paycode = 'alipay', $notify = true) {
        $pay_method_arr = config('global.pay_method_arr');

        $OrderWhere = [
            'order_code'    => $order_code,
        ];
        !empty($users_id) && $OrderWhere['users_id'] = intval($users_id);
        $orderData = \think\Db::name('shop_order')->field('*')->where($OrderWhere)->find();

        if (empty($orderData)) {
            return [
                'code'  => 0,
                'msg'   => '该订单不存在！',
            ];
        }
        else if (0 == $orderData['order_status']) {
            $saveData = [
                'order_status' => 1,
                'pay_details'  => serialize($pay_details),
                'pay_time'     => getTime(),
                'update_time'  => getTime(),
            ];
            if ('balance' == $paycode) {
                $saveData['pay_name'] = $paycode;
                $saveData['wechat_pay_type'] = ''; // 余额支付则清空微信标志
            } else if ('alipay' == $paycode) {
                $saveData['pay_name'] = $paycode;
                $saveData['wechat_pay_type'] = ''; // 支付宝支付则清空微信标志
            }
            $ret = \think\Db::name('shop_order')->where([
                'order_id'  => $orderData['order_id'],
                'users_id'  => $orderData['users_id'],
            ])->update($saveData);
            if (false !== $ret) {

                // 更新订单变量，保存最新数据
                $orderData = array_merge($orderData, $saveData);

                if (!empty($paycode) && isset($pay_method_arr[$paycode])){
                    $orderData['pay_method'] = $pay_method_arr[$paycode];
                    $actionNote = "使用{$pay_method_arr[$paycode]}完成支付";
                }else{
                    $orderData['pay_method'] = '';
                    $actionNote = "完成支付";
                }
                // 添加订单操作记录
                AddOrderAction($orderData['order_id'], $orderData['users_id'], 0, 1, 0, 1, "支付成功", $actionNote);

                // 发送站内信给后台
                SendNotifyMessage($orderData, 5, 1, 0);

                // 虚拟自动发货
                $PayModel = new \app\user\model\Pay;
                $autoSendGoods = $PayModel->afterVirtualProductPay($orderData);

                $data = [];
                if (false === $autoSendGoods && true === $notify) {
                    $users = \think\Db::name('users')->field('*')->find($orderData['users_id']);
                    // 邮箱发送
                    $data['email'] = GetEamilSendData(tpCache('smtp'), $users, $orderData, 1, $paycode);
                    // 手机发送
                    $data['mobile'] = GetMobileSendData(tpCache('sms'), $users, $orderData, 1, $paycode);
                }

                if ('balance' == $paycode) {
                    $users = empty($users) ? \think\Db::name('users')->field('*')->find($orderData['users_id']) : $users;
                    UsersMoneyRecording($order_code, $users, $orderData['order_amount'], '商品购买', 3);
                }

                // 订单操作完成，返回跳转
                $url = url('user/Shop/shop_centre');
                if (true === $autoSendGoods) {
                    $msg = '支付订单完成！';
                } else {
                    $msg = '支付成功，处理订单完成！';
                }
                return [
                    'code'  => 1,
                    'msg'   => $msg,
                    'url'   => $url,
                    'data'  => $data,
                ];
            }
            else {
                return [
                    'code'  => 0,
                    'msg'   => '支付成功，处理订单失败！',
                ];
            }
        }
        else if (1 <= $orderData['order_status'] && $orderData['order_status'] <= 3) {
            return [
                'code'  => 1,
                'msg'   => '已支付',
            ];
        }
        else if (4 == $orderData['order_status']) {
            return [
                'code'  => 0,
                'msg'   => '该订单已过期！',
            ];
        }
        else {
            return [
                'code'  => 0,
                'msg'   => '该订单不存在或已关闭！',
            ];
        }
    }
}

if (!function_exists('OrderServiceLog')) 
{
    /**
     * 订单服务记录表
     * 参数说明：
     * $ServiceId 订单服务信息ID
     * $OrderId   订单ID
     * $UsersId   会员ID
     * $AdminId   管理员ID
     * $LogNote   记录信息
     * 返回说明：
     * return 无需返回
     */
    function OrderServiceLog($ServiceId = null, $OrderId = null, $UsersId = 0, $AdminId = 0, $LogNote = '会员提交退换货申请')
    {
        if (empty($ServiceId) || empty($OrderId)) return false;
        /*使用余额支付时，同时添加一条记录到金额明细表*/
        $Time = getTime();
        $LogData = [
            'service_id'  => $ServiceId,
            'order_id'    => $OrderId,
            'users_id'    => $UsersId,
            'admin_id'    => $AdminId,
            'log_note'    => $LogNote,
            'add_time'    => $Time,
            'update_time' => $Time,
        ];
        M('shop_order_service_log')->add($LogData);
        /* END */
    }
}

if (!function_exists('UsersMoneyRecording')) 
{
    /**
     * 添加会员余额明细表
     * 参数说明：
     * $OrderCode  订单编号
     * $Users      会员信息
     * $UsersMoney 记录余额
     * $Cause      订单状态，如过期，取消，退款，退货等
     * 返回说明：
     * return 无需返回
     */
    function UsersMoneyRecording($OrderCode = null, $Users = [], $UsersMoney = null, $Cause = '商品退换货', $CauseType = 2)
    {
        if (empty($OrderCode) || empty($Users) || empty($UsersMoney)) return false;
        $Time = getTime();
        $pay_method = '';
        /*使用余额支付时，同时添加一条记录到金额明细表*/
        if (2 == $CauseType) {
            $Status = 3;
            $Cause = $Cause . '，退还使用余额，订单号：' . $OrderCode;
            $UsersNewMoney = sprintf("%.2f", $Users['users_money'] += $UsersMoney);
            $pay_method = 'balance';
        } else if (3 == $CauseType) {
            $Status = 2;
            $Cause = $Cause . '，使用余额支付，订单号：' . $OrderCode;
            $UsersNewMoney = sprintf("%.2f", $Users['users_money']);
            $pay_method = 'balance';
        }
        $MoneyData = [
            'users_id'     => $Users['users_id'],
            'money'        => $UsersMoney,
            'users_money'  => $UsersNewMoney,
            'cause'        => $Cause,
            'cause_type'   => $CauseType,
            'status'       => $Status,
            'pay_method'   => $pay_method,
            'pay_details'  => '',
            'order_number' => $OrderCode,
            'add_time'     => $Time,
            'update_time'  => $Time,
        ];
        M('users_money')->add($MoneyData);
        /* END */
    }
}

if (!function_exists('GetScoreArray')) {
    /**
     * 评价转换星级评分
     */
    function GetScoreArray($total_score = 0)
    {
        $Result = 0;
        if (empty($total_score)) return $Result;
        if (in_array($total_score, [1, 2])) {
            $Result = 1;
        } else if (in_array($total_score, [3, 4])) {
            $Result = 3;
        } else if (in_array($total_score, [5])) {
            $Result = 5;
        }
        return $Result;
    }
}

if (!function_exists('getTrueTypeid')) {
    /**
     * 在typeid传值为目录名称的情况下，获取栏目ID
     */
    function getTrueTypeid($typeid = '')
    {
        /*tid为目录名称的情况下*/
        if (!empty($typeid) && strval($typeid) != strval(intval($typeid))) {
            $typeid = \think\Db::name('arctype')->where([
                    'dirname'   => $typeid,
                    'lang'  => get_current_lang(),
                ])->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->getField('id');
        }
        /*--end*/

        return $typeid;
    }
}

if (!function_exists('getTrueAid')) {
    /**
     * 在aid传值为自定义文件名的情况下，获取真实aid
     */
    function getTrueAid($aid = '')
    {
        /*aid为自定义文件名的情况下*/
        if (!empty($aid) && strval($aid) != strval(intval($aid))) {
            $aid = \think\Db::name('archives')->where([
                    'htmlfilename'   => $aid,
                    'lang'  => get_current_lang(),
                ])->cache(true,EYOUCMS_CACHE_TIME,"archives")
                ->getField('aid');
        }
        /*--end*/

        return intval($aid);
    }
}

if (!function_exists('SendNotifyMessage')) 
{
    /**
     * 发送站内信通知
     * 参数说明：
     * $ContentArr  需要存入的通知内容
     * $SendScene   发送来源
     * $UsersID     会员ID
     * $Cause      订单状态，如过期，取消，退款，退货等
     * 返回说明：
     * return 无需返回
     */
    function SendNotifyMessage($GetContentArr = [], $SendScene = 0, $AdminID = 0, $UsersID = 0, $UsersName = null)
    {
        // 存储数据为空则返回结束
        if (empty($GetContentArr) || empty($SendScene)) return false;

        // 查询通知模板信息
        $tpl_where = [
            'lang' => get_home_lang(),
            'send_scene' => $SendScene
        ];
        $Notice = M('users_notice_tpl')->where($tpl_where)->find();

        // 通知模板存在并且开启则执行
        if (!empty($Notice) && !empty($Notice['tpl_title']) && 1 == $Notice['is_open']) {
            if (in_array($SendScene, [1, 5])) {
                $ContentArr = [];
                if (1 == $SendScene) {
                    // 留言表单
                    $ContentArr = $GetContentArr;
                } else if (5 == $SendScene) {
                    // 订单付款
                    $ContentArr = [
                        '订单编号：' . $GetContentArr['order_code'],
                        '订单总额：' . $GetContentArr['order_amount'],
                        '支付方式：' . $GetContentArr['pay_method'],
                        '手机号：' . $GetContentArr['mobile']
                    ];
                }

                $Content = implode('<br/>', $ContentArr);
                $ContentData = [
                    'source'      => $SendScene,
                    'admin_id'    => $AdminID,
                    'users_id'    => $UsersID,
                    'content_title' => $Notice['tpl_title'],
                    'content'     => !empty($Content) ? $Content : '',
                    'is_read'     => 0,
                    'lang'        => get_home_lang(),
                    'add_time'    => getTime(),
                    'update_time' => getTime()
                ];
                M('users_notice_tpl_content')->add($ContentData);
            } else if (6 == $SendScene) {
                // 订单发货
                $ContentArr = [
                    '快递公司：' . $GetContentArr['express_name'],
                    '快递单号：' . $GetContentArr['express_order'],
                    '发货时间：' . date('Y-m-d H:i:s', $GetContentArr['express_time']),
                ];

                $Content = implode('<br/>', $ContentArr);
                $ContentData = [
                    'title'       => $Notice['tpl_title'],
                    'users_id'    => $UsersID,
                    'usernames'   => $UsersName,
                    'remark'      => $Content,
                    'lang'        => get_home_lang(),
                    'add_time'    => getTime(),
                    'update_time' => getTime()
                ];
                M('users_notice')->add($ContentData);
            }
        }
    }
}

if (!function_exists('usershomeurl')) 
{
    /**
     * 个人主页URL
     * @param  [type] $users_id [description]
     * @return [type]           [description]
     */
    function usershomeurl($users_id)
    {
        $usershomeurl = '';
        static $is_users_weapp = null;
        static $users_seo_pseudo = 1;
        if (is_dir('./weapp/Users/') && null === $is_users_weapp) {
            $weappInfo = \think\Db::name('weapp')->field('data,status')->where(['code' => 'Users'])->find();
            if (!empty($weappInfo['status'])) {
                $is_users_weapp = true;
                $weappInfo['data'] = unserialize($weappInfo['data']);
                $users_seo_pseudo = !empty($weappInfo['data']['seo_pseudo']) ? intval($weappInfo['data']['seo_pseudo']) : 1;
            }
        }

        if (true === $is_users_weapp) {
            $usershomeurl = url('plugins/Users/userask', ['id'=>$users_id], true, false, $users_seo_pseudo);
        }

        return $usershomeurl;
    }
}

if (!function_exists('restric_type_logic')) 
{
    /**
     * 付费限制模式与之前三个字段 arc_level_id、 users_pricem、 users_free 组合逻辑兼容
     * @param array $post [description]
     */
    function restric_type_logic(&$post = [])
    {
        if (empty($post['restric_type'])) { // 免费
            $post['arc_level_id'] = 0;
            $post['users_price'] = 0;
            $post['users_free'] = 0;
        } else if (1 == $post['restric_type']) { // 付费
            $post['arc_level_id'] = 0;
            $post['users_free'] = 0;
            if (empty($post['users_price'])) {
                return ['code'=>0, 'msg'=>'购买价格不能为空！'];
            }
        } else if (2 == $post['restric_type']) { // 会员专享
            $post['users_price'] = 0;
            $post['users_free'] = 1;
        } else if (3 == $post['restric_type']) { // 会员付费
            $post['users_free'] = 0;
            if (empty($post['users_price'])) {
                return ['code'=>0, 'msg'=>'购买价格不能为空！'];
            }
        }

        return true;
    }
}

if (!function_exists('download_restric_type_logic')) {
    /**
     * 下载模型 付费限制模式与之前三个字段 level_value、 users_price 组合逻辑兼容
     * @param array $post [description]
     */
    function download_restric_type_logic(&$post = [])
    {
        if (empty($post['restric_type'])) { // 免费
            $post['level_value'] = 0;
            $post['users_price'] = 0;
        } else if (1 == $post['restric_type']) { // 付费
            $post['level_value'] = 0;
            $post['users_free'] = 0;
            if (empty($post['users_price'])) {
                return ['code' => 0, 'msg' => '购买价格不能为空！'];
            }
        } else if (2 == $post['restric_type']) { // 会员专享(免费)
            $post['users_price'] = 0;
        }

        return true;
    }
}

if (!function_exists('clear_session_file')) 
{
    /**
     * 清理过期的data/session文件
     * @param array $post [description]
     */
    function clear_session_file()
    {
        $path = \think\Config::get('session.path');
        if (!empty($path) && file_exists($path)) {
            if ('data/session' != $path && is_dir('data/session')) {
                delFile('./data/session', true);
            }

            $time = getTime();
            $web_login_expiretime = tpCache('web.web_login_expiretime');
            empty($web_login_expiretime) && $web_login_expiretime = config('login_expire');
            $files = glob($path.'/sess_*');
            foreach ($files as $key => $file) {
                clearstatcache(); // 清除文件状态缓存
                $filemtime = filemtime($file);
                if (false === $filemtime) {
                    $filemtime = $time;
                }
                $filesize = filesize($file);
                if (false === $filesize) {
                    $filesize = 1;
                }
                if (empty($filesize) || (($time - $filemtime) > ($web_login_expiretime + 300))) {
                    $referurl = '';
                    if (isset($_SERVER['HTTP_REFERER'])) {
                        $referurl = $_SERVER['HTTP_REFERER'];
                    }
                    @unlink($file);
                }
            }
        }
    }
}

if (!function_exists('func_thumb_img')) 
{
    /**
     * 压缩图 - 从原始图来处理出来
     * @param type $original_img  图片路径
     * @param type $width     生成缩略图的宽度
     * @param type $height    生成缩略图的高度
     * @param type $quality   压缩系数
     */
    function func_thumb_img($original_img = '', $width = '', $height = '', $quality = 75)
    {
        // 缩略图配置
        static $thumbextra = null;
        static $thumbConfig = null;
        if (null === $thumbextra) {
            @ini_set('memory_limit', '-1'); // 内存不限制，防止图片大小过大，导致缩略图处理失败，网站打不开
            $thumbConfig = tpCache('thumb');
            $thumbextra = config('global.thumb');
            empty($thumbConfig['thumb_width']) && $thumbConfig['thumb_width'] = $thumbextra['width'];
            empty($thumbConfig['thumb_height']) && $thumbConfig['thumb_height'] = $thumbextra['height'];
        }

        $c_width = !empty($width) ? intval($width) : intval($thumbConfig['thumb_width']);
        $c_height = !empty($height) ? intval($height) : intval($thumbConfig['thumb_height']);
        if ((empty($c_width) && empty($c_height)) || stristr($original_img,'.gif')) {
            return $original_img;
        }

        $original_img_new = handle_subdir_pic($original_img, 'img', false, true);
        $original_img_new = trim($original_img_new, '/');

        //获取图像信息
        $info = @getimagesize('./'.$original_img_new);
        $img_width = !empty($info[0]) ? intval($info[0]) : 0;
        $img_height = !empty($info[1]) ? intval($info[1]) : 0;

        // 过滤实际图片大小比设置最大宽高小的，直接忽视
        if (!empty($img_width) && !empty($img_height) && $img_width <= $c_width && $img_height <= $c_height) {
            return $original_img;
        }

        //检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            return $original_img;
        } else {
            if (!empty($info['mime']) && stristr($info['mime'], 'bmp') && version_compare(PHP_VERSION,'7.2.0','<')) {
                return $original_img;
            }
        }

        try {
            vendor('topthink.think-image.src.Image');
            vendor('topthink.think-image.src.image.Exception');
            $image = \think\Image::open('./'.$original_img_new);
            $image->thumb($c_width, $c_height, 1)->save($original_img_new, NULL, $quality); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        } catch (think\Exception $e) {}

        return $original_img;
    }
}

if (!function_exists('pc_to_mobile_url')) 
{
    /**
     * 生成静态模式下且PC和移动端模板分离，自动获取移动端URL
     * @access public
     */
    function pc_to_mobile_url($pageurl = '', $tid = '', $aid = '')
    {
        $url = '';
        $webData = tpCache('web');
        if (file_exists('./template/'.TPL_THEME.'mobile')) { // 分离式模板

            $domain = request()->host(true);

            /*是否开启手机站域名，并且配置*/
            if (!empty($webData['web_mobile_domain_open']) && !empty($webData['web_mobile_domain'])) {
                $domain = $webData['web_mobile_domain'] . '.' . request()->rootDomain();
            }
            /*end*/

            if (!empty($aid)) { // 内容页
                $url = url('home/View/index', ['aid' => $aid], true, $domain, 1, 1, 0);
            } else if (!empty($tid)) { // 列表页
                $url = url('home/Lists/index', ['tid' => $tid], true, $domain, 1, 1, 0);
            } else { // 首页
                $url = request()->scheme().'://'. $domain . ROOT_DIR . '/index.php';
            }
        } else { // 响应式模板
            // 开启手机站域名，且配置
            if (!empty($webData['web_mobile_domain_open']) && !empty($webData['web_mobile_domain'])) {
                if (empty($pageurl)) {
                    $url = request()->subDomain($webData['web_mobile_domain']) . ROOT_DIR . '/index.php';
                } else {
                    $url = !preg_match('/^(http(s?):)?\/\/(.*)$/i', $pageurl) ? request()->domain() . $pageurl : $pageurl;
                    $url = preg_replace('/^(.*)(\/\/)([^\/]*)(\.?)(' . request()->rootDomain() . ')(.*)$/i', '${1}${2}' . $webData['web_mobile_domain'] . '.${5}${6}', $url);
                }
            }
        }

        return $url;
    }
}

if (!function_exists('GetSortData')) 
{
    /**
     * list/arclist标签的排序处理
     * @param string $orderby [description]
     * @param array  $Param   [description]
     */
    function GetSortData($orderby = '', $Param = [])
    {
        if (empty($Param)) {
            $Param = request()->param();
        }

        $sort_asc = !empty($Param['sort_asc']) ? $Param['sort_asc'] : 'desc';
        
        if (!empty($Param['sort']) && 'sales' == $Param['sort']) {
            $orderby = 'a.sales_num ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'price' == $Param['sort']) {
            $orderby = 'a.users_price ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'appraise' == $Param['sort']) {
            $orderby = 'a.appraise ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'new' == $Param['sort']) {
            $orderby = 'a.add_time ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'collection' == $Param['sort']) {
            $orderby = 'a.collection ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'click' == $Param['sort']) {
            $orderby = 'a.click ' . $sort_asc . ', ' . $orderby;
        } else if (!empty($Param['sort']) && 'download' == $Param['sort']) {
            $orderby = 'a.downcount ' . $sort_asc . ', ' . $orderby;
        }
        return $orderby;
    }
}

if (!function_exists('set_tagseotitle')) 
{
    /**
     * 设置Tag标题
     */
    function set_tagseotitle($tag = '', $seo_title = '', $site_info = [])
    {
        $page = I('param.page/d', 1);
        static $lang = null;
        $lang === null && $lang = get_home_lang();
        static $seoConfig = null;
        null === $seoConfig && $seoConfig = tpCache('seo');
        $seo_title_symbol = isset($seoConfig['seo_title_symbol']) ? htmlspecialchars_decode($seoConfig['seo_title_symbol']) : '_';
        if (empty($seo_title)) { // 针对没有自定义SEO标题的Tag
            $web_name = tpCache('web.web_name');
            if ($page > 1) {
                if (in_array($lang, ['cn'])) {
                    $tag .= "{$seo_title_symbol}第{$page}页";
                } else {
                    $tag .= "{$seo_title_symbol}{$page}";
                }
            }
            $seo_title = $tag.'_'.$web_name;
        } else {
            if ($page > 1) {
                if (in_array($lang, ['cn'])) {
                    $seo_title .= "{$seo_title_symbol}第{$page}页";
                } else {
                    $seo_title .= "{$seo_title_symbol}{$page}";
                }
            }
        }

        // 城市分站的seo
        empty($site_info) && $site_info = cookie('site_info');
        $seo_title = site_seo_handle($seo_title, $site_info);

        return $seo_title;
    }
}
if (!function_exists('spellLabel'))
{
    /**
     * 会员价格拼标签
     */
    function spellLabel( $value = '' )
    {
        $value = '<span id="users_price_1640658971">'.$value.'</span>';
        return $value;
    }
}

if (!function_exists('get_discount_price'))
{
    /**
     * 获取会员折扣价格
     */
    function get_discount_price( $discount = 100 , $price = 0 )
    {
        if (0 < $price){
            // static $discount = null;
            // if (null === $discount) {
            //     $discount = \think\Db::name('users_level')->where(['level_id'=>$level_id])->value('discount');
            // }
            if (!empty($discount)) {
                $price = round($price * ($discount/100),2);
            } else {
                $price = 0;
            }
        }
        return $price;
    }
}

if (!function_exists('site_seo_handle'))
{
    /**
     * 转换多站点的区域seo标题、关键字、描述
     */
    function site_seo_handle($value = '', $site_info = [])
    {
        if (!empty($value)) {
            if (!config('city_switch_on')) {
                $value = str_ireplace(['{region}','{regionAll}','{parent}','{top}'], '', $value);
            } else {
                if (empty($site_info)) {
                    $site_info = [
                        'name'  => '',
                        'topid' => 0,
                        'parent_id' => 0,
                        'level' => 0,
                    ];
                }

                if (stristr($value, "{region}")) {
                    $name = !empty($site_info['name']) ? $site_info['name'] : '';
                    $value = str_ireplace('{region}', $name, $value);
                }
                if (stristr($value, "{regionAll}") || stristr($value, "{parent}") || stristr($value, "{top}")) {
                    static $citysiteList = null;
                    if (null === $citysiteList) {
                        $citysiteList = get_citysite_list();
                    }

                    $topName = !empty($citysiteList[$site_info['topid']]) ? $citysiteList[$site_info['topid']]['name'] : '';
                    $parentName = !empty($citysiteList[$site_info['parent_id']]) ? $citysiteList[$site_info['parent_id']]['name'] : '';
                    if (1 == $site_info['level']) {
                        $topName = $parentName = '';
                    } else if (2 == $site_info['level']) {
                        $topName = '';
                    } else {
                        $topName = !empty($citysiteList[$site_info['topid']]) ? $citysiteList[$site_info['topid']]['name'] : '';
                        $parentName = !empty($citysiteList[$site_info['parent_id']]) ? $citysiteList[$site_info['parent_id']]['name'] : '';
                    }
                    $regionAll = $topName.$parentName.$site_info['name'];
                    $value = str_ireplace(['{parent}','{top}','{regionAll}'], [$parentName,$topName,$regionAll], $value);
                }
            }
        }
        return $value;
    }
}

if (!function_exists('adminLoginAfter')) {
    /**
     * 管理员登录成功后的后置业务逻辑
     * @return [type] [description]
     */
    function adminLoginAfter($admin_id = 0, $session_id = '', $third_type = '')
    {
        if (!empty($admin_id)) {
            $admin_info = \think\Db::name('admin')->where(['admin_id'=>$admin_id])->find();
            $role_id = !empty($admin_info['role_id']) ? $admin_info['role_id'] : -1;
            $auth_role_info = array();
            $is_founder = 0;
            if (!empty($admin_info['parent_id'])) {
                $role_name = '超级管理员';
            } else {
                $is_founder = 1;
                $role_name = '创始人';
            }
            $admin_info['is_founder'] = $is_founder;

            if (0 < intval($role_id)) {
                $auth_role_info = \think\Db::name('auth_role')
                    ->field("a.*, a.name AS role_name")
                    ->alias('a')
                    ->where('a.id','eq', $role_id)
                    ->find();
                if (!empty($auth_role_info)) {
                    $auth_role_info['language'] = unserialize($auth_role_info['language']);
                    $auth_role_info['cud'] = unserialize($auth_role_info['cud']);
                    $auth_role_info['permission'] = unserialize($auth_role_info['permission']);
                    $role_name = $auth_role_info['name'];
                }
            }
            $admin_info['auth_role_info'] = $auth_role_info;
            $admin_info['role_name'] = $role_name;

            $last_login_time = getTime();
            $last_login_ip = clientIP();
            $login_cnt = $admin_info['login_cnt'] + 1;
            \think\Db::name('admin')->where(['admin_id'=>$admin_info['admin_id']])->save(array('last_login'=>$last_login_time, 'last_ip'=>$last_login_ip, 'login_cnt'=>$login_cnt, 'session_id'=>$session_id));
            $admin_info['last_login'] = $last_login_time;
            $admin_info['last_ip'] = $last_login_ip;

            // 头像
            empty($admin_info['head_pic']) && $admin_info['head_pic'] = get_head_pic($admin_info['head_pic'], true);

            // 多语言
            $langRow = \think\Db::name('language')->order('id asc')
                ->cache(true, EYOUCMS_CACHE_TIME, 'language')
                ->select();

            // 重置登录错误次数
            $login_errnum_key = 'adminlogin_'.md5('login_errnum_'.$admin_info['user_name']);
            $login_errtime_key = 'adminlogin_'.md5('login_errtime_'.$admin_info['user_name']);
            $login_lock_key = 'adminlogin_'.md5('login_lock_'.$admin_info['user_name']); // 是否被锁定
            foreach ($langRow as $key => $val) {
                tpSetting('adminlogin', [$login_errnum_key=>0, $login_errtime_key=>0, $login_lock_key=>0], $val['mark']);
            }

            // 二次安全验证 - 每次登录后，如果没设置同IP不验证，则清除答案验证成功的IP记录
            $security = tpSetting('security');
            if (isset($security['security_ask_ip_open']) && empty($security['security_ask_ip_open'])) {
                foreach ($langRow as $key => $val) {
                    tpSetting('security', ['security_answerverify_ip'=>''], $val['mark']);
                }
            }

            // 第三方扫码登录
            if (in_array($third_type, ['WechatLogin','EyouGzhLogin'])) {
                $map = ['admin_id'=>$admin_id];
                if ('EyouGzhLogin' == $third_type) {
                    $map['type'] = 1;
                } else if ('WechatLogin' == $third_type) {
                    $map['type'] = 2;
                }
                $admin_info['openid'] = \think\Db::name('admin_wxlogin')->where($map)->value('openid');
            }

            $admin_info_new = $admin_info;
            /*过滤存储在session文件的敏感信息*/
            foreach (['user_name','true_name','password'] as $key => $val) {
                unset($admin_info_new[$val]);
            }
            /*--end*/

            // 保存后台session
            session('admin_id', $admin_info['admin_id']);
            session('admin_info', $admin_info_new);
            session('admin_login_expire', getTime()); // 登录有效期
            return $admin_info_new;
        }
        else {
            session('admin_id', null);
            session('admin_info', null);
            session('admin_login_expire', null);
            return false;
        }
    }
}

if (!function_exists('get_form_read_value')){
    /**
     * 表单数据类型转换
     * $field_value     字段值
     * $field_type      字段类型
     *  $domain          图片文件是否完整链接
     */
    function get_form_read_value($field_value,$field_type,$domain = false){
        static $region_arr = null;
        if (null === $region_arr) {
            $region_arr = get_region_list();
        }

        if ('checkbox' == $field_type && !empty($field_value)) {
            $field_value = str_replace(',', '] [', '['.$field_value.']');
        }else if('region' == $field_type && !empty($field_value)){
            if (is_string($field_value)) {
                $field_value_arr = explode(',', $field_value);
            } else {
                $field_value_arr = $field_value;
            }

            $attr_value = [];
            foreach ($field_value_arr as $key => $val) {
                $attr_value[] = !empty($region_arr[$val]['name']) ? $region_arr[$val]['name'] : '';
            }
            $field_value = implode('',$attr_value);
        }elseif (('file' == $field_type || 'img' == $field_type) && !empty($field_value)){
            static $file_type = null;
            null === $file_type && $file_type = tpCache('basic.file_type');
            if(preg_match('/(\.(jpg|gif|png|bmp|jpeg|ico|webp))$/i', $field_value)){
                if (!stristr($field_value, '|')) {
                    $field_value = handle_subdir_pic($field_value,'img',$domain);
                    $field_value  = "<a href='{$field_value}' target='_blank'><img src='{$field_value}' width='60' height='60' style='float: unset;cursor: pointer;' /></a>";
                }
            }else if(preg_match('/(\.('.$file_type.'))$/i', $field_value)){
                static $current_domain = null;
                null === $current_domain && $current_domain = request()->domain();
                if (!stristr($field_value, '|')) {
                    $field_value = handle_subdir_pic($field_value,'img',$domain);
                    $domain_new = "";
                    if ($domain){
                        $domain_new = $current_domain;
                    }
                    $field_value  = "<a href='{$field_value}' download='".time()."'><img src=\"{$domain_new}".ROOT_DIR."/public/static/common/images/file.png\" alt=\"\" style=\"width: 16px;height:  16px;\">点击下载</a>";
                }
            }
        }

        return $field_value;
    }
}
if (!function_exists('del_all_dir')){
    //完整删除目录，以及目录下面所有文件(相比于rmdir不会产生错误报告)
    function del_all_dir($path){
        $path = rtrim($path, '/').'/';
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            //如果 $p 中有两个以上的元素则说明当前 $path 不为空
            if(count($p)>2){
                foreach($p as $val){
                    //排除目录中的.和..
                    if($val !="." && $val !=".."){
                        //如果是目录则递归子目录，继续操作
                        if(is_dir($path.$val)){
                            //子目录中操作删除文件夹和文件
                            del_all_dir($path.$val.'/');
                        }else{
                            //如果是文件直接删除
                            unlink($path.$val);
                        }
                    }
                }
            }
        }
        //删除目录
        return @rmdir($path);
    }
}
if (!function_exists('get_image_type')){
    //获取图片的类型
    function get_image_type($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }
        try {
            $info = getimagesize($image);
            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

/**
 * ------------- 此行代码请保持最底部 --------------
 */
if (!function_exists('function_1601946443')) 
{
    /**
     * 引入插件公共函数
     */
    function function_1601946443()
    {
        $file_1601946443 = glob('weapp/*/function.php');
        foreach ($file_1601946443 as $key => $val) {
            include_once ROOT_PATH.$val;
        }
    }
}
// function_1601946443();