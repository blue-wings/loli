<?php
class 	promotionModel extends Model{
	//字段映射
	protected $_map = array(
	'proname'=>'name',
	'valid'=>'validate',
	'param'=>'params',
	'api'=>'apikey',
	'url'=>'pushurl',
	'message'=>'remark',
	);
}
?>