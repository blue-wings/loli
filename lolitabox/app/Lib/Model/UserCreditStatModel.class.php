	<?php
/**
 * 用户积分统计模型
 * @author 
 * @version v2.0
 */
class UserCreditStatModel extends Model {

	private $time;
	public function _initialize()
	{
		$this->time = date('Y-m-d H:i:s');
	}

	public function saveUserScore($userid,$content,$score)
	{
		$addtime = $this->time;
		$sql = "INSERT INTO user_score (userid,content,score,addtime) VALUES ({$userid},'{$content}',{$score},'{$addtime}');";
		return $this->db->execute($sql);
	}

	/**
	 * 根据行为编号得到其相关值
	 * 并执行相关积分加减操作
	 * @param int $userid 用户ID
	 * @param var $action_id 用户行为ID
	 * @param int $i_score 指定积分
	 * @param int $i_experience 指定经验值
	 * @return array arr("score"=>xxx,"experience"=>xxx) 本次产生的积分和经验值
	 */
	public function optCreditSet($userid,$action_id,$i_score=0,$i_experience=0)
	{
		$arr=array();
		$rs=$this->db->query("SELECT * FROM user_credit_set WHERE action_id='$action_id'");
		$rs = $rs[0];
		if(empty($rs)){
			return false;
		}else {
			$score=$i_score?$i_score:$rs["score"]; //每次积分
			$score_daylimit=$rs["score_daylimit"]; //每日积分最大值
			$experience=$i_experience?$i_experience:$rs["experience"]; //每次经验值
			$experience_daylimit=$rs["experience_daylimit"]; //每日经验值最大值
			//判断是否为唯一记录
			if($rs["is_unique"]){
				//需要判断统计表中是否已经有记录
				$uni_rs = $this->field('id')->where("userid=$userid AND action_id='".$action_id."'")->find();
				if(empty($uni_rs)){
					//是唯一记录，但数据表中没有记录，则可以插入一条记录		
					if(!empty($score)){
						$this->addStat($userid,$action_id,"1",$score);
						$arr['score']=$score;
					}
					if($experience){
						$this->addStat($userid,$action_id,"2",$experience);
						$arr['experience']=$experience;
					}
				}
				return $arr;
			}

			//判断是否有当日积分限制
			if($score_daylimit!=0){
				if($this->ifAreadyOutRange($userid,$action_id,$score_daylimit,1)){
					$this->addStat($userid,$action_id,"1",$score);
					$arr['score']=$score;
				}
			}else {
				if($score!=0) {
					$this->addStat($userid,$action_id,"1",$score);
					$arr['score']=$score;
				}
			}
			//判断是否有当日经验值限制
			if($experience_daylimit!=0){
				if($this->ifAreadyOutRange($userid,$action_id,$experience_daylimit,2)){
					$this->addStat($userid,$action_id,"2",$experience);
					$arr['experience']=$experience;
				}
			}
			else {
				if($experience!=0) {
					$this->addStat($userid,$action_id,"2",$experience);
					$arr['experience']=$experience;
				}
			}
		}
		return $arr;
	}

