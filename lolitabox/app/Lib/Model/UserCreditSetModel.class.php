	<?php
/**
 * 用户积分/经验值配置模型
 * @author 
 * @version v5
 * @author zhenghong
 */
class UserCreditSetModel extends Model {

	/**
	 * 根据动作id获取积分值与经验值
	 * @param unknown_type $actionid
	 */
	public function getCreditValById($actionid,$field=""){
		if(!empty($actionid)) {
			$result=$this->where("action_id='$actionid'")->find();
			if($result) {
				if(!empty($field) && $result[$field]){
					return  $result[$field];
				}
				else {
					return $result;
				}
			}
			else {
				return "";
			}
		}
		else {
			return "";
		}
	}
	
}

?>