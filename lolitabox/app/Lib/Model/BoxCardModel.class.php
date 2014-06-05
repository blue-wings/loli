<?php
/**
 * 购盒卡模型
*/
class BoxCardModel extends Model {
	
	/**
	 * 购盒卡列表
	 * @param $where 查询条件
	 */
	public function getBoxCardList($where=null,$order=null,$p=null){
		if(empty($where)) $where="1=1";
		if(empty($order)) $order="id DESC";
		$boxcard_mod=M("user_boxcard");
		if(!empty($p))
			$boxcard_list=$boxcard_mod->where($where)->order($order)->limit($p)->select();
		else
			$boxcard_list=$boxcard_mod->where($where)->order($order)->select();
		return $boxcard_list;
	}
	
	
	
	
	
	
	
	
	
	
	
}