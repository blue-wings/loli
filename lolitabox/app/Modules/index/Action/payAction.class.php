<?php
class payAction extends commonAction {
	
	/**
     +----------------------------------------------------------
	 * 支付宝接口 alipayto           把参数传给支付宝 
     +----------------------------------------------------------
	 */
	function alipayto() {
		header("Content-type: text/html; charset=utf-8");
		require_once (ALIPAY_ROOT . "alipay.config.php");
		require_once (ALIPAY_ROOT . "lib" . DIRECTORY_SEPARATOR . "alipay_service.class.php");
		/**************************请求参数**************************/
		//请与贵网站订单系统中的唯一订单号匹配
		$out_trade_no = $_POST['ordernmb'];
		//订单名称，显示在支付宝收银台里的“商品名称”里，显示在支付宝的交易管理的“商品名称”的列表里。
		$subject      = $_POST['subject'];
		//订单描述、订单详细、订单备注，显示在支付宝收银台里的“商品描述”里
		$body         = $_POST['body'];
		//订单总金额，显示在支付宝收银台里的“应付总额”里
		$total_fee    = $_POST['total_fee'];
		//扩展功能参数——默认支付方式//
		//默认支付方式，取值见“即时到帐接口”技术文档中的请求参数列表
		$paymethod    = '';
		//默认网银代号，代号列表见“即时到帐接口”技术文档“附录”→“银行列表”
		$defaultbank  = '';
		
		if ($_POST['pay_bank'] == 'directPay'){
			$paymethod = 'directPay';
		}
		else {
			$paymethod = 'bankPay';
			$defaultbank = $_POST['pay_bank'];
		}

		$anti_phishing_key  = '';
		$exter_invoke_ip = '';
		$show_url			= "http://".$_SERVER["SERVER_NAME"]."/subscribe/theirs.html";
		$extra_common_param = '';
		$royalty_type		= "";			//提成类型，该值为固定值：10，不需要修改
		$royalty_parameters	= "";
		/************************************************************/
		//构造要请求的参数数组
		$parameter = array(
				"service"			=> "create_direct_pay_by_user",
				"payment_type"		=> "1",
		
				"partner"			=> trim($aliapy_config['partner']),
				"_input_charset"	=> trim(strtolower($aliapy_config['input_charset'])),
		        "seller_email"		=> trim($aliapy_config['seller_email']),
		        "return_url"		=> trim($aliapy_config['return_url']),
		        "notify_url"		=> trim($aliapy_config['notify_url']),
		
				"out_trade_no"		=> $out_trade_no,
				"subject"			=> $subject,
				"body"				=> $body,
				"total_fee"			=> $total_fee,
		
				"paymethod"			=> $paymethod,
				"defaultbank"		=> $defaultbank,
		
				"anti_phishing_key"	=> $anti_phishing_key,
				"exter_invoke_ip"	=> $exter_invoke_ip,
		
				"show_url"			=> $show_url,
				"extra_common_param"=> $extra_common_param,
		
				"royalty_type"		=> $royalty_type,
				"royalty_parameters"=> $royalty_parameters
		);
		//构造即时到帐接口
		$alipayService = new AlipayService($aliapy_config);
		$html_text = $alipayService->create_direct_pay_by_user($parameter);
		echo $html_text;
		exit;
	}
	
