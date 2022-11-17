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

namespace app\admin\controller;

use think\Page;
use think\Db;
use think\Config;

class Article extends Base
{
    // 模型标识
    public $nid = 'article';
    // 模型ID
    public $channeltype = '';

    public function _initialize()
    {
        parent::_initialize();
        $channeltype_list  = config('global.channeltype_list');
        $this->channeltype = $channeltype_list[$this->nid];
        empty($this->channeltype) && $this->channeltype = 1;
        $this->assign('nid', $this->nid);
        $this->assign('channeltype', $this->channeltype);
    }

    /**
     * 文章列表
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
            $param[$key] = isset($param[$key]) ? addslashes(trim($param[$key])) : '';
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
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
                'sql_name' => '|article|' . $this->channeltype . '|',
                'sql_result' => $count,
                'sql_md5' => md5($SqlQuery),
                'sql_query' => $SqlQuery,
                'add_time' => getTime(),
                'update_time' => getTime(),
            ];
            if (!empty($FlagNew)) $SqlCacheTable['sql_name'] = $SqlCacheTable['sql_name'] . $FlagNew . '|';
            if (!empty($typeid)) $SqlCacheTable['sql_name'] = $SqlCacheTable['sql_name'] . $typeid . '|';
            if (!empty($keywords)) $SqlCacheTable['sql_name'] = '|article|keywords|';
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
            // 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整
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
        
        $show = $Page->show();
        $assign_data['page'] = $show;
        $assign_data['list'] = $list;
        $assign_data['pager'] = $Page;
        $assign_data['typeid'] = $typeid;
        $assign_data['tab'] = input('param.tab/d', 3);// 选项卡
        $assign_data['seo_pseudo'] = tpCache('global.seo_pseudo');// 前台URL模式
        $assign_data['archives_flags'] = model('ArchivesFlag')->getList();// 文档属性
        $assign_data['arctype_info'] = $typeid > 0 ? Db::name('arctype')->field('typename')->find($typeid) : [];// 当前栏目信息
        $this->assign($assign_data);
        $recycle_switch = tpSetting('recycle.recycle_switch');// 回收站开关
        $this->assign('recycle_switch', $recycle_switch);
        return $this->fetch();
    }

    //添加
    public function add()
    {
        $admin_info = session('admin_info');
        $auth_role_info = $admin_info['auth_role_info'];
        $this->assign('auth_role_info', $auth_role_info);
        $this->assign('admin_info', $admin_info);

        if (IS_POST) {
            $post = input('post.');

            //处理TAG标签
            if (!empty($post['tags_new'])) {
                $post['tags'] = !empty($post['tags']) ? $post['tags'] . ',' . $post['tags_new'] : $post['tags_new'];
                unset($post['tags_new']);
            }
            $post['tags'] = explode(',', $post['tags']);
            $post['tags'] = array_unique($post['tags']);
            $post['tags'] = implode(',', $post['tags']);

            $content = input('post.addonFieldExt.content', '', null);
            if (!empty($post['restric_type']) && 0 < $post['restric_type']) {
                $content = input('post.free_content', '', null);
            }

            // 根据标题自动提取相关的关键字
            $seo_keywords = $post['seo_keywords'];
            if (!empty($seo_keywords)) {
                $seo_keywords = str_replace('，', ',', $seo_keywords);
            } else {
                // $seo_keywords = get_split_word($post['title'], $content);
            }

            // 自动获取内容第一张图片作为封面图
            $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
            $litpic = '';
            if ($is_remote == 1) {
                $litpic = $post['litpic_remote'];
            } else {
                $litpic = $post['litpic_local'];
            }
            if (empty($litpic)) {
                $litpic = get_html_first_imgurl($content);
            }
            $post['litpic'] = $litpic;

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
            $is_jump = isset($post['is_jump']) ? $post['is_jump'] : 0;
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
                        'htmlfilename'  => $htmlfilename,
                        'lang'  => $this->admin_lang,
                    ])->count();
                if (!empty($filenameCount)) {
                    $this->error("自定义文件名已存在，请重新设置！");
                }
            }
            $post['htmlfilename'] = $htmlfilename;

            //做自动通过审核判断
            if ($admin_info['role_id'] > 0 && $auth_role_info['check_oneself'] < 1) {
                $post['arcrank'] = -1;
            }

            // 付费限制模式与之前三个字段 arc_level_id、 users_pricem、 users_free 组合逻辑兼容
            $restricData = restric_type_logic($post);
            if (isset($restricData['code']) && empty($restricData['code'])) {
                $this->error($restricData['msg']);
            }

            // 副栏目
            if (isset($post['stypeid'])) {
                $post['stypeid'] = preg_replace('/([^\d\,\，]+)/i', ',', $post['stypeid']);
                $post['stypeid'] = str_replace('，', ',', $post['stypeid']);
                $post['stypeid'] = trim($post['stypeid'], ',');
                $post['stypeid'] = str_replace(",{$post['typeid']},", ',', ",{$post['stypeid']},");
                $post['stypeid'] = trim($post['stypeid'], ',');
            }
            
            // 存储数据
            $newData = array(
                'typeid' => empty($post['typeid']) ? 0 : $post['typeid'],
                'channel'   => $this->channeltype,
                'is_b'      => empty($post['is_b']) ? 0 : $post['is_b'],
                'is_head'      => empty($post['is_head']) ? 0 : $post['is_head'],
                'is_special'      => empty($post['is_special']) ? 0 : $post['is_special'],
                'is_recom'      => empty($post['is_recom']) ? 0 : $post['is_recom'],
                'is_roll'      => empty($post['is_roll']) ? 0 : $post['is_roll'],
                'is_slide'      => empty($post['is_slide']) ? 0 : $post['is_slide'],
                'is_diyattr'      => empty($post['is_diyattr']) ? 0 : $post['is_diyattr'],
                'is_jump'     => $is_jump,
                'is_litpic'     => $is_litpic,
                'jumplinks' => $jumplinks,
                'origin'      => empty($post['origin']) ? '网络' : $post['origin'],
                'seo_keywords'     => $seo_keywords,
                'seo_description'     => $seo_description,
                'admin_id'  => session('admin_info.admin_id'),
                'lang'  => $this->admin_lang,
                'sort_order'    => 100,
                'add_time'     => strtotime($post['add_time']),
                'update_time'  => strtotime($post['add_time']),
            );
            $data = array_merge($post, $newData);
            $aid = Db::name('archives')->insertGetId($data);
            if (!empty($aid)) {
                $_POST['aid'] = $aid;
                if (!empty($post['restric_type']) && 0 < $post['restric_type']) {
                    if (empty($post['size'])) {$post['size'] = 1;}
                    $free_content = !empty($post['free_content']) ? $post['free_content'] : '';
                    if (!empty($post['part_free']) && 2 == $post['part_free']){
                        $free_content = htmlspecialchars_decode($post['addonFieldExt']['content']);
                        $free_content = $this->SpLongBody($free_content,$post['size']*1024);
                        $free_content = htmlspecialchars($free_content);
                    }
                    Db::name('article_pay')->insert([
                        'aid'          => $aid,
                        'part_free'    => !empty($post['part_free']) ? $post['part_free'] : 1,
                        'size'         => $post['size'],
                        'free_content' => $free_content,
                        'add_time'     => getTime(),
                    ]);
                }
                model('Article')->afterSave($aid, $data, 'add');
                // 添加查询执行语句到mysql缓存表
                model('SqlCacheTable')->InsertSqlCacheTable();
                adminLog('新增文章：'.$data['title']);

                // 生成静态页面代码
                $successData = [
                    'aid' => $aid,
                    'tid' => $post['typeid'],
                    'method'   =>'add',
                ];
                $this->success("操作成功!", null, $successData);
            }
            $this->error("操作失败!");
        }

        $typeid = input('param.typeid/d', 0);
        $assign_data['typeid'] = $typeid;

        $arctypeInfo = Db::name('arctype')->find($typeid);

        //允许发布文档列表的栏目
        $arctype_html = allow_release_arctype($typeid, array($this->channeltype));
        $assign_data['arctype_html'] = $arctype_html;

        // 阅读权限
        $arcrank_list = get_arcrank_list();
        $assign_data['arcrank_list'] = $arcrank_list;

        // 模板列表
        $archivesLogic = new \app\admin\logic\ArchivesLogic;
        $templateList = $archivesLogic->getTemplateList($this->nid);
        $assign_data['templateList'] = $templateList;

        // 默认模板文件
        $tempview = 'view_'.$this->nid.'.'.config('template.view_suffix');
        !empty($arctypeInfo['tempview']) && $tempview = $arctypeInfo['tempview'];
        $assign_data['tempview'] = $tempview;
        
        // 会员等级信息
        $field = 'level_id, level_name, level_value';
        $UsersLevel = Db::name('users_level')->field($field)->where('lang', $this->admin_lang)->select();
        $assign_data['users_level'] = $UsersLevel;

        // 文档默认浏览量
        $globalConfig = tpCache('global');
        if (isset($globalConfig['other_arcclick']) && 0 <= $globalConfig['other_arcclick']) {
            $arcclick_arr = explode("|", $globalConfig['other_arcclick']);
            if (count($arcclick_arr) > 1) {
                $assign_data['rand_arcclick'] = mt_rand($arcclick_arr[0], $arcclick_arr[1]);
            } else {
                $assign_data['rand_arcclick'] = intval($arcclick_arr[0]);
            }
        }else{
            $arcclick_config['other_arcclick'] = '500|1000';
            tpCache('other', $arcclick_config);
            $assign_data['rand_arcclick'] = mt_rand(500, 1000);
        }

        // URL模式
        $tpcache = config('tpcache');
        $assign_data['seo_pseudo'] = !empty($tpcache['seo_pseudo']) ? $tpcache['seo_pseudo'] : 1;

        /*文档属性*/
        $assign_data['archives_flags'] = model('ArchivesFlag')->getList();

