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

namespace app\admin\controller;
use app\admin\logic\UpgradeLogic;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
class Base extends Controller {

    public $session_id;
    public $php_servicemeal = 0;
    public $globalConfig = [];
    public $usersConfig = [];

    /**
     * 析构函数
     */
    function __construct() 
    {
        if (!session_id()) {
            Session::start();
        }
        header("Cache-control: private");  // history.back返回后输入框值丢失问题
        parent::__construct();

        $this->editor = tpSetting('editor');
        if (empty($this->editor['editor_select'])) $this->editor['editor_select'] = 1;
        $this->assign('editor', $this->editor);

    }
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        $this->session_id = session_id(); // 当前的 session_id
        !defined('SESSION_ID') && define('SESSION_ID', $this->session_id); //将当前的session_id保存为常量，供其它方法调用

        parent::_initialize();
        $this->global_assign();

       /*及时更新cookie中的admin_id，用于前台的可视化权限验证*/
       // $auth_role_info = model('AuthRole')->getRole(array('id' => session('admin_info.role_id')));
       // session('admin_info.auth_role_info', $auth_role_info);
       /*--end*/

        //过滤不需要登陆的行为
        $ctl_act = CONTROLLER_NAME.'@'.ACTION_NAME;
        $ctl_all = CONTROLLER_NAME.'@*';
        $filter_login_action = config('filter_login_action');
        $filter_login_action = empty($filter_login_action) ? [] : $filter_login_action;
        if (in_array($ctl_act, $filter_login_action) || in_array($ctl_all, $filter_login_action) || !in_array(MODULE_NAME, ['admin'])) {
            //return;
        }else{
            $web_login_expiretime = tpCache('global.web_login_expiretime');
            empty($web_login_expiretime) && $web_login_expiretime = config('login_expire');
            $admin_login_expire = session('admin_login_expire'); //最后登录时间
            $admin_info = session('admin_info');
            $isLogin = false; // 未登录
            if (!empty($admin_info['admin_id']) && (getTime() - intval($admin_login_expire)) < $web_login_expiretime) {
                $isLogin = $this->checkWechatLogin($admin_info); // 校验微信扫码登录
                if (!IS_AJAX_POST) {
                    session('admin_login_expire', getTime()); // 登录有效期
                }
                $this->check_priv();//检查管理员菜单操作权限
            }

            if (!$isLogin) {
                /*自动退出*/
                adminLog('访问后台');
                session_unset();
                session::clear();
                cookie('admin-treeClicked', null); // 清除并恢复栏目列表的展开方式
                cookie('admin-treeClicked-1649642233', null); // 清除并恢复内容管理的展开方式
                /*--end*/
                if (IS_AJAX) {
                    $this->error('登录超时！');
                } else {
                    $url = request()->baseFile().'?s=Admin/login';
                    $this->redirect($url);
                    exit;
                }
            }
        }

        /* 增、改的跳转提示页，只限制于发布文档的模型和自定义模型 */
        $channeltype_list = config('global.channeltype_list');
        $controller_name = $this->request->controller();
        $this->assign('controller_name', $controller_name);
        if (isset($channeltype_list[strtolower($controller_name)]) || 'Custom' == $controller_name) {
            if (in_array($this->request->action(), ['add','edit'])) {
                \think\Config::set('dispatch_success_tmpl', 'public/dispatch_jump');
                $id = input('param.id/d', input('param.aid/d'));
                ('GET' == $this->request->method()) && cookie('ENV_IS_UPHTML', 0);
            } else if (in_array($this->request->action(), ['index'])) {
                cookie('ENV_GOBACK_URL', $this->request->url());
                cookie('ENV_LIST_URL', request()->baseFile()."?m=admin&c={$controller_name}&a=index&lang=".$this->admin_lang);
            }
        } else if ('Archives' == $controller_name && in_array($this->request->action(), ['index_archives','index_draft'])) {
            cookie('ENV_GOBACK_URL', $this->request->url());
            cookie('ENV_LIST_URL', request()->baseFile()."?m=admin&c=Archives&a=".$this->request->action()."&lang=".$this->admin_lang);
        }

