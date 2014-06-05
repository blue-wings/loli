<?php
/**
 * 用户分享模型
 */
class UserShareModel extends Model {
	
    /**
     * 获取@品牌，单品，解决方案等分享列表
     * @param int $sourceid
     * @param int $sourcetype
     * @author litingting
     */
	public function getAtSharelist($sourceid,$sourcetype,$limit,$order="addtime DESC",$state=1){
/* 		$where ['ischeck'] = 1;
		$where ['sharetype'] =1 ;
		$where ['status'] = 1; */
		$where ['relationtype'] = 1;
		$where ['sourceid'] = $sourceid;
		$where ['sourcetype'] = $sourcetype;
		if($sourcetype!=1){
			$where ['state'] = 1;
		}
		$list = M("UserAtme")->field("relationid")->where($where)->limit($limit)->order($order)->select();
		foreach($list as $key =>$val){
             	$list [$key] = $this ->getShareInfo($val['relationid']);
		}
		return $list;
		
	}
	
	
	/**
	 * 获取at我的分享列表
	 * @param int $userid
	 * @param string $limit
	 * @param string $order
	 */
	public function getAtmeShareList($userid,$limit="0,10",$order="id DESC"){
		return $this->getAtSharelist($userid,1,$limit,$order);
	}
	
	
	/**
	 * 获取at我的分享总数
	 * @param int $userid
	 * @param string $limit
	 * @param string $order
	 */
	public function getAtmeShareCount($userid){
		$where ['relationtype'] = 1;
		$where ['sourceid'] = $userid;
		$where ['sourcetype'] = 1;
		return M("UserAtme") ->where($where)->count("id");
	}
	
	
	
	/**
	 * 获取@某品牌，单品，解决方案的分享总数
	 * @param unknown_type $sourceid
	 * @param unknown_type $sourcetype
	 */
    public function getAtShareCount($sourceid,$sourcetype){
    	$where ['relationtype'] = 1;
    	$where ['sourceid'] = $sourceid;
    	$where ['sourcetype'] = $sourcetype;
    	$where ['state'] =1;
    	return M("UserAtme") ->where($where)->count("id");
    	
    }
    
    
    /**
     * 供后台使用
     * 获取@某品牌，单品，解决方案的分享总数
     * @param unknown_type $sourceid
     * @param unknown_type $sourcetype
     */
    public function getAtShareCount_admin($sourceid,$sourcetype){
    	$where ['relationtype'] = 1;
    	$where ['sourceid'] = $sourceid;
    	$where ['sourcetype'] = $sourcetype;
    	return M("UserAtme") ->where($where)->count("id");
    	 
    }
    
    /**
     * 供后台使用
     * 获取@某品牌，单品，解决方案的分享总数
     * @param unknown_type $sourceid
     * @param unknown_type $sourcetype
     */
     public function getAtSharelist_admin($sourceid,$sourcetype,$limit,$order="addtime DESC"){
/* 		$where ['ischeck'] = 1;
		$where ['sharetype'] =1 ;
		$where ['status'] = 1; */
		$where ['relationtype'] = 1;
		$where ['sourceid'] = $sourceid;
		$where ['sourcetype'] = $sourcetype;
		$list = M("UserAtme")->field("relationid")->where($where)->limit($limit)->order($order)->select();
		foreach($list as $key =>$val){
             	$list [$key] = $this ->getShareInfo($val['relationid']);
		}
		return $list;
		
	}
    
    
	
	/**
	 * 通过品牌ID获取到品牌相关的所有分享
	 * @param int $brandid
	 * @param string $limit
	 */
	public function getShareListByBrandid($brandid,$limit,$order="status DESC,addtime DESC"){
		$p_list = M("Products")->where("brandcid=".$brandid)->select();
		$pro_list=array();
		foreach($p_list as $key =>$val){
			$where[] ="(sourceid=". $val['pid']." and sourcetype=2)";
		}
		$where[] = "(sourceid=".$brandid. " AND sourcetype=3)";
		$wher["_string"] = implode("  OR ",$where);
		$wher['state'] = 1;
		$wher['relationtype'] =1;
		$products_mod = M("Products");
		$list = M("UserAtme")->field("distinct(relationid) as relationid") ->where($wher)->limit($limit)->order($order)->select();
		foreach($list as $key => $val){
			$list[$key] = $this->getShareInfo($val['relationid']);
			if(empty($list[$key]['img'])){
				if($val['sourcetype']==2){
					$img = $products_mod->where("pid=".$val['sourceid'])->getField("pimg");
					$list[$key]['img'] = $img;
					$list[$key]['img_big'] = $img;
				}
			}
		}
		return $list;
	}
	
	
	/**
	 * 通过品牌ID获取总数
	 * @param int $brandid
	 */
	public function getShareCountBybrandid($brandid){
		$p_list = M("Products")->where("brandcid=".$brandid)->select();
		$pro_list=array();
		foreach($p_list as $key =>$val){
			$where[] ="(sourceid=". $val['pid']." and sourcetype=2)";
		}
		$where[] = "(sourceid=".$brandid. " AND sourcetype=3)";
		$wher["_string"] = implode("  OR ",$where);
		$wher['state'] = 1;
		$wher['relationtype'] =1;
		return  M("UserAtme")->where($wher)->count("distinct(relationid)");
	}
	
	/**
	 * 通过单品ID获取到产品相关的所有分享
	 * @param int $brandid
	 * @param string $limit
	 */
	public function getShareListByPid($pid,$limit,$order="pick_status DESC,status DESC,posttime DESC",$pk='',$wordnum=75){
		if(empty($pid)){
			return false;
		}
		$prolist = M("Products")->where("pid=".$pid)->getField("productlist");
		if($prolist){
			 $where['resourceid']= array("IN",$pid.",".$prolist);
		}else{
			$where ['resourceid'] = $pid;
		}
		$where['sharetype'] =1;
		$where['resourcetype'] = 1;
		$where['status'] = array("gt",0);
 		$where['ischeck'] = 1;
// 		$where['pick_status'] = 1;
		return $this->getShareList($where,$limit,$order);
	}
	
	
	/**
	 * 通过产品ID获取收录总数
	 * @param int $pid
	 * @author litingting
	 */
	public function getShareCountByPid($pid){
		$prolist = M("Products")->where("pid=".$pid)->getField("productlist");
		if($prolist){
			$where['resourceid']= array("IN",$pid.",".$prolist);
		}else{
			$where ['resourceid'] = $pid;
		}
		$where['sharetype'] =1;
		$where['resourcetype'] = 1;
		$where['status'] = array("gt",0);
		$where['ischeck'] = 1;
// 		$where['pick_status'] = 1;
		$count= $this->where($where)->count("1");
		return $count;
	}
	
	/**
	 * 通过产品ID获取总数
	 * @param int $pid
	 * @author litingting
	 */
	public function getShareTotalByPid($pid){
		$prolist = M("Products")->where("pid=".$pid)->getField("productlist");
		if($prolist){
			$where['resourceid']= array("IN",$pid.",".$prolist);
		}else{
			$where ['resourceid'] = $pid;
		}
		$where['sharetype'] =1;
		$where['resourcetype'] = 1;
		$where['status'] = array("gt",0);
		$where['ischeck'] = 1;
		$count= $this->where($where)->count("1");
		return $count;
	}
	
	/**
	 * 产品精选分享推荐
	 * @param unknown_type $pid
	 */
	public function getHotShareByPid($pid,$limit=5){
		$where['resourcetype'] = 1;
		$where['resourceid'] = $pid;
		$where['status'] = array("gt",0);
		$where['pick_status']=1;
		$list = M("UserShare") ->field("id")->where($where)->limit($limit)->order("iscommend DESC,status DESC")->select();
		if($list){
			foreach($list as $key =>$val){
				$list [$key] = $this ->getShareInfo($val['id'],75);
			}
		}else{
			$list="";
		}
		return $list;
	}
	
	
	
