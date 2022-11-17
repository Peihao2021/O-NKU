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

use think\Cookie;
use think\Db;
use think\Page;

/**
 * 我的下载
 */
class Download extends Base
{
    public function _initialize() {
        parent::_initialize();

        $status = Db::name('channeltype')->where([
                'nid'   => 'download',
                'is_del'    => 0,
            ])->getField('status');
        if (empty($status)) {
            $this->error('下载模型已关闭，该功能被禁用！');
        }
        $this->download_order_db = Db::name('download_order');
    }

    public function index()
    {
        $list = array();

        $condition = array();

        $condition['users_id'] = $this->users_id;

        $count = Db::name('download_log')->where($condition)->count('log_id');// 查询满足要求的总记录数
        $Page = $pager = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = Db::name('download_log')->where($condition)->group('aid')->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        $aids = [];
        foreach ($list as $key => $val) {
            array_push($aids, $val['aid']);
        }

        $channeltype_row = \think\Cache::get('extra_global_channeltype');

        $archivesList = DB::name('archives')
            ->field("b.*, a.*, a.aid as aid")
            ->alias('a')
            ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
            ->where('a.aid', 'in', $aids)
            ->getAllWithIndex('aid');
        foreach ($archivesList as $key => $val) {
            $controller_name = $channeltype_row[$val['channel']]['ctl_name'];
            $val['arcurl'] = arcurl('home/'.$controller_name.'/view', $val);
            $val['litpic'] = handle_subdir_pic($val['litpic']); // 支持子目录
            $archivesList[$key] = $val;
        }
        $this->assign('archivesList', $archivesList);

        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页对象
        return $this->fetch('users/download_index');
    }

