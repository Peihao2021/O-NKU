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

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 栏目
 */
class Arctype extends Model
{
    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 获取单条记录
     * @author wengxianhu by 2017-7-26
     */
    public function getInfo($id, $field = '', $get_parent = false)
    {
        if (empty($field)) {
            $field = 'c.*, a.*';
        }
        $field .= ', a.id as typeid';

        /*当前栏目信息*/
        $result = Db::name('Arctype')->field($field)
            ->alias('a')
            ->where('a.id', $id)
            ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
            ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
            ->find();
        /*--end*/
        if (!empty($result)) {
            if ($get_parent) {
                $result['typeurl'] = $this->getTypeUrl($result); // 当前栏目的URL
                /*获取当前栏目父级栏目信息*/
                if ($result['parent_id'] > 0) {
                    $parent_row = Db::name('Arctype')->field($field)
                        ->alias('a')
                        ->where('a.id', $result['parent_id'])
                        ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
                        ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                        ->find();
                    $ptypeurl = $this->getTypeUrl($parent_row);
                    $parent_row['typeurl'] = $ptypeurl;
                } else {
                    $parent_row = $result;
                }
                /*--end*/
                
                /*给每个父类字段开头加上p*/
                foreach ($parent_row as $key => $val) {
                    $newK = 'p'.$key;
                    $parent_row[$newK] = $val;
                }
                /*--end*/
                $result = array_merge($result, $parent_row);
            } else {
                $result = $this->parentAndTopInfo($id, $result);
            }
        }

        return $result;
    }

    /**
     * 获取指定栏目的父级和顶级栏目信息（用于前台与静态生成）
     * @author wengxianhu by 2017-7-26
     */
    public function parentAndTopInfo($id, $result = [])
    {
        $result['typeurl'] = $this->getTypeUrl($result); // 当前栏目的URL
        if (!empty($result['parent_id'])) {
            // 当前栏目的父级栏目信息
            $parent_row = Db::name('arctype')->where('id', $result['parent_id'])
                ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->find();
            $ptypeid = $parent_row['id'];
            $ptypeurl = $this->getTypeUrl($parent_row);
            $ptypename = $parent_row['typename'];
            $pdirname = $parent_row['dirname'];
            // 当前栏目的顶级栏目信息
            if (!isset($result['toptypeurl'])) {
                $allPid = $this->getAllPid($id);
                $toptypeinfo = current($allPid);
                $toptypeid = $toptypeinfo['id'];
                $toptypeurl = $this->getTypeUrl($toptypeinfo);
                $toptypename = $toptypeinfo['typename'];
                $topdirname = $toptypeinfo['dirname'];
            }
            // end
        } else {
            // 当前栏目的父级栏目信息 或 顶级栏目的信息
            $toptypeid = $ptypeid = $result['id'];
            $toptypeurl = $ptypeurl = $result['typeurl'];
            $toptypename = $ptypename = $result['typename'];
            $topdirname = $pdirname = $result['dirname'];
        }
        // 当前栏目的父级栏目信息
        $result['ptypeid'] = $ptypeid;
        $result['ptypeurl'] = $ptypeurl;
        $result['ptypename'] = $ptypename;
        $result['pdirname'] = $pdirname;
        // 当前栏目的顶级栏目信息
        !isset($result['toptypeid']) && $result['toptypeid'] = $toptypeid;
        !isset($result['toptypeurl']) && $result['toptypeurl'] = $toptypeurl;
        !isset($result['toptypename']) && $result['toptypename'] = $toptypename;
        !isset($result['topdirname']) && $result['topdirname'] = $topdirname;
        // end

        return $result;
    }

    /**
     * 根据目录名称获取单条记录
     * @author wengxianhu by 2018-4-20
     */
    public function getInfoByDirname($dirname)
    {
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.$dirname);
        $result = cache($cacheKey);
        if (empty($result)) {
            $field = 'c.*, a.*, a.id as typeid';

            $result = Db::name('Arctype')->field($field)
                ->alias('a')
                ->where('a.dirname', $dirname)
                ->join('__CHANNELTYPE__ c', 'c.id = a.current_channel', 'LEFT')
                // ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->find();
            if (!empty($result)) {
                $result['typeurl'] = $this->getTypeUrl($result);

                $result_tmp = Db::name('arctype')->where('id', $result['parent_id'])->find();
                $result['ptypeurl'] = $this->getTypeUrl($result_tmp);
            }

            cache($cacheKey, $result, null, 'arctype');
        }

