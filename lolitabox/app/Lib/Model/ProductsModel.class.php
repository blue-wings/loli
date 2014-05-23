<?php
//产品模型类
class ProductsModel extends Model {

     /**
      * 获取产品列表
      * @param mixed $where 
      * @param string $p
      */    
     public function getProductsList($where=array(),$p=null,$order=null,$me="",$field=""){
     	$where['status'] = 1;
     	$list=$this->where($where)->limit($p)->field($field)->order($order)->select();
     	$follow_mod = M("Follow");
     	foreach($list as $key=>$val){
     		$list [$key]['prourl'] = getProductUrl($val['pid']);
     		if($me){
     			$type =$follow_mod ->where("userid=".$me." AND whoid=".$val['pid']." AND type=2")->find();
     			$list[$key]['type'] = $type ? 1:0;
     		}
     		
     		if($val['if_super']==1){
     			$list[$key]['if_super']=2;
     		}else{
     			$list[$key]['if_super']=0;
     		}
     	}
     	return $list;
     }
     
     /**
      * 获取感兴趣的产品
      * @param string $limit
      */
     public function getInterestProductsList($limit){
     	$order = "evaluatenum DESC";
     	return $this ->getProductsList(array(),$limit,$order,"","pid,pname,evaluatenum,pimg" );
     	 
     }
     
     /**
      * 获取推荐的产品列表
      * @param unknown_type $limit
      * @param unknown_type $order
      */
     public function getCommendProductList($limit="0,5",$order="pid DESC",$me=""){
     	$where ['iscommend'] = 1;
     	return $this ->getProductsList($where,$limit,$order,$me);
     }
     
     /**
      * 通过品牌ID获取单品列表
      * @param int $brandid
      * @param mixed $p
      */
     public function getProductsListByBrandid($brandid,$p=null,$order="pid DESC"){
     	$where['brandcid']=$brandid;
     	return $this->getProductsList($where,$p,$order);
     }
     
     /**
      * 通过品牌id获取合作产品列表
      * @param unknown_type $brandid
      * @param unknown_type $limit
      * @author litingting
      */
     public function getCooperateProductsList($brandid,$limit){
     	$where['brandcid']=$brandid;
     	$where["_string"] = "exists (select 1 from inventory_item where relation_id=products.pid )";
     	return $this->getProductsList($where,$limit,"pid DESC");
     }
     
     /**
      * 根据品牌id获取产品总数
      * @param unknown_type $brandid
      */
     public function getProductNumByBrandid($brandid){
     	$where['brandcid']=$brandid;
     	$where['status'] =1;
        return $this ->where($where)->count("brandcid");
     }
     
     /**
      * 通过tag搜索单品列表
      * @param unknown_type $tagname
      */
     public function getProductsListByTag($tagname,$p=null,$order=""){
     	$xs = new XunSouModel("products");
		$list = $xs ->search($tagname,$order,$p);
		foreach($list as $key =>$val){
			$list[$key]['if_super']= $val['if_super']?2:0;
			$list[$key]['name'] = str_replace("'","\\'",$val['pname']);
		}
     /* 	foreach ($list as $key =>$val){
     		$list[$key]['pname'] = str_replace($tagname, "<span class='S_txt3'>$tagname</span>", $list[$key]['pname']);
     	} */
     	return $list;
     }
     
     /**
      * 通过tag搜索单品总数
      * @param unknown_type $tagname
      */
     public function getProductsCountByTag($tagname){
     	$xs = new XunSouModel("products");
		return $xs->count($tagname);
     }
     
     /**
      * 通过品牌id获取该品牌最热单品列表
      * @param int $brandid
      */
     public function getHotproductListByBrandid($brandid,$limit=6){
     	$where['brandcid'] =$brandid;
     	$where['status'] =1 ;
        $list = $this->getProductsList($where,$limit,"evaluatenum desc");
     	return $list;
     
     }
     
