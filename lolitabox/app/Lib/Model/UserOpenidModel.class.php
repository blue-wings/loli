<?php
class UserOpenidModel extends Model {
	/**
	 * 判断用户是否绑定
	 * @param int $userid
	 */
	public function checkOpenLock($userid){
		$return['sina'] = $this->checkOpenLockByType($userid,'sina');
		$return['sohu'] = $this->checkOpenLockByType($userid,'sohu');
		$return['qq'] = $this->checkOpenLockByType($userid,'qq');
		$return['mobile'] = $this->checkOpenLockByType($userid,'mobile');
		return $return;
	}
	
	/**
	 * 根据用户ID和第三方平台判断用户是否具有该属性
	 * @param unknown_type $userid
	 * @param unknown_type $type
	 */
	public function checkOpenLockByType($userid,$type){
		$flag = $this->where("type='$type' AND uid=$userid AND isbind=1")->find();
		if($flag){
			$info=1;
		}else{
			$info=0;
		}
		return $info;
	}
	
	/**
	 * 判断绑定的用户是否还在授权期
	 */
	public function getBindDetail($userid){
		if(!$userid){
			return false;
		}
		$return["qq"]=$this->checkOpenDetailByType($userid,"qq");		
		$return["sina"]=$this->checkOpenDetailByType($userid,"sina");
		return $return;
	}
	
	/**
	 * 根据用户ID,绑定类型判断绑定的用户是否在授权期
	 * @param int $userid
	 * @param string $type
	 * @return $return 授权状态，
	 * 											  $return=0：表示用户还没有绑定，
	 * 											  $return=1：表示在授权期定，
	 * 				  							  $return=2：表示用户已绑定但已过授权期
	 */
	public function checkOpenDetailByType($userid,$type){
		$info=$this->where("type='$type' AND uid=$userid AND isbind=1")->find();
		if(!$info){
			$return=0;
		}else{
			$logindate=strtotime($info["logindate"],time());
			$ntime=time();
			//当类型为qq时
			if($type=="qq"){
				if($info['accesstoken']=="" || ($ntime-$logindate>90*24*3600)){
					$return=2;
				}else{
					$return=1;
				}				
			}
			//当类型为sina时
			if($type=="sina"){
				if($info['accesstoken']=="" || ($ntime-$logindate>7*24*3600)){
					$return=2;
				}else{
					$return=1;
				}				
			}
		}
		return $return;
	}
	
	/**
	 * 获取用户第三方账号的openid
	 * @param  $userid 用户ID
	 * @param  $type 第三方账号类型
	 */
	public function getUserOpenid($userid,$type){
		if(!$userid || !$type){
			return false;
		}
		$info=$this->field("id")->where("uid=$userid AND type='".$type."'")->find();
		return $info['id'];
	}
	
	
}