<?php

class ProductModel extends Model {
	protected $trueTableName = 'products';

	//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
	protected $_validate=array(
		array('pname','require','必须填写单品名称！',1,),
		//array('goodssize','require','必须填写正装规格！',1,),
		//array('goodsprice','currency','必须正确填写正装价格！',1,),
		//array('trialsize','require','必须填写试用装规格！',1,),
		//array('trialprice','currency','必须正确填写试用装价格！',1,),
		array('effectcid','chkeffectcid','必须选择单品功效（至少一个）',1,'callback'),
		//array('maincomponent','require','必须填写主要成分！',1,),
		//array('pintro','require','必须填写单品介绍！',1,),
		//array('officialweburl','url','必须填写官方网址！',2,), //如果输入了URL，则进行验证
	);

	//验证单品功效多选项
	function chkeffectcid(){
		$effectcid=$_REQUEST["effectcid"];
		if(count($effectcid)<=0){
			return false;
		}
		else {
			return true;
		}
	}

	protected $_auto=array(
		array('firstcid','getFirstcid',3,'callback'),
		array('secondcid','getSecondcid',3,'callback'),
		array('for_skin','getSkinList',3,'callback'),
		array('for_people','getPeopleList',3,'callback'),
		array('for_hair','getHairList',3,'callback'),
		array('user_type','getLevelList',3,'callback'),
	);

	function getSkinList(){
		$str=implode(",", $_POST["for_skin"]);
		return $str;
	}
	
	function getLevelList(){
		$str=implode(",", $_POST["for_level"]);
		return $str;
	}
	function getPeopleList(){
		$str=implode(",", $_POST["for_people"]);
		return $str;
	}	
	
	function getHairList(){
		$str=implode(",", $_POST["for_hair"]);
		return $str;
	}	
	
	function getFirstcid(){
		$pcid=$_POST["pcid"];
		if(!empty($pcid)) {
			$arraypcid=explode("-",$pcid);
			if($arraypcid[0]>0){
				return $arraypcid[0];
			}
		}
	}

	function getSecondcid(){
		$pcid=$_POST["pcid"];
		if(!empty($pcid)) {
			$arraypcid=explode("-",$pcid);
			if($arraypcid[1]>0){
				return $arraypcid[1];
			}
		}
	}

}
?>