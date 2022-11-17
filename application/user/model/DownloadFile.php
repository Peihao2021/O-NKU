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

namespace app\user\model;

use think\Db;
use think\Model;

//下载文件
class DownloadFile extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    //获取单条下载文章的所有文件
    public function getDownFile($aid, $field = '*')
    {
        $result = Db::name('DownloadFile')->field($field)
            ->where('aid', $aid)
            ->order('sort_order asc')
            ->select();

        foreach ($result as $key => $val) {
            if (!empty($val['file_url'])) {
                $result[$key]['file_url'] = handle_subdir_pic($val['file_url'], 'soft');
            }
            if (!isset($val['server_name'])) {
                $result[$key]['server_name'] = $result[$key]['file_name'];
            }
        }

        return $result;
    }

    //删除单条下载文章的所有文件
    public function delDownFile($aid = array())
    {
        if (!is_array($aid)) {
            $aid = array($aid);
        }
        $result = Db::name('DownloadFile')->where(array('aid'=>array('IN', $aid)))->delete();
        if ($result !== false) {
            Db::name('download_log')->where(array('aid'=>array('IN', $aid)))->delete();
        }

        return $result;
    }



    //保存下载文章的文件
    public function savefile($aid, $post = array())
    {
        // 拼装本地链接数据
        $data = array();
        $sort_order = 0;
        $fileupload = isset($post['fileupload']) ? $post['fileupload'] : array();
        if (!empty($fileupload)) {
            foreach($fileupload['file_url'] as $key => $val)
            {
                if($val == null || empty($val))  continue;
                $title     = !empty($post['title']) ? $post['title'] : '';
                $file_size = isset($post['fileupload']['file_size'][$key]) ? $post['fileupload']['file_size'][$key] : 0;
                $file_mime = isset($post['fileupload']['file_mime'][$key]) ? $post['fileupload']['file_mime'][$key] : '';
                $uhash     = isset($post['fileupload']['uhash'][$key]) ? $post['fileupload']['uhash'][$key] : md5($val);
                $md5file   = isset($post['fileupload']['md5file'][$key]) ? $post['fileupload']['md5file'][$key] : md5($val);
                $file_name   = isset($post['fileupload']['file_name'][$key]) ? $post['fileupload']['file_name'][$key] : '';
                $file_ext   = isset($post['fileupload']['file_ext'][$key]) ? $post['fileupload']['file_ext'][$key] : '';
                $server_name   = isset($post['fileupload']['server_name'][$key]) ? $post['fileupload']['server_name'][$key] : '';
                ++$sort_order;
                $data[] = array(
                    'aid'        => $aid,
                    'title'      => $title,
                    'file_url'   => $val,
                    'extract_code'  => '',
                    'file_size'  => $file_size,
                    'file_ext'   => $file_ext,
                    'file_name'  => $file_name,
                    'file_mime'  => $file_mime,
                    'uhash'      => $uhash,
                    'md5file'    => $md5file,
                    'server_name'    => $server_name,
                    'is_remote'  => 0,
                    'sort_order' => $sort_order,
                    'add_time'   => getTime(),
                );
            }
        }

        // 拼装远程链接数据
        $data_new   = array();
        if (!empty($post['remote_file'])) {
            foreach($post['remote_file'] as $kkk => $vvv)
            {
                if($vvv == null || empty($vvv)) continue;
                $server_name = !empty($post['server_name'][$kkk]) ? trim($post['server_name'][$kkk]) : '';
                $extract_code = !empty($post['extract_code'][$kkk]) ? trim($post['extract_code'][$kkk]) : '';
                ++$sort_order;
                $data_new[] = array(
                    'aid'        => $aid,
                    'title'      => $post['title'],
                    'file_url'   => $vvv,
                    'extract_code' => $extract_code,
                    'file_size'  => '0',
                    'file_ext'   => '',
                    'file_name'  => $server_name,
                    'file_mime'  => '',
                    'uhash'      => md5($vvv),
                    'md5file'    => md5($vvv),
                    'server_name'  => $server_name,
                    'is_remote'  => 1,
                    'sort_order' => $sort_order,
                    'add_time'   => getTime(),
                );
            }
        }
        
        $data_new_new = [];
        if (!empty($data) && !empty($data_new)) {
            $data_new_new = array_merge($data,$data_new);
        }else if (!empty($data)) {
            $data_new_new = $data;
        }else if (!empty($data_new)) {
            $data_new_new = $data_new;
        }

        $this->delDownFile($aid);
        if (!empty($data_new_new)) {
            Db::name('DownloadFile')->insertAll($data_new_new);
        }
    }
}