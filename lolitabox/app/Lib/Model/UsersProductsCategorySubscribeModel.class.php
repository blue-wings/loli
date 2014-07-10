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

    /**
     * @param $userId
     * @param $productsCategoryId
     * @return bool
     */
    public function getByUserIdAndProductsCategoryId($userId,$productsCategoryId){
        if(empty($userId) || empty($productsCategoryId)){
            return false;
        }
        $where['user_id'] = $userId;
        $where['product_category_id'] = $productsCategoryId;
        return $this->where($where)->select();
    }
	
}