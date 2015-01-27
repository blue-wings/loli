<?php
class UserOrderViewModel extends ViewModel {

	protected $viewFields = array (
		'UserOrder' => array (
			'ordernmb','userid','boxid','type','trade_no','coupon','discount','addtime','paytime','address_id','status',
			'_table' => 'user_order',
			'_type' => 'LEFT'
		),
		'Users' => array (
			'nickname' => 'username',
			'usermail',
			'order_num',
			'_table' => 'users',
			'_on' => 'UserOrder.userid=Users.userid',
			'_type' => 'LEFT'
		),
		'UserProfile' => array (
			'linkman',
			'telphone',
			'province',
			'city',
			'district',
			'address',
			'postcode',
			'_table' => 'user_profile',
			'_on' => 'UserOrder.userid=UserProfile.userid',
		    '_type' => 'LEFT'
		),
		'UserOrderSend' => array (
			'senddate',
			'proxysender',
			'proxyorderid',
			'productnum',
			'productprice',
			'_table' => 'user_order_send',
			'_on' => 'UserOrder.ordernmb=UserOrderSend.orderid'
		),

	);
}
?>