	/**
	 * 通过解决方案ID获取总数
	 * @param int $brandid
	 */
	public function getShareCountBySolutionid($id){
		return $this->getAtShareCount($id,4);
	}
	
	
	/**
	 * 通过解决方案ID获取到相关的所有分享
	 * @param int $brandid
	 * @param string $limit
	 */
	public function getShareListBySolutionid($id,$limit,$order="addtime DESC"){
		return $this->getAtSharelist($id,4,$limit,$order);
	}
	
	/**
	 * 通过查询条件获取分享列表
	 * @param unknown_type $where
	 * @param unknown_type $limit
	 * @param unknown_type $order
	 */
	public function getShareList($where,$limit="",$order="status DESC,posttime DESC",$ifall=false,$wordnum=300){
		$field = $ifall==false ? "id,userid,commentnum,agreenum,posttime,clienttype,sharetype":"" ;
		$where['sharetype'] = 1;
		$list=$this->field($field)->where($where)->limit($limit)->order($order)->select();
		foreach($list as $key =>$val){
			$list[$key] = $this ->getShareInfo($val['id']);
		}
		return $list;
	}
	
	/**
	 * 获取分享详情
	 * @param int $shareid
	 */
	public function getShareInfo($shareid,$wordnum=100){
		$info = $this->where("sharetype=1")->getById($shareid);
		if(!$info)
			return false;
		$public_mod = D("Public");
		if($info['status'] <1){
			$info['content'] = "分享已被删除";
		}else{
			$data = M("UserShareData")->getByShareid($shareid);
			import("ORG.Util.String");
			if($data){
				if(preg_match("|<\s*img.*src='([^']*)'[^>]*>|Ui", $data['content'], $matche)){
					$content_arr=sliceGraphic($data['content']);
					$data['content'] = $content_arr[0]['content'] ?  $content_arr[0]['content']: $content_arr[1]['content'];
					$data['img'] = $content_arr[0]['img'] ? $content_arr[0]['img']:$content_arr[1]['img'];
				}
				$data['content']=$public_mod ->deleteContentSmilies($data['content']);
				$info['content_all'] = $data['content'];
				if(mb_strlen($data['content'],"utf8") > $wordnum){
					$data['content']=String::msubstr($data['content'] ,0,$wordnum,'utf-8');
					$info['more']="查看更多";
				}
				if($info['userid']==2375 && $info['sharetype']==1){
					$data['content']=text2links($data['content']);
				}
				$info['content'] =  $data['content'];
				if(empty($data['content'])){
					$info ['details'] = unserialize($data['details']);
					if(preg_match("|<\s*img.*src='([^']*)'[^>]*>|Ui", $info['details'][0]['content'], $matches)){
						$info ['details'] = sliceGraphic($info['details'][0]['content']);
					}
					if(empty($info['content'])){
						$info['content'] = $info['details'][1]['content'];
					}
				}
				$info['details'] = unserialize($data['details']);
			}
		}
		//图文混排的内容，判断是否有图片
		if($info['details']){
			$detail_img=M("UserShareAttach")->where("shareid=".$shareid." AND status=1")->find();
			if(!$detail_img){
				$info['no_detail_img']=1;
			}
		}
		if($info['resourcetype'] && $info['resourceid']){
			$tag = array();
			$sourceid = $info['resourceid'];
			switch($info['resourcetype']){
				case 1:
					$proinfo = M("Products")->field("pname,pimg")->where("pid=".$sourceid)->find();
					$tag['name']=$proinfo['pname'];
					$tag['img']=$proinfo['pimg'];
					$tag['spaceurl'] = getProductUrl($sourceid);
					break;
				case 2:
					$tag['name'] = M("ProductsBrand")->where("id=".$sourceid)->getField("name");
					$tag['spaceurl'] = getBrandUrl($sourceid);
					break;
				case 3:
					$tag['name'] = M("Users")->where("userid=".$sourceid)->getField("nickname");
					$tag['spaceurl'] = getSpaceUrl($sourceid);
					break;
// 				case 4:
// 					$boxid = M("UserOrder")->where("ordernmb=".$sourceid)->getField("boxid");
// 					$tag['name'] = "晒盒";
// 					break;
			}
			$info['tag'] = $tag;
		}
		$info['img'] = M("UserShareAttach")->where("shareid=".$shareid." AND status=1")->limit(1)->getField("imgpath"); 
		//图片的大图、小图
		if($info['img'] ){
			$pic_arr=$public_mod->getSmallPic($info['img']);
			$info['img']=$pic_arr['img'];
			$info['img_big']=$pic_arr['img_big'];
			list($info['img_w'],$info['img_h']) = getimagesize(ltrim($info['img'],"/"));
			list($info['img_big_w'],$info['img_big_h']) = getimagesize(ltrim($info['img_big'],"/"));
		}		
		//如果是对盒子发表的分享，盒子的名称
		if($info['boxid']){
			$boxinfo=D("Box")->getBoxInfo($info['boxid'],"name,pic");
			if(!$info['img']){
				$info['img']=$boxinfo['pic'];
				$info['img_w']=200;
				$info['img_h']=200;
				$info['img_big']=$info['img'];
			}
			$info['boxname']=$boxinfo['name'];
			$info['boxurl']=getBoxUrl($info['boxid']);
		}		
		if(!$info['img'] && $info['resourcetype']==1 && $info['resourceid']){
			$info['img']=$tag['img'];
			$info['img_w']=200;
			$info['img_h']=200;
			$info['img_big']=$info['img'];
		}
		if($info['resourcetype']==1 && $info['resourceid']){
			$info['boxname']=$info['boxname'] ? $info['boxname']: $tag['name'];
			$info['boxurl']=getProductUrl($info['resourceid']);
		}
		$userinfo = D("Users")->getUserInfo($info['userid']);
		$info['nickname'] = $userinfo ['nickname'];
		$info['userface'] = $userinfo ['userface_65_65'];
		$info['userface_100_100'] = $userinfo ['userface_100_100'];
		$info['spaceurl'] = getSpaceUrl($info['userid']);
		$info['shareurl'] = getShareUrl($shareid,$info['userid']);
		if($userinfo['is_solution']==1){
			$info['if_super']=2;
		}else{
			$info['if_super']=$userinfo['if_super'];
		}
	   // $info['tag'] = D("UserAtme")->getTagListByShareid($shareid);
		return $info;
	}
	
