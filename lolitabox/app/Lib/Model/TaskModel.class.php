<?php

/**
 * 任务模型
 * @author lit
 *
 */
class TaskModel extends Model {
	
	
	/**
	 * 获取未完成任务列表
	 * @param unknown_type $userid
	 */
	public function unfinishedList($userid){
		$list = $this->where("status > 0")->order("sort DESC,id")->select();
		if(empty($userid)){
			return $list;
		}
		$unfinished = array();
		$task_stat = M("TaskStat");
		$user_mod = D("Users");
		if($list){
			$userinfo = $user_mod->getUserInfo($userid);
		}
		//dump($userinfo);exit;
		$user_credit_mod = D("UserCreditStat");
		foreach($list as $key =>$val){
			if($val['status']==1){    // 代表一次性任务
				if($task_stat ->where("userid=$userid AND taskid=".$val['id']." AND status=1")->select()){
					continue;
				}

				switch($val['id']){
					case 2:
				        $flag = $userinfo['state'] == 2 ? 2:1;
						break;
					case 3:
						$flag = $user_credit_mod->getUserTaskStat($userid,"mobile") ? 2:1;
						break;
					case 4:
						$flag = $user_credit_mod->where("userid=".$userid." AND action_id='user_uploadface'")->find() ? 2:1;
						break;
					case 5:
						$flag = $user_credit_mod->getUserTaskStat($userid,"sina") ? 2:1;
						break;
					case 6:
						$flag = $user_credit_mod->getUserTaskStat($userid,"qq") ? 2:1;
						break;
					default:
						$flag = 1;
				}
				if($flag==1){
					$unfinished[]=$val;
				}else if($flag==2){
					$task_stat->add(array("userid"=>$userid,"taskid"=>$val['id'],"status"=>1));
					continue;
				}
				
			}else if($val['status']==3){    //3代表动态任务
			    if($flag=$this->hasChild($val['id'],$userid)){
			    	$val['name'] = $val['name']." <span class='S_txt1'>({$flag})</span>";
			    	$unfinished[]=$val;
			    }else{
			    	continue;
			    }
				
				
			}else if($val['status']==4){
				$flag = $this->ifFinishedTodayTask($userid,$val['id']);
				if(empty($flag)){
					$unfinished[]=$val;
				}
				
			}else{                           //2为长期任务
				if($val['id']==8){
					$flag = $this->getDryBoxCount($userid);
					if($flag){
						$val['name'] = $val['name']." <span class='S_txt1'>({$flag})</span>";
					}
				}
				$unfinished[]=$val;
			}
		} 
		return $unfinished;
	}
	
	
	/**
	 * 判断是否完成每日任务
	 * @param int $userid
	 * @param int $taskid
	 */
	public function ifFinishedTodayTask($userid,$taskid){
		$start =strtotime(date("Y-m-d"));
		$to = strtotime("+1 day",$start);
		$flag= M("TaskStat")->where("userid=".$userid." AND taskid=".$taskid." AND addtime >=".$start." AND addtime <".$to)->count();
	//	echo M("TaskStat")->getLastSql();die;
		return $flag;
	}
	
	
	
	/**
	 * 获取巳完成任务列表
	 * @param int $userid
	 * @author lit
	 */
	public function finishedList($userid){
		$day_task = array(11,8);
		$where['userid'] = $userid;
		$where['status'] = 1;
		//排除每日任务
		$where['taskid'] = array("not IN",$day_task);
		$list=M("taskStat")->distinct('taskid')->where($where)->field("taskid")->select();
		
		$count = count($list);          //
		foreach($day_task as $k =>$v){
			if($this->ifFinishedTodayTask($userid,$v)){
				$list[$count]['taskid'] = $v;
				$count++;
			}
		}
		 
		//晒盒任务
		if(M("UserShare")->where("userid={$userid} AND status > 0 AND sharetype=1 AND resourcetype=4 AND pick_status=1 AND resourceid >0")->find()){
			$list[$count]['taskid'] = 8;
			$count++;
		}
		
		$array = array(7,8,9);
		foreach($list as $key=>$val){
			$info = $this->where("id=".$val['taskid'])->field("name,style")->find();
			$list[$key]['title']=$info['name'];
			$list[$key]['style']=$info['style'];
			if(in_array($val['taskid'],$array)){
				$list[$key]['count']  = $this->getFinishedCount($userid,$val['taskid']);
			}
		}
		return $list;
	}
	
