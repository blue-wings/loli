/*-------------------------- +
  获取id, class, tagName
 +-------------------------- */
var get = {
	byId: function(id) {
		return typeof id === "string" ? document.getElementById(id) : id
	},
	byClass: function(sClass, oParent) {
		var aClass = [];
		var reClass = new RegExp("(^| )" + sClass + "( |$)");
		var aElem = this.byTagName("*", oParent);
		for (var i = 0; i < aElem.length; i++) reClass.test(aElem[i].className) && aClass.push(aElem[i]);
		return aClass
	},
	byTagName: function(elem, obj) {
		return (obj || document).getElementsByTagName(elem)
	}
};
var EventUtil = {
	addHandler: function (oElement, sEvent, fnHandler) {
		oElement.addEventListener ? oElement.addEventListener(sEvent, fnHandler, false) : (oElement["_" + sEvent + fnHandler] = fnHandler, oElement[sEvent + fnHandler] = function () {oElement["_" + sEvent + fnHandler]()}, oElement.attachEvent("on" + sEvent, oElement[sEvent + fnHandler]))
	},
	removeHandler: function (oElement, sEvent, fnHandler) {
		oElement.removeEventListener ? oElement.removeEventListener(sEvent, fnHandler, false) : oElement.detachEvent("on" + sEvent, oElement[sEvent + fnHandler])
	},
	addLoadHandler: function (fnHandler) {
		this.addHandler(window, "load", fnHandler)
	},
	/*event对象的引用*/
	addEvent:function(event){
		return event ? event : window.event
	},
	/*获取事件的源对象，返回事件的目标*/
	addTarget:function(event){
		return event.target||event.srcElement;
	},
	/*取消事件默认行为、行为*/
	preventDefault:function(event){
		if(event.preventDefault){
			event.preventDefault()
		}else{
			event.returnValue=false;
		}
	},
	/*取消事件进一步捕获或冒泡*/
	stopPropagation:function(event){
		if(event.stopPropagation){
			event.stopPropagation()
		}else{
			event.cancelBubble = true;
		}
	}
}

/*
	By sean at 2013.01
	
	Example:
	//图片无缝循环向上滚动
	 scorllUnim(box,topId,AddId)
	 //box    外围div 限制高度                 必需项;
	 //topId  内容ul 或div                    必需项;
	 //AddId  复制内容ul 或div                 必需项;
	 //timer  设置滚动时间                     默认30毫秒。
*/
 function scorllUnim(box,topId,AddId,timer){
		var scorllWrap =document.getElementById(box);
		var scorllTop =document.getElementById(topId);
		var scorllAdd =document.getElementById(AddId);
		var speed= timer||30; 
		scorllAdd.innerHTML=scorllTop.innerHTML 
		function Marquee(){ 
		if(scorllAdd.offsetTop-scorllWrap.scrollTop<=0) 
		scorllWrap.scrollTop-=scorllTop.offsetHeight 
		else{ 
		scorllWrap.scrollTop++ 
		} 
		} 
		var MyMar=setInterval(Marquee,speed) 
		scorllWrap.onmouseover=function() {clearInterval(MyMar)} 
		scorllWrap.onmouseout=function() {MyMar=setInterval(Marquee,speed)} 	
};

/*头部-导航-个人信息*/
	$(function(){

		$('.date_name').hover(function(){
			$('.date_name_email').show();
			$('.date_name').find('.login_name').css({'color':'#dd1471'});
			$('.date_name').find('.lg_pull').addClass('lg_pull_on');
		},function(){
			$('.date_name_email').hide();
			$('.date_name').find('.login_name').css({'color':'#333'});
			$('.date_name').find('.lg_pull').removeClass('lg_pull_on');
		})
	

		$('.date_style').hover(function(){
			$('.date_name_style').show();
			$('.date_style').css({'borderColor':'#c9c9c9','paddingBottom':'9px'});
		},function(){
			$('.date_name_style').hide();
			$('.date_style').css({'borderColor':'#fff','paddingBottom':'0'});
		})
		$('.set').hover(function(){
			$('.set_style').show();
			$('.set').css({'borderColor':'#c9c9c9','paddingBottom':'9px'});
		},function(){
			$('.set_style').hide();
			$('.set').css({'borderColor':'#fff','paddingBottom':'0'});
		})
	})

//右侧导航超过宽度隐藏
function shortcutFun(){
	var docW = document.body.clientWidth;
	if(docW<=1084){
		$('#home_nav_bar').hide();
	}else{
		$('#home_nav_bar').show();
	}
}
$(function(){
	try{
		shortcutFun();
		rackRight();
		window.onresize =shortcutFun;
		window.onresize =rackRight;
	}catch(err){
		console.log(err);
	}
})

$(function(){
	$('#home_nav_bar .nav_backtotop').bind('click',function(){$("html, body").animate({ scrollTop: 0 }, 120);})
	backToTopFun();
	$(window).bind("scroll",backToTopFun);
})

function backToTopFun(){
	var st = $(document).scrollTop(), winh = $(window).height();
	var $backToTopEle = $('#home_nav_bar');
	(st > 0) ? $('#home_nav_bar').css({display:'block'}): $('#home_nav_bar').hide();	
	//IE6下的定位
	if (!window.XMLHttpRequest) {
		$backToTopEle.css("top", st + winh - 620);	
	}
};

//右侧-悬右侧定位
function rackRight(){
	var rackleft = $('.fm1020').offset().left || $('.fm1024').offset().left || $('.fm1020_c').offset().left ||$('.fm1020_b').offset().left;
	$('#home_nav_bar').css({'left':(rackleft+1040)+'px'})
	
}







