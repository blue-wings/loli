<?php

class ProductsEvaluateImgViewModel extends ViewModel {

	protected $viewFields = array (
		'ProductsEvaluateImg'=>array(
			'imgid','evaluateid','userid','imgpath','postdate',
			'_table'=>'products_evaluate_img',
			'_type' => 'LEFT'
		),
		'ProductsEvaluate' => array (
			'title','content',
			'_on' => 'ProductsEvaluateImg.evaluateid=ProductsEvaluate.evaluateid',
			'_table' => 'products_evaluate',
		),
		'Users' => array (
			'nickname',
			'usermail',
			'_on' => 'ProductsEvaluateImg.userid=Users.userid',
			'_table' => 'users',
		),

	);
}
?>