	/**
	 * 获取子任务完成总数
	 * @param unknown_type $userid
	 * @param unknown_type $taskid
	 * @author lit
	 */
	public function getFinishedCount($userid,$taskid){
		if($taskid == 8){
			return M("TaskShare")->where("userid=".$userid." AND resourcetype=4 AND pick_status=1 AND resourceid >0")->group("resourceid")->count("id");
		}else{
			return M("TaskStat") ->where("userid=".$userid." AND taskid=".$taskid." AND status=1")->count();
		}
	}
	
	
	/**
	 * 获取子任务列表
	 * @param userid
	 * @param taskid
	 * @author litingting
	 */
	public function childList($taskid,$userid=""){
		$task_child = M("TaskChild");
		$task_stat = M("TaskStat");
		$current = time();
		$where['from'] = array("lt",$current);
		$where['to']  =  array("gt",$current);
		$where['taskid'] = $taskid;
		$where['status']  = array("gt",0);
		$list=$task_child->where($where)->select();
		if($userid){
			foreach($list as $key =>$val){
				if($task_stat->where("userid=".$userid." AND taskid=".$val['taskid']." AND childid=".$val['id']." AND status=1")->find()){
			        unset($list[$key]);
				}
			}
		}
		return $list;
	
	}
	
	/**
	 * 判断是否含有未完成子任务
	 * @param unknown_type $taskid
	 * @param unknown_type $userid
	 * @author lit
	 */
	public function hasChild($taskid,$userid){
		$task_child = M("TaskChild");
		$task_stat = M("TaskStat");
		$current = time();
		$where['from'] = array("lt",$current);
		$where['to']  =  array("gt",$current);
		$where['taskid'] = $taskid;
		$where['status']  = array("gt",0);
		$where['_string'] = "NOT exists(SELECT childid FROM task_stat WHERE taskid=task_child.taskid AND childid=task_child.id AND status =1 AND userid=".$userid.")";
		$flag= $task_child->where($where)->count();
		//echo $task_child->getLastSql();die;
		return $flag?$flag:0;
	}
	
	/**
	 * 获取指定产品分离列表
	 * @param int $userid
	 * @param int userid
	 */
	public function pidShareList($userid){
		$list = $this->childList(9,$userid);
		$pro_mod = M("Products");
		foreach($list as $key =>$val){
			$pinfo = $pro_mod ->getByPid($val['relationid']);
			$val['pname']= $pinfo['pname'];
			$val['pimg']= $pinfo['pimg'];
			$val['pname']= $pinfo['pname'];
			$val['status'] = $this->getPidshareStatus($val['id'],$userid);
			if($val['status'] < 4){
				$val['shareid'] = $this ->getNearProShareid($val['id'],$userid,$val['status']);
				if($val['status']==2){
					$val['award']=$this->ifAwardPidShare($userid,$val['id']);
				}
			}
			$list[$key] = $val;
		}
		return $list;
	}
	
