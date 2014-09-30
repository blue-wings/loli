<?php
class UserSelfPackageOrderModel extends Model {
	
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
	 * 创建邮费订单
	 * @param unknown_type $userid
	 * @param unknown_type $productIds
	 * @param unknown_type $productNums
	 * @throws Exception
	 */
	public function createOrder($userId, $userOrderSendProductDetailIds){
        try{
            M()->startTrans();
            $data['ordernmb']=date("YmdHis").rand(100,999);
            $data["cost"] = 0;
            $data['userid']=$userId;
            $data['addtime']=date("Y-m-d H:i:s");
            $data['user_order_send_productdetail_ids']=join(",", $userOrderSendProductDetailIds);
            $data['state']=C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED");
            $data['ifavalid']=C("ORDER_IFAVALID_VALID");
            //生成订单
            $this->add($data);
            foreach ($userOrderSendProductDetailIds as $userOrderSendProductDetailId){
                $productDetail = D("UserOrderSendProductdetail")->getById($userOrderSendProductDetailId);
                if($productDetail["userid"] != $userId){
                    throw new Exception("货物归属不正确");
                }
                if($productDetail["status"] != C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED")){
                    throw new Exception("货物状态不正确");
                }
                $params["id"]=$userOrderSendProductDetailId;
                $params["self_package_order_id"]=$data['ordernmb'];
                D("UserOrderSendProductdetail")->save($params);
            }
            M()->commit();
            return $data['ordernmb'];
        }catch (Exception $e){
            M()->rollback();
            throw new Exception($e->getMessage());
        }
	}
	
	/**
	 * 获取订单内的详细信息
	 * @param string $orderid
	 * @author ltingting
	 */
	public function getOrderDetail($orderId){
		$orderInfo=$this->getByOrdernmb($orderId);
		if(!$orderInfo)
			return false;
		//判断用户未订单是否已失效
		if($orderInfo['state']==C("USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED") && $orderInfo['ifavalid']==C("ORDER_IFAVALID_VALID")){
			$orderTime=strtotime($orderInfo['addtime']);
			$nowTime=time();
			if($nowTime-$orderTime>C("ORDER_VALID_DURATION")){
				$orderInfo['ifavalid']=C("ORDER_IFAVALID_OVERDUE");
			}
		}
		
		$weight = 0;
		$productTotalNum = 0;
		$orderProducts = D("UserOrderSendProductdetail")->getSelfPackageOrderProducts($orderId);
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
		$list['ori_cost']=$orderInfo['ori_cost'];
		$list['cost']=$orderInfo['cost'];
		$list['costYuan']=bcdiv($orderInfo['cost'], 100,1);
		$list['postage']=$orderInfo['postage'];
		
		return $list;
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
	public function completeOrder($userId,$orderId, $addressId,$payBank, $sendWord="", $expressCompanyId){
        try {
            M()->startTrans();
            $order = D("DBLock")->getSingleSelfPackageOrderLock($orderId);
            if($order["state"] != C("USER_ORDER_STATUS_NOT_PAYED") || $order["ifavalid"]==C("ORDER_IFAVALID_OVERDUE")){
                throw new Exception("订单已失效");
            }
            $data['ordernmb']=$orderId;
            $address = M("UserOrderAddress")->getById($addressId);
            $postage = D("PostageStandard")->calculateSelfPackageOrderPostage($orderId, $expressCompanyId, $address["district_area_id"]);
            $data["cost"]=$postage;
            $data['sendword']=$sendWord;
            $data['address_id']=$addressId;
            $data['pay_bank']=$payBank;
            $this->save($data);
            M()->commit();
        }catch(Exception $e){
            M()->rollback();
            throw new Exception($e->getMessage());
        }
	}
	
	/**
	 * 用户支付完毕
	 * @param unknown_type $orderId
	 * @param unknown_type $tradeNumber
	 */
	public function hasPayed($orderId, $tradeNumber, $payTime){
        try {
            M()->startTrans();
            $order = D("DBLock")->getSingleSelfPackageOrderLock($orderId);
            if($order["ifavalid"] == C("ORDER_IFAVALID_OVERDUE")){
                Log::write("order was recycled during user pay, orderId ".$order." tradeNumber ".$tradeNumber." paytime ".$payTime,CRIT);
                return;
            }
            if($order["state"] == C("USER_ORDER_STATUS_PAYED")){
                if($order["trade_no"] != $tradeNumber){
                    Log::write("order has payed before, orderId ".$order." tradeNumber ".$tradeNumber." paytime ".$payTime,CRIT);
                }
                return;
            }
            $data["ordernmb"]=$orderId;
            $data["state"]=C("USER_ORDER_STATUS_PAYED");
            if(empty($order["paytime"]) && !empty($payTime)) {
                $data["paytime"]=$payTime;
            }
            if(empty($order["trade_no"]) && !empty($tradeNumber)){
                $data["trade_no"]=$tradeNumber;
            }
            $this->save($data);
            D("UserOrderSendProductdetail")->changeStatus2PostagePayedBySelfPackageOrderId($orderId);
            M()->commit();
        }catch(Exception $e){
            M()->rollback();
        }
		//@TODOcreate inventoryOut and orderSend record
        $this->createSystemOutInventory($orderId);
	}
    public function createSystemOutInventory($orderId){
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
            'confirmdatetime'=>'',
            'ifselfpackage'=>1
        );
        $in_out_id=$out_mod->add($data);

        $total_num=0;//当前订单内商品总数

        //get product detail
        $detail_mod=M("userOrderSendProductdetail");
        $info=$detail_mod->where(array('self_package_order_id'=>$orderId))->field('productid,product_num')->select();
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
        $orderSendData['productprice'] = $order['cost'];
        $orderSendData['inventory_out_id'] = $in_out_id;
        $orderSendData['inventory_out_status'] = C("INVENTORY_OUT_STATUS_UNFINISHED");
        $order_send_mod->add($orderSendData);

        //关联user_self_package _order
        $orderData['inventory_out_id'] = $in_out_id;
        $orderData['inventory_out_status'] = C("INVENTORY_OUT_STATUS_UNFINISHED");
        $this->where(array('ordernmb'=>$orderId))->setField($orderData);
	}
	
}