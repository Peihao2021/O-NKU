<?php
namespace think;
 
class AjaxPage{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 11;// 分页栏每页显示的页数
	public $lastSuffix = true; // 最后一页是否显示总页数

    private $p       = 'p'; //分页参数名
    private $url     = ''; //当前链接URL
    private $nowPage = 1;

	// 分页显示定制
    private $config  = array(
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        /*
        'prev'   => '<<',
        'next'   => '>>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        */
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '首页',
        'last'   => '尾页',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    );

    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
       // config('VAR_PAGE') && $this->p = config('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */        
        $p = I('get.p');
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? input() : $parameter;
        $this->nowPage    = empty($p) ? 1 : intval($p);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows) return '';

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;
        $this->config['last'] = '尾页';
        
        $up_page = $down_page = $the_first = $the_end = $link_page = "";
        if ($this->totalPages > 1) {
            //上一页
            $up_row  = $this->nowPage - 1;
            $up_page = $up_row > 0 ? '<li id="example1_previous" class="paginate_button prev"><a class="prev" data-p="'.$up_row.'" href="javascript:void(0)">' . $this->config['prev'] . '</a></li>' : '<li id="example1_previous" class="paginate_button previous disabled"><a class="first" data-p="1" href="javascript:void(0)">' . $this->config['prev'] . '</a></li>';

            //下一页
            $down_row  = $this->nowPage + 1;
            $down_page = ($down_row <= $this->totalPages) ? '<li id="example1_next" class="paginate_button next"><a class="next" data-p="'.$down_row.'" href="javascript:void(0)">' . $this->config['next'] . '</a></li>' : '<li id="example1_previous" class="paginate_button previous disabled"><a class="end" data-p="'.$this->totalPages.'" href="javascript:void(0)">' . $this->config['next'] . '</a></li>';

            //第一页
            $the_first = '';
            if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
                $the_first = '<li id="example1_previous" class="paginate_button previous disabled"><a class="first" data-p="1" href="javascript:void(0)">' . $this->config['first'] . '</a></li>';
            }

            //最后一页
            $the_end = '';
            if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
                $the_end = '<li id="example1_previous" class="paginate_button previous disabled"><a class="end" data-p="'.$this->totalPages.'" href="javascript:void(0)">' . $this->config['last'] . '</a></li>';
            }

            //数字连接
            for($i = 1; $i <= $this->rollPage; $i++){
                if(($this->nowPage - $now_cool_page) <= 0 ){
                    $page = $i;
                }elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
                    $page = $this->totalPages - $this->rollPage + $i;
                }else{
                    $page = $this->nowPage - $now_cool_page_ceil + $i;
                }
                if($page > 0 && $page != $this->nowPage){
                    if($page <= $this->totalPages){
                        $link_page .= '<li class="paginate_button"><a class="num" data-p="'.$page.'" href="javascript:void(0)">' . $page . '</a></li>';
                    }else{
                        break;
                    }
                }else{
                    if($page > 0 && $this->totalPages != 1){
                        // $link_page .= '<span class="current">' . $page . '</span>';
                        $link_page .= '<li class="paginate_button active"><a tabindex="0" data-dt-idx="1" aria-controls="example1" data-p="'.$page.'" href="javascript:void(0);">' . $page . '</a></li>';
                    }
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']);
        return "<div class='dataTables_paginate paging_simple_numbers'><ul class='pagination'>{$page_str}</ul></div>";
    }
}