    /**
     * 增加分享
     * @param int $userid
     * @param string $content
     * @param string $img
     * @param mixed $atuserlist @用户列表
     * @param int $resourceid
     * @param int $resourcetype
     * @param int $clienttype
     * @param array $sharedata 【图文混排的内容】
     * @author litingting
     */
	public function addShare($userid,$content,$img="",$clienttype=0,$atlist=array(),$sourceid=0,$sourcetype=0,$sharedata=array()){
		//update by 2013-11-28 11:39:52
		if($sharedata && !empty($sharedata['clean_content'])){
			$content=$sharedata['clean_content'];
		}
	   	if((empty($content) && empty($sharedata)) || empty($userid)){
	   		return false;
	   	}
	   	if(filterwords($content))
	   		return false;     //含敏感词 
	   	$is_solution = M("Users")->where("userid=".$userid)->getField("is_solution");
	   	$data['userid'] =$userid;
	   	$data['clienttype']= $clienttype;
	   	$data['sharetype'] =1;
	   	$data['posttime'] =time();
	   	$data['resourceid'] = $sourceid;
	   	$data['resourcetype'] = $sourcetype;
	   	$data['ischeck'] = filterwords($content) ? 0:1; 
	   	if($is_solution){
	   		//如果是解决方案用户，则将其分享的权重置为1
	   		$data['status'] = 1;
	   	}else{
	   		//非解决方案用户，其分享的权重置为2
	   		$data['status'] = 2;
	   	}
	   	if($sourcetype==4){
	   		$data['boxid'] = M("UserOrder")->where("ordernmb=".$sourceid)->getField("boxid");
	   	}
	   	if($is_solution){
	   		//如果用户为解决方案账号，则分享自动被收录
	   		$data['pick_status'] = 1; 
	   	}
	   	
	   	$shareid =$this->add($data);   //主表
	   	if(empty($shareid))
	   		return false;
	   	
	   	$data_content['content'] = $content;
	   	$data_content['shareid'] = $shareid;
	   	//图文混排的内容序列化后存到details中 update by 2013-11-28 11:39:52
	   	if(($sharedata['clean_content'] || $sharedata['clean_img']) && $sharedata){
	   		$data_content['details']=serialize($sharedata['content']);
	   	}
	   	M("UserShareData")->add($data_content);  //内容表
	   	
	    $taglist = D("Scws")->getTagsByContent($content);
	    
	   	//update by 2013-11-28 11:39:52
	    if($sharedata['imglist']){
	    	//图文形式的分享
	    	foreach($sharedata['imglist'] as $key){
	    		$data_img=array();
	    		mb_internal_encoding( 'UTF-8');
	    		$title = implode(" ", $taglist);
	    		$data_img['shareid'] =$shareid;
	    		$data_img['userid'] =$userid;
	    		$data_img['imgpath'] =str_replace("//","/","/".$key);
	    		$data_img['title'] =$title;
	    		$data_img['status'] =1;
	    		M("UserShareAttach") ->add($data_img);   //附件表
	    	}
	    }else{
	    	//普通形式的分享
	    	if($img){
	    		mb_internal_encoding( 'UTF-8');
	    		//$title=mb_substr($content,0,10);
	    		$title = implode(" ", $taglist);
	    		$data_img['shareid'] =$shareid;
	    		$data_img['userid'] =$userid;
	    		$data_img['imgpath'] =str_replace("//","/","/".$img);
	    		$data_img['title'] =$title;
	    		$data_img['status'] =1;
	    		M("UserShareAttach") ->add($data_img);   //附件表
	    	} 	
	    }
	   	
	   	M("Users")->where("userid=".$userid)->setInc("blog_num",1);    //用户分享数加1
	 
	   	//加入到用户行为表中
	   	$data = array();
	   	$data['type'] ="post_shareid";
	   	$data['usertype'] = 1;
	   	$data['userid'] = $userid;
	   	$data['whoid'] = $shareid;
	   	$data['status'] = 1;
	   	$data['addtime'] =time();
	   	M("UserBehaviourRelation") ->add($data);
	   	return $shareid;
	}
	
	/**
	 * 通过分享ID编辑分享
	 * @param int $sharid
	 * @param string $img
	 * @param string $coantent
	 * @param array $sharedata 图文混排的内容
	 * @author litingting
	 */
    public function updateShare($shareid,$userid,$content,$img="",$sharedata=array()){
    	if(empty($userid) || empty($shareid) || (empty($content) && empty($sharedata))){
    		return false;
    	}
    	if(filterwords($content))
    		return false;     //含敏感词
    	$attach_mod = M("UserShareAttach");
    	
    	//update by 2013-12-2 9:42:01
    	if($sharedata && $sharedata['clean_content']){
    		$content=$sharedata['clean_content'];
    	}
    	$taglist = D("Scws")->getTagsByContent($content);
    	 
    	//update by 2013-12-2 9:42:01
    	if($sharedata['imglist']){
    		$attach_mod->where("shareid=".$shareid)->delete();
    		//图文形式的分享
    		foreach($sharedata['imglist'] as $key){
    			$data_img=array();
    			mb_internal_encoding( 'UTF-8');
    			$title = implode(" ", $taglist);
    			$data_img['shareid'] =$shareid;
    			$data_img['userid'] =$userid;
    			$data_img['imgpath'] =str_replace("//","/","/".$key);
    			$data_img['title'] =$title;
    			$data_img['status'] =1;
    			$attach_mod ->add($data_img);   //附件表
    		}
    	}else{
    		//普通形式的分享
	    	if($img){
	    		$save['title']=mb_substr($content,0,10);
	    		$save['imgpath'] = $img;
	    		$save['status'] = 1; 
	    		$where['shareid'] = $shareid;
	    		$where['status'] = 1;
	    		if($info=M("UserShareAttach")->where($where)->find()){
	    			$attach_mod->where("id=".$info['id'])->limit(1)->save($save);
	    		}else{
	    			$save['shareid'] = $shareid;
	    			$save['status'] = 1;
	    			$attach_mod->add($save);
	    		}
	    	}else{
	    		$where['shareid'] = $shareid;
	    		$where['status'] = 1;
	    		$attach_mod ->where($where)->setField("status", 0);
	    	}
    	}
    	$where = array();
    	$data = array();
    	$save['content'] = $content;
    	$where['shareid'] = $shareid;
    	$flag=M("UserShareData")->where($where)->save($save);  //内容表
    	//如果是图文混排形式的，则更新details数据
    	if(($sharedata['clean_content'] || $sharedata['clean_img']) && $sharedata){
    		$data_comment['details']=serialize($sharedata['content']);
    		$data_comment['content']=$content;
    		M("UserShareData")->where("shareid=".$shareid)->save($data_comment);
    	}
    	
    	return $flag===false ? false:true;
    }
	
	
	
   
	/**
	 * 获取我的分享列表
	 * @param int $userid
	 * @param int $type 分享类型，（包含试用和晒盒）
	 * @param string $order
	 */
	public function getMyShareList($userid,$type=0,$limit="0,10",$order="posttime DESC"){
	    $where['userid'] = $userid;
	    $where ['status'] = array("gt",0);
	    if($type){
	    	$where['resourcetype']= $type;
	    }
	    return $this->getShareList($where,$limit,$order,false); 
	}
	
	/**
	 * 获取我的全部分享数
	 * @param int $userid
	 * @param int $type
	 */
	public function getMyShareNum($userid,$type=0){
		$where['userid'] = $userid;
		$where['status'] = array("gt",0);
		$where['sharetype'] = 1;
		if($type){
			$where['resourcetype']= $type;
		}
		$num = $this->where($where)->count();
		return $num;
	}
	
	
	/**
	 * 获取我的晒盒分享列表
	 * @param int $userid
	 * @param string $order
	 */
	public function getMyBoxShareList($userid,$limit="0,10",$order="status DESC,posttime DESC"){
		$where['userid'] = $userid;
		$where ['status'] = array("gt",0);
		$where ['boxid'] = array("gt",0);
		return $this->getShareList($where,$limit,$order);
	}
	
	/**
	 * 获取我的晒盒全部分享数
	 * @param int $userid
	 */
	public function getMyBoxShareNum($userid){
		$where['userid'] = $userid;
		$where['status'] = array("gt",0);
		$where['boxid'] = array("gt",0);
		$num = $this->where($where)->count();
		return $num;
	}
	
	
	/**
	 * 获取我赞的分享
	 * @param int $userid
	 * @param string $limit
	 * @param string $order
	 */
	public function getMyAgreeList($userid,$limit="0,10",$order="addtime DESC"){
		$where ['userid'] = $userid;
		$where ['type'] = 2;
		$where ['status']  = 1;
		$share_action = M("UserShareAction");
		$list = $share_action->field("shareid,addtime") ->where($where)->order($order)->limit($limit)->select();
		foreach($list as $key =>$val){
			$info=$this->getShareInfo($val['shareid']);
			$list [$key]=$info;
		}
		return $list;
	}
	
