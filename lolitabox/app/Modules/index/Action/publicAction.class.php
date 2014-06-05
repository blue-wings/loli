<?php
/**
 * 全局功能控制器
 * @author zhenghong
 */
class publicAction extends commonAction {


	/**
	 * EDM 代码-统计图片展示数
	 * @author zhenghong
	 */
	public function imgload() {
		header("Content-type: image/png");
		$im = @imagecreate(1,1)  or die("Cannot Initialize new GD image stream");
		$background_color = imagecolorallocate($im, 255, 255, 255);
		imagepng($im);
		imagedestroy($im);
		$ipaddress = get_client_ip ();
		$dt = date ( "Y-m-d H:i:s", time () );
		$edm_stat_mod = M ( "EdmStat" );
		$edm_no=$_REQUEST ["n"];
		if(!$edm_no) exit;
		$data = array (
		"edm_no" => $edm_no,
		"type" => "1",
		"ipaddress" => $ipaddress,
		"c_datetime" => $dt
		);
		$edm_stat_mod->add ( $data );
		Log::write ( "测试广告统计【展示】" . $ipaddress . "---" . date ( "Y-m-d", time () ), INFO );
	}

	/**
	 *  EDM 代码-统计图片点击数
	 * @author zhenghong
	 */
	public function eclick() {
		$edm_no=$_REQUEST ["n"];
		$to_url = base64_decode ( $_REQUEST ["s"] );
		$ipaddress = get_client_ip ();
		$dt = date ( "Y-m-d H:i:s", time () );
		$edm_stat_mod = M ( "EdmStat" );
		$data = array (
		"edm_no" => $edm_no,
		"type" => "2",
		"ipaddress" => $ipaddress,
		"c_datetime" => $dt
		);
		$edm_stat_mod->add ( $data );
		Log::write ( "测试广告统计【点击】---" . $to_url . "---" . $ipaddress . "---" . date ( "Y-m-d", time () ), INFO );
		//增加测试EDM订单转化效果
		$promotion_data=array(
		"f"=>"9001",
		"id"=>$edm_no,
		);
		$this->setPromotionCookie($promotion_data);
		header ( "location:$to_url" );
		exit ();
	}


	/**
	 * ajax记录登录用户行为轨迹
	 * @author litingting
	 */
	public function loli_trace(){
		$url=base64_decode(urldecode($_REQUEST['url']));
		if($userid=$this->getUserid()) {
			$_REQUEST['mobule']=$_REQUEST['mobule']?$_REQUEST['mobule']:C("DEFAULT_MODULE");
			$_REQUEST['action']=$_REQUEST['action']?$_REQUEST['action']:C("DEFAULT_ACTION");
			$a=$this->addUserTrace ( $url,$_REQUEST['module'],$_REQUEST['action']);
		}
	}

	/**
	 * 用户行为轨迹
	 * @param  $url代表用户访问地址
	 * @author litingting
	 */
	private function addUserTrace($url, $module, $action) {
		$userTrace_mod = M ( "UserBehaviourTrace" );
		$userid = $this->getUserid ();
		$ip = get_client_ip ();
		$optime = time ();
		$data = compact ( "userid", "url", "action", "module", "ip", "optime" );
		$userTrace_mod->add ( $data );
	}

