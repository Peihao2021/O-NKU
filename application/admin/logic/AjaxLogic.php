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

namespace app\admin\logic;

use think\Model;
use think\Db;

/**
 * 逻辑定义
 * Class CatsLogic
 * @package admin\Logic
 */
class AjaxLogic extends Model
{
    private $request = null;
    private $admin_lang = 'cn';
    private $main_lang = 'cn';

    /**
     * 析构函数
     */
    function  __construct() {
        $this->request = request();
        $this->admin_lang = get_admin_lang();
        $this->main_lang = get_main_lang();
    }

    /**
     * 进入登录页面需要异步处理的业务
     */
    public function login_handle()
    {
        // $this->repairAdmin(); // 修复管理员ID为0的问题
        $this->saveBaseFile(); // 存储后台入口文件路径，比如：/login.php
        clear_session_file(); // 清理过期的data/session文件
    }

    /**
     * 修复管理员
     * @return [type] [description]
     */
    private function repairAdmin()
    {
        $row = [];
        $result = Db::name('admin')->field('admin_id,user_name')->order('add_time asc')->select();
        $total = count($result);
        foreach ($result as $key => $val) {
            $pre_admin_id = $next_admin_id = 0;
            if (empty($val['admin_id'])) {
                if (1 == $total) {
                    Db::name('admin')->where(['user_name'=>$val['user_name']])->update(['admin_id'=>1, 'update_time'=>getTime()]);
                } else {
                    $pre_admin_id = empty($key) ? 0 : $result[$key - 1]['admin_id'];
                    if ($key < ($total - 1)) {
                        $next_admin_id = $result[$key + 1]['admin_id'];
                    } else {
                        $next_admin_id = $pre_admin_id + 2;
                    }

                    if (($next_admin_id - $pre_admin_id) >= 2) {
                        $admin_id = $pre_admin_id + 1;
                        Db::name('admin')->where(['user_name'=>$val['user_name']])->update(['admin_id'=>$admin_id, 'update_time'=>getTime()]);
                    }
                }
            }
        }
    }

    /**
     * 进入欢迎页面需要异步处理的业务
     */
    public function welcome_handle()
    {
        getVersion('version_themeusers', 'v1.0.1', true);
        getVersion('version_themeshop', 'v1.0.1', true);
        $this->saveBaseFile(); // 存储后台入口文件路径，比如：/login.php
        $this->renameInstall(); // 重命名安装目录，提高网站安全性
        $this->renameSqldatapath(); // 重命名数据库备份目录，提高网站安全性
        $this->del_adminlog(); // 只保留最近一个月的操作日志
        tpversion(); // 统计装载量，请勿删除，谢谢支持！
    }
    
    /**
     * 只保留最近一个月的操作日志
     */
    public function del_adminlog()
    {
        try {
            $mtime = strtotime("-1 month");
            Db::name('admin_log')->where([
                'log_time'  => ['lt', $mtime],
                ])->delete();
        } catch (\Exception $e) {}
    }

    /*
     * 修改备份数据库目录
     */
    private function renameSqldatapath() {
        $default_sqldatapath = config('DATA_BACKUP_PATH');
        if (is_dir('.'.$default_sqldatapath)) { // 还是符合初始默认的规则的链接方式
            $dirname = get_rand_str(20, 0, 1);
            $new_path = '/data/sqldata_'.$dirname;
            if (@rename(ROOT_PATH.ltrim($default_sqldatapath, '/'), ROOT_PATH.ltrim($new_path, '/'))) {
                /*多语言*/
                if (is_language()) {
                    $langRow = \think\Db::name('language')->order('id asc')->select();
                    foreach ($langRow as $key => $val) {
                        tpCache('web', ['web_sqldatapath'=>$new_path], $val['mark']);
                    }
                } else { // 单语言
                    tpCache('web', ['web_sqldatapath'=>$new_path]);
                }
                /*--end*/
            }
        }
    }

