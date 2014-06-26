<?php
return array(
	'PRODUCT_STATUS_UNPUBLISH'               			=> 0,
    'PRODUCT_STATUS_PUBLISHED'               			=> 1, 
    
	'USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED'	=> 0,
	'USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_PAYED'		=> 1,
	'USER_ODER_SEND_PRODUCT_STATUS_WITHOUT_POSTAGE'		=> 2,

	'USER_ORDER_STATUS_NOT_PAYED'						=> 0,
	'USER_ORDER_STATUS_PAYED'							=> 1,
	'USER_ORDER_STATUS_REFUNDED'						=> 2,

	'USER_ORDER_SEND_INVENTORY_OUT_STATUS_UNFINISHED'	=>0,
	'USER_ORDER_SEND_INVENTORY_OUT_STATUS_FINISHED'		=>1,
	
	//他们都在订阅什么，即将开始的产品时间延迟，以纠错系统返回到前台的时间
	'SUBSCRIBE_FUTURE_INC'								=>5,

	//category的ctype种类id
	'CTYPE_PRODUCT'										=>1,
	'CTYPE_EFFECT'										=>2,
	'CTYPE_PRODUCT_BRAND'								=>3
);