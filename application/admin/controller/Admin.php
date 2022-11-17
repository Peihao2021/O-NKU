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

use think\Page;
use think\Verify;
use think\Db;
use think\db\Query;
use think\Session;
use app\admin\model\AuthRole;
use app\admin\logic\AjaxLogic;

class Admin extends Base {

    private $admin_info = [];

    public function _initialize() {
        parent::_initialize();
        $this->admin_info = session('?admin_info') ? session('admin_info') : [];
        $this->assign('admin_info', $this->admin_info);
    }

    public function index()
    {
        $list = array();
        $keywords = input('keywords/s');
        $keywords = addslashes(trim($keywords));

        $condition = array();
        if (!empty($keywords)) {
            $condition['a.user_name|a.true_name'] = array('LIKE', "%{$keywords}%");
        }

        /*权限控制 by 小虎哥*/
        if (0 < intval($this->admin_info['role_id'])) {
            $condition['a.admin_id|a.parent_id'] = $this->admin_info['admin_id'];
        } else {
            if (!empty($this->admin_info['parent_id'])) {
                $condition['a.admin_id|a.parent_id'] = $this->admin_info['admin_id'];
            }
        }
        /*--end*/

        /**
         * 数据查询
         */
        $count = DB::name('admin')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $Page = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('admin')->field('a.*, b.name AS role_name')
            ->alias('a')
            ->join('__AUTH_ROLE__ b', 'a.role_id = b.id', 'LEFT')
            ->where($condition)
            ->order('a.admin_id asc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        $locklist = tpSetting('adminlogin');
        foreach ($list as $key => $val) {
            if (0 >= intval($val['role_id'])) {
                $val['role_name'] = !empty($val['parent_id']) ? '超级管理员' : '创始人';
            }
            // 是否被锁定
            $login_lock_key = 'adminlogin_'.md5('login_lock_'.$val['user_name']); // 是否被锁定
            $val['is_locklogin'] = !empty($locklist[$login_lock_key]) ? 1 : 0;

            $list[$key] = $val;
        }
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$Page);// 赋值分页集

        // 第三方扫码绑定与解绑
        $wxlist = [];
        $thirdata = login_third_type();
        if ('EyouGzhLogin' == $thirdata['type']) {
            $wxlist = Db::name('admin_wxlogin')->where(['type'=>1])->getAllWithIndex('admin_id');
        } else if ('WechatLogin' == $thirdata['type']) {
            $wxlist = Db::name('admin_wxlogin')->where(['type'=>2])->getAllWithIndex('admin_id');
        }
        $this->assign('wxlist', $wxlist);
        $this->assign('thirdata', $thirdata);

        /*第一次同步CMS用户的栏目ID到权限组里*/
        $this->syn_built_auth_role();
        /*--end*/
        return $this->fetch();
    }

