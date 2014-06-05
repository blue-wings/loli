<?php

class ProductsEvaluateReplyViewModel extends ViewModel {

	protected $viewFields = array (
		'ProductsEvaluateReply'=>array(
			'replyid','evaluateid','userid','replycontent','postdate',
			'_table'=>'products_evaluate_reply',
			'_type' => 'LEFT'
		),
		'ProductsEvaluate' => array (
			'title','content',
			'_on' => 'ProductsEvaluateReply.evaluateid=ProductsEvaluate.evaluateid',
			'_table' => 'products_evaluate',
		),
		'Users' => array (
			'nickname',
			'usermail',
			'_on' => 'ProductsEvaluateReply.userid=Users.userid',
			'_table' => 'users',
		),

	);
}
?>