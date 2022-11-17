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

namespace app\home\controller;

use think\Db;
use think\Config;

class View extends Base
{
    // 模型标识
    public $nid = '';
    // 模型ID
    public $channel = '';
    // 模型名称
    public $modelName = '';

    public function _initialize()
    {
        //运营状态下，检验阅读权限
        if(Config::get('app_debug') != true){
            $aid = input('param.aid/d');
            $map = [];
            if (!is_numeric($aid) || strval(intval($aid)) !== strval($aid)) {
                $map = array('a.htmlfilename' => $aid);
            } else {
                $map = array('a.aid' => intval($aid));
            }
            $map['a.is_del'] = 0; // 回收站功能
            $field        = 'a.aid,a.arcrank,a.users_id, a.typeid, a.channel, b.nid, b.ctl_name,c.typearcrank,c.dirname';
            $archivesInfo = Db::name('archives')->field($field)
                ->alias('a')
                ->join('channeltype b', 'a.channel = b.id', 'LEFT')
                ->join('arctype c', 'a.typeid = c.id', 'LEFT')
                ->where($map)
                ->find();
            $archivesInfo['arcurl'] = arcurl('home/'.$archivesInfo['ctl_name'].'/view', $archivesInfo, true, true);
            $users_id = (int)session('users_id');
            $emptyhtml = $this->check_arcrank($archivesInfo,$users_id);
        }
        parent::_initialize();
    }

