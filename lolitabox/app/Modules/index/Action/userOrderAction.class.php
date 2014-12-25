<?php
class userOrderAction extends commonAction {

    /**
     * 以购物车为依据创建订单
     */
    public function createOrder() {
        $logTag = MODULE_NAME."-".ACTION_NAME;
        $userId = $this->userid;
        $shoppingCartIds = $_POST["shoppingCartIds"];
        if(!$shoppingCartIds){
            eLog($logTag,$this->userid," 创建订单失败","提交的购物车为空", ERROR);
        	$this->error("创建订单失败");
        }
        $shoppingCartIdArray = split(",", $shoppingCartIds);
    	if(!$shoppingCartIdArray || !count($shoppingCartIdArray)){
            eLog($logTag,$this->userid," 创建订单失败","提交的购物车为空", ERROR);
        	$this->error("创建订单失败");exit;
        }
        
        $shoppingCartModel = D("ShoppingCart");
        $productIds = array();
        $productNums = array();
        
        foreach ($shoppingCartIdArray as $shopingCartId){
        	$shoppingCartItem = $shoppingCartModel->getById($shopingCartId);
            if($shoppingCartItem["userid"] != $this->userid){
                eLog($logTag,$this->userid,"创建订单失败"," 提交的购物车不是自己的", ERROR);
                $this->error("非法创建订单");exit;
            }
        	if($shoppingCartItem["status"]==C("SHOPPING_CART_STATUS_VALID")){
				array_push($productIds, $shoppingCartItem["productid"]);
        		array_push($productNums, $shoppingCartItem["product_num"]);        	
        	}else{
                eLog($logTag,$this->userid,"创建订单失败","购物车已过期 ", ERROR);
        		$this->error("创建订单失败,购物车已过期");exit;
        	}
        }
        try{
            $orderId = D("UserOrder")->createOrder( $this->userid, $productIds, $productNums);
            $shoppingCartModel->invalidUserShoppingCart($shoppingCartIdArray);
        }catch (Exception $e){
            eLog($logTag,$this->userid,"创建订单失败",$e->getMessage(), ERROR);
            $this->error("创建订单失败,".$e->getMessage());exit;
        }
        eLog($logTag,$this->userid,"创建订单成功",$orderId, INFO);
        $this->redirect("userOrder/getOrder2Complete", array("orderId"=>$orderId));
    }
    
    public function getOrder2Complete(){
        $logTag = MODULE_NAME."-".ACTION_NAME;
    	$orderId = $_GET["orderId"];	
    	if(!$orderId){
            eLog($logTag,$this->userid,"获取订单失败","orderId 为空", ERROR);
    		$this->error("获取订单信息失败");
    	}
    	$order = D("UserOrder")->getOrderDetail($orderId);
        if($order["ifavalid"]==C("ORDER_IFAVALID_OVERDUE")){
            eLog($logTag,$this->userid,"获取订单失败","订单已失效", ERROR);
            $this->error("订单已失效");
        }
        if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED")){
            eLog($logTag,$this->userid,"获取订单失败","订单状态异常", ERROR);
            $this->error("订单状态异常");
        }
    	if($order["userid"] != $this->userid){
            eLog($logTag,$this->userid,"获取订单失败","非法获取订单信息", ERROR);
    		$this->error("非法获取订单信息");
    	}
    	$this->assign("order", $order);
    	$this->assign("ExpressCompanies", array(C("EXPRESS_SHENTONG_ID"), C("EXPRESS_SHUNFENG_ID")));
    	$userOrderAddresses = M("UserOrderAddress")->where(array("userid"=>$this->userid))->order("if_active DESC, id DESC")->select();
    	if($userOrderAddresses){
	    	foreach ($userOrderAddresses as $key=>$userOrderAddress){
	    		$userOrderAddress["provinceName"]=M("area")->field("title")->getByAreaId($userOrderAddress["province_area_id"]);
	    		$userOrderAddress["provinceName"]=$userOrderAddress["provinceName"]["title"];	
	    		$userOrderAddress["cityName"]=M("area")->field("title")->getByAreaId($userOrderAddress["city_area_id"]);
	    		$userOrderAddress["cityName"]=$userOrderAddress["cityName"]["title"];
	    		$userOrderAddress["districtName"]=M("area")->field("title")->getByAreaId($userOrderAddress["district_area_id"]);	
	    		$userOrderAddress["districtName"]=$userOrderAddress["districtName"]["title"];
                $shenTongPostage = $this->getPostage($orderId, C("EXPRESS_SHENTONG_ID")["id"], $userOrderAddress["district_area_id"]);
                $userOrderAddress["shenTongPostage"]=bcdiv($shenTongPostage, 100, 2);
                $userOrderAddress["shenTongTotalCost"]=bcdiv(($order["cost"]+$shenTongPostage), 100 , 2);
                $shunfengPostage = $this->getPostage($orderId, C("EXPRESS_SHUNFENG_ID")["id"], $userOrderAddress["district_area_id"]);
                $userOrderAddress["shunfengPostage"]=bcdiv($shunfengPostage, 100, 2);
                $userOrderAddress["shunfengTotalCost"]=bcdiv(($order["cost"]+$shunfengPostage), 100 , 2);
	    		$userOrderAddresses[$key]=$userOrderAddress;
	    	}
    	}
        $this->assign("oldAdresses", $userOrderAddresses);

