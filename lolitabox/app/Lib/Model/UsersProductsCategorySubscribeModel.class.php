<?php
/**
 *
 * user subscribe prodcut category class
 * @author fangrui
 *
 */
class UsersProductsCategorySubscribeModel extends Model {

	/**
	 * get user subcribe category ids by userId
	 * @param unknown_type $userId
	 */
	public function getByUserId($userId){
		if(empty($userId)){
			return false;
		}
		$where ['user_id']  = $userId;
		return $this->where($where)->order ( "subscribe_time desc" )->select();
	}
	
}