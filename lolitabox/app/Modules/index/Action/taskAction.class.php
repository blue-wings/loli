<?php

/**
 * 任务控制器
 * @author lit
 *
 */
class taskAction extends commonAction{

	
	/**
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++
	 *       在这个控制器不要加构造方法
	 * +++++++++++++++++++++++++++++++++++++++++++++++++++++
	 */
	
	
	/**
	 * 未完成任务入口
	 * @author lit
	 */
	public function index(){
		$userid=$this->userid;
		$task_mod = D("Task");
		$taskid = trim($_REQUEST['id']);
		$pagesize = 5;
		if($this->isAjax()){
			$return['userinfo']=$this->userinfo;
			$taskinfo = $task_mod ->getTaskInfo($taskid,$userid,$this->getlimit($pagesize));
			if($taskid==10){
				$code =encodeNum($userid);
				$return['inviteurl'] = "http://" . $_SERVER ["SERVER_NAME"] . U ( "user/reglogin", array (
						"s" => $code
				) );
			}
			$this->assign("return",$return);
			$this->assign("info",$taskinfo);
			if(is_array($taskinfo) && $taskinfo['list']){
				$tpl="task_".$taskid."_ajaxlist";
				$param = array(
						"total" =>$taskinfo['count'],
						'result'=>$taskinfo['list'],			//分页用的数组或sql
						'listvar'=>'list',			//分页循环变量
						'listRows'=>$pagesize,			//每页记录数
						'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
						'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
						'template'=>"task:$tpl",//ajax更新模板
						"task" =>1,
				);
				$this->page($param);
			}
			echo $this->fetch("task:task_".$taskid);
			die;
		}
		$unfinished = $task_mod ->unfinishedList($userid);
		$return['userinfo']=$this->userinfo;
		$return['type']=$taskid ? $taskid : $unfinished[0]['id'];
		if($this->array_val_exists($return['type'],"id",$unfinished)){
			$return['if_right']=1;
		}
		$return['tasklist']=$unfinished;
		$return['title']=$return['userinfo']['nickname']."的未完成任务-".C("SITE_NAME");
		cookie("blognum",$return['userinfo']['blog_num']);
		$this->assign("return",$return);
		$this->display("index");
	}
	
	
	/**
	 * 巳完成任务入口
	 */
	public function finished(){
		$userid=$this->userid;
		$task_mod = D("Task");
		$taskid = trim($_REQUEST['id']);
		if($this->isAjax()){
			$taskinfo = $task_mod ->getFinishedTaskInfo($taskid,$userid,$this->getlimit());
			$taskinfo['userinfo']=$this->userinfo;
			$this->assign("info",$taskinfo);
			if($taskinfo['list']){
				$tpl="finished_".$taskid."_ajaxlist";
				$param = array(
						"total" =>$taskinfo['count'],
						'result'=>$taskinfo['list'],			//分页用的数组或sql
						'listvar'=>'list',			//分页循环变量
						'listRows'=>10,			//每页记录数
						'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
						'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
						'template'=>"task:$tpl",//ajax更新模板
						"task" =>1,
				);
				$this->page($param);
			}
			echo $this->fetch("task:finished_".$taskid);
			die;
		}	
		$finished = $task_mod ->finishedList($this->userid);
		$return['taskid']=$taskid ?  trim($_REQUEST['id']):$finished[0]['taskid'];
		$return['userinfo']=$this->userinfo;
		$return['title']=$return['userinfo']['nickname']."的已完成任务-".C("SITE_NAME");
		$return['tasklist']=$finished;
		$this->assign("return",$return);
		$this->display("finished");
	}
	
	
	/**
	 * 我的任务：动态获取用户的问题列表
	 * @author penglele
	 */
	public function get_user_question_list(){
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		//查看用户是否已经完成过该任务
		$info=M("TaskStat")->where("userid=$userid AND status=1 AND taskid=1")->find();
		if($info){
			$this->ajaxReturn(0,"您已完成该任务，请勿重复操作",0);
		}
		//获取问题列表
		$qlist=D("Task")->questionList(5);
		if($qlist==false){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$this->ajaxReturn(1,$qlist,1);
	}
	
	
	/**
	 * 检测用户【玩转萝莉盒】的答案是否正确
	 * @author penglele
	 */
	public function check_user_answer(){
		$userid=$this->userid;
		$id=$_POST['id'];//题号
		$result=$_POST['result'];//答案
		$tn=$_POST['tn'];//用户一共答对了多少题
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(!$id || !$result){
			$this->ajaxReturn(0,"非法操作",0);
		}
		//查看用户是否已经完成过该任务
		$info=M("TaskStat")->where("userid=$userid AND status=1 AND taskid=1")->find();
		if($info){
			$this->ajaxReturn(0,"非法操作",0);
		}
		//真正的答案
		$answer=D("Task")->answerList($id);
		if($answer==false){
			$this->ajaxReturn(0,"非法操作",0);
		}
		if($answer!=$result){
			$this->ajaxReturn(100,"fail",0);
		}
		$tn++;
		if($tn==5){
			$data['userid']=$userid;
			$data['taskid']=1;
			$data['status']=1;
			$if_add=M("TaskStat")->add($data);
			if($if_add!==false){
				D("UserCreditStat")->optCreditSet($userid,"user_lolitabox");
			}
		}
		$this->ajaxReturn($tn,"success",1);
	}
	
	
	/**
	 * 晒盒领取积分
	 * @author litingting
	 */
	public function show_box_award(){
		$orderid = $_POST['orderid'];
		if(empty($orderid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		$userid = $this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$task_order = M("TaskOrderStat");
		$share = M("UserShare");
		if($task_order ->where("userid=".$userid." AND orderid=".$orderid." AND status=1 AMD ifavalid=1")->find()){
			$this->ajaxReturn(0,"巳经加过积分，不能重复领取",0);  //如果巳经加过积分，则跳过
		}
		
		$list = $task_order->where("userid=".$userid." AND orderid=".$orderid." AND status=2")->select();
		if(empty($list)){
			$this->ajaxReturn(0,"分享还未被收录",0);
		}
		
		$task = D("Task");
		foreach($list as $key =>$val){
			$shareinfo = $share->where("id=".$val['shareid']." AND status>0")->find();
			if($shareinfo['outnum']>= 6){
				$credit_stat = D("UserCreditStat");
				$task_order->where($val)->setField("status", 1);
				$credit_stat->optCreditSet($val['userid'],"user_show_box");   		//晒盒加积分
				$task->addUserTask($val['userid'],8);
				//如果status=1,则将此订单发的所有晒单置为0
				$task_order ->where("orderid=".$val['orderid']." AND status>1")->setField("status", 0);
				$this->ajaxReturn(0,"恭喜你，成功领取80积分",1);
				break;
			}
		}
		$this->ajaxReturn(0,"转发达到六次才能领取积分哦",0);
		
	}
	
	
	/**
	 * 指定产品发分享
	 * @author litingting
	 */
	public function pid_share_award(){
		$childid = $_POST['id'];
		$userid = $this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(empty($childid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		$time = time();
		$where['from'] = array("lt",$time);
		$where['to'] = array("gt",$time); 
		$where['status']=1;
		$taskinfo = M("TaskChild")->where($where)->getById($childid);
		if(empty($taskinfo)){
			$this->ajaxReturn(0,"任务不存在",0);
		}
		$task_stat = M("TaskStat");
		$share = M("UserShare");
		$credit_stat = D("UserCreditStat");
		
		$where = array();
		$where['addtime']=array("exp","BETWEEN ".$taskinfo['from']." AND ".$taskinfo['to']);
		$where['taskid'] = 9;
		$where['status'] = 2;
		$where['userid'] = $userid;
		$where['childid'] = $childid;  
		$list = $task_stat->where($where)->select();
		$num = 0;
		foreach ($list as $key =>$val){
				$shareinfo = $share->where("id=".$val['relationid']." AND status>0")->find();
			if($shareinfo['outnum']>= 6){
				$task_stat->where($val)->setField("status", 1);
				$credit_stat->optCreditSet($val['userid'],"assign_post_share");   		//晒盒加积分
			    $num++;
			}
		}
		if($num > 0){
			$this->ajaxReturn(0,"您有{$num}个分享巳完成。恭喜您获取".($num*80)."积分",1);
		}else{
			$this->ajaxReturn(0,"转发达到六次才能领取积分哦",0);
		}
	}
	
	/**
	 * 判断某个值是否存在于数组中
	 * @param $val 需要查询的值
	 * @param string $key 该值在数组中的字段
	 * @param array $array 要查询的数组
	 */
	public function array_val_exists($val,$key="",$array){
		if(empty($array)){
			return false;
		}
		if(!$key){
			return in_array($val,$array);
		}
		$new_arr=array();
		foreach($array as $ikey=>$ival){
			$new_arr[]=$ival[$key];
		}
		return in_array($val,$new_arr);
	}
	
	/**
	 * 解除绑定中间跳转页
	 * @author penglele
	 */
	public function jumpto_unbound(){
		$type=$_GET['type'];
		if(!$type){
			exit;
		}
		if($type=="sina"){
			$url=urlencode("http://".$_SERVER["SERVER_NAME"].U("task/finished",array('id'=>5)));
			$j_url=U("user/sina_unlock")."?returnurl=".$url;
			header("location:".$j_url);exit;
		}elseif($type=="qq"){
			$url=urlencode("http://".$_SERVER["SERVER_NAME"].U("task/finished",array('id'=>6)));
			$j_url=U("user/qq_unlock")."?returnurl=".$url;
			header("location:".$j_url);exit;			
		}
	}
	
	
}
?>