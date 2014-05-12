<?php
class UserOrderAddrModel extends ViewModel {

	protected $viewFields = array (
		'UserOrder' => array (
			'ordernmb',
			'userid',
			'boxid',
			'boxprice',
			'credit',
			'giftcard',
			'type',
			'state',
			'trade_no',
			'coupon',
			'discount',
			'addtime',
			'paytime',
			'fromid',
			'frominfo',
			'address_id',
			'projectid',
			'sendword',
			'_table' => 'user_order',
			'_type' => 'LEFT'
		),
		'Box' => array (
			'name'  =>  'boxname',
			'boxid'=>'bid',
			'_table' => 'box',
			'_on' => 'UserOrder.boxid=Box.boxid',
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
		'UserOrderAddress' => array (
			'linkman',
			'telphone',
			'province',
			'city',
			'district',
			'address',
			'postcode',
			'_table' => 'user_order_address',
			'_on' => 'UserOrderAddress.orderid=UserOrder.ordernmb',
			'_type' => 'LEFT'
		)

	);
}
?>