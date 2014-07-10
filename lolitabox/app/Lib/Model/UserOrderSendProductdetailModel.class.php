<?php
/**
 * Created by JetBrains PhpStorm.
 * User: libin
 * Date: 14-6-23
 * Time: 下午6:54
 * To change this template use File | Settings | File Templates.
 */

class UserOrderSendProductdetailModel extends Model{

    /**
     *
     * @param $userId
     * @param $status
     */
    public function getUserOrderNumByUserIdAndStatus($userId,$status){
        if(empty($userId)){
            return false;
        }
        $where ['user_id']  = $userId;
        $where['status'] = $status;
        return $this->where($where)->count();
    }

    /**
     * 得到在自主提货区即将过期(距离过期还有7天)的产品数
     * @param $userId
     */
    public function getWillExpiredNumInSelfPickupProduct($userId){
        if(empty($userId)){
            return false;
        }
        $now = date("Y-m-d");
        $mimDate = date("Y-m-d",time()-24*60*60*7);
        $where['user_order_send_productdetail.userid'] = $userId;
        $where['user_order_send_productdetail.status'] = c("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED");
        $where['ii.validdate'] = array(array('egt',$mimDate),array('lt',$now));
        $productNum = $this->join("left join products as p on user_order_send_productdetail.productid = p.pid")->join("left join inventory_item as ii on p.inventory_item_id = ii.id")->where($where)->count();
        return $productNum;
    }

    /**
     * 得到用户收到的试用产品数
     * @param $userId
     */
    public function getReceiveTryProductNum($userId){
        if(empty($userId)){
            return false;
        }
        $where['user_order_send_productdetail.userid'] = $userId;
        $where['uos.senddate'] = array('exp','is not null');
        $productNum = $this->join("left join user_order_send as uos on user_order_send_productdetail.orderid = uos.orderid")->where($where)->count();
        //跟别的拼单一起打包的产品
        $productPackageNum = $this->join("left join user_order_send as uos on user_order_send_productdetail.self_package_order_id= uos.orderid")->where($where)->count();

        return $productNum + $productPackageNum;
    }
    
/**
	 * 生成订单产品详细信息
	 * @param unknown_type $userid
	 * @param unknown_type $products
	 * @param unknown_type $orderId
	 */
	function addOrderSendProducts($userid,$products,$orderId){
		foreach ($products as $product){
			$data["orderid"]=$orderId;
			$data["userid"]=$userid;
			$data["productid"]=$product["pid"];
			$data["status"]=C("USER_ODER_SEND_PRODUCT_STATUS_NOT_PAYED");
		}
		$this->add($data);	
	}
}