<?php
class UserSendModel extends ViewModel {

	protected $viewFields = array (
			'UserOrderSend' => array (
					'proxyorderid',
					'_table' => 'user_order_send',
					'_type' => 'LEFT'
			),
			'Users' => array (
					'usermail','nickname','userid',
					'_table' => 'users',
					'_on' => 'UserOrderSend.userid=Users.userid',
					'_type' => 'LEFT'
			),
			'Box'   =>  array  (
			        'name',
					'_table' => 'box',
					'_on' => 'UserOrderSend.boxid=Box.boxid',
					'_type' => 'LEFT' 		
			)
	);
}
?>