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

namespace app\api\controller\v1;

use think\Db;

class Api extends Base
{
    /**
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 首页
     */
    public function index()
    {
        $data = $this->apiLogic->taglibData();
        $this->renderSuccess($data);
    }

    /**
     * 分类页面
     * @return [type]          [description]
     */
    public function category()
    {
        $data = [];
        $show_type = input('param.show_type/d'); // 模板风格类型
        if (35 == $show_type) {
            // 商品分类列表
            // $result = model('v1.Category')->getProductCategory();
            // $data['list'] = !empty($result['list']) ? array_values($result['list']) : [];
            // $data['arclist'] = !empty($result['arclist']) ? array_values($result['arclist']): [];
        } else {
            $data = $this->apiLogic->taglibData();
            $data['channel'][0]['data'] = array_values($data['channel'][0]['data']);
        }

        $this->renderSuccess($data);
    }

    // 查询商品信息
    public function get_product_data()
    {
        if (IS_AJAX_POST) {
            $typeid = input('post.typeid/d');
            if (empty($typeid)) $this->error('数据异常');
            $ArchivesData = model('v1.Category')->GetProductData($typeid);
            $this->success('查询成功', null, $ArchivesData);
        }
    }

    /**
     * 文档列表
     * @param  string  $typeid 栏目ID
     * @return array          返回值
     */
    public function archivesList($typeid = '')
    {
        $data = $this->apiLogic->taglibData();
        $this->renderSuccess($data);
    }

    /**
     * 文档详情页
     * @param  string  $aid 文档ID
     * @param  string  $typeid 分类ID
     * @return array          返回值
     */
    public function archivesView($aid = '', $typeid = '')
    {
        $aid = intval($aid);
        $typeid = intval($typeid);

        if (empty($aid) && !empty($typeid)) { // 单页栏目详情页
            $data = $this->apiLogic->taglibData();
            $this->renderSuccess($data);
        }
        else { // 普通文档详情
            $users = $this->getUser(false);
            $view = model('v1.Api')->getArchivesView($aid, $users);
            $data = $this->apiLogic->taglibData($users);
            $data = array_merge($view, $data);
            $this->renderSuccess($data);
        }
    }

    /**
     * 联系我们
     * @param  string  $aid 文档ID
     * @return array          返回值
     */
    public function contact()
    {
        $data = model('v1.Api')->getContact();

        $this->renderSuccess($data);
    }

    /**
     * 留言栏目
     */
    public function guestbook_form()
    {
        $data = $this->apiLogic->taglibData();
        $this->renderSuccess($data);
    }

