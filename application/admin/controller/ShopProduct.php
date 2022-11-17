<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 易而优团队 by 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */

namespace app\admin\controller;

use think\Page;
use think\Db;
use app\common\logic\ArctypeLogic;
use app\admin\logic\ProductLogic;
use app\admin\logic\ProductSpecLogic; // 用于商品规格逻辑功能处理

class ShopProduct extends Base
{
    // 模型标识
    public $nid = 'product';
    // 模型ID
    public $channeltype = '';
    // 表单类型
    public $attrInputTypeArr = array();

    public function _initialize()
    {
        parent::_initialize();

        if (!preg_match('/^attrlist_/i', ACTION_NAME) && !preg_match('/^attribute_/i', ACTION_NAME)) {
            $this->language_access(); // 多语言功能操作权限
        }
        
        $channeltype_list  = config('global.channeltype_list');
        $this->channeltype = $channeltype_list[$this->nid];
        empty($this->channeltype) && $this->channeltype = 2;
        $this->attrInputTypeArr = config('global.attr_input_type_arr');
        $this->assign('nid', $this->nid);
        $this->assign('channeltype', $this->channeltype);

        // 商城产品参数表
        $this->shop_product_attrlist_db = Db::name('shop_product_attrlist');

        // // 产品属性表
        // $this->product_attrlist_db = Db::name('product_attrlist');
        // // 产品规格表
        // $this->product_spec_preset_db = Db::name('product_spec_preset');
        // // 产品规格值表
        // $this->product_spec_value_db = Db::name('product_spec_value');
        // // 规格名称模型层
        // $this->ProductSpecPresetModel = model('ProductSpecPreset');
        // // 规格值模型层
        // $this->ProductSpecValueModel = model('ProductSpecValue');

        // 规格业务层
        $this->productSpecLogic = new ProductSpecLogic;
    }

