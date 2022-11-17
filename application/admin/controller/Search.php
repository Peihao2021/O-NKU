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
use think\Page;

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
        $list = array();
        $param = input('param.');
        $keywords = input('keywords/s');
        $keywords = trim($keywords);
        $condition = [];

        foreach (['keywords'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['word'] = array('LIKE', "%{$keywords}%");
                } else {
                    $condition[''.$key] = array('eq', trim($param[$key]));
                }
            }
        }

        $condition['lang'] = array('eq', $this->admin_lang);

        $count = $this->searchword_db->where($condition)->count('id');
        $Page = $pager = new Page($count, config('paginate.list_rows'));
        $list = $this->searchword_db->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('list',$list);
        $this->assign('pager',$pager);
        return $this->fetch();
    }

    public function edit()
    {
        if (IS_AJAX_POST){
            $param = input('param.');
            if (empty($param['id'])){
                $this->error('缺少id');
            }
            $update[$param['field']] = $param['value'];
            $where['lang'] = array('eq', $this->admin_lang);
            $where['id'] = $param['id'];
            $r = $this->searchword_db->where($where)->update($update);
            if (false !== $r){
                $this->success('操作成功');
            }
        }
        $this->error('操作失败');
    }

    public function del()
    {
        if (IS_POST) {
            $id_arr = input('del_id/a');
            $id_arr = eyIntval($id_arr);
            if(!empty($id_arr)){
                $result = $this->searchword_db->field('word')
                    ->where([
                        'id'    => ['IN', $id_arr],
                        'lang'  => $this->admin_lang,
                    ])->select();
                $title_list = get_arr_column($result, 'word');

                $r = $this->searchword_db->where([
                        'id'    => ['IN', $id_arr],
                        'lang'  => $this->admin_lang,
                    ])
                    ->cache(true, null, "search_word")
                    ->delete();
                if($r !== false){
                    adminLog('删除搜索关键词：'.implode(',', $title_list));
                    $this->success('删除成功');
                }
            }
        }
        $this->error('删除失败');
    }

    public function conf()
    {
        if (IS_POST) {
            $param = input('param.');
            /*多语言*/
            if (is_language()) {
                $langRow = \think\Db::name('language')->order('id asc')
                    ->cache(true, EYOUCMS_CACHE_TIME, 'language')
                    ->select();
                foreach ($langRow as $key => $val) {
                    tpCache('search', ['search_model'=>intval($param['search_model'])], $val['mark']);
                }
            } else {
                tpCache('search', ['search_model'=>intval($param['search_model'])]);
            }
            /*--end*/
            $this->success('操作成功');
        }
        $search = tpCache('search');
        $this->assign('search',$search);

        return $this->fetch();
    }
}