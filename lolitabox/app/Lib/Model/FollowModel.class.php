<?php
/**
 * 用户关注关系模型
 * @author Administrator
 *
 */
  class FollowModel extends Model{
  	  
  	  /**
  	   * 获取粉丝[关注]用户列表
  	   * @param int $where
  	   * @param string $field
  	   * @param int $userid $
  	   * @param int $field
  	   * @param string $order 排序
  	   */
  	  public function getUserList($where,$limit="",$userid="",$field="userid",$order="addtime DESC"){
			if($field)
				$to_field=$field.",type";
  	  	  $list=$this->field($to_field)->where($where)->limit($limit)->order($order)->select();
  	  	  $users_mod = D("Users");
  	  	  $Article_mod = D("Users");
  	  	  foreach($list as $key =>$val){
 			  if($val['type']==1 || $val['type']==4){
				  	$userinfo = $users_mod ->getUserInfo($val[$field],"userface,nickname,fans_num,if_super,is_solution");
				  	$list[$key]['userface'] =$userinfo['userface_65_65'];
				  	$list[$key]['userface_100_100'] =$userinfo['userface_100_100'];
				  	$list[$key]['nickname'] =$userinfo['nickname'];
				  	$list[$key]['userid'] = $val[$field];
				  	$list[$key]['fans_num'] = $userinfo['fans_num'];
				  	$list[$key]['spaceurl'] =getSpaceUrl($val[$field]);
				  	//如果当前用户为解决方案，则为加V用户
			  		if($userinfo['userid']==2375 || $userinfo['is_solution']==1){
			  			$list[$key]['if_super']=2;
			  		}else{
			  			$list[$key]['if_super']=(int)$userinfo['if_super'];
			  		}
				  	
			  }else if($val['type']==2){
			  		$pro_info=D("Products")->getProductInfo($val[$field],$userid,0);
			  		$list[$key]['userid']=$val[$field];
			  		$list[$key]['userface_100_100']=$pro_info['pimg'];
			  		$list[$key]['nickname']=$pro_info['pname'];
			  		$list[$key]['fans_num']=$pro_info['fans_num'];
			  		$list[$key]['spaceurl']=getProductUrl($pro_info['pid']);
			  		//判断产品是否加V
			  		$list[$key]['if_super']=$pro_info['if_super'];
			  		
			  }elseif($val['type']==3){
					$brand_info=D("ProductsBrand")->getBrandInfo($val[$field],"fans_num,logo_url,name,name_foreign,id,if_super",'',0);
					$list[$key]['userid']=$val[$field];
					$list[$key]['userface_100_100']=$brand_info['logo_url'];
					$list[$key]['nickname']=$brand_info['name'];
					$list[$key]['aliasname'] = $brand_info['name_foreign'];
					$list[$key]['fans_num']=$brand_info['fans_num'];
					$list[$key]['spaceurl']=$brand_info['brandurl'];
					//判断产品是否加V
					$list[$key]['if_super']=$brand_info['if_super'];
					$list[$key]['info_num']=D("Article")->getBrandInfoNum($val[$field]);
					$list[$key]['info_url']=U("home/brand_msg",array('id'=>$val[$field]));
			  } 
   	  	  	  if($userid){
  	  	  	  	$info = $this ->where("userid=".$userid." AND whoid=".$val[$field])->find();
  	  	  	  	if($info){
  	  	  	  		$if_followuid=$this ->where("userid=".$val[$field]." AND whoid=".$userid)->find();
  	  	  	  		$list[$key]['status'] = $if_followuid ? 2:1;
  	  	  	  	}else{
  	  	  	  		$list[$key]['status']=0;
  	  	  	  	}
  	  	  	  } 
  	  	  	  $list[$key]['type']=$val['type'];
  	  	  }
  	  	  return $list;
  	  }
  	  
  	  
  	  /**
  	   * 获取粉丝[关注]用户列表
  	   * @param int $where
  	   * @param string $field
  	   * @param int $userid $
  	   * @param int $field
  	   * @param string $order 排序
  	   */
  	  public function getFansUserList($where,$limit="",$userid="",$field="userid",$order=""){
  	  	$list=$this->field($field)->where($where)->limit($limit)->order($order)->select();
  	  	$users_mod = D("Users");
  	  	foreach($list as $key =>$val){
  	  		$userinfo = $users_mod ->getUserInfo($val[$field],"userface,nickname,fans_num");
  	  		$list[$key]['userface'] =$userinfo['userface_65_65'];
  	  		$list[$key]['userface_100_100'] =$userinfo['userface_100_100'];
  	  		$list[$key]['userface_180_180'] =$userinfo['userface_180_180'];
  	  		$list[$key]['nickname'] =$userinfo['nickname'];
  	  		$list[$key]['userid'] = $val[$field];
  	  		$list[$key]['fans_num'] = $userinfo['fans_num'];
  	  		$list[$key]['spaceurl'] =getSpaceUrl($val['userid']);
  	  		if($userid){
  	  			$info = $this ->where("userid=".$userid." AND whoid=".$val[$field]." AND type=1")->find();
  	  			$list[$key]['status'] = $info ? 1:0;
  	  		}
  	  	}
  	  	return $list;
  	  }
  	  
  	  
  	  /**
  	   * 根据品牌id获取用户id
  	   * @param unknown_type $brandid
  	   */
  	  public function getFansListByBrandid($brandid,$limit="",$userid=""){
  	  	 $where['type'] =3;
  	  	 $where['whoid'] = $brandid;
  	  	 return $this->getFansUserList($where,$limit,$userid);
  	  }
  	  
  	  
  	  /**
  	   * 通过品牌id获取粉丝数
  	   * @param unknown_type $brandid
  	   */
  	  public function getFansNumByBrandid($brandid){
  	     $where['type'] =3;
  	  	 $where['whoid'] = $brandid;
  	  	 return $this->where($where)->count("userid");
  	  }
  	  
  	  /**
  	   * 根据单品id获取用户列表
  	   * @param int $pid
  	   */
  	  public function getFansListByPid($pid,$limit="",$userid=""){
  	  	$where['type'] =2;
  	  	$where['whoid'] = $pid;
  	  	return $this->getFansUserList($where,$limit,$userid);
  	  }
  	  
  	  /**
  	   * 通过单品id获取粉丝数
  	   * @param int $pid
  	   */
  	  public function getFansNumByPid($pid){
  	  	$where['type'] =2;
  	  	$where['whoid'] = $pid;
  	  	return $this->where($where)->count("userid");
  	  }
  	  
  	  
      /**
       * 通过用户ID获取用户粉丝列表
       * @param int $userid
       * @param string $limit
       * @param int $me
       * @return mixed|null
       */
  	  public function getFansListByUserid($userid,$limit="",$me="",$order="addtime DESC"){
  	  	$where['whoid'] = $userid;
  	  	$where['userid'] = array("neq",$userid);
  	  	$where['type']=1;
  	  	return $this->getUserList($where,$limit,$me,'userid',$order);
  	  }
  	  
  	  /**
  	   * 通过用户ID获取用户关注列表
  	   * @param int $userid
  	   * @param string $limit
  	   * @param int $me
  	   * @return mixed|null
  	   */
  	  public function getFollowListByUserid($userid,$type=2,$limit="",$me="",$order="addtime DESC"){
  	  	if($type){
  	  		$where["type"]=$type;
  	  	}
  	  	$where['userid']=$userid;
  	  	$where['_string'] = "!(whoid=".$userid." AND type=1)";
  	  	$list=$this->getUserList($where,$limit,$me,"whoid",$order);
  	  	return $list;
  	  }
  	  
  	  /**
  	   * 获取用户的粉丝总数
  	   * @param int $userid
  	   */
  	  public function getFansNumByUserid($userid){
  	  	$where['type'] = 1;
  	  	$where['whoid'] = $userid;
  	  	$where['userid'] = array("neq",$userid);
  	  	return $this->where($where)->count("userid");
  	  }
  	  
  	  /**
  	   * 获取用户的关注总数
  	   * @param int $userid
  	   */
  	  public function getFollowNumByUserid($userid,$type=2){
  	  	$where['type'] = $type;
  	  	$where['userid'] = $userid;
  	  	$where['_string'] = "!(whoid=".$userid." AND type=1)";
  	  	return $this->where($where)->count("whoid");
  	  }
  	  
      /**
       * 获取所有我关注用户的userid列表
       * @param int $userid
       */
  	  public function getMyFollowUserList($userid){
  	  	$where['type'] = 1;
  	  	$where['userid'] = $userid;
  	  	$list=$this->where($where)->select();
  	  	$userlist = array();
  	  	foreach($list as $key =>$val){
  	  		$userlist [] = $val['whoid'];
  	  	}
  	  	return $userlist;
  	  }
  	  
   
  	  /**
  	   * 获取解决方案的粉丝总数
  	   * @param int $userid
  	   */
  	  public function getFansNumBySolutionid($userid){
  	  	$where['type'] = 1;
  	  	$where['whoid'] = $userid;
  	  	$where['userid'] = array("neq",$userid);
  	  	return $this->where($where)->count("userid");
  	  }
  	  
  	  /**
  	   * 获取解决方案的粉丝总数
  	   * @param int $userid
  	   */
      public function getFansListBySolutionid($id,$limit="",$userid=""){
  	  	 $where['type'] = 1;
  	  	 $where['whoid'] = $id;
  	  	 $where['userid'] = array("neq",$userid);
  	  	 return $this->getFansUserList($where,$limit,$userid);
  	  }
  	  
  	  /**
  	   * 关注用户|产品|品牌|解决方案
  	   * @param unknown_type $userid
  	   * @param unknown_type $whoid
  	   * @param unknown_type $type
  	   * @return boolean
  	   */
  	  public function addFollow($userid,$whoid,$type=1){
  	  	 if(empty($userid) || empty($whoid) || empty($type))
  	  	 	return false;
  	  	 $data['userid'] = $userid;
  	  	 $data['whoid'] = $whoid;
  	  	 $data['type'] = $type==4 ? 1:$type;  
  	  	 if($this ->where($data)->find()){
  	  	 	return 0;       //查找是否己经关注
  	  	 }   
  	  	 $data['addtime'] = time();
  	  	 if($this->add($data)){
  	  	 	switch($type){
  	  	 		case 1:
  	  	 			$data['type'] = "follow_uid";
  	  	 		    D("UserData")->addUserData($whoid,'newfans_num');
  	  	 			break;
  	  	 		case 2:
  	  	 			$data['type'] = "follow_pid";
  	  	 			break;
  	  	 		case 3:
  	  	 			$data['type'] = "follow_brandid";
  	  	 			break;
  	  	 		case 4:
  	  	 			$data['type'] = "follow_uid";
  	  	 			D("UserData")->addUserData($whoid,'newfans_num');
  	  	 			break;
  	  	 	}
  	  	 	M("UserBehaviourRelation") ->add($data);
  	  	 	$this ->updateFansNum($userid,$whoid,$type);
  	  	 	return true;
  	  	 }
  	  	 return false;
  	  	
  	  }
  	  
  	  /**
  	   * 取消关注用户|产品|品牌|解决方案
  	   * @param unknown_type $userid
  	   * @param unknown_type $whoid
  	   * @param unknown_type $type
  	   * @return boolean
  	   */
  	  public function delFollow($userid,$whoid,$type=1){
  	  	if(empty($userid) || empty($whoid) || empty($type))
  	  		return false;
  	  	$data['userid'] = $userid;
  	  	$data['whoid'] = $whoid;
  	  	$data['type'] = $type==4 ? 1:$type;
  	  	if(false!==$this ->where($data)->delete()){
  	  		switch($type){
  	  			case 1:
  	  				$data['type'] = "follow_uid";
  	  				break;
  	  			case 2:
  	  				$data['type'] = "follow_pid";
  	  				break;
  	  			case 3:
  	  				$data['type'] = "follow_brandid";
  	  				break;
  	  			case 4:
  	  				$data['type'] = "follow_uid";
  	  		}
  	  		M("UserBehaviourRelation") ->where($data)->delete();
  	  		$this ->updateFansNum($userid,$whoid,$type);
  	  		return true;       //删除成功
  	  	}else{
  	  		return false;
  	  	}
  	  
  	  }
  	  
      /**
       * 重新统计粉丝数量
       * @param unknown_type $whoid
       * @param unknown_type $type
       */
  	  public function updateFansNum($userid,$whoid,$type=1){
  	  	switch($type){
  	  		case 1:
  	  			$table = "Users";
  	  			$field ="userid";
  	  			break;
  	  		case 2:
  	  			$table = "Products";
  	  			$field ="pid";
  	  			break;
  	  		case 3:
  	  			$table = "ProductsBrand";
  	  			$field ="id";
  	  			break;
  	  		case 4:
  	  			$table = "Users";
  	  			$field ="userid";
  	  			break;
  	  		default:
  	  			return false;
  	  	}
  	  	$map["userid"] = $userid;
  	  	$map["_string"] = "!(whoid=".$userid." AND type=1)";
  	  	$count = $this->where($map)->count("whoid");
  	  	M("Users") ->where("userid=".$userid)->setField("follow_num",$count);
  	  	$type = $type==4 ?1:$type;
  	  	$list = $this->field("userid") ->where("whoid=".$whoid." AND type=".$type." AND userid != whoid")->select();
  	  	M($table) ->where($field."=".$whoid)->save(array('fans_num' =>count($list)));
  	  }
  	  
  	  
  	  /**
  	   * 判断两个用户之间的关注状态
  	   * @param $me 当前用户的ID 
  	   * @param $userid 查看的用户的ID
  	   *@return status=0未关注 status=1已关注 status=2相互关注
  	   *@author penglele
  	   */
  	  public function getUserFollowState($me,$userid){
  	  	if(empty($me))
  	  		return 0;
  	  	$if_followta = $this ->where("userid=$me AND whoid=$userid AND type=1")->find();
  	  	if($if_followta){
  	  		$if_followme=$this ->where("userid=$userid AND whoid=$me AND type=1")->find();
  	  		$status= $if_followme ? 2:1;
  	  	}else{
  	  		$status=0;
  	  	}
  	  	 return $status;
  	  }
  	  
  	  /**
  	   * 获取某某用户关注列表，包括品牌，用户，产品等
  	   * @param unknown_type $userid
  	   */
  	  public function getFollowByList($userid,$limit="9"){
  	  	 $brand_mod = M("ProductsBrand");
  	  	 $products_mod = M("Products");
  	  	 $users_mod = M("Users");
  	  	 $where['userid'] = $userid;
  	  	 $where['_string'] = "!(whoid=".$userid." AND type=1)";
  	  	 $list = $this ->order("addtime desc")->where($where)->limit($limit)->select();
  	  	 foreach($list as $key =>$val){
  	  	 	switch ($val['type']){
  	  	 		case 1:
  	  	 			$name = $users_mod->where("userid=".$val['whoid'])->getField("nickname");
  	  	 			break;
  	  	 		case 2:
  	  	 			$name = $products_mod ->where("pid=".$val['whoid']." AND status=1")->getField("pname");
  	  	 			break;
  	  	 		case 3:
  	  	 			$name = $brand_mod ->where("id=".$val['whoid']." AND status=1")->getField("name");
  	  	 			break;
  	  	 	}
  	  	 	$list [$key]['nickname'] = $name;
  	  	 }
  	  	 return $list;
  	  }
  	  
  	  /**
  	   * 批量加粉
  	   * @param unknown_type $whoid
  	   * @param unknown_type $type
  	   */
  	  public function datAddFollow($whoid,$type,$num=1){
  	  	  $inner_users = M ( "Users" )->field ( "userid" )->order("follow_num ASC")->where ( array ('_string' => "usermail like 'nbceshi%lolitabox.com' OR usermail like 'pingce%lolitabox.com'","userid" =>array("neq",$whoid)) )->select ();
  	  	  $addfansnum=0;
  	  	  foreach($inner_users as $key =>$val){
  	  	  	  $data = array();
  	  	  	  $data['userid'] = $val['userid'];
  	  	  	  $data['whoid'] = $whoid;
  	  	  	  $data['type'] = $type;
  	  	  	  if($this->where($data)->find()){
  	  	  	  	continue;
  	  	  	  }
  	  	  	  $data['addtime'] = time();
  	  	  	  $this->add($data);
  	  	  	  $addfansnum++;
  	  	  	  if($addfansnum >= $num){
  	  	  	  	 break;
  	  	  	  }
  	  	  }
  	  	  if($type==1 || $type==4){
  	  	     M("users")->where("userid=".$whoid)->setInc("fans_num",$addfansnum);
  	  	     D("UserData")->addUserData($whoid,"newfans_num",$addfansnum,0);
  	  	  }
  	  	  if($type==3)
  	  	  	  M("ProductsBrand")->where("id=".$whoid)->setInc("fans_num",$addfansnum);
  	  	  if($type==2)
  	  	      M("Products")->where("pid=".$whoid)->setInc("fans_num",$addfansnum);
  	  	  return $addfansnum;
  	  	 /*  $sql = "insert into follow (userid,whoid,type,addtime) select userid,".$whoid.",$type,unix_timestamp() from users where usermail like '%lolitabox.com' and not exists(select * from follow where userid=users.userid and whoid=$whoid and type=$type) limit ".$num;
  	  	  return $this->query($sql);*/
  	  }
  	  
  	  /**
  	   * 关注自己和官网
  	   * @param unknown_type $userid
  	   */
  	  public function followMe($userid){
  	  	$array = array($userid,C("LOLITABOX_ID"),C("SHOW_BOX_USERID"));
  	  	//加关注
  	  	foreach($array as $key =>$val){
  	  		$data['userid'] = $userid;
  	  		$data['whoid'] = $val;
  	  		$data['type'] = 1;
  	  		if(!$this->where($data)->find()){
  	  			$data['addtime'] = time();
  	  			$this->add($data);
  	  		}
  	  	}
  	  	
  	  }
  	  
  	  /**
  	   * 获取关注列表（过滤）
  	   * @param unknown_type $userid
  	   * @param unknown_type $limit
  	   */
  	  public function getFilterFollowListByUserid($userid,$limit="9"){
  	  	$where['userid']=$userid;
  	  	$where['_string'] = "!(whoid=".$userid." AND type=1)";
  	  	$list = $this->field("whoid,type")->where($where)->order("addtime DESC")->limit($limit)->select();
  	  	$field = "whoid";
  	  	$users_mod = D("Users");
  	  	$a=0;
  	  	$return = array();
  	  	foreach ( $list as $key => $val ) {
			if ($val ['type'] == 1 || $val ['type'] == 4) {
				$userinfo = $users_mod->getUserInfo ( $val [$field], "userface,nickname,fans_num" );
				$return [$a] ['userface'] = $userinfo ['userface_65_65'];
				$return [$a] ['userface_100_100'] = $userinfo ['userface_100_100'];
				$return [$a] ['nickname'] = $userinfo ['nickname'];
				$return [$a] ['userid'] = $val [$field];
				$return [$a] ['fans_num'] = $userinfo ['fans_num'];
				$return [$a] ['spaceurl'] = getSpaceUrl ( $val [$field] );
			} else if ($val ['type'] == 2) {
				$pro_info = D ( "Products" )->getProductInfo ( $val [$field], $userid );
				if(empty($pro_info)){
				    continue;
				}
				$return [$a] ['userid'] = $val [$field];
				$return [$a] ['userface_100_100'] = $pro_info ['pimg'];
				$return [$a] ['nickname'] = $pro_info ['pname'];
				$return [$a] ['fans_num'] = $pro_info ['fans_num'];
				$return [$a] ['spaceurl'] = getProductUrl ( $pro_info ['pid'] );
			} elseif ($val ['type'] == 3) {
				$brand_info = D ( "ProductsBrand" )->getBrandInfo ( $val [$field], "fans_num,logo_url,name,id" );
				if(empty($brand_info)){
					continue;
				}
				$return [$a] ['userid'] = $val [$field];
				$return [$a] ['userface_100_100'] = $brand_info ['logo_url'];
				$return [$a] ['nickname'] = $brand_info ['name'];
				$return [$a] ['fans_num'] = $brand_info ['fans_num'];
				$return [$a] ['spaceurl'] = $brand_info ['brandurl'];
			}
			$return [$a] ['type']=$val['type'];
			$a++;
		}
		return $return;
  	  }  
  	  
  	  /**
  	   * 获取用户的关注总数
  	   */
  	  public function getUserFollowNum($userid,$type=""){
  	  	$where['userid']=$userid;
  	  	if($type){
  	  		$where['type']=$type;
  	  	}
  	  	$where['_string'] = "!(whoid=".$userid." AND type=1)";
  	  	$count=$this->where($where)->count();
  	  	return $count;
  	  }
  	  
 }

?>