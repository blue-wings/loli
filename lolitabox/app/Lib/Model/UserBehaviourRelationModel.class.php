<?php
//用户行为关系类
class UserBehaviourRelationModel extends Model {

	var $user_action_type=array(
	//"collect_pid", //关注的人收藏了商品
	"follow_uid", //关注的人关注了别人
	//"buy_boxid", //关注的人购买了萝莉盒
	//"post_evaluateid", //关注的人发表了商品评论
	//"reply_replyid", //关注的人回复了商品评测
	//"post_blogid",//关注的人发表了日志
	"bound_sina", //关注的人绑定了新浪微博
	"post_shareid",   //关注的人发布分享
	//"post_commentid",   //关注的人发表评论
	"follow_brandid",
	"follow_pid",
	"collect_shareid",
	);

	//增加用户行为数据
	public function addData($userid,$usertype=1,$whoid,$type){
		$data['userid'] = $userid;
		$data['usertype'] = $usertype ==4 ? 1: $usertype;
		$data['whoid'] = $whoid;
		$data['type'] = $type;
		if($this->where($data)->find()){
			return false;
		}
		$data['addtime']=time();
		$data['status'] = 0;
		return $this->add($data);
	}

	/**
	 * 删除动态
	 * @param unknown_type $userid
	 * @param unknown_type $usertype
	 * @param unknown_type $whoid
	 * @param unknown_type $type
	 */
	public function del($userid,$usertype,$whoid,$type){
		$data['userid'] = $userid;
		$data['usertype'] = $usertype==4 ? 1:$usertype;
		$data['whoid'] = $whoid;
		$data['type'] = $type;
		return $this->where($data)->delete();
	}

	/**
	 * 删除收录动态
	 * @param unknown_type $userid
	 * @param unknown_type $usertype
	 * @param unknown_type $whoid
	 */
	public function delCollect($userid,$usertype,$whoid){
		return $this->del($userid,$usertype,$whoid,'collect_shareid');
	}

	/**
	 * 根据分享ID号删除所有收录动态
	 * @param unknown_type $shareid
	 */
	public function delCollectByShareid($shareid){
		$this ->where("whoid=".$shareid." AND type='collect_shareid'")->delete();
	}

	/**
	 * 删除发分享动态
	 * @param unknown_type $userid
	 * @param unknown_type $whoid
	 */
	public function delPostShare($userid,$whoid){
		$this->del($userid,1,$whoid,'post_shareid');
	}


	/**
	 * 删除评论动态
	 * @param unknown_type $userid
	 * @param unknown_type $whoid
	 */
	public function delComment($userid,$whoid){
		$this->del($userid,1,$whoid,'post_commentid');
	}

