
var axupimgs = new Array();
var arrimg = new Array();
var arrimgname = new Array();
$(function() {
    // 左侧分组里的图片数量
    $('#count_'+type_id, window.parent.document).html(countimg);
    var img_id_upload = $.cookie("img_id_upload");
    if (undefined != img_id_upload && img_id_upload.length > 0) {
        arrimg = img_id_upload.split(",");
    }
    var imgname_id_upload = $.cookie("imgname_id_upload");
    if (undefined != imgname_id_upload && imgname_id_upload.length > 0){
        arrimgname = imgname_id_upload.split(",");
    }
    // 检测是否选择
    if (num > 1) {
        $("#file_list li").each(function(index, item) {
            $(item).removeClass('up-over');
            var val = $(item).attr("data-img");
            for (var i = arrimg.length - 1; i >= 0; i--) {
                if (arrimg[i].indexOf(val) != -1 || val.indexOf(arrimg[i]) != -1) {
                    $(item).addClass('up-over');
                }
            }
        });
        $('.removeall').html('删除选中('+arrimg.length+')');
        if (arrimg.length > 1) {
            $('.removeall').show();
        } else {
            $('.removeall').hide();
        }
    } else {
        $("#file_list li").each(function(index, item) {
            $(item).removeClass('up-over');
            var val = $(item).attr("data-img");
            for (var i = arrimg.length - 1; i >= 0; i--) {
                if (arrimg[i].indexOf(val) != -1 || val.indexOf(arrimg[i]) != -1) {
                    $(item).addClass('up-over');
                    break;
                }
            }
        });
    }
});

// 删除列表图片
function delimg(obj, img_id) {
    layer_loading('正在处理');
    var img_id_arr = [img_id];
    $.ajax({
        type: 'post',
        url: eyou_basefile + "?m="+module_name+"&c=Uploadimgnew&a=del_uploadsimg&lang=" + __lang__,
        data: {img_id:img_id_arr, _ajax:1},
        dataType: 'json',
        success: function (res) {
            layer.closeAll();
            if (res.code == 1) {
                layer.msg(res.msg, {icon: 6, time: 1000}, function() {
                    window.location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 5});
            }
        },
        error : function(e) {
            layer.closeAll();
            layer.alert(e.responseText, {icon: 5, title: false, closeBtn: false});
        }
    });
}

// 删除选中的图片记录
function batch_delimg(obj) {
    var img_id_arr = [];
    $('#file_list li').each(function(i,o){
        if($(o).hasClass('up-over')){
            img_id_arr.push($(o).attr('data-id'));
        }
    })
    if(img_id_arr.length == 0){
        layer.msg('请至少选择一张！', {icon: 5});
        return;
    }

    layer_loading('正在处理');
    $.ajax({
        type: 'post',
        url : eyou_basefile + "?m="+module_name+"&c=Uploadimgnew&a=del_uploadsimg&lang=" + __lang__,
        data: {img_id: img_id_arr, _ajax: 1},
        dataType: 'json',
        success: function (res) {
            layer.closeAll();
            if (res.code == 1) {
                $.cookie("img_id_upload", "");
                $.cookie("imgname_id_upload", "");
                layer.msg(res.msg, {icon: 6, time: 1000}, function() {
                    window.location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 5});
            }
        },
        error : function(e) {
            layer.closeAll();
            layer.alert(e.responseText, {icon: 5, title: false, closeBtn: false});
        }
    });
}

function getLastMonth(){
    var now=new Date();
    var year = now.getFullYear();//getYear()+1900=getFullYear()
    var month = now.getMonth() +1;//0-11表示1-12月
    var day = now.getDate();
    var dateObj = {};
    if(parseInt(month)<10){
        month = "0"+month;
    }
    if(parseInt(day)<10){
        day = "0"+day;
    }

    dateObj.now = year + '-'+ month + '-' + day;

    if (parseInt(month) ==1) {//如果是1月份，则取上一年的12月份
        dateObj.last = (parseInt(year) - 1) + '-12-' + day;
        return dateObj;
    }

    var  preSize= new Date(year, parseInt(month)-1, 0).getDate();//上月总天数
    if (preSize < parseInt(day)) {//上月总天数<本月日期，比如3月的30日，在2月中没有30
        dateObj.last = year + '-' + month + '-01';
        return dateObj;
    }

    if(parseInt(month) <=10){
        dateObj.last = year + '-0' + (parseInt(month)-1) + '-' + day;
        return dateObj;
    }else{
        dateObj.last = year + '-' + (parseInt(month)-1) + '-' + day;
        return dateObj;
    }
}

