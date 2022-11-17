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

namespace app\api\controller;

use think\Db;

class Ajax extends Base
{
    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 获取下级地区
     */
    public function get_region()
    {
        if (IS_AJAX) {
            $pid  = input('pid/d', 0);
            $res = Db::name('region')->where('parent_id',$pid)->select();
            $this->success('请求成功', null, $res);
        }
    }

    /**
     * 内容页浏览量的自增接口
     */
    public function arcclick()
    {
        if (!IS_AJAX) {
            // 第一种方案，js输出
            $aids = input('param.aids/d', 0);
            if (!empty($aids)) {
                $type = input('param.type/s', '');
                $archives_db = Db::name('archives');
                if ('view' == $type) {
                    $archives_db->where('aid', $aids)->update([
                        'click' => Db::raw('click + 1'),
                    ]);
                }
                $click = $archives_db->where('aid', $aids)->value('click');
                echo "document.write('".$click."');\r\n";
                exit;
            }
        } else {
            // 第二种方案，执行ajax
            $param = input('param.');
            if (isset($param['aids'])) {
                $aids = $param['aids'];
                if (!empty($aids)) {
                    $aid_arr = explode(',', $aids);
                    foreach ($aid_arr as $key => $val) {
                        $aid_arr[$key] = intval($val);
                    }
                    $type = input('param.type/s', '');
                    $archives_db = Db::name('archives');
                    if ('view' == $type) {
                        $archives_db->where(['aid'=>['IN', $aid_arr]])->update([
                            'click' => Db::raw('click + 1'),
                        ]);
                    }
                    $data = $archives_db->field('aid,click')->where(['aid'=>['IN', $aid_arr]])->getAllWithIndex('aid');
                    respose($data);
                }
            } else {
                $click = 0;
                $aid = input('param.aid/d', 0);
                $type = input('param.type/s', '');
                if ($aid > 0) {
                    $archives_db = Db::name('archives');
                    if ('view' == $type) {
                        $archives_db->where(array('aid'=>$aid))->setInc('click'); 
                    }
                    $click = $archives_db->where(array('aid'=>$aid))->getField('click');
                }
                echo($click);
                exit;
            }
        }
        abort(404);
    }

    /**
     * 付费文档的订单数/用户数
     */
    public function freebuynum()
    {
        $aid = input('param.aid/d', 0);
        if (IS_AJAX && !empty($aid)) {
            $freebuynum = 0;
            $modelid = input('modelid/d', 0);
            $modelid = input('channelid/d', $modelid);

            if (empty($modelid)) {
                $modelid = Db::name('archives')->where(['aid'=>$aid])->value('channel');
            }
            
            if (1 == $modelid) {
                $freebuynum = Db::name('article_order')->where(['order_status'=>1,'product_id'=>$aid])->count();
            } else if (5 == $modelid) {
                $freebuynum = Db::name('media_order')->where(['order_status'=>1,'product_id'=>$aid])->count();
            } else if (4 == $modelid) {
                $freebuynum = Db::name('download_order')->where(['order_status'=>1,'product_id'=>$aid])->count();
            }

            echo($freebuynum);
            exit;
        } else {
            abort(404);
        }
    }

    /**
     * 文档下载次数
     */
    public function downcount()
    {
        $aid = input('param.aid/d', 0);
        if (IS_AJAX && !empty($aid)) {
            $downcount = Db::name('archives')->where(array('aid'=>$aid))->getField('downcount');
            echo($downcount);
            exit;
        } else {
            abort(404);
        }
    }

    /**
     * 文档收藏次数
     */
    public function collectnum()
    {
        $aid = input('param.aid/d', 0);
        if (IS_AJAX && !empty($aid)) {
            $collectnum = Db::name('users_collection')->where(array('aid'=>$aid))->count();
            echo($collectnum);
            exit;
        } else {
            abort(404);
        }
    }

    /**
     * 站内通知数量
     */
    public function notice()
    {
        if (IS_AJAX) {
            $unread_notice_num = 0;
            $users_id = session('users_id');
            if ($users_id > 0) {
                $unread_notice_num = Db::name('users')->where(array('users_id'=>$users_id))->value('unread_notice_num');
            }
            echo($unread_notice_num);
            exit;
        } else {
            abort(404);
        }
    }

    /**
     * arclist列表分页arcpagelist标签接口
     */
    public function arcpagelist()
    {
        if (!IS_AJAX) {
            abort(404);
        }

        $pnum = input('page/d', 0);
        $pagesize = input('pagesize/d', 0);
        $tagid = input('tagid/s', '');
        $tagidmd5 = input('tagidmd5/s', '');
        !empty($tagid) && $tagid = preg_replace("/[^a-zA-Z0-9-_]/",'', $tagid);
        !empty($tagidmd5) && $tagidmd5 = preg_replace("/[^a-zA-Z0-9_]/",'', $tagidmd5);

        if (empty($tagid) || empty($pnum) || empty($tagidmd5)) {
            $this->error('参数有误');
        }

        $data = [
            'code' => 1,
            'msg'   => '',
            'lastpage'  => 0,
        ];

        $arcmulti_db = Db::name('arcmulti');
        $arcmultiRow = $arcmulti_db->where(['tagid'=>$tagidmd5])->find();
        if(!empty($arcmultiRow) && !empty($arcmultiRow['querysql']))
        {
            // arcpagelist标签属性pagesize优先级高于arclist标签属性pagesize
            if (0 < intval($pagesize)) {
                $arcmultiRow['pagesize'] = $pagesize;
            }

            // 取出属性并解析为变量
            $attarray = unserialize(stripslashes($arcmultiRow['attstr']));
            // extract($attarray, EXTR_SKIP); // 把数组中的键名直接注册为了变量

            // 通过页面及总数解析当前页面数据范围
            $pnum < 2 && $pnum = 2;
            $strnum = intval($attarray['row']) + ($pnum - 2) * $arcmultiRow['pagesize'];

            // 拼接完整的SQL
            $querysql = preg_replace('#LIMIT(\s+)(\d+)(,\d+)?#i', '', $arcmultiRow['querysql']);
            $querysql = preg_replace('#SELECT(\s+)(.*)(\s+)FROM#i', 'SELECT COUNT(*) AS totalNum FROM', $querysql);
            $queryRow = Db::query($querysql);
            if (!empty($queryRow)) {
                $tpl_content = '';
                $filename = './template/'.THEME_STYLE_PATH.'/'.'system/arclist_'.$tagid.'.'.\think\Config::get('template.view_suffix');
                if (!file_exists($filename)) {
                    $data['code'] = -1;
                    $data['msg'] = "模板追加文件 arclist_{$tagid}.htm 不存在！";
                    $this->error("标签模板不存在", null, $data);
                } else {
                    $tpl_content = @file_get_contents($filename);
                }
                if (empty($tpl_content)) {
                    $data['code'] = -1;
                    $data['msg'] = "模板追加文件 arclist_{$tagid}.htm 没有HTML代码！";
                    $this->error("标签模板不存在", null, $data);
                }

                /*拼接完整的arclist标签语法*/
                $offset = intval($strnum);
                $row = intval($offset) + intval($arcmultiRow['pagesize']);
                $innertext = "{eyou:arclist";
                foreach ($attarray as $key => $val) {
                    if (in_array($key, ['tagid','offset','row'])) {
                        continue;
                    }
                    $innertext .= " {$key}='{$val}'";
                }
                $innertext .= " limit='{$offset},{$row}'}";
                $innertext .= $tpl_content;
                $innertext .= "{/eyou:arclist}";
                /*--end*/
                $msg = $this->display($innertext); // 渲染模板标签语法
                $data['msg'] = $msg;

                //是否到了最终页
                if (!empty($queryRow[0]['totalNum']) && $queryRow[0]['totalNum'] <= $row) {
                    $data['lastpage'] = 1;
                }

            } else {
                $data['lastpage'] = 1;
            }
        }

        $this->success('请求成功', null, $data);
    }

