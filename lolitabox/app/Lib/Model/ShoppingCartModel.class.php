<?php
class ShoppingCartModel extends Model {
	
	/**
	 * 减少产品可售数目，并添加购物车记录
	 * @param unknown_type $userId
	 * @param unknown_type $productId
	 * @param unknown_type $productNum
	 */
	public function addProdcutToCart($userId, $productId, $productNum){
		$param["userid"]=$userId;
		$param["productid"]=$productId;
		$param["status"]=C("SHOPPING_CART_STATUS_VALID");
		$cartProduct = $this->where($param)->find();
		$sql = $this->getLastSql();
		$time = date("Y-m-d H:i:s");
		$param["update_time"]=$time;
		if($cartProduct){
			$param["id"]=$cartProduct["id"];
			$param["product_num"] = $productNum + $cartProduct["product_num"];
			$this->save($param);
		}else{
			$param["add_time"]=$time;
			$param["product_num"]=$productNum;
			$this->add($param);
		}
	}
	
	/**
	 * 删除用户购物车中某个产品
	 * @param unknown_type $userid
	 */
	public function invalidUserShoppingCartProduct($userid, $productId){
		if(!$userid){
			return null;
		}
		$param["status"]=C("SHOPPING_CART_STATUS_INVALID");
		$where["userid"]=$userid;
		$where["productid"]=$productId;
		$this->where($where)->save($param);
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
		$shoppingCartList = $this->where($param)->select();
		foreach ($shoppingCartList as $key=>$shoppingCart){
			$product = D("Products")->getByPid($shoppingCart["productid"]);
			$shoppingCart["product"] = $product;
			$shoppingCartList[$key]=$shoppingCart;
		}
		return $shoppingCartList;
	}
	
	/**
	 * 将购物车中超市未购买的产品记录置为无效，回收购物车中的预定，重新供用户订阅
	 * 可能被后台cron调用
	 */
	public function setOutOfTimeRecordInvalid(){
		$where["status"]=C("SHOPPING_CART_STATUS_VALID");
		$where["add_time"]=date("Y-m-d H:i:s", time()-C("SHOPPING_CART_INVALID_DURATION"));
		$cartProductList = $this->where($where)->select();
		//加数据库行锁
		$productModel = D("Products");
		foreach ($cartProductList as $cartProduct){
			$cartParam["id"]=$cartProduct["id"];
			$cartParam["status"]=C("SHOPPING_CART_STATUS_INVALID");
			$this->save($cartParam);
		}
	}
} 