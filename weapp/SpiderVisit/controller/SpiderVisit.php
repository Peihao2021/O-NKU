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
 * Date: 2018-06-28
 */

namespace weapp\SpiderVisit\controller;

use think\Page;
use think\Db;
use app\common\controller\Weapp;

/**
 * 插件的控制器
 */
class SpiderVisit extends Weapp
{
    /**
     * 实例化对象
     */
    private $db;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 蜘蛛引擎分类
     */
    private $spiderTypes;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->db = Db::name('WeappSpiderVisit');

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/

        $this->spiderTypes = [
            1   => '谷歌',
            2   => '百度',
            3   => '搜狗',
            4   => '360',
            5   => 'Yandex',
            6   => '微软bing',
            99   => '其他',
        ];
        $this->assign('spiderTypes', $this->spiderTypes);
    }

    /**
     * 插件使用指南
     */
    public function doc(){
        return $this->fetch('doc');
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        /*默认配置信息*/
        $dataInfo = Db::name('weapp')->where(['code'=>'SpiderVisit'])->value('data');
        $dataInfo = json_decode($dataInfo, true);
        if (!isset($dataInfo['maxnum']) || !isset($dataInfo['spiders'])) {
            $maxnum = Db::name('weapp_spider_visit')->count();
            if ($maxnum < 5000) {
                $maxnum = 5000;
            }
            $dataInfo = [
                'maxnum'    => $maxnum,
                'spiders'    => array_keys($this->spiderTypes),
            ];
            Db::name('weapp')->where(['code'=>'SpiderVisit'])->update([
                    'data'  => json_encode($dataInfo),
                    'update_time'   => getTime(),
                ]);
        }
        $this->assign('dataInfo', $dataInfo); // 赋值分页对象
        /*end*/

        $list = array();
        $condition = array();
        // 获取到所有GET参数
        $param = input('param.');
        // 应用搜索条件
        foreach (['keywords','spider'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['url'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $condition[$key] = array('eq', $param[$key]);
                }
            }
        }

        $count = $this->db->where($condition)->count('id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $this->db->where($condition)->order('id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('index');
    }

    /**
     * 插件后台管理 - 统计
     */
    public function tongji()
    {
        $list = array();
        $condition = array();

        $count = Db::name('weapp_spider_tongji')->where($condition)->count('id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = Db::name('weapp_spider_tongji')->where($condition)->order('spider asc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('tongji');
    }
    
    /**
     * 删除文档
     */
    public function del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if(!empty($id_arr) && IS_POST){
            $result = $this->db->where("id",'IN',$id_arr)->select();
            $r = $this->db->where("id",'IN',$id_arr)->delete();
            if($r){
                $spider_list = get_arr_column($result, 'spider');
                foreach ($spider_list as $key => $val) {
                    $total = Db::name('weapp_spider_visit')->where(['spider'=>$val])->count();
                    empty($total) && $total = 0;
                    Db::name('weapp_spider_tongji')->where(['spider'=>$val])->update([
                            'total'         => $total,
                            'update_time'   => getTime(),
                        ]);
                }
                $this->success("操作成功!");
            }else{
                $this->error("操作失败!");
            }
        }else{
            $this->error("参数有误!");
        }
    }
    
    /**
     * 清空
     */
    public function clearall()
    {
        $r = M('weapp_spider_visit')->where([
                'id'  => ['gt', 0],
            ])->delete();
        if(false !== $r){
            try {
                Db::execute("ALTER TABLE `".PREFIX."weapp_spider_visit` AUTO_INCREMENT 1");
            } catch (\Exception $e) {}
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    
    /**
     * 配置
     */
    public function conf()
    {
        if (IS_AJAX_POST) {
            $maxnum = input('post.maxnum/d');
            $spiders = input('post.spiders/a');
            $spiders = !empty($spiders) ? $spiders : [];

            $data = [
                'maxnum'    => intval($maxnum),
                'spiders'    => $spiders,
            ];
            $r = Db::name('weapp')->where(['code'=>'SpiderVisit'])->update([
                    'data'  => json_encode($data),
                    'update_time'   => getTime(),
                ]);
            if ($r !== false) {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }

        $data = Db::name('weapp')->where(['code'=>'SpiderVisit'])->value('data');
        $data = json_decode($data, true);
        if (!isset($data['maxnum'])) {
            $data['maxnum'] = 5000;
            $data['spiders'] = array_keys($this->spiderTypes);
        }
        $this->assign('data', $data);

        return $this->fetch('conf');
    }
}