<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2022/2/23
 * Time: 16:48
 */

namespace app\admin\controller;

use think\Page;
use think\Db;
use app\admin\logic\FormLogic;

class Form extends Base
{
    private $field_type_list;
    /**
     * 构造方法
     */
    public function _initialize() {
        parent::_initialize();

        // 数据表
        $this->form_db = Db::name('_form');
        $this->form_list_db = Db::name('form_list');
        $this->form_field_db = Db::name('form_field');
        $this->form_value_db = Db::name('form_value');

        // 业务层
        $this->formLogic = new FormLogic;

        // 模型层
        $this->form_model = model('Form');
        $this->form_field_model = model('FormField');
        // 多语言功能操作权限
        $this->language_access();
        $this->field_type_list = config("global.form_field_type_list");
    }
    /*
    * 表单字段 -- 列表
    */
    public function field()
    {
        $assign_data = [];
        // 查询条件
        $condition = [
            'lang' => $this->admin_lang,
            'status' => 1,
        ];
        // 应用搜索条件
        $keywords = input('keywords/s');
        if (!empty($keywords)) $condition['form_name'] = array('LIKE', "%{$keywords}%");

        // 分页查询
        $count = $this->form_db->where($condition)->count();
        $Page = new Page($count, config('paginate.list_rows'));
        $show = $Page->show();
        $assign_data['page'] = $show;
        $assign_data['pager'] = $Page;

        // 数据查询
        $list = $this->form_db
            ->where($condition)
            ->order('form_id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        $assign_data['list'] = $list;
        $form_ids = get_arr_column($list, 'form_id');

        // 查询表单填写数量
        $assign_data['form_list_count'] = $this->form_model->GetFormListCount($form_ids);

        // 查询字段数量
        // $assign_data['form_field_total'] = $this->form_field_model->GetFormFieldTotal($form_ids);

        $this->assign($assign_data);
        return $this->fetch();
    }
    /*
        * 表单字段 -- 新增
        */
    public function field_add()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            if (empty($post['form_name'])) $this->error('请输入表单名称');

            // 查询表单名称是否已存在
            $FormName = $this->form_model->FormNameDoesItExist($post['form_name'], null);
            if (!empty($FormName)) $this->error('表单名称已存在，不可重复添加');

            // 添加表单管理数据
            $FormData = [
                'form_name'   => $post['form_name'],
                'intro'       => '',
                'status'      => 1,
                'lang'        => $this->admin_lang,
                'add_time'    => getTime(),
                'update_time' => getTime()
            ];
            $FormID = $this->form_db->insertGetId($FormData);

            if (!empty($FormID)) {
                /* 处理表单字段数据 */
                if (!empty($post['field_name'])) {
                    // 获取表单字段数据
                    $FormFieldData = $this->formLogic->GetSaveFormFieldData($post, $FormID);
                    // 保存表单字段数据
                    if (!empty($FormFieldData)) $this->form_field_model->saveAll($FormFieldData);
                }
                /* END */

                $this->success('保存成功');
            } else {
                $this->error('保存失败，请刷新重试');
            }
        }

