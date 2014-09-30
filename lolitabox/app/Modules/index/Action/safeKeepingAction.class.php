<?php
class safeKeepingAction extends commonAction{
	
	public function selfPickUp(){
		$userOrderSendProductDetail =M("UserOrderSendProductdetail");	
		$inventoryItem = M("InventoryItem");
		$userid = $this->userid;
		import("ORG.Util.Page");
		$where["status"]=C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED");
		$where["userid"]=$userid;
 		$productDetailsCount = $userOrderSendProductDetail->where($where)->count();
 		$p = new Page($productDetailsCount,12);
 		if($productDetailsCount){
 			$productDetails = $userOrderSendProductDetail->where($where)->order("orderid DESC")->limit($p->firstRow.','.$p->listRows)->select();
 			$product = M("Products");
 			for($i=0; $i<count($productDetails); $i++){
 				$productId = $productDetails[$i]["productid"];
 				$productRecord = $product->where("pid=".$productId)->find();
 				$productDetails[$i]["product"]= $productRecord;
 				$inventoryItemRecord = $inventoryItem->where("id=".$productRecord["inventory_item_id"])->find();
 				$inventoryItemRecord["weightKg"]=bcdiv($inventoryItemRecord["weight"], 1000, 3);
				$productDetails[$i]["inventoryItem"]= $inventoryItemRecord;
 			}
 			$this->assign("productDetails",$productDetails);
 			$this->assign("page",$p->show());
  		}
 		$this->assign("productDetailsCount",$productDetailsCount);
        $this->assign("selfPickUpSelect","select");
  		$this->display();
	}
	
	public function createOrder() {
        $userOrderSendProductDetailIdArray = $_POST["detailIds"];
        if(!$userOrderSendProductDetailIdArray || !count($userOrderSendProductDetailIdArray)){
            $this->error("生成订单出错");
        }
        try{
            $orderId = D("UserSelfPackageOrder")->createOrder( $this->userid, $userOrderSendProductDetailIdArray);
            $this->redirect("safeKeeping/getOrder2Complete", array("orderId"=>$orderId));
        }catch (Exception $e){
            $this->error("提取货物出错,".$e->getMessage());
        }
    }
    
    public function getOrder2Complete(){
    	$orderId = $_GET["orderId"];
    	if(!$orderId){
    		$this->error("获取订单信息失败");
    	}
    	$order = D("UserSelfPackageOrder")->getOrderDetail($orderId);
        if($order["ifavalid"]==C("ORDER_IFAVALID_OVERDUE")){
            $this->error("订单已失效");
        }
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
    	$postage = D("PostageStandard")->calculateSelfPackageOrderPostage($orderId, $expressCompanyId, $addressId);
    	return $postage;
    }
    
 	public function completeOrder(){
    	$orderId = $_POST["orderId"];
    	$order = D("UserSelfPackageOrder")->getOrderInfo($orderId);
    	if($order["state"] != C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED")){
    		$this->error("订单状态异常");
    	}
        if($order["userid"] != $this->userid){
            $this->error("非法获取订单信息");
        }
        $sendWord = $_POST["send_word"];
        import("ORG.Util.String");
        if(mb_strlen($sendWord,"utf8") > 200){
            $sendWord=String::msubstr($sendWord ,0,200,'utf-8',false);
        }
        $expressCompanyId = $_POST["expressCompanyId"];
        $payBank = $_POST["pay_bank"];
        $addressId = $_POST["addressId"];
        try{
            D("UserSelfPackageOrder")->completeOrder($this->userid,$orderId, $addressId,$payBank,$sendWord, $expressCompanyId);
            $this->redirect("safeKeeping/getCompleteOrer2Pay", array("orderId"=>$orderId));
        }catch (Exception $e){
            $this->error("提取货物失败,".$e->getMessage());exit;
        }
    }
    
    public function getCompleteOrer2Pay(){
    	$orderId = $_GET["orderId"];
    	$order = D("UserSelfPackageOrder")->getOrderInfo($orderId);
    	if($order["state"] != C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED")){
    		$this->error("订单状态异常");
    	}
        $this->assign("order", $order);
    	$userOrderAddresses = M("UserOrderAddress")->getById($order["address_id"]);
	    $provinceName=M("area")->where(array("area_id"=>$userOrderAddresses["province_area_id"]))->getField("title");
	    $cityName=M("area")->where(array("area_id"=>$userOrderAddresses["city_area_i"]))->getField("title");
	    $districtName=M("area")->where(array("area_id"=>$userOrderAddresses["district_area_id"]))->getField("title");
	    $addressNote = $userOrderAddresses["linkman"].",".$userOrderAddresses["telphone"].",".$provinceName.$cityName.$districtName.$userOrderAddresses["address"]."(".$userOrderAddresses["postcode"].")";
	    $this->assign("addressNote", $addressNote);
		$priceYuan = bcdiv($order["cost"], 100, 2);
	    $this->assign("priceYuan", $priceYuan);
        $this->display();
    }
    
	public function gopay($orderId, $repay){
		header("Content-type: text/html; charset=utf-8");
		$orderinfo=D("UserSelfPackageOrder")->getOrderInfo($orderId);
		//未支付订单再次支付
		if($repay){
				
		}else{
			//正常去支付
			$name="我的订阅";
			$pay_bank=$orderinfo['pay_bank'];
			$priceFen = $orderinfo["cost"];
			$priceYuan = bcdiv($priceFen, 100, 2);
			echo "<form name=\"form1\" method=\"post\" id=\"form1\" action=\"".U('safeKeepingPay/alipayto')."\" >\r\n";
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