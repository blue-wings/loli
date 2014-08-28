<?php
/**
 * 消息控制嚣 【THINKPHP 3.1.3】echo THINK_VERSION;
 *
 * @author gaodongchen@gmail.com
 * @version 
 */
class msgAction extends commonAction {

	public function msg(){
		$userid=$this->userid;
		$type= $_REQUEST['type'] ? $_REQUEST['type']:1;
		if($type>3){
			$type=1;
		}
		$msg_mod=D("Msg");
		$msg_num=null;
		$msg_list=null;
		if($type==1){
			$msg_num=$msg_mod->getReceverMsgCount($userid);
			$msg_list=$msg_mod->getReceverMsgListByUserid($userid,$this->getlimit(15));
		}elseif($type==2){
			$msg_num=$msg_mod->getPostMsgCount($userid);
			$msg_list=$msg_mod->getPostMsgListByUserid($userid,$this->getlimit(15));
		}elseif($type==3){
			$msg_list=$msg_mod->getMsgListByLolitabox($userid,$this->getlimit(15));
			$msg_num=$msg_mod->getMsgCountByLolitabox($userid);
		}
		$this->assign("count",$msg_num);
		$param = array(
		"total" =>$msg_num,
		'result'=>$msg_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>15,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'msg:msg_ajaxlist',//ajax更新模板
		'parameter' =>"type=".$type,
		);
		$this->page($param, false);
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的私信-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 个人中心--私信详情
	 */
	public function mymsg_detail(){
		$userid=$this->userid;
		$id=$_GET['id'];
		if(empty($id)) $this->error("非法操作","/");
		//D("UserData")->updataUserData($userid,"newmsg_num");
		$to_userinfo=D("Users")->getUserInfo($id,"nickname");
		$msg_detail_num=D("Msg")->getMsgDialogueCount($userid,$id);
		$msg_detail_list=D("Msg")->getMsgDialogue($userid,$id,$this->getlimit());
		D("Msg")->where("id=".$id)->save(array('to_status'=>2));
		foreach($msg_detail_list as $key=>$val){
			if($val['from_uid']==$userid){
				$msg_detail_list[$key]['spaceurl']=getSpaceUrl($val['to_uid']);
			}else{
				$msg_detail_list[$key]['spaceurl']=getSpaceUrl($val['from_uid']);
			}
		}
		$param = array(
		"total" =>$msg_detail_num,
		'result'=>$msg_detail_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:mymsg_detail_ajaxlist',//ajax更新模板
		);
		//$return=$this->getInterestUserList();
		$return['to_userinfo']=$to_userinfo;
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的私信详情-".C("SITE_NAME");
		$return['msg_num']=$msg_detail_num;
		$this->assign("return",$return);
		$this->page($param);
		$this->display();
	}

	/**
	 * 个人中心--删除私信详情中的某一条信息ajax
	 */
	public function delete_msg_dialog(){
		$userid=$this->userid;
		$id=$_POST['id'];
		if(empty($userid) || empty($id) || !$this->isAjax())
		$this->ajaxreturn(0,"非法操作",0);
		$res=D("Msg")->deletDialogueMsg($userid,$id);
		if($res==false)
		$this->ajaxreturn(0,"操作失败",0);
		else
		$this->ajaxreturn(1,"success",1);
	}
	
	/**
	 * 发私信
	 */
	public function write_user_msg(){
		$userid=$this->userid;
		$to_nick=trim($_POST['to_nick']);
		if(empty($userid) || empty($to_nick)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$userinfo=D("Users")->getUserInfoByData(array("nickname"=>"$to_nick"),'userid');
		if($userinfo==false){
			$this->ajaxReturn(0,"接收对象不存在",0);
		}
		$to_userid=$userinfo[0]['userid'];
		$msg_content=trim($_POST['msg_content']);
		$msg_content=htmlspecialchars($msg_content);
		if(empty($msg_content)){
			$this->ajaxReturn(0,"私信内容不能为空",0);
		}
		$res=D("Msg")->addMsg($userid,$to_userid,$msg_content);
		if($res==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			$this->ajaxReturn(1,"success",1);
		}
	}
	
	
}
