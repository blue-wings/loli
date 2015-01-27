<?php
class subscribeAction extends commonAction{
	public function mine(){
		$this->assign("displayNewUserItem",$this->isNewMember());
        $products = $this->getAllProductsAndShowPage("0");
		$products = $this->formatProductPrice($products); 
		$products = $this->formatProductCountdown($products);	
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
        $this->assign("mineSelect","select");
		$this->display();
	}
	

	
	public function newuser(){

        $this->assign("displayNewUserItem",true);
		$products = $this->getAllProductsAndShowPage("3");
		
		$products = $this->formatProductPrice($products);
			
	    $this->showCountDown();
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
        $this->assign("newuserSelect","select");
		$this->display();
	}	
	
	public function advance(){
		$this->assign("displayNewUserItem",$this->isNewMember());		
		$products = $this->getAllProductsAndShowPage("1");		
		$products = $this->formatProductPrice($products);
		$products = $this->formatProductCountdown($products);			
		$this->assign("list",$products);
		$this->assign("ismember",$this->userinfo['if_member']);
        $this->assign("advanceSelect","select");
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
	 private function formatProductPrice($products) {
	 	$productArray = array();
	 	foreach ($products as $product){
	 		$product["price"]= bcdiv($product["price"],100, 2);
	 		$product["member_price"] = bcdiv($product["member_price"],100, 2);
	 		array_push($productArray, $product);
	 	}
		return $productArray;	
	}
	
	private function formatProductCountdown($products) {
		$productsResult = array();
		foreach($products as $product) {
			$dateOffset= strtotime($product["start_time"])-time();
			if($dateOffset<0){
				$product["start"]=true;
			}else{
				$product["start"]=false;
				$product["start_time_seconds"]=strtotime($product["start_time"]);
			}
			array_push($productsResult, $product);
		}
		return $productsResult;
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
        $sql = "select count(p.pid) as count from products as p, inventory_item as item where p.inventory_item_id=item.id and p.end_time> now() and (inventory - inventoryreduced)>0 and p.status= ".C("PRODUCT_STATUS_PUBLISHED")." and FIND_IN_SET(".$productType.",p.user_type) and item.firstcid in (" .$categoryIds .")";
		$model= new Model();
		$productCount = $model->query($sql);
		$count = $productCount[0]['count'];
		$p = new Page($count,8);
		$pageSql = $p->firstRow.','.$p->listRows;
		$productSql = "select p.pid as productIds, p.inventory_item_id as inventoryItemIds  from products as p, inventory_item as item where p.inventory_item_id=item.id and p.end_time> now() and (inventory - inventoryreduced)>0 and p.status= ".C("PRODUCT_STATUS_PUBLISHED")." and FIND_IN_SET(".$productType.",p.user_type) and item.firstcid in (" .$categoryIds .")" ."order by p.sort_num limit " . $pageSql;
		$prodcutIdList = $model->query($productSql);
		$productIdsStr = "";
        $inventoryItemIdsStr = "";
		for($i=0; $i<count($prodcutIdList); $i++){
			$productIdsStr .= $prodcutIdList[$i]["productIds"];
            $inventoryItemIdsStr .= $prodcutIdList[$i]["inventoryItemIds"];
			if($i != count($prodcutIdList)-1){
				$productIdsStr .= ",";
                $inventoryItemIdsStr .=",";
			}	
		}
		$products = D("Products")->where("pid in (".$productIdsStr.")")->select();
        $inventoryItems = M("InventoryItem")->where("id in (".$inventoryItemIdsStr.")")->select();
        foreach($inventoryItems as $index=>$inventoryItem){
            $inventoryMap[$inventoryItem["id"]]=$inventoryItem;
        }
        foreach($products as $index => $product){
            $product["inventoryItem"]=$inventoryMap[$product["inventory_item_id"]];
            $products[$index]=$product;
        }
		$page=$p->show();
		$this->assign('page',$page);
		return $products;
	}
	
	public function theirs(){

        $productsModel = M("Products");
        
        $startTime = date("Y-m-d H:i:s",time());
        $where["start_time"]=array('egt',$startTime);
        $where["status"]=c('PRODUCT_STATUS_PUBLISHED');
        $futureProdcutsList = $productsModel->where($where)->order("pre_share_sort_num ASC")->limit(6)->select();
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
        
		$whereClosed["end_time"]=array('elt',$startTime);
		$whereClosed["status"]=c('PRODUCT_STATUS_PUBLISHED');
		$closedProducts = $productsModel->where($whereClosed)->order("end_time DESC")->limit(5)->select();
		for($i=0; $i<count($closedProducts); $i++){
			$closedProducts[$i]["end_time_seconds"] = strtotime($closedProducts[$i]["end_time"]);	
		}
        $futureProductFirst = $this->fillInventoryItems($futureProductFirst, true);
        $futureProducts = $this->fillInventoryItems($futureProducts, false);
        $closedProducts = $this->fillInventoryItems($closedProducts, false);
		$this->assign("futureProductFirst",$futureProductFirst);
		$this->assign("futureProducts",$futureProducts);
		$this->assign("closedProducts",$closedProducts);
		$this->display();
	}

    private function fillInventoryItems($products, $single){
        if($single==true){
            $inventoryItem = M("InventoryItem")->getById($products["inventory_item_id"]);
            $products["inventoryItem"]=$inventoryItem;
            return $products;
        }
        $inventoryItemIdsStr = "";
        for($i=0; $i<count($products); $i++){
            $inventoryItemIdsStr .= $products[$i]["inventory_Item_Id"];
            if($i != count($products)-1){
                $inventoryItemIdsStr .=",";
            }
        }
        $inventoryItems = M("InventoryItem")->where("id in (".$inventoryItemIdsStr.")")->select();
        foreach($inventoryItems as $index=>$inventoryItem){
            $inventoryMap[$inventoryItem["id"]]=$inventoryItem;
        }
        foreach($products as $index => $product){
            $product["inventoryItem"]=$inventoryMap[$product["inventory_item_id"]];
            $products[$index]=$product;
        }
        return $products;
    }

    public function getAllSubscribeFirstCategories(){
        $firstCategories = M("Category")->where(array("ctype"=>1, "pcid"=>0))->select();
        $subscribes = M("UsersProductsCategorySubscribe")->where(array("user_id"=>$this->userid))->select();
        foreach($subscribes as $key =>$val){
            $subscribeMap[$val["product_category_id"]]=true;
        }
        foreach($firstCategories as $key =>$val){
            if($subscribeMap[$val["cid"]]==true){
                $val["subscribe"]=true;
                $firstCategories[$key]=$val;
            }
        }
        $this->assign("firstCategories", $firstCategories);
        $this->assign("subscribeCategorySelect","select   ");
        $this->display();
    }

    public function subscribeCategories(){
        $firstCategoryIds = $_POST["firstCategoryIds"];
        if(!$firstCategoryIds){
            $this->ajaxReturn(array("status"=>"n","info"=>"订阅失败!"), "JSON");
        }
        M("UsersProductsCategorySubscribe")->where(array("user_id"=>$this->userid))->delete();
        $time = date("Y-m-d H:i:s");
        foreach($firstCategoryIds as $key=>$val){
            $param["product_category_id"]=$val;
            $param["user_id"]=$this->userid;
            $param["subscribe_time"]=$time;
            M("UsersProductsCategorySubscribe")->add($param);
        }
        $this->ajaxReturn(array("status"=>"y","info"=>"订阅成功!"), "JSON");
    }
	
	
}