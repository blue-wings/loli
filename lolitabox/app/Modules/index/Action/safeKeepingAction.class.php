<?php
class safeKeepingAction extends commonAction{
	
	public function selfPickUp(){
		$userOrderSendProductDetail =M("UserOrderSendProductdetail");	
		$inventoryItem = M("InventoryItem");
		$userid = $this->userid;
		import("ORG.Util.Page");
 		$productDetailsCount = $userOrderSendProductDetail->where("status=0 and userid=".$userid)->count();
 		$p = new Page($productDetailsCount,12);
 		if($productDetailsCount){
 			$productDetails = $userOrderSendProductDetail->where("status=0 and userid=".$userid)->order("orderid DESC")->limit($p->firstRow.','.$p->listRows)->select();
 			$product = M("Products");
 			for($i=0; $i<count($productDetails); $i++){
 				$productId = $productDetails[$i]["productid"];
 				$productRecord = $product->where("pid=".$productId)->find();
 				$productDetails[$i]["product"]= $productRecord;
 				$inventoryItemRecord = $inventoryItem->where("id=".$productRecord["inventory_item_id"])->find();
				$productDetails[$i]["inventoryItem"]= $inventoryItemRecord;
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