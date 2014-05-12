<?php
class ShopModel extends CommonModel{

	protected $_validate=array(
		array('name','require','商家名称必填'),
		array('brandtxt','require','商家必须选择'),
		array('province_areaid','require','省必须选择'),
		array('city_areaid','require','市必须选择'),
		array('county_areaid','require','区必须选择'),
		array('address','require','详细地址必须填写'),
		
	);

	protected $_auto = array (
		array('c_datetime','returnDateTime',3,'callback')
	);

	function returnDateTime(){
		return date('Y-m-d H:i:s',time());
	}
}
?>