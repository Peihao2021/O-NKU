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
 * Date: 2019-6-5
 */

namespace think\template\taglib\eyou;

use think\Request;
use think\Db;

/**
 * 搜索表单
 */
class TagScreening extends Base
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
        $this->channelfield_db = Db::name('channelfield');
        $this->dirname = input('param.tid/s');
        $this->tid = 0;
    }

    // URL中隐藏index.php入口文件，此方法仅此控制器使用到
    private function auto_hide_index($url = '', $seo_pseudo = 1)
    {
        if (2 != $seo_pseudo) {
            if (empty($url)) return false;
            // 是否开启去除index.php文件
            $seo_inlet = null;
            $seo_inlet === null && $seo_inlet = config('ey_config.seo_inlet');
            if (1 == $seo_inlet) {
                $url = str_replace('/index.php', '/', $url);
            }
        }
        return $url;
    }

    /**
     * 获取搜索表单
     */
    public function getScreening($currentclass='', $addfields='', $addfieldids='', $alltxt='', $typeid='')
    {
        if (self::$home_lang != self::$main_lang) return false;
        
        $param = input('param.');
        // 定义筛选标识
        $url_screen_var = config('global.url_screen_var');
        // 隐藏域参数处理
        $hidden  = '';
        // 是否在伪静态下搜索
        $seo_pseudo = config('ey_config.seo_pseudo');
        if (!isset($param[$url_screen_var]) && 3 == $seo_pseudo && !is_numeric($this->dirname)) {
            $arctype_where = [
                'dirname' => $this->dirname,
                'lang'    => self::$home_lang,
            ];
            $this->tid = Db::name('arctype')->where($arctype_where)->getField('id');
        } else {
            $this->tid = input('param.tid/d');
        }

        if (!empty($typeid)) $this->tid = $typeid;
        
        // 查询数据条件
        $where = [
            'a.is_screening' => 1,
            'a.ifeditable'   => 1,
            'b.typeid'       => $this->tid,
            // 根据需求新增条件
        ];

        // 是否指定参数读取
        if (!empty($addfields)) {
            $addfieldids = '';
            $where['a.name'] = array('IN',$addfields);
        } else if (!empty($addfieldids)) {
            $where['a.id'] = array('IN',$addfieldids);
        }

        // 数据查询
        $row = $this->channelfield_db
            ->field('a.id,a.title,a.name,a.dfvalue,a.dtype')
            ->alias('a')
            ->join('__CHANNELFIELD_BIND__ b', 'b.field_id = a.id', 'LEFT')
            ->where($where)
            ->order('a.sort_order asc, a.id asc')
            ->select();
        // Onclick点击事件方法名称加密，防止冲突
        $OnclickScreening  = 'ey_'.md5('OnclickScreening');
        // Onchange改变事件方法名称加密，防止冲突
        $OnchangeScreening = 'ey_'.md5('OnchangeScreening');
        // 定义搜索点击的name值
        $is_data = '';
        // 数据处理输出
        foreach ($row as $key => $value) {
            // 搜索的name值
            $name = $value['name'];
            // 封装onClick事件
            $row[$key]['onClick']  = "onClick='{$OnclickScreening}(this);'";
            // 封装onchange事件
            $row[$key]['onChange'] = "onChange='{$OnchangeScreening}(this);'";
            // 在伪静态下拼装控制器方式参数名
            if (!isset($param[$url_screen_var]) && 3 == $seo_pseudo) {
                $param_query = [];
                $param_query['m'] = 'home';
                $param_query['c'] = 'Lists';
                $param_query['a'] = 'index';
                $param_query['tid'] = $this->tid;
                $param_new = request()->param();
                unset($param_new['tid']);
                $param_query = array_merge($param_query, $param_new);
            } else {
                $param_query = request()->param();
            }

            // 生成静态页面代码
            if (2 == $seo_pseudo && !isMobile()) {
                $param_query['m'] = 'home';
                $param_query['c'] = 'Lists';
                $param_query['a'] = 'index';
                unset($param_query['_ajax']);
                unset($param_query['id']);
                unset($param_query['fid']);
                unset($param_query['lang']);
            }

            // 筛选时，去掉url上的页码page参数
            unset($param_query['page']);
            
            // 筛选值处理
            if ('region' == $value['dtype']) {
                // 类型为区域则执行，处理自定义参数名称
                $region_alltxt = $alltxt;
                if (!empty($region_alltxt)) {
                    // 等于OFF表示关闭，不需要此项
                    if ('off' == $region_alltxt) $region_alltxt = '';
                } else {
                    $region_alltxt = '全部';

                }
                $all = [];
                if (!empty($region_alltxt)) {
                    // 拼装数组
                    $all[0] = [
                        'id'   => '',
                        'name' => $region_alltxt,
                    ];
                }

                // 搜索点击的name值
                $is_data = isset($param[$name]) && !empty($param[$name]) ? $param[$name] : $region_alltxt;

                // 参数值含有单引号、双引号、分号，直接跳转404
                if (preg_match('#(\'|\"|;)#', $is_data)) abort(404,'页面不存在');

                // 处理后台添加的区域数据
                $RegionData = [];
                // 反序列化参数值
                $dfvalue = unserialize($value['dfvalue']);
                // 拆分ID值
                $region_ids = explode(',', $dfvalue['region_ids']);
                foreach ($region_ids as $id_key => $id_value) {
                    $RegionData[$id_key]['id'] = $id_value;
                }
                // 拆分name值
                $region_names = explode('，', $dfvalue['region_names']);
                foreach ($region_names as $name_key => $name_value) {
                    $RegionData[$name_key]['name'] = $name_value;
                }
                // 合并数组
                $RegionData = array_merge($all, $RegionData);

                // 处理参数输出
                foreach ($RegionData as $kk => $vv) {
                    // 参数拼装URL
                    if (!empty($vv['id'])) {
                        $param_query[$name] = $vv['id'];
                    } else {
                        unset($param_query[$name]);
                    }
                    // 筛选标识始终追加在最后
                    unset($param_query[$url_screen_var]);
                    $param_query[$url_screen_var] = 1;
                    foreach (['index','findex','achieve','s'] as $_uk => $_uv) {
                        if (isset($param_query[$_uv])) {
                            unset($param_query[$_uv]);
                        }
                    }
                    if (!empty($typeid)) {
                        // 存在typeid表示在首页展示
                        foreach (['m','c','a','tid'] as $_uk => $_uv) {
                            if (isset($param_query[$_uv])) {
                                unset($param_query[$_uv]);
                            }
                        }
                        if (empty($param_query['page'])) $param_query['page'] = 1;
                        $url = ROOT_DIR.'/index.php?m=home&c=Lists&a=index&tid='.$typeid.'&'.urlencode(http_build_query($param_query));
                    } else {
                        $url = ROOT_DIR.'/index.php?'.urlencode(http_build_query($param_query));
                    }
                    $url = $this->auto_hide_index(urldecode($url), $seo_pseudo);
                    // 拼装onClick事件
                    $RegionData[$kk]['onClick'] = $row[$key]['onClick']." data-url='{$url}'";
                    // 拼装onchange参数
                    $RegionData[$kk]['SelectUrl'] = "data-url='{$url}'";
                    // 初始化参数，默认未选中
                    $RegionData[$kk]['name']         = "{$vv['name']}";
                    $RegionData[$kk]['SelectValue']  = "";
                    $RegionData[$kk]['currentclass'] = $RegionData[$kk]['currentstyle'] = "";
                    // 选中时执行
                    if ($vv['id'] == $is_data) {
                        $RegionData[$kk]['name']         = "<b>{$vv['name']}</b>";
                        $RegionData[$kk]['SelectValue']  = "selected";
                        $RegionData[$kk]['currentclass'] = $RegionData[$kk]['currentstyle'] = $currentclass;
                    } else if ($vv['name'] == $region_alltxt && $is_data == $region_alltxt) {
                        $RegionData[$kk]['name']         = "<b>{$vv['name']}</b>";
                        $RegionData[$kk]['SelectValue']  = "selected";
                        $RegionData[$kk]['currentclass'] = $RegionData[$kk]['currentstyle'] = $currentclass;
                    }
                }
                // 数据赋值到数组中
                $row[$key]['dfvalue'] = $RegionData;
            } else {
                // 类型不为区域则执行
                $dfvalue = explode(',', $value['dfvalue']);
                $all[0] = [];
                if (!empty($alltxt)) {
                    // 等于OFF表示关闭，不需要此项
                    if ('off' != $alltxt) $all[0] = $alltxt;
                } else {
                    $all[0] = '全部';
                }

                // 搜索点击的name值
                $is_data = isset($param[$name]) && !empty($param[$name]) ? $param[$name] : $alltxt;

                // 参数值含有单引号、双引号、分号，直接跳转404
                if (preg_match('#(\'|\"|;)#', $is_data)) abort(404,'页面不存在');

                // 合并数组
                $dfvalue  = array_merge($all, $dfvalue);
                // 处理参数输出
                $data_new = [];
                foreach ($dfvalue as $kk => $vv) {
                    if ('off' == $alltxt && empty($vv)) {
                        continue;
                    }
                    $param_query[$name]    = $vv;
                    $data_new[$kk]['id']           = $vv;
                    $data_new[$kk]['name']         = "{$vv}";
                    $data_new[$kk]['SelectValue']  = "";
                    $data_new[$kk]['currentclass'] = $data_new[$kk]['currentstyle'] = "";

                    // 目前单选类型选中和多选类型选中的数据处理是相同的，后续可能会有优化，暂时保留两个判断
                    if ($vv == $is_data) {
                        // 单选/下拉类型选中
                        $data_new[$kk]['name']         = "<b>{$vv}</b>";
                        $data_new[$kk]['SelectValue']  = "selected";
                        $data_new[$kk]['currentclass'] = $data_new[$kk]['currentstyle'] = $currentclass;

                    } else if ($vv.'|' == $is_data) {
                        // 多选类型选中
                        $data_new[$kk]['name']         = "<b>{$vv}</b>";
                        $data_new[$kk]['SelectValue']  = "selected";
                        $data_new[$kk]['currentclass'] = $data_new[$kk]['currentstyle'] = $currentclass;

                    } else if ($vv == $all[0] && empty($is_data)) {
                        // “全部” 按钮选中
                        $data_new[$kk]['name']         = "<b>{$vv}</b>";
                        $data_new[$kk]['SelectValue']  = "selected";
                        $data_new[$kk]['currentclass'] = $data_new[$kk]['currentstyle'] = $currentclass;

                    }

                    if ($all[0] == $vv) {
                        // 若选中 “全部” 按钮则清除这个字段参数
                        unset($param_query[$name]);
                    } else if ('checkbox' == $value['dtype']) {
                        // 等于多选类型，则拼装上-号，用于搜索时分割，可匹配数据
                        $param_query[$name] = $vv.'|';
                    }
                    /* 筛选标识始终追加在最后 */
                    unset($param_query[$url_screen_var]);
                    $param_query[$url_screen_var] = 1;
                    /* end */
                    foreach (['index','findex','achieve','s'] as $_uk => $_uv) {
                        if (isset($param_query[$_uv])) {
                            unset($param_query[$_uv]);
                        }
                    }
                    // 参数拼装URL
                    if (!empty($typeid)) {
                        // 存在typeid表示在首页展示
                        foreach (['m','c','a','tid'] as $_uk => $_uv) {
                            if (isset($param_query[$_uv])) {
                                unset($param_query[$_uv]);
                            }
                        }
                        if (empty($param_query['page'])) $param_query['page'] = 1;
                        $url = ROOT_DIR.'/index.php?m=home&c=Lists&a=index&tid='.$typeid.'&'.urlencode(http_build_query($param_query));
                    }else{
                        $url = ROOT_DIR.'/index.php?'.urlencode(http_build_query($param_query));
                    }
                    $url = $this->auto_hide_index(urldecode($url), $seo_pseudo);
                    // 封装onClick
                    $data_new[$kk]['onClick'] = $row[$key]['onClick']." data-url='{$url}'";
                    // 封装onchange事件
                    $data_new[$kk]['SelectUrl'] = "data-url='{$url}'";
                }

                // 数据赋值到数组中
                $row[$key]['dfvalue'] = $data_new;
            }
        }
        
        $resetUrl = ROOT_DIR.'/index.php?m=home&c=Lists&a=index&tid='.$this->tid.'&'.$url_screen_var.'=1';

        $hidden .= <<<EOF
<script type="text/javascript">
    function {$OnclickScreening}(obj) {
        var dataurl = obj.getAttribute("data-url");
        if (dataurl) {
            window.location.href = dataurl;
        } else {
            alert('没有选择筛选项');
        }
    }

    function {$OnchangeScreening}(obj) {
        var dataurl = obj.options[obj.selectedIndex].getAttribute("data-url");
        if (dataurl) {
            window.location.href = dataurl;
        } else {
            alert('没有选择筛选项');
        }
    }
</script>
EOF;
        $result = array(
            'hidden'    => $hidden,
            'resetUrl' => $resetUrl,
            'list'       => $row,
        );
        return $result;
    }
}