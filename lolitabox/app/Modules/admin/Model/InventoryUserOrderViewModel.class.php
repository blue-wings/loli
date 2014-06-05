<?php
class InventoryUserOrderViewModel extends ViewModel {

	protected $viewFields = array (
	'UserOrderSend' => array (
	'orderid',
	'child_id',
	'boxid',
	'userid',
	'proxysender',
	'proxyorderid',
	'productnum',
	'inventory_out_id',
	'inventory_out_status',
	'_type' => 'LEFT'
	),
	'UserOrder' => array (
	'ordernmb',
	'addtime'=>'atime',
	'paytime',
	'address_id',
	'_type' => 'LEFT',
	'_on' => 'UserOrderSend.orderid=UserOrder.ordernmb'
	),
	'box'=>array(
		'name'=>'boxname',
		'_type' => 'LEFT',
		'_on' => 'UserOrderSend.boxid=box.boxid'
	)	
	);
}
?>