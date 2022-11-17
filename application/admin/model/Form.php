<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022/2/24
 * Time: 15:09
 */

namespace app\admin\model;

use think\Db;
use think\Model;

class Form extends Model
{
// 初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();

    }
    // 获取表单数据(单条\全部)
    public function GetFormData($FormID = null, $FieID = null, $Order = null)
    {
        $FieID = empty($FieID) ? '*' : $FieID;
        $Order = empty($Order) ? 'form_id desc' : $Order;
        // 查询条件
        $where = [
            'lang' => get_admin_lang(),
        ];
        // 执行查询
        if (!empty($FormID)) {
            $where['form_id'] = $FormID;
            $form = Db::name('form')->where($where)->field($FieID)->find();
        } else {
            $form = Db::name('form')->where($where)->field($FieID)->order($Order)->select();
            $form[0]['selected'] = true;
        }

        // 返回结果
        return $form;
    }

    // 更新表单数据
    public function UpdateFormData($post = [])
    {
        // 更新条件
        $where = [
            'lang' => get_admin_lang(),
            'form_id' => $post['form_id']
        ];

        // 更新数据
        $FormData = [
            'form_name'   => $post['form_name'],
            'intro'       => '',
            'status'      => 1,
            'update_time' => getTime()
        ];

        // 执行更新
        $ResultID = $this->where($where)->update($FormData);

        // 返回结果
        return $ResultID;
    }

    // 查询表单名称是否已存在
    public function FormNameDoesItExist($FormName = null, $FormID = null)
    {
        // 查询条件
        $where = [
            'lang' => get_admin_lang(),
            'form_name' => trim($FormName)
        ];

        if (!empty($FormID)) $where['form_id'] = ['NEQ', $FormID];

        // 执行查询
        $FormCount = $this->where($where)->count();

        // 返回结果
        return $FormCount;
    }

    // 查询表单提交的数量
    public function GetFormListCount($form_ids = [])
    {
        // 查询条件
        $where = [
            'lang' => get_admin_lang(),
            'form_id' => ['IN', $form_ids],
        ];

        // 执行查询
        $form_list_count = Db::name('form_list')
            ->field('form_id, count(form_id) AS count')
            ->where($where)
            ->group('form_id')
            ->getAllWithIndex('form_id');

        // 返回结果
        return $form_list_count;
    }
}