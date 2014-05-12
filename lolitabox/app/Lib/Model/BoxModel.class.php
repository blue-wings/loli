<?php
/**
 * 盒子模型模型
 */
class BoxModel extends Model {

	/**
	 * 获取盒子信息【传参 || 不传参】
	 * @param int $boxid 盒子ID
	 * @param array $field 查询条件
	 * @return array  $boxinfo 返回盒子信息
	 */
	public function getBoxInfo($boxid,$field="*"){
		if(empty($boxid)) return false;
		$boxinfo=$this->field($field)->where("boxid=$boxid")->find();
		if(!$boxinfo) return false;
		if(isset($boxinfo['pic_big']) && empty($boxinfo['pic_big'])){
			$boxinfo['pic_big']="public/images/box_default.jpg";
		}
// 		if($boxinfo['box_intro']){
// 			$boxinfo['box_intro']=strip_tags($boxinfo['box_intro'],"<br><a><br />");
// 		}
		if($boxinfo['box_price']){
			if($boxinfo['box_price']<=80){
				$boxinfo['btn']="buy_btn buy_btn_02";
			}elseif($boxinfo['box_price']>80 && $boxinfo['box_price']<=160){
				$boxinfo['btn']="buy_btn";
			}elseif($boxinfo['box_price']>160 && $boxinfo['box_price']<=260){
				$boxinfo['btn']="buy_btn buy_btn_03";
			}elseif($boxinfo['box_price']>260){
				$boxinfo['btn']="buy_btn buy_btn_01";
			}
		}
		$boxinfo['eq']=0;
		if(empty($boxinfo['member_price']) || $boxinfo['member_price']==$boxinfo['box_price']){
			$boxinfo['eq']=1;
		}
		return $boxinfo;
	}
	
	/**
	 * 获取随心所欲盒详情
	 * @param $boxid 盒子ID
	 * @param $field 需要查询的字段
	 * @return array $box_detail_info 随心所欲盒产品列表
	 */
	public function getZhutiBoxList($boxid,$field="*"){
		if(empty($boxid)) return false;
		$box_detail_mod=M("BoxDetail");
		$box_detail_info=$box_detail_mod->field($field)->getByBoxid($boxid);
		if(!$box_detail_info) return false;
		if(isset($box_detail_info['product_list'])){
			$pid_arr=explode(",",$box_detail_info['product_list']);
			$pro_mod=D("Products");
			foreach($pid_arr as $val){
				$info=$pro_mod->getSimpleInfoByItemid($val);
				$pro_list[]=$info;
			}
// 			$map['pid']=array('in',$box_detail_info['product_list']);
// 			$pro_list=D("Products")->getProductsList($map);
// 			foreach($pro_list as $keys=>$val){
// 				$pro_list[$keys]['producturl']=getProductUrl($val['pid']);
// 			}
			$box_detail_info['product_list']=$pro_list;
		}
		return $box_detail_info;
	}
	
	/**
	 * 获取增值产品列表
	 * @param $projectid 增值ID
	 * @return array $project_product_list 增值产品列表
	 */
	public function getProjectProductList($projectid){
		if(empty($projectid)) return false;
		$project_product_list=M("BoxProjectList")->where("projectid=$projectid")->select();
		if(!$project_product_list) return false;
		return $project_product_list;
	}
	
	/**
	 * 获取正在售卖的且未售完的盒子列表，只包括【主题盒、自选盒、积分兑换】
	 */
 	public function getBoxListOnSelling(){
		$ndate=date("Y-m-d");
		//正在售卖的盒子列表
		$list=$this->field("boxid,quantity,category")->where("starttime<='".$ndate."' AND endtime>='".$ndate."' AND state=1 AND (category=".C("BOX_TYPE_SUIXUAN")." OR category=".C("BOX_TYPE_ZIXUAN")." OR category=".C("BOX_TYPE_EXCHANGE_PRODUCT").")")->order("boxid DESC")->select();
		if($list){
			$order_mod=D("UserOrder");
			foreach($list as $key=>$value){
				$where=array();
				$where['boxid']=$value['boxid'];
				$order_num=$order_mod->getOrderNum($where);
				if($value['quantity']>$order_num){
					unset($value['quantity']);
					$box_list[]=$value;
				}	
			}	
		}
		return $box_list;
	} 
	