	/**
	 * 增加统计记录
	 */
	public function addStat($userid,$action_id,$credit_type,$credit_value)
	{
		$add_datetime = $this->time;
		$sql="INSERT INTO user_credit_stat (userid,action_id,credit_type,credit_value,add_datetime) VALUES ($userid,'$action_id',$credit_type,'$credit_value','$add_datetime')";
		$result=$this->db->execute($sql);
		if($result  && $credit_type==1){
		    //发私信
		    $unpost_action_arr = array("user_share_pick");
		    if(!in_array($action_id,$unpost_action_arr)){
		    	$action_name = M("UserCreditSet")->where("action_id='{$action_id}' AND status=1")->getField("action_name");
		    	if($action_name){
		    		$username = M("Users")->where("userid=".$userid)->getField("nickname");
		    		if($credit_value > 0){
		    			$msg = "{$username}你好，由于{$action_name}, 获得<b>{$credit_value}</b>个积分奖励，感谢您的支持。获取更多积分请到<a href='/task/index.html' class='WB_info'>我的任务</a>！";
		    		}else{
		    			$msg = "{$username}你好，由于{$action_name}, 扣减积分:".abs($credit_value)."。<a href='/task/index.html'   class='WB_info'>我的任务</a>！";
		    		}
		    		
		    		D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$msg);
		    	}
		    }
		    
		    //加入积分动态
		    if($credit_value >0){
		    	$this->addCreditDynamic($userid, $action_id, $credit_value);
		    }
			
		}
		$resultscore = $this->updateUserScore($userid);
		$resultexprience = $this->updateUserExprience($userid);
		if($result && $resultscore && $resultexprience) return $result;
		else return false;
	}

	/**
	 * 判断是否超出限值范围
	 * @param int userid
	 * @param int $limit
	 * @param string $credit_type score|exprience
	 * @param int $limit_type day|month|year... [此参数默认为日限制，将来可能会有更多增减的条件]
	 */
	private function ifAreadyOutRange($userid,$action_id,$limit_val,$credit_type,$limit_type="day")
	{
		if($limit_val!=0){
			$stat_total=$this->getUserCreditTotalByDay($userid,$action_id,$credit_type);
			if($limit_val<0 && $limit_val<$stat_total) {
				return true;
			}else if($limit_val>0 && $limit_val>$stat_total){
				return true;
			}
			return false;
		}else{
			return false;
		}
	}

	/**
	 * 根据系统时间计算用户 日 积分|经验值 总和
	 * 注：总和有可能是负数
	 */
	public function getUserCreditTotalByDay($userid,$action_id,$credit_type)
	{
		if($userid){
			$sql="SELECT SUM(credit_value) AS t FROM user_credit_stat WHERE userid=$userid AND action_id='$action_id' AND credit_type=$credit_type AND DATE_FORMAT(add_datetime,'%Y-%m-%d')=CURDATE()";
			$credit_total=$this->db->query($sql);
			$credit_total = intval($credit_total[0]['t']);
			if($credit_total<0) return $credit_total;
			return $credit_total ? $credit_total : 0;
		}else{
			return false;
		}
	}

	/**
	 *	更新用户积分总值
	 */
	private function updateUserScore($userid)
	{
		if($userid){
			$credit_total = $this->where('userid='.$userid." AND credit_type=1")->sum('credit_value');
			$credit_total = $credit_total ? $credit_total : 0;
			return $this->db->execute("UPDATE users SET score=$credit_total WHERE userid=$userid");
		}else {
			return false;
		}
	}

	//更改用户经验总值
	private function updateUserExprience($userid)
	{
		if($userid){
			$credit_total = $this->where('userid='.$userid." AND credit_type=2")->sum('credit_value');
			$credit_total = $credit_total ? $credit_total : 0;
			return $this->db->execute("UPDATE users SET experience=$credit_total WHERE userid=$userid");
		}else{
			return false;
		}
	}
	
	/**
	 * 用户积分兑换，增加说明
	 * @param int $userid 操作对象的userid
	 * @param string $act_id 行为名称[user_score_exchange]
	 * @param string $describe 对此行为的描述
	 * @param int $score 积分的变化值
	 * @param int $experience 经验的变化值
	 */
	public function addUserCreditStat($userid,$describe="",$score=0,$experience=0){
		if(empty($userid) || (empty($score) && empty($experience))) return false;
		$act_id="user_score_exchange";
		$creditstat_mod=M("UserCreditStat");
		$extend_mod=M("UserCreditStatExtend");
		$data["action_id"]=$act_id;
		$data['userid']=$userid;
		$data['add_datetime']=$this->time;
		//如果积分不为0
		if(!empty($score)){
			$data['credit_type']=1;
			$data['credit_value']=$score;
			$res_score=$creditstat_mod->add($data);
			$score_id=$creditstat_mod->getLastInsID();
			$resultscore = $this->updateUserScore($userid);
			unset($data['credit_type']);
			unset($data['credit_value']);
			if(!empty($describe)){
				$ex_data['id']=$score_id;
				$ex_data['remark']=$describe;
				$extend_mod->add($ex_data);
			}
			
			//加积分动态
			$this->addCreditDynamic($userid, $act_id, $score,$describe);
			//发私信
			if($score > 0){
				$username = M("Users")->where("userid=".$userid)->getField("nickname");
				$msg = "{$username}你好，由于{$describe}，获得积分奖励（<a href='/home/score.html' target='_blank' class='WB_info'>查看我的积分</a>），感谢您的支持。 获取更多积分请到<a href='/task/index.html' class='WB_info'>我的任务</a>！";
				D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$msg);
			}
			
			
			
		}
		//如果经验值不为0
		if(!empty($experience)){
			$data['credit_type']=2;
			$data['credit_value']=$experience;
			$res_score=$creditstat_mod->add($data);
			$experience_id=$creditstat_mod->getLastInsID();
			$resultexprience = $this->updateUserExprience($userid);
			if(!empty($describe)){
				$ex_data['id']=$experience_id;
				$ex_data['remark']=$describe;
				$extend_mod->add($ex_data);
			}
		}		
	}
	
    /**
     * 根据用户ID获取积分变化列表
     * @param int $userid
     * @param mixed $p
     */
	public function getScoreListByUserid($userid,$limit=null){
		$where['userid'] =$userid;
		$where['credit_type'] =1 ;
		return $this->getCreditList($where,$limit);
	}
	
	/**
	 * \通过用户ID获取记录总数
	 * @param unknown_type $userid
	 */
	public function getScoreCountByUserid($userid){
		$where['userid'] =$userid;
		$where['credit_type']=1;
		return $this->where($where)->count("id");
	}
	
	
	/**
	 * 通过查询条件获取列表
	 * @param mixed $where
	 * @param string $limit
	 */
	public function getCreditList($where=array(),$limit=null,$order="id DESC"){
	    $credit_stat_extend_mod=M("UserCreditStatExtend");
	    $credit_set_mod=M("UserCreditSet");
		$list=$this->where($where)->limit($limit)->order($order)->select();
		foreach($list as $key =>$val){
			$remark =  $credit_stat_extend_mod -> where("id=".$val['id'])->getField("remark");
		    if($remark){
		    	$list[$key]['action_name'] =$remark;
		    }else{
		    	$list[$key]['action_name'] = $credit_set_mod->where('action_id="'.$val['action_id'].'"')->getField("action_name");
		    }
		}
		return $list;
	}
	
	
	
	public function getUserSignTotalNum(){
		$userlist=$this->distinct("userid")->where("action_id='user_sign' AND credit_type=1")->field("userid")->select();
		$type="sign_num";
		foreach($userlist as $key=>$value){
			$sign_num=0;
			$userid=$value['userid'];
			$sign_num=$this->where("action_id='user_sign' AND credit_type=1 AND userid=$userid")->count();
			$ret=D("UserData")->addUserData($userid,$type,$sign_num,2);
			if($ret==false){
				echo $userid."<br >";
			}
		}
		echo "success";
	}
	
	/**
	 * 获取用户在任务中是否绑定新浪微博或手机
	 * @param int $userid 用户ID
	 * @param string $type
	 * @return int $num 【返回的状态：$num=1表示绑定过，$num=0表示没有绑定过】
	 * @author penglele
	 */
	public function getUserTaskStat($userid,$type=""){
		$num=0;
		if(!$userid || !$type){
			return $num;
		}
		$where=array();
		$where['userid']=$userid;
		if($type=="sina"){
			$where["action_id"]="user_bound_sina_weibo";
		}elseif($type=="mobile"){
			$where["action_id"]="user_verify_mobile";
		}elseif($type=="qq"){
			$where["action_id"]="user_bound_qq";
		}
		$info=$this->where($where)->find();
		if($info){
			$num=1;
		}else{
			if($type=="sina" || $type=="qq"){
				$data['type']=$type;
				$data['uid']=$userid;
				$open_info=M("UserOpenid")->where($data)->find();
				if($open_info){
					$num=1;
				}
			}
		}
		return $num;
	}
	
	
	
	
	/**
	 * 增加积分动态
	 * @param int $userid
	 * @param string $action_id
	 * @param string $remark
	 * @param int $value
	 * @author litingting
	 */
	public function addCreditDynamic($userid,$action_id,$value,$remark=''){
		if(empty($userid) || empty($action_id)){
			return false;
		}
		$time = time();
		if(empty($remark)){
			$remark = M("UserCreditSet")->where(array('action_id'=>$action_id))->getField("action_name");
			$remark = $remark."，获得 ".$value." 积分";
		}
		return D("UserDynamic") ->addDynamic($userid,$remark);
	}
	
}

?>