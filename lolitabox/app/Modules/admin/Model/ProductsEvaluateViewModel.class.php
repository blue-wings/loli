<?php

class ProductsEvaluateViewModel extends ViewModel {

	protected $viewFields = array (
		'ProductsEvaluate' => array (
			'evaluateid','userid','productid','title','content','postdate','agreenum','replynum','contentlevel','toptime','if_sync_weibo',
			'_table' => 'products_evaluate',
			'_type' => 'LEFT'
		),
		'Users' => array (
			'nickname',
			'usermail',
			'_on' => 'ProductsEvaluate.userid=Users.userid',
			'_table' => 'users',
		),
		'Products' => array (
			'pname',
			'pid',
			'_on' => 'ProductsEvaluate.productid=Products.pid',
			'_table' => 'products',
		),

	);

}
?>