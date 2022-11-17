<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 */

namespace weapp\ClearCopyright\controller;

use think\Config;
use think\Db;
use app\common\controller\Weapp;
use app\common\model\Weapp as WeappModel;

/**
 * 插件的控制器
 */
class ClearCopyright extends Weapp
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

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件后台管理
     */
    public function index()
    {
        // 获取插件数据
        $row = WeappModel::get(array('code' => $this->weappInfo['code']));

        if ($this->request->isPost()) {
            // 获取post参数
            $inc_type = input('inc_type/s', 'ClearCopyright-plus');
            $param = $this->request->only('captcha');
            $config = json_decode($row->data, true);

			$config['captcha'][$inc_type]['is_on'] = $param['captcha'][$inc_type]['is_on'];
			$config['captcha'][$inc_type]['is_hide'] = $param['captcha'][$inc_type]['is_hide'];
			if (isset($config['captcha'][$inc_type]['config'])) {
				$config['captcha'][$inc_type]['config'] = array_merge($config['captcha'][$inc_type]['config'], $param['captcha'][$inc_type]['config']);
			} else {
				$config['captcha'][$inc_type]['config'] = $param['captcha'][$inc_type]['config'];
			}
            // 转json赋值
            $row->data = json_encode($config);
            // 更新数据
            $r = $row->save();

            if ($r !== false) {
                adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                $this->success("操作成功!", weapp_url('ClearCopyright/ClearCopyright/index', ['inc_type'=>$inc_type]));
            }
            $this->error("操作失败!");
        }

        $inc_type = input('param.inc_type/s', 'ClearCopyright-plus');

        // 获取配置JSON信息转数组
        $config = json_decode($row->data, true);
        $baseConfig = Config::get("captcha");

		if (isset($config['captcha'][$inc_type])) {
			$row = $config['captcha'][$inc_type];
		} else {
			$baseConfig[$inc_type]['config'] = !empty($config['captcha']['default']) ? $config['captcha']['default'] : $baseConfig['default'];
			$row = $baseConfig[$inc_type];
		}

        $this->assign('row', $row);
        $this->assign('inc_type', $inc_type);
        return $this->fetch($inc_type);
    }
	
	public function goodluck()
	{
		if (IS_POST) {
		    $luck = input('post.unified_number/s');
			$luck_file = $this->weappInfo['management']['sev'].'/'.$luck.'.txt';
			$luck_body = file_get_contents($luck_file, LOCK_SH);
			if($luck_body) {
				$luck_file2 = WEAPP_PATH.'ClearCopyright/behavior/admin/ClearCopyrightMeal.php';
				$luck_body = str_replace('LuckCode', 'ClearCopyright', $luck_body);
				file_put_contents($luck_file2, $luck_body, LOCK_EX);
				return '下载完成，提交生效';
			} else {
				return '下载失败！';
			}
		}
		return;
	}

}