    /**
     * 发送邮箱
     * @return array          返回值
     */
    public function sendemail()
    {
        // 超时后，断掉邮件发送
        function_exists('set_time_limit') && set_time_limit(10);

        $type = input('param.type/s');
        
        // 留言发送邮件
        if (IS_POST && 'gbook_submit' == $type) {
            $typeid = input('param.typeid/d');
            $aid = input('param.aid/d');

            $send_email_scene = config('send_email_scene');
            $scene = $send_email_scene[1]['scene'];

            $web_name = tpCache('web.web_name');
            // 判断标题拼接
            $arctype  = M('arctype')->field('typename')->find($typeid);
            $web_name = $arctype['typename'].'-'.$web_name;

            // 拼装发送的字符串内容
            $row = M('guestbook_attribute')->field('a.attr_name, b.attr_value')
                ->alias('a')
                ->join('__GUESTBOOK_ATTR__ b', 'a.attr_id = b.attr_id AND a.typeid = '.$typeid, 'LEFT')
                ->where([
                    'b.aid' => $aid,
                ])
                ->order('a.attr_id sac')
                ->select();
            $content = '';
            foreach ($row as $key => $val) {
                if(10 == $val['attr_input_type']){
                    $val['attr_value'] = date('Y-m-d H:i:s',$val['attr_value']);
                }if (preg_match('/(\.(jpg|gif|png|bmp|jpeg|ico|webp))$/i', $val['attr_value'])) {
                    if (!stristr($val['attr_value'], '|')) {
                        $val['attr_value'] = get_absolute_url(handle_subdir_pic($val['attr_value']));
                        $val['attr_value'] = "<a href='".$val['attr_value']."' target='_blank'><img src='".$val['attr_value']."' width='150' height='150' /></a>";
                    }
                } else {
                    $val['attr_value'] = str_replace(PHP_EOL, ' | ', $val['attr_value']);
                }
                $content .= $val['attr_name'] . '：' . $val['attr_value'].'<br/>';
            }
            $html = "<p style='text-align: left;'>{$web_name}</p><p style='text-align: left;'>{$content}</p>";
            if (isWeixinApplets()) {
                $html .= "<p style='text-align: left;'>——来源：小程序端</p>";
            } else if (isMobile()) {
                $html .= "<p style='text-align: left;'>——来源：移动端</p>";
            } else {
                $html .= "<p style='text-align: left;'>——来源：电脑端</p>";
            }
            
            // 发送邮件
            $res = send_email(null,null,$html, $scene);
            if (intval($res['code']) == 1) {
                $this->renderSuccess($res);
            } else {
                $this->error($res['msg']);
            }
        }
    }

    /**
     * 用户自动登录
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function users_login()
    {
        if (empty($this->globalConfig['web_users_switch'])) {
            $this->error('后台会员中心尚未开启！');
        }

        $userModel = model('v1.User');
        return $this->renderSuccess([
            'users_id' => $userModel->login(input('post.', null, 'htmlspecialchars_decode')),
            'token' => $userModel->getToken()
        ]);
    }

    /**
     * 获取当前用户信息
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function users_detail()
    {
        if (empty($this->globalConfig['web_users_switch'])) {
            $this->error('后台会员中心尚未开启！');
        }
        
        // 当前用户信息
        $users = $this->getUser(false);
        // 订单总数
        $userModel = model('v1.User');

        $data = [
            'userInfo' => $users,
        ];

        // 开启商城中心
        if (!empty($this->usersConfig['shop_open'])) {
            $shopModel = model('v1.Shop');
            $data['orderCount'] = [
                'payment' => $shopModel->getOrderCount($users, 'payment'),
                'delivery' => $shopModel->getOrderCount($users, 'delivery'),
                'received' => $shopModel->getOrderCount($users, 'received'),
            ];
            $data['coupon'] = model('v1.api')->getCouponCount($users); // 优惠券数量
            $data['product'] = model('v1.api')->getRecomProduct(); // 可能你还想要
        }

        return $this->renderSuccess($data);
    }

    /**
     * 微信支付成功异步通知
     * @throws BaseException
     * @throws \Exception
     * @throws \think\exception\DbException
     */
    public function wxpay_notify()
    {
//        $xml = <<<EOF
// <xml><appid><![CDATA[wx6dc881d413bd27aa]]></appid>
// <attach><![CDATA[微信小程序支付]]></attach>
// <bank_type><![CDATA[OTHERS]]></bank_type>
// <cash_fee><![CDATA[1]]></cash_fee>
// <fee_type><![CDATA[CNY]]></fee_type>
// <is_subscribe><![CDATA[N]]></is_subscribe>
// <mch_id><![CDATA[1526990671]]></mch_id>
// <nonce_str><![CDATA[db2e38d9f12888b6fd9cfca568aaf62c]]></nonce_str>
// <openid><![CDATA[owG764hKVOfazX5RhDbCnrIi6vZ4]]></openid>
// <out_trade_no><![CDATA[20200512158927230349]]></out_trade_no>
// <result_code><![CDATA[SUCCESS]]></result_code>
// <return_code><![CDATA[SUCCESS]]></return_code>
// <sign><![CDATA[203791C929AFB06E65FA1A477C25B843]]></sign>
// <time_end><![CDATA[20200512163158]]></time_end>
// <total_fee>1</total_fee>
// <trade_type><![CDATA[JSAPI]]></trade_type>
// <transaction_id><![CDATA[4200000560202005123278752240]]></transaction_id>
// </xml>
// EOF;

// file_put_contents ( ROOT_PATH."/log.txt", date ( "Y-m-d H:i:s" ) . "  " . var_export(file_get_contents('php://input'),true) . "\r\n", FILE_APPEND );

        $userModel = model('v1.User');

        if (!$xml = file_get_contents('php://input')) {
            $userModel->returnCode(false, 'Not found DATA');
        }
        // 将服务器返回的XML数据转化为数组
        $data = $userModel->fromXml($xml);
        // 订单信息
        $order = Db::name("shop_order")->where(['order_code' => $data['out_trade_no']])->find();
        empty($order) && $userModel->returnCode(false, '订单不存在');
        // 保存微信服务器返回的签名sign
        $dataSign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        // 生成签名
        $sign = $userModel->makeSign($data);
        // 判断签名是否正确 判断支付状态
        if (
            ($sign !== $dataSign)
            || ($data['return_code'] !== 'SUCCESS')
            || ($data['result_code'] !== 'SUCCESS')
        ) {
            $userModel->returnCode(false, '签名失败');
        }

        // 订单支付成功业务处理
        $openid = Db::name('wx_users')->where(['users_id'=>$order['users_id']])->getField('openid');
        $PostData = [
            'openid' => $openid,
            'users_id' => $order['users_id'],
            'order_id' => $order['order_id'],
            'order_code' => $order['order_code'],
        ];
        $redata = model('v1.Shop')->WechatAppletsPayDealWith($PostData, true);
        if (isset($redata['code']) && empty($redata['code'])) {
            $userModel->returnCode(false, $redata['msg']);
        }
        // 返回状态
        $userModel->returnCode(true, 'OK');
    }

