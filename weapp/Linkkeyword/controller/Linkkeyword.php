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

namespace weapp\Linkkeyword\controller;

use think\Db;
use think\Page;
use app\common\controller\Weapp;
use weapp\Linkkeyword\model\LinkkeywordModel;

/**
 * 插件的控制器
 */
class Linkkeyword extends Weapp
{

    /**
     * 实例化模型
     */
    private $model;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = new LinkkeywordModel;

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件安装的后置操作
     */
    public function afterInstall(){
        $data = array(
            'data'  => '',
            'sort_order' => 10,
            'update_time' => getTime(),
        );
        M('weapp')->where('code','Linkkeyword')->update($data);
    }

    /**
     * 插件使用指南
     */
    public function doc()
    {
        return $this->fetch('doc');
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        $list     = array();
        $keywords = input('keywords/s');

        $map = array();
        if ( ! empty($keywords)) {
            $map['title'] = array( 'LIKE', "%{$keywords}%" );
        }
        $count   = db($this->model->name)->where($map)->count('id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list    = db($this->model->name)->where($map)->order('id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('index');
    }

    /**
     * 插件后台管理 - 新增
     */
    public function add()
    {
        if (IS_POST) {
            $post = input('post.');

            /*关键字判重 start*/
            $checkRes = $this->checkKeyword($post['title']);
            if ($checkRes != false) {
                $this->error($checkRes);
            }
            /*--end*/

            if (!is_http_url($post['url'])) {
                $this->error('您输入的超链接中不包含http://或https://等协议名称');
            }

            /*组装存储数据*/
            $nowData  = array(
                'title'       => trim($post['title']),
                'url'         => trim($post['url']),
                'sort_order'  => 10,
                'add_time'    => getTime(),
                'update_time' => getTime(),
            );
            $saveData = array_merge($post, $nowData);
            /*--end*/
            $insertId = $this->model->insert($saveData);
            if (false !== $insertId) {
                adminLog('新增'.$this->weappInfo['name'].'：'.$post['title']); // 写入操作日志
                $this->success("操作成功", weapp_url('Linkkeyword/Linkkeyword/index'));
            } else {
                $this->error("操作失败");
            }
            exit;
        }

        return $this->fetch('add');
    }

    /**
     * 插件后台管理 - 编辑
     */
    public function edit()
    {
        if (IS_POST) {
            $post       = input('post.');
            $post['id'] = eyIntval($post['id']);
            if ( ! empty($post['id'])) {
                /*关键字判重 start*/
                $checkRes = $this->checkKeyword($post['title'], $post['id']);
                if ($checkRes != false) {
                    $this->error($checkRes);
                }
                /*--end*/

                if (!is_http_url($post['url'])) {
                    $this->error('您输入的超链接中不包含http://或https://等协议名称');
                }

                /*组装存储数据*/
                $nowData  = array(
                    'title'       => trim($post['title']),
                    'url'         => trim($post['url']),
                    'update_time' => getTime(),
                );
                $saveData = array_merge($post, $nowData);
                /*--end*/

                $r = $this->model->save($saveData,
                    array( 'id' => $post['id'] ));
                if ($r) {
                    adminLog('编辑'.$this->weappInfo['name'].'：'.$post['title']); // 写入操作日志
                    $this->success("操作成功!", weapp_url('Linkkeyword/Linkkeyword/index'));
                }
            }
            $this->error("操作失败!");
        }

        $id  = input('id/d', 0);
        $row = $this->model->get($id);
        if (empty($row)) {
            $this->error('数据不存在，请联系管理员！');
            exit;
        }

        $this->assign('row', $row);

        return $this->fetch('edit');
    }

    /**
     * 删除文档
     */
    public function del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if ( ! empty($id_arr)) {
            $result     = $this->model->where("id", 'IN', $id_arr)->select();
            $title_list = get_arr_column($result, 'title');

            $r = $this->model->where("id", 'IN', $id_arr)->delete();
            if ($r) {
                adminLog('删除'.$this->weappInfo['name'].'：'.implode(',', $title_list)); // 写入操作日志
                $this->success("操作成功!");
            }
        }
        $this->error("操作失败");
    }

    /**
     * 关键字判重和判空格
     * 编辑时$id有传值，新增时$id不传值
     */
    private function checkKeyword($title, $id = 0)
    {
        $title = trim($title);
        $res   = false;
        if ( ! empty($title)) {
            $map['title'] = array( '=', $title );
            //编辑时判重需要排己
            if ($id > 0) {
                $map['id'] = array( '<>', $id );
            }
            $exist = db($this->model->name)->where($map)->value('id');// 判重
            if ($exist > 0) {
                $res = '该关键字已存在，请勿重复设置！';
            }
        } else {
            $res = '关键字不能为空格！';
        }

        return $res;
    }

    // 批量新增
    public function batch_add()
    {
        if (IS_POST) {
            $post = input('post.');

            $titles = $post['titles'];
            if (empty($titles)) {
                $this->error('关键词列表不能为空！');
            }

            $words = [];
            $urls = [];
            $titleArr = explode("\r\n", $titles);
            foreach ($titleArr as $key => $val) {
                $arr = $arr_1 = explode('=', $val);
                $title_tmp = !empty($arr[0]) ? trim($arr[0]) : '';
                if (empty($title_tmp) || in_array($title_tmp, $words)) {
                    unset($titleArr[$key]);
                } else {
                    array_push($words, $title_tmp);
                    unset($arr_1[0]);
                    $url_tmp = implode('=', $arr_1);
                    array_push($urls, trim($url_tmp));
                }
            }

            $addData = [];
            $titleList = Db::name('weapp_linkkeyword')->where([
                    'title'  => ['IN', $words],
                ])->column('title');
            foreach ($words as $key => $val) {
                if(in_array($val, $titleList))
                {
                    continue;
                }

                $addData[] = [
                    'title'         => $val,
                    'url'           => !empty($urls[$key]) ? $urls[$key] : '',
                    'target'        => isset($post['target']) ? intval($post['target']) : 1,
                    'status'        => isset($post['status']) ? intval($post['status']) : 1,
                    'sort_order'    => 100,
                    'add_time'      => getTime(),
                    'update_time'   => getTime(),
                ];
            }
            if (!empty($addData)) {
                $r = Db::name('weapp_linkkeyword')->insertAll($addData);
                if ($r !== false) {
                    adminLog('批量新增关键词：'.implode(',', $words));
                    $this->success('操作成功！', weapp_url('Linkkeyword/Linkkeyword/index'));
                } else {
                    $this->error('操作失败');
                }
            } else {
                $this->success('操作成功！', weapp_url('Linkkeyword/Linkkeyword/index'));
            }
        }

        return $this->fetch();
    }
}