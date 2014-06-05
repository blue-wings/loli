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
				y_dialog("赞成功",3,false,obj);
			}else{
				n_dialog(data.info,3,false,obj);
			}
		}
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
				y_dialog("踩成功",3,false,obj);
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