        $channelRow = Db::name('channeltype')->where('id', $this->channeltype)->find();
        $channelRow['data'] = json_decode($channelRow['data'], true);
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

    //编辑
    public function edit()
    {
        $admin_info = session('admin_info');
        $auth_role_info = $admin_info['auth_role_info'];
        $this->assign('auth_role_info', $auth_role_info);
        $this->assign('admin_info', $admin_info);

        if (IS_POST) {
            $post = input('post.');
            $post['aid'] = intval($post['aid']);
            // 处理TAG标签
            if (!empty($post['tags_new'])) {
                $post['tags'] = !empty($post['tags']) ? $post['tags'] . ',' . $post['tags_new'] : $post['tags_new'];
                unset($post['tags_new']);
            }
            $post['tags'] = explode(',', $post['tags']);
            $post['tags'] = array_unique($post['tags']);
            $post['tags'] = implode(',', $post['tags']);

            $typeid = input('post.typeid/d', 0);
            $content = input('post.addonFieldExt.content', '', null);
            if (!empty($post['restric_type']) && 0 < $post['restric_type']) {
                $content = input('post.free_content', '', null);
            }

            // 根据标题自动提取相关的关键字
            $seo_keywords = $post['seo_keywords'];
            if (!empty($seo_keywords)) {
                $seo_keywords = str_replace('，', ',', $seo_keywords);
            } else {
                // $seo_keywords = get_split_word($post['title'], $content);
            }

            // 自动获取内容第一张图片作为封面图
            $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
            $litpic = '';
            if ($is_remote == 1) {
                $litpic = $post['litpic_remote'];
            } else {
                $litpic = $post['litpic_local'];
            }
            if (empty($litpic)) {
                $litpic = get_html_first_imgurl($content);
            }
            $post['litpic'] = $litpic;

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

            // 外部链接
            $jumplinks = '';
            $is_jump = isset($post['is_jump']) ? $post['is_jump'] : 0;
            if (intval($is_jump) > 0) {
                $jumplinks = $post['jumplinks'];
            }

            // 模板文件，如果文档模板名与栏目指定的一致，默认就为空。让它跟随栏目的指定而变
            if ($post['type_tempview'] == $post['tempview']) {
                unset($post['type_tempview']);
                unset($post['tempview']);
            }

            // 同步栏目切换模型之后的文档模型
            $channel = Db::name('arctype')->where(['id'=>$typeid])->getField('current_channel');

            //处理自定义文件名,仅由字母数字下划线和短横杆组成,大写强制转换为小写
            $htmlfilename = trim($post['htmlfilename']);
            if (!empty($htmlfilename)) {
                $htmlfilename = preg_replace("/[^a-zA-Z0-9_-]+/", "-", $htmlfilename);
                $htmlfilename = strtolower($htmlfilename);
                //判断是否存在相同的自定义文件名
                $filenameCount = Db::name('archives')->where([
                        'aid'   => ['NEQ', $post['aid']],
                        'htmlfilename'  => $htmlfilename,
                        'lang'  => $this->admin_lang,
                    ])->count();
                if (!empty($filenameCount)) {
                    $this->error("自定义文件名已存在，请重新设置！");
                }
            }
            $post['htmlfilename'] = $htmlfilename;

            //做未通过审核文档不允许修改文档状态操作
            if ($admin_info['role_id'] > 0 && $auth_role_info['check_oneself'] < 1) {
                $old_archives_arcrank = Db::name('archives')->where(['aid' => $post['aid']])->getField("arcrank");
                if ($old_archives_arcrank < 0) {
                    unset($post['arcrank']);
                }
            }

            // 付费限制模式与之前三个字段 arc_level_id、 users_pricem、 users_free 组合逻辑兼容
            $restricData = restric_type_logic($post);
            if (isset($restricData['code']) && empty($restricData['code'])) {
                $this->error($restricData['msg']);
            }

            // 副栏目
            if (isset($post['stypeid'])) {
                $post['stypeid'] = preg_replace('/([^\d\,\，]+)/i', ',', $post['stypeid']);
                $post['stypeid'] = str_replace('，', ',', $post['stypeid']);
                $post['stypeid'] = trim($post['stypeid'], ',');
                $post['stypeid'] = str_replace(",{$typeid},", ',', ",{$post['stypeid']},");
                $post['stypeid'] = trim($post['stypeid'], ',');
            }

            // 存储数据
            $newData = array(
                'typeid'=> $typeid,
                'channel'   => $channel,
                'is_b'      => empty($post['is_b']) ? 0 : $post['is_b'],
                'is_head'      => empty($post['is_head']) ? 0 : $post['is_head'],
                'is_special'      => empty($post['is_special']) ? 0 : $post['is_special'],
                'is_recom'      => empty($post['is_recom']) ? 0 : $post['is_recom'],
                'is_roll'      => empty($post['is_roll']) ? 0 : $post['is_roll'],
                'is_slide'      => empty($post['is_slide']) ? 0 : $post['is_slide'],
                'is_diyattr'      => empty($post['is_diyattr']) ? 0 : $post['is_diyattr'],
                'is_jump'   => $is_jump,
                'is_litpic'     => $is_litpic,
                'jumplinks' => $jumplinks,
                'seo_keywords'     => $seo_keywords,
                'seo_description'     => $seo_description,
                'add_time'     => strtotime($post['add_time']),
                'update_time'     => getTime(),
            );
            $data = array_merge($post, $newData);
            $r = Db::name('archives')->where(['aid' => $data['aid'], 'lang'  => $this->admin_lang])->update($data);
            if (!empty($r)) {
                if (!empty($post['restric_type']) && 0 < $post['restric_type']) {
                    if (empty($post['size'])) {$post['size'] = 1;}
                    $free_content = !empty($post['free_content']) ? $post['free_content'] : '';
                    if (!empty($post['part_free']) && 2 == $post['part_free']){
                        $free_content = htmlspecialchars_decode($post['addonFieldExt']['content']);
                        $free_content = $this->SpLongBody($free_content,$post['size']*1024);
                        $free_content = htmlspecialchars($free_content);
                    }
                    $is_in = Db::name('article_pay')->where('aid',$data['aid'])->find();
                    $article_pay_data = [
                        'part_free' => !empty($post['part_free']) ? $post['part_free'] : 1,
                        'size'=> $post['size'],
                        'free_content' => $free_content
                    ];
                    if (empty($is_in)){
                        $article_pay_data['aid'] = $data['aid'];
                        $article_pay_data['add_time'] = getTime();
                        Db::name('article_pay')->insert($article_pay_data);
                    }else{
                        $article_pay_data['update_time'] = getTime();
                        Db::name('article_pay')->where('aid',$data['aid'])->update($article_pay_data);
                    }
                }
                model('Article')->afterSave($data['aid'], $data, 'edit');
                adminLog('编辑文章：'.$data['title']);
                
                // 生成静态页面代码
                $successData = [
                    'aid'       => $data['aid'],
                    'tid'       => $typeid,
                    'method'   =>'edit',
                ];
                $this->success("操作成功!", null, $successData);
            }
            $this->error("操作失败!");
        }

        $assign_data = array();
        $id = input('id/d');
        $info = model('Article')->getInfo($id, null, false);
        if (empty($info)) $this->error('数据不存在，请联系管理员！');

        if (!empty($info['users_price'])) {
            $article_pay =  Db::name('article_pay')->field('part_free,free_content,size')->where('aid',$id)->find();
            if (!empty($article_pay)){
                $info = array_merge($article_pay ,$info);
            }
        }
        // 兼容采集没有归属栏目的文档
        if (empty($info['channel'])) {
            $channelRow = Db::name('channeltype')->field('id as channel')
                ->where('id',$this->channeltype)
                ->find();
            $info = array_merge($info, $channelRow);
        }

        $typeid = $info['typeid'];
        $assign_data['typeid'] = $typeid;
        
        // 副栏目
        $stypeid_arr = [];
        if (!empty($info['stypeid'])) {
            $info['stypeid'] = trim($info['stypeid'], ',');
            $stypeid_arr = Db::name('arctype')->field('id,typename')->where(['id'=>['IN', $info['stypeid']],'is_del'=>0])->select();
        }
        $assign_data['stypeid_arr'] = $stypeid_arr;

        // 栏目信息
        $arctypeInfo = Db::name('arctype')->find($typeid);
        $info['channel'] = $arctypeInfo['current_channel'];
        if (is_http_url($info['litpic'])) {
            $info['is_remote'] = 1;
            $info['litpic_remote'] = handle_subdir_pic($info['litpic']);
        } else {
            $info['is_remote'] = 0;
            $info['litpic_local'] = handle_subdir_pic($info['litpic']);
        }
    
        // SEO描述
        // if (!empty($info['seo_description'])) {
        //     $info['seo_description'] = @msubstr(checkStrHtml($info['seo_description']), 0, config('global.arc_seo_description_length'), false);
        // }

        $assign_data['field'] = $info;

        // 允许发布文档列表的栏目，文档所在模型以栏目所在模型为主，兼容切换模型之后的数据编辑
        $arctype_html = allow_release_arctype($typeid, array($info['channel']));
        $assign_data['arctype_html'] = $arctype_html;

        // 阅读权限
        $arcrank_list = get_arcrank_list();
        $assign_data['arcrank_list'] = $arcrank_list;

        // 模板列表
        $archivesLogic = new \app\admin\logic\ArchivesLogic;
        $templateList = $archivesLogic->getTemplateList($this->nid);
        $assign_data['templateList'] = $templateList;

        // 默认模板文件
        $tempview = $info['tempview'];
        empty($tempview) && $tempview = $arctypeInfo['tempview'];
        $assign_data['tempview'] = $tempview;

        // 会员等级信息
        $field = 'level_id, level_name, level_value';
        $UsersLevel = Db::name('users_level')->field($field)->where('lang', $this->admin_lang)->select();
        $assign_data['users_level'] = $UsersLevel;

        // URL模式
        $tpcache = config('tpcache');
        $assign_data['seo_pseudo'] = !empty($tpcache['seo_pseudo']) ? $tpcache['seo_pseudo'] : 1;

        // 文档属性
        $assign_data['archives_flags'] = model('ArchivesFlag')->getList();

        $channelRow = Db::name('channeltype')->where('id', $this->channeltype)->find();
        $channelRow['data'] = json_decode($channelRow['data'], true);
        $assign_data['channelRow'] = $channelRow;

        // 来源列表
        $system_originlist = tpSetting('system.system_originlist');
        $system_originlist = json_decode($system_originlist, true);
        $system_originlist = !empty($system_originlist) ? $system_originlist : [];
        $assign_data['system_originlist_str'] = implode(PHP_EOL, $system_originlist);

        $this->assign($assign_data);
        return $this->fetch();
    }
    
