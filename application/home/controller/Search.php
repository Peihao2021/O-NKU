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

class Search extends Base
{
    private $searchword_db;

    public function _initialize() {
        parent::_initialize();
        $this->searchword_db = Db::name('search_word');
    }

    /**
     * 搜索主页
     */
    public function index()
    {
        $result = [];

        $result = $param = input('param.');

        $result['pageurl'] = request()->url(true); // 获取当前页面URL
        $result['pageurl_m'] = pc_to_mobile_url($result['pageurl']); // 获取当前页面对应的移动端URL
        // 移动端域名
        $result['mobile_domain'] = '';
        if (!empty($this->eyou['global']['web_mobile_domain_open']) && !empty($this->eyou['global']['web_mobile_domain'])) {
            $result['mobile_domain'] = $this->eyou['global']['web_mobile_domain'] . '.' . $this->request->rootDomain(); 
        }
        
        !isset($result['keywords']) && $result['keywords'] = '';
        $eyou = array(
            'field' => $result,
        );
        $this->eyou = array_merge($this->eyou, $eyou);
        $this->assign('eyou', $this->eyou);

        $viewfile = 'index_search';

        if (config('city_switch_on') && !empty($this->home_site)) { // 多站点内置模板文件名
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile.".".$this->view_suffix;
            if (!file_exists($viewfilepath)) {
                $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile."_{$this->home_site}.".$this->view_suffix;
                if (file_exists($viewfilepath)) {
                    $viewfile .= "_{$this->home_site}";
                } else {
                    return $this->lists();
                }
            }
        } else if (config('lang_switch_on') && !empty($this->home_lang)) { // 多语言内置模板文件名
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile.".".$this->view_suffix;
            if (!file_exists($viewfilepath)) {
                $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile."_{$this->home_lang}.".$this->view_suffix;
                if (file_exists($viewfilepath)) {
                    $viewfile .= "_{$this->home_lang}";
                } else {
                    return $this->lists();
                }
            }
        } else {
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile.".".$this->view_suffix;
            if (!file_exists($viewfilepath)) {
                return $this->lists();
            }
        }

        return $this->fetch(":{$viewfile}");
    }

    /**
     * 搜索列表
     */
    public function lists()
    {
        $param = input('param.');

        /*记录搜索词*/
        if (!isset($param['keywords'])) {
            die('标签调用错误：缺少属性 name="keywords"，请查看标签教程修正 <a href="https://www.eyoucms.com/plus/view.php?aid=521" target="_blank">前往查看</a>');
        }
        $word = $this->request->param('keywords');
        if(empty($word)){
            $this->error('关键词不能为空！');
        }
        $page = $this->request->param('page');
        if(!empty($word) && 2 > $page)
        {
            $word = str_replace(['<','>','"',';'], '', $word);
            $word = addslashes($word);
            
            /*前台禁止搜索开始*/
            if (is_dir('./weapp/Wordfilter/')) {
                $wordfilterRow = Db::name('weapp')->where(['code'=>'Wordfilter', 'status'=>1])->find();
                if(!empty($wordfilterRow['data'])){
                    $wordfilterRow['data'] = json_decode($wordfilterRow['data'], true);
                    if ($wordfilterRow['data']['search'] == 3){
                        $wordfilter = Db::name('weapp_wordfilter')->where(['title'=>$word, 'status'=>1])->find();
                        if(!empty($wordfilter)){
                            $this->error('包含敏感关键词，禁止搜索！');
                        }
                    }
                }
            }
            /*前台禁止搜索结束*/

            /*记录搜索词*/
            $nowTime = getTime();
            $row = $this->searchword_db->field('id')->where(['word'=>$word, 'lang'=>$this->home_lang])->find();
            if(empty($row))
            {
                $this->searchword_db->insert([
                    'word'      => $word,
                    'sort_order'    => 100,
                    'lang'      => $this->home_lang,
                    'add_time'  => $nowTime,
                    'update_time'  => $nowTime,
                ]);
            }else{
                $this->searchword_db->where(['id'=>$row['id']])->update([
                    'searchNum'         =>  Db::raw('searchNum+1'),
                    'update_time'       => $nowTime,
                ]);
            }
        }
        /*--end*/

        $result = $param;
        !isset($result['keywords']) && $result['keywords'] = '';
        $eyou = array(
            'field' => $result,
        );
        $this->eyou = array_merge($this->eyou, $eyou);
        $this->assign('eyou', $this->eyou);

        /*模板文件*/
        $viewfile = 'lists_search';
        $channelid = input('param.channelid/d');
        if (!empty($channelid)) {
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile."_{$channelid}.".$this->view_suffix;
            if (file_exists($viewfilepath)) {
                $viewfile .= "_{$channelid}";
            }
        }
        /*--end*/

        if (config('city_switch_on') && !empty($this->home_site)) { // 多站点内置模板文件名
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile."_{$this->home_site}.".$this->view_suffix;
            if (file_exists($viewfilepath)) {
                $viewfile .= "_{$this->home_site}";
            }
        } else if (config('lang_switch_on') && !empty($this->home_lang)) { // 多语言内置模板文件名
            $viewfilepath = TEMPLATE_PATH.$this->theme_style_path.DS.$viewfile."_{$this->home_lang}.".$this->view_suffix;
            if (file_exists($viewfilepath)) {
                $viewfile .= "_{$this->home_lang}";
            }
        }

        return $this->fetch(":{$viewfile}");
    }
}