/**
 * 登录弹框方法
 * @author litingting
 */
function dialog_login(){
    var myDialog = art.dialog({ fixed:true,opacity:0.3,lock:true,close:function(){
    	$("#Validform_msg").remove();
    }});
	$.ajax({
	    url: "/public/dialog/id/login/",
	    dataType:"html",
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
	$.dialog({
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
						var title="<p>非常抱歉，您目前的等级为&nbsp;<b>萝莉新生</b>&nbsp;(经验值"+ret.info+")。</p><p>根据付邮试用规定，等级不足&nbsp;<b>萝莉之初</b>&nbsp;(经验值100)时无法参与付邮试用。</p><p><a href=\"/info/lolitabox/aid/1235.html\" target=\"_blank\" class=\"A_line3\">如何获得经验值?</a></p> ";
						id_dialog(title,obj,2);
					}else{
						id_dialog("您选择的产品已选完，您可以重新选择其他付邮产品",obj,2);
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