	/**
	 * 获取我赞的分享数
	 * @param int $userid 用户ID
	 */
	public function getMyAgreeNum($userid){
		$where ['userid'] = $userid;
		$where ['type'] = 2;
		$where ['status']  = 1;
		$share_action = M("UserShareAction");
		$num = $share_action->where($where)->count();
		return $num;
	}
	
	
	
	/**
	 * 通过分享ID获取评论列表
	 * @param int $shareid
	 * @param string $limit
	 * @param string $order
	 * +---------------------------------------------------------+
	 * 返回结果中type=1代表评论分享，type=2代表回复评论
	 * +---------------------------------------------------------+
	 */
	public function getCommentListByShareid($shareid,$limit="0,5",$order="id desc"){
		$where['isdel'] =0;
		$where['ischeck'] =1;
		$where['shareid'] =$shareid;
		$list = M("UserShareComment")->where($where)->field("id,shareid,userid,content,posttime,to_uid")->limit($limit)->order($order)->select();
		$user_mod = D("Users");
		$public_mod = D("Public");
		foreach($list as $key =>$val){
			$userinfo = $user_mod ->getUserInfo($val['userid'],"nickname,userface");
			$list[$key]['nickname'] =$userinfo['nickname'];
			$list[$key]['userface'] =$userinfo['userface_65_65'];
			$list[$key]['spaceurl'] =getSpaceUrl($val['userid']);
			$list[$key]['content'] = $public_mod->handleShareContent($list[$key]['content']);
			$to_userinfo = $user_mod ->getUserInfo($val['to_uid'],"nickname,userface");
			$list[$key]['to_nickname'] = $to_userinfo['nickname'];
			$list[$key]['to_userface'] =$to_userinfo['userface_65_65'];
			$list[$key]['to_spaceurl'] =getSpaceUrl($val['to_uid']);
		}
		return $list;
	}
	
	/**
	 * 通过分享ID获取评论总数
	 * @param int $shareid
	 */
	public function getCommentCountByShareid($shareid){
		$where['isdel'] =0;
		$where['ischeck'] =1;
		$where['shareid'] =$shareid;
		return M("UserShareComment")->where($where)->count("id");
		
	}
	
	/**
	 * 获取用户发送的评论列表
	 * @param int $userid
	 * @param string $limit
	 */
	public function getSendCommentListByUserid($userid,$limit="0,10"){
		$where ['userid'] =$userid;
		return $this->getCommentList($where,"to_uid",$limit,"id DESC");
	}
	
	/**
	 * 获取用户收到的评论列表
	 * @param int $userid
	 * @param string $limit
	 */
	public function getReceiverCommentListByUserid($userid,$limit="0,10"){
		$where ['to_uid'] =$userid;
		return $this->getCommentList($where,"userid",$limit,"id DESC");
	}
	
	/**
	 * 获取用户收到的评论总数
	 * @param int $userid
	 */
	public function getReceiverCommentNum($where){
		$where ['ischeck'] =1;
		$where ['isdel'] = 0;
		$num = M("UserShareComment")-> where($where)->count();
		return $num;
	}
	
	/**
	 * 根据条件获取用户评论列表
	 * @param unknown_type $where
	 * @param unknown_type $limit
	 * @param unknown_type $order
	 */
	public function getCommentList($where,$field="userid",$limit="",$order="status DESC,posttime DESC"){
		$where ['ischeck'] =1;
		$where ['isdel'] = 0;
		$list = M("UserShareComment")-> where($where)->order($order)-> field("id,shareid,userid,to_uid,content,commentdata,posttime")->limit($limit)->select();
		$user_mod = D("Users");
		$public_mod = D("Public");
		foreach($list as $key =>$val){
			$userinfo= $user_mod ->getUserInfo($val[$field],"userface,nickname");
			$list[$key]['nickname'] = $userinfo['nickname'];
			$list[$key]['userface'] = $userinfo['userface_65_65'];
			$list[$key]['spaceurl'] =getSpaceUrl($val['userid']);
			$to_userinfo = $user_mod ->getUserInfo($val['to_uid'],"nickname,userface");
			$list[$key]['to_nickname'] = $to_userinfo['nickname'];
			$list[$key]['to_userface'] =$to_userinfo['userface_65_65'];
			$list[$key]['to_spaceurl'] =getSpaceUrl($val['to_uid']);
			$list[$key]['content'] = $public_mod->handleShareContent($list[$key]['content']);
			$data = unserialize($val['commentdata']);
			if($data){
				$list[$key]['type'] = $data['type'];
				$list[$key]['to_content'] =$data['content'];
				$list[$key]['to_commentid'] = $data['id'];
				$list[$key]['to_url'] = getShareUrl($list[$key][shareid]);
			}
			unset($list[$key]['commentdata']);
		}
		return $list;
	}
	
	/**
	 * 获取评论详情
	 * @param int $commentid
	 */
	public function getCommentInfo($commentid){
		$where ['id'] = $commentid;
		$info = $this ->getCommentList($where);
		return $info[0];
	}
	
	/**
	 * 发布评论
	 * @param unknown_type $userid
	 * @param unknown_type $content
	 * @param unknown_type $to_uid
	 * @param unknown_type $to_commentid
	 */
	public function addComment($userid,$shareid,$content,$to_uid,$to_commentid=0,$atuserlist=array()){
		if(empty($userid) || empty($shareid) ||empty($content) || empty($to_uid)){
			return false;
		}
		$data['userid'] =$userid;
		$data['shareid'] = $shareid;
		$data['content'] = $content;
		$data['to_uid'] = $to_uid;
		$data['to_commentid'] = $to_commentid;
		$data['ischeck'] = filterwords($content) ? 0:1;
		if($to_commentid==0){
			$commentdata['id'] = $shareid;
			$to_content = M("UserShareData") ->where("shareid=".$shareid)->getField("content");
			$commentdata['content'] = msubstr($to_content, 0,25);
			$commentdata['type'] =1 ; //代表评论分享
		}else{
			$commentdata['id'] = $to_commentid;
			$to_content = M("UserShareComment") ->where("id=".$to_commentid)->getField("content");
			$commentdata['content'] = msubstr($to_content, 0,25);
			$commentdata['type'] = 2 ; //代表评论分享
		}
		
		$data['commentdata'] =serialize($commentdata);
		$data['posttime'] = time();
		$commentid=M("UserShareComment")->add($data);
		$return =$data;
		$return['id'] = $data;
		D("UserData")->addUserData($to_uid,"unread_comment");    //加入未读信息
		if(!$commentid){
			return false;
		}
		$this ->where("id=".$shareid)->setInc('commentnum',1); // 分享的评论加1
	
		//加入到用户行为表中
		$data = array();
		$data['type'] ="post_commentid";
		$data['usertype'] =1;
		$data['userid'] = $userid;
		$data['whoid'] = $commentid;
		$data['status'] = 1;
		$data['addtime'] =time();
		M("UserBehaviourRelation") ->add($data);
		return $commentid;
	}
	
	
	
	/**
	 * 删除用户分享
	 * @param int $userid 用户ID 
	 * @param int $shareid 分享ID
	 */
	public function deleteUserShare($userid,$shareid){
		if(empty($userid) || empty($shareid))
			return false;
		$shareinfo=$this->field("userid")->getById($shareid);
		if(!$shareinfo || $shareinfo['userid']!=$userid)
			return false;
		$data['status']=0;
		$res=$this->where("id=$shareid")->save($data);
		if($res===false) 
			return false;
		$behaviour_mod =D("UserBehaviourRelation");
		M("Users")->where("userid=".$userid)->setDec("blog_num",1);
		$behaviour_mod->delPostShare($userid,$shareid);  //删除发分享动态
		$behaviour_mod->delCollectByShareid($shareid);   //删除收录动态
		M("UserAtme") ->where("relationid=".$shareid." AND relationtype=1")->setField("state", 0);   //删除收录
		return true;
	}
	