	/**
	 * 玩转萝莉盒问题--【获取问题的数组，可随机抽取】
	 * @author penglele
	 */
	public function questionList($num=0){
		$questionlist=array(
				1=>array(
						"title"=>"以下哪种方式不能获得积分哩？",
						"answerlist"=>array("A"=>"上传头像","B"=>"绑定新浪微博","C"=>"填写基本信息址","D"=>"赞他人的分享"),
						// 					"res"=>"D"
				),
				2=>array(
						"title"=>"以下这几种方式，哪一种是获得积分最多滴？",
						"answerlist"=>array("A"=>"验证手机","B"=>"参与调查问卷","C"=>"邀请注册","D"=>"激活邮箱"),
						// 					"res"=>"C"
				),
				3=>array(
						"title"=>"萝莉盒的客服电话号码是多少哇？",
						"answerlist"=>array("A"=>"4006-263-362","B"=>"4006-262-363","C"=>"4006-226-363","D"=>"4006-262-336"),
						// 					"res"=>"A"
				),
				4=>array(
						"title"=>"以下哪一项内容与积分试用不符滴？",
						"answerlist"=>array("A"=>"兑换的产品需等待审核通过后才算成功兑换","B"=>"积分试用有可能会出现正装产品哦","C"=>"需使用积分来兑换产品","D"=>"需支付10元邮费"),
						// 					"res"=>"A"
				),
				5=>array(
						"title"=>"萝莉盒中的小样是否有正品保证涅？",
						"answerlist"=>array("A"=>"萝莉盒所有产品均来自品牌方，100%正品保证","B"=>"萝莉盒有一部分产品来自品牌方，有正品保证"),
						// 					"res"=>"A"
				),
				6=>array(
						"title"=>"对产品发表分享被收录后，可以获得多少积分哩？",
						"answerlist"=>array("A"=>"20积分","B"=>"10积分","C"=>"30积分","D"=>"40积分"),
						// 					"res"=>"B"
				),
				7=>array(
						"title"=>"付邮试用，每次订单需要支付多少邮费？",
						"answerlist"=>array("A"=>"10元邮费，偏远地区除外","B"=>"无需支付邮费","C"=>"15元邮费，偏远地区除外","D"=>"5元邮费，偏远地区除外"),
						// 					"res"=>"A"
				),
				8=>array(
						"title"=>"付邮试用，每笔订单能试用多少个产品？",
						"answerlist"=>array("A"=>"1个","B"=>"10个","C"=>"14个","D"=>"不限"),
						// 					"res"=>"A"
				),
				9=>array(
						"title"=>"萝莉盒中的产品只有护肤品吗？",
						"answerlist"=>array("A"=>"萝莉盒的产品护肤彩妆香氛美体一应俱全啦","B"=>"萝莉盒的产品只有护肤品啦","C"=>"萝莉盒的产品只有彩妆啦"),
						// 					"res"=>"A"
				),
				10=>array(
						"title"=>"以下选项，哪一个是萝莉盒提倡的理念？",
						"answerlist"=>array("A"=>"萝莉盒就是化妆品试用","B"=>"先试后买，理性消费最流行","C"=>"爱分享就能得实惠","D"=>"通过亲身试用，轻松找到最适合自己的产品！"),
						// 					"res"=>"B"
				),
				11=>array(
						"title"=>"萝莉盒的积分有米有使用期限哇？",
						"answerlist"=>array("A"=>"米有，永久有效","B"=>"有，自获得日起一年内有效"),
						// 					"res"=>"A"
				),
				12=>array(
						"title"=>"使用积分兑换产品，每次最多可兑换多少件呢？",
						"answerlist"=>array("A"=>"14件","B"=>"15件","C"=>"10件","D"=>"12件"),
						// 					"res"=>"C"
				),
				13=>array(
						"title"=>"在一次兑换过程中，同一款产品可重复选择兑换吗？",
						"answerlist"=>array("A"=>"不可以啦","B"=>"可以呢"),
						// 					"res"=>"A"
				),
				14=>array(
						"title"=>"如果积分不够，是否可以进行积分试用呢？",
						"answerlist"=>array("A"=>"不可以进行积分试用啦","B"=>"可以通过现金来补差额的方式获得想要兑换的产品"),
						// 					"res"=>"B"
				),
				15=>array(
						"title"=>"使用积分兑换产品时，没支付成功的订单，被扣减的积分会退还吗？",
						"answerlist"=>array("A"=>"会退还哦","B"=>"不会退还"),
						// 					"res"=>"A"
				),
				16=>array(
						"title"=>"使用积分兑换产品时，没支付成功但却被扣减了积分的订单，扣减的积分会在什么时候退还？",
						"answerlist"=>array("A"=>"会在15天后返还到用户的账户中","B"=>"会在10天后返还到用户的账户中","C"=>"会在7天后返还到用户的账户中","D"=>"会在15个工作日后返还到用户的账户中"),
						// 					"res"=>"A"
				),
				17=>array(
						"title"=>"使用积分试用兑换产品，需要支付多少钱邮费呢？",
						"answerlist"=>array("A"=>"10元,偏远地区除外","B"=>"12元","C"=>"15元","D"=>"根据地区不同而不同"),
						// 					"res"=>"A"
				),
				19=>array(
						"title"=>"萝莉盒中的产品只有小样规格的吗？",
						"answerlist"=>array("A"=>"是的，只有小样规格的哦","B"=>"不是啦，偶尔会有超值正装哦"),
						// 					"res"=>"B"
				),
				20=>array(
						"title"=>"验证手机号码以及激活注册邮箱分别可以获得多少积分呀？",
						"answerlist"=>array("A"=>"50和20积分","B"=>"10和20积分","C"=>"20和10积分","D"=>"20和50积分"),
						// 					"res"=>"A"
				),
				21=>array(
						"title"=>"使用积分免费兑换的产品有萝莉盒包装的吗？",
						"answerlist"=>array("A"=>"米有萝莉盒包装滴","B"=>"只有兑换数量为10款时，才能免费获得萝莉盒精美包装"),
						// 					"res"=>"B"
				),
				22=>array(
						"title"=>"你邀请好友注册，好友需完成以下哪一项操作，你才能获得积分奖励呢？",
						"answerlist"=>array("A"=>"好友需验证手机","B"=>"好友需上传头像","C"=>"好友需设置默认收货地址","D"=>"好友需绑定微博"),
						// 					"res"=>"A"
				),
				23=>array(
						"title"=>"邀请好友注册能获得的积分有上限限制吗？",
						"answerlist"=>array("A"=>"没有上限限制，多邀请就多送积分","B"=>"有上限限制，每天最多邀请10位好友注册有奖励","C"=>"有上限限制，每月最多邀请10位好友注册有奖励 "),
						// 					"res"=>"A"
				),
				24=>array(
						"title"=>"在兑换过程中，以下哪一种方式不能选择?",
						"answerlist"=>array("A"=>"全额积分兑换","B"=>"积分加现金兑换","C"=>"全额支付兑换","D"=>"免邮费兑换"),
						// 					"res"=>"D"
				),
				25=>array(
						"title"=>"每一份调查问卷，每人每次最多可参与多少次捏？",
						"answerlist"=>array("A"=>"每份调查问卷每人每次仅能参与1次","B"=>"每份调查问卷每人每次可参与2次","C"=>"每份调查问卷每人每次不限参与次数"),
						// 					"res"=>"A"
				)
		);
		if($num==0){
			return $questionlist;
		}
		//如果随机从中抽取问题
		$q_list=array_rand($questionlist,5);
		if(!$q_list){
			return false;
		}
		foreach($q_list as $key=>$val){
			$info=$questionlist[$val];
			$info['id']=$val;
			$list[$key]=$info;
		}
		return $list;
	}
	
	
	
	
	/**
	 * 获取问题的答案
	 * @param int $id 题号
	 * @author penglele
	 */
	public function answerList($id){
		if(!$id){
			return false;
		}
		$answerlist=array(
				1=>D,
				2=>C,
				3=>A,
				4=>A,
				5=>A,
				6=>B,
				7=>A,
				8=>A,
				9=>A,
				10=>B,
				11=>A,
				12=>C,
				13=>A,
				14=>B,
				15=>A,
				16=>A,
				17=>A,
				19=>B,
				20=>A,
				21=>B,
				22=>A,
				23=>A,
				24=>D,
				25=>A
		);
			if(!array_key_exists($id,$answerlist)){
				return false;
			}
			return $answerlist[$id];
	}
	
