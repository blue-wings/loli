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
	//$('#home_nav_bar').css({'left':(rackleft+1040)+'px'})
	$('#home_nav_bar').css({'right':'10px'})
}


//***share.js***//
/**
 * 赞
 * @param id
 * @param obj
 */
function agree_share(id,obj){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	$.ajax({
		url:"/home/agree",
		type:"post",
		data:{id:id},
		dataType:"json",
		success:function(data){
			if(data.status==1){
				var mess="<p>分享内容很精彩，赞完啦！相信她一定会继续努力的！</p><br />";
				var arr=action_shareto_common(data.info,mess);
				y_dialog(arr[0],arr[1],false,obj);
				action_shareto(id,1);
			}else{
				n_dialog(data.info,3,false,obj);
			}
		}
	})
}

/**
 * 踩、赞成功后显示的内容
 * @param data
 * @param mess
 * @returns {Array}
 */
function action_shareto_common(data,mess){
	var sina=data.sina;
	var qq=data.qq;
	var time=3;
	if(sina!=1 && qq!=1){
		var mess=mess+"<p><b>小萝莉温馨提醒：</b>绑定微博，把你的观点告诉更多的朋友，</p><p>获得积分奖励之外还可以参加每日抽奖哦~机会不可错过:)</p>";
		if(sina==2){
			var sina_url="/user/sina_login.html";
		}else{
			var sina_url="/user/sina_lock.html";
		}
		if(qq==2){
			var qq_url="/user/qq_login.html";
		}else{
			var qq_url="/user/qq_lock.html";
		}
		var mess=mess+"<br /><p><b title=\"分享到新浪微博\" class=\"i_sina\">&nbsp;</b><a href=\""+sina_url+"\" target=\"_blank\">新浪微博</a>　　<b title=\"分享到腾讯微博\" class=\"i_qqweibo\"> &nbsp;</b><a href=\""+qq_url+"\" target=\"_blank\">腾讯微博</a</p>";
		var time=10;
	}
	var arr=new Array(mess,time);
	return arr;
}


/**
 * 踩或赞 转发到第三方
 * @param id
 * @param type
 */
function action_shareto(id,type){
	$.ajax({
		url:"/home/action_shareto_common",
		type:"post",
		data:{id:id,'type':type},
		dataType:"json",
		success:function(data){}		
	})
}

/**
 * 踩
 * @param id
 * @param obj
 */
function tread_share(id,obj){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	$.ajax({
		url:"/home/tread",
		type:"post",
		data:{id:id},
		dataType:"json",
		success:function(data){
			if(data.status==1){
				var mess="<p>分享内容不够好，踩完了！她会更努力的！</p><br />";
				var arr=action_shareto_common(data.info,mess)
				y_dialog(arr[0],arr[1],false,obj);
				action_shareto(id,2);
			}else{
				n_dialog(data.info,3,false,obj);
			}
		}
	})
}

////////////////////////////////////////私信/////////////

/**
 * 删除私信
 */
function del_msg_detail(id){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	if(confirm("删除后不可恢复，您确定要删除吗？")){
		$.ajax({
			url:"/home/delete_msg_dialog.html",
			type:"post",
			dataType:"json",
			data:"id="+id,
			success:function(ret){
				y_dialog("删除成功",3,function(){
					location.reload();
				})
			}
		})
	}else{
		return false;
	}
}

/**
 * 删除分享
 * @author litingting
 */
function delete_share(id){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	if(confirm("您确定删除分享吗？？？")){
		$.ajax({
			url:"/home/delete_share_ajax",
			type:"post",
			data:{id:id},
			success:function(data){
				if(data.status==1){
					y_dialog("删除成功",2,function(){
						location.href="/home/share";
					});
				}else{
					n.dialog("删除失败",2);
				}
			}
		})
	}
	
}

//**dialog.js***//

/**
 * 登录弹框方法
 * @author litingting
 */
