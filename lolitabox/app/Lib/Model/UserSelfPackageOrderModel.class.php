<?php
class UserSelfPackageOrderModel extends Model {
	
	/**
	 * 获取订单信息
	 * @param $orderid 订单ID
	 * @return array $order_info 订单信息
	 * @author penglele
	 */
	public function getOrderInfo($orderId,$field="*"){
		if(empty($orderId)) return false;
		$orderInfo=$this->field($field)->getByOrdernmb($orderId);
		if(!$orderInfo) return false;
		return $orderInfo;
	}
	
	/**
	 * 创建邮费订单
	 * @param unknown_type $userid
	 * @param unknown_type $productIds
	 * @param unknown_type $productNums
	 * @throws Exception
	 */
	public function createOrder($userId, $userOrderSendProductDetailIds){
		$data['ordernmb']=date("YmdHis").rand(100,999);
		$data["cost"] = 0;
		$data['userid']=$userId;
		$data['addtime']=date("Y-m-d H:i:s");
		$data['user_order_send_productdetail_ids']=join(",", $userOrderSendProductDetailIds);
		$data['state']=C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED");
		$data['ifavalid']=C("ORDER_IFAVALID_VALID");
		//生成订单
		$this->add($data);
		foreach ($userOrderSendProductDetailIds as $userOrderSendProductDetailId){
			$params["id"]=$userOrderSendProductDetailId;
			$params["self_package_order_id"]=$data['ordernmb'];
			D("UserOrderSendProductdetail")->save($params);
		}
		return $data['ordernmb'];
	}
	
	/**
	 * 获取订单内的详细信息
	 * @param string $orderid
	 * @author ltingting
	 */
	public function getOrderDetail($orderId){
		$orderInfo=$this->getByOrdernmb($orderId);
		if(!$orderInfo)
			return false;
		//判断用户未订单是否已失效
		if($orderInfo['state']==C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED") && $orderInfo['ifavalid']==C("ORDER_IFAVALID_VALID")){
			$orderTime=strtotime($orderInfo['addtime']);
			$nowTime=time();
			if($nowTime-$orderTime>C("ORDER_VALID_DURATION")){
				$orderInfo['ifavalid']=C("ORDER_IFAVALID_OVERDUE");
			}
		}
		
		$weight = 0;
		$productTotalNum = 0;
		$orderProducts = D("UserOrderSendProductdetail")->getSelfPackageOrderProducts($orderId);
		foreach ($orderProducts as $key => $product){
			$inventoryItem = D("InventoryItem")->getById($product["inventory_item_id"]);
			$validdateTime = strtotime($inventoryItem["validdate"]);
			$inventoryItem["validdateFormat"]= date("Y年m月d日", $validdateTime);
			$inventoryItem["weightKg"]=bcdiv($inventoryItem["weight"], 1000, 3);
			$product["inventoryItem"]=$inventoryItem;
			$productNum = $product["product_num"];
			$product["totalWeight"]=bcmul($inventoryItem["weightKg"], $productNum, 3);
			$weight = bcadd($weight, $product["totalWeight"], 3);
			$productTotalNum += $productNum;
			$orderProducts[$key]=$product;
		}
		
		$list["products"] = $orderProducts;
		
		
		$list["weight"]=$weight;
		$list["productTotalNum"]=$productTotalNum;
		
		
		//订单信息
		$list['orderid']=$orderId;
		$list['userid']=$orderInfo['userid'];
		$list['ifavalid']=$orderInfo['ifavalid'];
		$list['addtime']=$orderInfo['addtime'];
		$list['credit']=$orderInfo['credit'];
		$list['state']=$orderInfo['state'];
		$list['ori_cost']=$orderInfo['ori_cost'];
		$list['cost']=$orderInfo['cost'];
		$list['costYuan']=bcdiv($orderInfo['cost'], 100,1);
		$list['postage']=$orderInfo['postage'];
		
		return $list;
	}
	
	
	/**
	 * 用户补全订单其他信息
	 * @param unknown_type $userId
	 * @param unknown_type $orderId
	 * @param unknown_type $addressId
	 * @param unknown_type $payBank
	 * @param unknown_type $ifGiftCard
	 * @param unknown_type $sendWord
	 * @param unknown_type $expressCompanyId
	 */
	public function completeOrder($userId,$orderId, $addressId,$payBank, $sendWord="", $expressCompanyId){
		if(empty($userId) || empty($orderId) || !isset($addressId) || !isset($expressCompanyId))
			return false;
			
		$data['ordernmb']=$orderId;
		$address = M("UserOrderAddress")->getById($addressId);
		$postage = D("PostageStandard")->calculateSelfPackageOrderPostage($orderId, $expressCompanyId, $address["district_area_id"]);
		$data["cost"]=$postage;
		$data['sendword']=$sendWord;
		$data['address_id']=$addressId;
		$data['pay_bank']=$payBank;
		$this->save($data);
	}
	
	/**
	 * 用户支付完毕
	 * @param unknown_type $orderId
	 * @param unknown_type $tradeNumber
	 */
	public function hasPayed($orderId, $tradeNumber, $payTime){
		$order = $this->getOrderInfo($orderId);
		$data["ordernmb"]=$orderId;
		$data["state"]=C("USER_ORDER_STATUS_PAYED");
		if(empty($order["paytime"]) && !empty($payTime)) {
			$data["paytime"]=$payTime;
		}
		if(empty($order["trade_no"]) && !empty($tradeNumber)){
			$data["trade_no"]=$tradeNumber;
		}
		$this->save($data);
		D("UserOrderSendProductdetail")->changeStatus2PostagePayedBySelfPackageOrderId($orderId);
		//@TODOcreate inventoryOut and orderSend record
        D("UserOrder")->createSystemOutInventory($orderId);
	}
	
}