(function(){

	 
/**
   *高度一致
*/
var r_h = $(".f780"),
    l_h = $('.side');
if(r_h && l_h){
  l_h.css({"height":r_h.height()+"px"}) ;
}

/**
  *我的订阅
  *产品描述浮层的显示与隐藏
*/
var dy_li = $(".dy_li"),
    dy_txt = dy_li.find('.txt');
if(dy_li && dy_txt){
    dy_li.hover(function(){
        $(".txt",this).show();
        $(".name",this).hide();
        //描述内容超出三行显示省略号，依赖ellipsisSizeH.js
        var row = $(".tip_xz");
        for (var i = 0; i < row.length;i++) {
            $(row)[i].ellipsisSizeH(3);//超出高度显示省略号
        };
    },function(){
    	$(".txt",this).hide();
    	$(".name",this).show();
    }) 
}

/**
  *footer位置控制
*/
//if($('.fm1025_m')){
//$('#foot_wrap .fm1020').css({'margin-top':'-24px'});
//}

/**
  *倒计时
  *timer();//倒计时函数
*/
  //倒计时
var timer = function(timeId){
   var timeDown = $('#'+timeId);
   var time_wrap = timeDown.find('.time_wrap');
   var price_wrap = timeDown.find('.price_wrap');
   var timeSpan = timeDown.find('.timeDown span');
   var hiInput = timeDown.find('input:hidden').attr("value"); 
   var hiInput_b = timeDown.find('input:hidden').attr('data-type'); 

    //获得时间
    var reg = /[\:\-\s+]/; //用:或者*或者是空格将时间分割

    var time = hiInput.split(reg); 
    var timeType = hiInput_b.split(/\d+/);

  //时间差的计算
  var timerSub = function(){
      var ts = (new Date(time[0]-0, time[1]-1, time[2]-0, time[3]-0, time[4]-0, time[5]-0)).getTime() - (new Date()).getTime();//计算剩余的毫秒数 
      var leave1=ts%(24*3600*1000); 
      var leave2=leave1%(3600*1000);
      var leave3=leave2%(60*1000); 
      var dd = Math.floor(ts/(24*3600*1000)) ;//计算剩余的天数  
      var hh = Math.floor(leave1/(3600*1000));//计算剩余的小时数  
      var mm = Math.floor(leave2/(60*1000));//计算剩余的分钟数  
      var ss = Math.round(leave3/1000);//计算剩余的秒数  
      var ta = [checkTime(dd),checkTime(hh),checkTime(mm),checkTime(ss)];
      var ta_x = [checkTime_x(dd),checkTime_x(hh),checkTime_x(mm),checkTime_x(ss)];
      if($('#cn_timeDown')[0]){
        timeSpan = timeDown.find('span');
        timeSpan[0].innerHTML = ta_x[0]+timeType[1]+ta_x[1]+timeType[2]+ta_x[2]+timeType[3];
      }else{
        timeSpan[0].innerHTML = ta[1]+timeType[2]+ta[2]+timeType[3]+ta[3]+timeType[4];
      }
      function checkTime(t){    
         if (t < 10 && t >= 0) {    
             t = "0" + t;    
          } else if(t < 0){
            t = "00";//这个是为了防止当上线的时候已经在订阅状态了，这样显示的时间就不会是负的
          }  
         return t;    
     }

     function checkTime_x(t){    
         if (t < 0) {    
             t = "0";    
          }  
         return t;    
     }
    //当时间截止的时候就clear
     if(animation){
        if(ts<=0){
          clearInterval(animation);
          time_wrap.show();
          price_wrap.hide();
        }else if(ts > 0 && ts <= (24*3600*1000)){
          time_wrap.show();
          price_wrap.hide();
          
        }else{
          time_wrap.hide();
          price_wrap.show();
        }
     }
    }
    var animation = setInterval(timerSub,1000);

}  



//产品列表加上id
var timeIdAdd = function(){
  for(var i = 0; i < $('.dy_li').length; i++){
     $('.dy_li').eq(i).attr('id','pro_'+i);
  }
}
var timeDo = function(){
   if($('#cn_timeDown')[0]){
      timer('cn_timeDown');
   }else{
      timeIdAdd();
      for(var i = 1; i < $('.dy_li').length; i++){
       timer('pro_'+i);
      }
   }
}
timeDo();



    

})();


