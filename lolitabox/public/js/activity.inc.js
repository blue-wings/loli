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
	
	/**
	 * 动态加载状态
	 */
	function get_pay_type(){
		 $.ajax({
			 url:"/activity/get_actv5_pay_type.html",
			 type:"post",
			 dataType:"json",
			 async:false,
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


	//up选择地址
	$("#exchange_toaddress").click(function(){
		$("#to_set_address").show();
	})

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
	
	
	
	
	
	
	
	
	