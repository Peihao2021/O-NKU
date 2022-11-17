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
 * Date: 2019-3-20
 */

namespace app\user\controller;

use think\Db;
use think\Config;
use think\Page;
use think\Cookie;

class ShopComment extends Base
{
    // 初始化
    public function _initialize() {
        parent::_initialize();

        $functionLogic = new \app\common\logic\FunctionLogic;
        $functionLogic->validate_authorfile(2);

        $this->shop_model = model('Shop');  // 商城模型
    }

    // 我的评价
    public function index()
    {
        $order_code = input('param.order_code');
        $functionLogic = new \app\user\logic\FunctionLogic;
        $ServiceInfo = $functionLogic->GetAllCommentInfo($this->users_id, $order_code);
        $eyou = [
            'field' => [
                'comment' => $ServiceInfo['Comment'],
                'pageStr' => $ServiceInfo['pageStr'],
            ],
        ];
        $this->assign('eyou', $eyou);
        return $this->fetch('users/shop_comment_list');
    }

    // 晒单评价
    public function need_comment_list()
    {
        $res = [];

        $where = [
            'is_comment' => 0,//未评价
            'order_status' => 3,//已完成
            'users_id' => $this->users_id,
        ];
        $field = 'order_id,order_code,order_amount,add_time';

        $count = Db::name('shop_order')->field($field)->where($where)->order('order_id desc')->count();
        $Page = new Page($count, config('paginate.list_rows'));
        $show = $Page->show();
        $this->assign('page', $show);

        $res = Db::name('shop_order')
            ->field($field)->where($where)
            ->order('order_id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->getAllWithIndex('order_id');

        if (!empty($res)){
            $orderIds = get_arr_column($res, 'order_id');
            $goodsWhere['a.is_comment'] = 0;
            $goodsWhere['a.order_id'] = ['in',$orderIds];
            $goodsArr = Db::name('shop_order_details')
                ->alias('a')
                ->join('archives b','a.product_id = b.aid')
                ->where($goodsWhere)
                ->field('a.product_id,a.litpic,a.order_id,b.*')
                ->select();
            foreach ($goodsArr as $k=> $v){
                $v['arcurl'] = urldecode(arcurl('home/Product/view', $v));
                $v['litpic'] = handle_subdir_pic(get_default_pic($v['litpic']));
                $res[$v['order_id']]['goods'][] = $v;
            }
        }
        foreach ($res as $k => $v){
            $res[$k]['OrderDetailsUrl'] = urldecode(url('user/Shop/shop_order_details',['order_id'=>$v['order_id']]));
            $res[$k]['CommentProduct'] = urldecode(url('user/ShopComment/comment_list', ['order_id' => $v['order_id']]));
            $res[$k]['order_amount'] = floatval($v['order_amount']);
        }

        // 加载数据
        $eyou = [
            'field' => $res,
        ];
        $this->assign('eyou',$eyou);
        return $this->fetch('users/shop_need_comment_list');
    }

    // 评价中转页
    public function comment_list()
    {
        // 查询订单信息
        $order_id = input('param.order_id');
        if (empty($order_id)) $this->error('请选择需要评价的订单！');

        $where = [
            'users_id'   => $this->users_id,
            'order_id' => $order_id,
            'is_comment' => 0,
        ];
        $orderData = Db::name('shop_order')->where($where)->find();

        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url("user/Shop/shop_centre", ['select_status'=>3]);
        if (empty($orderData)) $this->error('订单不存在', $url);
        if (!empty($orderData['order_status']) && 3 != $orderData['order_status']) $this->error('订单暂不可评价', $url);
        if (!empty($orderData['is_comment']) && 1 == $orderData['is_comment'] ) $this->error('商品已评价过', $url);

        $data = Db::name('shop_order_details')->where($where)->select();
        foreach ($data as $k => $v){
            // 规格
            $ValueData = unserialize($v['data']);
            $spec_value = !empty($ValueData['spec_value']) ? htmlspecialchars_decode($ValueData['spec_value']) : '';
            $product_spec_list = [];
            $spec_value_arr = explode('<br/>', $spec_value);
            foreach ($spec_value_arr as $sp_key => $sp_val) {
                $sp_arr = explode('：', $sp_val);
                if (trim($sp_arr[0]) && !empty($sp_arr[0])) {
                    $product_spec_list[] = [
                        'name'  => !empty($sp_arr[0]) ? trim($sp_arr[0]) : '',
                        'value' => !empty($sp_arr[1]) ? trim($sp_arr[1]) : '',
                    ];
                }
            }
            $v['product_spec_list'] = $product_spec_list;
            // 图片处理
            $v['litpic'] = handle_subdir_pic(get_default_pic($v['litpic']));

            // 产品内页地址
            $New = get_archives_data([$v], 'product_id');
            if (!empty($New)) {
                $v['arcurl'] = urldecode(arcurl('home/Product/view', $New[$v['product_id']]));
            } else {
                $v['arcurl'] = urldecode(url('home/View/index', ['aid'=>$v['product_id']]));
            }
            $data[$k] = $v;
        }

        $returnData = [
            'order' => $orderData,
            'goods' => $data,
        ];
        $eyou = [
            'field' => $returnData,
            'SubmitUrl' => url('user/ShopComment/add_comment', ['_ajax'=>1])

        ];
        $this->assign('eyou', $eyou);
        return $this->fetch('users/shop_comment_goods_list');
    }

    // 第三套添加评论
    public function add_comment()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            $post['order_id'] = intval($post['order_id']);
            $id_sym = 0;
            foreach ($post['total_score'] as $k => $v){
                if (!empty($v)) $id_sym =1;
            }
            if(0 == $id_sym) $this->error('请至少选择一个评价评分');
            /*再次查询确认商品是否已评价过*/
            $where = [
                'users_id' => $this->users_id,
                'order_id' => $post['order_id'],
                'is_comment' => 1
            ];
            $ResultID = Db::name('shop_order')->where($where)->count();
            if (!empty($ResultID)) $this->error('订单已评价过');
            /* END */

            $AddData = $insertData = $details_idArr = $product_idArr = [];
            foreach ($post['total_score'] as $k => $v){
                if (!empty($v)){
                    $details_idArr[] = intval($post['details_id'][$k]);
                    $product_idArr[] = intval($post['product_id'][$k]);
                    $insertData = [
                        'product_id'  => intval($post['product_id'][$k]),
                        'users_id'    => $this->users_id,
                        'order_id'    => $post['order_id'],
                        'order_code'  => $post['order_code'],
                        'details_id'  => intval($post['details_id'][$k]),
                        'total_score' => $v,
                        'content'     => !empty($post['content'][$k]) ? serialize(htmlspecialchars($post['content'][$k])) : '',
                        'upload_img'  => !empty($post['upload_img'][$k][0]) ? serialize(implode(',', $post['upload_img'][$k])) : '',
                        'ip_address'  => clientIP(),
                        'is_new_comment' => 1,
                        'add_time'    => getTime(),
                        'update_time' => getTime()
                    ];
                    $AddData[] = $insertData;
                }
            }

            $ResultID = Db::name('shop_order_comment')->insertAll($AddData);
            /* END */

            if (!empty($ResultID)) {
                // 商品主表增加评价数
                if (!empty($product_idArr)) {
                    $where = [
                        'aid' => ['IN', $product_idArr],
                    ];
                    Db::name('archives')->where($where)->setInc('appraise', 1);
                }

                //同步更新订单/商品为已评价
                $UpDate = [
                    'is_comment'  => 1,
                    'update_time' => getTime()
                ];
                Db::name('shop_order_details')->where('details_id','in',$details_idArr)->update($UpDate);

                $comment0 = Db::name('shop_order_details')->where(['order_id'=>$post['order_id'],'is_comment'=>0])->count();
                if (0 == $comment0){
                    $where = [
                        'order_id' => $post['order_id'],
                        'users_id' => $this->users_id,
                        'order_status'=> 3,
                    ];
                    $UpDate = [
                        'is_comment' => 1,
                        'update_time' => getTime()
                    ];
                    Db::name('shop_order')->where($where)->update($UpDate);
                }

                $this->success('评价成功！', url('user/ShopComment/need_comment_list'));
            } else {
                $this->error('评价失败，请重试！');
            }
        }