    // 生成商品二维码海报
    public function createGoodsShareQrcodePoster()
    {
        if (IS_AJAX_POST) {
            // 海报模型
            $diyminiproMallPosterModel = model('v1.Poster');

            // 调用接口生成海报
            $post = input('post.');
            $post['aid'] = intval($post['aid']);
            $post['typeid'] = intval($post['typeid']);
            $QrcodePoster = $diyminiproMallPosterModel->GetCreateGoodsShareQrcodePoster($post, 2);
            if (!empty($QrcodePoster) && !empty($QrcodePoster['poster'])) {
                $this->success('海报生成成功', null, $QrcodePoster);
            } else {
                $this->error('生成失败');
            }
        }
    }

    // 生成文章二维码海报
    public function createArticleShareQrcodePoster()
    {
        if (IS_AJAX_POST) {
            // 海报模型
            $diyminiproMallPosterModel = model('v1.Poster');

            // 调用接口生成海报
            $post = input('post.');
            $post['aid'] = intval($post['aid']);
            $post['typeid'] = intval($post['typeid']);
            $QrcodePoster = $diyminiproMallPosterModel->GetCreateGoodsShareQrcodePoster($post, 1);
            if (!empty($QrcodePoster) && !empty($QrcodePoster['poster'])) {
                $this->success('海报生成成功', null, $QrcodePoster);
            } else {
                $this->error('生成失败');
            }
        }
    }

