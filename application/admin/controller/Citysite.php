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

class Citysite extends Base
{
    private $web_citysite_open;
    // 禁用的目录名称
    private $disableDirname = [];

    public function _initialize(){
        parent::_initialize();
        $this->disableDirname      = config('global.disable_dirname');

        $functionLogic = new \app\common\logic\FunctionLogic;
        $functionLogic->validate_authorfile(2);

        $this->web_citysite_open = tpCache('global.web_citysite_open');
        $this->assign('web_citysite_open', $this->web_citysite_open);
    }

    public function index()
    {
        $assign_data = array();
        $condition = array();
        // 获取到所有GET参数
        $param = input('param.');
        $parent_id = input('pid/d', 0);

        // 应用搜索条件
        foreach (['keywords','pid'] as $key) {
            $param[$key] = addslashes(trim($param[$key]));
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['name'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'pid') {
                    $condition['parent_id'] = array('eq', $param[$key]);
                } else {
                    $condition[$key] = array('eq', $param[$key]);
                }
            }
        }

        // 上一级区域名称
        $parentInfo = Db::name('citysite')->where(['id'=>$parent_id])->find();
        $parentLevel = !empty($parentInfo['level']) ? intval($parentInfo['level']) : 0;
        $condition['level'] = $parentLevel + 1;

        $regionM =  Db::name('citysite');
        $count = $regionM->where($condition)->count('id');// 查询满足要求的总记录数
        $Page = $pager = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $regionM->where($condition)->order('sort_order asc, id asc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $key => $val) {
            $val['siteurl'] = siteurl($val);
            $list[$key] = $val;
        }

        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页对象
        $this->assign('parentInfo',$parentInfo);

