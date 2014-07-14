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
		}
		if(!$_POST["pNum"]){
			$data["result"]=false;
			$data["msg"]="请填写商品数目";
		}
		if($product["end_time"] < date("Y-m-d H:i:s")){
			$data["result"]=false;
			$data["msg"]="商品已经下架";
		}
		if($product["inventory"] < $product["inventoryreduced"]){
			$data["result"]=false;
			$data["msg"]="商品已售空";	
		}
		if(!$data["result"]){
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
	 * 配置cron，定期清理购物车
	 */
	public function cronClearCart(){
		D("ShoppingCart")->setOutOfTimeRecordInvalid();
	}
	
}