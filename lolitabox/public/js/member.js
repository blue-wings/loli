/**
 * 判断用户是否能购买
 * @author penglele
 */
function check_member(obj){
	if(!USER_ID){
		dialog_login();
	}else{
		var type=$("#memberid").val();
		if(!type){
			id_dialog("请选择您要购买的特权会员类型",obj,2);return false;
		}
		if(document.getElementById("member_sure").checked==false){
			id_dialog("请选择并接受萝莉盒会员服务条款",obj,2);
			return false;
		}
		$.ajax({
			url:"/member/check_if_membere.html",
			type:"post",
			dataType:"json",
			data:{type:type},
			async:false,
			success:function(ret){
				if(parseInt(ret.status)==1){
					open_paystate();
					$("#memform").submit();
					return false;
				}else{
					if(parseInt(ret.data)==100){
						show_memmsg(ret.info,obj);
					}else{
						id_dialog(ret.info,obj,2);	
					}
				}
			}
		})
	}
	return false;
}

function show_memmsg(id,obj){
	if(!USER_ID){
		dialog_login();return false;
	}
	if(id==1){
		var msg="<p>非常抱歉，您已经购买过优惠价为5元的月度特权会员。</p><p>在2014年1月1日前不能再次购买优惠期内的月度特权会员。</p><p>请您选择购买半年特权或年度特权。</p><p><a href='/home/member.html' target='_blank' class='A_line3 judge_login'>(查看特权会员订单)</a></p>";
	}else{
		var msg="<p>对不起，因为您曾经购买过非月度特权会员，目前</p><p>的月度特权会员为尝新特价，只允许第一次成为特</p><p>权员会选择，请在2014年1月1日后再进行选择购买。 </p><p><a href='/home/member.html' target='_blank' class='A_line3 judge_login'>(查看特权会员订单)</a></p>";
	}
	id_dialog(msg,obj,2);
}

/**
 * 提交订单的提示信息
 * @author penglele
 */
function open_paystate(){
	$.dialog({
	    content: document.getElementById('confirm_tips'),
	    id: 'confirm_tips',
	    close:function(){
	    	location.reload();
	    },
	    fixed:true,
	    opacity:0.3,
	    lock:true
	});
}

function choose_member(type){
	if(!USER_ID){
		dialog_login();
	}else{
		var memberid=parseInt($("#memberid").val());
		if(memberid!=parseInt(type)){
			var price=$("#type_price_"+type).val();
			var name=$("#type_name_"+type).val();
			var stime=$("#type_stime_"+type).val();
			var etime=$("#type_etime_"+type).val();
			var info=stime+" 至 "+etime;
				//2013年10月21日 至 2013年11月20日
			$("#select_name").html(name);
			$("#select_time").html(info);
			$("#select_money").html(price);
			$("#memberid").val(type);
			var id="typeSelect"+type;
			document.getElementById(id).checked=true;
			
			$('.allclass').css({background :'none',border:'2px solid #fff'})
			$('.mclass'+type).css({background :'#fff6d2',border:'2px solid #e7308c'})
		}
	}
}




