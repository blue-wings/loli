<?php
/**
 * 用户地址模型
*/
class UserAddressModel extends Model {
	
	/**
	 * 获取用户的某一地址信息
	 * @param $id 用户地址ID
	 */
	public function getUserAddressInfo($id,$if_del=0){
		if(empty($id)) return false;
		if($if_del==0){
			$where["if_del"]=$if_del;
		}
		$where['id']=$id;
		$add_info=$this->where($where)->find();
		if(!$add_info) return false;
		return $add_info;
	}
	
	/**
	 * 根据用户ID获取用户地址列表
	 * @param $userid 用户ID
	 */
	public function getUserAddressList($userid,$order=""){
		if(empty($userid))
			return false;
		$where['userid']=$userid;
		$where['if_del']=0;
		$add_list=$this->where($where)->order($order)->select();
		return $add_list;
	}
	
	/**
	 * 获取用户地址条数
	 * @param $userid 用户ID
	 */
	public function getUserAddressCount($userid){
		if(empty($userid))
			return false;
		$where['userid']=$userid;
		$where['if_del']=0;
		$add_count=$this->where($where)->count();
		return $add_count;
	}
	
	/**
	 * 获取用户的默认地址
	 * @param  $userid
	 */
	public function getUserActiveAddress($userid){
		if(!$userid) return false;
		$where['userid']=$userid;
		$where['if_del']=0;
		$where['if_active']=1;
		$addressinfo=$this->where($where)->find();
		if(!$addressinfo) return false;
		return $addressinfo;
	}
	
	
	/**
	 * 判断用户user_address中的地址
	 * @param int $userid
	 * @author lit
	 */
	public function ishaveuseraddress($userid) {
		return $this->where ( "userid=$userid AND if_del=0" )->order ( "addtime DESC" )->select ();
		
	}
	
	
	
	
	
	
	
	
}