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

namespace weapp\Linkkeyword\model;

use think\Db;
use think\Model;

/**
 * 模型
 */
class LinkkeywordModel extends Model
{

    /**
     * 数据表名，不带前缀
     */
    public $name = 'weapp_linkkeyword';

    const STATUS_ENABLE = 1;//关键字启用
    const STAUTS_DISABLE = 0;//关键字禁用
    const WEAPP_STATUS_ENABLE = 1;//本插件启用
    const WEAPP_STATUS_DISABLE = 0;//本插件禁用
    const WEAPP_CODE = 'Linkkeyword';//插件标识，对应weapp表里的code字段
    const TABEL_NAME = 'weapp_linkkeyword';//表名

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 获取关键字和链接列表
     */
    public function getList()
    {
        /*有页面缓存，页面缓存清除时才会走这里的查询，为了节省添加、编辑、删除等操作时清除查询缓存的操作，这个查询就不做缓存处理了*/
        /*->->cache(true, EYOUCMS_CACHE_TIME, self::TABEL_NAME)*/
        $list = self::field('title,target,url')->where('status', self::STATUS_ENABLE)->order('id', 'asc')->select();

        return $list;
    }

    public function handle_content($content)
    {

        /*处理每个插件获取的内容应当是处理过的内容 - 【插件涉及到内容替换必备代码】*/
        $contentNew = $content;
        $linkKeyword = $this->getList();

        preg_match_all('/<img.*(\/)?>/iUs', $contentNew, $imginfo);
        $imginfo = $imginfo[0];
        if ($imginfo) {
            foreach ($imginfo as $key => $value) {
                $imgmd5 = md5($value);
                $contentNew = str_ireplace($value, $imgmd5, $contentNew);
            }
        }
        //处理内容详情
        $contentNew = $this->link_substr_replace($contentNew,$linkKeyword);
        if ($imginfo) {
            foreach ($imginfo as $key => $value) {
                $imgmd5 = md5($value);
                $contentNew = str_ireplace($imgmd5, $value, $contentNew);
            }
        }

        return $contentNew;
    }
    /*
    *  检索替换所有linkworld
    */
    public function link_substr_replace($contentNew,$linkKeyword){
        //处理内容详情
        foreach ($linkKeyword as $key => $val) {
            $contentNew = $this->link_substr_replace_one($contentNew,$val);
        }

        return $contentNew;
    }
    /*
     * 单个关键字替换
     */
    private function link_substr_replace_one($contentNew,$val){
        $keywordlength       = strlen($val['title']);   //关键字长度
        $keywordStartPosition = $this->get_keyword_start_position($contentNew,$val['title'],$keywordlength);

        if ($keywordStartPosition) {
            //替换关键字为带链接的关键字
            $target = (1 == $val['target']) ? ' target="_blank" ' : '';
            $valLink    = '<a href="'.$val['url'].'" '.$target.' >'.$val['title'].'</a>';
            $contentNew = substr_replace($contentNew, $valLink, $keywordStartPosition, $keywordlength);
        }

        return $contentNew;
    }
    /*
     * 获取合法的字符串出现的首个位置起始位置
     */
    private function get_keyword_start_position($contentNew,$title,$keywordlength,$keywordEndPosition = 0){
        $keywordStartPosition = stripos($contentNew, $title,$keywordEndPosition);//关键字首次出现的起始位置
        if ($keywordStartPosition !== false){
            $keywordEndPosition  = $keywordStartPosition + $keywordlength;
            $contentAfterKeyword = substr($contentNew, $keywordEndPosition);//关键字首次结束位置后的字符串
            $aEndPosition        = stripos($contentAfterKeyword,'</a>');//从关键字首次结束位置后的字符串中，查找a结束标签</a>首次出现的位置
            if ($aEndPosition === 0) {
                $keywordStartPosition = $this->get_keyword_start_position($contentNew,$title,$keywordlength,$keywordEndPosition);//如果关键字后就是</a>则说明关键字已经有链接，不再替换
            } elseif ($aEndPosition !== false) {
                $contentBetweenKeywordAndAend = substr($contentAfterKeyword, 0,$aEndPosition);//关键字首次结束位置到下一个</a>标签之间的内容
                $href  = stripos($contentBetweenKeywordAndAend,'href=');;//检查关键字首次结束位置到下一个</a>标签之间的内容中，是否包含href=，如果包含，则关键字没有链接，如果不包含，则关键字已有链接
                if ($href === false) {
                    $keywordStartPosition = $this->get_keyword_start_position($contentNew,$title,$keywordlength,$keywordEndPosition);//不替换
                }
            }
        }

        return $keywordStartPosition;
    }
}