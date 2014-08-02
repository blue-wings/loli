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
  		$this->display();
	}
	
	public function selectedDetail(){
		$productDetailIds = $_POST["productDetailIds"];
	}
	
	public function createOrder() {
        $userId = $this->userid;
        $userOrderSendProductDetailIdArray = $_POST["detailIds"];
        if(!$userOrderSendProductDetailIdArray){
        	throw new Exception("生成订单出错");
        }
        $userOrderSendProductDetailModel = D("UserOrderSendProductdetail");
        $productIds = array();
        $productNums = array();
        
        foreach ($userOrderSendProductDetailIdArray as $userOrderSendProductDetailId){
        	$detailItem = D("UserOrderSendProductdetail")->getById($userOrderSendProductDetailId);
        	if($detailItem["status"]==C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED")){
				array_push($productIds, $shoppingCartItem["productid"]);
        		array_push($productNums, $shoppingCartItem["product_num"]);        	
        	}
        }
        $orderId = D("UserSelfPackageOrder")->createOrder( $this->userid, $userOrderSendProductDetailIdArray);
        $this->redirect("safeKeeping/getOrder2Complete", array("orderId"=>$orderId));
    }
    
    public function getOrder2Complete(){
    	$orderId = $_GET["orderId"];
    	if(!$orderId){
    		$this->error("获取订单信息失败");
    	}
    	$order = D("UserSelfPackageOrder")->getOrderDetail($orderId);
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