<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 陈风任 <491085389@qq.com>
 * Date: 2018-12-11
 */

namespace weapp\PictureCleaning\controller;

use think\Page;
use think\AjaxPage;
use think\Db;
use think\Config;
use app\common\controller\Weapp;

/**
 * 插件的控制器
 */
class PictureCleaning extends Weapp
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
    public function __construct() {
        parent::__construct();
        $this->db = Db::name('WeappPictureCleaning');

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件使用说明
     */
    public function doc()
    {
        return $this->fetch('doc');
    }

    // 插件首页
    public function index()
    {
        return $this->fetch('index');
    }

    // 获取未使用文件个数并存入数据库记录
    public function picture_query()
    {
        ini_set("memory_limit", "-1");

        $file_image = $mysql_image = $result = [];

        // 获取项目文件中的图片
        $file_image  = $this->get_file_image();

        // 获取数据库中的所有图片路径
        $mysql_image = $this->get_mysql_image();

        // 对比处理图片，返回差集
        $result = array_diff($file_image, $mysql_image);

        // 图片数据处理
        if (is_array($result) && !empty($result)) {
            // 删除数据库所有未被清理的图片
            $this->db->where('status', 0)->delete(true);

            $saveData = [];
            foreach ($result as $value) {
                $nowData = [
                    'url' => handle_subdir_pic($value),
                    'add_time' => getTime(),
                    'update_time' => getTime()
                ];
                array_push($saveData, $nowData);
                $nowData = [];
                if (count($saveData) >= 3000) {
                    // 3000张图片为一个断点，执行添加数据库后继续循环操作
                    $this->savePicData($saveData);
                    $saveData = [];
                }
            }
        }

        // 存在相片且不足3000时执行
        if (!empty($saveData)) $this->savePicData($saveData);

        // 返回结果
        $count = count($result);
        $this->success('正在加载图片', null, ['count' => $count]);
    }

    // AJAX查看文件-未清理
    public function picture_list()
    {
        // 查询条件
        $where['status'] = 0; // 未清理数据

        // 查询分页
        $Count = $this->db->where($where)->order('id asc')->count('id');
        $pageObj = new AjaxPage($Count, 56); // config('paginate.list_rows')

        // 查询图片数据
        $ResultData = $this->db->where($where)->order('id asc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $PicArray = ['.gif', '.jpeg', '.png', '.bmp', '.jpg', '.ico', '.webp'];
        foreach ($ResultData as $key => $value) {
            $PicType = strtolower(substr($value['url'], -4));
            if (!in_array($PicType, $PicArray)) {
                $ResultData[$key]['url'] = ROOT_DIR . '/public/static/common/images/Archive.png';
            }
        }

        $this->assign('times',  date('Ymd'));
        $this->assign('result', $ResultData);
        $this->assign('Page', $pageObj->show());

        // 查询剩余未处理的图片数量
        $SurplusCount = $this->db->where($where)->count('id');
        $this->assign('surplus_count', $SurplusCount);
        return $this->fetch('picture_list');
    }

    // 移动选中图片至插件本身的回收站
    public function image_deal_with()
    {
        if (IS_AJAV_POST) {
            $post = input('post.');
            if (empty($post) || empty($post['pic_arr']) || empty($post['type'])) $this->error("请正确选择操作");
            
            // 查询图片清理查询数据表数据，已扫描但未处理文件
            $where = [
                'status' => 0,
                'id' => ['IN', $post['pic_arr']]
            ];
            $ResultData = $this->db->field('id, url')->where($where)->select();

            // 执行图片删除或移动操作
            $DelNum = 0;
            $DeleteID = [];
            if ('Delete' == $post['type']) {
                // 删除操作
                if (is_array($ResultData) && !empty($ResultData)) {
                    foreach ($ResultData as $value) {
                        if (!empty($value['url'])) {
                            // 处理文件的路径
                            $url = substr($value['url'], strlen(ROOT_DIR));
                            $url = iconv('UTF-8', 'GB2312', ROOT_PATH . $url);
                            if (@unlink($url)) {
                                $DelNum++;
                                array_push($DeleteID, $value['id']);
                            }
                        }
                    }
                    if (!empty($DelNum) && !empty($DeleteID)) {
                        // 删除数据中的数据
                        $where['id'] = ['IN', $DeleteID];
                        $this->db->where($where)->delete();

                        // 返回提示
                        $this->success('成功删除' . $DelNum . '个文件');
                    }
                } else {
                    $this->error("请选择需要删除的文件");
                }

            } else if ('CleanUp' == $post['type']) {
                // 移动操作
                if (is_array($ResultData) && !empty($ResultData)) {
                    // 移动相片的目录
                    $times = date('Ymd');

                    // 处理文件移动
                    foreach ($ResultData as $value) {
                        // 处理文件的路径
                        $url = substr($value['url'], strlen(ROOT_DIR));
                        $url = iconv('UTF-8', 'GB2312', ROOT_PATH . $url);

                        // 获取需要创建的文件夹路径
                        $path  = substr($value['url'], 0, strrpos($value['url'], "/"));
                        $datapath = DATA_PATH . 'tempimg/' . $times . $path;

                        // 创建文件夹，权限0777，true表示创建多级文件夹
                        @mkdir($datapath, 0777, true);

                        // 移动文件夹
                        if (@rename($url, DATA_PATH . 'tempimg/' . $times . $value['url'])) {
                            $DelNum++;
                            array_push($DeleteID, $value['id']);
                        }
                    }
                    if (!empty($DeleteID) && !empty($DelNum)) {
                        // 修改数据中的数据
                        $where['id'] = ['IN', $DeleteID];
                        $saveData = [
                            'status' => '1',
                            'update_time' => getTime(),
                            'table_of_ontents' => $times
                        ];
                        $this->db->where($where)->update($saveData);

                        // 返回提示
                        $this->success('成功移动' . $DelNum . '个文件');
                    }

                } else {
                    $this->error("请选择需要移动的文件");
                }
            }
        }
    }

    // 获取项目文件中的图片
    private function get_file_image()
    {
        $filenames = [];
        $public_path = 'public/upload/';
        if (is_dir($public_path)) {
            $upload    = $this->getDirFile($public_path, '/public/upload');
            $filenames = $upload;
        }

        $uploads_path = 'uploads/';
        if (is_dir($uploads_path)) {
            $uploads   = $this->getDirFile($uploads_path, '/uploads');
            $filenames = $uploads;
        }

        if (!empty($upload) && !empty($uploads)) {
            $filenames = array_merge($upload, $uploads);
        }
        
        if (!empty($filenames)) {
            $ImagesArray = array('.gif', '.jpeg', '.png', '.bmp', '.jpg', '.ico', '.webp');
            $ReturnFiles = [];
            foreach ($filenames as $key => $value) {
                $type = strtolower(substr($value, -4));
                if(in_array($type, $ImagesArray)) array_push($ReturnFiles, $value);
            }
            return $ReturnFiles;
        } else {
            return $filenames;
        }
    }

    // 递归读取文件夹文件
    private function getDirFile($directory = '', $dir_name = '', &$arr_file = array())
    {
        if (!file_exists($directory)) return false;

        $mydir = dir($directory);
        while($file = $mydir->read())
        {
            if ('weapp' != $file && 'thumb' != $file) {
                if((is_dir("$directory/$file")) AND ($file != ".") AND ($file != "..")) {
                    if (!empty($dir_name)) {
                        getDirFile("$directory/$file", "$dir_name/$file", $arr_file);
                    } else {
                        getDirFile("$directory/$file", "$file", $arr_file);
                    }
                    unset($file);
                } else if(($file != ".") AND ($file != "..")) {
                    if (!empty($dir_name)) {
                        $arr_file[] = "$dir_name/$file";
                    } else {
                        $arr_file[] = "$file";
                    }
                }
            }
            unset($file);
        }
        $mydir->close();
        return $arr_file;
    }

    // 获取数据库中的所有图片路径
    private function get_mysql_image()
    {
        // 获取数据库表名和字段
        $array = $this->get_array();
        $database_data = $this->getDatabaseData($array);
        return $database_data;
    }

    // 拼装数据库表和字段
    private function get_array()
    {
        // 自定义字段获取逻辑
        $where = 'a.ifsystem = 0 and (a.dtype = "img" || a.dtype = "imgs" || a.dtype = "htmltext")';
        $result = Db::name('channelfield')->field('a.channel_id, a.name as field, a.dtype, b.table')
            ->alias('a')
            ->join('__CHANNELTYPE__ b', 'a.channel_id = b.id', 'LEFT')
            ->order('a.channel_id desc')
            ->where($where)
            ->select();
        foreach ($result as $key => $value) {
            if (Config::get('global.arctype_channel_id') == $value['channel_id']) { 
                $result[$key]['table'] = 'arctype'; 
            } else {
                $result[$key]['table'] = $value['table'] . '_content';
            }
        }

        // 获取内置的config配置中的数据表和字段
        $array = Config::get('global.get_tablearray');

        // 合并两个数组
        $data_new = array_merge($result, $array);

        // 返回结果集
        return $data_new;
    }

    // 获取数据中指定数据表的图片和文件路径
    private function getDatabaseData($array = [])
    {
        $data = $hello = array();
        $i = 0;
        foreach ($array as $value) {
            $where  = $value['field'] . "<> '' ";
            $result = M($value['table'])->field($value['field'])->where($where)->select();
            // 查找多余未使用文件
            foreach ($result as $vv) {
                if ('litpic' == $value['field']) {
                    $path = parse_url($vv['litpic']);
                    if ($path['host']) {
                        $data[$i] = $path['path'];
                    } else {
                        $data[$i] = $this->images_remove_sub($path['path']);
                    }
                    $i++;
                } else if ('image_url' == $value['field']) {
                    $path = parse_url($vv['image_url']);
                    if ($path['host']) {
                        $data[$i] = $path['path'];
                    } else {
                        $data[$i] = $this->images_remove_sub($path['path']);
                    }
                    $i++;
                } else if ('logo' == $value['field']) {
                    $path = parse_url($vv['logo']);
                    if ($path['host']) {
                        $data[$i] = $path['path'];
                    } else {
                        $data[$i] = $this->images_remove_sub($path['path']);
                    }
                    $i++;
                } else if ('file_url' == $value['field']) {
                    $path = parse_url($vv['file_url']);
                    if ($path['host']) {
                        $data[$i] = $path['path'];
                    } else {
                        $data[$i] = $this->images_remove_sub($path['path']);
                    }
                    $i++;
                } else if ('head_pic' == $value['field']) {
                    $path = parse_url($vv['head_pic']);
                    if ($path['host']) {
                        $data[$i] = $path['path'];
                    } else {
                        $data[$i] = $this->images_remove_sub($path['path']);
                    }
                    $i++;
                } else if ('intro' == $value['field']) {
                    $str = htmlspecialchars_decode($vv['intro']);
                    $str = strip_tags($str, '<img>');
                    preg_match_all('/\<img(.*?)src\=[\'|\"]([\w:\/\.\-]+)[\'|\"]/i', $str, $matches);
                    $match = $matches[2];
                    foreach ($match as $vvv) {
                        $data[$i] = $vvv;
                        $i++;
                    }
                } else if ('content' == $value['field']) {
                    $str = htmlspecialchars_decode($vv['content']);
                    $str = strip_tags($str, '<img>');
                    preg_match_all('/\<img(.*?)src\=[\'|\"]([\w:\/\.\-]+)[\'|\"]/i', $str, $matches);
                    $match = $matches[2];
                    foreach ($match as $vvv) {
                        $data[$i] = $vvv;
                        $i++;
                    }
                } else if ('config' == $value['table']) {
                    $strlen = strlen(ROOT_DIR);
                    if (substr($vv['value'], $strlen, 7 ) == '/public' || substr($vv['value'], $strlen, 7 ) == '/upload') {
                        $data[$i] = $vv['value'];
                        $i++;
                    }
                } else if ('ui_config' == $value['table']) {
                    $values = json_decode($vv['value'],true);
                    $str = htmlspecialchars_decode($values['info']['value']);
                    $str = strip_tags($str, '<img>');
                    preg_match_all('/\<img(.*?)src\=[\'|\"]([\w:\/\.\-]+)[\'|\"]/i', $str, $matches);
                    $match = $matches[2];
                    foreach ($match as $vvv) {
                        $data[$i] = $vvv;
                        $i++;
                    }
                } else if ($value['channel_id']) {
                    if ('imgs' == $value['dtype']) {
                        $hello = explode(',',$vv[$value['field']]);
                    } else if ('img' == $value['dtype']) {
                        $path = parse_url($vv[$value['field']]);
                        if ($path['host']) {
                            $data[$i] = $path['path'];
                        } else {
                            $data[$i] = $this->images_remove_sub($path['path']);
                        }
                        $i++;
                    } else if ('htmltext' == $value['dtype']) {
                        $str = htmlspecialchars_decode($vv[$value['field']]);
                        $str = strip_tags($str, '<img>');
                        preg_match_all('/\<img(.*?)src\=[\'|\"]([\w:\/\.\-]+)[\'|\"]/i', $str, $matches);
                        $match = $matches[2];
                        foreach ($match as $vvv) {
                            $data[$i] = $vvv;
                            $i++;
                        }
                    }
                }
            }
        }

        $data = array_merge($data, $hello);
        $data = array_unique($data);
        return $data;
    }

    // 在图片清理插件读取数据库图片时，删除子目录前缀
    private function images_remove_sub($images = null)
    {
        $images = preg_replace('#^(/[/\w]+)?(/public/upload/|/uploads/)#i', '$2', $images);
        return $images;
    }

    // 批量添加传入的图片
    private function savePicData($saveData = [])
    {
        if (!empty($saveData)) $this->db->insertAll($saveData);
    }



    // 图片回收站列表页
    public function lists()
    {
        $list = array();
        $keywords = input('keywords/s');

        $map = array();
        if (!empty($keywords)) {
            $map['table_of_ontents'] = array('LIKE', "%{$keywords}%");
        }

        $map['status'] = 1; // status=1为被清理数据

        $count = $this->db->where($map)
            ->group('table_of_ontents')
            ->count('id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $result = $this->db->field('*, count(id) AS count')
            ->where($map)
            ->group('table_of_ontents')
            ->order('id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('result', $result); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('lists');
    }

    // 查看文件-已清理
    public function clean_up_list()
    {
        $table_of_ontents = (int)input('param.table_of_ontents');
        if(!empty($table_of_ontents)) {
            $ResultData = [];

            // 查询条件
            $where = [
                // 已清理数据
                'status' => 1,
                // 查看目录
                'table_of_ontents' => $table_of_ontents,
            ];

            // 查询分页
            $Count = $this->db->where($where)->order('id asc')->count('id');
            $pageObj = new Page($Count, 56); // config('paginate.list_rows')

            // 查询图片数据
            $ResultData = $this->db->where($where)->order('id asc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
            $PicArray = ['.gif', '.jpeg', '.png', '.bmp', '.jpg', '.ico', '.webp'];
            foreach ($ResultData as $key => $value) {
                $PicType = '.' . array_pop(explode('.', $value['url']));
                if (in_array($PicType, $PicArray)) {
                    $ResultData[$key]['url'] = ROOT_DIR . '/data/tempimg/' . $table_of_ontents . $value['url'];
                } else {
                    $ResultData[$key]['url'] = ROOT_DIR . '/public/static/common/images/Archive.png';
                }
            }
            
            $this->assign('path', 'data/tempimg/' . $table_of_ontents);
            $this->assign('result', $ResultData);
            $this->assign('Page', $pageObj->show());
        }
        return $this->fetch('clean_up_list');
    }

    // 还原文件
    public function recovery()
    {
        $table_of_ontents = input('param.table_of_ontents');
        if (!empty($table_of_ontents)) {
            $result = $this->db->where("table_of_ontents", $table_of_ontents)->select();
            $url = get_arr_column($result[0], 'url');

            $DeleteID = $this->db->where("table_of_ontents", $table_of_ontents)->delete();
            if(!empty($DeleteID)) {
                recurse_copy(DATA_PATH . 'tempimg/' . $table_of_ontents . ROOT_DIR, ROOT_PATH);
                delFile(DATA_PATH . 'tempimg/' . $table_of_ontents . ROOT_DIR, true);
                adminLog('还原' . $this->weappInfo['name'] . '：' . implode(',', $url));
                $this->success('还原成功！');
            } else {
                $this->error('还原失败！');
            }
        } else {
            $this->error('参数错误！');
        }
    }
    
    // 单个删除文件
    public function del()
    {
        $table_of_ontents = input('param.table_of_ontents');
        if (!empty($table_of_ontents)) {
            $result = $this->db->where("table_of_ontents" ,$table_of_ontents)->select();
            $url = get_arr_column($result['0'], 'url');

            $DeleteID = $this->db->where("table_of_ontents", $table_of_ontents)->delete();
            if (!empty($DeleteID)) {
                delFile(DATA_PATH . 'tempimg' . DS . $table_of_ontents, true);
                adminLog('删除' . $this->weappInfo['name'] . '：' . implode(',', $url));
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        } else {
            $this->error('参数错误！');
        }
    }

    // 批量删除文件
    public function batch_del()
    {
        $table_arr = input('param.table_of_ontents/a');
        $table_arr = eyIntval($table_arr);
        if(!empty($table_arr)){
            $result = $this->db->where("table_of_ontents", 'IN', $table_arr)->select();
            $url = get_arr_column($result['0'], 'url');

            $DeleteID = $this->db->where("table_of_ontents",'IN',$table_arr)->delete();
            if(!empty($DeleteID)){
                foreach ($table_arr as $value) {
                    delFile(DATA_PATH . 'tempimg' . DS . $value, true);
                }
                adminLog('删除' . $this->weappInfo['name'] . '：' . implode(',', $url));
                $this->success("操作成功!");
            } else {
                $this->error("操作失败!");
            }
        } else {
            $this->error("参数有误!");
        }
    }
}