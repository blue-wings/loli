(function(){
/*倒计时*/
var timer = function(timeId){
  this.timeId = timeId;
  //定义变量 
    var isRe = false;
    var timeDown = document.getElementById(timeId);
    var timeSpan = timeDown.getElementsByTagName('span');
    var hiInput = timeDown.getElementsByTagName('input')[0].value+'';
  //将获取后台的时间在这里处理形成数组
    var reg = /[\:\-\s+]/; //用:或者*或者是空格将时间分割
    var time = hiInput.split(reg);
    var txt1 = timeDown.innerHTML,
    txt2 = "<span>00</span>天<span>00</span>时<span>00</span>分<span>00</span>秒";     
    timeDown.innerHTML = txt2+txt1;
    timeDown.style.display = "none";
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
      for(var i=0;i<timeSpan.length;i++) {  
         timeSpan[i].innerHTML = ta[i];
       }
      function checkTime(t){    
         if (t < 10 && t >= 0) {    
             t = "0" + t;    
          } else if(t < 0){
            t = "00";//这个是为了防止当上线的时候已经在订阅状态了，这样显示的时间就不会是负的
          }  
         return t;    
     } 
    //当时间截止的时候就clear
     if(animation&&ss<=0&&mm<=0&&hh<=0&&dd<=0){
      clearInterval(animation);
      timeDown.className = "timeDown time0";
     }
     isRe = true;
     if(isRe){
        timeDown.style.display = "block";
        timeIdAdd();
        timeStyle();
     }
    }
    var animation = setInterval(timerSub,1000);
}

//使用时间倒计时
var np = $('.timeDown');
//给显示时间的DOM加入不同的ID
var timeIdAdd = function(){
    for(var i=0;i<np.length;i++){
       $(np[i]).attr('id','timeDown_'+i);
    }
 }
//倒计时执行方法
var timeDo = function(){
    timeIdAdd();
    for(var i=0;i<np.length;i++){   
        timer('timeDown_'+i);
      }
}
//DOM样式控制
var timeStyle = function(){
  var time0 = $('.chart_main_b').find('.time0');
      time0.prev('p').css({'color':'#fff'});
      time0.text('订阅进行中...');

}
//执行倒计时
timeDo();

/*关于订阅产品数量的控制*/
//关于点击时数量变化方法
var numControl = function(proId,state){
  var maxNum = parseInt(proId.parents('.pro_num').next('.max_num').find('span').text());
  if(state == '-'){
    var num = parseInt(proId.parent().find('.num').val());
    if(num <= maxNum && num > 2){
      proId.parent().find('.num').val(parseInt(num)-1);
      proId.removeClass('disabled').parent().find('.up_num').removeClass('disabled');
    }else if(num == 2){ 
      proId.parent().find('.num').val(parseInt(num)-1);
      proId.addClass('disabled').parent().find('.up_num').attr('disabled',false);
    }else if(num < 2){
      proId.attr('disabled',true);
    }
  }else if(state == '+') {
    var num = proId.parent().find('.num').val();
    if(num < maxNum-1 && num > 0){
      proId.parent().find('.num').val(parseInt(num)+1);
      proId.removeClass('disabled').parent().find('.low_num').removeClass('disabled');
    }else if(num == maxNum-1){
      proId.parent().find('.num').val(parseInt(num)+1);
      proId.addClass('disabled');
    }else if(num > maxNum-1){
      proId.attr('disabled',true).parent().find('.low_num').attr('disabled',false);
    }
  }
}
//实现数量的控制
    $('.low_num').click(function(){
       numControl($(this),"-");
    })
    $('.up_num').click(function(){
      numControl($(this),"+");
    })
   
    

/*关于DOM划出*/

//定义变量
var sub_ew =  $('.sub_ew'),
    close = $('.close'),
    menu_a = $('.menu_c').find('a');  
//DOM滑出效果
//sub_ew.hide();
menu_a.click(function(){
  var n = $(this).index();
  //这里采用animate()是为了解决IE中slideUp()闪屏的问题
  sub_ew.hide().eq(n).animate({height: '456px', opacity: '1'}, 200).show();
  //自定义滚动条

  //调用 destroy 方法可以移除某个对象的自定义滚动条并且恢复默认样式
  sub_ew.eq(n).find('ul').mCustomScrollbar("destroy");
  
  sub_ew.eq(n).find('ul').mCustomScrollbar({

          theme:"dark"
    });
  
})
//点击关闭DOM
close.click(function(){
  $(this).parents('.sub_ew').animate({height: '0', opacity: '0'}, 200);
})



/*加入购物车抛物线运动*/

// 元素以及其他一些变量
var eleFlyElement = document.querySelector("#flyItem"), eleShopCart = document.querySelector("#cartWrap");
var numberItem = 0;
// 抛物线运动
var myParabola = funParabola(eleFlyElement, eleShopCart, {
  speed: 400,
  curvature: 0.001, 
  complete: function() {
    eleFlyElement.style.visibility = "hidden";
    eleShopCart.querySelector("span").innerHTML = ++numberItem;
  }
});
// 绑定点击事件
if (eleFlyElement && eleShopCart) {
  [].slice.call(document.getElementsByClassName("btnCart")).forEach(function(button) {
    var eleImg = eleFlyElement.getElementsByTagName('img');
    button.addEventListener("click", function(event) {
      // 滚动大小
      var scrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft || 0,
          scrollTop = document.documentElement.scrollTop || document.body.scrollTop || 0;

      //抛物线运动图片的跟随
      var imgs = $(this).parents('dl').find('img').attr('src');
      $(eleImg[0]).attr('src',imgs);

      //对按钮进行状态处理
      this.className = "btnCart selected";
      this.disabled = true;
      this.value = "已加入订购";
      eleFlyElement.style.left = event.clientX + scrollLeft + "px"; 
      eleFlyElement.style.top = event.clientY + scrollTop + "px";
      eleFlyElement.style.visibility = "visible";
      
      // 需要重定位
      myParabola.position().move();     
    });
  });
}

/*chart固定*/

function findDimensions(){           
            var isIE6 = !-[1,] && !window.XMLHttpRequest;
            var meg = $("#meg");
            var ids =$("#wrap");
            var scrolltop_top =  ids.offset().top;
            var scrolltop_left =  ids.offset().left; 
            $(window).scroll(function(){//定位导航栏
                var H = $(this).scrollTop();
                var fl_left = scrolltop_left;
                 if( H >= scrolltop_top){
                  ids.find(".main").css({"height":"20px"});
                     if(isIE6){
                        meg.css({ "position": "absolute","top":H-82+"px"});
            
                     }else{
                        meg.css({"position":"fixed","top":"0"});
                    }
                    
                }else{  
                    ids.find(".main").css({"height":"0px"});
                    meg.css({"position":"static"});
                }
        
        });
           $('.zt_nav_main ul li').click(function(){
              setTimeout(function(){
              var scrollTop = $(window).scrollTop();
              $(window).scrollTop(scrollTop-62);
             },10)}); 
        }
        findDimensions();
        window.onresize=findDimensions;


})();