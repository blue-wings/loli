<?php
class safeKeepingAction extends commonAction{
	
	public function selfPickUp(){
		$userid = $this->userid;
		$userOrderSendProductDetail =M("UserOrderSendProductdetail");	
		$total = $userOrderSendProductDetail->where("status=0")->count();
		$userid = $this->userid;
		import("ORG.Util.Page");
		$p = new Page($count,12);
		$productDetailsCount = $userOrderSendProductDetail->where("status=0 and userid=".$userid)->count();
		if($productDetailsCount){
			$productDetails = $userOrderSendProductDetail->where("status=0 and userid=".$userid)->order("orderid DESC")->limit($p->firstRow.','.$p->listRows)->select();
			$product = M("Products");
			for($i=0; $i<count($productDetails); $i++){
				$productId = $productDetails[$i]["productid"];
				$productRecord = $product->where("pid=".$productId)->find();
				$productDetails[$i]["product"]= $productRecord;
			}
			$this->assign("productDetails",$productDetails);
			$this->assign("page",$p->show());
		}
		$this->assign("productDetailsCount",$productDetailsCount);
		$this->display();
	}

	
	public function autoDelivery(){
		
		
	
	}
	
}