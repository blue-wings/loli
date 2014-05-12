
var boxid=$("#boxid").val();

//删除已选的积分试用产品
function del_exchange(id,u_score,type){
	if(!USER_ID){
		dialog_login();return false;
	}	
	$.ajax({
		url:"/try/del_exchange_product.html",
		data:"id="+id+"&boxid="+boxid,
		type:"post",
		dataType:"json",
		success:function(ret){
				if(parseInt(ret.status)==0){
					select_div("<p>"+ret.info+"</p>");
				}else{
					var score=parseInt($("#iex_total_score").html())-parseInt(u_score);
					var num=parseInt($("#iex_total_num").html())-1;
					//没有任何产品时显示的提示信息
					if(num==0){
						$("#iexchange_nomsg").show();
					}
					//此处是对已选择产品列表的显示的动态修改
					$("#iex_total_score").html(score);//动态修改已选总积分数
					$("#iex_total_num").html(num);	//动态修改已选产品总数
					$("#have_select_"+id).remove();//将已选产品动态删除掉
					var btn_name="iex_select_"+id;
					//将按钮置为可选状态
					$("#"+btn_name).removeClass("W_pin_grey");
					$("#"+btn_name).addClass("W_pin_orange");
					//设置按钮文字
					$("#"+btn_name).html("我要试用");
					//设置已选择标签
					$("#i_select_"+id).hide();
					if(type==1){
						if(num==0){
							iex_dialog.close();
							return false;
						}
						var select_idname="div_product_"+id;
						$("#"+select_idname).remove();
						get_pay_type();
					}
				}
			}
		})	
}
var iex_dialog;
function show_select(){
	iex_dialog=	art.dialog({
	    content: document.getElementById('exchange_div'),
	    lock:true,
	    close:function(){
	    	if($("#step3").css("display")=="block" || $("#step4").css("display")=="block"){
	    		location.reload();
	    	}
	    },
	    opacity:0.4
	});
}

//立即兑换
	function exchange_buy(obj){
		if(!USER_ID){
			dialog_login();return false;
		}
		$.ajax({
			url:"/try/check_exchange_product_select.html",
			type:"post",
			dataType:"json",
			data:"boxid="+boxid,
			success:function(ret){
				if(parseInt(ret.status)==0){
					if(parseInt(ret.data)==100){
						//当已选产品部分已售完时
						html="<p>以下产品已经售完，请重新选择：</p>";
						html+="<ul>";
						$.each(ret.info,function(ikey,ival){
							html+="<li>"+ival+"</li>";
						})
						html+="</ul>";
						var msg=html;
					}else if(parseInt(ret.data)==1000){
						var msg="<p>只有特权会员或经验值等级达到V3级“萝莉女孩”</p><p>的普通会员才可参与积分试用</p><p><a href='/info/lolitabox/aid/1235.html' target='_blank' class='A_line3'>如何提高经验值等级</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='/member/index.html' target='_blank' class='A_line3'>升级特权会员</a></p>";
					}else if(parseInt(ret.data)==200){
						var msg="<p>非常抱歉，本次兑换需要<b>"+ret.info.total_score+"</b>积分，</p><p>您目前的积分（<b>"+ret.info.user_score+"</b>)不能够完成积分兑换。</p><a href='/info/lolitabox/aid/1235.html' target='_blank' class='A_line3'>查看如何获得积分？</a>";
					}else{
						var msg="<p>"+ret.info+"</p>";
					}
					id_dialog(msg,obj,2);
				}else{
					
					//动态调取用户可以选择付款的方式+++++++++++
					get_user_addresslist(1);
					
					get_pay_type();
				
					$(".select_div").hide();
					$("#step1").show();
					//此处为用户已选择的产品列表
					var html=""
						
					$.each(ret.info.selectlist,function(ikey,ival){
						html+=("<li id=\"div_product_"+ival.itemid+"\">");
						html+=("<div class=\"tc_changeBox_close\"><a href=\"javascript:void(0)\" class=\"W_ico12 icon_close\" onclick=\"del_exchange('"+ival.itemid+"','"+ival.member_score+"',1)\"></a></div>");
						html+=("<dl>");
						html+=("<dt>");
						html+=("<div class=\"tc_changeBox_close\"><a href=\"javascript:void(0)\" onclick=\"del_exchange('"+ival.itemid+"','"+ival.member_score+"',1)\" class=\"W_ico12 icon_close\"></a></div>");
						html+=("<a href=\""+ival.producturl+"\" target=\"_blank\" class=\"B_line1\"><img src=\""+ival.pimg+"\" width=\"98\" height=\"98\"></a>");
						html+=("<p class=\"pos_txt\"><i></i><span>规格："+ival.norms+"</span></p>");
						html+=("</dt>");
						html+=("<dd class=\"names\"><a href=\""+ival.producturl+"\" target=\"_blank\" class=\"A_line2\">"+ival.pname+"</a></dd>");
						html+=("<dd class=\"integral\">"+ival.member_score+" 积分</dd>");
						html+=("</dl>");
						html+=("</li>");
					})
					
					html+=("<script>");
					html+=("	$(function(){");
					html+=("		$(\"#changeBoxS1\").xslider({");
					html+=("			unitdisplayed:6,");
					html+=("			movelength:1,");
					html+=("			maxlength:null,");
					html+=("			unitlen:120,");
					html+=("			dir:\"H\",");
					html+=("			autoscroll:null");
					html+=("		});");
					html+=("	});");
					html+=("</script>");
					
					$("#div_selectlist").html("");
					$("#div_selectlist").append(html);
					
					show_select();
					
				}
			}
		})
	}
	

	//选择付款方式
	$("input[name='pay_type']").click(function(){
		var type=$(this).val();
		$("#iex_pay_type").val(type);
	})

	//up选择地址
	$("#exchange_toaddress").click(function(){
		$("#to_set_address").show();
	})

	
