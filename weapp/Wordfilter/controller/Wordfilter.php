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

namespace weapp\Wordfilter\controller;

use think\File;
use think\Page;
use think\Db;
use app\common\controller\Weapp;
use weapp\Wordfilter\logic\WordfilterLogic;

/**
 * 插件的控制器
 */
class Wordfilter extends Weapp
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
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->db = db('WeappWordfilter');

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        $list = array();
        $keywords = input('keywords/s');

        $map = array();
        if (!empty($keywords)) {
            $keywords = trim($keywords);
            $map['title'] = array('LIKE', "%{$keywords}%");
        }

        $count = $this->db->where($map)->count('id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $this->db->where($map)->order('id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
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
            $filename = input('post.filename/s');
            !empty($filename) && @unlink($filename);

            $content = input('post.content/s');
            $word_list = explode("\r\n", $content);
            $word_list = array_filter($word_list);//去除数组空值
            $word_list = array_unique($word_list); //输入去重

            $logData = $nowData = [];
            foreach ($word_list as $key => $val) {
                //存在去重 //空格去重
                if(!$this->db->where('title',$val)->find() && trim($val) !== '')
                {
                    /*组装存储数据*/
                    $nowData[] = array(
                        'title'       => $val,
                        'add_time'    => getTime(),
                        'update_time'    => getTime()
                    );
                    $logData[] = $val;
                }
            }

            if(!empty($nowData))
            {
                $res = $this->db->insertAll($nowData);
                if (false !== $res) {
                    adminLog('新增'.$this->weappInfo['name'].'：'.implode(',', $logData)); // 写入操作日志
                    $this->success("操作成功，有效新增".$res.'条敏感词', weapp_url('Wordfilter/Wordfilter/index'));
                }else{
                    $this->error("操作失败");
                }
            }else{
                $this->success('操作成功', weapp_url('Wordfilter/Wordfilter/index'));
            }
            exit;
        }

        return $this->fetch('add');
    }
    
    /**
     * 删除文档
     */
    public function del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if(IS_POST && !empty($id_arr)){
            $result = $this->db->where("id",'IN',$id_arr)->select();
            $title_list = get_arr_column($result, 'title');

            $r = $this->db->where("id",'IN',$id_arr)->delete();
            if($r){
                adminLog('删除'.$this->weappInfo['name'].'：'.implode(',', $title_list));
                $this->success("操作成功!");
            }else{
                $this->error("操作失败!");
            }
        }else{
            $this->error("参数有误!");
        }
    }

    /**
     * 批量导入
     */
    public function upload()
    {
        if(IS_POST)
        {
            $state = '';
            $upload_limit_size = intval(tpCache('basic.file_size') * 1024 * 1024);
            // 获取表单上传文件
            $file = $this->request->file('wordfile');
            if(empty($file))
                $file = $this->request->file('file');    

            $result = $this->validate(
                ['file' => $file], 
                ['file'=>'fileSize:'.$upload_limit_size.'|fileExt:txt'],
                ['file.fileSize' => '上传文件过大','file.fileExt'=>'上传文件后缀名必须为txt']
            );

            if (true !== $result || empty($file)) {
                $state = "ERROR：" . $result;
            } else {
                $savePath = UPLOAD_PATH.'weapp/'.date('Ymd/');
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->rule(function ($file) {    
                    return  md5(mt_rand()); // 使用自定义的文件保存规则
                })->move($savePath);
                if ($info) {
                    $state = "SUCCESS";
                } else {
                    $state = "ERROR：" . $file->getError();
                }
                if ("SUCCESS" == $state) {
                    $filename = $savePath.$info->getSaveName();
                    if (!file_exists($filename)) {
                        $this->error('上传文件不存在！');
                    }
                    
                    $words = '';
                    $wordfilterLogic = new WordfilterLogic;
                    $res = $wordfilterLogic->txtAjax($filename);
                    if (!empty($res)) {
                        $data = [];
                        foreach ($res as $key => $val) {
                            if (!empty($val)) {
                                array_push($data, $val);
                            }
                        }
                        $words = implode(PHP_EOL, $data);
                    }
                    $this->success('导入成功', null, ['words'=>$words,'filename'=>$filename]);
                }
            }
            $this->error('导入失败！'.$state);
        }
    }
}