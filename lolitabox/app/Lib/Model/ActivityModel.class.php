<?php
/**
 * 网站活动模型
 */
class ActivityModel extends Model {

	public $ACTIVITY_ROOTID; //所在文章管理中根分类ID
	
	/**
	 * 获取参加达人风云积分榜的用户名单
	 */
	public function getUserListOfScorelist($lottery_id){
		//不同期的只需要更改whoid
		$behaviour_mod=M("UserBehaviourRelation");
		$where["type"]="scorelist";
		$where["usertype"]=1;
		$where["whoid"]=$lottery_id;
		$where["status"]=1;
		$behaviour_list=$behaviour_mod->field("userid")->where($where)->select();
		return $behaviour_list;
	}
	
	/**
	 * 获取用户在活动期间发表的且被收录的分享数
	 * @param $userid 用户ID
	 * @param $pro_arr 产品ID组成的字符串
	 * @param $stime 起始时间 $stime[year] 年 $stime[month] 月 $stime[day] 日
	 * 
	 */
	public function getUserGoodShareNum($userid,$pro_arr,$stime,$etime=""){
		$s_time=mktime(0,0,0,$stime['month'],$stime['day'],$stime['year']);
		$where["userid"]=$userid;
		$where["sharetype"]=1;
		$where["status"]=array("exp",">0");
		$where["ischeck"]=1;
		if(!empty($etime)){
			$e_time=mktime(23,59,59,$etime['month'],$etime['day'],$etime['year']);
			$where["posttime"]=array(array("egt",$s_time),array("elt",$e_time,"AND"));
		}else{
			$where["posttime"]=array("exp",">=$s_time");
		}
		$num=0;
		$usershare_mod=M("UserShare");
		$share_list=$usershare_mod->field("id")->where($where)->select();
		if($share_list){
			$userat_mod=M("UserAtme");
			foreach($share_list as $val){
				$at_where["relationid"]=$val["id"];
				$at_where["relationtype"]=1;
				$at_where["sourceid"]=array("exp","in ($pro_arr)");
				$at_where["sourcetype"]=2;
				$at_where["state"]=1;
				$at_info=$userat_mod->field("id")->where($at_where)->find();
				if($at_info){
					$num++;
				}
			}
		}
		return $num;
	}
	
	/**
	 * 获取需要加积分的用户组成的字符串
	 * @param int $lottery_id 活动期号 【例：$lottery_id=5】
	 * @param string $pro_arr 对某些产品发的分享 【例：$pro_arr="123,22,45"】
	 * @param array $stime 起始时间 【例：array("month"=>05,"day"=>03,"year"=>2013)】
	 * @param array $etime 结束时间 【例：array("month"=>05,"day"=>28,"year"=>2013)】
	 */
	public function getUserString($lottery_id,$pro_arr,$stime,$etime=""){
		$userlist=$this->getUserListOfScorelist($lottery_id);
		$userstr="";
		if($userlist){
			foreach($userlist as $val){
				$user_share_num=$this->getUserGoodShareNum($val["userid"],$pro_arr,$stime,$etime);
				if($user_share_num>0){
					for($i=0;$i<$user_share_num;$i++){
						if($userstr==""){
							$userstr=$val["userid"];
						}else{
							$userstr=$userstr.",".$val["userid"];
						}
					}
				}
			}
		}
		return $userstr;
	}
	
	/**
	 * 连HIGH三周萝莉盒换新颜送豪礼
	 * 增加用户参加活动微博转发信息
	 * @param userid 用户ID
	 * @param spreadtype 转发通道类型【qq/sina】
	 * @return boolean true/false
	 * @author zhenghong
	 * 2013-09-06
	 */
	public function addV5SpreadInfo($userid,$spreadtype){
		if(!$userid || empty($spreadtype)) {
			return false;
		}
		$nowtime=time();
		$data=array(
			"userid"=>$userid,
			"activitykey"=>"V5", //V5活动
			"spreadtype"=>$spreadtype,
			"postdate"=>date("Y-m-d",$nowtime),
			"posttime"=>$nowtime
		);
		$ActivitySpreadMod=M("ActivitySpread");
		if($ActivitySpreadMod->add($data)) {
			$UserCreditMod=D("UserCreditStat");
			switch ($spreadtype) {
				case "qq":
					$spreadtitle="腾讯微博";
					break;
				case "sina":
					$spreadtitle="新浪微博";
					break;
			}
			$UserCreditMod->addUserCreditStat($userid,"参与签到转发".$spreadtitle."，获得10积分奖励",10,0);
			return true;
		}
		else {
			return false;
		}
	}
	

}