    /**
     * 获取表单令牌
     */
    public function get_token($name = '__token__')
    {
        $name = preg_replace('/([^\w\-]+)/i', '', $name);
        if (IS_AJAX && strstr($name, '_token_')) {
            echo $this->request->token($name);
            exit;
        } else {
            abort(404);
        }
    }

    /**
     * 检验会员登录
     */
    public function check_user()
    {
        if (IS_AJAX) {
            $type = input('param.type/s', 'default');
            $img = input('param.img/s');
            $afterhtml = input('param.afterhtml/s');
            $users_id = session('users_id');
            if ('login' == $type) {
                if (!empty($users_id)) {
                    $currentstyle = input('param.currentstyle/s');
                    $users = M('users')->field('username,nickname,head_pic,sex')
                        ->where([
                            'users_id'  => $users_id,
                            'lang'      => $this->home_lang,  
                        ])->find();
                    if (!empty($users)) {
                        $nickname = $users['nickname'];
                        if (empty($nickname)) {
                            $nickname = $users['username'];
                        }
                        $head_pic = get_head_pic(htmlspecialchars_decode($users['head_pic']), false, $users['sex']);
                        $users['head_pic'] = func_preg_replace(['http://thirdqq.qlogo.cn'], ['https://thirdqq.qlogo.cn'], $head_pic);
                        if (!empty($afterhtml)) {
                            preg_match_all('/~(\w+)~/iUs', $afterhtml, $userfields);
                            if (!empty($userfields[1])) {
                                $users['url'] = url('user/Users/login');
                                foreach ($userfields[1] as $key => $val) {
                                    $replacement = !empty($users[$val]) ? $users[$val] : '';
                                    $afterhtml = str_replace($userfields[0][$key], $users[$val], $afterhtml);
                                }
                                $users['html'] = htmlspecialchars_decode($afterhtml);
                            } else {
                                $users['html'] = $nickname;
                            }
                        } else {
                            if ('on' == $img) {
                                $users['html'] = "<img class='{$currentstyle}' alt='{$nickname}' src='{$users['head_pic']}' />";
                            } else {
                                $users['html'] = $nickname;
                            }
                        }
                        $users['ey_is_login'] = 1;
                        cookie('users_id', $users_id);
                        $this->success('请求成功', null, $users);
                    }
                }
                
                $data = [
                    'ey_is_login'   => 0,
                    'ey_third_party_login'  => $this->is_third_party_login(),
                    'ey_third_party_qqlogin'  => $this->is_third_party_login('qq'),
                    'ey_third_party_wxlogin'  => $this->is_third_party_login('wx'),
                    'ey_third_party_wblogin'  => $this->is_third_party_login('wb'),
                    'ey_login_vertify'  => $this->is_login_vertify(),
                ];
                $this->success('请先登录', null, $data);
            }
            else if ('reg' == $type)
            {
                if (!empty($users_id)) {
                    $users['ey_is_login'] = 1;
                } else {
                    $users['ey_is_login'] = 0;
                }
                $this->success('请求成功', null, $users);
            }
            else if ('logout' == $type)
            {
                if (!empty($users_id)) {
                    $users['ey_is_login'] = 1;
                } else {
                    $users['ey_is_login'] = 0;
                }
                $this->success('请求成功', null, $users);
            }
            else if ('cart' == $type)
            {
                if (!empty($users_id)) {
                    $users['ey_is_login'] = 1;
                    $users['ey_cart_num_20191212'] = Db::name('shop_cart')->where(['users_id'=>$users_id])->sum('product_num');
                } else {
                    $users['ey_is_login'] = 0;
                    $users['ey_cart_num_20191212'] = 0;
                }
                $this->success('请求成功', null, $users);
            }
            else if ('collect' == $type)
            {
                if (!empty($users_id)) {
                    $users['ey_is_login'] = 1;
                    $users['ey_collect_num_20191212'] = Db::name('users_collection')->where(['users_id'=>$users_id])->count();
                } else {
                    $users['ey_is_login'] = 0;
                    $users['ey_collect_num_20191212'] = 0;
                }
                $this->success('请求成功', null, $users);
            }
            $this->error('访问错误');
        } else {
            abort(404);
        }
    }

    /**
     * 是否启用并开启第三方登录
     * @return boolean [description]
     */
    private function is_third_party_login($type = '')
    {
        static $result = null;
        if (null === $result) {
            $result = Db::name('weapp')->field('id,code,data')->where([
                   'code'  => ['IN', ['QqLogin','WxLogin','Wblogin']],
                   'status'    => 1,
               ])->getAllWithIndex('code');
        }
        $value = 0;
        if (empty($type)) {
           $qqlogin = 0;
           if (!empty($result['QqLogin']['data'])) {
               $qqData = unserialize($result['QqLogin']['data']);
               if (!empty($qqData['login_show'])) {
                   $qqlogin = 1;
               }
           }
           
           $wxlogin = 0;
           if (!empty($result['WxLogin']['data'])) {
               $wxData = unserialize($result['WxLogin']['data']);
               if (!empty($wxData['login_show'])) {
                   $wxlogin = 1;
               }
           }
           
           $wblogin = 0;
           if (!empty($result['Wblogin']['data'])) {
               $wbData = unserialize($result['Wblogin']['data']);
               if (!empty($wbData['login_show'])) {
                   $wblogin = 1;
               }
           }
           
           if ($qqlogin == 1 || $wxlogin == 1 || $wblogin == 1) {
               $value = 1;
           } 
        } else {
            if ('qq' == $type) {
                if (!empty($result['QqLogin']['data'])) {
                   $qqData = unserialize($result['QqLogin']['data']);
                   if (!empty($qqData['login_show'])) {
                       $value = 1;
                   }
                }
            } else if ('wx' == $type) {
                if (!empty($result['WxLogin']['data'])) {
                   $wxData = unserialize($result['WxLogin']['data']);
                   if (!empty($wxData['login_show'])) {
                       $value = 1;
                   }
                }
            } else if ('wb' == $type) {
                if (!empty($result['Wblogin']['data'])) {
                   $wbData = unserialize($result['Wblogin']['data']);
                   if (!empty($wbData['login_show'])) {
                       $value = 1;
                   }
                }
            }
        }
    
        return $value;
    }

    /**
     * 是否开启登录图形验证码
     * @return boolean [description]
     */
    private function is_login_vertify()
    {
        // 默认开启验证码
        $is_vertify          = 1;
        $users_login_captcha = config('captcha.users_login');
        if (!function_exists('imagettftext') || empty($users_login_captcha['is_on'])) {
            $is_vertify = 0; // 函数不存在，不符合开启的条件
        }

        return $is_vertify;
    }

    /**
     * 获取用户信息
     */
    public function get_tag_user_info()
    {
        if (!IS_AJAX) {
            abort(404);
        }

        $t_uniqid = input('param.t_uniqid/s', '');
        if (IS_AJAX && !empty($t_uniqid)) {
            $users_id = session('users_id');
            if (!empty($users_id)) {
                $users = Db::name('users')->field('b.*, a.*')
                    ->alias('a')
                    ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                    ->where([
                        'a.users_id' => $users_id,
                        'a.lang'     => $this->home_lang,
                    ])->find();
                if (!empty($users)) {
                    $users['reg_time'] = MyDate('Y-m-d H:i:s', $users['reg_time']);
                    $users['update_time'] = MyDate('Y-m-d H:i:s', $users['update_time']);
                } else {
                    $users = [];
                    $tableFields1 = Db::name('users')->getTableFields();
                    $tableFields2 = Db::name('users_level')->getTableFields();
                    $tableFields = array_merge($tableFields1, $tableFields2);
                    foreach ($tableFields as $key => $val) {
                        $users[$val] = '';
                    }
                }
                unset($users['password']);
                unset($users['paypwd']);
                // 头像处理
                $head_pic = get_head_pic(htmlspecialchars_decode($users['head_pic']), false, $users['sex']);
                $users['head_pic'] = func_preg_replace(['http://thirdqq.qlogo.cn'], ['https://thirdqq.qlogo.cn'], $head_pic);
                $users['url'] = url('user/Users/centre');
                $dtypes = [];
                foreach ($users as $key => $val) {
                    $html_key = md5($key.'-'.$t_uniqid);
                    $users[$html_key] = $val;

                    $dtype = 'txt';
                    if (in_array($key, ['head_pic'])) {
                        $dtype = 'img';
                    } else if (in_array($key, ['url'])) {
                        $dtype = 'href';
                    }
                    $dtypes[$html_key] = $dtype;

                    unset($users[$key]);
                }

                $data = [
                    'ey_is_login'   => 1,
                    'users'  => $users,
                    'dtypes'  => $dtypes,
                ];
                $this->success('请求成功', null, $data);
            }
            $this->success('请先登录', null, ['ey_is_login'=>0]);
        }
        $this->error('访问错误');
    }

