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
			
		}
		//当前页面的url
		if(!(MODULE_NAME=="user" && in_array(ACTION_NAME,array("reglogin","logout")))){
			$thirdurl="http://".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'];
			$thirdurl=urlencode($thirdurl);
			$this->assign("thirdurl",$thirdurl);
		}
		if(MODULE_NAME == 'task' || MODULE_NAME =='home'){
			
			if(empty($this->userid)){
				$unlogin_page = array();     //不需要登录的页面
				$current_page = MODULE_NAME."_".ACTION_NAME;
				if(!in_array($current_page, $unlogin_page)){    //如果此页面不需要则跳过，反之则header到首页
					header("location:".U('user/reglogin'));    //未登录则跳转到登录页
				}
			}
			if($this->userid){
				$datanum=D("Users")->getUserDataNum($this->userid);
				$this->assign("datanum",$datanum);
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
     * 设置loli_from COOKIE
     * @param mixed $data
     * @author litingting
     */
    public function setPromotionCookie($data){
    	if($data['f']){
    		$info=M("Promotion")->getByCode($data['f']);
    		if(empty($info)){
    			return false;
    		}
    		$params[]=$data['f'];
    		if($info['params']){
    			$param_arr=explode(" ",$info['params']);
    			foreach($param_arr as $value){
    				$params[]=$data[$value];
    			}
    		}
    		$validate=$info['validate'] ? $info['validate']:1;
    		cookie('from',implode("_||_",$params),$validate*24*3600);
    	}
    }
    
    /**
     * 获取并解析loli_from cookie
     * @return mixed $data,含有from_id和from_info等键值
     */
    public function getPromotionCookie(){
    	return getPromotionCookie();
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
    public function page($param) {
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
    	if ($this->isAjax ()) { // 判断ajax请求
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
     * +----------------------------------------------------------
     * JS:kindeditor插件提交的文本中的远程图片转存到本地
     * 存储目录:/data/userdata/年/月/日/
     * +----------------------------------------------------------
     *
     * @access protected
     *         +----------------------------------------------------------
     * @param string $data
     *        	文本
     *        	+----------------------------------------------------------
     * @return void 返回过滤之后的文本
     *         +----------------------------------------------------------
     * @throws ThinkExecption
     *         +----------------------------------------------------------
     */
    protected function remoteimg($data, $create_time = null) {
    	if (! empty ( $create_time )) {
    		$time_array = array ();
    		$time_array = explode ( '-', $create_time );
    		// 文件保存目录路径
    		$imgPath = USER_DATA_DIR_ROOT . DIRECTORY_SEPARATOR . $time_array [0] . DIRECTORY_SEPARATOR . $time_array [1] . DIRECTORY_SEPARATOR . $time_array [2] . DIRECTORY_SEPARATOR;
    		$imgUrl_one = "/data/userdata/" . $time_array [0] . "/" . $time_array [1] . '/' . $time_array [2] . '/';
    	} else {
    		$imgPath = USER_DATA_DIR_ROOT . DIRECTORY_SEPARATOR . date ( "Y" ) . DIRECTORY_SEPARATOR . date ( "m" ) . DIRECTORY_SEPARATOR . date ( "d" ) . DIRECTORY_SEPARATOR;
    		$imgUrl_one = "/data/userdata/" . date ( "Y" ) . "/" . date ( "m" ) . '/' . date ( "d" ) . '/';
    	}
    	import ( "ORG.Util.Image" );
    	// 日期名
    	$milliSecond = time ();
    	$img_array = array ();
    	$data = html_entity_decode ( $data );
    	$pattern = '/<[img|IMG].*?src=[\'|\"](http.*?[gif|jpg|jpeg|bmp|png])[\'|\"].*?[\/]?>/';
    	preg_match_all ( $pattern, $data, $img_array );
    	$img_array = array_unique ( $img_array [1] );
    	$arr = array ();
    	foreach ( $img_array as $key => $value ) {
    		$get_file = @file_get_contents ( $value );
    		$arr = explode ( '.', $value );
    		$count = count ( $arr );
    		$fileurl = $imgPath . $milliSecond . $key . '.' . $arr [$count - 1];
    		$imgUrl = $imgUrl_one . $milliSecond . $key . '.' . $arr [$count - 1];
    		if ($get_file) {
    			dir_create ( $imgPath );
    			$fp = @fopen ( $fileurl, 'w' );
    			@fwrite ( $fp, $get_file );
    			@fclose ( $fp );
    			Image::thumb ( $fileurl, $fileurl, "", 500, 500 );
    		}
    		$data = str_replace ( $value, $imgUrl, $data );
    	}
    	return $data;
     }
	
	/**
	 * 分享上传图片
	 */
	public function UploadSharePic() {
		$img_parts = pathinfo ( $_FILES ['file_tu'] ['name'] );
		$imginfo = getimagesize ( $_FILES ["file_tu"] ["tmp_name"] );
		$max_width = $imginfo [0];
		$max_height = $imginfo [1];
		if ($max_width > 500) {
			$thumb_width = 500;
			$thumb_height = $max_height * (500 / $max_width);
		} else {
			$thumb_width = 500;
			$thumb_height = $max_height;
		}
		if ($_FILES ['file_tu']) {
			$type_arr = array (
					"jpg",
					"jpeg",
					"gif",
// 					"png" 
			);
			if (! in_array ( strtolower ( $img_parts ['extension'] ), $type_arr )) {
				echo "<script>alert('目前只支持jpg/jpeg/gif');</script>";
				exit ();
			}
			import ( "ORG.Net.UploadFile" );
			// 导入上传类
			$upload = new UploadFile ();
			// 设置上传文件大小
			$upload->maxSize = 5242880;
			// 设置上传文件类型
			$upload->allowExts = explode(',', 'jpg,gif,jpeg');
			// 设置附件上传目录
			$upload->savePath = "data/userdata/" . date ( "Y", time () ) . "/" . date ( "m", time () ) . "/" . date ( "d", time () ) . "/";
			dir_create ( $upload->savePath );
			// 设置需要生成缩略图，仅对图像文件有效
			$upload->thumb = true;
			// 设置引用图片类库包路径
			$upload->imageClassPath = 'ORG.Util.Image';
			// 设置需要生成缩略图的文件后缀
			// $upload->thumbPrefix = 'm_'; // 生产2张缩略图
			$upload->thumbPrefix = "loli_";
			// 设置缩略图最大宽度
			$upload->thumbMaxWidth = "$thumb_width";
			// 设置缩略图最大高度
			$upload->thumbMaxHeight = "$thumb_height";
			// 设置上传文件规则
			$upload->saveRule = time();
			// 删除原图
			$upload->thumbRemoveOrigin = true;
			if (! $upload->upload ()) {
				// 捕获上传异常
				$error = $upload->getErrorMsg ();
			} else {
				// 取得成功上传的文件信息
				$uploadList = $upload->getUploadFileInfo ();
			}
			if ($uploadList [0]) {
				// 上传成功
				$uploadfile = "/" . $uploadList [0] ["savepath"] . 'loli_' . $uploadList [0] ["savename"];
				echo "<script>parent.imgload('$uploadfile');	</script>";
				exit ();
			} else {
				echo "<script>alert('$error')</script>";
				exit ();
			}
		} else {
			echo "<script>alert('缺少参数');</script>";
			exit ();
		}
	}
	
	/**
	 * 发布QQ微博[DEMO]
	 */
	public function postQQWeibo($content, $pic, $url) {
		$userid = $this->getUserid ();
		$open_info = M ( "UserOpenid" )->where ( "uid=$userid AND type='qq' AND isbind=1" )->find ();
		if (! $open_info || ! $open_info ['accesstoken']) {
			return false;
			// R("user/qq_login");
		} else {
			$token = $open_info ['accesstoken'];
			$openid = $open_info ['openid'];
			require_once (QQ_OPEN_ROOT . "class/" . "QC.class.php");
			$qc = new QC ( $token, $openid );
			// 将content内容去掉@
			$content = D ( "Public" )->deleteContentSmilies ( $content );
			$content="#萝莉盒就是化妆品试用# ".$content;
			import ( "ORG.Util.String" );
			import ( "ORG.Util.Input" );
			$content = Input::deleteHtmlTags ( $content );
			$content = String::msubstr ( $content, 0, 120 );
			
			$url=$this->getShareToUrl($url,$userid);//处理过的地址
			
			$post ["content"] = $content . " " . $url;
			if (! $pic) {
				$pic = DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "weibo.jpg";
			} else {
				$arr = explode ( "/", $pic );
				$pic = implode ( DIRECTORY_SEPARATOR, $arr );
			}
			$post ['pic'] = "@" . PROJECT_ROOT_PATH . DIRECTORY_SEPARATOR . $pic;
			$ret = $qc->add_pic_t ( $post );
			return $ret;
		}
	}
	
	
	/**
	 * inviteidauth_encode
	 * 要求用户注册的链接加密
	 * @param string $inviteuserid
	 * @return string $authcode
	 */
	public function inviteidauth_encode($inviteuserid) {
		if (empty ( $inviteuserid ))
			return false;
		$mail_authcode = $inviteuserid . C ( 'COOKIE_AUTHKEY_SPLIT' ) . get_client_ip ();
		$code = base64_encode ( authcode ( $mail_authcode, "ENCODE", C ( 'COOKIE_AUTHKEY' ) ) );
		return $code;
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
	 * ajax关注
	 * @author litingting
	 */
	public function follow() {
		$whoid = $this->_request ( "whoid" );
		$userid = $this->getUserid ();
		$type = $this->_request ( "type" );
		if (  empty ( $userid )) {
			$this->ajaxReturn ( 0, '您还没有登录', -1 );
		}
	
		if (empty ( $whoid ) ||  empty($type)) {
			$this->ajaxReturn ( 0, '缺少参数', -1 );
		}
	
		if ($userid ==$whoid ) {
			$this->ajaxReturn ( 0, '不能关注自己', -2 );
		}
		$flag = D("Follow") ->addFollow($userid,$whoid,$type);
	
		if ($flag == 0) {
			$this->ajaxReturn ( 0, '不能重复关注', -3);
		} elseif($flag==true) {
	
			$this->ajaxReturn ( 0, '加为关注', 1 );
	
		}else{
			$this->ajaxReturn ( 0, "关注失败", -4 );
		}
	
	}
	

	/**
	 * ajax取消关注
	 * @author litingting
	 */
	public function cancel_follow() {
		$userid = $this->getUserid ();
		if(empty($userid)){
			$this->ajaxReturn ( 0, '您还未登录', -1 );
		}
		$whoid = $this->_request ( 'whoid' );
		$type = $this->_request ( 'type' );
		if (D("Follow")->delFollow($userid,$whoid,$type)) {
			$this->ajaxReturn ( 0, '取消关注成功', 1 );
		} else {
			$this->ajaxReturn ( 0, '取消关注失败', 0 );
		}
	}
	
	/**
	 * 查询关注状态
	 * 2为互相关注
	 * 1为关注
	 * -1为被关注
	 * -2为互相都没有关注
	 */
	public function getFollowStatus($userid, $whoid) {
		$user_behaviour_relation_mod = M ( "UserBehaviourRelation" );
		$where ['userid'] = $userid;
		$where ['whoid'] = $whoid;
		$where ['type'] = 'follow_uid';
		$where1 ['userid'] = $whoid;
		$where1 ['whoid'] = $userid;
		$where1 ['type'] = 'follow_uid';
		if ($user_behaviour_relation_mod->where ( $where )->select ()) {
			if ($user_behaviour_relation_mod->where ( $where1 )->select ())
				return 2;
			else
				return 1;
		} else {
			if ($user_behaviour_relation_mod->where ( $where1 )->select ())
				return - 1;
			else
				return - 2;
		}
	}
	
	
	/**
	 * 获取当前登录用户的新浪第三方TOKEN
	 * @auth zhenghong@sohu.com
	 * @param $uid 用户在lolitabox网站的userid
	 * @param $content 同步到微博的内容
	 * @param $url 同步到微博的的图片的地址
	 * @param $contenturl 供新浪生成短链接的地址
	 */
	public function postSinaWeibo($uid, $content, $url = null, $contenturl = null) {
		if (! $uid)
			$uid = $this->getUserid ();
		if (empty ( $content ))
			return false;
		if (! $uid)
			return false;
		$user_open_info = $this->getUserSinaOpenInfo ( $uid );
		if (! $user_open_info)
			return false;
		$token = $user_open_info ["last_token"];
		require_once SINA_OPEN_ROOT . 'config.php';
		require_once SINA_OPEN_ROOT . 'saetv2.ex.class.php';
		$c = new SaeTClientV2 ( SINA_OPEN_AKEY, SINA_OPEN_SKEY, $token ['access_token'], $token );
		$contenturl=$this->getShareToUrl($contenturl,$uid);//处理过的地址
		if (! empty ( $contenturl )) {
			$contenturl_short = $this->shortenSinaUrl ( $contenturl );
			if (! empty ( $contenturl_short ))
				$content = $content . $contenturl_short;
		}
		if (! empty ( $url )) {
			$postweibo = $c->upload ( $content, $url );
		} else {
			$postweibo = $c->update ( $content );
		}
		return $postweibo [id];
	}
	
	
	
	/**
	 * 获取指定用户ID的SINA信息
	 * @param int $uid
	 * @return array $info
	 *         @auth zhenghong@sohu.com
	 */
	public function getUserSinaOpenInfo($uid) {
		$user_open_model = M ( "UserOpenid" );
		if ($user_open_info = $user_open_model->where ( "type='sina' AND uid=$uid AND isbind=1" )->find ()) {
			if($user_open_info['accesstoken']){
				$info['last_token']['access_token']=$user_open_info['accesstoken'];
			}else{
				$info = unserialize ( $user_open_info ["info"] );
			}
			return $info;
		} else {
			return false;
		}
	}
	
	
	/**
	 * 根据新浪微博API获取短链接地址
	 *
	 * @param $long_url 长链接地址
	 * @return $short_url 短链接地址
	 * @author zhenghong@sohu.com
	 *   API:http://open.weibo.com/wiki/2/short_url/shorten
	 */
	function shortenSinaUrl($long_url) {
		if (empty ( $long_url ))
			return $long_url;
		$long_url = urlencode ( $long_url );
		require_once SINA_OPEN_ROOT . 'config.php';
		$apiKey = SINA_OPEN_AKEY; // 这里是你申请的应用的API KEY，随便写个应用名就会自动分配给你
		$apiUrl = 'https://api.weibo.com/2/short_url/shorten.json?source=' . $apiKey . '&url_long=' . $long_url;
		$curlObj = curl_init ();
		curl_setopt ( $curlObj, CURLOPT_URL, $apiUrl );
		curl_setopt ( $curlObj, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $curlObj, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curlObj, CURLOPT_HEADER, 0 );
		curl_setopt ( $curlObj, CURLOPT_HTTPHEADER, array (
		'Content-type:application/json'
		) );
		$response = curl_exec ( $curlObj );
		curl_close ( $curlObj );
		$json = json_decode ( $response, true );
		return $json ['urls'] [0] ['url_short'];
	}
	
	/**
	 * 返回指定积分经验值配置表ID的指定值
	 */
	public function getCreditVal($actionid,$fieldname){
		$result=D("UserCreditSet")->getCreditValById($actionid,$fieldname);
		return $result;
	}
	
	/**
	 * 处理转发出去的地址
	 * @author penglele
	 */
	public function getShareToUrl($url,$userid){
		if($url){
			$url=PROJECT_URL_ROOT."public/hi.html?f=9999&uid=".$userid."&c=33&tourl=".urlencode($url);
		}
		return $url;
	}
	
	
	
}
?>
