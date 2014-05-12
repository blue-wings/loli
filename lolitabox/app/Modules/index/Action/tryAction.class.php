<?php

/**
 * 试用控制器
 * @author litingting
 *
 */
class tryAction extends commonAction{
     
	/**
	 * 全部试用
	 * @author penglele
	 */
	public function index(){
		$box_pro_mod=D("BoxProducts");
		$type=$_GET['type'];
		if($type==1){
			$boxid=C("BOXID_PAYPOSTAGE");
		}else{
			$boxid="";
		}
		if($type==1){
			$return['remark']=$this->getTryBoxRemark(2);
		}else{
			$return['remark']=$this->getTryBoxRemark();
		}
		$list=$box_pro_mod->getTryList($boxid,$this->getlimit(8));
		$count=$box_pro_mod->getTryCount($boxid);
		$this->assign("type",$type);
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>8,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"try:index_ajaxlist",//ajax更新模板
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$return['title']="我要试用,提供付邮试用,积分兑换试用等多种试用方式供你选择-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 订购--积分兑换列表
	 * @author penglele
	 */
	public function iexchange(){
		$boxid=C("BOXID_CREDITEXCHANGE");
		$box_mod=M("Box");
		$score=$_GET['score'];
		$id=$_GET['id'];//单品ID
		$userid=$this->getUserid();
		//积分兑换session名
		$sname=getExchangeProductSessName($boxid,$userid);
		$order=$_GET['order'];
		//判断盒子是否存在
		$boxinfo=$box_mod->where("boxid=".$boxid." AND state=1")->find();
		if(!$boxinfo){
			//积分兑换信息不存在时直接header到全部试用
			header("location:".U("try/index"));exit;
		}
		$box_pro_mod=D("BoxProducts");
		$productlist=$box_pro_mod->getTryList($boxid,$this->getlimit(9),$score,$userid,$sname,$order);
		
		if($productlist===false){
			//重定位到全部试用
			header("location:".U("try/index"));
		}
	
		//如果是带参数的，在用户已登录且未选择的状态下将其加到已选列表
		if($userid && $id){
			//判断当前的单品是否在已选列表中
			//首先要判断用户是否符合积分兑换的条件
			$if_member=D("Member")->getUserIfMember($userid);
			$userinfo=$this->userinfo;
			if($userinfo['experience']>=500 ||  $if_member==1){
				if(!in_array($id,$_SESSION[$sname])){
					$if_prolist=$box_pro_mod->getExchangeProductlistOnselling();
					$if_scoreproduct=$box_pro_mod->checkIfExchangeProduct($id);
					if($if_prolist!=false && in_array($id,$if_prolist) && $if_scoreproduct!==false && $if_scoreproduct>0){
						//update by penglele 2013-11-7 15:30:14
						if(count($_SESSION[$sname])<5){
							$_SESSION[$sname][]=$id;
						}
					}
				}				
			}
			header("location:".U("try/iexchange"));
		}
		$this->assign("score",$score);
		$this->assign("boxid",$boxid);
		$count=$box_pro_mod->getScoreProductNum($score,$boxinfo['boxid'],$userid);
		$param = array(
				"total" =>$count,
				'result'=>$productlist,			//分页用的数组或sql
				'parameter' => "score=".$score,
				'listvar'=>'list',				//分页循环变量
				'listRows'=>9,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"try:iexchange_ajaxlist",//ajax更新模板
		);
		
		$this->page($param);
		
		//已选列表
		$return['sessionlist']=$box_pro_mod->getExchangeProductList($_SESSION[$sname],$boxinfo['boxid'],$userid);
		$select=$this->getUserCost($userid, $_SESSION[$sname],$boxinfo['boxid']);
		$return['select']=$select;
		$return['remark']=$this->getTryBoxRemark(3);
		$return['userinfo']=$this->userinfo;		
		$return['title']="积分试用-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}

	
	/**
	 * 根据用户已选列表和用户的积分，计算用户可以支付的方式
	 * @author penglele
	 */
	public function getUserCost($userid,$list,$boxid){
		$total_price=0;
		$score=0;
		$products_score=0;
		$return['num']=count($list);
		$return['total_score']=$score;
		$return['products_score']=$products_score;
		if(!$userid || !$list){
			return $return;
		}
		if(count($list)==0){
			return $return;
		}
		$member_state=D("Member")->getUserIfMember($userid);
		$item_mod=D("InventoryItem");
		$box_pro_mod=M("BoxProducts");
		foreach($list as $key=>$val){
			$info=$item_mod->getInventoryItemInfo($val,"price");
			$box_pro_info=$box_pro_mod->field("pquantity,discount")->where("boxid=$boxid AND pid=$val")->find();
			$per_price=0;
			if($box_pro_info['pquantity']>0){
				
				if($member_state==1){
					//特权用户
					$discount=$box_pro_info['discount']=="0.00" ? 1 : $box_pro_info['discount'];
					$per_price=$box_pro_info['pquantity']*$info['price']*$discount;
				}else{
					$per_price=$box_pro_info['pquantity']*$info['price'];
				}
				
			}
			$total_price=$total_price+$per_price;
		}
		$userinfo=$this->userinfo;
		$score=round($total_price*10);//所有产品的积分总和
		$return['user_score']=(int)$userinfo['score'];//用户积分
		$return['postage_score']=300;//邮费的积分
		$return['products_score']=$score;//产品的积分
		$return['total_score']=$score+$return['postage_score'];//用户需要支付的全部积分
// 		dump($return);exit;

		
// 		if($userinfo['score']>=$score){
// 			$return['type']=1;
// 		}else{
// 			$return['type']=0;//type为看用户可选的类型type=1的时候表示用户积分充足，可以选择全额积分支付
// 			$cost=round(($score-$userinfo['score'])/10);
// 			$return['user_cost']="$cost";//user_cost表示用户在积分不足的情况下如果选择剩余积分支付，还需要付的金额
// 		}

		return $return;
	}	
	
	
	/**
	 * 检测选择的积分产品是否符合规则
	 * @author penglele
	 */
	public function check_exchange_product(){
		$userid=$this->userid;
		$id=$_POST['id'];
		$boxid=$_POST['boxid'];
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(!$this->isAjax() || !$id || !$boxid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		//用户等级达不到v3的不能积分兑换产品--------待定
		$userinfo=$this->userinfo;
		$if_member=D("Member")->getUserIfMember($userid);
		if($userinfo['experience']<500 && $if_member!=1){
			$this->ajaxReturn(1000,$userinfo,0);
		}
		
		$sname=getExchangeProductSessName($boxid,$userid);
		$session_list=$_SESSION[$sname];
		//免费积分兑换中一次最多可以兑换5款产品 update by penglele 2013-11-7 15:11:24
		$num=5;
		if(isset($session_list) && count($session_list)>=$num){
			$this->ajaxReturn(100,$num,0);
		}
		//如果选择的产品已经在已选产品列表，则不能继续选择
		if(in_array($id, $session_list)){
			$this->ajaxReturn(200,"fail",0);
		}
		$box_pro_mod=D("BoxProducts");
		//查看产品的库存量,如果小于0，则说明产品已经兑换完
		$product_num=D("InventoryItem")->getProductInventory($id,$boxid);
		if($product_num<0){
			$this->ajaxReturn(300,"fail",0);
		}
		//产品有一个兑换周期，在一个兑换周期内不能重复兑换
		$date_info=D("InventoryItem")->checkPidInterval($id,$userid);
		if($date_info['day']>0){
			$this->ajaxReturn(400,$date_info,0);
		}
		//到此为全部符合规则的，可以将此产品加入到session列表中
		$_SESSION[$sname][]=$id;
		$pro_info=$box_pro_mod->getProductsInfo($id);
		$box_pro_info=M("BoxProducts")->field("pquantity,discount")->where("boxid=$boxid AND pid=$id")->find();
		
		$member_state=D("Member")->getUserIfMember($userid);
		if($member_state==1){
			$discount= $box_pro_info['discount']=="0.00" ? 1 : $box_pro_info['discount'];
			$pro_info['member_score']=round($pro_info['credit']*$box_pro_info['pquantity']*$discount);
		}else{
			$pro_info['member_score']=$pro_info['credit']*$box_pro_info['pquantity'];
		}
		$this->ajaxReturn(1,$pro_info,1);
	}	
	
	
	/**
	 * 删除免费积分兑换中的某个产品
	 * @author penglele
	 */
	public function del_exchange_product($id,$boxid){
		$id=$_POST['id'];
		$boxid=$_POST['boxid'];
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(!$id || !$boxid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$sname=getExchangeProductSessName($boxid,$userid);
		$session_list=$_SESSION[$sname];
		if(!isset($session_list) || count($session_list)==0 || !in_array($id, $session_list)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$key=array_search($id,$session_list);
		unset($_SESSION[$sname][$key]);
		$this->ajaxReturn(1,"success",1);
	}
	
	
	/**
	 * 提交时判断已选产品信息
	 * @author penglele
	 */
	public function check_exchange_product_select(){
		$boxid=$_POST['boxid'];
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(!$boxid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		
		$userinfo=$this->userinfo;
		$member=D("Member")->getUserIfMember($userid);
		if($userinfo['experience']<500 && $member!=1){
			$this->ajaxReturn(1000,"fail",0);
		}
		
		$sname=getExchangeProductSessName($boxid,$userid);
		$session_list=$_SESSION[$sname];
		//如果session中没有任何数据，不符合规则
		if(!isset($session_list) || count($session_list)==0){
			$this->ajaxReturn(0,"您还没有选择任何产品",0);
		}
		if(count($session_list)>10){
			$this->ajaxReturn(0,"您选择的产品不符合规则",0);
		}
		//如果已选产品中有已售完的，则不能继续提交
		$not_list=$this->getExchangeOutProductList($session_list,$boxid);
		if($not_list!=""){
			$this->ajaxReturn(100,$not_list,0);
		}
		//用户所需积分等信息
		$return['scorelist']=$this->getUserCost($userid,$session_list,$boxid);
		if($return['scorelist']['user_score']<$return['scorelist']['total_score']){
			$this->ajaxReturn(200,$return['scorelist'],0);
		}
		$return['selectlist']=D("BoxProducts")->getExchangeProductList($session_list,$boxid,$userid);
		$this->ajaxReturn(1,$return,1);
	}
	


	/**
	 * 检测已选产品中是否有已售完的产品
	 * @param  $list 已选产品列表
	 * @param  $boxid 盒子id
	 * @author penglele
	 */
	public function getExchangeOutProductList($list,$boxid,$orderid=""){
		$item_mod=D("InventoryItem");
		$not_arr=array();
		if($orderid!=""){
			$list=M("UserOrderSendProductdetail")->field("productid")->distinct(true)->where("orderid=$orderid")->select();
		}
		foreach($list as $key=>$val){
			if($orderid!=""){
				$pid=$val['productid'];
			}else{
				$pid=$val;
			}
			$pro_num=$item_mod->getProductInventory($pid,$boxid);
			if($pro_num<0){
				$not_arr[]=$pid;
			}
		}
		$return="";
		if(count($not_arr)>0){
			$box_pro_mod=D("BoxProducts");
			foreach($not_arr as $key=>$val){
				$pro_info=$box_pro_mod->getProductsInfo($val);
				$name=$pro_info['pname'];
				$not_list[]=$name;
			}
			$return=$not_list;
		}
		return $return;
	}
	
	
	/**
	 * 根据用户已选产品判断用户可以选择的支付方式ajax
	 * @author penglele
	 */
	public function get_pay_type(){
		$boxid=$_POST['boxid'];
		$userid=$this->userid;
		if(!$boxid || !$userid){
			$this->ajaxReturn(0,"fail",0);
		}
		$sname=getExchangeProductSessName($boxid,$userid);
		$list=$this->getUserCost($userid,$_SESSION[$sname],$boxid);
		$this->ajaxReturn(1,$list,1);
	}
	
	/**
	 * 获取用户地址的详细信息
	 * @author penglele
	 */
	
	public function getUserAddressInfo(){
		$id=$_POST['id'];
		if(!$id){
			$this->ajaxReturn(0,"fail",0);
		}
		$info=D("UserAddress")->getUserAddressInfo($id);
		$this->ajaxReturn(1,$info,1);
	}
	
	
	/**
	 * 免费积分兑换生成订单
	 * @author penglele
	 */
	public function exchange_confirm(){
		$addressid=$_POST["aid"];//地址
		$boxid=$_POST["boxid"];//盒子ID 
		//判断用户是否有资格购买当前盒子
		$userid=$this->userid;
 		//删除兑换盒子的cookie值
		$boxinfo=D("Box")->getBoxInfo($boxid);
		if(!$userid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		
		if($boxinfo['state']!=1){
			$this->ajaxReturn(0,"盒子已兑完",0);
		}
		if($boxinfo['category']!=C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$this->ajaxReturn(0,"操作失败，请重新选择",0);
		}
		
		//判断用户的地址信息--------------待定-----------------------
		$address_info=D("UserAddress")->getUserAddressInfo($addressid);
		if($address_info==false || $address_info['if_del']==1 || $address_info['userid']!=$userid){
			$this->ajaxReturn(0,"您的地址信息有误，请确认后再兑换",0);
		}
		
		$sname=getExchangeProductSessName($boxid,$userid);
		$list=$this->getExchangeOutProductList($_SESSION[$sname],$boxid);
		if($list){
			$this->ajaxReturn(0,"您选择的产品部分已兑完，请重新选择",0);
		}
		$user_cost=$this->getUserCost($userid,$_SESSION[$sname],$boxid);
		if($user_cost['user_score']<$user_cost['total_score']){
			$this->ajaxReturn(0,"您的积分不足以支付此订单，请重新选择",0);
		}
		
		$score=$user_cost['total_score'];
		$price=0;
		//生成订单
		$order_mod=D("UserOrder");
		$if_order=$order_mod->addOrder($userid,$boxid,"",$addressid,"","",$sname,$score,$price);
		if($if_order==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			if(isset($_SESSION[$sname])){
				unset($_SESSION[$sname]);
			}
			$order_info=$order_mod->getOrderInfo($if_order);
			$array_return=R("pay/doByBuySuccess",array($order_info['ordernmb'],$order_info['addtime']));
			$info['orderid']=$if_order;
			$info['score']=$score;
			$this->ajaxReturn(1,$info,1);
		}
	}
	
	
	/**
	 * 订单的状态
	 * @author penglele
	 */
	public function get_order_state(){
		$orderid=$_POST["id"];
		if(!$orderid){
			$this->ajaxReturn(0,"fail",0);
		}
		$info=D("UserOrder")->getOrderInfo($orderid);
		$this->ajaxReturn(1,$info,1);
	}
	
	

	/**
	 * 积分兑换重新支付
	 * @author penglele
	 */
// 	public function exchange_repay(){
// 		$userid=$this->userid;
// 		$order_cookie="exchange_".$userid;
// 		$orderid=cookie($order_cookie);
// 		if(!$orderid){
// 			$this->error("订单不存在");exit;
// 		}
// 		$orderinfo=D("UserOrder")->getOrderInfo($orderid,"state");
// 		if(!$orderinfo){
// 			$this->error("订单不存在");exit;
// 		}
// 		if($orderinfo['state']==1){
// 			$this->error("订单已支付，请勿重复操作");exit;
// 		}
// 		header("location:".U("buy/gopay",array("paytype"=>"repay","orderid"=>$orderid)));
// 	}	

	/**
	 * 判断用户是否可以积分兑换产品
	 * @author penglele
	 */
	public function check_score_products(){
		$pid=$_POST['id'];//库存单品ID
		$userid=$this->userid;
		//如果用户没有登录或者没有产品信息，都不能继续
		if(!$pid || !$userid){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		$userinfo=$this->userinfo;
		//判断用户是否达到v3等级
		$if_member=D("Member")->getUserIfMember($userid);
		if($userinfo['experience']<500 && $if_member!=1){
			$msg="<p>只有特权会员或经验值等级达到V3级“萝莉女孩”</p><p>的普通会员才可参与积分试用</p><p><a href='/info/lolitabox/aid/1235.html' target='_blank' class='A_line3'>如何提高经验值等级</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='/member/index.html' target='_blank' class='A_line3'>升级特权会员</a></p>";
			$this->ajaxReturn(0,$msg,0);
		}
		//判断当前产品是否是积分兑换,及产品的库存
		$box_pro_mod=D("BoxProducts");
		$if_scoreproduct=$box_pro_mod->checkIfExchangeProduct($pid);
		//该产品不是积分兑换产品或者已下架
		if($if_scoreproduct===false){
			$this->ajaxReturn(0,"该产品已下架",0);
		}
		//该产品已被选完
		if($if_scoreproduct<=0){
			$this->ajaxReturn(0,"<p>您选择的产品已选完，您可以重新选择其他积分兑换产品</p><p> <a href='/try/iexchange.html' target='_blank' class='A_line3'>查看更多积分兑换产品</a></p>",0);
		}
		//update by penglele 2013-11-7 15:24:31
		$boxid=C("BOXID_CREDITEXCHANGE");
		$sname=getExchangeProductSessName($boxid,$userid);
		$session_list=$_SESSION[$sname];
		$num=5;
		if(isset($session_list) && count($session_list)>=$num){
			$this->ajaxReturn(0,"<p>积分兑换中，一次最多可以选择".$num."款商品</p><p>您可以从已选的产品中移除某件产品后，再重新选择哦^_^</p>",0);
		}
		
		$this->ajaxReturn(1,$pid,1);
	}
	
	/**
	 * 我要试用页描述信息
	 * @param $type=1全部，$type=2付邮试用，$type=3积分试用
	 * @author penglele
	 */
	public function getTryBoxRemark($type=1){
		$box_tryid=C("BOXID_PAYPOSTAGE");
		$box_scoreid=C("BOXID_CREDITEXCHANGE");
		$box_mod=M("Box");
		$try_boxremark=$box_mod->where("boxid=".$box_tryid)->getField("box_remark");
		$score_boxremark=$box_mod->where("boxid=".$box_scoreid)->getField("box_remark");
		$return=array(1=>'',2=>'');
		if($type==2){
			$return[1]=$try_boxremark;
		}else if($type==3){
			$return[2]=$score_boxremark;
		}
		return $return;
	}
	
	
	
	

}

?>