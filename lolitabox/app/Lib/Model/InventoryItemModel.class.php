<?php
/**
 * 网站活动模型
 */
class InventoryItemModel extends Model {

	/**
	 * 获取inventory_item中的信息
	 */
	public function getInventoryItemInfo($id,$field="*"){
		if(!$id)
			return false;
		$info=$this->field($field)->where("id=$id")->find();
		if(!$info)
			return false;
		return $info;
	}
	
	/**
	 * 产品目前的库存量
	 * @author penglele
	 */
	public function getProductInventory($id,$boxid){
		if(!$id)
			return false;
		$box_pro_info=M("BoxProducts")->where("pid=$id AND boxid=$boxid")->find();
		$box_pro_count=$box_pro_info["ptotal"]-$box_pro_info["saletotal"]-$box_pro_info["pquantity"];
		$item_info=$this->getInventoryItemInfo($id,"inventory_estimated");
		$item_count=$item_info['inventory_estimated'];
		if($item_count==0)
			$item_count=-1;
		$count=$box_pro_count < $item_count ? $box_pro_count : $item_count;
		return $count;
	}
	
	/**
	 * 判断选择的产品是否已在兑换周期内兑换过
	 * @param $id [invenroty_item 下的id]
	 * @param $userid 用户ID
	 */
	public function checkPidInterval($id,$userid,$type=""){
		if(!$id)
			return false;
		$info=$this->getInventoryItemInfo($id,"exchange_interval");
		if(empty($type)){
			$type=C("BOX_TYPE_EXCHANGE_PRODUCT");
		}
		$return['day']=0;
		$return['time']="";
		if($type==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			//对于积分兑换，为0则表示没有兑换周期
			if($info['exchange_interval']==0){
				return $return;
			}
		}
		if($type==C("BOX_TYPE_PAYPOSTAGE")){
			$info['exchange_interval'] = $info['exchange_interval']==0 ? 30 : $info['exchange_interval'];
		}
		$sdate=date("Y-m-d 00:00:00",strtotime("-".$info['exchange_interval']." day"));
		$sql="SELECT * FROM `user_order_send_productdetail` s WHERE s.orderid in( SELECT o.ordernmb FROM user_order o WHERE userid=$userid AND type=$type AND ifavalid=1 AND state=1 AND paytime>='".$sdate."' ) AND s.productid=$id ORDER BY s.orderid DESC;";
		$list=$this->query($sql);
		if(!$list)
			return $return;
		$return['day']=$info['exchange_interval'];
		$order_info=M("UserOrder")->field("paytime")->where("ordernmb=".$list[0]['orderid'])->find();
		$time=D("Public")->getDateCut($order_info['paytime'],$info['exchange_interval']);
		$return['time']=$time;
		return $return;
	}
	
	/**
	 * 通过条件查询单品信息
	 * @param $where 查询条件
	 * @param $field 
	 * @author penglele
	 */
	public function getInvetoryInfoByCondition($where,$field="*"){
		if(!$where)
			$where="1=1";
		$info=$this->field($field)->where($where)->find();
		if(!$info)
			return false;
		return $info;
	}
	
	/**
	 * 通过产品ID获取boxproduct中的单品ID
	 * @author penglele
	 */
	public function getItemIDByProductid($pid,$boxid){
		if(!$pid || !$boxid){
			return '';
		}
		$sql="SELECT i.id FROM inventory_item i, box_products b WHERE i.relation_id=$pid AND b.ishidden=0 AND i.id=b.pid AND b.boxid=$boxid";
		$info=$this->query($sql);
		return $info[0]['id'];
	}

	/**
	 * 根据产品ID获取库存单品ID
	 * 产品ID与库存单品ID可能存在1:N关系，本方法暂时时只返回第一条【1:N的关系需要进一步完善】
	 * @param unknown_type $pid
	 * @author zhenghong
	 * 2013-09-04
	 */
	public function getIdByPid($pid){
		if(!$pid) {
			return '';
		}
		$result=M("InventoryItem")->field("id")->where("relation_id=".$pid)->select();
		return $result;
	}
	



}