	/**
     * 获取我关注用户动态列表
     * @param unknown_type $userid
     * @param string $limit
     */
	public function getMyFollowDynamicList($userid,$limit="0,10"){
		$action_type =array(   
					"follow_uid", //关注的人关注了别人
	                "bound_sina", //关注的人绑定了新浪微博
	                "follow_brandid",
                 	"follow_pid",
	    );
		$type_list = implode("','",$action_type);
		$sql = "SELECT u.* FROM user_behaviour_relation u,follow f WHERE u.userid=f.whoid AND u.usertype=f.type AND u.type IN('".$type_list."') AND u.addtime >".(time()-60*60*24*30)." AND f.userid=".$userid." ORDER BY addtime DESC LIMIT ".$limit;
		$dynamic_list = $this->query($sql);
		for($i=0;$i<count($dynamic_list);$i++){
			$dynamic_list[$i]["action_date"]=date("Y-m-d H:i",$dynamic_list[$i]["addtime"]);
			$dynamic_list[$i]["info"]=$this->getDynamicDescription($dynamic_list[$i]["userid"],$dynamic_list[$i]["type"],$dynamic_list[$i]["whoid"],$dynamic_list[$i]["addtime"],$dynamic_list[$i]["usertype"]);
		}
		return $dynamic_list;
	}
    
	
	/**
	 * 获取我关注用户分享动态列表
	 * @param unknown_type $userid
	 * @param unknown_type $limit
	 */
	public function getMyFollowShareDynamicList($userid,$limit="0,5"){
		$type_list = "'post_shareid','collect_shareid'";
		$sql = "SELECT u.* FROM user_behaviour_relation u,follow f WHERE u.userid=f.whoid AND u.usertype=f.type AND u.type IN(".$type_list.") AND u.addtime >".(time()-60*60*24*30)." AND f.userid=".$userid." ORDER BY addtime DESC LIMIT ".$limit;
		$dynamic_list = $this->query($sql);
		for($i=0;$i<count($dynamic_list);$i++){
			$dynamic_list[$i]["action_date"]=date("Y-m-d H:i",$dynamic_list[$i]["addtime"]);
			$dynamic_list[$i]["info"]=$this->getDynamicDescription($dynamic_list[$i]["userid"],$dynamic_list[$i]["type"],$dynamic_list[$i]["whoid"],$dynamic_list[$i]["addtime"],$dynamic_list[$i]["usertype"]);
			if($userid==$dynamic_list[$i]["userid"]){
				$dynamic_list[$i]["info"]['del']="删除";
			}
		}
		return $dynamic_list;
	}
	
	/**
	 * 获取我关注的用户动态的总数
	 * @param $userid 用户ID
	 * @return $dynamic_list_count 动态总数
	 */
	public function getMyFollowDynamicCount($userid){
		$action_type =array(
				"follow_uid", //关注的人关注了别人
				"bound_sina", //关注的人绑定了新浪微博
				"follow_brandid",
				"follow_pid",
		);
		$type_list = implode("','",$action_type);
		$sql = "SELECT COUNT(u.userid) AS T FROM user_behaviour_relation u,follow f WHERE u.userid=f.whoid AND u.usertype=f.type AND u.type IN('".$type_list."') AND u.addtime >".(time()-60*60*24*30)." AND f.userid=".$userid;
		$dynamic_list = $this->query($sql);
		return $dynamic_list[0]['T'];
	}
    
	
	/**
	 * 获取我关注的用户动态的总数
	 * @param $userid 用户ID
	 * @return $dynamic_list_count 动态总数
	 */
	public function getMyFollowShareDynamicCount($userid){
		$type_list = "'post_shareid','collect_shareid'";
		$sql = "SELECT COUNT(u.userid) AS T FROM user_behaviour_relation u,follow f WHERE u.userid=f.whoid AND u.usertype=f.type AND u.type IN(".$type_list.") AND u.addtime >'".(time()-60*60*24*30)."' AND f.userid=".$userid;
		$dynamic_list = $this->query($sql);
		return $dynamic_list[0]['T'];
	}
	
    
	/**
	 * 获取某用户的动态数据
	 * @param string $limit
	 */
	public function getUserDynamicList($userid,$limit="0,10"){
		$where['userid'] = $userid;
		$where['usertype'] = 1;
		$where["type"]=array("IN",$this->user_action_type);
		$where["addtime"]=array("gt",time()-60*60*24*30); //查询一个月内的动态信息
		$dynamic_list=$this->where($where)->limit($limit)->order("addtime DESC")->select();
		for($i=0;$i<count($dynamic_list);$i++){
			$dynamic_list[$i]["action_date"]=date("Y-m-d H:i",$dynamic_list[$i]["addtime"]);
			$dynamic_list[$i]["info"]=$this->getDynamicDescription($dynamic_list[$i]["userid"],$dynamic_list[$i]["type"],$dynamic_list[$i]["whoid"],$dynamic_list[$i]["addtime"],$dynamic_list[$i]["usertype"]);
		}
		return $dynamic_list;
	}

