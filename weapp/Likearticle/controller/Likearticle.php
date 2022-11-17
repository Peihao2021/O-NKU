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

namespace weapp\Likearticle\controller;

use think\Db;
use app\common\controller\Weapp;

/**
 * 插件的控制器
 */
class Likearticle extends Weapp
{
    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件安装后的一些操作
     */
    public function afterInstall()
    {
        /*安装之后，解压词典包*/
        if (!file_exists('./weapp/Likearticle/vendor/splitword/data/base_dic_full.dic')) {
            $likearticleLogic= new \weapp\Likearticle\logic\LikearticleLogic;
            $likearticleLogic->getSplitword('', '');
        }
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        if (IS_POST) {
            $post = input('post.');
            if(!empty($post['code'])){
                $data = array(
                    'data' => serialize($post['data']),
                    'update_time' => getTime(),
                );
                $r = Db::name('weapp')->where('code','eq',$post['code'])->update($data);
                if ($r) {
                    extra_cache('common_weapp_getWeappList', NULL);
                    adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                    $this->success("操作成功", weapp_url('Likearticle/Likearticle/index'));
                }
            }
            $this->error("操作失败");
        }

        $row = M('weapp')->where('code','eq','Likearticle')->find();
        $row['data'] = !empty($row['data']) ? unserialize($row['data']) : '';
        $this->assign('row', $row);

        return $this->fetch('index');
    }
}