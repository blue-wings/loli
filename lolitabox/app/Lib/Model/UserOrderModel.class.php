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
	public function getOrderDetail($orderId){
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
		
		$weight = 0;
		$productTotalNum = 0;
		$orderProducts = D("UserOrderSendProductdetail")->getUserOrderProducts($orderId);
		foreach ($orderProducts as $key => $product){
			$inventoryItem = D("InventoryItem")->getById($product["inventory_item_id"]);
			$validdateTime = strtotime($inventoryItem["validdate"]);
			$inventoryItem["validdateFormat"]= date("Y年m月d日", $validdateTime);
			$inventoryItem["weightKg"]=bcdiv($inventoryItem["weight"], 1000, 3);
			$product["inventoryItem"]=$inventoryItem;
			$productNum = $product["product_num"];
			$product["totalWeight"]=bcmul($inventoryItem["weightKg"], $productNum, 3);
			$weight = bcadd($weight, $product["totalWeight"], 3);
			$productTotalNum += $productNum;
			$orderProducts[$key]=$product;
		}
		
		$list["products"] = $orderProducts;
		
		
		$list["weight"]=$weight;
		$list["productTotalNum"]=$productTotalNum;
		
		
		//订单信息
		$list['orderid']=$orderId;
		$list['userid']=$orderInfo['userid'];
		$list['ifavalid']=$orderInfo['ifavalid'];
		$list['addtime']=$orderInfo['addtime'];
		$list['credit']=$orderInfo['credit'];
		$list['state']=$orderInfo['state'];
		$list['cost']=$orderInfo['cost'];
		$list['costYuan']=bcdiv($orderInfo['cost'], 100, 2);
		
		return $list;
	}
    
	/**
	 * 减少单品发售量，创建订单
	 * @param unknown_type $userid
	 * @param unknown_type $productIds
	 * @param unknown_type $productNums
	 * @throws Exception
	 */
	public function createOrder($userId, $productIds, $productNums){
		if(count($productIds) != count($productNums)){
			throw new Exception("创建订单失败");
		}
		try {
			M()->startTrans();
			$lockWhere["pid"] = array("in", $productIds);
			$products = D("Products")->where($lockWhere)->lock(true)->select();
			$productMap = null;
			foreach ($products as $product){
				$productMap[$product["pid"]]=$product;
			}
			foreach ($productIds as $index=>$productId){
				$product = $productMap[$productId];
				$productNum = $productNums[$index];
				if($product["status"]!=C("PRODUCT_STATUS_PUBLISHED")){
					throw new Exception($product["pname"]."处于下架状态");
				}
				$remainNum = D("Products")->getRemainProdcutNumPerUserOrder($product["pid"], $userId);
				if($productNum > $remainNum){
					if(!$remainNum){
						throw new Exception($product["pname"]."购买超出限制, 您不能再购买此产品");
					}else{
						throw new Exception($product["pname"]."购买超出限制, 您最多能购买".$remainNum."件");
					}
				}
				D("Products")->addInventoryReducedInDBLock($product["pid"], $productNum);	
			}
			
			$data['ordernmb']=date("YmdHis").rand(100,999);
			//因为增加特权会员，在此整理特权会员价格
			$memberMod=D("Member");
			$memberInfo=$memberMod->getUserMemberInfo($userId);
			$totalCost=0;
			$originalTotalCost=0;
			foreach ($productIds as $key => $productId){
				$product = $productMap[$productId];
				$productMemberPrice = $product["member_price"]?$product["price"]:$product["member_price"];
				if($memberInfo['state']==1){
					//用户还在特权期
					$totalCost += $productMemberPrice*$productNums[$key];
				}else{
					$totalCost += $product["price"]*$productNums[$key];
				}
				$originalTotalCost += $product["price"]*$productNums[$key];
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
			D("UserOrderSendProductdetail")->addOrderSendProducts($userId,$productIds,$productNums, $data['ordernmb']);
			M()->commit();
			return $data['ordernmb'];
		}catch(Exception $e){
			M()->rollback();
			throw new Exception("创建订单失败-".$e->getMessage());	
		}
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
	public function completeOrder($userId,$orderId, $addressId,$payBank,$ifGiftCard=0, $ifPayPostage, $sendWord="", $expressCompanyId){
		if(empty($userId) || empty($orderId) || !isset($addressId) || empty($ifPayPostage) || !isset($expressCompanyId))
			return false;
			
		$data['ordernmb']=$orderId;

		$data["address_id"]=$addressId;
		
		if($ifPayPostage){
			$address = M("UserOrderAddress")->getById($addressId);
			$postage = D("PostageStandard")->calculateOrderPostage($orderId, $expressCompanyId, $address["district_area_id"]);
			$data["postage"]=$postage;
			$data["pay_postage"]=C("USER_PAY_POSTAGE_ORDER");
		}else{
			$data["postage"]=0;
			$data["pay_postage"]=C("USER_NOT_PAY_POSTAGE_ORDER");
		}
		
		$data['sendword']=$sendWord;
		$data['address_id']=$addressId;
		$data['pay_bank']=$payBank;
		
		//计算用户的礼品卡余额可以折扣的金额
		if($ifGiftCard==1){
			$giftcardPrice=D("Giftcard")->getUserGiftCardPriceInLock($userId);
			if($giftcardPrice>0){
				if($giftcardPrice >= ($data['cost']+$data["postage"])){
					$data['pay_bank']=null;//如果使用礼品卡余额全额支付，清除支付方式
					$data['giftcard']=$data['cost'];
				}else{
					$data['giftcard']=$giftcardPrice;
				}
			}
		}
		$this->save($data);
	}
	
	/**
	 * 用户支付完毕
	 * @param unknown_type $orderId
	 * @param unknown_type $tradeNumber
	 */
	public function hasPayed($orderId, $tradeNumber, $payTime){
		$order = $this->getOrderInfo($orderId);
		$data["ordernmb"]=$orderId;
		$data["state"]=C("USER_ORDER_STATUS_PAYED");
		if(empty($order["paytime"]) && !empty($payTime)) {
			$data["paytime"]=$payTime;
		}
		if(empty($order["trade_no"]) && !empty($tradeNumber)){
			$data["trade_no"]=$tradeNumber;
		}
		$this->save($data);
		if($order["pay_postage"]==C("USER_NOT_PAY_POSTAGE_ORDER")){
			D("UserOrderSendProductdetail")->changeStatus2PostageNotPay($orderId);	
		}else{
			D("UserOrderSendProductdetail")->changeStatus2PostagePayed($orderId);
            //@TODOcreate inventoryOut and orderSend record
            $this->createSystemOutInventory($orderId);
        }
    }
    private function createSystemOutInventory($orderId){
        //generate inventory out record
        $out_mod=M('inventoryOut');
        $data=array(
            'type'=> C("INVENTORY_OUT_TYPE_SYSTEM"),
            'title'=>'出库单'.date("m.d"),
            'outdate'=>date("Y-m-d").' 00:00:00', //todo 今天？或者过一定时间变成明天？
            'description'=>'系统出库单'.date("m.d"),
            'cdatetime'=>date('Y-m-d H:i:s'),
            'operator'=>'system',
            'status'=>1,
            'ifagree'=>0,
            'agreeoperator'=>'',
            'agreedatetime'=>'',
            'ifconfirm'=>0,
            'confirmoperator'=>'',
            'confirmdatetime'=>''
        );
        $in_out_id=$out_mod->add($data);

        $total_num=0;//当前订单内商品总数

        //get product detail
        $detail_mod=M("userOrderSendProductdetail");
        $info=$detail_mod->where(array('orderid'=>$orderId))->field('productid,product_num')->select();
        $stat_mod=M("inventoryStat");
        $product_mod = M("products");
        foreach ($info AS $k => $val){
            $product = $product_mod->field("inventory_item_id")->getByPid($val['productid']);
            $data1=array(
                'itemid'=>$product['inventory_item_id'],
                'message'=>'',
                'operator'=>'',
                'quantity'=>-$val['product_num'],
                'add_time'=>'',
                'status'=>0,
                'in_out_id'=>$in_out_id
            );
            $stat_mod->add($data1);
            $total_num = $total_num + $val['product_num'];
        }
        $order = $this->getOrderInfo($orderId,"userid,cost");

        //generate order send
        $order_send_mod=M("UserOrderSend");
        $orderSendData['orderid'] = $orderId;
        $orderSendData['userid'] = $order['userid'];
        $orderSendData['productnum'] = $total_num;
        $orderSendData['productprice'] = $order['cost']; //todo ??
        $orderSendData['inventory_out_id'] = $in_out_id;
        $orderSendData['inventory_out_status'] = C("INVENTORY_OUT_STATUS_UNFINISHED");
        $order_send_mod->add($orderSendData);

        //关联user_order
        $orderData['inventory_out_id'] = $in_out_id;
        $orderData['inventory_out_status'] = C("INVENTORY_OUT_STATUS_UNFINISHED");
        $this->where(array('ordernmb'=>$orderId))->setField($orderData);
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