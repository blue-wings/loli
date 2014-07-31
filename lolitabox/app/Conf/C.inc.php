<?php 
  return array(
  
  		'VAR_PAGE'=> 'p',
  		
  		//短信配置
		'MSG_NAME' => 'lolitabox_sms',    
		'MSG_PWD' => '3ga41dg5',
		'MSG_HTTP_IMPLEMENT' => 'http://smsapi.qxt100.com/dapi/send_simple.php',
		
  		//邮件配置
		'MAIL_ADDRESS' => 'no-reply@lolitabox.com',
		'MAIL_SMTP' => 'smtp.exmail.qq.com',
		'MAIL_LOGINNAME' => 'no-reply@lolitabox.com',
		'MAIL_PASSWORD' => 'reply123',
  		
  		'URL_ROUTER_ON'   => true, //开启路由
  		'URL_ROUTE_RULES' => array( //定义路由规则
  				'/^brand\/(\d+)$/'  => 'brand/detail?brandid=:1',
  				'/^products\/(\d+)$/'  => 'brand/products?pid=:1',
  				'/^solution\/(\d+)$/'  => 'beauty/solution:1?id=:1',
  				'/^space\/(\d+)$/'  => 'space/index?userid=:1',
  				'/^share\/(\d+)$/' => 'public/share?id=:1',
  				'/^home\/task$/' => 'task/index',
  				'/^home\/finished_task$/' => 'task/finished',
  				
  				'/^home\/iexchange$/' => 'try/iexchange',
  				'/^home\/share_detail\/id\/(\d+)$/' =>'public/share?id=:1',
  				'/^home\/goods_select$/' => 'buy/index',
  				'/^home\/goods_show\/boxid\/(\d+)$/' => 'buy/show?boxid=:1',
  				'/^home\/trycentry$/' => 'try/index',
  				'space/blog_detail' =>'public/blog_jump',
  				'space/evaluate_detail' =>'public/evaluate_jump',
  				'/^article\/detail\/id\/(\d+)$/' => 'info/about?aid=:1',
  		),

);