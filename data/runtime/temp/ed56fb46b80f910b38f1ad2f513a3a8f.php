<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:23:"./template/pc/index.htm";i:1662952960;s:42:"C:\wwwroot\waiguo.com\template\pc\head.htm";i:1662952960;s:44:"C:\wwwroot\waiguo.com\template\pc\footer.htm";i:1662952960;}*/ ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_title"); echo $__VALUE__; ?></title>
<meta name="description" content="<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_description"); echo $__VALUE__; ?>" />
<meta name="keywords" content="<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_keywords"); echo $__VALUE__; ?>" />
<link href="<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_cmspath"); echo $__VALUE__; ?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link href="/template/pc/css/index.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>
 <div class="header clear">
  <div class="wrap ty">
    <div class="logo flt"><a href="<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_basehost"); echo $__VALUE__; ?>" style="font-family: logo"> 
		<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_name"); echo $__VALUE__; ?></a></div>
    <div class="menu frt">
      <ul id="starlist">
        <li <?php if(\think\Request::instance()->param('m') == 'Index'): ?>class="on"<?php endif; ?>>
			<a href="<?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_basehost"); echo $__VALUE__; ?>" class="on2"><span>Home</span> </a> </li>
        <?php  if(isset($ui_typeid) && !empty($ui_typeid)) : $typeid = $ui_typeid; else: $typeid = ""; endif; if(empty($typeid) && isset($channelartlist["id"]) && !empty($channelartlist["id"])) : $typeid = intval($channelartlist["id"]); endif;  if(isset($ui_row) && !empty($ui_row)) : $row = $ui_row; else: $row = 10; endif; $tagChannel = new \think\template\taglib\eyou\TagChannel; $_result = $tagChannel->getChannel($typeid, "top", "on", ""); if(is_array($_result) || $_result instanceof \think\Collection || $_result instanceof \think\Paginator): $i = 0; $e = 1;$__LIST__ = is_array($_result) ? array_slice($_result,0, $row, true) : $_result->slice(0, $row, true); if( count($__LIST__)==0 ) : echo htmlspecialchars_decode("");else: foreach($__LIST__ as $key=>$field1): $field1["typename"] = text_msubstr($field1["typename"], 0, 100, false); $__LIST__[$key] = $_result[$key] = $field1;$i= intval($key) + 1;$mod = ($i % 2 ); ?>
        <li class="<?php echo $field1['currentclass']; ?>"><a class="on2"><span><?php echo $field1['typename']; ?></span> </a>
			<?php if(!(empty($field1['children']) || (($field1['children'] instanceof \think\Collection || $field1['children'] instanceof \think\Paginator ) && $field1['children']->isEmpty()))): ?> <em></em><?php endif; if(!(empty($field1['children']) || (($field1['children'] instanceof \think\Collection || $field1['children'] instanceof \think\Paginator ) && $field1['children']->isEmpty()))): ?>
          <ul class="sub">
            <?php  if(isset($ui_typeid) && !empty($ui_typeid)) : $typeid = $ui_typeid; else: $typeid = ""; endif; if(empty($typeid) && isset($channelartlist["id"]) && !empty($channelartlist["id"])) : $typeid = intval($channelartlist["id"]); endif;  if(isset($ui_row) && !empty($ui_row)) : $row = $ui_row; else: $row = 10; endif;if(is_array($field1['children']) || $field1['children'] instanceof \think\Collection || $field1['children'] instanceof \think\Paginator): $i = 0; $e = 1;$__LIST__ = is_array($field1['children']) ? array_slice($field1['children'],0,10, true) : $field1['children']->slice(0,10, true); if( count($__LIST__)==0 ) : echo htmlspecialchars_decode("");else: foreach($__LIST__ as $key=>$field2): $field2["typename"] = text_msubstr($field2["typename"], 0, 100, false); $__LIST__[$key] = $_result[$key] = $field2;$i= intval($key) + 1;$mod = ($i % 2 ); ?>
            <li><a href="<?php echo $field2['typeurl']; ?>" ><?php echo $field2['typename']; ?></a></li>
            <?php ++$e; endforeach; endif; else: echo htmlspecialchars_decode("");endif; $field2 = []; ?>
          </ul>
          <?php endif; ?> </li>
        <?php ++$e; endforeach; endif; else: echo htmlspecialchars_decode("");endif; $field1 = []; ?>
      </ul>
    </div>
    <div id="mnavh" class="clear"><span class="navicon"></span></div>
  </div>
</div>

	
	<div class="main ty">
	<div class="tpbj">
	<Img src="/template/pc/images/1.jpg">
	</div>
	<div class="ycyx">
		<li><img src="/template/pc/images/2.png">投稿邮箱:468785938@qq.com</li>
		<li><img src="/template/pc/images/1.png"> 举报投诉邮箱:4154050@qq.com</li>
		  
		</div>
		
		
		
	
	</div>
	 	<div class="clear"></div>
	<div class="footer ">
		<P class="ty"><?php  $tagGlobal = new \think\template\taglib\eyou\TagGlobal; $__VALUE__ = $tagGlobal->getGlobal("web_copyright"); echo $__VALUE__; ?></P>
		
		</div>
		
	
<script type="text/javascript" src="/template/pc/js/jquery-1.8.3.min.js"></script> 
<script>
	$("#mnavh").click(function(){
    $("#starlist").toggle();
	$("#mnavh").toggleClass("open");
	});
	
	 $(".menu ul li em").click(function(event) {
   $(this).next('.sub').slideToggle();
   });
	$(".sidebar-toggle").click(function(){
  
	$("body").toggleClass("close");
	});
	</script>
	
	
	
	
	

</body>
</html>
