<?php
class safeKeepingAction extends commonAction{
	
	public function selfPickUp(){
		$userOrderSendProductDetail =M("UserOrderSendProductdetail");	
		$total = $userOrderSendProductDetail->where("status=0")->count();
		$userid = $this->userid;
		import("ORG.Util.Page");
		$p = new Page($count,10);
		$productDetails = $userOrderSendProductDetail->where("status=0")->order("orderid DESC")->limit($p->firstRow.','.$p->listRows)->select();
		$product = M("Products");
		for($i=0; $i<count($productDetails); $i++){
			$productId = $productDetails[$i]["productId"];
			$productDetails[$i]["product"]=$product->where("pid=".$pid)->find();
		}
		$this->assign("productDetails",$productDetails);
	}

	
	public function autoDelivery(){
		
		
	
	}
	
}