<?php
class UserModel extends Model {
	protected $trueTableName = 'users';
	protected $_validate=array(
		array('nickname','require','必须填写分类名称！',2,'unique'),
		array('usermail','require','必须填写分类名称！',2,'unique'),
	);
}
?>