    /**
     * 商品列表
     */
    public function index()
    {
        $assign_data = $condition = [];

        // 获取到所有GET参数
        $param = input('param.');
        $typeid = input('typeid/d', 0);

        // 搜索、筛选查询条件处理
        foreach (['keywords', 'typeid', 'flag', 'is_release'] as $key) {
            if ($key == 'typeid' && empty($param['typeid'])) {
                $typeids = Db::name('arctype')->where('current_channel', $this->channeltype)->column('id');
                $condition['a.typeid'] = array('IN', $typeids);
            }
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $keywords = $param[$key];
                    $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'typeid') {
                    $typeid = $param[$key];
                    $hasRow = model('Arctype')->getHasChildren($typeid);
                    $typeids = get_arr_column($hasRow, 'id');
                    // 权限控制 by 小虎哥
                    $admin_info = session('admin_info');
                    if (0 < intval($admin_info['role_id'])) {
                        $auth_role_info = $admin_info['auth_role_info'];
                        if (!empty($typeid) && !empty($auth_role_info) && !empty($auth_role_info['permission']['arctype'])) {
                            $typeids = array_intersect($typeids, $auth_role_info['permission']['arctype']);
                        }
                    }
                    $condition['a.typeid'] = array('IN', $typeids);
                } else if ($key == 'flag') {
                    if ('is_release' == $param[$key]) {
                        $condition['a.users_id'] = array('gt', 0);
                    } else {
                        $FlagNew = $param[$key];
                        $condition['a.'.$param[$key]] = array('eq', 1);
                    }
                } else {
                    $condition['a.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        // 权限控制 by 小虎哥
        $admin_info = session('admin_info');
        if (0 < intval($admin_info['role_id'])) {
            $auth_role_info = $admin_info['auth_role_info'];
            if (!empty($auth_role_info) && isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']) {
                $condition['a.admin_id'] = $admin_info['admin_id'];
            }
        }

        // 时间检索条件
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end'));
        if ($begin > 0 && $end > 0) {
            $condition['a.add_time'] = array('between', "$begin, $end");
        } else if ($begin > 0) {
            $condition['a.add_time'] = array('egt', $begin);
        } else if ($end > 0) {
            $condition['a.add_time'] = array('elt', $end);
        }

        // 必要条件
        $condition['a.channel'] = array('eq', $this->channeltype);
        $condition['a.lang'] = array('eq', $this->admin_lang);
        $condition['a.is_del'] = array('eq', 0);
        $conditionNew = "(a.users_id = 0 OR (a.users_id > 0 AND a.arcrank >= 0))";

        // 自定义排序
        $orderby = input('param.orderby/s');
        $orderway = input('param.orderway/s');
        if (!empty($orderby) && !empty($orderway)) {
            $orderby = "a.{$orderby} {$orderway}, a.aid desc";
        } else {
            $orderby = "a.aid desc";
        }

        // 数据查询，搜索出主键ID的值
        $SqlQuery = Db::name('archives')->alias('a')->where($condition)->where($conditionNew)->fetchSql()->count('aid');
        $count = Db::name('sql_cache_table')->where(['sql_md5'=>md5($SqlQuery)])->getField('sql_result');
        $count = ($count < 0) ? 0 : $count;
        if (empty($count)) {
            $count = Db::name('archives')->alias('a')->where($condition)->where($conditionNew)->count('aid');
            /*添加查询执行语句到mysql缓存表*/
            $SqlCacheTable = [
                'sql_name' => '|product|' . $this->channeltype . '|',
                'sql_result' => $count,
                'sql_md5' => md5($SqlQuery),
                'sql_query' => $SqlQuery,
                'add_time' => getTime(),
                'update_time' => getTime(),
            ];
            if (!empty($FlagNew)) $SqlCacheTable['sql_name'] = $SqlCacheTable['sql_name'] . $FlagNew . '|';
            if (!empty($typeid)) $SqlCacheTable['sql_name'] = $SqlCacheTable['sql_name'] . $typeid . '|';
            if (!empty($keywords)) $SqlCacheTable['sql_name'] = '|product|keywords|';
            Db::name('sql_cache_table')->insertGetId($SqlCacheTable);
            /*END*/
        }

        $Page = new Page($count, config('paginate.list_rows'));
        $list = [];
        if (!empty($count)) {
            $limit = $count > config('paginate.list_rows') ? $Page->firstRow.','.$Page->listRows : $count;
            $list = Db::name('archives')
                ->field("a.aid")
                ->alias('a')
                ->where($condition)
                ->where($conditionNew)
                ->order($orderby)
                ->limit($limit)
                ->getAllWithIndex('aid');
            if (!empty($list)) {
                $aids = array_keys($list);
                $fields = "b.*, a.*, a.aid as aid";
                $row = Db::name('archives')
                    ->field($fields)
                    ->alias('a')
                    ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                    ->where('a.aid', 'in', $aids)
                    ->getAllWithIndex('aid');
                foreach ($list as $key => $val) {
                    $row[$val['aid']]['arcurl'] = get_arcurl($row[$val['aid']]);
                    $row[$val['aid']]['litpic'] = handle_subdir_pic($row[$val['aid']]['litpic']);
                    $list[$key] = $row[$val['aid']];
                }
            }
        }

        $assign_data['page'] = $Page->show();
        $assign_data['list'] = $list;
        $assign_data['pager'] = $Page;
        $assign_data['typeid'] = $typeid;
        $assign_data['tab'] = input('param.tab/d', 3);// 选项卡
        $assign_data['arctype_html'] = allow_release_arctype($typeid, array($this->channeltype)); // 允许发布文档列表的栏目
        $assign_data['arctype_info'] = $typeid > 0 ? Db::name('arctype')->field('typename')->find($typeid) : [];// 当前栏目信息
        // 是否存在产品模型的栏目
        $where = ['current_channel' => 2, 'is_del' => 0, 'lang' => get_current_lang()];
        $assign_data['is_product_arctype'] = Db::name('arctype')->where($where)->count();
        $this->assign($assign_data);
        $recycle_switch = tpSetting('recycle.recycle_switch');//回收站开关
        $this->assign('recycle_switch', $recycle_switch);
        return $this->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $admin_info = session('admin_info');
        $auth_role_info = $admin_info['auth_role_info'];
        $this->assign('auth_role_info', $auth_role_info);
        $this->assign('admin_info', $admin_info);

        if (IS_POST) {
            $post = input('post.');

            /* 处理TAG标签 */
            if (!empty($post['tags_new'])) {
                $post['tags'] = !empty($post['tags']) ? $post['tags'] . ',' . $post['tags_new'] : $post['tags_new'];
                unset($post['tags_new']);
            }
            $post['tags'] = explode(',', $post['tags']);
            $post['tags'] = array_unique($post['tags']);
            $post['tags'] = implode(',', $post['tags']);
            /* END */

            $content = input('post.addonFieldExt.content', '', null);

            // 根据标题自动提取相关的关键字
            $seo_keywords = $post['seo_keywords'];
            if (!empty($seo_keywords)) {
                $seo_keywords = str_replace('，', ',', $seo_keywords);
            } else {
                // $seo_keywords = get_split_word($post['title'], $content);
            }

            // 自动获取内容第一张图片作为封面图
            $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
            $litpic    = '';
            if ($is_remote == 1) {
                $litpic = $post['litpic_remote'];
            } else {
                $litpic = $post['litpic_local'];
            }
            if (empty($litpic)) {
                $litpic = get_html_first_imgurl($content);
            }
            $post['litpic'] = $litpic;

            /*是否有封面图*/
            if (empty($post['litpic'])) {
                $is_litpic = 0; // 无封面图
            } else {
                $is_litpic = 1; // 有封面图
            }

            // SEO描述
            $seo_description = '';
            if (empty($post['seo_description']) && !empty($content)) {
                $seo_description = @msubstr(checkStrHtml($content), 0, config('global.arc_seo_description_length'), false);
            } else {
                $seo_description = $post['seo_description'];
            }

            // 外部链接跳转
            $jumplinks = '';
            $is_jump   = isset($post['is_jump']) ? $post['is_jump'] : 0;
            if (intval($is_jump) > 0) {
                $jumplinks = $post['jumplinks'];
            }

            // 模板文件，如果文档模板名与栏目指定的一致，默认就为空。让它跟随栏目的指定而变
            if ($post['type_tempview'] == $post['tempview']) {
                unset($post['type_tempview']);
                unset($post['tempview']);
            }

            //处理自定义文件名,仅由字母数字下划线和短横杆组成,大写强制转换为小写
            $htmlfilename = trim($post['htmlfilename']);
            if (!empty($htmlfilename)) {
                $htmlfilename = preg_replace("/[^a-zA-Z0-9_-]+/", "-", $htmlfilename);
                $htmlfilename = strtolower($htmlfilename);
                //判断是否存在相同的自定义文件名
                $filenameCount = Db::name('archives')->where([
                    'htmlfilename' => $htmlfilename,
                    'lang'         => $this->admin_lang,
                ])->count();
                if (!empty($filenameCount)) {
                    $this->error("自定义文件名已存在，请重新设置！");
                }
            }
            $post['htmlfilename'] = $htmlfilename;

            // 产品类型
            if (!empty($post['prom_type'])) {
                if ($post['prom_type_vir'] == 2) {
                    $post['netdisk_url'] = trim($post['netdisk_url']);
                    if (empty($post['netdisk_url'])) {
                        $this->error("网盘地址不能为空！");
                    }
                    $post['prom_type'] = 2;
                } else if ($post['prom_type_vir'] == 3) {
                    $post['text_content'] = trim($post['text_content']);
                    if (empty($post['text_content'])) {
                        $this->error("虚拟文本内容不能为空！");
                    }
                    $post['prom_type'] = 3;
                }
            }

            //做自动通过审核判断
            if ($admin_info['role_id'] > 0 && $auth_role_info['check_oneself'] < 1) {
                $post['arcrank'] = -1;
            }

            // 副栏目
            if (isset($post['stypeid'])) {
                $post['stypeid'] = preg_replace('/([^\d\,\，]+)/i', ',', $post['stypeid']);
                $post['stypeid'] = str_replace('，', ',', $post['stypeid']);
                $post['stypeid'] = trim($post['stypeid'], ',');
                $post['stypeid'] = str_replace(",{$post['typeid']},", ',', ",{$post['stypeid']},");
                $post['stypeid'] = trim($post['stypeid'], ',');
            }

            // --存储数据
            $newData = array(
                'typeid'          => empty($post['typeid']) ? 0 : $post['typeid'],
                'channel'         => $this->channeltype,
                'is_b'            => empty($post['is_b']) ? 0 : $post['is_b'],
                'is_head'         => empty($post['is_head']) ? 0 : $post['is_head'],
                'is_special'      => empty($post['is_special']) ? 0 : $post['is_special'],
                'is_recom'        => empty($post['is_recom']) ? 0 : $post['is_recom'],
                'is_roll'         => empty($post['is_roll']) ? 0 : $post['is_roll'],
                'is_slide'        => empty($post['is_slide']) ? 0 : $post['is_slide'],
                'is_diyattr'      => empty($post['is_diyattr']) ? 0 : $post['is_diyattr'],
                'is_jump'         => $is_jump,
                'is_litpic'       => $is_litpic,
                'jumplinks'       => $jumplinks,
                'origin'      => empty($post['origin']) ? '网络' : $post['origin'],
                'seo_keywords'    => $seo_keywords,
                'seo_description' => $seo_description,
                'admin_id'        => session('admin_info.admin_id'),
                'stock_show'      => empty($post['stock_show']) ? 0 : $post['stock_show'],
                'users_price'     => empty($post['users_price']) ? 0 : floatval($post['users_price']),
                'lang'            => $this->admin_lang,
                'sort_order'      => 100,
                'add_time'        => strtotime($post['add_time']),
                'update_time'     => strtotime($post['add_time']),
            );
            $data = array_merge($post, $newData);
            // if (!empty($post['param_type']) && 2 == $post['param_type']) {
            //     $data['attrlist_id'] = 0;
            // }
            $aid = Db::name('archives')->insertGetId($data);
            $_POST['aid'] = $aid;
            if (!empty($aid)) {
                // ---------后置操作
                model('Product')->afterSave($aid, $data, 'add', true);

                // 添加查询执行语句到mysql缓存表
                model('SqlCacheTable')->InsertSqlCacheTable();

                // 若选择多规格选项，则添加产品规格
                if (!empty($post['spec_type']) && 2 == $post['spec_type']) {
                    // 更新规格名称数据
                    $data['aid'] = $aid;
                    model('ProductSpecData')->ProducSpecNameEditSave($data, 'add');
                    // 更新规格值及金额数据
                    model('ProductSpecValue')->ProducSpecValueEditSave($data, 'add');
                    // model('ProductSpecPreset')->ProductSpecInsertAll($aid, $data);
                }

                // 若选择自定义参数则执行
                if (!empty($post['attr_name']) && !empty($post['attr_value'])) {
                    // 新增商品参数
                    $attrName = !empty($post['attr_name']) ? $post['attr_name'] : [];
                    $attrValue = !empty($post['attr_value']) ? $post['attr_value'] : [];
                    $sortOrder = !empty($post['sort_order']) ? $post['sort_order'] : 100;
                    $productAttribute = $productAttr = [];
                    $time = getTime();
                    foreach ($attrName as $key => $value) {
                        if (!empty($value)) {
                            $productAttribute = [
                                'aid' => $aid,
                                'attr_name' => trim($value),
                                'attr_values' => '',
                                'sort_order' => 100,//intval($sortOrder[$key]),
                                'lang' => $this->admin_lang,
                                'is_custom' => 1,
                                'add_time' => $time,
                                'update_time' => $time,
                            ];
                            $attrID = Db::name('shop_product_attribute')->insertGetId($productAttribute);
                            if (!empty($attrValue[$key])) {
                                $productAttr = [
                                    'aid' => $aid,
                                    'attr_id' => $attrID,
                                    'attr_value' => $attrValue[$key],
                                    'is_custom' => 1,
                                    'sort_order' => intval($sortOrder[$key]),
                                    'add_time' => $time,
                                    'update_time' => $time,
                                ];
                                Db::name('shop_product_attr')->insertGetId($productAttr);
                            }
                        }
                    }
                }
                // if (!empty($post['param_type']) && 2 == $post['param_type']) {
                //     // 强制删除商品参数
                //     $where = [
                //         'aid' => $aid,
                //     ];
                //     Db::name('product_custom_param')->where($where)->delete(true);
                //     // 新增商品参数
                //     $paramName = !empty($post['param_name']) ? $post['param_name'] : [];
                //     $paramValue = !empty($post['param_value']) ? $post['param_value'] : [];
                //     if (!empty($paramName) && !empty($paramValue)) {
                //         $insertAll = [];
                //         $time = getTime();
                //         foreach ($paramName as $key => $value) {
                //             if (!empty($value) && !empty($paramValue[$key])) {
                //                 $insertAll[] = [
                //                     'aid' => $aid,
                //                     'param_name' => $value,
                //                     'param_value' => $paramValue[$key],
                //                     'sort_order' => $key,
                //                     'add_time' => $time,
                //                     'update_time' => $time,
                //                 ];
                //             }
                //         }
                //         if (!empty($insertAll)) Db::name('product_custom_param')->insertAll($insertAll);
                //     }
                // }

                adminLog('新增产品：' . $data['title']);

                // 虚拟商品保存
                if (!empty($post['prom_type']) && in_array($post['prom_type'], [2, 3])) {
                    model('ProductNetdisk')->saveProductNetdisk($aid, $data);
                }

                // 生成静态页面代码
                $successData = [
                    'aid' => $aid,
                    'tid' => $post['typeid'],
                ];
                $this->success("操作成功!", url('ShopProduct/index'), $successData);
            }
            $this->error("操作失败!");
        }

        $typeid = input('param.typeid/d', 0);
        $assign_data['typeid'] = $typeid; // 栏目ID

        // 栏目信息
        $arctypeInfo = Db::name('arctype')->find($typeid);

        // 允许发布文档列表的栏目
        $assign_data['arctype_html'] = allow_release_arctype($typeid, array($this->channeltype));

        // 可控制的字段列表
        $assign_data['ifcontrolRow'] = Db::name('channelfield')->field('id,name')->where([
            'channel_id' => $this->channeltype,
            'ifmain'     => 1,
            'ifeditable' => 1,
            'ifcontrol'  => 0,
            'status'     => 1,
        ])->getAllWithIndex('name');

        // 阅读权限
        $assign_data['arcrank_list'] = get_arcrank_list();

        // 产品参数
        $assign_data['canshu'] = $this->ajax_get_attr_input($typeid);

        // 模板列表
        $archivesLogic = new \app\admin\logic\ArchivesLogic;
        $assign_data['templateList'] = $archivesLogic->getTemplateList($this->nid);

        // 默认模板文件
        $tempview = 'view_' . $this->nid . '.' . config('template.view_suffix');
        !empty($arctypeInfo['tempview']) && $tempview = $arctypeInfo['tempview'];
        $assign_data['tempview'] = $tempview;

        // 商城配置
        $shopConfig = getUsersConfigData('shop');
        $assign_data['shopConfig'] = $shopConfig;

        // 商品规格
        if (isset($shopConfig['shop_open_spec']) && 1 == $shopConfig['shop_open_spec']) {
            // 清除处理表的aid
            session('handleAID', 0);
            // 删除商品添加时产生的废弃规格
            $del_spec = session('del_spec') ? session('del_spec') : [];
            if (!empty($del_spec)) {
                $del_spec = array_unique($del_spec);
                $where = [
                    'spec_mark_id' => ['IN', $del_spec]
                ];
                Db::name('product_spec_data_handle')->where($where)->delete(true);
                // 清除 session
                session('del_spec', null);
            }
            // 预设值名称
            $assign_data['preset_value'] = Db::name('product_spec_preset')->where('lang', $this->admin_lang)->field('preset_id, preset_mark_id, preset_name')->group('preset_mark_id')->order('preset_mark_id desc')->select();
            // 读取规格预设库最大参数标记ID
            $maxPresetMarkID = $assign_data['preset_value'][0]['preset_mark_id'];
            $assign_data['maxPresetMarkID'] = $maxPresetMarkID + 1;
        }

        // 商品参数列表
        $where = [
            'status' => 1,
            'is_del' => 0,
            'lang' => $this->admin_lang,
        ];
        $assign_data['AttrList'] = $this->shop_product_attrlist_db->where($where)->order('sort_order asc, list_id asc')->select();

        // 最大参数属性ID值 +1
        $maxAttrID = Db::name('shop_product_attribute')->max('attr_id');
        $assign_data['maxAttrID'] = ++$maxAttrID;

        // URL模式
        $tpcache = config('tpcache');
        $assign_data['seo_pseudo'] = !empty($tpcache['seo_pseudo']) ? $tpcache['seo_pseudo'] : 1;

        // 文档默认浏览量
        $globalConfig = tpCache('global');
        if (isset($globalConfig['other_arcclick']) && 0 <= $globalConfig['other_arcclick']) {
            $arcclick_arr = explode("|", $globalConfig['other_arcclick']);
            if (count($arcclick_arr) > 1) {
                $assign_data['rand_arcclick'] = mt_rand($arcclick_arr[0], $arcclick_arr[1]);
            } else {
                $assign_data['rand_arcclick'] = intval($arcclick_arr[0]);
            }
        } else {
            $arcclick_config['other_arcclick'] = '500|1000';
            tpCache('other', $arcclick_config);
            $assign_data['rand_arcclick'] = mt_rand(500, 1000);
        }

        // 文档属性
        $assign_data['archives_flags'] = model('ArchivesFlag')->getList();
        $channelRow = Db::name('channeltype')->where('id', $this->channeltype)->find();
        $assign_data['channelRow'] = $channelRow;

        // 来源列表
        $system_originlist = tpSetting('system.system_originlist');
        $system_originlist = json_decode($system_originlist, true);
        $system_originlist = !empty($system_originlist) ? $system_originlist : [];
        $assign_data['system_originlist_str'] = implode(PHP_EOL, $system_originlist);

        // 多站点，当用站点域名访问后台，发布文档自动选择当前所属区域
        model('Citysite')->auto_location_select($assign_data);

        $this->assign($assign_data);
        return $this->fetch();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $admin_info = session('admin_info');
        $auth_role_info = $admin_info['auth_role_info'];
        $this->assign('auth_role_info', $auth_role_info);
        $this->assign('admin_info', $admin_info);

        if (IS_POST) {
            $post = input('post.');
            $post['aid'] = intval($post['aid']);

            /* 处理TAG标签 */
            if (!empty($post['tags_new'])) {
                $post['tags'] = !empty($post['tags']) ? $post['tags'] . ',' . $post['tags_new'] : $post['tags_new'];
                unset($post['tags_new']);
            }
            $post['tags'] = explode(',', $post['tags']);
            $post['tags'] = array_unique($post['tags']);
            $post['tags'] = implode(',', $post['tags']);
            /* END */

            $typeid  = input('post.typeid/d', 0);
            $content = input('post.addonFieldExt.content', '', null);

            // 根据标题自动提取相关的关键字
            $seo_keywords = $post['seo_keywords'];
            if (!empty($seo_keywords)) {
                $seo_keywords = str_replace('，', ',', $seo_keywords);
            } else {
                // $seo_keywords = get_split_word($post['title'], $content);
            }

            // 自动获取内容第一张图片作为封面图
            $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
            $litpic    = '';
            if ($is_remote == 1) {
                $litpic = $post['litpic_remote'];
            } else {
                $litpic = $post['litpic_local'];
            }
            if (empty($litpic)) {
                $litpic = get_html_first_imgurl($content);
            }
            $post['litpic'] = $litpic;

            /*是否有封面图*/
            if (empty($post['litpic'])) {
                $is_litpic = 0; // 无封面图
            } else {
                $is_litpic = !empty($post['is_litpic']) ? $post['is_litpic'] : 0; // 有封面图
            }

            // SEO描述
            $seo_description = '';
            if (empty($post['seo_description']) && !empty($content)) {
                $seo_description = @msubstr(checkStrHtml($content), 0, config('global.arc_seo_description_length'), false);
            } else {
                $seo_description = $post['seo_description'];
            }

            // --外部链接
            $jumplinks = '';
            $is_jump   = isset($post['is_jump']) ? $post['is_jump'] : 0;
            if (intval($is_jump) > 0) {
                $jumplinks = $post['jumplinks'];
            }

            // 模板文件，如果文档模板名与栏目指定的一致，默认就为空。让它跟随栏目的指定而变
            if ($post['type_tempview'] == $post['tempview']) {
                unset($post['type_tempview']);
                unset($post['tempview']);
            }

            // 产品类型
            if (!empty($post['prom_type'])) {
                if ($post['prom_type_vir'] == 2) {
                    $post['netdisk_url'] = trim($post['netdisk_url']);
                    if (empty($post['netdisk_url'])) {
                        $this->error("网盘地址不能为空！");
                    }
                    $post['prom_type'] = 2;
                } else if ($post['prom_type_vir'] == 3) {
                    $post['text_content'] = trim($post['text_content']);
                    if (empty($post['text_content'])) {
                        $this->error("虚拟文本内容不能为空！");
                    }
                    $post['prom_type'] = 3;
                }
            }

            //处理自定义文件名,仅由字母数字下划线和短横杆组成,大写强制转换为小写
            $htmlfilename = trim($post['htmlfilename']);
            if (!empty($htmlfilename)) {
                $htmlfilename = preg_replace("/[^a-zA-Z0-9_-]+/", "-", $htmlfilename);
                $htmlfilename = strtolower($htmlfilename);
                //判断是否存在相同的自定义文件名
                $filenameCount = Db::name('archives')->where([
                    'aid'          => ['NEQ', $post['aid']],
                    'htmlfilename' => $htmlfilename,
                    'lang'         => $this->admin_lang,
                ])->count();
                if (!empty($filenameCount)) {
                    $this->error("自定义文件名已存在，请重新设置！");
                }
            }
            $post['htmlfilename'] = $htmlfilename;

            // 同步栏目切换模型之后的文档模型
            $channel = Db::name('arctype')->where(['id' => $typeid])->getField('current_channel');

            //做未通过审核文档不允许修改文档状态操作
            if ($admin_info['role_id'] > 0 && $auth_role_info['check_oneself'] < 1) {
                $old_archives_arcrank = Db::name('archives')->where(['aid' => $post['aid']])->getField("arcrank");
                if ($old_archives_arcrank < 0) {
                    unset($post['arcrank']);
                }
            }

            // 副栏目
            if (isset($post['stypeid'])) {
                $post['stypeid'] = preg_replace('/([^\d\,\，]+)/i', ',', $post['stypeid']);
                $post['stypeid'] = str_replace('，', ',', $post['stypeid']);
                $post['stypeid'] = trim($post['stypeid'], ',');
                $post['stypeid'] = str_replace(",{$typeid},", ',', ",{$post['stypeid']},");
                $post['stypeid'] = trim($post['stypeid'], ',');
            }

            // --存储数据
            $newData = array(
                'typeid'          => $typeid,
                'channel'         => $channel,
                'is_b'            => empty($post['is_b']) ? 0 : $post['is_b'],
                'is_head'         => empty($post['is_head']) ? 0 : $post['is_head'],
                'is_special'      => empty($post['is_special']) ? 0 : $post['is_special'],
                'is_recom'        => empty($post['is_recom']) ? 0 : $post['is_recom'],
                'is_roll'         => empty($post['is_roll']) ? 0 : $post['is_roll'],
                'is_slide'        => empty($post['is_slide']) ? 0 : $post['is_slide'],
                'is_diyattr'      => empty($post['is_diyattr']) ? 0 : $post['is_diyattr'],
                'is_jump'         => $is_jump,
                'is_litpic'       => $is_litpic,
                'jumplinks'       => $jumplinks,
                'seo_keywords'    => $seo_keywords,
                'seo_description' => $seo_description,
                'stock_show'      => empty($post['stock_show']) ? 0 : $post['stock_show'],
                'users_price'     => empty($post['users_price']) ? 0 : floatval($post['users_price']),
                'add_time'        => strtotime($post['add_time']),
                'update_time'     => getTime(),
            );
            $data = array_merge($post, $newData);
            // if (!empty($post['param_type']) && 2 == $post['param_type']) {
            //     $data['attrlist_id'] = 0;
            // }
            // 更新商品信息
            $where = [
                'aid'  => $data['aid'],
                'lang' => $this->admin_lang,
            ];
            $result = Db::name('archives')->where($where)->update($data);
            if (!empty($result)) {
                // ---------后置操作
                model('Product')->afterSave($data['aid'], $data, 'edit', true);

                // 虚拟商品保存
                if (!empty($post['prom_type']) && in_array($post['prom_type'], [2, 3])) {
                    model('ProductNetdisk')->saveProductNetdisk($data['aid'], $data);
                }

                // 若选择单规格则清理多规格数据
                if (!empty($post['spec_type']) && 1 == $post['spec_type']) {
                    // 产品规格数据表
                    Db::name("product_spec_data")->where('aid', $data['aid'])->delete();
                    // 产品多规格组装表
                    Db::name("product_spec_value")->where('aid', $data['aid'])->delete();
                    // 产品规格数据处理表
                    Db::name("product_spec_data_handle")->where('aid', $data['aid'])->delete();
                }
                // 若选择多规格选项，则添加产品规格
                else if (!empty($post['spec_type']) && 2 == $post['spec_type']) {
                    // 更新规格名称数据
                    model('ProductSpecData')->ProducSpecNameEditSave($data);
                    // 更新规格值及金额数据
                    model('ProductSpecValue')->ProducSpecValueEditSave($data);
                }

                // 若选择自定义参数则执行
                if (!empty($post['attr_name']) && !empty($post['attr_value'])) {
                    // 新增商品参数
                    $attrName = !empty($post['attr_name']) ? $post['attr_name'] : [];
                    $attrValue = !empty($post['attr_value']) ? $post['attr_value'] : [];
                    $sortOrder = !empty($post['sort_order']) ? $post['sort_order'] : 100;
                    $productAttribute = $productAttr = [];
                    $time = getTime();
                    foreach ($attrName as $key => $value) {
                        if (!empty($value)) {
                            $productAttribute = [
                                'aid' => $post['aid'],
                                'attr_name' => trim($value),
                                'attr_values' => '',
                                'sort_order' => 100,//intval($sortOrder[$key]),
                                'lang' => $this->admin_lang,
                                'is_custom' => 1,
                                'add_time' => $time,
                                'update_time' => $time,
                            ];
                            $attrID = Db::name('shop_product_attribute')->insertGetId($productAttribute);
                            if (!empty($attrValue[$key])) {
                                $productAttr = [
                                    'aid' => $post['aid'],
                                    'attr_id' => $attrID,
                                    'attr_value' => $attrValue[$key],
                                    'is_custom' => 1,
                                    'sort_order' => intval($sortOrder[$key]),
                                    'add_time' => $time,
                                    'update_time' => $time,
                                ];
                                Db::name('shop_product_attr')->insertGetId($productAttr);
                            }
                        }
                    }
                }
                // 删除指定的商品参数
                if (!empty($post['del_attr_id'])) {
                    $delAttrID = explode(',', $post['del_attr_id']);
                    $where = [
                        'is_custom' => 1,
                        'attr_id' => ['IN', $delAttrID]
                    ];
                    Db::name('shop_product_attr')->where($where)->delete(true);
                    Db::name('shop_product_attribute')->where($where)->delete(true);
                }
                // if (!empty($post['param_type']) && 2 == $post['param_type']) {
                //     // 强制删除商品参数
                //     $where = [
                //         'aid' => $data['aid'],
                //     ];
                //     Db::name('product_custom_param')->where($where)->delete(true);
                //     // 新增商品参数
                //     $paramName = !empty($post['param_name']) ? $post['param_name'] : [];
                //     $paramValue = !empty($post['param_value']) ? $post['param_value'] : [];
                //     if (!empty($paramName) && !empty($paramValue)) {
                //         $insertAll = [];
                //         $time = getTime();
                //         foreach ($paramName as $key => $value) {
                //             if (!empty($value) && !empty($paramValue[$key])) {
                //                 $insertAll[] = [
                //                     'aid' => $data['aid'],
                //                     'param_name' => $value,
                //                     'param_value' => $paramValue[$key],
                //                     'sort_order' => $key,
                //                     'add_time' => $time,
                //                     'update_time' => $time,
                //                 ];
                //             }
                //         }
                //         if (!empty($insertAll)) Db::name('product_custom_param')->insertAll($insertAll);
                //     }
                // }

                adminLog('编辑产品：' . $data['title']);

                // 生成静态页面代码
                $successData = [
                    'aid' => $data['aid'],
                    'tid' => $typeid,
                ];
                $this->success("操作成功!", url('ShopProduct/index'), $successData);
            }
            $this->error("操作失败!");
        }

        $assign_data = array();

        $id = input('id/d', 0);
        $info = model('Product')->getInfo($id);
        if (empty($info)) $this->error('数据不存在，请联系管理员！');

        // 获取规格数据信息
        // 包含：SpecSelectName、HtmlTable、spec_mark_id_arr、preset_value
        $assign_data = model('ProductSpecData')->GetProductSpecData($id);

        // 兼容采集没有归属栏目的文档
        if (empty($info['channel'])) {
            $channelRow = Db::name('channeltype')->field('id as channel')->where('id', $this->channeltype)->find();
            $info = array_merge($info, $channelRow);
        }

        // 栏目ID及栏目信息
        $typeid = $info['typeid'];
        $assign_data['typeid'] = $typeid;
        $arctypeInfo = Db::name('arctype')->find($typeid);
        $info['channel'] = $arctypeInfo['current_channel'];
        if (is_http_url($info['litpic'])) {
            $info['is_remote']     = 1;
            $info['litpic_remote'] = handle_subdir_pic($info['litpic']);
        } else {
            $info['is_remote']    = 0;
            $info['litpic_local'] = handle_subdir_pic($info['litpic']);
        }

        // 副栏目
        $stypeid_arr = [];
        if (!empty($info['stypeid'])) {
            $info['stypeid'] = trim($info['stypeid'], ',');
            $stypeid_arr = Db::name('arctype')->field('id,typename')->where(['id'=>['IN', $info['stypeid']],'is_del'=>0])->select();
        }
        $assign_data['stypeid_arr'] = $stypeid_arr;

        // SEO描述
        // if (!empty($info['seo_description'])) {
        //     $info['seo_description'] = @msubstr(checkStrHtml($info['seo_description']), 0, config('global.arc_seo_description_length'), false);
        // }

        $assign_data['field'] = $info;

        // 产品相册
        $proimg_list = model('ProductImg')->getProImg($id);
        foreach ($proimg_list as $key => $val) {
            $proimg_list[$key]['image_url'] = handle_subdir_pic($val['image_url']); // 支持子目录
        }
        $assign_data['proimg_list'] = $proimg_list;

        // 允许发布文档列表的栏目，文档所在模型以栏目所在模型为主，兼容切换模型之后的数据编辑
        $assign_data['arctype_html'] = allow_release_arctype($typeid, array($info['channel']));

        // 自定义字段
        // $addonFieldExtList = model('Field')->getChannelFieldList($info['channel'], 0, $id, $info);
        // $channelfieldBindRow = Db::name('channelfield_bind')->where(['typeid' => ['IN', [0, $typeid]]])->column('field_id');
        // if (!empty($channelfieldBindRow)) {
        //     foreach ($addonFieldExtList as $key => $val) {
        //         if (!in_array($val['id'], $channelfieldBindRow)) unset($addonFieldExtList[$key]);
        //     }
        // }
        // $assign_data['aid'] = $id;
        // $assign_data['addonFieldExtList'] = $addonFieldExtList;

        // 可控制的主表字段列表
        $assign_data['ifcontrolRow'] = Db::name('channelfield')->field('id,name')->where([
            'channel_id' => $this->channeltype,
            'ifmain'     => 1,
            'ifeditable' => 1,
            'ifcontrol'  => 0,
            'status'     => 1,
        ])->getAllWithIndex('name');

        // 虚拟商品内容读取
        $assign_data['netdisk'] = Db::name("product_netdisk")->where('aid', $id)->find();

        // 阅读权限
        $assign_data['arcrank_list'] = get_arcrank_list();

        // 模板列表
        $archivesLogic = new \app\admin\logic\ArchivesLogic;
        $templateList  = $archivesLogic->getTemplateList($this->nid);
        $assign_data['templateList'] = $templateList;

        // 默认模板文件
        $tempview = $info['tempview'];
        empty($tempview) && $tempview = $arctypeInfo['tempview'];
        $assign_data['tempview'] = $tempview;

        // 商城配置
        $shopConfig = getUsersConfigData('shop');
        $assign_data['shopConfig'] = $shopConfig;

        // 处理产品价格属性
        $IsSame = '';
        if (empty($shopConfig['shop_type']) || 1 == $shopConfig['shop_type']) {
            if ($shopConfig['shop_type'] == $assign_data['field']['prom_type']) {
                $IsSame = '0'; // 相同
            }elseif ($shopConfig['shop_type']==1 && in_array($assign_data['field']['prom_type'], [2,3])) {
                $IsSame = '0'; // 相同
            }else{
                $IsSame = '1'; // 不相同
            }
        }
        $assign_data['IsSame'] = $IsSame;

        // URL模式
        $tpcache = config('tpcache');
        $assign_data['seo_pseudo'] = !empty($tpcache['seo_pseudo']) ? $tpcache['seo_pseudo'] : 1;

        // 商品参数列表
        $where = [
            'status' => 1,
            'is_del' => 0,
            'lang' => $this->admin_lang,
        ];
        $assign_data['AttrList'] = $this->shop_product_attrlist_db->where($where)->order('sort_order asc, list_id asc')->select();

        // 商品参数值
        $assign_data['canshu'] = $assign_data['customParam'] = '';
        if (!empty($info['attrlist_id'])) {
            $assign_data['canshu'] = $this->ajax_get_shop_attr_input($typeid, $id, $info['attrlist_id']);
        }

        // 自定义参数
        $where = [
            'a.is_custom' => 1,
            'b.is_custom' => 1,
            'a.aid' => $info['aid'],
        ];
        $field = 'a.*, b.attr_name';
        $order = 'a.sort_order asc, b.attr_id sac, a.product_attr_id asc';
        $productAttr = Db::name('shop_product_attr')
            ->alias('a')
            ->where($where)
            ->field($field)
            ->order($order)
            ->join('__SHOP_PRODUCT_ATTRIBUTE__ b', 'a.attr_id = b.attr_id', 'LEFT')
            ->select();
        $assign_data['customParam'] = $productAttr;
        $delAttrID = get_arr_column($productAttr, 'attr_id');
        $assign_data['delAttrID'] = !empty($delAttrID) ? implode(',', $delAttrID) : '';

        // 最大参数属性ID值 +1
        $maxAttrID = Db::name('shop_product_attribute')->max('attr_id');
        $maxAttrID++;
        $assign_data['maxAttrID'] = $maxAttrID;

        // 文档属性
        $assign_data['archives_flags'] = model('ArchivesFlag')->getList();
        $channelRow = Db::name('channeltype')->where('id', $this->channeltype)->find();
        $assign_data['channelRow'] = $channelRow;

        // 来源列表
        $system_originlist = tpSetting('system.system_originlist');
        $system_originlist = json_decode($system_originlist, true);
        $system_originlist = !empty($system_originlist) ? $system_originlist : [];
        $assign_data['system_originlist_str'] = implode(PHP_EOL, $system_originlist);

        $this->assign($assign_data);
        return $this->fetch();
    }

    /**
     * 删除
     */
    public function del()
    {
        if (IS_POST) {
            $archivesLogic = new \app\admin\logic\ArchivesLogic;
            $archivesLogic->del([], 0, 'product');
        }
    }

    /**
     * 删除商品相册图
     */
    public function del_proimg()
    {
        if (IS_POST) {
            $filename= input('filename/s');
            $aid = input('aid/d');
            if (!empty($filename) && !empty($aid)) {
                Db::name('product_img')->where('image_url','like','%'.$filename)->where('aid',$aid)->delete();
            }

        }
    }

    // 添加商品自定义规格并返回规格表格
    public function add_product_custom_spec()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 添加自定义规格
            $resultArray = $this->productSpecLogic->addProductCustomSpec($post);
            if (!empty($resultArray['errorMsg'])) $this->error($resultArray['errorMsg']);

            // 获取已选规格进行HTML代码拼装
            if (!empty($post['aid'])) {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray['spec_array'], $post['aid']);
            } else {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray['spec_array']);
            }
            // 返回数据
            if (in_array($post['action'], ['specName', 'specValue'])) {
                $specData = $this->productSpecLogic->getProductSpecValueOption($resultArray['spec_mark_id'], $post);
            }
            $returnData = [
                'htmlTable' => !empty($htmlTable) ? $htmlTable : ' ',
                'spec_name' => !empty($specData['spec_name']) ? $specData['spec_name'] : '',
                'spec_value' => !empty($specData['spec_value']) ? $specData['spec_value'] : '',
                'spec_mark_id' => !empty($resultArray['spec_mark_id']) ? $resultArray['spec_mark_id'] : 0,
                'spec_value_id' => !empty($resultArray['spec_value_id']) ? $resultArray['spec_value_id'] : 0,
                'spec_value_option' => !empty($specData['spec_value_option']) ? $specData['spec_value_option'] : '',
                'spec_mark_id_arr' => !empty($resultArray['spec_mark_id_arr']) ? $resultArray['spec_mark_id_arr'] : 0,
                'preset_name_option' => !empty($specData['preset_name_option']) ? $specData['preset_name_option'] : '',
            ];
            