	/**
	 * 支付宝页面跳转同步通知页面
	 */
	public function alipay_return(){
		require_once (ALIPAY_ROOT . "alipay.config.php");
		require_once (ALIPAY_ROOT . "lib" . DIRECTORY_SEPARATOR . "alipay_notify.class.php");

		$alipayNotify = new AlipayNotify($aliapy_config);
		$verify_result = $alipayNotify->verifyReturn();
		
		if($verify_result) {//验证成功
		    $out_trade_no	= $_GET['out_trade_no'];	//获取订单号
		    $trade_no		= $_GET['trade_no'];		//获取支付宝交易号
		    $total_fee		= $_GET['total_fee'];		//获取总价格
		    $paytime=$_GET['notify_time'];
		    if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
		    	$success_return=$this->doByBuySuccess($out_trade_no, $paytime, $trade_no);
		    	if($success_return["boxid"]) {
		    		$boxid=$success_return["boxid"];
		    		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
// 		    		$this->redirect("pay/pay_success",$success_return);
		    		exit;
		    	}
		    	else {
		    		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
		    		//$this->redirect("pay/pay_fail");
		    		exit;
		    	}
		    }
		   	else {
		   		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
				//$this->redirect("pay/pay_fail");
				exit;
		   	}
		}
		else {
			header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
			//$this->redirect("pay/pay_fail");
			exit;
		}
	}

	/**
	 * 支付宝服务器异步通知页面
	 */
	public function alipay_notify(){
		require_once (ALIPAY_ROOT . "alipay.config.php");
		require_once (ALIPAY_ROOT . "lib" . DIRECTORY_SEPARATOR . "alipay_notify.class.php");	
		$alipayNotify = new AlipayNotify($aliapy_config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {//验证成功
		    $out_trade_no	= $_POST['out_trade_no'];	//获取订单号
		    $trade_no		= $_POST['trade_no'];		//获取支付宝交易号
		    $total_fee		= $_POST['total_fee'];		//获取总价格
		    $paytime=date("Y-m-d H:i:s");
		    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		    	$this->doByBuySuccess($out_trade_no, $paytime, $trade_no);
		    	logResult( "n1_TRADE_FINISHED_支付成功,订单号码".$out_trade_no."--支付宝交易号--".$trade_no);
		    }
		    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		    	$this->doByBuySuccess($out_trade_no, $paytime, $trade_no);
		    	logResult( "n2_TRADE_SUCCESS_支付成功,订单号码".$out_trade_no."--支付宝交易号--".$trade_no);
		    }
		    Log::write("alipay_notify:".$out_trade_no.",".$trade_no.",".$total_fee,INFO);
			echo "success";		//请不要修改或删除
		}
		else {
			Log::write("alipay_notify pay fail",INFO);
		    echo "fail";
		}
	}
	
	/**
	 * WAP手机支付宝支付操作，用于手机APP客户端订单数据收集并转向支付宝WAP端
	 * @param int $orderid 订单号
	 * @return mixed
	 *  无返回值，已经设置支付的同步、异步、中断返回URL，用于接收手机支付宝返回数据
	 */
	public function wap_alipayto(){
		require_once (ALIPAY_ROOT . "wap/alipay_config.php");
		require_once (ALIPAY_ROOT . "wap/class" . DIRECTORY_SEPARATOR . "alipay_service.php");
		$orderid=$_REQUEST["orderid"];
		if(!empty($orderid) && $orderid) {
			$userorder_model=M("UserOrder");
			if($orderinfo = $userorder_model->getByOrdernmb($orderid)){
				//------------
					$res=$this->checkUserAppOrder($orderid,$orderinfo['boxid'],$orderinfo['userid'],$orderinfo['discount'],$orderinfo['coupon']);
					if($res==false){
						$this->show("订单失效，请重新下单！");exit;
					}
				//------------
				
				//设置订单数据
				$orderid=$orderinfo["ordernmb"];
				$boxid=$orderinfo["boxid"];
				$box_mod=M("Box");
				$boxinfo=$box_mod->where("boxid=$boxid")->find();
				$box_name=$boxinfo["name"];
				$userid=$orderinfo["userid"];
				$order_price=$orderinfo["boxprice"]-$orderinfo["discount"];
				$subject=$box_name;
				$out_trade_no=$orderid;
				$total_fee=$order_price;
				$out_user=$userid;
				// 构造要请求的参数数组，无需改动
				$pms1 = array (
						"req_data" => '<direct_trade_create_req><subject>' . $subject . '</subject><out_trade_no>' . $out_trade_no . '</out_trade_no><total_fee>' . $total_fee . "</total_fee><seller_account_name>" . $seller_email . "</seller_account_name><notify_url>" . $notify_url . "</notify_url><out_user>" .$out_user. "</out_user><merchant_url>" . $merchant_url . "</merchant_url>" . "<call_back_url>" . $call_back_url . "</call_back_url></direct_trade_create_req>",
						"service" => $Service_Create,
						"sec_id" => $sec_id,
						"partner" => $partner,
						"req_id" => date ( "Ymdhms" ),
						"format" => $format,
						"v" => $v 
				);
				// 构造请求函数
				$alipay = new alipay_service ();
				// 调用alipay_wap_trade_create_direct接口，并返回token返回参数
				$token = $alipay->alipay_wap_trade_create_direct ( $pms1, $key, $sec_id );
				// 构造要请求的参数数组，无需改动
				$pms2 = array (
						"req_data" => "<auth_and_execute_req><request_token>" . $token . "</request_token></auth_and_execute_req>",
						"service" => $Service_authAndExecute,
						"sec_id" => $sec_id,
						"partner" => $partner,
						"call_back_url" => $call_back_url,
						"format" => $format,
						"v" => $v
				);
				// 调用alipay_Wap_Auth_AuthAndExecute接口方法，并重定向页面
				$alipay->alipay_Wap_Auth_AuthAndExecute ( $pms2, $key );
			}
			else {
				$this->show("订单不存在，无法支付！");
			}
		}
		else {
			$this->show("参数错误，无法完成支付！");
		}
	}

	/**
	 * 手机支付宝支付后同步返回URL
	 */
	public function wap_alipay_callback(){
		$back_url_wap_alipay_success="http://".$_SERVER["SERVER_NAME"]."/wap/pay_success";
		$back_url_wap_alipay_fail="http://".$_SERVER["SERVER_NAME"]."/wap/pay_fail";
		require_once (ALIPAY_ROOT . "wap/alipay_config.php");
		require_once (ALIPAY_ROOT . "wap/class" . DIRECTORY_SEPARATOR . "alipay_notify.php");
		$alipay = new alipay_notify( $partner, $key, $sec_id, $_input_charset );
		$verify_result = $alipay->return_verify ();
		if ($verify_result) { // 验证成功
			$payresult = $_GET ['result']; // 订单状态，是否成功
			$orderid = $_GET ['out_trade_no']; // 外部交易号
			$tradeno = $_GET ['trade_no']; // 交易号
			if ($_GET ['result'] == 'success') {
				$paytime=date("Y-m-d H:i:s");
				$this->doByBuySuccess($orderid, $paytime, $tradeno);
				//支付成功，跳到URL
				header("location:$back_url_wap_alipay_success");
			} else {
				header("location:$back_url_wap_alipay_fail");
			}
		} else {
			header("location:$back_url_wap_alipay_fail");
		}
	}

	/**
	 * 接收手机支付宝异步通知URL
	 */
	public function wap_alipay_notify(){
		require_once (ALIPAY_ROOT . "wap/alipay_config.php");
		require_once (ALIPAY_ROOT . "wap/class" . DIRECTORY_SEPARATOR . "alipay_notify.php");
		$alipay = new alipay_notify ( $partner, $key, $sec_id, $_input_charset ); // 构造通知函数信息
		$verify_result = $alipay->notify_verify (); // 计算得出通知验证结果
	
		if ($verify_result) { // 验证成功
			$status = getDataForXML ( $_POST ['notify_data'], '/notify/trade_status' ); // 返回token
			if ($status == 'TRADE_FINISHED') { // 交易成功结束
				echo "success"; // 请不要修改或删除，在判断交易正常后，必须在页面输出success
				Log::write ( 'success', INFO );
			} else {
				Log::write ( 'fail', INFO );
			}
		} else {
			echo "fail";
		}
	}
	
	/**
	 * 手机支付宝中断支付返回URL
	 */
	public function wap_alipay_paybreak(){
		
		
		
	}
	

	/**
	 * 根据支付宝返回状态，做订单及优惠券相关状态处理
	 * 订单成功后做一系列动作
	 * @param orderid 平台产生订单ID
	 * @param paytime 订单支付时间
	 * @param tradeno 支付宝交易号
	 * @return mixed
	 * @author zhenghong@lolitabox.com
	 */
	public function doByBuySuccess($orderid,$paytime,$tradeno=""){
		$userorder_model=M("UserOrder");
		if($userorder=$userorder_model->getByOrdernmb($orderid)){
			$userid=$userorder["userid"];
			$boxid=$userorder["boxid"];
			$array_return=array("boxid"=>$boxid,"userid"=>$userid,"orderid"=>$orderid);
			//如果返回的支付成功的订单号存在
			if($userorder["state"]<1) {
				$data["state"]=1;
			}
			else {
				return  $array_return; //已经支付了，无需再进行处理
			}
			if(empty($userorder["paytime"]) && !empty($paytime)) {
				$data["paytime"]=$paytime;
			}
			if(empty($userorder["trade_no"]) && !empty($tradeno)){
				$data["trade_no"]=$tradeno;
			}
			$giftcard_price=D("Giftcard")->getUserGiftcardPrice($userid);
			$order_giftcardprice=(int)$userorder['giftcard'];
			$order_add_mod=M("UserOrderAddress");
			$order_add_info=$order_add_mod->getByOrderid($orderid);
			if($giftcard_price<$order_giftcardprice){
				//用户当前礼品卡内的余额与用户订单中需要支付的礼品卡金额比较
				$not_msg="非常抱歉，您的订单".$orderid."因为礼品卡余额不足订购不成功，请您尽快与客服人员联系。";
				D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$not_msg);
				sendtomess($order_add_info['telphone'],$not_msg);
				return false;
			}
			
			//更新订单状态及支付相关信息【支付码，支付时间】
			$updaterst=$userorder_model->where("ordernmb=".$orderid)->save($data);
			$user_credit_stat_model = D ( "UserCreditStat" );
			if($updaterst !== false){
				//订单信息修改成功后
				$boxinfo=M("box")->getByBoxid($boxid);
				if($userorder["discount"]<=0 && $userorder['giftcard']<=0) {
					//只有用户购买盒子没有用到优惠券或没有使用购盒卡时，才给购买用户赠送积分
					$not_type=D("Box")->returnBoxType();
					$not_arr=explode(",",$not_type);
					if(!in_array($userorder['type'],$not_arr)){
						$user_credit_stat_model->optCreditSet ($userid, 'box_buy' );
					}
					// 判断当前用户是否是被邀请用户，如果是则在该用户【第一次】购买商品时，给其邀请者赠送积分
					$users_mod = M ( "Users" );
					$userinfo = $users_mod->where("userid=".$userid)->find();
					$order_num=D("UserOrder")->getUserOrderCount($userid);
					if ($userinfo['invite_uid']>0 && $order_num==1)
					{
						$invite_userinfo=$users_mod->where("userid=".$userinfo['invite_uid'])->find();
						if($invite_userinfo && (int)($boxinfo['box_price'])>=80 && !in_array($boxinfo['category'],$not_arr)){
							$user_credit_stat_model->optCreditSet ($invite_userinfo['userid'], 'user_invite_buy' );
						}
					}
				}
				else {
					//如果使用了优惠券，则更新订单优惠券的使用状态
					$order_couponcode=$userorder["coupon"];
					$coupon_model=M("Coupon");
					$conpon_data["status"]=2;
					$coupon_model->where("code='$order_couponcode'")->save($conpon_data);
				}
				D("Users")->updateOrderNum($userid);//更新订单数
				//D ( "UserBehaviourRelation" )->addData ($userid,1,$boxid,"buy_boxid");//增加用户动态信息
				$this->buyBoxSyncSinaWeibo($userid,$boxid); // 同步用户购买信息到SINA微博
				$this->giveCoupon($boxid,$userid,$orderid);//根据当前商品ID的“是否赠送优惠券”属性给用户发送优惠券
				
				//如果当前盒子是自选盒子，则增加订单的发货信息及更新自选产品的库存
				$type_arr=array(C("BOX_TYPE_ZIXUAN"),C("BOX_TYPE_EXCHANGE_PRODUCT"),C("BOX_TYPE_PAYPOSTAGE"));
				if(in_array($userorder['type'],$type_arr)){
					$this->saveOrderSendInfo($boxid, $orderid, $userid, $userorder['type'],$userorder['projectid']);
					//判断用户是否使用了购盒卡,如果使用了，则修改购盒卡的状态
					$card_mod=M("UserBoxcard");
					$card_info=$card_mod->where("orderid=$orderid")->find();
					$card_data['status']=1;
					$card_mod->where("orderid=$orderid")->save($card_data);
				}
				
				//用户支付成功，向task_order_stat表中添加数据
				//M("TaskOrderStat")->add(array("userid"=>$userid,"orderid"=>$orderid));
				//支付成功，给用户发短信
				$box_info=M("Box")->getByBoxid($boxid);
				if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
					$mess_content="积分试用订单(".$orderid.")支付成功！您的试用美妆两天后就出发，不要着急哦！试用后记得回来和大家分享一下哟~【萝莉盒】";
				}else if($boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
					$mess_content="付邮试用订单(".$orderid.")支付成功！您的试用美妆两天后就出发，不要着急哦！试用后记得回来和大家分享一下哟~【萝莉盒】";
				}else{
					$mess_content="订单（".$orderid."）支付成功！您订购的".$boxinfo['name'].$box_info['box_senddate']."就出发，不要着急哦！盒子到手后记得回来晒一晒~【萝莉盒】";
				}
				sendtomess($order_add_info['telphone'],$mess_content);
				
				//支付成功后，如果当前盒子是赠送特权会员的，更新用户特权信息
				if($boxinfo['if_give_member']>0){
					D("Member")->addMember($userid,$boxinfo['if_give_member']);
				}
				//拆分订单
				D("UserOrder")->splitUserOrder($orderid);
				return $array_return;
			}
			else {
				return false; //订单信息无法修改
			}
		}
		else {
			return false; //订单不存在
		}
	}	
	
	/**
	 * 根据盒子属性，对购买盒子的用户进行赠送优惠券
	 * @param int boxid 盒子ID
	 * @param int userid 用户ID
	 * @param int ordernmb 订单ID
	 */
	private function giveCoupon($boxid,$userid,$ordernmb){
		if(!empty($boxid) && !empty($userid) && !empty($ordernmb)){
			$box_mod=M("box");
			$box_info=$box_mod->where("boxid=$boxid")->find();
			if($box_info['if_give_coupon']>0){
				$coupon_mod=M("coupon");
				$coupon_info=$coupon_mod->where("ordernmb=$ordernmb AND owner_uid=$userid")->find();
				if(!$coupon_info){
					$coupon_title="购买".$box_info[name]."赠送";
					$coupon=D("Coupon")->addCoupon($box_info[if_give_coupon],$coupon_title,$userid,$ordernmb,$box_info['coupon_valid_date']);
					$return['coupon']=$coupon;
					$return['boxname']=$box_info['name'];
					if($coupon){
						$title="恭喜您，获得一张50元优惠券！";
						$message="恭喜您，购买".$box_info[boxname]."获得一张50元优惠券，您可以到个人中心-优惠券里面进行详细查看，感谢您的支持。";
						D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$message);
						D("Users")->updateCouponNum($userid);//更新用户优惠券数
					}
				}
			}
		}
		return true;
	}
	
	
	/**
	 * 订单支付成功后的订单发送信息及自选盒子的更新库存
	 * @param int boxid 盒子的ID
	 * @param orderid 订单ID
	 * @param userid  用户ID
	 * @param boxtype 盒子的类型
	 * @param $projectid 加价购关联ID
	 * @author penglele
	 */
	public function saveOrderSendInfo($boxid,$orderid,$userid,$boxtype,$projectid){
		if(empty($boxid) || empty($orderid) || empty($userid) || empty($boxtype)){
			return false;
		}
		$box_products_mod=M("BoxProducts");
		$order_send_mod=M("UserOrderSend");
		$order_send_products_mod=M("UserOrderSendProductdetail");
		
		$data['boxid']=$boxid;
		$data['orderid']=$orderid;
		$data['userid']=$userid;
		$data['boxtype']=$boxtype;
		
		//从UserOrderSendProductdetail 中查询出当前订单内的产品
		$send_products_list=$order_send_products_mod->where("orderid=$orderid")->select();
		$products_count=count($send_products_list);
		$total_num=0;//当前订单内商品总数
		$total_price=0;//当前订单内商品总价格
		
		//检测自选产品下的福利产品是否有售完的，如果有则将其从UserOrderSendProductdetail中删除,并将该订单下的所有产品重组
		$product_second_arr=array();//重组后的单品pid的数组
		for($i=0;$i<$products_count;$i++){
			$box_products_info=$box_products_mod->where("boxid=$boxid AND pid=".$send_products_list[$i]['productid'])->find();
			//当 当前的产品属于福利类，且已售完，则将其从用户订单发送表中将数据删除
			if($box_products_info['maxquantitytype']==0 && $box_products_info['ptotal']-$box_products_info['saletotal']-$box_products_info['pquantity']<0){
				$where_fuli['orderid']=$orderid;
				$where_fuli['productid']=$send_products_list[$i][productid];
				$send_prdouct_info=$order_send_products_mod->where($where_fuli)->find();//看当前订单下是否还有此类商品存在【可能已经被删除了】
				if($send_prdouct_info){
					$order_send_products_mod->where($where_fuli)->delete();
				}
			}else{
				$product_second_arr[]=$send_products_list[$i]['productid'];
			}
		}
		//如果用户选择了加价购,将次类型下的产品加到用户的订单列表中
		if($projectid){
			$project_product_list=M("box_project_list")->where("projectid=$projectid")->select();
			for($m=0;$m<count($project_product_list);$m++){
				$product_second_arr[]=$project_product_list[$m]['pid'];
				$inventory_item_mod=M("InventoryItem");
				$product_info=$inventory_item_mod->field("price")->where("id=".$project_product_list[$m]['pid'])->find();
				$order_data['orderid']=$orderid;
				$order_data['userid']=$userid;
				$order_data['productid']=$project_product_list[$m]['pid'];
				$order_data['productprice']=$product_info['price'];
				$order_send_products_mod->add($order_data);
			}
		}
		
		//对符合订单需求的产品进行库存数的更新
		for($i=0;$i<count($product_second_arr);$i++){
			$product_price=$order_send_products_mod->field("productprice")->where("orderid=$orderid AND productid=".$product_second_arr[$i])->find();//查看产品的价格
			$total_num+=1;//该订单下的产品的总数的统计
			$total_price=$total_price+$product_price['productprice'];//该订单下的产品的总价格的统计
		}
		$data['productnum']=$total_num;
		$data['productprice']=$total_price;
		$order_send_mod->add($data);//向user_order_send表中添加数据
		
		$this->updateZixuanSaleNum($boxid); //更新自选类盒子中的库存单品售出数量
		$this->updateInventoryStat();
		
	}
	
	
	/**
	 * 用户购买盒子后，将其动态信息同步发布新浪微博
	 *
	 * @param $userid
	 * @param $boxid
	 * @author zhenghong
	 */
	public function buyBoxSyncSinaWeibo($userid, $boxid) {
		if (! $boxid) return false;
		if (! $userid)	return false;
		$box_mod = M ( "Box" );
		$boxinfo = $box_mod->getByBoxid ( $boxid );
		$boxname = $boxinfo ["name"];
		$boximg = $boxinfo ["pic"];
		$imgurl = "http://www.lolitabox.com/" . $boximg;
		$tourl = "http://www.lolitabox.com/buy/goods_select.html";
		$weibo_content = "我刚刚在@LOLITABOX 订购了".$boxname."，萝莉盒提倡先试后购买的消费理念，我已经在试用了，感兴趣的朋友可以来试试呀！";
		return $this->postSinaWeibo ( $userid, $weibo_content, $imgurl, $tourl );
	}
	
	/**
	 * 当自选类盒子订单支付成功后，需要重新统计当前系统的产品库存数据
	 */
	private function updateInventoryStat(){
			$Model=M();
			//统计库存数
			//先清0
			$sql= "UPDATE `inventory_item` SET relation_out_quantity=0 WHERE 1";
			$Model->query( $sql );
			//汇总库存单品预计出库数
			$sql="SELECT productid,count(productid) AS T  FROM `user_order_send_productdetail` WHERE orderid IN (SELECT A.orderid FROM `user_order_send` AS A,user_order AS B WHERE (A.orderid=B.ordernmb) AND (A.productnum>0 AND A.senddate IS NULL AND A.proxysender IS NULL AND A.proxyorderid IS NULL) AND (B.state=1 AND B.ifavalid=1 AND B.inventory_out_status=0)) GROUP BY productid ORDER BY NULL";
			$list = $Model->query ( $sql );
			while ( list ( $key, $item ) = each ( $list ) ) {
				if ($item ['productid']) {
					$updatesql = "UPDATE inventory_item SET relation_out_quantity=" . $item ['T'] . " WHERE id=" . $item ['productid'];
					$Model->query( $updatesql );
				}
			}
			/*2 汇总库存表stat数据*/
			$sql= "UPDATE `inventory_item` SET inventory_in=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity>0 AND  status=1) ,inventory_out=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity<0 AND  status=1)";
			$Model->query ( $sql );
			$sql= "UPDATE `inventory_item` SET inventory_real=inventory_in+inventory_out,inventory_estimated=inventory_in+inventory_out-relation_out_quantity WHERE 1";
			$Model->query ( $sql );
	}
	
	/**
	 * 根据自选类盒子ID重新统计盒子中库存单品的售出数量
	 * @author zhenghong
	 */
	private function updateZixuanSaleNum($boxid) {
		$sql="
SELECT productid,count(productid) AS SaleNum FROM user_order_send_productdetail WHERE orderid IN (SELECT ordernmb FROM user_order WHERE boxid=$boxid AND state=1 AND ifavalid=1) GROUP BY productid";
		$Model=M();
		$list = $Model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['productid']) {
				$updatesql = "UPDATE box_products SET saletotal=" . $item ['SaleNum'] . " WHERE boxid=$boxid AND pid=" . $item ['productid'];
				$Model->query( $updatesql );
			}
		}
	}
	
	/**
	 * 重新支付时，如果是自选盒，则判断用户已选的产品是否有已售完的
	 * @param orderid 用户订单id【必须】
	 * @param boxid 盒子id【必须】
	 * @author penglele
	 */
	public function checkRepayProduct($orderid,$boxid,$userid){
		if(empty($orderid) || empty($boxid) || empty($userid)){
			return false;
		}
		$order_send_mod=M("UserOrderSendProductdetail");
		$box_product_mod=M("BoxProducts");
		//UserOrderSendProductdetail表中，当前订单下的产品列表
		$order_product_list=$order_send_mod->where("orderid=$orderid AND userid=$userid")->select();
		if(!$order_product_list){
			return false;
		}
		$box_products_mod=D("BoxProducts");
		for($i=0;$i<count($order_product_list);$i++){
			//通过productid与boxproduct表关联，查看当前非产品是否已售完，如果有return false
			$product_info=$box_product_mod->field("ptotal,saletotal,pquantity,maxquantitytype")->where("boxid=$boxid AND pid=".$order_product_list[$i]['productid'])->find();
			//新增判断产品库存是否>0 update by 2013-05-24
			$inventory_realnum=$box_products_mod->getProductInventoryEstimatedNum($order_product_list[$i]['productid']);
			if(!$product_info || (($product_info['ptotal']-$product_info['saletotal']-$product_info['pquantity']<0 || $inventory_realnum<=0) && $product_info['maxquantitytype']!=0)){
					return false;
			}
		}
		return true;
	}
	
	/**
	 * 手机app应用重新支付判断
	 * @param  $orderid 订单ID
	 * @param int $boxid 盒子ID
	 * @param int $userid 用户ID
	 * @param $discount 订单的折扣金额
	 * @param $coupon 订单的优惠券号码
	 */
	public function checkUserAppOrder($orderid,$boxid,$userid,$discount,$coupon){
		if(!orderid || !$boxid || !$userid){ 
			return false;
		}
		$box_model=M("Box");
		$userorder_model=M("UserOrder");
		$box_info=$box_model->getByBoxid($boxid);
		if(!$box_info){
			return false;
		} 
		//判断盒子的售卖数量和截止日期
		$box_quantity=$box_info["quantity"];
		$box_order_count=$userorder_model->where("boxid=$boxid AND state=1 AND ifavalid=1")->count();
		$current_date=date("Y-m-d",time());
		//判断当前的盒子是否已售完
		if($box_order_count-$box_quantity>=0 || $box_info["endtime"]<$current_date){
			return false;
		} 
		//判断当前用户是否在不限制购买的用户列表中
		if(in_array($userid,$this->allow_userid_list)) return true;
		//判断用户是否可以购买新会员盒子
		$userinfo=D("Users")->getUserInfo($userid);
		if($box_info['only_newuser']==1){
			if($userinfo["order_num"]>0){
				return false;
			} 
		}
		//判断当前盒子是否可以重复购买:【if_repeat=1可以】 【if_repeat=0不可以】
		if($box_info['if_repeat']==0){
			$user_ifbuy=$userorder_model->where("userid=$userid AND boxid=$boxid AND state=1")->count();
			if($user_ifbuy>0){
				return false;
			}
		}
	
		/*-------------------------------------------------------
		 到此说明用户符合购买盒子的条件
		---------------------------------------------------------*/
		//当用户购买的是自选盒时，检测购盒卡是否有效,且检测用户选择的产品是否有已售完
		if($box_info['category']==C("BOX_TYPE_ZIXUAN")){
			if($discount>0 && $coupon==""){
				//如果当前用户使用了购盒卡，检测当前订单与购盒卡是否绑定
				$card_mod=M("UserBoxcard");
				$card_info=$card_mod->where("orderid=$orderid AND userid=$userid AND status=0")->find();
				if(!$card_info){
					return false;
				} 
			}
			if($this->checkRepayProduct($orderid,$boxid,$userid)==false) return false;
		}
	
		//当用户使用的是优惠券时
		if($discount>0 && $coupon!=""){
			$coupon_mod=M("coupon");
			$couponinfo=$coupon_mod->where("code='$coupon'")->find();
			$c_time=date("Y-m-d H:i:s");
			if(!empty($coupon) && ($couponinfo['status']==2 || !$couponinfo || $couponinfo['starttime']>$c_time || $couponinfo['endtime']<$c_time)){
				return false;
			}
		}
		return true;
	}
	
	
}
?>