function dialog_login(){
    var myDialog = art.dialog({ fixed:true,opacity:0.3,lock:true,close:function(){
    	$("#Validform_msg").remove();
    }});
    var url=""+TRACK_URL+"";
	$.ajax({
	    url: "/public/dialog/id/login/",
	    dataType:"html",
	    data:{url:url},
	    success: function (data) {
	        myDialog.content(data);// 填充对话框内容
	    }
	});
	return myDialog;
}
$(".dialog_login_obj").click(dialog_login);   //所有自动调用登弹框加上此样式

/**
 * 分享弹框
 * @author litingting
 */
var share_dialog;    //分享弹框对象
function dialog_share($id,$type){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	share_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/share/",
	    type:"post",
	    data:{pid:$id,type:$type},
	    success: function (data) {
	    	share_dialog.content(data);// 填充对话框内容
	    }
	});
}

/**
 * 编辑分享框
 * @param id
 * @returns
 */
function edit_share(id){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	var content = $.trim($(".WB_text").html());
	var img = $(".chePicMin img").attr("src");
	share_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/share/",
	    type:"post",
	    data:{shareid:id,content:content,img:img},
	    success: function (data) {
	    	share_dialog.content(data);// 填充对话框内容
	    }
	});
	
}

/**
 * 通过PID获取产品详情弹框
 * @param $pid
 */
function dialog_products($pid){
	if(!$pid){
		return false;
	}
	var xdialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
		url:"/brand/dialog_products",
		data:{pid:$pid},
		dataType:"html",
		success:function(data){
			xdialog.content(data);
		}
	})
}

/**
 * dialog转发到
 * @param id
 * @author litingting
 */
var shareto_dialog;
function dialog_shareto(id){
	if(USER_ID==""){
		dialog_login();
		return false;
	}
	shareto_dialog = art.dialog({fixed:true,opacity:0.3,lock:true});
	var evt = (evt) ? evt : ((window.event) ? window.event : "");
	var obj =  evt.target  ||  evt.srcElement || evt.currentTarget; 
	$.ajax({
		url:"/public/dialog/id/shareto",
		type:"post",
		dataType:"html",
		data:"shareid="+id,
		success:function(ret){
			shareto_dialog.content(ret);
		}
	})
}

/**
 * dialog 我要试用
 * @author penglele
 */
var tryto_dialog;
function dialog_tryto(pid,type,obj){
	if(USER_ID==""){
		dialog_login();
		return false;
	}
	tryto_dialog = art.dialog({fixed:true,opacity:0.3,lock:true});
	var evt = (evt) ? evt : ((window.event) ? window.event : "");
	var obj =  evt.target  ||  evt.srcElement || evt.currentTarget; 
	//alert(2);return false;
	$.ajax({
		url:"/public/dialog/id/tryto",
		type:"post",
		dataType:"html",
		data:{pid:pid,type:type},
		success:function(ret){
			tryto_dialog.content(ret);
		}
	})
}

/**
 * 私信弹框
 * @author litingting
 */
var msg_dialog;   //私信弹框对象
function dialog_msg($name,$to){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	msg_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/msg/",
	    type:"post",
	    data:{name:$name,to:$to},
	    success: function (data) {
	    	msg_dialog.content(data);// 填充对话框内容
	    }
	});
}


var info_dialog;
function dialog_information(){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	msg_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/information/",
	    type:"post",
	    success: function (data) {
	    	msg_dialog.content(data);// 填充对话框内容
	    }
	});
}


/**
 * 当没有数据时输出提示html内容
 * @param string id  元素ID
 * @param string title
 * @param string content
 * @author litingting
 */
function  nodata(id,title,content){
	if(!id){
		return false;
	}
	title = title ||'';
	content = content ||  '';
	var $html='<div class="no_content">	<p><img src="public/images/face/dizzy.gif" width="95" height="95"></p><p>'+title+'</p><p>'+content+'</p></div>';
	$("#"+id).html($html);
}

