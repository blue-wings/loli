<?php

class BoxViewModel extends ViewModel {

	protected $viewFields = array (
		'Box'=>array(
			'boxid',
			'name'=>'boxname',
			'quantity',
			'category',
			'starttime',
			'endtime',
			'box_price'=>'price',
			'member_price',
			'box_remark',
			'only_newuser',
			'if_use_coupon',
			'if_repeat',
			'if_give_coupon',
			'coupon_valid_date',
			'toptime',
			'_table'=>'box',
			'_type' => 'LEFT'
		),
		'FeeProduct' => array (
			'id',
			'price'=>'fprice',
			'_on' => 'Box.category=FeeProduct.id',
			'_table' => 'fee_product',
			'_type'=>'LEFT'
		),
		'boxDetail'=>array(
			'boxid'=>'bid',		
			//'instruction',  
			//'details',
		    '_table'=>'box_detail',	
			'_on'=>'Box.boxid=boxDetail.boxid',
			'_type'=>'LEFT'
		)
	);
}
?>