    /**
     * 通过关键字搜索分享列表
     * @param string $taglist 用逗号分隔的关键字
     */
	public function getShareListByTag($tagname,$limit="10"){
		$xs = new XunSouModel("share");
		$sharelist = $xs ->search($tagname,"shareid",$limit);
		$public_mod = D("Public");
        foreach($sharelist as $key =>$val){
        	$sharelist[$key] = $this ->getShareInfo($val['shareid']);
        	//$preg = "/[^@|^#](".$tagname."(?! |#))/i";
        	//$content_a=preg_replace($preg,"<span class='S_txt4' style='font-weight:bold;color:#FF8800'>\\1</span>",$sharelist[$key]['content_all']);
        	//$sharelist[$key]['content_a'] = $public_mod->handleShareContent($content_a);
        	if($sharelist[$key]['status']<=0){
        		$sharelist[$key]['content_a']="oh，my god，小萝莉感到很抱歉，内容被原作者删除哩~";
        		$sharelist[$key]['more']="";
        		$sharelist[$key]['img'] = "";
        	}
        	
        }
        return $sharelist;
        
	}
	
	/**
	 * 通过关键字搜索分享总数
	 * @param string $taglist 用逗号分隔的关键字
	 */
	public function getShareCountByTag($tagname){
		$xs = new XunSouModel("share");
		return $xs->count($tagname);
	
	}
	
	/**
	 * 判断用户是否已对当前的分享赞过
	 * @param unknown_type $parentid
	 * @param unknown_type $userid
	 * @return -2已赞过 1没赞过
	 */
	public function checkUserIfAgree($parentid,$userid){
		if($this->where("parentid=".$parentid." AND userid=".$userid." AND status=1")->find()){
			return -2;    //不能对同一个分享赞两次
		}else{
			return 1;
		}
	}
	
	/**
	 * 删除回复
	 * @param $commentid 评论ID
	 * @param $userid 用户ID
	 * @author penglele
	 */
	public function delComment($commentid,$userid){
		if(empty($commentid) || empty($userid)){
			return false;
		}
		$comment_mod=M("UserShareComment");
		$comment_info=$comment_mod->where("id=$commentid AND userid=$userid")->find();
		if(!$comment_info)
			return false;
		$data['isdel']=1;
		D("UserBehaviourRelation")->delComment($userid,$commentid);
		$res=$comment_mod->where("id=$commentid")->save($data);
		if(!$res)
			return false;
		
		$this->where("id=".$comment_info['shareid'])->setDec("commentnum",1);
		return true;
	}
	
	/**
	 * 能过评测id和日志ID获取分享ID
	 * @param int $blogid
	 * @param int $blogtype 1-评测，2-日志
	 * @return  int|null
	 */
	public function getShareidByBlogid($blogid,$blogtype=2){
		if(empty($blogid))
			return false;
		$id=$this ->where("blogid=".$blogid." AND blogtype=".$blogtype)->getField("id");
		return $id;
	}
	
	/**
	 * 获取精选分享
	 */
	public function getChoiceShareList($limit="30"){
	//	$where['status'] = array("gt",4);
	    $where ['iscommend'] = 1;
		$where ['status'] = 1;
		$where ['sharetype'] =1 ;
		$list = $this->getShareList($where,$limit,"id DESC");
		foreach($list as $key =>$val){
			$arr=$this->getShareShortContent($val['content_all'],$list[$key]["posttime"]);
			$list[$key]['action_date']=$arr["action_date"];
			$list[$key]['content']=$arr["content"];
		}
		return $list;
	}
	
	/**
	 * 获取全部 || 某个用户的 分享
	 */
	public function getShareListToApp($where="",$limit=""){
		$where['sharetype']=1;
		$where['status']=array("exp",">0");
		if($limit){
			$list=$this->field("id")->where($where)->limit($limit['offset'].",".$limit['pagesize'])->order("id DESC")->select();
		}else{
			$list=$this->field("id")->where($where)->order("id DESC")->select();
		}
		if($list){
			foreach($list as $key=>$value){
				$shareinfo=$this->getShareInfo($value['id']);
				$userinfo=D("Users")->getUserInfo($shareinfo['userid'],"if_super");
				$share_list[$key]['title']=$shareinfo['nickname'];
				$share_list[$key]['uid']=$shareinfo['userid'];
				$share_list[$key]['img']=$shareinfo['img'];
				$share_list[$key]['nickname']=$shareinfo['nickname'];
				$share_list[$key]['userface']=$shareinfo['userface'];
				$share_list[$key]['if_super']=$userinfo['if_super']?"达人":"网友";
			}
		}
		return $share_list;
	}
	
	/**
	 * 获取某个用户的分享列表--app
	 * @param  $userid 用户ID
	 * @param  $limit 
	 */
	public function getUserShareListApp($userid,$limit=""){
		if($userid) 	$where['userid']=$userid;
		$where['sharetype']=1;
		$where['status']=array("exp",">0");
		//$where['pick_status'] = 1;
		if($limit){
			$list=$this->field("id as blogid,userid as uid,posttime as postdate")->where($where)->limit($limit['offset'].",".$limit['pagesize'])->order("status DESC,posttime DESC")->select();
		}else{
			$list=$this->field("id as blogid,userid as uid,posttime as postdate")->where($where)->order("status DESC,posttime DESC")->select();
		}
		$share_data_mod = M("UserShareData");
		$share_attach_mod = M("UserShareAttach");
		$user_mod = D("Users");
		if($list){
			foreach($list as $key=>$value){
				$share_data = $share_data_mod->getByShareid($value['blogid']);
				$share_img = $share_attach_mod ->where("shareid=".$value['blogid'])->limit(1)->getField("imgpath");
				$userinfo = $user_mod->getUserInfo($value['uid']);
				$list[$key]["title"]=$userinfo['nickname'];
				$list[$key]["content"] = $share_data['content'];
				$list[$key]["img"] = $share_img; 
				$list[$key]['if_super'] = $userinfo['if_super'];
				$list[$key]['nickname'] = $userinfo['nickname'];
				$list[$key]['userface'] =  "http://".$_SERVER['SERVER_NAME'].$userinfo['userface_50_50'];
				$list[$key]['postdate'] = date("Y-m-d H:i:s",$value['postdate']);
				$list[$key]['if_super']=$userinfo['if_super']?"达人":"网友";
				if(empty($share_img)){
					$list[$key]{'img'}="http://".$_SERVER['SERVER_NAME']."/data/userdata/public/app_blog_noimg.png";
				}
			}
		}
		return $list;
	} 
	
	
	/**
	 * 获取某个用户的分享列表--app
	 * @param  $limit
	 */
	public function getAllShareListApp($limit=""){
		if($limit){
			$p=$limit['offset'].",".$limit['pagesize'];
		}
		$where['sharetype']=1;
		$where['pick_status'] = 1;
		$where['status']  = array("gt",0);
		$order=" status DESC,id DESC";
		$list = M("UserShare")->field("id as blogid")->where($where)->order($order)->limit($p)->select();
		if($list){
			$share_data_mod = M("UserShareData");
			$share_attach_mod = M("UserShareAttach");
			$user_mod = D("Users");
			$user_share_mod = M("UserShare");
			foreach($list as $key=>$value){
				$share_data = $share_data_mod->getByShareid($value['blogid']);
				$share_img = $share_attach_mod ->where("shareid=".$value['blogid'])->limit(1)->getField("imgpath");
				$info= $user_share_mod ->field("userid,posttime as postdate")->getById($value['blogid']);
				$userinfo = $user_mod->getUserInfo($info['userid']);
				$list[$key]["title"]=$userinfo['nickname'];
				//$list[$key]["content"] = $share_data['content'];
				$list[$key]["img"] = $share_img;
				$list[$key]['if_super'] = $userinfo['if_super'];
				$list[$key]['nickname'] = $userinfo['nickname'];
				$list[$key]['userface'] =  "http://".$_SERVER['SERVER_NAME'].$userinfo['userface_50_50'];
				$list[$key]['postdate'] = date("Y-m-d H:i:s",$info['postdate']);
				$list[$key]['if_super']=$userinfo['if_super']?"达人":"网友";
				if(empty($share_img)){
					$list[$key]{
						'img'}="http://".$_SERVER['SERVER_NAME']."/data/userdata/public/app_blog_noimg.png";
				}
			}
		}
		return $list;
	}
	