/**
 * ajax替换文本内容
 * @param string id 组元素id前缀
 * @param string url ajax提交路径
 * @param string param 参数 关联元素id前缀
 * @param string $class 样式
 * @uathor litingting
 */
function ajax_replace(id,url,param,callback,container,$class){
	$class = $class || "select";
	callback = callback || false;
	container = container || "ajax_content";
	param = param || '';
	if(!id || !url ){
		return false;
	}
	var get_values = $("[id^='"+id+"_'][get_values]").attr("get_values") || 1;
	$("[id='"+id+"_"+get_values+"']").addClass($class);
	$("[id^='"+id+"_']").click(function(){
		var data = $(this).attr("id").replace(id+"_","");
		$("[id^='"+id+"_']").removeClass($class);
		$(this).addClass($class);
		var returns = id+"="+data;
		if(param){
			var params_id = $("[id^='"+param+"_']").filter("."+$class).attr("id");
			var params= params_id.replace(param+"_","")
			returns+="&"+param+"="+params;
		}
		if(typeof callback=='function'){
			callback($(this));
		}
			 $.ajax({
				 url:url,
				 data:returns,
				 type:"get",
				 success:function(data){
					 $("#"+container).html(data);
				 }
			 })
	})
}


/**
 * ajax替换文本内容【不带样式的改变】---------待定
 * @param string id 组元素id前缀
 * @param string url ajax提交路径
 * @param string param 参数 关联元素id前缀
 * @param string $class 样式
 * @uathor litingting
 */
function select_ajax_replace(url,name,val){
	if(!url ){
		return false;
	}
	if(name){
		var param=""+name+"="+val;
	}
	 $.ajax({
		 url:url,
		 data:param,
		 type:"get",
		 success:function(data){
			 $("#ajax_content").html(data);
		 }
	 })
}


/**
 * 显示提示信息
 * @param content 提示框的内容
 * @param time 自动关闭的世界
 * @param close 关闭弹框时触发的方法
 * @param width 定义弹框的宽
 * @param height 定义弹框的高
 * @author penglele
 */
function select_div(content,time,close,width,height){
	if(close !==false){
		if(close == true){
			var callback = function(){
				location.reload();
			}
		}else{
			var callback = close;
		}
	}else{
		var callback=false;
	} 		
	$.dialog({
		content:content,
		time:time,
		close:callback,
		width:width,
		height:height,
		fixed:true,
		opacity:0.4,
		lock:true
	})
}


/**
 * 成功弹框
 * @param content
 * @param time
 * @author ltiingting
 */
function y_dialog(content,time,close,follow){
	close = close || null;
	time = time || 2;
	follow = follow || null;
	$.dialog({
		content:content,
		time:time,
		fixed:true,
		opacity:0.2,
		lock:true,
		icon:"succeed",
		close:close,
		follow:follow,
		padding:"20px"
	})
}

/**
 * 失败弹框
 * @param content
 * @param time
 * @author ltiingting
 */
function n_dialog(content,time,close,follow){
	close = close || null;
	time = time || 2;
	follow = follow || null;
	$.dialog({
		content:content,
		time:time,
		fixed:true,
		opacity:0.2,
		lock:true,
		icon:"error",
		close:close,
		follow:follow,
		padding:"20px"
	})
}


/**
 * 字数计数
 * @param textid text内容元素
 * @param numid  计数元素
 * @param length  字母 长度
 * @author litingting
 */
function limit_word_num(textid,numid,length){
	$("#"+textid).keyup(function(){
		var val = $("#"+textid).val();
		var len = val.length;
		if(len <= length){
			var now_num=length-len;
			$("#"+numid).text(now_num);
		}else{
			$("#"+textid).val(val.substr(0,length));
			$("#"+numid).text(0);
		}
	});
}


/**
 * 关注
 * @param $id
 * @param $type
 * @param obj
 * @returns {Boolean}
 */