    public function search_servername()
    {
        if (IS_AJAX_POST) {
            $post = input('param.');
            $keyword = $post['keyword'];

            $servernames = tpCache('download.download_select_servername');
            $servernames = unserialize($servernames);

            $search_data = $servernames;
            if (!empty($keyword)) {
                $search_data = [];
                if ($servernames) {
                    foreach ($servernames as $k => $v) {
                        if (preg_match("/$keyword/s", $v)) $search_data[] = $v;
                    }
                }
            }

            $this->success("获取成功",null,$search_data);
        }
    }
    public function get_template()
    {
        if (IS_AJAX_POST) {
            //$list = Db::name('download_attr_field')->where('field_use',1)->select();
            $list = Db::name('download_attr_field')->select();
            $this->success("查询成功！", null, $list);
        }
    }
    //购买
    public function buy() {
        if (IS_AJAX_POST) {

            // 提交的订单信息判断
            $post = input('param.');
            if (empty($post['aid'])) $this->error('操作异常，请刷新重试');

            // 查询是否已购买
            $where = [
                'order_status' => 1,
                'product_id' => intval($post['aid']),
                'users_id' => $this->users_id
            ];
            $count = $this->download_order_db->where($where)->count();
            if (!empty($count)) $this->error('已购买过');

            // 查询文档内容
            $Where = [
                'is_del' => 0,
                'status' => 1,
                'aid' => $post['aid'],
                'arcrank' => ['>', -1]
            ];

            $list = Db::name('archives')->where($Where)->find();
            if (empty($list)) $this->error('操作异常，请刷新重试');

            // 查看是否已生成过订单
            $where['order_status'] = 0;
            $order =$this->download_order_db->where($where)->order('order_id desc')->find();
            $list['users_price'] = get_discount_price($this->users['level_discount'],$list['users_price']);
            if (!empty($order)) {
                $OrderID = $order['order_id'];
                // 更新订单信息
                $time = getTime();
                $OrderData = [
                    'order_code'      => $order['order_code'],
                    'users_id'        => $this->users_id,
                    'order_status'    => 0,
                    'order_amount'    => $list['users_price'],
                    'product_id'      => $list['aid'],
                    'product_name'    => $list['title'],
                    'product_litpic'  => get_default_pic($list['litpic']),
                    'lang'            => $this->home_lang,
                    'add_time'        => $time,
                    'update_time'     => $time
                ];
                $this->download_order_db->where('order_id', $OrderID)->update($OrderData);
            } else {
                // 生成订单并保存到数据库
                $time = getTime();
                $OrderData = [
                    'order_code'      => date('Y') . $time . rand(10,100),
                    'users_id'        => $this->users_id,
                    'order_status'    => 0,
                    'order_amount'    => $list['users_price'],
                    'pay_time'        => '',
                    'pay_name'        => '',
                    'wechat_pay_type' => '',
                    'pay_details'     => '',
                    'product_id'      => $list['aid'],
                    'product_name'    => $list['title'],
                    'product_litpic'  => get_default_pic($list['litpic']),
                    'lang'            => $this->home_lang,
                    'add_time'        => $time,
                    'update_time'     => $time
                ];
                $OrderID = $this->download_order_db->insertGetId($OrderData);
            }

            // 保存成功
            if (!empty($OrderID)) {
                // 支付结束后返回的URL
                $ReturnUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $post['return_url'];
                cookie($this->users_id . '_' . $post['aid'] . '_EyouDownloadViewUrl', $ReturnUrl);

                // 对ID和订单号加密，拼装url路径
                $Paydata = [
                    'type' => 10,
                    'order_id' => $OrderID,
                    'order_code' => $OrderData['order_code'],
                ];

                // 先 json_encode 后 md5 加密信息
                $Paystr = md5(json_encode($Paydata));

                // 清除之前的 cookie
                Cookie::delete($Paystr);

                // 存入 cookie
                cookie($Paystr, $Paydata);

                // 跳转链接
                if (isMobile()){
                    $PaymentUrl = urldecode(url('user/Pay/pay_recharge_detail',['paystr'=>$Paystr]));//第一种支付
                }else{
                    $PaymentUrl = urldecode(url('user/Download/pay_recharge_detail',['paystr'=>$Paystr]));//第二种支付,弹框支付
                }
                $this->success('订单已生成！', $PaymentUrl);
            }
        } else {
            abort(404);
        }
    }
    // 购买
    public function pay_recharge_detail()
    {
    	$channelData = Db::name('channeltype')->where(['nid'=>'download','status'=>1])->value('data');
    	if (!empty($channelData)) $channelData = json_decode($channelData,true);
    	if (empty($channelData['is_download_pay'])){
            $this->error('请先开启下载付费模式');
        }
        // 接收数据读取解析
        $Paystr = input('param.paystr/s');
        $PayData = cookie($Paystr);
        if (!empty($PayData['order_id']) && !empty($PayData['order_code'])) {
            // 订单信息
            $order_id   = !empty($PayData['order_id']) ? intval($PayData['order_id']) : 0;
            $order_code = !empty($PayData['order_code']) ? $PayData['order_code'] : '';
        } else {
            $this->error('订单不存在或已变更', url('user/Shop/shop_centre'));
        }

        // 处理数据
        if (is_array($PayData) && (!empty($order_id) || !empty($order_code))) {
            $data = [];
            if (!empty($order_id)) {
                /*余额开关*/
                $pay_balance_open = getUsersConfigData('pay.pay_balance_open');
                if (!is_numeric($pay_balance_open) && empty($pay_balance_open)) {
                    $pay_balance_open = 1;
                }
                /*end*/

                if(!empty($PayData['type']) && 10 == $PayData['type']){
                    //下载模型支付订单
                    $where = [
                        'order_id'   => $order_id,
                        'order_code' => $order_code,
                        'users_id'   => $this->users_id,
                        'lang'       => $this->home_lang
                    ];
                    $data = $this->download_order_db->where($where)->find();
                    if (empty($data)) $this->error('订单不存在或已变更', url('user/Download/index'));

                    $url = url('user/Download/index');
                    if (in_array($data['order_status'], [1])) $this->error('订单已支付，即将跳转！', $url);

                    // 组装数据返回
                    $data['transaction_type'] = 10; // 交易类型，10为购买下载模型
                    $data['unified_id']       = $data['order_id'];
                    $data['unified_number']   = $data['order_code'];
                    $data['cause']            = $data['product_name'];
                    $data['pay_balance_open'] = $pay_balance_open;
                    $this->assign('data', $data);
                }
            }

            return $this->fetch('system/download_pay');

        }
        $this->error('参数错误！');
    }
}