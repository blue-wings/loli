<?php
class userOrderAction extends commonAction {
	
    public function createOrder() {
        $userId = $this->userid;
        $shoppingCartIds = $_POST["shoppingCartIds"];
        if(!$shoppingCartIds){
        	$this->error("创建订单失败");exit;
        }
        $shoppingCartIdArray = split(",", $shoppingCartIds);
    	if(!$shoppingCartIdArray || !count($shoppingCartIdArray)){
        	$this->error("创建订单失败");exit;
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
        		$this->error("创建订单失败");exit;
        	}
        }
        $orderId = D("UserOrder")->createOrder( $this->userid, $productIds, $productNums);
        $shoppingCartModel->invalidUserShoppingCart($shoppingCartIdArray);
        $this->redirect("userOrder/getOrder2Complete", array("orderId"=>$orderId));
    }
    
    public function getOrder2Complete(){
    	$orderId = $_GET["orderId"];	
    	if(!$orderId){
    		$this->error("获取订单信息失败");
    	}
    	$order = D("UserOrder")->getOrderDetail($orderId);
    	if($order["userid"] != $this->userid){
    		$this->error("非法获取订单信息");
    	}
    	$this->assign("order", $order);
    	$this->assign("ExpressCompanies", array(C("EXPRESS_SHENTONG_ID"), C("EXPRESS_SHUNFENG_ID")));
    	$userOrderAddresses = M("UserOrderAddress")->where(array("userid"=>$this->userid))->order("id DESC")->select();
    	if($userOrderAddresses){
	    	foreach ($userOrderAddresses as $key=>$userOrderAddress){
	    		$userOrderAddress["provinceName"]=M("area")->field("title")->getByAreaId($userOrderAddress["province_area_id"]);
	    		$userOrderAddress["provinceName"]=$userOrderAddress["provinceName"]["title"];	
	    		$userOrderAddress["cityName"]=M("area")->field("title")->getByAreaId($userOrderAddress["city_area_id"]);
	    		$userOrderAddress["cityName"]=$userOrderAddress["cityName"]["title"];
	    		$userOrderAddress["districtName"]=M("area")->field("title")->getByAreaId($userOrderAddress["district_area_id"]);	
	    		$userOrderAddress["districtName"]=$userOrderAddress["districtName"]["title"];
	    		$userOrderAddresses[$key]=$userOrderAddress;
	    	}
    	}
    	if(count($userOrderAddresses)){
    		$this->assign("oldAdresses", $userOrderAddresses);
    		$firstExpressCompany = C("EXPRESS_SHENTONG_ID");
    		$postage =$this->getPostage($orderId, $firstExpressCompany["id"], $userOrderAddresses[0]["district_area_id"]);
    		$this->assign("postage", bcdiv($postage, 100, 2));
    		$this->assign("totalCost", bcdiv(($order["cost"]+$postage), 100 , 1));
    	}
    	$this->display();
    }
    
    public function ajaxGetPostage(){
    	$orderId = $_POST["orderId"];
    	$expressCompanyId = $_POST("expressCompanyId");
    	$areaId = $_POST("areaId");
    	$data["postage"]=$this->getPostage($orderId, $expressCompanyId, $areaId);
		$this->ajaxReturn($data, "JSON");    	
    }
    
    private function getPostage($orderId, $expressCompanyId, $addressId){
    	$postage = D("PostageStandard")->calculateOrderPostage($orderId, $expressCompanyId, $addressId);
    	return $postage;
    }
    
    
    public function completeOrderAnd2Pay(){
    	$orderId = $_POST["orderId"];
        $sendWord = $_POST["send_word"];
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