        // 查询订单信息
        $order_id = input('param.order_id');
        if (empty($order_id)) $this->error('请选择需要评价的订单！');

        $where = [
            'users_id'   => $this->users_id,
            'order_id' => $order_id,
        ];
        $orderData = Db::name('shop_order')->where($where)->find();

        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url("user/Shop/shop_centre", ['select_status'=>3]);
        if (empty($orderData)) $this->error('订单不存在', $url);
        if (!empty($orderData['order_status']) && 3 != $orderData['order_status']) $this->error('订单暂不可评价', $url);
        if (!empty($orderData['is_comment']) && 1 == $orderData['is_comment'] ) $this->error('商品已评价过', $url);

        // 查询订单商品数据
        $Details = Db::name('shop_order_details')->where($where)->select();

        foreach ($Details as $k => $v){
            // 商品规格
            $v['spec_value'] = htmlspecialchars_decode(unserialize($v['data'])['spec_value']);

            // 图片处理
            $v['litpic'] = handle_subdir_pic(get_default_pic($v['litpic']));

            // 产品内页地址
            $New = get_archives_data([$v], 'product_id');
            if (!empty($New)) {
                $v['arcurl'] = urldecode(arcurl('home/Product/view', $New[$v['product_id']]));
            } else {
                $v['arcurl'] = urldecode(url('home/View/index', ['aid'=>$v['product_id']]));
            }
            $Details[$k] = $v;
        }
        $returnData = [
            'order' => $orderData,
            'goods' => $Details,
        ];
        $eyou = [
            'field' => $returnData,
            'SubmitUrl' => url('user/ShopComment/add_comment', ['_ajax'=>1])
        ];
        $this->assign('eyou', $eyou);

