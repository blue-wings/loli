//v5活动

$(function(){
	$(".actv5-select").click(function(){
		if(!USER_ID){
			dialog_login();return false;
		}
		if($(this).hasClass("W_pin_grey")){
			return false;
		}		
		//当前的类型共选择的数量
		var total_selectnum=$("#total_selectnum").val();
		var obj=this;
		if(total_selectnum>=5){
			id_dialog("限时抢活动每单最多可选5件产品",obj,2);return false;
		}
		var pid=$(this).attr("select-id");
		var type=$(this).attr("select-type");
		var title=$(this).attr("select-name");
		var select_num=$("#selet_type_"+type).val();
		if(parseInt(select_num)>=parseInt(type)){
			id_dialog("<p>不要太贪心哦，"+title+"只能选择"+type+"件哦！</p><p>您可以去兑换清单中迚行修改哦~</p>",obj,2);
			return false;
		}
		$.ajax({
			url:"/activity/ajaxv5_select_product.html",
			type:"post",
			dataType:"json",
			data:"pid="+pid+"&type="+type,
			success:function(ret){
					if(parseInt(ret.status)==0){
						id_dialog(ret.info,obj,2);
						return false;
					}else{
						$(obj).removeClass("W_pin_b");
						$(obj).addClass("W_pin_grey");		
						$(obj).find("span").html("已选择");
						$("#i_select_"+pid).show();
						select_num++;
						$("#selet_type_"+type).val(select_num);
						if(total_selectnum==0){
							$("#iexchange_nomsg").hide();
						}
						total_selectnum++;
						$("#total_selectnum").val(total_selectnum);
						$(".select_totalnum").html(total_selectnum);
						var pro_totalscore=parseInt($(".pro_totalscore").html())+parseInt(ret.info.discount_score);
						$(".pro_totalscore").html(pro_totalscore);
						
						html=("<li id=\"have_select_"+pid+"\">");
						html+=("<dl class=\"cfl\">");
						html+=("<dt><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ret.info.productid+"');\"><img src=\""+ret.info.pimg+"\" width=\"64\" height=\"64\" alt=\""+ret.info.pname+"\" /></a></dt>");
						html+=("<dd class=\"name\"><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ret.info.productid+"');\">【"+title+"】"+ret.info.pname+"</a></dd>");
						html+=("<dd class=\"select\"><span>"+ret.info.norms+"</span><a href=\"javascript:void(0)\" class=\"delect\" onclick=\"del_actv5(\'"+pid+"\','"+type+"','"+ret.info.discount_score+"',this)\">删除</a></dd>");
						html+=("</dl>");
						html+=("</li>");
						$("#zixuan_selectlist").prepend(html);
						return false;
					}
				}
			})
	})
})


function del_actv5(id,type,p_score,obj,aid){
	if(!USER_ID){
		dialog_login();return false;
	}
	if(!id || !type){
		return false;
	}
	$.ajax({
		url:"/activity/del_actv5_product.html",
		data:"id="+id,
		type:"post",
		dataType:"json",
		success:function(ret){
				if(parseInt(ret.status)==0){
					id_dialog("<p>"+ret.info+"</p>",obj,2);return false;
				}else{
					var score=parseInt($(".pro_totalscore").html())-parseInt(p_score);
					var num=parseInt($(".select_totalnum").html())-1;
					//没有任何产品时显示的提示信息
					if(num==0){
						$("#iexchange_nomsg").show();
					}
					
					var type_num=$("#selet_type_"+type).val()-1;
					$("#selet_type_"+type).val(type_num);
					
					//此处是对已选择产品列表的显示的动态修改
					$(".pro_totalscore").html(score);//动态修改已选总积分数
					$(".select_totalnum").html(num);	//动态修改已选产品总数
					$("#total_selectnum").val(num);
					$("#have_select_"+id).remove();//将已选产品动态删除掉
					
					var btn_name="act_select_"+id;
					//将按钮置为可选状态
					$("#"+btn_name).removeClass("W_pin_grey");
					$("#"+btn_name).addClass("W_pin_b");
					//设置按钮文字
					$("#"+btn_name).find("span").html("我要抢购");
					//设置已选择标签
					$("#i_select_"+id).hide();
					if(aid==1){
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

//立即兑换
function actv5_buy(obj){
	if(!USER_ID){
		dialog_login();return false;
	}
	$.ajax({
		url:"/activity/check_actv5_productslist.html",
		type:"post",
		dataType:"json",
		success:function(ret){
			if(parseInt(ret.status)==0){
				if(parseInt(ret.data)==100){
					//当已选产品部分已售完时
					html="<p><b>以下产品已经售完，请重新选择：</b></p>";
					html+="<ul>";
					$.each(ret.info,function(ikey,ival){
						html+="<li>"+ival+"</li>";
					})
					html+="</ul>";
					var msg=html;
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
					html+=("<dl>");
					html+=("<dt>");
					html+=("<div class=\"tc_changeBox_close\"><a href=\"javascript:void(0)\" onclick=\"del_actv5('"+ival.itemid+"','"+ival.maxquantitytype+"','"+ival.discount_score+"',this,1)\" class=\"W_ico12 icon_close\"></a></div>");
					html+=("<a href=\""+ival.producturl+"\" target=\"_blank\" class=\"B_line1\"><img src=\""+ival.pimg+"\" width=\"98\" height=\"98\"></a>");
					html+=("<p class=\"pos_txt\"><i></i><span>规格："+ival.norms+"</span></p>");
					html+=("</dt>");
					html+=("<dd class=\"names\"><a href=\""+ival.producturl+"\" target=\"_blank\" class=\"A_line2\">"+ival.pname+"</a></dd>");
					html+=("<dd class=\"integral\">"+ival.discount_score+" 积分</dd>");
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


function actv5_topay(obj){
	//积分兑换-----支付积分
	var aid=$("#iex_pay_address").val();
//	var boxid=$("#boxid").val();
	$(obj).find("span").html("支付中...");
	$(obj).removeAttr("onclick");
	$(obj).addClass("W_pin_grey");
	$(obj).removeClass("W_pin_b14");
	$.ajax({
		url:"/activity/actv5_confirm.html",
		type:"post",
		dataType:"json",
		data:{aid:aid},
		success:function(ret){
			if(parseInt(ret.status)==0){
				if(parseInt(ret.data)==100){
					html="<p><b>以下产品已经售完，请重新选择：</b></p>";
					html+="<ul>";
					$.each(ret.info,function(ikey,ival){
						html+="<li>"+ival+"</li>";
					})
					html+="</ul>";
					var msg=html;
				}else{
					var msg="<p>"+ret.info+"</p>";
				}
				id_dialog(msg,obj,2,function(){
					location.reload();
				});
			}else{
				var message="	<p>您的积分已经被成功扣减"+ret.info.score+" ，积分兑换订单号："+ret.info.orderid+"。</p><p>您可以：</p><p>【<a href='/home/order_detail/id/"+ret.info.orderid+".html' class='A_line3'>查看订单详情</a>】 【<a href='/activity/lolihighv5/type/2.html' class='A_line3'>再次兑换</a>】</p>";
				$("#order_info").append(message);
				$(".select_div").hide();
				$("#step3").show();
			}
		}
	})
}

