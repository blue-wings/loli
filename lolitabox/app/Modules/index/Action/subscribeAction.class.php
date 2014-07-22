<?php
class subscribeAction extends commonAction{
	
	public function mine(){
		$userinfo=D("Users")->getUserInfo($this->userid);
		$registerTime = strtotime($userinfo['addtime']);
		$is_member = $this->userinfo['if_member'];
		$dataOffset = time() - $registerTime;
		$displayNewUserItem = true;
		if($dataOffset/(3600 *24) > 7){
			$displayNewUserItem = false;
		}
		$this->assign("displayNewUserItem",$displayNewUserItem);
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
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
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
		$this->assign("ismember",$is_member);
		$this->display();
	}
	
	
	
	
	public function year(){
		$userinfo=D("Users")->getUserInfo($_COOKIE['loli_userid']);
		$registerTime = strtotime($userinfo['addtime']);
		$dataOffset = time() - $registerTime;
		$displayNewUserItem = true;
		if($dataOffset/(3600 *24) > 7){
			$displayNewUserItem = false;
		}
		$this->assign("displayNewUserItem",$displayNewUserItem);
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
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firtstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firtstcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
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
		$userinfo=D("Users")->getUserInfo($this->userid);
		$registerTime = strtotime($userinfo['addtime']);
		$is_member = $this->userinfo['if_member'];
		$endTime = $registerTime + 3600 *24 *7;
		$dataOffset = $endTime - time();
		$dayOffSet = floor($dataOffset/(3600 *24));
		$hourOffSet = floor(($dataOffset - $dayOffSet*24*3600)/3600);
		$minOffSet = floor(($dataOffset - $dayOffSet*24*3600 - $hourOffSet*3600)/60);
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
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
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
		$this->assign("endTime",$endTime);
		$dayOffSetStr=$dayOffSet;
		$hourOffSetStr=$hourOffSet;
		$minOffSetStr=$minOffSet;

		if($hourOffSet<10){
			$hourOffSetStr = "0".$hourOffSetStr;
		}
		if($minOffSetStr<10){
			$minOffSetStr = "0".$minOffSetStr;
		}
		$this->assign("dayOffset",$dayOffSetStr);
		$this->assign("hourOffset",$hourOffSetStr);
		$this->assign("minOffset",$minOffSetStr);
		$this->assign('page',$page);
		$this->assign("list",$products);
		$this->assign("ismember",$is_member);
		
		$this->display();
	}	
	
	public function advance(){
		$userinfo=D("Users")->getUserInfo($this->userid);
		$registerTime = strtotime($userinfo['addtime']);
		$is_member = $this->userinfo['if_member'];
		$dataOffset = time() - $registerTime;
		$displayNewUserItem = true;
		if($dataOffset/(3600 *24) > 7){
			$displayNewUserItem = false;
		}
		$this->assign("displayNewUserItem",$displayNewUserItem);		$userid = $this->userid;
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
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$userType.",p.user_type) and p.firstcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
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
		$this->assign("ismember",$is_member);
		$this->display();
	}

    public function subscribeCategory(){
        $categoryIds = $_POST["categoryIds"];
        $userid = $this->userid;
        $usersProductsCategorySubscribe = D("UsersProductsCategorySubscribe");
        foreach($categoryIds as $id){
            $subscribe  = $usersProductsCategorySubscribe->getByUserIdAndProductsCategoryId($userid,$id);
            if(!is_null($subscribe)){
                continue;
            }
            $data['product_category_id'] = $id;
            $data['user_id'] = $userid;
            $data['subscribe_time'] = date("Y-m-d H:i:s");
            $result = $usersProductsCategorySubscribe->add($data);
            if(!$result){
                $this->ajaxReturn($result,'订阅失败',1);
            }
        }
        $this->ajaxReturn(0,'订阅成功',1);
    }
}