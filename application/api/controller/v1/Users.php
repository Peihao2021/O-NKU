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
use think\Request;

class Users extends Base
{
    public $users;
    public $users_id;

    /**
     * 初始化操作
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->users    = $this->getUser();   // 用户信息
        $this->users_id = !empty($this->users['users_id']) ? intval($this->users['users_id']) : null;
        if (empty($this->users_id)) $this->error('请先登录');
    }

    /**
     * 我的订单列表
     * @param $dataType
     * @return array
     * @throws \think\exception\DbException
     */
    public function order_lists($dataType)
    {
        $list = model('v1.Shop')->getOrderList($this->users_id, $dataType);
        return $this->renderSuccess(compact('list'));
    }

    //获取留言列表
    public function guestbook_list()
    {
        if (IS_AJAX) {
            $list = model('v1.User')->guestbookList();
            return $this->renderSuccess(compact('list'));
        }
        $this->error('请求错误！');


    }

    //获取我的预约详情
    public function get_book_detail()
    {
        if (IS_AJAX) {
            $param = input('param.');
            $list = model('v1.User')->GetMyBookDetail($param);

            return $this->renderSuccess(compact('list'));
        }
        $this->error('请求错误！');
    }

    //取消预约
    public function cancel_book()
    {
        if (IS_AJAX) {
            $aid = input('param.aid/d',0);
            if (empty($aid)){
                $this->error('缺少参数aid！');
            }
            $r = Db::name('guestbook')
                ->where(['aid'=>$aid,'users_id'=>$this->users_id])
                ->delete();
            if (false !== $r){
                Db::name('guestbook_attr')->where('aid',$aid)->delete();
                $this->success('取消成功！');
            }else{
                $this->error('取消失败！');
            }
        }
        $this->error('请求错误！');
    }