	/**
	 * 通过boxid获取盒子内的产品pid列表【pid为products下的pid】
	 * @param $boxid 盒子ID
	 * @param $category 盒子类型
	 * @author penglele
	 */
	public function getBoxPidList($boxid,$category){
		if(!$boxid || !$category){
			return false;
		}
		$products_list=array();
		$pro_item_mod=M("InventoryItem");
		//当盒子类型为主题盒时
		if($category==C("BOX_TYPE_SUIXUAN")){
			$box_detail_mod=M("BoxDetail");
			$box_detail_info=$box_detail_mod->field("product_list")->getByBoxid($boxid);
			$pid_list=explode(",",$box_detail_info['product_list']);
			foreach($pid_list as $vals){
				$info=$pro_item_mod->where("id=".$vals)->field("relation_id")->find();
				$products_list[]=$info['relation_id'];
			}
		}
		//当盒子类型为自选盒时
		if($category==C("BOX_TYPE_ZIXUAN") || $category==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$box_product_mod=M("BoxProducts");
			$box_product_list=$box_product_mod->where("boxid=$boxid AND ishidden=0")->field("pid")->select();
			foreach($box_product_list as $key=>$val){
				$itenm_info=$pro_item_mod->where("id=".$val['pid'])->field("relation_id")->find();
				if($itenm_info){
					$products_list[]=$itenm_info['relation_id'];
				}
			}
		}
		return $products_list;
	}
	
	/**
	 * 通过盒子ID及盒子类型，确认盒子的链接地址/目前只有自选和主题盒两种
	 * @param $boxid 盒子ID
	 * @param $category 盒子类型
	 * @author penglele
	 */
	public function getBoxUrl($boxid,$category){
		if(empty($boxid) || empty($category))
			return false;
		$boxurl=U("buy/show",array("boxid"=>$boxid));
		return $boxurl;
	}
	
	/**
	 * 通过盒子类型获取正在售卖的盒子列表【包括已售完】
	 * @param $type 盒子类型
	 * @param $order 排序
	 * @author penglele
	 */
	public function getBoxListOnSellingByType($type,$order="",$limit="",$where=""){
		$ndate=date("Y-m-d");
		if($type){
			$where["category"]=$type;
		}else{ 
			$not_type=$this->returnBoxType();
			$where["category"]=array("exp","not in (".$not_type.")");
		}
		$order= $order=="" ? "boxid DESC":$order;
		$where["starttime"]=array("elt",$ndate);
		$where["endtime"]=array("egt",$ndate);
		$where["state"]=1;
		$boxlist=$this->where($where)->order($order)->limit($limit)->select();
		if($boxlist){
			foreach($boxlist as $key=>$val){
				$order_num=D("UserOrder")->getOrderNum(array("boxid"=>$val["boxid"]));
				$boxlist[$key]["now_quantity"]=$val["quantity"]-$order_num;
			}
		}
		return $boxlist;
	}
	
	/**
	 * 获取盒子列表【可以获取某一类型的盒子列表】
	 * @param $type 盒子类型
	 * @param $order 排序
	 * @param $limit 限制条数
	 * @param $range 查询的时间范围【$range=1全部，$range=2有效期内，$range=3往期】
	 * @param $where 查询条件
	 * @author penglele
	 */
	public function getBoxList($type="",$order="",$limit="",$range=2,$where=array()){
		$order= empty($order) ? "boxid DESC" : $order ;
		$ndate=date("Y-m-d");
		if($type){
			$where["category"]=$type;
		}else{
			$not_type=$this->returnBoxType();
			$where["category"]=array("exp","not in (".$not_type.")");
		}
		if($range==2){
			//查询正在售卖的萝莉盒
			$where["starttime"]=array("elt",$ndate);
			$where["endtime"]=array("egt",$ndate);
		}elseif($range==3){
			//查询往期的萝莉盒
			$where["endtime"]=array("lt",$ndate);
		}
		$where["state"]=1;
		$where['if_hidden']=0;
		$boxlist=$this->where($where)->order($order)->limit($limit)->select();
		if($boxlist){
			foreach($boxlist as $key=>$val){
				$boxlist[$key]['box_intro']=strip_tags($val['box_intro'],"<br><a><br />");
				$boxcost=strip_tags($val['boxcost'],"<br><a><br />");
				$boxlist[$key]['boxcost']= $boxcost =="" ? $boxlist[$key]['box_intro'] : $boxcost;
				$order_num=D("UserOrder")->getOrderNum(array("boxid"=>$val["boxid"]));
				$boxlist[$key]["now_quantity"]=$val["quantity"]-$order_num;

				if($val['box_price']<=80){
					$boxlist[$key]['btn']="buy_btn buy_btn_02";
				}elseif($val['box_price']>80 && $val['box_price']<=160){
					$boxlist[$key]['btn']="buy_btn";
				}elseif($val['box_price']>160 && $val['box_price']<=260){
					$boxlist[$key]['btn']="buy_btn buy_btn_03";
				}elseif($val['box_price']>260){
					$boxlist[$key]['btn']="buy_btn buy_btn_01";
				}
				
				//盒子的标签
				$boxlist[$key]['boxtag']=$this->getBoxTag($val['boxid']);

				//判断盒子是否已下架$boxlist[$key]['range']=3表示此盒子已下架
				if($range==3){
					$boxlist[$key]['range']=$range;
					$boxlist[$key]['boxurl']= $val['special_url']=="" ? U("buy/show",array("boxid"=>$val['boxid'])) : $val['special_url'];
				}elseif($range==1){
					if($val['endtime']<$ndate){
						$boxlist[$key]['range']=3;
						$boxlist[$key]['boxurl']= $val['special_url']=="" ? U("buy/show",array("boxid"=>$val['boxid'])) : $val['special_url'];
					}
				}
				if($val['member_price']==$val['box_price'] || empty($val['member_price'])){
					$boxlist[$key]['eq']=1;
				}else{
					$boxlist[$key]['eq']=0;
				}
			}
		}
		return $boxlist;
	}
	
