<?php
class ShoppingCartAction extends commonAction {
	
	public function addProduct2Cart(){
		$userid = $this->userid;
		$productId = $_POST["pid"];
		$product = D("Products")->getByPid($productId);
		$data["result"]=true;
		if(!$product){
			$data["result"]=false;
			$data["msg"]="商品不存在";
		}else if(!$_POST["pNum"]){
			$data["result"]=false;
			$data["msg"]="请填写商品数目";
		}else if($product["end_time"] < date("Y-m-d H:i:s")){
			$data["result"]=false;
			$data["msg"]="商品已经下架";
		}else if($product["status"] != C("PRODUCT_STATUS_PUBLISHED")){
			$data["result"]=false;
			$data["msg"]="商品已经下架";
		}else if($product["inventory"] < $product["inventoryreduced"]){
			$data["result"]=false;
			$data["msg"]="商品已售空";	
		}else if(!$data["result"]){
			$this->ajaxReturn($data, "JSON");
		}
		$shoppingCartModel = D("ShoppingCart");
		try{
			$shoppingCartModel->addProdcutToCart($userid, $productId, $_POST["pNum"]);
		}catch (Exception $e){
			$data["result"]=false;
			$data["msg"]=$e->getMessage();	
		}
		$this->ajaxReturn($data, "JSON");
	}
	
	/**
	 * 提交购物车，更新购物车记录，跳转到创建订单页面
	 */
	public function update(){
		$shoppingCartIds = $_POST["shoppingCartIds"];
		$shoppingCartProductNums = $_POST["productNums"];
		if(!$shoppingCartIds ||!$shoppingCartProductNums || count($shoppingCartIds) != count($shoppingCartProductNums)){
			$this->error("购物车异常");exit;
		}
		$shoppingCartIdArray = split(",", $shoppingCartIds);
		$shoppingCartProductNumArray = split(",", $shoppingCartProductNums);
		for($i=0; $i<count($shoppingCartIdArray); $i++){
			$shoppingCartId = $shoppingCartIdArray[$i];
			$shoppingCartProductNum = $shoppingCartProductNumArray[$i];
			$data["id"]=$shoppingCartId;
			$data["product_num"]=$shoppingCartProductNum;
			D("ShoppingCart")->save($data);	
		}
		$this->redirect("userOrder/createOrder",array("shoppingCartIds"=>$shoppingCartIds));		
	}
	
	public function detail(){
		$shoppingCartItems = D("ShoppingCart")->getProductList($this->userid);
		$weight=0;
		$productTotalNum = 0;
		$totalCost = 0;
		foreach ($shoppingCartItems as $key=>$shoppingCartItem){
			if($this->userinfo['if_member'] && $shoppingCartItem["product"]["member_price"]){
				$shoppingCartItem["product"]["realPrice"]=bcdiv($shoppingCartItem["product"]["member_price"], 100, 1);
				$totalCost += $shoppingCartItem["product"]["member_price"];
			}else{
				$shoppingCartItem["product"]["realPrice"]=bcdiv($shoppingCartItem["product"]["price"], 100, 1);
				$totalCost += $shoppingCartItem["product"]["price"];
			}
			$userCanBuyNum = D("Products")->getRemainProdcutNumPerUserOrder($shoppingCartItem["product"]["pid"], $this->userid);
			$shoppingCartItem["product"]["userCanBuyNum"]= $userCanBuyNum;
			$shoppingCartItems[$key]=$shoppingCartItem;
			
			$inventoryItem = D("InventoryItem")->getById($shoppingCartItem["product"]["inventory_item_id"]);
			$weight = bcadd($weight, $inventoryItem["weight"]);
			$weight = bcmul($weight, $shoppingCartItem["product_num"]);
			$totalCost = bcmul($totalCost, $shoppingCartItem["product_num"]);
			$productTotalNum += $shoppingCartItem["product_num"];
		}
		$weight = bcdiv($weight, 1000, 3);
		$totalCost = bcdiv($totalCost, 100, 1);
		$this->assign("shoppingCartItems", $shoppingCartItems);
		$this->assign("productTotalNum", $productTotalNum);
		$this->assign("weight", $weight);
		$this->assign("totalCost", $totalCost);
		$this->display();
	}
	
	/**
	 * 配置cron，定期清理购物车
	 */
	public function cronClearCart(){
		D("ShoppingCart")->setOutOfTimeRecordInvalid();
	}
	
}