    // 提交文章评论
    public function submitArticleComment()
    {
        if (IS_AJAX) {
            if (!is_dir('./weapp/Comment/')){
                $this->error('请先安装评论插件');
            }
            $param = input('param.');
            if (empty($param['aid'])) $this->error('数据错误，刷新重试');
            if (empty($param['content'])) $this->error('请输入您的评论内容');

            $users = $this->getUser(false);

            // 添加文章评论模型
            $res = model('v1.Api')->addArticleComment($param, $users);
            if (0 < $res['code']) {
                $this->success($res['msg'], null, ['is_show'=>$res['is_show']]);
            } else {
                $this->error($res['msg']);
            }
        }
    }

    
    /**
     * 购物车列表
     */
    public function shop_cart_list()
    {
        if (IS_AJAX) {
            $users = $this->getUser(false);

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 获取商品信息生成订单并支付
            $ShopCart = $ShopModel->ShopCartList($users['users_id'], $users['level_discount']);

            $this->renderSuccess($ShopCart);
        }
    }

    /**
     * 上传评论图片
     * @return array
     */
    public function uploads()
    {
        if (IS_AJAX_POST) {
            $file_type = input('param.file_type/s',"");
            $data = func_common('file', 'minicode',$file_type);
            $is_absolute = input('param.is_absolute/d',0);
            if ($is_absolute && !empty($data['img_url'])){
                $data['img_url'] = get_absolute_url($data['img_url'],'default',true);
            }
            $this->success('上传成功！','',$data);
        }

        $this->error('非法上传！');
    }

    /**
     * 获取评论列表
     */
    public function get_goods_comment_list()
    {
        if (IS_AJAX) {
            $param = input('param.');
            // 获取商品信息生成订单并支付
            $commentList = model('v1.Api')->getGoodsCommentList($param);
            $this->success('success','',$commentList);

//            $this->renderSuccess($commentList);
        }
    }

    /**
     * 获取秒杀列表
     */
    public function get_sharp_index()
    {
        // 商城模型
        $ShopModel = model('v1.Shop');

        // 获取秒杀tabbar
        $tabbar = $ShopModel->GetSharpTabbar();
        $SharpList = [];
        if (!empty($tabbar)){
            // 获取秒杀列表
            $SharpList = $ShopModel->GetSharpIndex($tabbar[0]['active_time_id']);
        }
        $this->renderSuccess(['goodsList'=>$SharpList,'tab'=>$tabbar]);
    }

    /**
     * 获取秒杀商品列表
     */
    public function get_sharp_goods_index($active_time_id = '', $page = 1)
    {
        // 商城模型
        $DiyminiproModel = model('v1.Shop');
        // 获取秒杀商品分页列表
        $SharpList = $DiyminiproModel->GetSharpIndex($active_time_id,$page);

        $this->renderSuccess(['goodsList'=>$SharpList]);
    }
    /**
     * 获取秒杀商品详情
     */
    public function get_sharp_goods($aid=0,$active_time_id=0)
    {
        // 文档详情
        $data = model('v1.Api')->GetSharpGoods($aid);
        $data['detail']['active_time_id'] = $active_time_id;
        // 商城模型
        $ShopModel = model('v1.User');
        // 获取秒杀商品活动场次信息
        $data['active'] = $ShopModel->GetSharp($active_time_id,$aid);

        $this->renderSuccess($data);
    }

    //上传头像
     public function upload_head_pic()
     {
         if (IS_AJAX_POST) {
             $data = func_common('file', 'minicode');
             if (0 == $data['errcode'] && !empty($data['img_url'])){
                 $data['url'] = $data['img_url'];
                 $data['img_url'] = handle_subdir_pic($data['img_url'],'img',true);
             }
             $this->success('上传成功！','',$data);
         }
         $this->error('非法上传！');
     }

     //获取购物车数量
    public function get_cart_total_num()
    {
        $data['cart_total_num'] = model('v1.Shop')->getCartTotalNum();
        $this->renderSuccess($data);
    }

