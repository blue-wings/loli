<?php

class CategoryModel extends Model{
	protected $trueTableName = 'category';
	protected $_validate=array(
		array('cname','require','必须填写分类名称！',1,)
	);

	protected $_auto=array(
		array('cpath','getPath',3,'callback'),
		array('ctype','getCtype',3,'callback')
	);

	//根据规则得到分类的层次
	function getPath(){
		$pcid=$_POST['pcid'];
		$mi=$this->field('cid,cpath')->getByCid($pcid);
		$cpath=$pcid!=0?$mi['cpath'].'-'.$mi['cid']:0;
		return $cpath;
	}

	//自动补充分类的分类
	function getCtype(){
		$ctype=$_POST['ctype'];
		return $ctype;
	}
}
?>