<?php
/**
 * 网站活动模型
 */
class UserDynamicModel extends Model{
	
	
	/**
	 * 增加动态
	 * @param int $userid
	 * @param string $action_id
	 * @param string $remark
	 * @param int $value
	 * @author litingting
	 */
	public function addDynamic($userid,$remark){
		if(empty($userid) || empty($remark)){
			return false;
		}
		$time = time();
		$nickname = M("Users")->where("userid=".$userid)->getField("nickname");;
		$sql="REPLACE INTO user_dynamic(userid,nickname,remark,addtime) values({$userid},'{$nickname}','{$remark}',{$time})";
		return $this->execute($sql);
	}
	
	
	
	/**
	 * 获取用户积分动态
	 * @uses 用于首页
	 * @return mixed|null
	 * @author litingting
	 */
	public function getUserDynamic($limit=50){
		$list = $this->order("addtime DESC")->limit($limit)->select();
		return $list;
	}
}