var user_score=$("#user_score").val();//用户当前的积分数
	
 function get_pay_type(){
	 $.ajax({
		 url:"/try/get_pay_type.html",
		 type:"post",
		 dataType:"json",
		 async:false,
		 data:"boxid="+boxid,
		 success:function(ret){
			 if(parseInt(ret.status)==1){
				 $("#iex_userscore").val(ret.info.user_score);//用户当前的总积分
				 $("#iex_productscore").val(ret.info.products_score);//用户选择的产品的积分
				 $("#iex_totalnum").html(ret.info.num);//用户选择的产品的总份数
				 $("#iex_totalscore").val(ret.info.total_score);//用户需要支付的总积分
				 $(".user_score").html(ret.info.user_score);
				 $(".iex_productscore").html(ret.info.products_score);
				 $(".total_score").html(ret.info.total_score);
			 }
		 }
	 })
 }



//选择的地址
function select_address(){
	var id=$("#exchange_addresslist").val();
	$("#iex_pay_address").val(id);
	$.ajax({
		url:"/try/getUserAddressInfo.html",
		data:"id="+id,
		dataType:"json",
		type:"post",
		anynic:false,
		success:function(ret){
			if(ret.status==1){
				var linkman=ret.info.linkman;
				var tel=ret.info.telphone;
				var address=ret.info.province+ret.info.city+ret.info.district+ret.info.address+"("+ret.info.postcode+")";
				$("#exchange_linkman").val(linkman);
				$("#exchange_tel").val(tel);
				$("#exchange_address").val(address);
			}
		}
	})
}



//获取用户地址列表
	function get_user_addresslist(id){
		$.ajax({
			url:"/buy/getuserAddress.html",
			data:"id="+id,
			type:"post",
			dataType:"json",
			async:false,
			success:function(ret){
				if(ret.status==1){
					if(ret.data!=""){
						html="";
						$.each(ret.data,function(ikey,ival){
							html+="<option value=\""+ival.id+"\">"+ival.linkman+"，"+ival.telphone+"，"+ival.province+ival.city+ival.district+ival.address+"("+ival.postcode+")</option>";
							if(ikey==0){
								$("#iex_pay_address").val(ival.id);
								
								$("#exchange_linkman").val(ival.linkman);
								$("#exchange_tel").val(ival.telphone);
								var address=ival.province+ival.city+ival.district+ival.address+"("+ival.postcode+")";
								$("#exchange_address").val(address);
							}
						})
					}
				}else{
					html="<option value=\"0\">没有收货地址</option>";
					$("#iex_pay_address").val(0);
				}
				$("#to_set_address").hide();
				$("#exchange_addresslist").html("");
				$("#exchange_addresslist").append(html);
			}
		})
	}


//提交兑换清单
function to_next(){
	var addressid=$("#iex_pay_address").val();
	if(addressid=="" || addressid==0){
		alert("请选择收货地址");return false;
	}		
	$("#step2_linkman").html($("#exchange_linkman").val());
	$("#step2_tel").html($("#exchange_tel").val());
	$("#step2_address").html($("#exchange_address").val());
	
	$(".total_score").html($("#iex_user_totalscore").val());
	
	
	var get_total_score=$(".step2_costscore").html();
	if(get_total_score>0){
		$("#confirm_tips").show();
	}else{
		$("#confirm_tips").hide();
	}
	
	$("#step1").hide();
	$("#step2").show();
}

function to_last(){
	$("#step2").hide();
	$("#step1").show();
}

//去支付
function exchange_topay(obj){
	//积分兑换-----支付积分
	var aid=$("#iex_pay_address").val();
	var boxid=$("#boxid").val();
	$(obj).find("span").html("支付中...");
	$(obj).removeAttr("onclick");
	$(obj).addClass("W_pin_grey");
	$(obj).removeClass("W_pin_b14");
	$.ajax({
		url:"/try/exchange_confirm.html",
		type:"post",
		dataType:"json",
		data:{aid:aid,boxid:boxid},
		success:function(ret){
			if(parseInt(ret.status)==0){
				id_dialog(ret.info,obj,2,function(){
					location.reload();
				});
			}else{
				var message="	<p>您的积分已经被成功扣减"+ret.info.score+" ，积分兑换订单号："+ret.info.orderid+"。</p><p>您可以：</p><p>【<a href='/home/order_detail/id/"+ret.info.orderid+".html' class='A_line3'>查看订单详情</a>】 【<a href='/try/iexchange.html' class='A_line3'>再次兑换</a>】</p>";
				$("#order_info").append(message);
				$(".select_div").hide();
				$("#step3").show();
			}
		}
	})
}