    /**
     * 内容页
     */
    public function index($aid = '')
    {
        $seo_pseudo = config('ey_config.seo_pseudo');
        /*URL上参数的校验*/
        if (3 == $seo_pseudo)
        {
            if (stristr($this->request->url(), '&c=View&a=index&')) {
                abort(404, '页面不存在');
            }
        }
        else if (1 == $seo_pseudo || (2 == $seo_pseudo && isMobile()))
        {
            $seo_dynamic_format = config('ey_config.seo_dynamic_format');
            if (1 == $seo_pseudo && 2 == $seo_dynamic_format && stristr($this->request->url(), '&c=View&a=index&')) {
                abort(404, '页面不存在');
            }
        }
        /*--end*/

        $map = [];
        if (!is_numeric($aid) || strval(intval($aid)) !== strval($aid)) {
            $map = array('a.htmlfilename' => $aid);
        } else {
            $map = array('a.aid' => intval($aid));
        }
        $map['a.is_del'] = 0; // 回收站功能
        // 多城市站点
        $field        = 'a.aid, a.typeid, a.channel, a.users_price, a.users_free, a.province_id, a.city_id, a.area_id, b.nid, b.ctl_name, c.level_id, c.level_name, c.level_value';
        $archivesInfo = Db::name('archives')->field($field)
            ->alias('a')
            ->join('__CHANNELTYPE__ b', 'a.channel = b.id', 'LEFT')
            ->join('__USERS_LEVEL__ c', 'a.arc_level_id = c.level_id', 'LEFT')
            ->where($map)
            ->find();
        if (empty($archivesInfo) || !in_array($archivesInfo['channel'], config('global.allow_release_channel'))) {
            abort(404, '页面不存在');
        }

        /*校验多城市站点*/
        if (config('city_switch_on')) {
            $site = get_home_site();
            if (empty($site)) {
                if (!empty($archivesInfo['province_id']) || !empty($archivesInfo['city_id']) || !empty($archivesInfo['area_id'])) { // 非全国文档
                    abort(404, '页面不存在');
                }
            } else if (!empty($site)) {
                if (empty($archivesInfo['province_id']) && empty($archivesInfo['city_id']) && empty($archivesInfo['area_id'])) { // 全国文档
                    abort(404, '页面不存在');
                }
                $siteInfo = Db::name('citysite')->where(['domain'=>$site])->find();
                if (!empty($siteInfo)) {
                    if (!empty($archivesInfo['area_id'])) {
                        if ($archivesInfo['area_id'] != $siteInfo['id']) {
                            abort(404, '页面不存在');
                        }
                    } else if (!empty($archivesInfo['city_id'])) {
                        if ($archivesInfo['city_id'] != $siteInfo['id']) {
                            abort(404, '页面不存在');
                        }
                    } else if (!empty($archivesInfo['province_id'])) {
                        if ($archivesInfo['province_id'] != $siteInfo['id']) {
                            abort(404, '页面不存在');
                        }
                    }
                }
            }
        }

        $aid             = $archivesInfo['aid'];
        $this->nid       = $archivesInfo['nid'];
        $this->channel   = $archivesInfo['channel'];
        $this->modelName = $archivesInfo['ctl_name'];

        $result = model($this->modelName)->getInfo($aid);
        // 若是管理员则不受限制
        // if (session('?admin_id')) {
        //     if ($result['arcrank'] == -1 && $result['users_id'] != session('users_id')) {
        //         $this->success('待审核稿件，你没有权限阅读！');
        //     }
        // }
        // 外部链接跳转
        if ($result['is_jump'] == 1) {
            header('Location: ' . $result['jumplinks']);
            exit;
        }
        /*--end*/

        $tid         = $result['typeid'];
        $arctypeInfo = model('Arctype')->getInfo($tid);
        /*自定义字段的数据格式处理*/
        $arctypeInfo = $this->fieldLogic->getTableFieldList($arctypeInfo, config('global.arctype_channel_id'));
        /*--end*/
        if (!empty($arctypeInfo)) {

            /*URL上参数的校验*/
            if (3 == $seo_pseudo) {
                $dirname            = input('param.dirname/s');
                $dirname2           = '';
                $seo_rewrite_format = config('ey_config.seo_rewrite_format');
                if (1 == $seo_rewrite_format) {
                    $toptypeRow  = model('Arctype')->getAllPid($tid);
                    $toptypeinfo = current($toptypeRow);
                    $dirname2    = $toptypeinfo['dirname'];
                } else if (2 == $seo_rewrite_format) {
                    $dirname2 = $arctypeInfo['dirname'];
                } else if (3 == $seo_rewrite_format) {
                    $dirname2 = $arctypeInfo['dirname'];
                }
                if ($dirname != $dirname2) {
                    abort(404, '页面不存在');
                }
            }
            /*--end*/

            // 是否有子栏目，用于标记【全部】选中状态
            $arctypeInfo['has_children'] = model('Arctype')->hasChildren($tid);
            // 文档模板文件，不指定文档模板，默认以栏目设置的为主
            empty($result['tempview']) && $result['tempview'] = $arctypeInfo['tempview'];

            /*给没有type前缀的字段新增一个带前缀的字段，并赋予相同的值*/
            foreach ($arctypeInfo as $key => $val) {
                if (!preg_match('/^type/i', $key)) {
                    $key_new = 'type' . $key;
                    !array_key_exists($key_new, $arctypeInfo) && $arctypeInfo[$key_new] = $val;
                }
            }
            /*--end*/
        } else {
            abort(404, '页面不存在');
        }

        $result = array_merge($arctypeInfo, $result);

        // 文档链接
        $result['arcurl'] = $result['pageurl'] = $result['pageurl_m'] = '';
        if ($result['is_jump'] != 1) {
            $result['arcurl'] = arcurl('home/'.$this->modelName.'/view', $result, true, true);
            $result['pageurl'] = $this->request->url(true);
            $result['pageurl_m'] = pc_to_mobile_url($result['pageurl'], $result['typeid'], $result['aid']); // 获取当前页面对应的移动端URL
        }
        /*--end*/

        // 移动端域名
        $result['mobile_domain'] = '';
        if (!empty($this->eyou['global']['web_mobile_domain_open']) && !empty($this->eyou['global']['web_mobile_domain'])) {
            $result['mobile_domain'] = $this->eyou['global']['web_mobile_domain'] . '.' . $this->request->rootDomain(); 
        }

        $result['seo_title']       = set_arcseotitle($result['title'], $result['seo_title'], $result['typename'], $result['typeid'], $this->eyou['site']);
        $result['seo_description'] = checkStrHtml($result['seo_description']);
        $result['tags'] = !empty($result['tags']['tag_arr']) ? $result['tags']['tag_arr'] : '';
        $result['litpic'] = handle_subdir_pic($result['litpic']); // 支持子目录
        $result = view_logic($aid, $this->channel, $result, true); // 模型对应逻辑
        $result = $this->fieldLogic->getChannelFieldList($result, $this->channel); // 自定义字段的数据格式处理
        //移动端详情
        if (isMobile() && !empty($result['content_ey_m'])){
            $result['content'] = $result['content_ey_m'];
        }
        if (!empty($result['users_id'])){
            $users_where['a.users_id'] = $result['users_id'];
        }elseif (!empty($result['admin_id'])){
            $users_where['a.admin_id'] = $result['admin_id'];
        }else {
            $users_where['a.admin_id'] = ['>',0];
        }
        $users = Db::name('users')->alias('a')->field('a.username,a.nickname,a.head_pic,b.level_name,b.level_value')->where($users_where)->join('users_level b','a.level = b.level_id','left')->find();
        if (!empty($users)) {
            $users['head_pic']  = get_default_pic($users['head_pic']);
            empty($users['nickname']) && $users['nickname'] = $users['username'];
        }

        $eyou = array(
            'type'  => $arctypeInfo,
            'field' => $result,
            'users' => $users,
        );

        $this->eyou = array_merge($this->eyou, $eyou);
        $this->assign('eyou', $this->eyou);

        /*模板文件*/
        $viewfile = !empty($result['tempview'])
            ? str_replace('.' . $this->view_suffix, '', $result['tempview'])
            : 'view_' . $this->nid;
        /*--end*/

        if (config('city_switch_on') && !empty($this->home_site)) { // 多站点内置模板文件名
            $viewfilepath = TEMPLATE_PATH . $this->theme_style_path . DS . $viewfile . "_{$this->home_site}." . $this->view_suffix;
            if (file_exists($viewfilepath)) {
                $viewfile .= "_{$this->home_site}";
            }
        } else if (config('lang_switch_on') && !empty($this->home_lang)) { // 多语言内置模板文件名
            $viewfilepath = TEMPLATE_PATH . $this->theme_style_path . DS . $viewfile . "_{$this->home_lang}." . $this->view_suffix;
            if (file_exists($viewfilepath)) {
                $viewfile .= "_{$this->home_lang}";
            }
        }

        $users_id = (int)session('users_id');
        $emptyhtml = $this->check_arcrank($this->eyou['field'],$users_id);
//        if ($this->eyou['field']['arcrank'] > 0 || $this->eyou['field']['typearcrank'] > 0) { // 若需要会员权限则执行
//            if (empty($users_id)) {
//                $url = url('user/Users/login');
//                if (stristr($url, '?')) {
//                    $url = $url."&referurl=".urlencode($this->eyou['field']['arcurl']);
//                } else {
//                    $url = $url."?referurl=".urlencode($this->eyou['field']['arcurl']);
//                }
//                $this->redirect($url);
//            }
//            $msg = action('api/Ajax/get_arcrank', ['aid' => $aid, 'vars' => 1]);
//            if (true !== $msg) {
//                $this->error($msg);
//            }
//        } else if ($this->eyou['field']['arcrank'] <= -1) {
//            /*待审核稿件，是否有权限查阅的处理，登录的管理员可查阅*/
//            $admin_id = input('param.admin_id/d');
//            if ( (session('?admin_id') && !empty($admin_id)) || ($this->eyou['field']['users_id'] > 0 && $this->eyou['field']['users_id'] == $users_id) ) {
//
//            }
//            else if (empty($users_id) && empty($admin_id)) {
//                abort(404);
//            }
//            else {
//                $emptyhtml = <<<EOF
//<!DOCTYPE html>
//<html>
//    <head>
//        <title>{$this->eyou['field']['seo_title']}</title>
//        <meta name="description" content="{$this->eyou['field']['seo_description']}" />
//        <meta name="keywords" content="{$this->eyou['field']['seo_keywords']}" />
//    </head>
//    <body>
//    </body>
//</html>
//EOF;
//            }
//            /*end*/
//        }

        if (!empty($emptyhtml)) {
            /*尝试写入静态缓存*/
            write_html_cache($emptyhtml, $result);
            /*--end*/
            return $this->display($emptyhtml);
        } else {
            return $this->fetch(":{$viewfile}");
        }
    }
    /*
     * 判断阅读权限
     */
    private function check_arcrank($eyou_field,$users_id){
        $emptyhtml = "";
        if ($eyou_field['arcrank'] > 0 || $eyou_field['typearcrank'] > 0) { // 若需要会员权限则执行
            if (empty($users_id)) {
                $url = url('user/Users/login');
                if (stristr($url, '?')) {
                    $url = $url."&referurl=".urlencode($eyou_field['arcurl']);
                } else {
                    $url = $url."?referurl=".urlencode($eyou_field['arcurl']);
                }
                $this->redirect($url);
            }
            $msg = action('api/Ajax/get_arcrank', ['aid' => $eyou_field['aid'], 'vars' => 1]);
            if (true !== $msg) {
                $this->error($msg);
            }
        } else if ($eyou_field['arcrank'] <= -1) {
            /*待审核稿件，是否有权限查阅的处理，登录的管理员可查阅*/
            $admin_id = input('param.admin_id/d');
            if ( (session('?admin_id') && !empty($admin_id)) || ($eyou_field['users_id'] > 0 && $eyou_field['users_id'] == $users_id) ) {

            }
            else if (empty($users_id) && empty($admin_id)) {
                abort(404);
            }
            else {
                $emptyhtml = <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <title>{$eyou_field['seo_title']}</title>
        <meta name="description" content="{$eyou_field['seo_description']}" />
        <meta name="keywords" content="{$eyou_field['seo_keywords']}" />
    </head>
    <body>
    </body>
</html>
EOF;
            }
            /*end*/
        }

        return $emptyhtml;
    }
    /**
     * 下载文件
     */
    public function downfile()
    {
        $file_id = input('param.id/d', 0);
        $uhash   = input('param.uhash/s', '');

        if (empty($file_id) || empty($uhash)) {
            $this->error('下载地址出错！');
            exit;
        }

        clearstatcache();

        // 查询信息
        $map    = array(
            'a.file_id' => $file_id,
            'a.uhash'   => $uhash,
        );
        $result = Db::name('download_file')
            ->alias('a')
            ->field('a.*,b.arc_level_id,b.restric_type,b.users_price,b.no_vip_pay')
            ->join('__ARCHIVES__ b', 'a.aid = b.aid', 'LEFT')
            ->where($map)
            ->find();

        $file_url_gbk = iconv("utf-8", "gb2312//IGNORE", $result['file_url']);
        $file_url_gbk = preg_replace('#^(/[/\w]+)?(/public/upload/soft/|/uploads/soft/)#i', '$2', $file_url_gbk);
        if (empty($result) || (!is_http_url($result['file_url']) && !file_exists('.' . $file_url_gbk))) {
            $this->error('下载文件不存在！');
            exit;
        }

        // 下载次数限制
        $this->down_num_access($result['aid']);
        
        //安装下载模型付费插件  走新逻辑 大黄
        $channelData = Db::name('channeltype')->where(['nid'=>'download','status'=>1])->value('data');
        if (!empty($channelData)) $channelData = json_decode($channelData,true);
        if (!empty($channelData['is_download_pay'])){
            if ($result['restric_type'] > 0) {
                $UsersData = session('users');
                if (empty($UsersData['users_id'])) {
                    $this->error('请登录后下载！', null, ['is_login' => 0, 'url' => url('user/Users/login')]);
                    exit;
                }
            }

            if ($result['restric_type'] == 1) {// 付费
                $order = Db::name('download_order')->where(['users_id' => $UsersData['users_id'], 'order_status' => 1, 'product_id' => $result['aid']])->find();
                if (empty($order)) {
                    $msg = '文件购买后可下载，请先购买！';
                    $this->error($msg, null, ['url' => url('user/Download/buy'), 'need_buy' => 1, 'aid' => $result['aid']]);
                    exit;
                }
            } elseif ($result['restric_type'] == 2) {//会员专享
                // 查询会员信息
                $users = Db::name('users')
                    ->alias('a')
                    ->field('a.users_id,b.level_value,b.level_name')
                    ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                    ->where(['a.users_id' => $UsersData['users_id']])
                    ->find();
                // 查询下载所需等级值
                $file_level = Db::name('archives')
                    ->alias('a')
                    ->field('b.level_value,b.level_name')
                    ->join('__USERS_LEVEL__ b', 'a.arc_level_id = b.level_id', 'LEFT')
                    ->where(['a.aid' => $result['aid']])
                    ->find();
                if ($users['level_value'] < $file_level['level_value']) {//未达到会员级别
                    if ($result['no_vip_pay'] == 1){ //会员专享 开启 非会员付费下载
                        $order = Db::name('download_order')->where(['users_id' => $UsersData['users_id'], 'order_status' => 1, 'product_id' => $result['aid']])->find();
                        if (empty($order)) {
                            $msg = '文件为【' . $file_level['level_name'] . '】免费下载，您当前为【' . $users['level_name'] . '】，可付费购买！';
                            $this->error($msg, null, ['url' => url('user/Download/buy'), 'need_buy' => 1, 'aid' => $result['aid']]);
                            exit;
                        }
                    }else{
                        $msg = '文件为【' . $file_level['level_name'] . '】可下载，您当前为【' . $users['level_name'] . '】，请先升级！';
                        $this->error($msg, null, ['url' => url('user/Level/level_centre')]);
                        exit;
                    }
                }
            } elseif ($result['restric_type'] == 3) {//会员付费
                // 查询会员信息
                $users = Db::name('users')
                    ->alias('a')
                    ->field('a.users_id,b.level_value,b.level_name')
                    ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                    ->where(['a.users_id' => $UsersData['users_id']])
                    ->find();
                // 查询下载所需等级值
                $file_level = Db::name('archives')
                    ->alias('a')
                    ->field('b.level_value,b.level_name')
                    ->join('__USERS_LEVEL__ b', 'a.arc_level_id = b.level_id', 'LEFT')
                    ->where(['a.aid' => $result['aid']])
                    ->find();
                if ($users['level_value'] < $file_level['level_value']) {
                    $msg = '文件为【' . $file_level['level_name'] . '】购买可下载，您当前为【' . $users['level_name'] . '】，请先升级后购买！';
                    $this->error($msg, null, ['url' => url('user/Level/level_centre')]);
                    exit;
                }
                $order = Db::name('download_order')->where(['users_id' => $UsersData['users_id'], 'order_status' => 1, 'product_id' => $result['aid']])->find();
                if (empty($order)) {
                    $msg = '文件为【' . $file_level['level_name'] . '】购买可下载，您当前为【' . $users['level_name'] . '】，请先购买！';
                    $this->error($msg, null, ['url' => url('user/Level/level_centre'), 'need_buy' => 1, 'aid' => $result['aid']]);
                    exit;
                }
            }
        }else{
            // 判断会员信息
            if (0 < intval($result['arc_level_id'])) {
                //走下载模型会员限制下载旧版逻辑
                $UsersData = session('users');
                if (empty($UsersData['users_id'])) {
                    $this->error('请登录后下载！' . $result['arc_level_id'], null, ['is_login' => 0, 'url' => url('user/Users/login')]);
                    exit;
                } else {
                    /*判断会员是否可下载该文件--2019-06-21 陈风任添加*/
                    // 查询会员信息
                    $users = Db::name('users')
                        ->alias('a')
                        ->field('a.users_id,b.level_value,b.level_name')
                        ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                        ->where(['a.users_id' => $UsersData['users_id']])
                        ->find();
                    // 查询下载所需等级值
                    $file_level = Db::name('archives')
                        ->alias('a')
                        ->field('b.level_value,b.level_name')
                        ->join('__USERS_LEVEL__ b', 'a.arc_level_id = b.level_id', 'LEFT')
                        ->where(['a.aid' => $result['aid']])
                        ->find();
                    if ($users['level_value'] < $file_level['level_value']) {
                        $msg = '文件为【' . $file_level['level_name'] . '】可下载，您当前为【' . $users['level_name'] . '】，请先升级！';
                        $this->error($msg, null, ['url' => url('user/Level/level_centre')]);
                        exit;
                    }
                    /*--end*/
                }
            }
        }

        // 外部下载链接
        if (is_http_url($result['file_url']) || !empty($result['is_remote'])) {
            if ($result['uhash'] != md5($result['file_url'])) {
                $this->error('下载地址出错！');
            }
            // 记录下载次数(限制会员级别下载的文件才记录下载次数)
            if (0 < intval($result['arc_level_id'])){
                $this->download_log($result['file_id'], $result['aid']);
            }

            $result['file_url'] = htmlspecialchars_decode($result['file_url']);
            if (IS_AJAX) {
                $this->success('正在跳转中……', $result['file_url']);
            } else {
                $this->redirect($result['file_url']);
                exit;
            }
        } 
        // 本站链接
        else
        {
            if (md5_file('.' . $file_url_gbk) != $result['md5file']) {
                $this->error('下载文件包已损坏！');
            }

            // 记录下载次数
            $this->download_log($result['file_id'], $result['aid']);

            $uhash_mch = mchStrCode($uhash);
            $url       = $this->root_dir . "/index.php?m=home&c=View&a=download_file&file_id={$file_id}&uhash={$uhash_mch}";
            cookie($file_id.$uhash_mch, 1);
            if (IS_AJAX) {
                $this->success('开始下载中……', $url);
            } else {
                $url = $this->request->domain() . $url;
                $this->redirect($url);
                exit;
            }
        }
    }