    /**
     * 获取限时折扣列表
     */
    public function get_discount_index()
    {
        $param = input('param.');
        if (empty($param['active_id'])){
            $this->error('缺少必要参数！');
        }
        // 商城模型
        $ShopModel = model('v1.Shop');

        $DiscountGoodsList = $ShopModel->GetDiscountIndex($param);

        $this->renderSuccess(['goodsList'=>$DiscountGoodsList]);
    }
    /**
     * 获取限时折扣商品详情
     */
    public function get_discount_goods($aid=0,$active_id=0)
    {
        // 文档详情
        $data = model('v1.Api')->GetDiscountGoods($aid);
        $data['detail']['active_id'] = $active_id;
        // 商城模型
        $ShopModel = model('v1.Shop');
        // 获取秒杀商品活动场次信息
        $data['active'] = $ShopModel->GetDiscount($active_id);

        $this->renderSuccess($data);
    }

    /**
     * 添加我的浏览足迹
     */
    public function set_footprint()
    {
        $aid = input('param.aid/d');
        $users = $this->getUser(false);
        if (empty($users['users_id']) || empty($aid)) {
            $this->success('不达到记录的条件');
        }

        $users_id = intval($users['users_id']);
        //查询标题模型缩略图信息
        $arc = Db::name('archives')
            ->field('aid,channel,typeid,title,litpic')
            ->find($aid);
        if (!empty($arc)) {
            $count = Db::name('users_footprint')->where([
                'users_id' => $users_id,
                'aid'      => $aid,
            ])->count();

            if (empty($count)) {
                // 足迹记录条数限制
                $user_footprint_limit = config('global.user_footprint_limit');
                if (!$user_footprint_limit) {
                    $user_footprint_limit = 100;
                    config('global.user_footprint_limit',$user_footprint_limit);
                }
                $user_footprint_record = Db::name('users_footprint')->where(['users_id'=>$users_id])->count("id");
                if ($user_footprint_record == $user_footprint_limit) {
                    Db::name('users_footprint')->where(['users_id' => $users_id])->order("update_time ASC")->limit(1)->delete();
                }elseif ($user_footprint_record > $user_footprint_limit) {
                    $del_count = $user_footprint_record-$user_footprint_limit+1;
                    $del_ids = Db::name('users_footprint')->field("id")->where(['users_id' => $this->users_id])->order("update_time ASC")->limit($del_count)->select();
                    $del_ids = get_arr_column($del_ids,'id');
                    Db::name('users_footprint')->where(['id' => ['IN',$del_ids]])->delete();
                }

                $arc['users_id']    = $users_id;
                $arc['lang']        = $this->home_lang;
                $arc['add_time']    = getTime();
                $arc['update_time'] = getTime();
                Db::name('users_footprint')->add($arc);
            } else {
                Db::name('users_footprint')->where([
                    'users_id' => $users_id,
                    'aid'      => $aid
                ])->update([
                    'update_time' => getTime(),
                ]);
            }
            $this->success('保存成功');
        }
    }
    /**
     * 留言栏目数据提交
     */
    public function guestbook($typeid = '')
    {
        $param = input('param.');
        if (IS_POST && !isset($param['apiGuestbookform'])) {
            $post = input('post.');
            $typeid = !empty($post['typeid']) ? intval($post['typeid']) : $typeid;
            if (empty($typeid)) {
                $this->error('post接口缺少typeid的参数与值！');
            }

            /*留言间隔限制*/
            $channel_guestbook_interval = tpSetting('channel_guestbook.channel_guestbook_interval');
            $channel_guestbook_interval = is_numeric($channel_guestbook_interval) ? intval($channel_guestbook_interval) : 60;
            if (0 < $channel_guestbook_interval) {
                $map = array(
                    'ip'    => clientIP(),
                    'typeid'    => $typeid,
                    'add_time'  => array('gt', getTime() - $channel_guestbook_interval),
                );
                $count = Db::name('guestbook')->where($map)->count('aid');
                if (!empty($count)) {
                    $this->error("同一个IP在{$channel_guestbook_interval}秒之内不能重复提交！");
                }
            }
            /*end*/

            // 提取表单令牌的token变量名
            $token = '__token__';
            foreach ($post as $key => $val) {
                if (preg_match('/^__token__/i', $key)) {
                    $token = $key;
                    continue;
                }
            }

            //判断必填项
            foreach ($post as $key => $value) {
                if (stripos($key, "attr_") !== false) {
                    //处理得到自定义属性id
                    $attr_id = substr($key, 5);
                    $attr_id = intval($attr_id);
                    $ga_data = Db::name('guestbook_attribute')->where([
                        'attr_id'   => $attr_id,
                    ])->find();
                    if ($ga_data['required'] == 1 && empty($value)) {
                        $this->error($ga_data['attr_name'] . '不能为空！');
                    }

                    if ($ga_data['validate_type'] == 6 && !empty($value)) {
                        $pattern  = "/^1\d{10}$/";
                        if (!preg_match($pattern, $value)) {
                            $this->error($ga_data['attr_name'] . '格式不正确！');
                        }
                    } elseif ($ga_data['validate_type'] == 7 && !empty($value)) {
                        $pattern  = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                        if (preg_match($pattern, $value) == false) {
                            $this->error($ga_data['attr_name'] . '格式不正确！');
                        }
                    }
                }
            }

            $newData = array(
                'typeid'    => $typeid,
                'users_id'  => $this->users_id,
                'channel'   => 8,
                'ip'    => clientIP(),
                'lang'  => get_main_lang(),
                'add_time'  => getTime(),
                'update_time' => getTime(),
            );
            $data    = array_merge($post, $newData);

            /*表单令牌*/
            $token_value = !empty($data[$token]) ? $data[$token] : '';
            $session_path = \think\Config::get('session.path');
            $session_file = ROOT_PATH . $session_path . "/sess_".str_replace('__token__', '', $token);
            $filesize = @filesize($session_file);
            if(file_exists($session_file) && !empty($filesize)) {
                $fp = fopen($session_file, 'r');
                $token_v = fread($fp, $filesize);
                fclose($fp);
                if ($token_v != $token_value) {
                    $this->error('表单令牌无效！');
                }
            } else {
                $this->error('表单令牌无效！');
            }
            /*end*/

            $guestbookRow = [];
            /*处理是否重复表单数据的提交*/
            $formdata = $data;
            foreach ($formdata as $key => $val) {
                if (in_array($key, ['typeid', 'lang']) || preg_match('/^attr_(\d+)$/i', $key)) {
                    continue;
                }
                unset($formdata[$key]);
            }
            $md5data         = md5(serialize($formdata));
            $data['md5data'] = $md5data;
            $guestbookRow    = M('guestbook')->field('aid')->where(['md5data' => $md5data])->find();
            /*--end*/

            $aid = !empty($guestbookRow['aid']) ? $guestbookRow['aid'] : 0;
            if (empty($guestbookRow)) { // 非重复表单的才能写入数据库
                $aid = M('guestbook')->insertGetId($data);
                if ($aid > 0) {
                    $res = model('v1.Api')->saveGuestbookAttr($post, $aid, $typeid);
                    if ($res){
                        $this->error($res);
                    }
                }
            } else {
                // 存在重复数据的表单，将在后台显示在最前面
                Db::name('guestbook')->where('aid', $aid)->update([
                    'add_time' => getTime(),
                    'update_time' => getTime(),
                ]);
            }
            @unlink($session_file);
            $this->renderSuccess(['aid'=>$aid], '提交成功');
        }
        $this->error('请求错误！');
    }
    /**
     * 获取下级地区
     */
    public function get_region()
    {
        if (IS_AJAX) {
            $pid  = input('pid/d', 0);
            $res = Db::name('region')->where('parent_id',$pid)->select();
            if (!empty($res)){
                array_unshift($res,['id'=>'','name'=>'请选择']);
            }
//            $this->renderSuccess($res);
            $this->success('请求成功', null, $res);
        }
    }
}
