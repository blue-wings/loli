<?php 
  return array(
  		//分类ID
  		'SITE_NAME' => "LOLITABOX-萝莉盒-化妆品试用",
		'INDEX_FOCUS_CATEID' => '582',
		'INDEX_EVALUATE_CATEID' => '583',
		'INDEX_DAREN_CATEID' => '585',
		'INDEX_FAVOURITE_CATEID' => '586',
		'HOME_INTEREST_CATEID' => '592',
		'BUY_DAREN_CATEID' => '591',
		'GOOD_PRODUCT_LIST' => '593',
		'LOLITABOX_ID' => '2375',
		'SPECIAL_FOCUS_CATEID' => '624',
		'BOX_TYPE_SUIXUAN' => '14',
		'BOX_TYPE_ZIXUAN' => '15',
		'BOX_TYPE_SOLO' => '13',
  		'BOX_TYPE_EXCHANGE' => '16',
  		'BOX_TYPE_EXCHANGE_PRODUCT'=>'18',//积分兑换盒子类型
		'MAIL_CATEID' => '575',
		'MSG_CATEID' => '576',
		'BOX_TYPE_DEFAULT' => '1',
		'INDEX_BRANDLOGO' => '611',
		'INDEX_SPECIAL_CATEID' => '584',
  		
  		"HOT_PRODUCTS_CATEID" => '686',   //美妆库最热产品微刊推荐
  		"INDEX_PRODUCTS_REMMEND" => "685",   //网站首页火热试用推荐
  		"INDEX_LABEL_CATEID"  => "684",     //网站首页标签
  		"BOX_TRY_CATEID" => "683",    //个人主页萝莉盒试用
  		"SHOW_BOX_USERID"  => "32568", //晒盒记解决方案USERID
  		
  		"BOX_TYPE_PAYPOSTAGE"=>"19",//付邮试用类型
  		"BOX_TYPE_FREEGET"=>"21",//免费获取
  			
  		"BOXID_PAYPOSTAGE"=>"95",//付费试用盒子ID
  		"BOXID_CREDITEXCHANGE"=>"89",//积分兑换盒子ID
  		"BOXID_FREEGET"=>"101",//免费试用盒子ID
  		
  		
  		'VAR_PAGE'          =>  'p',
		
  		"SOLUTION_RECOMMEND_CATEID" =>'676',
  		"BRAND_COMMEND_CATEID"  => '677',
  		"HOT_CONTENT_CATEID" =>"675",
  		"SKIP_ADDPRODUCT_BOXID_LIST"=>array(109,128,133,134,135,136), //不需要添加产品可以直接编辑快递信息的盒子ID范围
  		
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