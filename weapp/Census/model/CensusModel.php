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

namespace weapp\Census\model;

use think\Model;

/**
 * 模型
 */
class CensusModel extends Model
{
    /**
     * 数据表名，不带前缀
     */
    public $name = 'weapp_census';
	public $code = 'Census';

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }
	/**
     * 获取数据
     */
    public function getWeappData()
    {
        static $_value = [];
        if (empty($_value[$this->code])) {
            $row = M('weapp')->where('code',$this->code)->find();
            if (!empty($row['data'])&&$row['data']!="[]") {
                $row['data'] = unserialize($row['data']);
            }
            $_value[$this->code] = $row;
        }

        return $_value[$this->code];
    }
}