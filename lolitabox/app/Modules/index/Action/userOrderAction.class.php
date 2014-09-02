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
        Log::write($userId."create order ".$orderId,CRIT);
        $shoppingCartModel->invalidUserShoppingCart($shoppingCartIdArray);
        $this->redirect("userOrder/getOrder2Complete", array("orderId"=>$orderId));
    }
    
    public function getOrder2Complete(){
    	$orderId = $_GET["orderId"];	
    	if(!$orderId){
    		$this->error("获取订单信息失败");
    	}
    	$order = D("UserOrder")->getOrderDetail($orderId);
    	if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED")){
    		$this->error("订单状态异常");
    	}
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
    		$this->assign("totalCost", bcdiv(($order["cost"]+$postage), 100 , 2));
    	}
    	//优惠券余额
        $giftCardRemain =D("Giftcard")->getUserGiftcardPrice($this->userid);
        $giftCardRemain = bcdiv($giftCardRemain, 100, 2);
        $this->assign("giftCardRemain", $giftCardRemain);
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
    
    
    public function completeOrder(){
    	$orderId = $_POST["orderId"];
    	$order = D("UserOrder")->getOrderDetail($orderId);
    	if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED")){
    		$this->error("订单状态异常");
    	}
        $sendWord = $_POST["send_word"];
        import("ORG.Util.String");
        if(mb_strlen($sendWord,"utf8") > 200){
            $sendWord=String::msubstr($sendWord ,0,200,'utf-8',false);
        }
        $expressCompanyId = $_POST["expressCompanyId"];
        $ifUseGiftCard = $_POST["ifUseGiftCard"];
        $payBank = $_POST["pay_bank"];
        $addressId = $_POST["addressId"];
        $ifPayPostage = $_POST["ifPayPostage"];
        $needGoToPayGateway = D("UserOrder")->completeOrder($this->userid,$orderId, $addressId,$payBank,$ifUseGiftCard,$ifPayPostage, $sendWord, $expressCompanyId);
        if(!$needGoToPayGateway){
			$this->redirect("userOrder/paySuccess");
        }else{
        	$this->redirect("userOrder/getCompleteOrer2Pay", array("orderId"=>$orderId));
        }
    }
    
    public function getCompleteOrer2Pay(){
    	$orderId = $_GET["orderId"];
    	$order = D("UserOrder")->getOrderInfo($orderId);
    	if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED")){
    		$this->error("订单状态异常");
    	}
        $this->assign("order", $order);
    	$userOrderAddresses = M("UserOrderAddress")->getById($order["address_id"]);
	    $provinceName=M("area")->where(array("area_id"=>$userOrderAddress["province_area_id"]))->getField("title");
	    $cityName=M("area")->where(array("area_id"=>$userOrderAddress["city_area_i"]))->getField("title");
	    $districtName=M("area")->where(array("area_id"=>$userOrderAddress["district_area_id"]))->getField("title");
	    $addressNote = $userOrderAddresses["linkman"].",".$userOrderAddresses["telphone"].",".$provinceName.$cityName.$districtName.$userOrderAddresses["address"]."(".$userOrderAddresses["postcode"].")";
	    $this->assign("addressNote", $addressNote);
	    $priceFen = $order["cost"] + $order["postage"] - $order["giftcard"];
		$priceYuan = bcdiv($priceFen, 100, 2);
	    $this->assign("priceYuan", $priceYuan);
        $this->display();
    }
    
	public function gopay($orderId, $repay){
		header("Content-type: text/html; charset=utf-8");
		$user_order_mod=D("UserOrder");
		$orderinfo=$user_order_mod->getOrderInfo($orderId);
		//未支付订单再次支付
		if($repay){
				
		}else{
			//正常去支付
			$name="我的订阅";
			$pay_bank=$orderinfo['pay_bank'];
			$priceFen = $orderinfo["cost"] + $orderinfo["postage"] - $orderinfo["giftcard"];
			$priceYuan = bcdiv($priceFen, 100, 2);
			echo "<form name=\"form1\" method=\"post\" id=\"form1\" action=\"".U('pay/alipayto')."\" >\r\n";
			echo "<input type=\"hidden\" name=\"ordernmb\" value=\"".$orderId."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"total_fee\" value=\"".$priceYuan."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"subject\" value=\""."我订阅的萝莉盒产品"."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"body\" value=\""."我订阅的萝莉盒产品"."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"pay_bank\" value=\"".$orderinfo["pay_bank"]."\"/>\r\n";
			echo "<input type=\"submit\" name=\"submit1\" style=\"display:none\"/>";
			echo "</form>\r\n";
			echo "<script>\r\n";
			echo " if ((navigator.userAgent.indexOf('MSIE') >= 0) && (navigator.userAgent.indexOf('Opera') < 0)){ \r\n";
			echo "	document.form1.submit(); \r\n";
			echo "}else if (navigator.userAgent.indexOf('Firefox') >= 0){ \r\n";
			echo "	document.form1.submit1.click(); \r\n";
			echo "}else if (navigator.userAgent.indexOf('Opera') >= 0){ \r\n";
			echo "	document.form1.submit();";
			echo "}else{";
			echo "	document.form1.submit();";
			echo "}";
			echo "</script>";
			exit();
		}
	}
	
	public function paySuccess(){
		$this->display();
	}
	
	public function payFailed(){
		$this->display();
	}
    
    
}