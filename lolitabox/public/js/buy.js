  var boxid=$("#boxid").val();
  var if_select_box=$("#if_select_box").val();//判断是否是自选盒
  
  /**
   * 判断用户是否可以购买此盒子
   * @param bid
   * @author penglele
   */
  function check_buy(bid,obj){
	  if(!bid){
		  return false;
	  }
	  if(if_select_box==1){
		  var zixuan_url="/buy/zixuan/boxid/"+bid;
		  location.href=zixuan_url;
		  return false;
	  }
	  if(!USER_ID){
		  dialog_login();
		  return false;
	  }
	  $.ajax({
		  url:"/buy/check_user_ifbuy.html",
		  type:'post',
		  dataType:"json",
		  data:"boxid="+bid,
		  success:function(ret){
			  if(parseInt(ret.status)==0){
				  if(parseInt(ret.data)==1000){
					  location.href=ret.info;
					  return false;
				  }else if(parseInt(ret.data)==11){
					  var content="<p>不能太贪心哦，您已经购买过该萝莉盒啦！</p>";
				  }else if(parseInt(ret.data)==100){
					  var content="<p>很抱歉，该产品数量有限，已经售罄。</p><p>建议您下次提前购买，并随时关注我们新推出的萝莉盒。</p>";
				  }else if(parseInt(ret.data)==200){
					  var content="<p>您之前购买过其他萝莉盒，就不能购买这个啦！</p>";
				  }else if(parseInt(ret.data)==300){
					  var content="<p>您需要进行手机验证后才可以订购</p><p><a href='/task/index/id/3.html' style='color:red;' target='_blank'>去验证</a></p>";
				  }else if(parseInt(ret.data)==400){
					  var content="<p>对不起，您正在订购的萝莉盒只允许特权会员购买。</p><p><a href='/info/lolitabox/aid/1484.html' target='_blank' class='A_line3'>什么是特权会员？</a>  <a href='/member/index.html' target='_blank' class='A_line3'>立即成为特权会员</a></p>";
				  }else{
					  var content=ret.info;
				  }
				  id_dialog(content,obj,2);
				  return false;
			  }else{
				  var detail_url="/buy/detail/boxid/"+bid;
				  location.href=detail_url;
				  return false;
			  }
		  }
	  })
  }
  
  