// layui 操作
layui.use(function() {
    var layer = layui.layer,
    form = layui.form,
    $ = layui.$,
    element = layui.element,
    laydate = layui.laydate;
    var timeObj = getLastMonth();

    // 日期时间范围
    var laydate_value = '';
    if (eytime == '') {
        laydate_value = timeObj.last + ' - ' + timeObj.now;
    }
    laydate.render({
        elem: '#eytime',
        type: 'date',
        range: true,
        value: laydate_value,
        calendar: true,
        max: timeObj.now,//默认最大值为当前日期
        done: function(value) {
            $('#eytime').val(value);
            $('#searchForm').submit();
        }
    });
    if (eytime == '') {
        lay('#eytime').val('');
    }

    // 点击选中保存图片
    $(document).on("click", ".image-list li .picbox", function() { 
        var li = $(this).parent('.image-list li');
        var val = li.attr("data-img");
        var title =  li.attr("data-title");
        var img_id = li.attr("data-id");
        var selectlayer = li.hasClass('up-over');
        if (selectlayer) {
            li.removeClass('up-over');
            var indx = arrimg.indexOf(val); 
            if(indx != -1) arrimg.splice(indx, 1);
            var indx = arrimgname.indexOf(title);
            if(indx != -1) arrimgname.splice(indx, 1);
        }

        if (num > 1) {
            if (!selectlayer) {
                li.addClass('up-over');
                arrimg.push(val);
                arrimgname.push(title);
            }
        } else {
            $.cookie("img_id_upload", "");
            $.cookie("imgname_id_upload", "");
            $("#file_list li").removeClass('up-over');
            if (!selectlayer) {
                li.addClass('up-over');
                arrimg = [];
                arrimg.push(val);
                arrimgname = [];
                arrimgname.push(title);
            }
        }
        $.cookie("img_id_upload", arrimg.join());
        $.cookie("imgname_id_upload", arrimgname.join());
        document.querySelector('.removeall').innerText = '删除选中('+arrimg.length+')';
        if (arrimg.length > 1) {
            $('.removeall').show();
        } else {
            $('.removeall').hide();
        }
    });
});

// 添加文件
document.querySelector('#topbar .addfile').addEventListener("click", function(){
    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    if (num > 1) {
        input.setAttribute('multiple', 'multiple');
    }
    input.setAttribute('accept', image_accept);
    input.setAttribute('onchange', "addfileChange(this);");
    input.click();
});

function addfileChange(obj)
{
    var files = obj.files;
    if (files.length > num) {
        layer.alert('每次最多可上传'+num+'张！', {icon: 5, title: false, closeBtn: false});
        return false;
    }
    if (document.querySelector('.addfiletext').innerText != '上传图片') return false;
    // addList(files);
    if ($('#file_list li').length == 0) {
        $('#file_list').html('');
    }
    for (let i = 0; i < files.length; i++) {
        axupimgs.push(files[i]);
    }
    if (axupimgs.length > 0) {
        layer_loading('正在上传');
        $('#file_list li.up-no').each(function(item) {
            var el = item.get(0);
            el.classList ? el.classList.add('up-now') : el.className+=' up-now';
        });
        document.querySelector('.addfiletext').innerText = '上传中...';
        upAllFiles(0);
    }
}

// 添加列表
function addList(files) {
    var files_sum = files.length;
    var vDom = document.createDocumentFragment();
    for (let i = 0; i < files_sum; i++) {
        let file = files[i];
        let blobUrl = window.URL.createObjectURL(file);
        axupimgs.unshift(file);

        var add_time = formatDate();
        let li = document.createElement('li');
        li.setAttribute('class','up-no');
        li.setAttribute('data-time',file.lastModified);
        li.setAttribute('data-img',blobUrl);
        li.innerHTML='<div class="picbox"><img src="'+blobUrl+'"><div class="image-select-layer"><i class="layui-icon layui-icon-ok-circle"></i></div></div><div class="namebox" style="height: 15px;"><span class="eyou_imgtime">'+file.name+'</span></div>';
        vDom.appendChild(li);
    }
    if ($('#file_list li').length == 0) {
        $('#file_list').html('');
    }
    var list = document.querySelector('#file_list');
    list.insertBefore(vDom, list.childNodes[0]);
}