            $this->success('加载成功！', null, $returnData);
        }
    }

    // 添加自定义规格名称并返回规格表
    public function add_product_custom_spec_name()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 添加自定义规格名称
            $resultArray = $this->productSpecLogic->addProductCustomSpecName($post);
            if (!empty($resultArray['errorMsg'])) $this->error($resultArray['errorMsg']);

            // 获取已选规格进行HTML代码拼装
            if (!empty($post['aid'])) {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray, $post['aid']);
            } else {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray);
            }
            // 返回数据
            $returnData = [
                'htmlTable' => !empty($htmlTable) ? $htmlTable : ' ',
            ];
            $this->success('加载成功！', null, $returnData);
        }
    }

    // 添加自定义规格值并返回规格表
    public function add_product_custom_spec_value()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 添加自定义规格值
            $resultArray = $this->productSpecLogic->addProductCustomSpecValue($post);
            if (!empty($resultArray['errorMsg'])) $this->error($resultArray['errorMsg']);

            // 获取已选规格进行HTML代码拼装
            if (!empty($post['aid'])) {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray, $post['aid']);
            } else {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray);
            }
            // 返回数据
            $returnData = [
                'htmlTable' => !empty($htmlTable) ? $htmlTable : ' ',
            ];
            $this->success('加载成功！', null, $returnData);
        }
    }

    // 删除商品自定义规格并返回规格表格
    public function del_product_custom_spec()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 删除自定义规格
            $resultArray = $this->productSpecLogic->delProductCustomSpec($post);
            // 获取已选规格进行HTML代码拼装
            if (!empty($post['aid'])) {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray, $post['aid']);
            } else {
                $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($resultArray);
            }
            if (in_array($post['del'], ['specName', 'specValue'])) {
                $specData = $this->productSpecLogic->getProductSpecValueOption(0, $post);
            }
            // 返回数据
            $returnData = [
                'htmlTable' => !empty($htmlTable) ? $htmlTable : ' ',
                'spec_value_option' => !empty($specData['spec_value_option']) ? $specData['spec_value_option'] : '',
                'preset_name_option' => !empty($specData['preset_name_option']) ? $specData['preset_name_option'] : '',
            ];
            $this->success('加载成功！', null, $returnData);
        }
    }

    // 选中规格名称值，追加html到页面展示
    public function spec_value_select()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            // Post 数据
            $aid = !empty($post['aid']) ? $post['aid'] : 0;
            $spec_mark_id = !empty($post['spec_mark_id']) ? $post['spec_mark_id'] : 0;
            $spec_value_id = !empty($post['spec_value_id']) ? $post['spec_value_id'] : 0;
            if (empty($aid) || empty($spec_mark_id) || empty($spec_value_id)) $this->error('操作异常，请刷新重试...');

            $spec_array = [];
            // 执行更新
            $where = [
                'aid' => $aid,
                'spec_mark_id' => $spec_mark_id,
                'spec_value_id' => $spec_value_id,
            ];
            $update = [
                'spec_is_select' => 1,
                'update_time' => getTime()
            ];
            $Value = Db::name('product_spec_data_handle')->where($where)->getField('spec_value');
            $isResult = Db::name('product_spec_data_handle')->where($where)->update($update);
            if (!empty($isResult)) {
                // 仅ID信息，二维数组形式
                $where = [
                    'aid' => $aid,
                    'spec_is_select' => 1,
                ];
                $order = 'spec_value_id asc, spec_id asc, spec_mark_id asc';
                $data = Db::name('product_spec_data_handle')->field('spec_mark_id, spec_value_id')->where($where)->order($order)->select();
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        $spec_array[$value['spec_mark_id']][] = $value['spec_value_id'];
                    }
                }
            }

            // 剔除已选择的规格值查询未选择的规格值组装成下拉返回
            $notInPresetID = !empty($spec_array[$specMarkID]) ? $spec_array[$specMarkID] : [];
            $where = [
                'aid' => $aid,
                'spec_is_select' => 0,
                'spec_mark_id' => ['IN', $spec_mark_id],
            ];
            if (!empty($notInPresetID)) $where['spec_value_id'] = ['NOT IN', $notInPresetID];
            $specData = Db::name('product_spec_data_handle')->where($where)->order('spec_value_id asc')->select();

            // 拼装下拉选项
            $Option = '<option value="0">选择规格值</option>';
            if (!empty($specData)) {
                foreach ($specData as $value) {
                    $Option .= "<option value='{$value['spec_value_id']}'>{$value['spec_value']}</option>";
                }
            }

            $htmlTable = $this->productSpecLogic->SpecAssemblyEdit($spec_array, $aid);

            // 返回数据
            $returnHtml = [
                'Value' => $Value,
                'Option' => $Option,
                'htmlTable' => $htmlTable
            ];
            $this->success('加载成功！', null, $returnHtml);
        }
    }

    // 商品属性列表
    public function attrlist_index()
    {
        // 查询条件
        $Where = [];
        $keywords        = input('keywords/s');
        if (!empty($keywords)) $Where['list_name'] = ['LIKE', "%{$keywords}%"];
        $Where['lang'] = $this->admin_lang;
        $Where['is_del'] = 0;

        // 分页
        $count   = $this->shop_product_attrlist_db->where($Where)->count('list_id');
        $pageObj = new Page($count, config('paginate.list_rows'));
        $pageStr = $pageObj->show();
        $this->assign('pager', $pageObj);
        $this->assign('page', $pageStr);

        // 数据
        $list = $this->shop_product_attrlist_db
            ->where($Where)
            ->order('sort_order asc, list_id asc')
            ->limit($pageObj->firstRow . ',' . $pageObj->listRows)
            ->select();
        $this->assign('list', $list);

        // 内容管理的产品发布/编辑里入口进来
        $oldinlet = input('param.oldinlet/d');
        $this->assign('oldinlet', $oldinlet);

        return $this->fetch();
    }

    // 保存全部参数
    public function attrlist_save()
    {
        function_exists('set_time_limit') && set_time_limit(0);

        if (IS_AJAX_POST) {
            $post = input('post.');
            // 参数名称不可重复
            $ListName = array_unique($post['list_name']);
            if (count($ListName) != count($post['list_name'])) $this->error('参数名称不可重复！');

            // 数据拼装
            $SaveData = [];
            foreach ($ListName as $key => $value) {
                if (!empty($value)) {
                    $list_id   = $post['list_id'][$key];
                    $list_name = trim($value);

                    $SaveData[$key] = [
                        'list_id'     => !empty($list_id) ? $list_id : 0,
                        'list_name'   => $list_name,
                        'desc'        => !empty($post['desc'][$key]) ? $post['desc'][$key] : '',
                        'sort_order'  => !empty($post['sort_order'][$key]) ? $post['sort_order'][$key] : 100,
                        'update_time' => getTime()
                    ];

                    if (empty($list_id)) {
                        $SaveData[$key]['add_time'] = getTime();
                        unset($SaveData[$key]['list_id']);
                    }
                }
            }

            $ReturnId = model('ShopProductAttrlist')->saveAll($SaveData);
            if ($ReturnId) {
                adminLog('新增商品参数：' . implode(',', $post['list_name']));
                $this->success('操作成功', url('Product/attrlist_index'));
            } else {
                $this->error('操作失败');
            }
        }
    }

    /**
     * 新增参数
     * @return [type] [description]
     */
    public function attrlist_add()
    {
        if (IS_AJAX_POST) {
            $post              = input('post.');
            $post['list_name'] = trim($post['list_name']);
            if (empty($post['list_name'])) {
                $this->error('参数名称不能为空！');
            }

            $SaveData = [
                'list_name'   => $post['list_name'],
                'desc'        => trim($post['desc']),
                'sort_order'  => 100,
                'lang'        => $this->admin_lang,
                'add_time'    => getTime(),
                'update_time' => getTime(),
            ];

            $ReturnId = Db::name('shop_product_attrlist')->insertGetId($SaveData);
            if ($ReturnId) {
                adminLog('新增商品参数：' . $post['list_name']);
                if (!empty($post['attr_name'])) {
                    //数据拼接
                    $saveAttrData = [];
                    foreach ($post['attr_name'] as $k => $v) {
                        $attr_values           = str_replace('_', '', $v); // 替换特殊字符
                        $attr_values           = str_replace('@', '', $attr_values); // 替换特殊字符
                        $attr_values           = trim($attr_values);
                        if (empty($attr_values)) {
                            unset($post['attr_name'][$k]);
                            continue;
                        }
                        $post['attr_name'][$k] = $attr_values;

                        $saveAttrData[] = array(
                            'attr_name'       => !empty($post['attr_name'][$k]) ? $post['attr_name'][$k] : '',
                            'list_id'         => $ReturnId,
                            'attr_input_type' => !empty($post['attr_input_type'][$k]) ? intval($post['attr_input_type'][$k]) : 0,
                            'attr_values'     => !empty($post['attr_values'][$k]) ? trim($post['attr_values'][$k]) : '',
                            'sort_order'      => isset($post['attr_sort_order'][$k]) ? intval($post['attr_sort_order'][$k]) : 100,
                            'status'          => 1,
                            'lang'            => $this->admin_lang,
                            'add_time'        => getTime(),
                            'update_time'     => getTime(),
                        );
                    }

                    if (!empty($saveAttrData)) {
                        $rdata = model('ShopProductAttribute')->saveAll($saveAttrData);
                        if ($rdata !== false) {
                            // 参数值合计增加
                            Db::name('shop_product_attrlist')->where('list_id', $ReturnId)->setInc('attr_count', count($post['attr_name']));
                        }
                    }
                }
                $this->success('操作成功', url('ShopProduct/attrlist_index'));
            }
            $this->error('操作失败');
        }
        return $this->fetch();
    }

    /**
     * 编辑参数
     * @return [type] [description]
     */
    public function attrlist_edit()
    {
        if (IS_AJAX_POST) {
            $post              = input('post.');
            $post['list_id'] = intval($post['list_id']);
            $post['list_name'] = trim($post['list_name']);
            if (empty($post['list_name'])) {
                $this->error('参数名称不能为空！');
            }

            $SaveData = [
                'list_name'   => $post['list_name'],
                'desc'        => trim($post['desc']),
                'update_time' => getTime(),
            ];

            $res = Db::name('shop_product_attrlist')->where('list_id', $post['list_id'])->update($SaveData);
            if ($res) {
                adminLog('编辑商品参数：' . $post['list_name']);
                if (!empty($post['attr_name'])) {
                    //数据拼接
                    $saveAttrData = [];
                    $attr_ids     = [];
                    $time = getTime();

                    foreach ($post['attr_name'] as $k => $v) {
                        $attr_values           = str_replace('_', '', $v); // 替换特殊字符
                        $attr_values           = str_replace('@', '', $attr_values); // 替换特殊字符
                        $post['attr_name'][$k] = trim($attr_values);

                        $attrData = array(
                            'attr_name'       => !empty($post['attr_name'][$k]) ? $post['attr_name'][$k] : '',
                            'list_id'         => !empty($post['list_id']) ? intval($post['list_id']) : 0,
                            'attr_input_type' => !empty($post['attr_input_type'][$k]) ? intval($post['attr_input_type'][$k]) : 0,
                            'attr_values'     => !empty($post['attr_values'][$k]) ? trim($post['attr_values'][$k]) : '',
                            'sort_order'      => isset($post['attr_sort_order'][$k]) ? intval($post['attr_sort_order'][$k]) : 100,
                            'update_time'     => $time,
                        );
                        if (!empty($post['attr_id'][$k])) {
                            $attrData['attr_id'] = $post['attr_id'][$k];
                            $attrData['add_time'] = $time;
                            $attr_ids[]          = $post['attr_id'][$k];
                        }
                        $saveAttrData[] = $attrData;
                    }

                    if (!empty($saveAttrData)) {
                        $RId = model('ShopProductAttribute')->saveAll($saveAttrData);
                        if ($RId !== false) {
                            //删除多余的参数
                            Db::name('shop_product_attribute')
                                ->where([
                                    'list_id'   => $post['list_id'],
                                    'attr_id'   => ['NOTIN', $attr_ids],
                                    'update_time'=> ['NEQ', $time],
                                ])
                                ->delete();
                            // 参数值合计增加
                            Db::name('shop_product_attrlist')->where('list_id', $post['list_id'])->update(['attr_count' => count($saveAttrData), 'update_time' => getTime()]);
                        } else {
                            $this->error('操作失败');
                        }
                    }
                }else{
                    //删除多余的参数
                    Db::name('shop_product_attribute')
                        ->where('list_id', $post['list_id'])
                        ->delete();
                    // 参数值合计增加
                    Db::name('shop_product_attrlist')->where('list_id', $post['list_id'])->update(['attr_count' => 0, 'update_time' => getTime()]);
                }
                $this->success('操作成功', url('ShopProduct/attrlist_index'));
            }
            $this->error('操作失败');
        }
        
        $list_id = input('param.list_id');
        $list    = Db::name('shop_product_attrlist')->where('list_id', $list_id)->find();
        if (empty($list)) $this->error('数据不存在，请联系管理员！');
        $list['attr'] = Db::name('shop_product_attribute')->where('list_id', $list_id)->order('sort_order asc, attr_id asc')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    // 参数删除
    public function attrlist_del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if (!empty($id_arr)) {
            $Result = $this->shop_product_attrlist_db->where('list_id', 'IN', $id_arr)->delete();
            if ($Result) {
                Db::name('shop_product_attribute')->where('list_id', 'IN', $id_arr)->delete();
                adminLog('删除商品参数-id：' . implode(',', $id_arr));
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('参数有误');
        }
    }

    /**
     * 商品参数值列表
     */
    public function attribute_index()
    {
        $condition = array();
        // 获取到所有GET参数
        $get     = input('get.');
        $list_id = input('list_id/d', 0);

        // 应用搜索条件
        foreach (['keywords', 'list_id'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.attr_name'] = ['LIKE', "%{$get[$key]}%"];
                } else if ($key == 'list_id') {
                    $condition['a.list_id'] = $list_id;
                } else {
                    $condition['a.' . $key] = ['eq', $get[$key]];
                }
            }
        }
        $condition['a.lang'] = $this->admin_lang;
        $condition['a.is_del'] = 0;

        // 分页
        $count   = Db::name('shop_product_attribute')->alias('a')->where($condition)->count();
        $pageObj = new Page($count, config('paginate.list_rows'));
        $pageStr = $pageObj->show();
        $this->assign('pager', $pageObj);
        $this->assign('page', $pageStr);

        // 数据
        $list = Db::name('shop_product_attribute')
            ->alias('a')
            ->where($condition)
            ->order('a.sort_order asc, a.attr_id asc')
            ->limit($pageObj->firstRow . ',' . $pageObj->listRows)
            ->select();

        $attrInputTypeArr = [
            0 => '手工录入',
            1 => '选取默认值'
        ];
        $this->assign('attrInputTypeArr', $attrInputTypeArr);
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * 新增商品参数
     */
    public function attribute_add()
    {
        //防止php超时
        function_exists('set_time_limit') && set_time_limit(0);

        if (IS_AJAX_POST) {
            $attr_values              = str_replace('_', '', input('attr_values')); // 替换特殊字符
            $attr_values              = str_replace('@', '', $attr_values); // 替换特殊字符
            $attr_values              = trim($attr_values);
            $post_data                = input('post.');
            $post_data['list_id'] = intval($post_data['list_id']);
            $post_data['attr_values'] = $attr_values;

            $SaveData = array(
                'attr_name'       => $post_data['attr_name'],
                'list_id'         => $post_data['list_id'],
                'attr_input_type' => isset($post_data['attr_input_type']) ? $post_data['attr_input_type'] : '',
                'attr_values'     => isset($post_data['attr_values']) ? $post_data['attr_values'] : '',
                'sort_order'      => $post_data['sort_order'],
                'status'          => 1,
                'lang'            => $this->admin_lang,
                'add_time'        => getTime(),
                'update_time'     => getTime(),
            );

            $ReturnId = Db::name('shop_product_attribute')->add($SaveData);
            if ($ReturnId) {
                // 参数值合计增加
                Db::name('shop_product_attrlist')->where('list_id', $post_data['list_id'])->setInc('attr_count');
                adminLog('新增商品参数：' . $SaveData['attr_name']);
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        $list_id = input('param.list_id/d', 0);
        $list    = $this->shop_product_attrlist_db->where('list_id', $list_id)->find();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 编辑商品参数
     */
    public function attribute_edit()
    {
        //防止php超时
        function_exists('set_time_limit') && set_time_limit(0);

        if (IS_AJAX_POST) {
            $attr_values              = str_replace('_', '', input('attr_values')); // 替换特殊字符
            $attr_values              = str_replace('@', '', $attr_values); // 替换特殊字符
            $attr_values              = trim($attr_values);
            $post_data                = input('post.');
            $post_data['list_id'] = intval($post_data['list_id']);
            $post_data['attr_values'] = $attr_values;

            $SaveData = array(
                'attr_name'       => $post_data['attr_name'],
                'list_id'         => $post_data['list_id'],
                'attr_input_type' => isset($post_data['attr_input_type']) ? $post_data['attr_input_type'] : '',
                'attr_values'     => isset($post_data['attr_values']) ? $post_data['attr_values'] : '',
                'sort_order'      => $post_data['sort_order'],
                'update_time'     => getTime(),
            );

            $ReturnId = Db::name('shop_product_attribute')->where(['attr_id'=>$post_data['attr_id'], 'lang'=>$this->admin_lang])->update($SaveData);
            if ($ReturnId) {
                adminLog('编辑商品参数：' . $SaveData['attr_name']);
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        $id = input('param.id/d');
        $info = Db::name('shop_product_attribute')->where(['attr_id'=>$id, 'lang'=>$this->admin_lang])->find();
        if (empty($info)) $this->error('数据不存在，请联系管理员！');
        $this->assign('field', $info);

        $list = $this->shop_product_attrlist_db->where('list_id', $info['list_id'])->find();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 删除商品参数
     */
    public function attribute_del()
    {
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if (!empty($id_arr)) {
            $r = Db::name('shop_product_attribute')->where(['attr_id' => ['IN', $id_arr], 'lang'=>$this->admin_lang])->delete();
            if ($r) {
                $IDCount = count($id_arr);
                Db::name('shop_product_attrlist')->where('list_id', input('list_id/d'))->setDec('attr_count', $IDCount);
                adminLog('删除商品参数-id：' . implode(',', $id_arr));
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('参数有误');
        }
    }

    /**
     * 动态获取商品参数输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajax_get_shop_attr_input($typeid = '', $aid = '', $list_id = '')
    {
        $typeid       = intval($typeid);
        $aid          = intval($aid);
        $list_id      = intval($list_id);
        $productLogic = new ProductLogic();
        $str          = $productLogic->getShopAttrInput($aid, $typeid, $list_id);
        if (empty($str)) {
            $str = '<div style="font-size: 12px;text-align: center;">提示：该参数还没有参数值，若有需要请点击【<a href="' . url('Product/attribute_index', array('list_id' => $list_id)) . '">商品参数</a>】进行更多操作。</div>';
        }
        if (IS_AJAX) {
            exit($str);
        } else {
            return $str;
        }
    }

    /**
     * 动态获取商品参数输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajax_get_attr_input($typeid = '', $aid = '', $list_id = '')
    {
        $typeid       = intval($typeid);
        $aid          = intval($aid);
        $list_id      = intval($list_id);
        $productLogic = new ProductLogic();
        $str          = $productLogic->getAttrInput($aid, $typeid, $list_id);
        if (empty($str)) {
            $str = '<div style="font-size: 12px;text-align: center;">提示：该参数还没有参数值，若有需要请点击【<a href="' . url('Product/attribute_index', array('list_id' => $list_id)) . '">商品参数</a>】进行更多操作。</div>';
        }
        if (IS_AJAX) {
            exit($str);
        } else {
            return $str;
        }
    }

    /**
     * 发布商品
     */
    public function release()
    {
        $typeid = input('param.typeid/d', 0);
        if (0 < $typeid) {
            $param = input('param.');
            $row   = Db::name('arctype')->field('current_channel')->find($typeid);
            /*针对不支持发布文档的模型*/
            if ($row['current_channel'] != 2) {
                $this->error('该栏目不支持发布商品！', url('ShopProduct/release'));
            }
            /*-----end*/

            $data    = [
                'typeid' => $typeid,
            ];
            $jumpUrl = url("ShopProduct/add", $data, true, true);
            header('Location: ' . $jumpUrl);
            exit;
        }

        /*允许发布文档列表的栏目*/
        $select_html = allow_release_arctype(0, [2]);
        $this->assign('select_html', $select_html);
        /*--end*/

        return $this->fetch();
    }
    //帮助
    public function help()
    {
        $system_originlist = tpSetting('system.system_originlist');
        $system_originlist = json_decode($system_originlist, true);
        $system_originlist = !empty($system_originlist) ? $system_originlist : [];
        $assign_data['system_originlist_str'] = implode(PHP_EOL, $system_originlist);
        $this->assign($assign_data);
    
        return $this->fetch();
    }
}