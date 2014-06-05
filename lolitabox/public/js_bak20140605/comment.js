/**
 * 点击回复按钮的效果
 * @param nickname
 * @param shareid
 * @param to_commentid
 * @param to_userid
 * @param id
 * @returns {Boolean}
 * @author litingting
 */
function open_receivecomment_reply(nickname,shareid,to_commentid,to_userid,obj){
	var reply_obj=$(obj).closest(".WB_func").next(".comment_reply");
	if(reply_obj.css("display")=="none"){
		var texts = reply_obj.find(".W_input_text");
		var to_nick="回复@"+nickname+" 的评论：";
		
		$("#shareid").val(shareid);
		$("#to_commentid").val(to_commentid);
		$("#to_userid").val(to_userid);
		$("#to_nick").val(nickname);
		
	   reply_obj.show();
	   texts.val("").focus().val(to_nick); 
	}else{
	   reply_obj.hide();
	}
	return false;
}

/**
 * 发表评论
 * @param id
 * @param to_userid
 * @param shareid
 * @param to_commentid
 * @returns {Boolean}
 */
function reply_receivecomment(id,obj){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	if(id){
		var content =$("#"+id).val();
	}else{
		var content = $(".W_input_text").val();
	}
	var nickname=$("#to_nick").val();
	var msg1="回复@"+nickname+" 的评论：";
	var msg2="回复@"+nickname+" 的分享：";
	if(!content || content==msg1 || content==msg2){
		alert("您还没有输入任何内容！");
		return false;
	}
	var shareid=$("#shareid").val();
	var to_userid=$("#to_userid").val();
	var to_commentid=$("#to_commentid").val();
    var evt = (evt) ? evt : ((window.event) ? window.event : "");
	//var target =  evt.target  ||  evt.srcElement || evt.currentTarget; 
	$.ajax({
		url:"/public/reply_comment",
		type:"post",
		data:"shareid="+shareid+"&to_uid="+to_userid+"&content="+content+"&commentid="+to_commentid,
		dataType:"json",
	    success:function(data){
	    	if(data.status==1){
	    		y_dialog("评论成功",3,function(){
	    			location.reload();
	    		},obj)
	    	}
	    	else{
	    		n_dialog("评论失败",2,false,obj);
	    	}
	    }
	})
}


/**
 * 删除评论
 * @param id
 * @returns {Boolean}
 * @author litingting
 */
function del_comment(id){
	if(!USER_ID){
		dialog_login();
		return false;
	}
	var evt = (evt) ? evt : ((window.event) ? window.event : "");
    var target =  evt.target  ||  evt.srcElement || evt.currentTarget; 
	if(confirm("删除后不可恢复，您确定要删除吗？")){
		$.ajax({
			url:"/home/delete_comment.html",
			type:"post",
			dataType:"json",
			data:"id="+id,
			success:function(ret){
				if((ret.status)==1){
					y_dialog("删除成功",2.5,function(){
						location.reload();
					},target);
				}else{
					n_dialog(ret.info,3);
				}
			}
		})
	}else{
		return false;
	}
}

/**
 * 详情页回复评论效果
 * @param nickname
 * @param shareid
 * @param to_commentid
 * @param to_userid
 * @param obj
 * @uses 用于分享详情页
 */
function comment_reply(){
	var texts = $(".W_input_text");
	var to_commentid=$("#to_commentid").val();
	var nickname=$("#to_nick").val();
	if(to_commentid){
		var to_nick="回复@"+nickname+" 的评论：";
	}
	//alert(to_nick);return false;
	if(to_nick){
		
	}
}

/**
 * 点击分享评论列表中某一条的“回复”的处理
 * @param nickname
 * @param shareid
 * @param to_commentid
 * @param to_userid
 * @param obj
 */
function go_comment_reply(nickname,to_commentid,to_userid){
	var texts = $(".W_input_text");
	$("#to_commentid").val(to_commentid);
	$("#to_userid").val(to_userid);
	$("#to_nick").val(nickname);
	if(to_commentid){
		var to_nick="回复@"+nickname+" 的评论：";
	}
	texts.val(to_nick);
	if(to_commentid){
		 texts.val("").focus().val(to_nick); 
	}
}