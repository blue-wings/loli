<?php
class userOrderAction extends commonAction {
	
    public function createOrder() {
        $userId = $this->userid;
        $shoppingCartIds = $_POST["shoppingCartIds"];
        if(!$shoppingCartIds){
        	$this->error("创建订单失败");
        }
        $shoppingCartIdArray = split(",", $shoppingCartIds);
    	if(!$shoppingCartIdArray || !count($shoppingCartIdArray)){
        	$this->error("创建订单失败");
        }
        
        $shoppingCartModel = D("ShoppingCart");
        $productIds = array();
        $productNums = array();
        
        foreach ($shoppingCartIdArray as $shopingCartId){
        	$shoppingCartItem = $shoppingCartModel->getById($shopingCartId);
        	if($shoppingCartItem["status"]==C("SHOPPING_CART_STATUS_VALID")){
				array_push($productIds, $shoppingCartItem["productid"]);
        		array_push($productNums, $shoppingCartItem["product_num"]);        	
        	}else{
        		$this->error("创建订单失败");
        	}
        }
        $result = D("UserOrder")->createOrder( $this->userid, $productIds, $productNums);
        $orderId = $result["orderId"];
        $productMsgResult = $result["msgResult"];
        $errorProducts = array();
        foreach ($productMsgResult as $productId=>$msg){
        	if(!$msg){
        		$shoppingCartModel->invalidUserShoppingCartProduct($userId, $productId);	
        	}else{
        		$product = D("Products")->getByPid($productId);
        		$product["errorMsg"]=$msg;
        		array_push($errorProducts, $product);
        	}
        }
        $order = D("UserOrder")->getOrderDetail($orderId);
        $this->assign("order",$order);
        $this->assign("errorProducts", $errorProducts);
        $this->display();
    }
    
    public function ajaxGetPostage(){
    	$orderId = $_POST["orderId"];
    	$expressCompanyId = $_POST("expressCompanyId");
    	$addressId = $_POST("addressId");
    	$products = D("UserOrderSendProductdetail")->getUserOrderProducts($orderId);
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
        $result = D("UserOrder")->complereOrder($this->userid,$orderId, $addressId,$payBank,$ifGiftCard,$ifPayPostage, $sendWord, $expressCompanyId);
    	if($result){
        	//TODO跳转到第三方支付	
        }
    }
    
 	public function hasPayed(){
    	$orderId = $_POST["orderId"];
        $tradeNum = $_POST("tradeNum");
        D("UserOrder")->hasPayed($orderId, $tradeNum);
    }
    
}