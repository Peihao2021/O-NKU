<?php

namespace app\admin\behavior;

use think\Db;

/**
 * 系统行为扩展：新增/更新/删除之后的后置操作
 */
load_trait('controller/Jump');
class ActionBeginBehavior {
    use \traits\controller\Jump;
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

    }

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        self::$actionName = request()->action();
        self::$controllerName = request()->controller();
        self::$moduleName = request()->module();
        self::$method = request()->method();
        $this->_initialize();
    }

    private function _initialize() {
        $this->security_verify();
        if ('POST' == self::$method) {
            $this->clearWeapp();
            $this->instyes();
        } else {
            $this->unotice();
            $this->verifyfile();
        }
    }

    private function security_verify()
    {
        $ctl_act = self::$controllerName.'@'.self::$actionName;
        if (in_array(self::$controllerName, ['Filemanager', 'Weapp']) || in_array($ctl_act, ['Arctype@ajax_newtpl','Archives@ajax_newtpl'])) {
            $security = tpSetting('security');

            /*---------强制必须开启二次安全认证 start----------*/
            if (in_array(self::$controllerName, ['Filemanager']) || in_array($ctl_act, ['Arctype@ajax_newtpl','Archives@ajax_newtpl'])) {
                if (empty($security['security_ask_open'])) {
                    $this->error("<span style='display:none;'>__html__</span>需要开启二次验证密码", url('Security/index'), '', 3);
                }
            }
            /*---------强制必须开启二次安全认证 end----------*/

            $nosubmit = input('param.nosubmit/d');
            if ('POST' == self::$method && empty($nosubmit)) {
                if (empty($security['security_ask_open']) || !security_verify_func($ctl_act)) {
                    return true;
                }
                $admin_id = session('?admin_id') ? (int)session('admin_id') : 0;
                $admin_info = Db::name('admin')->field('admin_id,last_ip')->where(['admin_id'=>$admin_id])->find();
                // 当前管理员二次安全验证过的IP地址
                $security_answerverify_ip = !empty($security['security_answerverify_ip']) ? $security['security_answerverify_ip'] : '-1';
                // 同IP不验证
                if ($admin_info['last_ip'] == $security_answerverify_ip) {
                    return true;
                }

                $this->error("<span style='display:none;'>__html__</span>出于安全考虑<br/>请勿非法越过二次安全验证", null, '', 3);
            }
        }
    }

    private function verifyfile()
    {
        $tmp1 = 'cGhwLnBocF9zZXJ2aW'.'NlaW5mbw==';
        $tmp1 = base64_decode($tmp1);
        $data = tpCache($tmp1);
        $data = mchStrCode($data, 'DECODE');
        $data = json_decode($data, true);
        if (empty($data['pid']) || 2 > $data['pid']) return true;
        $file = "./data/conf/{$data['code']}.txt";
        $tmp2 = 'cGhwX3NlcnZpY2VtZWFs';
        $tmp2 = base64_decode($tmp2);
        if (!file_exists($file)) {
            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')->select();
                foreach ($langRow as $key => $val) {
                    tpCache('php', [$tmp2=>1], $val['mark']);
                }
            } else { // 单语言
                tpCache('php', [$tmp2=>1]);
            }
            /*--end*/
        } else {
            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')->select();
                foreach ($langRow as $key => $val) {
                    tpCache('php', [$tmp2=>$data['pid']], $val['mark']);
                }
            } else { // 单语言
                tpCache('php', [$tmp2=>$data['pid']]);
            }
            /*--end*/
        }
    }

    private function unotice(){
        $str = 'VXNlcnNOb3RpY2U=';
        if (self::$controllerName == base64_decode($str)) {
            $str = 'd2ViLndlYl9pc19hdXRob3J0b2tlbg==';
            $value = tpCache(base64_decode($str));
            if (-1 == $value) {
                $str = '6K+l5Yqf6IO95LuF6ZmQ5LqO5ZWG5Lia5o6I5p2D5Z+f5ZCN77yB';
                $this->error(base64_decode($str));
            }
        }
    }

    /**
     * 插件每次post提交都清除插件相关缓存
     * @access private
     */
    private function clearWeapp()
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $ctlActArr = array(
            'Weapp@*',
        );
        $ctlActStr = self::$controllerName.'@*';
        if (in_array($ctlActStr, $ctlActArr)) {
            \think\Cache::clear('hooks');
        }
        /*--end*/
    }

    /**
     * @access private
     */
    private function instyes()
    {
        $ca = md5(self::$actionName.'@'.self::$controllerName);
        if ('0e3e00da04fcf78cd9fd7dc763d956fc' == $ca) {
            $s = '5a6J'.'6KOF'.'5oiQ5'.'Yqf';
            if (1605110400 < getTime()) {
                sleep(5);
                $this->success(base64_decode($s));
            }
        }
    }
}
