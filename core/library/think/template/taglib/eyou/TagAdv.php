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

namespace think\template\taglib\eyou;

/**
 * 广告
 */
class TagAdv extends Base
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取广告
     * @author wengxianhu by 2018-4-20
     */
    public function getAdv($pid = '', $where = '', $orderby = '')
    {
        if (empty($pid)) {
            echo '标签adv报错：缺少属性 pid 。';
            return false;
        }

        /*多语言*/
        $pid = model('LanguageAttr')->getBindValue($pid, 'ad_position');
        if (empty($pid)) {
            echo '标签adv报错：找不到与第一套【'.self::$main_lang.'】语言关联绑定的属性 pid 值。';
            return false;
        }
        /*--end*/

        $uiset = I('param.uiset/s', 'off');
        $uiset = trim($uiset, '/');

        $args = [$pid, $where, $orderby, $uiset, self::$home_lang];
        $cacheKey = 'taglib-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (!empty($result) && 'rand' != $orderby) {
            return $result;
        }

        // $times = time();
        if (empty($where)) { // 新逻辑
            // 排序
            switch ($orderby) {
                case 'hot':
                case 'click':
                    $orderby = 'a.click desc';
                    break;

                case 'now':
                case 'new': // 兼容写法
                    $orderby = 'a.add_time desc';
                    break;
                    
                case 'id':
                    $orderby = 'a.id desc';
                    break;

                case 'sort_order':
                    $orderby = 'a.sort_order asc';
                    break;

                case 'rand':
                    $orderby = 'rand()';
                    break;
                
                default:
                    if (empty($orderby)) {
                        $orderby = 'a.sort_order asc, a.id desc';
                    }
                    break;
            }
            // $where = "b.status = 1 AND a.pid={$pid} and a.start_time < {$times} and (a.end_time > {$times} OR a.end_time = 0) and a.status = 1";
            $where = [
                'a.pid' => $pid,
                'a.status'  => 1,
                'b.status'  => 1,
                'b.lang'    => self::$home_lang,
            ];
            $result = M("ad")->alias('a')
                ->field("a.*")
                ->join('__AD_POSITION__ b', 'b.id = a.pid', 'LEFT')
                ->where($where)
                ->orderRaw($orderby)
                ->cache(true,EYOUCMS_CACHE_TIME,"ad") // 如果查询条件有时间字段，一定要去掉这行，避免产生一堆缓存文件
                ->select();
        } else {
            $adpRow = M("ad_position")->where(['id'=>$pid, 'status'=>1])->count();
            if (empty($adpRow)) {
                return false;
            }
            // 排序
            switch ($orderby) {
                case 'hot':
                case 'click':
                    $orderby = 'click desc';
                    break;

                case 'now':
                case 'new': // 兼容写法
                    $orderby = 'add_time desc';
                    break;
                    
                case 'id':
                    $orderby = 'id desc';
                    break;

                case 'sort_order':
                    $orderby = 'sort_order asc';
                    break;

                case 'rand':
                    $orderby = 'rand()';
                    break;
                
                default:
                    if (empty($orderby)) {
                        $orderby = 'sort_order asc, id desc';
                    }
                    break;
            }
            $result = M("ad")->field("*")
                ->where($where)
                ->where('lang', self::$home_lang)
                ->orderRaw($orderby)
                ->cache(true,EYOUCMS_CACHE_TIME,"ad")
                ->select();
        }
        
        foreach ($result as $key => $val) {
            if (1 == $val['media_type']) {
                $val['litpic'] = handle_subdir_pic(get_default_pic($val['litpic'])); // 默认无图封面
            } else if (2 == $val['media_type']) {
                $val['litpic'] = handle_subdir_pic($val['litpic'], 'media');
            }
            $val['target'] = ($val['target'] == 1) ? 'target="_blank"' : 'target="_self"';
            $val['intro'] = htmlspecialchars_decode($val['intro']);
            /*支持子目录*/
            $val['intro'] = handle_subdir_pic($val['intro'], 'html');
            /*--end*/
            if ($uiset == 'on') {
                $val['links'] = "javascript:void(0);";
            }
            $result[$key] = $val;
        }

        cache($cacheKey, $result, null, 'ad');

        return $result;
    }
}