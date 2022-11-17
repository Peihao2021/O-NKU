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
 * Date: 2019-07-30
 */

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 模型
 */
class AskType extends Model
{
    /**
     * 数据表名，不带前缀
     */
    public $name = 'ask_type';

    /**
     * 插件标识
     */
    public $code = 'Askr';

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }
}