<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 陈风任 <491085389@qq.com>
 * Date: 2019-7-3
 */

namespace app\user\controller;

use think\Db;
use think\Config;
use think\Verify;
use think\Page;
use think\Request;

/**
 * 会员投稿发布
 */
class UsersRelease extends Base
{
    public function _initialize() {
        parent::_initialize();
        $this->users_menu_db   = Db::name('users_menu');  // 会员中心栏目表
        $this->users_level_db  = Db::name('users_level'); // 会员等级表
        $this->channeltype_db  = Db::name('channeltype'); // 模型表
        $this->archives_db     = Db::name('archives');    // 文档内容表

        if (empty($this->usersConfig['users_open_release'])) {
            $this->error('会员投稿功能已关闭');
        }

        // 菜单名称
        $this->MenuTitle = $this->users_menu_db->where([
                'mca' => 'user/UsersRelease/release_centre',
                'lang' => $this->home_lang,
            ])->getField('title');
        $this->assign('MenuTitle', $this->MenuTitle);

        /*模型数据，是否开启缩略图用于会员投稿*/
        $this->ChannelID = input('param.channel/d');
        if (empty($this->ChannelID) || !isset($this->ChannelID)) $this->ChannelID = 1;
        $field = 'table, is_release, is_litpic_users_release';
        $this->ChannelData = $this->channeltype_db->where('id', $this->ChannelID)->field($field)->find();
        $this->assign('is_release', $this->ChannelData['is_release']);
        $this->assign('is_litpic_users_release', $this->ChannelData['is_litpic_users_release']);
        /* END */

        // 当前访问的方法名
        $Method = request()->action();
        $list = input('param.list/d');
        // 如果访问的方式是release_centre，并且没有list标识，则默认重定向至文章发布页
        // 如果$this->ChannelData['is_release']为空则表示模型未开启投稿
        if ('release_centre' == $Method && !empty($this->ChannelData['is_release']) && (empty($list) || !isset($list))) {
            $this->redirect('user/UsersRelease/article_add');
        }
        $this->assign('Method', $Method);

        // 会员投稿配置
        $this->assign('UsersConfig', $this->usersConfig);
    }

    // 会员投稿--文章首页
    public function release_centre()
    {
        $result = [];
        $condition = [
            'a.users_id' => $this->users_id,
            'a.lang'     => $this->home_lang,
            'a.is_del'   => 0,
        ];

        $typeid = input('typeid/d', 0); // 栏目ID，暂无用，预留
        if (!empty($typeid)) $condition['a.typeid'] = $typeid;
        
        $SqlQuery = $this->archives_db->alias('a')->where($condition)->fetchSql()->count('aid');
        $count = Db::name('sql_cache_table')->where(['sql_md5'=>md5($SqlQuery)])->getField('sql_result');
        if (!empty($count) && 0 > $count) {
            Db::name('sql_cache_table')->where(['sql_md5'=>md5($SqlQuery)])->delete();
            unset($count);
        }
        if (!isset($count)) {
            $count = $this->archives_db->alias('a')->where($condition)->count('aid');
            // 添加查询执行语句到mysql缓存表
            $SqlCacheTable = [
                'sql_name' => '|users_release|' . $this->users_id . '|all|',
                'sql_result' => $count,
                'sql_md5' => md5($SqlQuery),
                'sql_query' => $SqlQuery,
                'add_time' => getTime(),
                'update_time' => getTime()
            ];
            if (!empty($typeid)) $SqlCacheTable['sql_name'] = '|users_release|' . $this->users_id . '|' . $typeid . '|';
            Db::name('sql_cache_table')->insertGetId($SqlCacheTable);
        }

        $Page = new Page($count, config('paginate.list_rows'));
        $result['data'] = [];
        if (!empty($count)) {
            $limit = $count > config('paginate.list_rows') ? $Page->firstRow.','.$Page->listRows : $count;
            // 数据查询
            $result['data'] = $this->archives_db
                ->field('t.*, a.*')
                ->alias('a')
                ->join('__ARCTYPE__ t', 'a.typeid = t.id', 'LEFT')
                ->where($condition)
                ->order('a.aid desc')
                ->limit($limit)
                ->select();
            $seo_pseudo = tpCache('seo.seo_pseudo');
            foreach ($result['data'] as $key => $value) {
                if ($seo_pseudo == 2) { // 生成静态模式下，以动态URL预览
                    $arcurl  = arcurl('home/View/index', $value, true, true, 1, 1);
                } else {
                    $arcurl  = arcurl('home/View/index', $value);
                }
                $result['data'][$key]['arcurl']  = $arcurl;
                $result['data'][$key]['editurl'] = url('user/UsersRelease/article_edit', array('aid'=>$value['aid']));
                $result['data'][$key]['litpic'] = handle_subdir_pic($result['data'][$key]['litpic']); // 支持子目录
            }
        }

        $result['delurl']  = url('user/UsersRelease/article_del');
        $eyou = array(
            'field' => $result,
        );
        $this->assign('eyou', $eyou);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('pager', $Page);
        // 会员投稿发布的文章状态
        $home_article_arcrank = Config::get('global.home_article_arcrank');
        $this->assign('home_article_arcrank', $home_article_arcrank);
        return $this->fetch('users/release_centre');
    }

