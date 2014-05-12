/**
 * 获取用户地址列表
 */
function load_address(id){
	  var addr="";
	  var j;
	    $.ajaxSettings.async = false;
		$.getJSON("/buy/getuserAddress.html",{"id":id},function(data){
			addr+=("<ul class=\"orderForm detail_addlist cfl\">");
			if(data.data!=0){
				  $.each(data.data, function(i,item){
					  j=i;
					  if(i==0){
						  addr+=("<li class=\"favo_Con\">");
					  }else{
						  addr+=("<li class=\"favo_Con favo_Con_gray\">");
					  }
					  addr+=("<p class=\"r\"><a href=\"javascript:void(0)\" onclick=\"editor(1,'"+item["linkman"]+"','"+item["id"]+"','"+item["telphone"]+"','"+item["province"]+"','"+item["city"]+"','"+item["district"]+"','"+item["address"]+"','"+item["postcode"]+"')\" class=\"A_line3\">修改此地址</a></p>");
					  if(i==0){
						  addr+=("<p class=\"l mr10 sdasdasds\"><i class=\"i_gps l mr5\"></i><span class=\"rd\">寄送至</span></p>");
					  }
					  addr+=("<div class=\"favo_min l\">");
					  if(i==0) {
						  addr+=("			<input type=\"radio\"  name=\"addres\"  checked='checked'     onclick=\"add_new("+item['id']+")\"   value=\""+item["id"]+"\" /> ");
						  var final_address=item["province"]+item["city"]+item["district"]+item["address"];
						  var final_user=item["linkman"]+"，"+item["telphone"];
						  $(".final_address").html(final_address);
						  $(".final_user").html(final_user);
						  $(".p-info").show();
					  }
					  else {
						  addr+=("			<input type=\"radio\"  name=\"addres\"    onclick=\"add_new("+item['id']+")\"    value=\""+item["id"]+"\" /> ");
					  }
					  addr+=("</div>");
					  addr+=("<span><span class=\"detail_linkman\">"+item["linkman"]+"</span>,<span class=\"detail_telphone\">"+item["telphone"]+"</span>, <span class=\"detail_province\">"+item["province"]+"</span> <span class=\"detail_city\">"+item["city"]+"</span> <span class=\"detail_district\">"+item["district"]+"</span> <span class=\"detail_address\">"+item["address"]+"</span> (<span class=\"detail_postcode\">"+item["postcode"]+"</span>)</span>");
					  addr+=("</li>");
				  });
			}else{
				j=undefined;
			}
			   if(j<2){
				   addr+=("<li class=\"favo_Con favo_tit\">");
				   addr+=("<div class=\"favo_min l\">");
				   addr+=("<input type=\"radio\"  name=\"addres\"   value=\"0\"  onclick=\"add_new(0)\" >");
				   addr+=("</div>");
				   addr+=("<span id=\"add_new\">创建新地址</span> </li>");
			   }
			   if(j==undefined){
				   addr+=("<li class=\"favo_Con favo_tit\">");
				   addr+=("<div class=\"favo_min l\">");
				   addr+=("<input type=\"radio\"  name=\"addres\"   value=\"0\"  onclick=\"add_new(0)\"  checked=\"checked\" >");
				   addr+=("</div>");
				   addr+=("<span id=\"add_new\">创建新地址</span> </li>");
			   }
			  addr+=(" </ul>");
			  $("#addresslist").html(addr);
		
					$('.detail_addlist > li').each(function(){
						
						$(this).click(function(){
							$(this).find('input')[0].checked = true;
							var nosele = $(this).hasClass('favo_Con_gray');
							var notxt =  $(this).hasClass('favo_tit');
							if( nosele||notxt ){
								$('.detail_addlist').find('li').eq(0).addClass('favo_Con_gray');
								$('.detail_addlist').find('li').eq(1).addClass('favo_Con_gray');
								$('.detail_addlist').find('li').eq(2).addClass('favo_Con_gray');
								$('.detail_addlist >li').find('.sdasdasds').remove()
								$(this).removeClass('favo_Con_gray') && $(this).removeClass('favo_tit');
								$(this).prepend("<p class=\"l mr10 sdasdasds\"><i class=\"i_gps l mr5\"></i><span class=\"rd\">寄送至</span></p>");
								if($(this).find('input')[0].value==0){
									$(".p-info").hide();
									add_new(0);
									set_area('北京市','北京市','东城区');
								}else{
									  var get_final_address=$(this).find(".detail_province").html()+$(this).find(".detail_city").html()+$(this).find(".detail_district").html()+$(this).find(".detail_address").html();
									  var get_final_user=$(this).find(".detail_linkman").html()+"，"+$(this).find(".detail_telphone").html();
									  $(".final_address").html(get_final_address);
									  $(".final_user").html(get_final_user);
									  $(".p-info").show();									
								}
							}
						})
					})
		});	  
	}

 
 
