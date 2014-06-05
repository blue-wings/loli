<?php
/*
admin后台用户积分经验值管理
*/
class UserCreditAction extends CommonAction{
	/**
      +----------------------------------------------------------
     * 用户积分经验值查询   user_credit_set
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     */	
	function  creditSet(){
		
		$set=M("userCreditSet");
		import ( "@.ORG.Page" ); // 导入分页类库
		
		if($this->_get('status') == 2){
		}else if($this->_get('status') == 0){
			$where['status']=0;
		}else{
			$where['status']=1;
		}
		
		if($this->_post("action_id")){
			$where['action_id']=$this->_post("action_id");
		}
		$count=$set->where($where)->count();
		$p = new Page($count,30);
		$exlist=$set->where($where)->limit($p->firstRow.','.$p->listRows)->select();
		$setlist=$set->field('action_id,action_name')->select();
		$page = $p->show();
		$this->assign("setlist",$setlist);
		$this->assign("page",$page);
		$this->assign("list",$exlist);
		$this->display();
	}

	/**
      +----------------------------------------------------------
     * 配置用户积分经验值   user_credit_set user_credit_stat
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     */	
	function creditStat(){

		$stat=M("userCreditStat");
		$Set=M("userCreditSet");
		$users=M("Users");
		import ( "@.ORG.Page" ); // 导入分页类库

		$retutn=$this->creditStatWhere(array_map('filterVar',$_GET));
		$where=$retutn['where'];
		$user_info=$retutn['info'];

		$count=$stat->where($where)->count();
		$p = new Page($count,15);

		$user_list = $stat->where($where)->order('add_datetime DESC')->limit($p->firstRow.','.$p->listRows)->select();

		foreach ($user_list as $key=>$value){
			$user_list[$key]['nickname']=$users->where(array('userid'=>$user_list[$key]['userid']))->getField('nickname');
			$user_list[$key]['action_name']=$Set->where(array('action_id'=>$user_list[$key]['action_id']))->getField('action_name');
			if($extend_info=M("UserCreditStatExtend")->getById($value['id']))
			{
				$user_list[$key]['remark']=$extend_info['remark'];
			}
		}
		$setlist=$Set->field('action_id,action_name')->select();
		$page = $p->show();
		$this->assign('setlist',$setlist);
		$this->assign('list',$user_list);
		$this->assign('user_info',$user_info);
		$this->assign('page',$page);
		$this->display();
	}


	/**
      +----------------------------------------------------------
     * 配置用户积分经验值查询条件    
     * 如果有userid和nickname则返回积分经验值
      +----------------------------------------------------------
     * @access private
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     *update by zhaoxiang 2013.1.31
     */	
	private function creditStatWhere($arguments){

		$users=M("Users");
		$stat=M("userCreditStat");
		$returnArray=array();

		if($arguments['userid']){
			$where['userid']=$arguments['userid'];
		}

		if($arguments['nickname']){
			$where['userid']=$users->where(array('nickname'=>$arguments['nickname']))->getField('userid');
		}

		if($arguments['action_id']){
			$where['action_id']=$arguments['action_id'];
		}

		if($arguments['credit_type']){
			$where['credit_type']=$arguments['credit_type'];
		}

		if($arguments['starttime'] && $arguments['endtime']){
			$where['add_datetime']=array(array('egt',$arguments['starttime'].' 00:00:00'),array('elt',$arguments['endtime'].' 23:59:59'),'AND');
		}else if($arguments['starttime']){
			$where['add_datetime']=array('egt',$arguments['starttime'].' 00:00:00');
		}else if($arguments['endtime']){
			$where['add_datetime']=array('elt',$arguments['endtime'].' 23:59:59');
		}

		//如果有userid,nickname就直接统计他的积分和经验值
		if($where['userid']){
			$user_info=$users->where(array('userid'=>filterVar($where['userid'])))->field('score,experience')->find();
			if($where['add_datetime']){
				$user_info['cscore']=$stat->where(array('userid'=>$where['userid'],'add_datetime'=>$where['add_datetime'],'credit_type'=>1))->SUM('credit_value');
				$user_info['cexperience']=$stat->where(array('userid'=>$where['userid'],'add_datetime'=>$where['add_datetime'],'credit_type'=>2))->SUM('credit_value');
			}
			$user_info['userid']=$where['userid'];
		}

		$returnArray['where']=$where;
		$returnArray['info']=$user_info;
		return $returnArray;
	}

	/**
      +----------------------------------------------------------
     * 修改单个用户积分经验值   user_credit_set
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     */		