    // 验证码获取
    public function vertify()
    {
        $time = getTime();
        $type = input('param.type/s', 'default');
        $type = preg_replace('/([^\w\-]+)/i', '', $type);
        $token = input('param.token/s', '');
        $token = preg_replace('/([^\w\-]+)/i', '', $token);
        $configList = \think\Config::get('captcha');
        $captchaArr = array_keys($configList);
        if (in_array($type, $captchaArr)) {
            /*验证码插件开关*/
            $admin_login_captcha = config('captcha.'.$type);
            $config = (!empty($admin_login_captcha['is_on']) && !empty($admin_login_captcha['config'])) ? $admin_login_captcha['config'] : config('captcha.default');
            /*--end*/
        } else {
            $config = config('captcha.default');
        }

        ob_clean(); // 清空缓存，才能显示验证码
        $Verify = new \think\Verify($config);
        if (!empty($token)) {
            $Verify->entry($token);
        } else {
            $Verify->entry($type);
        }
        exit();
    }
      
    /**
     * 邮箱发送
     */
    public function send_email()
    {
        // 超时后，断掉邮件发送
        function_exists('set_time_limit') && set_time_limit(10);
        \think\Session::pause(); // 暂停session，防止session阻塞机制

        $type = input('param.type/s');
        
        // 留言发送邮件
        if (IS_AJAX_POST && 'gbook_submit' == $type) {

            // 是否满足发送邮箱的条件
            $is_open = Db::name('smtp_tpl')->where(['send_scene'=>1,'lang'=>$this->home_lang])->value('is_open');
            $smtp_config = tpCache('smtp');
            if (empty($is_open) || empty($smtp_config['smtp_user']) || empty($smtp_config['smtp_pwd'])) {
                $this->error("邮箱尚未配置，发送失败");
            }

            $tid = input('param.tid/d');
            $aid = input('param.aid/d');

            $send_email_scene = config('send_email_scene');
            $scene = $send_email_scene[1]['scene'];

            $web_name = tpCache('web.web_name');
            // 判断标题拼接
            $arctype  = M('arctype')->field('typename')->find($tid);
            $web_name = $arctype['typename'].'-'.$web_name;

            // 拼装发送的字符串内容
            $row = M('guestbook_attribute')->field('a.attr_input_type,a.attr_name, b.attr_value')
                ->alias('a')
                ->join('__GUESTBOOK_ATTR__ b', 'a.attr_id = b.attr_id AND a.typeid = '.$tid, 'LEFT')
                ->where([
                    'b.aid' => $aid,
                ])
                ->order('a.attr_id sac')
                ->select();
            $content = '';
            foreach ($row as $key => $val) {
                if ($val['attr_input_type'] == 9) {
                    $val['attr_value'] = Db::name('region')->where('id','in',$val['attr_value'])->column('name');
                    $val['attr_value'] = implode('',$val['attr_value']);
                } else if ($val['attr_input_type'] == 4) {
                    $val['attr_value'] = filter_line_return($val['attr_value'], '、');
                }else if(10 == $val['attr_input_type']){
                    $val['attr_value'] = date('Y-m-d H:i:s',$val['attr_value']);
                }else if(11 == $val['attr_input_type']){
                    $attr_value_arr = explode(",",$val['attr_value']);
                    $attr_value_str = "";
                    foreach ($attr_value_arr as $attr_value_k => $attr_value_v){
                        $attr_value_v = handle_subdir_pic($attr_value_v,'img',true);
                        $attr_value_str .= "<a href='{$attr_value_v}' target='_blank'><img src='{$attr_value_v}' width='60' height='60' style='float: unset;cursor: pointer;' /></a>";
                    }
                    $val['attr_value'] = $attr_value_str;
                } else {
                    if (preg_match('/(\.(jpg|gif|png|bmp|jpeg|ico|webp))$/i', $val['attr_value'])) {
                        if (!stristr($val['attr_value'], '|')) {
                            $val['attr_value'] = handle_subdir_pic($val['attr_value'],'img',true);
                            $val['attr_value'] = "<a href='{$val['attr_value']}' target='_blank'><img src='{$val['attr_value']}' width='60' height='60' style='float: unset;cursor: pointer;' /></a>";
                        }
                    }elseif (preg_match('/(\.('.tpCache('basic.file_type').'))$/i', $val['attr_value'])){
                        if (!stristr($val['attr_value'], '|')) {
                            $val['attr_value'] = handle_subdir_pic($val['attr_value'],'img',true);
                            $val['attr_value'] = "<a href='{$val['attr_value']}' download='".time()."'><img src=\"".$this->request->domain().ROOT_DIR."/public/static/common/images/file.png\" alt=\"\" style=\"width: 16px;height:  16px;\">点击下载</a>";
                        }
                    }
                }
                $content .= $val['attr_name'] . '：' . $val['attr_value'].'<br/>';
            }
            $html = "<p style='text-align: left;'>{$web_name}</p><p style='text-align: left;'>{$content}</p>";
            if (isMobile()) {
                $html .= "<p style='text-align: left;'>——来源：移动端</p>";
            } else {
                $html .= "<p style='text-align: left;'>——来源：电脑端</p>";
            }
            
            // 发送邮件
            $res = send_email(null,null,$html, $scene);
            if (intval($res['code']) == 1) {
                $this->success($res['msg']);
            } else {
                $this->error($res['msg']);
            }
        }
    }

