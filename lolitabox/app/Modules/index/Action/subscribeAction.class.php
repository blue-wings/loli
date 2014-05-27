<?php
class subscribeAction extends commonAction{
	
	public function mine(){
		$userid = $this->userid;
		$userType = $_GET["userType"];
		import("ORG.Util.Page");
		$usersProductsCategorySubscribe = D("UsersProductsCategorySubscribe");
		$subscribes = $usersProductsCategorySubscribe->getByUserId($userid);
		if(count($subscribes) == 0){
			$this->error("please subscribe prodcut category!");
		}
		$categoryIds = "";
		for($i=0; $i<count($subscribes); $i++){
			$categoryIds .= $subscribes[$i]["product_category_id"];
			if($i != count($subscribes)-1){
				$categoryIds .= ",";
			}
		}
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and per.effectcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and per.effectcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
		$prodcutIdList = $model->query($productSql);
		$productIdsStr = "";
		for($i=0; $i<count($prodcutIdList); $i++){
			$productIdsStr .= $prodcutIdList[$i]["productIds"];
			if($i != count($prodcutIdList)-1){
				$productIdsStr .= ",";
			}	
		}
		$where = array("in", $productIdsStr);
		$products = D("Products")->where("pid in (".$productIdsStr.")")->select();
		$page=$p->show();	
		$this->assign('page',$page);
		$this->assign("list",$products);
		$this->display();
	}
	
	public function theirs(){
		$futureProductFirst = D("Products")->limit(0,1)->find();
		$futureProductFirst["start_time_seconds"] = strtotime($futureProductFirst["start_time"]);
		$futureProducts = D("Products")->limit(1,5)->select();
		for($i=0; $i<count($futureProducts); $i++){
			$futureProducts[$i]["start_time_seconds"] = strtotime($futureProducts[$i]["start_time"]);	
		}
		$closedProducts = D("Products")->limit(6,4)->select();
		$this->assign("futureProductFirst",$futureProductFirst);
		$this->assign("futureProducts",$futureProducts);
		$this->assign("closedProducts",$closedProducts);
		$this->display();
	}
	
	
	
}