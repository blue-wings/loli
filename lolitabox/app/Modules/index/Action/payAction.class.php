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
		$show_url			= "http://".$_SERVER["SERVER_NAME"]."/home/index.html";
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
	 * pay/alipay_return?out_trade_no=20140805223007401&trade_no=1&total_fee=2.0&notify_time=2014-08-08&trade_status=TRADE_FINISHED
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
				$order = D("UserOrder")->getOrderInfo($out_trade_no);
				if($order["state"] == C("USER_ORDER_STATUS_NOT_PAYED")){
					D("UserOrder")->hasPayed($out_trade_no, $trade_no, $paytime);
					header("location:".U('userOrder/paySuccess',array('id'=>$out_trade_no)));
				}else{
					header("location:".U('userOrder/paySuccess',array('id'=>$out_trade_no)));
				}
			}else{
				header("location:".U('userOrder/payFailed',array('id'=>$out_trade_no)));
			}
		}else{
			header("location:".U('userOrder/payFailed'));
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
		    $order = D("UserOrder")->getOrderInfo($out_trade_no);
		    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		    	if($order["state"] == C("USER_ORDER_STATUS_NOT_PAYED")){
					D("UserOrder")->hasPayed($out_trade_no, $trade_no, $paytime);
				}
		    	logResult( "n1_TRADE_FINISHED_支付成功,订单号码".$out_trade_no."--支付宝交易号--".$trade_no);
		    }
		    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		    	if($order["state"] == C("USER_ORDER_STATUS_NOT_PAYED")){
					D("UserOrder")->hasPayed($out_trade_no, $trade_no, $paytime);
				}
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
	
}
?>