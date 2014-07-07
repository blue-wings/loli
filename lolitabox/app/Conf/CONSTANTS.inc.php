<?php
return array(
	'PRODUCT_STATUS_UNPUBLISH'               			=> 0,
    'PRODUCT_STATUS_PUBLISHED'               			=> 1, 
    
	//未支付订单
	'USER_ODER_SEND_PRODUCT_STATUS_NOT_PAYED'			=> 0,
	//已支付订单,未支付邮费
	'USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED'	=> 1,
	//已支付订单和邮费
	'USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_PAYED'		=> 2,
	//无需支付任何费用
	'USER_ODER_SEND_PRODUCT_STATUS_WITHOUT_POSTAGE'		=> 3,

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
	'CTYPE_PRODUCT_BRAND'								=>3,

    //category的pcid根结点
    'PCID_ROOT'                                         =>0,

	'EXPRESS_SHENTONG_ID'								=>0,
	'EXPRESS_SHUNFENG_ID'								=>1,

	//订单的失效时间72小时
	'order_valid_duration'								=>259200,
	//有效
	'order_ifavalid_valid'								=>1,
	//失效
	'order_ifavalid_overdue'							=>0,
);