    /*
     * 管理员登陆
     */
    public function login()
    {
        if (session('?admin_id') && session('admin_id') > 0) {
            $web_adminbasefile = tpCache('global.web_adminbasefile');
            $web_adminbasefile = !empty($web_adminbasefile) ? $web_adminbasefile : $this->root_dir.'/login.php';
            $this->success("您已登录", $web_adminbasefile);
        }
      
        $is_vertify = 1; // 默认开启验证码
        $admin_login_captcha = config('captcha.admin_login');
        if (!function_exists('imagettftext') || empty($admin_login_captcha['is_on'])) {
            $is_vertify = 0; // 函数不存在，不符合开启的条件
        } else if (is_file('./data/conf/admin_vertify.txt')) {
            $is_exist = @file_get_contents('./data/conf/admin_vertify.txt');
            if ($is_exist !== false && empty($is_exist)) {
                $is_vertify = 0;
            }
        }
        $this->assign('is_vertify', $is_vertify);

        /*----------------微信扫码登录 start---------------*/
        $login_type = 1; //仅账号密码登录  2-账号密码登录&微信扫码登录 3-仅微信扫码登录
        $thirdata = login_third_type();
        $third_login = !empty($thirdata['type']) ? $thirdata['type'] : '';
        if ('EyouGzhLogin' == $third_login) {
            if (empty($thirdata['data']['force'])){
                $login_type = 2; //2-账号密码登录&微信扫码登录
            } else {
                $login_type = 3; //仅微信扫码登录
            }
        } else if ('WechatLogin' == $third_login) {
            if (empty($thirdata['data']['security_wechat_forcelogin'])) {
                $login_type = 2; //2-账号密码登录&微信扫码登录
            } else {
                $login_type = 3; //仅微信扫码登录
            }
        }
        $this->assign('login_type', $login_type);
        $this->assign('third_login', $third_login);
        /*----------------微信扫码登录 end---------------*/

        if (IS_POST) {

            if (!in_array($login_type, [1,2])) {
                $this->error('强制扫码，不支持普通登录！');
            }

            $post = input('post.');

            if (!function_exists('session_start')) {
                $this->error('请联系空间商，开启php的session扩展！');
            }
            if (!testWriteAble(ROOT_PATH.config('session.path').'/')) {
                $this->error('请仔细检查以下问题：<br/>1、磁盘空间大小是否100%；<br/>2、站点目录权限是否为755；<br/>3、站点所有目录的权限，禁止用root:root ；<br/>4、如还没解决，请点击：<a href="http://www.eyoucms.com/wenda/6958.html" target="_blank">查看教程</a>');
            }
            
            if (1 == $is_vertify) {
                $verify = new Verify();
                if (!$verify->check(input('post.vertify'), "admin_login")) {
                    $this->error('验证码错误');
                }
            }

            $is_clicap = 0; // 默认关闭文字验证码
            if (is_dir('./weapp/Clicap/')) {
                $ClicapRow = model('Weapp')->getWeappList('Clicap');
                if (!empty($ClicapRow['status']) && 1 == $ClicapRow['status']) {
                    if (!empty($ClicapRow['data']) && $ClicapRow['data']['captcha']['admin_login']['is_on'] == 1) {
                        $clicaptcha_info = input('post.clicaptcha-submit-info');
                        $clicaptcha = new \weapp\Clicap\vendor\Clicaptcha;
                        if (empty($clicaptcha_info) || !$clicaptcha->check($clicaptcha_info, false)) {
                            $this->error('文字点击验证错误！');
                        }
                    }
                }
            }

            $admin_count = 0;
            $user_name = input('post.user_name/s');
            $password = input('post.password/s');

            /*---------登录错误次数的限制 start----------*/
            $globalConfing = tpCache('global');
            $web_login_lockopen = 0; // 是否开启登录失败锁定
            if (!isset($globalConfing['web_login_lockopen']) || !empty($globalConfing['web_login_lockopen'])) {
                $web_login_lockopen = 1;
                $admin_count = Db::name('admin')->where(['user_name'=>$user_name])->count();
                if (!empty($admin_count)) {
                    $loginErrtotal = !empty($globalConfing['web_login_errtotal']) ? intval($globalConfing['web_login_errtotal']) : config('login_errtotal'); // 登录错误最大次数
                    $loginErrexpire = !empty($globalConfing['web_login_errexpire']) ? intval($globalConfing['web_login_errexpire']) : config('login_errexpire'); // 登录错误最大限制时间
                    $login_errnum_key = 'adminlogin_'.md5('login_errnum_'.$user_name);
                    $login_errtime_key = 'adminlogin_'.md5('login_errtime_'.$user_name);
                    $login_lock_key = 'adminlogin_'.md5('login_lock_'.$user_name); // 是否被锁定
                    $loginErrnum = (int)tpSetting('adminlogin.'.$login_errnum_key); // 登录错误次数
                    $loginErrtime = tpSetting('adminlogin.'.$login_errtime_key); // 最后一次登录错误时间
                    if ($loginErrnum >= $loginErrtotal) {
                        if (getTime() < $loginErrtime + $loginErrexpire) {
                            adminLog("登录失败(已被锁定，登录错误超限{$loginErrtotal}次)");
                            $surplus_time = ($loginErrtime + $loginErrexpire) - getTime();
                            if ($surplus_time <= 0) {
                                $surplus_time = 1;
                            }
                            $this->error("多次登录失败，距离解锁还有".ceil($surplus_time/60)."分钟！");
                        } else {
                            // 重置登录错误次数
                            $loginErrnum = $loginErrtime = $login_lock = 0;
                            tpSetting('adminlogin', [$login_errnum_key => $loginErrnum]);
                            tpSetting('adminlogin', [$login_errtime_key => $loginErrtime]);
                            tpSetting('adminlogin', [$login_lock_key => $login_lock]);
                        }
                    }
                }
            }
            /*---------登录错误次数的限制 end----------*/

            if (!empty($user_name) && !empty($password)) {
                $condition['user_name'] = $user_name;
                $admin_info = Db::name('admin')->where($condition)->find();
                if (!empty($admin_info)) {
                    $entry = pwd_encry_type($admin_info['password']);
                    $encry_password = func_encrypt($password, true, $entry);
                    if ($admin_info['password'] == $encry_password) {
                        if ($admin_info['status'] == 0) {
                            adminLog('登录失败(用户名被禁用)');
                            $this->error('用户名被禁用！');
                        }
                        $admin_info = adminLoginAfter($admin_info['admin_id'], $this->session_id);
                        // 检查密码复杂度
                        session('admin_login_pwdlevel', checkPasswordLevel($password));

                        adminLog('后台登录');
                        $url = session('from_url') ? session('from_url') : $this->request->baseFile();
                        session('isset_author', null); // 内置勿动

                        // 同步追加一个后台管理员到会员用户表
                        $isFounder = !empty($admin_info['parent_id']) ? 0 : 1;
                        $this->syn_users_login($admin_info, $isFounder);
                        $this->success('登录成功', $url);
                    }
                }
            }

            /*----------记录登录错误次数 start-----------*/
            if (!empty($admin_count) && !empty($web_login_lockopen)) {
                $login_errnum = $loginErrnum + 1;
                $login_num = $loginErrtotal - $login_errnum;
                tpSetting('adminlogin', [$login_errnum_key=>$login_errnum]);
                tpSetting('adminlogin', [$login_errtime_key=>getTime()]);
                if ($login_num > 0) {
                    $this->error("用户名或密码错误，您还可以尝试[{$login_num}]次！");
                } else {
                    tpSetting('adminlogin', [$login_lock_key => 1]);
                    $this->error("登录错误超限{$loginErrtotal}次，账号将被锁定".ceil($loginErrexpire/60)."分钟！");
                }
            }
            /*----------记录登录错误次数 end-----------*/

            adminLog("登录失败({$user_name})");
            $this->error("用户名或密码错误！");
        }

        $ajaxLogic = new AjaxLogic;
        $ajaxLogic->login_handle();
        
        // 仅微信扫码登录
        // if ('WechatLogin' == $third_login && 3 == $login_type) {
        //     $this->wechatLogin();
        // }
        
        session('admin_info', null);
        $viewfile = 'admin/login';
        if (2 <= $this->php_servicemeal) {
            $viewfile = 'admin/login_zy';
        }
        $this->global = tpCache('global');
        $this->assign('global', $this->global);

        return $this->fetch(":{$viewfile}");
    }

    private function wechatLogin()
    {
        $url = url('Admin/wechat_login', [], true, true);
        $url = preg_replace('/^http(s?)/i', $this->request->scheme(), $url);
        $this->redirect($url);
        exit;
    }

