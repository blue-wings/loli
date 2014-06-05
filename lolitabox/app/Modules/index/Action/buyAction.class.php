<?php
/**
 * 购买控制器
 * @author litingting
 */
class buyAction extends commonAction{
     
	/**
	 * 	订购--盒子列表页【正在售卖】
	 * 	@author penglele
	 */
    public function index_2013(){
    		$boxlist=$this->getBoxlist();
		$return['boxlist']=$boxlist;
		$share_mod=D("UserShare");
		$boxidinfo=D("Box")->getBoxidListNotTry();
		$type=array("exp","in(".$boxidinfo.")");
		$sharelist=$share_mod->getOrderShowByBox($type,$this->getlimit(8));
		$count=$share_mod->getOrderShowNumByBox($type);
		$param = array(
				"total" =>$count,
				'result'=>$sharelist,			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>8,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:share_waterfall",//ajax更新模板
		);
		$this->page($param);
		$return['title']="订购萝莉盒-萝莉美妆盒,关注正在热推的多款主题礼盒,适合不同的你-".C("SITE_NAME");
		$this->assign("return",$return);
	    $this->display();
	}
	
	/**
	 *  正在售卖的盒子列表
	 *  @author penglele
	 */
	public function getBoxlist(){
		$boxlist=D("Box")->getBoxList("","toptime DESC,endtime DESC,boxid DESC");
		return $boxlist;
	}
	