        return $this->fetch();
    }

    public function add(){
        if (IS_POST) {
            $post = input('post.');
            $name = trim($post['name']);
            if (empty($name)) {
                $this->error('区域名称不能为空！');
            }

            $domain = trim($post['domain']);
            if (!empty($domain)) {
                $domain = preg_replace("/[^a-zA-Z0-9]+/", "", $domain);
                $domain = strtolower($domain);
                $count = Db::name('citysite')->where([
                        'domain'    => $domain,
                    ])->count();
                if (!empty($count)) {
                    $this->error('英文名称已存在！');
                }
                // 检测
                if (!empty($domain) && !$this->domain_unique($domain)) {
                    $this->error('英文名称与系统内置冲突，请更改！');
                }
                /*--end*/
            } else {
                $this->error('英文名称不能为空！');
            }

            // --存储数据
            $nowData = array(
                'name'  => $name,
                'domain'  => $domain,
                'initial'   => getFirstCharter($name),
                'seo_description'   => !empty($post['seo_description']) ? $post['seo_description'] : '',
                'sort_order'    => 100,
                'add_time'    => getTime(),
                'update_time'    => getTime(),
            );
            if (!empty($post['city_id'])){
                $nowData['level'] = 3;
                $nowData['parent_id'] = intval($post['city_id']);
                $nowData['topid'] = intval($post['province_id']);
            } else if (!empty($post['province_id'])){
                $nowData['level'] = 2;
                $nowData['parent_id'] = intval($post['province_id']);
                $nowData['topid'] = intval($post['province_id']);
            } else {
                $nowData['level'] = 1;
                $nowData['parent_id'] = 0;
                $nowData['topid'] = 0;
            }
            $data = array_merge($post, $nowData);
            $insertId = M('citysite')->insertGetId($data);
            if (false !== $insertId) {
                \think\Cache::clear('citysite');
                // extra_cache('global_get_site_province_list', null);
                // extra_cache('global_get_site_city_list', null);
                // extra_cache('global_get_site_area_list', null);
                adminLog('新增区域：'.$data['name']);
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
            exit;
        }

        $pid = input('param.pid/d', 0);
        $region = array_reverse($this->getParentCitysiteId($pid));
        $assign_data['province_id'] = !empty($region[0]) ? $region[0] : 0;
        $assign_data['city_id'] = !empty($region[1]) ? $region[1] : 0;

        // 省份列表
        $province_all = $this->get_site_province_all();
        $assign_data['province_all'] = $province_all;
        $assign_data['rootDomain'] = $this->request->rootDomain().ROOT_DIR;

        $this->assign($assign_data);

        return $this->fetch();
    }

    public function edit(){
        if (IS_POST) {
            $post = input('post.');
            if(!empty($post['id'])){
                $post['id'] = intval($post['id']);
                $name = trim($post['name']);
                if (empty($name)) {
                    $this->error('区域名称不能为空！');
                }

                $domain = trim($post['domain']);
                if (!empty($domain)) {
                    $domain = preg_replace("/[^a-zA-Z0-9]+/", "", $domain);
                    $domain = strtolower($domain);
                    $count = Db::name('citysite')->where([
                            'domain'    => $domain,
                            'id'    => ['NEQ', $post['id']],
                        ])->count();
                    if (!empty($count)) {
                        $this->error('英文名称已存在！');
                    }
                    // 检测
                    if (!empty($domain) && !$this->domain_unique($domain, $post['id'])) {
                        $this->error('英文名称与系统内置冲突，请更改！');
                    }
                    /*--end*/
                } else {
                    $this->error('英文名称不能为空！');
                }

                // --存储数据
                $nowData = array(
                    'name'  => $name,
                    'domain'  => $domain,
                    'initial'   => getFirstCharter($name),
                    'seo_description'   => !empty($post['seo_description']) ? $post['seo_description'] : '',
                    'update_time'    => getTime(),
                );

                if (!isset($post['province_id'])) $post['province_id'] = $post['old_province_id'];
                if (!isset($post['city_id'])) $post['city_id'] = $post['old_city_id'];

                if (!empty($post['city_id'])){
                    $nowData['level'] = 3;
                    $nowData['parent_id'] = intval($post['city_id']);
                    $nowData['topid'] = intval($post['province_id']);
                } else if (!empty($post['province_id'])){
                    $nowData['level'] = 2;
                    $nowData['parent_id'] = intval($post['province_id']);
                    $nowData['topid'] = intval($post['province_id']);
                } else {
                    $nowData['level'] = 1;
                    $nowData['parent_id'] = 0;
                    $nowData['topid'] = 0;
                }
                $data = array_merge($post, $nowData);
                $r = M('citysite')->where([
                        'id'    => $post['id'],
                    ])
                    ->cache(true, null, "citysite")
                    ->update($data);
                if (false !== $r) {
                    // 同步处理子级城市
                    if (empty($post['province_id'])) {
                        Db::name('citysite')->where([
                            'parent_id' => $post['id'],
                        ])->update([
                            'level' => 2,
                            'topid' => $post['id'],
                            'update_time' => getTime(),
                        ]);
                    } else if (!empty($post['province_id']) && empty($post['city_id'])) {
                        Db::name('citysite')->where([
                            'parent_id' => $post['id'],
                        ])->update([
                            'level' => 3,
                            'topid' => $data['topid'],
                            'update_time' => getTime(),
                        ]);
                    }
                    // extra_cache('global_get_site_province_list', null);
                    // extra_cache('global_get_site_city_list', null);
                    // extra_cache('global_get_site_area_list', null);
                    adminLog('编辑区域：'.$data['name']);
                    $this->success("操作成功");
                }
            }
            $this->error("操作失败");
        }

        $id = input('param.id/d', 0);
        $info = model("Citysite")->getInfo($id);
        $assign_data['field'] = $info;
        $region = array_reverse($this->getParentCitysiteId($info['parent_id']));
        $assign_data['province_id'] = !empty($region[0]) ? $region[0] : 0;
        $assign_data['city_id'] = !empty($region[1]) ? $region[1] : 0;

        // 省份列表
        $province_all = $this->get_site_province_all();
        $assign_data['province_all'] = $province_all;
        $assign_data['rootDomain'] = $this->request->rootDomain().ROOT_DIR;

        // 是否有下级以及层级
        $assign_data['childrenLevelCount'] = Db::name('citysite')->field('level')->where(['parent_id|topid'=>$id])->group('level')->count();

        $this->assign($assign_data);

        return $this->fetch();
    }

    public function conf(){
        if (IS_POST) {
            $post = $data = input('post.');
            foreach ($data as $key => $val) {
                $val = trim($val);
                $data[$key] = $val;
            }
            tpCache('site', $data);
            adminLog('多站点功能配置');
            $this->success("操作成功");
        }

        $assign_data = [];
        $row = tpCache('site');
        $assign_data['row'] = $row;
        // 站点区域
        $site_default_home = !empty($row['site_default_home']) ? intval($row['site_default_home']) : 0;
        $citysiteLogic = new \app\common\logic\CitysiteLogic(); 
        $assign_data['citysite_html'] = $citysiteLogic->citysite_list(0, $site_default_home, true, 0, array(), false);
        $assign_data['site_default_home'] = $site_default_home;

        $this->assign($assign_data);
        return $this->fetch();
    }

    /*
     * 开启关闭启用
     * 开启当前，判断当前是否为唯一开启，如果是，则将当前设置为默认区域
     * 关闭当前，判断当前是否为原来默认区域：如果是，则判断当前同级（相同上级）是否存在开启：如存在，设置为默认，如不存在：判断第一级是否存在开启：如存在，设置第一个为默认，如不存在，继续往下级查找。
     *
     * 至少必须存在一个开启区域
     */
    public function setStatus() {
        $id = input('id/d', 0);
        $status = input('status/d', 0);
        $list = Db::name("citysite")->where("status=1")->getField("id,status");
        if ($status == 0){
            if (count($list) == 1 && !empty($list[$id])){
                $this->error("至少存在一个开启区域！");
            }
        }
        Db::name('citysite')->where(['id'=>$id])->cache(true, null, "citysite")->update(['status'=>$status, 'update_time'=>getTime()]);
        $this->success("设置成功");

/*
        $id = input('id/d', 0);
        $status = input('status/d', 0);
        $list = Db::name("citysite")->where("status=1")->order("level asc")->getField("id,parent_id,status,is_default,level");
        $count = count($list);
        $is_true = true;
        if ($status == 1){
            if ($count == 0 || ($count == 1 && empty($list[$id]))){
                $is_true = $this->setIsDefault($id);
            }
        }else{
            if ($count == 1 && !empty($list[$id])){
                $this->error("至少存在一个开启区域！".$status);
            }
            if (!empty($list[$id]) && $list[$id]['is_default'] == 1){
                $peer_id = $top_id = $any_id = 0;
                foreach ($list as $val){
                    if (empty($peer_id) && $val['id']!= $id && $val['parent_id'] == $list[$id]['parent_id']){
                        $peer_id = $val['id'];
                        break;
                    }
                    if (empty($top_id) && $val['id']!= $id && $val['parent_id'] == 0){
                        $top_id =  $val['id'];
                    }
                    if (empty($any_id) && $val['id']!= $id){
                        $any_id =  $val['id'];
                    }
                }
                if ($peer_id){
                    $default_id = $peer_id;
                }else if($top_id){
                    $default_id = $top_id;
                }else{
                    $default_id = $any_id;
                }
                $is_true = $this->setIsDefault($default_id);
            }
        }
        if (!$is_true){
            $this->error("设置失败，请检查二级域名不能为空！");
        }
        Db::name('citysite')->where(['id'=>$id])->cache(true, null, "citysite")->update(['status'=>$status, 'update_time'=>getTime()]);

        $this->success("设置成功");
        */
    }

    /*
     * 设置默认区域
     */
    // private function setIsDefault($id){
    //     $id = intval($id);
    //     $subdomain = Db::name('citysite')->where(['id'=>$id])->getField('domain');
    //     if ($this->web_citysite_open && empty($subdomain)) { //如果为开启状态，且二级域名为空，不允许设置
    //         return false;
    //     }
    //     $is_true = Db::name('citysite')->where(['id'=>$id])->update(['is_default'=>1, 'update_time'=>getTime()]);
    //     if ($is_true){
    //         Db::name('citysite')->where(['id'=>['neq',$id]])->update(['is_default'=>0, 'update_time'=>getTime()]);
    //         tpCache('site', ['site_default_home'=>$id]);
    //     }
    //     \think\Cache::clear('citysite');

    //     return $is_true;
    // }

    /*
     * 设置是否默认
     */
    // public function setSortOrder(){
    //     $id = input('id/d', 0);
    //     $is_true = $this->setIsDefault($id);
    //     if ($is_true){
    //         $this->success("设置成功");
    //     }else{
    //         $this->error("设置失败，请检查二级域名不能为空！");
    //     }
    // }

    //获取全部省份
    private function get_site_province_all()
    {
        $result = Db::name('citysite')->field('id, name')
            ->where('level',1)
            ->order("sort_order asc")
            ->getAllWithIndex('id');

        return $result;
    }

    /**
    * 获取子类列表
    */  
    public function ajax_get_region($pid = 0, $level = 2, $siteid = '', $text = '--请选择--'){
        $data = model('Citysite')->getList($pid,'*','',$level);
        $html = "<option value=''>".urldecode($text)."</option>";
        foreach($data as $key=>$val){
            if ($val['id'] == $siteid) {
                unset($data[$key]);
                continue;
            }
            $html.="<option value='".$val['id']."'>".$val['name']."</option>";
        }
        $isempty = 0;
        if (empty($data)){
            $isempty = 1;
        }
        $this->success($html,'',['isempty'=>$isempty]);

    }

    /*
     * 获取区域列表（关联栏目）
     * pid          上级id
     * level        级别
     * relevance    关联模型（表名称），为空时表示不关联
     * text         不选择时显示text
     */
    public function ajax_get_region_arc($pid = 0,$level = 1,$channel = '9', $text = '--请选择--'){
        $regionIds = $this->getAllRegionIds($level,'',$channel);
        $data = Db::name('citysite')->field("*")
            ->where(["id"=>['in',$regionIds],'parent_id'=>$pid])
            ->select();
        if ($level == 1 && count($data) == 1){   //只存在一个省份
            $html = "<input type='hidden' id='province_id' name='province_id' value='".$data[0]['id']."'>";
        }else if ($level == 1){
            $html = "<select name='province_id' id='province_id'>";
            $html .= "<option value=''>".urldecode($text)."</option>";
            foreach($data as $key=>$val){
                $html.="<option value='".$val['id']."'>".$val['name']."</option>";
            }
            $html .= "</select>";
        }else{
            $html = "<select name='city_id' id='city_id'>";
            $html .= "<option value=''>".urldecode($text)."</option>";
            foreach($data as $key=>$val){
                $html.="<option value='".$val['id']."'>".$val['name']."</option>";
            }
            $html .= "</select>";
        }

        $this->success($html);
    }

    /*
     * 获取所有区域（id）集合
     */
    private function getAllRegionIds($level,$typeid = "",$channel = ""){
        $field = "province_id";
        if ($level == 2){
            $field = "city_id";
        }else if ($level == 3){
            $field = "area_id";
        }
        $where['status'] = 1;
        $where['is_del'] = 0;
        if (!empty($typeid)){
            $where['typeid'] = ['in',$typeid];
        }else if (!empty($channel)){
            $where['channel'] = ['in',$channel];
        }
        $regionIds = Db::name('archives')->where($where)->group($field)->getField($field,true);

        return $regionIds;
    }

    /**
     * 删除
     */
    public function del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if(IS_POST && !empty($id_arr)){

            // $count = Db::name('citysite')->where([
            //         'is_default'    => 1,
            //         'id' => ['IN', $id_arr],
            //     ])->count();
            // if ($count > 0){
            //     $this->error('默认区域不允许删除');
            // }

            $count = Db::name('citysite')->where('parent_id','IN',$id_arr)->count();
            if ($count > 0){
                $this->error('所选区域有下级区域，请先删除下级区域');
            }

            $result = Db::name('citysite')->field('name')
                ->where([
                    'id'    => ['IN', $id_arr],
                ])->select();
            $name_list = get_arr_column($result, 'name');

            $r = Db::name('citysite')->where([
                    'id'    => ['IN', $id_arr],
                ])
                ->cache(true, null, "citysite")
                ->delete();
            if($r !== false){
                /*默认区域被删除，自动处理主站默认区域为空*/
                $site_default_home = tpCache('global.site_default_home');
                if (!empty($site_default_home) && in_array($site_default_home, $id_arr)) {
                    tpCache('site', ['site_default_home'=>0]);
                }
                /*end*/
                // extra_cache('global_get_site_province_list', null);
                // extra_cache('global_get_site_city_list', null);
                // extra_cache('global_get_site_area_list', null);
                adminLog('删除区域：'.implode(',', $name_list));
                $this->success('删除成功');
            }
        }
        $this->error('删除失败');
    }

    /**
     * 判断子域名的唯一性
     */
    private function domain_unique($domain = '', $id = 0)
    {
        $result = Db::name('citysite')->field('id,domain')->getAllWithIndex('id');
        if (!empty($result)) {
            if (0 < $id) unset($result[$id]);
            !empty($result) && $result = get_arr_column($result, 'domain');
        }
        empty($result) && $result = [];
        $dirnames = Db::name('arctype')->column('dirname');
        foreach ($dirnames as $key => $val) {
            $dirnames[$key] = strtolower($val);
        }
        $disableDirname = array_merge($this->disableDirname, $dirnames, $result);
        if (in_array(strtolower($domain), $disableDirname)) {
            return false;
        }
        return true;
    }

    /*
     * js打开获取子区域列表
     */
    public function ajaxSelectRegion(){
        $list = Db::name("citysite")->where("status=1")->select();
        $this->assign('list', $list);
        $this->assign('json_arctype_list', json_encode($list));
        $func = input('func/s');
        $assign_data['func'] = $func;
        $this->assign($assign_data);

        return $this->fetch();
    }

    /*
     * js获取region
     */
    public function ajaxGetOne($where = ""){
        return Db::name('citysite')->where($where)->find();
    }

    /**
     * 获取城市站点的所有上级区域id
     */
    private function getParentCitysiteId($id){
        $id = intval($id);
        static $regionArr = array();
        static $countnext = 0;
        $countnext++;
        $regionArr[] = $id;
        if(!empty($id)){
            $list = \think\Db::name('citysite')->field('id,parent_id')->where('id',$id)->find();
            if($list && $list['parent_id']!=0){
                $this->getParentCitysiteId($list['parent_id']);
            }
        }
        $countnext--;
        $result = $regionArr;
        if($countnext == 0){
            $regionArr = array();
        }
        return $result;
    }

    /**
     * 获取区域的拼音
     */
    public function ajax_get_name_pinyin($name = '')
    {
        $pinyin = get_pinyin($name);
        $this->success('提取成功', null, ['pinyin'=>$pinyin]);
    }
}