function follow(id,$type,obj){
	$type = $type || 3;
	if(!USER_ID){
		dialog_login();
		return false;
	}
	var evt = (evt) ? evt : ((window.event) ? window.event : "");
	var obj =  evt.target  ||  evt.srcElement || evt.currentTarget; 
    $.ajax({
    	type:"post",
    	url: "/common/follow",
    	async:false,
    	data:{whoid:id,type:$type},
    	dataType:"json",
    	success:function(data){
    		if(data.status==1){
    			y_dialog("订阅成功",2,false,obj);
    			var f_id="brand_f_"+id;
    			var cancel_f_id="cancel_brand_f_"+id;
    			$("#"+cancel_f_id).show();
    			$("#"+f_id).hide();
    			//setTimeout('$("#'+obj+'").removeClass("cboxElement");',1000);
    		}else{
    			n_dialog("订阅失败",2,false,obj)
    		}
    	}
    })
}


/**
 * 取消关注
 * @param id
 * @param type
 * @param flag
 */
function cancel_follow(id,type,flag){
	  if(!USER_ID){
			dialog_login();
			return false;
	  }
	  type = type|| 3;
	  var evt = (evt) ? evt : ((window.event) ? window.event : "");
	  var obj =  evt.target  ||  evt.srcElement || evt.currentTarget; 
	  $.ajax({
	    	type:"post",
	    	url: "/common/cancel_follow",
	    	data:{whoid:id,type:type},
	    	dataType:"json",
	    	success:function(data){
	    			y_dialog("取消订阅成功",2,false,obj);
		    		if(data.status==1){
		    			var f_id="brand_f_"+id;
		    			var cancel_f_id="cancel_brand_f_"+id;
		    			$("#"+cancel_f_id).hide();
		    			$("#"+f_id).show();	    				
	    		}
	    		else{
	    			n_dialog("取消订阅失败",2,false,obj)
	    		}
	    	}
	    })
}


/**
 * 带定位的弹框
 */
var dialog_id;
function id_dialog(content,obj,type,close,time){
	if(type==1){
		var icon="succeed";
	}else if(type==2){
		var icon="error";
	}
	
	if(close !==false){
		if(close == true){
			var callback = function(){
				location.reload();
			}
		}else{
			var callback = close;
		}
	}else{
		var callback=false;
	} 	
	var time = time || 0;
	dialog_id=art.dialog({
						follow:obj,
						content:content,
						opacity:0.2,
						lock:true,
						icon:icon,
						padding:"20px 20px",
						close:callback,
						time:time
			})
}
/**
 * 填写信息时的提示信息
 * @param id
 * @param type
 * @param content
 * @author penglele
 */
function notice(id,type,content){
	$("#"+id).closest(' div ').next(".tips").remove();
	var clas ='ico_error';
	if(type==1){
		clas='ico_eryes';
	}
	$("#notice_tips .notice").html("<span class='W_ico20 "+clas+"'></span>"+content);
	$("#"+id).closest(' div').after($("#notice_tips").html());
}


/**
 * 判断链接是否需要登录
 */
function a_jump(obj){
	if(!USER_ID){
		var url = $(obj).attr("href");
		TRACK_URL= url;
		var dialog=dialog_login();
		return false;
	}else{
		return true;
	}
}