	/**
	 * 获取购买盒子--核对信息的 详情
	 * @param $boxid 盒子ID
	 */
	public function getBoxInfoByBuy($boxid,$projectid="",$userid){
		if(empty($boxid))
			return false;
		$boxinfo=$this->getBoxInfo($boxid);

//***(start) 2014-01-02 add by zhenghong 识别盒子订单页是否已经无法出售**/
		//盒子信息不存在或盒子为关闭状态，返回false
		if(!$boxinfo || !$boxinfo['state']){
			return false;
		}
		$ntime=date("Y-m-d");
		$box_state=0;
		//判断当前盒子是否是已下架的盒子或还没开始
		$if_order=1;
		if($boxinfo['starttime']>$ntime){
			//盒子还没开始售卖
			$if_order=0;
		}else if($boxinfo['endtime']<$ntime){
			$if_order=0;//盒子已下架
		}else{
			//当前盒子的订单总量
			$order_num=D("UserOrder")->getOrderNum(array('boxid'=>$boxid));
			if($boxinfo["quantity"]-$order_num<=0){
				$if_order=0;//已售完
			}
		}
		$boxinfo['if_order']=$if_order;
//***(end)add by zhenghong 识别盒子订单页是否已经无法出售**/

		$boxinfo['member_price'] = !$boxinfo['member_price'] ?  $boxinfo['box_price'] : $boxinfo['member_price'] ;
		$discount=$boxinfo['box_price'];
		//用户特权状态
		$memberinfo=D("Member")->getUserMemberInfo($userid);
		$boxinfo['now_price']=$boxinfo['box_price'];
		if($memberinfo['state']==1){
			$discount=$boxinfo['member_price'];
			$boxinfo['now_price']=$boxinfo['member_price'];
		}
		
		if($boxinfo['category']==C("BOX_TYPE_SOLO")){
			//solo盒
			$boxinfo['if_solo']=1;
			$boxinfo['now_price']=$boxinfo['box_price']-20;
		}else{
			$boxinfo['if_solo']=0;
			//当用户选择了加价购就不能使用优惠券
			if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
				$boxinfo['if_select']=1;
			}
		}
		//是否选择了增值方案  【适应所有盒子】update by penglele 2013-10-25 16:09:45
		if(!empty($projectid)){
			$project_info=$this->getProjectInfo($projectid);
			if($project_info!=false){
				$boxinfo['add_price']=(int)$project_info['price'];
				$boxinfo['add_name']=$project_info['projectname'];
				$boxinfo["now_price"]=$discount+$boxinfo['add_price'];
				$boxinfo['projectinfo']=$project_info;
			}
		}
		$boxinfo['projectlist']=$this->getBoxProjectList($boxid);
		return $boxinfo;
	}
	
	/**
	 * 获取加价购信息
	 * @param $projectid 增值方案ID
	 */
	public function getProjectInfo($projectid){
		if(!$projectid)
			return false;
		$project_info=M("BoxProject")->where("status=1")->getById($projectid);
		if(!$project_info)
			return false;
		return $project_info;
	}
	
	
	/**
	 * 获取主题或自选的产品信息
	 * @author penglele
	 */
	public function getDetailsInBox($boxid){
		if(!$boxid) return false;
		$boxinfo=$this->getBoxInfo($boxid);
		$ntime=date("Y-m-d");
		if(!$boxinfo || $boxinfo['starttime']>$ntime || $boxinfo['endtime']<$ntime) return false;
		$order_num=D("UserOrder")->getOrderNum(array('boxid'=>$boxid));
		$boxinfo['now_num']=$boxinfo["quantity"]-$order_num;
		$return['boxinfo']=$boxinfo;
		if($boxinfo['category']==C("BOX_TYPE_SUIXUAN")){
			//主题盒
			$boxdetail=M("BoxDetail")->where("boxid=$boxid")->find();
			if(!$boxdetail) return false;
			$details=array();
			$details['details']=$boxdetail['details'];
			$details['instruction']=$boxdetail['instruction'];
			$return['boxdetails']=$details;
			$pro_arr=explode(",",$boxdetail["product_list"]);
			$product_mod=D("Products");
			if(!$pro_arr) return false;
			foreach($pro_arr as $val){
				$info=$product_mod->getSimpleInfoByItemid($val);
// 				$info['pname']=$pro_info['pname'];
// 				$info['pid']=$pro_info['pid'];
// 				$info['pimg']=$pro_info['pimg'];
// 				$info['trialsize']=$pro_info['trialsize'];
// 				$info['producturl']=getProductUrl($pro_info['pid']);
				$product_list[]=$info;
			}
			$return['productlist']=$product_list;
		}else if($boxinfo['category']==C("BOX_TYPE_ZIXUAN") || $boxinfo['category']==C("BOX_TYPE_EXCHANGE")){
			$box_product_mod=D("BoxProducts");
			$zixuan_prolist=$box_product_mod->getBoxProductByType($boxid);
			$fulist=$zixuan_prolist[0];
			unset($zixuan_prolist[0]);
			$return['fulilist']=$fulist;
			$return['productlist']=$zixuan_prolist;
			$select_name=$box_product_mod->getBoxProductsCname($boxid);
			$return['cnamelist']=$select_name['cname_list'];
			$return['titlelist']=$select_name['title_list'];
			$return['projectlist']=$this->getProjectListByBoxid($boxid);
		}
		return $return;
	}
	
	/**
	 * 获取盒子内的产品等信息
	 * @param $boxid 盒子ID
	 * @author penglele
	 */
	public function getBoxDetails($boxid,$userid,$sname,$addname,$limit=""){
		$boxinfo=$this->getBoxInfo($boxid);
		//盒子信息不存在或盒子为关闭状态，返回false
		if(!$boxinfo || $boxinfo['state']!=1){
			return false;
		}
		$ntime=date("Y-m-d");
		$box_state=0;
		//判断当前盒子是否是已下架的盒子或还没开始
		$if_select=1;
		if($boxinfo['starttime']>$ntime){
			//盒子还没开始售卖
			$if_select=4;
		}else if($boxinfo['endtime']<$ntime){
			$boxinfo['pastbox']=1;
			$if_select=3;//盒子已下架
		}else{
			//当前盒子的订单总量
			$order_num=D("UserOrder")->getOrderNum(array('boxid'=>$boxid));
			$boxinfo['now_num']=$boxinfo["quantity"]-$order_num;//剩余可订购的数量
			if($boxinfo['now_num']<=0){
					$if_select=2;//已售完
			}			
		}
		if($boxinfo['category']==C("BOX_TYPE_SUIXUAN")){
			//主题盒
			$return=$this->getZhutiDetails($boxid);
		}else if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
			//自选盒
			$return=$this->getZixuanDetails($boxid,$userid,$box_state,$sname);
		}
		if($boxinfo['category']==C("BOX_TYPE_ZIXUAN")){
			$boxinfo['zixuan']=1;
		}
		$boxinfo['if_select']=$if_select;
		$return['boxinfo']=$boxinfo;
		$share_mod=D("UserShare");
		$return['sharelist']=$share_mod->getOrderShowByBox($boxid,$limit);
		$return['sharecount']=$share_mod->getOrderShowNumByBox($boxid);
		return $return;
	}
	
	
	
	/**
	 * 获取主题盒内的信息
	 * @param $box 盒子ID
	 * @author penglele
	 */
	public function getZhutiDetails($boxid){
		if(!$boxid){
			return false;
		}
		//主题盒
		$boxdetail=M("BoxDetail")->where("boxid=$boxid")->find();
		if(!$boxdetail || !$boxdetail['product_list']) return false;
		$details=array();
		$details['details']=$boxdetail['details'];
		$details['instruction']=$boxdetail['instruction'];
		$return['boxdetails']=$details;
		$pro_arr=explode(",",$boxdetail["product_list"]);
		$product_mod=D("Products");
		if(!$pro_arr) return false;
		foreach($pro_arr as $val){
			$pro_info=$product_mod->getSimpleInfoByItemid($val);
// 			$info['pname']=$pro_info['pname'];
// 			$info['pid']=$pro_info['pid'];
// 			$info['pimg']=$pro_info['pimg'];
// 			$info['trialsize']=$pro_info['trialsize'];
			$product_list[]=$pro_info;
		}
		$return['productlist']=$product_list;
		return $return;
	}
	
	/**
	 * 获取自选盒内的信息
	 * @param $boxid 盒子ID
	 * @param $userid 用户ID
	 * @param $box_state 盒子的状态【$box_state=2已售完，$box_state=3已下架】
	 * @param $if_discount 是否是折扣限时抢，【$if_discount=1是】
	 * @author penglele
	 */
	public function getZixuanDetails($boxid,$userid,$box_state,$sname,$if_discount=0){
		$box_product_mod=D("BoxProducts");
		$zixuan_prolist=$box_product_mod->getBoxProductByType($boxid);
		if(!$zixuan_prolist){
			return false;
		}
		if($if_discount==0){
			$fulist=$zixuan_prolist[0];
			unset($zixuan_prolist[0]);
			$return['fulilist']=$fulist;
		}
		$select_name=$box_product_mod->getBoxProductsCname($boxid);
		$return['cnamelist']=$select_name['cname_list'];
		$return['titlelist']=$select_name['title_list'];
		$return['projectlist']=$this->getProjectListByBoxid($boxid);
		$total_select_num=0;
		if($box_state>1){
			//当盒子已售完或者已下架时，如果存在session，则删除session值
			if(isset($sname) && isset($_SESSION[$sname])){
				unset($_SESSION[$sname]);
			}
		}else{
			//当不存在session时，创建session
			if($userid){
				if(isset($sname) && !isset($_SESSION[$sname])){
					foreach($return['cnamelist'] as $key=>$value){
						for($m=1;$m<=$key;$m++){
							$_SESSION[$sname][$key][$m]="";
						}
					}
				}				
			}
			//获取已选中的产品列表 start
			$session_list="";
			if(isset($sname) && isset($_SESSION[$sname])){
				$session_info=D("BoxProducts")->getBoxProductSessionList($sname,$boxid);
				if($session_info!=false){
					$session_list=$session_info;
				}else{
					$session_list="";
				}
			}else{
				$session_list="";
			}
			$return['sessionlist']=$session_list;
			//获取已选择的产品列表 end
			if(isset($sname) && isset($_SESSION[$sname])){//判断session是否存在
				$products_select_arr=$this->getSessionProductsList($_SESSION[$sname]);//选中的产品列表
			}else{
				$products_select_arr="";
			}
			$select_num=array();
			$total_num=0;
			foreach($zixuan_prolist as $key_one=>$val_one){
				$total_num=$total_num+$key_one;
				//每类产品下已选择的数量
				if(!isset($select_num[$key_one]) || empty($select_num[$key_one])){
					$select_num[$key_one]=0;
				}
				foreach($val_one as $key_two=>$val_two){
					if($box_state>1){
						//当盒子已售完或已下架
						$zixuan_prolist[$key_one][$key_two]['box_state']=$box_state;
					}else{
						//正在售卖
						if(in_array($val_two['id'], $products_select_arr)){//判断当前的产品是否在已选产品的数组中
							$total_select_num++;
							$select_num[$key_one]++;
							//如果当前的pid存在于$products_select_arr中，说明该产品已选择
							$zixuan_prolist[$key_one][$key_two]['if_select']=1;
						}else{
							$zixuan_prolist[$key_one][$key_two]['if_select']=0;
						}							
					}
					if($if_discount==1){
						$zixuan_prolist[$key_one][$key_two]['discount_score']=(int)(round($val_two['discount']*$val_two['score'])*$val_two['pquantity']);
						$zixuan_prolist[$key_one][$key_two]['total_score']=$val_two['score']*$val_two['pquantity'];
					}
				}
			}
			ksort($select_num);//对各类产品已选数量进行重新排序
			$return['total_num']=$total_num;
			$return['select_num']=$select_num;
			
			//查看该用是否有选择加价购
			$addname=getAddBoxSessName($boxid,$userid);
			if(isset($addname) && isset($_SESSION[$addname]) && !empty($_SESSION[$addname])){
				foreach($return['projectlist'] as $key=>$val){
					if($val['info']['id']==$_SESSION[$addname]){
						$return['projectlist'][$key]['info']['if_select']=1;
					}
				}
			}			
		}
		$return["total_selectnum"]=$total_select_num;
		$return['productlist']=$zixuan_prolist;
		return $return;
	}
	
	
	/**
	 * 获取盒子下的增值方案
	 */
	public function getProjectListByBoxid($boxid){
		$if_project="";
		if(!$boxid) 
			return $if_project;
		$project_list=M("BoxProjectRelation")->where("boxid=$boxid")->select();
		if(!$project_list) 
			return $if_project;
		$projectlist=array();
		foreach($project_list as $key=>$val){
			$project_info=M("BoxProject")->where("id=".$val['projectid']." AND status=1")->find();
			if($project_info){
				$productlist=M("BoxProjectList")->field("pid")->where("projectid=".$val['projectid'])->select();
				if($productlist){
					$list=array();
					foreach($productlist as $value){
						$product_info=D("Products")->getSimpleInfoByItemid($value['pid']);
						if($product_info){
							$list[]=$product_info;
						}
					}
					$return[$key]['info']=$project_info;
					$return[$key]['productlist']=$list;
				}
			}
		}		
		return $return;
	}
	
	/**
	 * 推荐的盒子列表及盒内产品信息
	 */
	public function getRecommendBoxList(){
		$boxlist=$this->getBoxListOnSellingByType("","toptime DESC,boxid DESC",4,array("toptime"=>array("exp",">0")));
		if(!$boxlist){
			return false;
		}
		$count=count($boxlist);
		for($i=0;$i<$count;$i++){
			$boxinfo=array();
			$boxinfo["category"]=$boxlist[$i]['category'];
			$boxinfo["name"]=$boxlist[$i]['name_modifier'].$boxlist[$i]['name'];
			$boxinfo["boxid"]=$boxlist[$i]['boxid'];
			$boxinfo["boxcost"]=$boxlist[$i]['boxcost'];
			$productlist=$this->getRecommendProductInBox($boxlist[$i]['boxid'],$boxlist[$i]['category']);
			if(!$productlist){
				$productlist="";
			}
			$return['boxinfo']=$boxinfo;
			$return['productlist']=$productlist;
			$list[]=$return;
		}
		return $list;
	}
	
	/**
	 * 获取盒子内的部分产品
	 */
	public function getRecommendProductInBox($boxid,$type,$limit="5"){
		if(empty($boxid) || empty($type)) 
			return false;
		if($type==C("BOX_TYPE_ZIXUAN")){
			//自选盒
			$pro_mod=D("BoxProducts");
			$list=$pro_mod->getBoxProductsList(array("boxid"=>$boxid,"iscommend"=>1,"ishidden"=>0),"",5);
			if($list){
				$productlist=array();
				foreach($list as $val){
					$pro_info=$pro_mod->getProductsInfo($val['pid']);
					$productlist[]=$pro_info;
				}
			}else{
				return false;
			}
		}elseif($type==C("BOX_TYPE_SUIXUAN")){
			//主题盒
			$box_detail_mod=M("BoxDetail");
			$box_detail_info=$box_detail_mod->where("boxid=$boxid")->find();
			$prostr=$box_detail_info['product_list'];
			$pro_arr=explode(",",$prostr);
			if($pro_arr){
				$product_mod=D("Products");
				for($i=0;$i<$limit;$i++){
					if($pro_arr[$i]){
						$product_info=$product_mod->getSimpleInfoByItemid($pro_arr[$i]);
						$productlist[]=$product_info;
					}
				}				
			}else{
				return false;
			}
		}else{
			return false;
		}
		return $productlist;
	}
	
	
	
	/**
	 * 将session中的产品pid转换成一维数组
	 * @param $arr 多维数组 [必须]
	 * @author penglele
	 */
	public function getSessionProductsList($arr){
		$products_select_arr=array();
		foreach($arr as $key=>$value_first){
			foreach($value_first as $value_two){
				if($value_two!=""){
					$products_select_arr[]=$value_two;
				}
			}
		}
		return $products_select_arr;
	}
	
	/**
	 * 获取正在售卖且未售完的自选盒、主题盒
	 * @author penglele
	 */
	public function getBoxListByType(){
		$ndate=date("Y-m-d");
		$type=C("BOX_TYPE_SUIXUAN").",".C("BOX_TYPE_ZIXUAN");
		$where['category']=array("exp","in (".$type.")");
		$where['state']=1;
		$where['if_hidden']=0;
		$where["starttime"]=array("elt",$ndate);
		$where["endtime"]=array("egt",$ndate);
		$boxlist=$this->field("boxid,category,quantity")->where($where)->select();
		$list=array();
		if($boxlist){
			$order_mod=D("UserOrder");
			foreach($boxlist as $key=>$val){
				$order_num=$order_mod->getOrderNum(array("boxid"=>$val['boxid']));
				if($val['quantity']>$order_num){
					$list[]=$val;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取自选、主题盒内的产品列表
	 * @author penglele
	 */
	public function getProductsListByType(){
		$prolist=array();
		$boxlist=$this->getBoxListByType();
		if($boxlist){
			$boxdetail_mod=M("BoxDetail");
			$boxproduct_mod=M("BoxProducts");
			$item_mod=M("InventoryItem");
			foreach($boxlist as $key=>$val){
				if($val['category']==C("BOX_TYPE_SUIXUAN")){
					//如果是主题盒
					$pidinfo=$boxdetail_mod->field("product_list")->where("boxid=".$val['boxid'])->find();
					if($pidinfo){
						$pidlist=explode(",",$pidinfo['product_list']);
						foreach($pidlist as $ikey=>$ival){
							if(!in_array($ival,$prolist[$ival])){
								$prolist[$ival][]=$val['boxid'];
							}
						}
					}
				}else{
					//如果是自选盒
					$zixuanlist=$boxproduct_mod->where("boxid=".$val['boxid']." AND ishidden=0")->select();
					if($zixuanlist){
						foreach($zixuanlist as $ikey=>$ival){
							$pid=$ival['pid'];
							if(!in_array($pid,$prolist[$pid])){
								$prolist[$pid][]=$val['boxid'];
							}
						}	
					}
				}
			}
		}
		return $prolist;
	}
	
	
	/**
	 * 获取盒子的当前状态
	 * @return $box_state 
	 * 				  盒子状态 $box_state=3已下架或不存在 $box_state=2已售完 $box_state=false不开放
	 * @author penglele
	 */
	public function getBoxState($boxid){
		if(!$boxid){
			return 3;
		}
		$boxinfo=$this->getBoxInfo($boxid,"boxid,state,endtime,quantity");
		$ndate=date("Y-m-d");
		if($boxinfo['state']==0){
			return false;
		}else{
			$box_state=1;
			if($boxinfo['endtime']<$ndate){
				$box_state=3;
			}else{
				$order_num=D("UserOrder")->getOrderNum(array("boxid"=>$boxid));			
				if($boxinfo['quantity']<=$order_num){
					$box_state=2;
				}
			}
		}
		return $box_state;
	}
	
	/**
	 * 排除积分兑换、付邮试用、免费试用的盒子类型
	 * @author penglele
	 */
	public function returnBoxType(){
		return C("BOX_TYPE_EXCHANGE").",".C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_FREEGET");
	}
	
	/**
	 * 正在售卖的盒子ID
	 * @author penglele
	 */
	public function getBoxIDOnSelling(){
		$ndate=date("Y-m-d");
		$not_type=$this->returnBoxType();
		$where["category"]=array("exp","not in (".$not_type.")");
		$where["starttime"]=array("elt",$ndate);
		$where["endtime"]=array("egt",$ndate);
		$where['state']=1;
		$list=$this->field("boxid")->where($where)->select();
		$boxid_arr=array();
		if($list){
			foreach($list as $val){
				$boxid_arr[]=$val['boxid'];
			}
		}
		$boxidlist=implode(",",$boxid_arr);
		return $boxidlist;
	}
	
	/**
	 * 获取盒子数量【可以获取某一类型的盒子列表】
	 * @param $type 盒子类型
	 * @param $range 查询的时间范围【$range=1全部，$range=2有效期内，$range=3往期】
	 * @param $where 查询条件
	 * @author penglele
	 */
	public function getBoxCount($type="",$range=2,$where=array()){
		$ndate=date("Y-m-d");
		if($type){
			$where["category"]=$type;
		}else{
			$not_type=$this->returnBoxType();
			$where["category"]=array("exp","not in (".$not_type.")");
		}
		if($range==2){
			//查询正在售卖的萝莉盒
			$where["starttime"]=array("elt",$ndate);
			$where["endtime"]=array("egt",$ndate);
		}elseif($range==3){
			//查询往期的萝莉盒
			$where["endtime"]=array("lt",$ndate);
		}
		$where["state"]=1;
		$where['if_hidden']=0;
		$count=$this->where($where)->count();
		return $count;
	}
	
	/**
	 * 晒是萝莉态度的盒子ID范围---暂定
	 */
	public function getBoxidListNotTry(){
		$boxid_arr=$this->returnBoxType();
		$list=$this->field("boxid")->where("category not in ($boxid_arr) AND boxid>=88")->select();
		if($list){
			foreach($list as $val){
				$boxid_list[]=$val['boxid'];
			}
			$arr=implode(",",$boxid_list);
		}else{
			$arr="";
		}
		return $arr;
	}
	
	/**
	 * 限时积分兑换盒子ID
	 * @author penglele
	 */
	public function getDiscountBoxid(){
		return 103;
	}
	
	/**
	 * 按年月统计盒子列表
	 * @author penglele
	 */
	public function getBoxDateList($order="sdate ASC"){
		$sql="SELECT DISTINCT(date_format(`starttime`,'%Y%m')) as sdate FROM `box` WHERE state=1 ORDER BY $order;";
		$arr=$this->query($sql);
		return $arr;
	}
	
	/**
	 * 通过时间获取盒子列表 
	 * @param $sdate 【例：201310】
	 * @author penglele
	 */
	public function getBoxidListByDate($sdate){
		$str="";
		$boxid_arr=array();
		if(!$sdate){
			return $str;
		}
		//通过时间获取盒子ID集合
		$boxlist_sql="SELECT boxid,starttime FROM `box`  WHERE state=1 AND date_format(`starttime`,'%Y%m')=".$sdate;
		$boxlist_query=$this->query($boxlist_sql);
		if(!$boxlist_query){
			return $str;
		}
		//将盒子的ID组合成字符串
		if($boxlist_query){
			foreach($boxlist_query as $ikey=>$ival){
				$boxid_arr[]=$ival['boxid'];
			}
		}
		$str=implode(",",$boxid_arr);
		return $str;
	}
	
	/**
	 * 判断盒子是否存在增值方案
	 * @author penglele
	 */
	public function checkIfProjectByBoxid($boxid,$projectid){
		if(!$boxid || !$projectid){
			return 0;
		}
		$project_info=M("BoxProjectRelation")->where("boxid=$boxid AND projectid=$projectid")->find();
		if(!$project_info){
			return 0;
		}
		return 1;
	}
	
	/**
	 * 获取盒子的增值方案
	 * @author penglele
	 */
	public function getBoxProjectList($boxid){
		$list=array();
		if(!$boxid){
			return "";
		}
		$prolist=M("BoxProjectRelation")->where("boxid=".$boxid)->select();
		if($prolist){
			$project_mod=M("BoxProject");
			foreach($prolist as $key=>$val){
				$info=$project_mod->where("id=".$val['projectid'])->find();
				$list[]=$info;
			}
		}
		if(empty($list)){
			return '';
		}
		return $list;
	}
	
	
	/**
	 * 获取盒子的标签
	 * @author penglele
	 */
	public function getBoxTag($boxid){
		$tag="";
		if($boxid){
			$if_out=$this->checkBoxIfOut($boxid);
			if($if_out==1){
				$tag="t_soldOut r_t";
			}else{
				$taginfo=$this->where("boxid=".$boxid)->getField("name_modifier");
				if($taginfo){
					switch($taginfo){
						case 1:
							//new
							$tag= "t_news r_t";
							break;
						case 2:
							///推荐
							$tag= "t_recommend r_t";
							break;
						case 3:
							//预售
							$tag= "t_sale r_t";
							break;
						case 4:
							//售罄
							$tag= "t_soldOut r_t";
							break;
					}
				}				
			}
		}
		return $tag;
	}
	
	/**
	 * 判断盒子是否已售罄或已下架
	 * @param int $boxid
	 * @return $if_out 【$if_out=1已售完，$if_out=0正在售卖】
	 */
	public function checkBoxIfOut($boxid){
		$if_out=0;
		if($boxid){
			$ndate=date("Y-m-d");
			$boxinfo=$this->field("endtime,quantity")->where("boxid=".$boxid)->find();
			$ordernmb=D("UserOrder")->getOrderNum(array('boxid'=>$boxid));
			if($ndate>$boxinfo['endtime'] || $ordernmb>=$boxinfo['quantity']){
				$if_out=1;
			}
		}
		return $if_out;
	}
	
	/**
	 * 获取指定盒子ID的分享
	 * @author penglele
	 */
	public function getShareListByBoxID($boxid_str){
		$sql="SELECT s.id FROM user_share s WHERE s.resourcetype=4 AND s.boxid IN ($boxid_str) AND s.pick_status=1 AND s.status>0 ORDER BY s.status DESC,s.id DESC";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$share_mod=D("UserShare");
			foreach($query as $key=>$val){
				$shareinfo=$share_mod->getShareInfo($val['id'],100);
				$list[]=$shareinfo;
			}
		}
		return $list;
	}
	
	/**
	 * 通过查询条件获取盒子列表
	 * @author penglele
	 */
	public function getBoxListByCondition($where="",$limit="",$order=""){
		if(!$order){
			$order="boxid DESC";
		}
		$list=$this->where($where)->order($order)->limit($limit)->select();
		if($list){
			$order_mod=D("UserOrder");
			foreach($list as $key=>$val){
				$order_num=$order_mod->getOrderNum(array("boxid"=>$val["boxid"]));
				$list[$key]['member_price']=$val['member_price']>0 ? $val['member_price'] : $val['box_price'] ;
				$list[$key]["now_quantity"]=$val["quantity"]-$order_num;
			}
		}
		return $list;
	}
	
	
}