    /**
     * 手机短信发送
     */
    public function SendMobileCode()
    {
        // 超时后，断掉发送
        function_exists('set_time_limit') && set_time_limit(5);
        // \think\Session::pause(); // 暂停session，防止session阻塞机制

        /*$pretime1 = getTime() - 120; // 3分钟内
        $ip_prefix = preg_replace('/\d+\.\d+$/i', '', clientIP());
        $count = Db::name('sms_log')->where([
                'ip'    => ['LIKE', "{$ip_prefix}%"],
                'is_use'    => 1,
                'add_time'  => ['gt', $pretime1],
            ])->count();
        if (!empty($count) && 5 <= $count) {
            $this->error('发送短信异常~');
        }*/

        // 发送手机验证码
        if (IS_AJAX_POST) {
            $post = input('post.');
            $source = !empty($post['source']) ? $post['source'] : 0;

            // 留言验证类型发送短信处理
            if (isset($post['scene']) && in_array($post['scene'], [7])) {
                // 是否允许再次发送
                $where = [
                    'source' => $post['scene'],
                    'mobile' => $post['phone'],
                    'status' => 1,
                    'is_use' => 0,
                    'add_time' => ['>', getTime() - 120]
                ];
                $Result = Db::name('sms_log')->where($where)->order('id desc')->count();
                if (!empty($Result)) $this->error('120秒内只能发送一次');

                // 图形验证码判断
                if (empty($post['code'])) $this->error('请输入图片验证码');
                $verify = new \think\Verify();
                if (!$verify->check($post['code'], $post['code_token'])) $this->error('图片验证码错误');

                // 发送并返回结果
                $Result = sendSms(7, $post['phone'], array('content' => mt_rand(1000, 9999)));
                if (1 === intval($Result['status'])) {
                    $this->success('发送成功');
                } else {
                    $this->error($Result['msg']);
                }
            }
            // 订单付款和订单发货类型发送短信处理
            else if (isset($post['scene']) && in_array($post['scene'], [5, 6])) {
                if (empty($post['mobile'])) return false;
                /*发送并返回结果*/
                $data = $post['data'];
                //兼容原先消息通知的发送短信的逻辑
                //查询消息通知模板的内容
                $sms_type = tpCache('sms.sms_type') ? : 1;
                $tpl_content = Db::name('sms_template')->where(["send_scene"=> $post['scene'],"sms_type"=> $sms_type])->value('tpl_content');
                if (!$tpl_content) return false;
                $preg_res = preg_match('/订单/', $tpl_content);
                switch ($data['type']) {
                    case '1':
                        $content = $preg_res ? '待发货' : '您有新的待发货订单';
                        break;
                    case '2':
                        $content = $preg_res ? $data['order_code'] : $data['order_code'];
                        break;
                    default:
                        $content = '';
                        break;
                }
                $Result = sendSms($post['scene'], $post['mobile'], array('content'=>$content));
                if (intval($Result['status']) == 1) {
                    $this->success('发送成功！');
                } else {
                    $this->error($Result['msg']);
                }
                /* END */
            }
            // 其他类型发送短信处理
            else {
                if (isset($post['type']) && in_array($post['type'], ['users_mobile_reg','users_mobile_login','reg'])) {
                    // 数据验证
                    $rule = [
                        'mobile'    => 'require|token:__mobile_1_token__',
                    ];
                    $message = [
                        'mobile.require' => '请输入手机号码！',
                    ];
                    $validate = new \think\Validate($rule, $message);
                    if(!$validate->batch()->check($post))
                    {
                        $this->error('表单令牌过期，请尝试刷新页面~');
                    }

                    $post['is_mobile'] = true;
                }
                $mobile = !empty($post['mobile']) ? $post['mobile'] : session('mobile');
                $is_mobile = !empty($post['is_mobile']) ? $post['is_mobile'] : false;
                if (empty($mobile)) $this->error('请先绑定手机号码');
                if (!empty($is_mobile)) {
                    /*是否存在手机号码*/
                    $where = [
                        'mobile' => $mobile
                    ];
                    $users_id = session('users_id');
                    if (!empty($users_id)) $where['users_id'] = ['NEQ', $users_id];
                    $Result = Db::name('users')->where($where)->count();
                    /* END */
                    if (0 == $post['source']) {
                        if (!empty($Result)) $this->error('手机号码已注册');
                    } else if (2 == $post['source']) {
                        if (empty($Result)) $this->error('手机号码未注册');
                    } else if (4 == $post['source']) {
                        if (empty($Result)) $this->error('手机号码不存在');
                    } else {
                        if (!empty($Result)) $this->error('手机号码已存在');
                    }
                }

                /*是否允许再次发送*/
                $where = [
                    'mobile'   => $mobile,
                    'source'   => $source,
                    'status'   => 1,
                    'is_use'   => 0,
                    'add_time' => ['>', getTime() - 120]
                ];
                $Result = Db::name('sms_log')->where($where)->order('id desc')->count();

                if (!empty($Result) && false == config('sms_debug')) $this->error('120秒内只能发送一次！');
                /* END */

                /*图形验证码判断*/
                if (!empty($post['IsVertify']) || (isset($post['type']) && in_array($post['type'], ['users_mobile_reg','users_mobile_login','bind','other']))) {
                    if (empty($post['vertify'])) $this->error('请输入图形验证码！');
                    $verify = new \think\Verify();
                    if (!$verify->check($post['vertify'], $post['type'])) $this->error('图形验证码错误！', null, ['code'=>'vertify']);
                }
                /* END */

                /*发送并返回结果*/
                $Result = sendSms($source, $mobile, array('content' => mt_rand(1000, 9999)));
                if (intval($Result['status']) == 1) {
                    $this->success('发送成功！');
                } else {
                    $this->error($Result['msg']);
                }
                /* END */
            }
        }
    }

    // 判断文章内容阅读权限
    public function get_arcrank($aid = '', $vars = '')
    {
        $aid = intval($aid);
        $vars = intval($vars);
        $gourl = input('param.gourl/s');
        $gourl = urldecode($gourl);
        $gourl = !empty($gourl) ? urldecode($gourl) : ROOT_DIR.'/';
        if ((IS_AJAX || !empty($vars)) && !empty($aid)) {
            // 用户ID
            $users_id = session('users_id');
            // 文章查看所需等级值
            $Arcrank = M('archives')->alias('a')
                ->field('a.users_id, a.arcrank,c.typearcrank')
                ->join('__ARCTYPE__ c', 'a.typeid = c.id', 'LEFT')
                ->where(['a.aid' => $aid])
                ->find();
            //文章存在限制条件，优先使用文章限制条件；如不存在，则使用栏目限制条件。
            if (empty($Arcrank['arcrank']) && (!empty($Arcrank['typearcrank']) && $Arcrank['typearcrank'] > 0)){
                $Arcrank['arcrank'] = $Arcrank['typearcrank'];
            }

            if (!empty($users_id)) {
                // 会员级别等级值
                $UsersDataa = Db::name('users')->alias('a')
                    ->field('a.users_id,b.level_value,b.level_name')
                    ->join('__USERS_LEVEL__ b', 'a.level = b.level_id', 'LEFT')
                    ->where(['a.users_id'=>$users_id])
                    ->find();
                if (0 == $Arcrank['arcrank']) {
                    if (IS_AJAX) {
                        $this->success('允许查阅！');
                    } else {
                        return true;
                    }
                }else if (-1 == $Arcrank['arcrank']) {
                    $is_admin = session('?admin_id') ? 1 : 0;
                    $param_admin_id = input('param.admin_id/d');
                    if ($users_id == $Arcrank['users_id']) {
                        if (IS_AJAX) {
                            $this->success('允许查阅！', null, ['is_admin'=>$is_admin, 'msg'=>'待审核稿件，仅限自己查看！']);
                        } else {
                            return true;
                        }
                    }else if(!empty($is_admin) && !empty($param_admin_id)){
                        if (IS_AJAX) {
                            $this->success('允许查阅！', null, ['is_admin'=>$is_admin, 'msg'=>'待审核稿件，仅限管理员查看！']);
                        } else {
                            return true;
                        }
                    }else{
                        $msg = '待审核稿件，你没有权限阅读！';
                    }
                }else if ($UsersDataa['level_value'] < $Arcrank['arcrank']) {
                    $level_name = Db::name('users_level')->where(['level_value' =>$Arcrank['arcrank']])->getField('level_name');
                    $msg = '__html__内容需要【'.$level_name.'】才可以查看<br/>您为【'.$UsersDataa['level_name'].'】，请先升级！';

                }else{
                    if (IS_AJAX) {
                        $this->success('允许查阅！');
                    } else {
                        return true;
                    }
                }
                if (IS_AJAX) {
                    $this->error($msg);
                } else {
                    return $msg;
                }
            }else{
                if (0 == $Arcrank['arcrank']) {
                    if (IS_AJAX) {
                        $this->success('允许查阅！');
                    } else {
                        return true;
                    }
                }else if (-1 == $Arcrank['arcrank']) {
                    $is_admin = session('?admin_id') ? 1 : 0;
                    $param_admin_id = input('param.admin_id/d');
                    if (!empty($is_admin) && !empty($param_admin_id)) {
                        $this->success('允许查阅！', null, ['is_admin'=>$is_admin, 'msg'=>'待审核稿件，仅限管理员查看！']);
                    } else {
                        $msg = '待审核稿件，你没有权限阅读！';
                    }
                }else if (!empty($Arcrank['arcrank'])) {
                    $level_name = Db::name('users_level')->where(['level_value' =>$Arcrank['arcrank']])->getField('level_name');
                    $msg = '文章需要【'.$level_name.'】才可以查看，游客不可查看，请登录！';
                }else{
                    $msg = '游客不可查看，请登录！';
                }
                if (IS_AJAX) {
                    $loginUrl = url('user/Users/login');
                    if (stristr($loginUrl, '?')) {
                        $gourl = $loginUrl."&referurl=".urlencode($gourl);
                    } else {
                        $gourl = $loginUrl."?referurl=".urlencode($gourl);
                    }
                    $data = [
                        'is_login' => 0,
                        'gourl' => $gourl,
                    ];
                    $this->error($msg, null, $data);
                } else {
                    return $msg;
                }
            }
        } else {
            abort(404);
        }
    }