	/**
	 * 将用户完成某项任务记录到数据表中
	 */
	public function addUserTask($userid,$taskid,$childid=0,$relationid=0,$state=1){
		if(!$userid || !$taskid){
			return false;
		}
		$data['userid']=$userid;
		$data['taskid']=$taskid;
		$data['childid']=$childid;
		$current = time();
		if($childid){
			$flag=M("TaskChild")->where(array("id"=>$childid,"from"=>array("lt",$current),"to"=>array("egt",$current),"status"=>1))->find();
			if(empty($flag)){
				return false;    //子任务不存在
			}
		} 
		$data['relationid'] = $relationid;
		$tast_stat=M("TaskStat");
		$if_info=$tast_stat->where($data)->find();
		if($if_info){
			if($taskid==11){
				$tast_stat->where($data)->setField("addtime",time());
				//echo $task_stat->getLastSql();
			}
			return true;
		}
		$data['addtime'] = time();
		$data['status']= $state;
		$tast_stat->add($data);
	}
	
	
	/**
	 * 晒盒任务状态记录
	 * @param unknown_type $userid
	 * @param unknown_type $taskid
	 * @param unknown_type $orderid
	 * @param unknown_type $state
	 */
	public function addDryBoxTask($userid,$orderid,$shareid,$state=3){
		if(!M("UserOrder")->where("state=1 AND userid=".$userid." AND ordernmb=".$orderid)->find()){
			return false;
		}
		$data['userid']=$userid;
		$data['orderid']=$orderid;
		$data['shareid']=$shareid;
		$tast_order_stat=M("TaskOrderStat");
		$if_info=$tast_order_stat->where($data)->find();
		if($if_info){
			$tast_order_stat->where($data)->setField("status",$state);
			return;
		}
		$data['addtime'] = time();
		$data['status']= $state;
		return $tast_order_stat->add($data);
	}
	
	
	
