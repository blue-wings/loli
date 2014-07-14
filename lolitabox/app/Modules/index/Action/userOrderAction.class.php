<?php
class UserOrderAction extends commonAction {
	
    public function createOrder() {
        $userId = $this->userid;
        $shoppingCartIds = $_POST("shoppingCartIds");
        if(!$shoppingCartIds){
        	throw new Exception("unknow error");
        }
        $shoppingCartIdArray = split(",", $shoppingCartIds);
        $shoppingCartModel = D("ShoppingCart");
        $productIds = array();
        $productNums = array();
        
        foreach ($shoppingCartIdArray as $shopingCartId){
        	$shoppingCartItem = $shoppingCartModel->getById($shopingCartId);
        	array_push($productIds, $shoppingCartItem["productid"]);
        	array_push($productNums, $shoppingCartItem["product_num"]);
        }
        $result = D("UserOrder")->createOrder( $this->userid, $productIds, $productNums);
        $orderId = $result["orderId"];
        $productMsgResult = $result["msgResult"];
        $errorProducts = array();
        foreach ($productMsgResult as $productId=>$msg){
        	if(!msg){
        		$shoppingCartModel->invalidUserShoppingCartProduct($userId);	
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