$(function(){
	$(".zixuan_select").click(function(){
		if(!USER_ID){
			dialog_login();return false;
		}
		//如果当前的产品是已选择状态，则不能被选择
		if($(this).hasClass("W_pin_grey")){
			return false;
		}
		var obj=this;
		var pid=$(this).attr("zixuan-id");
		var type=$(this).attr("zixuan-type");
		var title=$(this).attr("zixuan-name");
		
		//当前的类型共选择的数量
		var now_num=$("#pro_type_"+type).val();
		var total_selectnum=$("#total_selectnum").val();
		
		if(now_num>=type){
			id_dialog("<p>不要太贪心哦，"+title+"只能选择"+type+"件哦！</p><p>您可以从右侧已选的产品中移除某件产品后，再重新选择哦^_^</p>",obj,2);
			return false;
		}
		$.ajax({
			url:"/buy/ajax_select_product.html",
			type:"post",
			dataType:"json",
			data:"boxid="+boxid+"&pid="+pid+"&type="+type,
			success:function(ret){
					if(ret.status==1){
						var id_name="iex_select_"+pid;
						now_num++;
						$("#pro_type_"+type).val(now_num);
						$("#i_select_"+pid).show();
						$("#"+id_name).removeClass("W_pin_b");
						$("#"+id_name).addClass("W_pin_grey");
						$("#"+id_name+" span").html("已选择");
						if(total_selectnum==0){
							$("#iexchange_nomsg").hide();
						}
						total_selectnum++;
						$("#total_selectnum").val(total_selectnum);
						
						html=("<li id=\"have_select_"+pid+"\">");
						html+=("<dl class=\"cfl\">");
						html+=("<dt><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ret.info.productid+"');\"><img src=\""+ret.info.pimg+"\" width=\"64\" height=\"64\" alt=\""+ret.info.pname+"\" /></a></dt>");
						html+=("<dd class=\"name\"><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ret.info.productid+"');\">【"+title+"】"+ret.info.pname+"</a></dd>");
						html+=("<dd class=\"select\"><span>"+ret.info.norms+"</span><a href=\"javascript:void(0)\" class=\"delect\" onclick=\"del_zixuan(\'"+pid+"\','"+type+"')\">删除</a></dd>");
						html+=("</dl>");
						html+=("</li>");
						$("#zixuan_selectlist").prepend(html);
						return false;
							
					}else{
						if(parseInt(ret.data)==100){
							var s_mess="<p>亲，该萝莉盒是新会员专享的，老会员不能购买哦~</p>";
						}else if(parseInt(ret.data)==200){
							var s_mess="<p>亲，不要贪心啦，该萝莉盒不能重复购买哦~</p>";
						}else if(parseInt(ret.data)==300){
							var s_mess="<p>对不起，您正在订购的萝莉盒只允许特权会员购买。</p><p><a href='/info/lolitabox/aid/1484.html' target='_blank' class='A_line3'>什么是特权会员？</a>  <a href='/member/index.html' target='_blank' class='A_line3'>立即成为特权会员</a></p>";
						}else{
							var s_mess="<p>"+ret.info+"</p>";
						}
						id_dialog(s_mess,obj,2);
					}
				}
			})			
	})
})

	function to_qiang(id){
		//没有登录的状态
		if(USER_ID==""){
			dialog_login();
			return false;
		}
		if(!id){
			return false;
		}
		$.ajax({
			url:"/buy/get_hongbao.html",
			type:"post",
			dataType:"json",
			data:"id="+id+"&boxid="+boxid,
			success:function(ret){
				if(parseInt(ret.status)==1){
					//如果抢到红包的一系列状态变化
					$(".qiang_hb").show();
					//$(".have_hb").hide();
					$(".cancel_hb").hide();
					$("#qiang_hb_"+id).hide();
					//$("#have_hb_"+id).show();
					$("#cancel_hb_"+id).show();
				}
			}
		})
	}
	
	function cancel_hb(id){
		if(!id){
			return false;
		}
		$.ajax({
			url:"/buy/to_cancel_hongbao.html",
			type:"post",
			dataType:"json",
			data:"id="+id+"&boxid="+boxid,
			success:function(ret){
				if(parseInt(ret.status)==1){
					//取消红包后的一系列变化
					$("#qiang_hb_"+id).show();
					//$("#have_hb_"+id).hide();
					$("#cancel_hb_"+id).hide();
				}
			}
		})
	}

//加价购----------start++++++++++++++++

	function show_msg(totalnum,data_num,url,obj){
		if(parseInt(data_num)==100){//没有选全
			//弹出框中带有选择的都可以使用弹框中的button属性
			$.dialog({
				follow:obj,
				content:"<p>亲爱的Lolitagirl,您还没有选够"+totalnum+"件产品哦~</p><p>您最多可以选择"+totalnum+"件产品，当然没有选择够也是可以提交订单的哦~</p>",
				lock:true,
				opacity:0.2,
				follow:obj,
				 button: [
				          {
				        	  name: '返回继续选择>>',
				              callback: function () {
				      			$.dialog.close();
				              }
				          },
				          {
				        	  name: '直接提交订单>>',
				              callback: function () {
				      			location.href=url;
				      			return false;
				              }
				          }
				      ],
				close:function(){
					$.dialog.close();
				},
				padding:"20px 20px"
			})
		}else{
			location.href=url;
		}
	}

//删除已选的产品--自选
function del_zixuan(pid,type){
	if(!USER_ID){
		dialog_login();return false;
	}
	if(!pid){
		return false;
	}
	$.ajax({
		url:"/buy/delete_products_select.html",
		type:"post",
		dataType:"json",
		data:"pid="+pid+"&boxid="+boxid,
		success:function(ret){
			if(ret.status==1){
				$("#i_select_"+pid).hide();
				$("#iex_select_"+pid).removeClass("W_pin_grey");
				$("#iex_select_"+pid).addClass("W_pin_b");
				$("#have_select_"+pid).remove();
				var num=$("#pro_type_"+type).val();
				num--;
				$("#pro_type_"+type).val(num);
				$("have_select_"+pid).remove();
				//当已选产品全部被删除时，提示信息显示
				var total_selectnum=$("#total_selectnum").val();
				total_selectnum--;
				$("#total_selectnum").val(total_selectnum);
				if(total_selectnum==0){
					$("#iexchange_nomsg").show();
				}
			}
		}
	})	
}

//自选产品，选择完成后提交去支付
function check_zixuan(type,obj){
	if(!USER_ID){
		dialog_login();
		return false;
	} 
	var url="/buy/detail/boxid/"+boxid+"";
	$.ajax({
		url:"/buy/check_box_products.html",
		type:"post",
		dataType:"json",
		data:"boxid="+boxid,
		async:false,
		success:function(ret){
			//自选页面的提交订单
			if(type==1){
				if(parseInt(ret.status)==0){
					if(ret.data==200){
						$.dialog({
							content:"<p>亲爱的Lolitagirl,真的很抱歉哦~</p><p>您挑选的产品中某些已被抢光了，还得麻烦您再重新挑选一下下~</p>",
							lock:true,
							opacity:0.2,
							follow:obj,
							okVal:"马上去挑选>>",
							ok:function(){
								location.reload();
							},
							close:function(){
								location.reload();
							}
						})
						return false;
					 }else{
						 id_dialog("<p>"+ret.info+"</p>",obj,2);
					} 
				}else{
					show_msg(ret.info,ret.data,url,obj);
				}				
			}else if(type==2){
				//核对信息、confirm页面的检测自选产品是否已售完
				var num=ret.data;
				var total_num=ret.info;
				$("#pro_info").val(num);
				$("#total_num").val(total_num);
			}
		}
	})
}
 
 //动态加载地址列表-------只有在萝莉盒核对信息页才需要调用------
$(function(){
	if(if_select_box==1){
		getProductList();
	}
})

   var if_try=$("#if_try").val();
	//提交前的判断----核对信息页
	function check_detail(type,obj){
		  if(!USER_ID){
			  dialog_login();return false;
		  }
		  var address_info=$("input[name='addres']:checked").val();
		  if(address_info==0 || address_info==""){
			  n_dialog("<p>亲爱的，您还没有创建收货地址哦~</p>",3);
			  return false;
		  }
		  //判断是否为付邮试用
		  if(if_try==1){
			  var try_id=$("#try_id").val();
			  check_try(try_id);
			  var try_num=$("#try_num").val();
			  if(try_num==1){
				  $("#formdetail").submit();
				  return false;
			  }else if(try_num==300){
				  var title="<p>非常抱歉，您目前的经验不足<b>100</b>。</p><p>根据付邮试用规定，经验值不足100时无法参与付邮试用。</p><p><a href=\"/info/lolitabox/aid/1235.html\" target=\"_blank\" class=\"A_line3\">如何获得经验值?</a></p> ";
					id_dialog(title,obj,2);
			  }else{
				  id_dialog("您选择的产品已售完",obj,2);
			  }
			  return false;
		  }
		  //普通盒
		  if(if_select_box!=1){
			  $("#formdetail").submit();
			  return false;
		  }
		 check_zixuan(type,obj);
	   	 var num=$("#pro_info").val();
	   	 var total_num=$("#total_num").val();
		  
//		  //以下只有当为自选盒时，才执行
		  if(num==200){
				$.dialog({
					follow:obj,
					content:"<p>亲爱的Lolitagirl,真的很抱歉哦~</p><p>您挑选的产品中某些已被抢光了，还得麻烦您再重新挑选一下下~</p>",
					lock:true,
					opacity:0.2,
					follow:obj,
					 button: [
					          {
					        	  name: '马上去挑选>>',
					              callback: function () {
									  location.href=url;
					              }
					          }
					      ],
					close:function(){
						location.reload();
					},
					padding:"20px 20px"
				})	
			  getProductList();
			  return false;
		  }else if(num>0){
			  if(num==100){
				  var url="/buy/zixuan/boxid/"+boxid+"";
					$.dialog({
						follow:obj,
						content:"<p>亲爱的Lolitagirl,您还没有选够"+total_num+"件产品哦~</p><p>您最多可以选择"+total_num+"件产品，当然没有选择够也是可以提交订单的哦~</p>",
						lock:true,
						opacity:0.2,
						follow:obj,
						 button: [
						          {
						        	  name: '返回继续选择>>',
						              callback: function () {
						            	  location.href=url;
						            	  return false;
						              }
						          },
						          {
						        	  name: '直接提交订单>>',
						              callback: function () {
											$("#formdetail").submit();
											return false;
						              }
						          }						          
						      ],
						      padding:"20px 20px"
					})	
					return false;
			  }else{
				  $("#formdetail").submit();
					return false;
			  }
		  }
		  return false;
		}

//验证优惠券
function checkcoupon(obj){
	$("#couponlist_div").hide();
	var code = $("#couponcode").val();
	var id="check_cou";
	var nowprice=$("#now_price").val();   //盒子需要支付的价格
	var if_giftcard=$("#if_usegiftcard").val();
	$("#if_coupon").val(0);
	if (code == "") {
		$(".codeprice").html(0);
		$(".totalprice").html(nowprice);
		notice(""+id+"",0,"请输入优惠券号码！");
		return false;
	}
	if(if_giftcard==1){
		$("#couponcode").val('');
		id_dialog("您已经使用礼品卡余额支付，不能使用优惠券哦~~",obj,2);return false;
	}
	var result = false;
	$.ajax({
		type:"POST",
		async:false,
		dataType: "json",//返回json格式的数据
		url:"/buy/check_coupon.html",
		timeout:10000,
		cache: false,
		data:"couponcode="+code,
		success:function(data){
			if(data.data=="1"){   
				notice(""+id+"",0,"优惠券已使用!");
				$(".codeprice").html(0);
				$(".totalprice").html(nowprice);
				result=false;
			}else if(data.data=="2"){
				notice(""+id+"",0,"优惠券已过期!");
				$(".codeprice").html(0);
				$(".totalprice").html(nowprice);
				result=false;
			}else if(data.data == "3"){
				notice(""+id+"",0,"优惠券无效!");
				$(".codeprice").html(0);
				$(".totalprice").html(nowprice);
				result=false;
			}else {
				var couponinfo = data.data;
				if(parseInt(couponinfo)>=parseInt(nowprice)){
					$(".totalprice").html(0);
				}else{
					$(".totalprice").html(nowprice-parseInt(couponinfo));
				}
				notice(""+id+"",1,"");
				$(".codeprice").html(couponinfo);
				$("#couponprice").val(couponinfo);
				$("#if_coupon").val(1);
				$("#cancelcoupon").show();
				return true;
			}			
		}		
   });
}
/**
 * 取消使用优惠券
 * @author penglele
 */
function cancel_coupon(obj){
	$("#couponcode").val('');
	var nowprice=$("#now_price").val(); 
	$(".totalprice").html(nowprice);
	$(".codeprice").html(0);
	$("#if_coupon").val(0);
	$("#check_cou").next(".tips").remove();
	$("#cancelcoupon").hide();
}

$(function(){
	$("#choose_giftcard").click(function(){
		var nowprice=$("#now_price").val(); 
		if(document.getElementById("choose_giftcard").checked==true){
			var if_coupon=$("#if_coupon").val();
			var obj=this;
			if(parseInt(if_coupon)==1){
				document.getElementById("choose_giftcard").checked==false;
				id_dialog("您已经使用优惠券，不能再使用礼品卡余额支付哦~~",obj,2);
				return false;
			}else{
				$.ajax({
					url:"/home/get_user_giftcard_price.html",
					type:"post",
					dataType:"json",
					success:function(ret){
						if(parseInt(ret.data)-nowprice>=0){
							$("#detail_pay_bank").hide();
							$(".totalprice").html(0);
							$(".cost_giftcardprice").html(parseInt(nowprice));
							$("#cost_giftcard_price").val(parseInt(nowprice));
						}else{
							$("#detail_pay_bank").show();
							$(".totalprice").html(nowprice-parseInt(ret.data));
							$(".cost_giftcardprice").html(ret.data);
							$("#cost_giftcard_price").val(ret.data);
						}
						if(parseInt(ret.data)>0){
							$("#if_usegiftcard").val(1);
							if(parseInt(ret.data)!=parseInt($(".giftcard_price").html())){
								$(".giftcard_price").html(ret.data);
							}
						}
						$(".giftcard_price").html(ret.data);
					}
				})			
			}			
		}else{
			$("#if_usegiftcard").val(0);
			$("#cost_giftcard_price").val(0);
			$(".cost_giftcardprice").html(0);
			$(".totalprice").html(parseInt(nowprice));
			$("#detail_pay_bank").show();
		}
	})
})

//获取自选产品列表信息
function getProductList(){
	  $.ajax({
		  url:"/buy/get_product_list.html",
		  type:"post",
		  dataType:"json",
		  data:"boxid="+boxid,
		  success:function(ret){
				  if(ret.info!=""){
					  $("#select_product_ul").remove();
					  var htmls=("<ul class=\"scroll_cont_con cfl\" id=\"select_product_ul\">");
					  for(i=0;i<ret.info.length;i++){
						  var ival=ret.info[i];
						  htmls+=("<li>");
						  htmls+=("<dl class=\"dl_box\">");
						  htmls+=("<p class=\"box_fabric\"><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ival.productid+"')\"><img src=\""+ival.pimg+"\" width=\"110\" height=\"110\" alt=\""+ival.pname+"\"></a></p>");
						  htmls+=("<p class=\"name\"><a href=\"javascript:void(0)\" onclick=\"dialog_products('"+ival.productid+"')\">"+ival.pname+"</a></p>");
						  htmls+=("</dl>");
						  htmls+=("</li>");
					  }
					  htmls+="</ul>";
					  $("#select_product_list").append(htmls);
				  }
			}
	  })
}
	
//原来的confirm页面的to_tijiao方法
function confirm_tijiao(type,obj){
		if(!USER_ID){
			dialog_login();return false;
		}
		//判断是否是付邮试用订单
		if(if_try==1){
			var pid=$("#pid").val();
			check_try(pid);
			  var try_num=$("#try_num").val();
			  if(try_num==1){
				  open_paystate();
				  $("#form1").submit();
			  }else if(try_num==300){
				  var title="<p>非常抱歉，您目前的经验不足<b>100</b>。</p><p>根据付邮试用规定，经验值不足100时无法参与付邮试用。</p><p><a href=\"/info/lolitabox/aid/1235.html\" target=\"_blank\" class=\"A_line3\">如何获得经验值?</a></p>";
					id_dialog(title,obj,2);
			  }else{
				  id_dialog("您选择的产品已售完",obj,2);
			  }
			return false;
		}
		
		//普通萝莉盒直接提交
		if(if_select_box<1){
	   		open_paystate();
	   		$("#form1").submit();
			return false;
	   	 }
		if(if_select_box==1){
			check_zixuan(type,obj);
		   	 var num=$("#pro_info").val();
		   	 var total_num=$("#total_num").val();
		   	 var url="/buy/zixuan/boxid/"+boxid;
			  if(num==200){
					$.dialog({
						follow:obj,
						content:"<p>亲爱的Lolitagirl,真的很抱歉哦~</p><p>您挑选的产品中某些已被抢光了，还得麻烦您再重新挑选一下下~</p><p><a href=\"javascript:void(0)\" id='go_select' class='A_line3'>选>></a></p>",
						lock:true,
						opacity:0.2,
						follow:obj,
						 button: [
						          {
						        	  name: '马上去挑选>>',
						              callback: function () {
										  location.href=url;
										  getProductList();
										  return false;
						              }
						          }
						      ],
						close:function(){
							location.reload();
						},
						padding:"20px 20px"
					})
					return false;
			  }else if(num>0){
				  if(num==100){
						$.dialog({
							follow:obj,
							content:"<p>亲爱的Lolitagirl,您还没有选够"+total_num+"件产品哦~</p><p>您最多可以选择"+total_num+"件产品，当然没有选择够也是可以提交订单的哦~</p>",
							lock:true,
							opacity:0.2,
							follow:obj,
							 button: [
							          {
							        	  name: '放弃此订单，重新选择>>',
							              callback: function () {
							            	  location.href=url;
							            	  return false;
							              }
							          },
							          {
							        	  name: '立即去支付>>',
							              callback: function () {
							            	  $("#form1").submit();
							            	  open_paystate();
							              }
							          }
							      ],
							      padding:"20px 20px"
						})
				  }else{
					  open_paystate();
					  $("#form1").submit();
					  return false;;
				  }
			  }else{
				  return false;
			  }			
		}
		  return false;
	}

function open_paystate(){
	var id=$("#ordernmb").val();
	$.dialog({
	    content: document.getElementById('confirm_tips'),
	    id: 'confirm_tips',
	    close:function(){
	    	location.href="/buy/pay_result/id/"+id+".html";
	    },
	    fixed:true,
	    opacity:0.3,
	    lock:true
	});
}

function ishavePaysucc(orderid){
	//支付成功跳转页
	var id=$("#ordernmb").val();
	location.href="/buy/pay_result/id/"+id+".html";
}

