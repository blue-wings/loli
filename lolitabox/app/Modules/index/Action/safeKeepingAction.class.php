<?php
class safeKeepingAction extends commonAction{
	
	public function selfPickUp(){
		$userOrderSendProductDetail =M("UserOrderSendProductdetail");	
		$total = $userOrderSendProductDetail->where("status=0")->count();
		$userid = $this->userid;
		import("ORG.Util.Page");
		$p = new Page($total,10);
		$productDetails = $userOrderSendProductDetail->where("status=0")->order("orderid DESC")->limit($p->firstRow.','.$p->listRows)->select();
		$product = M("Products");
		$inventoryItem = M("InventoryItem");
		for($i=0; $i<count($productDetails); $i++){
			$productId = $productDetails[$i]["productid"];
			$productRecord = $product->where("pid=".$productId)->find();
			$productDetails[$i]["product"]= $productRecord;
			$inventoryItemRecord = $inventoryItem->where("id=".$productRecord["inventory_item_id"])->find();
				$productDetails[$i]["inventoryItem"]= $inventoryItemRecord;
		}
		$this->assign("productDetails",$productDetails);
		$page=$p->show();	
		$this->assign('page',$page);
		$this->display();
	}

	
	public function autoDelivery(){
		
		
	
	}
	
}