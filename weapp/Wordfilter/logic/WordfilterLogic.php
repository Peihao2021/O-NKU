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

namespace weapp\Wordfilter\logic;

/**
 * 业务逻辑
 */
class WordfilterLogic
{
    /**
     * 解码返回
     */
    public function txtAjax($file_path)
    {
        $content = $this->auto_read($file_path);
        $res = explode(PHP_EOL,$content);
        return $res;
    }

    /**
     * 自动解析编码读入文件
     * @param string $file_path 文件路径
     * @param string $charset 读取编码
     * @return string 返回读取内容
     */
    private function auto_read($file_path, $filesize = '', $charset = 'UTF-8') {
        $list = array('GBK', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
        $str = $this->fileToSrting($file_path, $filesize);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return mb_convert_encoding($str, $charset, $item);
            }
        }
        return "";
    }

    private function fileToSrting($file_path, $filesize = '') {
        //判断文件路径中是否含有中文，如果有，那就对路径进行转码，如此才能识别
        if (preg_match("/[\x7f-\xff]/", $file_path)) {
            $file_path = iconv('UTF-8', 'GBK', $file_path);
        }
        if (file_exists($file_path)) {
            $fp = fopen($file_path, "r");
            if (empty($filesize)) {
                $filesize = filesize($file_path);
            }
            $str = fread($fp, $filesize); //指定读取大小，这里默认把整个文件内容读取出来
            return $str;
        } else {
            return "";
        }
    }
}