	/**
	 * 获取某用户的动态数据总数
	 * @param $userid 用户ID
	 * @return $dynamic_list_count 动态总数
	 */
	public function getUserDynamicCount($userid){
		$where['userid'] = $userid;
		$where['usertype'] = 1;
		$where["type"]=array("IN",$this->user_action_type);
		$where["addtime"]=array("gt",time()-60*60*24*30); //查询一个月内的动态信息
		$dynamic_list_count=$this->where($where)->count();
		return $dynamic_list_count;
	}

	/**
	 * 获取分享动态列表
	 * @param string $limit
	 * @author litingting
	 */
	public function getAllShareDynamicList($limit="30"){
		$where['type']= "post_shareid";
		$dynamic_list = $this ->where($where)->order("addtime DESC")->limit($limit)->select();
		foreach($dynamic_list as $key =>$val){
			$posttime=$dynamic_list[$key]["addtime"];
			$currenttime =time();
			if($currenttime - $posttime > 3600*24){
				$posttime = floor(($currenttime - $posttime)/3600*24)."天前";
			}elseif($currenttime - $posttime > 3600){
				$posttime = floor(($currenttime - $posttime)/3600)."小时前";
			}elseif($currenttime - $posttime > 60){
				$posttime = floor(($currenttime - $posttime)/60)."分钟前";
			}else{
				$posttime = $currenttime - $posttime ."秒前";
			}
			$dynamic_list[$key]["action_date"] = $posttime;
			$dynamic_list[$key]["info"]=$this->getDynamicDescription($dynamic_list[$key]["userid"],$dynamic_list[$key]["type"],$dynamic_list[$key]["whoid"],$dynamic_list[$key]["addtime"]);
		}
		return $dynamic_list;
	}

	/**
	 * 根据用户行为表的记录中UID,TYPE,WHOID，返回相关事件的描述 信息
	 * @param unknown_type $uid
	 * @param unknown_type $type
	 * @param unknown_type $whoid
	 * @param unknown_type $addtime
	 * @return string $content
	 */
	public function getDynamicDescription($uid,$type,$whoid,$addtime,$usertype=""){
		if(empty($type)) return false;
		if(!in_array($type,$this->user_action_type)) return false;
		//处理分支
		switch($type){
			case "collect_pid":
				return $this->getCollectPidContent($uid,$whoid);
				break;

			case "follow_uid":
				return $this->getFollowUidContent($uid,$whoid);
				break;

			case "follow_brandid":
				return $this->getFollowBrandContent($uid,$whoid);
				break;

			case "follow_pid":
				return $this->getFollowProductContent($uid,$whoid);
				break;


			case "buy_boxid":
				return $this->getBuyBoxidContent($uid,$whoid);
				break;

			case "post_evaluateid":
				return $this->getPostEvaluateidContent($uid,$whoid);
				break;

			case "reply_replyid":
				return $this->getReplyidContent($uid,$whoid);
				break;

			case "bound_sina":
				return $this->getBoundSinaContent($uid);
				break;

			case "post_blogid":
				return $this->getBlogContent($uid,$whoid);
				break;

			case "post_shareid":
				return $this->getShareContent($uid,$whoid);
				break;

			case "post_commentid":
				return $this->getCommentContent($uid,$whoid);
				break;
			case "collect_shareid":
				return $this ->getCollectShareContent($uid,$whoid,$usertype);
				break;

		}
	}

	/**
	 * 获取绑定新浪微博账号的动态信息
	 * @param unknown_type $userid
	 * @return array $array_content
	 */
	public function getBoundSinaContent($userid){
		if(!$userid){
			return false;
		}
		else {
			$array_content=array();
			$userinfo=D("Users")->getUserInfo($userid);
			$array_content['userid'] = $userinfo['userid'];
			$array_content["userface"]=$userinfo["userface_50_50"];
			$array_content["nickname"]=$userinfo["nickname"];
			$array_content["spaceurl"]=getSpaceUrl($userid);
			$array_content["faceurl"]=getSpaceUrl($userid);
			$array_content ["facename"] = $userinfo ["nickname"];
			$array_content["type_name"]="绑定了新浪微博";
			$array_content["to_name"]="";
			$array_content["to_url"]="";
			$array_content["to_img"]="";
			return($array_content);
		}
	}



