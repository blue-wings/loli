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
	
	public function createOrder() {
        $userId = $this->userid;
        $userOrderSendProductDetailIds = $_GET["detailIds"];
        if(!$userOrderSendProductDetailIds){
        	throw new Exception("unknow error");
        }
        $userOrderSendProductDetailIdArray = split(",", $userOrderSendProductDetailIds);
        $userOrderSendProductDetailModel = D("UserOrderSendProductdetail");
        $productIds = array();
        $productNums = array();
        
        foreach ($userOrderSendProductDetailIdArray as $userOrderSendProductDetailId){
        	$detailItem = $shoppingCartModel->getById($userOrderSendProductDetailId);
        	if($detailItem["status"]==C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED")){
				array_push($productIds, $shoppingCartItem["productid"]);
        		array_push($productNums, $shoppingCartItem["product_num"]);        	
        	}
        }
        $result = D("UserSelfPackageOrder")->createOrder( $this->userid, $productIds, $productNums);
        $this->display();
    }
    
    public function ajaxGetPostage(){
    	$orderId = $_POST["orderId"];
    	$expressCompanyId = $_POST("expressCompanyId");
    	$addressId = $_POST("addressId");
    	$products = D("UserOrderSendProductdetail")->getUserSelfPackageOrderProducts($orderId);
    	$productIds = array();
    	foreach ($products as $product){
    		array_push($productIds, $product["pid"]);
    	}
    	$postage = D("PostageStandard")->calculateOrderPostageByAddress($productIds, $expressCompanyId, $addressId);
    	$data["postage"]=$postage;
		$this->ajaxReturn($data, "JSON");    	
    }
    
    
    public function completeOrderAnd2Pay(){
    	$orderId = $_POST["orderId"];
        $sendWord = $_POST("send_word");
        import("ORG.Util.String");
        if(mb_strlen($sendWord,"utf8") > 200){
            $sendWord=String::msubstr($sendWord ,0,200,'utf-8',false);
        }
        $expressCompanyId = $_POST("expressCompanyId");
        $ifUseGiftCard = $_POST("ifUseGiftCard");
        $payBank = $_POST("payBank");
        $addressId = $_POST("addressId");
        $ifGiftCard = $_POST("ifGiftCard");
        $ifPayPostage = $_POST("ifPayPostage");
        $result = D("UserSelfPackageOrder")->complereOrder($this->userid,$orderId, $addressId,$payBank,$ifGiftCard,$ifPayPostage, $sendWord, $expressCompanyId);
    	if($result){
        	//TODO跳转到第三方支付	
        }
    }
    
 	public function hasPayed(){
    	$orderId = $_POST["orderId"];
        $tradeNum = $_POST("tradeNum");
        D("UserSelfPackageOrder")->hasPayed($orderId, $tradeNum);
    }
	
	public function autoDelivery(){
		
		
	
	}
	
}