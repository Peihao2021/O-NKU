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

use think\Db;
use think\template\driver\File;

class Sitemap extends Base
{
    public function _initialize() {
        parent::_initialize();
    }

    /*
     * Sitemap
     */
    public function index()
    {
        $inc_type =  'sitemap';
        if (IS_POST) {
            $param = input('post.');
            $param['sitemap_not1'] = isset($param['sitemap_not1']) ? $param['sitemap_not1'] : 0;
            $param['sitemap_not2'] = isset($param['sitemap_not2']) ? $param['sitemap_not2'] : 0;
            $param['sitemap_xml'] = isset($param['sitemap_xml']) ? $param['sitemap_xml'] : 0;
            $param['sitemap_html'] = isset($param['sitemap_html']) ? $param['sitemap_html'] : 0;
            $param['sitemap_txt'] = isset($param['sitemap_txt']) ? $param['sitemap_txt'] : 0;
            $param['sitemap_archives_num'] = isset($param['sitemap_archives_num']) ? intval($param['sitemap_archives_num']) : 100;

            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')
                    ->cache(true, EYOUCMS_CACHE_TIME, 'language')
                    ->select();
                foreach ($langRow as $key => $val) {
                    tpCache($inc_type,$param,$val['mark']);
                }
            } else {
                tpCache($inc_type,$param);
            }
            /*--end*/
            
            /* 生成sitemap */
            sitemap_all();
            $this->success('操作成功', url('Sitemap/index'));
        }