	/**
	 * 获取关注动态信息
	 * @param unknown_type $userid
	 * @param unknown_type $touid
	 * @return array $array_content
	 */
	public function getFollowUidContent($userid,$touid){
		if(!$userid || !$touid){
			return false;
		}
		else {
			$array_content=array();
			$userinfo=D("Users")->getUserInfo($userid);
			$array_content['userid'] = $userinfo['userid'];
			$array_content["userface"]=$userinfo["userface_50_50"];
			$array_content["nickname"]=$userinfo["nickname"];
			$array_content["spaceurl"]=getSpaceUrl($userid);
			$array_content["faceurl"]=getSpaceUrl($userid);
			$array_content ["facename"] = $userinfo ["nickname"];
			unset($userinfo);
			$userinfo=D("Users")->getUserInfo($touid);
			$array_content["to_name"]=$userinfo["nickname"];
			if($userinfo['is_solution']){
				$array_content["to_url"]=getSolutionUrl($touid);
				$array_content["type_name"]="关注了";
			}else{
				$array_content["to_url"]=getSpaceUrl($touid);
				$array_content["type_name"]="关注了";
			}
			$array_content['user_type']=1;
			$array_content["to_img"]=$userinfo["userface_50_50"];
			unset($userinfo);
			return($array_content);
		}
	}



	/**
	 * 获取用户购买盒子的动态信息
	 * @param unknown_type $userid
	 * @param unknown_type $touid
	 * @return array $array_content
	 */
	public function getBuyBoxidContent($userid,$touid){
		if(!$userid || !$touid){
			return false;
		}
		else {
			$array_content=array();
			$userinfo=D("Users")->getUserInfo($userid);
			$array_content['userid'] = $userinfo['userid'];
			$array_content["userface"]=$userinfo["userface_50_50"];
			$array_content["nickname"]=$userinfo["nickname"];
			$array_content["spaceurl"]=getSpaceUrl($userid);
			$array_content["faceurl"]=getSpaceUrl($userid);
			$array_content ["facename"] = $userinfo ["nickname"];
			unset($userinfo);
			$array_content["type_name"]="购买了盒子";
			$boxinfo=M("Box")->getByBoxid($touid);
			$array_content["to_name"]=$boxinfo["name"];
			$array_content["to_url"]="/buy/goods_select.html";
			$array_content["to_img"]=$boxinfo["pic"];
			unset($boxinfo);
			return($array_content);
		}
	}

	/**
	 * 获取关注单品动态信息
	 * @param unknown_type $userid
	 * @param unknown_type $touid
	 * @return array $array_content
	 */
	public function getFollowProductContent($userid,$touid){
		if(!$userid || !$touid){
			return false;
		}
		else {
			$array_content=array();
			$userinfo=D("Users")->getUserInfo($userid);
			$array_content['userid'] = $userinfo['userid'];
			$array_content["userface"]=$userinfo["userface_50_50"];
			$array_content["nickname"]=$userinfo["nickname"];
			$array_content["spaceurl"]=getSpaceUrl($userid);
			$array_content["faceurl"]=getSpaceUrl($userid);
			$array_content ["facename"] = $userinfo ["nickname"];
			unset($userinfo);
			$array_content["type_name"]="关注了";
			$productsinfo=M("Products")->getByPid($touid);
			$array_content["to_name"]=$productsinfo["pname"];
			$array_content["to_url"]=getProductUrl($touid);
			$array_content["to_img"]=$productsinfo["pimg"];
			$array_content["user_type"]=2;
			unset($productsinfo);
			return($array_content);
		}
	}

