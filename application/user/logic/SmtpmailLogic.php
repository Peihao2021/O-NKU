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

namespace app\user\logic;

use think\Model;
use think\Db;
use think\Request;
use think\Config;

/**
 * 邮箱逻辑定义
 * Class CatsLogic
 * @package user\Logic
 */
class SmtpmailLogic extends Model
{
    private $home_lang = 'cn';

    /**
     * 初始化操作
     */
    public function initialize() {
        parent::initialize();
        $this->home_lang = get_home_lang();
    }

    /**
     * 发送邮件
     */
    public function send_email($email = '', $title = '', $type = 'reg', $scene = 2, $data = [])
    {
        // 是否传入邮箱地址
        if (empty($email)) {
            return ['code'=>0, 'msg'=>"邮箱地址参数不能为空！"];
        } else if (!check_email($email)) {
            return ['code'=>0, 'msg'=>"邮箱格式不正确！"];
        }

        // 查询扩展是否开启
        $openssl_funcs = get_extension_funcs('openssl');
        if (!$openssl_funcs) {
            return ['code'=>0, 'msg'=>"请联系空间商，开启php的 <font color='red'>openssl</font> 扩展！"];
        }

        // 是否填写邮件配置
        $smtp_config = tpCache('smtp');
        if (empty($smtp_config['smtp_user']) || empty($smtp_config['smtp_pwd'])) {
            return ['code'=>0, 'msg'=>"该功能待开放，网站管理员尚未完善邮件配置！"];
        }

        // 邮件使用场景
        $scene = intval($scene);
        $send_email_scene = config('send_email_scene');
        $send_scene = $send_email_scene[$scene]['scene'];

        // 获取邮件模板
        $emailtemp = M('smtp_tpl')->where(['send_scene' => $send_scene, 'lang' => $this->home_lang])->find();

        // 是否启用邮件模板
        if (empty($emailtemp) || empty($emailtemp['is_open'])) {
            return ['code'=>0, 'msg'=>"该功能待开放，网站管理员尚未启用(<font color='red'>{$emailtemp['tpl_name']}</font>)邮件模板"];
        }

        // 会员ID
        $users_id = session('users_id');

        // 发送邮件操作分发
        if ('retrieve_password' == $type) {
            // 找回密码，判断邮箱是否存在
            $where = array(
                'info'     => array('eq',$email),
                'lang'     => array('eq',$this->home_lang),
            );
            $users_list = M('users_list')->where($where)->field('info')->find();

            // 判断会员是否已绑定邮箱
            $userswhere = array(
                'email'    => array('eq',$email),
                'lang'     => array('eq',$this->home_lang),
            );
            $usersdata = M('users')->where($userswhere)->field('is_email,is_activation')->find();
            if (!empty($usersdata)) {
                if (empty($usersdata['is_activation'])) {
                    return ['code'=>0, 'msg'=>'该会员尚未激活，不能找回密码！'];
                } else if (empty($usersdata['is_email'])) {
                    return ['code'=>0, 'msg'=>'邮箱地址未绑定，不能找回密码！'];
                }
            }

            if (!empty($users_list)) {
                // // 判断是否已发送过验证链接，链接一小时内有效
                // $where_ = array(
                //     'email'     => array('eq',$email),
                //     'status'    => array('eq',0),
                //     'lang'      => $this->home_lang,
                // );
                // $isrecord = M('smtp_record')
                //         ->where($where_)
                //         ->field('record_id,add_time')
                //         ->order('add_time desc')
                //         ->find();
                $time = getTime();

                // // 邮箱验证码有效期
                // if (!empty($isrecord) && ($time - $isrecord['add_time']) < Config::get('global.email_default_time_out')) {
                //     return ['code'=>1, 'msg'=>'验证码已发送至邮箱：'.$email.'，请登录邮箱查看验证码！'];
                // }

                // 数据添加
                $datas['source']   = 4; // 来源，与场景ID对应：4=找回密码
                $datas['email']    = $email;
                $datas['code']     = rand(1000,9999);
                $datas['lang']     = $this->home_lang;
                $datas['add_time'] = $time;
                M('smtp_record')->add($datas);
            } else {
                return ['code'=>0, 'msg'=>'邮箱地址不存在！'];
            }

        } else if ('bind_email' == $type) {
            // 邮箱绑定，判断邮箱是否已存在
            $listwhere = array(
                'info'     => array('eq',$email),
                'users_id' => array('neq',$users_id),
                'lang'     => array('eq',$this->home_lang),
            );
            $users_list = M('users_list')->where($listwhere)->field('info')->find();

            // 判断会员是否已绑定相同邮箱
            $userswhere = array(
                'users_id' => array('eq',$users_id),
                'email'    => array('eq',$email),
                'is_email' => 1,
                'lang'     => array('eq',$this->home_lang),
            );
            $usersdata = M('users')->where($userswhere)->field('is_email')->find();
            if (!empty($usersdata['is_email'])) {
                return ['code'=>0, 'msg'=>'邮箱已绑定，无需重新绑定！'];
            }

            // 邮箱数据处理
            if (empty($users_list)) {
                // // 判断是否已发送过验证链接，链接一小时内有效
                // $where_ = array(
                //     'email'     => array('eq',$email),
                //     'users_id'  => array('eq',$users_id),
                //     'status'    => array('eq',0),
                //     'lang'      => $this->home_lang,
                // );
                // $isrecord = M('smtp_record')
                //         ->where($where_)
                //         ->field('record_id,add_time')
                //         ->order('add_time desc')
                //         ->find();
                $time = getTime();

                // // 邮箱验证码有效期
                // if (!empty($isrecord) && ($time - $isrecord['add_time']) < Config::get('global.email_default_time_out')) {
                //     return ['code'=>1, 'msg'=>'验证码已发送至邮箱：'.$email.'，请登录邮箱查看验证码！'];
                // }

                // 数据添加
                $datas['source']   = 3; // 来源，与场景ID对应：3=绑定邮箱
                $datas['email']    = $email;
                $datas['users_id'] = $users_id;
                $datas['code']     = rand(1000,9999);
                $datas['lang']     = $this->home_lang;
                $datas['add_time'] = $time;
                M('smtp_record')->add($datas);
            } else {
                return ['code'=>0, 'msg'=>"邮箱已经存在，不可以绑定！"];
            }

        } else if ('reg' == $type) {
            // 注册，判断邮箱是否已存在
            $where = array(
                'info' => array('eq',$email),
                'lang' => array('eq',$this->home_lang),
            );
            $users_list = M('users_list')->where($where)->field('info')->find();

            if (empty($users_list)) {
                // // 判断是否已发送过验证链接，链接一小时内有效
                // $where_ = array(
                //     'email'     => array('eq',$email),
                //     'status'    => array('eq',0),
                //     'lang'      => $this->home_lang,
                // );
                // $isrecord = M('smtp_record')
                //         ->where($where_)
                //         ->field('record_id,add_time')
                //         ->order('add_time desc')
                //         ->find();
                $time = getTime();

                // // 邮箱验证码有效期
                // if (!empty($isrecord) && ($time - $isrecord['add_time']) < Config::get('global.email_default_time_out')) {
                //     return ['code'=>1, 'msg'=>'验证码已发送至邮箱：'.$email.'，请登录邮箱查看验证码！'];
                // }

                // 数据添加
                $datas['source']   = 2; // 来源，与场景ID对应：2=注册
                $datas['email']    = $email;
                $datas['code']     = rand(1000,9999);
                $datas['lang']     = $this->home_lang;
                $datas['add_time'] = $time;
                M('smtp_record')->add($datas);
            } else {
                return ['code'=>0, 'msg'=>'邮箱已存在！'];
            }
            
        } else if ('order_msg' == $type) {
            $content = '订单有新的消息，请登录查看。';
            if (!empty($data)) {
                $PayMethod = '';
                if (!empty($data['pay_method'])) {
                    switch ($data['pay_method']) {
                        case 'balance':
                            $PayMethod = '余额支付';
                            break;
                        case 'delivery_pay':
                            $PayMethod = '货到付款';
                            break;
                        case 'wechat':
                            $PayMethod = '微信';
                            break;
                        case 'alipay':
                            $PayMethod = '支付宝';
                            break;
                        default:
                            $PayMethod = '第三方支付';
                            break;
                    }
                }

                switch ($data['type']) {
                    case '1':
                        $content = '您好，管理员。 会员(' . $data['nickname'] . ')使用'. $PayMethod .'对订单(' . $data['order_code'] . ')支付完成，请登录后台审查并及时发货。';
                        break;
                    case '2':
                        $url = request()->domain() . url('user/Shop/shop_order_details', ['order_id'=>$data['order_id']]);
                        $chayue = '<a href="'. $url .'">查阅</a>';
                        $content = '您好，' . $data['nickname'] . '。 管理员已对订单(' . $data['order_code'] . ')发货完成，请登录会员中心'. $chayue .'。';
                        break;
                }
            }
        }

        // 判断标题拼接
        $title = addslashes($title);
        $web_name = $emailtemp['tpl_name'].'：'.$title.'-'.tpCache('web.web_name');
        $content = !empty($content) ? $content : '感谢您的注册,您的邮箱验证码为: '.$datas['code'];
        $html = "<p style='text-align: left;'>{$web_name}</p><p style='text-align: left;'>{$content}</p>";

        if (isMobile()) {
            $html .= "<p style='text-align: left;'>——来源：移动端</p>";
        } else {
            $html .= "<p style='text-align: left;'>——来源：电脑端</p>";
        }

        // 实例化类库，调用发送邮件
        $res = send_email($email,$emailtemp['tpl_title'],$html, $send_scene);
        if (intval($res['code']) == 1) {
            return ['code'=>1, 'msg'=>$res['msg']];
        } else {
            return ['code'=>0, 'msg'=>$res['msg']];
        }
    }
}
