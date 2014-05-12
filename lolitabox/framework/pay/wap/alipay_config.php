<?php
/**
	*功能：设置帐户有关信息及返回路径（基础配置页面）
	*版本：2.0
	*日期：2011-11-04
	*说明：
	*以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
	*该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
*/

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

	$partner		= "2088701849713532";			//合作身份者ID，以2088开头的16位纯数字
	$key   			= "x60fvb27v9mv7j4kuxxwfi6zfcui0ohb";			//安全检验码，以数字和字母组成的32位字符
	$seller_email	= "lolitabox2012@163.com";			//签约支付宝账号或卖家支付宝帐户


	//以下是三个返回URL
	$notify_url		= "http://".$_SERVER["SERVER_NAME"]."/pay/wap_alipay_notify.html"; //服务端获取通知地址，用户交易完成异步返回地址
	$call_back_url	= "http://".$_SERVER["SERVER_NAME"]."/pay/wap_alipay_callback.html"; //用户交易完成同步返回地址
	$merchant_url	= "http://".$_SERVER["SERVER_NAME"]."/pay/wap_alipay_paybreak.html"; //用户付款中途退出返回地址



//↓↓↓↓↓↓↓↓↓↓以下参数为支付宝默认参数，禁止修改其参数值↓↓↓↓↓↓↓↓↓↓

	$Service_Create				= "alipay.wap.trade.create.direct";
	$Service_authAndExecute		= "alipay.wap.auth.authAndExecute";
	$format						= "xml";
	$sec_id						= "MD5";
	$_input_charset				= "utf-8";
	$v							= "2.0";

?>