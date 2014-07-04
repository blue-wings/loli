<?php
/**
 * 我的个人中心控制器
* @author penglele
*/
class homeAction extends commonAction {

	

	/**
	 * 个人中心首页
	 * @author penglele
	 */
	public function index(){
		$userid=$this->userid;
		//提示信息
		$user_order_mod=D("UserOrder");
		//订单列表
		$return['orderlist']=$user_order_mod->getUserOrderList($userid);
		//我的试用产品
		$return['productslist']=D("Products")->getUserOrderProductsList($userid,6);
		$where_share['userid']=$userid;
		//我的试用分享列表
// 		$return['sharelist']=D("UserShare")->getShareListByTry($where_share,6);
		$return['userinfo']=$this->userinfo;
		$return['title']=$return['userinfo']['nickname']."的主页-".C("SITE_NAME");
		$return['products_class']="list_col4_addbtn";
		$return['recommendlist']=D("Article")->getHomeRecommendList();
		$tips=$this->get_index_msg($this->userid);
		
		$if_finished=D("UserVote")->getUserIfFinished($userid);
		$return['if_finished']=$if_finished["finished"];

        //优惠券余额
        $price=D("Giftcard")->getUserGiftcardPrice($userid);
        $info['giftcard_price'] = $price;

        //我的订阅
        $model= new Model();
        $productSql = "select count(distinct(p.pid)) as productNum  from products as p right join users_products_category_subscribe as upcs  ON p.effectcid = upcs.product_category_id WHERE upcs.user_id=".$userid." and p.end_time > NOW() and p.start_time < NOW()";
        $productResult = $model->query($productSql);
        $productTryNum = 0;
        if(count($productResult) > 0){
            $productTryNum = $productResult[0]["productNum"];
        }
        $info['productTryNum'] = $productTryNum;

        //保管箱
        $userOrderSendProductDetail = D("UserOrderSendProductdetail");
        $selfPickUpProductCount = $userOrderSendProductDetail->getUserOrderNumByUserIdAndStatus($userid,C("USER_ODER_SEND_PRODUCT_STATUS_POSTAGE_NOT_PAYED"));
        //fixme: add code for auto send area products
        $autoSendProductCount = 0;//$userOrderSendProductDetail->getUserOrderNumByUserIdAndStatus($userid,1);
        $willExpiredNum = $userOrderSendProductDetail->getWillExpiredNumInSelfPickupProduct($userid);

        $info['selfPickUpProductCount'] = $selfPickUpProductCount;
        $info['autoSendProductCount'] = $autoSendProductCount;
        $info['totalProductCount'] = $selfPickUpProductCount + $autoSendProductCount;
        $info['willExpiredNum'] = $willExpiredNum;

        //我试用的产品
        $receiveTryProductNum = $userOrderSendProductDetail->getReceiveTryProductNum($userid);
        $info['receiveTryProductNum'] = $receiveTryProductNum;

        $this->assign("info",$info);
        $this->assign("tips",$tips);
        $this->assign("return",$return);
        $this->display();
	}
	

	/**
	 * 个人中心---基本信息
	 */
	public function information(){
		$userid =$this->userid;
		$u_info = D ( "Users" )->getUserInfo($userid);
		if (isset ( $_POST ['introduction'] )) {
			$user = M ( "UserProfile" );
			$data ['sex'] = $_POST ['sex'];
			$data ['years'] = $_POST ['year'];
			$data ['months'] = $_POST ['month'];
			$data ['days'] = $_POST ['day'];
			$data ['province'] = $_POST ['province'];
			$data ['city'] = $_POST ['city'];
			$data ['district'] = $_POST ['district'];
			$data ['edu'] = $_POST ['edu'];
			$data ['skin_property'] = $_POST ['skin'];
			$data ['hair_property'] = $_POST ['hair'];
			$data ['profession'] = $_POST ['professional'];
			$data ['description'] = $_POST ['introduction'];
			if($u_info['tel_status']!=1){
				$data ['telphone'] = $_POST ['telphone'];
			}
			$info = $user->where ( "userid={$this->userid}" )->save ( $data );
			D("UserCreditStat")->optCreditSet($userid,"user_profile_complete");
			$this->ajaxReturn ( $info, '成功', 1 );
		}else{
			
			if (empty ( $u_info ['province'] ) || $u_info ['province'] == null) {
				$u_info ['province'] = '请选择';
			}
			if (empty ( $u_info ['city'] ) || $u_info ['province'] == null) {
				$this->userinfo ['city'] = '请选择';
			}
			if (empty ( $u_info ['district'] ) || $u_info ['province'] == null) {
				$u_info ['district'] = '请选择';
			}
			if ($u_info ['years'] == 0) {
				$u_info ['years'] = '1988';
			}
			if ($u_info ['months'] == 0) {
				$u_info ['months'] = '1';
			}
			if ($u_info ['days'] == 0) {
				$u_info ['days'] = '1';
			}
			$return['userinfo']=$u_info;
			if($return['userinfo']['password']==""){
				$user_share=M("UserShare")->field("id")->where("userid=$userid AND ischeck=1 AND status>0")->find();
				if(!$user_share){
					$return['userinfo']['if_can']=1;
				}
			}
		    $return['yearlist'] = "";
		    for($t=date("Y"),$i=$t;$i>1960;$i--){
		    	if($i==$return['userinfo']['years']){
		    		$return['yearlist'].="<option value='{$i}' selected>{$i}</option>";
		    	}else{
		    		$return['yearlist'].="<option value='{$i}'>{$i}</option>";
		    	}
		    }
			$return['userinfo']['usersign']=$this->userinfo['usersign'];
			$return["title"]=$return['userinfo']['nickname']."的基本信息-".C("SITE_NAME");
			$this->assign("return",$return);
			$this->display();
		}

	}

	/**
	 * ajax获取星座（用于个人中心--基本信息）
	 */ 
	public function constellation() {
		$m = $_POST ['month'];
		$d = $_POST ['day'];
		$XZDict = array (
		'摩羯',
		'水瓶',
		'双鱼',
		'白羊',
		'金牛',
		'双子',
		'巨蟹',
		'狮子',
		'处女',
		'天秤',
		'天蝎',
		'射手'
		);
		$Zone = array (
		1222,
		120,
		219,
		321,
		420,
		521,
		622,
		723,
		823,
		923,
		1024,
		1123,
		1222
		);
		$da = array (
		'1222119',
		'120218',
		'219320',
		'321419',
		'420520',
		'521621',
		'622722',
		'723822',
		'823922',
		'9231023',
		'10231122',
		'11231221',
		'1222119'
		);
		if ((100 * $m + $d) >= $Zone [0] || (100 * $m + $d) < $Zone [1])
		$i = 0;
		else
		for($i = 1; $i < 12; $i ++) {
			if ((100 * $m + $d) >= $Zone [$i] && (100 * $m + $d) < $Zone [$i + 1])
			break;
		}
		$re1 = $da [$i] . '.jpg';
		$re2 = $XZDict [$i] . '座';
		$this->ajaxReturn ( $re1, $re2, 1 );
	}

	/**
	 * 个人中心--账号绑定
	 */
	public function account(){
		$userinfo=$this->userinfo;
		$userid=$this->userid;
		$bound=D("UserOpenid")->checkOpenLock($userid);
		$return['userinfo']=$userinfo;
		$return["title"]=$return['userinfo']['nickname']."的账号绑定-".C("SITE_NAME");
		$return['bound']=$bound;
		$returnurl=urlencode(PROJECT_URL_ROOT.U('home/account'));
		$return['returnurl']=$returnurl;
		$this->assign("return",$return);
		$this->display();
	}

	
	/**
	 * 个人中心--修改密码
	 * @author litingting
	 */
	public function amending(){
		if ($this->_post('pw') && $this->_post('pw1') && $this->_post('pw2')){

			if($this->_post('pw1') === $this->_post('pw2')){

				$u_info=D("Users")->getUserInfo($this->userid,"password");

				if(md5($this->_post('pw')) == $u_info['password']){
					$data = array(
					'userid'=>$this->userid,
					'password'=>md5($this->_post('pw1'))
					);

					if(false !== D("Users")->save($data)){
						$this->ajaxReturn ( 1, '修改密码成功!', 1 );
					}else{
						$this->ajaxReturn ( 0, '修改密码失败!', 0 );
					}
				}else{
					$this->ajaxReturn ( 0, '旧密码不正确!', 0 );
				}
			}else{
				$this->ajaxReturn ( 0, '两次密码不一致!', 0 );
			}
			exit();
		} else {
			$return ["title"] = $this->userinfo ['nickname'] . "的修改密码-".C("SITE_NAME");
			$return['userinfo']=$this->userinfo;
			$this->assign ( "return", $return);
			$this->display ();
		}
	}

	
	/**
	 * 个人中心--- ajax验证原始密码
	 * @author litingting
	 */
	function checkuseroldpw(){

		if($this->_post('param')){
			$u_info=D("Users")->getUserInfo($this->userid,"password");

			$result = md5($this->_post('param')) === $u_info['password']?1:0;

			if($result){
				echo "y";exit();
			}else{
				echo '验证失败!';exit();
			}
		}else{
			echo  "缺少参数";
		}
	}

	/**
	 * 个人中心--我的积分
	 */
	function score(){
		$userscore_mod = D("UserCreditStat");
		$count = $userscore_mod->getScoreCountByUserid($this->userid);
		$score_list = $userscore_mod->getScoreListByUserid($this->userid,$this->getlimit());
		$return["title"] = $this->userinfo ['nickname'] . "的积分-萝莉盒";
		$return['userinfo']   = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的积分-".C("SITE_NAME");
		$param = array(
		"total" =>$count,
		'result'=>$score_list,			//分页用的数组或sql
		'listvar'=>'list',				//分页循环变量
		'listRows'=>10,					//每页记录数
		'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
		'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:score_ajaxlist',//ajax更新模板
		);
		$this->page($param);
		$this->assign ( "return", $return);
		$this->display();
	}

	/**
	 * 个人中心--我的等级
	 */
	function level(){
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的等级-".C("SITE_NAME");
		$this->assign ( 'return',  $return);
		$this->display ();
	}