	/**
	 * 获取晒盒列表
	 * @param int $userid
	 * @author lit
	 */
	public function getDryBoxList($userid,$limit=""){
		$where['userid']=$userid;
		//$where['paytime'] = array("elt",date("Y-m-d H:i:s",strtotime("-3 days")));
		$where['state'] = 1;
		$where['ifavalid'] = 1;
		$where['_string'] = "NOT exists(select * from user_share where resourceid=user_order.ordernmb AND resourcetype=4 AND resourceid >0 AND  pick_status=1)";
		$not_type=D("Box")->returnBoxType();
		$where['type']=array("exp","not in (".$not_type.")");
	  // $where['task_order_stat.status'] = array("neq",1);
	  //->join("task_order_stat on task_order_stat.orderid=user_order.ordernmb")
		$list = M("UserOrder")->where($where)->limit($limit)->select();
		$box=M("Box");
		foreach($list as $key=>$val){
			$list[$key]['boxname'] = $box->where("boxid=".$val['boxid'])->getField("name");
			$list[$key]['status'] = $this->getDryBoxStatus($val['ordernmb']);
			if($list[$key]['status'] <4){
				$list[$key]['shareid'] = $this ->getNearDryBoxShareid($val['ordernmb'],$val['userid'],$list[$key]['status']);
			}
		}
		return $list;
	}
	
	
	/**
	 * 判断晒单是否达到领取积分条件 [提醒用户领取积分]
	 * @param int $shareid
	 */
    public function ifAwardShowBox($orderid){
    	$task_order  = M("TaskOrderStat");
    	$share = M("UserShare");
    	$user_atme = M("UserAtme");
    	$list = M("TaskOrderStat")->where("orderid=".$orderid." AND status=2")->select();
    	foreach($list as $key =>$val){
    		$shareinfo = $share->where("status>0 AND id=".$val['shareid'])->find();
    		if($shareinfo['outnum'] >= 6){
    				return 1;   
    		}
    	}
    	return 0;
    }
    
    
    /**
     * 判断指定产品分享是否达到领取各积分条件
     * @param unknown_type $childid
     */
    public function ifAwardPidShare($userid,$childid){
    	$task_stat = M("Task_stat");
    	$share = M("UserShare");
    	$list = $task_stat ->where("childid=".$childid." AND userid=".$userid." AND status =2")->select();
    	$num = 0;
    	foreach($list as $key =>$val){
    		$shareinfo = $share->where("status>0 AND id=".$val['relationid'])->find();
    		if($shareinfo['outnum'] >= 6){
    			return 1;
    		}
    	}
    	return 0;
    }
    
	 
	/**
	 * 获取未完成晒盒总数
	 * @param int $userid
	 */
	public function getDryBoxCount($userid){
		$where['userid']=$userid;
		$where['state'] = 1;
		$where['ifavalid'] = 1;
		$not_type=D("Box")->returnBoxType();
		$where['type']=array("exp","not in (".$not_type.")");
		$where['_string'] = "NOT exists(select * from user_share where resourceid=user_order.ordernmb AND resourceid >0 AND  resourcetype=4 AND  pick_status=1)";
		$total = M("UserOrder")->where($where)->count();
		return $total;
	}
	
