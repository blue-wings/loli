<?php
/**
 * 公共控制器
 * @author litingting
 *
 */
class commonAction extends Action{
     

	
	public $userid, $username, $userinfo;
	
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
			$score = M("UserScore")->where(array("userid"=>$this->userid))->getField("score");
        	$this->userinfo["score"] = $score;
            $subscribes = M("UsersProductsCategorySubscribe")->where(array("user_id"=>$this->userid))->select();
            if(!$subscribes){
                $this->assign("gotoSubscribeCategory",true);
            }
		}
		//当前页面的url
		if(!(MODULE_NAME=="user" && in_array(ACTION_NAME,array("reglogin","logout")))){
			$thirdurl="http://".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'];
			$thirdurl=urlencode($thirdurl);
			$this->assign("thirdurl",$thirdurl);
		}


        $notMustLoginModule = array("Index","user");
		if(!in_array(MODULE_NAME, $notMustLoginModule)){
			
			if(empty($this->userid)){
				$unlogin_page = array("subscribe_theirs","public_verify","public_CheckVerify");     //不需要登录的页面
				$current_page = MODULE_NAME."_".ACTION_NAME;
				if(!in_array($current_page, $unlogin_page)){    //如果此页面不需要则跳过，反之则header到首页
					header("location:".U('user/login'));    //未登录则跳转到登录页
				}
			}
		}
		$module_arr=array("buy","try","loli","brand");
		if(in_array(MODULE_NAME,$module_arr) && ACTION_NAME=="index"){
			$article_mod=D("Article");
			$ad=$article_mod->getADList(MODULE_NAME);
			$this->assign("ad",$ad);
		}
		

		
		
	
	}
	
	/**
	 * 检查黑名单
	 * 如果用户已经登录，则判断用户是否在黑名单中
	 * 检查用户IP地址是否在黑名单中
	 * @return boolean true/false
	 * true表示用户在黑名单,false表示用户不在黑名单
	 */
	public function checkBlacklist(){
		$userid=$this->getUserid(); //USERID
		$ipaddress=get_client_ip(); //用户IP地址
		$ip_blacklist_mod=M("IpBlacklist");
		if($ip_blacklist_mod->getByIp($ipaddress)){
			//查到IP在黑名单中
			return true;
		}
		if($userid) {
			$user_blacklist_mod=M("user_blacklist");
			if($user_blacklist_mod->getByUserid($userid)){
				//查到用户ID在黑名单中
				return true;
			}
		}
		return false;
	}
 

    /**
     * 判断用户登录状态
     * ription 在所在需要必须登录注册的ACTION中调用此方法
     * @return boolean true/false 如果用户已经登录 返回true,否则返回false
     * @author zhenghong
     */
    public function check_login() {
    	if (! cookie ( "userauth" )) {
    		return false;
    	} else {
    		$username = cookie ( "username" );
    		$nickname = cookie ( "nickname" );
    		$userid = cookie ( "userid" );
    		$c_userauth = authcode ( cookie ( "userauth" ), "DECODE", C ( 'COOKIE_AUTHKEY' ) );
    		$array_userauth = explode ( C ( 'COOKIE_AUTHKEY_SPLIT' ), $c_userauth );
    		//$username == $array_userauth [0] && 【暂时删除】
    		if ($nickname == $array_userauth [1] && $userid == $array_userauth [2]) {
    			return true;
    		} else {
    			return false;
    		}
    	}
    }
    
    /**
     *  获取登录用户ID
     *  update by penglele 2013-08-06
     */
    public function getUserid() {
    	return cookie ( "userid" );
    }
    
    /**
     * 获取登录用户呢称
     *  update by penglele 2013-08-06
     */
    public function getNickname() {
    	return cookie ( "nickname" );
    }
    
    /**
     * 获取登录用户名
     *  update by penglele 2013-08-06
     */
    public function getUsername() {
    	return cookie ( "username" );
    }
    
    /**
     * 通过分页参数获取limit数据
     * @param int $offset  表示一页显示多少条
     * @return string
     * update by penglele 2013-8-6
     */
    public function getlimit($offset=10){
    	$p=C("VAR_PAGE");
    	if($_REQUEST[$p])
    		$firstrow = ($_REQUEST[$p]-1) * $offset;
    	else
    		$firstrow =0;
    	return $firstrow.",".$offset;
    }    
    
    /**
     +----------------------------------------------------------
     * 分页函数 支持sql和数据集分页 sql请用 buildSelectSql()函数生成
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array   $result 排好序的数据集或者查询的sql语句
     * @param int    $total     记录总数
     * @param int    $totalRows  每页显示记录数 默认21
     * @param string $listvar    赋给模板遍历的变量名 默认list
     * @param string $parameter  分页跳转的参数
     * @param string $target  分页后点链接显示的内容id名
     * @param string $pagesId  分页后点链接元素外层id名
     * @param string $template ajaxlist的模板名
     * @param string $url ajax分页自定义的url
     +----------------------------------------------------------
     * @author litingting
     */
    public function page($param, $isAjax) {
    	extract ( $param );
    	import ( "@.ORG.Util.Page" );
    	// 总记录数
    	$listvar = $listvar ? $listvar : 'list';
    	$listRows = $listRows ? $listRows : 21;
    	$totalRows = $total;
    	// 创建分页对象
    	if ($target && $pagesId)
    		$p = new Page ( $totalRows, $listRows, $parameter, $url, $target, $pagesId,$waterfall );
    	else
    		$p = new Page ( $totalRows, $listRows, $parameter, $url );
    	$voList = $result;
    	$pages = C ( 'PAGE' ); // 要ajax分页配置PAGE中必须theme带%ajax%，其他字符串替换统一在配置文件中设置，
    	// 可以使用该方法前用C临时改变配置
    	foreach ( $pages as $key => $value ) {
    		$p->setConfig ( $key, $value ); // 'theme'=>'%upPage% %linkPage%
    		// %downPage% %ajax%'; 要带 %ajax%
    	}
    	// 分页显示
    	$page = $p->show ();
    	// 模板赋值
    	$this->assign ("total",$total);
    	$this->assign ( $listvar, $result );
    	$this->assign ( "page", $page );
    	if ($isAjax && $this->isAjax ()) { // 判断ajax请求
    		$p = $_REQUEST['VAR_PAGE'];
    		if($task && empty($_REQUEST['p'])){
    			return $voList;
    		}
    		layout ( false );
    		$template = (! $template) ? 'ajaxlist' : $template;
    		exit ( $this->fetch ( $template ) );
    	}
    	return $voList;
    }
    
    
	/**
	 * 输出错误模板【非系统定义】
	 * @param $assign 输出内容
	 * @param $templete 需要输出的模板名
	 */
	public function errors($templete,$assign=""){
		if(!$templete)
			return false;
		if(!empty($assign)){
			$this->assign("return",$assign);
		}
		$this->display("public:$templete");
	}

    /**
     * 获取并解析loli_from cookie
     * @return mixed $data,含有from_id和from_info等键值
     */
    public function getPromotionCookie(){
        return getPromotionCookie();
    }



}
?>
