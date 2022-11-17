function HideKefu(){
    obj=document.getElementById("kefu_pannel");
    obj.style.display='none';
}
lastScrollYser = 0;
function HeartBeatser()
{
    diffY= document.body.scrollTop+document.documentElement.scrollTop;
    obj=document.getElementById("kefu_pannel");
    percent =1*(diffY-lastScrollYser);
    if(percent>0)
    {
         percent = Math.ceil(percent);
     }
    else
    {
         percent = Math.floor(percent);
    }
    obj.style.top=(parseInt(obj.style.top,10)+percent)+"px";
    lastScrollYser = lastScrollYser+percent;
}
window.setInterval("HeartBeatser()",1);