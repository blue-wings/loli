<?php
/**
 * 用户订单模型
 */
class UserOrderModel extends Model {
    
	/**
	 * 获取订单信息
	 * @param $orderid 订单ID
	 * @return array $order_info 订单信息
	 * @author penglele
	 */
	public function getOrderInfo($orderId,$field="*"){
		if(empty($orderId)) return false;
		$orderInfo=$this->field($field)->getByOrdernmb($orderId);
		if(!$orderInfo) return false;
		return $orderInfo;
	}
	
	/**
	 * 获取订单内的详细信息
	 * @param string $orderid
	 * @author ltingting
	 */
	public function getOrderDetail($orderId)
	{
		$orderInfo=$this->getOrderInfo($orderId);
		if(!$orderInfo)
			return false;
		//判断用户未订单是否已失效
		if($orderInfo['state']==C("USER_ORDER_STATUS_NOT_PAYED") && $orderInfo['ifavalid']==C("ORDER_IFAVALID_VALID")){
			$orderTime=strtotime($orderInfo['addtime']);
			$nowTime=time();
			if($nowTime-$orderTime>C("ORDER_VALID_DURATION")){
				$orderInfo['ifavalid']=C("ORDER_IFAVALID_OVERDUE");
			}
		}
		
		//订单信息
		$list['orderid']=$orderId;
		$list['boxid']=$orderInfo['boxid'];
		$list['ifavalid']=$orderInfo['ifavalid'];
		$list['addtime']=$orderInfo['addtime'];
		$list['boxprice']=$orderInfo['boxprice'];
		$list['discount']=$orderInfo['discount'];
		$list['lastprice']=$orderInfo['boxprice']-$orderInfo['discount'];//实际支付金额
		$list['credit']=$orderInfo['credit'];
		$list['state']=$orderInfo['state'];
		
		//订单--收货地址
		$orderAddressInfo=$this->getUserOrderAddressList($orderid);
		$addressInfo=array();
		$addressInfo['linkman']=$orderAddressInfo['linkman'];
		$addressInfo['telphone']=$orderAddressInfo['telphone'];
		$addressInfo['address']=$orderAddressInfo['province'].$orderAddressInfo['city'].$orderAddressInfo['district'].$orderAddressInfo['address'];
		$addressInfo['postcode']=$orderAddressInfo['postcode'];
		$list['address_list']=$addressInfo;
		if($orderInfo['state']==1){
			/*--------已付款--------*/
			//物流信息
			$orderProxyInfo=$this->getUserOrderProxyInfo($orderid);
			if($orderProxyInfo==false){
				$proxyInfo="";
			}else{
				$proxyInfo=$orderProxyInfo;
			}
			$list['proxyinfo']=$proxyInfo;
		}
		return $list;
	}
    
	
	/**
	 * 订单物流信息
	 * @param $orderid 订单ID
	 * @return array $proxy_info 物流信息
	 * @author penglele
	 */
	public function getUserOrderProxyInfo($orderid,$childid){
		if(empty($orderid)) return false;
		$orderProxyMod=M("UserOrderProxy");
		$orderSendMod=M("UserOrderSend");
		$where['orderid']=$orderid;
		if($childid){
			$where['child_id']=$childid;
		}
		$proxyInfo=$orderSendMod->where($where)->find();
		if(!$proxyInfo || !$proxyInfo['proxyorderid']) return false;
		$proxyInfo['proxyinfo']=$orderProxyMod->where($where)->getField("proxyinfo");
		return $proxyInfo;
	}
	
	/*
	 * 获取用户订单地址
	 * @param $orderid 订单ID
	 * @return array $address_list 地址列表
	 * @author penglele
	 */
	public function getUserOrderAddressList($orderid,$field=null){
		if(empty($orderid)) return false;
		$orderAddressMod=M("UserOrderAddress");
		if(empty($field))  $field="*";
		$orderAddressInfo=$orderAddressMod->field($field)->getByOrderid($orderid);
		return $orderAddressInfo;
	}
	