    // 会员投稿--文章添加
    public function article_add()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            if (!empty($post['tags'])) $post['tags'] = str_replace("，", ',', $post['tags']);

            // 会员投稿次数开启，查询会员投稿次数是否超过级别设置次数
            if (!empty($this->usersConfig['is_open_posts_count'])) {
                $where = [
                    'level_id'    => $this->users['level'],
                    'posts_count' => ['<=', $this->GetOneDayReleaseCount()],
                ];
                $result = $this->users_level_db->where($where)->count();
                if (!empty($result)) $this->error('投稿次数超过会员级别限制，请先升级！');
            }

            // 判断POST参数：文章标题是否为空、所属栏目是否为空、是否重复标题、是否重复提交
            $this->PostParameCheck($post);

            // POST参数处理后返回
            $post = $this->PostParameDealWith($post);
            if (!empty($post['arc_level_id'])){
                $post['restric_type'] = 2;
            }

            // 拼装会员ID及用户名
            $post['users_id'] = $this->users['users_id'];
            $post['author']   = $this->users['username'];

            $newData = array(
                'typeid'      => empty($post['typeid']) ? 0 : $post['typeid'],
                'origin'      => empty($post['origin']) ? '网络' : $post['origin'],
                'lang'        => $this->home_lang,
                'sort_order'  => 100,
                'add_time'    => getTime(),
                'update_time' => getTime(),
            );
            $data = array_merge($post, $newData);
            $aid = $this->archives_db->insertGetId($data);
            if (!empty($aid)) {
                $_POST['aid'] = $aid;
                model('UsersRelease')->afterSave($aid, $data, 'add', $this->ChannelData['table']);
                // 添加查询执行语句到mysql缓存表
                model('SqlCacheTable')->InsertSqlCacheTable();
                $this->success("投稿成功！", url('user/UsersRelease/release_centre', ['list' => 1]));
            }
            $this->error("投稿失败！");
        }

        // 自定义字段、栏目选项、Token验证
        $assign_data = $this->GetAssignData($this->ChannelID);
        $assign_data['channel_id'] = $this->ChannelID;

        //视频模型 需要数据 开始
        // 系统最大上传视频的大小
        $assign_data['upload_max_filesize'] = upload_max_filesize();

        //视频类型
        $media_type = tpCache('basic.media_type');
        $media_type = !empty($media_type) ? $media_type : config('global.media_ext');
        $assign_data['media_type'] = $media_type;
        //文件类型
        $file_type = tpCache('basic.file_type');
        $file_type = !empty($file_type) ? $file_type : "zip|gz|rar|iso|doc|xls|ppt|wps";
        $assign_data['file_type'] = $file_type;

        // 视频模型配置信息
        $channelRow = Db::name('channeltype')->where('id', 5)->find();
        $channelRow['data'] = json_decode($channelRow['data'], true);
        $assign_data['channelRow'] = $channelRow;

        // 会员等级信息
        $field = 'level_id, level_name, level_value';
        $UsersLevel = Db::name('users_level')->field($field)->where('lang', $this->home_lang)->select();
        $assign_data['users_level'] = $UsersLevel;
        //视频模型 需要数据 结束

        // 加载数据
        $this->assign($assign_data);
        return $this->fetch('users/article_add');
    }

    // 会员投稿--文章编辑
    public function article_edit()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            $post['aid'] = intval($post['aid']);
            if (!empty($post['tags'])) $post['tags'] = str_replace("，", ',', $post['tags']);
            
            // 判断POST参数：文章标题是否为空、所属栏目是否为空、是否重复标题、是否重复提交
            $this->PostParameCheck($post);

            // POST参数处理后返回
            $post = $this->PostParameDealWith($post, 'edit');

            // 更新数据
            $newData = array(
                'typeid'      => empty($post['typeid']) ? 0 : $post['typeid'],
                'update_time' => getTime(),
            );

            if (!empty($post['arc_level_id'])){
                $post['restric_type'] = 2;
            }

            $data = array_merge($post, $newData);
            // 更新条件
            $where = [
                'aid'      => $data['aid'],
                'lang'     => $this->home_lang,
                'users_id' => $this->users_id,
            ];
            $ResultID = $this->archives_db->where($where)->update($data);
            if (!empty($ResultID)) {
                /*后置操作*/
                $data['attr']['typeid'] = $data['old_typeid'];
                model('UsersRelease')->afterSave($data['aid'], $data, 'edit', $this->ChannelData['table']);
                /* END */
                $url = url('user/UsersRelease/release_centre',['list'=>1]);
                $this->success("编辑成功！", $url);
            }
            $this->error("编辑失败！");
        }

        // 文章ID
        $aid = input('param.aid/d');
        $channel_id = Db::name('archives')->where('aid', $aid)->getField('channel');
        $info = model('UsersRelease')->getInfo($aid, null, false);
        if ($info['users_id'] != $this->users_id) {
            $this->error('文档不存在！');
        }

        $is_remote_file = 0;
        $imgupload_list = $downfile_list = [];
        $video_list = '';
        // 图集模型
        if ($channel_id == 3) {
            $imgupload_list = model('UsersRelease')->getImgUpload($aid);
            foreach ($imgupload_list as $key => $val) {
                $imgupload_list[$key]['image_url'] = handle_subdir_pic($val['image_url']);
            }
        }else if ($channel_id == 4) {
            // 下载模型
            $downfile_list = model('DownloadFile')->getDownFile($aid);
            // 下载文件中是否存在远程链接
            foreach ($downfile_list as $key => $value) {
                if (1 == $value['is_remote']) {
                    $is_remote_file = 1;
                    break;
                }
            }
        }else if ($channel_id == 5){
            $video_list = Db::name('media_file')
                ->where('aid', $aid)
                ->order('file_id asc')
                ->select();
            $video_list = json_encode($video_list);
        }

        // 自定义字段、栏目选项、Token验证
        $assign_data = $this->GetAssignData($channel_id, $aid, $info);
        // 文章列表
        $assign_data['ArchivesData'] = $info;
        // 图集列表
        $assign_data['imgupload_list'] = $imgupload_list;
        // 是否远程链接
        $assign_data['is_remote_file'] = $is_remote_file;
        // 下载列表
        $assign_data['downfile_list'] = !empty($downfile_list) ? json_encode($downfile_list) : '';
        //视频模型视频列表
        $assign_data['video_list'] = $video_list;
        // 加载数据
        $assign_data['aid'] = $aid;
        $assign_data['channel_id'] = $channel_id;
        //视频模型 需要数据 开始
        // 系统最大上传视频的大小
        $assign_data['upload_max_filesize'] = upload_max_filesize();

        //视频类型
        $media_type = tpCache('basic.media_type');
        $media_type = !empty($media_type) ? $media_type : config('global.media_ext');
        $assign_data['media_type'] = $media_type;
        //文件类型
        $file_type = tpCache('basic.file_type');
        $file_type = !empty($file_type) ? $file_type : "zip|gz|rar|iso|doc|xls|ppt|wps";
        $assign_data['file_type'] = $file_type;

        // 视频模型配置信息
        $channelRow = Db::name('channeltype')->where('id', 5)->find();
        $channelRow['data'] = json_decode($channelRow['data'], true);
        $assign_data['channelRow'] = $channelRow;

        // 会员等级信息
        $field = 'level_id, level_name, level_value';
        $UsersLevel = Db::name('users_level')->field($field)->where('lang', $this->home_lang)->select();
        $assign_data['users_level'] = $UsersLevel;
        //视频模型 需要数据 结束

        $this->assign($assign_data);
        return $this->fetch('users/article_edit');
    }

    // 会员投稿--文章删除
    public function article_del()
    {
        if(IS_AJAX_POST){
            $del_id = input('del_id/a');
            $aids   = eyIntval($del_id);
            if(!empty($del_id)){
                $Where = [
                    'aid'  => ['IN', $aids],
                    'lang' => $this->home_lang,
                    'users_id' => $this->users_id,
                ];
                $field = 'a.aid, a.typeid, a.channel, a.arcrank, a.is_recom, a.is_special, a.is_b, a.is_head, a.is_litpic, a.is_jump, a.is_slide, a.is_roll, a.is_diyattr, a.users_id, b.table';
                $archives = $this->archives_db
                    ->alias('a')
                    ->field($field)
                    ->join('__CHANNELTYPE__ b', 'a.channel = b.id', 'LEFT')
                    ->where($Where)
                    ->select();
                $typeids = [];
                $del_content = [];
                foreach ($archives as $key => $value) {
                    $del_content[$value['table']][] = $value['aid'];
                    if (-1 === $value['arcrank']) {
                        array_push($typeids, $value['typeid']);
                        unset($archives[$key]);
                    }
                }
                $return = $this->archives_db->where($Where)->delete();
                if (!empty($return)) {
                    foreach ($del_content as $k => $v){
                        Db::name($k.'_content')->where('aid','in',$v)->delete();
                        if ('media' == $k){
                            model('MediaFile')->delVideoFile($v);
                        }elseif ('download' == $k){
                            model('DownloadFile')->delDownFile($v);
                        }elseif ('images' == $k){
                            $image_url = Db::name('images_upload')->where('aid','in',$v)->column('image_url');
                            Db::name('images_upload')->where('aid','in',$v)->delete();
                            foreach ($image_url as $key => $val) {
                                $file_url_tmp = preg_replace('#^(/[/\w]+)?(/uploads/)#i', '.$2', $val);
                                if (!is_http_url($val) && file_exists($file_url_tmp)) {
                                    @unlink($file_url_tmp);
                                }
                            }
                        }
                    }
                    if (!empty($typeids)) model('SqlCacheTable')->UpdateDraftSqlCacheTable($typeids, 'del');
                    if (!empty($archives)) model('SqlCacheTable')->UpdateSqlCacheTable($archives, 'del', '', true);
                    $this->success('删除成功');
                } else {
                    $this->error('删除失败');
                }
            }
        }
    }

    public function get_addonextitem()
    {
        $typeid = input('post.typeid/d');
        if (!empty($typeid)) {
            // 模型ID
            $channel_id = Db::name('arctype')->where('id',$typeid)->getField('current_channel');
            // 获取加载到页面的数据
            $aid = input('post.aid/d', 0);
            $info['typeid'] = $typeid;
            $assign_data = $this->GetAssignData($channel_id, $aid, $info, true);

            $assign_data['channel_id'] = $channel_id;

            $this->assign($assign_data);
            $filename = 'users_release_field';
            if (isMobile()) {
                $filename .= '_m';
            }
            $web_users_tpl_theme = 'users';
            if ($this->usersTplVersion != 'v1') {
                $web_users_tpl_theme .= '_'.$this->usersTplVersion;
            }
            $html = $this->fetch("./public/static/template/{$web_users_tpl_theme}/{$filename}.htm");
        } else {
            $html = '';
            $assign_data['htmltextField'] = '';
        }
        $data = ['html'=>$html, 'htmltextField'=>$assign_data['htmltextField']];
        $channel_id = !empty($channel_id) ? $channel_id : 0;
        $channel_data = $this->channel_addonextitem($channel_id);
        if (!empty($channel_data)){
            $data = array_merge($data,$channel_data);
        }
        $this->success('请求成功', null, $data);
    }

    //get_addonextitem一些模型后续处理
    public function channel_addonextitem($channel=0)
    {
        $data = [];
        if (4 == $channel){
            $data['download']['users_level'] = Db::name('users_level')->field('level_id,level_name')->where('lang',$this->home_lang)->select();
            //下载模型自定义属性字段
            $attr_field = Db::name('download_attr_field')->select();
            $servername_use = 0;
            if ($attr_field) {
                $servername_info = [];
                for ($i = 0; $i < count($attr_field); $i++) {
                    if ($attr_field[$i]['field_name'] == 'server_name') {
                        if ($attr_field[$i]['field_use'] == 1) {
                            $servername_use = 1;
                        }
                        $servername_info = $attr_field[$i];
                        break;
                    }
                }
                $data['download']['servername_info'] = $servername_info;
            }
            $data['download']['attr_field'] = $attr_field;
            $data['download']['servername_use'] = $servername_use;

            $servername_arr = unserialize(tpCache('download.download_select_servername'));
            $data['download']['default_servername'] = $servername_arr?$servername_arr[0]:'立即下载';
            $weapp = Db::name('weapp')->where('code','in',['Qiniuyun','AliyunOss','Cos'])->where('status',1)->getAllWithIndex('code');
            $config = Db::name('channeltype')->where('nid','download')->value('data');
            $config = json_decode($config,true);
            if (!empty($config['qiniuyun_open']) && '1' == $config['qiniuyun_open'] && !empty($weapp['Qiniuyun'])) {
                $upload_flag = 'qny';
            } else if (!empty($config['oss_open']) && '1' == $config['oss_open'] && !empty($weapp['AliyunOss'])) {
                $upload_flag = 'oss';
            } else if (!empty($config['cos_open']) && '1' == $config['cos_open'] && !empty($weapp['Cos'])) {
                $upload_flag = 'cos';
            }else{
                $upload_flag = 'local';
            }
            $data['download']['upload_flag'] = $upload_flag;
            // 系统最大上传大小  限制类型
            $file_size = tpCache('basic.file_size');
            $postsize       = @ini_get('file_uploads') ? ini_get('post_max_size') : -1;
            $fileupload     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : -1;
            $min_size = strval($file_size) < strval($postsize) ? $file_size : $postsize;
            $min_size = strval($min_size) < strval($fileupload) ? $min_size : $fileupload;
            $basic['file_size'] = intval($min_size) * 1024 * 1024;
            $file_type = tpCache('basic.file_type');
            $basic['file_type'] = !empty($file_type) ? $file_type : "zip|gz|rar|iso|doc|xls|ppt|wps";
            $data['download']['basic'] = $basic;
        }
        return $data;
    }

    // 处理加载到页面的数据
    private function GetAssignData($channel_id = null, $aid = null, $info = array(), $is_if = false)
    {
        /* 自定义字段 */
        if (!empty($aid) && !empty($info) && !empty($channel_id)) {
            $addonFieldExtList = model('UsersRelease')->GetUsersReleaseData($channel_id, $info['typeid'], $aid, 'edit');
        } else if (!empty($channel_id) && !empty($info)){
            $addonFieldExtList = model('UsersRelease')->GetUsersReleaseData($channel_id, $info['typeid']);
        } else {
            $addonFieldExtList = model('UsersRelease')->GetUsersReleaseData($channel_id);
        }

        // 匹配显示的自定义字段
        $htmltextField = []; // 富文本的字段名
        foreach ($addonFieldExtList as $key => $val) {
            if ($val['dtype'] == 'htmltext') {
                array_push($htmltextField, $val['name']);
            }
        }

        $assign_data['addonFieldExtList'] = $addonFieldExtList;
        $assign_data['htmltextField'] = $htmltextField;
        /* END */

        if (empty($is_if)) {
            /*允许发布文档列表的栏目*/
            $typeid = 0;
            if (!empty($info['typeid'])) $typeid = $info['typeid'];
            $arctype_html = $this->allow_release_arctype($typeid);
            $assign_data['arctype_html'] = $arctype_html;
            /* END */

            /*封装表单验证隐藏域*/
            static $request = null;
            if (null == $request) { $request = Request::instance(); }  
            $token = $request->token();
            $assign_data['TokenValue'] = " <input type='hidden' name='__token__' value='{$token}'/> ";
            /* END */
        }

        return $assign_data;
    }

    // 判断POST参数：文章标题是否为空、所属栏目是否为空、是否重复标题、是否重复提交
    private function PostParameCheck($post = array(), $channel_id = 1)
    {
        if (empty($post)) $this->error('提交错误！', null, 'title');

        // 判断文章标题是否为空
        if (empty($post['title'])) $this->error('请填写文章标题！', null, 'title');

        // 判断所属栏目是否为空
        if (empty($post['typeid'])) $this->error('请选择所属栏目！', null, 'typeid');

        // 如果模型不允许重复标题则执行
        $is_repeat_title = $this->channeltype_db->where('id', $channel_id)->getField('is_repeat_title');
        if (empty($is_repeat_title)) {
            $where = [
                'title' => $post['title'],
                // 'channel' => $channel_id,
            ];
            if (!empty($post['aid'])) $where['aid'] = ['NOT IN', $post['aid']];
            $count = $this->archives_db->where($where)->count();
            if(!empty($count)) $this->error('文档标题不允许重复！', null, 'title');
        }

        // 数据验证
        $rule = [
            'title' => 'require|token',
        ];
        $message = [
            'title.require' => '不可为空！',
        ];
        $validate = new \think\Validate($rule, $message);
        if(!$validate->check($post)) $this->error('不允许连续提交！');
    }

    // POST参数处理后返回
    private function PostParameDealWith($post = array(), $type = 'add')
    {
        // 内容详情
        $content = input('post.addonFieldExt.content', '', null);

        // 自动获取内容第一张图片作为封面图
        $post['litpic'] = !empty($post['litpic_inpiut']) ? $post['litpic_inpiut'] : get_html_first_imgurl($content);

        // 是否有封面图
        $post['is_litpic'] = !empty($post['litpic']) ? 1 : 0;

        // SEO描述
        if ('add' == $type && !empty($content)) {
            $post['seo_description']= @msubstr(checkStrHtml($content), 0, config('global.arc_seo_description_length'), false);
        }

        $post['addonFieldExt']['content'] = htmlspecialchars(strip_sql($content));

        if (empty($this->usersConfig['is_automatic_review'])) {
            // 自动审核关闭，文章默认待审核
            $post['arcrank']  = -1; // 待审核
        } else {
            // 自动审核开启，文章默认已审核
            $post['arcrank']  = 0;  // 已审核
        }

        return $post;
    }

    // 计算获取一天内的投稿文档合计次数
    private function GetOneDayReleaseCount()
    {
        $time1 = strtotime(date('Y-m-d', time()));
        $time2 = $time1 + 86399;
        $where = [
            'lang'     => $this->home_lang,
            'users_id' => $this->users_id,
            'add_time' => ['between time', [$time1, $time2]],
        ];
        $AidCount = $this->archives_db->where($where)->count();
        return $AidCount;
    }

    /**
     * 允许会员投稿发布的栏目列表
     */
    private function allow_release_arctype($typeid = 0)
    {
        // 查询会员投稿设置的投稿栏目
        $release_typeids = Db::name('arctype')->where(['is_release' => 1, 'lang' => $this->home_lang])->where('current_channel','in',[1,3,4,5])->column('id');

        // 生成栏目选择下拉列表
        $arctype_html = allow_release_arctype($typeid, [1,3,4,5], true, $release_typeids,true);
        $arctype_html = str_ireplace('data-current_channel=', 'data-channel=', $arctype_html);
        $arctype_html = "<select name='typeid' id='typeid'><option value='0'>请选择栏目…</option>{$arctype_html}</select>";
        return $arctype_html;
    }

    /**
     * 模型字段 - 删除多图字段的图集
     */
    public function del_channelimgs()
    {
        if (IS_AJAX_POST) {
            $aid     = input('aid/d', '0');
            $channel = input('channel/d', ''); // 模型ID
            if (!empty($aid) && !empty($channel)) {
                $path      = input('param.filename/s', ''); // 图片路径
                $fieldid = input('param.fieldid/d'); // 多图字段
                $fieldname = Db::name('channelfield')->where(['id'=>$fieldid])->value('name');
                if (!empty($fieldname)) {
                    /*模型附加表*/
                    $table    = M('channeltype')->where('id', $channel)->getField('table');
                    $tableExt = $table . '_content';
                    /*--end*/

                    /*除去多图字段值中的图片*/
                    $info     = M($tableExt)->field("{$fieldname}")->where("aid", $aid)->find();
                    $valueArr = explode(',', $info[$fieldname]);
                    foreach ($valueArr as $key => $val) {
                        if ($path == $val) {
                            unset($valueArr[$key]);
                        }
                    }
                    $value = implode(',', $valueArr);
                    M($tableExt)->where('aid', $aid)->update(array($fieldname => $value, 'update_time' => getTime()));
                    /*--end*/
                }
            }
        }
    }
}