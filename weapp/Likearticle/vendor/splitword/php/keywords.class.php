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

class keywords
{
    /**
     *  自动获取关键字
     *
     * @access    public
     * @param     string  $title  标题
     * @param     array  $body  内容
     * @return    string
     */
    public function GetSplitWord($title = '', $body = '' )
    {
        $keywords = '';
        require_once('splitword.class.php');
        $sp = new splitword();
        $sp->SetSource($title);
        $returndata = $sp->StartAnalysis();
        if (true !== $returndata) {
            return $returndata;
        }
        $titleindexs = preg_replace("/#p#|#e#/",'',$sp->GetFinallyIndex());
        $sp->SetSource($this->Html2Text($body));
        $sp->StartAnalysis();
        $allindexs = preg_replace("/#p#|#e#/",'',$sp->GetFinallyIndex());
        
        if(is_array($allindexs) && is_array($titleindexs))
        {
            foreach($titleindexs as $k => $v)
            {
                if(strlen($keywords.$k)>=60)
                {
                    break;
                }
                else
                {
                    if(strlen($k) <= 2) continue;
                    $keywords .= $k.',';
                }
            }
            foreach($allindexs as $k => $v)
            {
                if(strlen($keywords.$k)>=60)
                {
                    break;
                }
                else if(!in_array($k,$titleindexs))
                {
                    if(strlen($k) <= 2) continue;
                    $keywords .= $k.',';
                }
            }
        }
        $sp = null;
        
        $keywords = trim($keywords, ',');

        return $keywords;
    }

    /**
     *  HTML转换为文本
     *
     * @param    string  $str 需要转换的字符串
     * @param    string  $r   如果$r=0直接返回内容,否则需要使用反斜线引用字符串
     * @return   string
     */
    public function Html2Text($str,$r=0)
    {
        if($r==0)
        {
            return $this->SpHtml2Text($str);
        }
        else
        {
            $str = $this->SpHtml2Text(stripslashes($str));
            return addslashes($str);
        }
    }

    private function SpHtml2Text($str)
    {
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$str);
        $alltext = "";
        $start = 1;
        for($i=0;$i<strlen($str);$i++)
        {
            if($start==0 && $str[$i]==">")
            {
                $start = 1;
            }
            else if($start==1)
            {
                if($str[$i]=="<")
                {
                    $start = 0;
                    $alltext .= " ";
                }
                else if(ord($str[$i])>31)
                {
                    $alltext .= $str[$i];
                }
            }
        }
        $alltext = str_replace("　"," ",$alltext);
        $alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
        $alltext = preg_replace("/[ ]+/s"," ",$alltext);
        return $alltext;
    }
}