     /**
      * 通过产品ID获取产品详情
      * @param int $pid
      * @author litingting
      */
     public function getProductInfo($pid,$field="",$flag=1){
     	if($flag==1)
     	     $where['status'] =1;
     	$where['pid'] = $pid;
     	$info = $this ->where($where)->field($field) ->find();
     	if($info){
     		//产品的功效
     		$info['effect']=$this->getProductsEffectByPid($pid);
     		
     		if($info['pimg']){
     			$info['pic'] = M("ProductsPic")->where("pid=".$pid)->order("toptime DESC")->select();
     			foreach($info['pic'] as $key =>$val){
     				$info['pic'][$key] = $val['pic_url'];
     			}
     		}
     		
     		if(isset($info['trialsize'])  ||  isset($info['trialprice'])){
     			$inventory_item = M("InventoryItem");
     			$item_info = M("InventoryItem")->field("id,norms,price")->where("relation_id=".$pid." AND category='试用装'")->find();
     			if($item_info){
     				$info['norms'] = $item_info['norms'];
     				$info['price'] = $item_info['price'];
     				$info['itemid'] = $item_info['id'];
     			}
     		}
     		
     		if($info['brandcid']){
     			$info['brandname'] = M("ProductsBrand")->where("id=".$info['brandcid'])->getField("name");
     		}
     		if($info['if_super']){
     			$info['if_super']= $info['if_super']?2:0;
     		}
     		if($info['sale_tag']){
     			$info['sale_tags'] = explode(",",$info['sale_tag']);
     		}
     		if($info['pintro']){
     			$info['pintro_all'] = $info['pintro'];
     			if(mb_strlen($info['pintro'],"utf8")>50){
     				$info['pintro'] = msubstr($info['pintro'],0,50);
     				$info['pintro_more'] =1;
     			}
     		}
     		if($info['pname']){
     			$info['name'] = str_replace("'","\\'",$info['pname']);
     		}
     	    
     		if($info['readme']){
     			$info['readme_all'] =$info['readme'];
     			if(mb_strlen($info['readme'],"utf8")>50){
     				$info['readme'] = msubstr($info['readme'],0, 50);
     				$info['readme_more'] =1;
     			}
     		}
     	}
     	if($info){
     		$info['producturl']=getProductUrl($pid);
     		//产品的购买渠道
     		$info['buylist']=$this->getProductsBuyUrl($pid);
     	}
     	return $info;
      }
      
      
	  //用户注册昵称不能与产品名重名
	  function searchPname($name){
		return $this->where(array('pname'=>$name))->find();
	  }
	  
	  
	  /**
	   * 获取某个单品下的所有子单品
	   * @param unknown_type $pid
	   */
	  public function getChildProductlist($pid){
	  	 $prolist = M("Products")->where("pid=".$pid." AND status=1")->getField("productlist");
	  	 $where['pid'] = array("in",$prolist);
	  	 return $this->getProductsList($where);
	  }
	  
	  /**
	   * 删除产品
	   * @param unknown_type $pid
	   */
	  public function delProduct($pid){
	  	 if($this->where("pid=".$pid)->delete()){
	  	 	M("Follow")->where("whoid=".$pid." AND type=2")->delete(); //删除相关关注
	  	 	return true;
	  	 }else{
	  	 	return false;
	  	 }
	  }
	  
	  /**
	   * 获取当前正在售卖的自选或主题盒内或积分兑换的产品列表
	   * @author penglele
	   */
	  public function getProductsListInBox(){
	  	$box_mod=D("Box");
	  	$boxlist=$box_mod->getBoxListOnSelling();
	  	if($boxlist){
	  		$pid_list=array();
	  		foreach($boxlist as $key=>$val){
	  			//如果当前盒子是主题盒
				$product_list=$box_mod->getBoxPidList($val['boxid'],$val['category']);  	
 	  			foreach($product_list as $ikey=>$ival){
 	  				if(empty($pid_list)){
 	  					$pid_list[$ival]=$val['boxid'];
 	  				}else{
 	  					if(isset($pid_list[$ival]) && $pid_list[$ival]!=""){
 	  						$pid_list[$ival]=$pid_list[$ival].",".$val['boxid'];
 	  					}else{
 	  						$pid_list[$ival]=$val['boxid'];
 	  					}
 	  				}
	  			} 
	  		}
	  	}
	  	return $pid_list;
	  }
	  