    /**
     * 本地附件下载
     */
    public function download_file()
    {
        $file_id = input('param.file_id/d');
        $uhash_mch   = input('param.uhash/s', '');
        $uhash   = mchStrCode($uhash_mch, 'DECODE');
        $map     = array(
            'file_id' => $file_id,
        );
        $result  = Db::name('download_file')->field('aid,file_url,file_mime,file_name,uhash')->where($map)->find();
        if (!empty($result['uhash']) && $uhash != $result['uhash']) {
            $this->error('下载地址出错！');
        }

        $value = cookie($file_id.$uhash_mch);
        if (empty($value)) {
            $result = Db::name('archives')
                ->field("b.*, a.*")
                ->alias('a')
                ->join('__ARCTYPE__ b', 'b.id = a.typeid', 'LEFT')
                ->where(['a.aid'=>$result['aid']])
                ->find();
            $arcurl = arcurl('home/Download/view', $result);
            $this->error('下载地址已失效，请在下载详情页进行下载！', $arcurl);
        } else {
            cookie($file_id.$uhash_mch, null);
        }

        download_file($result['file_url'], $result['file_mime'], $result['file_name']);
        exit;
    }

    /**
     * 会员每天下载次数的限制
     */
    private function down_num_access($aid)
    {
        /*是否安装启用下载次数限制插件*/
        if (is_dir('./weapp/Downloads/')) {
            $DownloadsRow = model('Weapp')->getWeappList('Downloads');
            if (1 != $DownloadsRow['status']) {
                return true;
            }
        } else {
            return true;
        }
        /*end*/

        $users = session('users');
        if (empty($users['users_id'])) {
            $this->error('请登录后下载！');
        }

        $level_info = Db::name('users_level')->field('level_name,down_count')->where(['level_id' => $users['level']])->find();
        if (empty($level_info)) {
            $this->error('当前会员等级不存在！');
        }

        $begin_mtime = strtotime(date('Y-m-d 00:00:00'));
        $end_mtime   = strtotime(date('Y-m-d 23:59:59'));
        $downNum     = Db::name('download_log')->where([
            'users_id' => $users['users_id'],
            'add_time' => ['between', [$begin_mtime, $end_mtime]],
            'aid'      => ['NEQ', $aid],
        ])->group('aid')->count('aid');
        if (intval($level_info['down_count']) <= intval($downNum)) {
            $msg = "{$level_info['level_name']}每天最多下载{$level_info['down_count']}个！";
            $this->error($msg);
        }
    }

