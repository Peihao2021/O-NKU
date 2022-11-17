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

use think\Db;

/**
 * 标签
 */
class TagTag extends Base
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取标签
     * @author wengxianhu by 2018-4-20
     */
    public function getTag($getall = 0, $typeid = '', $aid = 0, $row = 30, $sort = 'new', $type = '')
    {
        $aid = !empty($aid) ? $aid : $this->aid;
        $getall = intval($getall);
        $result = false;
        $condition = [];

        $args = [$aid, $getall, $typeid, $row, $sort, $type, self::$home_lang];
        $cacheKey = 'taglib-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (!empty($result) && 'rand' != $sort) {
            return $result;
        }

        if ($getall == 0 && $aid > 0) {
            $condition['a.aid'] = $aid;
            $condition['a.lang'] = self::$home_lang;
            $result = Db::name('taglist')
                ->alias('a')
                ->field('a.*, a.tid AS tagid')
                ->where($condition)
                ->limit($row)
                ->select();

        } else {
            /*多语言*/
            if (!empty($typeid)) {
                $typeid = model('LanguageAttr')->getBindValue($typeid, 'arctype');
            }
            /*--end*/
            
            if (!empty($typeid)) {
                $typeid = $this->getTypeids($typeid, $type);
                $tid_list = Db::name('taglist')
                    ->where([
                        'typeid'    => ['IN', $typeid],
                        'lang'      => self::$home_lang,
                    ])
                    ->group('tid')
                    ->column('tid');
                $condition['a.id'] = array('in', $tid_list);
            }
            if($sort == 'rand') $orderby = 'rand() ';
            else if($sort == 'week') $orderby=' a.weekcc DESC ';
            else if($sort == 'month') $orderby=' a.monthcc DESC ';
            else if($sort == 'hot') $orderby=' a.count DESC ';
            else if($sort == 'total') $orderby=' a.total DESC ';
            else if($sort == 'aid') $orderby=' a.id ASC ';
            else $orderby = 'a.id DESC  ';

            $condition['a.lang'] = self::$home_lang;

            $result = Db::name('tagindex')
                ->alias('a')
                ->field('a.*, a.id AS tagid, b.arcrank')
                ->join('taglist b','a.id=b.tid','left')
                ->where($condition)
                ->group('a.id')
                ->orderRaw($orderby)
                ->limit($row)
                ->select();
        }

        foreach ($result as $key => $val) {
            if (is_numeric($val['arcrank']) && 0 > $val['arcrank']) {
                unset($result[$key]);
                continue;
            }
            $val['link'] = tagurl('home/Tags/lists', array('tagid'=>$val['tagid']));
            $val['target'] = ' target="_blank" ';
            $result[$key] = $val;
        }

        cache($cacheKey, $result, null, 'taglist');

        return $result;
    }
    
    private function getTypeids($typeid, $type = '')
    {
        $typeidArr = $typeid;
        if (!is_array($typeidArr)) {
            $typeidArr = explode(',', $typeid);
        }
        $typeids = [];
        
        foreach($typeidArr as $key => $tid) {
            $result = [];
            switch ($type) {
                case 'son': // 下级栏目
                    $result = model('Arctype')->getSon($tid, false);
                    break;

                case 'self': // 同级栏目
                    $result = model('Arctype')->getSelf($tid);
                    break;

                case 'top': // 顶级栏目
                    $result = model('Arctype')->getTop();
                    break;

                case 'sonself': // 下级、同级栏目
                    $result = model('Arctype')->getSon($tid, true);
                    break;

                case 'first': // 第一级栏目
                    $result = model('Arctype')->getFirst($tid);
                    break;

                default:
                    $result = [
                        [
                            'id'    => $tid,
                        ]
                    ];
                    break;
            }

            if (!empty($result)) {
                $typeids = array_merge($typeids, get_arr_column($result, 'id'));
            }
        }
        
        return $typeids;
    }
}