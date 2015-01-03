<?php
class UserOrderAddrModel extends ViewModel {

	protected $viewFields = array (
		'UserOrder' => array (
			'ordernmb',
			'userid',
			'giftcard',
			'state',
			'trade_no',
			'addtime',
			'paytime',
			'address_id',
			'sendword',
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
		'UserOrderAddress' => array (
			'linkman',
			'telphone',
			'province_area_id',
			'city_area_id',
			'district_area_id',
			'address',
			'postcode',
			'_table' => 'user_order_address',
			'_on' => 'UserOrderAddress.id=UserOrder.address_id',
			'_type' => 'LEFT'
		)

	);
}
?>