<?php
class ShoppingCartAction extends commonAction {
	
	public function addProduct2Cart(){
		$userid = $this->userid;
		$productId = $_POST("productId");
		$product = D("Products")->getByPid($productId);
		if($product["end_time"] < date("Y-m-d H:i:s")){
			$this->error("商品已经下架");
		}
		$shoppingCartModel = D("ShoppingCart");
		$shoppingCartModel->addProdcutToCart($userid, $productId, 1); 
	}
	
}