//设置省市地区信息（初始化/默认值）
 function set_area(prov,city,dist) {
 	if(prov=="" || prov=="请选择") {
 		$("#myarea").citySelect({nodata:"none",required:false});	
 	}
 	else {
 		$("#myarea").citySelect({
 	    	prov:prov, 
 	    	city:city,
 			dist:dist
 		}); 
 	}
 }
 
 
//动态显示修改收货地址表单
 function editor(type,linkman,id,telphone,province,city,district,address,postcode){
 	$("#add_newaddress").show();
 	$(".mima_tips_ico").hide();
 	$(".mima_tips_title").html("");
     $("#add_new").html("创建新地址");
 	$("#address_type").val(type);
 	$("#address_id").val(id); 
 	$(".MyCen1Form").show();
 	$("#linkman").val(linkman);
 	$("#telphone").val(telphone);
 	$("#address").val(address);
 	$("#postcode").val(postcode);
 	$(".edit_add").hide();
 	$(".bianji").show();
 	set_area(province,city,district);
 }
 
//验证新建/修改地址表单请求并处理
 function checkForm(){
 	var pro = $("#s1").val();
 	var city= $("#s2").val();
 	var dis=$("#s3").val();
 	if(dis==null || dis=="null"){
 		dis="";
 	}
 	var address_type=$("#address_type").val();
 	var address_id=$("#address_id").val();
 	var linkman = $("#linkman").val();
 	var telphone = $("#telphone").val();
 	var address = $("#address").val();
 	var postcode = $("#postcode").val();
 	
 	if(pro=="请选择" || pro==null){alert("请选择省份");return false;}
 	if(city=="请选择" || city==null || city==""){alert("请选择地级市");return false;}
 	if(linkman==''){alert("请填写联系人");return false;}
 	if(telphone==''){alert("请填写手机号");return false;}
 	if(address==''){alert("请填写地址");return false;}
 	if(postcode==''){alert("请填写邮编");return false;}

 	var reg0=/^1[3|4|5|8][0-9]\d{8}$/;
 	var reg5=/^[0-9]{6}$/;

 	if(!(reg5.test(postcode)))
 	{
 		alert('请输入正确的邮编!');
 		return false;
 	}

 	if(!(reg0.test(telphone))){
 		alert('请输入正确的手机号!');
 		return false;
 	}
 	if(address_type==""){
 		$.ajax({
 			url:"/home/add_address.html",
 			type:"post",
 			dataType:"json",
 			data:"linkman="+linkman+"&province="+pro+"&city="+city+"&district="+dis+"&address="+address+"&postcode="+postcode+"&telphone="+telphone,
 			success:function(chkresult){
 					if(parseInt(chkresult.status)==1){
 						y_dialog("恭喜您，添加地址成功！",1);
 						$("#add_newaddress").hide();
 					}else	{
 						n_dialog("很抱歉，添加地址失败！",1);
 					}
 					$("#dis").show();
 					load_address();
 			}
 		})	
 	}else	if(address_type==1){
 		$.ajax({
 			url:"/home/edit_address.html",
 			type:"post",
 			dataType:"json",
 			data:"linkman="+linkman+"&province="+pro+"&city="+city+"&district="+dis+"&address="+address+"&postcode="+postcode+"&telphone="+telphone+"&id="+address_id+"&act="+'edit',
 			success:function(chkresult){
 					if(chkresult.status==1){
 						y_dialog("恭喜您，修改成功！",1);
 						$("#add_newaddress").hide();
 					}else{
 						n_dialog("很抱歉，修改失败！",1);
 						$("#dis").show();
 					}
 					load_address()
 			}
 	})	 
 	}
 }