    /**
     * 重命名安装目录，提高网站安全性
     * 在 Admin@login 和 Index@index 操作下
     */
    private function renameInstall()
    {
        if (stristr($this->request->host(), 'eycms.hk')) {
            return true;
        }
        $install_path = ROOT_PATH.'install';
        if (is_dir($install_path) && file_exists($install_path)) {
            $install_time = get_rand_str(20, 0, 1);
            $new_path = ROOT_PATH.'install_'.$install_time;
            @rename($install_path, $new_path);
        }
        else {
            $dirlist = glob('install_*');
            $install_dirname = current($dirlist);
            if (!empty($install_dirname)) {
                /*---修补v1.1.6版本删除的安装文件 install.lock start----*/
                if (!empty($_SESSION['isset_install_lock'])) {
                    return true;
                }
                $_SESSION['isset_install_lock'] = 1;
                /*---修补v1.1.6版本删除的安装文件 install.lock end----*/

                $install_path = ROOT_PATH.$install_dirname;
                if (preg_match('/^install_[0-9]{10}$/i', $install_dirname)) {
                    $install_time = get_rand_str(20, 0, 1);
                    $install_dirname = 'install_'.$install_time;
                    $new_path = ROOT_PATH.$install_dirname;
                    if (@rename($install_path, $new_path)) {
                        $install_path = $new_path;
                        /*多语言*/
                        if (is_language()) {
                            $langRow = \think\Db::name('language')->order('id asc')->select();
                            foreach ($langRow as $key => $val) {
                                tpSetting('install', ['install_dirname'=>$install_time], $val['mark']);
                            }
                        } else { // 单语言
                            tpSetting('install', ['install_dirname'=>$install_time]);
                        }
                        /*--end*/
                    }
                }

                $filename = $install_path.DS.'install.lock';
                if (!file_exists($filename)) {
                    @file_put_contents($filename, '');
                }
            }
        }
    }

    /**
     * 存储后台入口文件路径，比如：/login.php
     * 在 Admin@login 和 Index@index 操作下
     */
    private function saveBaseFile()
    {
        $data = [];
        $data['web_adminbasefile'] = $this->request->baseFile();
        $data['web_cmspath'] = ROOT_DIR; // EyouCMS安装目录
        /*多语言*/
        if (is_language()) {
            $langRow = \think\Db::name('language')->field('mark')->order('id asc')->select();
            foreach ($langRow as $key => $val) {
                tpCache('web', $data, $val['mark']);
            }
        } else { // 单语言
            tpCache('web', $data);
        }
        /*--end*/
    }

    /**
     * 升级前台会员中心的模板文件
     */
    public function update_template($type = '')
    {
        if (!empty($type)) {
            if ('users' == $type) {
                if (file_exists(ROOT_PATH.'template/'.TPL_THEME.'pc/users') || file_exists(ROOT_PATH.'template/'.TPL_THEME.'mobile/users')) {
                    $upgrade = getDirFile(DATA_PATH.'backup'.DS.'tpl');
                    if (!empty($upgrade) && is_array($upgrade)) {
                        delFile(DATA_PATH.'backup'.DS.'template_www');
                        // 升级之前，备份涉及的源文件
                        foreach ($upgrade as $key => $val) {
                            $val_tmp = str_replace("template/", "template/".TPL_THEME, $val);
                            $source_file = ROOT_PATH.$val_tmp;
                            if (file_exists($source_file)) {
                                $destination_file = DATA_PATH.'backup'.DS.'template_www'.DS.$val_tmp;
                                tp_mkdir(dirname($destination_file));
                                @copy($source_file, $destination_file);
                            }
                        }

                        // 递归复制文件夹
                        $this->recurse_copy(DATA_PATH.'backup'.DS.'tpl', rtrim(ROOT_PATH, DS));
                    }
                    /*--end*/
                }
            }
        }
    }

