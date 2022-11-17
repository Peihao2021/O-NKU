/*
html: 
	  <div class="scollbox">
      <div class="scollpic"><!--图片 图片滚动区容器id定为PicContainer-->
          <div id="PicContainer" class="picul">
            <dl>
            	<dt><img src="" /></dt>
                <dd>图片1</dd>
            </dl>
				....
			<dl>
            	<dt><img src="" /></dt>
                <dd>图片1</dd>
            </dl>
          </div>
      </div>
  	  <a class="leftbtn arrow" href="javascript:void(0);"><img src="" /></a><!--左按纽 容器class设为arrow-->
      <a class="rightbtn arrow1" href="javascript:void(0);"><img src="" /></a><!--右按纽容器 class设为arrow1-->
  </div>
  
css:

	.scollbox{ position:relative; width:310px; height:69px;}
	.scollpic{ width:228px; height:89px; overflow:hidden;  position:absolute; top:0px; left:41px;}
	.picul dl{ float:left; margin-right:10px; height:89px; width:69px; cursor:pointer;}
	.picul dl dt{ width:69px; height:69px;}
	.picul dl dd{ width:69px; height:20px; text-align:center;}
	.leftbtn{ display:block; position:absolute; width:24px; height:21px; left:0; top:21px;}
	.rightbtn{ display:block; position:absolute; width:24px; height:21px; right:0; top:21px;}
  
*/
var SellerScroll = function (options) {
    this.SetOptions(options);
    this.lButton = this.options.lButton;
    this.rButton = this.options.rButton;
    this.oList = this.options.oList;
    this.showSum = this.options.showSum;

    this.iList = $("#" + this.options.oList + ">li");
    this.iListSum = this.iList.length;
    this.iListHeight = this.iList.outerHeight(true);
    this.moveHeight = this.iListHeight * this.showSum;
    this.dividers = Math.ceil(this.iListSum / this.showSum);//共分为多少块 
    this.moveMaxOffset = (this.dividers - 1) * this.moveHeight;
    $("#" + this.options.oList).height(this.dividers * this.moveHeight);
    this.LeftScroll();
    this.RightScroll();
};
SellerScroll.prototype = {
    SetOptions: function (options) {
        this.options = {
            lButton: "wleftarrow",
            rButton: "wrightarrow",
            oList: "scrollxh",
            showSum: 1//一次滚动多少个items 
        };
        $.extend(this.options, options || {});
    },
    ReturnLeft: function () {
        return isNaN(parseInt($("#" + this.oList).css("margin-top"))) ? 0 : parseInt($("#" + this.oList).css("margin-top"));
    },
    LeftScroll: function () {
        if (this.dividers == 1) return;
        var _this = this, currentOffset;
        $("." + this.lButton).click(function () {

            currentOffset = _this.ReturnLeft();
            $("#" + _this.oList).css("margin-top", -_this.moveHeight);
            for (var j = _this.dividers - 1; j > (_this.dividers - 1) - _this.showSum; j--) {
                $("#" + _this.options.oList + ">li:last").prependTo($("#" + _this.oList));
            }

            $("#" + _this.oList + ":not(:animated)").animate({ marginTop: "+=" + _this.moveHeight }, {
                duration: "slow", complete: function () {
                    $("#" + _this.oList).css("margin-top", "0px");

                }
            });
        });
    },
    RightScroll: function () {

        if (this.dividers == 1) return;
        var _this = this, currentOffset;
        $("." + this.rButton).click(function () {
            currentOffset = _this.ReturnLeft();
            $("#" + _this.oList + ":not(:animated)").animate({ marginTop: "-=" + _this.moveHeight }, {
                duration: "slow", complete: function () {

                    for (var j = 0; j < _this.showSum; j++) {

                        $("#" + _this.options.oList + ">li:first").appendTo($("#" + _this.oList));

                    }
                    $("#" + _this.oList).css("margin-top", "0px");

                }
            });
        });
    }
};
$(document).ready(function () {
    var ff = new SellerScroll();
    var timer = setInterval(function () {
        $(".wrightarrow").trigger("click");
    }, 2000);
    $(".news").hover(function () {
        clearTimeout(timer);
    }, function () {
        timer = setInterval(function () {
            $(".wrightarrow").trigger("click");
        }, 2000);
    });

});
