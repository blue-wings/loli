<?php
/**
 * 自选盒子产品模型
 */
class BoxProductsModel extends Model {
	
	
	/**
	 * 自选的单品的详细信息
	 * @param $pid 【inventory_item 下的id】
	 * @return $select_products_info [inventory_item的产品信息]
	 * @author penglele
	 */
	public function getProductsInfo($pid){

		if(empty($pid)){
			return false;
		}
		$pro_item_mod=M("InventoryItem");
		$pro_mod=M("products");

		$pro_item_info=$pro_item_mod->where("id=$pid")->find();
		if(!$pro_item_info){
			return false;
		}
		//通过relation_id关联productspid
		$pro_info=$pro_mod->where("pid=".$pro_item_info['relation_id'])->find();
		if(!$pro_info){
			return false;
		}
		$select_products_info['pname']=$pro_info['pname'];
		$select_products_info['pimg']=$pro_info['pimg'];
		$select_products_info['norms']=$pro_item_info['norms'];//库存的产品规格
		$select_products_info['itemid']=$pid;//inventory_item 的id
		$select_products_info['totalscore']=$pro_info['totalscore'];//产品的平均评分
		$select_products_info['productid']=$pro_info['pid'];//products下的产品ID
		$select_products_info['productlist']=$pro_info['productlist'];//是否为套装，非空则为套装
		$select_products_info['evaluatenum']=$pro_info['evaluatenum'];
		$select_products_info['producturl']=getProductUrl($pro_info['pid']);
		$select_products_info['price']=$pro_item_info['price'];
		$select_products_info['credit']=round($pro_item_info['price']*10);
		return $select_products_info;
	}
	
	/**
	 * 获取产品的实际库存数
	 * @param $pid 【inventory_item下的ID】
	 */
	public function getProductInventoryEstimatedNum($pid){
		$item_mod=M("InventoryItem");
		$item_info=$item_mod->field("inventory_estimated")->where("id=$pid")->find();
		if(!$item_info)
			return false;
		return (int)$item_info["inventory_estimated"];
	}
	
	/**
	 * 根据条件获取自由选的产品
	 * @param $where 查询条件
	 * @author penglele
	 */
	public function getBoxProductsList($where="1=1",$field="*",$limit=""){
		$products_list=$this->field($field)->where($where)->order("sortnum DESC")->limit($limit)->select();
		return $products_list;
	}
	
	/**
	 * 根据 产品ID及盒子ID 获得自由选产品的详细信息
	 * @param $pid 单品ID
	 * @param $boxid 盒子ID
	 * @author penglele
	 */
	public function getBoxProductInfo($pid,$boxid,$field="*"){
		if(empty($pid) || empty($boxid))
			return false;
		$product_info=$this->field($field)->where("pid=$pid AND boxid=$boxid")->find();
		if(!$product_info)
			return false;
		$product_info['now_num']=D("InventoryItem")->getProductInventory($pid,$boxid);
		return $product_info;
	}
	
	
	/**
	 * 按分类获取自选盒的产品列表
	 * @param $boxid 盒子ID
	 */
	
	public function getBoxProductByType($boxid){
		if(!$boxid) return false;
		$pro_list=$this->getBoxProductsList(array("boxid"=>$boxid,"ishidden"=>0));
		if(!$pro_list) return false;
		$product_list=array();
		$pro_mod=D("Products");
		foreach($pro_list as $key=>$val){
			$ikey=$val['maxquantitytype'];
			$pro_info=$pro_mod->getSimpleInfoByItemid($val['pid']);
			unset($val['id']);
			unset($val['pid']);
			if($pro_info!=false){
				$val=array_merge($val,$pro_info);
				$true_num=$this->getInventoryNum($val['id'],$boxid);
				$val['now_num']=$true_num;//当前产品还可以选择的数量
				$product_list[$ikey][]=$val;
			}
		}		
		ksort($product_list);
		return $product_list;
	}
	
	
	/**
	 * 获取自选类型名称
	 * @param $boxid 盒子ID	
	 */
	public function getBoxProductsCname($boxid){
		if(!$boxid) return false;
		$cname_list=M("BoxProductsCname")->where("boxid=$boxid")->order("maxquantity ASC")->select();
		if(!$cname_list) return false;
		$sname=array();
		foreach($cname_list as $key=>$val){
			$ikey=$val['maxquantity'];
			if($key==0 || $key==1 || $key==(count($cname_list)-1)){
				$arr=array(
						'key'=>$ikey,
						'info'=>$val['title'],
						);
				$title_list[]=$arr;
			}
			$sname[$ikey]['title']=$val['title'];
		}
		$return['title_list']=$title_list;
		$return['cname_list']=$sname;
		return $return;
	}
	