    /**
     * 获取会员列表
     * @author 小虎哥 by 2018-4-20
     */
    public function get_tag_memberlist()
    {
        $this->error('暂时没用上！');
        if (IS_AJAX_POST) {
            $htmlcode = input('post.htmlcode/s');
            $htmlcode = htmlspecialchars_decode($htmlcode);
            $htmlcode = preg_replace('/<\?(\s*)php(\s+)/i', '', $htmlcode);

            $attarray = input('post.attarray/s');
            $attarray = htmlspecialchars_decode($attarray);
            $attarray = json_decode(base64_decode($attarray));

            /*拼接完整的memberlist标签语法*/
            $eyou = new \think\template\taglib\Eyou('');
            $tagsList = $eyou->getTags();
            $tagsAttr = $tagsList['memberlist'];
            
            $innertext = "{eyou:memberlist";
            foreach ($attarray as $key => $val) {
                if (!in_array($key, $tagsAttr) || in_array($key, ['js'])) {
                    continue;
                }
                $innertext .= " {$key}='{$val}'";
            }
            $innertext .= " js='on'}";
            $innertext .= $htmlcode;
            $innertext .= "{/eyou:memberlist}";
            /*--end*/
            $msg = $this->display($innertext); // 渲染模板标签语法
            $data['msg'] = $msg;

            $this->success('读取成功！', null, $data);
        }
        $this->error('加载失败！');
    }

