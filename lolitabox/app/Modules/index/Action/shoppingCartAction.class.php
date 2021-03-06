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
		}
		$remainNum = D("Products")->getRemainProductNumPerUserOrder($productId, $this->userid);
		$shoppingCartModel = D("ShoppingCart");
		$shoppingCart = $shoppingCartModel->get($this->userid, $productId);
		$shoppingCartProductNum = 0;
		if($shoppingCart){
			$shoppingCartProductNum = $shoppingCart["product_num"];
		}
		if(($shoppingCartProductNum+ intval($_POST["pNum"]))>$remainNum){
			$data["result"]=false;
			$data["msg"]="购买受限";		
		}
		if(!$data["result"]){
			$this->ajaxReturn($data, "JSON");
		}
		
		try{
			$shoppingCartModel->addProductToCart($userid, $productId, $_POST["pNum"]);
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
		$data["result"]=true;
		if(!$shoppingCartIds ||!$shoppingCartProductNums || count($shoppingCartIds) != count($shoppingCartProductNums)){
			$data["result"]=false;
			$data["msg"]="购物车异常,请刷新页面后重新提交";	
		}
		try{
			$shoppingCartIdArray = split(",", $shoppingCartIds);
			$shoppingCartProductNumArray = split(",", $shoppingCartProductNums);
			
			for($i=0; $i<count($shoppingCartIdArray); $i++){
				$shoppingCartId = $shoppingCartIdArray[$i];
				$shoppingCart = D("ShoppingCart")->getById($shoppingCartId);
				if(!$shoppingCart){
					$data["result"]=false;
					$data["msg"]="购物车异常,请刷新页面后重新提交";	
				}
				$shoppingCartProductNum = $shoppingCartProductNumArray[$i];
				$shopingCartProductId = $shoppingCart["productid"];
				$remainNum = D("Products")->getRemainProductNumPerUserOrder($shopingCartProductId, $this->userid);
				if(($shoppingCartProductNum+$shoppingCart["productNum"])>$remainNum){
					$product = D("Products")->getByPid($shopingCartProductId);
					$data["result"]=false;
					$data["msg"]="购买受限";
					if($remainNum){
						$data["msg"]=$data["msg"].",".$product["pname"]."还能购买".$remainNum."件";
					}
				}
				$paramData["id"]=$shoppingCartId;
				$paramData["product_num"]=$shoppingCartProductNum;
				D("ShoppingCart")->save($paramData);	
			}
		}catch (Exception $e){
			$data["result"]=false;
			$data["msg"]="购物车异常,请刷新页面后重新提交";	
		}
		if($data["result"]){
			$data["shoppingCartIds"]=$shoppingCartIds;
			$result = $this->getDetail();
			$data["shoppingCartItems"] = $result["shoppingCartItems"];
			$data["productTotalNum"] = $result["productTotalNum"];
			$data["weight"] = $result["weight"];
			$data["totalCost"] = $result["totalCost"];
		}
		$this->ajaxReturn($data, "JSON");
	}
	
	public function detail(){
		$result = $this->getDetail();
		$this->assign("shoppingCartItems", $result["shoppingCartItems"]);
		$this->assign("productTotalNum", $result["productTotalNum"]);
		$this->assign("weight", $result["weight"]);
		$this->assign("totalCost", $result["totalCost"]);
		$this->display();
	}
	
	private function getDetail(){
		$shoppingCartItems = D("ShoppingCart")->getProductList($this->userid);
		$weight=0;
		$productTotalNum = 0;
		$totalCost = 0;
		foreach ($shoppingCartItems as $key=>$shoppingCartItem){
			$productPrice = 0;
			if($this->userinfo['if_member'] && $shoppingCartItem["product"]["member_price"]){
				$shoppingCartItem["product"]["realPrice"]=bcdiv($shoppingCartItem["product"]["member_price"], 100, 2);
				$productPrice = $shoppingCartItem["product"]["member_price"];
			}else{
				$shoppingCartItem["product"]["realPrice"]=bcdiv($shoppingCartItem["product"]["price"], 100, 2);
				$productPrice = $shoppingCartItem["product"]["price"];
			}
			$userCanBuyNum = D("Products")->getRemainProductNumPerUserOrder($shoppingCartItem["product"]["pid"], $this->userid);
			$shoppingCartItem["product"]["userCanBuyNum"]= $userCanBuyNum;
			$shoppingCartItems[$key]=$shoppingCartItem;
			
			$inventoryItem = D("InventoryItem")->getById($shoppingCartItem["product"]["inventory_item_id"]);
			$weight += $inventoryItem["weight"] * $shoppingCartItem["product_num"];
			
			$totalCost += $productPrice * $shoppingCartItem["product_num"];
			$productTotalNum += $shoppingCartItem["product_num"];
		}
		$weight = bcdiv($weight, 1000, 3);
		$totalCost = bcdiv($totalCost, 100, 2);
		$result["shoppingCartItems"]=$shoppingCartItems;
		$result["productTotalNum"]=$productTotalNum;
		$result["weight"]=$weight;
		$result["totalCost"]=$totalCost;
		return $result;
	}

    public function delete(){
        $shoppingCartId = $_POST["shoppingCartId"];
        if(!$shoppingCartId){
            $this->error("非法参数");
        }
        $shoppingCartIds[0]=$shoppingCartId;
        D("ShoppingCart")->invalidUserShoppingCart($shoppingCartIds);
        $this->ajaxReturn(array("result"=> true), "JSON");
    }
	
	/**
	 * 配置cron，定期清理购物车
	 */
	public function cronClearCart(){
		D("ShoppingCart")->setOutOfTimeRecordInvalid();
	}
	
}