	/**
	 * 获取晒盒状态，
	 * @author lit 
	 * @return int (1----巳完成,2--巳收录，3--巳晒未收录，4--未晒)
	 */
	public function getDryBoxStatus($orderid){
		$user_share = M("UserShare");
		$info = $user_share ->where(array("resourceid"=>$orderid,"resourcetype"=>4,"status"=>array("gt",0)))->find();
		return $info ? 3:4;
	}
	
	
	/**
	 * 获取晒盒最接近目标的分享
	 * @param int $orderid
	 * @param int $userid
	 * @param int $status
	 */
	public function getNearDryBoxShareid($orderid,$userid,$status){
	    $user_share = M("UserShare");
		$info = $user_share ->where(array("resourceid"=>$orderid,"resourcetype"=>4,"status"=>array("gt",0),"userid"=>$userid))->find();
		return $info ? $info['id']:0;
	}
	
    /**
     * 指定产品发分享获取最接近目标的分享ID
     * @param unknown_type $childid
     * @param unknown_type $userid
     * @param unknown_type $status
     */
	public function getNearProShareid($childid,$userid,$status){
		$sql = "SELECT s.id FROM user_share s,task_stat t WHERE s.id=t.relationid 	AND s.userid=t.userid AND t.status={$status} AND t.userid={$userid} AND t.childid={$childid} AND s.status > 0 AND taskid=9 ORDER BY s.outnum DESC LIMIT 1";
		$list = $this->query($sql);
		if(empty($list[0])){
			return null;
		}
		return $list[0]['id'];
	}
	
	
	/**
	 * 判断某一个订单是否晒过
	 * @param int $orderid
	 * @return int 1--巳晒，0---未晒
	 */
	public function ifShowBox($orderid){
		return  M("TaskOrderStat")->where("orderid=".$orderid." AND status=1")->count();
		
	}
	
	/**
	 * 获取指定产品发分享状态
	 * @author lit
	 * @return int (1----巳完成,2--巳收录，3--巳晒未收录，4--未晒)
	 */
	public function getPidshareStatus($childid,$userid){
		$task_stat = M("taskStat");
		$info=$task_stat->where("status>0 AND childid=".$childid." AND userid=".$userid)->order("status ASC")->find();
		return $info['status']?$info['status']:4;
	}
	
	/**
	 * 获取任务详情
	 * @param int $taskid
	 * @author lit
	 */
	public function getTaskInfo($taskid,$userid,$limit=""){
		
		switch($taskid){
			
			case 1:    //玩转萝莉盒
				break;
			case 3:
				$tel_info=M("UserTelphone")->where("userid=$userid AND if_check=0")->order("addtime DESC")->find();
				if($tel_info){
					$info['tel']=$tel_info['tel'];
					if(time()-$tel_info['addtime']<=10*60 && time()-$tel_info['addtime']>0){
						$etime=$tel_info['addtime']+10*60;
						$date=date("Y-m-d-H-i-s",$etime);
						$date_arr=explode("-",$date);
						$info['year']=$date_arr[0];
						$info['mon']=$date_arr[1];
						$info['day']=$date_arr[2];
						$info['hour']=$date_arr[3];
						$info['min']=$date_arr[4];
						$info['sec']=$date_arr[5];
						$info['if_date']=1;
					}
				}
				$info["credit"]=D("UserCreditSet")->getCreditValById("user_verify_mobile","score");
				break;
			case 5:	   //绑定新浪微博
				$info["returnurl"]=$this->getBindReturnUrl();
				$info["credit"]=D("UserCreditSet")->getCreditValById("user_bound_sina_weibo","score");
				break;
			case 6:
				$info["returnurl"]=$this->getBindReturnUrl();
				$info["credit"]=D("UserCreditSet")->getCreditValById("user_bound_qq","score");
				break;
			case 10:    //邀请注册
				$user_mod=D("Users");
				$info['count'] = $user_mod ->getInviteCount($userid);
				//$list = $user_mod ->getInviteList($userid,$this->getlimit(10));
				$info['list'] = $user_mod ->getInviteList($userid,$limit);
				$info["credit"]=D("UserCreditSet")->getCreditValById("user_invite_reg","score");
				break;
			case 7:      //调查问卷列表
				$info=$this->childList($taskid,$userid);
				break;
			case 8:     //晒盒记
				$info['list']=$this->getDryBoxList($userid,$limit);
			    $info['count'] = $this->getDryBoxCount($userid);
			    $not_type=D("Box")->returnBoxType();
				$where['type']=array("exp","not in (".$not_type.")");
			    $info['ordercount'] = M("UserOrder") ->where("userid=".$userid." AND state=1 AND type not in (".$not_type.")")->find() ? 1:0;
				break;
			case 9:    //指定产品发分享
				$info = $this->pidShareList($userid);
				break;
			default:
				$info = null;
		}
		return $info;
	}
	
