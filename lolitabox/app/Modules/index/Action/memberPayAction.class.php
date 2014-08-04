<?php
/**
 * 特权会员购买
 * @author penglele
 *
 */
class memberPayAction extends commonAction {
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
		$subject      = "萝莉盒".$_POST['subject'];
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
		$show_url			= "http://".$_SERVER["SERVER_NAME"]."/member/index.html";
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
		        "return_url"		=> trim($aliapy_config['return_member_url']),
		        "notify_url"		=> trim($aliapy_config['notify_member_url']),
		
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
		    	header("location:".U('member/result',array('id'=>$out_trade_no)));exit;
// 		    	if($success_return["boxid"]) {
// 		    		$boxid=$success_return["boxid"];
// 	    		$this->redirect("pay/pay_success",$success_return);
// 		    		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
// 		    		exit;
// 		    	}
// 		    	else {
// 		    		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
// 		    		//$this->redirect("pay/pay_fail");
// 		    		exit;
// 		    	}
		    }
		   	else {
		   		header("location:".U('member/result',array('id'=>$out_trade_no)));exit;
// 		   		header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
				//$this->redirect("pay/pay_fail");
				exit;
		   	}
		}
		else {
			header("location:".U('member/result',array('id'=>$out_trade_no)));exit;
// 			header("location:".U('buy/pay_result',array('id'=>$out_trade_no)));
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
	 * 根据支付宝返回状态，做订单及会员特权状态相关状态处理
	 * 订单成功后做一系列动作
	 * @param orderid 平台产生订单ID
	 * @param paytime 订单支付时间
	 * @param tradeno 支付宝交易号
	 * @return mixed
	 * @author zhenghong@lolitabox.com
	 */
	public function doByBuySuccess($orderid,$paytime,$tradeno=""){
		$userorder_model=M("MemberOrder");
		if($userorder=$userorder_model->getByOrdernmb($orderid)){
			$userid=$userorder["userid"];
			$type=$userorder["m_type"];
			$array_return=array("type"=>$type,"userid"=>$userid,"orderid"=>$orderid);
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
			//更新订单状态及支付相关信息【支付码，支付时间】
			$updaterst=$userorder_model->where("ordernmb=".$orderid)->save($data);
			if($updaterst !== false){
				//订单支付成功的一系列操作+++++++++++++++++
				//更新用户的特权有效期
				$member_mod=D("Member");
				$type_statelist=$member_mod->getUserMemberDateOfType($userid);
				
				$msg="您刚刚成功订购了萝莉盒特权会员（".$type_statelist[$type]['name']."），特权有效期为：".$type_statelist[$type]['sdate']." 至 ".$type_statelist[$type]['edate']."。您可以选择：<a href='/home/member.html' target='_blank' class='WB_info'>查看订单详情</a>  <a href='/buy/index.html' target='_blank' class='WB_info'>订购萝莉盒</a>  <a href='/try/index/type/1.html' target='_blank' class='WB_info'>付邮试用</a>  <a href='/try/iexchange.html' target='_blank' class='WB_info'>积分兑换试用</a>";
				D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$msg);
				
				$member_mod->addMember($userid,$type);
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
	 * 用户购买盒子后，将其动态信息同步发布新浪微博
	 * @param $userid
	 * @param $boxid
	 * @author zhenghong
	 */
// 	public function buyBoxSyncSinaWeibo($userid, $boxid) {
// 		if (! $boxid) return false;
// 		if (! $userid)	return false;
// 		$box_mod = M ( "Box" );
// 		$boxinfo = $box_mod->getByBoxid ( $boxid );
// 		$boxname = $boxinfo ["name"];
// 		$boximg = $boxinfo ["pic"];
// 		$imgurl = "http://www.lolitabox.com/" . $boximg;
// 		$tourl = "http://www.lolitabox.com/buy/goods_select.html";
// 		$weibo_content = "刚刚在@LOLITABOX 抢购了" . $boxname . "，每月仅需80元起，就能打包试用6-10件品牌美妆品，定制试用现在最流行了，更轻松找到适合自己的宝贝~";
// 		return $this->postSinaWeibo ( $userid, $weibo_content, $imgurl, $tourl );
// 	}
	
}
?>