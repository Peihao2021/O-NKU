
function showErrorMsg(msg){
    layer.msg(msg, {icon: 5,time: 2000});
}
function showErrorAlert(msg, icon){
    if (!icon && icon != 0) {
        icon = 5;
    }
    layer.alert(msg, {icon: icon, title: false, closeBtn: false});
}

function showMbErrorMsg(msg){
    layer.open({
        content: msg
        ,skin: 'footer'
    });
}
function showMbErrorAlert(msg){
    layer.open({
        content: '<font color="red">提示：'+msg+'</font>'
        ,btn: '确定'
    });
}

/*
 * 上传图片 后台专用
 * @access  public
 * @null int 一次上传图片张图
 * @elementid string 上传成功后返回路径插入指定ID元素内
 * @path  string 指定上传保存文件夹,默认存在public/upload/temp/目录
 * @callback string  回调函数(单张图片返回保存路径字符串，多张则为路径数组 )
 */
var layer_GetUploadify;
// PC端上传头像
function GetUploadify(num,elementid,path,callback,url,title,is_mobile)
{
    if (layer_GetUploadify){
        layer.close(layer_GetUploadify);
    }
    if (num > 0) {
        if (!url) url = GetUploadify_url;
        if (!title) {
            if (callback.indexOf('head_pic_call_back') > -1) {
                title = '上传头像';
            } else {
                title = '选择上传';
            }
        }
        if (!is_mobile) is_mobile = 0;
        
        if (url.indexOf('?') > -1) {
            url += '&';
        } else {
            url += '?';
        }

        var upurl = url+'num='+num+'&input='+elementid+'&path='+path+'&func='+callback;
        var area = is_mobile==0?['50%', '60%']:['100%', '100%'];
        layer_GetUploadify = layer.open({
            type: 2,
            title: title,
            shadeClose: false,
            shade: 0.3,
            maxmin: true, //开启最大化最小化按钮
            area: area,
            content: upurl
         });
    } else {
        showErrorAlert('允许上传0张图片');
        return false;
    }
}

// 手机端上传头像
function GetUploadify_mobile(num, url, title)
{
    var scriptUrl = '/public/plugins/layer_mobile/layer.js';
    // 支持子目录
    if (typeof __root_dir__ != "undefined") {
        scriptUrl = __root_dir__ + scriptUrl;
    }
    if (typeof __version__ != "undefined") {
        scriptUrl = scriptUrl + '?v=' + __version__;
    }
    // end
    $.getScript(scriptUrl, function(){
        if (num > 0) {
            if (!url) url = GetUploadify_url;
            if (!title) title = '头像';
            
            if (url.indexOf('?') > -1) {
                url += '&';
            } else {
                url += '?';
            }

            var content = $('#update_mobile_file').html();
            content = content.replace(/up_f/g, 'upfile');
            content = content.replace(/form1/g,'form2'); 
            if ('缩略图' == title) {
                content += '<input type="hidden" id="UpFileType" value="1">';
            }else{
                content += '<input type="hidden" id="UpFileType" value="0">';
            }
            layer_GetUploadify = layer.open({
                type:1,
                title:title,
                anim:'up',
                style:'position:fixed; bottom:0; left:0; width: 100%; padding:10px 0; border:none;max-width: 100%;',
                content:content,
             });
        } else {
            layer.open({
                content: '允许上传0张图片',
                skin: 'footer',
            });
            return false;
        }
    });
}

// 加载层
function layer_loading(msg){
    var loading = layer.msg(
    msg+'...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;请勿刷新页面', 
    {
        icon: 1,
        time: 3600000, //1小时后后自动关闭
        shade: [0.2] //0.1透明度的白色背景
    });
    //loading层
    var index = layer.load(3, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });

    return loading;
}

// 加载层
function layer_loading_mini(icon){
    //loading层
    if (!icon) {
        icon = 2;
    }
    var loading = layer.load(icon, {
        shade: [0.2,'#000'] //0.1透明度的白色背景
    });

    return loading;
}

// 渲染编辑器
function showEditor_1597892187(elemtid){

    var content = '';

    try{
        content = UE.getEditor(elemtid).getContent();
        UE.getEditor(elemtid).destroy();
    }catch(e){}

    var serverUrl = __root_dir__+'/index.php?m=user&c=Uploadify&a=index&savepath=ueditor&lang='+__lang__;
    var options = {
        serverUrl : serverUrl,
        zIndex: 999,
        initialFrameWidth: "100%", //初化宽度
        initialFrameHeight: 450, //初化高度            
        focus: false, //初始化时，是否让编辑器获得焦点true或false
        maximumWords: 99999,
        removeFormatAttributes: 'class,style,lang,width,height,align,hspace,valign',//允许的最大字符数 'fullscreen',
        pasteplain:false, //是否默认为纯文本粘贴。false为不使用纯文本粘贴，true为使用纯文本粘贴
        autoHeightEnabled: false,
        toolbars: [['fullscreen', 'forecolor', 'backcolor', 'removeformat', '|', 'simpleupload', 'unlink', '|', 'paragraph', 'fontfamily', 'fontsize']],
        // xss 过滤是否开启,inserthtml等操作
        xssFilterRules: true,
        //input xss过滤
        inputXssFilter: true,
        //output xss过滤
        outputXssFilter: true
    };
    
    eval("ue_"+elemtid+" = UE.getEditor(elemtid, options);ue_"+elemtid+".ready(function() {ue_"+elemtid+".setContent(content);});");
}

/**
 * 设置cookie
 * @param {[type]} name  [description]
 * @param {[type]} value [description]
 * @param {[type]} time  [description]
 */
function ey_setCookies(name, value, time)
{
    var cookieString = name + "=" + escape(value) + ";";
    if (time != 0) {
        var Times = new Date();
        Times.setTime(Times.getTime() + time);
        cookieString += "expires="+Times.toGMTString()+";"
    }
    document.cookie = cookieString+"path=/";
}

// 读取 cookie
function ey_getCookie(c_name)
{
    if (document.cookie.length>0)
    {
        c_start = document.cookie.indexOf(c_name + "=")
        if (c_start!=-1)
        {
            c_start=c_start + c_name.length+1
            c_end=document.cookie.indexOf(";",c_start)
            if (c_end==-1) c_end=document.cookie.length
            return unescape(document.cookie.substring(c_start,c_end))
        }
    }
    return "";
}

function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
}