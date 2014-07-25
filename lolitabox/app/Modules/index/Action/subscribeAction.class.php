<?php
class subscribeAction extends commonAction{
	public function mine(){

		$this->assign("displayNewUserItem",$this->isNewMember());
        $products = $this->getAllProductsAndShowPage("0");
		$products = $this->formatProductPrice($products); 
		$products = $this->formatProductCountdown($products);	
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
		$this->display();
	}
	

	
	public function newuser(){
		
		
		$products = $this->getAllProductsAndShowPage("3");
		
		$products = $this->formatProductPrice($products);
			
	    $this->showCountDown();
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
		
		$this->display();
	}	
	
	public function advance(){
		$this->assign("displayNewUserItem",$this->isNewMember());		
		$products = $this->getAllProductsAndShowPage("1");		
		$products = $this->formatProductPrice($products);
		$products = $this->formatProductCountdown($products);			
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
		
		$this->display();
	}
	
	
	
	public function year(){

		$this->assign("displayNewUserItem",$this->isNewMember());
		$userid = $this->userid;
        $products = $this->getAllProductsAndShowPage("2");        
		$products = $this->formatProductPrice($products);
		$this->assign("ismember",$this->userinfo['if_member']);
		$this->assign("list",$products);
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
    
    public function isNewMember(){
    	$userinfo=D("Users")->getUserInfo($this->userid);
		$registerTime = strtotime($userinfo['addtime']);
		$dataOffset = time() - $registerTime;
		$isNewMember = true;
		if($dataOffset/(3600 *24) > 7){
			$isNewMember = false;
		}
		return $isNewMember;
    }
    
    private function showCountDown(){
    	$userinfo=D("Users")->getUserInfo($this->userid);
		$registerTime = strtotime($userinfo['addtime']);
		$endTime = $registerTime + 3600 *24 *7;
		$dataOffset = $endTime - time();
		$dayOffSet = floor($dataOffset/(3600 *24));
		$hourOffSet = floor(($dataOffset - $dayOffSet*24*3600)/3600);
		$minOffSet = floor(($dataOffset - $dayOffSet*24*3600 - $hourOffSet*3600)/60);
				
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
    }
	private function formatProduct($productsArray,$func) {
	     $productsResult = array(); 
	     foreach($productsArray as $product) {
	         if($productResult = $func($product)) {
	             $productsResult[] = $productResult;
	         }
	     }
	     return $productsResult;
	}
	 private function formatProductPrice($products) {
	 	  $func1 = '$product["price"]= $product["price"]/100; $product["member_price"] = $product["member_price"]/100; return $product;';
		  return $this->formatProduct($products,create_function('$product',$func1));
	
	}
	
	 private function formatProductCountdown($products) {
	 	 $func1 = '$dateOffset= strtotime($product["start_time"]) - time(); if($dateOffset<0){$product["start"]=true; }else{$product["start"]=false; $product["end_day"]= floor($dateOffset/3600/24); $product["end_hour"]= floor(($dateOffset-$product["end_day"]*3600*24)/3600); $product["end_min"] = floor(($dateOffset-$product["end_day"]*3600*24-3600* $product["end_hour"])/60); $product["end_sec"] = floor($dateOffset-$product["end_day"]*3600*24-3600* $product["end_hour"]- $product["end_min"]*60);if($product["end_day"]<10){$product["end_day"]="0".$product["end_day"];} if($product["end_hour"]<10){$product["end_hour"]="0".$product["end_hour"];}if($product["end_min"]<10){$product["end_min"]="0".$product["end_min"];}if($product["end_sec"]<10){$product["end_sec"]="0".$product["end_sec"];}}  return $product;';
	 	 // $func1 = '$dateOffset= strtotime($product["start_time"]) - time(); if($dateOffset<0){$product["start"]=true; }else{$product["start"]=false; $product["end_hour"]= floor($dateOffset/3600); $product["end_min"] = floor(($dateOffset-3600* $product["end_hour"])/60); $product["end_sec"] = floor($dateOffset-3600* $product["end_hour"]- $product["end_min"]*60); if($product["end_hour"]<10){$product["end_hour"]="0".$product["end_hour"];}if($product["end_min"]<10){$product["end_min"]="0".$product["end_min"];}if($product["end_sec"]<10){$product["end_sec"]="0".$product["end_sec"];}}  return $product;';
		  return $this->formatProduct($products,create_function('$product',$func1));
	
	}
	
	private function getAllProductsAndShowPage($productType){
		$userid = $this->userid;
		import("ORG.Util.Page");
		$usersProductsCategorySubscribe = D("UsersProductsCategorySubscribe");
		$subscribes = $usersProductsCategorySubscribe->getByUserId($userid);
		if(count($subscribes) == 0){
			$this->error("尚未订阅任何分类，请订阅!");
		}
		$categoryIds = "";
		for($i=0; $i<count($subscribes); $i++){
			$categoryIds .= $subscribes[$i]["product_category_id"];
			if($i != count($subscribes)-1){
				$categoryIds .= ",";
			}
		}
		$sql = "select count(distinct(p.pid)) as count from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$productType.",p.user_type) and p.end_time> now() and (inventory - inventoryreduced)>0 and p.firstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select distinct(p.pid) as productIds from products  as p, product_effect_relation as per where p.pid=per.pid and FIND_IN_SET(".$productType.",p.user_type) and p.end_time> now() and (inventory - inventoryreduced)>0 and p.firstcid in (" .$categoryIds .")" ."order by p.pid limit " . $pageSql;
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
		return $products;
	}
	
	
}