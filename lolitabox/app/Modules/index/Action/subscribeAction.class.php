<?php
class subscribeAction extends commonAction{
	
	public function mine(){
		$userid = $this->userid;
		$userType = "0";
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
		
        $productsModel = M("Products");
        
        $startTime = date("Y-m-d H:i:s",time()+C('SUBSCRIBE_FUTURE_INC'));
        $where["start_time"]=array('egt',$startTime);
        $where["status"]=c('PRODUCT_STATUS_PUBLISHED');
        $futureProdcutsList = $productsModel->where($where)->order("start_time ASC")->limit(6)->select();
        $count = count($futureProdcutsList);
        $futureProductFirst = NULL;
        $futureProducts = array();
        if($count){
        	$futureProductFirst = $futureProdcutsList[0];	
        	$futureProductFirst["start_time_seconds"] = strtotime($futureProductFirst["start_time"]);
        }
        if($count>1){
	        for($i=1; $i<$count; $i++){
	        	$num = $i-1;
	        	$futureProducts[$num]=$futureProdcutsList[$i];
				$futureProducts[$num]["start_time_seconds"] = strtotime($futureProducts[$num]["start_time"]);	
			}	
        }
        
        $endTime = date("Y-m-d");
		$whereClosed["end_time"]=array('elt',$startTime);
		$whereClosed["status"]=c('PRODUCT_STATUS_PUBLISHED');
		$closedProducts = $productsModel->where($whereClosed)->order("end_time DESC")->limit(5)->select();
		for($i=0; $i<count($closedProducts); $i++){
			$closedProducts[$i]["end_time_seconds"] = strtotime($closedProducts[$i]["end_time"]);	
		}
		$this->assign("futureProductFirst",$futureProductFirst);
		$this->assign("futureProducts",$futureProducts);
		$this->assign("closedProducts",$closedProducts);
		$this->display();
	}
	
	
	
	public function year(){
		$userid = $this->userid;
		$userType = "2";
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
	
	public function newuser(){
		$userid = $this->userid;
		$userType = "3";
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
	
	public function advance(){
		$userid = $this->userid;
		$userType = "1";
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
}