        $config = tpCache($inc_type);
        $this->assign('config',$config);//当前配置项
        if($this->globalConfig['web_mobile_domain_open']){
            $mobile_domain = preg_replace('/^(.*)(\/\/)([^\/]*)(\.?)(' . request()->rootDomain() . ')(.*)$/i', '${1}${2}' . $this->globalConfig['web_mobile_domain'] . '.${5}${6}', request()->domain());
            $this->assign('mobile_domain',$mobile_domain);
        }
        return $this->fetch('seo/sitemap');
    }

    /**
     * 生成相应的搜索引擎sitemap
     */
    public function create($ver = 'xml')
    {
        if (empty($ver)) {
            sitemap_all();
        } else {
            $fun_name = 'sitemap_'.$ver;
            $fun_name();
        }
    }

    public function ajax_update_sitemap()
    {
        if (IS_POST) {
            /* 生成sitemap */
            sitemap_all();
            $this->success('更新成功');
        }
        $this->error('更新失败！');
    }
    public function ajax_update_sitemap_xml()
    {
        if (IS_POST) {
            /* 生成sitemap */
            sitemap_all('xml');
            $this->success('更新成功');
        }
        $this->error('更新失败！');
    }
    public function ajax_update_sitemap_txt()
    {
        if (IS_POST) {
            /* 生成sitemap */
            sitemap_all('txt');
            $this->success('更新成功');
        }
        $this->error('更新失败！');
    }


    /**
     * 生成sitemap.html
     * @return [type] [description]
     */
    public function ajax_update_sitemap_html(){
        if (IS_AJAX) {
            \think\Session::pause(); // 暂停session，防止session阻塞机制
            $this->handleBuildSitemap();
            $this->success('更新成功');
        }
        $this->error('更新失败！');
    }
    /*
     * 处理生成html
     */
    private function handleBuildSitemap()
    {
        $is_auto = input('param.is_auto/s', 'on'); // 是否自动生成，还是手工生成
        $sitemapid = tpSetting('system.system_sitemapid1647228884');  //四个模块最后一条id，type_arc_tag_ask
        $last_type = $last_arc = $last_tag = $last_ask = 0;
        $is_create = false;
        if (!is_file('./sitemap.html') || 'off' == $is_auto) {
            $is_create = true;
        }
        if (!empty($sitemapid)){
            $sitemapid_arr = explode("_",$sitemapid);
            $last_type = !empty($sitemapid_arr[0]) ? $sitemapid_arr[0] : 0;
            $last_arc = !empty($sitemapid_arr[1]) ? $sitemapid_arr[1] : 0;
            $last_tag = !empty($sitemapid_arr[2]) ? $sitemapid_arr[2] : 0;
            $last_ask = !empty($sitemapid_arr[3]) ? $sitemapid_arr[3] : 0;
        }

        $globalConfig = tpCache('global');
        $web_name = $globalConfig['web_name'];
        $lang = get_current_lang();
        //栏目信息
        $type_map = array(
            'status'    => 1,
            'is_del'    => 0,
            'lang'      => $lang,
        );
        if (is_array($globalConfig)) {
            // 过滤隐藏栏目
            if (isset($globalConfig['sitemap_not1']) && $globalConfig['sitemap_not1'] > 0) {
                $type_map['is_hidden'] = 0;
            }
            // 过滤外部模块
            if (isset($globalConfig['sitemap_not2']) && $globalConfig['sitemap_not2'] > 0) {
                $type_map['is_part'] = 0;
            }
        }
        $result_arctype = Db::name('arctype')->field("*")
            ->where($type_map)
            ->order('id desc')
            ->getAllWithIndex('id');
        $last_type_new = reset($result_arctype);
        if ($is_create == false && !empty($last_type_new['id']) && $last_type_new['id'] > $last_type){
            $is_create = true;
            $last_type = $last_type_new['id'];
        }
        $type_list = [];
        foreach ($result_arctype as $sub){
            if ($sub['is_part'] == 1 && !empty($sub['typelink'])) {
                $url = $sub['typelink'];
            } else {
                $url = get_typeurl($sub, false);
            }
            $type_list[] = [
                'url' => $url,
                'title' => $sub['typename']
            ];
        }
        //文档信息
        $arc_map = array(
            'channel'   => ['IN', config('global.allow_release_channel')],
            'arcrank'   => array('gt', -1),
            'status'    => 1,
            'is_del'    => 0,
            'lang'      => $lang,
        );
        if (is_array($globalConfig)) {
            // 过滤外部模块
            if (isset($globalConfig['sitemap_not2']) && $globalConfig['sitemap_not2'] > 0) {
                $arc_map['is_jump'] = 0;
            }
        }
        /*定时文档显示插件*/
        if (is_dir('./weapp/TimingTask/')) {
            $weappModel = new \app\admin\model\Weapp;
            $TimingTaskRow = $weappModel->getWeappList('TimingTask');
            if (!empty($TimingTaskRow['status']) && 1 == $TimingTaskRow['status']) {
                $arc_map['add_time'] = ['elt', getTime()]; // 只显当天或之前的文档
            }
        }
        /*end*/
        $field = "aid, channel, is_jump, jumplinks, htmlfilename, add_time, update_time, typeid,title";
        $result_archives = Db::name('archives')->field($field)
            ->where($arc_map)
            ->order('aid desc')
            ->select();
        $arc_list = [];
        if ($is_create == false && !empty($result_archives[0]['aid']) && $result_archives[0]['aid'] > $last_arc){
            $is_create = true;
            $last_arc = $result_archives[0]['aid'];
        }
        foreach ($result_archives as $val){
            if (empty($result_arctype[$val['typeid']])){
                continue;
            }
            $val = array_merge($result_arctype[$val['typeid']], $val);
            if ($val['is_jump'] == 1) {
                $url = $val['jumplinks'];
            } else {
                $url = get_arcurl($val, false);
            }
            $arc_list[] = [
                'url' => $url,
                'title' => $val['title']
            ];
        }
        //tags页面
        $tags_map = array(
            'lang'      => $lang,
        );
        $field = "id, add_time, tag";
        $result_tags = Db::name('tagindex')->field($field)
            ->where($tags_map)
            ->order('id desc')
            ->select();
        if ($is_create == false && !empty($result_tags[0]['id']) && $result_tags[0]['id'] > $last_tag){
            $is_create = true;
            $last_tag = $result_tags[0]['id'];
        }
        $tags_list = [];
        foreach ($result_tags as $val){
            $tags_list[] = [
                'url' => get_tagurl($val['id']),
                'title' => $val['tag']
            ];
        }

        // 问答插件
        $ask_list = [];
        if (is_dir('./weapp/Ask/')) {
            try{
                $Askow = Db::name("weapp")->where(['code'=>'Ask'])->field("status,data")->find();
                if (!empty($Askow['status']) && 1 == $Askow['status']) {
                    $ask_map = [
                        'is_review' =>1,
                    ];

                    $ask_seo_pseudo = 1;
                    $Askow['data'] = unserialize($Askow['data']);
                    if (!empty($Askow['data']['seo_pseudo'])) {
                        $ask_seo_pseudo = intval($Askow['data']['seo_pseudo']);
                    }
                    //问答首页
                    $ask_list[] = [
                        'url' => auto_hide_index(url('plugins/Ask/index', [], true, false, $ask_seo_pseudo)),
                        'title' => "问答首页"
                    ];
                    //问答栏目
                    $result_ask_type = Db::name("weapp_ask_type")->field("type_id,type_name")->order('sort_order asc')->select();
                    foreach ($result_ask_type as $val){
                        $url = url('plugins/Ask/index', ['type_id'=>$val['type_id']],true,false,$ask_seo_pseudo);
                        $ask_list[] = [
                            'url' => auto_hide_index($url),
                            'title' => $val['type_name']
                        ];
                    }
                    //问答内容
                    $result_ask = Db::name('weapp_ask')->field('ask_id,type_id,ask_title')
                        ->where($ask_map)
                        ->order('ask_id desc')
                        ->select();
                    foreach ($result_ask as $val){
                        $url = url('plugins/Ask/details', ['ask_id'=>$val['ask_id']],true,false,$ask_seo_pseudo);
                        $ask_list[] = [
                            'url' => auto_hide_index($url),
                            'title' => $val['ask_title']
                        ];
                    }
                    if ($is_create == false && !empty($result_ask[0]['ask_id']) && $result_ask[0]['ask_id'] > $last_ask){
                        $is_create = true;
                        $last_ask = $result_ask[0]['ask_id'];
                    }
                }
            }catch (\Exception $e){}
        }

        $msg = '';
        if ($is_create){
            //数据整合与生成
            $eyou = array(
                'seo_title' => $web_name.'_网站地图',
                'seo_keywords' => '',
                'seo_description' => '',
                'index' => ['url'=>request()->domain().ROOT_DIR.'/','title'=>$web_name],  //首页信息（url链接和title）
                'type_list' => $type_list,
                'arc_list' => $arc_list,
                'tags_list' => $tags_list,
                'ask_list' => $ask_list
            );
            $this->assign('eyou', $eyou);
            try {
                $savepath = "./sitemap.html";
                $tpl      = 'index';
                $this->filePutContents($savepath, $tpl);
                $sitemapid = $last_type."_".$last_arc."_".$last_tag."_".$last_ask;
                $r = tpSetting('system',['system_sitemapid1647228884'=>$sitemapid]);
            } catch (\Exception $e) {
                $msg .= '<span>index.html生成失败！' . $e->getMessage() . '</span><br>';
            }
        }

        return $msg;
    }
    /*
      * 写入静态页面
      * $savepath    保存位置
      * $tpl         模板名称
      *
      */
    private function filePutContents($savepath, $tpl)
    {
        ob_start();
        static $templateConfig = null;
        null === $templateConfig && $templateConfig = \think\Config::get('template');
        $templateConfig['view_path'] = "./public/html/";
        $template                    = "./public/html/sitemap.{$templateConfig['view_suffix']}";

        $content                     = $this->fetch($template, [], [], $templateConfig);

        /*解决模板里没有设置编码的情况*/
        if (!stristr($content, 'utf-8')) {
            $content = str_ireplace('<head>', "<head>\n<meta charset='utf-8'>", $content);
        }
        /*end*/
        echo $content;
        $_cache = ob_get_contents();
        ob_end_clean();

        static $File = null;
        null === $File && $File = new File;
        $File->fwrite($savepath, $_cache);
    }
}
