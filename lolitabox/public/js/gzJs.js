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
if($('.fm1025_m')){
$('#foot_wrap .fm1020').css({'margin-top':'-24px'});
}

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


/**
   *购物车
   *numpro是计算订阅的产品的数量
*/

//chart固定
var meg = $('#meg');
var ids =$("#ids");
if(meg && ids){
  function findDimensions(){           
      var isIE6 = !-[1,] && !window.XMLHttpRequest;
      var scrolltop_top =  ids.offset().top;
      var scrolltop_left =  ids.offset().left;    
      $(window).scroll(function(){//定位导航栏
          var H = $(this).scrollTop();
          if( H >= scrolltop_top){
               if(isIE6){
                  meg.css({ "position": "absolute","top":H-82+"px"});
          
               }else{
                  meg.css({"position":"fixed","top":"-4px"});
              }  
          }else{
              meg.css({"position":"relative","z-index":"10"}); 
          }
    });
  }
  findDimensions();
  window.onresize=findDimensions;
}

//chart_list展开
$('.open_chart').click(function(){
  $('.chart_list').show();
  $(this).hide();
  $('.chart_ico_i').hide();
  $('.chart_ico_m').show();
  $('.close_chart').show();
  $('.chart_info').find('.info').removeClass('tr');
});
//页面滚动时chart_list收缩
$('.close_chart').click(function(){ 
  $('.chart_list').hide();
  $(this).hide();
  $('.chart_ico_i').show();
  $('.chart_ico_m').hide();
  $('.open_chart').show();
  $('.chart_info').find('.info').removeClass('tr');
})

var buy_box = $('#sider_wrap');
var all_box = $('#all_box');
var pro_btn = all_box.find("input:button");
var buyLiArray = new Array();
var numpro = 0,that,numli=0;
//mark标记
if(all_box){
   for(var i = 0; i < pro_btn.length; i++){
      pro_btn.eq(i).attr('mark','pro_'+i);
   }
}

pro_btn.click(function(){
    proBtnClick($(this));
    slider();
});
//点击按钮加入产品开始
function proBtnClick(btnobj){
      var oValue = btnobj.attr('mark');
      var imgsrc = btnobj.parents('.mn').find('.img img').attr('src'); 
      var proname = btnobj.parents('.mn').find('.name').text();
      var proprice = btnobj.prev().text();
      var max_num = btnobj.attr('max_num');
      if($.inArray(oValue,buyLiArray) == -1){
         numpro++;
         buyLiArray.push(oValue);
         var addHtml = '<li><dl><dt class="img"><img src="'+imgsrc+
                       '" alt=""/></dt><dd class="name">'+proname+
                       '</dd><dd class="price">'+proprice+
                       '</dd><dd class="num_ctrl" id="id_'+oValue+
                       '"><input type="button" class="num_down"/><input type="text" class="num" max_num="'+max_num+
                       '" value="1"/><input type="button" class="num_up"/></dd></dl></li>'
        buy_box.find('ul').append(addHtml);
      }else{
        //alert("你已经添加过此产品！")
        var clickpro = $("#id_"+oValue).find('.num_up');
            numControl(clickpro,"+");//点击相同的产品，只是数量上增加
      }
}

//订阅内容滚动
function slider(){
    var count = $("#sider_wrap li").length - 6;  
    var interval = $("#sider_wrap li:first").outerWidth(true); 
    var curIndex = 0;
    var maxIndex = -count*interval+"px";
    if(count > 0){
        $('.aright').removeClass('agray');
    }else{
        $('.aright').addClass('agray');
    }
    $('.a_arrow').click(function(){
        if ($(this).hasClass('aleft')) {
          --curIndex;
        }
        if($(this).hasClass('aright')){
          ++curIndex;
        }
        $("#sider_wrap ul").stop().animate({"left" : -curIndex*interval + "px"},300,function(){
            var leftul = $("#sider_wrap ul").css("left");
            if(leftul == "0px"){
              $('.aleft').addClass('agray');
            }else if(leftul == maxIndex){
              $('.aright').addClass('agray');
            }else{
              $('.aleft').removeClass('agray');
              $('.aright').removeClass('agray');
            }
        });     
    });

}

//实现数量的控制
$(".num_down").live("click",function(){    
      numControl($(this),"-");
})
$(".num_up").live("click",function(){
      numControl($(this),"+");
})

//关于点击时数量变化方法
function numControl(proId,state){
  var maxNum = parseInt(proId.parent().find('.num').attr('max_num')); 
  var num = parseInt(proId.parent().find('.num').val());
  if(state == '-'){
    if(num == 1){
       proId.attr('disabled',true);
    }else{
          var num_d = proId.parent().find('.num').val(num-1).stop().val();
          if(num_d <= 1){
            proId.attr('disabled',true);
          }else if(num_d > 1 && num_d < maxNum){
            proId.attr('disabled',false);
            proId.parent().find('.num_up').attr('disabled',false);
          }
    }
    
  }
  if(state == '+'){
    var num_u = proId.parent().find('.num').val(num+1).stop().val();
    if(num_u >= maxNum){
      proId.attr('disabled',true);
    }else if(num_u >= 1 && num_u < maxNum){
      proId.attr('disabled',false);
      proId.parent().find('.num_down').attr('disabled',false);
    }
    
  }
}


//直接修改数量时对输入字符的控制
$(".num").live("change",function(){
  check($(this),$(this).val());
})

//直接修改数量,对输入的字符的控制
function check(proId,num){
   var m_num = proId.attr('max_num');
   var c = Number(num);
   if(!c || c < 1 || c > m_num){
      alert("请输入1~"+m_num+"的整数");
      proId.val(1);
   }   
}
//chartlist可滚动显示

  

    

})();