	 /**
	  * 通过产品ID，判断当前的产品是否在正在售卖的盒子中【主题盒/自选盒】
	  */ 
	  public function getProductIfInbox($pid){
	  	if(empty($pid))
	  		return false;
	  	$product_list=$this->getProductsListInBox();
	  	if(isset($product_list[$pid]) && $product_list[$pid]!=""){
	  		$box_mod=D("Box");
	  		$arr=explode(",",$product_list[$pid]);
	  		$pro=array();
	  		foreach($arr as $key=>$val){
	  			$box_info=$box_mod->getBoxInfo($val,"name,boxid,category");
	  			if($box_info['category']!=C("BOX_TYPE_EXCHANGE_PRODUCT")){
	  				$info=array();
	  				$info['boxname']=$box_info['name'];
	  				$info['boxurl']=$box_mod->getBoxUrl($box_info["boxid"],$box_info["category"]);
	  				$pro[]=$info;
	  			}
	  		}
	  		if(count($pro)==0){
	  			$pro="";
	  		}
	  	}else{
	  		$pro="";
	  	}
	  	return $pro;
	  }
	  
	
	/**
	 * 产品排重
	 * 
	 * @param unknown_type $saveid        	
	 * @param unknown_type $deleteid        	
	 */
	public function filterProduct($save_pid, $deleteid) {
		if($save_pid == $deleteid){
			return false;
		}
		$follow = M ( "Follow" );
		$user_atme = M ( "UserAtme" );
		$user_behaviour_relation_mod = M ( "UserBehaviourRelation" );
		
		//分享
		$save_info = $this->getByPid($save_pid);
		$delete_info = $this ->getByPid($deleteid);
		if($save_info['pname'] != $delete_info['pname']){
			$string = "@".$delete_info['pname']." ";
			$replace = "@".$save_info['pname']." ";
			$this->query("UPDATE user_share_data set content=REPLACE(content,'$string','$replace') WHERE content like '%$string%'");
		}
		$follow_list = $follow->field ( "userid" )->where ( "whoid=" . $deleteid . " AND type=2" )->select ();
		if ($follow_list) {
			foreach ( $follow_list as $key => $val ) {
				// 查看关注重复ID的用户有没有关注正确的ID
				if (! $follow->where ( "userid=" . $val ['userid'] . " AND type=2 AND whoid=" . $save_pid )->find ()) {
					// 如果没有，将关注重复的whoid改为正确的ID
					$follow->where ( "userid=" . $val ['userid'] . " AND type=2 AND whoid=" . $deleteid )->setField ( "whoid", $save_pid );
				}
			}
		}
		$follow->where ( "whoid=" . $deleteid . " AND type=2" )->delete ();
		
		// user_atme相关纪录变更
		$user_atme_list = $user_atme->where ( "sourceid=" . $deleteid . " AND sourcetype=2" )->select ();
		foreach ( $user_atme_list as $key => $val ) {
			if (! $user_atme->where ( "relationid=" . $val ['relationid'] . " AND relationtype=" . $val ['relationtype'] . " AND sourcetype=2 AND sourceid=" . $save_pid )->find ()) {
				$user_atme->where ( "relationid=" . $val ['relationid'] . " AND relationtype=" . $val ['relationtype'] . " AND sourcetype=2 AND sourceid=" . $deleteid )->setField ( "sourceid", $save_pid );
			}
		}
		$user_atme->where ( "sourceid=" . $deleteid . " AND sourcetype=2" )->delete ();
		
		// 动态相关纪录变更
		$behaviour_list = $user_behaviour_relation_mod->where ( "(userid=" . $deleteid . " AND usertype=2) OR (whoid=" . $deleteid . " AND usertype=1 AND type='follow_pid')" )->select ();
		foreach ( $behaviour_list as $key => $val ) {
			if (! $user_behaviour_relation_mod->where ( "whoid=" . $save_pid . " AND type='" . $val ['type'] . "' AND usertype=" . $val ['usertype'] . " AND userid=" . $val ['userid'] )->find ()) {
				$user_behaviour_relation_mod->where ( "whoid=" . $val ['whoid'] . " AND type='" . $val ['type'] . "' AND usertype=" . $val ['usertype'] . " AND userid=" . $val ['userid'] )->setField ( "whoid", $save_pid );
			}
		}
		$user_behaviour_relation_mod->where ( "(userid=" . $deleteid . " AND usertype=2) OR (whoid=" . $deleteid . " AND usertype=1 AND type='follow_pid')" )->delete ();
		$sql = "update user_share set resourceid=" . $save_pid . " WHERE resourceid=" . $deleteid . " AND resourcetype=1";
		$this->query ( $sql );
		$sql = "update inventory_item set relation_id=" . $save_pid . " WHERE relation_id=" . $deleteid;
		$this->query ( $sql );
		$this->where ( "pid=" . $deleteid )->delete ();
	}
	