    /**
     * 发布或编辑文档时，百度自动推送
     */
    public function push_zzbaidu($url = '', $type = 'add')
    {
        $msg = '百度推送URL失败！';
        if (IS_AJAX_POST) {
            \think\Session::pause(); // 暂停session，防止session阻塞机制

            if (is_dir('./weapp/Pushall/')) {
                $info = Db::name('weapp')->where(['code'=>'Pushall', 'status'=>1])->find();
                if (!empty($info)) {
                    $this->error('检测到已使用【聚合推送】插件', null, ['code'=>0]);
                }
            }

            // 获取token的值：http://ziyuan.baidu.com/linksubmit/index?site=http://www.eyoucms.com/
            $sitemap_zzbaidutoken = config('tpcache.sitemap_zzbaidutoken');
            if (empty($sitemap_zzbaidutoken)) {
                $this->error('尚未配置实时推送Url的token！', null, ['code'=>0]);
            } else if (!function_exists('curl_init')) {
                $this->error('请开启php扩展curl_init', null, ['code'=>1]);
            }

            $urlsArr[] = $url;
            $type = ('edit' == $type) ? 'update' : 'urls';

            if (is_http_url($sitemap_zzbaidutoken)) {
                $searchs = ["/urls?","/update?"];
                $replaces = ["/{$type}?", "/{$type}?"];
                $api = str_replace($searchs, $replaces, $sitemap_zzbaidutoken);
            } else {
                $api = 'http://data.zz.baidu.com/'.$type.'?site='.$this->request->host(true).'&token='.trim($sitemap_zzbaidutoken);
            }

            $ch = curl_init();
            $options =  array(
                CURLOPT_URL => $api,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => implode("\n", $urlsArr),
                CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            !empty($result) && $result = json_decode($result, true);
            if (!empty($result['success'])) {
                $this->success('百度推送URL成功！');
            } else {
                $msg = !empty($result['message']) ? $result['message'] : $msg;
            }
        }

        $this->error($msg);
    }

    /**
     * 发布或编辑文档/栏目时，小程序 API 提交
     * 将小程序资源 path 路径，提交到 API 接口中
     */
    public function push_bdminipro($aid=0,$typeid=0)
    {
        //先判断是否安装百度小程序插件
        $BdDiyminipro = Db::name('weapp')->where('code','BdDiyminipro')->where('status',1)->find();
        if (empty($BdDiyminipro)){
            $this->error('未安装可视化百度小程序！');
        }else{
            $data = Db::name('weapp_bd_diyminipro_setting')->where('name','setting')->order('mini_id desc')->find();
            $value = json_decode($data['value'],true);
            if (empty($value['appKey']) || empty($value['appSecret'])) {
                $this->error('未配置可视化百度小程序！');
            }
            $access_token = '';
            if (empty($value['access_token']) || (!empty($value['access_token_extime']) && $value['access_token_extime'] > getTime() )){
                if (!empty($value['appId'])){
                    $vaules = [];
                    $vaules['appId'] = $value['appId'];
                    $url = "https://service.eyysz.cn/index.php?m=api&c=BaiduMiniproClient&a=minipro&".http_build_query($vaules);
                    $response = httpRequest($url);
                    $params = array();
                    $params = json_decode($response, true);
                    if (!empty($params) && $params['errcode'] == 0) {
                        $at_url = 'https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id='.$params['errmsg']['appKey'].'&client_secret='.$params['errmsg']['appSecret'].'&scope=smartapp_snsapi_base';
                        $response1 = httpRequest($at_url);
                        $params1 = array();
                        $params1 = json_decode($response1, true);
                        if (!empty($params1['access_token'])){
                            $value['access_token'] = $access_token = $params1['access_token'];
                            $value['access_token_extime'] = getTime()+$params1['expires_in'];
                            $updateValue = json_encode($value);
                            Db::name('weapp_bd_diyminipro_setting')->where('mini_id',$data['mini_id'])->update(['value'=>$updateValue,'update_time'=>getTime()]);
                        }
                    }
                }
            }else{
                $access_token = $value['access_token'];
            }
            if (!empty($access_token)){
                $res = push_bdminiproapi($access_token,1,$aid,$typeid);
                if (!empty($res)){
                    $this->success($res['msg']);
                }
            }
        }
    }

    /*
     * 视频权限播放逻辑
     */
    public function video_logic()
    {
        $post = input('post.');
        if (IS_AJAX_POST && !empty($post['aid'])) {

            // 查询文档信息 
            $field = 'a.*,b.*,c.*';
            $where = [
                'a.aid' => $post['aid'],
                'a.is_del' => 0
            ];
            $archivesInfo = Db::name('archives')
                ->alias('a')
                ->field($field)
                ->join('__USERS_LEVEL__ b', 'a.arc_level_id = b.level_id', 'LEFT')
                ->join('__ARCTYPE__ c', 'c.id = a.typeid', 'LEFT')
                ->where($where)
                ->find();

            if (5 == $archivesInfo['channel']) {
                // 获取用户最新信息
                $UsersData = GetUsersLatestData();
                $UsersID = !empty($UsersData['users_id']) ? $UsersData['users_id'] : 0;
                $result['status_value'] = 0; // status_value 0-所有人免费 1-所有人付费 2-会员免费 3-会员付费
                $result['status_name'] = ''; //status_name 要求会员等级时会员级别名称
                $result['play_auth'] = 0; //播放权限
                $result['vip_status'] = 0; //status_value=3时使用 vip_status=1则已升级会员暂未购买
                $users_price = get_discount_price($UsersData['level_discount'],$archivesInfo['users_price']);

                /*是否需要付费*/
                if (0 < $archivesInfo['users_price'] && empty($archivesInfo['users_free'])) {
                    if (empty($archivesInfo['arc_level_id'])){
                        //不限会员 付费
                        $result['status_value'] = 1;
                        $result['users_price'] = $users_price;
                    }else{
                        //3-限制会员 付费
                        $result['status_value'] = 3;
                        $result['users_price'] = $users_price;
                        if ($archivesInfo['level_value'] <= $UsersData['level_value']){
                            $result['vip_status'] = 1;//已升级会员未购买
                        }
                    }

                    if (!empty($UsersID)) {
                        $where = [
                            'users_id' => intval($UsersID),
                            'product_id' => intval($post['aid']),
                            'order_status' => 1
                        ];
                        // 存在数据则已付费
                        $Paid = Db::name('media_order')->where($where)->count();
                        //已购买
                        if (!empty($Paid)) {
                            if (3 == $result['status_value']) {
                                if (1 == $result['vip_status']){
                                    $result['play_auth'] = 1;
                                    $result['vip_status'] = 3;//已升级会员已经购买
                                }else{
                                    $result['play_auth'] = 0;
                                    $result['vip_status'] = 2;//未升级会员已经购买
                                }
                            }else{
                                $result['play_auth'] = 1;
                                $result['vip_status'] = 4;//不限会员已经购买
                            }
                        }
                    }
                }else{
                    if (0 < intval($archivesInfo['arc_level_id'])) { // 会员免费
                        $result['status_value'] = 2;
                        if (!empty($UsersID) && $archivesInfo['level_value'] <= $UsersData['level_value']) {
                            $result['play_auth'] = 1;
                        }
                    } else { // 所有人免费
                        $result['play_auth'] = 1;
                    }
                }
                /*END*/

                /**注册会员免费但是没有登录*/
                // if (empty($UsersID) && !empty($result['status_value'])) {
                //     $this->error('请先登录', url('user/Users/login'));
                // }

                $where = [
                    'users_id' => intval($UsersID),
                    'product_id' => intval($post['aid']),
                    'order_status' => 1
                ];
                // 存在数据则已付费
                /*END*/

                $is_pay = 0;
                if (in_array($result['status_value'], [1,3])){ // 所有人、会员付费
                    $is_pay = Db::name('media_order')->where($where)->count();
                }
                $result['is_pay'] = $is_pay;

                if (in_array($result['status_value'], [2,3])){ // 已满足会员级别要求
                    $result['status_name'] = Db::name('users_level')->where('level_id', intval($archivesInfo['arc_level_id']))->value('level_name');
                }

/*
                if (in_array($result['status_value'], [2,3])){ // 会员免费与会员付费
                    $result['status_name'] = Db::name('users_level')->where('level_id', $archivesInfo['arc_level_id'])->value('level_name');
                    $vip_status = 0;
                    if ($archivesInfo['level_value'] <= $UsersData['level_value']) {
                        $vip_status = 1; // 已满足会员级别要求
                    }
                }*/

                if ($result['status_value'] == 0){
                    $result['button'] = '免费';
                    $result['status_name'] = '免费';
                }else if ($result['status_value'] == 1){ // 所有人付费
                    $result['button'] = '付费';
                    if (!empty($is_pay)){
                        $result['button'] = '观看';
                    }
                }else if ($result['status_value'] == 2){
                    $result['button'] = 'VIP';
                    if (!empty($result['play_auth'])){
                        $result['button'] = '观看';
                    }
                }else if ($result['status_value'] == 3){
                    // if(1 == $result['vip_status']){
                    //     $result['button'] = '立即购买';
                    //     $result['button_url'] = 'MediaOrderBuy_1592878548();';
                    // }else
                    if (2 == $result['vip_status']){
                        $result['button'] = 'VIP';
                        $result['button_url'] = "window.location.href = '" . url('user/Level/level_centre') . "'";
                    } else if (3 == $result['vip_status']) {
                        $result['button'] = '观看';
                    }else{
                        $result['button'] = 'VIP付费';
                        $result['button_url'] = "window.location.href = '" . url('user/Level/level_centre') . "'";
                    }
                    // if (!empty($is_pay) && !empty($result['vip_status'])){
                    //     $result['button'] = '观看';
                    // }
                }
                if ('观看' == $result['button']){
                    $result['button_url'] = arcurl('home/Media/view', $archivesInfo);
                }

                $this->success('查询成功', null, $result);
            } else {
                $this->error('非视频模型的文档！');
            }
        }
        abort(404);
    }

    /**
     * 查看站内通知
     */
    public function notice_read()
    {
        $id = input('param.id/d');
        $users_id = session('users.users_id');
        $users_id = intval($users_id);
        if (!empty($id) && !empty($users_id)) {
            $count = Db::name('users_notice_read')
                ->where(['id' => $id])
                ->value("id");
            if (empty($count)) $this->error('未知错误！');

            //未读消息数-1
            $unread_num = Db::name('users')->where(['users_id' => $users_id])->value("unread_notice_num");
            if ($unread_num>0){
                $unread_num = $unread_num-1;
                Db::name('users')->where(['users_id' => $users_id])->update(['unread_notice_num'=>$unread_num]);
            }
            Db::name('users_notice_read')->where(['id'=>$id])->update(['is_read'=>1]);
            $this->success('保存成功',null,['unread_num'=>$unread_num]);
        }
    }

    /**
     * 收藏与取消
     * @return [type] [description]
     */
    public function collect_save()
    {
        $aid = input('param.aid/d');
        if (IS_AJAX && !empty($aid)) {

            $users_id = session('users_id');
            if (empty($users_id)) {
                $this->error('请先登录！');
            }

            $row = Db::name('users_collection')->where([
                'users_id'  => $users_id,
                'aid'   => $aid,
            ])->find();
            if (empty($row)) {
                $archivesInfo = Db::name('archives')->field('aid,title,litpic,channel,typeid')->find($aid);
                if (!empty($archivesInfo)) {
                    $r = Db::name('users_collection')->add([
                        'users_id'  => $users_id,
                        'title' => $archivesInfo['title'],
                        'aid' => $aid,
                        'litpic' => $archivesInfo['litpic'],
                        'channel' => $archivesInfo['channel'],
                        'typeid' => $archivesInfo['typeid'],
                        'lang'  => $this->home_lang,
                        'add_time'  => getTime(),
                        'update_time' => getTime(),
                    ]);
                    if (!empty($r)) {
                        Db::name('archives')->where('aid', $aid)->setInc('collection');
                        $this->success('收藏成功', null, ['opt'=>'add']);
                    }
                }
            } else {
                $r = Db::name('users_collection')->where([
                    'users_id'  => $users_id,
                    'aid' => $aid,
                ])->delete();
                Db::name('archives')->where('aid', $aid)->setDec('collection');
                $this->success('取消成功', null, ['opt'=>'cancel']);
            }
            $this->error('收藏失败', null);
        }
        abort(404);
    }

    /**
     * 判断是否收藏
     * @return [type] [description]
     */
    public function get_collection()
    {
        if (IS_AJAX) {
            $aid = input('param.aid/d');
            $users_id = session('users_id');
            $total = Db::name('users_collection')->where([
                'aid'   => $aid,
            ])->count();
            if (!empty($users_id)) {
                $count = Db::name('users_collection')->where([
                    'aid'   => $aid,
                    'users_id'  => $users_id,
                ])->count();
                if (!empty($count)) {
                    $this->success('已收藏', null, ['total'=>$total]);
                }
            }
            $this->error('未收藏', null, ['total'=>$total]);
        }
        abort(404);
    }

    /**
     * 保存足迹
     */
    public function footprint_save()
    {
        $aid = input('param.aid/d');
        $ajaxLogic = new \app\api\logic\AjaxLogic;
        $data = $ajaxLogic->footprint_save($aid);
        if ($data === false) {
            abort(404);
        } else {
            $this->success('ok');
        }
    }

    /**
     * 签到
     * @return [type] [description]
     */
    public function signin_save()
    {
        if (IS_AJAX) {
            $users_id = session('users_id');
            if (empty($users_id)) {
                $this->error('请先登录！');
            }
            $signin_conf = getUsersConfigData('score');
            if (!$signin_conf || !isset($signin_conf['score_signin_status']) || $signin_conf['score_signin_status'] != 1) {
                $this->error('未开启签到配置！');
            }

            //今日签到信息
            $now_time = time();
            $today_start = mktime(0,0,0,date("m",$now_time),date("d",$now_time),date("Y",$now_time));
            $today_end = mktime(23,59,59,date("m",$now_time),date("d",$now_time),date("Y",$now_time));
            $row = Db::name('users_signin')->where(['users_id'=>$users_id,'add_time'=>['BETWEEN',[$today_start,$today_end]]])->value("id");

            if (!$row) {
                $r = Db::name('users_signin')->add([
                    'users_id'  => $users_id,
                    'lang'  => $this->home_lang,
                    'add_time'  => getTime(),
                ]);
                if (!empty($r)) {
                    $scores_step = $signin_conf['score_signin_score'] ?:0;
                    Db::name('users')->where(['users_id'=>$users_id])->setInc('scores',$scores_step);
                    $users_scores = Db::name('users')->where(['users_id'=>$users_id])->value("scores");

                    Db::name('users_score')->add([
                        'type'  => 5,//每日签到
                        'users_id'  => $users_id,
                        'ask_id'  => 0,
                        'reply_id'  => 0,
                        'score'  => $scores_step,
                        'devote'  => $scores_step,
                        'money'  => 0.00,
                        'info'  => '每日签到',
                        'lang'  => $this->home_lang,
                        'add_time'  => getTime(),
                        'update_time'  => getTime(),
                    ]);
                    $this->success('签到成功', null,['scores'=>$users_scores]);
                }
                $this->error('未知错误', null);
            }
            $this->error('今日已签过到', null);
        }
        abort(404);
    }

    /**
     * 登录页面清除session多余文件
     */
    public function clear_session()
    {
        if (IS_AJAX_POST) {
            \think\Session::pause(); // 暂停session，防止session阻塞机制
            clear_session_file(); // 清理过期的data/session文件
        } else {
            abort(404);
        }
    }

    //文章付费
    public function ajax_get_content($aid=0)
    {
        if (empty($aid)){
            $this->error('缺少文档id');
        }
        $artData = Db::name('archives')
            ->alias('a')
            ->field('a.restric_type,a.arc_level_id,b.content,b.content_ey_m,a.users_price,a.add_time')
            ->join('article_content b','a.aid = b.aid')
            ->where('a.aid',$aid)
            ->find();
        if (isMobile() && !empty($artData['content_ey_m'])){
            $artData['content'] = $artData['content_ey_m'];
        }

        if (empty($artData['restric_type'])) { // 免费
            $result['display'] = 0; // 1-显示购买 0-不显示
            $result['content'] = $artData['content'];
        }
        else { // 其他

            /*预览内容*/
            $free_content = '';
            $pay_data = Db::name('article_pay')->field('part_free,free_content')->where('aid',$aid)->find();
            if (!empty($pay_data['part_free'])) {
                $free_content = !empty($pay_data['free_content']) ? $pay_data['free_content'] : '';
            }
            /*end*/

            $users_id = session('users_id');
            if (empty($users_id)) {
                //会员限制
                if (0 < $artData['arc_level_id']) {
                    $result['vipDisplay'] = 1;// 1-显示会员限制 0-不显示
                    $result['content'] = $free_content;
                } else {
                    $result['display'] = 1; // 1-显示购买 0-不显示
                    $result['content'] = $free_content;
                    $result['users_price'] = $artData['users_price'];
                }
            }
            else {
                $UsersData = GetUsersLatestData();
                //会员限制
                if (0 < $artData['arc_level_id'] && $UsersData['level'] < $artData['arc_level_id']) {
                    $result['vipDisplay'] = 1;// 1-显示会员限制 0-不显示
                    $result['content'] = $free_content;
                } else if (in_array($artData['restric_type'], [1,3])) { // 付费/会员付费
                    $is_pay = Db::name('article_order')->where(['users_id'=>$users_id,'order_status'=>1,'product_id'=>$aid])->find();
                    if (empty($is_pay)){ // 没有购买
                        $result['display'] = 1; // 1-显示购买 0-不显示
                        $result['content'] = $free_content;
                        $result['users_price'] = get_discount_price($UsersData['level_discount'],$artData['users_price']);
                    }else{ // 已经购买
                        $result['display'] = 0; // 1-显示购买 0-不显示
                        $result['content'] = $artData['content'];
                    }
                } else {
                    $result['content'] = $artData['content'];
                }
            }
        }

        $result['content'] = htmlspecialchars_decode($result['content']);
        $titleNew = !empty($data['title']) ? $data['title'] : '';
        $result['content'] = img_style_wh($result['content'], $titleNew);
        $result['content'] = handle_subdir_pic($result['content'], 'html');
        if (is_dir('./weapp/Linkkeyword')){
            $LinkkeywordModel = new \weapp\Linkkeyword\model\LinkkeywordModel();
            if (method_exists($LinkkeywordModel, 'handle_content')) {
                $result['content'] = $LinkkeywordModel->handle_content($result['content']);
            }
        }

        $this->success('success', null,$result);
    }

    //获取第三方上传的域名
    public function get_third_domain()
    {
        $weappList = \think\Db::name('weapp')->field('code,data,status,config')->where([
            'status'    => 1,
        ])->cache(true, EYOUCMS_CACHE_TIME, 'weapp')
            ->getAllWithIndex('code');
        $third_domain = '';
        if (!empty($weappList['Qiniuyun']) && 1 == $weappList['Qiniuyun']['status']) {
            // 七牛云
            $qnyData = json_decode($weappList['Qiniuyun']['data'], true);
            $third_domain = $qnyData['domain'];
        } else if (!empty($weappList['AliyunOss']) && 1 == $weappList['AliyunOss']['status']) {
            // 到OSS
            $ossData = json_decode($weappList['AliyunOss']['data'], true);
            $third_domain = $ossData['domain'];
        } else if (!empty($weappList['Cos']) && 1 == $weappList['Cos']['status']) {
            // 同步图片到COS
            $CosData = json_decode($weappList['Cos']['data'], true);
            $third_domain = $CosData['domain'];
        }
        $this->success('success', null,$third_domain);
    }

    /**
     * 获取表单数据信息
     */
    public function form_submit(){
        $form_id = input('post.form_id/d');
        if (IS_POST && !empty($form_id))
        {
            $post = input('post.');
            $ip = clientIP();

            /*提交间隔限制*/
            $channel_guestbook_interval = tpSetting('channel_guestbook.channel_guestbook_interval');
            $channel_guestbook_interval = is_numeric($channel_guestbook_interval) ? intval($channel_guestbook_interval) : 60;
            if (0 < $channel_guestbook_interval) {
                $map   = array(
                    'ip'       => $ip,
                    'form_id'   => $form_id,
                    'lang'     => $this->home_lang,
                    'add_time' => array('gt', getTime() - $channel_guestbook_interval),
                );
                $count = Db::name('form_list')->where($map)->count('list_id');
                if ($count > 0) {
                    if ($this->home_lang == 'cn') {
                        $msg = '同一个IP在'.$channel_guestbook_interval.'秒之内不能重复提交！';
                    } else if ($this->home_lang == 'zh') {
                        $msg = '同一個IP在'.$channel_guestbook_interval.'秒之內不能重複提交！';
                    } else {
                        $msg = 'The same IP cannot be submitted repeatedly within '.$channel_guestbook_interval.' seconds!';
                    }
                    $this->error($msg);
                }
            }
            /*end*/

            $come_url = input('post.come_url/s');
            $parent_come_url = input('post.parent_come_url/s');
            $come_url = !empty($parent_come_url)? $parent_come_url :$come_url;
            $come_from = input('post.come_from/s');
            $city = "";
            $newData = array(
                'form_id'    => $form_id,
                'ip'    => $ip,
                'aid'    => !empty($post['aid']) ? $post['aid'] : 0,
                'come_from' => $come_from,
                'come_url' => $come_url,
                'city' => $city,
                'lang'     => $this->home_lang,
                'add_time'  => getTime(),
                'update_time' => getTime(),
            );
            $data = array_merge($post, $newData);
            // 数据验证
            $token = '__token__';
            foreach ($post as $key => $val) {
                if (preg_match('/^__token__/i', $key)) {
                    $token = $key;
                    break;
                }
            }
            $rule = [
                'form_id'    => 'require|token:'.$token,
            ];
            $message = [
                'form_id.require' => '表单缺少标签属性{$field.hidden}',
            ];
            $validate = new \think\Validate($rule, $message);
            if(!$validate->batch()->check($data))
            {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $this->error($error_msg[0]);
            } else {
                $formlistRow = [];
                /*处理是否重复表单数据的提交*/
                $formdata = $data;
                foreach ($formdata as $key => $val) {
                    if (in_array($key, ['form_id','ip','aid']) || preg_match('/^field_(\d+)$/i', $key)) {
                        continue;
                    }
                    unset($formdata[$key]);
                }
                $md5data = md5(serialize($formdata));
                $data['md5data'] = $md5data;
                $formlistRow = Db::name('form_list')->field('list_id')->where(['md5data'=>$md5data])->find();
                /*--end*/
                if (empty($formlistRow)) { // 非重复表单的才能写入数据库
                    $list_id = Db::name('form_list')->insertGetId($data);
                    if ($list_id > 0) {
                        $this->saveFormValue($list_id, $form_id,$post);
                    }
                } else {
                    // 存在重复数据的表单，将在后台显示在最前面
                    $list_id = $formlistRow['list_id'];
                    Db::name('form_list')->where('list_id',$list_id)->update([
                        'is_read'   => 0,
                        'add_time'   => getTime(),
                        'update_time'   => getTime(),
                    ]);
                }

                if ($this->home_lang == 'cn') {
                    $msg = '操作成功';
                } else if ($this->home_lang == 'zh') {
                    $msg = '操作成功';
                } else {
                    $msg = 'success';
                }
                $this->success($msg, null, ['form_id'=>$form_id,'list_id'=>$list_id]);
            }
        }

        $this->error('表单缺少标签属性{$field.hidden}');
    }

    /**
     *  给指定报名信息添加表单值到 form_value
     * @param int $list_id  报名id
     * @param int $form_id  表单id
     */
    private function saveFormValue($list_id, $form_id,$post)
    {
        /*上传图片或附件*/
        $arr = explode(',', config('global.image_ext'));
        foreach ($_FILES as $fileElementId => $file) {
            try {
                if (!empty($file['name']) && !is_array($file['name'])) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (in_array($ext,$arr)){
                        $uplaod_data = func_common($fileElementId, 'allimg');
                    }else{
                        $uplaod_data = func_common_doc($fileElementId, 'files');
                    }
                    if (0 == $uplaod_data['errcode']) {
                        $post[$fileElementId] = $uplaod_data['img_url'];
                    } else {
//                                return $uplaod_data['errmsg'];
                        $post[$fileElementId] = '';
                    }
                }
            } catch (\Exception $e) {}
        }
        /*end*/
        $notify_content_arr = []; // 添加站内信所需参数
        $send_content_str = "";   //发送短信内容
        $field_list = Db::name("form_field")->where(['form_id'=>$form_id])->getField("field_id,field_name,field_type");
        // post 提交的属性  以 field_id _ 和值的 组合为键名
        foreach($post as $key => $val)
        {
            if(!strstr($key, 'field_'))
                continue;
            $val = addslashes(htmlspecialchars(strip_tags($val)));
            $field_id = str_replace('field_', '', $key);
            $field_id = intval($field_id);
            is_array($val) && $val = implode(',', $val);
            $val = trim($val);
            $field_value = stripslashes(filter_line_return($val, '。'));
            $adddata = array(
                'form_id'   => $form_id,
                'list_id'   => $list_id,
                'field_id'   => $field_id,
                'field_value'   => $field_value,
                'add_time'   => getTime(),
                'update_time'   => getTime(),
            );
            Db::name('form_value')->add($adddata);
            $field_value = get_form_read_value($field_value,$field_list[$field_id]['field_type'],true);
            array_push($notify_content_arr, $field_value);  //添加站内信数据
            $send_content_str .= $field_list[$field_id]['field_name'] . '：' . $field_value.'<br/>';

        }
        /*发送站内信给后台*/
        SendNotifyMessage($notify_content_arr, 1, 1, 0);
        /* END */
        /* 发送短信 */
        $is_open = Db::name('smtp_tpl')->where(['send_scene'=>1,'lang'=>$this->home_lang])->value('is_open');
        $smtp_config = tpCache('smtp');
        if (empty($is_open) || empty($smtp_config['smtp_user']) || empty($smtp_config['smtp_pwd'])) {
            return false;
        }
        $send_email_scene = config('send_email_scene');
        $scene = $send_email_scene[1]['scene'];
        $web_name = tpCache('web.web_name');    //title
        $web_name .= "-表单消息";
        $html = "<p style='text-align: left;'>{$web_name}</p><p style='text-align: left;'>{$send_content_str}</p>";
        if (isMobile()) {
            $html .= "<p style='text-align: left;'>——来源：移动端</p>";
        } else {
            $html .= "<p style='text-align: left;'>——来源：电脑端</p>";
        }
        // 发送邮件
        $res = send_email(null,null,$html, $scene);
        /* END */
        return $res;
    }
    //下载付费
    public function get_download($aid=0)
    {
        if (empty($aid)){
            $this->error('缺少文档id');
        }
        $artData = Db::name('archives')
            ->where('aid',$aid)
            ->find();
        $users_id = session('users_id');

        $canDownload = 0;
        $buyVip = 0;
        $msg = '';
        if (empty($artData['restric_type'])) { // 免费
            $canDownload = 1;
        } else if (1 == $artData['restric_type']) { // 付费
            // 查询是否已购买
            $where = [
                'order_status' => 1,
                'product_id' => intval($aid),
                'users_id' => $users_id
            ];
            $count = Db::name('download_order')->where($where)->count();
            if (!empty($count)){
                $canDownload = 1;
            }
        }else if (2 == $artData['restric_type']) { // 会员专享
            $level = Db::name('users')->where('users_id',$users_id)->value('level');
            if ($level >= $artData['arc_level_id']){
                $canDownload = 1;
            }else{
                if (0 == $artData['no_vip_pay']){
                    $buyVip = 1;
                }else{
                    $where = [
                        'order_status' => 1,
                        'product_id' => intval($aid),
                        'users_id' => $users_id
                    ];
                    $count = Db::name('download_order')->where($where)->count();
                    if (!empty($count)){
                        $canDownload = 1;
                    }
                }
            }
        }else if (3 == $artData['restric_type']) { // 会员付费
            $level = Db::name('users')->where('users_id',$users_id)->value('level');
            if ($level >= $artData['arc_level_id']){
                // 查询是否已购买
                $where = [
                    'order_status' => 1,
                    'product_id' => intval($aid),
                    'users_id' => $users_id
                ];
                $count = Db::name('download_order')->where($where)->count();
                if (!empty($count)){
                    $canDownload = 1;
                }
            }else{
                $buyVip = 1;
            }
        }
        $result['canDownload'] = $canDownload;

        if (1 == $buyVip){
            $result['onclick'] = 'BuyVipClick();';
        }else{
            if (isMobile()) {
                $result['onclick'] = 'ey_download_1655866225(' . $aid . ');';//第一种跳转页面支付
            } else {
                $result['onclick'] = 'DownloadBuyNow1655866225(' . $aid . ');';//第二种弹框页支付
            }
        }


        $this->success('success', null,$result);
    }

}