	/**
	 * 获取巳完成详情
	 * @param unknown_type $taskid
	 * @param unknown_type $userid
	 */
	public function getFinishedTaskInfo($taskid,$userid,$limit){
		$task_stat = M("TaskStat");
		$task_child = M("TaskChild");
		$jump = array(8,1,2,3,4,5,6,9);
		if(in_array($taskid,$jump)==false){
			$finishedlist = $task_stat ->where("taskid=".$taskid." AND userid=".$userid." AND status=1")->limit($limit)->select();
			$count=$task_stat ->where("taskid=".$taskid." AND userid=".$userid." AND status=1")->count();
		}
		$list=array();
		switch($taskid){
			case 3 :
				$finishedlist=M("userTelphone")->where("userid=$userid AND if_check=1")->find();
				break;
			case 5:
				$finishedlist['type']=D("UserOpenid")->checkOpenLockByType($userid,"sina");
				$finishedlist['returnurl']=urlencode(PROJECT_URL_ROOT.U('task/finished'));
				$finishedlist['if_unbound']=$this->getIfUnboundWeibo("sina",$userid);
				break;
			case 6:
				$finishedlist['type']=D("UserOpenid")->checkOpenLockByType($userid,"qq");
				$finishedlist['returnurl']=urlencode(PROJECT_URL_ROOT.U('task/finished'));
				$finishedlist['if_unbound']=$this->getIfUnboundWeibo("qq",$userid);
				break;				
			case 10:  //邀请注册
				$user_mod = M("Users");
				foreach($finishedlist as $key =>$val){
					$u_info = $user_mod ->field("userid,nickname,addtime")->getByUserid($val['relationid']);
					$finishedlist[$key] = $u_info;
				}
				$list['list']=$finishedlist;
				$list['count']=$count;
				break;
			case 7:      //调查问卷列表
				foreach($finishedlist as $key =>$val){
					$finishedlist[$key]['relationid'] = $task_child->where("id=".$val['childid'])->getField("relationid");
					$child_info =  $task_child ->where("id=".$val['childid'])->find();
 					$finishedlist[$key]['title'] = $child_info['title'];
 					$finishedlist[$key]['credit'] = $child_info['credit'];
				}
				$list['list'] = $finishedlist;
				$list['count'] =  $count;
				break;
			case 8:     //晒盒记
				$task_order_stat = M("TaskOrderStat");
				$box = M("Box");
				$user_order = M("UserOrder");
				$finishedlist = $task_order_stat ->where("userid=".$userid." AND status=1")->limit($limit)->select();
				$finishedlist = M("UserShare")->field("resourceid as orderid,max(id) as shareid")->where(array("userid"=>$userid,"resourcetype"=>4,"pick_status"=>1,"resourceid" =>array("gt",0)))->group("resourceid")->order("id desc")->limit($limit)->select();
				foreach($finishedlist as $key =>$val){
					$boxid = $user_order ->where("ordernmb=".$val['orderid'])->getField("boxid");
					if($boxid){
						$finishedlist[$key]['boxname'] = $box ->where("boxid=".$boxid)->getField("name");
					}
				    	
				}
				$list['list'] = $finishedlist;
				$list['count'] =  M("UserShare")->where(array("userid"=>$userid,"resourcetype"=>4,"pick_status"=>1))->group("resourceid")->select("id");
				break;
			case 9:    //指定产品发分享
				$products = M("Products");
				$finishedlist = $task_stat ->where("taskid=".$taskid." AND userid=".$userid." AND status=1")->group("childid")->limit($limit)->select();
				$count=$task_stat ->where("taskid=".$taskid." AND userid=".$userid." AND status=1")->group("childid")->count();
				foreach($finishedlist as $key =>$val){
					$pid = $task_child->where("id=".$val['childid'])->getField("relationid");
					$pinfo = $products ->field("pid,pname,pimg")->getByPid($pid);
					$val['pname'] = $pinfo['pname'];
					$val['pid'] = $pinfo['pid'];
					$val['pimg'] = $pinfo['pimg'];
					$finishedlist[$key]= $val;
				}
				$list['list'] = $finishedlist;
				$list['count'] = $count;
				break;
			default:
			   break;
		}
		$list = !empty($list) ? $list : $finishedlist; 
		return $list;
	}
	
