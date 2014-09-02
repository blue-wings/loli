<?php
/**
 * 用户信息控制器
 * @author litingting
 */
class userAction extends commonAction {

	/**
     * 新浪微博登录链接生成
     * 转向第三方登录地址
     */
	public function sina_login(){
		require_once SINA_OPEN_ROOT.'config.php';
		require_once SINA_OPEN_ROOT.'saetv2.ex.class.php';
		$returnurl=$_REQUEST["returnurl"];
		if(empty($returnurl)) {
			$redirect_uri=PROJECT_URL_ROOT.U('user/sina_callback');
		}
		else {
			$redirect_uri=PROJECT_URL_ROOT.U('user/sina_callback')."?returnurl=".$returnurl;
		}
		$o = new SaeTOAuthV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY);
		$login_url=$o->getAuthorizeURL($redirect_uri);
		header("Location:$login_url");
	}

	/**
     * 新浪微博第三方登录后返回数据接收方法
     */
	 public function sina_callback(){
		require_once SINA_OPEN_ROOT.'config.php';
		require_once SINA_OPEN_ROOT.'saetv2.ex.class.php';
		$returnurl=$_REQUEST['returnurl'];
		$o = new SaeTOAuthV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY);
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] =PROJECT_URL_ROOT.U('user/sina_callback');
			try {
				$token = $o->getAccessToken('code', $keys ) ;
			}
			catch (OAuthException $e) {
				//捕捉异常，是否输出
			}
		}
		/**
         * $token输出
         * array
         'access_token' => string '2.00VDJ3FCc8IzBCa714c779691Q4OMC' (length=32)
         'remind_in' => string '65488' (length=5)
         'expires_in' => int 65488
         'uid' => string '281827410'	(length=10)th
         */
		$c = new SaeTClientV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY,$token['access_token'] ,'');

		//第三方登录失败
		if(!$token){$this->theThirdLoginFails('sina');}

		$res=$c->show_user_by_id($token['uid']);

		if($res['error_code']==21321){
			$this->error('新浪微博网站接入审核中，暂时只能获取测试账户资料！');exit;
		}
		//$postweibo=$c->update("我通过第三方登录方式，进入了www.lolitabox.com！".date("Y-m-d H:i:s",time()));

		$_SESSION['token'] = $token;
		$_SESSION['access_token']=$token['access_token'];
		$_SESSION['openid_type']="sina";
		$_SESSION['openid']=$token['uid'];
		$_SESSION['openid_name']=$res['name']; //新浪微博用户昵称

		//查询用户第三方登录数据表中是否有当前第三方登录的用户信息
		$user_openid=$token['uid'];
		$user_open_model=M("UserOpenid");
		$type="sina";
		$info=$token;
		$this->doByLoginThirdSuccess($user_openid,$type,$res['name'],$res,$token['access_token'],$returnurl);//记录登录第三方后的信息--公用
	}

	//第三方登录失败公用
	private  function theThirdLoginFails($type){
		$return = array(
		'message'=>'很抱歉,帐号绑定失败了,太没天理了~',
		'bingurl'=>$type
		);
		$this->assign('return',$return);
		$this->display("thirdloginfail");
	}
	/**
     * 新浪微博账号绑定
     * P.S:当前用户必须是已经登录的用户
     */
	public function sina_lock(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号绑定操作！");exit;
		}
		require_once SINA_OPEN_ROOT.'config.php';
		require_once SINA_OPEN_ROOT.'saetv2.ex.class.php';
		$returnurl=$_REQUEST['returnurl'];
		if(empty($returnurl)){
			$redirect_uri=PROJECT_URL_ROOT.U('user/sina_lock_callback');
		}else{
			$redirect_uri=PROJECT_URL_ROOT.U('user/sina_lock_callback')."?returnurl=$returnurl";
		}
		$o = new SaeTOAuthV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY);
		$login_url=$o->getAuthorizeURL($redirect_uri);
		header("Location:$login_url");
	}

	/**
     * 新浪微博账号绑定
     */
	public function sina_lock_callback(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号绑定操作！");exit;
		}
		$returnurl=$_REQUEST['returnurl'];
		require_once SINA_OPEN_ROOT.'config.php';
		require_once SINA_OPEN_ROOT.'saetv2.ex.class.php';
		$o = new SaeTOAuthV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY);
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] =PROJECT_URL_ROOT.U('user/sina_lock_callback');

			try {
				$token = $o->getAccessToken('code', $keys ) ;
			}
			catch (OAuthException $e) {
				//捕捉异常，是否输出
			}
		}
		$c = new SaeTClientV2(SINA_OPEN_AKEY,SINA_OPEN_SKEY,$token['access_token'] ,'');
		if(!$token){
			$this->error('账号绑定失败!');exit;
		}
		$res=$c->show_user_by_id($token['uid']);

		if($res['error_code']==21321){
			$this->error('新浪微博网站接入审核中，暂时只能获取测试账户资料！');exit;
		}

		//查询用户第三方登录数据表中是否有当前第三方登录的用户信息
		$user_openid=$token['uid'];
		$user_open_model=M("UserOpenid");
		if($user_open_info=$user_open_model->where("type='sina'")->getByOpenid($token['uid'])){
			$uid=$this->getUserid();
			if($user_open_info['uid']>0 && $user_open_info['uid']!=$uid && $user_open_info['isbind']==1){
				$return['returnurl']=urldecode($returnurl);
				$return['message']="您的新浪账号已经与其他账号绑定，请先解绑再重新绑定！";
				$this->errors("error_weibo",$return);exit;
			}

			//修改记录
			$update_logindate=date("Y-m-d H:i:s");
			$info=unserialize($user_open_info["info"]);
			$info["last_token"]=$token;

			$save_info=array(
			"uid"=>$uid,
			"info"=>serialize($info),
			"logindate"=>$update_logindate,
			"accesstoken"=>$token['access_token'],
			"isbind"=>1,
			);
			$user_open_model->where("type='sina' AND openid=".$token['uid'])->save($save_info);
			$img_url="http://www.lolitabox.com/public/images/weibo.gif";
			$content_url="http://www.lolitabox.com";
			$weibo_content="入手化妆品之前总要试用一下吧？BA太烦，申请免费的太难，现在都去@LOLITABOX ，每月仅需80元起，就能试用到6款以上最IN的大牌美妆品，轻松找到最适合自己的东东，帮咱理性消费。一起来呗！";
			$this->postSinaWeibo($uid,$weibo_content,$img_url,$content_url);
			//增加用户绑定微博动态信息
			//$user_dynamic_data=array("userid"=>$uid,"whoid"=>1,"type"=>"bound_sina");
			D("UserBehaviourRelation")->addData($uid,1,1,'bound_sina');
			$credit=D("UserCreditSet")->getCreditValById("user_bound_sina_weibo","score");
			if(!M("userCreditStat")->where("action_id='user_bound_sina_weibo' AND userid=$uid")->find()){
				$mess="恭喜您，绑定成功，获得".$credit."积分哦~<a href=\"".U("task/index")."\">继续完成其他任务>></a>";
			}else{
				$mess="您的账号已经与新浪微博账号再次绑定";
			}

			//用户绑定微博账号成功，增加积分
			$user_credit_stat_model = D("UserCreditStat");
			$user_credit_stat_model ->optCreditSet($uid,'user_bound_sina_weibo');
			D("Task")->addUserTask($uid,5);
			if(!empty($returnurl)){
				$returnurl=urldecode($returnurl);
				$this->success($mess,$returnurl);
			}else{
				$this->success($mess,U("home/account"));
			}

		}
		else {
			//新建记录
			$data["openid"]=$token['uid'];
			$data["type"]="sina";
			$data["uid"]=$this->getUserId();
			$res["last_token"]=$token;
			$data["info"]=serialize($res);
			$data["logindate"]=date("Y-m-d H:i:s");
			$data['accesstoken']=$token['access_token'];
			$data['isbind']=1;
			if(false!=$user_open_model->add($data)){
				//增加用户绑定微博动态信息
				//$user_dynamic_data=array("userid"=>$data["uid"],"whoid"=>1,"type"=>"bound_sina");
				D("UserBehaviourRelation")->addData($data['uid'],1,1,"bound_sina");

				//用户绑定微博账号成功，增加积分
				$user_credit_stat_model = D("UserCreditStat");
				$user_credit_stat_model ->optCreditSet($data["uid"],'user_bound_sina_weibo');
				$credit=D("UserCreditSet")->getCreditValById("user_bound_sina_weibo","score");
				$message="恭喜您，绑定成功，获得".$credit."积分哦~<a href=\"".U("task/index")."\">继续完成其他任务>></a>";
				if(!empty($returnurl)){
					$returnurl=urldecode($returnurl);
					$this->success($message,$returnurl);
				}else{
					$this->success($message,U("home/account"));
				}
			}
			else {
				$this->show("网站系统故障，请与LOLITABOX网站管理员联系");
			}
		}
	}



	/**
     * 解除新浪微博账号绑定
     */
	public function sina_unlock(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号解除绑定操作！");exit;
		}
		$userid=$this->getUserid();
		$user_open_model=M("UserOpenid");
		if($user_open_model->where("type='sina' AND uid=$userid")->save(array("isbind"=>'0'))){
			$credit_info=M("userCreditStat")->where("userid=$userid AND action_id='user_unbound_sina_weibo'")->find();
			if($credit_info){
				$mess="您的账号已经与新浪微博账号解除绑定";
			}else{
				$mess="您的账号已经与新浪微博账号解除绑定，并扣除50积分";
			}
			//此处需增加解除绑定时扣减积分
			$user_credit_stat_model = D("UserCreditStat");
			$user_credit_stat_model ->optCreditSet($userid,"user_unbound_sina_weibo");
			$url=$_REQUEST['returnurl'] ? urldecode($_REQUEST['returnurl']) : U("home/account");

			$this->success($mess,$url);
		}
	}

	/**
     * QQ第三方登录
     * @auth zhenghong
     */
	public function qq_login(){
		require_once QQ_OPEN_ROOT.'config.php';
		$appid=$_SESSION["appid"];
		$scope=$_SESSION["scope"];
		$callback=$_SESSION["callback"];
		if(empty($callback)){
			$callback=PROJECT_URL_ROOT.U('user/qq_callback');
		}
		if(!empty($_REQUEST['returnurl'])){
			$returnurl="?returnurl=".$_REQUEST['returnurl'];
			$callback=$callback.$returnurl;
		}
		$_SESSION['state'] = md5(uniqid(rand(), TRUE));
		$login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
		. $appid . "&redirect_uri=" . urlencode($callback)
		. "&state=" . $_SESSION['state']
		. "&scope=".$scope;
		header("Location:$login_url");
	}

	/**
     * QQ第三方登录[callback]
     * @author zhenghong
     */
	function qq_callback(){
		require_once QQ_OPEN_ROOT.'config.php';
		$redirect_uri=U("user/union_reglogin");
		$returnurl=$_REQUEST['returnurl'];
		if($_REQUEST['state'] == $_SESSION['state']){
			$callback=PROJECT_URL_ROOT.U('user/qq_callback');
			$token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
			. "client_id=" . $_SESSION["appid"]. "&redirect_uri=" . urlencode($callback)
			. "&client_secret=" . $_SESSION["appkey"]. "&code=" . $_REQUEST["code"];
			$response = file_get_contents($token_url);
			if(strpos($response, "callback") !== false){
				$lpos = strpos($response, "(");
				$rpos = strrpos($response, ")");
				$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
				$msg = json_decode($response);

				//第三方登录失败
				if (isset($msg->error)){$this->theThirdLoginFails('qq');}
			}
			$params = array();
			parse_str($response, $params);
			$_SESSION["access_token"] = $params["access_token"];
			$_SESSION['openid_type']="qq";
			$this->get_openid();
			$arr=$this->get_user_info();
			$user_openid=$_SESSION["openid"];
			$type="qq";
			$info=$arr['nickname']; //QQ用户昵称
			$this->doByLoginThirdSuccess($user_openid,$type,$info,"",$params["access_token"],$returnurl);
		}
		else{
			$this->show("网站系统故障，请与LOLITABOX网站管理员联系");
		}
	}

	/**
     * 第三方登录成功后，对user_openid表的操作
     * @param $user_openid 第三方账号[必须]
     * @param $type 第三方类型[必须]
     * @param $info 第三方账号的昵称[必须]
     * @param $res 目前新浪用到的一些信息[非必须]
     * @author penglele
     */
	private function doByLoginThirdSuccess($user_openid,$type,$info,$res="",$token_access="",$returnurl=""){
		if(empty($user_openid) || empty($type) || empty($info)){
			return false;
		}
		if($returnurl){
			$returnurl=urldecode($returnurl);
		}else{
			$returnurl=U("home/index");
		}
		$open_user_info=array(
		"openid"=>$user_openid, //第三方开放平台用户ID
		"opentype"=>$type, //第三方开放平台用户类型
		"openname"=>$info //第三方开放平台用户昵称
		);
		$user_open_model=M("UserOpenid");
		$user_open_info=$user_open_model->where("type='$type'")->getByOpenid($user_openid);
		$lolitabox_uid=$user_open_info['uid']; //第三方登录用户对应的LOLITABOX用户ID
		if($user_open_info){
			$id=$user_open_info["id"];
			$uid=$user_open_info['uid'];
			//更新第三方登录用户最后登录时间
			$update_logindate=date("Y-m-d H:i:s");
			$save_info=array(
			"logindate"=>$update_logindate,
			"isbind"=>1,
			);
			if($token_access){
				$save_info['accesstoken']=$token_access;
			}
			$user_open_model->where("id=$id")->save($save_info);
			
			if($user_open_info['uid']<=0){
				//针对V4上线前用户的逻辑，需要在这里自动创建一个LOLITABOX用户，并绑定
				$lolitabox_uid=$this->createLolitaboxAccount($open_user_info);
				if($lolitabox_uid) {
					$save_info["uid"]=$lolitabox_uid;
					$user_open_model->where("id=$id")->save($save_info);
				}
				$this->success($open_user_info["openname"]."您好，你已经通过第三方登录萝莉盒！请稍候...",U("user/register",array("openuserlogin"=>1)));
			}
			else
			{
				$userid=$this->userid;
				//如果用户已经登录，更新token值
				if($userid && $userid==$user_open_info['uid']){
					$return_info= $type=="sina" ? "新浪" : "腾讯";
					$this->success("恭喜您已经通过第三方[".$return_info."]授权！请稍候...",$returnurl);exit;
				}
				//V4上线后，大部分第三方登录用户会走这个分支流程
				$user_model=M("Users");
				$userinfo=$user_model->getByUserid($uid);
				//注册用户会话状态
				$user_session=array(
				"username"=>$userinfo["usermail"],
				"nickname"=>$userinfo["nickname"],
				"userid"=>$userinfo["userid"]
				);
				if($this->set_user_session($user_session)){
// 					$homeurl=getSpaceUrl($lolitabox_uid);
					header("location:$returnurl");
				}
			}
			//通过第三方登录的，给用户加积分
			if($type=="sina"){
				D("UserCreditStat") ->optCreditSet($lolitabox_uid,"user_bound_sina_weibo");
			}else if($type=="qq"){
				D("UserCreditStat") ->optCreditSet($lolitabox_uid,"user_bound_qq");
			}
			header("location:$returnurl");
// 			gotoHome($lolitabox_uid);
		}
		else
		{
			//如果user_openid表中不存在当前openid的信息
			//如果当前用户非绑定行为，则新增【在users,user_profile中新增用户信息】
			$lolitabox_uid=$this->createLolitaboxAccount($open_user_info);
			$data["openid"]=$user_openid;
			$data["type"]=$type;
			$data["uid"]=$lolitabox_uid;
			$data["logindate"]=date("Y-m-d H:i:s");
			$data['isbind']=1;
			if($token_access){
				$data['accesstoken']=$token_access;
			}
			if(false!=$user_open_model->add($data)){
				$_SESSION['openid_name']=$open_user_info["openname"];
			}

			//通过第三方登录的，给用户加积分
			if($type=="sina"){
				D("Task")->addUserTask($lolitabox_uid,5);
				D("UserCreditStat") ->optCreditSet($lolitabox_uid,"user_bound_sina_weibo");
			}else if($type=="qq"){
				D("UserCreditStat") ->optCreditSet($lolitabox_uid,"user_bound_qq");
			}
			$this->success($open_user_info["openname"]."，您好，你已经通过第三方登录萝莉盒！请稍候...",$returnurl);
		}
	}

	/**
     * 配合第三方登录V4版需求，允许第三方账号登录用户自动创建LOLITABOX用户并绑定
     * @param array 开放平台获取的用户信息
     * array('openname'=>openname,'openid'=>xxx,'opentype'=>sina/qq/xxx)
     * @return lolitabox_uid LOLITABOX用户ID
     */
	public function createLolitaboxAccount($openinfo){
		$data["usermail"]=$this->autoLolitaUsername($openinfo["opentype"]."_".$openinfo["openid"]);
		$data["password"]="";
		$data["nickname"]=$this->autoLolitaNickname($openinfo["openname"]);
		$data["addtime"]=date("Y-m-d H:i:s",time());
		$data["state"]=0;
		$data["invite_uid"]=cookie("inviteuid")?cookie("inviteuid"):0; //记录注册用户的邀请者ID
		$user_mod = D("Users");
		if($user_mod->add($data)){
			$new_userid = $user_mod->getLastInsID();  //新注册用户ID
			$this->promoteInformationAdd($new_userid);
			$user_session=array(
			"username"=>$data["usermail"],
			"nickname"=>$data["nickname"],
			"userid"=>$new_userid,
			);
			//给新注册的用户发封私信
			$message="Hello我亲爱的Lolitagirls:<br />欢迎您加入萝莉盒大家庭。萝莉盒就是化妆品试用，我们提倡先试后买理性消费，这里有海量的热门明星产品供你选择试用，让你轻松找到最适合自己的美丽武器！";
			D("Msg")->addMsg(C("LOLITABOX_ID"),$new_userid,$message);
			//新注册用户自动关注官网ID
			D("Follow") ->addFollow($new_userid,C('LOLITABOX_ID'),1);
			$this->set_user_session($user_session);
			return $new_userid;
		}
		else {
			return false;
		}

	}

	//自动创建LOLITABOX用户名【适用于第三方登录】
	private function autoLolitaUsername($username){
		if(D("Users")->searchUserinfoByField("usermail",$username)) {
			return $this->autoLolitaUsername($username."_".rand(100,999));
		}
		else {
			return $username;
		}
	}

	//自动创建LOLITABOX用户昵称【适用于第三方登录】
	public function autoLolitaNickname($nickname){
		$nickname=D("Public")->checkThirdUsername($nickname);
		$i=0;
		while($i<100){
			if($i==0){
				$username=$nickname;
			}else{
				$username=$nickname."_".rand(100,999);
			}
			$userinfo=D("Users")->searchUserinfoByField("nickname",$username);
			if(!$userinfo){
				break;
			}
			$i++;
		}
		return $username;
	}


	/**
     * 获取qq第三方登录后ID
     */
	private function get_openid(){
		require_once QQ_OPEN_ROOT.'config.php';
		$graph_url = "https://graph.qq.com/oauth2.0/me?access_token="
		. $_SESSION['access_token'];
		$str  = file_get_contents($graph_url);
		if (strpos($str, "callback") !== false){
			$lpos = strpos($str, "(");
			$rpos = strrpos($str, ")");
			$str  = substr($str, $lpos + 1, $rpos - $lpos -1);
		}
		$user = json_decode($str);
		if (isset($user->error)){
			return false;
		}
		$_SESSION["openid"] = $user->openid;
		return true;
	}

	/**
     * 获取qq第三方登录后的信息
     */
	private function get_user_info(){
		require_once QQ_OPEN_ROOT.'config.php';
		$get_user_info = "https://graph.qq.com/user/get_user_info?"
		. "access_token=" . $_SESSION['access_token']
		. "&oauth_consumer_key=" . $_SESSION["appid"]
		. "&openid=" . $_SESSION["openid"]
		. "&format=json";
		$info = file_get_contents($get_user_info);
		$arr = json_decode($info, true);
		return $arr;
	}

	/**
     * qq绑定
     */
	public function qq_lock(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号绑定操作！");exit;
		}
		require_once QQ_OPEN_ROOT.'config.php';
		$appid=$_SESSION["appid"];
		$scope=$_SESSION["scope"];
		$callback=$_REQUEST['redirect_uri'];
		if(empty($callback)){
			$callback=PROJECT_URL_ROOT.U('user/qq_lock_callback');
			if(!empty($_REQUEST['returnurl'])){
				$returnurl="?returnurl=".$_REQUEST['returnurl'];
				$callback=$callback.$returnurl;
			}
		}
		$_SESSION['state'] = md5(uniqid(rand(), TRUE));
		$login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
		. $appid . "&redirect_uri=" . urlencode($callback)
		. "&state=" . $_SESSION['state']
		. "&scope=".$scope;
		header("Location:$login_url");
	}

	/**
     * qq绑定
     */
	public function qq_lock_callback(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号绑定操作！");exit;
		}
		$returnurl=$_REQUEST['returnurl'];
		if(!empty($returnurl)){
			$returnurl=urldecode($returnurl);
		}
		require_once QQ_OPEN_ROOT.'config.php';
		if($_REQUEST['state'] == $_SESSION['state']){
			$callback=PROJECT_URL_ROOT.U('user/qq_lock_callback');
			$token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
			. "client_id=" . $_SESSION["appid"]. "&redirect_uri=" . urlencode($callback)
			. "&client_secret=" . $_SESSION["appkey"]. "&code=" . $_REQUEST["code"];
			$response = file_get_contents($token_url);
			if(strpos($response, "callback") !== false){
				$lpos = strpos($response, "(");
				$rpos = strrpos($response, ")");
				$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
				$msg = json_decode($response);
				if (isset($msg->error)){
					$this->error('第三方登录验证失败!');exit;
				}
			}
			$params = array();
			parse_str($response, $params);
			$_SESSION["access_token"] = $params["access_token"];
			$_SESSION['openid_type']="qq";
			$this->get_openid();
			$arr=$this->get_user_info();
			$user_openid=$_SESSION["openid"];
			$type="qq";
			$info=$arr['nickname'];
			$userid=$this->getUserid();
			$open_mod=M("UserOpenid");
			$open_info=$open_mod->where("openid='".$user_openid."' AND type='".$type."'")->find();
			//如果当前openid存在，且其记录中的uid为当前登录用户，则说明当前用户已经绑定过QQ,不需要重复绑定

			if($open_info['uid']>0 && $open_info['uid']!=$userid && $open_info['isbind']==1){
				$return['returnurl']=urldecode($returnurl);
				$return['message']="您的QQ账号已经与其他账号绑定，请先解绑再重新绑定！";
				$this->errors("error_weibo",$return);exit;
			}

			if($open_info['uid']==$userid){
				$open_mod->where("openid='".$user_openid."' AND type='".$type."'")->save(array("accesstoken"=>$_SESSION["access_token"],"isbind"=>1));

				if(!empty($returnurl)){
					$this->success("您已经与QQ绑定",$returnurl);exit;
				}else{
					$this->success("您已经与QQ绑定",U("home/index"));exit;
				}
			}
			//如果当前openid存在，且uid与当前登录用户不一致，up
			$data['uid']=$userid;
			$data['info']=$info;
			$data['logindate']=date("Y-m-d H-i-s");
			$data['accesstoken']=$_SESSION["access_token"];
			$data['isbind']=1;
			$credit=D("UserCreditSet")->getCreditValById("user_bound_qq","score");
			$succ_mess="恭喜您，绑定成功，获得".$credit."积分哦~<a href='' class='A_line3'>继续完成其他任务>></a>";
			if($open_info){
				$open_mod->where("openid='".$user_openid."' AND type='".$type."'")->save($data);

				if(M("UserCreditStat")->where("userid=$userid AND action_id='bound_qq'")->find()){
					$succ_mess="您的账号与QQ绑定成功";
				}
				
				//增加用户绑定微博动态信息
				D("UserBehaviourRelation")->addData($userid,$usertype=1,1,"bound_qq");
				//qq绑定成功，给用户加积分
				D("UserCreditStat") ->optCreditSet($userid,"user_bound_qq");

				if(!empty($returnurl)){
					$this->success($succ_mess,$returnurl);exit;
				}else{
					$this->success($succ_mess,U("home/index"));exit;
				}
			}else{
				//当前的openid不存在，向数据表增加一条记录
				$data['type']=$type;
				$data['openid']=$user_openid;
				$open_mod->add($data);

				//增加用户绑定微博动态信息
				D("UserBehaviourRelation")->addData($userid,$usertype=1,1,"bound_qq");
				//qq绑定成功，给用户加积分
				D("UserCreditStat") ->optCreditSet($userid,"user_bound_qq");
				
				if(!empty($returnurl)){
					$this->success($succ_mess,$returnurl);exit;
				}else{
					$this->success($succ_mess,U("home/index"));exit;
				}
			}
		}
		else{
			$this->show("网站系统故障，请与LOLITABOX网站管理员联系");exit;
		}
	}

	/**
     * 解除qq账号绑定
     */
	public function qq_unlock(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法进行账号解除绑定操作！");exit;
		}
		$userid=$this->getUserid();
		$user_open_model=M("UserOpenid");
		if($user_open_model->where("type='qq' AND uid=$userid")->save(array("isbind"=>'0'))){

			$credit_info=M("userCreditStat")->where("userid=$userid AND action_id='user_unbound_qq'")->find();
			if($credit_info){
				$mess="您的账号已经与QQ账号解除绑定";
			}else{
				$mess="您的账号已经与QQ账号解除绑定，并扣除50积分";
			}

			//qq解绑，给用户减积分
			D("UserCreditStat") ->optCreditSet($userid,"user_unbound_qq");
			$url=$_REQUEST['returnurl'] ? urldecode($_REQUEST['returnurl']) : U("home/account");
			$this->success($mess,$url);
		}
	}

	/**
     * 显示用户注册登录框
     * @author zhenghong
     */
	function reglogin(){
		if($this->check_login()){
			$userid=$this->getUserid();
			gotoHome($userid);
		}
		$u=$_GET['u'];
		if($u){
			$inviteuserid=$this->useridauth_decode($u);
			$return['inviteuserid'] = $inviteuserid;
			$inviteuserid = $inviteuserid ? $inviteuserid :0;
			cookie("inviteuid",$inviteuserid,array("expire",3600*3));
		}else{
			//新增短链接的邀请方式update by penglele 2013-11-6 12:05:03
			$s=$_GET['s'];
			if($s){
				$inviteuserid=decodeNum($s);
				$return['inviteuserid'] = $inviteuserid;
				$inviteuserid = $inviteuserid ? $inviteuserid :0;
				cookie("inviteuid",$inviteuserid,array("expire",3600*3));
			}
		}
		$return['title']= '注册萝莉盒-LOLITABOX萝莉盒';
		$this->assign('return',$return);
		$this->display();
	}


	/**
     * useridauth_decode
     * 邮件中验证链接解码
     * @param string $authcode 加密CODE
     * @return mix array("inviteuserid"=>"")
     */
	public function useridauth_decode($authcode){
		$code=base64_decode($authcode);
		$userid_info=authcode($code,"DECODE",C('COOKIE_AUTHKEY'));
		$array_userid=explode(C('COOKIE_AUTHKEY_SPLIT'),$userid_info);
		$userid=$array_userid[0];
		$userip=$array_userid[1];
		$u_ip=get_client_ip();
		return $userid;
	}


	/**
     * 判断用户信息是否已经存在 ***reglogin***
     * 检查用户名（邮箱）是否存在
     * AJAX方式请求
     * @author zhenghong
     */
	public function allow_username(){
		if($this->isAjax()){
			$usermail=trim($_POST["param"]);

			//判断用户邮箱是否在黑名单中
			$array_disable_domain=array("chuaizi.com","600mail.com","yy369.com","chuaizai.com",'wiseie.net','wow8.net','qianbao666.net','bccto.me');

			$result = null;
			foreach ($array_disable_domain as $key=>$value){
				if(stristr($usermail,$value)){
					$result.=$value;
				}
			}

			if($result){
				echo("请您输入常用的邮箱地址！");exit;
			}


			if(!empty($usermail) && D("Users")->searchUserinfoByField("usermail",$usermail)){
				echo "该邮箱太火了，已经存在了"; //存在返回"n"
			}
			else {
				echo "y"; //不存在，则允许使用
			}
		}
	}

	/**
     * 判断用户信息是否已经存在***reglogin***
     * 检查昵称是否存在
     * AJAX方式请求
     * @author zhenghong
     */
	public function allow_nickname(){
		if($this->isAjax()){
			$nickname=trim($_POST["param"]);

			$status = null;

			if(empty($nickname)){
				$status = 1;
			}

			if(D("Users")->searchUserinfoByField("nickname",$nickname)){
				$status = 1;
			}
			if(D("ProductsBrand")->searchName($nickname)){
				$status = 1;
			}

			if(D("Products")->searchPname($nickname)){
				$status = 1;
			}

			//过滤敏感词
			if(filterwords($nickname)){
				$status = 2;
			}

			if($status == 1){
				echo "昵称已经存在"; //存在返回"n"
			}else if($status == 2){
				echo '该昵称不允许注册';
			}else{
				cookie("falsestr",null);
				echo "y"; //不存在，则允许使用
			}
		}
	}

	//注册过滤敏感词
	function filterword($name){
		return 	 D("Filterword")->where(array('words'=>array('LIKE',"%".$name."%")))->find();
	}

	/**
     * 用户注册表单处理
     * 
     一、提交注册前，检查邮箱规则（是否为垃圾邮箱）【ajax中判断】
     二、查看当前用户注册来源是地址是否为站内地址，排除机器注册可能性
     三、判断用户从哪个推广来源注册
     四、判断用户是否为被邀请用户【忽略】
     五、判断用户是否为第三方注册用户【忽略，因为第三方登录可以直接进入账号】
     六、判断用户是否有注册后指定返回的URL
     *
     * @author zhenghong
     */
	public function register(){
		if($this->check_login()){
			gotoHome($this->getUserid());
		}
		//防止垃圾注册，需要验证其注册请求来源必须是当前LOLITABOX网址域名
		if(!preg_match("/http:\/\/".$_SERVER["SERVER_NAME"]."/i",$_SERVER["HTTP_REFERER"])) {
			$this->error("非法注册，请通过正常方式注册！");
		}
		if($_SESSION['verify'] === md5($_POST['verify'])) {
			//执行注册
			$user_mod = D("Users");
			if($userdata = $user_mod->create()){
				$return['title'] = "成功注册萝莉盒-LOLITABOX萝莉盒";
				$this->assign('return',$return);
				if($user_mod->add()){
					$new_userid = $user_mod->getLastInsID();  //新注册用户ID
					//补充联盟推广信息
					$this->promoteInformationAdd($new_userid);
					//设置用户会话状态
					$user_session=array(
					"username"=>$userdata["usermail"],
					"nickname"=>$userdata["nickname"],
					"userid"=>$new_userid
					);
					//新注册的用户，给用户发私信
					$message="<br />Hi~亲爱的Lolitagirl，欢迎加入萝莉盒大家庭。我们提倡“先试后买”的理性消费观念，萝莉盒有海量的明星美妆品供你选择试用，让你轻松找到最适合自己的美丽武器！<br />初来乍到，对网站还不是很熟悉，可以先看看<a href='/special/xszn.html' target='_blank' class='WB_info'>新手指南</a>，相信一定对你有所帮助。如果您对新手指南不太感冒，不如去看看<a href='/buy/index.html#boxs_4' target='_blank' class='WB_info'>新会员独享神秘盒</a>，现在仅需70元就能体验到这款超值萝莉盒。<br />当然，您也可以选择<a href='/member/index.html' target='_blank' class='WB_info'>立即成为特权会员</a>，以更优惠的价格购买更多其它超值萝莉盒。还不知道什么是特权会员，<a href='/info/lolitabox/aid/1484.html' target='_blank' class='WB_info'>可以猛戳这里>></a>";
					D("Msg")->addMsg(C("LOLITABOX_ID"),$new_userid,$message);
					//$user_session['usermail'] = $user_session['username'];
					$this->send_reg_success_mail($user_session);    //发送邮件
					if($this->set_user_session($user_session)){
						$return["usermail"]=$userdata["usermail"];
					}
					$return['userid']=$new_userid;
					$mailtype = $this ->emailType($return["usermail"]);
					$return['mailurl'] = $mailtype['mail_all'];
					if(!empty($_POST["returntitle"]) && !empty($_POST["returnurl"])) {
						$this->assign('FromTitle',$_POST["returntitle"]);
						$this->assign('FromUrl',$_POST["returnurl"]);
					}
					//获取用户的头像
					$u_info=D("Users")->getUserInfo($new_userid,'userface');
					$userface=$u_info['userface_40_40'];
					$this->assign("userface",$userface);


                    $sql = "SELECT cid,cname from category WHERE ctype = ".C("CTYPE_PRODUCT")." and pcid = ".C("PCID_ROOT");
                    $model= new Model();
                    $categories = $model->query($sql);
                    $this->assign('categories',$categories);

					$this->assign('return',$return);
					$this->display("reg_success");
				}else{
					$this->display("register_fail");
				}
			}else{
				$this->error($user_mod->getError());
				exit;
			}
		}else{
			$this->error("验证码错误");
		}
	}

	function welcome(){
		header("location:/");
	}



	/**
     * 增加联盟推广信息
     * 如果当前用户是登录推广过来的用户，则将其推广信息保存到数据表中
     * 【未验证】
     * @author zhaoxiang
     * @last update zhenghong
     */
	public  function  promoteInformationAdd($new_userid){
		$user_profile_model=M("UserProfile");
		$data=array("userid"=>$new_userid);
		$promotion_cookie_data=$this->getPromotionCookie();
		if($promotion_cookie_data['from_id']){
			$data['fromid']=$promotion_cookie_data['from_id'];
			if($promotion_cookie_data['from_info']){
				$data['frominfo']=$promotion_cookie_data['from_info'];
			}
		}
		if($user_profile_model->add($data)){
			return true;
		}else{
			return false;
		}
	}

	/**
     * 当用户注册成功后，发送注册成功邮箱 ***with register***
     * @param unknown_type $userinfo
     * @return boolean
     */
	public function send_reg_success_mail($userinfo){
		$usermail=$userinfo["username"];
		$nickname=$userinfo["nickname"];
		$userid=$userinfo["userid"];
		$valid_time=time()+60*60*24; //24h后失效
		$mailauth_code=$this->mailauth_encode($usermail);
		$activemail_url="http://".$_SERVER["SERVER_NAME"].U("user/active_mail",array("s"=>$mailauth_code));
		$this->assign("nickname",$nickname);
		$this->assign("activemail_url",$activemail_url);
		$mail_content=$this->fetch("reg_success_sendmail");
		$title="萝莉盒-注册成功！激活邮箱获积分（系统邮件，请勿回复）";
		if(sendtomail($usermail,$title,$mail_content)){
			return true;
		}else{
			return false;
		}
	}

	/**
     * mailauth_encode
     *
     * 邮件中验证链接加密
     * @param string $usermail
     * @return string $authcode
     */
	public function mailauth_encode($usermail){
		if(empty($usermail)) return false;
		$mail_authcode=$usermail.C('COOKIE_AUTHKEY_SPLIT').(time()+60*60*24);
		$code=base64_encode(authcode($mail_authcode,"ENCODE",C('COOKIE_AUTHKEY')));
		return $code;
	}


	/**
     * mailauth_decode
     *
     * 邮件中验证链接解码
     * @param string $authcode 加密CODE
     * @param boolean $ifexp 是否判断过期
     * @return mix array("usermail"=>"","stime"=>"")
     */
	public function mailauth_decode($authcode,$ifexp=false){
		$code=base64_decode($authcode);
		$usermail_info=authcode($code,"DECODE",C('COOKIE_AUTHKEY'));
		$array_usermail=explode(C('COOKIE_AUTHKEY_SPLIT'),$usermail_info);
		$stime=$array_usermail[1];
		$usermail=$array_usermail[0];
		if(!$ifexp){
			return $usermail;
		}
		else {
			//如果需要判断是否过期，则进行当前时间TIME与加密串中时间比较的逻辑
			if(time()>$stime){
				//当前时间已经超出有效期
				return false;
			}
			else {
				return $usermail;
			}
		}
	}


	/**
     * 判断邮件类型，为用户激活邮件准备 ***with register***
     * @param unknown_type $usermail
     * 【未验证，未使用】
     * @author zhaoxiang
     * last update zhenghong
     */
	private function  emailType($usermail){
		$arr=explode('@',$usermail);
		$mail_all=array(
		'126.com'=>'http://mail.126.com/','163.com'=>'http://mail.163.com/','sohu.com'=>'http://mail.sohu.com',
		'sogou.com'=>'http://mail.sogou.com/','sina.com'=>'http://mail.sina.com.cn/','http://mail.sina.com.cn/',
		'qq.com'=>'http://mail.qq.com','yahoo.cn'=>'http://mail.cn.yahoo.com/','yahoo.com.cn'=>'http://mail.cn.yahoo.com/',
		'gmail.com'=>'https://mail.google.com/','msn.com'=>'http://hotmail.msn.com/','hotmail.com'=>'http://www.hotmail.com/',
		'21cn.com'=>'http://mail.21cn.com/','263.net'=>'http://www.263.net/','139.com'=>'http://mail.10086.cn/'
		);
		if(in_array($arr[1],array_keys($mail_all))){
			$return['mail_all'] = $mail_all[$arr[1]];
		}
		$return['mail_type'] = $arr[1];
		return $return;
	}


	/**
     * 邮件激活功能
     * 用户收到激活邮件后，通过邮件中的加密链接返回到这个控制器做处理
     * @param $s 邮件加密串
     */
	public function active_mail(){
		$code=$this->_request("s");
		$usermail=$this->mailauth_decode($code,true);
		//2013-10-14 发现发出去的激活邮件在30分钟后会被不明IP通过GET方式访问激活邮件中的链接，造成邮件被匿名激活
		//处理办法：需要先判断用户登录状态
		if(!$this->check_login()){
			$this->error("邮件验证失败，请您先登录再进行邮件激活操作！<br/>如果登录后仍然无法正常通过邮件验证，请将收到的激活邮件地址复制粘贴到地址栏！",U("user/reglogin"));
			exit;
		}
		
		if($usermail){
			//激活邮件
			$user_model=M("users");
			$userinfo=$user_model->getByUsermail($usermail);
			if($userinfo["state"]=='2') {
				$this->error("激活失败，您之前已经完成激活啦！");
				exit;
			}
			if(!$this->check_login()){
				//如果当前没有登录 ,则进行会话注
				if($userinfo){
					//注册其会话状态
					$user_session=array(
					"username"=>$userinfo["usermail"],
					"nickname"=>$userinfo["nickname"],
					"userid"=>$userinfo["userid"]
					);
					$this->set_user_session($user_session);
				}
				else {
					//没有该用户信息
					$this->error("对不起，你的激活动作是非法的！");
					exit;
				}
			}
			$data["state"]=2;
			$user_model->where("usermail='$usermail'")->save($data);
			$user_credit_stat_model = D("UserCreditStat");
			$user_credit_stat_model ->optCreditSet($userinfo['userid'],'user_verify_email');
			D("Task")->addUserTask($userinfo["userid"],2);
			$this->success("邮件激活成功，您刚刚获取20积分！",U("home/index"),5);
		}
		else {
			$this->assign("mailauth",$code);
			$this->display("active_mail_fail");
		}
	}



	/**
     * 当用户通过邮件激活失败后，需要重新发送激活邮件
     * @author zhenghong
     * 可以通过传userid或邮箱地址（编码后）参数来执行，优先选择userid
     */
	public function send_reactive_mail(){
		$userid=$this->_request("userid");
		$code=$this->_request("s");
		$nuserid=$this->getUserid();
		if(empty($code) && (!$userid || !$nuserid) && $userid!=$nuserid){
			$this->error("参数错误！");
		}
		$user_model=M("users");
		$userinfo=$user_model->getByUserid($userid);
		if($userid){
			//说明当前是登录用户，可以直接获取其登录邮箱地址
			$usermail=$userinfo['usermail'];
		}
		else {
			$usermail=$this->mailauth_decode($code,false); //解析MAIL，不考虑有效期
		}
		if(empty($usermail)) {
			$this->assign("error","未知邮箱，操作失败！");
			$this->display("public:error");
			exit();
		}
		$usermail=$userinfo["usermail"];
		$nickname=$userinfo["nickname"];
		$user_state=$userinfo["state"];
		if($user_state=='2'){
			$this->error("对不起，您的邮箱已经被激活，不用再激活啦。");exit;
		}

		$this->assign("nickname",$nickname);
		$mailauth_code=$this->mailauth_encode($usermail);
		$activemail_url="http://".$_SERVER["SERVER_NAME"].U("user/active_mail",array("s"=>$mailauth_code));
		$this->assign("activemail_url",$activemail_url);
		$content=$this->fetch("reactive_mail_sendmail");
		$mail_content=$content;
		$title="萝莉盒-注册成功！激活邮箱获积分（系统邮件，请勿回复）";

		if(sendtomail($usermail,$title,$mail_content)){
			$return['usermail']=$usermail;
			$return['mailtype']=$this->emailType($usermail);
		}else{
			$return = array(
			'usermail'=>$usermail,
			'error'=>'发送激活邮件失败!'
			);
		}
		$this->assign("return",$return);
		$this->display();
	}

	function jihuo(){
		$this->display("send_reactive_mail");
	}
	//判断当前用户是否为第三方登录用户
	private  function isOpenUser($new_userid,$nickname,$usermail){
		$user_openid_model=M("UserOpenid");
		if(session("openid")){
			$user_openid_model->where(array('openid'=>session('openid'),'type'=>session('openid_type')))->save(array('uid'=>$new_userid));
		}
		if(session("open_username")){
			$user_openid_model->where(array('openid'=>session('open_userid'),'type'=>session('openid_type')))->save(array('uid'=>$new_userid));
		}

		//注册用户会话状态
		$user_session=array(
		"username"=>$new_userid,
		"nickname"=>$nickname,
		"userid"=>$new_userid
		);

		if($this->set_user_session($user_session)){
			$user_session["usermail"]=$usermail;
			$this->send_reg_success_mail($user_session);
		}
	}



	/**
     * 设置用户会话信息
     * 注意：在此之前不要有任何输出
     * @param string $type 设置会话类型[add 添加/del 清除]
     */
	public function set_user_session($user_session){
		if(empty($user_session["username"]) || empty($user_session["nickname"]) || empty($user_session["userid"])){
			return false;
		}
		//分别SETCOOKIE[顺序：username,nickname,userid]
		foreach ($user_session as $name => $value){
			cookie($name,$value);
		}
		$userauth_cookie=implode(C('COOKIE_AUTHKEY_SPLIT'),$user_session);
		cookie("userauth",authcode($userauth_cookie,"ENCODE",C('COOKIE_AUTHKEY')));
		return true;
	}




	//与邀请者互相关注
	private function inviterEachOther($inviteuserid,$new_userid){

		$user_mod = D("Users");
		$rel = $user_mod->getUserInfo($inviteuserid,"nickname");

		if($rel){
			$user_behaviour_model=D("UserBehaviourRelation");
			$user_dynamic_data1=array("userid"=>$inviteuserid,"whoid"=>$new_userid,"type"=>"follow_uid","status"=>'0',"addtime"=>time());
			$user_dynamic_data2=array("userid"=>$new_userid,"whoid"=>$inviteuserid,"type"=>"follow_uid","status"=>'0',"addtime"=>time());
			$user_behaviour_model->add($user_dynamic_data1);
			$user_behaviour_model->add($user_dynamic_data2);

			$user_mod->updateFollowNum ( $new_userid );
			$user_mod->updateFansNum ( $new_userid );
			$user_mod->updateFollowNum ( $inviteuserid );
			$user_mod->updateFansNum ( $inviteuserid );
		}
	}


	/**
     * 用户登录
     */
	public function login(){

        if (IS_POST) {
            $username=$_REQUEST["usermail"]; //用户注册的邮箱地址
            $userpassword=$_REQUEST["password"];

            if(empty($username) || empty($userpassword)) {
                if($this->isAjax()){
                    $this->ajaxReturn(0,'缺少登录信息!',0);
                }
                else {
                    $this->error("请填写用户名、密码");
                }
            }
            Log::write("user login ".$username,CRIT);
            $UserModel=M("users");
            $userinfo=$UserModel->getByUsermail($username);
            if($userinfo){
                //用户名存在，则验证其密码是否与当前用户名中的密码相等
                if($userinfo["password"]==md5($userpassword)){
                    //用户身份验证通过 后，注册其会话状态
                    $user_session=array(
                        "username"=>$userinfo["usermail"],
                        "nickname"=>$userinfo["nickname"],
                        "userid"=>$userinfo["userid"]
                    );
                    if($this->set_user_session($user_session)){
                        $this->loginBindUserOpenid($userinfo["userid"]);
                        if($this->isAjax()){
                            $this->ajaxReturn($user_session,'用户登录成功!',1);
                        }else {
                            header('Location:/home');
                            $this->success("用户已经登录！");
                        }
                    }
                }
                else {
                    if($this->isAjax()){
                        $this->ajaxReturn(0,'密码错误!',0);
                    }
                    else {
                        $this->error("密码错误");
                    }
                }
            }
            else {
                if($this->isAjax()){
                    $this->ajaxReturn(0,'登录账号错误!',0);
                }
                else {
                    $this->error("登录账号错误");
                }
            }
        }

        $this->display();
	}

	/**
     * 绑定用户合作平台账号关系
     * @param unknown_type $loliuid
     * @param unknown_type $open_type
     * @param unknown_type $open_uid
     * @author zhenghong@sohu.com
     * 本方法将实现已经有SESSION会话的合作平台账号信息并进入到user_openid表中的情况 下，与LOLITABOX的USERID进行关联
     */
	private  function loginBindUserOpenid($loli_uid='',$open_type='',$open_uid=''){

		$open_uid=$open_uid?$open_uid:session("open_userid");
		$openid_typ=$open_type?$open_type:session("openid_type");
		$loli_uid=$loli_uid?$loli_uid:$this->getUserid();

		$user_openid_mod=M("UserOpenid");

		$where["type"]=$openid_typ;
		$where["openid"]=$open_uid;

		if($user_openid_mod->where($where)->find()){
			$data["uid"]=$loli_uid;
			$user_openid_mod->where($where)->save($data);
		}
	}

	/**
     * 注销登录
     */
	public function logout(){
		cookie("username",null);
		cookie("nickname",null);
		cookie("userid",null);
		cookie("userauth",null);
        $this->redirect('/index');
	}

	//忘记密码
	public function forgetpwd(){

		$return['title'] = "找回萝莉盒密码-LOLITABOX萝莉盒";
		$this->assign('return',$return);

		if($this->isPost()){
			$where = "usermail='{$this->_post('usermail')}'";
			$result = D('Users')->getUserInfoByData($where);

			if($result){
				if($this->send_resetpwd_mail($result[0]['usermail'],$result[0]['nickname'])){
					$return = $this->emailType($this->_post('usermail'));
					$return['usermail']=$this->_post('usermail');
					$this->assign('return',$return);
					$this->display('forgetpwd_success');
				}else{
					$this->display('forgetpwd_fail');
				}
			}else{
				$this->error("用户邮件地址无效！");
			}
		}else {
			$this->display();
		}
	}

	//重置密码表单
	public function reset_pwd(){
		$return['title'] = "重置萝莉盒密码-LOLITABOX萝莉盒";
		$return['mailauth']=$this->_request("s");
		if($return['usermail']=$this->mailauth_decode($return['mailauth'],true)){
			$this->assign("return",$return);
			$this->display();
		}else {
			//重定向到找回密码初始请求页
			$this->display('forgetpwd');
		}
	}


	//发送用户重置密码邮件
	public function send_resetpwd_mail($usermail,$nickname=''){
		if(empty($usermail)) return false;
		$mailauth_code=$this->mailauth_encode($usermail);
		//找回密码
		$resetpwd_url="http://".$_SERVER["SERVER_NAME"].U("user/reset_pwd",array("s"=>$mailauth_code));
		$return = array('resetpwd_url'=>$resetpwd_url,'nickname'=>$nickname);
		$this->assign("return",$return);
		$content=$this->fetch("reset_pwd_sendmail");
		$title="萝莉盒-找回密码（系统邮件，请勿回复）";
		return sendtomail($usermail,$title,$content);
	}


	//忘记密码,检查用户名
	function checkuserfield(){
		if($this->_post('param')){
			$where = "usermail='{$this->_post('param')}'";
			if(D('Users')->getUserInfoByData($where)){
				echo "y";exit;
			}else{
				echo "邮箱不存在";
			}
		}else{
			echo "没有参数";
		}
	}

	//用户修改密码提交页
	public function change_pwd_ajax(){

		$pwd1 = $this->_post('password');
		$pwd2 = $this->_post('re_password');
		$usermail=$this->_post('usermail');
		$mailauth=$this->mailauth_decode($this->_post('mailauth'));
		$userid_cookie=cookie('userid');

		if($usermail != $mailauth){
			$this->ajaxReturn(0,"两次密码输入不一致",0);
		}

		if($this->isPost()){

			if($pwd1 && $pwd2){
				if($pwd1 == $pwd2){

					$user_model = D('Users');

					$ret = $user_model->where(array('usermail'=>$usermail))->save(array('password'=>md5($pwd1)));

					if($ret !== false){
						$where['usermail']=$usermail;
						if($userinfo = $user_model->getUserInfoByData($where)){

							//注册其会话状态
							$user_session=array(
							"username"=>$userinfo[0]["usermail"],
							"nickname"=>$userinfo[0]["nickname"],
							"userid"=>$userinfo[0]["userid"]
							);
							$this->set_user_session($user_session);
							$this->ajaxReturn(1,"修改成功!",1);
						}
					}else{
						$this->ajaxReturn(0,"修改失败",0);
					}
				}else{
					$this->ajaxReturn(0,"两次密码输入不一致",0);
				}
			}else{
				$this->ajaxReturn(0,"密码框不能为空",0);
			}
		}
	}

	//完善第三方信息
	function thirdPerfectInformation(){
		$user_mod = D("Users");
		if($userdata = $user_mod->create()){
			unset($userdata['addtime']);
			if($user_mod->where(array('userid'=>cookie('userid')))->save($userdata)){
				$return = array(
				"username"=>$userdata["usermail"],
				"nickname"=>$userdata["nickname"],
				"userid"=>cookie('userid')
				);
				$this->set_user_session($return);
                $this->ajaxReturn(1,1,1);
            }
        }else{
            $this->ajaxReturn(0,0,0);
			exit;
		}
	}
	
}
?>