	/**
	 * 订购--往期萝莉盒列表
	 * @author penglele
	 */
	public function pastbox(){
		$return['boxlist']=$this->getBoxlist();
		$box_mod=D("Box");
		$list=$box_mod->getBoxList("","endtime DESC,boxid DESC",$this->getlimit(8),3);
		$count=$box_mod->getBoxCount("",3);
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>8,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"buy:pastbox_ajaxlist",//ajax更新模板
		);
		$this->page($param);
		$return['title']="往期萝莉盒列表-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 订购--盒子详情
	 * @author penglele
	 */
	public function show(){
		$boxid=$_GET['boxid'];
		$userid=$this->getUserid();
		$sname=getBoxSessName($boxid,$userid);
		$addname=getAddBoxSessName($boxid,$userid);
		$box_mod=D("Box");
		$return=$box_mod->getBoxDetails($boxid,$userid,$sname,$addname,$this->getlimit(8));
		if(!$boxid || !$return){
			header("location:".U("buy/index"));exit;
		}
		if($return['boxinfo']['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			//积分兑换
			$dis_boxid=$box_mod->getDiscountBoxid();
			if((int)$boxid==$dis_boxid){
				header("location:".U('activity/lolihighv5',array('type'=>2)));exit;
			}
			header("location:".U("try/iexchange"));exit;
		}elseif($return['boxinfo']['category']==C("BOX_TYPE_PAYPOSTAGE")){
			//付邮试用
			header("location:".U("try/index",array("type"=>1)));exit;
		}
		$return['boxlist']=$this->getBoxlist();
		if($return['boxinfo']['zixuan']==1){
			if($return['productlist']){
				$list=array();
				foreach($return['productlist'] as $key=>$val){
					foreach($val as $ikey=>$ival){
						if($ival){
							$ival['pname']="【".$return['cnamelist'][$key]['title']."】".$ival['pname'];
							$list[]=$ival;
						}
					}
				}
				$return['productlist']=$list;
			}
		}
		
		//如果是已登录用户且买过该盒子，给用户一个发分享的入口
		$return['if_toshare']="";
		if($userid){
			$if_order=M("UserOrder")->where("userid=$userid AND boxid=$boxid AND state=1 AND ifavalid=1")->order("ordernmb DESC")->getField("ordernmb");
			if($if_order){
				$return['if_toshare']=$if_order;
			}
		}
		
		$param = array(
				"total" =>$return['sharecount'],
				'result'=>$return['sharelist'],			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>8,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:share_waterfall",//ajax更新模板
		);
		
		//seo 优化
		$pro_arr=array();
		$effect_arr=array();
		if($return['productlist']){
			//将盒子内的产品信息组合
			foreach($return['productlist'] as $val){
				if(!in_array($val['pname'],$pro_arr)){
					$pro_arr[]=$val['pname'];
				}
				if($val['effect'][2]){
					//将产品的功效组合
					$info_effect_arr=explode("，",$val['effect'][2]);
					if($info_effect_arr){
						foreach($info_effect_arr as $ival){
							if(!in_array($ival,$effect_arr)){
								$effect_arr[]=$ival;
							}
						}
					}
				}
			}
		}
		$pro_num=count($pro_arr);
		$pro_str=implode(",",$pro_arr);
		$effect_str=implode(",",$effect_arr);
		$return['title']=$return['boxinfo']['name']."_LOLITABOX-萝莉盒-化妆品试用";
		if($pro_str){
			//当盒子内产品信息已知时：
			$return['keywords']=$pro_str.",化妆品试用装,化妆品,小样,试用装,积分试用,免费试用,付邮试用,按月订购";
			$return['description']=$return['boxinfo']['name']."提供".$pro_num."种化妆品试用，有".$pro_str;
			//已知产品功效时
			if($effect_str){
				$return['description']=$return['description'].",主要功效:".$effect_str;
			}
		}else{
			//当盒子内产品信息未知时：
			$return['keywords']="化妆品试用装,化妆品,小样,试用装,积分试用,免费试用,付邮试用,按月订购";
			$return['description']=$return['boxinfo']['name']."售价：".$return['boxinfo']['box_price'];
			//盒子的描述信息
			if($return['boxinfo']['box_intro']){
				$return['boxinfo']['box_intro']=$return['boxinfo']['box_intro'];
				//$return['boxinfo']['box_intro']=str_replace(array("\r","\n"),"",$return['boxinfo']['box_intro']);
				$return['description']=$return['description'].",".$return['boxinfo']['box_intro'];
			}
			//盒子的备注信息
			if($return['boxinfo']['box_remark']){
				$return['boxinfo']['box_remark']=strip_tags($return['boxinfo']['box_remark']);
				$return['boxinfo']['box_remark']=str_replace(array("\r","\n"),"",$return['boxinfo']['box_remark']);
				$return['description']=$return['description'].",".$return['boxinfo']['box_remark'];
			}
		}
		$return['returnurl']=urlencode(getBoxUrl($boxid));
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 订购--sohu跳转页
	 * @author penglele
	 */
	public function jump_sohu(){
		$this->display();
	}
	
	/**
	 * 订购--支付详情
	 * @author penglele
	 */
	public function detail(){
		$boxid=$_REQUEST["boxid"]; //盒子ID
		$userid=$this->userid;
		$pid=$_GET['id'];//产品id
		$projectid=$_GET['projectid'];
		if((empty($boxid) && !$pid) || !$userid){
			header("location:".U("buy/show",array("boxid"=>$boxid)));
		}
		$box_mod=D("Box");
		if(!$pid){
			$addname=getAddBoxSessName($boxid,$userid);//增值方案存储的session名
			$projectid= $projectid ? $projectid : $_SESSION[$addname];
		}else{
			$boxid=C("BOXID_PAYPOSTAGE");
		}
		$boxinfo=$box_mod->getBoxInfoByBuy($boxid,$projectid,$userid);
		
		//判断当前盒子是否能订购 add by zhenghong 2014-01-02
		if(!$boxinfo["if_order"]) {
			//不能订购的可能情况(人工盒子下线，过售卖期，订单数大于计划数）
			header("location:".U("buy/show",array("boxid"=>$boxid)));
		}
		
		$return['boxinfo']=$boxinfo;
		if($pid){
			//$itemid=D("InventoryItem")->getItemIDByProductid($pid,C("BOXID_PAYPOSTAGE"));
			$pro_info=$this->check_try_product($pid);
			if($pro_info==false){
				//如果当前产品不属于付邮试用产品,或者改产品已兑完，重定向到付邮试用首页
				header("location:".U("try/index"));exit;
			}		
			$return['product_info']=$pro_info;
			$return['boxid']=C("BOXID_PAYPOSTAGE");
			$return['if_try']=1;
		}else{
			//检测当前用户是否有购买权限
			$this->check_user_ifbuy($boxid);
			//如果当前盒子是自选盒，而且还没有选择任何产品
			if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
				$sname=getBoxSessName($boxid,$userid);
				$prr=$box_mod->getSessionProductsList($_SESSION[$sname]);
				if(empty($prr)){
					header("location:".U("buy/zixuan",array("boxid"=>$boxid)));exit;
				}
			}
			if($boxinfo==false){
				header("location:".U("buy/index"));exit;
			}
			//如果选择了增值方案，检测增值方案是否有效
			if($projectid){
				//除了自选盒
				$if_project=$box_mod->checkIfProjectByBoxid($boxid,$projectid);
				if($if_project==0){
					header("location:".U("buy/detail",array("boxid"=>$boxid)));exit;
				}					
			}
			
			if($boxinfo['if_use_coupon']==1){
				$couponlist=D("Coupon")->getUserCouponListByBuy($userid,10,1);
				$return['couponlist']=$couponlist;
			}
			$return['boxid']=$boxinfo['boxid'];			
		}	
		$return['giftcard_price']=D("Giftcard")->getUserGiftcardPrice($userid);	
		$return['addresscount']=D("UserAddress")->getUserAddressCount($userid);
		$return['member_state']=D("Member")->getUserIfMember($userid);
		if($return['member_state']!=1){
			$ndate=date("Y-m-d");
			if($ndate<="2013-12-31"){
				$return['member_price']=5;
			}else{
				$return['member_price']=12;
			}
		}
		
		$return['title']="订购".$boxinfo['name']."-LOLITABOX萝莉盒";
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 订购--确认信息
	 * @author penglele
	 */
	public function confirm(){
		$addressid=$_POST["addres"];
		$couponcode=$_POST["couponcode"];
		$pay_bank=$_POST["pay_bank"];
		$boxid=$_POST["boxid"];
		$pid=$_POST['pid'];
		$if_giftcard=$_POST['if_usegiftcard'];
		$projectid=$_POST['projectid'];
		$sendword=trim($_POST['wordsend_text']);//订单赠言
		if($sendword=="请写下您的赠言，小萝仆纯手工帮你抄写在精美的卡片上，转达你美好的祝福，字写的不好不要怪罪小萝仆哦。"){
			$sendword="";
		}
		
		//如果是付邮试用，判断产品
		if($boxid==C("BOXID_PAYPOSTAGE")){
			$pro_info=$this->check_try_product($pid);
			if($pro_info==false){
				$this->error("您选择的产品已售完，请重新选择");
			}
		}else{
			//判断用户是否有资格购买当前盒子
			$this->check_user_ifbuy($boxid);
		}
		$box_mod=D("Box");
		$userid=$this->getUserid();
		$boxinfo=$box_mod->getBoxInfo($boxid,"name,boxid,category");
		
		if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
			$sname=getBoxSessName($boxid,$userid);
			$add_sname=getAddBoxSessName($boxid,$userid);
		}
		//生成订单
		$order_mod=D("UserOrder");
		$if_order=$order_mod->addOrder($userid,$boxid,$couponcode,$addressid,$pay_bank,$add_sname,$sname,"","",$pid,$if_giftcard,$projectid,$sendword);
		if($if_order==false){
			$this->error("参数不全");
		}else{
			$order_info=$order_mod->getOrderInfo($if_order);
			if($order_info['boxprice']==$order_info['discount'] || $order_info['boxprice']==$order_info['giftcard']){
				//如果是自选盒，生成订单后，如果为全额优惠券支付，则删除session
				if($boxinfo["category"]==C("BOX_TYPE_ZIXUAN")){
					if(isset($_SESSION[$sname])){
						unset($_SESSION[$sname]);
					}
					if(isset($_SESSION[$add_sname])){
						unset($_SESSION[$add_sname]);
					}
				}
// 				$not_type=$box_mod->returnBoxType();
				$not_type=array(C("BOX_TYPE_EXCHANGE"),C("BOX_TYPE_EXCHANGE_PRODUCT"),C("BOX_TYPE_FREEGET"));
				$not_arr=explode(",",$not_type);
				//如果订单不是积分兑换、付邮试用、免费试用
				if(!in_array($boxinfo["category"], $not_arr)){
					$array_return=R("pay/doByBuySuccess",array($order_info['ordernmb'],$order_info['addtime']));
					if($array_return!=false){
						header("location:".U('buy/pay_result',array('id'=>$array_return['orderid'])));
					}else{
						$this->error("add order fail");
					}					
				}
			}
		
			$address_info=D("UserAddress")->getUserAddressInfo($addressid);
			$pay_info=$address_info;
			$pay_info['price']=$order_info['boxprice']-$order_info['discount']-$order_info['giftcard'];
			$pay_info['pay_bank']=$order_info['pay_bank'];
			$pay_info['ordernmb']=$order_info['ordernmb'];
			$pay_info['name']=$boxinfo["name"];
			$pay_info['boxid']=$boxinfo["boxid"];
			$pay_info['discount']=$order_info['discount'];
			$pay_info['coupon']=$order_info['coupon'];
			$pay_info['addtime']=$order_info['addtime'];
			$return['payinfo']=$pay_info;
			
			//是否是自选盒
			if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
				$return['if_select']=1;
			}
			//是否是付邮试用
			if($boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
				$return['if_try']=1;
				$return['pid']=$pid;
			}
		}
		$return['userinfo']=$this->userinfo;
		$return['title']="提交萝莉盒订单-LOLITABOX萝莉盒";
		$this->assign('return',$return);		
		
		$this->display();
	}
	
