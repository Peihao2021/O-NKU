<?php if (!defined('THINK_PATH')) exit(); /*a:5:{s:40:"./application/admin/template/seo\seo.htm";i:1662952957;s:66:"C:\wwwroot\waiguo.com\application\admin\template\public\layout.htm";i:1662952957;s:69:"C:\wwwroot\waiguo.com\application\admin\template\public\theme_css.htm";i:1662952957;s:60:"C:\wwwroot\waiguo.com\application\admin\template\seo\bar.htm";i:1662952957;s:66:"C:\wwwroot\waiguo.com\application\admin\template\public\footer.htm";i:1662952957;}*/ ?>
<!doctype html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-capable" content="yes">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link href="/public/plugins/layui/css/layui.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css">
<link href="/public/static/admin/css/main.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css">
<link href="/public/static/admin/css/page.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css">
<link href="/public/static/admin/font/css/font-awesome.min.css?v=<?php echo $version; ?>" rel="stylesheet" />
<link href="/public/static/admin/font/css/iconfont.css?v=<?php echo $version; ?>" rel="stylesheet" />
<!--[if IE 7]>
  <link rel="stylesheet" href="/public/static/admin/font/css/font-awesome-ie7.min.css?v=<?php echo $version; ?>">
<![endif]-->
<script type="text/javascript">
    var eyou_basefile = "<?php echo \think\Request::instance()->baseFile(); ?>";
    var module_name = "<?php echo MODULE_NAME; ?>";
    var GetUploadify_url = "<?php echo url('Uploadimgnew/upload'); ?>";
    // 插件专用旧版上传图片框
    if ('Weapp@execute' == "<?php echo CONTROLLER_NAME; ?>@<?php echo ACTION_NAME; ?>") {
      GetUploadify_url = "<?php echo url('Uploadify/upload'); ?>";
    }
    var __root_dir__ = "";
    var __lang__ = "<?php echo $admin_lang; ?>";
</script>  
<link href="/public/static/admin/js/jquery-ui/jquery-ui.min.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css"/>
<link href="/public/static/admin/js/perfect-scrollbar.min.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css"/>
<!-- <link type="text/css" rel="stylesheet" href="/public/plugins/tags_input/css/jquery.tagsinput.css?v=<?php echo $version; ?>"> -->
<style type="text/css">html, body { overflow: visible;}</style>
<link href="/public/static/admin/css/diy_style.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css" />

<!-- 官方内置样式表，升级会覆盖变动，请勿修改，否则后果自负 -->

<style type="text/css">
	/*左侧收缩图标*/
	#foldSidebar i { font-size: 24px;color:<?php echo $global['web_theme_color']; ?>; }
    /*左侧菜单*/
    .eycms_cont_left{ background:<?php echo $global['web_theme_color']; ?>; }
    .eycms_cont_left dl dd a:hover,.eycms_cont_left dl dd a.on,.eycms_cont_left dl dt.on{ background:<?php echo $global['web_assist_color']; ?>; }
    .eycms_cont_left dl dd a:active{ background:<?php echo $global['web_assist_color']; ?>; }
    .eycms_cont_left dl.jslist dd{ background:<?php echo $global['web_theme_color']; ?>; }
    .eycms_cont_left dl.jslist dd a:hover,.eycms_cont_left dl.jslist dd a.on{ background:<?php echo $global['web_assist_color']; ?>; }
    .eycms_cont_left dl.jslist:hover{ background:<?php echo $global['web_assist_color']; ?>; }
    /*栏目操作*/
    .cate-dropup .cate-dropup-con a:hover{ background-color: <?php echo $global['web_theme_color']; ?>; }
    /*按钮*/
    a.ncap-btn-green { background-color: <?php echo $global['web_theme_color']; ?>; }
    a:hover.ncap-btn-green { background-color: <?php echo $global['web_assist_color']; ?>; }
    .flexigrid .sDiv2 .btn:hover { background-color: <?php echo $global['web_theme_color']; ?>; }
    .flexigrid .mDiv .fbutton div.add{background-color: <?php echo $global['web_theme_color']; ?>; border: none;}
    .flexigrid .mDiv .fbutton div.add:hover{ background-color: <?php echo $global['web_assist_color']; ?>;}
	.flexigrid .mDiv .fbutton div.adds{color:<?php echo $global['web_theme_color']; ?>;border: 1px solid <?php echo $global['web_theme_color']; ?>;}
	.flexigrid .mDiv .fbutton div.adds:hover{ background-color: <?php echo $global['web_theme_color']; ?>;}
    /*选项卡字体*/
    .tab-base a.current,
    .tab-base a:hover.current {color:<?php echo $global['web_theme_color']; ?> !important;}
    .tab-base a.current:after,
    .tab-base a:hover.current:after {background-color: <?php echo $global['web_theme_color']; ?>;}
    .addartbtn input.btn:hover{ border-bottom: 1px solid <?php echo $global['web_theme_color']; ?>; }
    .addartbtn input.btn.selected{ color: <?php echo $global['web_theme_color']; ?>;border-bottom: 1px solid <?php echo $global['web_theme_color']; ?>;}
	/*会员导航*/
	.member-nav-group .member-nav-item .btn.selected{background: <?php echo $global['web_theme_color']; ?>;border: 1px solid <?php echo $global['web_theme_color']; ?>;box-shadow: -1px 0 0 0 <?php echo $global['web_theme_color']; ?>;}
	.member-nav-group .member-nav-item:first-child .btn.selected{border-left: 1px solid <?php echo $global['web_theme_color']; ?> !important;}
	/*搜索按钮图标*/
	.flexigrid .sDiv2 .fa-search{}
        
    /* 商品订单列表标题 */
   .flexigrid .mDiv .ftitle h3 {border-left: 3px solid <?php echo $global['web_theme_color']; ?>;} 
	
    /*分页*/
    .pagination > .active > a, .pagination > .active > a:focus, 
	.pagination > .active > a:hover, 
	.pagination > .active > span, 
	.pagination > .active > span:focus, 
	.pagination > .active > span:hover { border-color: <?php echo $global['web_theme_color']; ?>;color:<?php echo $global['web_theme_color']; ?>; }
    
    .layui-form-onswitch {border-color: <?php echo $global['web_theme_color']; ?> !important;background-color: <?php echo $global['web_theme_color']; ?> !important;}
    .onoff .cb-enable.selected { background-color: <?php echo $global['web_theme_color']; ?> !important;border-color: <?php echo $global['web_theme_color']; ?> !important;}
    .onoff .cb-disable.selected {background-color: <?php echo $global['web_theme_color']; ?> !important;border-color: <?php echo $global['web_theme_color']; ?> !important;}
    .pcwap-onoff .cb-enable.selected { background-color: <?php echo $global['web_theme_color']; ?> !important;border-color: <?php echo $global['web_theme_color']; ?> !important;}
    .pcwap-onoff .cb-disable.selected {background-color: <?php echo $global['web_theme_color']; ?> !important;border-color: <?php echo $global['web_theme_color']; ?> !important;}
    input[type="text"]:focus,
    input[type="text"]:hover,
    input[type="text"]:active,
    input[type="password"]:focus,
    input[type="password"]:hover,
    input[type="password"]:active,
    textarea:hover,
    textarea:focus,
    textarea:active { border-color:<?php echo hex2rgba($global['web_theme_color'],0.8); ?>;box-shadow: 0 0 0 1px <?php echo hex2rgba($global['web_theme_color'],0.5); ?> !important;}
    .input-file-show:hover .type-file-button {background-color:<?php echo $global['web_theme_color']; ?> !important; }
    .input-file-show:hover {border-color: <?php echo $global['web_theme_color']; ?> !important;box-shadow: 0 0 5px <?php echo hex2rgba($global['web_theme_color'],0.5); ?> !important;}
    .input-file-show:hover span.show a,
    .input-file-show span.show a:hover { color: <?php echo $global['web_theme_color']; ?> !important;}
    .input-file-show:hover .type-file-button {background-color: <?php echo $global['web_theme_color']; ?> !important; }
    .color_z { color: <?php echo $global['web_theme_color']; ?> !important;}
    a.imgupload{
        background-color: <?php echo $global['web_theme_color']; ?> !important;
        border-color: <?php echo $global['web_theme_color']; ?> !important;
    }
    /*专题节点按钮*/
    .ncap-form-default .special-add{background-color:<?php echo $global['web_theme_color']; ?>;border-color:<?php echo $global['web_theme_color']; ?>;}
    .ncap-form-default .special-add:hover{background-color:<?php echo $global['web_assist_color']; ?>;border-color:<?php echo $global['web_assist_color']; ?>;}
    
    /*更多功能标题*/
    .on-off_panel .title::before {background-color:<?php echo $global['web_theme_color']; ?>;}
    .on-off_panel .icon_bg {background-color: <?php echo $global['web_theme_color']; ?>;}
    .on-off_panel .e-jianhao {color: <?php echo $global['web_theme_color']; ?>;}
    
     /*内容菜单*/
    .ztree li a:hover{color:<?php echo $global['web_theme_color']; ?> !important;}
    .ztree li a.curSelectedNode{background-color: <?php echo $global['web_theme_color']; ?> !important; border-color:<?php echo $global['web_theme_color']; ?> !important;}
    .layout-left .on-off-btn {background-color: <?php echo $global['web_theme_color']; ?> !important;}

    .iframe_loading{
        width:100%;
        background:url(/public/static/admin/images/loading-0.gif) no-repeat center center;
    }
    
    .layui-btn-normal {background-color: <?php echo $global['web_theme_color']; ?>;}
    
    /* 商品规格按钮 */
    /* .preset-bt{border-color: <?php echo $global['web_theme_color']; ?> !important;background:<?php echo $global['web_theme_color']; ?>;} */
