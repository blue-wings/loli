<?php
class UserSelfPackageOrderModel extends Model {
	/**
	 * 创建邮费订单
	 * @param unknown_type $userid
	 * @param unknown_type $productIds
	 * @param unknown_type $productNums
	 * @throws Exception
	 */
	public function createOrder($userId, $productIds, $productNums){
		$result = array();
		$productMsgResult = array();
		$productModel = D("Products");
		if(count($productIds) != count($productNums)){
			throw new Exception("未知错误");
		}
		$data['ordernmb']=date("YmdHis").rand(100,999);
		$data["cost"] = 0;
		$data['userid']=$userId;
		$data['addtime']=date("Y-m-d H:i:s");
		$data['state']=C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED");
		//生成订单
		$this->add($data);
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
	public function complereOrder($userId,$orderId, $addressId,$payBank="",$ifGiftCard=0, $ifPayPostage, $sendWord="", $expressCompanyId){
		if(empty($userId) || empty($orderId) || empty($addressId) || $addressId==0 || empty($ifPayPostage) || empty($expressCompanyId))
			return false;
			
		$data['ordernmb']=$orderId;
		
		$addresInfo=D("UserAddress")->getUserAddressInfo($addressId);
		if($addresInfo==false)
			return false;
		$products = D("UserOrderSendProductdetail")->getUserSelfPackageOrderProducts($orderId);
    	$productIds = array();
    	foreach ($products as $product){
    		array_push($productIds, $product["pid"]);
    	}
			
		$postage = D("PostageStandard")->calculateOrderPostageByAddress($productIds, $expressCompanyId, $addressId);
		$data["cost"]=$postage;
		$data['sendword']=$sendWord;
		$data['address_id']=$addressId;
		$data['pay_bank']=$payBank;
		$orderAddRst = $this->save($data);
	}
	
/**
	 * 用户支付完毕
	 * @param unknown_type $orderId
	 * @param unknown_type $tradeNumber
	 */
	public function hasPayed($orderId, $tradeNumber){
		$order = $this->getOrderInfo($orderId);
		$data["ordernmb"]=$orderId;
		$data["state"]=C("USER_ORDER_STATUS_PAYED");
		$data["trade_no"]=$tradeNumber;
		$this->save($data);
		D("UserOrderSendProductdetail")->changeStatus2PostagePayedBySelfPackageOrderId($orderId);
	}
	
}