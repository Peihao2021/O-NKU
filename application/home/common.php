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

// 模板错误提示
switch_exception();

if (!function_exists('set_home_url_mode')) 
{
    // 设置前台URL模式
    function set_home_url_mode() {
        $uiset = I('param.uiset/s', 'off');
        $uiset = trim($uiset, '/');
        $seo_pseudo = tpCache('seo.seo_pseudo');
        if ($seo_pseudo == 1 || $uiset == 'on') {
            config('url_common_param', true);
            config('url_route_on', false);
        } elseif ($seo_pseudo == 2 && $uiset != 'on') {
            config('url_common_param', false);
            config('url_route_on', true);
        } elseif ($seo_pseudo == 3 && $uiset != 'on') {
            config('url_common_param', false);
            config('url_route_on', true);
        }
    }
}

if (!function_exists('set_arcseotitle')) 
{
    /**
     * 设置内容标题
     */
    function set_arcseotitle($title = '', $seo_title = '', $typename = '', $typeid = 0, $site_info = [])
    {
        /*针对没有自定义SEO标题的文档*/
        $title = trim($title);
        $seo_title = trim($seo_title);
        $typename = trim($typename);
        if (empty($seo_title)) {
            static $web_name = null;
            if (null === $web_name) {
                $web_name = tpCache('web.web_name');
                $web_name = trim($web_name);
            }
            static $seoConfig = null;
            null === $seoConfig && $seoConfig = tpCache('seo');
            $seo_viewtitle_format = !empty($seoConfig['seo_viewtitle_format']) ? intval($seoConfig['seo_viewtitle_format']) : 0;
            $seo_title_symbol = isset($seoConfig['seo_title_symbol']) ? htmlspecialchars_decode($seoConfig['seo_title_symbol']) : '_';
            switch ($seo_viewtitle_format) {
                case '1':
                    $seo_title = $title;
                    break;
                
                case '3':
                    $seo_title = $title;
                    if (!empty($typename)) {
                        $seo_title .= $seo_title_symbol.$typename;
                    }
                    $seo_title .= $seo_title_symbol.$web_name;
                    break;
                
                case '2':
                default:
                    $opencodetype = config('global.opencodetype');
                    if (1 == $opencodetype && in_array($typeid, [3,9,10])) {
                        $seo_title = '';
                    } else {
                        $seo_title = $title.$seo_title_symbol.$web_name;
                    }
                    break;
            }
        }
        /*--end*/

        // 城市分站的seo
        empty($site_info) && $site_info = cookie('site_info');
        $seo_title = site_seo_handle($seo_title, $site_info);

        return $seo_title;
    }
}

if (!function_exists('set_typeseotitle')) 
{
    /**
     * 设置栏目标题
     */
    function set_typeseotitle($typename = '', $seo_title = '', $site_info = [])
    {
        static $lang = null;
        $lang === null && $lang = get_home_lang();
        static $seoConfig = null;
        null === $seoConfig && $seoConfig = tpCache('seo');
        $seo_liststitle_format = !empty($seoConfig['seo_liststitle_format']) ? intval($seoConfig['seo_liststitle_format']) : 0;
        $seo_title_symbol = isset($seoConfig['seo_title_symbol']) ? htmlspecialchars_decode($seoConfig['seo_title_symbol']) : '_';

        if (empty($seo_title)) { // 针对没有自定义SEO标题的列表
            static $web_name = null;
            $web_name === null && $web_name = tpCache('web.web_name');
            $old_typename = $typename;
            $page = I('param.page/d', 1);
            if ($page > 1) {
                if (in_array($lang, ['cn'])) {
                    $typename .= "{$seo_title_symbol}第{$page}页";
                } else {
                    $typename .= "{$seo_title_symbol}{$page}";
                }
            }
            switch ($seo_liststitle_format) {
                case '1':
                    $seo_title = $old_typename.$seo_title_symbol.$web_name;
                    break;

                case '3':
                    $seo_title = $old_typename;
                    break;

                case '4':
                    $seo_title = $typename;
                    break;
                
                case '2':
                default:
                    $seo_title = $typename.$seo_title_symbol.$web_name;
                    break;
            }
        } else {
            if (!in_array($seo_liststitle_format, [1,3])) {
                $page = I('param.page/d', 1);
                if ($page > 1) {
                    if (in_array($lang, ['cn'])) {
                        $seo_title .= "{$seo_title_symbol}第{$page}页";
                    } else {
                        $seo_title .= "{$seo_title_symbol}{$page}";
                    }
                }
            }
        }

        // 城市分站的seo
        empty($site_info) && $site_info = cookie('site_info');
        $seo_title = site_seo_handle($seo_title, $site_info);

        return $seo_title;
    }
}

if (!function_exists('getArcLevelName')) 
{
    /**
     * 获取文档会员权限对应的名称
     */
    function getArcLevelName($arc_level_id = 0)
    {
        $level_name = '';
        static $users_level_list = null;
        if (null === $users_level_list) {
            $users_level_list = \think\Db::name('users_level')->field('level_id,level_name')->getAllWithIndex('level_id');
        }
        if (!empty($users_level_list[$arc_level_id])) {
            $level_name = $users_level_list[$arc_level_id]['level_name'];
        }
        return $level_name;
    }
}