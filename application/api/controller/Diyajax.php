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

class Diyajax extends Base
{
    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 检验会员登录
     */
    public function check_userinfo()
    {
        if (IS_AJAX) {
            $users_id = (int)session('users_id');
            if (!empty($users_id)) {
                $users = GetUsersLatestData($users_id);
                // 头像处理
                $head_pic = get_head_pic(htmlspecialchars_decode($users['head_pic']), false, $users['sex']);
                $users['head_pic'] = func_preg_replace(['http://thirdqq.qlogo.cn'], ['https://thirdqq.qlogo.cn'], $head_pic);
                // 注册时间转换时间日期格式
                $users['reg_time'] = MyDate('Y-m-d H:i:s', $users['reg_time']);
                // 购物车数量
                $users['cart_num'] = Db::name('shop_cart')->where(['users_id'=>$users_id])->sum('product_num');

                $assignData = [
                    'users' => $users,
                ];
                $this->assign($assignData);

                $filename = './template/'.THEME_STYLE_PATH.'/'.'system/users_info.htm';
                if (file_exists($filename)) {
                    $html = $this->fetch($filename); // 渲染模板标签语法
                } else {
                    $html = '缺少模板文件：'.ltrim($filename, '.');
                }

                $data = [
                    'ey_is_login'   => 1,
                    'html'  => $html,
                ];
            }
            else {
                $data = [
                    'ey_is_login'   => 0,
                    'ey_third_party_login'  => $this->is_third_party_login(),
                    'ey_third_party_qqlogin'  => $this->is_third_party_login('qq'),
                    'ey_third_party_wxlogin'  => $this->is_third_party_login('wx'),
                    'ey_third_party_wblogin'  => $this->is_third_party_login('wb'),
                    'ey_login_vertify'  => $this->is_login_vertify(),
                ];
            }

            // 记录访问足迹
            $aid = input('param.aid/d');
            $ajaxLogic = new \app\api\logic\AjaxLogic;
            $ajaxLogic->footprint_save($aid);

            $this->success('请求成功', null, $data);
        }
        abort(404);
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
}