</style>
<script type="text/javascript" src="/public/static/admin/js/jquery.js?v=<?php echo $version; ?>"></script>
<!-- <script type="text/javascript" src="/public/plugins/tags_input/js/jquery.tagsinput.js?v=<?php echo $version; ?>"></script> -->
<script type="text/javascript" src="/public/static/admin/js/jquery-ui/jquery-ui.min.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/plugins/layer-v3.1.0/layer.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/jquery.cookie.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/admin.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/jquery.validation.min.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/common.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/perfect-scrollbar.min.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/static/admin/js/jquery.mousewheel.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="/public/plugins/layui/layui.js?v=<?php echo $version; ?>"></script>
<script src="/public/static/admin/js/myFormValidate.js?v=<?php echo $version; ?>"></script>
<script src="/public/static/admin/js/myAjax2.js?v=<?php echo $version; ?>"></script>
<script src="/public/static/admin/js/global.js?v=<?php echo $version; ?>"></script>
</head>
<body class="bodystyle">
<div id="append_parent"></div>
<div id="ajaxwaitid"></div>
<div class="page atta">
        <div class="fixed-bar">
        <div class="item-title">
            <a class="back_xin" href="javascript:history.back();" title="返回"><i class="iconfont e-fanhui"></i></a>
            <div class="subject">
                <h3>SEO设置</h3>
                <h5></h5>
            </div>
            <ul class="tab-base nc-row">
                <?php if($main_lang == $admin_lang): if(is_check_access('Seo@seo') == '1'): ?>
                <li><a href="<?php echo url('Seo/seo'); ?>" <?php if('seo'==ACTION_NAME): ?>class="current"<?php endif; ?>><span>URL配置</span></a></li>
                <?php endif; endif; if($main_lang == $admin_lang): if(is_check_access('Sitemap@index') == '1'): ?>
                <li><a href="<?php echo url('Sitemap/index'); ?>" <?php if('Sitemap'==CONTROLLER_NAME): ?>class="current"<?php endif; ?>><span>Sitemap</span></a></li>
                <?php endif; endif; if(is_check_access('Links@index') == '1'): ?>
                <li><a href="<?php echo url('Links/index'); ?>" <?php if('Links'==CONTROLLER_NAME): ?>class="current"<?php endif; ?>><span>友情链接</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <form method="post" id="handlepost" action="<?php echo url('Seo/handle'); ?>" enctype="multipart/form-data" name="form1">
        <div class="ncap-form-default">
            <dl class="row">
                <dt class="tit">
                    <label for="seo_pseudo">URL模式</label>
                </dt>
                <dd class="opt">
                    <?php if(is_array($seo_pseudo_list) || $seo_pseudo_list instanceof \think\Collection || $seo_pseudo_list instanceof \think\Paginator): $i = 0; $__LIST__ = $seo_pseudo_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <label class="curpoin">
                            <input type="radio" name="seo_pseudo" value="<?php echo $key; ?>" <?php if(isset($config['seo_pseudo']) && $config['seo_pseudo'] == $key): ?>checked="checked"<?php endif; if(!empty($default_lang) && $default_lang != 'cn' && $key == 2): ?>disabled<?php endif; ?>/><?php echo $vo; ?>&nbsp;
                        </label>
                    <?php endforeach; endif; else: echo "" ;endif; if(!empty($default_lang) && $default_lang != 'cn'): ?>
                        <p class="notic">前台默认语言不是中文时，不允许使用静态模式！</p>
                    <?php endif; ?>
                    <!-- &nbsp;&nbsp;<a id="a_seo_build" <?php if(empty($config['seo_pseudo']) || 2 != $config['seo_pseudo']): ?>style="display: none;"<?php endif; ?> href="javascript:void(0);" data-url="<?php echo url('Seo/build'); ?>" onclick="seo_build(this);">[前往生成静态]</a> -->
                    <input type="hidden" id="seo_pseudo_old" value="<?php echo (isset($config['seo_pseudo']) && ($config['seo_pseudo'] !== '')?$config['seo_pseudo']:'1'); ?>"/>
                    <input type="hidden" name="seo_html_arcdir_limit" value="<?php echo $seo_html_arcdir_limit; ?>"/>
                    <input type="hidden" id="seo_inlet" value="<?php echo $config['seo_inlet']; ?>"/>
                    <input type="hidden" id="init_html" value="<?php echo (isset($init_html) && ($init_html !== '')?$init_html:'1'); ?>"/>
                </dd>
            </dl>
            <dl class="row <?php if(empty($config['seo_pseudo']) || 1 != $config['seo_pseudo'] || (1 == $config['seo_pseudo'] && 1 == $config['seo_dynamic_format'])): ?>none<?php endif; ?>" id="dl_seo_dynamic_format">
                <dt class="tit">
                    <label>动态格式</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin"><input type="radio" name="seo_dynamic_format" value="1" <?php if(!isset($config['seo_dynamic_format']) OR $config['seo_dynamic_format'] == 1): ?>checked="checked"<?php endif; ?>>完全兼容（<a href="javascript:void(0);" onclick="view_exp('view_1_1');">查看例子</a><span id="view_1_1" class="none">：http://www.onku.ink/index.php?m=home&amp;c=Lists&amp;a=index&amp;tid=1</span>）</label>&nbsp;
                    <?php if(isset($config['seo_dynamic_format']) AND $config['seo_dynamic_format'] == 2): ?>
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_dynamic_format" value="2" checked="checked">部分兼容&nbsp;<font color="red">[部分空间不支持]</font>（<a href="javascript:void(0);" onclick="view_exp('view_1_2');">查看例子</a><span id="view_1_2" class="none">：http://www.onku.ink/home/Lists/index.html?tid=1</span>）</label>&nbsp;
                    <?php endif; ?>
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <div class="row <?php if(isset($config['seo_pseudo']) && $config['seo_pseudo'] != 2): ?>none<?php endif; ?>" id="dl_seo_html_format">
                <?php if(!(empty($seo_pseudo_lang) || (($seo_pseudo_lang instanceof \think\Collection || $seo_pseudo_lang instanceof \think\Paginator ) && $seo_pseudo_lang->isEmpty()))): ?>
                <dl class="row">
                    <dt class="tit">
                        <label for="seo_pseudo_lang">多语言URL</label>
                    </dt>
                     <dd class="opt">
                        <label class="curpoin"><input type="radio" name="seo_pseudo_lang" value="1" <?php if(!isset($seo_pseudo_lang) OR $seo_pseudo_lang != 3): ?>checked="checked"<?php endif; ?>/>动态URL</label>&nbsp;
                        <label class="curpoin"><input type="radio" name="seo_pseudo_lang" value="3" <?php if(isset($seo_pseudo_lang) AND $seo_pseudo_lang == 3): ?>checked="checked"<?php endif; ?>>伪静态化</label>&nbsp;
                        <span class="err"></span>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <?php endif; ?>
                <dl class="row">
                    <dt class="tit">
                        <label for="seo_html_arcdir">页面保存目录</label>
                    </dt>
                    <dd class="opt">
                        <input id="seo_html_arcdir" name="seo_html_arcdir" value="<?php echo (isset($config['seo_html_arcdir']) && ($config['seo_html_arcdir'] !== '')?$config['seo_html_arcdir']:''); ?>" placeholder="留空默认根目录" type="text" />
                        （如：html）
                        <p class="notic">填写需要生成静态页面的文件夹名称，不能包含特殊字符，不能和根目录系统文件夹重名</p>
                        <p class="<?php if(empty($seo_html_arcdir_1) || (($seo_html_arcdir_1 instanceof \think\Collection || $seo_html_arcdir_1 instanceof \think\Paginator ) && $seo_html_arcdir_1->isEmpty())): ?>none<?php endif; ?>" style="color: red;" id="tips_seo_html_arcdir_1">页面将保存在 http://www.onku.ink<span id="tips_seo_html_arcdir_2"><?php echo (isset($seo_html_arcdir_1) && ($seo_html_arcdir_1 !== '')?$seo_html_arcdir_1:''); ?></span>/</p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit">
                        <label>栏目页面名称</label>
                    </dt>
                    <dd class="opt">
                        <label class="curpoin"><input type="radio" name="seo_html_listname" value="1" <?php if(isset($config['seo_html_listname']) && $config['seo_html_listname'] == 1): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>顶级目录名称/lists_ID.html（<a href="javascript:void(0);" onclick="view_exp('lists_2_1');">查看例子</a><span id="lists_2_1" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/news/lists_1.html</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_listname" value="2" <?php if(!isset($config['seo_html_listname']) || $config['seo_html_listname'] != 1): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>父级目录名称/子目录名称/（<a href="javascript:void(0);" onclick="view_exp('lists_2_2');">查看例子</a><span id="lists_2_2" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/news/lol/</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_listname" value="3" <?php if(isset($config['seo_html_listname']) && $config['seo_html_listname'] == 3): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>子目录名称/（<a href="javascript:void(0);" onclick="view_exp('lists_2_3');">查看例子</a><span id="lists_2_3" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/lol/</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_listname" value="4" <?php if(isset($config['seo_html_listname']) && $config['seo_html_listname'] == 4): ?>checked="checked"<?php endif; ?>>自定义（<a href="javascript:void(0);" onclick="view_exp('lists_2_4');">点击查看</a><span id="lists_2_4" class="none">：在【栏目管理】新增/编辑栏目可以自定义文件保存目录和列表命名规则</span>）</label>&nbsp;
                        <span class="err"></span>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit">
                        <label>文档页面名称</label>
                    </dt>
                    <dd class="opt">
                        <label class="curpoin"><input type="radio" name="seo_html_pagename" value="1" <?php if(isset($config['seo_html_pagename']) && $config['seo_html_pagename'] == 1): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>顶级目录名称/ID.html（<a href="javascript:void(0);" onclick="view_exp('view_2_1');">查看例子</a><span id="view_2_1" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/news/10.html</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_pagename" value="2" <?php if(!isset($config['seo_html_pagename']) || $config['seo_html_pagename'] != 1): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>父级目录名称/子目录名称/ID.html（<a href="javascript:void(0);" onclick="view_exp('view_2_2');">查看例子</a><span id="view_2_2" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/news/lol/20.html</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_pagename" value="3" <?php if(isset($config['seo_html_pagename']) && $config['seo_html_pagename'] == 3): ?>checked="checked"<?php endif; ?>><?php if(!(empty($root_dir) || (($root_dir instanceof \think\Collection || $root_dir instanceof \think\Paginator ) && $root_dir->isEmpty()))): ?>子目录名称/<?php endif; ?>子目录名称/ID.html（<a href="javascript:void(0);" onclick="view_exp('view_2_3');">查看例子</a><span id="view_2_3" class="none">：http://www.onku.ink<span id="exp_seo_html_arcdir"><?php echo $seo_html_arcdir_1; ?></span>/lol/20.html</span>）</label>&nbsp;
                        <br/>
                        <label class="curpoin"><input type="radio" name="seo_html_pagename" value="4" <?php if(isset($config['seo_html_pagename']) && $config['seo_html_pagename'] == 4): ?>checked="checked"<?php endif; ?>>自定义（<a href="javascript:void(0);" onclick="view_exp('view_2_4');">点击查看</a><span id="view_2_4" class="none">：在【栏目管理】新增/编辑栏目可以自定义文件保存目录和文档命名规则</span>）</label>&nbsp;
                        <span class="err"></span>
                        <p class="notic"></p>
                    </dd>
                </dl>
            </div>
            <dl class="row <?php if(isset($config['seo_pseudo']) && $config['seo_pseudo'] != 3): ?>none<?php endif; ?>" id="dl_seo_rewrite_format">
                <dt class="tit">
                    <label>URL格式</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin">
                        <input type="radio" name="seo_rewrite_format" <?php if(!isset($config['seo_rewrite_format']) OR $config['seo_rewrite_format'] == 1): ?> value="1" checked="checked" <?php else: ?> value="3" checked="checked" <?php endif; ?>>目录名称（<a href="javascript:void(0);" onclick="view_exp('view_3_1');">示例</a><span id="view_3_1" class="none">：http://www.onku.ink/news/</span>）
                    </label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_rewrite_format" value="2" <?php if(isset($config['seo_rewrite_format']) AND $config['seo_rewrite_format'] == 2): ?>checked="checked"<?php endif; ?>>模型标识（<a href="javascript:void(0);" onclick="view_exp('view_3_2');">示例</a><span id="view_3_2" class="none">：http://www.onku.ink/article/news.html</span>）</label>&nbsp;
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <dl class="row <?php if(!isset($config['seo_pseudo']) OR $config['seo_pseudo'] != 3 OR $config['seo_rewrite_format'] == 2): ?>none<?php endif; ?>" id="dl_seo_rewrite_view_format">
                <dt class="tit">
                    <label>文档隶属URL</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin"><input type="radio" name="seo_rewrite_view_format" value="1" <?php if((!isset($config['seo_rewrite_format']) OR $config['seo_rewrite_format'] == 1) OR (!isset($config['seo_rewrite_view_format']) OR $config['seo_rewrite_view_format'] == 1)): ?>checked="checked"<?php endif; ?>>父目录</label>&nbsp;
                    <label class="curpoin"><input type="radio" name="seo_rewrite_view_format" value="3" <?php if((isset($config['seo_rewrite_format']) AND $config['seo_rewrite_format'] == 3) OR $config['seo_rewrite_view_format'] == 3): ?>checked="checked"<?php endif; ?>>当前目录</label>&nbsp;
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <dl class="row">
                <dt class="tit">
                    <label>栏目标题格式</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin"><input type="radio" name="seo_liststitle_format" value="3" <?php if(isset($config['seo_liststitle_format']) AND $config['seo_liststitle_format'] == 3): ?>checked="checked"<?php endif; ?>>栏目名称</label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_liststitle_format" value="1" <?php if(isset($config['seo_liststitle_format']) AND $config['seo_liststitle_format'] == 1): ?>checked="checked"<?php endif; ?>>栏目名称<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>网站名称</label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_liststitle_format" value="4" <?php if(isset($config['seo_liststitle_format']) AND $config['seo_liststitle_format'] == 4): ?>checked="checked"<?php endif; ?>>栏目名称<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>第N页</label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_liststitle_format" value="2" <?php if(!isset($config['seo_liststitle_format']) OR $config['seo_liststitle_format'] == 2): ?>checked="checked"<?php endif; ?>>栏目名称<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>第N页<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>网站名称</label>&nbsp;
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <dl class="row">
                <dt class="tit">
                    <label>文档标题格式</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin"><input type="radio" name="seo_viewtitle_format" value="1" <?php if(isset($config['seo_viewtitle_format']) AND $config['seo_viewtitle_format'] == 1): ?>checked="checked"<?php endif; ?>>内容标题</label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_viewtitle_format" value="2" <?php if(!isset($config['seo_viewtitle_format']) OR $config['seo_viewtitle_format'] == 2): ?>checked="checked"<?php endif; ?>>内容标题<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>网站名称</label>&nbsp;
                    <br/>
                    <label class="curpoin"><input type="radio" name="seo_viewtitle_format" value="3" <?php if(isset($config['seo_viewtitle_format']) AND $config['seo_viewtitle_format'] == 3): ?>checked="checked"<?php endif; ?>>内容标题<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>栏目名称<span class="sp_seo_title_symbol"><?php if(!isset($config['seo_title_symbol'])): ?>_<?php else: ?><?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); endif; ?></span>网站名称</label>&nbsp;
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <dl class="row">
                <dt class="tit">
                    <label for="seo_title_symbol">标题连接符号</label>
                </dt>
                <dd class="opt">
                    <input id="seo_title_symbol" name="seo_title_symbol" <?php if(!isset($config['seo_title_symbol'])): ?>value="_"<?php else: ?>value="<?php echo (isset($config['seo_title_symbol']) && ($config['seo_title_symbol'] !== '')?$config['seo_title_symbol']:''); ?>"<?php endif; ?> type="text" />
                    <p class="notic"></p>
                </dd>
            </dl>
            <!-- <dl class="row <?php if(empty($config['seo_inlet']) OR (1 == $config['seo_inlet'] AND 1 == $config['seo_force_inlet'])): else: ?>none<?php endif; ?>" id="dl_seo_force_inlet"> -->
            <dl class="row none" id="dl_seo_force_inlet">
                <dt class="tit">
                    <label for="site_url">强制去除index.php</label>
                </dt>
                <dd class="opt">
                    <div class="onoff">
                        <label for="seo_force_inlet1" class="cb-enable <?php if(isset($config['seo_force_inlet']) && $config['seo_force_inlet'] == 1): ?>selected<?php endif; ?>">开启</label>
                        <label for="seo_force_inlet0" class="cb-disable <?php if(empty($config['seo_force_inlet'])): ?>selected<?php endif; ?>">关闭</label>
                        <input id="seo_force_inlet1" name="seo_force_inlet" value="1" type="radio" <?php if(isset($config['seo_force_inlet']) && $config['seo_force_inlet'] == 1): ?> checked="checked"<?php endif; ?>>
                        <input id="seo_force_inlet0" name="seo_force_inlet" value="0" type="radio" <?php if(empty($config['seo_force_inlet'])): ?> checked="checked"<?php endif; ?>>
                    </div>
                    <br/>
                    <p class=""></p>
                </dd>
            </dl>
            <dl class="row <?php if(isset($config['seo_pseudo']) && $config['seo_pseudo'] != 2): ?>none<?php endif; ?>" id="dl_seo_uphtml_after">
                <dt class="tit">
                    <label>发布文档后</label>
                </dt>
                <dd class="opt">
                    <label class="curpoin"><input type="checkbox" name="seo_uphtml_after_home" value="1" <?php if(!isset($config['seo_uphtml_after_home']) OR $config['seo_uphtml_after_home'] == 1): ?>checked="checked"<?php endif; ?>>更新首页</label>&nbsp;&nbsp;
                    <label class="curpoin"><input type="checkbox" name="seo_uphtml_after_channel" value="1" <?php if(!isset($config['seo_uphtml_after_channel']) OR $config['seo_uphtml_after_channel'] == 1): ?>checked="checked"<?php endif; ?>>更新相关栏目</label>&nbsp;&nbsp;
                    <label class="curpoin"><input type="checkbox" name="seo_uphtml_after_pernext" value="1" <?php if(!isset($config['seo_uphtml_after_pernext']) OR $config['seo_uphtml_after_pernext'] == 1): ?>checked="checked"<?php endif; ?>>更新上下篇</label>&nbsp;&nbsp;
                    <span class="err"></span>
                    <p class="notic"></p>
                </dd>
            </dl>
            <div class="bot">
                <input type="hidden" id="is_change_data" value="0">
                <input type="hidden" id="seo_html_arcdir_old" value="<?php echo (isset($config['seo_html_arcdir']) && ($config['seo_html_arcdir'] !== '')?$config['seo_html_arcdir']:''); ?>"/>
                <input type="hidden" id="seo_html_listname_old" value="<?php echo (isset($config['seo_html_listname']) && ($config['seo_html_listname'] !== '')?$config['seo_html_listname']:''); ?>"/>
                <input type="hidden" id="seo_html_pagename_old" value="<?php echo (isset($config['seo_html_pagename']) && ($config['seo_html_pagename'] !== '')?$config['seo_html_pagename']:''); ?>"/>
                <input type="hidden" id="seo_liststitle_format_old" value="<?php echo (isset($config['seo_liststitle_format']) && ($config['seo_liststitle_format'] !== '')?$config['seo_liststitle_format']:''); ?>"/>
                <input type="hidden" id="seo_viewtitle_format_old" value="<?php echo (isset($config['seo_viewtitle_format']) && ($config['seo_viewtitle_format'] !== '')?$config['seo_viewtitle_format']:''); ?>"/>
                <input type="hidden" id="seo_uphtml_after_home_old" value="<?php echo (isset($config['seo_uphtml_after_home']) && ($config['seo_uphtml_after_home'] !== '')?$config['seo_uphtml_after_home']:''); ?>"/>
                <input type="hidden" id="seo_uphtml_after_channel_old" value="<?php echo (isset($config['seo_uphtml_after_channel']) && ($config['seo_uphtml_after_channel'] !== '')?$config['seo_uphtml_after_channel']:''); ?>"/>
                <input type="hidden" id="seo_uphtml_after_pernext_old" value="<?php echo (isset($config['seo_uphtml_after_pernext']) && ($config['seo_uphtml_after_pernext'] !== '')?$config['seo_uphtml_after_pernext']:''); ?>"/>
                <a href="JavaScript:void(0);" class="ncap-btn-big ncap-btn-green" onclick="adsubmit();">确认提交</a>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript">

    // 静态模式下，是否改变任意配置
    function change_html_confdata()
    {
        // 是否改变任意一项配置
        var is_change_data = 0;
        
        var seo_pseudo = $('input[name=seo_pseudo]:checked').val();
        var seo_pseudo_old = $('#seo_pseudo_old').val();
        if (seo_pseudo == 2 && seo_pseudo_old != seo_pseudo) {
            is_change_data = 1;
        } else {
            is_change_data = 0;
        }

        if (0 == is_change_data) {
            var seo_html_arcdir_old = $('#seo_html_arcdir_old').val();
            var seo_html_arcdir = $('#seo_html_arcdir').val();
            if (seo_html_arcdir_old != seo_html_arcdir) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }

        if (0 == is_change_data) {
            var seo_html_listname_old = $('#seo_html_listname_old').val();
            var seo_html_listname = $('input[name=seo_html_listname]:checked').val();
            if (seo_html_listname_old != seo_html_listname) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }

        if (0 == is_change_data) {
            var seo_html_pagename_old = $('#seo_html_pagename_old').val();
            var seo_html_pagename = $('input[name=seo_html_pagename]:checked').val();
            if (seo_html_pagename_old != seo_html_pagename) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }
        
        if (0 == is_change_data) {
            var seo_liststitle_format_old = $('#seo_liststitle_format_old').val();
            var seo_liststitle_format = $('input[name=seo_liststitle_format]:checked').val();
            if (seo_liststitle_format_old != seo_liststitle_format) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }
        
        if (0 == is_change_data) {
            var seo_viewtitle_format_old = $('#seo_viewtitle_format_old').val();
            var seo_viewtitle_format = $('input[name=seo_viewtitle_format]:checked').val();
            if (seo_viewtitle_format_old != seo_viewtitle_format) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }
        
        if (0 == is_change_data) {
            var seo_uphtml_after_home_old = $('#seo_uphtml_after_home_old').val();
            if (0 == seo_uphtml_after_home_old) {
                seo_uphtml_after_home_old = undefined;
            }
            var seo_uphtml_after_home = $('input[name=seo_uphtml_after_home]:checked').val();
            if (seo_uphtml_after_home_old != seo_uphtml_after_home) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }
        
        if (0 == is_change_data) {
            var seo_uphtml_after_channel_old = $('#seo_uphtml_after_channel_old').val();
            if (0 == seo_uphtml_after_channel_old) {
                seo_uphtml_after_channel_old = undefined;
            }
            var seo_uphtml_after_channel = $('input[name=seo_uphtml_after_channel]:checked').val();
            if (seo_uphtml_after_channel_old != seo_uphtml_after_channel) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }
        
        if (0 == is_change_data) {
            var seo_uphtml_after_pernext_old = $('#seo_uphtml_after_pernext_old').val();
            if (0 == seo_uphtml_after_pernext_old) {
                seo_uphtml_after_pernext_old = undefined;
            }
            var seo_uphtml_after_pernext = $('input[name=seo_uphtml_after_pernext]:checked').val();
            if (seo_uphtml_after_pernext_old != seo_uphtml_after_pernext) {
                is_change_data = 1;
            } else {
                is_change_data = 0;
            }
        }

        $('#is_change_data').val(is_change_data);
    }

    $(function(){
        // 栏目页面名称
        $('input[name=seo_html_listname]').click(function(){
            var seo_html_listname = $(this).val();
            var seo_html_pagename = $('input[name=seo_html_pagename]:checked').val();
            if (4 == seo_html_listname && 4 != seo_html_pagename) {
                layer.alert('文档页面名称也可以自定义哦！', {icon: 6, closeBtn:false, title: false});
            }
            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });
        // 文档页面名称
        $('input[name=seo_html_pagename]').click(function(){
            var seo_html_pagename = $(this).val();
            var seo_html_listname = $('input[name=seo_html_listname]:checked').val();
            if (4 == seo_html_pagename && 4 != seo_html_listname) {
                layer.alert('栏目页面名称也可以自定义哦！', {icon: 6, closeBtn:false, title: false});
            }
            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });
        // 栏目标题格式
        $('input[name=seo_liststitle_format]').click(function(){
            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });
        // 文档标题格式
        $('input[name=seo_viewtitle_format]').click(function(){
            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });
        // 标题连接符号
        $('#seo_title_symbol').keyup(function(){
            var seo_title_symbol = $(this).val();
            $('.sp_seo_title_symbol').html(seo_title_symbol);
        });
        // 发布文档后，更新哪些页面
        $('input[name=seo_uphtml_after_home],input[name=seo_uphtml_after_channel],input[name=seo_uphtml_after_pernext]').click(function(){
            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });

        $('input[name=seo_pseudo]').click(function(){
            var _this = this;
            $('#dl_seo_dynamic_format').hide();
            $('#dl_seo_html_format').hide();
            $('#a_seo_build').hide();
            $('#dl_seo_uphtml_after').hide();
            // $('#tab_base_html').attr('style','display:none!important');
            $('#dl_seo_rewrite_format').hide();
            $('#seo_right_uphtml').hide();
            var seo_pseudo = $(_this).val();
            if (1 == seo_pseudo) {
                $('#dl_seo_rewrite_view_format').hide();
                if (1 != $('input[name=seo_dynamic_format]:checked').val()) {
                    $('#dl_seo_dynamic_format').show();
                }
                if (1 != $('#seo_inlet').val()) {
                    $('#dl_seo_force_inlet').show();
                } else {
                    $('#dl_seo_force_inlet').hide();
                }
            } else if (2 == seo_pseudo) {
                $('#dl_seo_force_inlet').hide();
                $('#dl_seo_rewrite_view_format').hide();
                msg = "静态模式下注意几点：<br/>1、修改模板记得生成<br/>2、更新后台数据记得生成<br/>3、安装的插件需要更新至最新版本";
                layer.alert(msg, {icon: 6, closeBtn:false, title: false});
                $('#dl_seo_html_format').show();
                $('#a_seo_build').show();
                $('#dl_seo_uphtml_after').show();
                // $('#tab_base_html').show();
                $('#seo_right_uphtml').show();
            } else {
                $('#dl_seo_rewrite_format').show();
                var seo_rewrite_format = $('input[name=seo_rewrite_format]:checked').val();
                if (seo_rewrite_format == 1 || seo_rewrite_format == 3) {
                    $('#dl_seo_rewrite_view_format').show();
                } else {
                    $('#dl_seo_rewrite_view_format').hide();
                }
                
                if (1 != $('#seo_inlet').val()) {
                    $('#dl_seo_force_inlet').show();
                } else {
                    $('#dl_seo_force_inlet').hide();
                }
            }

            // 静态模式下，是否改变任意一项配置
            change_html_confdata();

            var seo_pseudo_old = $('#seo_pseudo_old').val();
            if (3 == seo_pseudo) {
                layer_loading('正在检测');
                $.ajax({
                    url: "<?php echo url('Seo/ajax_checkHtmlDirpath'); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {seo_pseudo_new:seo_pseudo, _ajax:1},
                    // async: true,
                    success: function(res){
                        layer.closeAll();
                        if (res.code == 0) {
                            layer.msg(res.msg, {icon: 5, time: 1500});
                        } else {
                            if (res.data.msg != '') {
                                $('input[name=seo_pseudo]').each(function(i,o){
                                    if($(o).val() == seo_pseudo_old){
                                        $(o).attr('checked',true);
                                    } else {
                                        $(o).attr('checked',false);
                                    }
                                })
                                $('#dl_seo_html_format').show();
                                $('#a_seo_build').show();
                                $('#dl_seo_uphtml_after').show();
                                $('#seo_right_uphtml').show();
                                // $('#tab_base_html').show();
                                var seo_pseudo = $(_this).val();
                                //询问框
                                var height = res.data.height + 116;
                                if (350 <= height) height = 350;
                                var intro = '<style type="text/css">.layui-layer-content{height:270px!important;text-align:left!important;}</style>';
                                intro += res.data.msg;
                                var confirm1 = layer.confirm(intro, {
                                        title: false
                                        ,area: ['320px', height+'px']
                                        ,btn: ['手工删除','自动删除'] //按钮

                                    }, function(){
                                        layer.close(confirm1);

                                    }, function(){
                                        layer_loading('正在处理');
                                        $.ajax({
                                            url: "<?php echo url('Seo/ajax_delHtmlDirpath'); ?>",
                                            type: "POST",
                                            dataType: "json",
                                            data: {_ajax:1},
                                            success: function(res){
                                                layer.closeAll();
                                                if (1 == res.code) {
                                                    $('input[name=seo_pseudo]').each(function(i,o){
                                                        if($(o).val() == seo_pseudo){
                                                            $(o).attr('checked',true);
                                                        } else {
                                                            $(o).attr('checked',false);
                                                        }
                                                    })
                                                    $('#dl_seo_html_format').hide();
                                                    $('#a_seo_build').hide();
                                                    $('#dl_seo_uphtml_after').hide();
                                                    $('#seo_right_uphtml').hide();
                                                    // $('#tab_base_html').attr('style','display:none!important');
                                                    layer.msg(res.msg, {icon: 1, time: 1500});
                                                } else {
                                                    layer.alert(res.data.msg, {icon: 5, title: false});
                                                }
                                            },
                                            error: function(e){
                                                layer.closeAll();
                                                layer.alert(e.responseText, {icon: 5, title:false});
                                            }
                                        })
                                    }
                                );
                            }
                        }
                    },
                    error: function(e){
                        layer.closeAll();
                        layer.alert(e.responseText, {icon: 5, title:false});
                    }
                });
            }
        });

        $('input[name=seo_rewrite_format]').click(function(){
            var seo_rewrite_format = $(this).val();
            if (1 == seo_rewrite_format || 3 == seo_rewrite_format) {
                $('#dl_seo_rewrite_view_format').show();
            } else {
                $('#dl_seo_rewrite_view_format').hide();
            }
        });

        $('#seo_html_arcdir').keyup(function(){
            var seo_html_arcdir = $(this).val();
            if (seo_html_arcdir != '') {
                if (seo_html_arcdir.substr(0,1) == '/') seo_html_arcdir = seo_html_arcdir.substr(1);
                seo_html_arcdir = '/' + seo_html_arcdir;
                $('#tips_seo_html_arcdir_1').show();
                $('#tips_seo_html_arcdir_2').html(seo_html_arcdir);
            } else {
                $('#tips_seo_html_arcdir_1').hide();
            }
            $('#exp_seo_html_arcdir').html(seo_html_arcdir);

            // 静态模式下，是否改变任意一项配置
            change_html_confdata();
        });

        $('input[name="seo_force_inlet"]').click(function(){
            if (1 == $(this).val()) {
                layer.open({
                    type: 2,
                    title: false,
                    area: ['0px', '0px'],
                    shade: 0.0,
                    closeBtn: 0,
                    shadeClose: true,
                    content: '//<?php echo \think\Request::instance()->host(); ?>/api/Rewrite/testing.html',
                    success: function(layero, index){
                        layer.close(index);
                        var body = layer.getChildFrame('body', index);
                        var content = body.html();
                        if (content.indexOf("Congratulations on passing") == -1)
                        {
                            $('label[for=seo_force_inlet1]').removeClass('selected');
                            $('#seo_force_inlet1').attr('checked','');
                            $('label[for=seo_force_inlet0]').addClass('selected');
                            $('#seo_force_inlet0').attr('checked','checked');
                            layer.alert('不支持去除index.php，请<a href="javascript:void(0);" onclick="layer.closeAll();click_to_eyou_1575506523(\'https://www.eyoucms.com/plus/view.php?aid=7874\',\'去掉URL中的index.php教程\');">点击查看教程</a>', {icon: 2, title:false});
                        }
                    }
                });
            }
        });

        /**
         * 初始化数据缓存
         * @return {[type]} [description]
         */
        function init_data_cache() {
            $.ajax({
                url : "<?php echo url('Seo/init_data_cache'); ?>",
                type: 'post',
                data: {'_ajax': 1},
                dataType: 'JSON',
                success: function(res) {
                    
                }
            });
        }
        init_data_cache();

        // 自动检测隐藏index.php
        function checkInlet() {
            if (2 == $('input[name=seo_pseudo]:checked').val()) {
                $('#dl_seo_force_inlet').hide();
            }
            layer.open({
                type: 2,
                title: false,
                area: ['0px', '0px'],
                shade: 0.0,
                closeBtn: 0,
                shadeClose: true,
                content: '//<?php echo \think\Request::instance()->host(); ?>/api/Rewrite/setInlet.html',
                success: function(layero, index){
                    layer.close(index);
                    var body = layer.getChildFrame('body', index);
                    var content = body.html();
                    if (content.indexOf("Congratulations on passing") == -1)
                    {
                        $('#seo_inlet').val(0);
                        $('label[for=seo_force_inlet1]').removeClass('selected');
                        $('#seo_force_inlet1').attr('checked','');
                        $('label[for=seo_force_inlet0]').addClass('selected');
                        $('#seo_force_inlet0').attr('checked','checked');
                        if (2 != $('input[name=seo_pseudo]:checked').val()) {
                            $('#dl_seo_force_inlet').show();
                        }
                        $.ajax({
                            type : "POST",
                            url  : "/index.php?m=api&c=Rewrite&a=setInlet",
                            data : {seo_inlet:0,_ajax:1},
                            dataType : "JSON",
                            success: function(res) {

                            }
                        });
                    } else {
                        $('#seo_inlet').val(1);
                        $('#dl_seo_force_inlet').hide();
                        $('label[for=seo_force_inlet0]').removeClass('selected');
                        $('#seo_force_inlet0').attr('checked','');
                        $('label[for=seo_force_inlet1]').addClass('selected');
                        $('#seo_force_inlet1').attr('checked','checked');
                    }
                }
            });
        }
        checkInlet();
    });

    function adsubmit(){
        if($("input[name='seo_pseudo']:checked").val() == '2'){
            var seo_html_arcdir = $('input[name="seo_html_arcdir"]').val();
            if (seo_html_arcdir != '') {
                seo_html_arcdir = seo_html_arcdir_new = seo_html_arcdir.replace('\\', '/');
                var reg = /^([0-9a-zA-Z\_\-\/]+)$/;
                if (seo_html_arcdir != '/' && reg.test(seo_html_arcdir)) {
                    // 去掉最前面的斜杆
                    if (seo_html_arcdir_new.substr(0,1) == '/') seo_html_arcdir_new = seo_html_arcdir_new.substr(1);
                    var html_arcdir_arr = seo_html_arcdir_new.split("/");
                    var html_arcdir_one = html_arcdir_arr[0]; // 一级路径名
                    var seo_html_arcdir_limit = $('input[name="seo_html_arcdir_limit"]').val();
                    var seo_html_arcdir_limit_arr = seo_html_arcdir_limit.split(",");
                    if (seo_html_arcdir_limit_arr.indexOf(html_arcdir_one) >= 0){
                        layer.msg('页面保存路径的目录名 '+html_arcdir_one+' 与内置禁用的重复!', {icon: 2,time: 3000});
                        $('input[name="seo_html_arcdir"]').focus();
                        return false;
                    }
                }else{
                    showErrorMsg('页面保存路径的格式错误！');
                    $('input[name="seo_html_arcdir"]').focus();
                    return false;
                }
            }
        }
        layer_loading("正在处理");
        var init_html = $('#init_html').val();
        $.ajax({
            url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
            type: 'POST',
            dataType: 'json',
            data: $('#handlepost').serialize(),
            success: function(res){
                if (1 == res.code) {
                    if (2 == init_html || 2 != $("input[name='seo_pseudo']:checked").val()) {
                        // layer.closeAll();
                        layer.msg(res.msg, {icon: 1, time: 1000}, function(){
                            if(parent.$('.left_menu_2003004').length > 0){
                                $('.left_menu_2003004',window.parent.document).show();
                            }
                            window.location.href = res.url;
                        });
                    } else {
                        layer.closeAll();
                        var confirm1 = layer.confirm('配置成功，是否要生成整站页面？', {
                                shade: layer_shade,
                                area: ['480px', '190px'],
                                move: false,
                                title: '提示',
                                btnAlign:'r',
                                closeBtn: 3,
                                btn: ['是','否'] ,//按钮
                                success: function () {
                                      $(".layui-layer-content").css('text-align', 'left');
                                  }
                            }, function(){
                                var url = eyou_basefile+"?m=admin&c=Seo&a=site&lang="+__lang__;
                                var index = layer.open({
                                    type: 2,
                                    shade: layer_shade,
                                    title: '开始生成',
                                    area: ['500px', '300px'],
                                    fix: false, 
                                    maxmin: false,
                                    content: url,
                                    end: function(layero, index){
                                        layer.msg(res.msg, {icon: 1, time: 1000}, function(){
                                            window.location.href = res.url;
                                        });
                                    }
                                });
                            }, function(){
                                layer.msg(res.msg, {icon: 1, time: 1000}, function(){
                                    window.location.href = res.url;
                                });
                            }
                        );
                    }
                } else {
                    layer.closeAll();
                    layer.alert(res.msg, {icon: 5, title:false});
                }
            },
            error: function(e){
                layer.closeAll();
                layer.alert(e.responseText, {icon: 5, title:false});
            }
        });
    }

    function view_exp(id)
    {
        $('#'+id).toggle();
    }

    function seo_build(obj)
    {
        var is_change_data = $('#is_change_data').val();
        if (0 == is_change_data) {
            window.location.href = $(obj).data('url');
        } else {
            $.ajax({
                url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
                type: 'POST',
                dataType: 'json',
                data: $('#handlepost').serialize(),
                beforeSend:function(){
                    layer_loading('保存跳转');
                },
                success: function(res){
                    layer.closeAll();
                    if (0 == res.code) {
                        showErrorAlert(res.msg);
                    } else {
                        window.location.href = $(obj).data('url');
                    }
                },
                error: function(e){
                    layer.closeAll();
                    layer.alert(e.responseText, {icon: 5, title:false});
                }
            });
        }
    }