// 当前的日期时间格式
function formatDate() {
    var date = new Date();
    var YY = date.getFullYear() + '-';
    var MM = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    var DD = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate());
    var hh = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
    var mm = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
    var ss = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
    return YY + MM + DD +" "+hh + mm + ss;
}

// 图片上传
var file_i = 0;
function upAllFiles(n) {
    var len = axupimgs.length;
    file_i = n;
    if (len == n) {
        file_i = 0;
        // layer_loading('正在上传');
        document.querySelector('#topbar .addfiletext').innerText = '上传图片';
        return true;
    }

    // 上传的图片数量
    var img_len = file_i + 1;
    if (axupimgs[n] != '') {
        if (n > num - 1) {
            layer.alert('最多一次性上传'+num+'张！', {icon: 5, title: false, closeBtn: false});
            return false;
        }
        var img_id_upload_tmp = $.cookie("img_id_upload");
        if (undefined != img_id_upload_tmp && img_id_upload_tmp.length > 0) {
            arrimg = img_id_upload_tmp.split(",");
        } else {
            arrimg = [];
        }
        var imgname_id_upload_tmp = $.cookie("imgname_id_upload");
        if (undefined != imgname_id_upload_tmp && imgname_id_upload_tmp.length > 0) {
            arrimgname =  imgname_id_upload_tmp.split(",");
        } else {
            arrimgname = [];
        }
        var form = new FormData();
        var file = axupimgs[n];
        form.append('_ajax', 1);
        form.append('file', file);
        form.append('type_id', type_id);
        $.ajax({
            type: 'post',
            url : UploadUpUrl,
            data: form,
            contentType: false,
            processData: false,
            dataType: 'json',
            // async: false,
            success: function (res) {
                if (res.state == 'SUCCESS') {

                    var html = '';
                    html += '<li class="up-no up-over" data-time="'+file.lastModified+'" data-img="'+res.url+'" data-title="'+file.name+'">';
                    html += '   <div class="picbox">';
                    html += '       <img src="'+res.url+'">';
                    html += '       <div class="image-select-layer"><i class="layui-icon layui-icon-ok-circle"></i></div>';
                    html += '   </div>';
                    html += '   <div class="namebox" style="height: 15px;"><span class="eyou_imgtime">'+file.name+'</span></div>';
                    html += '</li>';
                    $('#file_list').prepend(html);

                    if (num == 1) {
                        $.cookie("img_id_upload", "");
                        arrimg = [];
                        $.cookie("imgname_id_upload", "");
                        arrimgname = [];
                    }
                    arrimg.push(res.url);
                    arrimgname.push(file.name);
                    $.cookie("img_id_upload", arrimg.join());
                    $.cookie("imgname_id_upload", arrimgname.join());

                    if (img_len == len) {
                        layer.closeAll();
                        layer.msg('上传成功', {icon: 6, time: 1000, shade: [0.2]}, function() {
                            window.location.reload();
                        });
                    }
                } else {
                    layer.closeAll();
                    layer.msg(res.state, {icon: 5});
                }
                n++;
                upAllFiles(n);
            },
            error : function(e) {
                $('#file_list li.up-now').each(function(item) {
                    var el = item.get(0);
                    el.setAttribute('class','up-no');
                });
                layer.closeAll();
                layer.alert(e.responseText, {icon: 5, title: false, closeBtn: false});
            }
        })
    }
}

// 加载框
function layer_loading(msg) {
    var loading = layer.msg(
    msg+'...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;请勿刷新页面', 
    {
        icon: 6,
        time: 3600000, //1小时后后自动关闭
        shade: [0.2] //0.1透明度的白色背景
    });
    //loading层
    var index = layer.load(3, {
        shade: [0.1,'#fff'] //0.1透明度的白色背景
    });
    return loading;
}