        if (empty($this->globalConfig['seo_uphtml_after_home']) && empty($this->globalConfig['seo_uphtml_after_channel']) && empty($this->globalConfig['seo_uphtml_after_pernext'])) {
            cookie('ENV_UPHTML_AFTER', '');
        } else {
            $seo_uphtml_after['seo_uphtml_after_home'] = !empty($this->globalConfig['seo_uphtml_after_home']) ? $this->globalConfig['seo_uphtml_after_home'] : 0;
            $seo_uphtml_after['seo_uphtml_after_channel'] = !empty($this->globalConfig['seo_uphtml_after_channel']) ? $this->globalConfig['seo_uphtml_after_channel'] : 0;
            $seo_uphtml_after['seo_uphtml_after_pernext'] = !empty($this->globalConfig['seo_uphtml_after_pernext']) ? $this->globalConfig['seo_uphtml_after_pernext'] : 0;
            cookie('ENV_UPHTML_AFTER', json_encode($seo_uphtml_after));
        }
        /* end */
    }

    /**
     * 校验微信扫码登录
     * @param  array  $admin_info [description]
     * @return [type]             [description]
     */
    private function checkWechatLogin($admin_info = [])
    {
        $isLogin = true;

        $login_type = 1; //仅账号密码登录  2-账号密码登录&微信扫码登录 3-仅微信扫码登录
        $thirdata = login_third_type();
        $third_login = !empty($thirdata['type']) ? $thirdata['type'] : '';
        $wx_map = ['admin_id'=>$admin_info['admin_id']];
        if ('EyouGzhLogin' == $third_login) {
            $wx_map['type'] = 1;
            if (empty($thirdata['data']['force'])){
                $login_type = 2; //2-账号密码登录&微信扫码登录
            } else {
                $login_type = 3; //仅微信扫码登录
            }
        } else if ('WechatLogin' == $third_login) {
            $wx_map['type'] = 2;
            if (empty($thirdata['data']['security_wechat_forcelogin'])) {
                $login_type = 2; //2-账号密码登录&微信扫码登录
            } else {
                $login_type = 3; //仅微信扫码登录
            }
        }

        if (!empty($third_login)) {
            if (3 == $login_type || (!empty($admin_info['openid']) && 2 == $login_type)) {
                $cacheKey = "admin_checkWechatLogin_".json_encode($wx_map);
                $wx_info = cache($cacheKey);
                if (empty($wx_info)) {
                    $wx_info = Db::name('admin_wxlogin')->where($wx_map)->find();
                    cache($cacheKey, $wx_info, null, "admin_wxlogin");
                }
                if (empty($admin_info['openid']) || empty($wx_info['openid']) || $admin_info['openid'] != $wx_info['openid']) {
                    $isLogin = false;
                    adminLog('重新登录验证');
                    session_unset();
                    session::clear();
                    cookie('admin-treeClicked', null); // 清除并恢复栏目列表的展开方式
                    cookie('admin-treeClicked-1649642233', null); // 清除并恢复内容管理的展开方式
                    $url = request()->baseFile().'?s=Admin/login';
                    $this->error('重新登录验证', $url);
                }
            }
        }

        return $isLogin;
    }
    
    /**
     * 检查管理员菜单操作权限
     * @return [type] [description]
     */
    private function check_priv()
    {
        $ctl = CONTROLLER_NAME;
        $act = ACTION_NAME;
        $ctl_act = $ctl.'@'.$act;
        $ctl_all = $ctl.'@*';
        //无需验证的操作
        $uneed_check_action = config('uneed_check_action');
        if (0 >= intval(session('admin_info.role_id'))) {
            //超级管理员无需验证
            return true;
        } else {
            $bool = false;

            /*检测是否有该权限*/
            if (is_check_access($ctl_act)) {
                $bool = true;
            }
            /*--end*/

            /*在列表中的操作不需要验证权限*/
            if (IS_AJAX || strpos($act,'ajax') !== false || in_array($ctl_act, $uneed_check_action) || in_array($ctl_all, $uneed_check_action)) {
                $bool = true;
            }
            /*--end*/

            //检查是否拥有此操作权限
            if (!$bool) {
                $this->error('您没有操作权限，请联系超级管理员分配权限');
            }
        }
    }

    /**
     * 保存系统设置 
     */
    public function global_assign()
    {
        /*随时更新每页记录数*/
        $pagesize = input('get.pagesize/d');
        if (!empty($pagesize)) {
            $system_paginate_pagesize = config('tpcache.system_paginate_pagesize');
            if ($pagesize != intval($system_paginate_pagesize)) {
                tpCache('system', ['system_paginate_pagesize'=>$pagesize]);
            }
        }
        /*end*/

        $this->globalConfig = tpCache('global');
        $this->php_servicemeal = $this->globalConfig['php_servicemeal'];
        $this->assign('global', $this->globalConfig);

        if (!empty($this->globalConfig['web_users_switch'])) {
            $this->usersConfig = getUsersConfigData('all');
        }
        $this->assign('usersConfig', $this->usersConfig);
    } 
    
    /**
     * 多语言功能操作权限
     */
    public function language_access()
    {
        if (is_language() && $this->main_lang != $this->admin_lang) {
            $lang_title = model('Language')->where('mark',$this->main_lang)->value('title');
            $this->error('当前语言没有此功能，请切换到【'.$lang_title.'】语言');
        }
    }
}