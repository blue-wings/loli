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
	public function getOrderInfo($orderid,$field="*"){
		if(empty($orderid)) return false;
		$order_info=$this->field($field)->getByOrdernmb($orderid);
		if(!$order_info) return false;
		return $order_info;
	}
	
	/**
	 * 获取订单内的详细信息
	 * @param string $orderid
	 * @author ltingting
	 */
	public function getOrderDetail($orderid)
	{
		$order_info=$this->getOrderInfo($orderid);
		if(!$order_info)
			return false;
		//判断用户未订单是否已失效
		if($order_info['state']==C("USER_ORDER_STATUS_NOT_PAYED") && $order_info['ifavalid']==C("order_ifavalid_valid")){
			$order_time=strtotime($order_info['addtime']);
			$now_time=time();
			if($now_time-$order_time>C("order_valid_duration")){
				$order_info['ifavalid']=C("order_ifavalid_overdue");
			}
		}
		
		//订单信息
		$list['orderid']=$orderid;
		$list['boxid']=$order_info['boxid'];
		$list['ifavalid']=$order_info['ifavalid'];
		$list['addtime']=$order_info['addtime'];
		$list['boxprice']=$order_info['boxprice'];
		$list['discount']=$order_info['discount'];
		$list['lastprice']=$order_info['boxprice']-$order_info['discount'];//实际支付金额
		$list['credit']=$order_info['credit'];
		$list['state']=$order_info['state'];
		
		//订单--收货地址
		$order_address_info=$this->getUserOrderAddressList($orderid);
		$address_info=array();
		$address_info['linkman']=$order_address_info['linkman'];
		$address_info['telphone']=$order_address_info['telphone'];
		$address_info['address']=$order_address_info['province'].$order_address_info['city'].$order_address_info['district'].$order_address_info['address'];
		$address_info['postcode']=$order_address_info['postcode'];
		$list['address_list']=$address_info;
		if($order_info['state']==1){
			/*--------已付款--------*/
			//物流信息
			$order_proxy_info=$this->getUserOrderProxyInfo($orderid);
			if($order_proxy_info==false){
				$proxy_info="";
			}else{
				$proxy_info=$order_proxy_info;
			}
			$list['proxyinfo']=$proxy_info;
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
		$order_proxy_mod=M("UserOrderProxy");
		$order_send_mod=M("UserOrderSend");
		$where['orderid']=$orderid;
		if($childid){
			$where['child_id']=$childid;
		}
		$proxy_info=$order_send_mod->where($where)->find();
		if(!$proxy_info || !$proxy_info['proxyorderid']) return false;
		$proxy_info['proxyinfo']=$order_proxy_mod->where($where)->getField("proxyinfo");
		return $proxy_info;
	}
	
	/***********************************************************************************************************************************
	 * 获取用户订单地址
	 * @param $orderid 订单ID
	 * @return array $address_list 地址列表
	 * @author penglele
	 */
	public function getUserOrderAddressList($orderid,$field=null){
		if(empty($orderid)) return false;
		$order_address_mod=M("UserOrderAddress");
		if(empty($field))  $field="*";
		$order_address_info=$order_address_mod->field($field)->getByOrderid($orderid);
		return $order_address_info;
	}
	
	/**
	 * 生成订单
	 * @param 	$userid 		用户ID
	 * @param 	$boxid 			盒子ID
	 * @param 	$code 			优惠券代码
	 * @param 	$pay_bank	支付方式
	 * @param 	$projecid 		增值方案
	 * @param 	$sname 		自由选的session名
	 * @param   $sendword   订单赠言
	 * @author 	penglele
	 */
	public function addOrder($userid,$boxid,$code,$addressid,$pay_bank="",$add_sname="",$sname="",$score="",$price="",$pid="",$if_giftcard=0,$projectid=0,$sendword=""){
		if(empty($userid) || empty($boxid) || empty($addressid) || $addressid==0)
			return false;
		//如果当前盒子类型为免费兑换，则产品不能为空
		if($boxid==C("BOXID_PAYPOSTAGE")){
			$pro_info=D("BoxProducts")->getTryProductInfo($pid);
			if(!$pro_info){
				return false;
			}
		}
		$box_mod=D("Box");
		$boxinfo=$box_mod->getBoxInfo($boxid);
		if($boxinfo==false)
			return false;
		$addres_info=D("UserAddress")->getUserAddressInfo($addressid);
		if($addres_info==false)
			return false;
		$order_mod=M("userOrder");
		if($if_giftcard==1){
			$code="";
		}
		
		//如果订单不是积分兑换或者付邮试用，则可以填写赠言
		if($boxinfo['category']!=C("BOX_TYPE_EXCHANGE_PRODUCT") && $boxinfo['category']!=C("BOX_TYPE_PAYPOSTAGE") && $sendword){
			import("ORG.Util.String");
			if(mb_strlen($sendword,"utf8") > 200){
				$sendword=String::msubstr($sendword ,0,200,'utf-8',false);
			}
			$data['sendword']=$sendword;
		}
		
		$if_discount=D("Coupon")->getDiscountByCoupon($boxid,$code);//检测优惠券是否可以使用等
		$discount=$if_discount["discount"];
		$code=$if_discount["code"];
		
		//因为增加特权会员，在此整理特权会员价格
		$boxinfo['member_price'] = empty($boxinfo['member_price']) ? $boxinfo['box_price'] : $boxinfo['member_price'];//盒子的特权价格
		$boxprice=$boxinfo['box_price']; //定义盒子的价格
		$member_mod=D("Member");
		$member_info=$member_mod->getUserMemberInfo($userid);
		if($member_info['state']==1){
			//用户还在特权期
			$boxprice=$boxinfo['member_price'];
		}
		//特权会员问题end
		
		//如果是积分兑换类型盒子，价格为传入的值
		if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$data['boxprice']=$price;
			if($score){
				$data['credit']=$score;
			}
		}else{
			$data['boxprice']=$boxprice;
			//如果选择了增值方案，增加盒子价格
			if($projectid){
				$if_project=$box_mod->getProjectInfo($projectid);
				if($if_project!=false){
					$data["projectid"]=$projectid;
					$data['boxprice']=$boxprice+$if_project["price"];
				}
			}
		}
		if(isset($add_sname) && isset($_SESSION[$add_sname]) && $boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
			$project_info=$box_mod->getProjectInfo($_SESSION[$add_sname]);
			if($project_info!=false){
				$data["projectid"]=$_SESSION[$add_sname];
				$data['boxprice']=$boxprice+$project_info["price"];
				//当选择加价购后，用户不能使用优惠券
				$discount=0;
				$code="";				
			}
		}
		if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$data['discount']=0;
		}else{
			if($data['boxprice']<=$discount){
				$data['discount']=$data['boxprice'];
			}else{
				$data['discount']=$discount;
			}			
		}

		$data['ordernmb']=date("YmdHis").rand(100,999);
		$data['userid']=$userid;
		$data['boxid']=$boxid;
		$data['addtime']=date("Y-m-d H:i:s");
		$data['coupon']=$code;
		$data['address_id']=$addressid;
		$data['pay_bank']=$pay_bank;
		$data['type']=$boxinfo['category'];
		
		//计算用户的礼品卡余额可以折扣的金额
		if($if_giftcard==1){
			$giftcard_price=D("Giftcard")->getUserGiftcardPrice($userid);
			if($giftcard_price>0){
				if($giftcard_price>=(int)$data['boxprice']){
					$data['pay_bank']="none";//如果使用礼品卡余额全额支付，清楚支付方式
					$data['giftcard']=$data['boxprice'];
				}else{
					$data['giftcard']=$giftcard_price;
				}
			}
		}
		
		//代言人购买盒子打折
		if($boxinfo['category']!=C("BOX_TYPE_SOLO")){
			//当用户没有使用优惠券及礼品卡切盒子的价格大于100时
			if((int)$discount<=0 && (int)$data['giftcard']<=0 && (int)$data['boxprice']>=100){
				$userinfo=D("Users")->getUserInfo($userid,"is_spreader");
				//如果用户是代言人
				if($userinfo['is_spreader']==1){
					$data['boxprice']=$data['boxprice']*0.8;
				}
			}
		}
		
		if($boxinfo['category']==C("BOX_TYPE_EXCHANGE")){
			$data['state']=1;
		}
		
		//增加联盟推广信息
		$promotion_cookie_data=getPromotionCookie();
		if(!empty($promotion_cookie_data['from_id']))
		{
			$data['fromid']=$promotion_cookie_data['from_id'];
			if(!empty($promotion_cookie_data['from_info']))
			{
				$data['frominfo']=$promotion_cookie_data['from_info'];
			}
		}
		//生成订单
		$order_add_rst=$order_mod->add($data);
		//如果当前的盒子是自选盒子，则增加配货信息
		if($boxinfo['category']==C("BOX_TYPE_ZIXUAN") || $boxinfo['category']==C("BOX_TYPE_EXCHANGE") || $boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT") || $boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
			$this->addOrderSendProducts($userid,$boxid,$data['ordernmb'],$sname,$boxinfo['category'],$pid);
		}
		
		//订单信息增加成功
		if($order_add_rst){
			
			//如果当前盒子是免费积分兑换，扣减用户积分
			if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT") && $data['credit']>0){
				D("UserCreditStat")->addUserCreditStat($userid,"积分兑换产品",-$data['credit']);
			}
			
			//生成订单时，给用户发条短信
