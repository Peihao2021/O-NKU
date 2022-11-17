$(function () {

    //console.log($(window).width());

    if ($(window).width() < 790) {

        if ($.browser.msie && ($.browser.version <= "8.0") && !$.support.style) {
        }
        else {
           // $("#banner a, #banner").height(267 * $(window).width() / 1000);
            $("#banner a, #banner").height(200);
            $(".adinfocpul img").height($(".adinfocpul img").eq(0).width());
            $(".adinfocpul").height($(".adinfocpul li").eq(0).height()*2)
        }

    }

    //回到顶部 begin
    $(window).scroll(function () {

        if ($(window).scrollTop() > 300) {
            $("#floatblock").fadeIn(500);
        }
        else {
            $("#floatblock").fadeOut(500);
        }
    }); //end

    //重要信息弹出框

    $(".floatblock_ss").click(function () {
        $("#impbox").stop().show();
    });
    $(".impboxinfo .btn").click(function () {
        $("#impbox").stop().hide();
    });
   // console.log(1)
    $(".impboxinfo").css("margin-top", ($(window).height() - 260) / 2);
    $(".impboxinfo .searchInput").focus(function () {
        $(".impboxinfo").css("margin-top", 50);
    }).blur(function () {
        $(".impboxinfo").css("margin-top", ($(window).height() - 260) / 2);
        $("#impbox").stop().hide();
        
    });

    //var impboxtnum = 8;
	// var impboxt = setTimeout(function(){
		 
	//	 $("#impbox").hide();
	//	 },9000);
		
	//	 setInterval(function(){ 
	//	 $("#impbox").find("a.btn").children("span").html(--impboxtnum);
	//	 },1000);
		

    //end


});