	/**
	 * 获取关注单品动态信息
	 * @param unknown_type $userid
	 * @param unknown_type $touid
	 * @return array $array_content
	 */
	public function getFollowBrandContent($userid,$touid){
		if(!$userid || !$touid){
			return false;
		}
		else {
			$array_content=array();
			$userinfo=D("Users")->getUserInfo($userid);
			$array_content['userid'] = $userinfo['userid'];
			$array_content["userface"]=$userinfo["userface_50_50"];
			$array_content["nickname"]=$userinfo["nickname"];
			$array_content["spaceurl"]=getSpaceUrl($userid);
			$array_content["faceurl"]=getSpaceUrl($userid);
			$array_content ["facename"] = $userinfo ["nickname"];
			unset($userinfo);
			$array_content["type_name"]="关注了";
			$brand_info=M("ProductsBrand")->getById($touid);
			$array_content["to_name"]=$brand_info["name"];
			$array_content["to_url"]=getBrandUrl($touid);
			$array_content["to_img"]=$brand_info["logo_url"];
			$array_content["user_type"]=3;
			unset($brand_info);
			return($array_content);
		}
	}





	/**
	 * 获取用户发表日志的动态信息
	 */
	public function getShareContent($userid,$shareid){
		if (! $userid || ! $shareid) {
			return false;
		}
		$array_content = array ();
		$userinfo = D ( "Users" )->getUserInfo ( $userid );
		$array_content ["userface"] = $userinfo ["userface_50_50"];
		$array_content ["nickname"] = $userinfo ["nickname"];
		$array_content ["spaceurl"] = getSpaceUrl ( $userid );
		$array_content ["faceurl"] = getSpaceUrl ( $userid );
		$array_content ["facename"] = $userinfo ["nickname"];
		//如果当前用户为解决方案，则为加V用户,if_super=2加V，if_super=1达人，if_super=0普通用户
		if($userinfo['is_solution']==1){
			$array_content['if_super']=2;
		}else{
			$array_content['if_super']=$userinfo['if_super'];
		}
		unset ( $userinfo );
		$share_info = D ( "UserShare" )->getShareInfo ($shareid);
		$array_content ["userid"]=$share_info['userid'];
		if (! $share_info || $share_info['status']==0) {
			$array_content ["to_name"] = "该分享己被删除";
			$array_content ["to_url"] = "";
			$array_content ["to_img"] = "";
			return $array_content;
		}else{
			if($share_info['sharetype'] ==1){
				$array_content["type_name"]="分享了";
			}else{
				$array_content["type_name"]="赞了分享";
			}

			$array_content["to_name"] = $share_info['content_a'];
			$array_content['more'] = $share_info['more'];
			$array_content['posttime'] = date("Y-m-d H:i",$share_info['posttime']);
			$array_content['agreenum'] = $share_info['agreenum'];
			$array_content['commentnum'] = $share_info['commentnum'];
			$array_content['outnum']=$share_info['outnum'];
			if($share_info ['sharedata']){
				$is_del=M("UserShare")->where("id=".$share_info['rootid'])->field("status,outnum")->find();
				if($is_del['status']==0){
					$sharedata['is_del']="oh，my god，小萝莉感到很抱歉，内容被原作者删除哩~";
				}else{
					//原分享没有被删除 start--------------
					$sharedata['outnum']=$is_del['outnum'];
					$sharedata= $share_info['sharedata'];
					//原分享没有被删除 end-----------------
				}
			}
			$array_content ['tag'] = $share_info['tag'];
			$array_content ['to_data'] = $sharedata;
			$array_content ["to_url"] = getShareUrl($shareid,$userid);
			$array_content ["to_img"]=$share_info['img'];
			$array_content ["to_img_big"]=$share_info['img_big'];
		}
		return ($array_content);
	}