        $assign_data = [];
        /* 可用字段类型 */
        $field_type_html = '';
        foreach ($this->field_type_list as $key => $value) {
            $field_type_html .= '<option value="' . $key . '">' . $value . '</option>';
        }
        $assign_data['field_type_html'] = $field_type_html;
        // 定义全国参数
        $china[] = [
            'id'   => 0,
            'name' => '全国',
        ];
        // 查询省份信息并且拼装上$China数组
        $province_list               = get_province_list();
        $assign_data['province_list'] = array_merge($china, $province_list);
        /* END */
        $this->assign($assign_data);
        return $this->fetch();
    }
    /*
    * 表单字段 -- 编辑
    */
    public function field_edit()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            if (empty($post['form_name']) || empty($post['form_id'])) $this->error('请输入表单名称');
            // 查询表单名称是否已存在
            $FormName = $this->form_model->FormNameDoesItExist($post['form_name'], $post['form_id']);
            if (!empty($FormName)) $this->error('表单名称已存在，不可重复添加');

            // 更新表单主表数据
            $ResultID = $this->form_model->UpdateFormData($post);
            // 判断执行返回
            if (!empty($ResultID)) {
                /* 删除指定的字段 */
                $this->form_field_model->FormFieldDelete($post);
                /* END */

                /* 处理表单字段数据 */
                if (!empty($post['field_name'])) {
                    // 获取表单字段数据
                    $FormFieldData = $this->formLogic->GetSaveFormFieldData($post, $post['form_id']);
                    // 保存表单字段数据
                    if (!empty($FormFieldData)) $this->form_field_model->saveAll($FormFieldData);
                }
                /* END */
                $this->success('保存成功');
            } else {
                $this->error('保存失败，请刷新重试');
            }
        }

        $AssignData = [];

        $FormID = input('form_id/d');
        if (empty($FormID)){
            $this->error("请打开正确链接");
        }
        // 主表数据
        $AssignData['form'] = $this->form_model->GetFormData($FormID, null, null);
        if (empty($AssignData['form'])){
            $this->error("未找到表单");
        }
        // 附表数据
        $AssignData['form_field'] = $this->form_field_model->GetFormFieldData($FormID, null, null, null);
        // 可用字段类型
        $AssignData['field_type_list'] = $this->field_type_list;

        // 定义全国参数
        $china[] = [
            'id'   => 0,
            'name' => '全国',
        ];
        // 查询省份信息并且拼装上$China数组
        $province_list               = get_province_list();
        $AssignData['province_list'] = array_merge($china, $province_list);


        $this->assign($AssignData);
        return $this->fetch();
    }

    /**
     * 表单提交数据 -- 列表
     */
    public function index()
    {
        $assign_data = [];
        $form_id = input('form_id/d');
        if (empty($form_id)){
            $this->error("请指定表单id");
        }
        // 查询条件
        $condition = [
            'a.lang' => $this->admin_lang,
        ];
        /*时间检索*/
        $begin = strtotime(input('param.add_time_begin/s'));
        $end = input('param.add_time_end/s');
        !empty($end) && $end .= ' 23:59:59';
        $end = strtotime($end);
        if ($begin > 0 && $end > 0) {
            $condition['a.add_time'] = array('between',"$begin, $end");
        } else if ($begin > 0) {
            $condition['a.add_time'] = array('egt', $begin);
        } else if ($end > 0) {
            $condition['a.add_time'] = array('elt', $end);
        }
        /* END */
        /*表单名称模糊搜索*/
        $keywords = input('keywords/s');
        if (!empty($keywords)) $condition['b.form_name'] = array('LIKE', "%{$keywords}%");
        /* END */
        /*表单名称ID搜索*/
        if (!empty($form_id)) $condition['a.form_id'] = $form_id;
        /* END */
        // 分页查询
        $count = $this->form_list_db
            ->alias('a')
            ->where($condition)
            ->join('form b', 'a.form_id = b.form_id', 'LEFT')
            ->count();
        $Page = new Page($count, config('paginate.list_rows'));
        $show = $Page->show();
        $assign_data['page'] = $show;
        $assign_data['pager'] = $Page;

        // 数据查询
        $list = $this->form_list_db
            ->field('a.*, b.form_name')
            ->alias('a')
            ->where($condition)
            ->join('form b', 'a.form_id = b.form_id', 'LEFT')
            ->order('a.is_read asc,a.list_id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        $assign_data['list'] = $list;
        // 查询表单字段
        $assign_data['form_field'] = $this->form_field_db->where('form_id', $form_id)->order('field_id asc')->select();
        // 查询表单提交的数据
        $list_id_arr = get_arr_column($list,'list_id');
        $FormValue = $this->form_value_db->where(['form_id'=>$form_id,'list_id'=>['in',$list_id_arr]])->order('field_id asc')->select();
        $form_value_list = group_same_key($FormValue, 'list_id');
        foreach ($form_value_list as $key=>$val){
            $form_value_list[$key] = convert_arr_key($val,'field_id');
        }


        $assign_data['form_value'] = $form_value_list;

        // 表单列表
        $where = [
            'lang' => $this->admin_lang,
        ];
        $formList = $this->form_db->field('form_id, form_name')->where($where)->order('form_id asc')->select();
        $assign_data['formList'] = $formList;

        $this->assign($assign_data);
        return $this->fetch();
    }
    /*
         * 表单提交数据 -- 详情
         */
    public function view_form_data()
    {
        $list_id = input('list_id/d');
        $form_id = input('form_id/d');
        if (empty($list_id) || empty($form_id)) $this->error('参数有误');
        // 查询条件
        $where = [
            'a.list_id' => $list_id,
            'a.form_id' => $form_id,
            'a.lang'    => $this->admin_lang
        ];
        // 执行查询
        $info = $this->form_list_db
            ->field('a.*, b.form_name')
            ->alias('a')
            ->where($where)
            ->join('form b', 'a.form_id = b.form_id', 'LEFT')
            ->find();
        $assign_data['info'] = $info;

        // 执行查询
        $value_list = $this->form_value_db
            ->field('a.*, b.field_name,b.field_type')
            ->alias('a')
            ->where($where)
            ->join('form_field b', 'a.field_id = b.field_id', 'LEFT')
            ->select();
        foreach ($value_list as $key => $value) {
            if ('checkbox' == $value['field_type'] && !empty($value['field_value'])) {
                $value_list[$key]['field_value'] = str_replace(',', '] [', '['.$value['field_value'].']');
            }else if('region' == $value['field_type'] && !empty($value['field_value'])){
                $value['attr_value'] = Db::name('region')->where('id','in',$value['field_value'])->column('name');
                $value_list[$key]['field_value'] = implode('',$value['attr_value']);
            }elseif (('file' == $value['field_type'] || 'img' == $value['field_type']) && !empty($value['field_value'])){
                if(preg_match('/(\.(jpg|gif|png|bmp|jpeg|ico|webp))$/i', $value['field_value'])){
                    if (!stristr($value['field_value'], '|')) {
                        $value['field_value'] = handle_subdir_pic($value['field_value']);
                        $value_list[$key]['field_value']  = "<a href='{$value['field_value']}' target='_blank'><img src='{$value['field_value']}' width='60' height='60' style='float: unset;cursor: pointer;' /></a>";
                    }
                }else if(preg_match('/(\.('.tpCache('global.file_type').'))$/i', $value['field_value'])){
                    if (!stristr($value['field_value'], '|')) {
                        $value['field_value'] = handle_subdir_pic($value['field_value']);
                        $value_list[$key]['field_value']  = "<a href='{$value['field_value']}' download='".time()."'><img src=\"".ROOT_DIR."/public/static/common/images/file.png\" alt=\"\" style=\"width: 16px;height:  16px;\">点击下载</a>";
                    }
                }
            }
        }
        $assign_data['value_list'] = $value_list;
        $this->assign($assign_data);
        //更新是否查看
        Db::name('form_list')->where(['list_id'=>$list_id, 'lang'=>$this->admin_lang])->update([
            'is_read'   => 1,
            'update_time'   => getTime(),
        ]);
        return $this->fetch();
    }
    /*
     * 导出表单数据
     */
    public function export()
    {
        // 查询条件
        $condition = [
            'a.lang' => $this->admin_lang,
        ];
        /*时间检索*/
        $begin = strtotime(input('param.add_time_begin/s'));
        $end   = input('param.add_time_end/s');
        !empty($end) && $end .= ' 23:59:59';
        $end = strtotime($end);
        if ($begin > 0 && $end > 0) {
            $condition['a.add_time'] = array('between', "$begin, $end");
        } else if ($begin > 0) {
            $condition['a.add_time'] = array('egt', $begin);
        } else if ($end > 0) {
            $condition['a.add_time'] = array('elt', $end);
        }
        /* END */
        /*表单名称ID搜索*/
        $form_id = input('form_id/d');
        if (!empty($form_id)) $condition['a.form_id'] = $form_id;
        /* END */
        // 数据查询
        $list                = $this->form_list_db
            ->field('a.*, b.form_name')
            ->alias('a')
            ->where($condition)
            ->join('form b', 'a.form_id = b.form_id', 'LEFT')
            ->order('a.update_time desc,a.list_id desc')
            ->select();
        // 查询表单字段
        $form_field = $this->form_field_db->where('form_id', $form_id)->order('field_id asc')->select();
        $strTable        = '<table width="500" border="1">';
        // 查询表单提交的数据
        $list_id_arr = get_arr_column($list,'list_id');
        $FormValue = $this->form_value_db->where(['form_id'=>$form_id,'list_id'=>['in',$list_id_arr]])->order('field_id asc')->select();
        $form_value_list = group_same_key($FormValue, 'list_id');
        foreach ($form_value_list as $key=>$val){
            $form_value_list[$key] = convert_arr_key($val,'field_id');
        }
        if ($form_field && $list) {
            $strTable .= '<tr>';
            foreach ($form_field as $key) {
                $strTable .= '<td style="text-align:center;font-size:12px;" width="*">' . $key['field_name'] . '</td>';
            }
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">新增时间</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">更新时间</td>';
            $strTable .= '</tr>';
            foreach ($list as $val) {
                $strTable   .= '<tr>';
                foreach ($form_field as $f_f) {
                    $field_value = !empty($form_value_list[$val['list_id']][$f_f['field_id']]['field_value']) ? $form_value_list[$val['list_id']][$f_f['field_id']]['field_value'] : '';
                    if('region' == $f_f['field_type'] && !empty($field_value)){
                        $field_value = Db::name('region')->where('id','in',$field_value)->column('name');
                        $field_value = implode('',$field_value);
                    }
                    $strTable .= '<td style="text-align:center;font-size:12px;" style=\'vnd.ms-excel.numberformat:@\' width="*">' . $field_value . '</td>';
                }

//                foreach ($form_value_new[$val['list_id']] as &$key) {
//                    if ('datetime' == $key['field_type']){$field_value
//                        $key['field_value'] = date('Y-m-d H:i',$key['field_value']);
//                    }
//                    $strTable .= '<td style="text-align:center;font-size:12px;" style=\'vnd.ms-excel.numberformat:@\' width="*">' . $key['field_value'] . '</td>';
//                }
                $strTable .= '<td style="text-align:left;font-size:12px;">' . date('Y-m-d H:i:s', $val['add_time']) . '</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">' . date('Y-m-d H:i:s', $val['update_time']) . '</td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, 'form');
        exit();
    }
    /*
     * 表单数据 -- 删除
     */
    public function index_del()
    {
        if (IS_AJAX_POST) {
            $post = input('post.');
            if (empty($post['list_id'])) $this->error('参数有误');
            $list_id = eyIntval($post['list_id']);
            // 查询条件
            $where = [
                'lang' => $this->admin_lang,
                'list_id' => ['IN', $list_id]
            ];
            // 删除表单列表数据
            $ResultID = $this->form_list_db->where($where)->delete();
            if (!empty($ResultID)) {
                // 同步删除表单下的字段
                $this->form_value_db->where($where)->delete();

                adminLog('删除表单数据ID：'.implode(',', $list_id));
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    /*
     * 标签调用
     */
    public function label_call(){
        $form_id = input('form_id/d',0);
        if (!empty($form_id)) {
            $form = model('form')->where(['form_id'=>$form_id])->find();
            $form_field_list = model("form_field")->where([
                'form_id'   => $form['form_id'],
            ])->order("sort_order asc")->select();

            $content = '{eyou:form formid="'.$form['form_id'].'"}'."\n";
            $content .= '<form method="post" id="{$field.name}" action="{$field.action}" onsubmit="{$field.submit}">'."\n";

            foreach ($form_field_list as $key=>$val){
                $field_id = $val['field_id'];
                switch ($val['field_type']){
                    case "multitext":
                        $content .= '{$field.itemname_'.$field_id.'}：<textarea rows="2" cols="60" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" placeholder="{$field.placeholder_'.$field_id.'}" style="height:60px;"></textarea>'."\n";
                        break;
                    case "checkbox":
                        $content .= '{$field.itemname_'.$field_id.'}：{eyou:volist name="$field.options_'.$field_id.'" id="attr"}'."\n";
                        $content .= '<input type="checkbox" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" value="{$attr.value}">{$attr.value}'."\n";
                        $content .= '{/eyou:volist}'."\n";
                        break;
                    case "radio":
                        $content .= '{$field.itemname_'.$field_id.'}：{eyou:volist name="$field.options_'.$field_id.'" id="attr"}'."\n";
                        $content .= '<input type="radio" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" value="{$attr.value}">{$attr.value}'."\n";
                        $content .= '{/eyou:volist}'."\n";
                        break;
                    case "switch":
                        $content .= '{$field.itemname_'.$field_id.'}：<input type="radio" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" value="是">是 &nbsp;
                        <input type="radio" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" value="否">否'."\n";
                        break;
                    case "select":
                        $content .= '{$field.itemname_'.$field_id.'}：<select id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}">'."\n";
                        $content .= '{eyou:volist name="$field.options_'.$field_id.'" id="attr"}'."\n";
                        $content .= '<option value="{$attr.value}">{$attr.value}</option>'."\n";
                        $content .= '{/eyou:volist}'."\n";
                        $content .= '</select>'."\n";
                        break;
                    case "region":  //联动区域
                        $content .= '{$field.itemname_'.$field_id.'}：<select {$field.first_id_'.$field_id.'}>'."\n";
                        $content .= '	<option value="">请选择</option>'."\n";
                        $content .= '	{eyou:volist name="$field.options_'.$field_id.'" id="vo"}'."\n";
                        $content .= '	<option value="{$vo.id}">{$vo.name}</option>'."\n";
                        $content .= '	{/eyou:volist}'."\n";
                        $content .= '</select>'."\n";
						$content .= '<select {$field.second_id_'.$field_id.'}></select>'."\n";
						$content .= '<select {$field.third_id_'.$field_id.'}></select>'."\n";
						$content .= '{$field.hidden_'.$field_id.'}'."\n";
                        break;
                    case "img":     //单图
                        $content .= '{$field.itemname_'.$field_id.'}：<input type="file" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" placeholder="{$field.placeholder_'.$field_id.'}" accept="image/*">'."\n";
                        break;
                    case "file":    //文件
                        $content .= '{$field.itemname_'.$field_id.'}：<input type="file" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" placeholder="{$field.placeholder_'.$field_id.'}" >'."\n";
                        break;
                    default:    //单行文本
                        $content .= '{$field.itemname_'.$field_id.'}：<input type="text" id="{$field.field_id_'.$field_id.'}" name="{$field.field_'.$field_id.'}" placeholder="{$field.placeholder_'.$field_id.'}" >'."\n";
                        break;
                }
            }
            $content .=' <input type="submit"  value="提交">'."\n";
            $content .='{$field.hidden}'."\n";
            $content .='</form>'."\n";
            $content .='{/eyou:form}';
            $assign_data = [
                'form_list' => $form,
                'form_attr_list' => $form_field_list,
                'content' => htmlspecialchars($content)
            ];
            $this->assign($assign_data);

            return $this->fetch();
        }
        $this->error('数据不存在！');
    }
    //删除表单
    public function form_del(){
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if (!empty($id_arr)) {
            $r = Db::name('form')->where([
                'form_id' => ['IN', $id_arr],
            ])->delete();
            if($r){
                Db::name('form_field')->where(['form_id' => ['IN', $id_arr]])->delete();
                Db::name('form_list')->where(['form_id' => ['IN', $id_arr]])->delete();
                Db::name('form_value')->where(['form_id' => ['IN', $id_arr]])->delete();
                adminLog('删除表单-id：'.implode(',', $id_arr));
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('参数有误');
        }
    }
    //删除数据
    public function list_del(){
        $id_arr = input('del_id/a');
        $id_arr = eyIntval($id_arr);
        if (!empty($id_arr)) {
            $r = Db::name('form_list')->where([
                'list_id' => ['IN', $id_arr],
                'lang' => $this->admin_lang
            ])->delete();
            if($r){
                Db::name('form_value')->where(['list_id' => ['IN', $id_arr],'lang' => $this->admin_lang])->delete();
                adminLog('删除表单数据-id：'.implode(',', $id_arr));
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('参数有误');
        }
    }

}