    //删除
    public function del()
    {
        if (IS_POST) {
            $archivesLogic = new \app\admin\logic\ArchivesLogic;
            $archivesLogic->del([], 0, 'article');
        }
    }
    
    //自动截取方法
    function SpLongBody($mybody, $spsize)
    {
        if (strlen($mybody) < $spsize) {
            return $mybody;
        }
        $mybody    = stripslashes($mybody);
        $bds       = explode('<', $mybody);
        $npageBody = '';
        $istable   = 0;
        $ret = '';
        foreach ($bds as $i => $k) {
            if ($i == 0) {
                $npageBody .= $bds[$i];
                continue;
            }
            $bds[$i] = "<" . $bds[$i];
            if (strlen($bds[$i]) > 6) {
                $tname = substr($bds[$i], 1, 5);
                if (strtolower($tname) == 'table') {
                    $istable++;
                } else if (strtolower($tname) == '/tabl') {
                    $istable--;
                }
                if ($istable > 0) {
                    $npageBody .= $bds[$i];
                    continue;
                } else {
                    $npageBody .= $bds[$i];
                }
            } else {
                $npageBody .= $bds[$i];
            }
            if (strlen($npageBody) > $spsize) {
                $ret = $npageBody;
                break;
            }
        }
        return $ret;
    }

    //获取免费阅读部分
    public function free_content($aid=0)
    {
        $free_content = '';
        if (!empty($aid)){
            $free_content = Db::name('article_pay')->where('aid',$aid)->value('free_content');
        }
        $this->assign('free_content', $free_content);
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