	/**
	 * 获取免费积分兑换已选产品列表
	 */
	public function getBoxProductSessionList($sname,$boxid){
		if(!$sname) return false;
		if(!$_SESSION[$sname]) return false;
		$list=array();
		foreach($_SESSION[$sname] as $ikey=>$val){
			foreach($val as $skey=>$value){
				if($value){
					$info=$this->getProductsInfo($value);
					$info['type']=$ikey;
					if($boxid){
						$box_product_info=$this->field("pquantity,discount")->where("boxid=$boxid AND pid=$value")->find();
					}
					$info['discount_score']=(int)(round($info['credit']*$box_product_info['discount'])*$box_product_info['pquantity']);
					$list[]=$info;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 免费积分试用的产品列表
	 * @param string $score 积分类型【为空或者 1、2、3 类型】
	 * @param int $boxid 盒子ID
	 * @param string $limit 限制条数
	 * @return array $list
	 * @author penglele
	 */
	public function getScoreProductList($score,$boxid,$limit,$userid,$sname,$order){
		if(!$boxid){
			return false;
		}
		$where="";
		if($score==1 || !$score){
			$where="";
		}else{
			//$score=6表示积分范围在【1201+】
			if($score==6){
				$where="AND i.price>120";
			}else if($score==7){
				//score=7表示查看的是用户可兑换的范围，只有当用户登录才可选择该范围
				if($userid){
					$userinfo=D("Users")->getUserInfo($userid,"score");
					if($userinfo['score']>0){
						$u_score=$userinfo['score']/10;
						$where="AND i.price <='".$u_score."' AND i.price>=0";
					}else{
						$where="AND i.price<=0";
					}
				}
			}else{
				$arr=explode(",",$this->returnScoreRange($score));
				$sprice=$arr[0]/10;
				$eprice=$arr[1]/10;
				$where="AND i.price<='".$eprice."' AND i.price>='".$sprice."'";
			}
		}
		//排序
		if($order=="down"){
			$s_order="i.price DESC";
		}else{
			$s_order="i.price ASC";
		}
		
		$sql="SELECT i.id,i.brandid,i.intro,b.pquantity,b.discount FROM inventory_item i,box_products b WHERE i.id=b.pid AND b.boxid=$boxid AND b.ishidden=0 $where ORDER BY b.sortnum DESC LIMIT $limit";
		$list=$this->query($sql);
		if(!$list)
			return "";
		$pro_mod=D("Products");
		$brand_mod=D("ProductsBrand");
		if($list){
			foreach($list as $key=>$val){
				$info=$pro_mod->getSimpleInfoByItemid($val['id']);
				
				$info['now_num']=$this->getInventoryNum($val['id'],$boxid);
				
				
				$info['score']=round($info["price"]*$val['pquantity']*10);//产品积分
				if($val['discount']=="0.00"){
					$info['member_score']=$info['score'];//特权会员积分值
				}else{
					$info['member_score']=round($info['score']*$val['discount']);//特权会员积分值
				}
				
				
				$list[$key]=array_merge($val,$info);
				if($userid && in_array($val[id],$_SESSION[$sname])){
					$list[$key]['if_select']=1;
				}else{
					$list[$key]['if_select']=0;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 可选择的积分范围
	 */
	public function returnScoreRange($key){
		$list=array(
				"1"=>"",
				"2"=>"0,100",
				"3"=>"101,300",
				"4"=>"301,500",
				"5"=>"501,1200",
				"6"=>"1201+"
				);
		return $list[$key];
	}
	
	/**
	 * 免费积分试用的产品总数
	 */
	public function getScoreProductNum($score,$boxid,$userid){
		if(!$boxid){
			return 0;
		}
		
		$where="";
		if($score==1 || !$score){
			$where="";
		}else{
			//$score=6表示积分范围在【1201+】
			if($score==6){
				$where="AND i.price>120";
			}else if($score==7){
				//score=7表示查看的是用户可兑换的范围，只有当用户登录才可选择该范围
				if($userid){
					$userinfo=D("Users")->getUserInfo($userid,"score");
					if($userinfo['score']>0){
						$u_score=$userinfo['score']/10;
						$where="AND i.price <='".$u_score."' AND i.price>=0";
					}else{
						$where="AND i.price<=0";
					}
				}
			}else{
				$arr=explode(",",$this->returnScoreRange($score));
				$sprice=$arr[0]/10;
				$eprice=$arr[1]/10;
				$where="AND i.price<='".$eprice."' AND i.price>='".$sprice."'";
			}
		}
		
		$sql="SELECT count(i.id) as num FROM inventory_item i,box_products b WHERE i.id=b.pid AND b.boxid=$boxid AND b.ishidden=0 $where";
		$num=$this->query($sql);
		return (int)$num[0]['num'];
	}
	
	/**
	 * 获取免费积分试用已选产品列表
	 * @param $arr session中的已选产品信息
	 */
	public function getExchangeProductList($arr,$boxid,$userid){
		if(!isset($arr) || count($arr)==0){
			return "";
		}
		$list=array();
		$member_state=D("Member")->getUserIfMember($userid);
		foreach($arr as $key=>$val){
			$info=$this->getProductsInfo($val,$boxid);
			$pro_info=$this->field("pquantity,discount,maxquantitytype")->where("boxid=$boxid AND pid=$val")->find();
			$info['score']=$pro_info['pquantity']*$info['credit'];
			$info['member_score']=$info['score'];
			if($member_state==1){
				$discount=$pro_info['discount']=="0.00" ? 1 : $pro_info['discount'] ;
				$info['member_score']=round($info['member_score']*$discount);
			}
			$info['discount_score']=(int)(round($info['credit']*$pro_info['discount'])*$pro_info['pquantity']);
			$info['maxquantitytype']=$pro_info['maxquantitytype'];
			$list[$key]=$info;
		}
		return $list;
	}
	
	/**
	 * 正在售卖的积分兑换萝莉盒-产品列表
	 */
	public function getExchangeProductlistOnselling(){
		$ndate=date("Y-m-d");
		$box_mod=M("Box");
		$boxinfo=$box_mod->where("boxid=".C("BOXID_CREDITEXCHANGE")." AND state=1")->order("boxid DESC")->find();
		if(!$boxinfo){
			return false;
		}
		$productlist=$this->field("pid")->where("boxid=".$boxinfo['boxid']." AND ishidden=0")->select();
		if(!$productlist){
			return false;
		}
		$list=array();
		foreach($productlist as $key=>$val){
			if($val['pid']){
				$list[]=$val['pid'];
			}
		}
		return $list;
	}
	
	/**
	 * 通过单品ID、盒子ID获取自选产品的数量
	 * @param $id inventory_item 下的ID
	 * @param $boxid 盒子ID
	 * @author penglele
	 */
	public function getExchangeScoreById($id,$boxid=""){
		$return['score']=0;
		$return['num']=1;
		if(!$id){
			return $return;
		}
		$box_mod=M("Box");
		if($boxid==""){
			$ndate=date("Y-m-d");
			$boxinfo=$box_mod->where("category=".C("BOX_TYPE_EXCHANGE_PRODUCT")." AND starttime<='".$ndate."' AND endtime>='".$ndate."'")->order("boxid DESC")->find();
			if(!$boxinfo){
				return $return;
			}
			$boxid=$boxinfo['boxid'];
		}
		$box_pro_info=$this->field("pquantity")->where("boxid=$boxid AND pid=$id")->find();
		if(!$box_pro_info){
			return $return;
		}
		$return['num']=$box_pro_info['pquantity'];
		$item_info=D("InventoryItem")->getInventoryItemInfo($id,"price");
		if($item_info){
			$return['score']=(int)($item_info['price']*10)*$return['num'];
		}
		return $return;
	}
	
	/**
	 * 通过产品ID获取当前单品在积分兑换中的积分状况
	 * @param $pid 产品ID
	 * @author penglele
	 */
	public function getExchangeScoreByPid($pid,$boxid=""){
		$item_info=M("InventoryItem")->field("id")->where("relation_id=".$pid)->find();
		return $this->getExchangeScoreById($item_info['id'],$boxid);
	}
	
	/**
	 * 使用中心-使用列表
	 * 返回值type值的意义为：type=1积分兑换，type=2付邮试用
	 * @param $boxid 盒子ID
	 * @param $limit 限制条数
	 * @author penglele
	 */
	public function getTryList($boxid="",$limit="",$score,$userid,$sname,$s_order){
		//积分兑换列表
		if($boxid==C("BOXID_CREDITEXCHANGE")){
			return $this->getScoreProductList($score,$boxid,$limit,$userid,$sname,$s_order);
		}
		$where['boxid'] = $boxid == "" ? $where['boxid']=array("exp","in(".C("BOXID_CREDITEXCHANGE").",".C("BOXID_PAYPOSTAGE").")") : $boxid ;
		$where['ishidden']=0;
		$order="sortnum DESC,id DESC";
		$list=$this->where($where)->order($order)->limit($limit)->select();
		if($list){
			$pro_mod=D("Products");
			foreach($list as $key=>$val){
				$info=$pro_mod->getSimpleInfoByItemid($val['pid']);
				$list[$key]=array_merge($val,$info);
				if($val['boxid']==C("BOXID_CREDITEXCHANGE")){
					if($val['discount']=="0.00"){
						$list[$key]['member_score']=$info['score'];
					}else{
						$list[$key]['member_score']=round($info['score']*$val['discount']);
					}
					$list[$key]['type']=1;
				}else if($val['boxid']==C("BOXID_PAYPOSTAGE")){
					$list[$key]['type']=2;
				}
				$list[$key]['now_num']=$this->getInventoryNum($val['pid'],$val['boxid']);
				$list[$key]['ptotal']=$val['settotal'] > 0 ? $val['settotal'] : $val['ptotal'];
 			}
		}
		return $list;
	}
	
	/**
	 * 试用产品的总数
	 * @author penglele 
	 */
	public function getTryCount($boxid,$score){
		if($boxid==C("BOXID_CREDITEXCHANGE")){
			return $this->getScoreProductNum($score,$boxid);
		}
		$where['boxid'] = $boxid == "" ? array("exp","in(".C("BOXID_CREDITEXCHANGE").",".C("BOXID_PAYPOSTAGE").")") : $boxid ;
		$where['ishidden']=0;
		$count=$this->where($where)->count();
		return $count;
	}
	
	/**
	 * 判断产品是否属于付邮试用
	 * @param $pid ID
	 * @param $type 表示产品类型 $type=1表示单品ID,$type=2表示产品ID
	 * @author penglele
	 */
	public function getTryProductInfo($pid,$type=1){
		if(!$pid){
			return false;
		}
		$id=$pid;
		if($type==2){
			$item_info=M("InventoryItem")->field("id")->where("relation_id=$pid AND status=1")->find();
			if(!$item_info){
				return false;
			}
			$id=$item_info['id'];
		}
		$info=$this->where("pid=$id AND ishidden=0 AND boxid=".C("BOXID_PAYPOSTAGE"))->find();
		if(!$info){
			return false;
		}
		return $info;
	}
	
	/**
	 * 产品目前的库存量(大于0才能继续选择)
	 * @author penglele
	 */
	public function getInventoryNum($id,$boxid){
		if(!$id)
			return false;
		$box_pro_info=$this->where("pid=$id AND boxid=$boxid")->find();
		if(!$box_pro_info){
			return 0;
		}
		$box_pro_count=$box_pro_info["ptotal"]-$box_pro_info["saletotal"]-$box_pro_info['pquantity'];
		if($box_pro_count>=0){
			$box_pro_count=$box_pro_info["ptotal"]-$box_pro_info["saletotal"];
		}
		$item_info=D("InventoryItem")->getInventoryItemInfo($id,"inventory_estimated");
		$item_count=$item_info['inventory_estimated'];
		$count=$box_pro_count < $item_count ? $box_pro_count : $item_count;
		return $count;
	}
	
	/**
	 * 获取当前正在售卖的积分兑换或付邮试用的产品列表
	 * @author penglele
	 */
	public function getProductsListByBoxid($boxid){
		if(!$boxid){
			return false;
		}
		$prolist=array();
		$list=$this->where("boxid=$boxid AND ishidden=0")->select();
		if($list){
			$item_mod=D("InventoryItem");
			foreach($list as $key=>$val){
				$item_info=$item_mod->getInventoryItemInfo($val['pid'],"norms,price");
				$info=array();
				$id=$val['pid'];
				$info['id']=$id;
				//积分兑换
				$info['norms']=$item_info['norms'];
				//付邮试用
				if($boxid==C("BOXID_CREDITEXCHANGE")){
					$info['score']=(int)($item_info['price']*10);
					$info['url']="/try/iexchange/id/".$id;
				}				
				$prolist[$id]=$info;
			}
		}
		return $prolist;
	}
	
	/**
	 * 判断产品是否是积分兑换产品，且判断库存量的多少
	 * @author penglele
	 */
	public function checkIfExchangeProduct($pid){
		$boxid=C("BOXID_CREDITEXCHANGE");
		$boxinfo=D("Box")->getBoxInfo($boxid,"state");
		if(!$boxinfo || $boxinfo['state']!=1 || !$pid){
			return false;
		}
		$boxproduct_info=$this->where("boxid=$boxid AND pid=$pid AND ishidden=0")->find();
		if(!$boxproduct_info){
			return false;
		}
		//当前产品的库存
		$num=$this->getInventoryNum($pid,$boxid);
		return $num;
	}

	/**
	 * 重新支付时，如果是自选盒，则判断用户已选的产品是否有已售完的
	 * @param orderid 用户订单id【必须】
	 * @param boxid 盒子id【必须】
	 * @author penglele
	 */
	public function checkRepayProduct($orderid,$boxid,$userid){
		if(empty($orderid) || empty($boxid) || empty($userid)){
			return false;
		}
		$order_send_mod=M("UserOrderSendProductdetail");
		//UserOrderSendProductdetail表中，当前订单下的产品列表
		$order_product_list=$order_send_mod->where("orderid=$orderid AND userid=$userid")->select();
		if(!$order_product_list){
			return false;
		}
		// 		$box_pro_mod=D("BoxProducts");
		for($i=0;$i<count($order_product_list);$i++){
			//通过productid与boxproduct表关联，查看当前非福利产品是否已售完，如果有return false
			$product_info=$this->field("ptotal,saletotal,pquantity,maxquantitytype,pid")->where("boxid=$boxid AND pid=".$order_product_list[$i]['productid'])->find();
			//新增判断产品库存是否>0 
			$inventory_realnum=$this->getInventoryNum($product_info['pid'],$boxid);
			if($inventory_realnum<=0 && $product_info['maxquantitytype']!=0){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 通过时间获取用户已购买的产品ID
	 * @param $sdate 【例：201310】
	 * @author penglele
	 */
	public function getProductIDByDate($sdate){
		$str="";
		if(!$sdate){
			return $str;
		}
		$arr=array();
		$sql="SELECT DISTINCT(productid) FROM user_order_send_productdetail WHERE orderid IN (SELECT ordernmb FROM user_order WHERE boxid IN (SELECT boxid FROM `box`  WHERE state=1 AND date_format(`starttime`,'%Y%m')='".$sdate."'))";
		$pid_arr=$this->query($sql);
		$item_mod=M("InventoryItem");
		if($pid_arr){
			foreach($pid_arr as $key=>$val){
				$relation_id=$item_mod->where("id=".$val['productid'])->getField("relation_id");
				if(!in_array($relation_id, $arr) && $relation_id){
					$arr[]=$relation_id;
				}
			}
		}
		sort($arr);
		$str=implode(",",$arr);
		return $str;
	}
	
	
	
	
	
	
	
}
?>