	/**
	 * 获取产品的购买渠道
	 * @param $pid 产品ID（products下pid）
	 */
	public function getProductBuyUrl($pid){
		if(!$pid){
			return false;
		}
		$pro_buy_mod=M("ProductsBuyChannel");
		$list=$pro_buy_mod->where("pid=$pid")->field("channelname,url")->order("sortnum DESC")->select();
		if(!$list){
			return false;
		}
		foreach($list as $key=>$val){
			$img_arr=explode(".",$val['url']);
			$img=".".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."productimg".DIRECTORY_SEPARATOR."buychannel".DIRECTORY_SEPARATOR.$img_arr[1].".jpg";
			$a_img="./data/productimg/buychannel/".$img_arr[1].".jpg";
			if(file_exists($img)){
				$list[$key]['img']=$a_img;
			}else{
				$list[$key]['img']="./data/productimg/buychannel/default.jpg";
			}
		}
		return $list;
	}
	
	/**
	 * 获取产品功效列表
	 * @param $pid 产品ID[products表中的pid]
	 */
	public function getProductEffectList($pid){
		if(!$pid){
			return false;
		}
		$effect=M("ProductEffectRelation")->where("pid=$pid")->select();
		if(!$effect){
			return false;
		}
		$category_mod=M("category");
		$list=array();
		foreach($effect as $key=>$val){
			$caregory_info=$category_mod->where("cid=".$val['effectcid'])->find();
			if($caregory_info){
				$list[]['cname']=$caregory_info['cname'];
			}
		}
		return $list;
	}
	
	/**
	 * 通过产品ID获取当前产品的信息
	 * @param $pid 产品ID
	 * @author penglele
	 */
	public function getPorudctExchangeInfoByPid($pid){
		$info=$this->getProductInfoByPid($pid,"pid,pimg,pname,brandcid");
		$brand_info=D("ProductsBrand")->getBrandInfo($info['brandcid'],"id,name");
		$effectlist=$this->getProductEffectList($pid);
		$info['effectlist']=$effectlist;
		$info['producturl']=getProductUrl($pid);
		$info['bname']=$brand_info['name'];
		$info['brandurl']=$brand_info['brandurl'];
		if(!$info){
			return false;
		}
		$item_info=M("InventoryItem")->field("id,norms,price")->where("relation_id=".$pid)->find();
		if($item_info){
			$num_info=D("BoxProducts")->getExchangeScoreById($item_info['id']);
			$item_info['score']=(int)($item_info['price']*10*$num_info['num']);
			$item_info['num']=$num_info['num'];
			$info=array_merge($info,$item_info);			
		}
		return $info;
	}
	
	/**
	 * 通过分享ID获取分享中@的产品列表
	 * @param $shareid 分享ID
	 * @author penglele
	 */
	public function getProductListOfShare($shareid){
		//分享关联的产品列表
		$at_list=D("UserAtme")->getPidListAtShare($shareid);
		if(empty($at_list)){
			return "";
		}
		$pro_list=array();
		foreach($at_list as $key=>$val){
			//产品信息
			$pro_info=$this->getPorudctExchangeInfoByPid($val);
			$pro_list[]=$pro_info;
		}
		return $pro_list;
	}
	
