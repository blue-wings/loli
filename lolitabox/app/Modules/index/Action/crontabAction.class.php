<?php
/**
 * 我的个人中心控制器
* @author penglele
*/
class crontabAction extends commonAction {

	/**
	 * 每5分钟执行一次，找出无效订单，回收产品库存
	 */
	public function recyclingOrder(){
		$ip = get_client_ip();
		if($ip != "127.0.0.1"){
			$this->ajaxReturn(array("action"=>"recyclingOrder","time"=>date("Y-m-d H:i:s"),"result"=>"false", "msg"=>"external ip"), "JSON");
		}
		$startTime = date("Y-m-d H:i:s",time()-C("ORDER_VALID_DURATION"));
		$userOrderWhere["addtime"]=array('elt',$startTime);
		$userOrderWhere["state"]=C("USER_ORDER_STATUS_NOT_PAYED");
		$userOrderWhere["ifavalid"]=C("ORDER_IFAVALID_VALID");
		$orders = D("UserOrder")->where($userOrderWhere)->select();
		foreach ($orders as $order){
			try {
				M()->startTrans();
				$lockWhere["ordernmb"] = $order["ordernmb"];
				D("UserOrder")->where($lockWhere)->lock(true)->select();
				$products = D("UserOrderSendProductdetail")->getUserOrderProducts($order["ordernmb"]);
				foreach ($products as $product){
					D("Products")->minusInventoryReducedInDBLock($product["pid"], $product["product_num"]);
				}
				D("UserOrder")->where(array("ordernmb"=>$order["ordernmb"]))->save(array("ifavalid"=>C("ORDER_IFAVALID_OVERDUE")));
				M()->commit();
			}catch(Exception $e){
				M()->rollback();
			}
		}
		
		$userSelfPackageOrderWhere["addtime"]=array('elt',$startTime);
		$userSelfPackageOrderWhere["state"]=C("USER_ORDER_STATUS_NOT_PAYED");
		$userSelfPackageOrderWhere["ifavalid"]=C("ORDER_IFAVALID_VALID");
		D("UserSelfPackageOrder")->where($userSelfPackageOrderWhere)->save(array("ifavalid"=>C("ORDER_IFAVALID_OVERDUE")));
		$this->ajaxReturn(array("action"=>"recyclingOrder","time"=>date("Y-m-d H:i:s"),"result"=>"ok"), "JSON");
	}
}