	/**
	 * 获取评论信息
	 * @param unknown_type $userid
	 * @param unknown_type $commentid
	 */
	public function getCommentContent($userid,$commentid){
		if (! $userid || ! $commentid) {
			return false;
		}
		$array_content = array ();
		$userinfo = D ( "Users" )->getUserInfo ( $userid );
		$array_content ["userface"] = $userinfo ["userface_50_50"];
		$array_content ["nickname"] = $userinfo ["nickname"];
		$array_content ["facename"] = $userinfo ["facename"];
		$array_content ["spaceurl"] = getSpaceUrl ( $userid );
		unset ( $userinfo );

		$comment_info = D ( "UserShare" )->getCommentInfo ($commentid);
		$array_content["type_name"]="评论了";
		if (! $comment_info) {
			$array_content ["to_name"] = "该分享己被删除";
			$array_content ["to_url"] = "";
			$array_content ["to_img"] = "";
			return $array_content;
		}else{
			import("ORG.Util.String");
			$array_content["to_name"] = $comment_info['content'] ;
			if(mb_strlen($comment_info['content']) > 100)
			$array_content["to_name"]=String::msubstr($comment_info['content'] ,0,100);
			$array_content ["to_content"] = $comment_info['to_content'];
			$array_content ['to_url'] = getShareUrl($comment_info['shareid']);
			$array_content ["to_img"] =  "";
		}
		return ($array_content);
	}

	/**
	 * 获取某品牌|解决方案|产品的分享详情
	 * @param int $userid
	 * @param int $whoid
	 * @param int $usertype
	 */
	public function getCollectShareContent($userid,$whoid,$usertype){
		if (empty($userid) || empty($whoid) || empty($usertype)) {
			return false;
		}
		$content = array();
		$users_mod = D("Users");
		if($usertype==1 || $usertype==4){
			$u_info = $users_mod -> getUserInfo($userid,"userface,nickname");
			$content['userface'] = $u_info['userface_50_50'];
			$content['facename'] = $u_info['nickname'];
			$content['faceurl'] = getSolutionUrl($userid);
		}elseif($usertype==2){
			$p_info = M("Products")->getByPid($userid);
			$content['userface'] = $p_info['pimg'];
			$content['facename'] = $p_info['pname'];
			$content['faceurl'] = getProductUrl($userid);
		}elseif($usertype==3){
			$b_info = M("ProductsBrand")->getById($userid);
			$content['userface'] = $b_info['logo_url'];
			$content['facename'] = $b_info['name'];
			$content['faceurl'] = getBrandUrl($userid);
		}

		$data = D("UserShare") ->getShareInfo($whoid);
		$content['userid'] = $data['userid'];
		if($data){
			$userinfo = $users_mod->getUserInfo($data['userid'],"nickname");
			$content['nickname'] = $userinfo['nickname'];
			$content['spaceurl'] = getSpaceUrl($data['userid']);
			$content['more'] = $data['more'];
			$content['type_name'] ="";
			$content['to_name'] = $data['content_a'];
			$content['posttime'] = date("Y-m-d H:i",$data['posttime']);
			$content['agreenum'] = $data['agreenum'];
			$content['commentnum'] = $data['commentnum'];
			$content['outnum'] = $data['outnum'];
			$content['to_url'] = getShareUrl($whoid);
			$content['to_img'] = $data['img'];
			$content ["to_img_big"]=$data['img_big'];
		}else{
			$data = M("UserShare")->getById($whoid);
			$data['to_name'] = "该分享己被删除";
			$data['to_url'] = "";
			$data['to_img'] = "";
			$userinfo = $users_mod->getUserInfo($data['userid'],"nickname");
			$content['userid'] = $data['userid'];
			$content['nickname'] = $userinfo['nickname'];
			$content['spaceurl'] = getSpaceUrl($data['userid']);
		}
		return $content;
	}

	//判断用户是否有v4login
	function returnBehaviourV4loginData($userid){
		if(empty($userid)){
			return;
		}else{
			$where=array(
			'userid'=>$userid,
			'type'=>'v4login'
			);
			return  $this->where($where)->find();
		}
	}
}