        return $this->fetch('shop_comment_product');
    }

    // 添加评论
    public function product()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            if (empty($post['total_score'])) $this->error('请选择评价评分');
            $post['order_id'] = intval($post['order_id']);
            $post['details_id'] = intval($post['details_id']);
            $post['product_id'] = intval($post['product_id']);

            /*再次查询确认商品是否已评价过*/
            $where = [
                'users_id' => $this->users_id,
                'order_id' => $post['order_id'],
                'details_id' => $post['details_id'],
                'product_id' => $post['product_id'],
                'is_comment' => 1
            ];
            $ResultID = Db::name('shop_order_details')->where($where)->count();
            if (!empty($ResultID)) $this->error('商品已评价过');
            /* END */

            // 是否开启评价自动审核
            $shopOpenCommentAudit = getUsersConfigData('shop.shop_open_comment_audit');
            /*添加评价数据*/
            $AddData = [
                'users_id'    => $this->users_id,
                'order_id'    => !empty($post['order_id']) ? $post['order_id'] : 0,
                'order_code'  => !empty($post['order_code']) ? $post['order_code'] : 0,
                'details_id'  => !empty($post['details_id']) ? $post['details_id'] : 0,
                'product_id'  => !empty($post['product_id']) ? $post['product_id'] : 0,
                'total_score' => !empty($post['total_score']) ? $post['total_score'] : 5,
                'content'     => !empty($post['content']) ? serialize(htmlspecialchars($post['content'])) : '',
                'upload_img'  => !empty($post['upload_img'][0]) ? serialize(implode(',', $post['upload_img'])) : '',
                'ip_address'  => clientIP(),
                'is_new_comment' => 1,
                'is_show'     => !empty($shopOpenCommentAudit) ? 0 : 1,
                'add_time'    => getTime(),
                'update_time' => getTime()
            ];
            $ResultID = Db::name('shop_order_comment')->insertGetId($AddData);
            /* END */

            if (!empty($ResultID)) {
                // 商品主表增加评价数
                if (!empty($post['product_id'])) {
                    $where = [
                        'aid' => $post['product_id'],
                    ];
                    Db::name('archives')->where($where)->setInc('appraise', 1);
                }
                
                /*同步更新订单商品为已评价*/
                $UpDate = [
                    'details_id'  => $AddData['details_id'],
                    'is_comment'  => 1,
                    'update_time' => getTime()
                ];
                Db::name('shop_order_details')->update($UpDate);
                /* END */

                /*如果订单商品已经全部评价，那么订单主表is_comment == 1*/
                $where = [
                    'order_id' => $post['order_id'],
                    'users_id' => $this->users_id,
                    'is_comment' => 0
                ];
                $ResultID = Db::name('shop_order_details')->where($where)->count();
                if (empty($ResultID)){
                    $where = [
                        'order_id' => $post['order_id'],
                        'users_id' => $this->users_id,
                        'order_status'=> 3,
                        'is_comment'  => 0,
                    ];
                    $UpDate = [
                        'is_comment' => 1,
                        'update_time' => getTime()
                    ];
                    Db::name('shop_order')->where($where)->update($UpDate);
                }
                /* END */

                cache('EyouHomeAjaxComment_' . $post['product_id'], null, null, 'shop_order_comment');

                $this->success('评价成功！', url('user/Shop/shop_centre'));
            } else {
                $this->error('评价失败，请重试！');
            }
        }

        // 查询订单信息
        $details_id = input('param.details_id');
        if (empty($details_id)) $this->error('请选择需要评价的商品！');
        // 排除字段
        $field1 = 'add_time, update_time, apply_service';
        // 查询字段
        $field2 = 'b.order_code, b.add_time';
        // 查询条件
        $where = [
            'a.users_id'   => $this->users_id,
            'a.details_id' => $details_id,
        ];
        // 查询数据
        $Details = Db::name('shop_order_details')
            ->alias('a')
            ->field($field1, true, PREFIX . 'shop_order_details', 'a')
            ->field($field2)
            ->where($where)
            ->join('__SHOP_ORDER__ b', 'a.order_id = b.order_id', 'LEFT')
            ->find();

        // 已评价商品跳转路径
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url("user/Shop/shop_centre", ['select_status'=>3]);
        if (empty($Details)) $this->error('商品已评价过', $url);
        if (1 == !empty($Details['is_comment'])) $this->error('商品已评价过', $url);

        // 商品规格
        $Details['spec_value'] = htmlspecialchars_decode(unserialize($Details['data'])['spec_value']);

        // 图片处理
        $Details['litpic'] = handle_subdir_pic(get_default_pic($Details['litpic']));

        // 产品内页地址
        $New = get_archives_data([$Details], 'product_id');
        if (!empty($New)) {
            $Details['arcurl'] = urldecode(arcurl('home/Product/view', $New[$Details['product_id']]));
        } else {
            $Details['arcurl'] = urldecode(url('home/View/index', ['aid'=>$Details['product_id']]));
        }

        $eyou = [
            'field' => $Details,
            'SubmitUrl' => url('user/ShopComment/product', ['_ajax'=>1])
        ];
        $this->assign('eyou', $eyou);

        return $this->fetch('shop_comment_product');
    }
}