	/**
	 * 个人中心--我的萝莉盒
	 */
	public function boxorder(){
		$userid=$this->userid;
		$order_model = D( "UserOrder" );
		//支付成功的订单的总数
		$type=$_GET['type'];
		$type = $type ? $type : 1 ;
		//不包含在此的盒子类型
		$not_tye=D("Box")->returnBoxType();
		$data=array();
		$data['userid']=$userid;
		$data['type']=array("exp","not in($not_tye)");
		if($type==1){
			//已支付萝莉盒订单
			$data['state']=array("exp",">0");
			$data['ifavalid']=1;
		}else{
			//未支付萝莉盒订单
			$data['state']=0;
		}		
		$order_num=$order_model->getOrderNum($data,0);
		$order_list=$order_model->getOrderListByUserid($data,$this->getlimit());
		$return['type']=$type;
		$param = array(
		"total" =>$order_num,
		'result'=>$order_list,			//分页用的数组或sql
		'parameter' => "type=".$type,
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:boxorder_ajaxlist',//ajax更新模板
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的萝莉盒订单-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 试用订单
	 * @author penglele
	 */
	public function tryorder(){
		$userid=$this->userid;
		$order_model = D( "UserOrder" );
		//支付成功的订单的总数
		$state =$_GET['state'];
		$type=$_GET["type"];
		$state = $state ? $state : 1 ;
		$type = $type ? $type : 1;
		
		$data=array();
		//查看的类型
		if($type==2){
			$data['type']=C("BOX_TYPE_PAYPOSTAGE");
		}elseif($type==3){
			$data['type']=C("BOX_TYPE_EXCHANGE_PRODUCT");
		}else{
			$data['type']=array("exp","in(".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_EXCHANGE_PRODUCT").")");
		}
		
		$data['userid']=$userid;
		if($state==1){
			//已支付萝莉盒订单
			$data['state']=array("exp",">0");
		}else{
			//未支付萝莉盒订单
			$data['state']=0;
		}
		$data['ifavalid']=1;
		$order_num=$order_model->getOrderNum($data,0);
		$order_list=$order_model->getOrderListByUserid($data,$this->getlimit());
		$this->assign("state",$state);
		$this->assign("type",$type);
		$param = array(
				"total" =>$order_num,
				'result'=>$order_list,			//分页用的数组或sql
				'parameter' => "state=".$state."&type=".$type,
				'listvar'=>'list',			//分页循环变量
				'listRows'=>10,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'home:tryorder_ajaxlist',//ajax更新模板
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的试用订单-".C("SITE_NAME");
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	

	/**
	 * 个人中心--订单详情（已支付）
	 */
	public function order_detail(){
		$orderid=$_GET['id'];
		if(empty($orderid)){
			echo "<script>history.back(-1);</script>";exit;
		}
		$orderinfo=D("UserOrder")->getOrderDetail($orderid);
		if(!$orderinfo || $orderinfo['ifavalid']==0){
			$this->error("请求信息不存在");
		}
		if(!empty($orderinfo['proxyinfo'])){
			$orderinfo['proxyinfo']['proxyinfo']=nl2br($orderinfo['proxyinfo']['proxyinfo']);
		}
		$boxinfo=D("Box")->getBoxInfo($orderinfo['boxid'],"name,box_remark,category");
		$return['orderinfo']=$orderinfo;
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的订单详情-".C("SITE_NAME");
		$return['boxinfo']=$boxinfo;
		
		if($boxinfo['category']==C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$return['if_exchange']=1;
		}
		//付邮试用订单详情
		if($boxinfo['category']==C("BOX_TYPE_PAYPOSTAGE")){
			$return['if_try']=1;
			$tps="tryorder.inc";
		}else{
			$tps="boxorder.inc";
		}
		$return['products_class']="list_col4_addbtn";
		$this->assign("return",$return);
		$this->assign("tps",$tps);
		$this->display();
	}

	/**
	 * 个人中心--未支付萝莉盒
	 */
// 	public function mybox_nopay(){
// 		$userid=$this->userid;
// 		$order_model = M ( "UserOrder" );
// 		//支付成功的订单的总数
// 		$order_count = $order_model->where ( "userid=$userid AND state=0 AND type!='".C("BOX_TYPE_EXCHANGE")."'" )->count ();
// 		$where['userid']=$userid;
// 		$where['state']=0;
// 		$where['type']=array("exp","!=".C("BOX_TYPE_EXCHANGE"));
// 		$orderlist=D("UserOrder")->getOrderListByUserid($where,$this->getlimit());
// 		$param = array(
// 		"total" =>$order_count,
// 		'result'=>$orderlist,			//分页用的数组或sql
// 		'listvar'=>'list',			//分页循环变量
// 		'listRows'=>10,			//每页记录数
// 		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
// 		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
// 		'template'=>'home:mybox_nopay_ajaxlist',//ajax更新模板
// 		);
// 		$this->page($param);
// 		$return['userinfo']=$this->userinfo;
// 		$return["title"]=$return['userinfo']['nickname']."的未支付萝莉盒-".C("SITE_NAME");
// 		$return['orderlist']=$orderlist;
// 		$this->assign("return",$return);
// 		$this->display();
// 	}

	/**
	 * 个人中心--订单详情（未支付）
	 */
	public function mybox_npdetail(){
		$orderid=$_GET['id'];
		if(empty($orderid)){
			$this->error("非法请求","/");
		}
		$orderinfo=D("UserOrder")->getOrderDetail($orderid);
		if(!$orderinfo){
			$this->error("请求信息不存在");
		}
		$boxinfo=D("Box")->getBoxInfo($orderinfo['boxid'],"name,box_remark");
		$return['orderinfo']=$orderinfo;
		$return['userinfo']=$this->userinfo;
		$return['boxinfo']=$boxinfo;
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的未支付订单详情-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * 邀请好友
	 */
	public function myinvite(){
		$userid = $this->userid;
		$code = $this->inviteidauth_encode ( $userid );
		$inviteurl = "http://" . $_SERVER ["SERVER_NAME"] . U ( "user/reglogin", array (
		"u" => $code
		) );
		$return['inviteurl'] = $inviteurl;
		$user_mod = D("Users");
		$list = $user_mod ->getInviteList($userid,$this->getlimit(10));
		$count = $user_mod ->getInviteCount($userid);
		$param = array(
		"total" =>$count,
		'result'=>$list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:myinvite_ajaxlist',//ajax更新模板
		);
		$return['userinfo']=$this->userinfo;
		$return['count'] = $count;
		$this ->assign("return",$return);
		$this->page($param);
		$this->display();
	}

	/**
	 * 个人中心--修改头像
	 */
	public function face(){
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的修改头像-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * 图片上传预览
	 */
	public function preview_img(){
		$img_parts = pathinfo ( $_FILES ['userface'] ['name'] );
		$imginfo = getimagesize ( $_FILES ["userface"] ["tmp_name"] );
		if($_FILES['userface']){
			if (in_array ( strtolower ( $imginfo ["mime"] ), array ("image/jpeg","image/jpg"))) {
				//图片必须大于尺寸160
				$upload_result = $this->_uploadMyPhoto ();
				if ($upload_result [0]) {
					// 上传成功
					$uploadfile = $upload_result [1] [0] ["savepath"] . 'm_' . $upload_result [1] [0] ["savename"];
					$up_imginfo = getimagesize($uploadfile) ;
					echo "<script>
					parent.change_img('$uploadfile','{$up_imginfo[0]}','{$up_imginfo[1]}');
					</script>";
					exit();

				} else {
					$error =$upload_result [1];
					echo "<script>parent.error_dialog('暂时只支持小于5M的图片哦~')</script>";
					exit ();
				}

			}else{
				echo "<script>parent.error_dialog('暂时只支持JPG格式的图片~');</script>";
				exit ();
			}

		} else{
			echo "<script>parent.error_dialog('缺少参数');</script>";
		}

	}

	/**
	 * 剪裁图片保存
	 */
	public function save_userface(){
		$img =$_POST['imgpath'];
		if(empty($img) || empty($_POST['w']))
	    	$this ->ajaxReturn(0,"请选择剪切位置",0);
		$arr['x'] = $_POST['x'];
		$arr['y'] = $_POST['y'];
		$arr['width'] = $_POST['w'];
		$arr['height'] = $_POST['h'];
		$arr['uploaddir'] = "data/userdata/";
		$arr ['tempdir'] = "data/userdata/";
		$arr['temp_uploadfile'] = $_POST['imgpath'];
		$arr['thumb'] = true;
		$date = md5 ( time () );
		$user_face_dir_url = D ( "Users" )->getUserfacePrefix ( $this->userid );
		$arr ['new_uploadfile'] = "data/userdata/" . date ( "Y_m", time () ) . "/" . date ( "d", time () ) . "/" . strtolower ( $date ) . '.jpg';
		$this->asidoImg ( $arr );
		D("Task")->addUserTask(4,$this->userid); 
		
		$user_credit_stat_model = D ( "UserCreditStat" );
		$flag=$user_credit_stat_model->where("userid=".$this->userid." AND action_id='user_uploadface'")->find();
		if(empty($flag) && $_POST['type']!='system'){
			$user_credit_stat_model->optCreditSet ( $this->userid, 'user_uploadface' );
			$this ->ajaxReturn(0,"恭喜您，头像保存成功，获得5积分哦~继续完成其他任务>>",1);
		}
		$this ->ajaxReturn(0,"头像保存成功",1);

	}


	/**
	 * 将所有上传的图片转成JPG格式
	 *
	 * @param unknown_type $arr
	 */
	private function asidoImg($arr) {
		include_once ('framework/asido/class.asido.php');
		asido::driver ( 'gd' );
		$height = $arr ['height'];
		$width = $arr ['width'];
		$x = $arr ['x'];
		$y = $arr ['y'];
		$i1 = asido::image ( $arr ['temp_uploadfile'], $arr ['new_uploadfile'] );
		if ($arr ['thumb'] === true) {
			Asido::Crop ( $i1, $x, $y, $width, $height );

		} else {
			Asido::Frame ( $i1, $width, $height, Asido::Color ( 255, 255, 255 ) );
		}
		Asido::convert ( $i1, 'image/jpg' );
		$i1->Save ( ASIDO_OVERWRITE_ENABLED );
		$data = array (
		'photo' => $arr ['new_uploadfile']
		);
		$this->copyMyPhoto ( $data ['photo'] ); // 批量生成多种尺寸头像文件
	}


	/**
	 * 批量生成多种尺寸头像
	 * @param int $imgsrc
	 * @author litingting
	 */
	private function copyMyPhoto($imgsrc) {
		// echo $imgsrc;
		$user_profile_mod = M ( "UserProfile" );
		$user_face_dir_url = D ( "Users" )->getUserfacePrefix ( $this->userid );
		$userface_savedir = $user_face_dir_url ['dir'];
		dir_create($userface_savedir);    //生成图像路径

		$userid = $this->getUserid ();
		$uid_photo = $userface_savedir . $this->getUserid () . ".jpg";
		$data ["userface"] = $this->getUserid () . ".jpg";
		$userid = $this->getUserid ();
		$user_profile_mod->where ( "userid=" . $userid )->save ( $data );
		$uid_photo_180 = $userface_savedir . $userid . "_180_180.jpg";
		$uid_photo_100 = $userface_savedir . $userid . "_100_100.jpg";
		$uid_photo_55 = $userface_savedir . $userid . "_55_55.jpg";
		import ( "@.ORG.Util.Image" );
		Image::thumb ( $imgsrc, $uid_photo, "", 200, 200 );
		$array_myphoto ["200_200"] = $uid_photo;
		Image::thumb ( $imgsrc, $uid_photo_180, "", 180, 180 );
		$array_myphoto ["180_180"] = $uid_photo_180;
		Image::thumb ( $imgsrc, $uid_photo_100, "", 100, 100 );
		$array_myphoto ["100_100"] = $uid_photo_100;
		Image::thumb ( $imgsrc, $uid_photo_55, "", 55, 55 );
		$array_myphoto ["55_55"] = $uid_photo_55;
	}

	/**
	 *  上传我的头像到临时文件夹
	 */
	protected function _uploadMyPhoto() {
		import ( "ORG.Net.UploadFile" );
		// 导入上传类
		$upload = new UploadFile ();
		// 设置上传文件大小
		$upload->maxSize = 5242880;
		// 设置上传文件类型
		$upload->allowExts = explode ( ',', 'jpg,png,jpeg' );
		// 设置附件上传目录
		$upload->savePath = "data/userdata/" . date ( "Y_m", time () ) . "/". date ( "d", time () ) . "/";
		dir_create ( $upload->savePath );
		// 设置需要生成缩略图，仅对图像文件有效
		$upload->thumb = true;
		// 设置引用图片类库包路径
		$upload->imageClassPath = 'ORG.Util.Image';
		// 设置需要生成缩略图的文件后缀
		$upload->thumbPrefix = 'm_'; // 生产2张缩略图
		// 设置缩略图最大宽度
		$upload->thumbMaxWidth = '424';
		// 设置缩略图最大高度
		$upload->thumbMaxHeight = '388';
		// 设置上传文件规则
		$upload->saveRule = uniqid;
		// 删除原图
		$upload->thumbRemoveOrigin = true;
		if (! $upload->upload ()) {
			// 捕获上传异常
			return array (0,$upload->getErrorMsg ());
		} else {
			// 取得成功上传的文件信息
			$uploadList = $upload->getUploadFileInfo ();
			return array (1,$uploadList);
		}
	}


	/**
	 * 个人中心--优惠券
	 * +----------------------------------------------------------+
	 * status=1未使用，status=2已使用，status=3已过期
	 * +----------------------------------------------------------+
	 */
	public function coupon(){
		$userid=$this->userid;
		$where=array();
		$state=$_GET['state'];
		
		$ctime=date("Y-m-d H-i-s");
		if($state){
			if($_GET['state']==3){
				$where['status']=1;
				$where["endtime"]=array("exp","<'".$ctime."'");
			}else{
				if($_GET['state']==1){
					$where["endtime"]=array("exp",">='".$ctime."'");
				}
				$where['status']=$_GET['state'];
			}
		}

		$count_where=$where;
		$count_where['owner_uid']=$userid;
		$count=M("coupon")->where($count_where)->count();
		$coupon_list=D("Coupon")->getCouponListByUserid($userid,$where,$this->getlimit());
		$coupon_count=count($coupon_list);
		if($coupon_list){
			for($i=0;$i<$coupon_count;$i++){
				if($coupon_list[$i]['status']==1){
					$cdate=date("Y-m-d H-i-s");
					if($coupon_list[$i]['endtime']<$cdate){
						$coupon_list[$i]['status']=3;
					}
				}
				$coupon_list[$i]['endtime']=substr($coupon_list[$i]['endtime'],0,10);
				$coupon_list[$i]['starttime']=substr($coupon_list[$i]['starttime'],0,10);
			}

		}
		$this->assign("state",$state);
		$param = array(
		"total" =>$count,
		'result'=>$coupon_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:coupon_ajaxlist',//ajax更新模板
		);
		$this->page($param);

		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的优惠券-".C("SITE_NAME");
		$return['state']=$_GET['state'];
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * 个人中心--我的品牌资讯
	 * @author litinging
	 */
	public function follow(){
		$userid=$this->userid;
		$follow_mod=D("Follow");
		$return['userinfo']=$this->userinfo;
		$follow_num=D("Follow")->getFollowNumByUserid($userid,3);
		$follow_list=D("Follow")->getFollowListByUserid($userid,3,$this->getlimit(12));
		$param = array(
		"total" =>$follow_num,
		'result'=>$follow_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>12,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>"home:follow_ajaxlist",//ajax更新模板
		);
		$this->page($param);
		$return['brand_num']=D("Article")->getBrandInfoNumByUserid($userid);
		$return["title"]=$return['userinfo']['nickname']."的关注-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 品牌资讯
	 * @author litngting
	 */
	public function brand_msg(){
		$userid=$this->userid;
		$follow_mod=D("Follow");
		$return['userinfo']=$this->userinfo;
		$brandid = $_GET['id']?$_GET['id']:0;
		$follow_num=D("Article")->getBrandInfoNumByUserid($userid,$brandid);
		$follow_list=D("Article")->getBrandInfoByUserid($userid,$brandid,$this->getlimit(10));
		$param = array(
				"total" =>$follow_num,
				'result'=>$follow_list,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>10,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:brand_msg_ajaxlist",//ajax更新模板
		);
		$this->page($param);
		D("UserData")->updataUserData($this->userid,'brandinfo_num');
		$return["title"]=$return['userinfo']['nickname']."的品牌资讯-".C("SITE_NAME");
		$return['follownum']=D("Follow")->getFollowNumByUserid($userid,3);
		$this->assign("return",$return);
		$this->display();
	}
	

	/**
	 * 个人中心--我的粉丝/TA的粉丝
	 * 
	 * last update :zhenghong 2013/4/27 8:56 TITLE显示当前登录者的粉丝信息，实际应该显示正在访问空间主人的信息，TA的粉丝列表增加TYPE=1（MODEL)
	 */
	public function fans(){
		$userid=$this->userid;
		$spaceid=$this->spaceid;
		$return=$this->getInterestUserList();
		if($spaceid && $userid!=$spaceid) {
			//如果当前指定的spaceid与当前登录userid不同，则传入指定spaceid的info
			$return['userinfo']=$this->spaceinfo;
			$return['spaceinfo']=$this->spaceinfo;
			$return['top_sharelist']=$this->space_topsharelist;
			$display="fans";
			$template="fans_ajaxlist";
			$page_count=30;
			$fans_list=D("Follow")->getFansListByUserid($spaceid,$this->getlimit($page_count),$userid);
			$return['hotcontent'] = D("Article") ->getHotContent(6);
			$fans_num=$return['spaceinfo']['fans_num'];
			$return['spacead']=D("Article")->spaceAD();
		}
		else {
			$return['userinfo']=$this->userinfo;
			D("UserData")->updataUserData($userid,"newfans_num");
			$page_count=20;
			$display="myfans";
			$template="myfans_ajaxlist";
			$fans_num=$return['userinfo']['fans_num'];
			$fans_list=D("Follow")->getFansListByUserid($userid,$this->getlimit($page_count),$userid);
		}
		$param = array(
		"total" =>$fans_num,
		'result'=>$fans_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>$page_count,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>"home:$template",//ajax更新模板
		);
		$this->page($param);
		$return["title"]=$return['userinfo']['nickname']."的粉丝-".C("SITE_NAME");
		$return['fans_num']=$fans_num;
		$this->assign("return",$return);
		$this->display("$display");
	}

	/**
	 * 个人中心--私信
	 */
	public function msg(){
		$userid=$this->userid;
		$type= $_REQUEST['type'] ? $_REQUEST['type']:1;
		if($type>3){
			$type=1;
		}
		$msg_mod=D("Msg");
		if($type==1){
			D("UserData")->updataUserData($this->userid,'newmsg_num');
			$msg_num=$msg_mod->getReceverMsgCount($userid);
			$msg_list=$msg_mod->getReceverMsgListByUserid($userid,$this->getlimit(15));
		}elseif($type==2){
			$msg_num=$msg_mod->getPostMsgCount($userid);
			$msg_list=$msg_mod->getPostMsgListByUserid($userid,$this->getlimit(15));
		}elseif($type==3){
			D("UserData")->updataUserData($this->userid,'notice_num');
			$msg_list=$msg_mod->getMsgListByLolitabox($userid,$this->getlimit(15));
			$msg_num=$msg_mod->getMsgCountByLolitabox($userid);
		}
		$this->assign("count",$msg_num);
		$param = array(
		"total" =>$msg_num,
		'result'=>$msg_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>15,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:msg_ajaxlist',//ajax更新模板
		'parameter' =>"type=".$type,
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的私信-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * 个人中心--私信详情
	 */
	public function mymsg_detail(){
		$userid=$this->userid;
		$id=$_GET['id'];
		if(empty($id)) $this->error("非法操作","/");
		D("UserData")->updataUserData($userid,"newmsg_num");
		$to_userinfo=D("Users")->getUserInfo($id,"nickname");
		$msg_detail_num=D("Msg")->getMsgDialogueCount($userid,$id);
		$msg_detail_list=D("Msg")->getMsgDialogue($userid,$id,$this->getlimit());
		foreach($msg_detail_list as $key=>$val){
			if($val['from_uid']==$userid){
				$msg_detail_list[$key]['spaceurl']=getSpaceUrl($val['to_uid']);
			}else{
				$msg_detail_list[$key]['spaceurl']=getSpaceUrl($val['from_uid']);
			}
		}
		$param = array(
		"total" =>$msg_detail_num,
		'result'=>$msg_detail_list,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:mymsg_detail_ajaxlist',//ajax更新模板
		);
		$return=$this->getInterestUserList();
		$return['to_userinfo']=$to_userinfo;
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的私信详情-".C("SITE_NAME");
		$return['msg_num']=$msg_detail_num;
		$this->assign("return",$return);
		$this->page($param);
		$this->display();
	}

	/**
	 * 个人中心--删除私信详情中的某一条信息ajax
	 */
	public function delete_msg_dialog(){
		$userid=$this->userid;
		$id=$_POST['id'];
		if(empty($userid) || empty($id) || !$this->isAjax())
		$this->ajaxreturn(0,"非法操作",0);
		$res=D("Msg")->deletDialogueMsg($userid,$id);
		if($res==false)
		$this->ajaxreturn(0,"操作失败",0);
		else
		$this->ajaxreturn(1,"success",1);
	}



	/**
	 * 个人中心--分享
	 * @param int $ac [1--我的分享，2--我赞的分享，3--我踩的分享，4--我的评论]
	 * @param int $type [1--全部分享，2--晒盒分享，3--试用分享] 
	 * @param int $option [1--收到的评论,2--发出的评论]
	 * @author litingting
	 */
	public function share(){
		$user_share =D("UserShare");
		$ac = trim($this->_get("ac"));
		if($ac){
			if($ac==2){
				header("location:".U("home/shareagree"));exit;
			}else if($ac==3){
				header("location:".U("home/sharetread"));exit;
			}else if($ac==4){
				header("location:".U("home/comment"));exit;
			}
		}
		$type= trim($this->_get("type")) ? trim($this->_get("type")):0;   
		$option = trim($this->_get("option"));
		$pagesize = 20;
		$user_share = D("UserShare");
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的分享-".C("SITE_NAME");
		$template = "share_ajaxlist";
		if($type==2){
			$resourcetype=4;
		}else if($type==3){
			$resourcetype=1;
		}else{
			$resourcetype=0;
		}
		$list = $user_share->getMyShareList($this->userid,$resourcetype,$this->getlimit($pagesize));
		$count = $user_share->getMyShareNum($this->userid,$resourcetype);
		$this->assign("template",$template);
		$param = array(
		"total" =>$count,
		'result'=>$list	,		//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>$pagesize,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>"home:".$template,//ajax更新模板
		"parameter" => "type=".$type."&option=".$option,
		);
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 我赞的分享
	 * @author penglele
	 */
	public function shareagree(){
		$user_share =D("UserShare");
		$type= trim($this->_get("type")) ? trim($this->_get("type")):0;
		$option = trim($this->_get("option"));
		$pagesize = 20;
		$user_share = D("UserShare");
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的分享-".C("SITE_NAME");
		$template = "share_waterfall_ajaxlist";
		$list = $user_share ->getShareListByAction($this->userid,2,$this->getlimit($pagesize));
		$count = $user_share ->getShareNumByAction($this->userid,2);
		$return["title"]=$return['userinfo']['nickname']."赞的分享-".C("SITE_NAME");
		$this->assign("template",$template);
		$param = array(
				"total" =>$count,
				'result'=>$list	,		//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:".$template,//ajax更新模板
				"parameter" => "type=".$type."&option=".$option,
				);
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}	
	
	/**
	 * 我踩的分享
	 * @author penglele
	 */
	public function sharetread(){
		$user_share =D("UserShare");
		$type= trim($this->_get("type")) ? trim($this->_get("type")):0;
		$option = trim($this->_get("option"));
		$pagesize = 20;
		$user_share = D("UserShare");
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的分享-".C("SITE_NAME");
		$template = "share_waterfall_ajaxlist";
		$list = $user_share ->getShareListByAction($this->userid,1,$this->getlimit($pagesize));
		$count = $user_share ->getShareNumByAction($this->userid,1);
		$return["title"]=$return['userinfo']['nickname']."踩的分享-".C("SITE_NAME");
		$this->assign("template",$template);
		$param = array(
				"total" =>$count,
				'result'=>$list	,		//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:".$template,//ajax更新模板
				"parameter" => "type=".$type."&option=".$option,
				);
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}	
	
	
	public function comment(){
		$user_share =D("UserShare");
		$type= trim($this->_get("type")) ? trim($this->_get("type")):0;
		$option = trim($this->_get("option"));
		$pagesize = 10;
		$user_share = D("UserShare");
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的分享-".C("SITE_NAME");
		$template = "comment_ajaxlist";
		D("UserData")->updataUserData($this->userid,'unread_comment');
		if($option==2){
			$count=$user_share->getReceiverCommentNum(array('userid'=>$this->userid));
			$list=$user_share->getSendCommentListByUserid($this->userid,$this->getlimit($pagesize));
		}else{
			$count=$user_share->getReceiverCommentNum(array('to_uid'=>$this->userid));
			$list=$user_share->getReceiverCommentListByUserid($this->userid,$this->getlimit($pagesize));
		}
		$return["title"]=$return['userinfo']['nickname']."的评论-".C("SITE_NAME");
		$this->assign("template",$template);
		$param = array(
				"total" =>$count,
				'result'=>$list	,		//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:".$template,//ajax更新模板
				"parameter" => "type=".$type."&option=".$option,
				);
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}	
	
	
	/**
	 * 我转发的分享
	 * @author penglele
	 */
	public function shareout(){
		$pagesize = 20;
		$user_share = D("UserShare");
		$userid=$this->userid;
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."转发的分享-".C("SITE_NAME");
		$template = "share_waterfall_ajaxlist";
		$list = $user_share->getUserShareOutShareList($userid,$this->getlimit($pagesize));
		$count = $user_share->getUserShareOutShareNum($userid);
		$this->assign("template",$template);
		$param = array(
				"total" =>$count,
				'result'=>$list	,		//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>"home:".$template,//ajax更新模板
		);
		$this->page($param);
		$this->assign("return",$return);
		$this->display();
	}
	
	

	/**
	 * 个人中心--删除分享ajax
	 * @author litingting
	 */
	public function delete_share_ajax(){
		$userid=$this->userid;
		$id=$_POST['id'];
		if(empty($userid)  || empty($id) || !$this->isAjax())
		$this->ajaxReturn ( 0, "非法操作", 0 );
		$res=D("UserShare")->deleteUserShare($userid,$id);
		if($res==false){
			$this->ajaxReturn ( 0, "操作失败", 0 );
		}else{
			$this->ajaxReturn ( 1, "success", 1 );
		}
	}


	/**
	 *  发分享
	 *  @author litingting
	 */
	public function write_share(){
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"还没登录",0);
		}
		$share_content=$_POST['share_info'];
		$share_content=trim($share_content);
		$point=$_POST['point'];
		$share_type=$_POST['sharetype'];//暂定图文混排的类型2
		if($share_type==2){
			$share_data=D("Public")->getNewImgContent($share_content);
			$share_content=$share_data['clean_content'];
// 			$_POST['imgpath']=$share_data['clean_img'];
		}else{
			$share_content=htmlspecialchars($share_content);
		}
		//我觉得【选择的观点】与我的试用感受最接近。
		
		if($fstr=filterwords($share_content)){
			$this->ajaxReturn($fstr,"包含敏感词",-1);
		}
		
		if($share_type==2){
			if(!$share_content && !$share_data['clean_img'] && !$point){
				$this->ajaxReturn(0,"内容不能为空",0);
			}
		}else{
			if($share_content=="" && !$point){
				$this->ajaxReturn(0,"内容不能为空",0);
			}
		}
		

		if(!$share_content && $point){
			$share_content="我觉得【".$point."】与我的试用感受最接近。";
		}
		if($share_type==2){
			$img=$share_data['clean_img'];
		}else{
			if($_POST['imgpath']!=""){
				$img=$_POST['imgpath'];
			}else{
				$img="";
			}
		}

		$sourceid = trim($_POST['resourceid'])?trim($_POST['resourceid']):0; ;
		$sourcetype = trim($_POST['resourcetype'])?trim($_POST['resourcetype']):0;
		$ret=D("UserShare")->addShare($userid,$share_content,$img,0,array(),$sourceid,$sourcetype,$share_data);
		if($ret==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			$flag = 0;     //标识他是否是个任务
			if($sourcetype==4){
				D("Task")->addDryBoxTask($this->userid,$sourceid,$ret);
			}else if($sourcetype ==1){
				$where = array();
				$current = time();
				$where ['from'] = array("lt",$current);
				$where ['to'] = array("egt",$current);
				$where ['relationid'] = $sourceid;
				$where ['status'] = 1;
				$where ['taskid'] = 9;
				$info=M("taskChild")->where($where)->find();
			//	file_put_contents("1.txt", M("taskChild")->getLastSql());
				if($info){
					$flag = $info['id'];
					D("Task")->addUserTask($this->userid,9,$info['id'],$ret,3);
				}
			}
			if($flag){
				$this->ajaxReturn($flag,$ret,1);
			}
			$this->ajaxReturn(1,$ret,1);
		}
	}
	
	/**
	 * 发分享并转发到微博，微博的内容组合
	 * @param string $content
	 * @param int $type
	 * @param string $id
	 * @param string $point
	 * @author penglele
	 */
	public function getShareToContent($content,$type,$id,$point=""){
		$share_data=D("Public")->getNewImgContent($content);
		$content=$share_data['clean_content'];
		
		$last_c="想看更多精彩试用分享，赶紧登录萝莉盒网站了解一下吧！";
		$share_name="";
		$bweibo_name="";
		if($type==1){
			$pro_info=M("products")->where("pid=$id")->field("pname")->find();
			$share_name="#".$pro_info['pname']."#的试用分享，";
			$bweibo_name=D("ProductsBrand")->getBrandWeiboAccountByPid($id);
			if($bweibo_name){
				$bweibo_name="@".$bweibo_name." ";
			}
		}elseif($type==4){
			$boxinfo=D("UserOrder")->getBoxInfoByOrderid($id);
			if($boxinfo!=false){
				$share_name="#".$boxinfo['name']."#的晒盒分享，";
			}
		}
		//当用户发的内容较少时
		if(!$content){
			$content=$point;
		}
		$fel="";
		if($content){
			$fel="我的试用感受是：【".$content."】";
		}
		$content=" 网站发表了一篇".$share_name."求围观！".$bweibo_name.$fel.$last_c;
		return $content;
	}
	
	/**
	 * 发分享--并转发到微博
	 * @author penglele
	 */
	public function post_share_tothird(){
		$if_sina=$_POST['sina_type'];//转发到新浪
		$if_qq=$_POST['qq_type'];//转发到腾讯
		$id=$_POST['id'];//分享ID 
		$point=$_POST['point'];//试用观点
		$content=$_POST['content'];
		$userid=$this->userid;//用户ID 
		if($if_sina!=1 && $if_qq!=1 || !$id || !$userid || !$this->isAjax()){
			exit;
		}
		$shareinfo=D("UserShare")->getShareInfo($id);//分享详情
		$type=$shareinfo['resourcetype'];
		$resourceid=$shareinfo['resourceid'];
		if(!in_array($type,array(1,4))){
			exit;
		}
		$shareimg=$shareinfo['img_big'];//分享的图片
		//删除分享内容的表情
		$public_mod=D("Public");
		$share_content=$public_mod->deleteContentSmilies($content,1);
		//如果是对产品的分享，如果没有图片，则试用产品图片
		if(!$shareimg && isset($resourceid) && isset($type)){
			if($type==1){
				$pro_info=M("products")->where("pid=".$shareinfo['resourceid'])->field("pimg")->find();
				$shareimg=$pro_info['pimg'];
			}
		}
		$share_id=$id;
		$shareurl=getShareUrl($share_id);
		
		//如果用户发表的内容比较简短，对转发出去的内容进行处理
		$share_content=$this->getShareToContent($share_content,$type,$resourceid,$point);
			
		if($if_sina==1){
			//新浪
			$shareto_sina=$this->postShareSyncSinaWeibo($userid,$share_id,"我在@Lolitabox ".$share_content,$shareimg,$shareurl);
		}
		if($if_qq==1){
			//腾讯
			$share_qq=$this->postQQWeibo("我在@lolitaboxcn ".$share_content,$shareimg,$shareurl);
			//记录同步到第三方信息
			if($share_qq['ret']==0){
				$share_mod=D("UserShare");
				$share_mod->postUserShareOut($userid,$share_id,2);
				$share_mod->updateShareOutNum($share_id);
			}
		}
	}
	
	
	
	/**
	 *  发分享
	 *  @author litingting
	 */
	public function edit_share(){
		//判断用户
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"还没登录",0);
		}
		
		//判断分享ID
		$shareid = $this->_post("shareid");
		if(empty($shareid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		
		//发表的是图文混排的分享
		$sharetype=$_POST['sharetype'];
		
		//判断分享内容
		$share_content=$_POST['share_info'];
		$share_content=trim($share_content);
		
		if($sharetype==2){
			$share_data=D("Public")->getNewImgContent($share_content);
			$share_content=$share_data['clean_content'];
		}else{
			$share_content=htmlspecialchars($share_content);
		}
		
		if($fstr=filterwords($share_content)){
			$this->ajaxReturn($fstr,"包含敏感词",-1);
		}
		
		if($sharetype==2){
			if($share_content=="" && $share_data['clean_img']==""){
				$this->ajaxReturn(0,"内容不能为空",0);
			}
		}else{
			if($share_content==""){
				$this->ajaxReturn(0,"内容不能为空",0);
			}
		}
		
		//判断图片
		if($sharetype==2){
			$img=$share_data['clean_img'];
		}else{
			if($_POST['imgpath']!=""){
				$img=$_POST['imgpath'];
			}else{
				$img="";
			}		
		}
		$ret=D("UserShare")->updateShare($shareid,$userid,$share_content,$img,$share_data);
		
		if($ret==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			$if_sina=$_POST['sina_type'];
			$if_qq=$_POST['qq_type'];
			if($if_sina==1 || $if_qq==1){
				$resourceid=$_POST['resourceid'];
				$resourcetype=$_POST['resourcetype'];
				$shareimg=$img;
				if(!$shareimg && isset($resourceid) && isset($resourcetype)){
					if($resourcetype==1){
						$pro_info=M("products")->where("pid=$resourceid")->field("pimg")->find();
						$shareimg=$pro_info['pimg'];
					}elseif($resourcetype==2){
						$brand_info=M("ProductsBrand")->where("id=$resourceid")->field("logo_url")->find();
						$shareimg=$brand_info['logo_url'];
					}else{
						$shareimg="";
					}
				}
				$share_id=$ret;
				$shareurl=getShareUrl($share_id);
				$public_mod=D("Public");
				$shareto['shareto_content']=$public_mod->deleteContentSmilies($share_content,1);
				if($if_sina==1){
					$shareto_sina=$this->postShareSyncSinaWeibo($userid,$share_id,$share_content,$shareimg,$shareurl);
				}
				if($if_qq==1){
					$share_qq=$this->postQQWeibo($share_content,$shareimg,$shareurl);
					//记录同步到第三方信息
					if($share_qq['ret']==0){
						$share_mod=D("UserShare");
						$share_mod->postUserShareOut($userid,$share_id,2);
						$share_mod->updateShareOutNum($share_id);
					}
				}
			}
			$this->ajaxReturn(1,"success",1);
		}
	}
	

	/**
	 * 用户发表赞
	 */
	public function user_agree_share(){
		$userid=$this->userid;
		$shareid=$_POST['id'];
		$rootid=$_POST['rootid'];
		$content=$_POST['content'];
		$content=htmlspecialchars($content);
		if(empty($userid) || empty($shareid) || !$this->isAjax())
		$this->ajaxReturn(0,"非法操作",0);
		if($rootid==0)
		$rootid=$shareid;
		$rel=D("UserShare")->addAgree($userid,$shareid,$rootid,$content);
		$usercredit_mod=D("UserCreditStat");
		if($content){
			$share_info=M("UserShare")->field("userid")->where("id=$shareid")->find();
			$flag = D("UserShare") ->addComment($userid,$shareid,$content,$share_info['userid']);
			$usercredit_mod->optCreditSet($userid,"user_share_commnent");
		}
		if($rel>0){
			$usercredit_mod->optCreditSet($userid,"user_share_agree");
			$this->ajaxReturn(1,"success",1);
		}else{
			if($rel==0){
				$this->ajaxReturn(0,"非法操作",0);
			}else if($rel==-1 || $rel==-3){
				$this->ajaxReturn(0,"操作失败",0);
			}else if($rel==-2){
				$this->ajaxReturn(100,"操作失败",0);
			}
		}
	}


	/**
	 * 用户地址
	 */
	public function address(){
		$return['list'] = D("Users") ->getAddressList($this->userid);
		$return['count'] = count($return['list']);
		$return['userinfo'] = $this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的地址-".C("SITE_NAME");
		$this ->assign("return",$return);
		$this ->display();
	}


	/**
	 * ajax添加数据
	 */
	public function add_address(){
		if(!$this->isAjax()){
			return false;
		}
		$id =$_POST['id'];
		if($id){      //编辑
			$data = $_POST;
			$data['if_active'] = $data['type'] ? 1:0;
			unset($data['type']);
			$data['addtime'] = date("Y-m-d H:i:s");
			$data['uid'] = $this->userid;
			$flag = D("Users") ->updateAddress($id,$data);
			if($flag!==false){
				$this->ajaxReturn ( 1, '编辑地址成功', 1 );
			}else{
				$this->ajaxReturn ( 0, '编辑地址失败', 0 );
			}
		}else{
			$data ['linkman'] = $this->_post ( 'linkman' );
			$data ['province'] = $this->_post ( 'province' );
			$data ['city'] = $this->_post ( 'city' );
			$data ['district'] = $this->_post ( 'district' );
			if($data['district']==null){
				$data['district']="";
			}
			$data ['address'] = $this->_post ( 'address' );
			$data ['postcode'] = $this->_post ( 'postcode' );
			$data ['telphone'] = $this->_post ( 'telphone' );
			$data ['userid'] = $this->userid;
			$data ['if_del'] = 0;
			$data['if_active'] = $_POST['type'] ? 1:0;
			$flag = D("Users") ->addAddress($data);
			if($flag > 0 ){
				$this->ajaxReturn ( 1, '添加地址成功', 1 );
			}else{
				$this->ajaxReturn ( 0, '添加地址失败', 0 );
			}
		}
	}
	
	

	// 逻辑上删除用户地址
	public function del_address() {
		$id = $this->_request ( "id" );
		if(empty($id)){
			$this->ajaxReturn ( 0, '缺少参数', 0 );
		}
		$address_mod = M ( "userAddress" );
		$data ['if_del'] = 1;
		$data ['id'] = $id;
		$data ['userid'] = $this->userid;
		$ret = $address_mod->save ( $data );
		if ($ret != false) {
			$this->ajaxReturn ( 1, 'del success', 1 );
		} else {
			$this->ajaxReturn ( 0, 'del fail', 0 );
		}
	}

	//设置默认地址
	public function set_address(){
		$id=$_POST['id'];
		$userid=$this->userid;
		if(D("Users")->setDefaultAddress($id,$userid)){
			$this->ajaxReturn(1,'update success',1);
		}else{
			$this->ajaxReturn(0,'fail',0);
		}
	}

	//编辑地址列表
	public function edit_address(){
		$id =$_REQUEST['id'];
		if($_POST['act'] == 'edit'){
			$data = $_POST;
			$data['if_active'] = $data['type'] ? 1:0;
			unset($data['type']);
			$data['addtime'] = date("Y-m-d H:i:s");
			$data['uid'] = $this->userid;
			$flag = D("Users") ->updateAddress($id,$data);
			if($flag!==false)
			$this->ajaxReturn(1,'success',1);
			else
			$this->ajaxReturn(0,'fail',0);
		}else{
			if(empty($id)){
				$this->error('缺少参数');
				die;
			}
			if(empty($this->userid)){
				$this->error('您没有登录');
				die;
			}
			$info = M("UserAddress")->where("id=".$id." AND userid=".$this->userid)->find();
			if(empty($info)){
				$this->error('地址不存在');
				die;
			}
			$return['userinfo']=$this->userinfo;
			$return["title"]=$return['userinfo']['nickname']."的修改地址-".C("SITE_NAME");
			$return['info']=$info;
			$return['list'] = D("Users") ->getAddressList($this->userid);
			$return['count'] = count($return['list']);
			$this ->assign("return",$return);
			$this->display();
		}
	}

    
	

	/**
	 * 删除评论
	 */
	public function delete_comment(){
		$userid=$this->userid;
		$id=$_POST['id'];
		if(empty($userid) || empty($userid) || !$this->isAjax()){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$ret=D("UserShare")->delComment($id,$userid);
		if($ret==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			$this->ajaxReturn(1,"success",1);
		}
	}


	/**
	 * 发私信
	 */
	public function write_user_msg(){
		$userid=$this->userid;
		$to_nick=trim($_POST['to_nick']);
		if(empty($userid) || empty($to_nick)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$userinfo=D("Users")->getUserInfoByData(array("nickname"=>"$to_nick"),'userid');
		if($userinfo==false){
			$this->ajaxReturn(0,"接收对象不存在",0);
		}
		$to_userid=$userinfo[0]['userid'];
		$msg_content=trim($_POST['msg_content']);
		$msg_content=htmlspecialchars($msg_content);
		if(empty($msg_content)){
			$this->ajaxReturn(0,"私信内容不能为空",0);
		}
		$res=D("Msg")->addMsg($userid,$to_userid,$msg_content);
		if($res==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			//D("UserData")->addUserData($to_userid,"newmsg_num",1);
			$this->ajaxReturn(1,"success",1);
		}
	}
	

	/**
	 * 用户签到，增加积分和经验
	 */ 
	public function user_sign() {
		$userid = $this->userid;
		$user_credit_stat_model = D ( "UserCreditStat" );
		if (! empty ( $userid )) {
			$rel = $user_credit_stat_model->optCreditSet ( $userid, 'user_sign' );
			if ($rel) {
				D("Task")->addUserTask($userid,11);
				$user_sign_day=D("UserData")->addUserData($userid,"sign_num");
				if($user_sign_day==false)
				$this->ajaxReturn ( 0, "fail", 0 );
				else
				$this->ajaxReturn ( $rel ['score'], "success", 1 );
			} else {
				$this->ajaxReturn ( 100, "fail1", 0 );
			}
		} else {
			$this->ajaxReturn ( 0, "fail2", 0 );
		}
	}
	/**
	 * 发表分享并同步到新浪微博
	 * @param unknown_type $userid
	 * @param unknown_type $pid
	 * @param unknown_type $content
	 * @param unknown_type $evaluate_last_id
	 */
	private function postShareSyncSinaWeibo($userid,$shareid="",$summary,$img="",$tourl){
		if(!$userid) return false;
		$summary=D("Public")->deleteContentSmilies($summary);
		$weibo_content="#萝莉盒就是化妆品试用# ".$summary;
		import("ORG.Util.String");
		import("ORG.Util.Input");
		$content=Input::deleteHtmlTags($weibo_content);
		$weibo_content=String::msubstr($weibo_content,0,120);
		if(!$img){
			$img=PROJECT_URL_ROOT."public/images/weibo.jpg";
		}else{
			$img=PROJECT_URL_ROOT.$img;
		}
		
		if($this->postSinaWeibo($userid,$weibo_content,$img,$tourl)){
			//记录分享同步到微博
			if($shareid){
				$share_mod=D("UserShare");
				$share_mod->postUserShareOut($userid,$shareid);
				$share_mod->updateShareOutNum($shareid);
			}
			return true;
		}
		else {
			return false;
		}
	}


	/**
	 * 获取用户的一些状态信息
	 */
	public function getUserState(){
		$userinfo=$this->userinfo;
		//判断用户是否激活邮箱
		if($userinfo['state']==2){
			$userstate['mail']=1;
		}else{
			$userstate['mail']=0;
		}
		//判断用户是否绑定新浪
		$bound=D("UserOpenid")->checkOpenLock($this->userid);
		if($bound['sina']!=""){
			$userstate['sina']=1;
		}else{
			$userstate['sina']=0;
		}
		return $userstate;
	}

	//积分兑换
	public function exchange_score() {
		$userid=$this->userid;
		$ndate=date("Y-m-d");
		$boxinfo=M(" box")->where("category=".C("BOX_TYPE_EXCHANGE")." AND starttime<='".$ndate."' AND endtime>='".$ndate."'")->order("boxid DESC")->find();
		if($boxinfo){
			$total_order_num=D("UserOrder")->getOrderNum(array("boxid"=>$boxinfo['boxid']));;
			if($total_order_num>=$boxinfo['quantity']){
				$return['boxid']="";
			}else{
				$return['boxid']=$boxinfo['boxid'];
			}
		}else{
			$return['boxid']="";
		}
		$order_model = M ( "UserOrder" );
		//积分兑换的订单总数
		$order_count = $order_model->where ( "userid=$userid AND ifavalid=1 AND type='".C("BOX_TYPE_EXCHANGE")."'" )->count ();
		$where['userid']=$userid;
		$where['type']=C("BOX_TYPE_EXCHANGE");
		$orderlist=D("UserOrder")->getOrderListByUserid($where,$this->getlimit());
		$param = array(
		"total" =>$order_count,
		'result'=>$orderlist,			//分页用的数组或sql
		'listvar'=>'list',			//分页循环变量
		'listRows'=>10,			//每页记录数
		'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
		'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
		'template'=>'home:exchange_score_ajaxlist',//ajax更新模板
		);
		$return['userinfo']=$this->userinfo;
		$return['title'] = $return['userinfo']['nickname']. "的兑换明细-萝莉盒";
		$this->assign("return",$return);
		$this->page($param);
		$this->display();



	}

	/**
	 * 关闭个人中心向导图
	 */
	public function to_close_tips(){
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"fail",0);
		}
		D("UserBehaviourRelation")->addData($userid,1,1,"v4tips");
		$this->ajaxReturn(0,"success",1);
	}

	/**
	 * 分享到---判断用户是否已绑定新浪微博、
	 */
	public function user_if_bind(){
		$userid=$this->userid;
		if(empty($userid) || !$this->isAjax()){
			$this->ajaxReturn(0,"fail",0);
		}
		$bound=D("UserOpenid")->getBindDetail($userid);
		$this->ajaxReturn(1,$bound,1);
	}
	
	/**
	 * 转发分享到微博
	 * @author penglele
	 */
	public function shareto_third(){
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"fail",0);
		}
		$type_sina=$_POST['type_sina'];
		$type_qq=$_POST['type_qq'];
		$shareid=$_POST['shareid'];
		$shareinfo=$this->getSharetoInfoByShareid($shareid);
		$share_img=$shareinfo['img'];
		$share_url=$shareinfo['shareurl'];
		$share_name="";
		//对盒子发分享
		if($shareinfo['type']==4){
			$share_name="#".$shareinfo['name']."#的晒盒分享，";
		}else{
			//对产品
			$bweibo_info="";
			if($shareinfo['bweibo_name']){
				$bweibo_info="@".$shareinfo['bweibo_name']."  ";
			}
			$share_name="#".$shareinfo['name']."#的试用分享，";
		}
		
		$share_content="分享一篇".$share_name."我觉得很有趣！ ".$bweibo_info;
		$last="想看更多精彩试用分享，赶紧登录萝莉盒网站了解一下吧！";
		
		//转发到新浪微博
		if($type_sina==1){
			if($share_img){
				$shareto_sina_img=$share_img;
			}else{
				$shareto_sina_img="";
			}
			$share_tosina=$this->postShareSyncSinaWeibo($userid,$shareid,$share_content." @Lolitabox ".$last,$shareto_sina_img,$share_url);
		}else{
			$share_tosina=false;
		}
		$share_mod=D("UserShare");
		//转发到腾讯微博
		if($type_qq==1){
			$share_toqq=$this->postQQWeibo($share_content." @lolitaboxcn ".$last,$share_img,$share_url);
			if($share_toqq['ret']==0){
				$share_mod->postUserShareOut($userid,$shareid,2);
				$share_mod->updateShareOutNum($shareid);
			}
		}else{
			$share_toqq=-1;
		}
		if($share_tosina!==false || $share_toqq['ret']==0){
			$num=$share_mod->updateShareOutNum($shareid);
			$to_uid = $share_mod->where("id=".$shareid)->getField("userid");
			$nickname = M("Users")->where("userid=".$userid)->getField("nickname");
			$msg_mod = D("Msg");
			$msg = "<a href='".getSpaceUrl($userid)."'  class='WB_info'>{$nickname}</a>非常喜欢你的<a  href='".getShareUrl($shareid)."'class='WB_info'>分享</a>";
			$msg_mod ->addMsg(C("LOLITABOX_ID"),$to_uid,$msg);
			if(!$num){
				$num=0;
			}
			$this->ajaxReturn($num,"success",1);
		}else{
			$this->ajaxReturn(0,"分享失败",0);
		}
	}

	/**
	 * 关闭个人分享tips
	 */
	public function to_close_sharetips(){
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"fail",0);
		}
		D("UserBehaviourRelation")->addData($userid,1,1,"v4guide");
		$this->ajaxReturn(0,"success",1);
	}

	/**
	 * 试用订单--已支付
	 * @author penglele
	 */
// 	public function mytry(){
// 		$userid=$this->userid;
// 		$type=$_GET['type'];
// 		if($type){
// 			$where['type']=$type;
// 		}else{
// 			$where['type']=array("exp","in(".C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_TRY").")");
// 		}
// 		$where['userid']=$userid;
// 		$where['state']=array("exp",">0");
// 		$where['ifavalid']=1;
// 		$order_mod=D("UserOrder");
// 		$num=$order_mod->getOrderNum($where,0);
// 		$orderlist=D("UserOrder")->getOrderListByUserid($where,$this->getlimit());
// 	}
	
	
	/**
	 * 试用订单--未支付
	 * @author penglele
	 */
// 	public function mytry_nopay(){
// 		$userid=$this->userid;
// 		$order_mod=D("UserOrder");
		
// 		$boxid=$_GET['boxid'];
// 		if($boxid){
// 			$where['boxid']=$boxid;
// 		}else{
// 			$where['boxid']=array("exp","in(89,".C("TRY_BOX_ID").")");
// 		}
// 		$where['userid']=$userid;
// 		$where['state']=0;
		
// 		$num=$order_mod->getOrderNum($where,0);
// 		$orderlist=$order_mod->getOrderListByUserid($where,$this->getlimit());
// 	}
	
	/**
	 * 我的试用列表
	 * @author litingting
	 */
	public function try_products(){
		$pagesize = 8;
		$type = trim($_GET['type']) ? trim($_GET['type']):1;
		if($type==2){
			$get_product_list_method="getOrderProductlistOfNotShare";
			$get_product_count_method ="getOrderProductNumOfNotShare";
		}elseif($type==3){
			$get_product_list_method="getOrderProductlistOfShare";
			$get_product_count_method = "getOrderProductCountOfShare";
		}else{
			$get_product_list_method = "getUserOrderProductsList";
			$get_product_count_method ="getUserOrderProductsCount";
		}
		$products = D("Products");
		$list = $products->$get_product_list_method($this->userid,$this->getlimit($pagesize));
		$count = $products ->$get_product_count_method($this->userid);
		$return['products_class']="list_col4_addbtn";
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'home:try_products_ajaxlist',//ajax更新模板
			    "parameter" =>"type=".$type
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$return["title"]=$return['userinfo']['nickname']."的试用产品-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * ajax赞
	 * @param int $shareid
	 * @uses 赞
	 * @return string
	 * @author litingting
	 */
	public function agree(){
		$shareid= $this->_post("id");
		$userid = $this->userid;
		if(empty($shareid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		if(empty($userid)){
			$this->ajaxReturn(0,"您还未登录",0);
		}
		$share_mod = D("UserShare");
		$flag =$share_mod ->addShareAction($userid,$shareid,2);
		if($flag >0){
			$count = M("UserShareAction")->where("shareid=".$shareid." AND status=1 and type=2")->count();
			//转发到微博
			$bind=$this->getUserBindDetail($userid);//用户绑定微博的状态
			$this->ajaxReturn($count,$bind,1);
		}elseif($flag == -1){
			$this->ajaxReturn(0,"您已经踩过，不能再赞",0);
		}elseif($flag == -2){
			$this->ajaxReturn(0,"不能重复赞",0);
		}elseif($flag == -3){
			$this->ajaxReturn(0,"不能赞自己的分享",0);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	}
	
	/**
	 * 用户绑定微博的状态
	 * @param unknown_type $userid
	 * @return unknown
	 */
	public function getUserBindDetail($userid){
		$bind=D("UserOpenid")->getBindDetail($userid);
		return $bind;
	}
	
	/**
	 * 赞和踩的转发到 公共部分
	 * @author penglele
	 */
	public function action_shareto_common(){
		$userid=$this->userid;
		if(!$userid || !$this->isAjax()){
			exit;
		}
		$shareid=$_POST['id'];
		$type=$_POST['type'];//1:赞 2:踩
		$share_name="";
		$bind=D("UserOpenid")->getBindDetail($userid);
		//当用户绑定新浪或qq微博时执行
		if($bind['qq']==1 || $bind['sina']==1){
			$shareinfo=$this->getSharetoInfoByShareid($shareid);
			$share_type=array(1,4);
			if(in_array($shareinfo['type'],$share_type)){
				$shareurl=$shareinfo['shareurl'];
				//当此分享是针对订单或者产品发的分享时，才转发到微博
				$if_true="";
				if($type==1){
					//赞
					$if_true="赞一下，我很喜欢！";
				}else{
					//踩
					$if_true="踩一下，我不赞同！";
				}
				$if_account="";
				if($shareinfo['type']==1){
					//对产品发分享
					//判断该品牌是否有官微
					$weibo_account=$shareinfo['bweibo_name'];
					if($weibo_account!=''){
						$if_account="@".$weibo_account." ";
					}
					$share_name="#".$shareinfo['name']."#的试用分享，";
				}else if($shareinfo['type']==4){
					//对订单发分享
					$share_name="#".$shareinfo['name']."#的晒盒分享，";
				}
				$last="想看更多精彩试用分享，赶紧登录萝莉盒网站了解一下吧！";
				$mes="分享一篇".$share_name.$if_true.$if_account;
				$credit_mod=D("UserCreditStat");
				if($bind['qq']==1){
					//同步到腾讯微博
					$share_qq=$this->postQQWeibo($mes."@lolitaboxcn ".$last,$shareinfo['img'],$shareurl);
					if($share_qq['ret']==0){
						//送积分
						$credit_mod->optCreditSet($userid,"qq_weibo_share");
					}
				}
				if($bind['sina']==1){
					//同步到新浪微博
					$shareto_sina=$this->postShareSyncSinaWeibo($userid,'',$mes."@lolitabox ".$last,$shareinfo['img'],$shareurl);
					if($shareto_sina==true){
						//送积分
						$credit_mod->optCreditSet($userid,"sina_weibo_share");
					}
				}
			}
		}
	}
	
	/**
	 * ajax踩
	 * @param int $shareid
	 * @uses 踩
	 * @return string
	 * @author litingting
	 */
	public function tread(){
		$shareid= $this->_post("id");
		$userid = $this->userid;
		if(empty($shareid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		if(empty($userid)){
			$this->ajaxReturn(0,"您还未登录",0);
		}
		$share_mod = D("UserShare");
		$flag =$share_mod ->addShareAction($userid,$shareid,1);
		if($flag >0){
			$count = M("UserShareAction")->where("shareid=".$shareid." AND status=1 and type=1")->count();
			$bind=$this->getUserBindDetail($userid);//用户绑定微博的状态
			$this->ajaxReturn($count,$bind,1);
		}elseif($flag == -1){
			$this->ajaxReturn(0,"不能重复踩",0);
		}elseif($flag == -2){
			$this->ajaxReturn(0,"您已经赞过，不能再踩",0);
		}elseif($flag == -3){
			$this->ajaxReturn(0,"不能踩自己的分享",0);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	
	}
	
	
	/**
	 * 给用户发送手机绑定验证码
	 */
	public function send_tel_code(){
		$userid=$this->userid;
		$tel=$_POST['tel'];
		if(!$userid || !$this->isAjax() || !$tel){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$if_tel=D("Users")->addUserTelCode($userid,$tel);
		if(!$if_tel){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			if(is_numeric($if_tel)){
				$this->ajaxReturn($if_tel,"fail",0);
			}
			$this->ajaxReturn($if_tel,"success",1);
		}
	}
	
	/**
	 * 验证用户手机验证的验证码
	 */
	public function check_tel_code(){
		$userid=$this->userid;
		$code=$_POST['code'];
		if(!$userid || !$this->isAjax() || !$code){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$if_code=D("Users")->checkUserTelCode($userid,$code);
		if($if_code==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			if(is_numeric($if_code)){
				$this->ajaxReturn($if_code,"fail",0);
			}
			$this->ajaxReturn(1,"success",1);
		}
	}
	

	/**
	 * 个人中心首页提示信息
	 * @author penglele
	 */
	public function get_index_msg($userid){
		// 		$userid=3418;
		//未支付订单
		$time=strtotime("-3days",time());
		$ndate=date("Y-m-d H:i:s",$time);
	
		$where_order=array();
		$where_try=array();
	
		$data['userid']=$userid;
		$data['state']=0;
		$data['addtime']=array("exp",">='".$ndate."'");
		$data['ifavalid']=1;
	
		//未支付订单
		$where_order=$data;
		$not_type=C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_PAYPOSTAGE").",".C("BOX_TYPE_FREEGET");
		$where_order['type']=array("exp","not in($not_type)");
		$return['ordernum']=D("UserOrder")->getOrderNum($where_order,0);
	
		//未支付付邮试用订单
		$where_try=$data;
		$type_id=C("BOX_TYPE_EXCHANGE_PRODUCT").",".C("BOX_TYPE_PAYPOSTAGE");
		$where_try['type']=array("exp","in(".$type_id.")");
		$return['trynum']=D("UserOrder")->getOrderNum($where_try,0);
	
		//未读私信数
		$data = D("UserData")->getUserDatalistByUserid($userid);
		$return['msgnum']=empty($data['newmsg_num']) ? 0: $data['newmsg_num'];
	
		//待分享产品数
		$return['sharenum']=D("Products")->getOrderProductNumOfNotShare($userid);
	
		return $return;
	
	}
	
	/**
	 * 我入手的萝莉盒
	 * @author penglele
	 */
	public function box(){
		$userid=$this->userid;
		$user_order_mod=D("UserOrder");
		$num=$user_order_mod->getUserOrderCount($userid);
		$list=$user_order_mod->getUserOrderList($userid,$this->getlimit(12));
		$param = array(
				"total" =>$num,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>12,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'home:box_ajaxlist',//ajax更新模板
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * ++++++++++++++礼品卡start+++++++++++++++++++
	 */
	
	/**
	 * 我的礼品卡
	 * @author penglele 
	 */
	public function giftcard(){
		$userid=$this->userid;
		$giftcard_mod=D("Giftcard");
		$order_mod=D("UserOrder");
		$type=$_GET['type'];
		$type=($type && $type <=2 ) ? $type : 1 ;
		$limit_num=10;
		if($type==1){
			$num=$giftcard_mod->getUserGiftcardNum($userid);
			$list=$giftcard_mod->getUserGiftcardList($userid,$this->getlimit($limit_num));
		}else{
			$num=$order_mod->getUserOrderGiftcardNum($userid);
			$list=$order_mod->getUserOrderGiftcardList($userid,$this->getlimit($limit_num));
		}
		$info['type']=$type;
		$info['giftcard_price']=$giftcard_mod->getUserGiftcardPrice($userid);
		$info['totalprice']=$giftcard_mod->getUserGiftcardTotalPrice($userid);
		$info['cost_price']=$order_mod->getUserOrderGiftcardPrice($userid);
		$this->assign("info",$info);
		$param = array(
				"total" =>$num,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',				//分页循环变量
				'listRows'=>$limit_num,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'home:giftcard_ajaxlist',//ajax更新模板
		);
		$this->page($param);
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 激活礼品卡
	 * @author penglele
	 */
	public function activate_giftcard(){
		$userid=$this->userid;
		if(!$this->isAjax()){
			$this->ajaxreturn(0,"非法操作",0);
		}
		$giftcard_mod=D("Giftcard");
		$cid=$_POST['cid'];
		$pwd=$_POST['pwd'];
		$ret=$giftcard_mod->activateGiftcard($cid,$pwd,$userid);
		if(is_numeric($ret)){
			if($ret==1000){
				$this->ajaxreturn(0,"您还没有登录",0);
			}else if($ret==100){
				$this->ajaxreturn(0,"请输入正确的礼品卡卡号/密码~",0);
			}else if($ret==101){
				$this->ajaxreturn(0,"<p>礼品卡已被激活过，</p><p>请输入有效的礼品卡卡号和密码~</p>",0);
			}else if($ret==102){
				$this->ajaxreturn(0,"很抱歉，您的礼品卡已过了激活有效期~",0);
			}
		}
		if($ret==false){
			$this->ajaxreturn(0,"操作失败，您可以稍后重试~",0);
		}
		$this->ajaxreturn($ret['price'],"success",1);
	}
	
	/**
	 * 获取用户的优惠券余额
	 * @author penglele
	 */
	public function get_user_giftcard_price(){
		$userid=$this->userid;
		$price=D("Giftcard")->getUserGiftcardPrice($userid);
		$this->ajaxReturn($price,"success",1);
	}

	/**
	 * ++++++++++++++礼品卡end+++++++++++++++++++
	 */
	
	/**
	 * 用户---我要试用
	 * @author penglele
	 */
	public function user_tryout(){
		$id=$_POST['pid'];
		$type=$_POST['try_type'];
		$userid=$this->userid;
		if(!$id || !$type || !$userid || $type>2 || !$this->isAjax()){
			exit;
		}
		D("TryoutStat")->addTryout($userid,$id,$type);
		$this->ajaxReturn(1,'success',1);
	}
	
	/**
	 * 试用转发到微博
	 * @author penglele
	 */
	public function tryout_shareto(){
		$userid=$this->userid;
		$type=$_POST['type'];
		$name=$_POST['name'];
		$id=$_POST['id'];
		$img=$_POST['img'];
		$content=$_POST['content'];
		$sina=$_POST['sina'];
		$qq=$_POST['qq'];
		if(!$id || !$type || !$userid || $type>2 || !$this->isAjax()){
			exit;
		}
// 		$bind=$this->getUserBindDetail($userid);
		if(!$content){
			$content="我很想试用！";
		}
		if($sina==1 || $qq==1){
			if($type==1){
				$brand_account=D("ProductsBrand")->getBrandWeiboAccountByPid($id);
				$brand_info="";
				if($brand_account){
					$brand_info="@".$brand_account;
				}
				$shareurl=getProductUrl($id);
				$info="正在提供#".$name."#试用,  ".$brand_info." 【".$content."】注册会员发布试用愿望就有机会获得免费试用的机会哦！快来加入吧！";
			}elseif($type==2){
				$info="推出#".$name."#【".$content."】同期还有多款主题礼盒同时热售，适合不同的你！注册会员发布试用愿望就有机会获得免费试用的机会哦！快来加入吧！";
				$boxurl=M("Box")->where("boxid=".$id)->getField("special_url");
				if(empty($boxurl)){
					$shareurl=getBoxUrl($id);
				}else{
					$shareurl=$boxurl;
				}
			}
		}
		
		$credit_mod=D("UserCreditStat");
		//如果绑定了新浪微博
		if($sina==1){
			$shareto_sina=$this->postShareSyncSinaWeibo($userid,'',"刚刚发现在@lolitabox ".$info,$img,$shareurl);
			if($shareto_sina==true){
				//送积分
				$credit_mod->optCreditSet($userid,"sina_weibo_share");
			}
		}
		//如果绑定了腾讯微博
		if($qq==1){
			$share_qq=$this->postQQWeibo("刚刚发现在@lolitaboxcn ".$info,$img,$shareurl);
			//记录同步到第三方信息
			if($share_qq['ret']==0){
				//送积分
				$credit_mod->optCreditSet($userid,"qq_weibo_share");
			}
		}	
	}
	
	/**
	 * 通过分享id获取转发需要的信息
	 * @author penglele
	 */
	public function getSharetoInfoByShareid($id){
		$return=array(
				'shareurl'=>'',
				'nickname'=>'',
				'name'=>'',
				'bweibo_name'=>'',
				'img'=>'',
				'type'=>0
				);
		if(!$id){
			return $return;
		}
		//分享详情
		$shareinfo=D("UserShare")->getShareInfo($id);
		if(!$shareinfo){
			return $return;
		}
		$return['shareurl']=getShareUrl($id);
		$return['nickname']=M("Users")->where("userid=".$shareinfo['userid'])->getField('nickname');
		$return['name']=$shareinfo['boxname'];
		$return['img']=$shareinfo['img_big'];
		$return['type']=$shareinfo['resourcetype'];
		if($shareinfo['resourcetype']==1){
			$return['bweibo_name']=D("ProductsBrand")->getBrandWeiboAccountByPid($shareinfo['resourceid']);
		}
		return $return;
	}
	
	/**
	 * 我的特权
	 * @author penglele
	 */
	public function member(){
		$member_mod=D("Member");
		$order_mod=D("MemberOrder");
		$userid=$this->userid;
		$where['userid']=$userid;
		$where['state']=1;
		$where['ifavalid']=1;
		$list=$order_mod->getMemberOrderList($where,"ordernmb ASC");
		if($list){
			$stime="";
			$etime="";
			$ntime=date("Y-m-d");
			foreach($list as $key=>$val){
				$order_time=substr($val['paytime'],0,10);
				if($key==0){
					//第一条数据
					$stime=$order_time;
					$etime=date("Y-m-d",strtotime($stime.$val['m_type']." month"));
				}else{
					//当用户还在特权期内购买的订单
					if($order_time<=$etime){
						$stime=date("Y-m-d",strtotime($etime."1 day"));
						if($val['m_type']==12 && $etime<="2013-12=31"){
							//当用户购买的是年度会员时 &且& 上一次购买的在20131231前
							$etime="2014-12-31";
						}else{
							$etime=date("Y-m-d",strtotime($stime.$val['m_type']." month"));
						}
					}else{
						//用户的特权已过期
						$stime=$ntime;
						if($val['m_type']==12 && $etime<="2013-12=31"){
							//在2013年底前购买年度特权有优惠
							$etime="2014-12-31";
						}else{
							$etime=date("Y-m-d",strtotime($stime.$val['m_type']." month"));
						}
					}
				}
				$list[$key]['from']=$stime;
				$list[$key]['to']=$etime;
			}
		}
		arsort($list);
		$return['list']=$list;
		$return['memberinfo']=$member_mod->getUserMemberInfo($userid);
		if($return['memberinfo']['state']==1){
			$up_time=strtotime($return['memberinfo']['date'])-strtotime(date("Y-m-d"));
			$return['memberinfo']['uptime']=$up_time/24/60/60;
		}
		$return['title']="我的特权-".C("SITE_NAME");
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 美丽档案
	 * @author penglele
	 */
	public function vote(){
		$userid=$this->userid;
		$return['alist']=D("UserVote")->getUserVoteList($userid);
		$return['userinfo']=$this->userinfo;
		$return['title']=$return['userinfo']['nickname']."的美丽档案-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 完成美丽档案
	 * @author penglele
	 */
	public function set_vote(){
		$qid=$_POST['qid'];
		$data=$_POST;
		$userid=$this->userid;
		if(!$qid || !$userid){
			$this->ajaxReturn(0,"操作失败，请稍后重试！",0);
		}
		$rel=D("UserVote")->addUserVote($qid,$userid,$data);
		if($rel==0){
			$this->ajaxReturn(0,"操作失败，请稍后重试！",0);
		}elseif($rel==1){
			$this->ajaxReturn(1,"success",1);
		}else{
			$this->ajaxReturn(100,"修改成功",1);
		}
	}
	
	/**
	 * 美丽档案更新以前的数据
	 * @author penglele
	 */
	public function up_user_vote(){
		D("UserVote")->setUserVote();
	}
	
	/**
	 * 添加/修改订单赠言
	 * @author penglele
	 */
	public function add_sendword(){
		$orderid=$_POST['orderid'];
		$childid=$_POST['childid'];
		$type=$_POST['type'];
		$userid=$this->userid;
		$content=$_POST['content'];
		if(!$content){
			$this->ajaxReturn(0,"内容不能为空",0);
		}
		if(!$orderid || !$childid || !$userid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$ret=D("UserOrder")->addOrderSendWord($orderid,$childid,$content,$userid,$type);
		$msg=$type==1 ? "添加" : "修改" ;
		if($ret){
			$this->ajaxReturn(1,$msg."成功~",1);
		}else{
			$this->ajaxReturn(0,$msg."失败，请稍后重试！",0);
		}
	}
	
	
	
}
?>