// 			if($boxinfo['category']!=C("BOX_TYPE_EXCHANGE_PRODUCT")){
// 				if($boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
// 					$mess_content="付邮试用订单(".$data['ordernmb'].")已生成，为了保障您能及时收到试用美妆，请在72小时内完成支付哦~【萝莉盒】";
// 				}else{
// 					$mess_content=$boxinfo['name']."订单(".$data['ordernmb'].")已生成，为了保障您能及时收到萝莉盒，请在72小时内完成支付哦~【萝莉盒】";
// 				}				
// 			}
//  			sendtomess($addres_info['telphone'],$mess_content);

			//将订单的收获地址信息增加到user_order_address表中
			$order_address_data['orderid']=$data['ordernmb'];
			$order_address_data['linkman']=$addres_info['linkman'];
			$order_address_data['telphone']=$addres_info['telphone'];
			$order_address_data['province']=$addres_info['province'];
			$order_address_data['city']=$addres_info['city'];
			$order_address_data['district']=$addres_info['district'];
			$order_address_data['address']=$addres_info['address'];
			$order_address_data['postcode']=$addres_info['postcode'];
			M("UserOrderAddress")->add($order_address_data);
			
			return $data['ordernmb'];
		}else{
			return false;
		}
	}
	
	/**
	 * 获取未支付订单的有效状态
	 */
	public function getUserOrderStat($orderid){
		if(empty($orderid)) return false;
		$orderinfo=$this->getOrderInfo($orderid,"state,ifavalid,addtime");
		if($orderinfo['state']==C("USER_ORDER_STATUS_PAYED")) return false;
		$order_time=strtotime($orderinfo['addtime']);
		$now_time=time();
		if($now_time-$order_time>C("order_valid_duration")){
			return false;
		}
		return true;
	}
	
	/**
	 * 通过订单ID获取订单内的产品列表
	 */
	public function getProductListByOrderid($orderid){
		if(!$orderid)
			return false;
		$pro_send_mod=M("UserOrderSendProductdetail");
		$pro_list=$pro_send_mod->field("productid")->distinct(true)->where("orderid=$orderid")->select();
		if(!$pro_list) return false;
		return $pro_list;
	}
	
	/**
	 * 获取用户消耗的礼品卡的总金额
	 * @author penglele
	 */
	public function getUserOrderGiftcardPrice($userid){
		if(!$userid){
			return 0;
		}
		$where["userid"]=$userid;
		$where["state"]=C("USER_ORDER_STATUS_PAYED");
		$where["ifavalid"]=C("order_ifavalid_valid");
		$where["giftcard"]=array("gt", 0);
		$price=$this->where($where)->sum('giftcard');
		return (int)$price;
	}
	
	/**
	 * 获取用户消耗的礼品卡记录的总数
	 * @author penglele
	 */
	public function getUserOrderGiftcardNum($userid){
		if(!$userid){
			return 0;
		}
		$where["userid"]=$userid;
		$where["state"]=C("USER_ORDER_STATUS_PAYED");
		$where["ifavalid"]=C("order_ifavalid_valid");
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
	public function addOrderSendWord($orderid,$childid,$content,$userid,$type=1){
		if(!$orderid || !$childid){
			return false;
		}
		$order_send_mod=M("UserOrderSendword");
		$data['content']=$content;
		$where['orderid']=$orderid;
		$where['child_id']=$childid;
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
				$data['orderid']=$orderid;
				$data['child_id']=$childid;
				$data['userid']=$userid;
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
			if(!$if_sw || $if_sw['userid']!=$userid){
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