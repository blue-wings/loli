<?php
/**
 * 我的个人中心控制器
* @author penglele
*/
class homeAction extends commonAction {

	

	/**
	 * 个人中心首页
	 * @author penglele
	 */
	public function index(){
		$userid=$this->userid;
		$return['userinfo']=$this->userinfo;
		$return['title']=$return['userinfo']['nickname']."的主页-".C("SITE_NAME");

        //优惠券余额
        $price=$this->userinfo["balance"];
        $price = bcdiv($price, 100, 2);
        $info['giftcard_price'] = $price;
        
        //我的订阅
        $model= new Model();
        $dataOffset = time() - strtotime($return['userinfo']['addtime']);
        $productSql = "select count(distinct(p.pid)) as productNum  from products as p right join users_products_category_subscribe as upcs  ON p.effectcid = upcs.product_category_id WHERE upcs.user_id=".$userid." and p.end_time > NOW()";
        //如果注册大于7天，则不是新用户，不在推送products表中user_type等于3的订阅
        if($dataOffset/(3600 *24) > 7){
            $productSql = $productSql." and p.user_type != '".C("PRODUCT_NEW_USER_TYPE") ."'";
        }
        $productResult = $model->query($productSql);
        $productTryNum = 0;
        if(count($productResult) > 0){
            $productTryNum = $productResult[0]["productNum"];
        }
        $info['productTryNum'] = $productTryNum;

        //保管箱
        $userOrderSendProductDetail = D("UserOrderSendProductdetail");
        $selfPickUpProductCount = $userOrderSendProductDetail->getUserOrderNumByUserIdAndStatus($userid,C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED"));
        //fixme: add code for auto send area products
        $autoSendProductCount = 0;//$userOrderSendProductDetail->getUserOrderNumByUserIdAndStatus($userid,1);
        $willExpiredNum = $userOrderSendProductDetail->getWillExpiredNumInSelfPickupProduct($userid);

        $info['selfPickUpProductCount'] = $selfPickUpProductCount;
        $info['autoSendProductCount'] = $autoSendProductCount;
        $info['totalProductCount'] = $selfPickUpProductCount + $autoSendProductCount;
        $info['willExpiredNum'] = $willExpiredNum;

        //我试用的产品
        $receiveTryProductNum = $userOrderSendProductDetail->getReceiveTryProductNum($userid);
        $info['receiveTryProductNum'] = $receiveTryProductNum;

        $this->assign("info",$info);
        $this->assign("return",$return);
        $this->display();
	}
	
	
}
?>