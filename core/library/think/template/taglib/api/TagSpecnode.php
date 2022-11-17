<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022/7/22
 * Time: 14:45
 */

namespace think\template\taglib\api;

use think\Db;

class TagSpecnode extends Base
{
//初始化
    protected function _initialize()
    {
        parent::_initialize();
    }
    public function getSpecnode($aid = 0,$title = '', $code = '',$thumb = ''){
        $aid = !empty($aid) ? intval($aid) : $this->aid;
        if (empty($aid)) {
            echo '标签apiSpecnodeList报错：缺少属性 aid 值，请填写专题文档ID。';
            return false;
        }
        $code = trim($code);
        $title = trim($title);
        $map = [
            'aid'   => $aid,
        ];
        if (!empty($code)) {
            $map['code'] = $code;
        } else if (!empty($title)) {
            $map['title']   = $title;
        }
        $map['status'] = 1;
        $map['is_del'] = 0;
        $map['lang'] = self::$home_lang;
        $specialNodeInfo = Db::name('special_node')->where($map)->order('node_id asc')->find();
        if (empty($specialNodeInfo)) {
            return ['info'=>$specialNodeInfo,'data'=>[]];
        }
        $isauto = !empty($specialNodeInfo['isauto']) ? $specialNodeInfo['isauto'] : 0;
        $keyword = !empty($specialNodeInfo['keywords']) ? $specialNodeInfo['keywords'] : '';
        $typeid = !empty($specialNodeInfo['typeid']) ? $specialNodeInfo['typeid'] : 0;
        $aidlist = !empty($specialNodeInfo['aidlist']) ? $specialNodeInfo['aidlist'] : '';
        $limit = !empty($specialNodeInfo['row']) ? $specialNodeInfo['row'] : 10;
        $titlelen = !empty($specialNodeInfo['titlelen']) ? $specialNodeInfo['titlelen'] : 100;
        $bodylen = !empty($specialNodeInfo['infolen']) ? $specialNodeInfo['infolen'] : 160;

        $isauto = intval($isauto);
        $titlelen = intval($titlelen);
        $bodylen = intval($bodylen);
        $limit = str_replace('，', ',', $limit);

        $aidlistArr = [];
        if (!empty($aidlist)) {
            $aidlist = str_replace('，', ',', $aidlist);
            $aidlistArr = explode(',', $aidlist);
            $aidlistArr = array_unique($aidlistArr); // 去重
            foreach ($aidlistArr as $key => $val) {
                $val = trim($val);
                if (empty($val) || !is_numeric($val)) {
                    unset($aidlistArr[$key]);
                    break;
                }
            }
        }
        // 查询条件
        $condition = [];
        if (empty($isauto)) {
            if (empty($aidlistArr)) {
                return false;
            }
            $condition['a.aid'] = ['IN', $aidlistArr];
            $limit = !empty($limit) ? $limit : count($aidlistArr);
            $aidlist = implode(',', $aidlistArr);
            $orderBy = "FIELD(a.aid, {$aidlist})";
        } else {
            if (!empty($typeid)) {
                $typeid_new = [];
                $typeid_arr = explode(',', $typeid);
                foreach ($typeid_arr as $_k => $_v) {
                    /*获取当前栏目下的所有同模型的子孙栏目*/
                    $typeid_tmp = intval($_v);
                    $arctype_info = M('arctype')->field('id,current_channel')->where('id', $typeid_tmp)->find();
                    $arctype_list = model("Arctype")->getHasChildren($typeid_tmp);
                    foreach ($arctype_list as $key => $val) {
                        if ($arctype_info['current_channel'] != $val['current_channel']) {
                            unset($arctype_list[$key]);
                        }
                    }
                    $typeids = get_arr_column($arctype_list, "id");
                    !in_array($typeid_tmp, $typeids) && $typeids[] = $typeid_tmp;
                    if (!empty($typeids)) {
                        $typeid_new = array_merge($typeid_new, $typeids);
                    }
                    /*--end*/
                }
                $condition['a.typeid'] = ['IN', $typeid_new];
            }
            if (!empty($keywordArr)) {
                $keyword = implode('|', $keywordArr);
                $condition[] = Db::raw(" CONCAT(a.title,a.seo_keywords) REGEXP '$keyword' ");
            }
            $orderBy = "a.sort_order asc, a.aid desc";
        }
        $condition['a.is_jump'] = 0;
        $condition['a.arcrank'] = ['gt', -1];
        $condition['a.status'] = 1;
        $condition['a.is_del'] = 0;
        $condition['a.lang'] = self::$home_lang;

        $allow_release_channel = config('global.allow_release_channel');
        $index = array_search(7, $allow_release_channel); // 过滤专题模型
        unset($allow_release_channel[$index]);
        $condition['a.channel'] = ['IN', $allow_release_channel];
        $list = Db::name('archives')->field("b.current_channel as channel_id, a.*")
            ->alias('a')
            ->join('__ARCTYPE__ b', 'b.id = a.typeid', 'LEFT')
            ->where($condition)
            ->orderRaw($orderBy)
            ->limit($limit)
            ->select();
        foreach ($list as $key => $val) {
            $val['title'] = text_msubstr($val["title"], 0, $titlelen, false);
            $val['seo_description'] = text_msubstr($val["seo_description"], 0, $bodylen, true);
            $val['litpic'] = get_default_pic($val['litpic']); // 默认封面图
            if ('on' == $thumb) { // 属性控制是否使用缩略图
                $val['litpic'] = thumb_img($val['litpic']);
            }
            $val['litpic'] = get_absolute_url($val['litpic'],'default',true);
            $val['add_time_format'] = $this->time_format($val['add_time']);
            $val['add_time'] = date('Y-m-d', $val['add_time']);
            $list[$key] = $val;
        }

        $result = [
            'info'=>$specialNodeInfo,
            'data' => !empty($list) ? $list : [], // 赋值数据集
        ];

        return $result;

    }

}