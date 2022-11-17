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

namespace app\plugins\controller;
use think\Page;
use think\Db;
use think\Response;
use think\Request;

class Pushall extends Base
{
    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * index
     */
    public function autopush() {
        function get_arcurl($arcview_info = array(), $admin = true){
        static $domain = null;
        null === $domain && $domain = request()->domain();

        /*兼容采集没有归属栏目的文档*/
        if (empty($arcview_info['channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arcview_info = array_merge($arcview_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arcview_info['channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if ($admin) {
            if (2 == $seo_pseudo) {
                static $lang = null;
                null === $lang && $lang = input('param.lang/s', 'cn');
                $arcurl = ROOT_DIR."/index.php?m=home&c=View&a=index&aid={$arcview_info['aid']}&lang={$lang}&admin_id=".session('admin_id')."&t=".getTime();
            } else {
                $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
                // 自动隐藏index.php入口文件
                $arcurl = auto_hide_index($arcurl);
            }
        } else {
            $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $arcurl = auto_hide_index($arcurl);
        }

        return $arcurl;
    }
        $assign_data = array();
        $condition = array();
        // 获取到所有URL参数
        $param = input('param.');
        $sxpushzt = input('sxpushzt');
        $ks = input('ks');
        $sxtsjg = input('sxtsjg');
        $typeid = input('typeid/d', 0);
        $d2ViX2lzX2F1 = tpCache('web.'.$this->arrJoinStr(['d2ViX2lzX2F1','dGhvcnRva2Vu']));
        /*跳转到指定栏目的文档列表*/
        if (0 < intval($typeid)) {
            $row = Db::name('arctype')
                ->alias('a')
                ->field('b.ctl_name,b.id')
                ->join('__CHANNELTYPE__ b', 'a.current_channel = b.id', 'LEFT')
                ->where('a.id', 'eq', $typeid)
                ->find();
            $ctl_name = $row['ctl_name'];
            $current_channel = $row['id'];
            if (6 == $current_channel) {
                $gourl = url('Arctype/single_edit', array('typeid'=>$typeid));
                $gourl = url("Arctype/single_edit", array('typeid'=>$typeid,'gourl'=>$gourl));
                $this->redirect($gourl);
            } else if (8 == $current_channel) {
                $gourl = url("Guestbook/index", array('typeid'=>$typeid));
                $this->redirect($gourl);
            } else if (5 == $current_channel) {
                if (-1 == $d2ViX2lzX2F1) {
                    $this->error(base64_decode('6KeG6aKR5qih5Z6L5LuF6ZmQ5LqO5o6I5p2D5Z+f5ZCN77yB'));
                }
            }
        }
        /*--end*/

        // 应用搜索条件
        foreach (['keywords','typeid','flag','is_release'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'typeid') {
                    $typeid = $param[$key];
                    $hasRow = model('Arctype')->getHasChildren($typeid);
                    $typeids = get_arr_column($hasRow, 'id');
                    /*权限控制 by 小虎哥*/
                    $admin_info = session('admin_info');
                    if (0 < intval($admin_info['role_id'])) {
                        $auth_role_info = $admin_info['auth_role_info'];
                        if(! empty($auth_role_info)){
                            if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                                $condition['a.admin_id'] = $admin_info['admin_id'];
                            }
                            if(! empty($auth_role_info['permission']['arctype'])){
                                if (!empty($typeid)) {
                                    $typeids = array_intersect($typeids, $auth_role_info['permission']['arctype']);
                                }
                            }
                        }
                    }
                    /*--end*/
                    $condition['a.typeid'] = array('IN', $typeids);
                } else if ($key == 'flag') {
                    if ('is_release' == $param[$key]) {
                        $condition['a.users_id'] = array('gt', 0);
                    } else {
                        $condition['a.'.$param[$key]] = array('eq', 1);
                    }
                // } else if ($key == 'is_release') {
                //     if (0 < intval($param[$key])) {
                //         $condition['a.users_id'] = array('gt', intval($param[$key]));
                //     }
                } else {
                    $condition['a.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        /*权限控制 by 小虎哥*/
        if (empty($typeid)) {
            $typeids = [];
            $admin_info = session('admin_info');
            if (0 < intval($admin_info['role_id'])) {
                $auth_role_info = $admin_info['auth_role_info'];
                if(! empty($auth_role_info)){
                    if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                        $condition['a.admin_id'] = $admin_info['admin_id'];
                    }
                    if(! empty($auth_role_info['permission']['arctype'])){
                        $typeids = $auth_role_info['permission']['arctype'];
                    }
                }
            }
            if (!empty($typeids)) {
                $condition['a.typeid'] = array('IN', $typeids);
            }
        }
        /*--end*/

        /*多语言*/
        $condition['a.lang'] = array('eq', $this->admin_lang);
        /*--end*/

        /*回收站数据不显示*/
        $condition['a.is_del'] = array('eq', 0);
        /*--end*/


        /*未审核数据不显示*/
        $condition['a.arcrank'] = array('eq', 0);
        /*--end*/

        /*自定义排序*/
        $orderby = input('param.orderby/s');
        $orderway = input('param.orderway/s');
        if (!empty($orderby)) {
            $orderby = "a.{$orderby} {$orderway}";
            $orderby .= ", a.aid desc";
        } else {
            $orderby = "a.aid desc";
        }
        /*end*/


if ($sxpushzt!=null) {
if ($sxpushzt==0){$conditionraw='(b.baidupushzt=0 OR b.baidupushzt is null) AND (b.shenmapushzt=0 OR b.shenmapushzt is null) AND (b.sogoupushzt=0 OR b.sogoupushzt is null) AND (b.toutiaopushzt=0 OR b.toutiaopushzt is null)';}
elseif ($sxpushzt==1) {$condition['b.baidupushzt'] = array('eq', 1);}
elseif ($sxpushzt==2) {$conditionraw='b.baidupushzt=0 OR b.baidupushzt is null';}
elseif ($sxpushzt==3) {$condition['b.shenmapushzt'] = array('eq', 1);}
elseif ($sxpushzt==4) {$conditionraw='b.shenmapushzt=0 OR b.shenmapushzt is null';}
elseif ($sxpushzt==5) {$condition['b.sogoupushzt'] = array('eq', 1);}
elseif ($sxpushzt==6) {$conditionraw='b.sogoupushzt=0 OR b.sogoupushzt is null';}
elseif ($sxpushzt==7) {$condition['b.toutiaopushzt'] = array('eq', 1);}
elseif ($sxpushzt==8) {$conditionraw='b.toutiaopushzt=0 OR b.toutiaopushzt is null';}
        }else {
            $conditionraw='';
        }

        $request = Request::instance();
        $domin = $request->domain();
        $rownum = M('weapp')->where('code','eq','Pushall')->find();
        $rownum = json_decode($rownum['data'],true);
        $urlms = $rownum['urlms'];

        /**
         * 数据查询，搜索出主键ID的值
         */
         if ($conditionraw!=null) {
        $count = DB::name('archives')->alias('a')->join('WeappPushall b','a.aid = b.aid', 'LEFT')->where($condition)->whereRaw($conditionraw)->count('a.aid');// 查询满足要求的总记录数
        $Page = new Page($count, $rownum[onnum]);// 实例化分页类 传入总记录数和每页显示的记录数

        $list = DB::name('archives')
            ->field("b.*,a.aid")
            ->alias('a')
            ->join('WeappPushall b','a.aid = b.aid', 'LEFT')
            ->where($condition)
            ->whereRaw($conditionraw)
            ->order($orderby)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->getAllWithIndex('aid');
         }else {
             
        $count = DB::name('archives')->alias('a')->join('WeappPushall b','a.aid = b.aid', 'LEFT')->where($condition)->count('aid');// 查询满足要求的总记录数
        $Page = new Page($count, $rownum[onnum]);// 实例化分页类 传入总记录数和每页显示的记录数


        $list = DB::name('archives')
            ->field("b.*,a.aid")
            ->alias('a')
            ->join('WeappPushall b','a.aid = b.aid', 'LEFT')
            ->where($condition)
            ->order($orderby)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->getAllWithIndex('aid');
         }
       /* $list = DB::name('archives')
            ->field("a.aid,a.channel")
            ->alias('a')
            ->where($condition)
            ->order($orderby)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->getAllWithIndex('aid');

        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        if ($list) {
            $aids = array_keys($list);
            $fields = "b.*, a.*, a.aid as aid";
            $row = DB::name('archives')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids)
                ->getAllWithIndex('aid');

            /*获取当页文档的所有模型*/
            $channelIds = get_arr_column($list, 'channel');
            $channelRow = Db::name('channeltype')->field('id, ctl_name, ifsystem')
                ->where('id','IN',$channelIds)
                ->getAllWithIndex('id');
            $assign_data['channelRow'] = $channelRow;
            /*--end*/
            /*获取推送状态*/
            $pushzt = DB::name('WeappPushall')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.aid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids,$pushztshaixuan)
                ->getAllWithIndex('aid');
            /*--end*/

            foreach ($list as $key => $val) {

                $row[$val['aid']]['arcurl'] = get_arcurl($row[$val['aid']], false);
                $row[$val['aid']]['litpic'] = handle_subdir_pic($row[$val['aid']]['litpic']); // 支持子目录
                $row[$val['aid']]['baidupushzt'] = $pushzt[$val['aid']]['baidupushzt'];
                $row[$val['aid']]['shenmapushzt'] = $pushzt[$val['aid']]['shenmapushzt'];
                $row[$val['aid']]['sogoupushzt'] = $pushzt[$val['aid']]['sogoupushzt'];
                $row[$val['aid']]['toutiaopushzt'] = $pushzt[$val['aid']]['toutiaopushzt'];
                $list[$key] = $row[$val['aid']];
            }


        }
        $listzt = $list;
        $show = $Page->show(); // 分页显示输出
        $assign_data['page'] = $show; // 赋值分页输出
        //$assign_data['list'] = $list; // 赋值数据集
        $assign_data['listzt'] = $listzt; // 赋值数据集
        $assign_data['pager'] = $Page; // 赋值分页对象


        /*前台URL模式*/
        $assign_data['seo_pseudo'] = tpCache('seo.seo_pseudo');


        $this->assign('urlms', $urlms);
        $this->assign('domin', $domin); //
        $this->assign($assign_data);
                    foreach ($listzt as $key => $val) {
                         $ids .= $val["aid"].',';
                         $listztautopush .= $val["arcurl"];
                        }
                    $listztautopush = str_replace( "htmlh", "html,h",$listztautopush);
                    $ids = substr($ids,0,strlen($ids)-1);
                    $ids = preg_split("/,/",$ids);
    /**
     * 百度主动推送
     */
    function bdUrls($listztautopush,$ids){
    $url = urldecode($listztautopush);
    $rowbd = M('weapp')->where('code','eq','Pushall')->find();
    $rowbd = json_decode($rowbd['data'],true);
    $urls = preg_split("/,/",$url);
    $request = Request::instance();
    //以下代码直接从百度站长工具里复制过来
    $api = 'http://data.zz.baidu.com/urls?site='.$request->domain().'&token='.htmlspecialchars_decode($rowbd['baidutoken']);
    $ch = curl_init();
        $options = array(
            CURLOPT_URL =>$api,
            CURLOPT_POST =>true,
            CURLOPT_RETURNTRANSFER =>true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_POSTFIELDS =>implode("\n", $urls),
            CURLOPT_HTTPHEADER =>array('Content-Type: text/plain'),
        );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从百度站长工具里复制过来


    if (isset($res['error'])) {
        $pushfankuijg = "推送失败：".$res['message']." 错误代码：".$res['error'];
    } else {
             for($i=0;$i<count($ids);$i++){

                $insertaId = DB::name('weapp_pushall');

            if($insertaId->where(array("aid"=>$ids[$i]))->count()){
                //存在
                DB::name('weapp_pushall')->where(array('aid'=>$ids[$i]))->update(array('aid'=>$ids[$i],'baidupushzt'=>'1',),true);
            }else{
                //不存在
                DB::name('weapp_pushall')->insert(array('aid'=>$ids[$i],'baidupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".$res['success']."条，今天还可推送".$res['remain']."条";
    }
    return $pushfankuijg;
}
    /**
     * 百度快速收录推送
     */
    function bdksUrls($listztautopush,$ids){
    $url = urldecode($listztautopush);
    $rowbd = M('weapp')->where('code','eq','Pushall')->find();
    $rowbd = json_decode($rowbd['data'],true);
    $urls = preg_split("/,/",$url);
    $request = Request::instance();
    //以下代码直接从百度站长工具里复制过来
    $api = 'http://data.zz.baidu.com/urls?site='.$request->domain().'&token='.htmlspecialchars_decode($rowbd['baidutoken'].'&type=daily');
    $ch = curl_init();
        $options = array(
            CURLOPT_URL =>$api,
            CURLOPT_POST =>true,
            CURLOPT_RETURNTRANSFER =>true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_POSTFIELDS =>implode("\n", $urls),
            CURLOPT_HTTPHEADER =>array('Content-Type: text/plain'),

        );
        curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从百度站长工具里复制过来


    if (isset($res['error'])) {
        $pushfankuijg = "推送失败：".$res['message']." 错误代码：".$res['error'];
    } else {

             for($i=0;$i<count($ids);$i++){

                $insertaId = DB::name('weapp_pushall');

            if($insertaId->where(array("aid"=>$ids[$i]))->count()){
                //存在
                $insertaId = DB::name('weapp_pushall')->where(array('aid'=>$ids[$i]))->update(array('aid'=>$ids[$i],'baidupushzt'=>'1',),true);
            }else{
                //不存在
                $insertaId = DB::name('weapp_pushall')->insert(array('aid'=>$ids[$i],'baidupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".$res['success']."条，今天还可推送".$res['remain']."条";
    }
    return $pushfankuijg;
}
    /**
     * 神马主动推送
     */
    function smUrls($listztautopush,$ids){
    $url = urldecode($listztautopush);
    $rowsm = M('weapp')->where('code','eq','Pushall')->find();
    $rowsm = json_decode($rowsm['data'],true);
    $urls = preg_split("/,/",$url);

    //以下代码直接从神马站长工具里复制过来
    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST => FALSE,
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['returnCode']==200) {

            for($i=0;$i<count($ids);$i++){

            $insertaId = DB::name('weapp_pushall');

            if($insertaId->where(array("aid"=>$ids[$i]))->count()){
                //存在
            $insertaId = DB::name('weapp_pushall')->where(array('aid'=>$ids[$i]))->update(array('aid'=>$ids[$i],'shenmapushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('weapp_pushall')->insert(array('aid'=>$ids[$i],'shenmapushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($ids)."条！返回代码：".$res['returnCode'];
    }
    else {
        if ($res['returnCode']==201) {
            $returnCode='token不合法';
        }
        elseif ($res['returnCode']==202) {
            $returnCode='当日额度已用完';
        }
        elseif ($res['returnCode']==400) {
            $returnCode='请求参数错误';
        }
        elseif ($res['returnCode']==500) {
            $returnCode='服务器内部错误';
        }
        else {
            $returnCode='';
        }
        $pushfankuijg = "推送失败：".$returnCode."！ 错误代码：".$res['returnCode'];
    }
    return $pushfankuijg;
}
    /**
     * 搜狗主动推送
     */
    function sgUrls($listztautopush,$ids){
    $url = urldecode($listztautopush);
    $rowsg = M('weapp')->where('code','eq','Pushall')->find();
    $rowsg = json_decode($rowsg['data'],true);
    $urls = strtr($url, ',', "\n");
    $request = Request::instance();
    //搜狗新接口提交格式
    $posts = array(
        'urls' =>$urls,
        'url' =>'',
        'code' =>$rowsg['sogoucode'],
        'site_id' => $rowsg['sogouid'],
        'site_address' => $request->host(),
        'urlSubFlag' =>true,
    );

    $posts = json_encode($posts,true);
    $posts = str_replace('\/','/',$posts);
    $cookie = htmlspecialchars_decode($rowsg['sogoucookie']);

    //以下代码直接从神马站长工具里复制过来    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $headers = array(
                    "Content-Type: application/json;charset=utf-8",
                    "Host: zhanzhang.sogou.com",
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0",
                    "Accept: application/json, text/plain, */*",
                    "Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2",
                    "Accept-Encoding: gzip, deflate",
                    "Origin: http://zhanzhang.sogou.com",
                    "Referer: http://zhanzhang.sogou.com/index.php/sitelink/index",
                    );
    $api = "https://zhanzhang.sogou.com/api/feedback/addMultiShensu";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posts);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $result = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['code']==0) {

            for($i=0;$i<count($ids);$i++){

            $insertaId = DB::name('weapp_pushall');

            if($insertaId->where(array("aid"=>$ids[$i]))->count()){
                //存在
            $insertaId = DB::name('weapp_pushall')->where(array('aid'=>$ids[$i]))->update(array('aid'=>$ids[$i],'sogoupushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('weapp_pushall')->insert(array('aid'=>$ids[$i],'sogoupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($ids)."条！返回代码：".$res['msg'];
    }
    elseif ($res['code']==1) {
            $returnCode="返回信息：".$res['msg'].'！';

        $pushfankuijg="推送失败：".$returnCode."！";
    }
    else {
            $returnCode="返回信息：".$res['msg'].'！';

        $pushfankuijg="推送失败：".$returnCode."！请稍后再推送！";
    }

    return $pushfankuijg;
}
    /**
     * 头条主动推送
     */
    function ttUrls($listztautopush,$ids){
    $url = urldecode($listztautopush);
    $rowtt = M('weapp')->where('code','eq','Pushall')->find();
    $rowtt = json_decode($rowtt['data'],true);
    $urls = str_replace(',', '","',$url);
    $post = '{"site_id":'.$rowtt['toutiaoid'].',"urls":["'.$urls.'"],"frequency":86400,"submitMethods":"url"}';

    $cookie = htmlspecialchars_decode($rowtt['toutiaocookie']);


    //以下代码直接从神马站长工具里复制过来    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $api = "https://zhanzhang.toutiao.com/webmaster/api/link/create";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post))
        );
    $result = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['message']=='success') {

            for($i=0;$i<count($ids);$i++){

            $insertaId = DB::name('weapp_pushall');

            if($insertaId->where(array("aid"=>$ids[$i]))->count()){
                //存在
            $insertaId = DB::name('weapp_pushall')->where(array('aid'=>$ids[$i]))->update(array('aid'=>$ids[$i],'toutiaopushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('weapp_pushall')->insert(array('aid'=>$ids[$i],'toutiaopushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($ids)."条！返回代码：".$res['message'];
    }
    else {
            $returnCode='请检查Cookie是否填写或者过期';

        $pushfankuijg = "推送失败：".$returnCode;
    }
    return $pushfankuijg;
}

        if ($sxpushzt==2&&$ks==1) {
        $pushfankuijg = bdksUrls($listztautopush,$ids);
        }
        elseif($sxpushzt==2&&$ks!=1) {
        $pushfankuijg = bdUrls($listztautopush,$ids);
        }
        elseif ($sxpushzt==4) {
        $pushfankuijg = smUrls($listztautopush,$ids);
        }
        elseif ($sxpushzt==6) {
        $pushfankuijg = sgUrls($listztautopush,$ids);
        }
        elseif ($sxpushzt==8) {
        $pushfankuijg = ttUrls($listztautopush,$ids);
        }
        $this->assign('listztautopush', $listztautopush);
        $this->assign('pushfankuijg', $pushfankuijg);

        return $this->fetch('push');
    }

    public function keywordspush(){
        $post_data = ['domain'=>$this->request->host(true)];
        $url       = 'https://www.eyoucms.com/user/ajax_memberplugin.php?action=myplugin';
        $response  = httpRequest2($url, 'POST', $post_data);
        $patten = '/Keywords/';
        $match = preg_match($patten,$response);
        $sxpushzt = input('sxpushzt');
        $rownum = M('weapp')->where('code','eq','Pushall')->find();
        $rownum = json_decode($rownum['data'],true);
        $domain = request()->domain();
        $domainurl = urlencode($domain);
        $list = array();
        $map = array();

        if ($sxpushzt!=null) {
            if ($sxpushzt==0){
                $map['baidupushzt'] = array('neq', 1);
                $map['shenmapushzt'] = array('neq', 1);
                $map['sogoupushzt'] = array('neq', 1);
                $map['toutiaopushzt'] = array('neq', 1);
            }
            elseif ($sxpushzt==1) {$map['baidupushzt'] = array('eq', 1);}
            elseif ($sxpushzt==2) {$map['baidupushzt'] = array('neq', 1);}
            elseif ($sxpushzt==3) {$map['shenmapushzt'] = array('eq', 1);}
            elseif ($sxpushzt==4) {$map['shenmapushzt'] = array('neq', 1);}
            elseif ($sxpushzt==5) {$map['sogoupushzt'] = array('eq', 1);}
            elseif ($sxpushzt==6) {$map['sogoupushzt'] = array('neq', 1);}
            elseif ($sxpushzt==7) {$map['toutiaopushzt'] = array('eq', 1);}
            elseif ($sxpushzt==8) {$map['toutiaopushzt'] = array('neq', 1);}
        }

        $count = Db::name('WeappKeywords')->where($map)->count('id');// 查询满足要求的总记录数

        $pageObj = new Page($count, $rownum['onnum']);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = Db::name('WeappKeywords')->where($map)->order('id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $listzt=$list;
        $this->assign('listzt', $listzt); // 赋值数据集
        foreach ($list as $key => $val) {
                         $listztautopush .= $domain.'/keywords/'.$val["url"].'.html';
                        }
                    $listztautopush = str_replace( "htmlh", "html,h",$listztautopush);
         /**
     * 百度主动推送
     */
    function bdUrls($listztautopush){
    $url = $listztautopush;
    $rowbd = M('weapp')->where('code','eq','Pushall')->find();
    $rowbd = json_decode($rowbd['data'],true);
        preg_match_all ("/aid=(\d+)/", $url, $aid);
    if ($aid[1]==null) {
        preg_match_all ("/(?<=\/)([^\/]+)\.html/", $url, $aid);
    }
    $urls = preg_split("/,/",$url);
    $request = Request::instance();
    //以下代码直接从百度站长工具里复制过来
    $api = 'http://data.zz.baidu.com/urls?site='.$request->domain().'&token='.htmlspecialchars_decode($rowbd['baidutoken']);
    $ch = curl_init();
    $options = array(CURLOPT_URL =>$api, CURLOPT_POST =>true, CURLOPT_RETURNTRANSFER =>true, CURLOPT_POSTFIELDS =>implode("\n", $urls), CURLOPT_HTTPHEADER =>array('Content-Type: text/plain'), );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从百度站长工具里复制过来

    if (isset($res['error'])) {
        $pushfankuijg = "推送失败：".$res['message']." 错误代码：".$res['error'];
    } else {

             for($i=0;$i<count($aid[1]);$i++){

                $insertaId = DB::name('WeappKeywords');

            if($insertaId->where(array("url"=>$aid[1][$i]))->count()){
                //存在
                $insertaId = DB::name('WeappKeywords')->where(array('url'=>$aid[1][$i]))->update(array('url'=>$aid[1][$i],'baidupushzt'=>'1',),true);
            }else{
                //不存在
                $insertaId = DB::name('WeappKeywords')->insert(array('url'=>$aid[1][$i],'baidupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".$res['success']."条，今天还可推送".$res['remain']."条";
    }
    return $pushfankuijg;
}
    /**
     * 百度快速收录推送
     */
    function bdksUrls($listztautopush){
    $url = $listztautopush;
    $rowbd = M('weapp')->where('code','eq','Pushall')->find();
    $rowbd = json_decode($rowbd['data'],true);
        preg_match_all ("/aid=(\d+)/", $url, $aid);
    if ($aid[1]==null) {
        preg_match_all ("/(?<=\/)([^\/]+)\.html/", $url, $aid);
    }
    $urls = preg_split("/,/",$url);
    $request = Request::instance();
    //以下代码直接从百度站长工具里复制过来
    $api = 'http://data.zz.baidu.com/urls?site='.$request->domain().'&token='.htmlspecialchars_decode($rowbd['baidutoken'].'&type=daily');
    $ch = curl_init();
    $options = array(CURLOPT_URL =>$api, CURLOPT_POST =>true, CURLOPT_RETURNTRANSFER =>true, CURLOPT_POSTFIELDS =>implode("\n", $urls), CURLOPT_HTTPHEADER =>array('Content-Type: text/plain'), );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从百度站长工具里复制过来


    if (isset($res['error'])) {
        $pushfankuijg = "推送失败：".$res['message']." 错误代码：".$res['error'];
    } else {

            for($i=0;$i<count($aid[1]);$i++){

                $insertaId = DB::name('WeappKeywords');

            if($insertaId->where(array("url"=>$aid[1][$i]))->count()){
                //存在
                $insertaId = DB::name('WeappKeywords')->where(array('url'=>$aid[1][$i]))->update(array('url'=>$aid[1][$i],'baidupushzt'=>'1',),true);
            }else{
                //不存在
                $insertaId = DB::name('WeappKeywords')->insert(array('url'=>$aid[1][$i],'baidupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".$res['success']."条，今天还可推送".$res['remain']."条";
    }
    return $pushfankuijg;
}
    /**
     * 神马主动推送
     */
    function smUrls($listztautopush){
    $url = $listztautopush;
    $rowsm = M('weapp')->where('code','eq','Pushall')->find();
    $rowsm = json_decode($rowsm['data'],true);
        preg_match_all ("/aid=(\d+)/", $url, $aid);
    if ($aid[1]==null) {
        preg_match_all ("/(?<=\/)([^\/]+)\.html/", $url, $aid);
    }
    $urls = preg_split("/,/",$url);

    //以下代码直接从神马站长工具里复制过来
    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['returnCode']==200) {

            for($i=0;$i<count($aid[1]);$i++){

            $insertaId = DB::name('WeappKeywords');

            if($insertaId->where(array("url"=>$aid[1][$i]))->count()){
                //存在
            $insertaId = DB::name('WeappKeywords')->where(array('url'=>$aid[1][$i]))->update(array('url'=>$aid[1][$i],'shenmapushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('WeappKeywords')->insert(array('url'=>$aid[1][$i],'shenmapushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($aid[1])."条！返回代码：".$res['returnCode'];
    }
    else {
        if ($res['returnCode']==201) {
            $returnCode='token不合法';
        }
        elseif ($res['returnCode']==202) {
            $returnCode='当日额度已用完';
        }
        elseif ($res['returnCode']==400) {
            $returnCode='请求参数错误';
        }
        elseif ($res['returnCode']==500) {
            $returnCode='服务器内部错误';
        }
        else {
            $returnCode='';
        }
        $pushfankuijg = "推送失败：".$aid['2'].$returnCode."！ 错误代码：".$res['returnCode'];
    }
    return $pushfankuijg;
}
    /**
     * 搜狗主动推送
     */
    function sgUrls($listztautopush){
    $url = $listztautopush;
    $rowsg = M('weapp')->where('code','eq','Pushall')->find();
    $rowsg = json_decode($rowsg['data'],true);
        preg_match_all ("/aid=(\d+)/", $url, $aid);
    if ($aid[1]==null) {
        preg_match_all ("/(?<=\/)([^\/]+)\.html/", $url, $aid);
    }
    $urls = strtr($url, ',', "\n");
    $sites = explode(",",$url);
    $posts = array(
        'site_type' => 1,
        'email' =>'53397588@qq.com',
        'urls' =>$urls,
        'reason' =>"",
        'code' =>$rowsg['sogoucode'],
        'sites' =>$sites,
        );

    $posts = json_encode($posts,true);
    $posts = str_replace('\/','/',$posts);
    $cookie = htmlspecialchars_decode($rowsg['sogoucookie']);

    //以下代码直接从神马站长工具里复制过来    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $headers = array(
                    "Content-Type: application/json;charset=utf-8",
                    "Host: zhanzhang.sogou.com",
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0",
                    "Accept: application/json, text/plain, */*",
                    "Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2",
                    "Accept-Encoding: gzip, deflate",
                    "Origin: http://zhanzhang.sogou.com",
                    "Referer: http://zhanzhang.sogou.com/index.php/sitelink/index",
                    );
    $api = "http://zhanzhang.sogou.com/api/feedback/addMultiShensu";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posts);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $result = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['code']==0) {

            for($i=0;$i<count($aid[1]);$i++){

            $insertaId = DB::name('WeappKeywords');

            if($insertaId->where(array("url"=>$aid[1][$i]))->count()){
                //存在
            $insertaId = DB::name('WeappKeywords')->where(array('url'=>$aid[1][$i]))->update(array('url'=>$aid[1][$i],'sogoupushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('WeappKeywords')->insert(array('url'=>$aid[1][$i],'sogoupushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($aid[1])."条！返回代码：".$res['msg'];
    }
    elseif ($res['code']==1) {
            $returnCode="返回信息：".$res['msg'].'！';

        $pushfankuijg="推送失败：".$returnCode."！";
    }
    else {
            $returnCode="返回信息：".$res['msg'].'！';

        $pushfankuijg="推送失败：".$returnCode."！请稍后再推送！";
    }

    return $pushfankuijg;
}
    /**
     * 头条主动推送
     */
    function ttUrls($listztautopush){
    $url = $listztautopush;
    $rowtt = M('weapp')->where('code','eq','Pushall')->find();
    $rowtt = json_decode($rowtt['data'],true);
        preg_match_all ("/aid=(\d+)/", $url, $aid);
    if ($aid[1]==null) {
        preg_match_all ("/(?<=\/)([^\/]+)\.html/", $url, $aid);
    }
    $urls = str_replace(',', '","',$url);
    $post = '{"site_id":'.$rowtt['toutiaoid'].',"urls":["'.$urls.'"],"frequency":86400,"submitMethods":"url"}';

    $cookie = htmlspecialchars_decode($rowtt['toutiaocookie']);


    //以下代码直接从神马站长工具里复制过来    $api = htmlspecialchars_decode($rowsm['shenmatoken']);
    $api = "https://zhanzhang.toutiao.com/webmaster/api/link/create";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post))
        );
    $result = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($result, true);
    //以上代码直接从神马站长工具里复制过来


    if ($res['message']=='success') {

            for($i=0;$i<count($aid[1]);$i++){

            $insertaId = DB::name('WeappKeywords');

            if($insertaId->where(array("url"=>$aid[1][$i]))->count()){
                //存在
            $insertaId = DB::name('WeappKeywords')->where(array('url'=>$aid[1][$i]))->update(array('url'=>$aid[1][$i],'toutiaopushzt'=>'1',),true);
            }else{
                //不存在
            $insertaId = DB::name('WeappKeywords')->insert(array('url'=>$aid[1][$i],'toutiaopushzt'=>'1',),true);

            }
            }
        $pushfankuijg = "成功推送".count($aid[1])."条！返回代码：".$res['message'];
    }
    else {
            $returnCode='请检查Cookie是否填写或者过期';

        $pushfankuijg = "推送失败：".$returnCode;
    }
    return $pushfankuijg;
}

        if ($sxpushzt==2&&$ks==1) {
        $pushfankuijg = bdksUrls($listztautopush);
        }
        elseif($sxpushzt==2&&$ks!=1) {
        $pushfankuijg = bdUrls($listztautopush);
        }
        elseif ($sxpushzt==4) {
        $pushfankuijg = smUrls($listztautopush);
        }
        elseif ($sxpushzt==6) {
        $pushfankuijg = sgUrls($listztautopush);
        }
        elseif ($sxpushzt==8) {
        $pushfankuijg = ttUrls($listztautopush);
        }

        $this->assign('domain', $domain); // 赋值数据集
        $this->assign('domainurl', $domainurl); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象
        $this->assign('listztautopush', $listztautopush);
        $this->assign('pushfankuijg', $pushfankuijg);

        return $this->fetch('keywordspush');

    }

    public function sitemap() {

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $count = Db::name('archives')->count('aid');
        $tagsCount = Db::name('tagindex')->count('id');
        $pageNum = ceil($count / $row['sitemapnum'])+1;
        $tagPageNum = ceil($tagsCount / $row['sitemapnum'])+1;
        $sitemap = '<?xml version="1.0" encoding="utf-8"?>';
        $sitemap .= '<sitemapindex>';
        for ($i=1; $i < $pageNum; $i++) {
        $sitemap .= '<sitemap>';
            $sitemap .= '<loc>' . $request->domain() . '/xml/' . $i . '.xml' . '</loc>';
            $sitemap .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
        $sitemap .= '</sitemap>';
        }
        for ($i=1; $i < $tagPageNum; $i++) {
        $sitemap .= '<sitemap>';
            $sitemap .= '<loc>' . $request->domain() . '/tagXml/' . $i . '.xml' . '</loc>';
            $sitemap .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
        $sitemap .= '</sitemap>';
        }
        $sitemap .= '</sitemapindex>';

        return Response::create($sitemap)->contentType('text/xml');;
    }

    public function xml() {

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $page = input('param.id');
        $page = $page ? $page : 1;
        if ($page==1) {

            $page=$page-1;
        }
        else {
            $page=($page-1)*$row['sitemapnum'];
        }
        $request = Request::instance();

        $rownum = M('weapp')->where('code','eq','Pushall')->find();
        $rownum = json_decode($rownum['data'],true);
        $urlms = $rownum['urlms'];


        /**
     * 获取栏目链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     */
    function get_typeurl($arctype_info = array(), $admin = true)
    {
        static $domain = null;
        null === $domain && $domain = request()->domain();

        /*兼容采集没有归属栏目的文档*/
        if (empty($arctype_info['current_channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arctype_info = array_merge($arctype_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arctype_info['current_channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if (2 == $seo_pseudo && $admin) {
            static $lang = null;
            null === $lang && $lang = input('param.lang/s', 'cn');
            $typeurl = ROOT_DIR."/index.php?m=home&c=Lists&a=index&tid={$arctype_info['id']}&lang={$lang}&t=".getTime();
        } else {
            $typeurl = typeurl("home/{$ctl_name}/lists", $arctype_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $typeurl = auto_hide_index($typeurl);
        }

        return $typeurl;
    }
        /**
     * 获取文档链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     */
    function get_arcurl($arcview_info = array(), $admin = true)
    {
        static $domain = null;
        null === $domain && $domain = request()->domain();

        /*兼容采集没有归属栏目的文档*/
        if (empty($arcview_info['channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arcview_info = array_merge($arcview_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arcview_info['channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if ($admin) {
            if (2 == $seo_pseudo) {
                static $lang = null;
                null === $lang && $lang = input('param.lang/s', 'cn');
                $arcurl = ROOT_DIR."/index.php?m=home&c=View&a=index&aid={$arcview_info['aid']}&lang={$lang}&admin_id=".session('admin_id')."&t=".getTime();
            } else {
                $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
                // 自动隐藏index.php入口文件
                $arcurl = auto_hide_index($arcurl);
            }
        } else {
            $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $arcurl = auto_hide_index($arcurl);
        }

        return $arcurl;
    }

        //$itemList = Db::name('archives')->limit($page,'200')->order('update_time desc')->select();
        $assign_data = array();
        $condition = array();
        // 获取到所有URL参数
        $param = input('param.');
        $flag = input('flag/s');
        $typeid = input('typeid/d', 0);
        $d2ViX2lzX2F1 = tpCache('web.'.$this->arrJoinStr(['d2ViX2lzX2F1','dGhvcnRva2Vu']));

        /*跳转到指定栏目的文档列表*/
        if (0 < intval($typeid)) {
            $row = Db::name('arctype')
                ->alias('a')
                ->field('b.ctl_name,b.id')
                ->join('__CHANNELTYPE__ b', 'a.current_channel = b.id', 'LEFT')
                ->where('a.id', 'eq', $typeid)
                ->find();
            $ctl_name = $row['ctl_name'];
            $current_channel = $row['id'];
            if (6 == $current_channel) {
                $gourl = url('Arctype/single_edit', array('typeid'=>$typeid));
                $gourl = url("Arctype/single_edit", array('typeid'=>$typeid,'gourl'=>$gourl));
                $this->redirect($gourl);
            } else if (8 == $current_channel) {
                $gourl = url("Guestbook/index", array('typeid'=>$typeid));
                $this->redirect($gourl);
            } else if (5 == $current_channel) {
                if (-1 == $d2ViX2lzX2F1) {
                    $this->error(base64_decode('6KeG6aKR5qih5Z6L5LuF6ZmQ5LqO5o6I5p2D5Z+f5ZCN77yB'));
                }
            }
        }
        /*--end*/

        // 应用搜索条件
        foreach (['keywords','typeid','flag','is_release'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'typeid') {
                    $typeid = $param[$key];
                    $hasRow = model('Arctype')->getHasChildren($typeid);
                    $typeids = get_arr_column($hasRow, 'id');
                    /*权限控制 by 小虎哥*/
                    $admin_info = session('admin_info');
                    if (0 < intval($admin_info['role_id'])) {
                        $auth_role_info = $admin_info['auth_role_info'];
                        if(! empty($auth_role_info)){
                            if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                                $condition['a.admin_id'] = $admin_info['admin_id'];
                            }
                            if(! empty($auth_role_info['permission']['arctype'])){
                                if (!empty($typeid)) {
                                    $typeids = array_intersect($typeids, $auth_role_info['permission']['arctype']);
                                }
                            }
                        }
                    }
                    /*--end*/
                    $condition['a.typeid'] = array('IN', $typeids);
                } else if ($key == 'flag') {
                    if ('is_release' == $param[$key]) {
                        $condition['a.users_id'] = array('gt', 0);
                    } else {
                        $condition['a.'.$param[$key]] = array('eq', 1);
                    }
                // } else if ($key == 'is_release') {
                //     if (0 < intval($param[$key])) {
                //         $condition['a.users_id'] = array('gt', intval($param[$key]));
                //     }
                } else {
                    $condition['a.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        /*权限控制 by 小虎哥*/
        if (empty($typeid)) {
            $typeids = [];
            $admin_info = session('admin_info');
            if (0 < intval($admin_info['role_id'])) {
                $auth_role_info = $admin_info['auth_role_info'];
                if(! empty($auth_role_info)){
                    if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                        $condition['a.admin_id'] = $admin_info['admin_id'];
                    }
                    if(! empty($auth_role_info['permission']['arctype'])){
                        $typeids = $auth_role_info['permission']['arctype'];
                    }
                }
            }
            if (!empty($typeids)) {
                $condition['a.typeid'] = array('IN', $typeids);
            }
        }
        /*--end*/

        if (empty($typeid)) {
            $id_tmp = [6,8];
            if (-1 == $d2ViX2lzX2F1) {
                array_push($id_tmp, 5);
            }
            // 只显示允许发布文档的模型，且是开启状态
            $channelIds = Db::name('channeltype')->where('status',0)
                ->whereOr('id','IN',$id_tmp)->column('id');
            $condition['a.channel'] = array('NOT IN', $channelIds);
        } else {
            // 只显示当前栏目对应模型下的文档
            $current_channel = Db::name('arctype')->where('id',$typeid)->getField('current_channel');
            $condition['a.channel'] = array('eq', $current_channel);
        }

        /*多语言*/
        $condition['a.lang'] = array('eq', $this->admin_lang);
        /*回收站数据不显示*/
        $condition['a.is_del'] = array('eq', 0);
        /*--end*/

        /*未审核数据不显示*/
        $condition['a.arcrank'] = array('eq', 0);
        /*--end*/
        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        $list = DB::name('archives')
            ->field("a.aid,a.channel")
            ->alias('a')
            ->where($condition)
            ->order('update_time desc')
            ->limit($page,$row['sitemapnum'])
            ->getAllWithIndex('aid');
        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        if ($list) {
            $aids = array_keys($list);
            $fields = "b.*, a.*, a.aid as aid";
            $row = DB::name('archives')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids)
                ->getAllWithIndex('aid');

            /*获取当页文档的所有模型*/
            $channelIds = get_arr_column($list, 'channel');
            $channelRow = Db::name('channeltype')->field('id, ctl_name, ifsystem')
                ->where('id','IN',$channelIds)
                ->getAllWithIndex('id');
            $assign_data['channelRow'] = $channelRow;
            /*--end*/

            foreach ($list as $key => $val) {
                $row[$val['aid']]['arcurl'] = get_arcurl($row[$val['aid']], false);
                $row[$val['aid']]['litpic'] = handle_subdir_pic($row[$val['aid']]['litpic']); // 支持子目录
                $list[$key] = $row[$val['aid']];
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        if ($page == 0) {
            $xml .= '<url>';
            $xml .= '<loc>' . $request->domain() . '/' . '</loc>';
            $xml .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>1.0</priority>';
            $xml .= '</url>';
                $fenleiList = Db::name('arctype')->where("lang = 'cn' AND is_hidden = 0 AND is_del = 0 AND status = 1")->order('id desc')->select();
                foreach($fenleiList as $k => $v) {
                    $xml .= '<url>';
                    $xml .= '<loc>' .htmlspecialchars(get_typeurl($v, false)) . '</loc>';

                    $xml .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
                    $xml .= '<changefreq>daily</changefreq>';
                    $xml .= '<priority>0.9</priority>';
                    $xml .= '</url>';

            }
        }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
        foreach($list as $k => $v) {
            $url=$v['arcurl'];
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($url).'</loc>';
            $xml .= '<lastmod>'.date("Y-m-d",$v["update_time"]).'</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';


        return Response::create($xml)->contentType('text/xml');
        //$this->assign('fenleiList', $fenleiList); // 赋值数据集
        //return $this->fetch('index');
    }

    public function tagXml() {
        function get_tagurl($tagid = '')
    {
        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        $tagurl = tagurl("home/Tags/lists", ['tagid'=>$tagid], true, true, $seo_pseudo, $seo_dynamic_format);
        // 自动隐藏index.php入口文件
        $tagurl = auto_hide_index($tagurl);

        return $tagurl;
    }

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $page = input('param.id');
        $page = $page ? $page : 1;
        if ($page==1) {
            $page=$page-1;
        }
        else {
            $page=($page-1)*$row['sitemapnum'];
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        $tagsList = Db::name('tagindex')->limit($page,$row['sitemapnum'])->order('add_time desc')->select();
        if ($tagsList) {
            foreach ($tagsList as $key => $val) {
                $tagsList[$key]['id'] = $val['id'];
                $tagsList[$key]['time'] = $val['add_time'] ? date("Y-m-d", $val["add_time"]) : date("Y-m-d");
            }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
            foreach ($tagsList as $key => $val) {
                $url=get_tagurl($val['id']);
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($url) . '</loc>';
                $xml .= '<lastmod>' . $tagsList[$key]['time'] . '</lastmod>';
                $xml .= '<changefreq>daily</changefreq>';
                $xml .= '<priority>0.9</priority>';
                $xml .= '</url>';
            }
        }
        $xml .= '</urlset>';
        return Response::create($xml)->contentType('text/xml');;
        //return $this->fetch('index');
    }

    public function BaiduXml() {
        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $page = input('param.id');
        $page = $page ? $page : 1;
        if ($page==1) {

            $page=$page-1;
        }
        else {
            $page=($page-1)*($row['sitemapnum']*4);
        }
        $request = Request::instance();

        $rownum = M('weapp')->where('code','eq','Pushall')->find();
        $rownum = json_decode($rownum['data'],true);
        $urlms = $rownum['urlms'];


        /**
     * 获取栏目链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     */
    function get_typeurl($arctype_info = array(), $admin = true)
    {
        static $domain = null;
        null === $domain && $domain = request()->domain();

        /*兼容采集没有归属栏目的文档*/
        if (empty($arctype_info['current_channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arctype_info = array_merge($arctype_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arctype_info['current_channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if (2 == $seo_pseudo && $admin) {
            static $lang = null;
            null === $lang && $lang = input('param.lang/s', 'cn');
            $typeurl = ROOT_DIR."/index.php?m=home&c=Lists&a=index&tid={$arctype_info['id']}&lang={$lang}&t=".getTime();
        } else {
            $typeurl = typeurl("home/{$ctl_name}/lists", $arctype_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $typeurl = auto_hide_index($typeurl);
        }

        return $typeurl;
    }
        /**
     * 获取文档链接
     *
     * @param array $arctype_info 栏目信息
     * @param boolean $admin 后台访问链接，还是前台链接
     */
    function get_arcurl($arcview_info = array(), $admin = true)
    {
        static $domain = null;
        null === $domain && $domain = request()->domain();

        /*兼容采集没有归属栏目的文档*/
        if (empty($arcview_info['channel'])) {
            $channelRow = \think\Db::name('channeltype')->field('id as channel')
                ->where('id',1)
                ->find();
            $arcview_info = array_merge($arcview_info, $channelRow);
        }
        /*--end*/

        static $result = null;
        null === $result && $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
        $ctl_name = '';
        if ($result) {
            $ctl_name = $result[$arcview_info['channel']]['ctl_name'];
        }

        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        if ($admin) {
            if (2 == $seo_pseudo) {
                static $lang = null;
                null === $lang && $lang = input('param.lang/s', 'cn');
                $arcurl = ROOT_DIR."/index.php?m=home&c=View&a=index&aid={$arcview_info['aid']}&lang={$lang}&admin_id=".session('admin_id')."&t=".getTime();
            } else {
                $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
                // 自动隐藏index.php入口文件
                $arcurl = auto_hide_index($arcurl);
            }
        } else {
            $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, $domain, $seo_pseudo, $seo_dynamic_format);
            // 自动隐藏index.php入口文件
            $arcurl = auto_hide_index($arcurl);
        }

        return $arcurl;
    }

        //$itemList = Db::name('archives')->limit($page,'200')->order('update_time desc')->select();
        $assign_data = array();
        $condition = array();
        // 获取到所有URL参数
        $param = input('param.');
        $flag = input('flag/s');
        $typeid = input('typeid/d', 0);
        $d2ViX2lzX2F1 = tpCache('web.'.$this->arrJoinStr(['d2ViX2lzX2F1','dGhvcnRva2Vu']));

        /*跳转到指定栏目的文档列表*/
        if (0 < intval($typeid)) {
            $row = Db::name('arctype')
                ->alias('a')
                ->field('b.ctl_name,b.id')
                ->join('__CHANNELTYPE__ b', 'a.current_channel = b.id', 'LEFT')
                ->where('a.id', 'eq', $typeid)
                ->find();
            $ctl_name = $row['ctl_name'];
            $current_channel = $row['id'];
            if (6 == $current_channel) {
                $gourl = url('Arctype/single_edit', array('typeid'=>$typeid));
                $gourl = url("Arctype/single_edit", array('typeid'=>$typeid,'gourl'=>$gourl));
                $this->redirect($gourl);
            } else if (8 == $current_channel) {
                $gourl = url("Guestbook/index", array('typeid'=>$typeid));
                $this->redirect($gourl);
            } else if (5 == $current_channel) {
                if (-1 == $d2ViX2lzX2F1) {
                    $this->error(base64_decode('6KeG6aKR5qih5Z6L5LuF6ZmQ5LqO5o6I5p2D5Z+f5ZCN77yB'));
                }
            }
        }
        /*--end*/

        // 应用搜索条件
        foreach (['keywords','typeid','flag','is_release'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'typeid') {
                    $typeid = $param[$key];
                    $hasRow = model('Arctype')->getHasChildren($typeid);
                    $typeids = get_arr_column($hasRow, 'id');
                    /*权限控制 by 小虎哥*/
                    $admin_info = session('admin_info');
                    if (0 < intval($admin_info['role_id'])) {
                        $auth_role_info = $admin_info['auth_role_info'];
                        if(! empty($auth_role_info)){
                            if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                                $condition['a.admin_id'] = $admin_info['admin_id'];
                            }
                            if(! empty($auth_role_info['permission']['arctype'])){
                                if (!empty($typeid)) {
                                    $typeids = array_intersect($typeids, $auth_role_info['permission']['arctype']);
                                }
                            }
                        }
                    }
                    /*--end*/
                    $condition['a.typeid'] = array('IN', $typeids);
                } else if ($key == 'flag') {
                    if ('is_release' == $param[$key]) {
                        $condition['a.users_id'] = array('gt', 0);
                    } else {
                        $condition['a.'.$param[$key]] = array('eq', 1);
                    }
                // } else if ($key == 'is_release') {
                //     if (0 < intval($param[$key])) {
                //         $condition['a.users_id'] = array('gt', intval($param[$key]));
                //     }
                } else {
                    $condition['a.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        /*权限控制 by 小虎哥*/
        if (empty($typeid)) {
            $typeids = [];
            $admin_info = session('admin_info');
            if (0 < intval($admin_info['role_id'])) {
                $auth_role_info = $admin_info['auth_role_info'];
                if(! empty($auth_role_info)){
                    if(isset($auth_role_info['only_oneself']) && 1 == $auth_role_info['only_oneself']){
                        $condition['a.admin_id'] = $admin_info['admin_id'];
                    }
                    if(! empty($auth_role_info['permission']['arctype'])){
                        $typeids = $auth_role_info['permission']['arctype'];
                    }
                }
            }
            if (!empty($typeids)) {
                $condition['a.typeid'] = array('IN', $typeids);
            }
        }
        /*--end*/

        if (empty($typeid)) {
            $id_tmp = [6,8];
            if (-1 == $d2ViX2lzX2F1) {
                array_push($id_tmp, 5);
            }
            // 只显示允许发布文档的模型，且是开启状态
            $channelIds = Db::name('channeltype')->where('status',0)
                ->whereOr('id','IN',$id_tmp)->column('id');
            $condition['a.channel'] = array('NOT IN', $channelIds);
        } else {
            // 只显示当前栏目对应模型下的文档
            $current_channel = Db::name('arctype')->where('id',$typeid)->getField('current_channel');
            $condition['a.channel'] = array('eq', $current_channel);
        }

        /*多语言*/
        $condition['a.lang'] = array('eq', $this->admin_lang);
        /*回收站数据不显示*/
        $condition['a.is_del'] = array('eq', 0);
        /*--end*/

        /*未审核数据不显示*/
        $condition['a.arcrank'] = array('eq', 0);
        /*--end*/
        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        $list = DB::name('archives')
            ->field("a.aid,a.channel")
            ->alias('a')
            ->where($condition)
            ->order('update_time desc')
            ->limit($page,($row['sitemapnum']*4))
            ->getAllWithIndex('aid');
        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        if ($list) {
            $aids = array_keys($list);
            $fields = "b.*, a.*, a.aid as aid";
            $row = DB::name('archives')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids)
                ->getAllWithIndex('aid');

            /*获取当页文档的所有模型*/
            $channelIds = get_arr_column($list, 'channel');
            $channelRow = Db::name('channeltype')->field('id, ctl_name, ifsystem')
                ->where('id','IN',$channelIds)
                ->getAllWithIndex('id');
            $assign_data['channelRow'] = $channelRow;
            /*--end*/

            foreach ($list as $key => $val) {
                $row[$val['aid']]['arcurl'] = get_arcurl($row[$val['aid']], false);
                $row[$val['aid']]['litpic'] = handle_subdir_pic($row[$val['aid']]['litpic']); // 支持子目录
                $list[$key] = $row[$val['aid']];
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        if ($page == 0) {
            $xml .= '<url>';
            $xml .= '<loc>' . $request->domain() . '/' . '</loc>';
            $xml .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>1.0</priority>';
            $xml .= '</url>';
                $fenleiList = Db::name('arctype')->where("lang = 'cn' AND is_hidden = 0 AND is_del = 0 AND status = 1")->order('id desc')->select();
                foreach($fenleiList as $k => $v) {
                    $xml .= '<url>';
                    $xml .= '<loc>' .htmlspecialchars(get_typeurl($v, false)) . '</loc>';

                    $xml .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
                    $xml .= '<changefreq>daily</changefreq>';
                    $xml .= '<priority>0.9</priority>';
                    $xml .= '</url>';

            }
        }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
        foreach($list as $k => $v) {
            $url=$v['arcurl'];
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($url).'</loc>';
            $xml .= '<lastmod>'.date("Y-m-d",$v["update_time"]).'</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';


        return Response::create($xml)->contentType('text/xml');
        //$this->assign('fenleiList', $fenleiList); // 赋值数据集
        //return $this->fetch('index');
    }

    public function BaidutagXml() {

    function get_tagurl($tagid = '')
    {
        static $seo_pseudo = null;
        static $seo_dynamic_format = null;
        if (null === $seo_pseudo || null === $seo_dynamic_format) {
            $seoConfig = tpCache('seo');
            $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
            $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
        }

        $tagurl = tagurl("home/Tags/lists", ['tagid'=>$tagid], true, true, $seo_pseudo, $seo_dynamic_format);
        // 自动隐藏index.php入口文件
        $tagurl = auto_hide_index($tagurl);

        return $tagurl;
    }

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $page = input('param.id');
        $page = $page ? $page : 1;
        if ($page==1) {
            $page=$page-1;
        }
        else {
            $page=($page-1)*($row['sitemapnum']*4);
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        $tagsList = Db::name('tagindex')->limit($page,($row['sitemapnum']*4))->order('add_time desc')->select();
        if ($tagsList) {
            foreach ($tagsList as $key => $val) {
                $tagsList[$key]['tid'] = $val['tid'];
                $tagsList[$key]['time'] = $val['add_time'] ? date("Y-m-d", $val["add_time"]) : date("Y-m-d");
            }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
            foreach ($tagsList as $key => $val) {
                    $url=get_tagurl($val['id']);

                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($url) . '</loc>';
                $xml .= '<lastmod>' . $tagsList[$key]['time'] . '</lastmod>';
                $xml .= '<changefreq>daily</changefreq>';
                $xml .= '<priority>0.9</priority>';
                $xml .= '</url>';
            }
        }
        $xml .= '</urlset>';
        return Response::create($xml)->contentType('text/xml');;
        //return $this->fetch('index');
    }

    public function keywords() {

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $keywordscount = Db::name('WeappKeywords')->count('id');
        $pageNum = ceil($keywordscount / $row['sitemapnum'])+1;
        $sitemap = '<?xml version="1.0" encoding="utf-8"?>';
        $sitemap .= '<sitemapindex>';
        for ($i=1; $i < $pageNum; $i++) {
        $sitemap .= '<sitemap>';
            $sitemap .= '<loc>' . $request->domain() . '/keywordsXml/' . $i . '.xml' . '</loc>';
            $sitemap .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
        $sitemap .= '</sitemap>';
        }
        $sitemap .= '</sitemapindex>';

        return Response::create($sitemap)->contentType('text/xml');;
    }

    public function keywordsXml() {
        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $page = input('param.id');
        $page = $page ? $page : 1;
        $domin = $request->domain();
        if ($page==1) {
            $page=$page-1;
        }
        else {
            $page=($page-1)*$row['sitemapnum'];
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        $KeywordsList = Db::name('WeappKeywords')->limit($page,$row['sitemapnum'])->order('add_time desc')->select();
        if ($KeywordsList) {
            foreach ($KeywordsList as $key => $val) {
                $KeywordsList[$key]['id'] = $val['id'];
            }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
            foreach ($KeywordsList as $key => $val) {

                $xml .= '<url>';
                $xml .= '<loc>' .$domin.'/keywords/'. $KeywordsList[$key]['url'] . '.html</loc>';
                $xml .= '<lastmod>' .  date("Y-m-d") . '</lastmod>';
                $xml .= '<changefreq>daily</changefreq>';
                $xml .= '<priority>0.9</priority>';
                $xml .= '</url>';
            }
        }
        $xml .= '</urlset>';
        return Response::create($xml)->contentType('text/xml');;
        //return $this->fetch('index');
    }

    public function BaidukeywordsXml() {

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $page = input('param.id');
        $page = $page ? $page : 1;
        $domin = $request->domain();
        if ($page==1) {
            $page=$page-1;
        }
        else {
            $page=($page-1)*($row['sitemapnum']*4);
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<urlset>';
        $KeywordsList = Db::name('WeappKeywords')->limit($page,($row['sitemapnum']*4))->order('id desc')->select();
        if ($KeywordsList) {
            foreach ($KeywordsList as $key => $val) {
                $KeywordsList[$key]['tid'] = $val['tid'];
                $KeywordsList[$key]['time'] = $val['add_time'] ? date("Y-m-d", $val["add_time"]) : date("Y-m-d");
            }
        $rowurlnum = Db::name('weapp')->where('code','eq','Pushall')->find();
        $rowadd = json_decode($rowurlnum['data'],true);
        $urlms = $rowadd['urlms'];
            foreach ($KeywordsList as $key => $val) {

                $xml .= '<url>';
                $xml .= '<loc>' .$domin.'/keywords/'. $KeywordsList[$key]['url'] . '.html</loc>';
                $xml .= '<lastmod>' .  date("Y-m-d") . '</lastmod>';
                $xml .= '<changefreq>daily</changefreq>';
                $xml .= '<priority>0.9</priority>';
                $xml .= '</url>';
            }
        }
        $xml .= '</urlset>';
        return Response::create($xml)->contentType('text/xml');;
        //return $this->fetch('index');
    }

    public function BaiduPro() {

        $row = M('weapp')->where('code','eq','Pushall')->find();
        $row = json_decode($row['data'],true);
        $request = Request::instance();
        $domin = $request->domain();
            $count = Db::name('archives')->count('aid');
            $pageNum = ceil($count / ($row['sitemapnum']*4))+1;
            $sitemap = '<strong>百度专用全站地图：</strong><br/>';
            for ($i=1; $i < $pageNum; $i++) {
                $sitemap .=  '<a href="'.$request->domain() . '/BaiduXml/' . $i . '.xml" target="_blank">'.$request->domain() . '/BaiduXml/' . $i . '.xml</a><br/>';
            }

            $count = Db::name('tagindex')->count('id');
            $pageNum = ceil($count / ($row['sitemapnum']*4))+1;
            $sitemap .= '<strong>百度专用TAG地图：</strong><br/>';
            for ($i=1; $i < $pageNum; $i++) {
                $sitemap .=  '<a href="'.$request->domain() . '/BaidutagXml/' . $i . '.xml" target="_blank">'.$request->domain() . '/BaidutagXml/' . $i . '.xml</a><br/>';
            }
        $post_data = ['domain'=>$this->request->host(true)];
        $url       = 'https://www.eyoucms.com/user/ajax_memberplugin.php?action=myplugin';
        $response  = httpRequest2($url, 'POST', $post_data);
        $patten = '/Keywords/';
        $match = preg_match($patten,$response);
        if ($match||$post_data['domain']=='127.0.0.1'||$post_data['domain']=='127.0.0.2'||$post_data['domain']=='127.0.0.3'||$post_data['domain']=='127.0.0.4'||$post_data['domain']=='127.0.0.5'||$post_data['domain']=='127.0.0.6'||$post_data['domain']=='127.0.0.7'||$post_data['domain']=='127.0.0.8'||$post_data['domain']=='127.0.0.9'||$post_data['domain']=='127.0.0.10'||$post_data['domain']=='localhost'){
            $keywordscount = Db::name('WeappKeywords')->count('id');
            $pageNum = ceil($keywordscount / ($row['sitemapnum']*4))+1;
            $sitemap .= '<strong>百度专用聚合关键词地图：</strong><br/>';
            for ($i=1; $i < $pageNum; $i++) {
                $sitemap .=  '<a href="'.$request->domain() . '/BaidukeywordsXml/' . $i . '.xml" target="_blank">'.$request->domain() . '/BaidukeywordsXml/' . $i . '.xml</a><br/>';
            }        }
            return Response::create($sitemap)->contentType('text/html');


    }

}