	function editUserData(){
		$set=M("userCreditSet");
		$action_id=$set->where(array('action_id'=>$this->_post('action_id')))->find();
		
		if($action_id){

			$data = array(
			'action_name'=>$this->_post('action_name'),
			'score'=>$this->_post('score'),
			'experience'=>$this->_post('experience'),
			'score_daylimit'=>$this->_post('score_daylimit'),
			'experience_daylimit'=>$this->_post('experience_daylimit'),
			'is_unique'=>$this->_post('is_unique'),
			);

			$rel = $set->where(array('action_id'=>$this->_post('action_id')))->save($data);

			if($rel){
				$this->ajaxReturn(1,'更新成功!',1);
			}else{
				$this->ajaxReturn(0,'更新失败!',0);
			}
		}else{
			
			$add = array(
				'action_id'=>$this->_post('action_id'),
				'action_name'=>$this->_post('action_name'),
				'score'=>$this->_post('score'),
				'experience'=>$this->_post('experience'),
				'score_daylimit'=>$this->_post('score_daylimit'),
				'experience_daylimit'=>$this->_post('experience_daylimit'),
				'is_unique'=>$this->_post('is_unique'),
			);
			
			if($set->add($add)){
				$this->ajaxReturn(1,'添加成功!',1);
			}else{
				$this->ajaxReturn(0,'添加失败!!',0);
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 为某个用户增加积分经验值   addScore
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return void
	 +----------------------------------------------------------
	 */
	function addScore(){
		extract($_REQUEST);
		$user_credit_stat_model = D ( "index://UserCreditStat" );
		$user_mod = M("Users");
		
		if(empty($add_userid)){
			echo "缺少参数<br>";
			exit();
		}
		
		$html = "";
		$userlist = explode(",",$add_userid);
		foreach($userlist as $key =>$val){
			if(empty($val)){
				continue;
			}
			$flag = 0;
			if(! $user_mod->getByUserid($val))
			{
				$flag= -1;
				
			}else{
				if($add_action_id!='system'){
					$arr=$user_credit_stat_model->optCreditSet($val,$add_action_id);
					$flag = 1;
				}
				else
				{
					if($jifenzhi)
					{
						$this->addStat($val,1,$jifenzhi,$describe);
					}
					if($jingyanzhi)
					{
						$this->addStat($val,2,$jingyanzhi,$describe);
					}
						
					//更改用户积分总值
					$credit_total = M("UserCreditStat")->where('userid='.$val." AND credit_type=1")->sum('credit_value');
					$credit_total = $credit_total ? $credit_total : 0;
					M("Users")->where("userid=".$val)->save(array('score'=>$credit_total));
					//	die(M("Users")->getLastSql());
						
					//更改用户经验总值
					$exp_total = M("UserCreditStat")->where ( 'userid=' . $val . " AND credit_type=2" )->sum ( 'credit_value' );
					$exp_total = $exp_total ? $exp_total : 0;
					M("Users")->where("userid=".$val)->save(array('experience'=>$exp_total));
				
					$flag = 1;
				}
			}

			
			
			if($flag ==1){
				$html.=$val."：加积分操作成功<br>";
			}elseif($flag == -1){
				$html.=$val."：用户ID不存在<br>";
			}else{
				$html.=$val."：加积分操作失败<br>";
			}
		}
		echo $html;

	}

	/**
	 +----------------------------------------------------------
	 * 根据参数在user_credit_stat表插入一条纪录   addStat
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return  boolean
	 +----------------------------------------------------------
	 */
	public function addStat($userid,$credit_type,$credit_value,$describe){
		$data['action_id']='system';
		$data['userid']=$userid;
		$data['credit_type']=$credit_type;
		$data['credit_value']=$credit_value;
		$data['add_datetime']=date("Y-m-d H:i:s");
		if(M("UserCreditStat")->add($data))
		{
			$id=M("UserCreditStat")->getLastInsID();
			if($this->insertCreditExtend($id, $describe))
		     	return true;
			else return false;
		}
		else
		return false;

	}

	/**
	 +----------------------------------------------------------
	 * 根据参数在user_credit_stat_extend表插入一条纪录 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return  boolean
	 +----------------------------------------------------------
	 */
	public function insertCreditExtend($id,$describe)
	{
		$data['id']=$id;
		$data['remark']=$describe;
		$credit_extend_model=M("UserCreditStatExtend");
		if($credit_extend_model->add($data)) return true;
		return false;
	}


}

?>

