<?php
class mySubscribeAction extends commonAction{
	
	public function productList(){
		$userid = $this->userid;
		$userType = $_GET("userType");
		$pageNum = $_GET("pageNum");
		import("ORG.Util.Page");
		$usersProductsCategorySubscribe = D("UsersProductsCategorySubscribe");
		$subscribes = $usersProductsCategorySubscribe->getByUserId($userid);
		if(count($subscribes) == 0){
			$this->error("please subscribe prodcut category!");
		}
		$array = array(count($subscribes));
		for($i=0; $i<count($subscribes); $i++){
			$array[i] = $subscribes["product_category_id"];
		}
		$categoryIds = join($array, ",");
		$sql = "select count(distinct(p.pid)) from products  as p, product_effect_relation as per where p.pid=per.pid and p.userType=" . $userType . " and per.effectcid in (" .$categoryIds .")";
		$productCount = $model->query($sql);
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select * from products  as p, product_effect_relation as per where p.pid=per.pid and p.userType=" . $userType . " and per.effectcid in (" .$categoryIds .")" . $pageSql;
		$prodcutList = $model->query($productSql);
		$page=$p->show();
		$this->assign('page',$page);
		$this->assign("list",$prodcutList);
		$this->display();
	}
	
	
	
}