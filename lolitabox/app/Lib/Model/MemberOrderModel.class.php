<?php
/**
 * 用户特权订单模型
*/
class MemberOrderModel extends Model {
	
	/**
	 * 获取特权会员类型列表
	 * @author penglele
	 */
	public function getMemberTypeList(){
		$list=array(
				1=>array('price'=>12,'title'=>'月度特权会员'),
				6=>array('price'=>48,'title'=>'半年特权会员'),
				12=>array('price'=>60,'title'=>'全年特权会员')
				);
		return $list;
	}
	
	/**
	 *  生成用户特权订单
	 *  @author penglele
	 */
	public function addOrder($userid,$pay_bank,$type){
		//数据不能为空
		if(!$userid || !$pay_bank || !$type){
			return false;
		}
		//特权类型列表
		$typelist=$this->getMemberTypeList();
		//查看用户选择的类型是否符合规则
		if(!array_key_exists($type,$typelist)){
			return false;
		}
		
		$starttime="2014-01-01 00:00:00";
		$ntime=date("Y-m-d H:i:s");
		
		//购买过特权会员的不能再购买月度的
		if($type==1){
			$member_info=D("Member")->getUserMemberInfo($userid);
			if($member_info['state']>0 && $ntime<$starttime){
				return false;
			}
		}
		
		$t_price=$typelist[$type]['price'];
		if($type==1 && $ntime<$starttime){
			$t_price=5;
		}
		//组合订单信息
		$data['ordernmb']=date("YmdHis").rand(100,999);
		$data['userid']=$userid;
		$data['pay_bank']=$pay_bank;
		$data['m_type']=$type;
		$data['price']=$t_price;
		$data['addtime']=$ntime;
		
		//增加联盟推广信息
		$promotion_cookie_data=getPromotionCookie();
		if(!empty($promotion_cookie_data['from_id'])){
			$data['fromid']=$promotion_cookie_data['from_id'];
			if(!empty($promotion_cookie_data['from_info'])){
				$data['frominfo']=$promotion_cookie_data['from_info'];
			}
		}
		//生成订单
		$rel=$this->add($data);
		if($rel==false){
			return false;
		}
		return $data['ordernmb'];
	}
	
	/**
	 * 订单详情
	 * @param unknown_type $id
	 * @author penglele
	 */
	public function getOrderDetail($id){
		if(!$id){
			return false;
		}
		$info=$this->where("ordernmb=".$id)->find();
		if(!$info){
			return false;
		}
		$typelist=$this->getMemberTypeList();
		$type=$info['m_type'];
		$info['name']=$typelist[$type]['title'];
		return $info;
	}
	
	/**
	 * 获取特权订单列表
	 * @param array $where
	 * @param string $order
	 * @author penglele
	 */
	public function getMemberOrderList($where,$order="",$limit=""){
		if(empty($order)){
			$order="ordernmb DESC";
		}
		$list=$this->where($where)->order($order)->limit($limit)->select();
		if($list){
			$user_mod=M("Users");
			$typelist=$this->getMemberTypeList();
			$member_mod=D("Member");
			foreach($list as $key=>$val){
				$nickname=$user_mod->where("userid=".$val['userid'])->getField("nickname");
				$member_info=$member_mod->getUserMemberInfo($val['userid']);
				$list[$key]['nickname']=$nickname;
				$type=$val['m_type'];
				$list[$key]['name']=$typelist[$type]['title'];
				$list[$key]['endtime']=$member_info['date'];
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户特权订单总数
	 * @author penglele
	 */
	public function getMemberOrderCount($where){
		$count=$this->where($where)->count();
		return $count;
	}
	
	/**
	 *  生成用户特权订单--共内部使用
	 *  @param $data 组成订单的信息
	 *  @author penglele
	 */
	public function addOrderAdmin($data){
		$ntime=date("Y-m-d H:i:s");
		$data['ordernmb']=date("YmdHis").rand(100,999);//订单号
		$data['addtime']=$ntime;//生成订单时间
		//支付方式
		if(empty($data['pay_bank'])){
			$data['pay_bank']="directPay";
		}
		//生成订单
		$rel=$this->add($data);
		if($rel==false){
			return false;
		}
		return $data['ordernmb'];
	}	
	
	
	
}
?>