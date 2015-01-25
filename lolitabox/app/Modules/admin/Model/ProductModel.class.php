<?php

class ProductModel extends Model {
	protected $trueTableName = 'products';

	protected $_auto=array(
		array('user_type','getUserTypeList',3,'callback')
	);

	function getUserTypeList(){
		$str=implode(",", $_POST["user_type"]);
		return $str;
	}



}
?>