	/**
	 * app评论
	 * @param unknown_type $userid
	 * @param unknown_type $userid
	 * @param unknown_type $content
	 */
	public function addCommentApp($userid,$shareid,$content){
		$shareinfo = $this->where("id=".$shareid." AND status>0")->find();
		if($shareinfo){
			$flag=$this->addComment($userid, $shareid, $content, $shareinfo['userid']);
			if($flag){
				$info = M("UserShareComment")->getById($flag);
				$userinfo = D("Users")->getUserinfo($userid,"nickname,userface");
				$return['replyid'] = $flag;
				$return['replycontent']=$info['content'];
				$return['postdate'] =date("Y-m-d H:i:s",$info['posttime']) ;
				$return['blogid'] = $shareid;
				$return['userface'] = "http://".$_SERVER['SERVER_NAME'].$userinfo['userface_50_50'];
				$return['nickname'] = $userinfo['nickname'];
				$return['uid'] = $userid;
				return $return;
			}else{
				return false;
			}
		}else{
			return 201;
		}
	}
	
	
	/**
	 * app分享详情
	 */
	public function getShareInfoApp($shareid){
		if(empty($shareid))
			return false;
		$info=$this->getShareInfo($shareid);
		if(!$info)
			return false;
		$blog_info['postdate']=date("Y-m-d H:i:s",$info['posttime']);
		$blog_info["title"] =$info['nickname'];
		$blog_info["replynum"]=$info['commentnum'];
		if($info['details']){
			$blog_info["contents"]=$info['details'];
		}else{
			$content_info['img']=$info['img_big'];
			$content_info['content']=$info['content_all'];
			$blog_info ['contents'][] = $content_info;
		}
		$share_info['bloginfo']=$blog_info;
		$userinfo['uid']=$info['userid'];
		$userinfo['nickname']=$info['nickname'];
		$userinfo['userface']="http://".$_SERVER['SERVER_NAME'].$info['userface'];
		$share_info['userinfo']=$userinfo;
		$commenlist=$this->getCommentListByShareid($shareid,2);
		if($commenlist){
			foreach($commenlist as $key=>$value){
					$userlist[$key]['userid']=$value['userid'];
					$userlist[$key]['nickname']=$value['nickname'];
			}
		}else{
			$userlist="";
		}
		$share_info['userlist']=$userlist;
		return $share_info;
	}
	
	/**
	 * APP 通过分享ID获取回复列表
	 * @param unknown_type $shareid
	 * @param unknown_type $limit
	 */
	public function getReplyListByShareid($shareid,$limit){
		$list = M("UserShareComment")->field("id as replyid,content as replycontent,userid as uid,posttime as postdate")->where("shareid=".$shareid." AND ischeck>0 AND isdel=0")->limit($limit)->select();
		$user_mod = D("Users");
		foreach($list as $key=>$val){
			$userinfo = $user_mod->getUserInfo($val['uid']);
			$list[$key]['nickname'] = $userinfo['nickname'];
			$list[$key]['userface'] = "http://".$_SERVER['SERVER_NAME'].$userinfo['userface_50_50'];
			$list[$key]['postdate'] = date("Y-m-d H:i:s",$val['postdate']);
			$list[$key]['if_child_reply'] = null;
		}
		return $list;
	}
	
