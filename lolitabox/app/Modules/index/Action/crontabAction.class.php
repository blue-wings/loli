<?php
/**
 * 我的个人中心控制器
* @author penglele
*/
class crontabAction extends commonAction {

	/**
	 * 每5分钟执行一次，找出无效订单，回收产品库存
     * 每次回收三天前的订单资源，理论上不会与订单的支付产生并发问题，支付宝订单的有效期为两天
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
		$failedOrders = array();
		$successOrders = array();
		foreach ($orders as $order){
			try {
				M()->startTrans();
                D("DBLock")->getSingleOrderLock($order["ordernmb"]);
                if($order["state"]==C("USER_ORDER_STATUS_PAYED")){
                    continue;
                }
				$products = D("UserOrderSendProductdetail")->getUserOrderProducts($order["ordernmb"]);
				foreach ($products as $product){
                    $product = D("DBLock")->getSingleProductLock($product["pid"]);
					D("Products")->minusInventoryReduced($product["pid"], $product["product_num"]);
				}
				D("UserOrder")->where(array("ordernmb"=>$order["ordernmb"]))->save(array("ifavalid"=>C("ORDER_IFAVALID_OVERDUE")));
                D("DBLock")->getSingleUserLock($order["userid"]);
                $sql = "UPDATE `users` SET balance=balance+" . $order["giftcard"] . " WHERE userid=" . $order["userid"];
                $this->db->execute($sql);
				array_push($successOrders, $order["ordernmb"]);
				M()->commit();
			}catch(Exception $e){
				M()->rollback();
				array_push($failedOrders, $order["ordernmb"]);
			}
		}
		
		$userSelfPackageOrderWhere["addtime"]=array('elt',$startTime);
		$userSelfPackageOrderWhere["state"]=C("USER_ORDER_STATUS_NOT_PAYED");
		$userSelfPackageOrderWhere["ifavalid"]=C("ORDER_IFAVALID_VALID");
		D("UserSelfPackageOrder")->where($userSelfPackageOrderWhere)->save(array("ifavalid"=>C("ORDER_IFAVALID_OVERDUE")));
		$msg = "successOrders[".join(",", $successOrders)."] failedOrders[".join(",", $failedOrders)."]";
		$this->ajaxReturn(array("action"=>"recyclingOrder","runtime"=>date("Y-m-d H:i:s"),"order max addtime"=>$startTime,"result"=>"ok", "msg"=>$msg), "JSON");
	}
}