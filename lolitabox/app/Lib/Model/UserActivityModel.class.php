<?php

/**
 * 活动奖品模型
 * @author penglele
 *
 */
class UserActivityModel extends Model {
	
	/**
	 * 获取某个活动【用户/总的】已兑换到的产品的总数
	 * @param $userid 用户ID
	 * @param $name 活动名称
	 * @param $gifttype 奖品类型
	 * @author penglele
	 */
	public function getGiftNum($userid="",$name,$gifttype=""){
		if(!$name){
			return 0;
		}
		if($userid){
			$where['userid']=$userid;
		}
		$where['activitytype']=$name;
		if($gifttype){
			$where['gifttype']=$gifttype;
		}
		$num=$this->where($where)->count();
		return (int)$num;
	}
	
	/**
	 * 增加活动记录
	 * @param int $userid 用户ID
	 * @param string $name 活动名称
	 * @param string $gifttype 活动奖品类型
	 * @param string $giftinfo 活动奖品信息
	 * @param string $remark 活动备注
	 * @author penglele
	 */
	public function addUserActivity($userid,$name,$gifttype,$giftinfo="",$remark=""){
		if(!$userid || !$name || !$gifttype){
			return false;
		}
		$where['userid']=$userid;
		$where['activitytype']=$name;
		$where['gifttype']=$gifttype;
		if($giftinfo){
			$where['giftinfo']=$giftinfo;
		}
		if($remark){
			$where['remark']=$remark;
		}
		$where['addtime']=date("Y-m-d H:i:s");
		$rel=$this->add($where);
		if($rel==false){
			return false;
		}
		return true;
	}
	
	
	
}
?>