        return $result;
    }

    /**
     * 检测是否有子栏目
     * @author wengxianhu by 2017-7-26
     */
    public function hasChildren($id)
    {
        if (is_array($id)) {
            $ids = array_unique($id);
            $row = Db::name('Arctype')->field('parent_id, count(id) AS total')->where([
                    'parent_id'=>['IN', $ids],
                    'current_channel'=>['neq', 51], // 过滤问答模型
                    'is_del'    => 0,
                ])->group('parent_id')->getAllWithIndex('parent_id');
            return $row;
        } else {
            $count = Db::name('Arctype')->where([
                    'parent_id' => $id,
                    'current_channel'=>['neq', 51], // 过滤问答模型
                    'is_del'    => 0,
                ])->count('id');
            return ($count > 0 ? 1 : 0);
        }
    }

    /**
     * 获取栏目的URL
     */
    public function getTypeUrl($res)
    {
        if ($res['is_part'] == 1) {
            $typeurl = get_absolute_url($res['typelink']);
        } else {
            $ctl_name = get_controller_byct($res['current_channel']);
            $typeurl = typeurl("home/{$ctl_name}/lists", $res);
        }

        return $typeurl;
    }


    /**
     * 获取指定级别的栏目列表
     * @param string type son表示下一级栏目,self表示同级栏目,top顶级栏目
     * @param boolean $self 包括自己本身
     * @author wengxianhu by 2018-4-26
     */
    public function getChannelList($id = '', $type = 'son')
    {
        $result = array();
        switch ($type) {
            case 'son':
                $result = $this->getSon($id, false);
                break;

            case 'self':
                $result = $this->getSelf($id);
                break;

            case 'top':
                $result = $this->getTop();
                break;

            case 'sonself':
                $result = $this->getSon($id, true);
                break;
        }

        return $result;
    }

    /**
     * 获取下一级栏目
     * @param string $self true表示没有子栏目时，获取同级栏目
     * @author wengxianhu by 2017-7-26
     */
    public function getSon($id, $self = false)
    {
        $result = array();
        if (empty($id)) {
            return $result;
        }

        $arctypeLogic = new \app\common\logic\ArctypeLogic();
        $arctype_max_level = intval(config('global.arctype_max_level'));
        $map = array(
            'is_hidden'   => 0,
            'status'  => 1,
            'is_del'  => 0, // 回收站功能
        );
        $res = $arctypeLogic->arctype_list($id, 0, false, $arctype_max_level, $map);

        if (!empty($res)) {
            $arr = group_same_key($res, 'parent_id');
            for ($i=0; $i < $arctype_max_level; $i++) {
                foreach ($arr as $key => $val) {
                    foreach ($arr[$key] as $key2 => $val2) {
                        if (!isset($arr[$val2['id']])) continue;
                        $val2['children'] = $arr[$val2['id']];
                        $arr[$key][$key2] = $val2;
                    }
                }
            }
            if (isset($arr[$id])) {
                $result = $arr[$id];
            }
        }

        if (empty($result) && $self == true) {
            $result = $this->getSelf($id);
        }

        return $result;
    }

    /**
     * 获取同级栏目
     * @author wengxianhu by 2017-7-26
     */
    public function getSelf($id)
    {
        $result = array();
        if (empty($id)) {
            return $result;
        }

        $map = array(
            'id'   => $id,
            'is_hidden'   => 0,
            'status'  => 1,
            'is_del'  => 0, // 回收站功能
        );
        $res = Db::name('arctype')->field('parent_id')->where($map)->find();

        if ($res) {
            $newId = $res['parent_id'];
            $arctypeLogic = new \app\common\logic\ArctypeLogic();
            $arctype_max_level = intval(config('global.arctype_max_level'));
            $map = array(
                'is_hidden'   => 0,
                'status'  => 1,
            );
            $res = $arctypeLogic->arctype_list($newId, 0, false, $arctype_max_level, $map);

            if (!empty($res)) {
                $arr = group_same_key($res, 'parent_id');
                for ($i=0; $i < $arctype_max_level; $i++) { 
                    foreach ($arr as $key => $val) {
                        foreach ($arr[$key] as $key2 => $val2) {
                            if (!isset($arr[$val2['id']])) continue;
                            $val2['children'] = $arr[$val2['id']];
                            $arr[$key][$key2] = $val2;
                        }
                    }
                }
                $result = $arr[$newId];
            }
        }

        return $result;
    }

    /**
     * 获取顶级栏目
     * @author wengxianhu by 2017-7-26
     */
    public function getTop()
    {
        $arctypeLogic = new \app\common\logic\ArctypeLogic();
        $arctype_max_level = intval(config('global.arctype_max_level'));
        $map = array(
            'is_hidden'   => 0,
            'status'  => 1,
            'is_del'  => 0, // 回收站功能
        );
        $res = $arctypeLogic->arctype_list(0, 0, false, $arctype_max_level, $map);

        $result = array();
        if (!empty($res)) {
            $arr = group_same_key($res, 'parent_id');
            for ($i=0; $i < $arctype_max_level; $i++) { 
                foreach ($arr as $key => $val) {
                    foreach ($arr[$key] as $key2 => $val2) {
                        if (!isset($arr[$val2['id']])) continue;
                        $val2['children'] = $arr[$val2['id']];
                        $arr[$key][$key2] = $val2;
                    }
                }
            }
            reset($arr);
            $firstResult = current($arr);
            $result = $firstResult;
        }

        return $result;
    }

    /**
     * 获取当前栏目及所有子栏目
     * @param boolean $self 包括自己本身
     * @author wengxianhu by 2017-7-26
     */
    public function getHasChildren($id, $self = true)
    {
        $lang = get_current_lang(); // 多语言
        $args = [$id, $self, $lang];
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (empty($result)) {

            static $res = null;
            if (null === $res) {
                $childrenRow = Db::name('arctype')->field('parent_id, count(id) as has_children')
                    ->where([
                        'status'  => 1,
                        'lang'    => $lang,
                        'is_del'  => 0,
                    ])->group('parent_id')
                    ->getAllWithIndex('parent_id');

                $where = [
                    'status'  => 1,
                    'lang'    => $lang,
                    'is_del'  => 0,
                ];
                $res = Db::name('arctype')->where($where)->order('parent_id asc, sort_order asc, id asc')->select();
                foreach ($res as $key => $val) {
                    $val['has_children'] = !empty($childrenRow[$val['id']]) ? $childrenRow[$val['id']]['has_children'] : 0;
                    $res[$key] = $val;
                }
            }

            $result = arctype_options($id, $res, 'id', 'parent_id');

            if (!$self) {
                array_shift($result);
            }

            cache($cacheKey, $result, null, "arctype");
        }

        return $result;
    }

    /**
     * 获取当前栏目及所有子栏目【已优化为 getHasChildren 函数】
     * @param boolean $self 包括自己本身
     * @author wengxianhu by 2017-7-26
     */
    public function getHasChildren_old($id, $self = true)
    {
        $lang = get_current_lang(); // 多语言
        $args = [$id, $self, $lang];
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (empty($result)) {

            static $res = null;
            if (null === $res) {
                $where = array(
                    'c.status'  => 1,
                    'c.lang'    => $lang,
                    'c.is_del'  => 0,
                );
                $fields = "c.*, count(s.id) as has_children";
                $res = Db::name('arctype')
                    ->field($fields)
                    ->alias('c')
                    ->join('__ARCTYPE__ s','s.parent_id = c.id','LEFT')
                    ->where($where)
                    ->group('c.id')
                    ->order('c.parent_id asc, c.sort_order asc, c.id')
                    ->select();
            }

            $result = arctype_options($id, $res, 'id', 'parent_id');

            if (!$self) {
                array_shift($result);
            }

            cache($cacheKey, $result, null, "arctype");
        }

        return $result;
    }

    /**
     * 获取所有栏目
     * @param   int     $id     栏目的ID
     * @param   int     $selected   当前选中栏目的ID
     * @param   int     $channeltype      查询条件
     * @author wengxianhu by 2017-7-26
     */
    public function getList($id = 0, $select = 0, $re_type = true, $map = array())
    {
        $id = $id ? intval($id) : 0;
        $select = $select ? intval($select) : 0;

        $arctypeLogic = new \app\common\logic\ArctypeLogic();
        $arctype_max_level = intval(config('global.arctype_max_level'));
        $options = $arctypeLogic->arctype_list($id, $select, $re_type, $arctype_max_level, $map);

        return $options;
    }


    /**
     * 默认获取全部
     * @author 小虎哥 by 2018-4-16
     */
    public function getAll($field = '*', $map = array(), $index_key = '')
    {
        $lang = get_current_lang(); // 多语言
        $args = [$field, $map, $index_key, $lang];
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (empty($result)) {
            $result = Db::name('arctype')->field($field)
                ->where($map)
                ->where('lang',$lang)
                ->order('sort_order asc')
                // ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->select();

            if (!empty($index_key)) {
                $result = convert_arr_key($result, $index_key);
            }

            cache($cacheKey, $result, null, "arctype");
        }

        return $result;
    }

    /**
     * 获取当前栏目的所有父级
     * @author wengxianhu by 2018-4-26
     */
    public function getAllPid($id, $is_RecycleBin = false)
    {
        $args = [THEME_STYLE, $id, $is_RecycleBin];
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $data = cache($cacheKey);
        if (empty($data)) {
            $data = array();
            $typeid = $id;
            $map = [
                'status'    => 1,
            ];
            if (false === $is_RecycleBin) $map['is_del'] = 0;
            $arctype_list = Db::name('Arctype')->field('*, id as typeid')
                ->where($map)
                ->getAllWithIndex('id');
            if (isset($arctype_list[$typeid])) {
                // 第一个先装起来
                $arctype_list[$typeid]['typeurl'] = $this->getTypeUrl($arctype_list[$typeid]);
                $data[$typeid] = $arctype_list[$typeid];
            } else {
                return $data;
            }

            while (true)
            {
                $typeid = $arctype_list[$typeid]['parent_id'];
                if($typeid > 0){
                    if (isset($arctype_list[$typeid])) {
                        $arctype_list[$typeid]['typeurl'] = $this->getTypeUrl($arctype_list[$typeid]);
                        $data[$typeid] = $arctype_list[$typeid];
                    }
                } else {
                    break;
                }
            }
            $data = array_reverse($data, true);

            cache($cacheKey, $data, null, "arctype");
        }

        return $data;
    }

    /**
     * 伪删除指定栏目（包括子栏目、所有相关文档）
     */
    public function pseudo_del($typeid)
    {
        $childrenList = $this->getHasChildren($typeid); // 获取当前栏目以及所有子栏目
        $typeidArr = get_arr_column($childrenList, 'id'); // 获取栏目数组里的所有栏目ID作为新的数组
        $typeidArr2 = $typeidArr;

        /*多语言*/
        $attr_name_arr = [];
        foreach ($typeidArr as $key => $val) {
            $attr_name = 'tid'.$val;
            $attr_name_arr[$key] = $attr_name;
        }
        $attr_values = Db::name('language_attr')->where([
                'attr_name'    => ['IN', $attr_name_arr],
                'attr_group'    => 'arctype',
            ])->column('attr_value');
        !empty($attr_values) && $typeidArr = $attr_values;
        /*--end*/

        /*标记当前栏目以及子栏目为被动伪删除*/
        $sta1 = Db::name('arctype')
            ->where([
                'id'    => ['IN', $typeidArr],
                'is_del'    => 0,
                'del_method' => 0,
            ])
            ->cache(true,null,"arctype")
            ->update([
                'is_del'    => 1,
                'del_method'    => 2, // 1为主动删除，2为跟随上级栏目被动删除
                'update_time'   => getTime(),
            ]); // 伪删除栏目
        /*--end*/

        /*标记当前栏目为主动伪删除*/
        // 多语言
        $attr_values = Db::name('language_attr')->where([
                'attr_name'    => 'tid'.$typeid,
                'attr_group'    => 'arctype',
            ])->column('attr_value');
        !empty($attr_values) && $typeidArr2 = $attr_values;
        // --end
        $sta2 = Db::name('arctype')
            ->where([
                'id'    => ['IN', $typeidArr2],
            ])
            ->cache(true,null,"arctype")
            ->update([
                'is_del'    => 1,
                'del_method'    => 1, // 1为主动删除，2为跟随上级栏目被动删除
                'update_time'   => getTime(),
            ]); // 伪删除栏目
        /*--end*/

        if ($sta1 && $sta2) {
            model('Archives')->pseudo_del($typeidArr); // 删除文档
            // 删除多语言栏目关联绑定
            if (!empty($attr_name_arr)) {
                Db::name('language_attribute')->where([
                    'attr_name'     => ['IN',$attr_name_arr],
                ])->update([
                    'is_del'    => 1,
                    'update_time'   => getTime(),
                ]);
            }
            /*--end*/

            /*清除页面缓存*/
            // $htmlCacheLogic = new \app\common\logic\HtmlCacheLogic;
            // $htmlCacheLogic->clear_arctype();
            /*--end*/

            return true;
        }

        return false;
    }

    /**
     * 删除指定栏目（包括子栏目、所有相关文档）
     */
    public function del($typeid)
    {
        $childrenList = $this->getHasChildren($typeid); // 获取当前栏目以及所有子栏目
        $typeidArr = get_arr_column($childrenList, 'id'); // 获取栏目数组里的所有栏目ID作为新的数组
        /*多语言*/
        $attr_name_arr = [];
        foreach ($typeidArr as $key => $val) {
            $attr_name = 'tid'.$val;
            $attr_name_arr[$key] = $attr_name;
        }
        $attr_values = Db::name('language_attr')->where([
                'attr_name'    => ['IN', $attr_name_arr],
                'attr_group'    => 'arctype',
            ])->column('attr_value');
        !empty($attr_values) && $typeidArr = $attr_values;
        /*--end*/
        $sta = Db::name('arctype')
            ->where([
                'id'    => ['IN', $typeidArr],
            ])
            ->cache(true,null,"arctype")
            ->delete(); // 删除栏目
        if ($sta) {
            model('Archives')->del($typeidArr); // 删除文档
            // 删除多语言栏目关联绑定
            if (!empty($attr_name_arr)) {
                Db::name('language_attribute')->where([
                    'attr_name'     => ['IN',$attr_name_arr],
                ])->update([
                    'is_del'    => 1,
                    'update_time'   => getTime(),
                ]);
            }
            /*--end*/

            /*清除页面缓存*/
            // $htmlCacheLogic = new \app\common\logic\HtmlCacheLogic;
            // $htmlCacheLogic->clear_arctype();
            /*--end*/

            return true;
        }

        return false;
    }

    /**
     * 每个栏目的顶级栏目的目录名称
     */
    public function getEveryTopDirnameList()
    {
        $lang = get_current_lang(); // 多语言
        $args = [$lang];
        $cacheKey = 'arctype-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (empty($result)) {
            $fields = "c.id, c.parent_id, c.dirname, c.grade, count(s.id) as has_children";
            $row = Db::name('arctype')
                ->field($fields)
                ->alias('c')
                ->join('__ARCTYPE__ s','s.parent_id = c.id','LEFT')
                ->where('c.lang',$lang)
                ->group('c.id')
                ->order('c.parent_id asc, c.sort_order asc, c.id')
                // ->cache(true,EYOUCMS_CACHE_TIME,"arctype")
                ->select();
            $row = arctype_options(0, $row, 'id', 'parent_id');

            $result = array();
            foreach ($row as $key => $val) {
                if (empty($val['parent_id'])) {
                    $val['tdirname'] = $val['dirname'];
                } else {
                    $val['tdirname'] = isset($row[$val['parent_id']]['tdirname']) ? $row[$val['parent_id']]['tdirname'] : $val['dirname'];
                }
                $row[$key] = $val;
                $result[md5($val['dirname'])] = $val;
            }

            cache($cacheKey, $result, null, "arctype");
        }

        return $result;
    }

    /**
     * 新增栏目数据
     *
     * @param array $data
     * @return intval|boolean
     */
    public function addData($data = [])
    {
        $insertId = false;
        if (!empty($data)) {
            $insertId = Db::name('arctype')->insertGetId($data);
            if($insertId){
                // --存储单页模型
                if ($data['current_channel'] == 6) {
                    $archivesData = array(
                        'title' => $data['typename'],
                        'typeid'=> $insertId,
                        'channel'   => $data['current_channel'],
                        'sort_order'    => 100,
                        'lang'  => $data['lang'],
                        'add_time'  => getTime(),
                    );
                    // $archivesData = array_merge($archivesData, $data);
                    $aid = Db::name('archives')->insertGetId($archivesData);
                    if ($aid) {
                        // ---------后置操作
                        if (!isset($data['addonFieldExt'])) {
                            $data['addonFieldExt'] = array(
                                'typeid'    => $archivesData['typeid'],
                            );
                        } else {
                            $data['addonFieldExt']['typeid'] = $archivesData['typeid'];
                        }
                        $data['addonFieldExt']['content'] = !empty($data['addonFieldExt']['content']) ? $data['addonFieldExt']['content'] : '';
                        $addData = array(
                            'addonFieldExt' => $data['addonFieldExt'],
                        );
                        $addData = array_merge($addData, $archivesData);
                        model('Single')->afterSave($aid, $addData, 'add');
                        // ---------end
                    }
                }

                /*同步栏目ID到权限组，默认是赋予该栏目的权限*/
                model('AuthRole')->syn_auth_role($insertId);
                /*--end*/

                /*清除页面缓存*/
                // $htmlCacheLogic = new \app\common\logic\HtmlCacheLogic;
                // $htmlCacheLogic->clear_arctype();
                /*--end*/

                // \think\Cache::clear("arctype");
                // extra_cache('admin_all_menu', NULL);
            }
        }
        return $insertId;
    }

    /**
     * 批量增加顶级栏目数据
     *
     * @param array $data
     * @return intval|boolean
     */
    public function batchAddTopData($addData = [], $post = [])
    {
        $arctypeLogic = new \app\common\logic\ArctypeLogic;

        $result = [];
        if (!empty($addData)) {
            $rdata = $this->saveAll($addData);
            if ($rdata) {
                // --存储单页模型的主表
                $archivesData = [];
                foreach ($rdata as $k1 => $v1) {
                    $info = $v1->getData();
                    if ($info['current_channel'] == 6) {
                        $archivesData[] = [
                            'title' => $info['typename'],
                            'typeid'=> $info['id'],
                            'channel'   => $info['current_channel'],
                            'sort_order'    => 100,
                            'lang'  => $info['lang'],
                            'add_time'  => getTime(),
                        ];
                    } else {
                        break;
                    }
                }
                // --存储单页模型的附表
                if (!empty($archivesData)) {
                    $arcdata = model('Archives')->saveAll($archivesData);
                    if ($arcdata) {
                        $singleData = [];
                        foreach ($arcdata as $k1 => $v1) {
                            $info = $v1->getData();
                            $singleData[] = [
                                'aid' => $info['aid'],
                                'typeid'=> $info['typeid'],
                                'content'   => '',
                                'add_time'  => getTime(),
                                'update_time'  => getTime(),
                            ];
                        }
                        !empty($singleData) && Db::name('single_content')->insertAll($singleData);
                    }
                }

                foreach ($rdata as $k1 => $v1) {
                    $info = $v1->getData();
                    $result[] = $info;

                    /*同步栏目ID到权限组，默认是赋予该栏目的权限*/
                    model('AuthRole')->syn_auth_role($info['id']);
                    /*--end*/

                    /*同步栏目ID到多语言的模板栏目变量里*/
                    $arctypeLogic->syn_add_language_attribute($info['id']);
                    /*--end*/
                }

                /*新增顶级栏目的下级栏目*/
                $saveData = [];
                $dirnameArr = [];
                foreach ($result as $key => $val) {
                    if (!empty($post['sontype'][$key])) {
                        $sontype = $post['sontype'][$key];
                        foreach ($sontype as $son_k => $son_v) {
                            $typename = trim($son_v);
                            if (empty($typename)) continue;

                            // 目录名称
                            $dirname = $arctypeLogic->get_dirname($typename, '', 0, $dirnameArr);
                            array_push($dirnameArr, $dirname);

                            $dirpath = $val['dirpath'].'/'.$dirname;

                            $data = [
                                'typename'  => $typename,
                                'channeltype'   => $val['channeltype'],
                                'current_channel'   => $val['current_channel'],
                                'parent_id' => intval($val['id']),
                                'dirname'   => $dirname,
                                'dirpath'   => $dirpath,
                                'grade' => intval($val['grade']) + 1,
                                'templist'  => !empty($val['templist']) ? $val['templist'] : '',
                                'tempview'  => !empty($val['tempview']) ? $val['tempview'] : '',
                                'is_hidden'  => $val['is_hidden'],
                                'seo_description'   => '',
                                'admin_id'  => $val['admin_id'],
                                'lang'  => $val['lang'],
                                'sort_order'    => $val['sort_order'],
                                'add_time'  => $val['add_time'],
                                'update_time'  => $val['update_time'],
                            ];

                            $saveData[] = $data;
                        }
                    }
                }
                if (!empty($saveData)) {
                    $result2 = $this->batchAddSubData($saveData);
                    $result = array_merge($result, $result2);
                }
                /*end*/
            }
        }

        return $result;
    }

    /**
     * 批量增加下级栏目数据
     *
     * @param array $data
     * @return intval|boolean
     */
    public function batchAddSubData($addData = [])
    {
        $result = [];
        if (!empty($addData)) {
            $rdata = $this->saveAll($addData);
            if ($rdata) {
                // --存储单页模型的主表
                $archivesData = [];
                foreach ($rdata as $k1 => $v1) {
                    $info = $v1->getData();
                    if ($info['current_channel'] == 6) {
                        $archivesData[] = [
                            'title' => $info['typename'],
                            'typeid'=> $info['id'],
                            'channel'   => $info['current_channel'],
                            'sort_order'    => 100,
                            'lang'  => $info['lang'],
                            'add_time'  => getTime(),
                        ];
                    } else {
                        break;
                    }
                }
                // --存储单页模型的附表
                if (!empty($archivesData)) {
                    $arcdata = model('Archives')->saveAll($archivesData);
                    if ($arcdata) {
                        $singleData = [];
                        foreach ($arcdata as $k1 => $v1) {
                            $info = $v1->getData();
                            $singleData[] = [
                                'aid' => $info['aid'],
                                'typeid'=> $info['typeid'],
                                'content'   => '',
                                'add_time'  => getTime(),
                                'update_time'  => getTime(),
                            ];
                        }
                        !empty($singleData) && Db::name('single_content')->insertAll($singleData);
                    }
                }

                foreach ($rdata as $k1 => $v1) {
                    $info = $v1->getData();
                    $result[] = $info;

                    /*同步栏目ID到权限组，默认是赋予该栏目的权限*/
                    model('AuthRole')->syn_auth_role($info['id']);
                    /*--end*/

                    /*同步栏目ID到多语言的模板栏目变量里*/
                    $arctypeLogic = new \app\common\logic\ArctypeLogic;
                    $arctypeLogic->syn_add_language_attribute($info['id']);
                    /*--end*/
                }
            }
        }

        return $result;
    }

    /**
     * 编辑栏目数据
     *
     * @param array $data
     * @return intval|boolean
     */
    public function updateData($data = [])
    {
        $bool = false;
        if (!empty($data)) {
            $admin_lang = get_admin_lang();
            $old_current_channel = Db::name('arctype')->where(['id'=>$data['id'],'lang'=>$admin_lang])->value('current_channel');
            $bool = Db::name('arctype')->where([
                    'id'    => $data['id'],
                    'lang'  => $admin_lang,
                ])
                ->cache(true,null,"arctype")
                ->update($data);
            if($bool){
                /*批量更新所有子孙栏目的最顶级模型ID / 顶级栏目ID*/
                $allSonTypeidArr = $this->getHasChildren($data['id'], false); // 获取当前栏目的所有子孙栏目（不包含当前栏目）
                if (!empty($allSonTypeidArr)) {
                    /*记录每一上级的指定数据*/
                    $arctypeArr = [];
                    $arctypeArr[$data['id']] = [
                        'dirpath'   => $data['dirpath'],
                    ];
                    /*end*/

                    $i = 1;
                    $minuendGrade = 0;
                    $update_data_all = [];
                    foreach ($allSonTypeidArr as $key => $val) {
                        if ($i == 1) {
                            $firstGrade = intval($data['oldgrade']);
                            $minuendGrade = intval($data['grade']) - $firstGrade;
                        }

                        /*记录每一上级的指定数据*/
                        $arctypeArr[$val['id']] = [
                            'dirpath'   => $arctypeArr[$val['parent_id']]['dirpath'].'/'.$val['dirname'],
                        ];
                        /*end*/

                        $update_data = [
                            'id'                => $val['id'],
                            'channeltype'       => $data['channeltype'],
                            'topid'             => !empty($data['topid']) ? $data['topid'] : $data['id'],
                            'dirpath'           => $arctypeArr[$val['id']]['dirpath'],
                            'update_time'       => getTime(),
                        ];
                        !empty($minuendGrade) && $update_data['grade'] = Db::raw('grade+'.$minuendGrade);

                        /*父级模板、命名规则继承*/
                        if (!empty($data['inherit_status'])) {
                            $update_data['templist'] = $data['templist'];
                            $update_data['tempview'] = $data['tempview'];
                            $update_data['rulelist'] = $data['rulelist'];
                            $update_data['ruleview'] = $data['ruleview'];
                        }
                        /*end*/

                        $update_data_all[] = $update_data;

                        ++$i;
                    }

                    if (!empty($update_data_all)) {
                        model('Arctype')->saveAll($update_data_all);
                        \think\Cache::clear('arctype');
                    }
                }
                /*--end*/

                // --存储单页模型
                if ($data['current_channel'] == 6) {
                    $archivesData = array(
                        'title' => $data['typename'],
                        'typeid'=> $data['id'],
                        'channel'   => $data['current_channel'],
                        'sort_order'    => 100,
                        'update_time'     => getTime(),
                    );
                    // $archivesData = array_merge($archivesData, $data);
                    $aid = Db::name('single_content')->where(array('typeid'=>$data['id']))->getField('aid');
                    if (empty($aid)) {
                        $opt = 'add';
                        $archivesData['lang'] = get_admin_lang();
                        $archivesData['add_time'] = getTime();
                        $up = $aid = Db::name('archives')->insertGetId($archivesData);
                    } else {
                        $opt = 'edit';
                        $up = Db::name('archives')->where([
                                'aid'   => $aid,
                                'lang'  => get_admin_lang(),
                            ])->update($archivesData);
                    }
                    if ($up) {
                        // ---------后置操作
                        if (!isset($data['addonFieldExt'])) {
                            $data['addonFieldExt'] = array(
                                'typeid'    => $data['id'],
                            );
                        } else {
                            $data['addonFieldExt']['typeid'] = $data['id'];
                        }
                        $updateData = array(
                            'addonFieldExt' => $data['addonFieldExt'],
                        );
                        $updateData = array_merge($updateData, $archivesData);
                        model('Single')->afterSave($aid, $updateData, $opt);
                        // ---------end
                    }
                }

                /*同步更改其他语言关联绑定的栏目模型*/
                $attr_name = Db::name('language_attr')->where([
                        'attr_value'     => $data['id'],
                        'attr_group'    => 'arctype',
                        'lang'  => get_admin_lang(),
                    ])->getField('attr_name');
                $attr_values = Db::name('language_attr')->where([
                        'attr_name'     => $attr_name,
                        'attr_group'    => 'arctype',
                    ])->column('attr_value');
                Db::name('arctype')->where([
                        'id'    => ['IN', $attr_values],
                        'current_channel' => $old_current_channel,
                    ])->update([
                        'current_channel'   => $data['current_channel'],
                        'update_time'   => getTime(),
                    ]);
                /*--end*/

                /*清除页面缓存*/
                // $htmlCacheLogic = new \app\common\logic\HtmlCacheLogic;
                // $htmlCacheLogic->clear_arctype();
                /*--end*/

                // \think\Cache::clear("arctype");
                // extra_cache('admin_all_menu', NULL);
            }
        }
        return $bool;
    }

    /**
     * 获取当前栏目下有几层，包括自身这一层
     * @author wengxianhu by 2017-7-26
     */
    public function getHierarchy($id = '')
    {
        $hierarchy = 1;
        $ids = [$id];
        while (true)
        {
            $ids = Db::name('arctype')->where([
                    'parent_id'    => ['IN', $ids],
                    'lang'    => get_admin_lang(),
                ])->column('id');
            if (empty($ids)) {
                break;
            } else {
                $hierarchy++;
            }
        }

        return $hierarchy;
    }
}