	/**
      * 推广渠道中间跳转页
      * @author tingting&zhenghong
      */
	public function hi(){
		//根据参数判断是否需要统计点击数
		if($_GET["c"]){
			$data = array (
			"edm_no" => $_GET["c"],
			"type" => "2",
			"ipaddress" => get_client_ip (),
			"c_datetime" => date ( "Y-m-d H:i:s", time () )
			);
			M("EdmStat")->add($data);
		}
		$this->setPromotionCookie($_REQUEST);
		$tourl=empty($_REQUEST['tourl']) ? "/":urldecode($_REQUEST['tourl']);
		header("location:".$tourl);
	}
	
	
	public function rclick(){
		$tourl="http://mdetail.tmall.com/comboMeal.htm?spm=a1z10.1.w5003-5880586049.1.bYtmXX&comboId=232620000011066564&mainItemId=35421794089&scene=taobao_shop";
		$data["ip"]= get_client_ip ();
		$data["dtime"]=date ( "Y-m-d H:i:s", time () );
		$data["fromreferer"]=$_SERVER['HTTP_REFERER'];
		if(empty($data["fromreferer"])) $data["fromreferer"]="direct";
		M("JumpStat")->add($data);
		header("location:".$tourl);
	}
	
	
	/**
	 * ajax动态增加检测来源
	 * @author penglele
	 */
	public function add_promotion(){
		//根据参数判断是否需要统计点击数
		if($_POST["c"]){
			$data = array (
					"edm_no" => $_POST["c"],
					"type" => "2",
					"ipaddress" => get_client_ip (),
					"c_datetime" => date ( "Y-m-d H:i:s", time () )
			);
			M("EdmStat")->add($data);
		}
		$this->setPromotionCookie($_REQUEST);
		$this->ajaxReturn(1,"success",1);
	}
	
	

	/**
 	 * 推广着陆页【搜索、网盟链接着陆页】
 	 * @see publicAction::lp()
 	 */
	function lp(){
		$this->display();
	}

	/**
      * 兼容V3日志地址
      */
	public function blog_jump(){
		$blogid= $_GET['blogid'];
		$userid = $_GET['id'];
		if($blogid){
			$shareid = D("UserShare")->getShareidByBlogid($blogid);
			if($shareid){
				$url = getShareUrl($shareid, $userid);
				header("location:".$url);
			}else{
				$this->error("日志不存在");
			}
		}else{
			$this->error("参数错误");
		}

	}

	/**
      * 兼容v3评测详情地址
      */
	public function evaluate_jump(){
		$eid= $_GET['eid'];
		if($eid){
			$shareid = D("UserShare")->getShareidByBlogid($eid,1);
			if($shareid){
				$url = getShareUrl($shareid);
				header("location:".$url);
			}else{
				$this->error("日志不存在");
			}
		}else{
			$this->error("参数错误");
		}
	}

	/**
      * 获取到用户新动态数
      */
	public function new_msg(){
		$userid = $this ->userid;
		if(!$userid) {
			//当用户ID不存在时不作任何处理
			return false;
		}
		$html ="";
		$data = D("UserData")->getUserDatalistByUserid($userid);
		if($a=$data['notice_num']){
			echo "$('#noticenum').addClass('S_txt1');";
			echo "$('#noticenum').html('（".$a."）');";
		}else{
			echo "$('#noticenum').html('');";
		}
		
		if($b=$data['newmsg_num']){
			echo "$('#newmsg').addClass('S_txt1');";
			echo "$('#newmsg').html('（".$b."）');";
		}else{
			echo "$('#newmsg').html('');";
		}
		
		if($c=$data['unread_comment']){
			echo "$('#newcomment').addClass('S_txt1');";
			echo "$('#newcomment').html('（".$c."）');";
		}else{
			echo "$('#newcomment').html('');";
		}
		
		if($d=$data['brandinfo_num']){
			echo "$('#brand_info_num').show();";
			echo "$('#infonum').addClass('S_txt1');";
			echo "$('#infonum').html('（".$d."）');";
		}else{
			echo "$('#brand_info_num').hide();";
		}
		
	 	if($a+$b+$c+$d > 0){
			echo " $(function(){
         			    $('.date_style').mouseover();
		            });
			";
		} 
 	 	echo <<<en
		     setTimeout(function(){
               $.ajax({
                    type:'get',
                    dataType:'script',
                    url:'/public/new_msg'
	           })
	          // $('#new_msg_script').attr('src','/public/new_msg');  
	         
	     },30000);
