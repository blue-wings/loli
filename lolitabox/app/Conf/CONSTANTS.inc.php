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

	'USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED'			=> 0,
	'USER_SELF_PACKAGE_ORDER_STATUS_PAYED'				=> 1,
	'USER_SELF_PACKAGE_ORDER_STATUS_REFUNDED'			=> 2,

	'USER_SELF_PACKAGE_ORDER_STATUS_NOT_PAYED'			=> 0,
	'USER_SELF_PACKAGE_ORDER_STATUS_PAYED'				=> 1,
	'USER_SELF_PACKAGE_ORDER_STATUS_REFUNDED'			=> 2,

	'USER_NOT_PAY_POSTAGE_ORDER'						=> 0,
	'USER_PAY_POSTAGE_ORDER'							=> 1,

	'USER_ORDER_SEND_INVENTORY_OUT_STATUS_UNFINISHED'	=>0,
	'USER_ORDER_SEND_INVENTORY_OUT_STATUS_FINISHED'		=>1,
	
	//category的ctype种类id
	'CTYPE_PRODUCT'										=>1,
	'CTYPE_EFFECT'										=>2,
	'CTYPE_PRODUCT_BRAND'								=>3,

    //category的pcid根结点
    'PCID_ROOT'                                         =>0,

	'EXPRESS_SHENTONG_ID'								=> array("id"=>0, "name"=>"申通快递"),
	'EXPRESS_SHUNFENG_ID'								=> array("id"=>1, "name"=>"顺丰快递"),

	//订单的失效时间72小时,支付宝订单失效时间48小时,该值需要大约48小时
	'ORDER_VALID_DURATION'								=>72*3600,
	//有效
	'ORDER_IFAVALID_VALID'								=>1,
	//失效
	'ORDER_IFAVALID_OVERDUE'							=>0,

	//购物车中失效的商品状态
	'SHOPPING_CART_STATUS_INVALID'						=>0,
	//购物车中正常的商品状态
	'SHOPPING_CART_STATUS_VALID'						=>1,
	//购物车记录失效时间
	'SHOPPING_CART_INVALID_DURATION'					=>30*24*3600,

    //products表中的user_type类型
    'PRODUCT_COMMON_USER_TYPE'                          =>"0",
    'PRODUCT_ADVANCED_USER_TYPE'						=>"1",
    'PRODUCT_NEW_USER_TYPE'						        =>"3",
    'PRODUCT_YEAR_USER_TYPE'						    =>"2",

	'PRODUCT_MAX_PER_USER_MAX'						    =>999,

    //出库单类型
    'INVENTORY_OUT_TYPE_SYSTEM'                         => 1,
    'INVENTORY_OUT_TYPE_HUMAN'                          => 2,
    'INVENTORY_OUT_TYPE_VIRTUAL'                        => 3,

	'INVENTORY_STAT_STATUS_INVALID'						=>0,
    'INVENTORY_STAT_STATUS_VALID'						=>1,

	//我的消息，萝莉盒官方消息发出方ID
	'LOLITABOX_ID' 										=>2375,
	'MSG_TO_ALL_USER_ID'								=>0,
	'MSG_TO_STATUS_VALID'								=>1,
	'MSG_TO_STATUS_INVALID'								=>0,
	'MSG_FROM_STATUS_VALID'								=>1,
	'MSG_FROM_STATUS_INVALID'							=>0,
  	
    //出库单出库状态
    'INVENTORY_OUT_STATUS_FINISHED'						=>1,
    'INVENTORY_OUT_STATUS_UNFINISHED'					=>0,
	
    //省地区的父id
	'AREA_PROVINCE_PID'									=>0,

    'MAX_ADDRESS_NUM_PER_USER'                      =>3,
    'USER_ORDER_ADDRESS_ACTIVE'                          =>1,
    'USER_ORDER_ADDRESS_NOT_ACTIVE'                 =>0,

    'GIFT_CARD_ACTIVATED'                                           =>1,
    'GIFT_CARD_INACTIVE'                                                =>0,

    'ORDER_TYPE_NORMAL'                                                =>1,
    'ORDER_TYPE_SELF_PACKAGE'                                                =>0,

    'PRODUCT_NOT_NEED_POSTAGE'                                          =>0,
    'PRODUCT_NEED_POSTAGE'                                          =>1,

    'PRODUCT_NOT_NEED_PRE_SHARE'                                          =>0,
    'PRODUCT_NEED_PRE_SHARE'                                          =>1
);