	/**
	 * 任务中新浪微博跳转地址
	 */
	public function getBindReturnUrl(){
		$url=urlencode(PROJECT_URL_ROOT.U('task/index'));
		return $url;
	}
	
	/**
	 * 判断用户是否解绑过新浪/腾讯 微博
	 * @param $type 查询的类型【'sina'/'qq'】
	 * @param $userid 用户id
	 * @return int 【返回值为1说明用户解绑过】
	 * @author penglele
	 */
	public function getIfUnboundWeibo($type,$userid){
    	if(($type!='sina' && $type!='qq') || !$userid){
			return 0;
		}
		$action_id= $type=="sina" ? "user_unbound_sina_weibo" : "user_unbound_qq";
		$credit_info=M("userCreditStat")->where("userid=$userid AND action_id='".$action_id."'")->find();
		if($credit_info){
			return 1;
		}else{
			return 0;
		}
	}
	
	
	/**
	 * 判断产品是否为任务
	 * @param int $pid
	 * @author lit
	 */
	public function ifTaskByPid($pid){
		$where = array();
		$current = time();
		$where ['from'] = array("lt",$current);
		$where ['to'] = array("egt",$current);
		$where ['relationid'] = $pid;
		$where ['status'] = 1;
		$where ['taskid'] = 9;
		return M("taskChild")->where($where)->getField("id");
	}
	
	/**
	 * 判断是否为一个晒盒任务
	 * @param int $orderid
	 * @author lit
	 */
	public function ifTaskByOrderid($orderid){
		if($info=M("TaskOrderStat")->where("orderid=".$orderid." AND status=1")->find()){
			return $info['shareid'];
		}else{
			return 1;
		}
	}
	
	
	/**
	 * 判断调查问卷在当前是否为一个任务
	 * @author lit
	 */
	public function ifCurrentSurveyTask($sid){
		$current = time();
		$where['from'] = array("lt",$current);
		$where['to']  = array("egt",$current);
		$where['status'] = 1;
		$where['relationid'] = $sid;
		$where['taskid'] = 7;
		return M("TaskChild")->where($where)->find();
	}
	
	
	/**
	 * 获取未完成任务的总数
	 * @author penglele
	 */
	public function getUnfinishedNum($userid){
		$num=0;
		if($userid){
			$list=$this->unfinishedList($userid);
			$num=count($list);
		}
		return $num;
	}
	
	/**
	 * 获取已完成任务的总数
	 * @author penglele
	 */
	public function getFinishedNum($userid){
		$num=0;
		if($userid){
			$list=$this->finishedList($userid);
			$num=count($list);
		}
		return $num;
	}	
	
	/**
	 * 通过产品ID判断产品是否在指定任务中
	 * @author penglele
	 */
	public function inTaskByProductID($pid){
 		if(!$pid){
			return false;
		} 
		$ntime=time();
		$where['taskid']=9;
		$where['from']=array("exp","<=$ntime");
		$where['to']=array("exp",">=$ntime");
		$where['status']=1;
		//新品分享的任务列表
		$tasklist=M("TaskChild")->where($where)->select();
		if(!$tasklist){
			return false;
		}
		$list=array();
		$keys="";
		foreach($tasklist as $key=>$val){
			$keys=$val['relationid'];
			$list[$keys]=$val['credit'];
		}
		if(!array_key_exists($pid, $list)){
			return false;
		}
		return true;
	}
	
	/**
	 * 获取当前正在活动中的-新品分享列表
	 * @author penglele
	 */
	public function getShareProductsListOfTask($field="*"){
		$task_child = M("TaskChild");
		$current = time();
		$where["taskid"]=9; //add by zhenghong at 2013/11/5 
		$where['from'] = array("lt",$current);
		$where['to']  =  array("gt",$current);
		$where['status']  = array("gt",0);
		$list=$task_child->field($field)->where($where)->select();
		if(!$list){
			return '';
		}
		return $list;
	}
}
?>