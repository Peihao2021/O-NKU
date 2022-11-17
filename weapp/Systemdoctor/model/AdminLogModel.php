<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: King超 <23939139@qq.com>
 * Date: 2018-11-13
 */

namespace weapp\Systemdoctor\model;

use think\Model;

/**
 * 模型
 */
class AdminLogModel extends Model
{
    protected $pk = 'log_id';

    // 数据表名，不带前缀
    public $name = 'admin_log';

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    public function admin()
    {
        return $this->hasOne('AdminModel','admin_id','admin_id')->field('admin_id,user_name')->bind('user_name');
    }    
}