    /**
     * 解除锁定登录
     * @return [type] [description]
     */
    public function ajax_unlock_login()
    {
        $admin_id = input('param.id/d');
        if (!empty($admin_id) && IS_POST) {
            if (!empty($this->admin_info['parent_id']) || -1 != $this->admin_info['role_id']) {
                $this->error('该功能仅限于创始人操作！');
            }
            $user_name = Db::name('admin')->where(['admin_id'=>$admin_id])->value('user_name');
            $login_errnum_key = 'adminlogin_'.md5('login_errnum_'.$user_name);
            $login_errtime_key = 'adminlogin_'.md5('login_errtime_'.$user_name);
            $login_lock_key = 'adminlogin_'.md5('login_lock_'.$user_name); // 是否被锁定
            tpSetting('adminlogin', [$login_errnum_key => 0]);
            tpSetting('adminlogin', [$login_errtime_key => 0]);
            tpSetting('adminlogin', [$login_lock_key => 0]);
            adminLog('解除锁定：'.$user_name);
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        /*验证码插件开关*/
        $admin_login_captcha = config('captcha.admin_login');
        $config = (!empty($admin_login_captcha['is_on']) && !empty($admin_login_captcha['config'])) ? $admin_login_captcha['config'] : config('captcha.default');
        /*--end*/
        ob_clean(); // 清空缓存，才能显示验证码
        $Verify = new Verify($config);
        $Verify->entry('admin_login');
        exit();
    }
    
    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function admin_pwd()
    {
        $admin_id = input('param.admin_id/d',0);
        $oldPwd = input('param.old_pw/s');
        $newPwd = input('param.new_pw/s');
        $new2Pwd = input('param.new_pw2/s');
        
        if(empty($admin_id)){
            $admin_id = session('?admin_id') ? (int)session('admin_id') : 0;
        }
        $admin_info = Db::name('admin')->where(['admin_id'=>$admin_id])->find();
        $this->assign('admin_info', $admin_info);
        
        if(IS_POST){
            $enOldPwd = func_encrypt($oldPwd, true, pwd_encry_type($admin_info['password']));
            $enNewPwd = func_encrypt($newPwd, true, pwd_encry_type('bcrypt'));
            if(empty($admin_info) || $admin_info['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1, 'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1, 'msg'=>'两次密码不一致')));
            }else{
                $data = array(
                    'update_time'   => getTime(),
                    'password'      => $enNewPwd,
                );
                $row = Db::name('admin')->where(['admin_id'=>$admin_id])->save($data);
                if($row !== false){
                    // 检查密码复杂度
                    session('admin_login_pwdlevel', checkPasswordLevel($newPwd));
                    adminLog('修改管理员密码');
                    exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'操作失败')));
                }
            }
        }

        if (IS_AJAX) {
            return $this->fetch('admin/admin_pwd_ajax');
        } else {
            return $this->fetch('admin/admin_pwd');
        }
    }
    
    /**
     * 退出登陆
     */
    public function logout()
    {
        adminLog('安全退出');
        session_unset();
        // session_destroy();
        session::clear();
        cookie('admin-treeClicked', null); // 清除并恢复栏目列表的展开方式
        cookie('admin-treeClicked-1649642233', null); // 清除并恢复内容管理的展开方式
        $this->success("安全退出", request()->baseFile());
    }

    /**
     * 新增管理员时，检测用户名是否与前台用户名相同
     */
    public function ajax_add_user_name()
    {
        if (IS_AJAX_POST) {
            $user_name = input('post.user_name/s');
            if (Db::name('admin')->where("user_name", $user_name)->count()) {
                $this->error("此用户名已被注册，请更换！");
            }
            $row = Db::name('users')->field('users_id')->where([
                    'username'  => $user_name,
                    'lang'      => $this->admin_lang,
                ])->find();
            if (!empty($row)) {
                $this->error('已有相同会员名，将其转为系统账号？');
            } else {
                $this->success('会员名不存在，无需提示！');
            }
        }
    }

    /**
     * 新增管理员
     */
    public function admin_add()
    {
        $this->language_access(); // 多语言功能操作权限

        if (IS_POST) {
            $data = input('post.');

            if (0 < $this->admin_info['role_id']) {
                $this->error("超级管理员才能操作！");
            }

            if (empty($data['password'])) {
                $this->error("用户密码不能为空！", null, ['input_name'=>'password']);
            }

            $data['user_name'] = trim($data['user_name']);
            $data['password'] = func_encrypt($data['password'], true, pwd_encry_type('bcrypt'));
            $data['role_id'] = intval($data['role_id']);
            $data['parent_id'] = $this->admin_info['admin_id'];
            $data['add_time'] = getTime();
            if (empty($data['pen_name'])) {
                $data['pen_name'] = '小编';
            }
            if (Db::name('admin')->where(['user_name'=>$data['user_name']])->count()) {
                $this->error("此用户名已被注册，请更换", url('Admin/admin_add'), ['input_name'=>'user_name']);
            } else {
                $admin_id = Db::name('admin')->insertGetId($data);
                if ($admin_id !== false) {
                    adminLog('新增管理员：'.$data['user_name']);

                    /*同步追加一个后台管理员到会员用户表*/
                    try {
                        $usersInfo = Db::name('users')->field('users_id')->where([
                                'username'  => $data['user_name'],
                                'lang'      => $this->admin_lang,
                            ])->find();
                        if (!empty($usersInfo)) {
                            $r = Db::name('users')->where(['users_id'=>$usersInfo['users_id']])->update([
                                    'nickname'      => $data['user_name'],
                                    'admin_id'      => $admin_id,
                                    'is_activation' => 1,
                                    'is_lock'       => 0,
                                    'is_del'        => 0,
                                    'update_time'   => getTime(),
                                ]);
                            if ($r !== false) {
                                $users_id = $usersInfo['users_id'];
                            }
                        } else {
                            // 获取要添加的用户名
                            $username = $this->GetUserName($data['user_name']);
                            $password = getTime();
                            $password = func_encrypt($password, false, pwd_encry_type('bcrypt'));
                            $AddData = [
                                'username' => $username,
                                'nickname' => $username,
                                'password' => $password,
                                'level'    => 1,
                                'lang'     => $this->admin_lang,
                                'reg_time' => getTime(),
                                'head_pic' => ROOT_DIR . '/public/static/common/images/dfboy.png',
                                'register_place' => 1,
                                'admin_id' => $admin_id,
                            ];
                            $users_id = Db::name('users')->insertGetId($AddData);
                        }
                        if ($users_id !== false) {
                            Db::name('admin')->where(['admin_id'=>$admin_id])->update([
                                    'syn_users_id'  => intval($users_id),
                                    'update_time'   => getTime(),
                                ]);
                        }
                    } catch (\Exception $e) {}
                    /* END */

                    $this->success("操作成功", url('Admin/index'));
                } else {
                    $this->error("操作失败");
                }
            }
        }

        // 权限组
        $admin_role_list = model('AuthRole')->getRoleAll();
        $this->assign('admin_role_list', $admin_role_list);

        // 模块组
        $modules = getAllMenu();
        $this->assign('modules', $modules);

        // 权限集
        $auth_rules = get_auth_rule(['is_modules'=>1]);
        $auth_rule_list=group_same_key($auth_rules,'menu_id');$hideapi=array_column($auth_rule_list[2001],'name');$apikey=array_search('接口配置',$hideapi);unset($auth_rule_list[2001][$apikey]);
        foreach ($auth_rule_list as $key => $val) {
            if (is_array($val)) {
                $sort_order = [];
                foreach ($val as $_k => $_v) {
                    $sort_order[$_k]  = $_v['sort_order'];
                }
                array_multisort($sort_order, SORT_ASC, $val);
                $auth_rule_list[$key] = $val;
            }
        }
        $hidemod=array_column($auth_rule_list[2004],'name');$modkey=array_search('模块开关',$hidemod);unset($auth_rule_list[2004][$modkey]);if(tpCache('global')['web_citysite_open']==0){$citykey=array_search('城市分站',$hidemod);unset($auth_rule_list[2004][$citykey]);}$this->assign('auth_rule_list',$auth_rule_list);

        // 栏目
        $arctype_list = Db::name('arctype')->where([
            'is_del'    => 0,
        ])->order("grade desc")->select();
        $arctype_p_html = $arctype_child_html = "";
        $arctype_all = list_to_tree($arctype_list);
        foreach ($arctype_all as $key => $arctype) {
            if (!empty($arctype['children'])) {
                if ($key > 0) {
                    $arctype_p_html .= '<em class="arctype_bg expandable"></em>';
                } else {
                    $arctype_p_html .= '<em class="arctype_bg collapsable"></em>';
                }
                $arctype_child_html .= '<div class="arctype_child" id="arctype_child_' . $arctype['id'] . '"';
                if ($arctype_all[0]['id'] == $arctype['id']) {
                    $arctype_child_html .= ' style="display: block;" ';
                }
                $arctype_child_html .= '>';
                $arctype_child_html .= $this->get_arctype_child_html($arctype);
                $arctype_child_html .= '</div>';
            }
            $arctype_p_html .= '<label>';
            $arctype_p_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/ok.png" />';
            $arctype_p_html .= '<input type="checkbox" class="arctype_cbox arctype_id_' . $arctype['id'] . ' none" name="permission[arctype][]" value="' . $arctype['id'] . '"';
            $arctype_p_html .= ' checked="checked" ';
            $arctype_p_html .= ' />' . $arctype['typename'] . '</label>&nbsp;';
        }
        $this->assign('arctype_p_html', $arctype_p_html);
        $this->assign('arctype_child_html', $arctype_child_html);

        // 插件
        $plugins = model('Weapp')->getList(['status'=>1]);
        $hideplu=array_column($plugins,'data');$plucode=array_column($plugins,'code');foreach($hideplu as $key=>$val){if(strpos($val,'"is_hide":"1"')!==false){unset($plugins[$plucode[$key]]);}}$this -> assign('plugins',$plugins);

        return $this->fetch();
    }
    
    /**
     * 编辑管理员
     */
    public function admin_edit()
    {
        if (IS_POST) {
            $data = input('post.');
            $id = $data['admin_id'] = intval($data['admin_id']);
            $user_name = $data['user_name'] = isset($data['user_name']) ? trim($data['user_name']) : '';
            empty($data['pen_name']) && $data['pen_name'] = '小编';

            if ($id == $this->admin_info['admin_id']) {
                unset($data['role_id']); // 不能修改自己的权限组
            } else if (0 < $this->admin_info['role_id'] && $this->admin_info['admin_id'] != $id) {
                $this->error('禁止更改别人的信息！');
            }

            if (empty($this->admin_info['parent_id'])) { // 创始人才可以修改所有管理员的用户名
                if (empty($user_name)) {
                    $this->error('用户名不能为空！', null, ['input_name'=>'user_name']);
                } else {
                    if ($user_name == $this->admin_info['user_name']) {
                        unset($data['user_name']);
                    } else {
                        $count = Db::name('admin')->where(['user_name'=>$user_name, 'admin_id'=>['NEQ', $id]])->count();
                        if (!empty($count)) {
                            $this->error("此用户名已被注册，请更换", null, ['input_name'=>'user_name']);
                        }
                    }
                }
            } else {
                unset($data['user_name']);
            }

            $password = $data['password'];
            if (empty($password)) {
                unset($data['password']);
            }else{
                $entry = pwd_encry_type('bcrypt');
                $data['password'] = func_encrypt($password, true, $entry);
            }

            /*不允许修改自己的权限组*/
            if (isset($data['role_id'])) {
                if (0 < $this->admin_info['role_id'] && intval($data['role_id']) != $this->admin_info['role_id']) {
                    $data['role_id'] = $this->admin_info['role_id'];
                }
            }
            /*--end*/
            $data['update_time'] = getTime();
            $r = Db::name('admin')->where('admin_id', $id)->save($data);
            if ($r !== false) {
                if ($id == $this->admin_info['admin_id']) {
                    // 检查密码复杂度
                    session('admin_login_pwdlevel', checkPasswordLevel($password));

                    // 过滤存储在session文件的敏感信息
                    $this->admin_info = array_merge($this->admin_info, $data);
                    foreach (['user_name','true_name','password'] as $key => $val) {
                        unset($this->admin_info[$val]);
                    }
                    session('admin_info', $this->admin_info);
                }

                /*同步相同数据到会员表对应的会员*/
                $syn_users_id = Db::name('admin')->where(['admin_id'=>$data['admin_id']])->getField('syn_users_id');
                if (!empty($syn_users_id)) {
                    $updateData = [
                        'nickname'  => $data['pen_name'],
                        'head_pic'  => $data['head_pic'],
                        'update_time'   => getTime(),
                    ];
                    Db::name('users')->where(['users_id'=>$syn_users_id])->update($updateData);
                }
                /*end*/

                adminLog('编辑管理员：'.$user_name);
                $this->success("操作成功", url('Admin/index'));
            } else {
                $this->error("操作失败");
            }
        }

        $id = input('param.id/d', 0);
        $info = Db::name('admin')->field('password', true)->find($id);
        if (empty($info)) {
            $this->error('数据不存在，请联系管理员！');
            exit;
        }
        $this->assign('info',$info);

        // 当前角色信息
        $admin_role_model = model('AuthRole');
        $role_info = $admin_role_model->getRole(array('id' => $info['role_id']));
        $this->assign('role_info', $role_info);

        // 权限组
        $admin_role_list = $admin_role_model->getRoleAll();
        $this->assign('admin_role_list', $admin_role_list);

        // 模块组
        $modules = getAllMenu();
        $this->assign('modules', $modules);

        // 权限集
        $auth_rules = get_auth_rule(['is_modules'=>1]);
        $auth_rule_list=group_same_key($auth_rules,'menu_id');$hideapi=array_column($auth_rule_list[2001],'name');$apikey=array_search('接口配置',$hideapi);unset($auth_rule_list[2001][$apikey]);
        foreach ($auth_rule_list as $key => $val) {
            if (is_array($val)) {
                $sort_order = [];
                foreach ($val as $_k => $_v) {
                    $sort_order[$_k]  = $_v['sort_order'];
                }
                array_multisort($sort_order, SORT_ASC, $val);
                $auth_rule_list[$key] = $val;
            }
        }
        $hidemod=array_column($auth_rule_list[2004],'name');$modkey=array_search('模块开关',$hidemod);unset($auth_rule_list[2004][$modkey]);if(tpCache('global')['web_citysite_open']==0){$citykey=array_search('城市分站',$hidemod);unset($auth_rule_list[2004][$citykey]);}$this->assign('auth_rule_list',$auth_rule_list);

        // 栏目
        $arctype_list = Db::name('arctype')->where([
                'is_del'    => 0,
            ])->order("grade desc")->select();
        $arctype_p_html = $arctype_child_html = "";
        $arctype_all = list_to_tree($arctype_list);
        foreach ($arctype_all as $key => $arctype) {
            if (!empty($arctype['children'])) {
                if ($key > 0) {
                    $arctype_p_html .= '<em class="arctype_bg expandable"></em>';
                } else {
                    $arctype_p_html .= '<em class="arctype_bg collapsable"></em>';
                }
                $arctype_child_html .= '<div class="arctype_child" id="arctype_child_' . $arctype['id'] . '"';
                if ($arctype_all[0]['id'] == $arctype['id']) {
                    $arctype_child_html .= ' style="display: block;" ';
                }
                $arctype_child_html .= '>';
                $arctype_child_html .= $this->get_arctype_child_html($arctype,$role_info);
                $arctype_child_html .= '</div>';
            }

            $arctype_p_html .= '<label>';
            if (!empty($role_info['permission']['arctype']) && in_array($arctype['id'], $role_info['permission']['arctype'])) {
                $arctype_p_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/ok.png" />';
            }else{
                $arctype_p_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/del.png" />';
            }
            $arctype_p_html .= '<input type="checkbox" class="arctype_cbox arctype_id_' . $arctype['id'] . ' none" name="permission[arctype][]" value="' . $arctype['id'] . '"';
            if (!empty($role_info['permission']['arctype']) && in_array($arctype['id'], $role_info['permission']['arctype'])) {
                $arctype_p_html .= ' checked="checked" ';
            }
            $arctype_p_html .= ' />' . $arctype['typename'] . '</label>&nbsp;';
        }
        $this->assign('arctype_p_html', $arctype_p_html);
        $this->assign('arctype_child_html', $arctype_child_html);

        // 插件
        $plugins = model('Weapp')->getList(['status'=>1]);
        $hideplu=array_column($plugins,'data');$plucode=array_column($plugins,'code');foreach($hideplu as $key=>$val){if(strpos($val,'"is_hide":"1"')!==false){unset($plugins[$plucode[$key]]);}}$this -> assign('plugins',$plugins);

        // 是否使用第三方扫码登录
        $wechatInfo = [];
        $thirdata = login_third_type();
        if ('WechatLogin' == $thirdata['type']) { // 扫码微信应用
            if (!empty($thirdata['data']['security_wechat_open'])) {
                $wechatInfo = Db::name('admin_wxlogin')->where(['admin_id'=>$id, 'type'=>2])->find();
            }
        }
        else if ('EyouGzhLogin' == $thirdata['type']) { // 扫码官方公众号
            if (!empty($thirdata['data']['switch'])) {
                $wechatInfo = Db::name('admin_wxlogin')->where(['admin_id'=>$id, 'type'=>1])->find();
            }
        }
        $this->assign('thirdata', $thirdata);
        $this->assign('wechatInfo', $wechatInfo);

        return $this->fetch();
    }
    /*
     *  递归生成$arctype_child_html
     *  $vo             栏目tree
     *  $info           权限集合（用于edit是否已经选中）
     *  return          完整html
     */
    private function get_arctype_child_html($vo,$info = []){
        $arctype_child_html = "";
        if (!empty($vo['children'])) {
            $arctype_child_html .= '<div class="arctype_child1" id="arctype_child_' . $vo['id'] . '">';
            //判断当前下级是否还存在下级,true为竖着，false为横着
            $has_chldren = true;
            if ($vo['grade'] != 0 && !empty($vo['has_chldren']) && $vo['has_chldren'] == count($vo['children'])){
                $has_chldren = false;
            }
            if ($has_chldren){
                foreach ($vo['children'] as $vo1) {
                    $arctype_child_html .= '<div class="arctype_child1">';
                    $arctype_child_html .= ' <span class="button level1 switch center_docu"></span><label>';
                    if (!empty($info['permission']['arctype']) && in_array($vo1['id'], $info['permission']['arctype'])) {
                        $arctype_child_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/ok.png" />';
                    }else{
                        $arctype_child_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/del.png" />';
                    }
                    $arctype_child_html .= '<input type="checkbox" class="arctype_cbox arctype_id_' . $vo1['id'] . ' none" name="permission[arctype][]" value="' . $vo1['id'] . '" data-pid="' . $vo1['parent_id'] . '"';
                    if (!empty($info['permission']['arctype']) && in_array($vo1['id'], $info['permission']['arctype'])) {
                        $arctype_child_html .= ' checked="checked" ';
                    }
                    $arctype_child_html .= '/>' . $vo1['typename'] . '</label></div>';
                    $arctype_child_html .= $this->get_arctype_child_html($vo1,$info);
                }
            }else{
                $arctype_child_html .= '<div class="arctype_child2"> <span class="button level1 switch center_docu"></span>';
                foreach ($vo['children'] as $vo1) {
                    $arctype_child_html .= ' <label>';
                    if (!empty($info['permission']['arctype']) && in_array($vo1['id'], $info['permission']['arctype'])) {
                        $arctype_child_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/ok.png" />';
                    }else{
                        $arctype_child_html .= '<img class="cboximg" src="'.ROOT_DIR.'/public/static/admin/images/del.png" />';
                    }
                    $arctype_child_html .= '<input type="checkbox" class="arctype_cbox arctype_id_' . $vo1['id'] . ' none" name="permission[arctype][]" value="' . $vo1['id'] . '" data-pid="' . $vo1['parent_id'] . '"';
                    if (!empty($info['permission']['arctype']) && in_array($vo1['id'], $info['permission']['arctype'])) {
                        $arctype_child_html .= ' checked="checked" ';
                    }
                    $arctype_child_html .= '/>' . $vo1['typename'] . '</label>';
                    $arctype_child_html .= $this->get_arctype_child_html($vo1,$info);
                }
                $arctype_child_html .= '</div>';
            }
            $arctype_child_html .= '</div>';
        }

        return $arctype_child_html;
    }
    /**
     * 删除管理员
     */
    public function admin_del()
    {
        $this->language_access(); // 多语言功能操作权限

        if (IS_POST) {
            $id_arr = input('del_id/a');
            $id_arr = eyIntval($id_arr);
            if (in_array(session('admin_id'), $id_arr)) {
                $this->error('禁止删除自己');
            }
            if (!empty($id_arr)) {
                if (0 < $this->admin_info['role_id'] || !empty($this->admin_info['parent_id']) ) {
                    $count = Db::name('admin')->where("admin_id in (".implode(',', $id_arr).") AND role_id = -1")
                        ->count();
                    if (!empty($count)) {
                        $this->error('禁止删除超级管理员');
                    }
                }

                $result = Db::name('admin')->field('user_name')->where("admin_id",'IN',$id_arr)->select();
                $user_names = get_arr_column($result, 'user_name');

                $r = Db::name('admin')->where("admin_id",'IN',$id_arr)->delete();
                if($r){
                    adminLog('删除管理员：'.implode(',', $user_names));

                    /*同步删除管理员关联的前台会员*/
                    Db::name('users')->where(['admin_id'=>['IN', $id_arr],'lang'=>$this->admin_lang])->delete();
                    /*end*/

                    $this->success('删除成功');
                }else{
                    $this->error('删除失败');
                }
            }else{
                $this->error('参数有误');
            }
        }
        $this->error('非法操作');
    }

    /*
     * 第一次同步CMS用户的栏目ID到权限组里
     * 默认赋予内置权限所有的内容栏目权限
     */
    private function syn_built_auth_role()
    {
        $authRole = new AuthRole;
        $roleRow = $authRole->getRoleAll(['built_in'=>1,'update_time'=>['elt',0]]);
        if (!empty($roleRow)) {
            $saveData = [];
            foreach ($roleRow as $key => $val) {
                $permission = $val['permission'];
                $arctype = Db::name('arctype')->where('status',1)->column('id');
                if (!empty($arctype)) {
                    $permission['arctype'] = $arctype;
                } else {
                    unset($permission['arctype']);
                }
                $saveData[] = array(
                    'id'    => $val['id'],
                    'permission'    => $permission,
                    'update_time'   => getTime(),
                );
            }
            $authRole->saveAll($saveData);
        }
    }

    /*
     * 设置admin表数据
     */
    public function ajax_setfield()
    {
        $field  = input('field'); // 修改哪个字段
        $field = preg_replace('/([^\w\-])/i', '', $field);
        $field = str_replace(['password'], '', $field);
        if (IS_POST && !empty($field)) {
            $value  = input('value', '', null); // 修改字段值  
            if (!empty($this->admin_info['admin_id'])) {
                $r = Db::name('admin')->where('admin_id', $this->admin_info['admin_id'])->save([
                        $field=>$value,
                        'update_time'=>getTime(),
                    ]); // 根据条件保存修改的数据
                if ($r !== false) {
                    /*更新存储在session里的信息*/
                    $this->admin_info[$field] = $value;
                    session('admin_info', $this->admin_info);
                    /*--end*/
                    $this->success('操作成功');
                }
            }
        }
        $this->error('操作失败');
    }

    /*
     * 检测密码的复杂程度
     */
    public function ajax_checkPasswordLevel()
    {
        $password = input('post.password/s');
        if (IS_AJAX_POST && !empty($password)) {
            $pwdLevel = checkPasswordLevel($password);
            if (3 >= $pwdLevel) {
                $this->success("<font color='red'>当前密码复杂度为 {$pwdLevel} ，建议复杂度在 4~7 范围内，避免容易被暴力破解！</font>", null, ['pwdLevel'=>$pwdLevel]);
            } else {
                $this->success("<font color='green'>当前密码复杂度为 {$pwdLevel} ，在系统设定 4~7 安全范围内！</font>", null, ['pwdLevel'=>$pwdLevel]);
            }
        }
        $this->error('操作失败');
    }

    // 确保用户名唯一
    private function GetUserName($username = null)
    {
        $count = Db::name('users')->where('username',$username)->count();
        if (!empty($count)) {
            $username_new = $username.rand(1000,9999);
            $username = $this->GetUserName($username_new);
        }

        return $username;
    }

    /**
     * 同步追加一个后台管理员到会员用户表，并同步前台登录
     */
    private function syn_users_login($admin_info = [], $isFounder = 0)
    {
        $where_new = [
            'admin_id'  => $admin_info['admin_id'],
            'lang'      => $this->admin_lang,
        ];
        $users_id = Db::name('users')->where($where_new)->getField('users_id');
        try {
            if (empty($users_id) && empty($admin_info['syn_users_id'])) {
                $usersInfo = [];
                if (1 == $isFounder) {
                    // 如果是创始人，强制将与会员名相同的改为管理员前台用户名
                    $usersInfo = Db::name('users')->field('users_id')->where([
                            'username'  => $admin_info['user_name'],
                            'lang'      => $this->admin_lang,
                        ])->find();
                }
                if (!empty($usersInfo)) {
                    $r = Db::name('users')->where(['users_id'=>$usersInfo['users_id']])->update([
                            'nickname'      => $admin_info['user_name'],
                            'admin_id'      => $admin_info['admin_id'],
                            'is_activation' => 1,
                            'is_lock'       => 0,
                            'is_del'        => 0,
                            'update_time'   => getTime(),
                            'last_login'    => getTime(),
                        ]);
                    !empty($r) && $users_id = $usersInfo['users_id'];
                } else {
                    // 获取要添加的用户名
                    $username = $this->GetUserName($admin_info['user_name']);
                    $password = getTime();
                    $password = func_encrypt($password, false, pwd_encry_type('bcrypt'));
                    $AddData = [
                        'username' => $username,
                        'nickname' => $username,
                        'password' => $password,
                        'level'    => 1,
                        'lang'     => $this->admin_lang,
                        'reg_time' => getTime(),
                        'head_pic' => ROOT_DIR . '/public/static/common/images/dfboy.png',
                        'add_time' => getTime(),
                        'last_login' => getTime(),
                        'register_place' => 1,
                        'admin_id' => $admin_info['admin_id'],
                    ];
                    $users_id = Db::name('users')->insertGetId($AddData);
                }
                if (!empty($users_id)) {
                    Db::name('admin')->where(['admin_id'=>$admin_info['admin_id']])->update([
                            'syn_users_id'  => $users_id,
                            'update_time'   => getTime(),
                        ]);
                    $admin_info['syn_users_id'] = $users_id;
                    session('admin_info', $admin_info);
                }
            } else if (!empty($users_id) && empty($admin_info['syn_users_id'])) {
                Db::name('admin')->where(['admin_id'=>$admin_info['admin_id']])->update([
                        'syn_users_id'  => $users_id,
                        'update_time'   => getTime(),
                    ]);
                $admin_info['syn_users_id'] = $users_id;
                session('admin_info', $admin_info);
            }
        } catch (\Exception $e) {}
        
        // 加载前台session
        if (!empty($users_id)) {
            $users = Db::name('users')->field('a.*,b.level_name,b.level_value,b.discount as level_discount')
                ->alias('a')
                ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                ->where([
                    'a.users_id'        => $users_id,
                    'a.lang'            => $this->admin_lang,
                    'a.is_activation'   => 1,
                ])->find();
            if (!empty($users)) {
                Db::name('users')->where(['users_id'=>$users_id])->update([
                        'update_time'   => getTime(),
                        'last_login'    => getTime(),
                    ]);
                GetUsersLatestData($users_id);
            }
        }
    }

    /*-----------------------------------扫码微信应用 start--------------------------*/

    /**
     * 微信应用登录
     * @return [type] [description]
     */
    public function wechat_login()
    {
        $redirect_uri = url('Admin/wechat_callback', [], true, true);
        $redirect_uri = urlencode($redirect_uri);//该回调需要url编码
        $security     = tpSetting('security');
        $scope        = "snsapi_login";//写死，微信暂时只支持这个值
        //准备向微信发请求
        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $security['security_wechat_appid'] . "&redirect_uri=" . $redirect_uri
            . "&response_type=code&scope=" . $scope . "&state=STATE#wechat_redirect";
        $this->redirect($url);
        exit;
    }

    /**
     * 立即绑定微信应用
     * @return [type] [description]
     */
    public function wechat_bind()
    {
        $origin = input('param.origin/s');
        $admin_id = input('param.admin_id/d');
        $gourl = input('param.gourl/s');
        $gourl = htmlspecialchars_decode($gourl);
        $redirect_uri = url('Admin/wechat_callback', ['bind'=>1,'admin_id'=>$admin_id,'origin'=>$origin,'gourl'=>$gourl], true, true);
        $redirect_uri = urlencode($redirect_uri);//该回调需要url编码
        $security     = tpSetting('security');
        $scope        = "snsapi_login";//写死，微信暂时只支持这个值
        //准备向微信发请求
        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $security['security_wechat_appid'] . "&redirect_uri=" . $redirect_uri
            . "&response_type=code&scope=" . $scope . "&state=STATE&self_redirect=true#wechat_redirect";
        $this->redirect($url);
        exit;
    }

    /**
     * 微信应用扫描回调
     * @return [type] [description]
     */
    public function wechat_callback()
    {
        $code   = input('param.code/s');
        $bind   = input('param.bind/d');
        $isframe   = 0; // 是否在弹窗内跳转
        if (!empty($bind)) {
            $isframe = 1;
        }

        if (empty($code)) {
            if (empty($isframe)) {
                $this->error('微信回调参数错误');
            } else {
                $html = <<<EOF
                    <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                    <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                    <script type="text/javascript">
                        var _parent = parent;
                        _parent.layer.closeAll();
                        _parent.layer.alert("微信回调参数错误", {icon: 5, title: false});
                    </script>
EOF;
                echo $html;
                exit;
            }
        }
        $security     = tpSetting('security');
        $appid = $security['security_wechat_appid'];
        $secret = $security['security_wechat_secret'];
        //通过code获得 access_token + openid
        $url         = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid
            . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
        $jsonResult  = httpRequest($url);
        $resultArray = json_decode($jsonResult, true);
        if (!empty($resultArray['errcode'])) {
            if (empty($isframe)) {
                $this->error($resultArray['errmsg']);
            } else {
                $html = <<<EOF
                    <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                    <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                    <script type="text/javascript">
                        var _parent = parent;
                        _parent.layer.closeAll();
                        _parent.layer.alert("{$resultArray['errmsg']}", {icon: 5, title: false});
                    </script>
EOF;
                echo $html;
                exit;
            }
        }
        $access_token = $resultArray["access_token"];
        $openid       = $resultArray["openid"];
        $unionid      = !empty($resultArray["unionid"]) ? $resultArray["unionid"] : '';

        //通过access_token + openid 获得用户所有信息,结果全部存储在$infoArray里
        $infoUrl    = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid;
        $infoResult = httpRequest($infoUrl);
        $infoArray  = json_decode($infoResult, true);
        empty($infoArray['nickname']) && $infoArray['nickname'] = '';
        $nickname = $infoArray['nickname'] = filterNickname($infoArray['nickname']);

        if (!empty($infoArray['errcode'])) {
            if (empty($isframe)) {
                $this->error($infoArray['errmsg']);
            } else {
                $html = <<<EOF
                    <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                    <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                    <script type="text/javascript">
                        var _parent = parent;
                        _parent.layer.closeAll();
                        _parent.layer.alert("{$infoArray['errmsg']}", {icon: 5, title: false});
                    </script>
EOF;
                echo $html;
                exit;
            }
        }

        if (!empty($bind)) { // 绑定
            $admin_id = input('param.admin_id/d');
            $origin = input('param.origin/s');
            $origin = preg_replace('/([^\w\-]+)/i', '', $origin);
            $this->wechat_bind_handle($openid, $unionid, $infoArray, $admin_id, $origin);
            return true;
        }
        else { // 登录
            $this->wechat_login_handle($openid);
            return true;
        }
    }

    /**
     * 微信应用扫码登录处理
     * @param  string $openid [description]
     * @return [type]         [description]
     */
    private function wechat_login_handle($openid = '')
    {
        $web_adminbasefile = tpCache('global.web_adminbasefile');
        $web_adminbasefile = !empty($web_adminbasefile) ? $web_adminbasefile : $this->root_dir.'/login.php';
        $we_user = Db::name('admin_wxlogin')->field('a.openid, b.admin_id, b.user_name')
            ->alias('a')
            ->join('admin b', 'a.admin_id=b.admin_id', 'LEFT')
            ->where(['a.openid'=>$openid, 'a.type'=>2])
            ->find();
        if (empty($we_user['user_name'])) {
            adminLog('登录失败(微信用户不存在)');
            $this->error('微信用户不存在！', $web_adminbasefile);
        } else {
            $admin_info = adminLoginAfter($we_user['admin_id'], session_id(), 'WechatLogin');
            if (!empty($admin_info)) {
                adminLog('扫码登录成功');
                $this->success('登录成功', $web_adminbasefile);
            }
            adminLog('扫码登录失败');
            $this->error('登录失败', $web_adminbasefile);
        }
    }

    /**
     * 微信应用绑定处理
     * @param  string $openid  [description]
     * @param  string $unionid [description]
     * @param  array  $wx_info [description]
     * @return [type]          [description]
     */
    private function wechat_bind_handle($openid = '', $unionid = '', $wx_info = [], $admin_id = 0, $origin = '')
    {
        if (empty($this->admin_info['parent_id']) && -1 == $this->admin_info['role_id']) { // 创始人
            $is_founder = 1;
            empty($admin_id) && $admin_id = $this->admin_info['admin_id'];
            $admin_info = Db::name('admin')->where(['admin_id'=>$admin_id])->find();
        } else {
            $is_founder = 0;
            $admin_info = $this->admin_info;
            $admin_id = $this->admin_info['admin_id'];
        }

        if (empty($admin_info)) {
            $html = <<<EOF
                <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                <script type="text/javascript">
                    var _parent = parent;
                    _parent.layer.closeAll();
                    _parent.layer.alert("查不到管理员信息", {icon: 5, title: false});
                </script>
EOF;
            echo $html;
            exit;
        }

        $row = Db::name('admin_wxlogin')->where(['openid'=>$openid, 'type'=>2])->find();
        if(!empty($row))
        {
            if (!empty($row['admin_id'])) {
                $count = Db::name('admin')->where(['admin_id'=>$row['admin_id']])->count();
                if (!empty($count)) {
                    $html = <<<EOF
                        <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                        <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                        <script type="text/javascript">
                            var _parent = parent;
                            _parent.layer.closeAll();
                            _parent.layer.alert("当前微信已被绑定", {icon: 5, title: false});
                        </script>
EOF;
                    echo $html;
                    exit;
                }
            }
            $saveData = [
                'admin_id'   => $admin_id,
                'nickname'   => $wx_info['nickname'],
                'headimgurl' => $wx_info['headimgurl'],
                'update_time'=> getTime(),
            ];
            $r = Db::name('admin_wxlogin')->where([
                    'wx_id' => $row['wx_id'],
                ])->update($saveData);
        } else {
            $saveData = [
                'admin_id'  => $admin_id,
                'nickname'  => $wx_info['nickname'],
                'headimgurl'  => $wx_info['headimgurl'],
                'type'  => 2,
                'openid'    => $openid,
                'unionid'    => $unionid,
                'add_time'=> getTime(),
                'update_time'=> getTime(),
            ];
            $r = Db::name('admin_wxlogin')->insert($saveData);
        }

        if ($r !== false) {
            \think\Cache::clear("admin_wxlogin");
            // 同步昵称、头像
            $updateData = [
                'update_time'=> getTime(),
            ];
            if (empty($admin_info['head_pic']) && !empty($wx_info['headimgurl'])) {
                $updateData['head_pic'] = $wx_info['headimgurl'];
                if ($admin_id == $this->admin_info['admin_id']) {
                    $this->admin_info['head_pic'] = $wx_info['headimgurl'];
                }
            }
            Db::name('admin')->where(['admin_id'=>$admin_id])->update($updateData);


            if ($admin_id == $this->admin_info['admin_id']) {
                $this->admin_info['openid'] = $openid;
                session('admin_info', $this->admin_info);
            } else {
                if (1 == $is_founder) {
                    $openid = Db::name('admin_wxlogin')->where(['admin_id'=>$this->admin_info['admin_id'], 'type'=>2])->value('openid');
                    if (!empty($openid)) {
                        $this->admin_info['openid'] = $openid;
                        session('admin_info', $this->admin_info);
                    }
                }
            }
            $gourl = input('param.gourl/s');
            $gourl = htmlspecialchars_decode($gourl);
            $html = <<<EOF
                <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
                <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
                <script type="text/javascript">
                    var origin = "{$origin}";
                    var _parent = parent;
                    if ('list' != origin) {
                        var documentOjb = window.parent.document;
                        $('#span_wechat_nickname', documentOjb).html("{$wx_info['nickname']}");
                        $('#wechat_bind', documentOjb).hide();
                        $('#wechat_unbind', documentOjb).show();
                    }
                    _parent.layer.closeAll();
                    _parent.layer.msg("绑定成功", {time: 1000}, function(){
                        if ('list' == origin) {
                            _parent.window.location.reload();
                        }
                    });
                </script>
EOF;
            echo $html;
            exit;
        }
        $html = <<<EOF
            <script type="application/javascript" src="{$this->root_dir}/public/static/common/js/jquery.min.js"></script>
            <script type="application/javascript" src="{$this->root_dir}/public/plugins/layer-v3.1.0/layer.js"></script>
            <script type="text/javascript">
                var _parent = parent;
                _parent.layer.closeAll();
                _parent.layer.alert("绑定失败", {icon: 5, title: false});
            </script>
EOF;
        echo $html;
        exit;
    }

    /**
     * 解除绑定微信应用
     * @return [type] [description]
     */
    public function wechat_unbind_handle()
    {
        if (empty($this->admin_info['parent_id']) && -1 == $this->admin_info['role_id']) {
            $admin_id = input('param.admin_id/d', $this->admin_info['admin_id']);
        } else {
            $admin_id = intval($this->admin_info['admin_id']);
        }

        if (IS_POST && !empty($admin_id)) {

            $security_wechat_forcelogin = tpSetting('security.security_wechat_forcelogin');
            if (!empty($security_wechat_forcelogin)) {
                $this->error('检测已开启强制扫码登录，禁止解绑');
            }

            $r = Db::name('admin_wxlogin')->where(['admin_id'=>$admin_id, 'type'=>2])->delete();
            if ($r !== false) {
                \think\Cache::clear("admin_wxlogin");
                if ($admin_id == $this->admin_info['admin_id'] && isset($this->admin_info['openid'])) {
                    unset($this->admin_info['openid']);
                }
                session('admin_info', $this->admin_info);
                $this->success("操作成功");
            }
        }
        $this->error("操作失败");
    }
    /*-----------------------------------扫码微信应用 end--------------------------*/


    /*--------------------------------扫码微信公众号 start--------------------------*/
    //获取官方微信公众号二维码
    public function mp_getqrcode()
    {
        $eyouGzhLoginLogic = new \weapp\EyouGzhLogin\logic\EyouGzhLoginLogic;
        $eyouGzhLoginLogic->mp_getqrcode();
    }

    //绑定官方微信公众号openid
    public function mp_bingwxgzhopenid()
    {
        $eyouGzhLoginLogic = new \weapp\EyouGzhLogin\logic\EyouGzhLoginLogic;
        $eyouGzhLoginLogic->mp_bingwxgzhopenid();
    }

    //解绑官方微信公众号
    public function mp_unbindwx()
    {
        $eyouGzhLoginLogic = new \weapp\EyouGzhLogin\logic\EyouGzhLoginLogic;
        $eyouGzhLoginLogic->mp_unbindwx();
    }
    /*--------------------------------扫码微信公众号 end--------------------------*/
}