	/**
	 * 获取分享数最多的产品
	 * @author penglele
	 */
	public function getTopListByShare($userid){
		$list=$this->getProductsList('',3,"evaluatenum DESC",$userid);
		return $list;
	}
	
	
	/**
	 * 获取同类产品【通过功效或二级分类随机获取】
	 * @param int $pid
	 * @param string $limit 分页
	 * @param int $type 方式2---代表功效，1-----二级分类
	 * @access public 
	 * @uses 产品页
	 * @author litingting
	 */
	public function getSameProducts($pid,$limit=4,$type=1){
		if($type==1){
			$where['cid'] = $this->where("pid=".$pid)->getField("secondcid");
			$where['status'] = 1;
			return $this->where($where)->field("pid,pname,pimg,evaluate_num")->limit($limit)->order("rand()")->select();
		}else{
			$product_effect_mod = M("productEffectRelation");
			$effectlist = $product_effect_mod ->where("pid=".$pid)->select();
			foreach($effectlist as $key =>$val){
				$effectlist[$key] = $val['effectcid'];
			}
			$where['effectcid'] = array("IN",$effectlist);
			$list = $product_effect_mod ->where($where)->field("pid")->group("pid")->order("count(pid) DESC")->limit($limit)->select();
			$return = array();
			foreach($list as $key =>$val){
				$pinfo = $this ->field("pid,pname,pimg,evalute_num")->where("pid=".$val['pid']." AND status=1")->find();
				if($pinfo){
					$return[$key] = $pinfo;
				}
			}
			return $return;
			
		}
	}
	

	/**
	 * 获取试用产品排行榜
	 * @uses 用于首页试用排行榜
	 * @param string $limit
	 * @author litingting
	 */
	public function getTopTryProductList($limit=20){
		$box_products = M("BoxProducts");
		$inventory_item = M("InventoryItem");
		//		$where['boxid'] = array("in",'xxx,xxx');
		$list = $box_products->cache(true)->field("boxid,pid,sum(ptotal-saletotal) as surplus,sum(saletotal) as sales")->order("sales DESC")->group("pid")->limit($limit)->where("ishidden=0")->select();
		$product_mod = D("Products");
		foreach($list as $key =>$val){
			$relation_p = $inventory_item ->where("id=".$val['pid'])->getField("relation_id");
			$pinfo = $product_mod ->getProductInfo($relation_p);
			$pinfo['sales'] = $val['sales'];
			$pinfo['surplus'] = $val['surplus'];
			$list[$key] = $pinfo;
		}
		return $list;
	}
	
	
	/**
	 * 获取简单的单品信息
	 * @param unknown_type $pid
	 */
	public function getSimpleInfo($pid){
		$pinfo = $this ->field("pid,pimg,pname,commend_tag,sale_tag,evaluatenum,goodsprice")->where("status>0 and pid=".$pid)->find();
		if($pinfo['sale_tag']){
		    $pinfo['sale_tags'] = explode(",",$pinfo['sale_tag']);
		}
		if($pinfo['commend_tag']){
			switch($pinfo['commend_tag']){
				case 1:
					$pinfo['commend_tags_class']= "t_news r_t";
					break;
				case 2:
					$pinfo['commend_tags_class']= "t_recommend r_t";
					break;
				case 3:
					$pinfo['commend_tags_class']= "t_hot r_t";
					break;
			}
		}
		//产品的试用装规格及价格等信息
		if($pinfo){
			$info=D("InventoryItem")->getInvetoryInfoByCondition(array("relation_id"=>$pid,"category"=>"试用装"),"price,norms,id");
			if($info){
				$info['score']=(int)($info['price']*10);
				$pinfo= array_merge($pinfo,$info);
			}
			$pinfo['producturl']=getProductUrl($pid);
			$pinfo['name'] = str_replace("'","\\'",$pinfo['pname']);
			$num=D("BoxProducts")->getInventoryNum($info['id'],C("BOXID_PAYPOSTAGE"));
			$pinfo['now_num']=$num;
		}
		return $pinfo ? $pinfo:false;
	}
	
