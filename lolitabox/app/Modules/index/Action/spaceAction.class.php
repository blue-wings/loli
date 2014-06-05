<?php
/**
 * 他的个人中心控制器
* @author litingting
*
*/
class spaceAction extends commonAction{
	
	
	public $space_userid;
	
	/**
	 * 个人中心--首页
	 */
	function _initialize() {
		header ( "Content-Type:text/html; charset=utf-8" );
		$this->page_module_name =  MODULE_NAME;
		
		if($this->checkBlacklist()) {
			exit("ERROR 501: 浏览器发生故障，请与网站管理员联系！");
		}
		
		$this->userid=$this->getUserid();
		$u_info=D("Users")->getUserInfo($this->userid);
		$this->userinfo=$u_info;
		if($this->userid){
			$bind=D("UserOpenid")->getBindDetail($this->userid);
			$userface=$u_info['userface_40_40'];
			$member=D("Member")->getUserIfMember($this->userid);
			$this->userinfo['if_member']=$member;
			$this->assign("member",$member);
			$this->assign("bind",$bind);
			$this->assign("userface",$userface);
		}
		
		$action_name = ACTION_NAME;
		if(!method_exists($this, $action_name)){
			$info = encodeShortUrl($action_name);
			$action_name = $info['url'];
			$_REQUEST['userid'] = $info['userid'];
		}
		
		$this->space_userid = $_REQUEST['userid'];
		if((empty($this->space_userid ) || $this->space_userid==$this->userid) && $action_name =="index" ){
			header("location:".U("home/index"));   //如果没有用户ID，则直接跳转到我的首页
		}
		
		if($action_name !=ACTION_NAME){          //如果不相等，刚代表是短链接
			$this->assign("action_name",$action_name);
			$this->$action_name();
			exit;
		}
		
	}
	
	
	/**
	 * 他的个人首页，
	 * 包含他的分享，他踩的分享，他赞的分享,他试用的产品
	 * @author litingting
	 */
	public function index(){
		$userid = $this->space_userid;
		$ac = trim($this->_get("ac")) ? trim($this->_get("ac")):1;
		$type= trim($this->_get("type")) ? trim($this->_get("type")):0;
		$option = trim($this->_get("option"));
		$pagesize =  20;
		$user_share = D("UserShare");
		$follow = D("Follow");
		$products = D("Products");
		switch($ac){
			case 2:     //他赞的分享
				$template = "home:share_waterfall_ajaxlist";
				$list = $user_share ->getShareListByAction($userid,2,$this->getlimit($pagesize));
				$count = $user_share ->getShareNumByAction($userid,2);
				break;
			case 3:     //他踩的分享
				$template = "home:share_waterfall_ajaxlist";
				$list = $user_share ->getShareListByAction($userid,1,$this->getlimit($pagesize));
				$count = $user_share ->getShareNumByAction($userid,1);
				break;
			case 4:      //他试用的产品
				$template = "space:products_ajaxlist";
				$list = $products->getUserOrderProductsList($userid,$this->getlimit($pagesize));
				$count = $products ->getUserOrderProductsCount($userid);
				break;
			case 5:      //他关注的品牌
				$template = "space:brand_ajaxlist";
				$list = $follow->getFollowListByUserid($userid,3,$this->getlimit($pagesize));
				$count = $follow->getFollowNumByUserid($userid,3);
				break;
			case 6:      //他转发的分享
				$template = "home:share_waterfall_ajaxlist";
				$list = $user_share->getUserShareOutShareList($userid,$this->getlimit($pagesize));
				$count = $user_share->getUserShareOutShareNum($userid);
				break;				
			default:    //全部分享
				$template = "space:index_ajaxlist";
				if($type==2){
					$resourcetype=4;
				}else if($type==3){
					$resourcetype=1;
				}else{
					$resourcetype=0;
				}
				$list = $user_share->getMyShareList($userid,$resourcetype,$this->getlimit($pagesize));
				$count = $user_share->getMyShareNum($userid,$resourcetype);
				break;
		}
		$param = array(
				"total" =>$count,
				'result'=>$list	,		//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>$template,//ajax更新模板
				"parameter" => "ac={$ac}&type=".$type,
				);
		$this->page($param);
		$return['u_info'] = D("Users") ->getUserInfo($userid);
		$return['u_info']['brand_follow'] = D("Follow")->getFollowNumByUserid($userid,3);
		$return['title'] = $return['u_info']['nickname']."--个人主页";
		$return['url'] = getSpaceUrl($userid);
		$return['agree_num'] = $user_share ->getShareNumByAction($userid,2);
		$return['tread_num'] = $user_share ->getShareNumByAction($userid,1);
		$return['pro_num'] = $products ->getUserOrderProductsCount($userid);
		$return['shareout_num'] = $user_share->getUserShareOutShareNum($userid);
		$return['title'] = $return["u_info"]['nickname']."的个人中心-".C("SITE_NAME");
		$this->assign("template",$template);
		$this->assign("return",$return);
		$this->display("index");
	}
	
	
	
	/**
	 * ajax获取赞踩用户数据
	 * @param int $shareid 分享ID
	 * @param int $type [0-全部，1-踩，2-赞]
	 * @author litingting
	 */
	public function share_action(){
		$shareid = $this->_get("id");
		$type = $_GET["action"]?$_GET["action"]:0;
		if(empty($shareid)){
			echo "";
		    exit;
		}
		$return['user_list'] =  D("UserShare")->getUserListByAction($shareid,$type);
		$this->assign("return",$return);
		echo $this->fetch();
	}

	
	
	
}