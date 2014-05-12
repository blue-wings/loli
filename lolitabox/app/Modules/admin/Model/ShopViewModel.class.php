<?php
class ShopViewModel extends ViewModel {

	protected $viewFields=array(
	'shop'=>array(
		'id',
		'name',
		'brandid',
		'province_areaid',
		'city_areaid',
		'county_areaid',
		'address',
		'longitude',
		'latitude',
		'linkman',
		'telphone',
		'status',
		'c_datetime'
	),

	'productsBrand'=>array(
		'name'=>'pname',
		'_on'=>'shop.brandid=productsBrand.id',
		'_type'=>'LEFT'
	),

	'area'=>array(
		'title',
		'_on'=>'shop.province_areaid=area_id',
		'_type'=>'LEFT'
	),

	'area_s'=>array(
		'_table'=>'area',
		'title'=>'stitle',
		'_on'=>'shop.city_areaid=area_s.area_id',
		'_type'=>'LEFT'
	),
	'area_t'=>array(
		'_table'=>'area',
		'title'=>'ttitle',
		'_on'=>'shop.county_areaid=area_t.area_id',
		'_type'=>'LEFT'
	),
	);
}
?>