<?php
class ShoppingCartModel extends ViewModel {
	
	/**
	 * 减少产品可售数目，并添加购物车记录
	 * @param unknown_type $userId
	 * @param unknown_type $productId
	 * @param unknown_type $productNum
	 */
	public function addProdcutToCart($userId, $productId, $productNum){
		$productModel = D("Products");
		$productModel->addCartNumInDBLock($productId, $productNum);
		$param["add_time"]=date("Y-m-d H:i:s");
		$param["userid"]=$userId;
		$param["productid"]=$productId;
		$param["status"]=C("SHOPPING_CART_STATUS_VALID");
		$param["product_num"]=$productNum;
		$this->save($param);
	}
	
	/**
	 * 获取用户购物车商品
	 * @param unknown_type $userid
	 */
	public function getProductList($userid){
		if(!$userid){
			return null;
		}
		$param["status"]=C("SHOPPING_CART_STATUS_VALID");
		$param["userid"]=$userid;
		return $this->where($param)->select();
	}
	
	/**
	 * 将购物车中超市未购买的产品记录置为无效，回收购物车中的预定，重新供用户订阅
	 * 可能被后台cron调用
	 */
	public function setRecordInvalid(){
		$where["status"]=C("SHOPPING_CART_STATUS_VALID");
		$where["add_time"]=date("Y-m-d H:i:s", time()-C("SHOPPING_CART_INVALID_DURATION"));
		$cartProductList = $this->where($where)->select();
		//加数据库行锁
		$productModel = D("Products");
		foreach ($cartProductList as $cartProduct){
			$cartParam["status"]=C("SHOPPING_CART_STATUS_INVALID");
			if($cartProduct->save($cartParam)){
				$productModel->minusCartNumInDBLock($cartProduct["productid"], $cartProduct["product_num"]);	
			}
		}
	}
} 