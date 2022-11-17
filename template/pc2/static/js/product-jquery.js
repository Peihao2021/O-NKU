(function($){
	//home_products
	//初始值
    $.fn.home_productsScrollInit = {
		oListparent:"",  //滚动区域父级
        oList: "",    //滚动区域
		Leftbtn:"",  //左按纽
		Rightbtn:"",   //右按纽
        oshowSum: "",  //展示区条目数
		oListchild:"", //子级标签
        showSum: 1,   //每次滚动条目数
		timer:"" , //间隔时间
		auto:"" //yes 是自动  no 不自动滚动
    }
    $.fn.home_productsScroll = function (options) {
        var n = $.extend({}, $.fn.home_productsScrollInit, options);
		var iList=$(n.oList).children("");
        var iListWidth = iList.outerWidth(true);
        var iListSum = iList.length; //The total number of news
        var dividers = Math.ceil(iListSum / n.showSum);     
        var moveWidth = iListWidth * n.showSum;
		$(n.oList).width(iListWidth*iListSum);
        var timer;
		
		$(n.Leftbtn).stop().click(function(){
			if (dividers == n.oshowSum) return;
			$(n.oList).css("margin-left",-moveWidth);
			$(n.oList + ">"+n.oListchild+":last").prependTo($(n.oList));
            $(n.oList + ":not(:animated)").animate({
                marginLeft: "+=" + moveWidth
            }, {
                duration: "slow", complete: function () {
                    $(n.oList).css("margin-left", "0px");	
                }
            });
		})
		$(n.Rightbtn).stop().click(function(){
			if (dividers == n.oshowSum) return;
            $(n.oList + ":not(:animated)").animate({
                marginLeft: "-=" + moveWidth
            }, {
                duration: "slow", complete: function () {
                    for (var j = 0; j < n.showSum; j++) {
                        $(n.oList + ">"+n.oListchild+":first").appendTo($(n.oList));
                    }
					$(n.oList).css("margin-left", "0px");
                }
            });
		})
		clearInterval(timer);
		if(n.auto=="yes"){
			stopscroll();
		}
		//鼠标悬浮暂停
		function stopscroll(){
			
			timer = setInterval(function () {
				$(n.Rightbtn).trigger("click");
			},n.timer);
			$(n.oListparent).hover(function(){
				 clearInterval(timer);
			 },function(){
					timer = setInterval(function () {
					$(n.Rightbtn).trigger("click");
			 	},n.timer); 
			 })	
		}
		
	     
    }
	
    
	
})(jQuery)