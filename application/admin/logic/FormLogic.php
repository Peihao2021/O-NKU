<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022/2/24
 * Time: 15:05
 */

namespace app\admin\logic;

use think\Model;
use think\Db;

class FormLogic extends Model
{
    /**
     * 初始化操作
     */
    public function initialize() {
        parent::initialize();
    }
// 分类封装表单字段数据
    public function GetSaveFormFieldData($post = [], $FormID = null)
    {
        $FormFieldData = [];
        foreach ($post['field_name'] as $key => $value) {
            // 为空则跳过本次循环
            if (empty($value)) continue;
            // 字段ID
            $field_id = !empty($post['field_id'][$key]) ? $post['field_id'][$key] : '';
            // 表单总表ID
            if (!empty($FormID)) {
                $form_id = $FormID;
            } else {
                $form_id = !empty($post['form_id'][$key]) ? $post['form_id'][$key] : '';
            }
            // 字段类型
            $field_type = !empty($post['field_type'][$key]) ? $post['field_type'][$key] : '';
            //是否必填
            $is_fill = !empty($post['is_fill'][$key]) ? $post['is_fill'][$key] : '0';
            // 处理重复及特殊字符
            $field_value = !empty($post['field_value'][$key]) ? $post['field_value'][$key] : '';
            $field_value = $this->FilterRepeaSpecCharact($field_value);
            //特殊处理关联区域
            if ($field_type == 'region'){
                $region_data = [
                    'region_id' => !empty($post['region_id'][$key]) ? $post['region_id'][$key] : '',
                    'region_names' => !empty($post['region_names'][$key]) ? $post['region_names'][$key] : '',
                    'region_ids' => !empty($post['region_ids'][$key]) ? $post['region_ids'][$key] : '',
                ];
                $field_value = serialize($region_data);
            }
            // 表单字段表
            $FormFieldData[$key] = [
                'field_id'    => $field_id,
                'form_id'     => $form_id,
                'field_name'  => $value,
                'field_type'  => $field_type,
                'field_value' => $field_value,
                'is_fill'     => $is_fill,
                'is_default'  => 0,
                'sort_order'  => 100,
                'lang'        => get_admin_lang(),
                'add_time'    => getTime(),
                'update_time' => getTime()
            ];

            // 数据处理
            if (empty($field_id)) {
                unset($FormFieldData[$key]['field_id']);
            } else {
                unset($FormFieldData[$key]['add_time']);
            }
        }

        return $FormFieldData;
    }

    // 处理重复及特殊字符
    public function FilterRepeaSpecCharact($values = '')
    {
        if (!empty($values)) {
            /* 替换特殊字符 */
            $values = str_replace('_', '', $values);
            $values = str_replace('@', '', $values);
            $values = str_replace('，', ',', $values);
            $values = func_preg_replace(['"','\''], '', $values);
            $values = trim($values);
            /* 替换特殊字符 END */

            /* 过滤重复值 */
            $values_arr = explode(PHP_EOL, $values);
            foreach ($values_arr as $kk => $val) {
                $tmp_val = trim($val);
                if (empty($tmp_val)) {
                    unset($values_arr[$kk]);
                    continue;
                }
                $values_arr[$kk] = $tmp_val;
            }
            $values_arr = array_unique($values_arr);
            $values = implode(PHP_EOL, $values_arr);
            /* 过滤重复值 END */
        }
        return $values;
    }

}