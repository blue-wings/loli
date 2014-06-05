//用户投票
function benefit_vote(aid,type,weibo,cid,img,obj){
    if(!USER_ID){
    	benefit_login();
        return false;
    }
    $.ajax({
            url:"/club/benefit_vote.html",
            type:"post",
            dataType:"json",
            data:{aid:aid,type:type,weibo:weibo},
            success:function(ret){
            			var time=3;
                    if(parseInt(ret.status)==0){
                        id_dialog(ret.info,obj,2,false,10);
                    }else{
                    	//var time=10;
                        if(type==1){
                            if(weibo==0){
                                var msg="<p>gimme brow温馨提示：只有10%的网友选择了这张哦~</p><p>看来您的眼光很特别，不觉得“丰盈、立体”更漂亮更诱惑吗？</p>"
                            }else{
                                var msg="<p>gimme brow温馨提示：您的选择和90%的网友一致哦！</p><p>群众的眼睛果然是雪亮的，“丰盈、立体”就是让人神采奕奕，更具诱惑！</p>";
                                if(ret.data==1){
                                	var msg="<p>您的投票结果已经转发到微博。</p>"+msg;
                                }else{
                                	var returnurl=$("#returnurl").val();
                                	var url="/user/sina_lock.html?returnurl="+TRACK_URL;
                                	var msg="<div style='width:400px;'><p>感谢您的投票！<br/>非常遗憾，您的投票结果暂时无法顺利转发到新浪微博，所以不能够获得积分奖励。您可以稍等一会儿，再尝试点击“转发”按钮进行转发，转发成功就会立即获得积分奖励。<br>如果您还没有绑定微博，请先<a href='"+url+"' class='A_line3'>绑定新浪微博</a></p></div><br/>"+msg;
                                }
                            }
                            
                        }else{
                        	//第二阶段的投票提示信息+++++++++++++++
                            var msg="<p>恭喜，投票成功！</p><p>gimme brow眉梦成真丰眉膏，</p><p>帮你实现丰盈、立体的好运眉！</p><p><a href='/products/63118.html' target='_blank' class='A_line3'>去看看>></a></p>";
                        }
                        $(".benefit_vo_"+aid).removeAttr("onclick");
                        $(".benefit_vo_"+aid).find("i").html("已投票"); 
                        $(".benefit_vo_"+aid).find("i").removeClass("be_btn_2"); 
                        $(".benefit_vo_"+aid).find("i").addClass("be_btn_2_off"); 
                        if(weibo==1){
                            benefit_to_weibo(cid,aid,type,img);
                        }
                        id_dialog( msg ,obj,1,false,false);
                    }
        }
    })
}

//benefit 用户登录框
function benefit_login(obj){
	var content=$("#benefit_login").html();
	dialog_benefit=art.dialog({
		follow:obj,
		content:content,
		opacity:0.2,
		lock:true,
		fixed:true,
		padding:"20px 20px",
	})
}

//投票后转发到微博
function benefit_to_weibo(cid,aid,type,img,obj,id){
    $.ajax({
        url:"/club/vote_to_weibo.html",
        type:"post",
        dataType:"json",
        async:false,
        data:{cate_id:cid,aid:aid,type:type,img:img},
        success:function(ret){
        	if(id==1){
        		if(parseInt(ret.status)==1){
        			id_dialog("您的投票结果已经转发到微博，感谢您的参与！",obj,1,function(){
        				location.reload();
        			});
        		}else{
        			if(parseInt(ret.data)==100){
        				var returnurl=$("#returnurl").val();
                    	var url="/user/sina_lock.html?returnurl="+returnurl;
        				var msg="<p>您还没有绑定微博，请先绑定再操作</p><p><a href='"+url+"' class='A_line3'>绑定微博</a></p>";
        				id_dialog(msg,obj,2);
        			}else{
        				id_dialog("转发失败，请稍后重试",obj,1);
        			}
        		}
        	}
        }
    })
}
var benefit_dialog;
function show_benefit_join(b_key){
	if(!USER_ID){
		benefit_login();return false;
	}else{
		benefit_dialog = art.dialog({ fixed:true,opacity:0.3,lock:true});
		$.ajax({
		    url: "/public/dialog/id/benefit/",
		    type:"post",
		    data:{b_key:b_key},
		    success: function (data) {
		    	benefit_dialog.content(data);// 填充对话框内容
		    }
		});
	}
}

//“报名参加”
function to_join_benefit(obj){
    if(!USER_ID){
    	benefit_login();
        return false;
    }
    var key=$("#b_key").val();
    var img1=$("#imgpathDialog1").val();
    var img2=$("#imgpathDialog2").val();
    if(!img1 || !img2){
        id_dialog("请您先上传图片",obj,2,false,3);return false;
    }
    $.ajax({
        url:"/club/join_benefit.html",
        type:"post",        
        dataType:"json",
        data:{key:key,img1:img1,img2:img2},
        success:function(ret){
            if(parseInt(ret.status)==0){
                if(parseInt(ret.data)==100){
                   var msg="您已提交作品，并审核通过，无法进行修改，2014年2月24日开始投票赢终极大奖，记得为自己拉票哦！";
                }else if(parseInt(ret.data)==200){
                     var msg="<p>您在“挑战丰盈靓女”活动中已上传作品，并审核通过，不可以重新提交。</p><p>2014年2月24日开始投票赢终极大奖，记得为自己拉票哦！";
                }else{
                    var msg=ret.info;
                }
                id_dialog(msg,obj,2);
            }else{
            	$("#b_to_join").html("重新上传");
                id_dialog("<p>您已成功提交作品，待小编审核通过，就可以参加2014年2月24日开始的终极投票啦！</p><p>请时刻关注“玩美任务”最新动态哦~</p>",obj,1,function(){
                	location.reload();
                });
            }
        }
    })
}

//提交试用报告
function try_report(){
    if(!USER_ID){
    	benefit_login();return false;
    }
    var resourcetype=123;//待定++++++++++++++++++++++++++++++
    var resourceid=456;//待定++++++++++++++++++++++++++++++
    $.ajax({
        url:"<{:u('home/join_benefit')}>",
        type:"post",        
        dataType:"json",
        data:{share_info:share_info,sharetype:2,resourceid:resourceid, resourcetype: resourcetype},
        success:function(ret){
            if(parseInt(ret.status)<=0){
                id_dialog(ret.info,obj,2,false,3);
            }else{
                id_dialog("提交成功",obj,1,false,3);
            }
        }
    })
}