	/**
	 * 记录同步到第三方的分享
	 * @param $shareid 分享ID
	 * @param $userid 用户ID
	 * @param $type 转发到的类型$type=1新浪 $type=2腾讯
	 */
	public function postUserShareOut($userid,$shareid,$type=1){
		$sina_data['shareid']=$shareid;
		$sina_data['outtype']=$type;
		
		$sina_data['userid']=$userid;
		if($type==1){
			$open_type="sina";
		}else{
			$open_type="qq";
		}
		$share_out_mod=M("UserShareOut");
		$if_shareout=$share_out_mod->where($sina_data)->find();
		if($if_shareout){
			return true;
		}
		$sina_data['openid']=D("UserOpenid")->getUserOpenid($userid,$open_type);
		$sina_data['posttime']=time();
		if($share_out_mod->add($sina_data)){
			//判断分享的产品是否在当前新品分享任务中
			$shareinfo=M("UserShare")->field("resourcetype,resourceid")->where("id=".$shareid." AND pick_status=1 AND status>0 AND sharetype=1")->find();
			if($shareinfo['resourcetype']==1 && !empty($shareinfo['resourceid'])){
				$if_task=D("Task")->inTaskByProductID($shareinfo['resourceid']);
			}
			if($type==1){
				//转发到新浪
				if($if_task) {
					D("UserCreditStat")->optCreditSet($userid,"task_share_transmit_sina");
				}
				else {
					D("UserCreditStat")->optCreditSet($userid,"sina_weibo_share");
				}
			}else if($type==2){
				//转发到QQ
				if($if_task) {
					D("UserCreditStat")->optCreditSet($userid,"task_share_transmit_qq");
				}
				else {
					D("UserCreditStat")->optCreditSet($userid,"qq_weibo_share");
				}
			}
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 更新分享转发出去的数量
	 * @param $shareid 分享ID
	 */
	public function updateShareOutNum($shareid){
		$shareout_mod=M("UserShareOut");
		$shareout_num=$shareout_mod->where("shareid=$shareid")->count();
		if($shareout_num===false){
			return false;
		}else{
			if($shareout_num>0){
				$data['outnum']=$shareout_num;
				$res=$this->where("id=$shareid")->save($data);
				if($res!==false){
					return $shareout_num;
				}else{
					return false;
				}			
			}else{
				return $shareout_num;
			}
		}
	}
	
	/**
	 * 对分享的时间等做精确处理，例如首页精选分享转播
	 * @param $content 分享内容
	 * @param $time 时间(时间戳形式)
	 */
	public function getShareShortContent($content,$posttime,$wordnum=25){
		$public_mod =D("Public");
		$currenttime =time();
		if($currenttime - $posttime > 3600*24){
			$posttime = floor(($currenttime - $posttime)/(3600*24))."天前";
		}elseif($currenttime - $posttime > 3600){
			$posttime = floor(($currenttime /3600- $posttime/3600))."小时前";
		}elseif($currenttime - $posttime > 60){
			$posttime = floor(($currenttime/60 - $posttime/60))."分钟前";
		}else{
			$posttime = $currenttime - $posttime ."秒前";
		}
		$arr["action_date"] = $posttime;
		if(mb_strlen($content,"utf8") >= $wordnum){
			$arr['content'] = msubstr($content,0,$wordnum);
			$arr['more']="查看更多";
		}else{
			$arr['content'] = $content;
			$arr['more']="";
		}
		$arr['content']=$public_mod->deleteContentSmilies($arr['content']);
		$arr['content'] = $public_mod->handleShareContent($arr['content']);
		return $arr;
	}
	
	/**
	 * 获取萝莉官网推荐的最新的一条分享
	 */
	public function getANewShareOfLolitabox(){
		$info=$this->field("id")->where("iscommend=1 AND userid=2375")->order("id DESC")->find();
		if($info){
			$shareinfo=$this->getShareInfo($info['id']);
			$return['nickname']=$shareinfo['nickname'];
			$return['spaceurl']=$shareinfo['spaceurl'];
			$return['shareurl']=$shareinfo['shareurl'];
			$return['content']=$shareinfo['content_a'];
			if($shareinfo['more']){
				$return['more']=$shareinfo['more'];
			}
		}else{
			$return="";
		}
		return $return;
	}
	
	
	/**
	 * 用户转发出去的分享【去重】
	 * @param  $userid 用户ID
	 * @param  $limit 限制条数
	 * @param  $order 排序
	 */
	public function getUserShareOut($userid,$limit="0,10",$order="status DESC,posttime DESC"){
		if(!$userid)
			return false;
		$shareout_mod=M("UserShareOut");
		$share_list=$shareout_mod->distinct(true)->field("shareid")->where("userid=$userid")->select();
		if($share_list){
			foreach($share_list as $key=>$val){
				$share_info=$this->getShareInfo($val['shareid']);
				$share_list[$key]=$share_info;
			}
		}
		return $share_list;
	}
	
	/**
	 * 用户转发出去的总数【去重】
	 */
	public function getUserShareOutNum($userid){
		if(!$userid)
			return false;		
		$shareout_mod=M("UserShareOut");
		$sharelist=$shareout_mod->distinct(true)->field("shareid")->where("userid=$userid")->select();
		return count($sharelist);
	}
	
	/**
	 * 通过盒子ID获取晒盒列表
	 * @param int $boxid
	 * @param int $limit
	 * @author litingting
	 */
	public function getOrderShowByBox($boxid='',$limit=10){
		if($boxid){
			$where['boxid']=$boxid;
		}else{
			$where['boxid']=array("gt",0);
		}
		$where['status'] = array("gt",0);
		$where['pick_status'] = 1;
		$where['sharetype'] = 1;
		return $this->getShareList($where,$limit,"status DESC");
	}
	
	
	/**
	 * 通过盒子ID获取晒盒总数
	 * @param int $boxid
	 * @param int $limit
	 * @author litingting
	 */
	public function getOrderShowNumByBox($boxid=''){
		if($boxid){
			$where['boxid']=$boxid;
		}else{
			$where['boxid']=array("gt",0);
		}
		$where['status'] = array("gt",0);
		$where['pick_status'] = 1;
		$where['sharetype'] = 1;
		return $this->where($where)->count();
	}
	
	
	
	/**
	 * 分享动作【包含踩和赞】
	 * @param int $userid
	 * @param int $shareid
	 * @param int $type 1--踩，2--赞
	 * @author litinging
	 */
	public function addShareAction($userid,$shareid,$type=1){
		$data['userid'] = $userid;
		$data['shareid'] = $shareid;
		$data['status'] = 1;
		$share_action =  M("UserShareAction");
		if(!$info=$share_action->where($data)->find()){
			
			$share_userid=M("UserShare")->where("id=".$shareid)->getField("userid");
			if($share_userid==$userid){
				return -3;//不能踩或赞 自己的 分享
			}
			
			$data['status'] = 1;
			$data['type'] = $type;
			$data['addtime'] = time();
			$flag= $this->changeActionShare($userid,$shareid,$type);
			
			if($flag){
				if($type==2){
				    D("UserCreditStat")->optCreditSet($userid,"user_share_agree");
				}

				$to_uid = $this->where("id=".$shareid)->getField("userid");
				$nickname = M("Users")->where("userid=".$userid)->getField("nickname");
				$msg_mod = D("Msg");
				if($type==1){
					$msg = "<a href='".getSpaceUrl($userid)."'  class='WB_info'>{$nickname}</a>不喜欢你的<a  href='".getShareUrl($shareid)."'  class='WB_info'>分享</a>";
				}else if($type==2){
					$msg = "<a href='".getSpaceUrl($userid)."'  class='WB_info'>{$nickname}</a>非常喜欢你的<a  href='".getShareUrl($shareid)."'   class='WB_info'>分享</a>";
				}
				$msg_mod ->addMsg(C("LOLITABOX_ID"),$to_uid,$msg);    //发私信
				
				if($type==1){
					$field="treadnum";
				}else{
					$field="agreenum";
				}
				$num=$share_action->where("shareid=$shareid AND status=1 AND type=$type")->count();
				$this->where("id=".$shareid)->setField($field,$num);
			}
			return 1;
		}else{
			if(	$info['type'] == 1 ){
				return -1;  //之前已踩过
			}else if($info['type'] == 2){
				return -2;  //之前已赞过
			}
			return 0;
		}
	}
	
	
	/**
	 * 踩的动作
	 * @param int $userid
	 * @param int $shareid
	 * @param int $type 1--踩，2--赞
	 * @author litinging
	 */
	public function ifActionShare($userid,$shareid,$type=1){
		$data['userid'] = $userid;
		$data['shareid'] = $shareid;
		$data['status'] = 1;
		$share_action =  M("UserShareAction");
	    if($info=$share_action->where($data)->find()){
	    	if(	$info['type'] == 1 ){
	    		return -1;  //之前已踩过
	    	}else if($info['type'] == 2){
	    		return -2;  //之前已赞过
	    	}
			return 0;//代表已经有过动作，不能重复
		}else{
			return 1;
		}
	}
	
	/**
	 * 改变分享动作状态
	 * @param int $userid
	 * @param int $shareid
	 * @param int $type
	 * @author litingting
	 */
	public function changeActionShare($userid,$shareid,$type){
		$time = time();
		$sql = "REPLACE INTO user_share_action(userid,shareid,type,status,addtime) values({$userid},{$shareid},{$type},1,{$time})";
		return $this->execute($sql);
	}
	
	
	/**
	 * 试用分享【如果是对产品发的分享，获取产品名称】
	 * @author penglele
	 */
	public function getShareListByTry($where="",$limit="",$order="status DESC,posttime DESC",$ifall=false,$wordnum=300){
		$where['sharetype']=1;
		$where['status']=array("exp",">0");
		$field = $ifall==false ? "id,userid,commentnum,agreenum,posttime,clienttype,sharetype,resourcetype,resourceid":"*" ;
		$list=$this->field($field)->where($where)->limit($limit)->order($order)->select();
		if($list){
			$pro_mod=D("Products");
			foreach($list as $key =>$val){
				$list[$key] = $this ->getShareInfo($val['id']);
				if($list[$key]['resourcetype']==1 && $list[$key]['resourceid']!=""){
					$pro_info=$pro_mod->getProductInfo($val['resourceid'],"pid,pname");
					$list[$key]['pname']=$pro_info['pname'];
					$list[$key]['producturl']=getProductUrl($val['resourceid']);
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户赞或踩的分享列表
	 * @param int $userid
	 * @param int $type [1-踩，2-赞]
	 * @param string $limit 分页
	 * @author litingting
	 */
	public function getShareListByAction($userid,$type=1,$limit=20,$order="addtime DESC"){
	    $where['userid'] = $userid;
	    $where['status'] = 1;
	    $where['type'] =$type;	
	    $list = M("UserShareAction")->where($where)->limit($limit)->order($order)->select();
	    foreach($list as $key =>$val){
	    	$list[$key] = $this->getShareInfo($val['shareid']);
;	    }
        return $list;
	}
	
	
	/**
	 * 获取用户赞或踩的分享总数
	 * @param int $userid
	 * @param int $type [1-踩，2-赞]
	 * @author litingting
	 */
	public function getShareNumByAction($userid,$type=1){
		$where['userid'] = $userid;
		$where['status'] = 1;
		$where['type'] =$type;
		return M("UserShareAction")->where($where)->count();
	}
	
	
	/**
	 * 通过分享ID获取赞或踩的用户
	 * @param int $shareid
	 * @param string $limit
	 * @author litingting
	 */
	public function getUserListByAction($shareid,$type=0,$limit=20){
		$where['shareid'] = $shareid;
		$where['status'] = 1;
		if($type){
			$where['type'] = $type;
		}
		$list = M("UserShareAction")->where($where)->limit($limit)->order("addtime DESC")->select();
		$user_mod = D("Users");
		foreach($list as $key =>$val){
			$list[$key]=$user_mod ->getUserInfo($val['userid']);
			$list[$key]['type'] = $val['type'];
			$list[$key]['spaceurl']=getSpaceUrl($val['userid']);
		}
		return $list;
	}
	
	
	/**
	 * 获取所有已收录试用产品分享
	 * @param unknown_type $limit
	 * @author litingting
	 */
	public function getAllPidShare($limit){
		$where['status'] = array("gt",0);
		$where['pick_status'] = 1;
		$where['resourcetype'] = 1;
		$where['sharetype'] = 1;
		$list = $this->getShareList($where,$limit);
		$products = M("Products");
		foreach($list as $key =>$val){
			$pinfo = $products ->where("pid=".$val['resourceid'])->find();
			$list[$key]['pname'] = $pinfo['pname'];
			$list[$key]['producturl'] = getProductUrl($pinfo['pid']);
			if(empty($val['img'])){
				$img = $pinfo['pimg'];
				$list[$key]['img'] = $img;
				list($img_w,$img_h) = getimagesize(".".$img);
				$list[$key]['img_w'] = 196;
				$list[$key]['img_h'] = ($img_h)*(196/$img_w);
				$list[$key]['shareurl'] = $list[$key]['producturl'];
			}
		}
		return $list;
	}
	
	/**
	 * 获取所有已收录试用分享总数
	 * @param unknown_type $limit
	 * @author litingting
	 */
	public function getAllPidShareNum(){
		$where['status'] = array("gt",0);
		$where['pick_status'] = 1;
		$where['resourcetype'] = 1;
		$where['sharetype'] = 1;
		return $this->where($where)->count();
	}
	
	
	
	
	
	/**
	 *获取相关资源类型的分享列表或分享数
	 *@param userid 用户ID
	 *@param resourcetype 资源类型
	 *@param $resourceid 资源ID
	 *@param $returnType 返回类型（"num"=>返回相同类型的分享数，"list"=>返回相同类型的分享记录集）
	 * 为了运营更好的对内容进行收录
	 * 显示同一类资源分享数及分享列表
	 * 对于某一条分享，无论是基于产品，基于订单（盒子），需要将同一ID的同一类型分享调取出来，供运营人员参照
	 * @author zhenghong 2013-08-24
	 */
	public function getShareListBySameType($userid,$resourcetype,$resourceid,$returnType="list"){
		$where["userid"]=$userid;
		$where['resourcetype'] = $resourcetype;
		$where['resourceid'] = $resourceid;
		if($returnType=="count") {
			//返回相同类型资源的分享总数
			if(!$resourceid) {
				return "0";
			}
			return $this->where($where)->count();
		}
		else {
			//返回分享记录列表
			$sharelist=$this->field("id")->where($where)->select();
			for($i=0;$i<count($sharelist);$i++){
				
				$sharelist[$i]=$this->getShareInfo($sharelist[$i]["id"]);
			}
			return $sharelist;
		}
	}
	
	/**
	 * 获取分享对象的名称
	 * @param $id 分享对象的id【1：product，4：order】
	 * @author penglele
	 */
	public function getShareToName($id,$type){
		$name="";
		if(!$id || !$type){
			return $name;
		}
		if($type==1){
			//对产品发的分享
			$proinfo=D("Products")->getProductInfo($id,"pname");
			$name=$proinfo['pname'];
		}elseif($type==4){
			//发表晒盒
			$orderinfo=D("UserOrder")->getOrderInfo($id,"boxid");
			$boxinfo=D("Box")->getBoxInfo($orderinfo['boxid']);
			$name=$boxinfo['name'];
		}
		return $name;
	}
	
	/**
	 * 通过年月获取用户的分享列表
	 * @param $sdate 时间【例：201310】
	 * @param $type 查询的类型【$type=1全部，$type=2对订单的分享，$type=3对产品的分享】
	 * @author penglele
	 */
	public function getShareListByDate($aid,$type=1,$limit=""){
		if($type>3){
			return "";
		}
		$box_mod=D("Box");
		$boxpro_mod=D("BoxProducts");
		$alist=D("Article")->getBoxInfoByArticleId($aid);
		$boxid_str=$alist['boxid_arr'];//盒子ID集合
		$pid_arr=$alist['pid_arr'];//产品id集合
		if($type==1){
			$where="((resourcetype=1 AND resourceid IN ($pid_arr)) OR (resourcetype=4 AND boxid IN ($boxid_str)))";
		}else if($type==2){
			$where="resourcetype=4 AND boxid IN ($boxid_str)";
		}else if($type==3){
			$where="resourcetype=1 AND resourceid IN ($pid_arr)";
		}
		$limit = $limit ? "LIMIT $limit" : "" ;
		$sql="SELECT id FROM user_share WHERE $where AND status>0 AND sharetype=1 ORDER BY status DESC,pick_status DESC,id DESC $limit";
		$query=$this->query($sql);
		$list=array();
		foreach($query as $val){
			$list[]=$this->getShareInfo($val['id']);
		}
		return $list;
	}
	
	/**
	 * 通过年月获取用户的分享总数
	 * @param $sdate 时间【例：201310】
	 * @param $type 查询的类型【$type=1全部，$type=2对订单的分享，$type=3对产品的分享】
	 * @author penglele
	 */
	public function getShareCountByDate($aid,$type=1){
		if($type>3){
			return 0;
		}
		$article_info=D("Article")->getBoxInfoByArticleId($aid);
		$boxid_str=$article_info['boxid_arr'];//盒子ID集合
		$pid_arr=$article_info['pid_arr'];//产品id集合
		if($type==1){
			//全部
			$where="((resourcetype=1 AND resourceid IN ($pid_arr)) OR (resourcetype=4 AND boxid IN ($boxid_str)))";
		}else if($type==2){
			//晒盒
			$where="resourcetype=4 AND boxid IN ($boxid_str)";
		}else if($type==3){
			//产品
			$where="resourcetype=1 AND resourceid IN ($pid_arr)";
		}
		$sql="SELECT COUNT(id) as num FROM user_share WHERE $where AND status>0 AND sharetype=1";
		$query=$this->query($sql);
		return (int)$query[0]['num'];
	}	
	
	/**
	 * 获取用户转发的分享列表
	 * @author penglele
	 */
	public function getUserShareOutShareList($userid,$limit=""){
		$list="";
		$where['userid'] = $userid;
		$list = M("UserShareOut")->field("shareid")->distinct(true)->where($where)->limit($limit)->order("posttime DESC")->select();
// 		echo M("UserShareOut")->getLastSql();exit;
		foreach($list as $key =>$val){
			$list[$key] = $this->getShareInfo($val['shareid']);
		}
		return $list;
	}
	
	/**
	 * 获取用户转发的分享总数
	 * @author penglele
	 */
	public function getUserShareOutShareNum($userid){
		$num=0;
		if($userid){
			$where['userid'] = $userid;
			$list = M("UserShareOut")->field("shareid")->distinct(true)->where($where)->select();
			$num=count($list);
		}
		return $num;
	}	
	
	/**
	 * 获取指定分享ID的列表
	 * @param array shareid_list 指定分享的ID列表
	 * @return array list 分享数组
	 * @author zhenghong
	 */
	public function getShareListByIdList($shareid_list){
		$id_condition=implode(",",$shareid_list);
		$sql="SELECT s.id FROM user_share s WHERE id IN ($id_condition)  ORDER BY s.status DESC,s.id DESC";
		$query=$this->query($sql);
		$list=array();
		if($query){
			foreach($query as $key=>$val){
				$shareinfo=$this->getShareInfo($val['id'],100);
				$list[]=$shareinfo;
			}
		}
		return $list;
	}
	
}