    /**
     * 取消订单
     * @param $order_id
     * @return array
     * @throws \Exception
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_cancel($order_id)
    {
        if (IS_AJAX_POST && !empty($order_id)) {
            model('v1.Shop')->orderCancel($order_id, $this->users_id);
        }
        $this->error('订单取消失败！');
    }

    /**
     * 订单提醒发货
     * @param $order_id
     * @return array
     * @throws \Exception
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_remind($order_id)
    {
        if (IS_AJAX_POST && !empty($order_id)) {
            model('v1.Shop')->orderRemind($order_id, $this->users_id);
        }
        $this->error('订单取消失败！');
    }

    /**
     * 订单详情信息
     * @param $order_id
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_detail($order_id)
    {
        if (IS_AJAX) {
            // 订单详情
            $detail = model('v1.Shop')->getOrderDetail($order_id, $this->users_id);
            return $this->renderSuccess([
                'order'   => $detail,  // 订单详情
                'setting' => [],
            ]);
        }
        $this->error('订单读取失败！');
    }

    /**
     * 订单支付
     * @param int $order_id 订单id
     * @param int $payType 支付方式
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function order_pay($order_id, $payType = 20)
    {
        // 订单支付事件
        $order =  model('v1.Shop')->getOrderDetail($order_id, $this->users_id);
        // 判断订单状态
        if (!isset($order['order_status']) || $order['order_status'] != 0) {
            $this->error('很抱歉，当前订单不合法，无法支付');
        }
        // 构建微信支付请求
        $payment = model('v.Shop')->onOrderPayment($this->users, $order, $payType);
        if (isset($payment['code']) && empty($payment['code'])) {
            $this->error($payment['msg'] ?: '订单支付失败');
        }
        // 支付状态提醒
        $this->renderSuccess([
            'order_id' => $order['order_id'],   // 订单id
            'pay_type' => $payType,             // 支付方式
            'payment'  => $payment               // 微信支付参数
        ], ['success' => '支付成功', 'error' => '订单未支付']);
    }

    /**
     * 获取物流信息
     * @param $order_id
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_express($order_id, $timestamp = '')
    {
        // 订单详情
        $detail = model('v1.Shop')->getOrderDetail($order_id, $this->users_id);
        if (empty($detail['express_order'])) {
            return $this->error('没有物流信息');
        }
        // 获取物流信息
        /* @var \app\store\model\Express $model */
        $express = model('v1.Shop')->orderExpress($detail['express_name'], $detail['express_code'], $detail['express_order'], $timestamp);
        if (!empty($express)) {
            return $this->renderSuccess(compact('express'));
        }
        $this->error('没有找到物流信息！');
    }

    /**
     * 确认收货
     * @param $order_id
     * @return array
     * @throws \Exception
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_receipt($order_id)
    {
        if (IS_AJAX_POST && !empty($order_id)) {
            model('v1.Shop')->orderReceipt($order_id, $this->users_id);
        }
        $this->error('确认收货失败！');
    }


    /* -------------陈风任------------- */
    /**
     * 添加商品到购物车
     */
    public function shop_add_cart()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            // 数量判断
            if (empty($post['product_num']) || 0 > $post['product_num']) $this->error('请输入数量');

            // 默认会员ID
            $post['users_id'] = $this->users_id;

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 商品是否已售罄
            $ShopModel->IsSoldOut($post);

            // 添加购物车
            $ShopModel->ShopAddCart($post);
        }
    }

    /**
     * 添加商品到购物车
     */
    public function shop_page_add_cart()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            // 数量判断
            if (empty($post['product_num']) || 0 > $post['product_num']) $this->error('请输入数量');
            // 默认会员ID
            $post['users_id'] = $this->users_id;

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 添加购物车
            $ShopModel->ShopPageAddCart($post);
        }
    }

    /**
     * 立即购买
     */
    public function shop_buy_now()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            // 数量判断
            if (empty($post['product_num']) || 0 > $post['product_num']) $this->error('请输入数量');

            // 默认会员ID
            $post['users_id'] = $this->users_id;

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 商品是否已售罄
            $ShopModel->IsSoldOut($post);

            // 立即购买
            $ShopModel->ShopBuyNow($post);
        }
    }

    /**
     * 产品购买
     */
    public function shop_product_buy()
    {
        if (IS_AJAX_POST) {
            // 获取解析数据
            $querystr = input('param.querystr/s');
            if (empty($querystr)) $this->error('无效链接！');

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 获取商品信息进行展示
            $ShopModel->GetProductData($querystr, $this->users_id, $this->users['level_discount'], $this->users);
        }
    }

    /**
     * 订单结算
     */
    public function shop_order_pay()
    {
        if (IS_AJAX_POST) {
            $post           = input('post.');
            $post['action'] = !empty($post['action']) ? $post['action'] : 'CreatePay';

            // 默认会员ID
            $post['users_id'] = $this->users_id;
            $post['openid']   = Db::name('wx_users')->where('users_id', $this->users_id)->getField('openid');

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 操作分发
            if ('DirectPay' == $post['action']) {
                // 订单直接支付
                $ShopModel->OrderDirectPay($post);
            } else if ('CreatePay' == $post['action']) {
                // 获取解析数据
                if (empty($post['querystr'])) $this->error('无效链接！');

                // 获取商品信息生成订单并支付
                $ShopModel->ShopOrderPay($post, $this->users['level_discount']);
            }
        }
    }

    /**
     * 订单支付后续处理
     */
    public function shop_order_pay_deal_with()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 默认会员ID
            $post['users_id'] = $this->users_id;
            $post['openid']   = Db::name('wx_users')->where('users_id', $this->users_id)->getField('openid');

            // 商城模型
            $ShopModel = model('v1.Shop');

            // 订单支付后续处理
            $ShopModel->WechatAppletsPayDealWith($post);
        }
    }

    /**
     * 购物车操作(改变数量、选中状态、删除)
     */
    public function shop_cart_action()
    {
        if (IS_AJAX) {
            $param = input('param.');
            if (empty($param['action'])) $param['action'] = null;
            $param['users_id'] = $this->users_id;

            // 商城模型
            $ShopModel = model('v1.Shop');

            /* 购物车操作 */
            if ('add' == $param['action']) {
                // 数量 + 1
                $ShopModel->ShopCartNumAdd($param);
            } else if ('less' == $param['action']) {
                // 数量 - 1
                $ShopModel->ShopCartNumLess($param);
            } else if ('selected' == $param['action']) {
                // 是否选中
                $ShopModel->ShopCartSelected($param);
            } else if ('all_selected' == $param['action']) {
                // 是否全部选中
                $ShopModel->ShopCartAllSelected($param);
            } else if ('del' == $param['action']) {
                // 删除购物车商品
                $ShopModel->ShopCartDelete($param);
            } else {
                $this->error('请正确操作');
            }
            /* END */

        }
    }

    /**
     * 收货地址列表
     */
    public function shop_address_list()
    {
        if (IS_AJAX) {
            // 商城模型
            $ShopModel = model('v1.Shop');

            // 收货地址列表
            $ReturnData = $ShopModel->GetAllAddressList($this->users);
        }
    }

    /**
     * 收货地址操作分发
     */
    public function shop_address_action()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            // 商城模型
            $ShopModel = model('v1.Shop');

            // 操作分发
            if ('find_add' == $post['action']) {
                // 添加单条收货地址
                $ShopModel->FindAddAddr($post, $this->users_id);
            } else if ('find_edit' == $post['action']) {
                // 设置默认收货地址
                $ShopModel->FindEditAddr($post, $this->users_id);
            } else if ('default' == $post['action']) {
                // 设置默认收货地址
                $ShopModel->SetDefaultAddr($post, $this->users_id);
            } else if ('find_detail' == $post['action']) {
                // 获取单条收货地址
                $ShopModel->GetFindAddrDetail($post, $this->users);
            } else if ('find_del' == $post['action']) {
                // 删除单条收货地址
                $ShopModel->FindDelAddr($post, $this->users_id);
            } else {
                $this->error('请正确操作');
            }
        }
    }
    /* -------------END------------- */

    /**
     * 获取评价订单商品列表
     * @param $order_id
     * @return array
     * @throws \Exception
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function order_comment($order_id)
    {
        if (IS_AJAX) {
            $data = model('v1.Shop')->getOrderComment($order_id, $this->users_id);
            return $this->renderSuccess([
                'goods'   => $data,  // 订单详情
            ]);
        }
        $this->error('读取失败！');
    }

    /**
     * 保存评价
     * @return array
     */
    public function save_comment()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            return model('v1.Shop')->getSaveComment($post,$this->users_id);
        }
        $this->error('评价失败！');
    }

    /**
     * 用户领取优惠券
     */
    public function get_coupon($coupon_id)
    {
        if (IS_AJAX) {
            $where = [
                'coupon_id' => $coupon_id,
            ];
            //查询优惠券信息,结束发放时间/库存
            $coupon = Db::name('shop_coupon')->where($where)->find();
            if (!empty($coupon)) {
                if (1 > $coupon['coupon_stock']) {
                    $this->error('优惠券库存不足！');
                }
                if (getTime() > $coupon['end_date']) {
                    $this->error('优惠券发放已结束！');
                }
                $where['users_id']   = $this->users_id;
                $where['use_status'] = 0;
                $where['start_time'] = ['<=',getTime()];
                $where['end_time'] = ['>=',getTime()];

                $count = Db::name('shop_coupon_use')->where($where)->find();

                if (!empty($count)) {
                    $this->error('请勿重复领取！');
                } else {
                    $insert['coupon_id']   = $coupon_id;
                    $insert['coupon_code'] = $coupon['coupon_code'];
                    $insert['users_id']    = $this->users_id;
                    $insert['use_status']  = 0;
                    $insert['get_time']    = getTime();
                    $insert['add_time']    = getTime();
                    $insert['update_time'] = getTime();
                    //根据使用期限不同插入开始/结束使用时间
                    if (1 == $coupon['use_type']) {//固定期限
                        $insert['start_time'] = $coupon['use_start_time'];
                        $insert['end_time']   = $coupon['use_end_time'];
                    } else if (2 == $coupon['use_type']) {//当日开始N天有效
                        $insert['start_time'] = strtotime(date("Y-m-d", time()));
                        $insert['end_time']   = $insert['start_time'] + $coupon['valid_days'] * 86400;
                    } else if (3 == $coupon['use_type']) {//次日开始N天有效
                        $insert['start_time'] = strtotime(date("Y-m-d", time())) + 86400;
                        $insert['end_time']   = $insert['start_time'] + $coupon['valid_days'] * 86400;
                    }
                    if (!empty($insert)) {
                        $use_insert = Db::name('shop_coupon_use')->insert($insert);
                        if (!empty($use_insert)) {
                            //减库存
                            Db::name('shop_coupon')->where('coupon_id', $coupon_id)->setDec('coupon_stock');
                            $this->success('领取成功！');
                        }
                    }
                }
            }
        }
        $this->error('优惠券领取失败！');
    }

    /**
     * 获取我的优惠券
     */
    public function get_my_coupon($dataType)
    {
        $list = model('v1.Shop')->GetMyCouponList($this->users_id, $dataType);

        return $this->renderSuccess(compact('list'));
    }
    /**
     * 领券中心
     */
    public function get_coupon_center()
    {
        $list = model('v1.Shop')->GetCouponCenter($this->users_id);

        return $this->renderSuccess(compact('list'));
    }

    /**
     * 收藏(喜欢)/取消
     * 收藏 type users_collection
     * 喜欢 type users_like
     */
    public function get_collect()
    {
        if (IS_AJAX_POST) {
            $aid = input('param.aid/d');
            $type = input('param.type/s','users_collection');
            $success = '已收藏';
            $cancel = '已取消';
            if ('users_collection' != $type){
                $success = 'success';
            }else{
                $cancel = 'cancel';
            }
            if(empty($aid)){
                $this->error('缺少文档ID！');
            }
            $count = Db::name($type)->where([
                'aid'   => $aid,
                'users_id'  => $this->users_id,
            ])->count();
            if (empty($count)) {
                $addSave = Db::name('archives')->field('aid,channel,typeid,lang,title,litpic')->where('aid',$aid)->find();
                if(empty($addSave)){
                    $this->error('文档不存在！');
                }
                $addSave['lang']        = $this->home_lang;
                $addSave['add_time']  = getTime();
                $addSave['users_id']  = $this->users_id;
                $r = Db::name($type)->insert($addSave);
                if (!empty($r)){
                    if ('users_collection' == $type){
                        $ret_data = ['is_collect'=>1];
                    }else if ('users_like' == $type){
                        $ret_data = ['is_like'=>1];
                    }
                    $this->success($success, null, $ret_data);
                }
            }else{
                $r = Db::name($type)->where(['aid'=>$aid,'users_id'=>$this->users_id])->delete();
                if (!empty($r)){
                    if ('users_collection' == $type){
                        $ret_data = ['is_collect'=>0];
                    }else if ('users_like' == $type){
                        $ret_data = ['is_like'=>0];
                    }
                    $this->success($cancel, null,$ret_data);
                }
            }
        }
        $this->error('请求错误！');
    }

    //获取收藏列表
    public function get_collect_list()
    {
        if (IS_AJAX) {
            $param = input('param.');
            $list = model('v1.User')->GetMyCollectList($param);

            return $this->renderSuccess(compact('list'));
        }
        $this->error('请求错误！');
    }
    //修改头像/昵称/手机号
    public function save_user_info()
    {
        if (IS_AJAX_POST) {
            $head_pic = input('param.head_pic/s');
            $nickname = input('param.nickname/s');
            $mobile = input('param.mobile/s');
            if(!empty($head_pic) || !empty($nickname) || !empty($mobile)){
                $update = ['update_time'=>getTime()];
                if (!empty($head_pic)){
                    $update['head_pic'] = $head_pic;
                }
                if (!empty($nickname)){
                    $update['nickname'] = $nickname;
                }
                if (!empty($mobile)){
                    $is_mobile = check_mobile($mobile);
                    if (!$is_mobile){
                        $this->error('手机号格式不正确!');
                    }
                    $update['mobile'] = $mobile;
                    $update['is_mobile'] = 1;
                }
                $r = Db::name('users')->where(['users_id'=>$this->users_id])->update($update);
                if (!empty($r)){
                    $this->success('保存成功');
                }
            }
            $this->error('保存失败');
        }
    }

    //获取手机号
    public function get_phone()
    {
        if (IS_AJAX_POST) {
            $code = input('param.code/s');
            model('v1.User')->getPhone($code);
        }
    }

    // 处理会员余额方法(集合方法)
    public function HandleUserMoneyAction()
    {
        if (IS_AJAX) {
            // 传入参数
            $param = input('param.');

            // 处理传入的参数
            $param['users_id'] = intval($this->users_id);
            $param['moneyid'] = !empty($param['moneyid']) ? intval($param['moneyid']) : 0;
            $param['usersMoney'] = !empty($param['usersMoney']) ? strval($param['usersMoney']) : 0;
            $param['order_number'] = !empty($param['order_number']) ? strval($param['order_number']) : '';

            $v1ShopModel = model('v1.Shop');
            // 判断执行操作
            $action = !empty($param['action']) ? strval($param['action']) : '';
            if ('details' == $action) {
                $v1ShopModel->getUsersMoneyDetails($this->users, $param);
            } else if ('recharge' == $action) {
                // 会员余额充值
                $v1ShopModel->getUsersMoneyRecharge($param);
            } else if ('rechargePay' == $action) {
                // 会员余额充值后续处理
                $v1ShopModel->getUsersMoneyRechargePay($param);
            }
        }
    }

    // 处理订单服务方法(集合方法)
    public function handleOrderServiceAction()
    {
        if (IS_AJAX) {
            // 传入参数
            $param = input('param.');

            // 处理传入的参数
            $param['users_id'] = intval($this->users_id);
            $param['order_id'] = !empty($param['order_id']) ? intval($param['order_id']) : 0;
            $param['product_id'] = !empty($param['product_id']) ? intval($param['product_id']) : 0;
            $param['details_id'] = !empty($param['details_id']) ? intval($param['details_id']) : 0;
            $param['service_id'] = !empty($param['service_id']) ? intval($param['service_id']) : 0;

            $v1ShopModel = model('v1.Shop');
            // 判断执行操作
            $action = !empty($param['action']) ? strval($param['action']) : '';
            if ('goodsDetect' == $action) {
                // 查询订单内的指定的某个商品，判断是否已申请售后或已评价商品
                $v1ShopModel->getOrderGoodsDetect($param);
            } else if ('getServiceList' == $action) {
                // 查询售后服务单列表
                $v1ShopModel->getOrderGoodsServiceList($param);
            } else if ('getService' == $action) {
                // 查询申请售后的订单商品
                $v1ShopModel->getOrderGoodsService($param);
            } else if ('addService' == $action) {
                $v1ShopModel->addOrderGoodsService($param);
            } else if ('cancelService' == $action) {
                // 取消售后订单会员邮寄物流信息
                $v1ShopModel->cancelOrderGoodsService($param);
            }  else if ('addUsersDelivery' == $action) {
                // 添加售后订单会员邮寄物流信息
                $v1ShopModel->addServiceUsersDelivery($param);
            }
        }
    }

    //获取我的足迹列表
    public function get_footprint_list()
    {
        if (IS_AJAX) {
            $param = input('param.');
            $list = model('v1.User')->GetMyFootprintList($param);

            return $this->renderSuccess(compact('list'));
        }
        $this->error('请求错误！');
    }

    /**
     * 添加转发记录
     */
    public function set_forward()
    {
        if (IS_AJAX) {
            $users_id = $this->users_id;
            $aid = input('param.aid/d');
            if (empty($aid)){
                $this->error('缺少文档ID!');
            }
            //查询标题模型缩略图信息
            $arc = Db::name('archives')
                ->field('aid,channel,typeid,title,litpic')
                ->find($aid);
            if (!empty($arc)) {
                $arc['users_id']    = $users_id;
                $arc['lang']        = $this->home_lang;
                $arc['add_time']    = getTime();
                $arc['update_time'] = getTime();
                Db::name('users_forward')->insert($arc);
                $this->success('保存成功');
            }
        }
        $this->error('请求错误！');
    }
    /**
     * 留言栏目
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
}