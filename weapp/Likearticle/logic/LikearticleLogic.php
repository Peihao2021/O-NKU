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

namespace weapp\Likearticle\logic;

/**
 * 业务逻辑
 */
class LikearticleLogic
{
    /**
     *  自动获取关键字
     *
     * @access    public
     * @param     string  $title  标题
     * @param     array  $body  内容
     * @return    string
     */
    public function getSplitword($title = '', $body = '' )
    {
        weapp_vendor('splitword.autoload', 'Likearticle');
        $keywords = '';
        $kw = new \keywords();
        $keywords = $kw->GetSplitWord($title, $body);

        return $keywords;
    }
}