	/**
	 * 通过库存ID获取
	 * @param int $itemid
	 * @return mixed|null
	 * @author litingting
	 */
	public function getSimpleInfoByItemid($itemid){
		//产品的试用装规格及价格等信息
		$info=D("InventoryItem")->getInvetoryInfoByCondition(array("id"=>$itemid),"price,norms,id,relation_id,validdate");
		if($info){
			$info['score']=(int)($info['price']*10);
		}
		//产品信息
		$pinfo = $this ->field("pid,pimg,pname,commend_tag,sale_tag,evaluatenum,goodsprice,goodssize,for_skin,for_people,for_hair")->where("status>0 and pid=".$info['relation_id'])->find();
		$pinfo['effect']=$this->getProductsEffectByPid($info['relation_id']);
		$pinfo=array_merge($pinfo,$info);
		$pinfo['producturl']=getProductUrl($info['relation_id']);
		$pinfo['name'] = str_replace("'","\\'",$pinfo['pname']);
		$num=D("BoxProducts")->getInventoryNum($itemid,C("BOXID_PAYPOSTAGE"));
		$pinfo['now_num']=$num;
		//产品的标签
		if($pinfo['sale_tag']){
			$pinfo['sale_tags'] = explode(",",$pinfo['sale_tag']);
		}
		//推荐标签（定位于产品图片右上角）
		if($pinfo['commend_tag']){
			switch($pinfo['commend_tag']){
				case 1: //最新
					$pinfo['commend_tags_class']= "t_news r_t";
					break;
				case 2: //推荐
					$pinfo['commend_tags_class']= "t_recommend r_t";
					break;
				case 3: //最热
					$pinfo['commend_tags_class']= "t_hot r_t";
					break;
				case 4: //折扣
					$pinfo['commend_tags_class']= "t_discount r_t";
					break;
				case 5 : //正装
					$pinfo ['commend_tags_class'] = "t_dress r_t";
					break;
			}
		}
		return $pinfo ? $pinfo:false;		
	}
	
	

	/**
	 *  获取用户已购买的萝莉盒中的产品列表
	 *  @author penglele
	 */
	public function getUserOrderProductsList($userid,$limit=""){
		if(!$userid){
			return false;
		}
		if($limit){
			$limit="LIMIT $limit";
		}
		$sql="SELECT DISTINCT(s.productid) FROM user_order_send_productdetail s,user_order o WHERE o.userid={$userid} AND o.state=1 AND o.ifavalid=1 AND s.orderid=o.ordernmb AND s.userid=o.userid ORDER BY o.ordernmb {$limit}";
		$p_list=$this->query($sql);
		$list=array();
		if($p_list){
			foreach($p_list as $key=>$val){
				$info=$this->getSimpleInfoByItemid($val['productid']);
				$list[]=$info;
			}
		}
		return $list;
	
	}
	
