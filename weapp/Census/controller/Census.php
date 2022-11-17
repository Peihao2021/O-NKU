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

namespace weapp\Census\controller;

use think\Page;
use think\Db;
use app\common\controller\Weapp;
use weapp\Census\model\CensusModel;

/**
 * 插件的控制器
 */
class Census extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 实例化对象
     */
    private $db;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->model = new CensusModel;
        $this->db = Db::name('WeappCensus');

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

	public function afterInstall()
    {
        // 系统默认是自动调用，这里在安装完插件之后，更改为手工调用
        $savedata = [
            'tag_weapp' => 2,
            'update_time'   => getTime(),
        ];
        Db::name('weapp')->where(['code'=>'Census'])->update($savedata);
    }
	
    /**
     * 插件使用指南
     */
    public function doc(){
        return $this->fetch('doc');
    }
	
    /**
     * 插件字体大全
     */
	public function demo(){
        return $this->fetch('demo');
    }
    /**
     * 系统内置钩子show方法（没用到这个方法，建议删掉）
     * 用于在前台模板显示片段的html代码，比如：QQ客服、对联广告等
     *
     * @param  mixed  $params 传入的参数
     */
    public function show($params = null){
		$info = $this->model->getWeappData();
		//文章统计
		//默认显示
		$arccounton = $info['data']['arccount'];
		if (empty($arccounton)) {
		    $arccounton = 1;
		}
		$this->assign('arccounton', $arccounton);
		//默认显示结束
		$whereon = [
            'lang'    => $this->admin_lang,
                        'status'    => 1,
                        'is_del'    => 0,
        ];
		$arccounttrue = Db::name('archives')->where($whereon)->count();
		$arccount = $arccounttrue + $info['data']['arccountfalse'];
		$this->assign('arccount', $arccount);
		
		//当天文章统计
		//默认显示
		$todayarccounton = $info['data']['todayarccount'];
		if (empty($todayarccounton)) {
		    $todayarccounton = 1;
		}
		$this->assign('todayarccounton', $todayarccounton);
		//默认显示结束
		$t = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$where1['add_time'] = ['gt', $t];
		$where = array_merge($whereon, $where1);
		$todayarccounttrue = Db::name('archives')->where($where)->count();
		$todayarccount = $todayarccounttrue + $info['data']['todayarccountfalse'];
		$this->assign('todayarccount', $todayarccount);
		
		//一周文章统计
		//默认显示
		$weekarccounton = $info['data']['weekarccount'];
		if (empty($weekarccounton)) {
		    $weekarccounton = 1;
		}
		$this->assign('weekarccounton', $weekarccounton);
		//默认显示结束
		$w = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
		$where2['add_time'] = ['gt', $w];
		$where = array_merge($whereon, $where2);
		$weekarccounttrue = Db::name('archives')->where($where)->count();
		$weekarccount = $weekarccounttrue + $info['data']['weekarccountfalse'];
		$this->assign('weekarccount', $weekarccount);
		
		//本月文章数量统计
		//默认显示
		$montharccounton = $info['data']['montharccount'];
		if (empty($montharccounton)) {
		    $montharccounton = 1;
		}
		$this->assign('montharccounton', $montharccounton);
		//默认显示结束
		$m = mktime(0,0,0,date('m'),1,date('Y'));
		$where3['add_time'] = ['gt', $m];
		$where = array_merge($whereon, $where3);
		$montharccounttrue = Db::name('archives')->where($where)->count();
		$montharccount = $montharccounttrue + $info['data']['montharccountfalse'];
		$this->assign('montharccount', $montharccount);
		
		//未审核文章统计
		//默认显示
		$noarccounton = $info['data']['noarccount'];
		if (empty($noarccounton)) {
		    $noarccounton = 1;
		}
		$this->assign('noarccounton', $noarccounton);
		//默认显示结束
		$whereoff = [
            'arcrank' => ['eq', -1],
            'status'  => 1,
            'is_del'  => 0,
			'channel' => 1,
            'lang'    => $this->home_lang,
        ];
		$noarccounttrue = Db::name('archives')->where($whereoff)->count();
		$noarccount = $noarccounttrue + $info['data']['noarccountfalse'];
		$this->assign('noarccount', $noarccount);
		
		//会员数量统计
		//默认显示
		$userscounton = $info['data']['userscount'];
		if (empty($userscounton)) {
		    $userscounton = 1;
		}
		$this->assign('userscounton', $userscounton);
		//默认显示结束
		$whereuser = [
            'is_activation' => ['eq', 1],
            'is_lock'  => 0,
            'is_del'  => 0,
			'admin_id' => 0,
            'lang'    => $this->home_lang,
        ];
		$userscounttrue = Db::name('users')->where($whereuser)->count();
		$userscount = $userscounttrue + $info['data']['userscountfalse'];
		$this->assign('userscount', $userscount);
		
		//开通收费会员统计
		//默认显示
		$onuserscounton = $info['data']['onuserscount'];
		if (empty($onuserscounton)) {
		    $onuserscounton = 1;
		}
		$this->assign('onuserscounton', $onuserscounton);
		//默认显示结束
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
		$this->assign('onuserscount', $onuserscount);
		
		//产品数量统计
		//默认显示
		$procounton = $info['data']['procount'];
		if (empty($procounton)) {
		    $procounton = 1;
		}
		$this->assign('procounton', $procounton);
		//默认显示结束
		$wherepro = [
            'arcrank' => ['gt', -1],
            'status'  => 1,
            'is_del'  => 0,
			'channel' => 2,
            'lang'    => $this->home_lang,
        ];
		$procounttrue = Db::name('archives')->where($wherepro)->count();
		$procount = $procounttrue + $info['data']['procountfalse'];
		$this->assign('procount', $procount);
		
		//交易金额统计
		//默认显示
		$moncounton = $info['data']['moncount'];
		if (empty($moncounton)) {
		    $moncounton = 1;
		}
		$this->assign('moncounton', $moncounton);
		//默认显示结束
		$wheremon = [
            'order_status'  => 3,
            'lang'    => $this->home_lang,
        ];
		$moncounttrue = Db::name('shop_order')->where($wheremon)->sum('order_amount'); 
		$moncount = $moncounttrue + $info['data']['moncountfalse'];
		$this->assign('moncount', $moncount);
		
		//订单数量统计onmoncount
		//默认显示
		$onmoncounton = $info['data']['onmoncount'];
		if (empty($onmoncounton)) {
		    $onmoncounton = 1;
		}
		$this->assign('onmoncounton', $onmoncounton);
		//默认显示结束
		$wheremons = [
            'order_status'  => 3,
            'lang'    => $this->home_lang,
        ];
		$onmoncounttrue = Db::name('shop_order')->where($wheremons)->count();
		$onmoncount = $onmoncounttrue + $info['data']['onmoncountfalse'];
		$this->assign('onmoncount', $onmoncount);
		
		//完成交易产品列表
		//默认显示
		$onmoncountson = $info['data']['onmoncounts'];
		if (empty($onmoncountson)) {
		    $onmoncountson = 1;
		}
		$this->assign('onmoncountson', $onmoncountson);
		//默认显示结束
		$wheremonss = [
            'a.order_status'  => 3,
            'a.lang'    => $this->home_lang,
        ];
		$onmoncounts = Db::name('shop_order')->alias('a')
            ->field('c.*,e.username,a.add_time,a.order_amount,d.dirname,e.head_pic')
			->join('__SHOP_ORDER_DETAILS__ b', 'a.order_id = b.order_id', 'LEFT')
			->join('__ARCHIVES__ c', 'c.aid = b.product_id', 'LEFT')
			->join('__ARCTYPE__ d', 'd.id = c.typeid', 'LEFT')
			->join('__USERS__ e', 'e.users_id = a.users_id', 'LEFT')
            ->where($wheremonss)
            ->order('c.aid', 'desc')
            ->limit($limit)
            ->select();
		// 获取查询的控制器名
        $channeltype_info = model('Channeltype')->getInfo($channel);
        $controller_name = $channeltype_info['ctl_name'];
        foreach ($onmoncounts as $key => $val) {
            $val['litpic'] = get_default_pic($val['litpic']); // 默认封面图
            /*文档链接*/
            if ($val['is_jump'] == 1) {
                $val['arcurl'] = $val['jumplinks'];
            } else {
                $val['arcurl'] = arcurl('home/'.$controller_name.'/view', $val);
            }
            /*--end*/
            $onmoncounts[$key] = $val;
        }
		$this->assign('onmoncounts', $onmoncounts);
		
		//留言数量统计
		//默认显示
		$bookcountson = $info['data']['bookcounts'];
		if (empty($bookcountson)) {
		    $bookcountson = 1;
		}
		$this->assign('bookcountson', $bookcountson);
		//默认显示结束
		$wherebook = [
			'channel' => 8,
            'lang'    => $this->home_lang,
        ];
		$bookcountstrue = Db::name('guestbook')->where($wherebook)->count();
		$bookcounts = $bookcountstrue + $info['data']['bookcountsfalse'];
		$this->assign('bookcounts', $bookcounts);
		
		//友情链接数量统计
		//默认显示
		$linkcountson = $info['data']['linkcounts'];
		if (empty($linkcountson)) {
		    $linkcountson = 1;
		}
		$this->assign('linkcountson', $linkcountson);
		//默认显示结束
		$wherelink = [
			'status' => 1,
            'lang'    => $this->home_lang,
        ];
		$linkcountstrue = Db::name('links')->where($wherelink)->count();
		$linkcounts = $linkcountstrue + $info['data']['linkcountsfalse'];
		$this->assign('linkcounts', $linkcounts);
		
		//检测问答插件是否安装
		$askName = 'ey_weapp_ask';
        $isask = Db::query('SHOW TABLES LIKE'."'".$askName."'");
		
		//判断问答是否安装
		if(!empty($isask)){
		    //问答提问数量统计
			//默认显示
		    $questioncountson = $info['data']['questioncounts'];
		    if (empty($questioncountson)) {
		        $questioncountson = 1;
		    }
		    $this->assign('questioncountson', $questioncountson);
		    //默认显示结束
		    $whereask = [
			    'is_review' => 1,
            ];
		    $questioncountstrue = Db::name('weapp_ask')->where($whereask)->count();
			$questioncounts = $questioncountstrue + $info['data']['questioncountsfalse'];
		    $this->assign('questioncounts', $questioncounts);
		
		    //问答今日提问数量统计
			//默认显示
		    $tquestioncountson = $info['data']['tquestioncounts'];
		    if (empty($tquestioncountson)) {
		        $tquestioncountson = 1;
		    }
		    $this->assign('tquestioncountson', $tquestioncountson);
		    //默认显示结束
		    $askt = mktime(0,0,0,date('m'),date('d'),date('Y'));
		    $where1['add_time'] = ['gt', $askt];
		    $where = array_merge($whereask, $where1);
		    $tquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
			$tquestioncounts = $tquestioncountstrue + $info['data']['tquestioncountsfalse'];
		    $this->assign('tquestioncounts', $tquestioncounts);
		
		    //问答本周提问数量统计
			//默认显示
		    $wquestioncountson = $info['data']['wquestioncounts'];
		    if (empty($wquestioncountson)) {
		        $wquestioncountson = 1;
		    }
		    $this->assign('wquestioncountson', $wquestioncountson);
		    //默认显示结束
		    $askw = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
		    $where1['add_time'] = ['gt', $askw];
		    $where = array_merge($whereask, $where1);
		    $wquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
			$wquestioncounts = $wquestioncountstrue + $info['data']['wquestioncountsfalse'];
		    $this->assign('wquestioncounts', $wquestioncounts);
		
		    //问答本月提问数量统计
			//默认显示
		    $mquestioncountson = $info['data']['mquestioncounts'];
		    if (empty($mquestioncountson)) {
		        $mquestioncountson = 1;
		    }
		    $this->assign('mquestioncountson', $mquestioncountson);
		    //默认显示结束
		    $askm = mktime(0,0,0,date('m'),1,date('Y'));
		    $where1['add_time'] = ['gt', $askm];
		    $where = array_merge($whereask, $where1);
		    $mquestioncountstrue = Db::name('weapp_ask')->where($where)->count();
			$mquestioncounts = $mquestioncountstrue + $info['data']['mquestioncountsfalse'];
		    $this->assign('mquestioncounts', $mquestioncounts);
		
		    //问答回答数量统计
			//默认显示
		    $askcountson = $info['data']['askcounts'];
		    if (empty($askcountson)) {
		        $askcountson = 1;
		    }
		    $this->assign('askcountson', $askcountson);
		    //默认显示结束
		    $whereanswer = [
			    'is_review' => 1,
			    'ifcheck' => 1,
            ];
		    $askcountstrue = Db::name('weapp_ask_answer')->where($whereanswer)->count();
			$askcounts = $askcountstrue + $info['data']['askcountsfalse'];
		    $this->assign('askcounts', $askcounts);
		}
		
		$this->assign('isask', $isask);
		
        $this->assign('info', $info);
        echo $this->fetch('show');
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
		$info = $this->model->getWeappData();
        $this->assign('info', $info);
		//检测问答插件是否安装
		$askName = 'ey_weapp_ask';
        $isask = Db::query('SHOW TABLES LIKE'."'".$askName."'");
		$this->assign('isask', $isask);

        return $this->fetch('index');
    }

	/**
     * 插件配置
     */
    public function save()
    {
        if (IS_POST) {
            $data = input('post.');

            $info = $this->model->getWeappData();
            

            $saveData = array(
                'data'        => serialize($data),
                'update_time' => getTime(),
            );

            $r = Db::name('weapp')->where(array('code' => 'Census'))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Census/Census/index'));
            }
        }
        $this->error("操作失败");
    }
    /**
     * 插件配置
     */
    public function conf()
    {
        if (IS_POST) {
            $post = input('post.');
            if(!empty($post['code'])){
                $data = array(
                    'tag_weapp' => $post['tag_weapp'],
                    'update_time' => getTime(),
                );
                $r = M('weapp')->where('code','eq',$post['code'])->update($data);
                if ($r) {
                    \think\Cache::clear('hooks');
                    adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                    $this->success("操作成功!", weapp_url('Census/Census/conf'));
                }
            }
            $this->error("操作失败!");
        }

        $row = M('weapp')->where('code','eq','Census')->find();
        $this->assign('row', $row);

        return $this->fetch('conf');
    }
}