$(".judge_login").click(function(){
	return a_jump(this);
})

	/**
	 * 判断付邮试用产品
	 * @param id 【id为单品ID】
	 * @param type [type==2时需要为判断用户是否满足积分兑换的要求]
	 * @param obj
	 */
	function check_try(id,type,obj){
		var type=type || 1;
		if(!id){
			return false;
		}
		if(type!=1 && !USER_ID){
			dialog_login();return false;
		}
		$.ajax({
			url:"/buy/check_try_product.html",
			type:"post",
			dataType:"json",
			data:"pid="+id,
			async:false,
			success:function(ret){
				if(type==1){
					$("#try_num").val(ret.status);
				}else if(type==2){
					if(parseInt(ret.status)==1){
						url="/buy/detail/id/"+ret.data.id;
						window.open(url);
					}else if(parseInt(ret.status)==200){
						dialog_login();return false;
					}else if(parseInt(ret.status)==300){
						var title="<p>尊敬的用户，您好：</p><p>我们提供的付邮试用功能仅对萝莉盒特权会员开放。</p><p>你目前为普通会员，请先升级为特权会员。</p><p> <a href='/member/index.html' class='W_pin_b' target='_blank'>立即升级</a>&nbsp;&nbsp;<a href='javascript:void(0)' onclick='return dialog_id.close();'>放弃付邮件试用</a></p> ";
						id_dialog(title,obj,2);
					}else if(parseInt(ret.status)==700){
						var title="<p>对不起，根据付邮试用规则“同一会员通过付邮试用</p><p>方式对同一款产品再次试用需要间隔"+ret.info.day+"天”你需要，</p><p>等到"+ret.info.time+"才可以再次对它进行付邮试用。</p><p><a href='/try/index/type/1.html' target='_blank' class='A_line3'>查看更多付邮试用产品</a></p>";
						id_dialog(title,obj,2);
					}else{
						id_dialog("<p>您选择的产品已选完，您可以重新选择其他付邮产品</p><p><a href='/try/index/type/1.html' target='_blank' class='A_line3'>查看更多付邮试用产品</a></p>",obj,2);
					}
				}
			}
		})
	}

function check_score(id,obj){
	if(!USER_ID){
		dialog_login();return false;
	}
	$.ajax({
		url:"/try/check_score_products.html",
		type:"post",
		dataType:"json",
		data:"id="+id,
		async:false,
		success:function(ret){
			if(parseInt(ret.status)==0){
				id_dialog(ret.info,obj,2);
			}else{
				var url="/try/iexchange/id/"+id+".html";
				window.open(url);
			}
		}
	})
}

/**
 * 关闭广告 
 * @param type
 */
function close_ad(type){
	var id="ad_"+type;
	var name="loli_closead";
	var cookie_val=getCookie(name);
	if(cookie_val){
		cookie_val=cookie_val+","+type;
	}else{
		cookie_val=type;
	}
	//document.cookie=name+"="+cookie_val+"; path=/"; 
	setCookie(name,cookie_val,100);
	$("#"+id).remove();
}

/**
 * 获取指定名称的cookie的值 
 * @param objName
 */
function getCookie(c_name){
	var arrStr = document.cookie.split("; "); 
	for(var i = 0;i < arrStr.length;i ++){ 
		var temp = arrStr[i].split("="); 
		if(temp[0] == c_name){
			return unescape(temp[1]); 
		} 
	} 
	return '';
} 

/**
 * js设置cookie
 * @param c_name
 * @param value
 * @param expiredays
 */
function setCookie(c_name,value,expiredays){
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=c_name+ "=" +escape(value)+"; path=/"+((expiredays==null) ? "" : "; expires="+exdate.toGMTString());
}

/**
 * js删除cookie
 * @param c_name
 */
function delCookie(c_name){
    var date=new Date();
    date.setTime(date.getTime()-10000);
    document.cookie=c_name+"=; expire="+date.toGMTString()+"; path=/";
}


var sharemore_dialog;    //分享弹框对象
function dialog_sharemore($id){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	sharemore_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/sharemore/",
	    type:"post",
	    data:{pid:$id},
	    success: function (data) {
	    	sharemore_dialog.content(data);// 填充对话框内容
	    }
	});
}

function add_promotion(data){
	$.ajax({
		url:"/public/add_promotion.html",
		type:"post",
		dataType:"json",
		async:false,
		data:data,
		success:function(ret){}
	})
}

/**
 * 分享弹框
 * @author litingting
 */
var sendword_dialog;    //分享弹框对象
function share_sendword(orderid,childid){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	sendword_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
	$.ajax({
	    url: "/public/dialog/id/sendword/",
	    type:"post",
	    data:{orderid:orderid,childid:childid},
	    success: function (data) {
	    	sendword_dialog.content(data);// 填充对话框内容
	    }
	});
}
