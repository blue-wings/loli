<?php
/**
 * 用户特权信息模型
*/
class MemberModel extends Model {
	
	/**
	 * 获取用户特权详情
	 * @return array 【state用户的特权状态：0没有购买过，1还在特权期，2已过期；date过期时间】
	 * @author penglele
	 */
	public function getUserMemberInfo($userid){
		$info=array("state"=>0,"date"=>"");
		if($userid){
			//用户特权信息
			$if_member=$this->where("userid=".$userid)->find();
			if($if_member){
				$info['date']=$if_member['endtime'];
				$ndate=date("Y-m-d");
				//判断用户是否还在特权期
				if($ndate<=$if_member['endtime']){
					$info['state']=1;
				}else{
					$info['state']=2;
				}
			}			
		}
		return $info;
	}
	
	/**
	 * 判断用户目前是否是特权会员
	 * @author penglele
	 */
	public function getUserIfMember($userid){
		$info=$this->getUserMemberInfo($userid);
		return $info['state'];
	}
		
	/**
	 *  添加用户特权信息
	 *  @param $userid 用户ID
	 *  @param $type 用户购买的特权类型
	 *  @author penglele
	 */
	public function addMember($userid,$type){
		if(!$userid || !$type){
			return false;
		}
		//判断用户选择的是否为特权类型
		$typelist=D("MemberOrder")->getMemberTypeList();
		if(!array_key_exists($type,$typelist)){
			return false;
		}
		$ndate=date("Y-m-d");
		$member_info=$this->where("userid=".$userid)->find();
		$memberlist=$this->getUserMemberDateOfType($userid);
		$info=$memberlist[$type];
		$data['endtime']=$info['edate'];
		if(!$member_info){
			//当用户不存在特权信息时
			$data['userid']=$userid;
			$rel=$this->add($data);//添加用户特权信息
			if(!$rel){
				return false;
			}
		}else{
			//用户购买过特权会员
			$rel=$this->where("userid=".$userid)->save($data);//更新用户特权信息
			if(!$rel){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 获取特权会员列表
	 * @author penglele
	 */
	public function getMemberList($where,$order="",$limit=""){
		$member_list=$this->where($where)->order($order)->limit($limit)->select();
		$list=array();
		if($member_list){
			$user_mod=D("Users");
			foreach($member_list as $val){
				$userinfo=$user_mod->getUserInfo($val['userid']);
				$userinfo['endtime']=$val['endtime'];
				$list[]=$userinfo;
			}
		}
		return $list;
	}
	
	/**
	 * 获取特权会员总数
	 * @author penglele
	 */
	public function getMemberCount($where){
		$count=$this->where($where)->count();
		return $count;
	}	
	
	/**
	 * 根据用户的当前状态获取用户每种特权的起止时间
	 * @author penglele
	 */
	public function getUserMemberDateOfType($userid){
		$memberinfo=$this->getUserMemberInfo($userid);//用户当前特权信息
		$order_mod=D("MemberOrder");
		$typelist=$order_mod->getMemberTypeList();//特权会员类型
		$list=array();
		$ntime=date("Y-m-d H:i:s");//当前时间
		$stime="2014-01-01 00:00:00";//优惠截止时间
		$ndate=date("Y-m-d");
		foreach($typelist as $key=>$val){
			$info=array();
			$price=$val['price'] ;//需要支付的金额
			$sdate=$ndate;//新特权起始时间
			$name=$val['title'];
			if($userid){
				if($key==1 || $key==6){
					if($memberinfo['state']==1){
						//在特权期时的起始时间
						$sdate=date("Y-m-d",strtotime($memberinfo['date']."1 day"));
					}
					$edate=date("Y-m-d",strtotime($sdate.$key." month"));
					if($key==1){
						$price=$ntime<$stime ? 5 : $val['price'] ;
					}
				}else if($key==12){
					if($memberinfo['state']==1){
						$sdate=date("Y-m-d",strtotime($memberinfo['date']."1 day"));
						if($memberinfo['date']<="2013-12-31"){
							$edate="2014-12-31";
						}else{
							$edate=date("Y-m-d",strtotime($sdate.$key." month"));
						}
					}else{
						if($ntime<$stime){
							$edate="2014-12-31";
						}else{
							$edate=date("Y-m-d",strtotime($key." month"));
						}
					}
				}		
			}else{
				$edate=$sdate;
			}
			$info['price']=$price;
			$info['sdate']=$sdate;
			$info['edate']=$edate;
			$info['name']=$name;
			$list[$key]=$info;
		}
		return $list;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}