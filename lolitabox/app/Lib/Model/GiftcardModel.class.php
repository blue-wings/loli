<?php
/**
 * 礼品卡模型
 * @author penglele
 */
class GiftcardModel extends Model {
	
	/**
	 * 获取用户礼品的列表
	 * @author penglele
	 */
	public function getUserGiftcardList($userid,$limit=""){
		if(!$userid){
			return '';
		}
		$order="activate_datetime DESC";
		$list=$this->where("userid=$userid")->limit($limit)->order($order)->select();
		return $list;
	}
	
	/**
	 * 获取用户礼品卡的总数
	 * @author penglele
	 */
	public function getUserGiftcardNum($userid){
		if(!$userid){
			return 0;
		}
		$num=$this->where("userid=$userid")->count();
		return $num;
	}
	
	/**
	 * 获取用户礼品卡的总金额
	 * @author penglele
	 * @return 返回的单位是分
	 */
	public function getUserGiftcardTotalPrice($userid){
		if(!$userid){
			return 0;
		}
		$price=$this->where("userid=$userid")->sum('price');
		return (int)$price;
	}
	
	/**
	 * 获取用户礼品卡的余额
	 * @param int $userid
	 * @author penglele
	 * @return 返回的单位是分
	 */
	public function getUserGiftcardPrice($userid){
		$total_price=$this->getUserGiftcardTotalPrice($userid);//礼品卡的总金额
		$cost_price=D("UserOrder")->getUserOrderGiftcardPrice($userid);//用户消耗的礼品卡的总金额
		$price=$total_price-$cost_price;//用户账户中礼品卡的余额
		return $price;
	}
	
	/**
	 * 激活礼品卡
	 * @author penglele
	 */
	public function activateGiftcard($cid,$pwd,$userid){
		if(!$userid){
			//用户没有登录
			return 1000;
		}
		if(!$cid || !$pwd){
			//礼品卡号或密码为空
			return 100;
		}
		$info=$this->where("card_id='".$cid."' AND card_pwd='".$pwd."'")->find();
		if(!$info){
			//礼品卡号或密码错误
			return 100;
		}
		if($info['userid']>0){
			//礼品卡已激活
			return 101;
		}
		$etime=strtotime($info['indate']." 23:59:59",time());
		$ntime=time();
		if($ntime>$etime){
			//礼品卡已过期
			return 102;
		}
		$data['userid']=$userid;
		$data['activate_datetime']=date("Y-m-d H:i:s");
		//保存用户的信息
		$rel=$this->where("card_id='".$info['card_id']."'")->save($data);
		if($rel==false){
			return false;
		}
		return $info;
	}
	
	
}
?>