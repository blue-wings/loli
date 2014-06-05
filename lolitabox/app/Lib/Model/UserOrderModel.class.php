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
		if($order_info['state']==0 && $order_info['ifavalid']==1){
			$order_time=strtotime($order_info['addtime']);
			$now_time=time();
			if($now_time-$order_time>72*60*60){
				$order_info['ifavalid']=0;
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
		$list['credit']=$order_info['credit'];
		//订单--收货地址
		$order_address_info=$this->getUserOrderAddressList($orderid);
		$address_info=array();
		$address_info['linkman']=$order_address_info['linkman'];
		$address_info['telphone']=$order_address_info['telphone'];
		$address_info['address']=$order_address_info['province'].$order_address_info['city'].$order_address_info['district'].$order_address_info['address'];
		$address_info['postcode']=$order_address_info['postcode'];
		$list['address_list']=$address_info;
		$list['if_task']=-1;
		if($order_info['state']==1){
			//判断用户是否晒过单 start
			if($list['ifavalid']==1){
				$list['if_task']=D("Task")->ifTaskByOrderid($orderid);
				//判断用户订单是否含有子订单
				$if_boxorder=$this->getListByOrderID($orderid);
				$list['if_mon']=(int)$if_boxorder['if_mon'];
				if($list['if_mon']>0){
					$list['mlist']=$if_boxorder['list'];
				}
			}
			//判断用户是否晒过单 end
			
			/*--------已付款--------*/
			//物流信息
			$order_proxy_info=$this->getUserOrderProxyInfo($orderid);
			if($order_proxy_info==false){
				$proxy_info="";
			}else{
				$proxy_info=$order_proxy_info;
			}
			$list['proxyinfo']=$proxy_info;
			//订单内的产品列表
			//自选、主题盒、积分兑换、付邮
			$type_arr=array(C("BOX_TYPE_ZIXUAN"),C("BOX_TYPE_SUIXUAN"),C("BOX_TYPE_EXCHANGE_PRODUCT"),C("BOX_TYPE_PAYPOSTAGE"));
			if(in_array($order_info['type'], $type_arr)){
				$product_list=$this->getOrderDetailList($orderid,$order_info['userid']);
			}else{
				//神秘盒
				if($proxy_info){
					$product_list=$this->getOrderDetailList($orderid,$order_info['userid']);
				}else{
					$product_list="";
				}
			}
			
 			if($product_list=="" && $order_info['type']==C("BOX_TYPE_SUIXUAN")){
				//当已支付订单为主题盒，且订单发送详情内没有产品信息时，调取盒子产品列表
				$box_detail=D("Box")->getZhutiDetails($order_info['boxid']);
				if($box_detail) $product_list=$box_detail['productlist'];//随心所欲盒内产品列表
			} 
		}else{
			/*--------未付款--------*/
			if($order_info['type']==C("BOX_TYPE_SUIXUAN")){
				/*--当盒子类型为随心所欲盒时--*/
				$box_detail=D("Box")->getZhutiDetails($order_info['boxid']);
				if($box_detail) $product_list=$box_detail['productlist'];//随心所欲盒内产品列表
				//$list['product_list']=$box_detail['product_list'];
			}else if($order_info['type']==C("BOX_TYPE_ZIXUAN") || $order_info['type']==C("BOX_TYPE_EXCHANGE_PRODUCT") || $order_info['type']==C("BOX_TYPE_PAYPOSTAGE")){
				/*--当盒子类型为自选盒时--*/
				$order_product_list=$this->getOrderDetailList($orderid,$order_info['userid']);
				if($order_product_list) $product_list=$order_product_list;//自选盒已加入订单列表的产品列表
				if($order_info['projectid']>0){
					//如果存在加价购
					$project_product_list=$this->getProjectProductList($order_info['projectid']);
					if($project_product_list) $product_list=array_merge($product_list,$project_product_list);
				}
			}
		}
		if(!$product_list) $product_list="";
		$list['product_list']=$product_list;
		return $list;
	}
    
	/**
	 * 通过用户ID获取订单列表
	 * @param int  $userid
	 * @param int $state 订单状态[0-未支付，1-己支付，2-己退款]
	 * @param mixed $p 分页
	 */
	public function getOrderListByUserid($where,$p=null){
		$list=$this->where($where)->order("ordernmb DESC")->limit($p)->select();
		if($list){
			$project_mod=M("BoxProject");
			for($i=0;$i<count($list);$i++){
				
				if($list[$i]['type']==C("BOX_TYPE_PAYPOSTAGE")){
					$list[$i]['boxstate']="付邮试用";
				}elseif($list[$i]['type']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
					$list[$i]['boxstate']="积分试用";
				}
				
				if($list[$i]['state']==1){
					//判断订单是否已经发货
					$proxy_info=$this->getUserOrderProxyInfo($list[$i]['ordernmb']);
					if($proxy_info){
						$list[$i]['status']="已完成";
					}else{
						$list[$i]['status']="未发货";
					}
				}
				//订单已退款
				if($list[$i]['state']==2){
					$list[$i]['status']="已退款";
				}

				$list[$i]['price']=$list[$i]['boxprice']-$list[$i]['discount'];//获取订单实际支付金额
				//获取订单的盒子信息
				$boxinfo=M("box")->field("name")->getByBoxid($list[$i]['boxid']);
				$list[$i]['boxname']=$boxinfo['name'];//盒子名称
				//获取订单收货人信息
				$order_u_name=$this->getUserOrderAddressList($list[$i]['ordernmb'],"linkman");
				$list[$i]['linkman']=$order_u_name['linkman'];//收货人名称
				if($list[$i]['state']==0 && $list[$i]['ifavalid']==1){
					//如果当前订单为未支付订单，且为有效状态，再次检测该订单是否失效
					$order_time=strtotime($list[$i]['addtime']);
					$now_time=time();
					if($now_time-$order_time>72*60*60){
						$list[$i]['ifavalid']=0;
					}
				}
				//如果用户选择了增值方案，获取增值方案的名称
				if($list[$i]['projectid']!=0){
					$project_info=$project_mod->where("id=".$list[$i]['projectid'])->find();
					if($project_info){
						$list[$i]['projectname']=$project_info['projectname'];
					}
				}
			}			
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
	
	
	/**
	 * 获取订单内产品信息
	 * @param $orderid 订单ID
	 * @return array  产品列表
	 * @author penglele
	 */
	public function getOrderDetailList($orderid,$userid,$child_id=0){
		if(empty($orderid) || empty($userid)) return false;
		$order_send_pro_mod=M("UserOrderSendProductdetail");
		$pro_list=$order_send_pro_mod->distinct(true)->field("productid")->where("orderid=$orderid AND userid=$userid"." AND child_id=".$child_id)->select();
		if(!$pro_list){
			$list="";
		}else{
			$pro_count=count($pro_list);
			$product_mod=D("Products");
			for($i=0;$i<$pro_count;$i++){
				$pro_info=$product_mod->getSimpleInfoByItemid($pro_list[$i]['productid']);
				$if_share=$this->getUserIfShareToProdu($userid,$pro_info['pid']);
				if($if_share==false){
					$pro_info['if_share']=0;
				}else{
					$pro_info['if_share']=1;
				}
				$list[$i]=$pro_info;
			}
		}
		return $list;
	}
	
	/**
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
	 * 检测未支付订单是否失效
	 * @param $orderid 订单ID
	 */
	public function checkOrderIfavalid($orderid){
		if(empty($orderid)) return false;
		$orderinfo=$this->getOrderInfo($orderid,"state,ifavalid,addtime");
		if($orderinfo['state']==1) return false;
		$order_time=strtotime($orderinfo['addtime']);
		$now_time=time();
		if($now_time-$order_time>72*60*60){
			$data=array();
			$data['ifavalid']=0;
			$up_ifavalid=$this->where("ordernmb=$orderid")->save($data);
			if($up_ifavalid!==false){
				return true;
			}else{
				return false;
			}
		}
		return false;
	}
	
	/**
	 * 获取加价购的产品列表
	 * @param unknown_type $projectid
	 */
	public function getProjectProductList($projectid){
		if(!$projectid)
			return false;
		$project_mod=M("BoxProjectList");
		$pro_list=$project_mod->where("projectid=$projectid")->field("pid")->select();
		if(!$pro_list)
			return false;
		$product_mod=D("Products");
		foreach($pro_list as $key=>$value){
			$prouduc_info=$product_mod->getSimpleInfoByItemid($value['pid']);
			$product_list[]=$prouduc_info;
		}
		return $product_list;
	}
	
	/**
	 * 通过条件查询订单数量
	 * @param $where 查询条件
	 * @param $ifavalid 是否为有效订单数
	 */
	public function getOrderNum($where="",$ifavalid=1){
		if(!$where){
			$where="1=1";
		}
		if($ifavalid==1){
			$where["state"]=1;
			$where["ifavalid"]=1;
		}
		$order_num=$this->where($where)->count();
		return $order_num;
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
	 * 生产订单时向数据表user_order_send_productdetail 中添加产品数据
	 * @param int $boxid 购买的盒子ID【必须】
	 * @param  $orderid 订单ID【必须】
	 * @param $sname session名
	 * @param $pid 如果是付邮试用，产品只有此一个
	 * @author penglele
	 */
	private function addOrderSendProducts($userid,$boxid,$orderid,$sname,$box_type,$pid){
		if(empty($userid) || empty($boxid) || empty($orderid)){
			return false;
		}
		$products_arr=array();
		$box_products_mod=D("BoxProducts");
		$send_products_mod=M("UserOrderSendProductdetail");
		$inventory_item_mod=M("InventoryItem");
		$product_mod=M("products");
		$ifhave_add_order_products=$send_products_mod->where("orderid=$orderid")->find();
		if($ifhave_add_order_products){//如果当前订单号下已有单品存在，则不再录产品进去
			return false;
		}
		if($box_type==C("BOX_TYPE_PAYPOSTAGE")){
			//付邮试用
			if(!$pid){
				return false;
			}
			$products_arr[]=$pid;
		}else{
			//自选或积分兑换
			$products_arr=$this->getSessionProductsList($_SESSION[$sname],$box_type,$boxid);//session 下所有产品id集合（inventory_item下id）
			//如果当前福利分类下有产品，则默认给用户发送
			$products_fuli_list=$box_products_mod->getBoxProductsList("boxid=$boxid AND maxquantitytype=0");
			if($products_fuli_list){
				$boxproduct_mod=D("BoxProducts");
				for($i=0;$i<count($products_fuli_list);$i++){
					$rel=$boxproduct_mod->getProductsInfo($products_fuli_list[$i]['pid']);
					$true_num=$boxproduct_mod->getProductInventoryEstimatedNum($products_fuli_list[$i]['pid']);
					//根据售出与总数及产品的理论库存数比较该产品是否还能发放
					if($rel!==false && ($products_fuli_list[$i]['ptotal']-$products_fuli_list[$i]['saletotal']-$products_fuli_list[$i]['pquantity']>=0) && $true_num>0){
						$products_arr[]=$products_fuli_list[$i]['pid'];
					}
				}
			}			
		}

		$products_count=count($products_arr);
		for($i=0;$i<$products_count;$i++){
			$box_products_info=$box_products_mod->getBoxProductInfo($products_arr[$i],$boxid);
			$inventory_item_info=$inventory_item_mod->where("id=".$products_arr[$i])->find();
			//UserOrderSendProductdetail下的数据组成
			$data['orderid']=$orderid;
			$data['productid']=$products_arr[$i];
			$data['productprice']=$inventory_item_info['price'];
			$data['userid']=$userid;
			//向UserOrderSendProductdetail增加数据
			for($j=0;$j<$box_products_info['pquantity'];$j++){
				$send_products_mod->add($data);
			}
		}
	}
	
	/**
	 * 将session中的产品pid转换成一维数组
	 * @param $arr 多维数组 [必须]
	 */
	private function getSessionProductsList($arr,$box_type,$boxid){
		$products_select_arr=array();
		$dis_boxid=D("Box")->getDiscountBoxid();//限时抢活动的boxid
		if($boxid && $boxid==$dis_boxid){
			foreach($arr as $key=>$value_first){
				foreach($value_first as $value_two){
					if($value_two!=""){
						$products_select_arr[]=$value_two;
					}
				}
			}
		}else{
			if($box_type==C("BOX_TYPE_EXCHANGE_PRODUCT")){
				foreach($arr as $key=>$val){
					$products_select_arr[]=$val;
				}
			}else{
				foreach($arr as $key=>$value_first){
					foreach($value_first as $value_two){
						if($value_two!=""){
							$products_select_arr[]=$value_two;
						}
					}
				}
			}			
		}
		return $products_select_arr;
	}
	
	
	/**
	 * 通过盒子ID及projectid获取加价购的产品列表
	 * @param $projectid 加价购ID
	 * @param $boxid 盒子ID
	 * @param @author penglele
	 */
	public function getProjectListByBoxid($projectid,$boxid){
		if(!$projectid || !$boxid) return false;
		$project_rel_mod=M("BoxProjectRelation");
		$project_rel_info=$project_rel_mod->where("boxid=$boxid AND projectid=$projectid")->find();
		$project_list_mod=M("boxProjectList");
		if(!$project_rel_info) return false;
		$project_list=$project_list_mod->where("projectid=$projectid")->select();
		if(!$projectid) return false;
		foreach($project_list as $key=>$value){
			$pro_info=D("BoxProducts")->getProductsInfo($value["pid"]);
			$products_list[$key]=$pro_info;
			$products_list[$key]["num"]=1;
		}
		return $products_list;
	}
	
	/**
	 * 获取未支付订单的有效状态
	 */
	public function getUserOrderStat($orderid){
		if(empty($orderid)) return false;
		$orderinfo=$this->getOrderInfo($orderid,"state,ifavalid,addtime");
		if($orderinfo['state']==1) return false;
		$order_time=strtotime($orderinfo['addtime']);
		$now_time=time();
		if($now_time-$order_time>72*60*60){
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
	 * 判断用户是否对某一产品发表过分享
	 * @param $userid 用户ID
	 * @param $pid product下的产品ID
	 */
	public function getUserIfShareToProdu($userid,$pid){
		if(!$userid || !$pid) return false;
		$sql="SELECT * FROM user_atme, user_share s WHERE s.id = relationid AND userid =$userid AND relationtype =1 AND sourceid =$pid AND sourcetype =2 AND s.status>0 AND s.ischeck=1 AND s.sharetype =1";
		$res=$this->query($sql);
		if(!$res[0]){
			return false;
		}
		return true;
	}
	
	/**
	 * 获取用户已支付订单列表
	 * @param $userid 用户ID
	 * @author penglele
	 */
	public function getUserOrderList($userid,$limit=4){
		$not_type=C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_FREEGET").",".C("BOX_TYPE_EXCHANGE");
		$where['type']=array("exp","not in (".$not_type.")");
		$where['userid']=$userid;
		$where['state']=1;
		$where['ifavalid']=1;
		$order_list=$this->where($where)->order("ordernmb DESC")->limit($limit)->select();
		$list=array();
		if($order_list){
			$box_mod=D("Box");
			foreach($order_list as $key=>$val){
				$boxinfo=$box_mod->getBoxInfo($val['boxid']);
				$info['name']=$boxinfo['name'];				
				$info['pic']=$boxinfo['pic'];
				$info['boxid']=$boxinfo['boxid'];
				$info['orderid']=$val['ordernmb'];
				$info['orderurl']=U("home/order_detail",array("id"=>$info['orderid']));
				$info['boxurl']=U("buy/show",array("boxid"=>$info['boxid']));
				$list[]=$info;
			}
		}
		return $list;
	}
	
	/**
	 * 用户已支付订单总数
	 * @author penglele
	 */
	public function getUserOrderCount($userid){
		$not_type=C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_FREEGET").",".C("BOX_TYPE_EXCHANGE");
		$where['type']=array("exp","not in (".$not_type.")");
		$where['userid']=$userid;
		$where['state']=1;
		$where['ifavalid']=1;
		$order_num=$this->where($where)->count();
		return $order_num;
	}
	/**
	 *  获取用户已购买的萝莉盒中的产品列表
	 *  @author penglele
	 */
	public function getUserOrderProductsList($userid,$limit=""){
		if(!$userid){
			return false;
		}
		$limit = empty($limit) ? "" : "LIMIT $limit";
		$sql="SELECT DISTINCT(s.productid) FROM user_order_send_productdetail s,user_order o WHERE o.userid=$userid AND o.state=1 AND o.ifavalid=1 AND s.orderid=o.ordernmb AND s.userid=o.userid ORDER BY o.ordernmb DESC $limit";
		$p_list=$this->query($sql);
		$list=array();
		if($p_list){
			$box_pro_mod=D("BoxProducts");
			foreach($p_list as $key=>$val){
				$info=$box_pro_mod->getProductsInfo($val['productid']);
				$list[]=$info;
			}
		}
		return $list;
	}
	

	/**
	 * 获取用户待分享产品数
	 */
	public function getProductNumOfNotShare($userid){
		if(!$userid){
			return 0;
		}
		$sql="SELECT COUNT(distinct(relation_id)) as num FROM user_order, user_order_send_productdetail p, inventory_item i WHERE user_order.userid =$userid AND user_order.userid = p.userid AND ifavalid =1 AND state =1 AND user_order.ordernmb=p.orderid AND i.id = p.productid AND NOT EXISTS ( SELECT * FROM user_share WHERE userid =$userid AND resourceid = i.relation_id AND resourcetype =1 AND status>0)";
		$num=$this->query($sql);
		return $num[0]['num'];
	}	
	
	/**
	 * 通过订单号获取付邮试用的产品ID
	 * @author penglele
	 */
	public function getProductidOfTryOrder($orderid){
		if(!$orderid){
			return false;
		}
		$orderinfo=$this->getOrderInfo($orderid,"type");
		if(!$orderinfo || $orderinfo['type']!=C("BOX_TYPE_PAYPOSTAGE")){
			return false;
		}
		$product_send_info=M("UserOrderSendProductdetail")->distinct("productid")->where("orderid=$orderid")->find();
		if(!$product_send_info){
			return false;
		}
		return $product_send_info['productid'];
	}
	
	/**
	 * 通过订单号获取盒子信息
	 * @author penglele
	 */
	public function getBoxInfoByOrderid($id){
		if(!$id){
			return false;
		}
		$orderinfo=$this->getOrderInfo($id,"boxid");
		if(!$orderinfo){
			return false;
		}
		$box_mod=D("Box");
		$boxinfo=$box_mod->getBoxInfo($orderinfo['boxid'],"name,pic,boxid,box_intro,box_price,category");
		$not_type=$box_mod->returnBoxType();
		$not_arr=explode(",",$not_type);
		if(in_array($boxinfo['category'],$not_arr)){
			$boxinfo="";
		}else{
			$boxinfo['boxurl']=getBoxUrl($boxinfo['boxid']);
		}
		return $boxinfo;
	}
	
	/**
	 * 用于在分享详情页中展示与分享相关的盒子信息 准备替换上面的方法 getBoxInfoByOrderid
	 * @param unknown_type $boxid
	 * @author zhenghong
	 */
	public function getBoxInfoByBoxid($boxid){
		if(!$boxid) return "";
		$box_mod=D("Box");
		$boxinfo=$box_mod->getBoxInfo($boxid,"name,pic,boxid,box_intro,box_price,category");
		$not_type=$box_mod->returnBoxType();
		$not_arr=explode(",",$not_type);
		if(in_array($boxinfo['category'],$not_arr)){
			$boxinfo="";
		}else{
			$boxinfo['boxurl']=getBoxUrl($boxinfo['boxid']);
		}
		return $boxinfo;
	}
	
	/**
	 * 获取用户萝莉盒的订单数
	 * @param $userid 用户ID
	 * @param $state 【$state=0未支付，$state=1已支付，$state=2全部】
	 * @author penglele
	 */
	public function getUserOrderNumByStat($userid,$state=2){
		if(!$userid){
			return 0;
		}
		$not_tye=D("Box")->returnBoxType();
		$data=array();
		$data['userid']=$userid;
		$data['type']=array("exp","not in($not_tye)");
		if($state<2){
			if($state==1){
				$data['state']=array('exp',">0");
			}else{
				$data['state']=$state;
			}
		}
		$order_num=$this->getOrderNum($data,0);
		return $order_num;
	}
	
	/**
	 * 获取用户使用订单总数
	 * @author penglele
	 */
	public function getUserTryOrderNumByStat($userid,$state=2){
		if(!$userid){
			return 0;
		}
		$data_trynum['type']=array("exp","in(".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_EXCHANGE_PRODUCT").")");
		$data_trynum['userid']=$userid;
		if($state<2){
			if($state==1){
				$data_trynum['state']=array('exp',">0");
			}else{
				$data_trynum['state']=$state;
			}
		}
		$data_trynum['ifavalid']=1;
		$order_num=$this->getOrderNum($data_trynum,0);
		return $order_num;
	}
	
	/**
	 * 获取用户消耗的礼品卡的总金额
	 * @author penglele
	 */
	public function getUserOrderGiftcardPrice($userid){
		if(!$userid){
			return 0;
		}
		$price=$this->where("userid=$userid AND giftcard>0 AND state=1 AND ifavalid=1")->sum('giftcard');
		return (int)$price;
	}
	
	/**
	 * 获取用户消耗的礼品卡记录
	 * @author penglele
	 */
	public function getUserOrderGiftcardList($userid,$limit=""){
		if(!$userid){
			return '';
		}
		$order="paytime DESC";
		$list=$this->where("userid=$userid AND giftcard>0 AND state=1 AND ifavalid=1")->limit($limit)->order($order)->select();
		if($list){
			foreach($list as $key=>$val){
				if($val['type']==C("BOX_TYPE_PAYPOSTAGE")){
					$list[$key]['status']="付邮试用";
				}else{
					$list[$key]['status']="购买萝莉盒";
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户消耗的礼品卡记录的总数
	 * @author penglele
	 */
	public function getUserOrderGiftcardNum($userid){
		if(!$userid){
			return 0;
		}
		$num=$this->where("userid=$userid AND giftcard>0 AND state=1 AND ifavalid=1")->count();
		return $num;		
	}
	
	/**
	 * 通过时间查找子订单的详细内容
	 * @author penglele
	 */
// 	public function getOrderInfoByTime($orderid,$stime){
// 		$list=array();
// 		if($orderid && $stime){
// 			$list=M("UserOrderSendProductdetail")->field("productid")->distinct(true)->where("orderid=$orderid")->select();
// 		}
// 		return $list;
// 	}
	
	/**
	 * 通过时间判断用户的某一子订单是否已发货
	 * @param  $orderid
	 * @param  $stime
	 * @author penglele
	 */
// 	public function getOrderIfSendByTime($orderid,$stime){
// 		$if_send=0;
// 		if($orderid && $stime){
// 			$info=M("UserOrderSend")->where("orderid=".$orderid." AND childid=".$stime)->find();
// 			if($info && $info['senddate']){
// 				$if_send=1;
// 			}
// 		}
// 		return $if_send;
// 	}
	
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
	
	
	/**
	 * 对user_order中已经支持的订单，如果其是多个月的订单，则对其进行拆分
	 *@author penglele
	 */
	public function splitUserOrder($orderid){
		if(!$orderid){
			return false;
		}
		$order_set_mod=D("BoxOrderSet");
		//订单信息
		$orderinfo=M("UserOrder")->where("ordernmb=".$orderid." AND state=1 AND ifavalid=1")->find();
		if($orderinfo && $orderinfo['paytime']){
			//盒子的子订单时间列表
			$box_stat=$order_set_mod->getBoxIfMonths($orderinfo['boxid'],$orderinfo['paytime']);
			//dump($box_stat);exit;
			if($box_stat['if_mon']==0){
				$box_stat['list'][]=0;
			}
			$send_mod=M("UserOrderSend");
			$sendword_mod=M("UserOrderSendword");
			$proxy_mod=M("UserOrderProxy");
			$time_list=$box_stat['list'];
			
			foreach($time_list as $key=>$val){
				if($val!=0){
					$time_arr=explode("-",$val);
					$time_str=implode("",$time_arr);
				}else{
					$time_str=0;
				}
				
				//订单发送信息
				$send_data=array();
				$send_data['orderid']=$orderid;
				$send_data['child_id']=$time_str;
				$if_send=$send_mod->where($send_data)->find();
				if(!$if_send){
					$send_data['boxid']=$orderinfo['boxid'];
					$send_data['userid']=$orderinfo['userid'];
					$send_data['boxtype']=$orderinfo['type'];
					$send_mod->add($send_data);
				}
				
				//订单赠言信息
				$sendword_data=array();
				$sendword_data['orderid']=$orderid;
				$sendword_data['child_id']=$time_str;
				$if_sendword=$sendword_mod->where($sendword_data)->find();
				if(!$if_sendword){
					$sendword_data['userid']=$orderinfo['userid'];
					$sendword_data['add_date']=date("Y-m-d H:i:s");
					if($orderinfo['sendword']){
						$sendword_data['content']=$orderinfo['sendword'];
					}
					$sendword_mod->add($sendword_data);
				}
				
				//订单快递信息
				$proxy_data=array();
				$proxy_data['orderid']=$orderid;
				$proxy_data['child_id']=$time_str;
				$if_proxy=$proxy_mod->where($proxy_data)->find();
				if(!$if_proxy){
					$proxy_mod->add($proxy_data);
				}
			}
		}
	}
	
	/**
	 * 获取用户子订单列表
	 * @author penglele
	 */
	function getListByOrderID($orderid){
		$return['if_mon']=0;
		if(!$orderid){
			return $return;
		}
		$boxid=$this->where("ordernmb=".$orderid)->getField("boxid");
		if(!$boxid){
			return $return;
		}
		$if_boxset=M("BoxOrderSet")->where("boxid=".$boxid)->getField("months");
		if(!$if_boxset || $if_boxset==0){
			return $return;
		}
		
		$wordsend_mod=M("UserOrderSendword");
		$send_mod=M("userOrderSend");
		$tlist=$send_mod->where("orderid=".$orderid)->order("child_id ASC")->select();
		$return['if_mon']=count($tlist);
		if(!$tlist){
			return $return;
		}
		$list=array();
		$order_send_detail_mod=M("UserOrderSendProductdetail");
		foreach($tlist as $key=>$val){
			$info=array();
			
			$where['orderid']=$orderid;
			$where['child_id']=$val['child_id'];
			$info['if_products']=0;
			$info['tkey']=$val['child_id'];
			$info['tname']=substr($val['child_id'],0,4)."年".substr($val['child_id'],4,2)."月";
			$if_sendword=$wordsend_mod->where($where)->find();//用户是否有赠言
			if($if_sendword && $if_sendword['content']){
				$info['if_sw']=1;
			}else{
				$info['if_sw']=0;
			}
			//订单是否已发货
			if($val['senddate'] && $val['proxysender']){
				$info['if_send']=1;
				$if_products=$order_send_detail_mod->where($where)->find();
				$info['if_products']=$if_products ? 1 : 0 ;
			}else{
				$info['if_send']=0;
			}
			$list[]=$info;
		}
		$return['list']=$list;
		return $return;
	}
	
	
	
	
}