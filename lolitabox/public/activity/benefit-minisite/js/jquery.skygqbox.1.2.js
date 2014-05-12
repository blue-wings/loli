/*
*Jquery 简单弹出层插件
*需要jquery1.3.2或者以上版本支持
*By 甘强 2011.01
*1.1版
* 添加一个延时自动关闭弹出层的功能
*插件官方地址：http://www.skygq.com/2010/01/05/jquery-simple-popup-overlay-1-1/
*/
;(function($){
	//给页面装载CSS样式
	var css = '<style type="text/css">#skygqOverlay{position:absolute;z-index:2000;left:0;top:0;width:100%;background:black;}.wrap_out{box-shadow:0 0 6px rgba(0,0,0,.5);-moz-box-shadow:0 0 6px rgba(0,0,0,0.5);-webkit-box-shadow:0 0 6px rgba(0,0,0,.5);position:absolute;z-index:2000;}.wrap_bar{width:100%;margin-top:2px;}.wrap_title{line-height:5px;margin-top:3px;}.wrap_title span{position:relative;margin-left:10px;cursor:text;}.wrap_body{display:inline-block;overflow-y:auto;}.wrap_close{margin-top:-18px;color:#34538b;font-weight:bold;margin-right:10px;font-family:Tahoma;text-decoration:none;cursor:pointer;float:right;}.wrap_close:hover{text-decoration:none;color:#f30;}.submit_btn{display:inline-block;padding:3px 12px 1.99px;color:#f3f3f3;line-height:16px;cursor:pointer;overflow:visible;}.submit_btn:hover{text-decoration:none;color:#ffffff;}.cancel_btn{display:inline-block;padding:3px 12px 1.99px;color:#333;line-height:16px;cursor:pointer;overflow:visible;}</style>';
	$("head").append(css);
	var timeout;
	$.fn.skygqbox = function(options){
		if (!this.length) {	return this;}
		var s = $.extend({}, $.fn.skygqbox.Default, options || {});
		return this.each(function(){
			$.skygqbox($(this),s)
		});
	};

	$.skygqbox = function($elements,s){
		var s = $.extend({}, $.fn.skygqbox.Default, s || {});
		//弹框的显示初始化
		var WRAP = '<div id="skygqOverlay"></div><div class="wrap_out" id="wrapOut"><div class="wrap_in" id="wrapIn"><div id="wrapBar" onselectstart="return false;"><a href="javascript:void(0);" class="wrap_close" id="wrapClose">'+s.shut+'</a></div><div class="wrap_body" id="wrapBody"></div></div></div>';
		if($("#wrapOut").size()){
			$("#wrapOut").show();
			if(s.bg){
				$("#skygqOverlay").show();
			}else{
				$("#skygqOverlay").hide();
			}
		}else{
			$("body").append(WRAP);
		}

		if (typeof (timeout) != "undefined"){
			clearTimeout(timeout);
		}

		//一些元素对象
		$.o = {
			s: s,
			ele: $elements,
			bg: $("#skygqOverlay"),
			out: $("#wrapOut"),
			bar: $("#wrapBar"),
			clo: $("#wrapClose"),
			//clo: $(".wrapclose"),

			bd: $("#wrapBody")
		};

		$elements.show();
		$.o.bd.append($elements);
		//尺寸
		$.skygqbox.setSize();
		//定位
		$.skygqbox.setPosition();
		$(window).resize(function(){
			$.skygqbox.setPosition();
		});
		if (s.fixed){
			$(window).scroll(function(){
				$.skygqbox.setPosition();
			});
		}
		$.o.clo.click(function(){
			$.skygqbox.hide();
		});

		if(s.autoClose > 0){
			timeout = setTimeout($.skygqbox.hide, s.autoClose);
		}
	};
	$.skygqbox.getSize = function(o){
		//获取任意元素的高宽
		var w_h = {};
		$('<div id="wrapClone" style="position:absolute;left:-6000px;"></div>').appendTo("body").append(o.clone());
		w_h.w = $("#wrapClone").width();
		w_h.h = $("#wrapClone").height();
		$("#wrapClone").remove();
		return w_h;
	};
	$.skygqbox.setSize = function(){
		if(!$.o.bd.size() || !$.o.ele.size() || !$.o.bd.size()){
			return;
		}
		//主体内容的尺寸
		var bd_w = parseInt($.o.s.width, 10), bd_h = parseInt($.o.s.height, 10);
		if(!bd_w || bd_w <= 0 ){
			var x_size = $.skygqbox.getSize($.o.ele), w = $(window).width();
			//宽度自动
			bd_w = x_size.w;
			if(bd_w < 50){
				bd_w = 120;
			}else if(bd_w > w){
				bd_w = w - 120;
			}
		}
		$.o.bd.css("width", bd_w);
		$.o.out.css("width", bd_w+2);
		if(bd_h > 0){
			$.o.bd.css("height", bd_h);
		}
		return $.o.bd;
	};
	$.skygqbox.setPosition = function(){
		if(!$.o.bg.size() || !$.o.ele.size() || !$.o.out.size()){
			return;
		}
		var w = $(window).width(),
		h = $(window).height(),
		st = $(window).scrollTop(),
		ph = $("body").height();
		if(ph < h){
			ph = h;
		}
		$.o.bg.width(w).height(ph).css("opacity", $.o.s.opacity);
		//主体内容的位置
		//获取当前主体元素的尺寸
		var xh = $.o.out.outerHeight(), xw = $.o.out.outerWidth();
		var t = st + (h - xh)/2, l = (w - xw)/2;
		$.o.out.css({
			top: t,
			left: l,
			zIndex: $.o.s.index
		});
		return $.o.out;
	};
	$.skygqbox.hide = function(){
		if($.o.ele && $.o.out.size() && $.o.bg.size()){
			$.o.ele.appendTo($("body")).hide();
			$.o.out.fadeOut("fast", function(){
				$(this).remove();
			});
			$.o.bg.fadeOut("fast", function(){
				$(this).remove();
			});
		}
		return false;
	};
	$.fn.skygqbox.Default = {
		title		: " ",
		shut		: " ",
		index		: 2000,
		opacity		: 0.5,
		width		: "auto",
		height		: "auto",
		autoClose	: 0,//弹出层等待多长时间自动关闭(单位：毫秒) 0或者负数不触发自动关闭
		fixed		: true
	};
})(jQuery);