        if($userOrderAddresses && count($userOrderAddresses)){
            $this->assign("defaultAddress", $userOrderAddresses[0]);
        }

    	//优惠券余额
        $giftCardRemain =$this->userinfo["balance"];
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
        $logTag = MODULE_NAME."-".ACTION_NAME;
    	$orderId = $_POST["orderId"];
    	$order = D("UserOrder")->getOrderDetail($orderId);
    	if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED") || $order["ifavalid"]==C("ORDER_IFAVALID_OVERDUE")){
            eLog($logTag,$this->userid,"补全订单失败","订单状态异常", ERROR);
    		$this->error("订单状态异常");
    	}
        if($order["userid"] != $this->userid){
            eLog($logTag,$this->userid,"补全订单失败","非法获取订单信息", ERROR);
            $this->error("非法获取订单信息");
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
        if(!isset($orderId) || !isset($ifPayPostage)){
            eLog($logTag,$this->userid,"补全订单失败","订单信息不完整", ERROR);
            $this->error("订单信息不完整");
        }
        try{
            $needGoToPayGateway = D("UserOrder")->completeOrder($this->userid,$orderId, $addressId,$payBank,$ifUseGiftCard,$ifPayPostage, $sendWord, $expressCompanyId);
            if ($needGoToPayGateway){
                $this->redirect("userOrder/getCompleteOrder2Pay", array("orderId"=>$orderId));
            }else{
                $this->redirect("userOrder/paySuccess");
            }
        }catch (Exception $e){
            eLog($logTag,$this->userid,"补全订单失败",$e->getMessage(), ERROR);
            $this->error("提交订单失败,".$e->getMessage());exit;
        }
        eLog($logTag,$this->userid,"补全订单成功",$orderId, INFO);
    }
    
    public function getCompleteOrder2Pay(){
        $logTag = MODULE_NAME."-".ACTION_NAME;
    	$orderId = $_GET["orderId"];
    	$order = D("UserOrder")->getOrderInfo($orderId);
    	if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED")){
            eLog($logTag,$this->userid,"获取订单支付失败","订单状态异常", ERROR);
    		$this->error("订单状态异常");
    	}
        $this->assign("order", $order);
    	$userOrderAddresses = M("UserOrderAddress")->getById($order["address_id"]);
	    $provinceName=M("area")->where(array("area_id"=>$userOrderAddresses["province_area_id"]))->getField("title");
	    $cityName=M("area")->where(array("area_id"=>$userOrderAddresses["city_area_i"]))->getField("title");
	    $districtName=M("area")->where(array("area_id"=>$userOrderAddresses["district_area_id"]))->getField("title");
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