    /**
     * 自定义函数递归的复制带有多级子目录的目录
     * 递归复制文件夹
     *
     * @param string $src 原目录
     * @param string $dst 复制到的目录
     * @return string
     */                        
    //参数说明：            
    //自定义函数递归的复制带有多级子目录的目录
    private function recurse_copy($src, $dst)
    {
        $planPath_pc = "template/".TPL_THEME."pc/";
        $planPath_m = "template/".TPL_THEME."mobile/";
        $dir = opendir($src);

        /*pc和mobile目录存在的情况下，才拷贝会员模板到相应的pc或mobile里*/
        $dst_tmp = str_replace('\\', '/', $dst);
        $dst_tmp = rtrim($dst_tmp, '/').'/';
        if (stristr($dst_tmp, $planPath_pc) && file_exists($planPath_pc)) {
            tp_mkdir($dst);
        } else if (stristr($dst_tmp, $planPath_m) && file_exists($planPath_m)) {
            tp_mkdir($dst);
        }
        /*--end*/

        while (false !== $file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $needle = '/template/'.TPL_THEME;
                    $needle = rtrim($needle, '/');
                    $dstfile = $dst . '/' . $file;
                    if (!stristr($dstfile, $needle)) {
                        $dstfile = str_replace('/template', $needle, $dstfile);
                    }
                    $this->recurse_copy($src . '/' . $file, $dstfile);
                }
                else {
                    if (file_exists($src . DIRECTORY_SEPARATOR . $file)) {
                        /*pc和mobile目录存在的情况下，才拷贝会员模板到相应的pc或mobile里*/
                        $rs = true;
                        $src_tmp = str_replace('\\', '/', $src . DIRECTORY_SEPARATOR . $file);
                        if (stristr($src_tmp, $planPath_pc) && !file_exists($planPath_pc)) {
                            continue;
                        } else if (stristr($src_tmp, $planPath_m) && !file_exists($planPath_m)) {
                            continue;
                        }
                        /*--end*/
                        $rs = @copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                        if($rs) {
                            @unlink($src . DIRECTORY_SEPARATOR . $file);
                        }
                    }
                }
            }
        }
        closedir($dir);
    }
    
    // 记录当前是多语言还是单语言到文件里
    public function system_langnum_file()
    {
        model('Language')->setLangNum();
    }
    
    // 记录当前是否多站点到文件里
    public function system_citysite_file()
    {
        $key = base64_decode('cGhwLnBocF9zZXJ2aWNlbWVhbA==');
        $value = tpCache($key);
        if (2 > $value) {
            /*多语言*/
            if (is_language()) {
                $langRow = Db::name('language')->order('id asc')->select();
                foreach ($langRow as $key => $val) {
                    tpCache('web', ['web_citysite_open'=>0], $val['mark']);
                }
            } else { // 单语言
                tpCache('web', ['web_citysite_open'=>0]);
            }
            /*--end*/
            model('Citysite')->setCitysiteOpen();
        }
    }

    public function admin_logic_1609900642()
    {
        $vars1 = 'cGhwLnBo'.'cF9zZXJ2aW'.'NlaW5mbw==';
        $vars1 = base64_decode($vars1);
        $data = tpCache($vars1);
        $data = mchStrCode($data, 'DECODE');
        $data = json_decode($data, true);
        if (empty($data['pid']) || 2 > $data['pid']) return true;
        $file = "./data/conf/{$data['code']}.txt";
        $vars2 = 'cGhwX3Nl'.'cnZpY2V'.'tZWFs';
        $vars2 = base64_decode($vars2);
        if (!file_exists($file)) {
            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')->select();
                foreach ($langRow as $key => $val) {
                    tpCache('php', [$vars2=>1], $val['mark']);
                }
            } else { // 单语言
                tpCache('php', [$vars2=>1]);
            }
            /*--end*/
        } else {
            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')->select();
                foreach ($langRow as $key => $val) {
                    tpCache('php', [$vars2=>$data['pid']], $val['mark']);
                }
            } else { // 单语言
                tpCache('php', [$vars2=>$data['pid']]);
            }
            /*--end*/
        }
    }

    // 评价主表评分由原先的(好评、中评、差评)转至实际星评数(1、2、3、4、5)(v1.6.1节点去掉--陈风任)
    public function admin_logic_1651114275()
    {
        $syn_admin_logic_1651114275 = tpSetting('syn.admin_logic_1651114275', [], 'cn');
        if (empty($syn_admin_logic_1651114275)) {
            $shopOrderComment = Db::name('shop_order_comment')->field('comment_id, total_score')->select();
            foreach ($shopOrderComment as $key => $value) {
                if (in_array($value['total_score'], [1])) {
                    $value['total_score'] = 5;
                } else if (in_array($value['total_score'], [2])) {
                    $value['total_score'] = 3;
                } else if (in_array($value['total_score'], [3])) {
                    $value['total_score'] = 2;
                }
                if (!empty($value)) Db::name('shop_order_comment')->update($value);
            }
            tpSetting('syn', ['admin_logic_1651114275'=>1], 'cn');
        }
    }

    public function admin_logic_1623036205()
    {
        $getTableInfo = [];
        $Prefix = config('database.prefix');

        // 重置页面保存目录
        $admin_logic_1655453263 = tpSetting('syn.admin_logic_1655453263', [], 'cn');
        if (empty($admin_logic_1655453263)) {
            $seo_pseudo = tpCache('seo.seo_pseudo');
            if (2 != $seo_pseudo) {
                /*多语言*/
                if (is_language()) {
                    $langRow = Db::name('language')->order('id asc')->select();
                    foreach ($langRow as $key => $val) {
                        tpCache('seo', ['seo_html_arcdir'=>'html'], $val['mark']);
                    }
                } else { // 单语言
                    tpCache('seo', ['seo_html_arcdir'=>'html']);
                }
                /*--end*/
            }
            tpSetting('syn', ['admin_logic_1655453263'=>1], 'cn');
        }
        // 隐藏问答模型
        $admin_logic_1649299958 = tpSetting('syn.admin_logic_1649299958', [], 'cn');
        if (true || empty($admin_logic_1649299958)) {
            $row = Db::name('arctype')->where(['current_channel'=>51])->count();
            if (empty($row)) {
                Db::name('channeltype')->where(['id'=>51])->cache(true,null,'channeltype')->update(['status'=>0, 'is_del'=>1, 'update_time'=>getTime()]);
            }
            tpSetting('syn', ['admin_logic_1649299958'=>1], 'cn');
        }

        // 标记当前管理员是否创始人
        $admin_info = session('admin_info');
        $admin_logic_1648775669 = tpCache("syn.admin_logic_{$admin_info['admin_id']}_1648775669", [], 'cn');
        if (empty($admin_logic_1648775669)) {
            $is_founder = 0;
            if (empty($admin_info['parent_id']) && -1 == $admin_info['role_id']) {
                $is_founder = 1;
            }
            $admin_info['is_founder'] = $is_founder;
            session('admin_info', $admin_info);
            tpCache('syn', ["admin_logic_{$admin_info['admin_id']}_1648775669"=>1], 'cn');
        }

        // 标记用户是否使用旧产品参数
        try {
            $aids = Db::name('product_attr')->where(['product_attr_id'=>['GT',0]])->column('aid');
            if (empty($aids)) {
                $system_old_product_attr = 0;
            } else {
                $count = Db::name('archives')->where(['aid'=>['IN', $aids], 'attrlist_id'=>0])->count();
                if (empty($count)) { // 这里会误伤正在新增旧产品参数，还没有发布文档的用户
                    $system_old_product_attr = 0;
                } else {
                    $system_old_product_attr = 1;
                }
            }
            tpSetting('system', ['system_old_product_attr'=>$system_old_product_attr], 'cn');
        } catch (\Exception $e) {}

        // 覆盖安装目录文件 / .htaccess 文件 / 入口文件
        $admin_logic_1643352860 = tpSetting('syn.admin_logic_1643352860', [], 'cn');
        if (empty($admin_logic_1643352860) || 1 >= $admin_logic_1643352860) {
            tpSetting('syn', ['admin_logic_1643352860'=>2], 'cn');
        }

        // 同步会员升级订单的会员级别ID level_id
        $admin_logic_1647918733 = tpSetting('syn.admin_logic_1647918733', [], 'cn');
        if (empty($admin_logic_1647918733)) {
            // 升级数据
            $UsersMoney = Db::name('users_money')->where(['cause_type'=>0])->select();
            $update = [];
            foreach ($UsersMoney as $key => $value) {
                // 处理获取会员级别ID level_id
                $level_id = 0;
                $valueCause = !empty($value['cause']) ? unserialize($value['cause']) : [];
                if (!empty($valueCause) && !empty($valueCause['level_id'])) $level_id = $valueCause['level_id'];

                // 更新数组
                $update[] = [
                    // 更新主键
                    'moneyid' => $value['moneyid'],
                    // 更新数据
                    'level_id' => $level_id,
                    'update_time' => getTime(),
                ];
            }
            !empty($update) && $ResultID = model('UsersMoney')->saveAll($update);
            tpSetting('syn', ['admin_logic_1647918733'=>1], 'cn');
        }

        // 优化第一波升级的功能地图
        $admin_logic_1648882158 = tpSetting('syn.admin_logic_1648882158', [], 'cn');
        if (empty($admin_logic_1648882158)) {
            $menu_ids = [2008001,2008002,2008003,2008008,2008004,2008005];
            Db::name('admin_menu')->where(['menu_id'=>['IN', $menu_ids]])->delete();
            Db::name('admin_menu')->where(['menu_id'=>['IN', [2008]]])->update(['is_menu'=>1, 'update_time'=>getTime()]);
            tpSetting('syn', ['admin_logic_1648882158'=>1], 'cn');
        }

        // 纠正左侧菜单数据
        $admin_logic_1649399344 = tpSetting('syn.admin_logic_1649399344', [], 'cn');
        if (empty($admin_logic_1649399344)) {
            Db::name('admin_menu')->where(['menu_id'=>'2004004'])->update(['action_name'=>'arctype_index', 'update_time'=>getTime()]);
            tpSetting('syn', ['admin_logic_1649399344'=>1], 'cn');
        }

        Db::name("admin_menu")->where(['menu_id'=>1001])->update(['param'=>'|mt20|1']);
        Db::name("admin_menu")->where(['menu_id'=>2004006])->update(['param'=>'|mt20|1']);
        Db::name("admin_menu")->where(['menu_id'=>2004017])->update(['title'=>'安全中心']);

        // 同步微站点的公众号配置到统一配置的地方
        $admin_logic_1652254594 = tpSetting('syn.admin_logic_1652254594', [], 'cn');
        if (empty($admin_logic_1652254594)) {
            try {
                $data = tpSetting("OpenMinicode.conf_wechat", [], $this->main_lang);
                if (empty($data)) {
                    $wechat_login_config = getUsersConfigData('wechat.wechat_login_config');
                    $login_config = unserialize($wechat_login_config);
                    if (!empty($login_config)) {
                        $data = [];
                        $data['appid'] = !empty($login_config['appid']) ? trim($login_config['appid']) : '';
                        $data['appsecret'] = !empty($login_config['appsecret']) ? trim($login_config['appsecret']) : '';
                        $data['wechat_name'] = !empty($login_config['wechat_name']) ? trim($login_config['wechat_name']) : '';
                        $data['wechat_pic'] = !empty($login_config['wechat_pic']) ? trim($login_config['wechat_pic']) : '';
                        tpSetting('OpenMinicode', ['conf_wechat' => json_encode($data)], $this->main_lang);
                    }
                }
            } catch (\Exception $e) {
                
            }
            tpSetting('syn', ['admin_logic_1652254594'=>1], 'cn');
        }

        // 兼容指定栏目旧数据 升级到1.5.9才需要兼容 大黃 开始
        $designated_column_1657069673 = tpSetting('syn.designated_column_1657069673');
        if (empty($designated_column_1657069673)){
            $arctype_channelfield_ids = Db::name('channelfield')->where(['channel_id'=>-99,'ifsystem'=>0])->column('id');
            if (!empty($arctype_channelfield_ids)){
                $inser_channelfield_bind = [];
                foreach ($arctype_channelfield_ids as $v){
                    $inser_channelfield_bind[] = [
                        'field_id' => $v,
                        'add_time' => getTime(),
                        'update_time' => getTime(),
                    ];
                }
                Db::name('channelfield_bind')->insertAll($inser_channelfield_bind);
            }
            tpSetting('syn', ['designated_column_1657069673'=>1]);

        }
        // 兼容指定栏目旧数据 升级到1.5.9才需要兼容 大黃 结束

        // 删除文档附表的数据表缓存文件
        $admin_logic_1652771782 = tpSetting('syn.admin_logic_1652771782', [], 'cn');
        if (empty($admin_logic_1652771782)) {
            try {
                @unlink('./data/schema/ey_arctype.php');
            } catch (\Exception $e) {
                
            }
            tpSetting('syn', ['admin_logic_1652771782'=>1], 'cn');
        }

        $this->admin_logic_1616123195();
    }
    /*
    * 初始化原来的菜单栏目
    */
    public function initialize_admin_menu(){
        $total = Db::name("admin_menu")->count();
        if (empty($total)){
            $menuArr = getAllMenu();
            $insert_data = [];
            foreach ($menuArr as $key => $val){
                foreach ($val['child'] as $nk=>$nrr) {
                    $sort_order = 100;
                    $is_switch = 1;
                    if ($nrr['id'] == 2004){
                        $sort_order = 10000;
                        $is_switch = 0;
                    }
                    $insert_data[] = [
                        'menu_id' => $nrr['id'],
                        'title' => $nrr['name'],
                        'controller_name' => $nrr['controller'],
                        'action_name' => $nrr['action'],
                        'param' => !empty($nrr['param']) ? $nrr['param'] : '',
                        'is_menu' => $nrr['is_menu'],
                        'is_switch' => $is_switch,
                        'icon' =>  $nrr['icon'],
                        'sort_order' => $sort_order,
                        'add_time' => getTime(),
                        'update_time' => getTime()
                    ];
                }
            }
            Db::name("admin_menu")->insertAll($insert_data);
        }
    }

    /**
     * 补充账号注册的短信模板的数据(v1.6.1节点去掉)
     */
    private function admin_logic_1616123195()
    {
        $syn_admin_logic_1616123195 = tpSetting('syn.syn_admin_logic_1616123195', [], 'cn');
        if (empty($syn_admin_logic_1616123195)) {
            try{
                Db::name('sms_template')->where(['send_scene'=>['IN', [2,7]]])->delete();
                /*多语言*/
                if (is_language()) {
                    $saveData = Db::name('sms_template')->field('tpl_id', true)->where(['send_scene'=>0])->select();
                    if (!empty($saveData)) {
                        $addData = [];
                        foreach ($saveData as $key => $val) {
                            $val['tpl_title'] = '账号登录';
                            $val['send_scene'] = 2;
                            $val['sms_sign'] = '';
                            $val['sms_tpl_code'] = '';
                            if (1 == $val['sms_type']) {
                                $val['tpl_content'] = '验证码为 ${content} ，请在30分钟内输入验证。';
                            } else if (2 == $val['sms_type']) {
                                $val['tpl_content'] = '验证码为 {1} ，请在30分钟内输入验证。';
                            }
                            $addData[] = $val;

                            $val['tpl_title'] = '留言验证';
                            $val['send_scene'] = 7;
                            $addData[] = $val;
                        }
                        Db::name('sms_template')->insertAll($addData);
                    }
                }
                else { // 单语言
                    $saveData = Db::name('sms_template')->field('tpl_id', true)->where(['send_scene'=>0])->select();
                    if (!empty($saveData)) {
                        $addData = [];
                        foreach ($saveData as $key => $val) {
                            $val['tpl_title'] = '账号登录';
                            $val['send_scene'] = 2;
                            $val['sms_sign'] = '';
                            $val['sms_tpl_code'] = '';
                            if (1 == $val['sms_type']) {
                                $val['tpl_content'] = '验证码为 ${content} ，请在30分钟内输入验证。';
                            } else if (2 == $val['sms_type']) {
                                $val['tpl_content'] = '验证码为 {1} ，请在30分钟内输入验证。';
                            }
                            $addData[] = $val;

                            $val['tpl_title'] = '留言验证';
                            $val['send_scene'] = 7;
                            $addData[] = $val;
                        }
                        Db::name('sms_template')->insertAll($addData);
                    }
                }
                /*--end*/
                tpSetting('syn', ['syn_admin_logic_1616123195'=>1], 'cn');
            }catch(\Exception $e){}
        }
    }

    /**
     * 将内容字段改成utf8mb4编码类型(v1.6.1节点去掉)
     */
    // private function admin_logic_1650964651()
    // {
    //     $syn_admin_logic_1650964651 = Db::name('setting')->where(['name'=>'syn_admin_logic_1650964651', 'inc_type'=>'syn', 'lang'=>'cn'])->value('value');
    //     if (empty($syn_admin_logic_1650964651)) {
    //         // 升级数据库结构
    //         try {
    //             $Prefix = config('database.prefix');
    //             // 文章模型
    //             $sql = "ALTER TABLE `{$Prefix}article_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}article_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 下载模型
    //             $sql = "ALTER TABLE `{$Prefix}download_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}download_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 图集模型
    //             $sql = "ALTER TABLE `{$Prefix}images_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}images_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 视频模型
    //             $sql = "ALTER TABLE `{$Prefix}media_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}media_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 产品模型
    //             $sql = "ALTER TABLE `{$Prefix}product_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}product_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 单页模型
    //             $sql = "ALTER TABLE `{$Prefix}single_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `typeid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}single_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);
    //             // 专题模型
    //             $sql = "ALTER TABLE `{$Prefix}special_content` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容详情' AFTER `aid`;";
    //             @Db::execute($sql);
    //             $sql = "ALTER TABLE `{$Prefix}special_content` MODIFY COLUMN `content_ey_m`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '手机端内容详情' AFTER `content`;";
    //             @Db::execute($sql);

    //             tpSetting('syn', ['syn_admin_logic_1650964651'=>1], 'cn');
    //         }catch(\Exception $e){}
    //     }
    // }
    public function admin_logic_1658220528(){
        $syn_admin_logic_1658220528 = Db::name('setting')->where(['name'=>'syn_admin_logic_1658220528', 'inc_type'=>'syn', 'lang'=>'cn'])->value('value');
        if (empty($syn_admin_logic_1658220528)){
            $Prefix = config('database.prefix');
            $isTable = Db::query('SHOW TABLES LIKE \''.$Prefix.'shop_order_unified_pay\'');
            if (empty($isTable)) {
                $tableSql = <<<EOF
CREATE TABLE `{$Prefix}shop_order_unified_pay` (
`unified_id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统一支付订单ID' ,
`unified_number`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '统一支付订单编号' ,
`unified_amount`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '统一支付订单应付款金额' ,
`users_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员ID' ,
`order_ids`  text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '合并支付的订单ID，serialize序列化存储' ,
`pay_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '统一支付订单状态：0未付款，1已付款' ,
`pay_time`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '统一支付订单时间' ,
`pay_name`  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '统一支付订单方式名称' ,
`wechat_pay_type`  varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '微信支付时，标记使用的支付类型（扫码支付，微信内部，微信H5页面）' ,
`add_time`  int(11) UNSIGNED NULL DEFAULT 0 COMMENT '下单时间' ,
`update_time`  int(11) UNSIGNED NULL DEFAULT 0 COMMENT '更新时间' ,
PRIMARY KEY (`unified_id`),
INDEX `users_id` (`users_id`) USING BTREE 
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
CHECKSUM=0
ROW_FORMAT=Dynamic
DELAY_KEY_WRITE=0
;
EOF;
                $r = @Db::execute($tableSql);
                if ($r !== false) {
                    schemaTable('shop_order_unified_pay');
                }
            }
            $archivesTableInfo = Db::query("SHOW COLUMNS FROM {$Prefix}archives");
            $archivesTableInfo = get_arr_column($archivesTableInfo, 'Field');
            if (!empty($archivesTableInfo) && !in_array('merchant_id', $archivesTableInfo)){
                $sql = "ALTER TABLE `{$Prefix}archives` ADD COLUMN `merchant_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '多商家ID' AFTER `attrlist_id`;";
                @Db::execute($sql);
            }
            if (!empty($archivesTableInfo) && !in_array('free_shipping', $archivesTableInfo)){
                $sql = "ALTER TABLE `{$Prefix}archives` ADD COLUMN `free_shipping`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品是否包邮(1包邮(免运费)  0跟随系统)' AFTER `merchant_id`;";
                @Db::execute($sql);
            }
            $shop_orderTableInfo = Db::query("SHOW COLUMNS FROM {$Prefix}shop_order");
            $shop_orderTableInfo = get_arr_column($shop_orderTableInfo, 'Field');
            if (!empty($shop_orderTableInfo) && !in_array('merchant_id', $shop_orderTableInfo)){
                $sql = "ALTER TABLE `{$Prefix}shop_order` ADD COLUMN `merchant_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '多商家ID' AFTER `users_id`;";
                @Db::execute($sql);
            }
            $shop_order_serviceTableInfo = Db::query("SHOW COLUMNS FROM {$Prefix}shop_order_service");
            $shop_order_serviceTableInfo = get_arr_column($shop_order_serviceTableInfo, 'Field');
            if (!empty($shop_order_serviceTableInfo) && !in_array('merchant_id', $shop_order_serviceTableInfo)){
                $sql = "ALTER TABLE `{$Prefix}shop_order_service` ADD COLUMN `merchant_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '多商家ID' AFTER `users_id`;";
                @Db::execute($sql);
            }
            tpSetting('syn', ['syn_admin_logic_1658220528'=>1], 'cn');
        }

        $syn_admin_logic_1658799138 = Db::name('setting')->where(['name'=>'syn_admin_logic_1658799138', 'inc_type'=>'syn', 'lang'=>'cn'])->value('value');
        if (empty($syn_admin_logic_1658799138)){
            $Prefix = config('database.prefix');
            $isTable = Db::query('SHOW TABLES LIKE \''.$Prefix.'product_custom_param\'');
            if (empty($isTable)) {
                $tableSql = <<<EOF
CREATE TABLE `{$Prefix}product_custom_param` (
`param_id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '参数ID' ,
`aid`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参数ID' ,
`param_name`  varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '参数名称' ,
`param_value`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '参数值' ,
`sort_order`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '属性排序' ,
`add_time`  int(11) NOT NULL DEFAULT 0 COMMENT '新增时间' ,
`update_time`  int(11) NOT NULL DEFAULT 0 COMMENT '更新时间' ,
PRIMARY KEY (`param_id`),
INDEX `aid` (`aid`) USING BTREE 
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
CHECKSUM=0
ROW_FORMAT=Dynamic
DELAY_KEY_WRITE=0
;
EOF;
                $r = @Db::execute($tableSql);
                if ($r !== false) {
                    schemaTable('product_custom_param');
                }
            }
            tpSetting('syn', ['syn_admin_logic_1658799138'=>1], 'cn');
        }
    }


}