	/**
	 * 获取用户已购买的盒子中的产品总数
	 * @param int $userid
	 * @access public
	 * @author litingting
	 */
	public function getUserOrderProductsCount($userid){
		if(!$userid){
			return false;
		}
		$sql = "SELECT count(DISTINCT(s.productid)) as T FROM user_order_send_productdetail s,user_order o WHERE o.userid={$userid} AND o.state=1 AND o.ifavalid=1 AND s.orderid=o.ordernmb AND s.userid=o.userid";
		$res = $this->query($sql);
		return $res[0]['T'];
	
	}
	
	
	/**
	 * 获取用户待分享产品数
	 * @author penglele
	 */
	public function getOrderProductNumOfNotShare($userid){
		if(!$userid){
			return 0;
		}
		$sql="SELECT COUNT(distinct(relation_id)) as num FROM user_order, user_order_send_productdetail p, inventory_item i WHERE user_order.userid =$userid AND user_order.userid = p.userid AND ifavalid =1 AND state =1 AND user_order.ordernmb=p.orderid AND i.id = p.productid AND NOT EXISTS ( SELECT * FROM user_share WHERE userid =$userid AND resourceid = i.relation_id AND resourcetype =1 AND status>0)";
		$num=$this->query($sql);
		return $num[0]['num'];
	}
	
	
	/**
	 * 获取待分享的产品列表
	 * @param int $userid
	 * @author litingting
	 */
	public function getOrderProductlistOfNotShare($userid,$limit='8'){
		$sql = "SELECT distinct(relation_id) as pid FROM user_order,user_order_send_productdetail p, inventory_item i WHERE user_order.userid ={$userid} AND user_order.userid = p.userid AND ifavalid =1 AND state =1 AND p.orderid = user_order.ordernmb AND i.id = p.productid AND NOT  EXISTS (
		SELECT * FROM user_share
		WHERE userid ={$userid}
		AND resourceid = i.relation_id
		AND resourcetype =1 AND status>0
		) LIMIT ".$limit;
		$list = $this->query($sql);
		foreach($list as $key =>$val){
		$pinfo = $this->getSimpleInfo($val['pid']);
				$list[$key] = $pinfo;
		}
		return $list;
	}
	
	
	/**
	 * 获取已经分享的购买产品列表
	 * @param int $userid
	 * @return Ambigous <unknown, mixed, boolean>
	 * @author litingting
	 */
	public function getOrderProductlistOfShare($userid,$limit='8'){
		$sql = "SELECT distinct(relation_id) as pid FROM user_order,user_order_send_productdetail p, inventory_item i,user_share s WHERE user_order.userid ={$userid} AND user_order.userid = p.userid AND ifavalid =1 AND state =1 AND p.orderid = user_order.ordernmb AND i.id = p.productid AND s.userid=p.userid AND  s.resourceid=i.relation_id  AND  resourcetype =1 AND s.status>0  limit ".$limit;
	    $list = $this ->query($sql);
		foreach($list as $key =>$val){
	     	$pinfo = $this->getSimpleInfo($val['pid']);
	    	$list[$key] = $pinfo;
		}
		return $list;
	}
	
	/**
	 * 获取巳经分享的购买产品总数
	 * @param int $userid
	 * @author litingting
	 */
	public function getOrderProductCountOfShare($userid){
		$sql = "SELECT count(distinct(relation_id)) as p FROM user_order,user_order_send_productdetail p, inventory_item i,user_share s WHERE user_order.userid ={$userid} AND user_order.userid = p.userid AND ifavalid =1 AND state =1 AND p.orderid = user_order.ordernmb AND i.id = p.productid AND s.userid=p.userid AND  s.resourceid=i.relation_id  AND resourcetype =1 AND s.status>0";
		$res = $this ->query($sql);
		return $res[0]['p'];
	}
	
	
	/**
	 * 判断产品是否正在售卖
	 * 1表示自选或主题盒，2表示付邮试用，3表示积分兑换
	 * @author penglele
	 */
	public function getProductsOnSelling($pid){
		$boxlist=array(
				1=>array(),
				2=>array(),
				3=>array()
				);
		if(!$pid){
			return $boxlist;
		}
		$item_mod=D("InventoryItem");
		$box_product_mod=D("BoxProducts");
		$list_one=D("Box")->getProductsListByType();
		$inventoryid=$item_mod->getIdByPid($pid); //获取PID对应的第一条库存单品ID by zhenghong 2013-09-04
		$list_two=$box_product_mod->getProductsListByBoxid(C("BOXID_PAYPOSTAGE"));
		$list_three=$box_product_mod->getProductsListByBoxid(C("BOXID_CREDITEXCHANGE"));
		$box_mod=D("Box");
		$first_arr=array();
		if($inventoryid){
			foreach($inventoryid as $tkey=>$tval){
				//是否存在于自选盒或主题盒内
				$id=$tval['id'];
				if(array_key_exists($id, $list_one)){
					$first_arr=array_merge($list_one[$id],$first_arr);
				}	
				//是否存在于付邮试用
				if(array_key_exists($id, $list_two)){
					$boxlist[2][]=$list_two[$id];
				}
				//是否存在于积分兑换
				if(array_key_exists($id, $list_three)){
					$boxlist[3][]=$list_three[$id];
				}
			}
		}
		//通道1，获取盒子信息 
		if($first_arr){
			$first_arr=array_unique($first_arr);
			$first_list=array();
			foreach($first_arr as $vals){
				$boxinfo=$box_mod->getBoxInfo($vals,"name");
				$boxinfo['url']=getBoxUrl($vals);
				 $first_list[]=$boxinfo;
			}
			$boxlist[1]=$first_list;
		}
		foreach($boxlist as $ekey=>$eval){
			if($eval){
				$info['num']=count($eval);;
				$info['list']=$eval;
				$boxlist[$ekey]=$info;
			}
		}
		return $boxlist;
	}
	
	/**
	 * 获取产品的购买渠道
	 * @param $pid 产品ID
	 * @author penglele
	 */
	public function getProductsBuyUrl($pid){
		if(!$pid){
			return '';
		}
		$img_arr=array("jd","jumei","lefeng","sephora","tmall","website");
		$img_arr=array(
				"品牌官方商城"=>"website",
				"丝芙兰官网"=>"sephora",
				"天猫官方商城"=>"tmall",
				"淘宝旗舰店"=>"taobao",
				"品牌专柜"=>""
				);
		$list=M("ProductsBuyChannel")->where("pid=$pid AND price>0")->order("sortnum DESC")->select();
		if($list){
			$brand_mod=D("ProductsBrand");
			foreach($list as $key=>$val){
				if($val['url']){
					if($val['channelname']=="品牌专柜"){
						$brand_info=$brand_mod->getBrandInfoByPid($pid,"logo_url");
						$img=$brand_info['logo_url'];
					}else{
						$img="/public/images/channels/".$img_arr[$val['channelname']].".jpg";
					}
					$list[$key]['img']=$img;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取产品的试用观点
	 * @author penglele
	 */
	public function getProductTryPointByPid($pid){
		$list=array();
		if(!$pid){
			return $list;
		}
		$proinfo=$this->getByPid($pid);
		if(!$proinfo || !$proinfo['try_viewpoint']){
			return $list;
		}
		$arr=explode("\r\n",$proinfo['try_viewpoint']);
		if($arr){
			foreach($arr as $val){
				if($val){
					$list[]=$val;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 增加产品购买渠道的点击数
	 * @author penglele
	 */
	public function addBuychannelHit($id){
		if(!$id){
			return false;
		}
		M("ProductsBuyChannel")->where("id=".$id)->setInc("realhit",1);
	}
	
	/**
	 * 通过产品ID获取产品的功效
	 * @return $str[1]表示不完整的功效内容，$str[2]产品完整的功效
	 * @author penglele
	 */
	public function getProductsEffectByPid($pid){
		$str=array(1=>'',2=>'');
		if(!$pid){
			return $str;
		}
		$relation_list=M("ProductEffectRelation")->distinct("effectcid")->where("pid=".$pid)->select();
		if($relation_list){
			$category_mod=M("Category");
			$arr1=array();
			$arr2=array();
			foreach($relation_list as $key=>$val){
				$category_info=$category_mod->where("cid=".$val['effectcid'])->getField("cname");
				if($category_info){
					if($key<2){
						$arr1[]=$category_info;
					}
					$arr2[]=$category_info;
				}
			}
			$str[1]=implode("，",$arr1);
			$str[2]=implode("，",$arr2);
		}
		return $str;
	}
	
	/**
	 * 获取产品适合肤质属性列表
	 * @author zhenghong
	 */
	public function getForSkinDefine(){
		$array_skin=array('所有',  '中性',  '干性',  '油性',  '混合性',  '敏感性');
		return $array_skin;
	}
	
	/**
	 * 获取产品适合人群属性列表
	 * @author zhenghong
	 */
	public function getForPeopleDefine(){
		$array_people=array('所有',  '男士',  '女士',  '婴幼儿',  '孕妇',  '化妆人群');
		return $array_people;
	}
	

	/**
	 * 获取产品适合发质属性列表
	 * @author zhenghong
	 */
	public function getForHairDefine(){
		$array_hair=array('所有',  '中性',  '干性',  '油性',  '敏感',  '染发',  '烫发');
		return $array_hair;
	}
}