</script>

<div class="seo-right <?php if(2 != $config['seo_pseudo']): ?>none<?php endif; ?>" id="seo_right_uphtml">
    <style>
        .seo-right{
            position: fixed;
            top: 70px;
            margin-top: 0px;
    /*        top: 50%;
            margin-top: -185px;*/
            right: 30px;
            width: 350px;
            height: 280px;
            background-color:#fff;
            z-index:666666;
            border: 1px solid #ddd;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        .seo-html dt.tit {
           width: 60px;
           padding-left:20px;
        }   
    </style>
    <div class="page">
        <form method="get" id="html_handlepost" name="form2">
            <div class="ncap-form-default seo-html">
                <dl class="row">
                    <dt class="tit">
                        <label>整站页面</label>
                    </dt>
                    <dd class="opt w0">       
                        <a href="javascript:void(0);" id="up_site" class="ncap-btn ncap-btn-green">一键生成</a>
                        <span class="err"></span>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit">
                        <label>首页</label>
                    </dt>
                    <dd class="opt w0">       
                        <a href="javascript:void(0);" id="up_index" class="ncap-btn ncap-btn-green">一键生成</a>
                        <span class="err"></span>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit">栏目页</dt>
                    <dd class="opt w0">
                        <select name="html_typeid" id="html_typeid" style="width: 150px;">
                            <option value="0">所有栏目…</option>
                            <?php echo $select_html; ?>
                        </select>
                        &nbsp;<a href="javascript:void(0);" id="up_channel" class="ncap-btn ncap-btn-green">一键生成</a>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit">文档页</dt>
                    <dd class="opt w0">
                        <select name="html_arc_typeid" id="html_arc_typeid" style="width: 150px;">
                            <option value="0">所有文档…</option>
                            <?php echo $select_html; ?>
                        </select>
                        &nbsp;<a href="javascript:void(0);" id="up_article" class="ncap-btn ncap-btn-green">一键生成</a>
                        <p class="notic"></p>
                    </dd>
                </dl>
                <dl class="row">
                    <dt class="tit" style="width: 100%;">
                        <a id="a_seo_build" href="javascript:void(0);" data-url="<?php echo url('Seo/build'); ?>" onclick="seo_build(this);">更多静态生成配置>></a>
                    </dt>
                </dl>
            </div>
        </form>
    </div>
    <script type="text/javascript">

        function check_index_file()
        {
            var is_index_file = <?php echo (isset($is_index_file) && ($is_index_file !== '')?$is_index_file:0); ?>;
            if (0 == is_index_file) {
                showErrorAlert('网站根目录缺少 index.php 文件，请拷贝该文件上传到空间里！');
                return false;
            }
            return true;
        }

        $(function(){
            
            //生成整站
            $("#up_site").click(function(){
                if (!check_index_file()) {return false;}
                $.ajax({
                    url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: $('#handlepost').serialize(),
                    beforeSend:function(){
                        layer.load(3, {shade: layer_shade});
                    },
                    success: function(res){
                        layer.closeAll();
                        if (0 == res.code) {
                            showErrorAlert(res.msg);
                        } else {
                            var url = eyou_basefile+"?m=admin&c=Seo&a=site&uphtmltype=0&lang="+__lang__;
                            var index = layer.open({type: 2,shade: layer_shade,title: '开始生成',area: ['500px', '300px'],fix: false, maxmin: false,content: url});
                        }
                    },
                    error: function(e){
                        layer.closeAll();
                        layer.alert(e.responseText, {icon: 5, title:false});
                    }
                });
            })

            //生成首页
            $("#up_index").click(function(){
                if (!check_index_file()) {return false;}
                $.ajax({
                    url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: $('#handlepost').serialize(),
                    beforeSend:function(){
                        layer.load(3, {shade: layer_shade});
                    },
                    success: function(res){
                        if (0 == res.code) {
                            layer.closeAll();
                            showErrorAlert(res.msg);
                        } else {
                            var timestamp1 = Date.parse(new Date());
                            $.ajax({
                                url:__root_dir__+"/index.php?m=home&c=Buildhtml&a=buildIndex&lang="+__lang__+"&_ajax=1",
                                type:'GET',
                                dataType:'json',
                                data: {},
                                beforeSend:function(){
                                    // layer.load(3, {shade: [0.1,'#000']});
                                },
                                success:function(res1){
                                    if(res1.msg !== "" && -1 == res1.msg.indexOf('浏览')){
                                        layer.alert(res1.msg, {icon: 5, title:false,closeBtn: 0 },function () {
                                            layer.closeAll();
                                        } );
                                    }else{
                                        layer.closeAll();
                                        var timestamp2 = Date.parse(new Date());
                                        var timestamp3 = (timestamp2 - timestamp1) / 1000;
                                        if (timestamp3 < 1) timestamp3 = 1; 
                                        layer.msg("生成完毕，共耗时：<font color='red'>"+timestamp3+"</font> 秒",{icon:1,time:2000}); 
                                    }
                                },
                                complete:function(){
                                    // layer.alert(1, {icon: 5, title:false});
                                },
                                error: function(e){
                                    layer.closeAll();
                                    showErrorAlert(e.responseText);
                                }
                            });
                        }
                    },
                    error: function(e){
                        layer.closeAll();
                        showErrorAlert(e.responseText);
                    }
                });
            })
            
            //生成栏目页
            $("#up_channel").click(function(){
                if (!check_index_file()) {return false;}
                $.ajax({
                    url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: $('#handlepost').serialize(),
                    beforeSend:function(){
                        layer.load(3, {shade: layer_shade});
                    },
                    success: function(res){
                        layer.closeAll();
                        if (0 == res.code) {
                            showErrorAlert(res.msg);
                        } else {
                            var typeid = $("#html_typeid").val();     //栏目id
                            var url = eyou_basefile+"?m=admin&c=Seo&a=channel&typeid="+typeid+"&lang="+__lang__;
                            var index = layer.open({type: 2,shade: layer_shade,title: '开始生成',area: ['500px', '300px'],fix: false, maxmin: false,content: url});
                        }
                    },
                    error: function(e){
                        layer.closeAll();
                        showErrorAlert(e.responseText);
                    }
                });
            })

            //生成文章页
            $("#up_article").click(function(){
                if (!check_index_file()) {return false;}
                $.ajax({
                    url: "<?php echo url('Seo/handle', ['_ajax'=>1]); ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: $('#handlepost').serialize(),
                    beforeSend:function(){
                        layer.load(3, {shade: layer_shade});
                    },
                    success: function(res){
                        layer.closeAll();
                        if (0 == res.code) {
                            showErrorAlert(res.msg);
                        } else {
                            var typeid = $("#html_arc_typeid").val();     //栏目id
                            var url = eyou_basefile+"?m=admin&c=Seo&a=article&typeid="+typeid+"&lang="+__lang__;
                            var index = layer.open({type: 2,shade: layer_shade,title: '开始生成',area: ['500px', '300px'],fix: false, maxmin: false,content: url});
                        }
                    },
                    error: function(e){
                        layer.closeAll();
                        showErrorAlert(e.responseText);
                    }
                });
            })
        })
    </script>
</div>

<br/>
<div id="goTop">
    <a href="JavaScript:void(0);" id="btntop">
        <i class="fa fa-angle-up"></i>
    </a>
    <a href="JavaScript:void(0);" id="btnbottom">
        <i class="fa fa-angle-down"></i>
    </a>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('#think_page_trace_open').css('z-index', 99999);
    });
</script>
</body>
</html>