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
 * Date: 2018-06-28
 */

namespace app\plugins\controller;

use think\Page;
use think\Db;
use weapp\Census\model\CensusModel;

/**
 * 插件的控制器
 */
class Census extends Base
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->model = new CensusModel;
    }
    public function ajaxpost(){
		\think\Session::pause(); // 暂停session，防止session阻塞机制

        if (IS_AJAX) {
			$info = $this->model->getWeappData();
			//文章统计
			$whereon = [
				'lang'    => $this->admin_lang,
							'status'    => 1,
							'is_del'    => 0,
			];
			$arccounttrue = Db::name('archives')->where($whereon)->count();
			$arccount = $arccounttrue + $info['data']['arccountfalse'];
			//当天文章统计
			$t = mktime(0,0,0,date('m'),date('d'),date('Y'));
			$where1['add_time'] = ['gt', $t];
			$where = array_merge($whereon, $where1);
			$todayarccounttrue = Db::name('archives')->where($where)->count();
			$todayarccount = $todayarccounttrue + $info['data']['todayarccountfalse'];
			//一周文章统计
			$w = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
			$where2['add_time'] = ['gt', $w];
			$where = array_merge($whereon, $where2);
			$weekarccounttrue = Db::name('archives')->where($where)->count();
			$weekarccount = $weekarccounttrue + $info['data']['weekarccountfalse'];
			//本月文章数量统计
			$m = mktime(0,0,0,date('m'),1,date('Y'));
			$where3['add_time'] = ['gt', $m];
			$where = array_merge($whereon, $where3);
			$montharccounttrue = Db::name('archives')->where($where)->count();
			$montharccount = $montharccounttrue + $info['data']['montharccountfalse'];
			//未审核文章统计
			$whereoff = [
				'arcrank' => ['eq', -1],
				'status'  => 1,
				'is_del'  => 0,
				'channel' => 1,
				'lang'    => $this->home_lang,
			];
			$noarccounttrue = Db::name('archives')->where($whereoff)->count();
			$noarccount = $noarccounttrue + $info['data']['noarccountfalse'];
			
			//会员数量统计
			$whereuser = [
				'is_activation' => ['eq', 1],
				'is_lock'  => 0,
				'is_del'  => 0,
				'admin_id' => 0,
				'lang'    => $this->home_lang,
			];
			$userscounttrue = Db::name('users')->where($whereuser)->count();
			$userscount = $userscounttrue + $info['data']['userscountfalse'];
			//开通收费会员统计
			$whereusers = [
				'is_activation' => ['eq', 1],
				'level' => ['gt', 1],
				'is_lock'  => 0,
				'is_del'  => 0,
				'admin_id' => 0,
				'lang'    => $this->home_lang,
			];
			$onuserscounttrue = Db::name('users')->where($whereusers)->count();
			$onuserscount = $onuserscounttrue + $info['data']['onuserscountfalse'];
			//产品数量统计
			$wherepro = [
				'arcrank' => ['gt', -1],
				'status'  => 1,
				'is_del'  => 0,
				'channel' => 2,
				'lang'    => $this->home_lang,
			];
			$procounttrue = Db::name('archives')->where($wherepro)->count();
			$procount = $procounttrue + $info['data']['procountfalse'];
			//交易金额统计
			$wheremon = [
				'order_status'  => 3,
				'lang'    => $this->home_lang,
			];
			$moncounttrue = Db::name('shop_order')->where($wheremon)->sum('order_amount'); 
			$moncount = $moncounttrue + $info['data']['moncountfalse'];
			
			//订单数量统计onmoncount
			$wheremons = [
				'order_status'  => 3,
				'lang'    => $this->home_lang,
			];
			$onmoncounttrue = Db::name('shop_order')->where($wheremons)->count();
			$onmoncount = $onmoncounttrue + $info['data']['onmoncountfalse'];
			//留言数量统计
			$wherebook = [
				'channel' => 8,
				'lang'    => $this->home_lang,
			];
			$bookcountstrue = Db::name('guestbook')->where($wherebook)->count();
			$bookcounts = $bookcountstrue + $info['data']['bookcountsfalse'];
			//友情链接数量统计
			$wherelink = [
				'status' => 1,
				'lang'    => $this->home_lang,
			];
			$linkcountstrue = Db::name('links')->where($wherelink)->count();
			$linkcounts = $linkcountstrue + $info['data']['linkcountsfalse'];
			
			//检测问答插件是否安装
			$askName = 'ey_weapp_ask';
			$isask = Db::query('SHOW TABLES LIKE'."'".$askName."'");
			
			//判断问答是否安装
			if(!empty($isask)){
				//问答提问数量统计
				$whereask = [
					'is_review' => 1,
				];
				$questioncountstrue = Db::name('weapp_ask')->where($whereask)->count();
				$questioncounts = $questioncountstrue + $info['data']['questioncountsfalse'];
				//问答今日提问数量统计
				$askt = mktime(0,0,0,date('m'),date('d'),date('Y'));
				$where1['add_time'] = ['gt', $askt];
				$where = array_merge($whereask, $where1);
				$tquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
				$tquestioncounts = $tquestioncountstrue + $info['data']['tquestioncountsfalse'];
				//问答本周提问数量统计
				$askw = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
				$where1['add_time'] = ['gt', $askw];
				$where = array_merge($whereask, $where1);
				$wquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
				$wquestioncounts = $wquestioncountstrue + $info['data']['wquestioncountsfalse'];
				//问答本月提问数量统计
				$askm = mktime(0,0,0,date('m'),1,date('Y'));
				$where1['add_time'] = ['gt', $askm];
				$where = array_merge($whereask, $where1);
				$mquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
				$mquestioncounts = $mquestioncountstrue + $info['data']['mquestioncountsfalse'];
				//问答回答数量统计
				$whereanswer = [
					'is_review' => 1,
					'ifcheck' => 1,
				];
				$askcountstrue = Db::name('weapp_ask_answer')->where($whereanswer)->count();
				$askcounts = $askcountstrue + $info['data']['askcountsfalse'];
			}
			$this->success('表决成功', null, [
				'code'=>1, 
				'arccount' => $arccount,
				'todayarccount' => $todayarccount,
				'weekarccount' => $weekarccount,
				'montharccount' => $montharccount,
				'noarccount' => $noarccount,
				'userscount' => $userscount,
				'onuserscount' => $onuserscount,
				'procount' => $procount,
				'moncount' => $moncount,
				'onmoncount' => $onmoncount,
				'bookcounts' => $bookcounts,
				'linkcounts' => $linkcounts,
				'questioncounts' => $questioncounts,
				'tquestioncounts' => $tquestioncounts,
				'wquestioncounts' => $wquestioncounts,
				'mquestioncounts' => $mquestioncounts,
				'askcounts' => $askcounts,
			]);
		}
    }
}