	/**
	 * 订购--执行支付请求操作
	 * @author penglele
	 */
	public function gopay(){
		header("Content-type: text/html; charset=utf-8");
		//未支付订单再次支付
		if($_REQUEST['paytype']=="repay"){
			$orderid=$_REQUEST["orderid"];
			if(!empty($orderid)){
				$user_order_mod=D("UserOrder");
				$orderinfo=$user_order_mod->getOrderInfo($orderid);
				$boxid=$orderinfo["boxid"];
				$userid=$this->getUserid();
				if($orderinfo && $boxid) {
					if($user_order_mod->getUserOrderStat($orderid)==false){
						header("location:".U("buy/pay_break"));
					}
					$box_mod=D("Box");
					$boxinfo=$box_mod->getBoxInfo($boxid);
					if($boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
						$pid=D("UserOrder")->getProductidOfTryOrder($orderid);
						$try_state=$this->check_try_product($pid);
						if($try_state==false){
							header("location:".U("buy/pay_break"));
						}
					}else{
						$this->check_user_ifbuy($boxid);
					}
					if($boxinfo){
						//当此订单为积分兑换萝莉盒时
						if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
							$out_list=$this->getExchangeOutProductList("",$boxid,$orderid);
							if($out_list!=""){
								header("location:".U("buy/pay_break"));
							}
						}
						$coupon_info=D("Coupon")->getDiscountByCoupon($boxid, $orderinfo["coupon"]);
						$coupon_price=$coupon_info['discount'];

						//如果优惠券的金额小于当初生成订单时的折扣金额，则说明优惠券已失效或已使用，订单失效
						if($coupon_info['discount']<$orderinfo['discount']){
							header("location:".U("buy/pay_break"));
						}

						//如果当前订单的盒子是自选盒子时，判断订单内的产品是否符合规则
						if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
							if($orderinfo['discount']>0 && $orderinfo['coupon']==""){
								//如果当前用户使用了购盒卡，检测当前订单与购盒卡是否绑定
								if($this->checkUserOrderBoxcard($orderid)==false){
									header("location:".U("buy/pay_break"));
									//$this->display("pay_break");exit;
								}
							}
							if(D("BoxProducts")->checkRepayProduct($orderid,$boxid,$userid)==false){
								header("location:".U("buy/pay_break"));
								//$this->display("pay_break");exit;
							}
							//判断加价购活动是否还存在
							if($orderinfo['projectid']>0 && $boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
								$project_info=M("BoxProject")->getById($orderinfo['projectid']);
								if(!$project_info || $project_info[status]==0){
									header("location:".U("buy/pay_break"));
									//$this->display("pay_break");exit;
								}
							}
						}
						if($coupon_price) {
							$order_price=$orderinfo["boxprice"]-$coupon_price;
						}
						else {
							$order_price=$orderinfo["boxprice"]-$orderinfo["discount"]-$orderinfo['giftcard'];
						}
						echo "<form name=\"form1\" method=\"post\" id=\"form1\" action=\"".U('pay/alipayto')."\" >\r\n";
						echo "<input type=\"hidden\" name=\"ordernmb\" value=\"".$orderinfo["ordernmb"]."\"/>\r\n";
						echo "<input type=\"hidden\" name=\"total_fee\" value=\"".$order_price."\"/>\r\n";
						echo "<input type=\"hidden\" name=\"subject\" value=\"".$boxinfo["name"]."\"/>\r\n";
						echo "<input type=\"hidden\" name=\"body\" value=\"".$boxinfo["name"]."\"/>\r\n";
						echo "<input type=\"hidden\" name=\"pay_bank\" value=\"".$orderinfo["pay_bank"]."\"/>\r\n";
						echo "<input type=\"submit\" name=\"submit1\" style=\"display:none\"/>";
						echo "</form>\r\n";
						echo "<script>\r\n";
						echo " if ((navigator.userAgent.indexOf('MSIE') >= 0) && (navigator.userAgent.indexOf('Opera') < 0)){ \r\n";
						echo "	document.form1.submit(); \r\n";
						echo "}else if (navigator.userAgent.indexOf('Firefox') >= 0){ \r\n";
						echo "	document.form1.submit1.click(); \r\n";
						echo "}else if (navigator.userAgent.indexOf('Opera') >= 0){ \r\n";
						echo "	document.form1.submit();";
						echo "}else{";
						echo "	document.form1.submit();";
						echo "}";
						echo "</script>";
						exit();
					}
					else {
						$this->error("对不起，暂时无法完成您的操作要求:您所购买的商品已经被删除");exit;
					}
				}
				else {
					$this->error("对不起，暂时无法完成您的操作要求:订单不存在");exit;
				}
			}
			else {
				$this->error("对不起，暂时无法完成您的操作要求！");exit;
			}
		}
		else
		{
			//正常去支付
			$ordernmb=$_POST['ordernmb'];
			if($ordernmb){
				$order_mod=M("UserOrder");
				$order_info=$order_mod->field("boxid,userid")->where("ordernmb=$ordernmb")->find();
				if($order_info){
					$boxid=$order_info['boxid'];
					$userid=$order_info['userid'];
					$box_mod=M("box");
					$box_info=$box_mod->field("category")->where("boxid=$boxid")->find();
					if($box_info){
						if($box_info['category']==C("BOX_TYPE_ZIXUAN")){
							$sname=getBoxSessName($boxid,$userid);
							$add_sname=getAddBoxSessName($boxid,$userid);
							if(isset($_SESSION[$sname])){
								unset($_SESSION[$sname]);
							}
							if(isset($_SESSION[$add_sname])){
								unset($_SESSION[$add_sname]);
							}
						}
						//如果是积分兑换，删除session
						if($box_info['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
							$exchange_sname=getExchangeProductSessName($boxid,$userid);
							unset($_SESSION[$exchange_sname]);
						}
					}
				}
			}

			$name=$_POST['name'];
			$pay_bank=$_POST['pay_bank'];
			$price=$_POST['price'];
			echo "<form name=\"form1\" method=\"post\" id=\"form1\" action=\"".U('pay/alipayto')."\" >\r\n";
			echo "<input type=\"hidden\" name=\"ordernmb\" value=\"".$ordernmb."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"total_fee\" value=\"".$price."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"subject\" value=\"".$name."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"body\" value=\"".$name."\"/>\r\n";
			echo "<input type=\"hidden\" name=\"pay_bank\" value=\"".$pay_bank."\"/>\r\n";
			echo "<input type=\"submit\" name=\"submit1\" style=\"display:none\"/>";
			echo "</form>\r\n";
			echo "<script>\r\n";
			echo " if ((navigator.userAgent.indexOf('MSIE') >= 0) && (navigator.userAgent.indexOf('Opera') < 0)){ \r\n";
			echo "	document.form1.submit(); \r\n";
			echo "}else if (navigator.userAgent.indexOf('Firefox') >= 0){ \r\n";
			echo "	document.form1.submit1.click(); \r\n";
			echo "}else if (navigator.userAgent.indexOf('Opera') >= 0){ \r\n";
			echo "	document.form1.submit();";
			echo "}else{";
			echo "	document.form1.submit();";
			echo "}";
			echo "</script>";
			exit();
		}
	}
	
	
	/**
	 * 选择产品的ajax方法--自选
	 * @param $boxid 盒子ID [必须]
	 * @param $pid 产品id【inventory_item下id】[必须]
	 * @param $type 所选产品所属分类[必须]
	 * @author penglele
	 */
	public function ajax_select_product(){
		$pid=$_POST["pid"];//pid为inventory_item下的id
		$type=$_POST['type'];//当前单品所属分类
		if(!$this->isAjax() || !$pid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$userid=$this->getUserid();
		if(!isset($userid) || empty($userid)){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$boxid=$_POST["boxid"];//当前单品所属盒子
		$boxinfo=D("Box")->getBoxInfo($boxid);
		if(!$boxinfo){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$ntime=date("Y-m-d");
		if($ntime<$boxinfo['starttime']){
			$this->ajaxReturn(0,"还没开始售卖，敬请关注！",0);
		}
		//判断当前盒子是否是新用户才能购买、且是否可以重复购买
		$userinfo=$this->userinfo;
		//是否只运行新用户购买
		if($boxinfo['only_newuser']==1){
			if($userinfo['order_num']>0){
				$this->ajaxReturn(100,"只允许新用户购买",0);
			}
		}
		//是否是特权会员才能购买
		if($boxinfo['only_member']==1 && $userinfo['if_member']!=1){
			$this->ajaxReturn(300,"只允许特权用户购买",0);
		}
		
		//是否允许用户重复购买
		if($boxinfo['if_repeat']==0){
			$if_order=M("UserOrder")->where("boxid=$boxid AND userid=$userid AND state=1")->find();
			if($if_order){
				$this->ajaxReturn(200,"不能重复购买",0);
			}
		}
		$sname=getBoxSessName($boxid,$userid);
		$box_pro_mod=M("BoxProducts");
		$box_pro_info=$box_pro_mod->where("pid=$pid AND boxid=$boxid AND ishidden=0")->find();
		if(!isset($_SESSION[$sname])){
			$this->ajaxReturn(0,"产品已售罄",0);
		}else{//如果session已存在
			if(in_array($pid,$_SESSION[$sname][$type])){
				$this->ajaxReturn(0,"您已经选择过该产品",0);
			}
			//判断产品库存是否>0 
			$inventory_realnum=D("InventoryItem")->getProductInventory($pid,$boxid);
			if(!$box_pro_info || $inventory_realnum<0){
				$this->ajaxReturn(0,"您选择的产品已售完",0);
			}
			if(!empty($_SESSION[$sname][$type])){
				$select_num=0;
				for($i=1;$i<=$type;$i++){
					if($_SESSION[$sname][$type][$i]!=""){
						$select_num++;
					}
				}
				if($select_num>=$type){
					$this->ajaxReturn(0,"该类产品可选数量已达上限",0);
				}
			}
		}
		for($i=1;$i<=$type;$i++){
			if($_SESSION[$sname][$type][$i]==""){
				$_SESSION[$sname][$type][$i]=$pid;
				break;
			}
		}
		$product_info=D("BoxProducts")->getProductsInfo($pid);
		$this->ajaxReturn(1,$product_info,1);
	}
	
	/**
	 * 删除选中的产品（ajax）--自选
	 * @param $boxid 删除的产品所属盒子id [必须]
	 * @param $pid 产品id【inventory_item下id】[必须]
	 * @author penglele
	 */
	public function delete_products_select(){
		$boxid=$_POST['boxid'];
		$pid=$_POST['pid'];////$pid为inventory_item下的id
		$userid=$this->getUserid();
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		
		if(empty($pid) || empty($boxid) || !$this->isAjax()){
			$this->ajaxReturn(0,"非法操作",0);
		}
		
		$sname=getBoxSessName($boxid,$userid);//session名
		
		foreach($_SESSION[$sname] as $key_one=>$value){
			foreach($value as $key_two=>$pid_value){
				if($pid_value==$pid){
					$_SESSION[$sname][$key_one][$key_two]="";
					$this->ajaxReturn($key_one,"success",1);
				}
			}
		}
	}	
	
	/**
	 * 订购抢购的加价购
	 * @author penglele
	 */
	public function get_hongbao(){
		$id=$_POST['id'];
		$boxid=$_POST['boxid'];
		$userid=$this->getUserid();
		if(empty($userid) || !$this->isAjax() || empty($id) || empty($boxid)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$sname=getAddBoxSessName($boxid,$userid);
		$_SESSION[$sname]=$id;
		$this->ajaxReturn(1,"success",1);
	}	
	
	/**
	 * 取消红包
	 * @author penglele
	 */
	public function to_cancel_hongbao(){
		$boxid=$_POST['boxid'];
		$userid=$this->getUserid();
		if(empty($userid) || !$this->isAjax() || empty($boxid)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$sname=getAddBoxSessName($boxid,$userid);
		unset($_SESSION[$sname]);
		$this->ajaxReturn(1,"success",1);
	}
	
	/**
	 * 检测选择的产品是否符合规则
	 * @param $boxid 检测盒子的id[必须]
	 * @author penglele
	 */
	public function check_box_products($box_id=""){
		$boxid= $box_id=="" ? $_REQUEST['boxid'] : $box_id;
		$userid=$this->getUserid();
		//判断userid、boxid是否存在
		if(!$userid || !$boxid){
			if($this->isAjax()){
				$this->ajaxReturn(0,"非法操作",0);
			}else{
				return false;
			}
		}
		
		//判断是否是特权会员专属盒
		$boxinfo=D("Box")->getBoxInfo($boxid);
		
		$ntime=date("Y-m-d");
		if($ntime<$boxinfo['starttime']){
			$this->ajaxReturn(0,"还没开始售卖，敬请关注！",0);
		}
		
		$userinfo=$this->userinfo;
		if($boxinfo['only_member']==1 && $userinfo['if_member']!=1){
			if($this->isAjax()){
				$this->ajaxReturn(0,"该萝莉盒只允许特权会员购买",0);
			}else{
				return false;
			}
		}
		
		$sname=getBoxSessName($boxid,$userid);
		$box_pro_mod=M("BoxProducts");
		$products_arr=D("Box")->getSessionProductsList($_SESSION[$sname]);
		if(empty($_SESSION[$sname]) || !isset($_SESSION[$sname]) || empty($products_arr)){
			if($this->isAjax()){
				$this->ajaxReturn(0,"您还未选择任何产品",0);
			}else{
				return false;
			}
		}
		$sale_out_product_arr=array();
		$total_product_num=0;
		$box_products_mod=D("BoxProducts");
		foreach($_SESSION[$sname] as $key_one=>$value_first){
			$total_product_num=$total_product_num+$key_one;
			$select_num=0;
			foreach($value_first as $key_two=>$value_two){
				if($value_two!=""){
					$select_num++;
					$box_products_info=$box_pro_mod->where("pid=$value_two AND boxid=$boxid")->find();
					//新增判断产品库存是否>0 update by 2013-05-24
					$inventory_realnum=$box_products_mod->getProductInventoryEstimatedNum($value_two);
					if($box_products_info[ptotal]-$box_products_info[pquantity]-$box_products_info[saletotal]<0 || $inventory_realnum<=0){
						$sale_out_product_arr[]=$value_two;//售完产品的pid数组
						$_SESSION[$sname][$key_one][$key_two]="";
					}
				}
				if($select_num>$key_one){
					if($this->isAjax()){
						$this->ajaxReturn(0,"您选择的产品不符合规则",0);
					}else{
						return false;
					}
				}
			}
		}
		if(count($sale_out_product_arr)!=0){
			if($this->isAjax()){
				$this->ajaxReturn(200,"部分产品已售完",0);
			}else{
				return false;
			}
		}
		if(count($products_arr)<$total_product_num){
			if($this->isAjax()){
				$this->ajaxReturn(100,$total_product_num,1);
			}else{
				return true;
			}
		}else{
			if($this->isAjax()){
				$this->ajaxReturn(1,"success",1);
			}else{
				return true;
			}
		}
	}
	
	/**
	 * 用户地址-----订购核对信息页用户地址列表
	 * @author penglele
	 */
	public function getuserAddress(){
		$userid=$this->userid;
		$id=$_REQUEST["id"];
		if($id==1){
			$order="if_active DESC,addtime DESC";
		}else{
			$order="addtime DESC";
		}
		$addres_list=D("UserAddress")->getUserAddressList($userid,$order);
		if($addres_list){
			$this->ajaxReturn($addres_list,'success',1);
		}else{
			$this->ajaxReturn(0,'fail',0);
		}
	}
	
	/**
	 * 检查优惠券是否可以使用
	 * @author penglele
	 */
	public function check_coupon($couponcode=""){
		if($couponcode==""){
			$couponcode=$_REQUEST["couponcode"];
		}
		if(empty($couponcode)){
			if($this->isAjax()){
				$this->ajaxReturn(0,'没有填写优惠券!',0);
			}
			else {
				return false;
			}
		}
		$coupon_model=M("coupon");
		$couponinfo=$coupon_model->where("code='$couponcode'")->find();
		if($couponinfo){
			if($couponinfo["status"]=='2'){
				if($this->isAjax()){
					$this->ajaxReturn("1",'优惠券已经被使用过!',0);
				}
				else {
					return false;
				}
			}
			if(date("Y-m-d H:i:s")>$couponinfo["endtime"]){
				if($this->isAjax()){
					$this->ajaxReturn("2",'优惠券已经过期!',0);
				}
				else {
					return false;
				}
			}
			if($this->isAjax()){
				$this->ajaxReturn($couponinfo[price],'优惠券可以使用!',1);
			}
			else {
				return $couponinfo[price];
			}
		}
		else {
			if($this->isAjax()){
				$this->ajaxReturn('3','优惠券不存在!',0);
			}
			else {
				return false;
			}
		}
	}
	
	
	/**
	 * 获取自选产品的产品列表信息ajax
	 * @author penglele
	 */
	public function get_product_list(){
		//判断当前的盒子是否是自选盒子
		$boxid=$_REQUEST['boxid'];
		$box_mod=M("box");
		$box_info=$box_mod->where("boxid=$boxid")->find();
		$userid=$this->userid;
		if(isset($box_info) && $box_info['category']==C("BOX_TYPE_ZIXUAN") && $userid){
			$box_products_mod=M("BoxProducts");
			$sname=getBoxSessName($boxid,$userid);
			$products_select_list=array();
			$session_product_arr=D("Box")->getSessionProductsList($_SESSION[$sname]);//session下的单品数组
			$boxpro_mod=D("BoxProducts");
			for($i=0;$i<count($session_product_arr);$i++){
				//产品信息
				$products_info=$boxpro_mod->getProductsInfo($session_product_arr[$i]);
				//自选中当前产品的份数
				$box_products_info=$box_products_mod->where("boxid=$boxid AND pid=".$session_product_arr[$i])->find();
				$products_info['num']=$box_products_info[pquantity];
				$products_select_list[]=$products_info;
			}
			//加价购产品
			$add_sname=getAddBoxSessName($boxid,$userid);
			if(isset($_SESSION[$add_sname]) && !empty($_SESSION[$add_sname])){
				$project_list=D("UserOrder")->getProjectListByBoxid($_SESSION[$add_sname],$boxid);
				if($project_list){
					foreach($project_list as $key=>$value){
						$products_select_list[]=$value;
					}
				}
			}
		}
		else{
			$products_select_list="";
		}
		$this->ajaxReturn(1,$products_select_list,1);
	}	
	
	
	

	/**
	 * 检测用户是否有购买权限、或者盒子是否售完等
	 * @author penglele
	 */
	public function check_user_ifbuy($box_id=""){
		//查看用户是否购买过盒子和现在是否还有盒子ajax
		$boxid=empty($box_id)?$_REQUEST["boxid"]:$box_id;
		if(!$boxid){
			return false;
		}
		//判断当前用户是否已经登录
		$userid=$this->getUserid();
		if(empty($userid) || !$userid){
			if($this->isAjax()){
				$this->ajaxReturn(0,"您还没有登录",0);
			}else{
				echo "<script>history.back(-1)</script>";exit;
			}
		}
		//判断盒子的售卖数量和截止日期
		$box_info=D("Box")->getBoxInfo($boxid);
		
		//如果当期盒子是积分兑换pass
		if($box_info['category']==C("BOX_TYPE_EXCHANGE_PRODUCT") && $box_info['state']==1) {
			if($this->isAjax()){
				$this->ajaxReturn(1,'pass!',1);
			}else{
				return true;
			}
		}
		$current_date=date("Y-m-d",time());
		if($box_info["starttime"]>$current_date){
			if($this->isAjax()){
				$this->ajaxReturn(0,'还没开始售卖！',0);
			}else{
				echo "<script>history.back(-1)</script>";exit;
			}				
		}
		$box_quantity=$box_info["quantity"];
		$box_order_count=D("UserOrder")->getOrderNum(array("boxid"=>$boxid));
		
		if($box_order_count-$box_quantity>=0 || $box_info["endtime"]<$current_date){	//判断当前的盒子是否已售完
			if($this->isAjax()){
				$this->ajaxReturn(100,'sale over!',0);
			}else{
				return true;
				header("location:".U("buy/sale_over"));
			}
		}
	
		//判断用户是否可以购买新会员盒子
		$userinfo=$this->userinfo;
		if($box_info['only_newuser']==1){
			if($userinfo["order_num"]>0){
				if($this->isAjax()){
					$this->ajaxReturn(200,'have buy others !',0);exit;
				}else{
					echo "<script>history.back(-1)</script>";exit;
				}
			}else{
				//未绑定手机
				if($userinfo['tel_status']==0){
					if($this->isAjax()){
						$this->ajaxReturn(300,'have buy others !',0);exit;
					}else{
						echo "<script>history.back(-1)</script>";exit;
					}					
				}
			}
		}
		
		//特权会员专享盒
		if($box_info['only_member']==1){
			if($userinfo['if_member']!=1){
				if($this->isAjax()){
					$this->ajaxReturn(400,'member user can buy',0);exit;
				}else{
					echo "<script>history.back(-1)</script>";exit;
				}
			}
		}
		
		//判断当前用户是否可以购买SOLO盒
		if($box_info['category']==C("BOX_TYPE_SOLO")){
			$open_mod=M("UserOpenid");
			$ret=$open_mod->where("type='sohu' AND uid=$userid")->find();
			if($ret==""){
				$url=getSoloJumpUrl();
				if($this->isAjax()){
					$this->ajaxReturn(1000,$url,0);exit;
				}else{
					header("location:".$url);exit;
				}
			}
		}
	
		//判断当前盒子是否可以重复购买:【if_repeat=1可以】 【if_repeat=0不可以】
		if($box_info['if_repeat']==1){
			if($this->isAjax()){
				$this->ajaxReturn(1,'pass!',1);
			}else{
				return true;
			}
		}else{
			$userorder_model=M("UserOrder");
			$user_ifbuy=$userorder_model->where("userid=$userid AND boxid=$boxid AND state=1")->count();
			if($user_ifbuy>0){
				if($this->isAjax()){
					$this->ajaxReturn(11,'user already !',0);exit;
				}else{
					header("location:".U("buy/user_already_buy"));
				}
			}
		}
		//DEFAULT
		if($this->isAjax()){
			$this->ajaxReturn('1','pass!',1);
		}else{
			return true;
		}
	}	
	
	/**
	 * 用户已购买的模板
	 * @author penglele
	 */
	public function user_already_buy(){
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 订单失效模板
	 */
	public function pay_break(){
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 判断盒子已售完的模板
	 */
	public function sale_over(){
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 支付--返回结果页面    【-----暂定-----】
	 * @author penglele
	 */
	public function pay_result(){
		$orderid=$_GET["id"];
		if(empty($orderid)){
			$this->error("缺少参数");
		}
		$userid=$this->userid;
		$userorder_model=M("UserOrder");
		$orderinfo=$userorder_model->getByOrdernmb($orderid);
		if($orderinfo['userid']!=$userid){
			echo "<script>history.back(-1);</script>";exit;
		}
		$return['state']=$orderinfo["state"];
		$return['orderid']=$orderid;
		if($orderinfo['type']==C("BOX_TYPE_PAYPOSTAGE")){
			$return['if_try']=1;
		}
		if($orderinfo["state"]==1){
			$not_type=D("Box")->returnBoxType();
			$type_arr=explode(",",$not_type);
			if(!in_array($orderinfo['type'],$type_arr) && $orderinfo['discount']<=0 && $orderinfo['giftcard']<=0){
				$return['if_score']=1;
			}
			//盒子的发货时间
			$box_order_set_info=M("box_order_set")->where("boxid=".$orderinfo['boxid'])->find();
			if($box_order_set_info){
				if($box_order_set_info['post_date'] && $box_order_set_info['post_date']!="0000-00-00" ){
					$return['boxinfo']['box_senddate']=$box_order_set_info['post_date'];
				}else{
					$post_day=$box_order_set_info['post_day'];
					if($post_day){
						$post_day=(int)$post_day-5;
						$tday=D("Public")->getPerDate($orderinfo['paytime'],2);
						$postdate=date("Y-m")."-".$box_order_set_info['post_day'];
						if($tday<=$post_day){
							$return['boxinfo']['box_senddate']=$postdate;
						}else{
							$return['boxinfo']['box_senddate']=date("Y-m-d",strtotime($postdate." 1 months"));
						}
					}
				}
			}
			if(!$return['boxinfo']['box_senddate']){
				$return['boxinfo']=D("Box")->getBoxInfo($orderinfo['boxid'],"box_senddate");
			}
			
			$return['title']="成功订购萝莉盒-LOLITABOX萝莉盒";
		}else{
			$return['title']="订购萝莉盒出现问题-LOLITABOX萝莉盒";
		}
		$return['userinfo']=$this->userinfo;
		$this->assign('return',$return);
		$this->display();
	}

	/**
	 * 判断付邮试用的产品是否已售完
	 * @param $pid 单品ID
	 * @author penglele
	 */
// 	public function check_try_products(){
// 		$pid=$_POST['pid'];
// 		if(!$pid){
// 			$this->ajaxReturn(0,"fail",0);
// 		}
// 		$boxid=C("TRY_BOX_ID");
// 		$num=D("InventoryItem")->getProductInventory($pid,$boxid);
// 		if($num<0){
// 			$this->ajaxReturn(100,"fail",0);
// 		}
// 		$this->ajaxReturn(1,"success",1);
// 	}
	

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
	 * 订购--自选页面【只有正在售卖的自选盒才显示】
	 * @author penglele
	 */
	public function zixuan(){
		$boxid=$_GET['boxid'];
		$userid=$this->userid;
		if(!$boxid){
			header("location:".U("buy/index"));exit;
		}
		$box_mod=D("Box");
		$box_state=$box_mod->getBoxState($boxid);
		if($box_state==false || $box_state==3){
			header("location:".U("buy/index"));exit;
		}
		$ndate=date("Y-m-d");
		$boxinfo=$box_mod->getBoxInfo($boxid);
		if($boxinfo['category']!=C("BOX_TYPE_ZIXUAN")){
			header("location:".U("buy/index"));exit;
		}
		$sname=getBoxSessName($boxid,$userid);
		$return=D("Box")->getZixuanDetails($boxid,$userid,$box_state,$sname);
		$return['boxinfo']=$boxinfo;
		$return['title']=$boxinfo['name']."详情-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 检测付邮试用产品是否符合规则
	 * @param $pid 单品ID
	 * @author penglele
	 */
	public function check_try_product($pid){
		$pid= $pid ? $pid : $_POST['pid'] ;
		if(!$pid){
			if($this->isAjax()){
				$this->ajaxReturn(0,"fail",0);
			}else{
				return false;
			}
		}
		$userid=$this->userid;
		if(!$userid){
			if($this->isAjax()){
				$this->ajaxReturn(0,"not login",200);
			}else{
				return false;
			}			
		}
		$userinfo=$this->userinfo;
		$ifmember=D("Member")->getUserIfMember($userid);
		if($ifmember!=1){
			if($this->isAjax()){
				$this->ajaxReturn(0,'fail',300);
			}else{
				return false;
			}			
		}
		
		$boxid=C("BOXID_PAYPOSTAGE");
		
		//判断该产品是否是付邮试用
		$if_try=D("BoxProducts")->getTryProductInfo($pid);
		if($if_try==false){
			if($this->isAjax()){
				$this->ajaxReturn(0,"fail",100);
			}else{
				return false;
// 				echo "<script>history.back(-1)</script>";exit;
			}			
		}
		
		//判断付邮试用产品是否还有剩余量
		$pro_info=D("Products")->getSimpleInfoByItemid($pid);
		if(!$pro_info || $pro_info['now_num']<0){
			if($this->isAjax()){
				$this->ajaxReturn(0,"fail",100);
			}else{
				return false;
			}
		}
		
		//付邮试用增加试用周期update by penglele 2013-10-31 10:33:55
		$dateinfo=D("InventoryItem")->checkPidInterval($pid,$userid,C("BOX_TYPE_PAYPOSTAGE"));
		if($dateinfo['time']!=""){
			if($this->isAjax()){
				$this->ajaxReturn(0,$dateinfo,700);
			}else{
				return false;
			}			
		}
		
		if($this->isAjax()){
			$this->ajaxReturn($pro_info,"success",1);
		}else{
			return $pro_info;
		}
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
	 * 重新支付时，如果是自选盒，则判断用户已选的产品是否有已售完的
	 * @param orderid 用户订单id【必须】
	 * @param boxid 盒子id【必须】
	 * @author penglele
	 */
	public function checkRepayProduct($orderid,$boxid){
		if(empty($orderid) || empty($boxid)){
			return false;
		}
		$userid=$this->getUserid();
		if(empty($userid)){
			return false;
		}
		$order_send_mod=M("UserOrderSendProductdetail");
		$box_product_mod=M("BoxProducts");
		//UserOrderSendProductdetail表中，当前订单下的产品列表
		$order_product_list=$order_send_mod->where("orderid=$orderid AND userid=$userid")->select();
		if(!$order_product_list){
			return false;
		}
		$box_pro_mod=D("BoxProducts");
		for($i=0;$i<count($order_product_list);$i++){
			//通过productid与boxproduct表关联，查看当前非产品是否已售完，如果有return false
			$product_info=$box_product_mod->field("ptotal,saletotal,pquantity,maxquantitytype")->where("boxid=$boxid AND pid=".$order_product_list[$i]['productid'])->find();
			//新增判断产品库存是否>0 update by 2013-05-24
			$inventory_realnum=$box_pro_mod->getProductInventoryEstimatedNum($order_product_list[$i]['productid']);
			if(!$product_info || (($product_info['ptotal']-$product_info['saletotal']-$product_info['pquantity']<0 || $inventory_realnum<=0) && $product_info['maxquantitytype']!=0)){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 统计盒子信息
	 * @author penglele 2013-10-21 10:10:12
	 */
	public function get_box_list(){
		D("Article")->getBoxlistInfoByDate();
	}
	
	/**
	 * 获取盒子的剩余量【仅限几个特殊的盒子】
	 * @author penglele
	 */
	public function get_box_surplus(){
		$boxid=$_GET['boxid'];
		$arr=array(122,123,124);
		$num=0;
		if(!empty($boxid) && in_array($boxid,$arr)){
			$total_num=M("Box")->where("boxid=".$boxid)->getfield("quantity");
			$order_num=D("UserOrder")->getOrderNum(array('boxid'=>$boxid));
			$num=$total_num-$order_num;
			$num=$num<0 ? 0 : $num ;
 		}
 		echo "document.write($num);";
	}
	

	
	/**
	 * 拆分以前的的订单
	 * @author penglele
	 */
	public function splitUserOrderBefore(){
		exit("stop run!");  //zhenghong 2014-01-02
		//确认需要处理的盒子id的范围
		$boxlist=M("BoxOrderSet")->select();
		$box_arr=array();
		if($boxlist){
			foreach($boxlist as $val){
				$box_arr[]=$val['boxid'];
			}
			$box_str=implode(",",$box_arr);
			$where['state']=1;
			$where['ifavalid']=1;
			$where['boxid']=array("exp","in ($box_str)");
			//找出需要拆分的订单列表
			$orderlist=M("UserOrder")->where($where)->select();
			//dump($orderlist);exit;
			if($orderlist){
				$order_mod=D("UserOrder");
				foreach($orderlist as $val){
					//拆分订单
					$order_mod->splitUserOrder($val['ordernmb']);
				}
			}
		}
		echo "end";exit;
	}
	
	/**
	 * 指定订单号进行拆分
	 * @author zhenghong@lolitabox.com
	 */
	public function testSplitOrder(){
		$orderid="20140102110604454";
		$order_mod=D("UserOrder");
		$order_mod->splitUserOrder($orderid);
	}
	
	
	/**
	 * 新萝莉美妆盒
	 * @author penglele
	 */
	public function index(){
		$ndate=date("Y-m-d");
		$box_mod=D("Box");
		$where=array();
		$where['state']=1;
		$where['if_hidden']=0;
		$list=array();
		//限量萝莉美妆盒
		$blist=$box_mod->getBoxListOnSellingByType(C("BOX_TYPE_SUIXUAN"),"","",array("if_hidden"=>0,array('only_newuser'=>0)));
		if(!$blist){
			$where1=$where;
			$where1['category']=C("BOX_TYPE_SUIXUAN");
			$where1["starttime"]=array("elt",$ndate);
			$where1['only_newuser']=0;
			$blist=$box_mod->getBoxListByCondition($where1,2);
		}
		$list[1]=$blist;
		
		//当月萝莉美妆盒
		$where2=$where;
		$where2['category']=C("BOX_TYPE_DEFAULT");
		$where2["starttime"]=array("elt",$ndate);
		$info2=$box_mod->getBoxListByCondition($where2,1);
		if($info2[0]){
			$time_arr=explode("-",$info2[0]['box_senddate']);
			$info2[0]['year']=$time_arr[0];
			$info2[0]['mon']=$time_arr[1];
			$info2[0]['day']=$time_arr[2];
		}
		$list[2]=$info2[0];
		
		//全年萝莉美妆盒
		$list[3]="";
		
		//新会员独享神秘盒
		$where4=$where;
		$where4['category']=C("BOX_TYPE_SUIXUAN");
		$where4["starttime"]=array("elt",$ndate);
		$where4['only_newuser']=1;
		$info4=$box_mod->getBoxListByCondition($where4,1);
		$list[4]=$info4[0];
		
		//随心选萝莉美妆盒
		$where5=$where;
		$where5['category']=C("BOX_TYPE_ZIXUAN");
		$where5["starttime"]=array("elt",$ndate);
		$where5['only_member']=1;
		$info5=$box_mod->getBoxListByCondition($where5,1);
		$list[5]=$info5[0];
		
		//SOLO神秘盒
		$where6=$where;
		$where6['category']=C("BOX_TYPE_SOLO");
		$where6["starttime"]=array("elt",$ndate);
		$info6=$box_mod->getBoxListByCondition($where6,1);
		if($info6[0]){
			$time_arr=explode("-",$info6[0]['box_senddate']);
			$info6[0]['year']=$time_arr[0];
			$info6[0]['mon']=$time_arr[1];
			$info6[0]['day']=$time_arr[2];
		}
		$list[6]=$info6[0];
		$return['title']="订购萝莉美妆盒-".C("SITE_NAME");
		$this->assign("boxlist",$list);
		$this->assign("return",$return);
		$this->display("index_all");
	}
	
	public function index_all(){
		$this->index();
	}

	/**
	 * 吃货盒
	 * @author penglele
	 * @author zhenghong
	 */
	public function tch(){
		$return['title']="订购贪吃盒-".C("SITE_NAME");
		$return['keywords']="吃货俱乐部,好吃的零食,办公室零食,网购零食";
		$return['description']="2014年订购萝莉吃货盒加入到吃货俱乐部来吧，这里有萝莉盒为你精心挑选好吃健康的零食，是你居家、办公室零食的绝佳选择；每月定期准时送到，不必为各种网购零食烦恼，还免邮费！";
		$type=$_GET["type"];
		switch($type) {
			case "share":
				//晒盒分享
				$boxid_str="109,128,133,134,135,136";
				$list=D("Box")->getShareListByBoxID($boxid_str);
				$this->assign("list",$list);
				break;
			default:
				//往期揭密
				$datalist=D("Article")->getTchHistoryData(12);
				$this->assign("historylist",$datalist); //往期揭密
				break;
		}
		$this->assign("return",$return);
		$this->display();
	}	

}

?>