	/**
	 * 减少单品发售量，创建订单
	 * @param unknown_type $userid
	 * @param unknown_type $productIds
	 * @param unknown_type $productNums
	 * @throws Exception
	 */
	public function createOrder($userId, $productIds, $productNums){
		$result = array();
		$productMsgResult = array();
		$productModel = D("Products");
		if(count($productIds) != count($productNums)){
			throw new Exception("未知错误");
		}
		$validProductIds = array();
		$validProductNums = array();
		for($i=0; $i<count($productIds); $i++){
			$productId = $productIds[$i];
			$productNum = $productNums[$i];
			try {
				$productMsgResult[$productId]=null;
				$product = $productModel->getByPid($productId);
				if($product["status"]!=C("PRODUCT_STATUS_PUBLISHED")){
					$productMsgResult[$productId]="产品处于下架状态";	
				}else if(!D("Products")->checkProdcutNumPerUserOrder($productId, $productNum, $userId)){
					$productMsgResult[$productId]="购买超出限制";
				}else{
					D("Products")->addInventoryReducedInDBLock($productId, $productNum);
					array_push($validProductIds, $productId);
					array_push($validProductNums, $productNum);	
				}
			}catch (Exception $e){
				$productMsgResult[$productId]=$e->getMessage();
			}
		}
		$result["msgResult"]=$productMsgResult;
		if(!count($validProductIds)){
			return $result; 
		}
		$data['ordernmb']=date("YmdHis").rand(100,999);
		//因为增加特权会员，在此整理特权会员价格
		$memberMod=D("Member");
		$memberInfo=$memberMod->getUserMemberInfo($userId);
		$totalCost=0;
		$originalTotalCost=0;
		$products = array();
		foreach ($validProductIds as $productId){
			$product = $productModel->getByPid($productId);
			array_push($products, $product);
			$productMemberPrice = $product["member_price"]?$product["price"]:$product["member_price"];
			if($memberInfo['state']==1){
				//用户还在特权期
				$totalCost += $productMemberPrice;
			}else{
				$totalCost += $product["price"];	
			}
			$originalTotalCost += $product["price"];
		}
		$data["ori_cost"]=$originalTotalCost;
		$data["cost"] = $totalCost;
		//特权会员问题end
		
		$data['userid']=$userId;
		$data['addtime']=date("Y-m-d H:i:s");
		$data['state']=C("USER_ORDER_STATUS_NOT_PAYED");
		//生成订单
		$this->add($data);
		$result["orderId"]=$data['ordernmb'];
		D("UserOrderSendProductdetail")->addOrderSendProducts($userId,$products,$validProductNums, $data['ordernmb']);
		
		return $result;
	}
	/**
	 * 用户补全订单其他信息
	 * @param unknown_type $userId
	 * @param unknown_type $orderId
	 * @param unknown_type $addressId
	 * @param unknown_type $payBank
	 * @param unknown_type $ifGiftCard
	 * @param unknown_type $sendWord
	 * @param unknown_type $expressCompanyId
	 */
	public function complereOrder($userId,$orderId, $addressId,$payBank="",$ifGiftCard=0, $ifPayPostage, $sendWord="", $expressCompanyId){
		if(empty($userId) || empty($orderId) || empty($addressId) || $addressId==0 || empty($ifPayPostage) || empty($expressCompanyId))
			return false;
			
		$data['ordernmb']=$orderId;
		
		$addresInfo=D("UserAddress")->getUserAddressInfo($addressId);
		if($addresInfo==false)
			return false;
			
		$products = D("UserOrderSendProductdetail")->getUserOrderProducts($orderId);
    	$productIds = array();
    	foreach ($products as $product){
    		array_push($productIds, $product["pid"]);
    	}
		
		if($ifPayPostage){
			$postage = D("PostageStandard")->calculateOrderPostageByAddress($productIds, $expressCompanyId, $addressId);
			$data["postage"]=$postage;
			$data["pay_postage"]=C("USER_PAY_POSTAGE_ORDER");
		}else{
			$data["pay_postage"]=C("USER_NOT_PAY_POSTAGE_ORDER");
		}
		
		$data["cost"] += $postage;
		
		$data['sendword']=$sendWord;
		$data['address_id']=$addressId;
		$data['pay_bank']=$payBank;
		
		//计算用户的礼品卡余额可以折扣的金额
		if($ifGiftCard==1){
			$giftcardPrice=D("Giftcard")->getUserGiftcardPrice($userId);
			if($giftcardPrice>0){
				if($giftcardPrice>=(int)$data['cost']){
					$data['pay_bank']=null;//如果使用礼品卡余额全额支付，清除支付方式
					$data['giftcard']=$data['cost'];
				}else{
					$data['giftcard']=$giftcardPrice;
				}
			}
		}
		$data["ifPayPostage"] = $ifPayPostage;
		$orderAddRst = $this->save($data);
		
		//订单信息增加成功
		if($orderAddRst){
			//将订单的收获地址信息增加到user_order_address表中
			$orderAddressData['orderid']=$data['ordernmb'];
			$orderAddressData['linkman']=$addresInfo['linkman'];
			$orderAddressData['telphone']=$addresInfo['telphone'];
			$orderAddressData['province']=$addresInfo['province'];
			$orderAddressData['city']=$addresInfo['city'];
			$orderAddressData['district']=$addresInfo['district'];
			$orderAddressData['address']=$addresInfo['address'];
			$orderAddressData['postcode']=$addresInfo['postcode'];
			M("UserOrderAddress")->add($orderAddressData);
			
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 用户支付完毕
	 * @param unknown_type $orderId
	 * @param unknown_type $tradeNumber
	 */
	public function hasPayed($orderId, $tradeNumber){
		$order = $this->getOrderInfo($orderId);
		$data["ordernmb"]=$orderId;
		$data["state"]=C("USER_ORDER_STATUS_PAYED");
		$data["trade_no"]=$tradeNumber;
		
		$this->save($data);
		if($order["pay_postage"]==C("USER_NOT_PAY_POSTAGE_ORDER")){
			D("UserOrderSendProductdetail")->changeStatus2PostageNotPay($orderId);	
		}else{
			D("UserOrderSendProductdetail")->changeStatus2PostagePayed($orderId);
			//@TODOcreate inventoryOut and orderSend record
		}
	}
	
	/**
	 * 获取未支付订单的有效状态
	 */
	public function getUserOrderStat($orderId){
		if(empty($orderId)) return false;
		$orderInfo=$this->getOrderInfo($orderId,"state,ifavalid,addtime");
		if($orderInfo['state']==C("USER_ORDER_STATUS_PAYED")) return false;
		$orderTime=strtotime($orderInfo['addtime']);
		$nowTime=time();
		if($nowTime-$orderTime>C("ORDER_VALID_DURATION")){
			return false;
		}
		return true;
	}
	
	/**
	 * 通过订单ID获取订单内的产品列表
	 */
	public function getProductListByOrderid($orderId){
		if(!$orderId)
			return false;
		$proSendMod=M("UserOrderSendProductdetail");
		$proList=$proSendMod->field("productid")->distinct(true)->where("orderid=$orderId")->select();
		if(!$proList) return false;
		return $proList;
	}
	
	/**
	 * 获取用户消耗的礼品卡的总金额
	 * @author penglele
	 */
	public function getUserOrderGiftcardPrice($userId){
		if(!$userId){
			return 0;
		}
		$where["userid"]=$userId;
		$where["state"]=C("USER_ORDER_STATUS_PAYED");
		$where["ifavalid"]=C("ORDER_IFAVALID_VALID");
		$where["giftcard"]=array("gt", 0);
		$price=$this->where($where)->sum('giftcard');
		return (int)$price;
	}
	
	/**
	 * 获取用户消耗的礼品卡记录的总数
	 * @author penglele
	 */
	public function getUserOrderGiftcardNum($userId){
		if(!$userId){
			return 0;
		}
		$where["userid"]=$userId;
		$where["state"]=C("USER_ORDER_STATUS_PAYED");
		$where["ifavalid"]=C("ORDER_IFAVALID_VALID");
		$where["giftcard"]=array("gt", 0);
		$num=$this->where($where)->count();
		return $num;		
	}
	
	/**
	 * 用户增加/修改赠言
	 * @param $orderid   订单ID
	 * @param $childid    子订单ID
	 * @param $content	  赠言内容
	 * @param $userid	  用户ID 
	 * @param $type       操作类型【$type=1新增，$type=2修改】
	 * @author penglele
	 */
	public function addOrderSendWord($orderId,$childId,$content,$userId,$type=1){
		if(!$orderId || !$childId){
			return false;
		}
		$order_send_mod=M("UserOrderSendword");
		$data['content']=$content;
		$where['orderid']=$orderId;
		$where['child_id']=$childId;
		if($type==1){
			//新增 赠言
			$if_sw=$order_send_mod->where($where)->find();
			if($if_sw){
				$res=$order_send_mod->where($where)->save($data);
				if($res===false){
					return false;
				}else{
					return true;
				}
			}else{
				$data['orderid']=$orderId;
				$data['child_id']=$childId;
				$data['userid']=$userId;
				$data['add_date']=date("Y-m-d H:i:s");
				$res=$order_send_mod->add($data);
				if($res){
					return true;
				}else{
					return false;
				}
			}
		}else{
			//重新编辑赠言
			$if_sw=$order_send_mod->where($where)->find();
			if(!$if_sw || $if_sw['userid']!=$userId){
				return false;
			}
			$res=$order_send_mod->where($where)->save($data);
			if($res!==false){
				return true;
			}
			return false;
		}
	}
	
	
}