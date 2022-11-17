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

namespace app\admin\model;

use think\Db;
use think\Model;

/**
 * 产品图片
 */
class ProductImg extends Model
{
    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 获取单条产品的所有图片
     * @author 小虎哥 by 2018-4-3
     */
    public function getProImg($aid, $field = '*')
    {
        $result = Db::name('ProductImg')->field($field)
            ->where('aid', $aid)
            ->order('sort_order asc')
            ->select();

        return $result;
    }

    /**
     * 删除单条产品的所有图片
     * @author 小虎哥 by 2018-4-3
     */
    public function delProImg($aid = array())
    {
        if (!is_array($aid)) {
            $aid = array($aid);
        }
        $result = Db::name('ProductImg')->where(array('aid'=>array('IN', $aid)))->delete();

        return $result;
    }

    /**
     * 保存产品图片
     * @author 小虎哥 by 2018-4-3
     */
    public function saveimg($aid, $post = array())
    {
        $proimg = isset($post['proimg']) ? $post['proimg'] : array();
        $imgintro = isset($post['imgintro']) ? $post['imgintro'] : array();

        if (!empty($proimg) && count($proimg) > 1) {
            array_pop($proimg); // 弹出最后一个
            // 删除产品图片
            $this->delProImg($aid);
             // 添加图片
            $data = array();
            $sort_order = 0;
            foreach($proimg as $key => $val)
            {
                if($val == null || empty($val))  continue;
                
                $img_info = array();
                $filesize = 0;
                if (is_http_url($val)) {
                    $imgurl = handle_subdir_pic($val);
                } else {
                    $imgurl = ROOT_PATH.ltrim($val, '/');
                    $filesize = @filesize('.'.$val);
                }
                $img_info = @getimagesize($imgurl);
                $width = isset($img_info[0]) ? $img_info[0] : 0;
                $height = isset($img_info[1]) ? $img_info[1] : 0;
                $type = isset($img_info[2]) ? $img_info[2] : 0;
                $attr = isset($img_info[3]) ? $img_info[3] : '';
                $mime = isset($img_info['mime']) ? $img_info['mime'] : '';
                $title = !empty($post['title']) ? $post['title'] : '';
                $intro = !empty($imgintro[$key]) ? $imgintro[$key] : '';
                ++$sort_order;
                $data[] = array(
                    'aid' => $aid,
                    'title' => $title,
                    'image_url'   => $val,
                    'intro'   => $intro,
                    'width' => $width,
                    'height' => $height,
                    'filesize'  => $filesize,
                    'mime'  => $mime,
                    'sort_order'    => $sort_order,
                    'add_time' => getTime(),
                );
            }
            if (!empty($data)) {
                Db::name('ProductImg')->insertAll($data);

                // 没有封面图时，取第一张图作为封面图
                $litpic = isset($post['litpic']) ? $post['litpic'] : '';
                if (empty($litpic)) {
                    $litpic = $data[0]['image_url'];
                    Db::name('archives')->where(array('aid'=>$aid))->update(array('litpic'=>$litpic, 'update_time'=>getTime()));
                }
            }
            delFile(UPLOAD_PATH."product/thumb/$aid"); // 删除缩略图
        }
    }
}