    /**
     * 记录下载次数（重复下载不做记录，游客可重复记录）
     */
    private function download_log($file_id = 0, $aid = 0)
    {
        try {
            $users_id = session('users_id');
            $users_id = intval($users_id);

            $counts = Db::name('download_log')->where([
                'file_id'  => $file_id,
                'aid'      => $aid,
                'users_id' => $users_id,
            ])->count();
            if (empty($users_id) || empty($counts)) {
                $saveData = [
                    'users_id' => $users_id,
                    'aid'      => $aid,
                    'file_id'  => $file_id,
                    'ip'       => clientIP(),
                    'add_time' => getTime(),
                ];
                $r        = Db::name('download_log')->insertGetId($saveData);
                if ($r !== false) {
                    Db::name('download_file')->where(['file_id' => $file_id])->setInc('downcount');
                    Db::name('archives')->where(['aid' => $aid])->setInc('downcount');
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * 获取播放视频路径（仅限于早期第一套和第二套使用）
     */
    public function pay_video_url()
    {
        $file_id = input('param.id/d', 0);
        $uhash   = input('param.uhash/s', '');
        if (empty($file_id) || empty($uhash)) $this->error('视频播放链接出错！');

        // 查询信息
        $map = array(
            'a.file_id' => $file_id,
            'a.uhash' => $uhash
        );
        $result = Db::name('media_file')
            ->alias('a')
            ->field('a.*, b.arc_level_id, b.users_price, b.users_free')
            ->join('__ARCHIVES__ b', 'a.aid = b.aid', 'LEFT')
            ->where($map)
            ->find();
        $result['txy_video_id'] = '';
        if (!empty($result['file_url'])) {
            $FileUrl = explode('txy_video_', $result['file_url']);
            if (empty($FileUrl[0]) && !empty($FileUrl[1])) {
                // 腾讯云视频ID
                $result['txy_video_id'] = $FileUrl[1];
            } else if (!empty($FileUrl[0]) && empty($FileUrl[1])) {
                // 原本的逻辑
                if (preg_match('#^(/[\w]+)?(/uploads/media/)#i', $result['file_url'])) {
                    $file_url = preg_replace('#^(/[\w]+)?(/uploads/media/)#i', '$2', $result['file_url']);
                } else {
                    $file_url = preg_replace('#^(' . $this->root_dir . ')?(/)#i', '$2', $result['file_url']);
                }
                if (empty($result) || (!is_http_url($result['file_url']) && !file_exists('.' . $file_url))) {
                    $this->error('视频文件不存在！');
                }
            } else {
                $this->error('视频文件不存在！');
            }
        }

        $UsersData = GetUsersLatestData();
        $UsersID = !empty($UsersData['users_id']) ? intval($UsersData['users_id']) : 0;

        $upVip = "window.location.href = '" . url('user/Level/level_centre') . "'";
        $data['onclick'] = "if (document.getElementById('ey_login_id_1609665117')) {\$('#ey_login_id_1609665117').trigger('click');}else{window.location.href = '" . url('user/Users/login') . "';}";
        $data['button']  = '点击登录！';
        $data['users_id'] = $UsersID;

        $arc_level_id = !empty($result['arc_level_id']) ? intval($result['arc_level_id']) : 0;
        if (!empty($arc_level_id)) {
            // 未登录则提示
            if (empty($UsersID)) $this->error('请先登录！', url('user/Users/login'), $data);
        }

        if (empty($result['gratis'])) {
            /*是否需要付费*/
            if (0 < $result['users_price'] && empty($result['users_free'])) {
                $Paid = 0; // 未付费
                $where = [
                    'users_id' => $UsersID,
                    'product_id' => $result['aid'],
                    'order_status' => 1
                ];
                // 存在数据则已付费
                $Paid = Db::name('media_order')->where($where)->count();
                // 未付费则执行
                if (empty($Paid)) {
                    if (0 < $arc_level_id && $UsersData['level'] < $arc_level_id) {
                        $data['onclick'] = $upVip;
                        $data['button'] = '升级会员';
                        $level_name = Db::name('users_level')->where(['level_id'=>$arc_level_id])->value('level_name');
                        $this->error('未付费，需要【' . $level_name . '】付费才能播放', '', $data);
                    } else {
                        $data['onclick'] = 'MediaOrderBuy_1592878548();';
                        $data['button'] = '立即购买';
                        $this->error('未付费，视频需要付费才能播放', '', $data);
                    }
                }
            }

            //会员
            if (0 < $arc_level_id && $UsersData['level'] < $arc_level_id) {
                $where = [
                    'level_id' => ['IN', [$arc_level_id, $UsersData['level']]],
                    'lang' => $this->home_lang
                ];
                $arcLevel = Db::name('users_level')->where($where)->Field('level_id,level_value,level_name')->getAllWithIndex('level_id');
                $data['onclick'] = $upVip;
                $data['button']  = '立即升级';
                $this->error('您是' . $arcLevel[$UsersData['level']]['level_name'] . '，请升级至【' . $arcLevel[$arc_level_id]['level_name'] . '】观看视频', '', $data);
            }
        }

        // 腾讯云点播视频
        if (!empty($result['txy_video_id'])) {
            $this->video_log($result['file_id'], $result['aid']);
            if (IS_AJAX) {
                $time = 'eyoucms-video-id-' . getTime();
                $txy_video_id = $result['txy_video_id'];
                $txy_video_html = <<<EOF
<video id="{$time}" preload="auto" width="600" height="400" playsinline webkit-playsinline x5-playsinline></video>
<script type="text/javascript">
    var txy_video_id = '{$txy_video_id}';
    var app_id = $('#appID').val();
    TxyVideo();
    function TxyVideo() {
        var player = TCPlayer('{$time}', { fileID: txy_video_id, appID: app_id});
    }
</script>
EOF;
                $this->success('准备播放中……', null, ['txy_video_html'=>$txy_video_html]);
            } else {
                $this->error('腾讯云点播视频不支持跳转播放');
            }
        }
        // 外部视频链接
        else if (is_http_url($result['file_url'])) {
            // 记录播放次数
            $this->video_log($result['file_id'], $result['aid']);
            if (IS_AJAX) {
                $this->success('准备播放中……', $result['file_url']);
            } else {
                $this->redirect($result['file_url']);
            }
        } 
        // 本站链接
        else
        {
            if (md5_file('.' . $file_url) != $result['md5file']) $this->error('视频文件已损坏！');
            // 记录播放次数
            $this->video_log($result['file_id'], $result['aid']);
            $url = $this->request->domain() . $this->root_dir . $file_url;
            if (IS_AJAX) {
                $this->success('准备播放中……', $url);
            } else {
                $this->redirect($url);
            }
        }
    }

    /**
     * 记录播放次数（重复播放不做记录，游客可重复记录）
     */
    private function video_log($file_id = 0, $aid = 0)
    {
        try {
            $users_id = session('users_id');
            $users_id = intval($users_id);

            $counts = Db::name('media_log')->where([
                'file_id'  => $file_id,
                'aid'      => $aid,
                'users_id' => $users_id,
            ])->count();
            if (empty($users_id) || empty($counts)) {
                $saveData = [
                    'users_id' => $users_id,
                    'aid'      => $aid,
                    'file_id'  => $file_id,
                    'ip'       => clientIP(),
                    'add_time' => getTime(),
                ];
                $r        = Db::name('media_log')->insertGetId($saveData);
                if ($r !== false) {
                    Db::name('media_file')->where(['file_id' => $file_id])->setInc('playcount');
                    Db::name('archives')->where(['aid' => $aid])->setInc('downcount');
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * 内容播放页【易而优视频模板专用】
     */
    public function play($aid = '', $fid = '')
    {
        $aid = intval($aid);
        $fid = intval($fid);

        $res    = Db::name('archives')
            ->alias('a')
            ->field('a.*,b.*,c.typename,c.dirname')
            ->join('media_content b', 'a.aid=b.aid')
            ->join('arctype c', 'a.typeid=c.id')
            ->where('a.aid', $aid)
            ->find();
        if(!empty($res['courseware'])){
            $res['courseware'] = get_default_pic($res['courseware'],true);
        }

        // 播放权限验证
        $redata = $this->check_auth($aid, $fid, $res, 1);
        if (!isset($redata['status']) || $redata['status'] != 2) {
            $url = null;
            if (!empty($redata['url'])) {
                $url = $redata['url'];
            }
            $this->error($redata['msg'], $url);
        }

        Db::name('media_file')->where(['file_id' => $fid])->setInc('playcount');
        $res['seo_title']       = set_arcseotitle($res['title'], $res['seo_title'], $res['typename'], $res['typeid']);
        $res['seo_description'] = @msubstr(checkStrHtml($res['seo_description']), 0, config('global.arc_seo_description_length'), false);
        $eyou['field'] = $res;
        $eyou['field']['fid'] = $fid;
        $this->eyou = array_merge($this->eyou, $eyou);
        $this->assign('eyou', $this->eyou);

        return $this->fetch(":view_media_play");
    }

    /**
     * 播放权限验证【易而优视频模板专用】
     */
    public function check_auth($aid = '', $fid = '', $res = [], $_ajax = 0)
    {
        if (IS_AJAX || $_ajax == 1){
            $is_mobile = isMobile() ? 1 : 0;
            if (empty($res)) {
                $res  = Db::name('archives')
                    ->alias('a')
                    ->join('media_content b', 'a.aid=b.aid')
                    ->where('a.aid', $aid)
                    ->field('a.title,b.courseware,a.arc_level_id,a.users_price,a.users_free')
                    ->find();
            }
            if ((0 < $res['users_price'] && empty($res['users_free'])) || 0 < $res['arc_level_id']) {
                $UsersData = GetUsersLatestData();
                $UsersID   = !empty($UsersData['users_id']) ? intval($UsersData['users_id']) : 0;

                $arc_level_id = !empty($res['arc_level_id']) ? intval($res['arc_level_id']) : 0;
                if (!empty($arc_level_id)) {
                    if (empty($UsersID)) return ['status'=>1,'msg'=>'请先登录','url'=>url('user/Users/login','', true, false, 1, 1),'is_mobile'=>$is_mobile];
                }

                $gratis = Db::name('media_file')->where(['file_id' => $fid])->value('gratis');
                if ($gratis == 0) {
                    /*是否需要付费*/
                    if (0 < $res['users_price'] && empty($res['users_free'])) {
                        $Paid = 0; // 未付费
                        if (!empty($UsersID)) {
                            $where = [
                                'users_id'     => $UsersID,
                                'product_id'   => $aid,
                                'order_status' => 1
                            ];
                            // 存在数据则已付费
                            $Paid = Db::name('media_order')->where($where)->count();
                        }

                        // 未付费则执行
                        if (empty($Paid)) {
                            if (0 < $arc_level_id && $UsersData['level'] < $arc_level_id) {
                                $where      = [
                                    'level_id' => $arc_level_id,
                                    'lang'     => $this->home_lang
                                ];
                                $arcLevel = DB::name('users_level')->where($where)->Field('level_value,level_name')->find();
                                return ['status'=>0,'msg'=>'尊敬的用户，该视频需要【' . $arcLevel['level_name'] . '】付费后才可观看全部内容!','price'=>$res['users_price'],'is_mobile'=>$is_mobile];
                            } else {
                                return ['status'=>0,'msg'=>'尊敬的用户，该视频需要付费后才可观看全部内容!','price'=>$res['users_price'],'is_mobile'=>$is_mobile];
                            }
                        }
                    }

                    // 会员
                    if (0 < $arc_level_id && $UsersData['level'] < $arc_level_id) {
                        $where      = [
                            'level_id' => $arc_level_id,
                            'lang'     => $this->home_lang
                        ];
                        $arcLevel = Db::name('users_level')->where($where)->Field('level_value,level_name')->find();
                        return ['status'=>0,'url'=>url('user/Level/level_centre','', true, false, 1, 1),'msg'=>'尊敬的用户，该视频需要【' . $arcLevel['level_name'] . '】才可观看!','is_mobile'=>$is_mobile];
                    }
                }
            }
            return ['status'=>2,'msg'=>'success!','is_mobile'=>$is_mobile];
        }
    }
}