en;
	}


	/**
      * 统计用户总的签到数
      */
	public function addUserSignNum(){
		$userlist=D("UserCreditStat")->getUserSignTotalNum();
	}


	/**
      * 用户动态行为轨迹
      * @author lit
      */
	public function user_trace(){
		$action = $_REQUEST['action'];
		$module = $_REQUEST['module'];
		$url = urldecode(base64_decode($_REQUEST['url']));
		if(empty($action)  || empty($module)  || empty($url)){
			return false;
		}

		$data = array(
		"module"  =>$module,
		"action"  =>$action,
		"url"   =>$url,
		);
		$userid= $this->getUserid();
		$data['userid'] = $userid ? $userid:0;
		$data['ip'] = get_client_ip();
		$data['optime'] = time();
		M("userBehaviourTrace")->add($data);
	}

	/**
      * 问卷调查提交
      * @author lit
      */
	public function surveyres(){
		$id = $_POST['surveyid'];
		$userid=$this->getUserid();
		if( $userid && $_SESSION['survey_'.$id."_u_".$userid]){
			$info = D("Task")->ifCurrentSurveyTask($id);
			if($info){
				if(M("TaskStat")->where("taskid=".$info['taskid']."AND userid=".$userid." AND childid=".$id." AND status=1")->find()){
					$this->ajaxReturn(-5,"had finished survey",0); //找不到任务
				}
				D("Task")->addUserTask($userid,7,$info['id']);
				$flag=D("UserCreditStat")->optCreditSet ($userid, 'user_join_survey' ,$info['credit'],0);    //加积分
				$this->ajaxReturn($flag['score'],"成功",1);
			}else{
				$this->ajaxReturn(-4,"task not found",0); //找不到任务
			}
			unset($_SESSION['survey_'.$id."_u_".$userid]);

		}else{
			$this->ajaxReturn(-2, "no flag",0); //参数错误
			//echo -2;     //没有标记
		}
	}

	/**
      * 调查问卷页
      * @author lit
      */
	public function survey(){
		$this->display();
	}

	//验证码
	Public function verify(){
		import('ORG.Util.Image');
		Image::buildImageVerify();
	}
	
	//检查验证码
	function CheckVerify(){
		$verify=trim($this->_post('param'));
		if(md5($verify) == $_SESSION['verify']){
			echo "y";
		}else{
			echo '验证码不正确';
		}
	}
	
	/**
	 * 友情链接
	 */
	public function friend_links(){
		$list = D("Article")->getFriendLinks();
		$html = "";
		foreach($list as $key =>$val){
			$html.=" <a href='{$val['url']}'  target='_blank'>".$val['title']."</a>";
		}
		//$html.="<a href='#' target='_blank'>更多>></a>";
		echo <<<eco
		$("#friends_link").append("{$html}");
eco;
	}
	
	/**
	 * 输出指定弹框内容
	 -----------------------------
	 * @param string $id 代表模板名
	 ------------------------------
	 * @author litingting
	 */
	public function dialog(){
		if(empty($_GET['id'])){
			echo '';
		}else{
			if($_GET['id']=='shareto'){
				if($shareid=$this->_post("shareid")){
					$return=D("UserShare")->getShareInfo($shareid);
				} 
				if(!$return['content']){
					$return['content']=$return['nickname']."的分享";
				}
				
			}else if($_GET['id']=='share'){
				$return['name']=D("UserShare")->getShareToName($_POST['pid'],$_POST['type']);
				$veiw_account=array();
				if($_POST['type']==1){
					$return['veiw_account']=D("Products")->getProductTryPointByPid($_POST['pid']);
				}
				$if_task=D("Task")->inTaskByProductID($_POST['pid']);
				$return['share_score']=$if_task==false ? 10 : 20 ;
				
				if($_POST['shareid']){
					$shareinfo=D("UserShare")->getShareInfo($_POST['shareid']);
					$return['name']=D("UserShare")->getShareToName($shareinfo['resourceid'],$shareinfo['resourcetype']);
					$return['type']=$shareinfo['boxname'];
					$content="";
					if($shareinfo['details']){
						foreach($shareinfo['details'] as $val){
							if($val['img']){
								$content=$content."<img src=\"".$val['img']."\" />";
							}
							$content=$content.$val['content'];
						}
					}else{
						$content=$shareinfo['content_all'];
					}
					$return['content']=$content;
				}
			}else if($_GET['id']=='tryto'){
				$return['pid']=$_POST['pid'];
				$return['type']=$_POST['type'];
				if($_POST['type']==1){
					//产品
					$proinfo=M("Products")->field("pname,pimg")->where("pid=".$return['pid'])->find();
					$return['img']=$proinfo['pimg'];
					$return['name']=$proinfo['pname'];
				}else if($_POST['type']==2){
					//盒子
					$boxinfo=M("Box")->where("boxid=".$return['pid'])->find();
					$return['img']=$boxinfo['pic'];
					$return['name']=$boxinfo['name'];
				}
			}else if($_GET['id']=='login'){
				$url=$_REQUEST['url'];
				if(in_array($url,array(PROJECT_URL_ROOT."user/reglogin.html",PROJECT_URL_ROOT."user/logout.html"))){
					$return_thurl=U("home/index");
				}else{
					$return_thurl=$url;
				}
				$return['thurl']=urlencode($return_thurl);
			}else if($_GET['id']=="sharemore"){
				$pid=$_POST["pid"];
				$return['pname']=M("Products")->where("pid=".$pid)->getField("pname");
				$return['sharemorelist']=D("UserShare")->getHotShareByPid($pid,20);
				$return['pid']=$pid;
			}else if($_GET['id']=="sendword"){
				$return['orderid']=$_POST['orderid'];
				$return['childid']=$_POST['childid'];
				$sendword=M("UserOrderSendword")->where("orderid=".$return['orderid']." AND child_id=".$return['childid'])->getField("content");
				if(!$sendword){
					$return['sw_title']="填写礼品卡赠言";
					$return['type']=1;
				}else{
					$return['sw_title']="修改礼品卡赠言";
					$return['type']=2;
					$return['sendword']=$sendword;
				}
			}else if($_GET['id']=="benefit"){
				$return['b_key']=$_POST['b_key'];
			}else if($_GET['id']=="proxy"){
				$proxy_info=D("UserOrder")->getUserOrderProxyInfo($_POST['orderid'],$_POST['childid']);
				if($proxy_info){
					$return['orderinfo']['proxyinfo']=$proxy_info;
					$return['orderinfo']['proxyinfo']['proxyinfo']=nl2br($return['orderinfo']['proxyinfo']['proxyinfo']);
				}
				$boxname=M("Box")->where("boxid=".$_POST['boxid'])->getField("name");
				$return['title_info']=$boxname."，订单号：".$_POST['orderid']."，快递物流信息(".$_POST['childid'].")";
				
			}else if($_GET['id']=="order_products"){
				$orderid=$_POST['orderid'];
				$childid=$_POST['childid'];
				$orderinfo=M("UserOrder")->field("userid,boxid")->where("ordernmb=".$orderid)->find();
				$return['orderinfo']['product_list']=D("UserOrder")->getOrderDetailList($orderid,$orderinfo['userid'],$childid);
				$return['boxinfo']['box_remark']=M("Box")->where("boxid=".$orderinfo['boxid'])->getField("box_remark");
				$return['show_type']=1;
				$return['products_class']="list_col4_addbtn";
				$return['title_info']=substr($childid,0,4)."年".substr($childid,4,2)."月";
			}
			$this->assign("return",$return);
			echo $this->fetch("public:dialog_".$_GET['id']);
		}
		
	}
	
	
	
	/**
	 * 获取某一段内容中@的用户列表
	 * @param string $content 需要处理的内容
	 * @return array $userlist @的用户列表
	 * @return penglele
	 */
	public function getUserAtList($content){
		$pattern = '/@[^@|^ ]+ /';
		$content=$content." ";
		preg_match_all ( $pattern, $content, $arr );
		$arr=array_unique($arr[0]);
		$userlist=array();
		foreach($arr as $value){
			$info=explode("@",trim($value));
			$nickname=$info[1];
			$userinfo=D("Users")->getUserInfoByData(array('nickname'=>$nickname),"userid");
			$userinfo=$userinfo[0];
			if($userinfo!=false){
				$userlist[]=$userinfo['userid'];
			}
		}
		return $userlist;
	}
	
	

	/**
	 * 回复分享或评论
	 * @author litingting
	 */
	public function reply_comment(){
		$userid = $this->getUserid ();
		if(empty($userid)){
			$this->ajaxReturn ( 0, '您还没有登录', 0 );
		}
			
		$to_uid =$this->_post('to_uid');
		$shareid = $this->_post('shareid');
		$content = $this->_post('content');
		if(empty($to_uid) || empty($shareid) ||  empty($content)){
			$this->ajaxReturn ( 0, '缺少参数', 0 );
		}
		if(isset($_POST['commentid'])){
			$to_commentid=$_POST['commentid'];
		}
		$user_atlist=$this->getUserAtList($content);
		$user_share_mod = D("UserShare");
		$flag = $user_share_mod ->addComment($userid,$shareid,$content,$to_uid,$to_commentid,$user_atlist);
		if($flag){
			$usercredit_mod=D("UserCreditStat");
			$info = $user_share_mod->getCommentInfo($flag);
			$info['posttime'] =date("Y-m-d H:i:s",$info['posttime']);
			$usercredit_mod->optCreditSet($userid,"user_share_commnent");
			$this->ajaxReturn ( $info, '发表成功', 1);
		}else
			$this->ajaxReturn ( 0, '发表失败', 0);
	
	}
	
	
	
	
	
	/**
	 * 分享详情
	 * @author litingting
	 */
	public function share(){
		$userid=$this->userid;
		$id=$_GET['id'];
		if(empty($id))
			$this->error("参数不全");
		$share_mod=D("UserShare");
		$share_info=$share_mod->getShareInfo($id);
		$share_userid=$share_info['userid'];
		$public_mod=D("Public");
		if(!$share_info || $share_info['status']==0){
			$this->error("请求信息不存在");
			exit;
		}
		if($share_userid==2375){
			$share_info['content_all']=text2links($share_info['content_all']);
		}
		$per_num=10;
		$count=$share_mod->getCommentCountByShareid($id);
		$list=$share_mod->getCommentListByShareid($id,$this->getlimit($per_num));
		if($userid==$share_userid){
			$display="home:share_detail";
			$template ="home:comment_list";
			$target = "ajax_content";
			$return['userinfo'] = $this->userinfo;
			$seo_nickname=$return['userinfo']['nickname'];
		}else{
			$display="space:share_detail";
			$template = "space:share_detail";
			$target = "ajax_content_comment";
			$return['u_info'] = D("Users")->getUserInfo($share_userid);
			$return['url'] = getSpaceUrl($share_userid);
			$return['agree_num'] = $share_mod ->getShareNumByAction($share_userid,2);
			$return['tread_num'] = $share_mod ->getShareNumByAction($share_userid,1);
			$return['pro_num'] = D("Products") ->getUserOrderProductsCount($share_userid);
			$seo_nickname=$return['u_info']['nickname'];
		}
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$per_num,			//每页记录数
				'target'=>$target,	//ajax更新内容的容器id，不带#
				'pagesId'=>'ajax_page_reply',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:comment_list",//ajax更新模板
		);
		$this->page($param);
		if($share_userid==$userid){
			$return["title"]=$return['userinfo']['nickname']."的分享详情-".C("SITE_NAME");
		}
		$return['shareinfo']=$share_info;
		$nickname = $return["u_info"] ? $return["u_info"]['nickname']:"我";
		if($share_info['tag']){
			$str = "-".$share_info['tag']['name'];
		}else{
			$str = "-".C("SITE_NAME");
		}
		$return['title'] = $nickname."的分享详情".$str;
		
		if($share_info['resourcetype']==1 && $share_info['resourceid']){
			$return['sourceinfo'] = D("Products")->getProductInfo($share_info['resourceid']);
		}
		
		if($userid !=$share_userid){     //当为TA的分享详情时
				if($share_info['resourcetype']==1 && $share_info['resourceid']){
					$return['sale_list'] = D("Products") ->getProductsOnSelling($share_info['resourceid']);
				}else if($share_info['resourcetype']==4 && $share_info['boxid']){
					$return['type']=4;
					$return['boxinfo']=D("UserOrder")->getBoxInfoByBoxid($share_info['boxid']);
				}
			$return['user_list'] = $share_mod ->getUserListByAction($id);
		}
		
		//seo
		import("ORG.Util.String");
		$seo_content=String::msubstr($share_info['content_all'] ,0,80,'utf-8');
		if($share_info['resourcetype']==1 && $share_info['resourceid']){
			//产品的分享详情
			$return['title']=$share_info['boxname']."的化妆品试用分享(".$seo_nickname.")_LOLITABOX-萝莉盒-化妆品试用";
			$brand_name=M("ProductsBrand")->where("id=".$return['sourceinfo']['brandcid'])->getfield("name");
			$brand_name = $brand_name ? ",".$brand_name : "" ;
			$effect="";
			if($return['sourceinfo']['effect'][2]){
				$effect=",".$return['sourceinfo']['effect'][2];
			}
			$return['keywords']=$share_info['boxname'].",".$share_info['boxname']."试用分享,".$share_info['boxname']."试用评测".$effect.$brand_name;
			$return['description']=$seo_nickname."通过萝莉盒进行了".$share_info['boxname']."的试用,".$seo_content;
		}else if($share_info['resourcetype']==4 && $share_info['boxid']){
			//对盒子的分享-分享详情
			$return['title']=$share_info['boxname']."的试用分享(".$seo_nickname.")_LOLITABOX-萝莉盒-化妆品试用";
			$return['keywords']=$share_info['boxname'].",试用评测,按月订购,按年订购";
			$boxintro=M("Box")->where("boxid=".$share_info['boxid'])->getField("box_intro");
			$boxintro = $boxintro ? ",".$boxintro : "" ;
			$boxintro=strip_tags($boxintro);
			$boxintro=str_replace(array("\r","\n"), "", $boxintro);
			$return['description']=$seo_nickname."购买了萝莉盒,".$share_info['boxname'].$boxintro.",".$seo_content;
		}
		
		$this->assign("template",$template);
		$this->assign("return",$return);
		$this->display($display);
	}
	
	/**
	 * 上传图片的参数设置
	 * @author penglele
	 */
	public function uploadPic() {
		$img_parts = pathinfo ( $_FILES ['file_tu'] ['name'] );
		$imginfo = getimagesize ( $_FILES ["file_tu"] ["tmp_name"] );
		if($imginfo==false){
			exit;
		}
		$max_width = $imginfo [0];
		$max_height = $imginfo [1];
		if ($max_width > 500) {
			$thumb_width = 500;
			$thumb_height = $max_height * (500 / $max_width);
		} else {
			$thumb_width = 500;
			$thumb_height = $max_height;
		}
		$max_size=$_POST['size'] ? (int)$_POST['size'] : 1;//文件最大值
		$max_size=$max_size*1024*1024;
		if ($_FILES ['file_tu']) {
			$type_arr = array (
					"jpg",
					"jpeg",
					"gif",
					"png"
			);
			if (! in_array ( strtolower ( $img_parts ['extension'] ), $type_arr )) {
				echo "<script>alert('目前只支持jpg/jpeg/png/gif');</script>";
				exit ();
			}
			import ( "ORG.Net.UploadFile" );
			// 导入上传类
			$upload = new UploadFile ();
			// 设置上传文件大小
			$upload->maxSize = $max_size;
			// 设置上传文件类型
			$upload->allowExts = explode ( ',', 'jpg,png,gif,jpeg' );
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
			$upload->saveRule = time ();
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
	
			//图片上传kindeditor编辑器
		public function  kindeditor_upload_json(){
			import("ORG.Util.Myupload");
			$myupload = new Myupload();
			$myupload->save_path = PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."userdata".DIRECTORY_SEPARATOR;
			$myupload->checkImg();
			$myupload->uploadfile();
			exit;
		}
	
}
?>