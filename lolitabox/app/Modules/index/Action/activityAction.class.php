<?php
//活动广场控制器

class activityAction extends commonAction{
	
	//活动广场列表
	function index(){
		header("location:"."/");
		$article_mod =  D("Article");
		$list['now']=$article_mod->getArticleListCommon(1,50,array('cate_id'=>673)); //正在进行
		$list['formerly']=$article_mod->getArticleListCommon(1,50,array('cate_id'=>674));//已经结束
	
		foreach ($list['now'] as $key => $value){
			if(strtotime($value['abst']) > 0 && strtotime($value['abst']) < strtotime(date('Y-m-d'),time())){
				array_push($list['formerly'],$value);
				unset($list['now'][$key]);
			}
			if(!strstr($value['abst'],'/')){
				$list['now'][$key]['explain']=$value['abst'];
				unset($list['now'][$key]['abst']);
			}
		}
		
		$return['title']  = "活动广场-LOLITABOX萝莉盒";
		$this->assign('return',$return);
		$this->assign('activity',$list);
		$this->display();
	}
	
	/**
	 * 达人申请
	 */
	public function apply(){
		$arr_daren=array();
		$userid=$this->getUserid();
		$darenapply_mod=M("UserDarenApply");
		$daren_list=$darenapply_mod->where("status=11")->limit(0,10)->order("apply_datetime DESC")->select();//达人列表
		$count=count($daren_list);
		for($i=0;$i<$count;$i++){
			$userinfo=D("Users")->getUserInfo($daren_list[$i]["userid"],"nickname,userface");
			$daren_list[$i]['userface_180_180']=$userinfo['userface_180_180'];
			$daren_list[$i]['nickname']=$userinfo['nickname'];
			$daren_list[$i]['spaceurl']=getSpaceUrl($daren_list[$i]["userid"]);
		}
		
		if(empty($userid) || !isset($userid)){
			$arr_daren="";
		}else{
			//申请达人需要达到的条件
			//1.完善基本信息   2.上传头像   3.绑定新浪微博   4.添加收货地址
			//1---2---
			$arr_mytask=$this->getFinishTaskStatus();
			$arr_daren[1]=$arr_mytask[2];
			$arr_daren[2]=$arr_mytask[1];
			//3---
			$user_openid_mod=M("UserOpenid");
			$sina_user_open=$user_openid_mod->where("type='sina' AND uid=".$this->getUserid())->find();
			if($sina_user_open){
				$arr_daren[3]=1;
			}else{
				$arr_daren[3]=0;
			}
			//4----
			$address_list=$this->ishaveuseraddress();
			if($address_list){
				$arr_daren[4]=1;
			}else{
				$arr_daren[4]=0;
			}
		}
		$return["title"]="达人认证-达人秀"."-萝莉盒";
		$return['arr_daren']=$arr_daren;
		$return['daren_list']=$daren_list;
		$return['userid']=$userid;

		$this->assign("return",$return);
		/* $this->assign("userid",$userid);
		$this->assign("arr_daren",$arr_daren);
		$this->assign("daren_list",$daren_list); */
		$this->display();
	}

	//检测是否已经申请
	public function check_ifdaren(){
		$userid=$this->getUserid();
		$darenapply_mod=M("UserDarenApply");
		$ret=$darenapply_mod->where("userid=$userid")->find();
		if($ret['status']==11){
			$this->ajaxReturn(100,"have a message",1);
		}elseif (isset($ret) && $ret['status']==0){
			$this->ajaxReturn(1,"have a message",1);
		}else{
			$this->ajaxReturn(0,"can make message",0);
		}
	}



	//达人申请，写入数据表
	public function check_darenidentify(){
		$userdaren_mod=M("UserDarenApply");
		$userid=$this->getUserid();
		$blog_url=$_POST['blog_url'];
		$weibo_url=$_POST['weibo_url'];
		$str1 = strstr($blog_url, "www", true);
		$str2 = strstr($weibo_url, "www", true);
		//blog地址
		if($str1==""){
			$data['blog_url']="http://".$blog_url;
		}else{
			$data['blog_url']=$blog_url;
		}
		//微博地址
		if($str2==""){
			$data['weibo_url']="http://".$weibo_url;
		}else{
			$data['weibo_url']=$weibo_url;
		}

		$data['weibo_url']=$_POST['weibo_url'];
		$data['qq']=$_POST['qq_num'];
		$expert=$_REQUEST['expert'];
		$data['update_current']=$_POST['update_current'];
		$data['apply_datetime']=date("Y-m-d H-i-s");

		if($userid==""){
			$this->ajaxReturn(0,"用户不能为空",0);
		}

		if($data['blog_url']==""){
			$this->ajaxReturn(0,"博客地址不能为空",0);
		}

		if($data['weibo_url']==""){
			$this->ajaxReturn(0,"微博地址不能为空",0);
		}

		if($data['qq']==""){
			$this->ajaxReturn(0,"QQ不能为空",0);
		}

		if($expert==""){
			$this->ajaxReturn(0,"擅长方向不能为空",0);
		}

		if($data['update_current']==""){
			$this->ajaxReturn(0," 更新LOLITABOX博客频率不能为空",0);
		}

		$arr=array();
		if(!$expert==""){
			$data['expert']=implode(",",$expert);
		}
		$rel=$userdaren_mod->where("userid=$userid")->find();
		if($rel['status']==11){
			$this->ajaxReturn(0,"您已经是达人了！",0);
		}else{
			if($rel){
				$data['status']=0;
				$ret=$userdaren_mod->where("userid=$userid")->save($data);
			}else{
				$data['userid']=$userid;
				$ret=$userdaren_mod->add($data);
			}
			if($ret){
				$this->ajaxReturn(1,"申请达人成功",1);
			}else{
				$this->ajaxReturn(0,"申请达人失败",0);
			}
		}
	}




	/**
		 * 夺宝乐翻天--免费得萝莉盒固定模式
		 */
	public function happybox(){
		//达人纷纭积分榜第五期，活动期号5
		$lottery_id=6;//定义活动期号
		//评测部分 单品ID列表
		$products_arr=array(
		'0'=>'63021',
		'1'=>'63022',
		'2'=>'63023',
		'3'=>'63024',
		'4'=>'63019',
		'5'=>'59551',
		'6'=>'7359',
		'7'=>'63018',
		);
		$pageinfo['title']="LOLITABOX第".$lottery_id."期达人积分风云榜-萝莉盒";
		$userid=$this->userid;
		//查看用户是否已绑定新浪微博$active_array[1]绑定微博,$active_array[2]激活邮箱
		if(!empty($userid)){
			
			$open_info=D("UserOpenid")->getBindDetail($userid);
			$active_array[1]=$open_info['sina'];
			
			//是否激活邮箱
/* 			$userinfo=M("users")->field("state,score")->where("userid=$userid")->find();
			if($userinfo['state']!=2){
				$active_array[2]=0;
			}else{
				$active_array[2]=1;
			} */
		}else{
			$active_array="";
		}
		$this->getFreePublicBoxInfo($products_arr,$pageinfo);//公共部分
		$userscore_list=$this->getActiveScoreList();//积分排行榜
		$userscore_count=count($userscore_list);
		foreach($userscore_list as $key=>$value){
			$userscore_list[$key]['spaceurl']=getSpaceUrl($value['userid']);
		}
		//用户分享到新浪微博
		$returnurl=urlencode(PROJECT_URL_ROOT.U('activity/sinaweibo'));//分享的方法
		$nowpage=urlencode(PROJECT_URL_ROOT.U('activity/happybox'));//用来操作成功后的定位

		if(!empty($userid)){
			$user_behaviour_mod=M("UserBehaviourRelation");
			$takeinfo=$user_behaviour_mod->where("userid=$userid AND whoid=$lottery_id AND type='scorelist'")->find();

			//判断用户是否已报名
			if(!$takeinfo){
				$takeinfo="";
			}else{
				$plan_list=$this->getHappyBoxPlanList($lottery_id);
				$stime=$plan_list['sdate']." 00:00:00";
				$etime=$plan_list['edate']." 23:59:59";
				$sql="SELECT userid, SUM(  `credit_value` ) AS uscore FROM  `user_credit_stat`  WHERE userid =".$userid." AND  `credit_type` =1 AND credit_value >0 AND  `add_datetime` >=  '".$stime."' AND  `add_datetime` <=  '".$etime."'";
				$my_model= new Model();
				$score=$my_model->query($sql);
				if($score[0]['uscore']==null){
					$userscore=0;
				}else{
					$userscore=$score[0]['uscore'];
				}
				$this->assign("userscore",$userscore);
			}
		}else{
			$takeinfo="";
		}
		//判断点击领取萝莉盒的按钮显示的颜色
		$g_sdate="2013-06-27 00:00:00";
		$g_edate="2013-06-30 23:59:59";
		$g_cdate=date("Y-m-d H:i:s");
		if($g_cdate<$g_sdate || $g_edate<$g_cdate){
			$button_color=0;
		}else{
			if(empty($userid)){
				//在用户可以领取盒子期间，如果用户没有登录，则显示红色
				$button_color=1;
			}else{
				$list=$this->get_happybox_score_list();
				$userlist=$list["userlist"];
				$scorelist=$list["scorelist"];
				if(!in_array($userid, $userlist)){
					//如果用户已登录，但是没有在前50名内，显示灰色，即用户不可点
					$button_color=0;
				}else{
					if((int)$scorelist[$userid]<500){
						//如果用户在前500名内，但是活动期间获得的积分没有达到500，灰色不可点
						$button_color=0;
					}else{
						//判断用户是否已领取过
						$behaviour_mod=M("UserBehaviourRelation");
						$data['userid']=$userid;
						$data['whoid']=$lottery_id;
						$data['type']="apply_box";
						$if_getbox=$behaviour_mod->where($data)->find();
						if($if_getbox){
							//用户已经领取过盒子，不可再领，灰色
							$button_color=2;
						}else{
							$button_color=1;
						}
					}
				}
			}
		}
		$last_score_list=$this->getLastUserScoreList();
		foreach($last_score_list as $ikey=>$val){
			$last_score_list[$ikey]['spaceurl']=getSpaceUrl($val['userid']);
		}
		$this->assign("nowpage",$nowpage);
		$this->assign("returnurl",$returnurl);
		$this->assign("open_info",$open_info);
		$this->assign("takeinfo",$takeinfo);
		$this->assign("lottery_id",$lottery_id);
		$this->assign("active_array",$active_array);
		$this->assign("userscore_list",$userscore_list);
		$this->assign("userscore_count",$userscore_count);
		$this->assign("button_color",$button_color);
// 		$this->assign("userinfo",$userinfo);
		$this->assign("last_score_list",$last_score_list);
		$this->display();
	}

	/**
		 * 夺宝乐翻天 公共部分
		 * @param $products_arr 活动期间评测可能活动双倍积分的产品ID数组Array
		 * @param $pageinfo 模板页面title [Array]
		 */
	private function getFreePublicBoxInfo($products_arr,$pageinfo){
		$u_credit_mod=M("UserCreditStat");
		$userid=$this->userid;
		$ntime=date("Y-m-d");
		//评测可能获得双倍积分的单品（判断用户是否对该产品进行过评测）
		$p_count=count($products_arr);
		$p_list=array();
		$pro_mod=M("products");
		for($i=0;$i<$p_count;$i++){
			$p_info=$pro_mod->field("pname,pimg")->where("pid=".$products_arr[$i])->find();
			$p_list[$i]['pname']=$p_info['pname'];
			$p_list[$i]['pimg']=$p_info['pimg'];
			$p_list[$i]['pid']=$products_arr[$i];
			$p_list[$i]['spaceurl']=getProductUrl($p_list[$i]['pid']);
		}
		$return['userid'] = $userid;
		$return['title'] = $pageinfo['title'];
		$this->assign("p_list",$p_list);
		$this->assign("return",$return);
	}

	public function getActiveScoreList(){
		$dir="./data".DIRECTORY_SEPARATOR."activity".DIRECTORY_SEPARATOR."topscore.txt";
		if(file_exists($dir)){//判断文件是否存在
			$userscore_list=unserialize(file_get_contents($dir));//前三十名用户的积分等情况
			if(!$userscore_list){
				$userscore_list="";
			}
		}else{
			$userscore_list="";
		}
		return $userscore_list;
	}



	//夺宝乐翻天每期的活动时间，及运行的报名人数
	private function getHappyBoxPlanList($id){
		$plan_list=array(
		'1'=>array(
		'sdate'=>'2013-02-01',
		'edate'=>'2013-02-28',
		//'amount'=>30,
		),
		'2'=>array(
		'sdate'=>'2013-03-01',
		'edate'=>'2013-03-28',
		),
		'3'=>array(
		'sdate'=>'2013-04-01',
		'edate'=>'2013-04-28',
		),
		'5'=>array(
		'sdate'=>'2013-05-03',
		'edate'=>'2013-05-28',
		),
		'6'=>array(
		'sdate'=>'2013-06-03',
		'edate'=>'2013-06-26',				
		),
		);
		return $plan_list[$id];
	}


	//积分榜中的名单
	public function get_happybox_score_list(){
		$score_list=$this->getActiveScoreList();
		foreach($score_list as $key=>$value){
			$list["userlist"][]=$value['userid'];
			$list["scorelist"][$value['userid']]=$value['uscore'];
		}
		return $list;
	}

	/**
		 * 夺宝乐翻天 报名
		 */
	public function check_take_partin(){
		$lottery_id=$_REQUEST['lottery_id'];
		if(!$this->isAjax() || empty($lottery_id)){
			$this->ajaxReturn(0,"非法操作！",0);
		}
		$userid=$this->userid;
		if(empty($userid)){
			$this->ajaxReturn(0,"非法操作！",0);
		}
		//夺宝乐翻天每期的活动时间
		$plan_list=$this->getHappyBoxPlanList($lottery_id);
		$ndate=date("Y-m-d");//当前时间
		if(!$plan_list){
			$this->ajaxReturn(0,"非法操作！",0);
		}
		if($plan_list['sdate']>$ndate){
			$this->ajaxReturn(0,"活动还没开始！",0);
		}
		if($plan_list['edate']<$ndate){
			$this->ajaxReturn(0,"活动已结束！",0);
		}
		$user_behaviour_mod=M("UserBehaviourRelation");
		$user_behaviour_info=$user_behaviour_mod->where("userid=$userid AND whoid=$lottery_id AND type='scorelist'")->find();//判断用户是否已报名
		if($user_behaviour_info){
			$this->ajaxReturn(0,"您已经报过名,请勿重复操作",0);
		}else{
			$data['userid']=$userid;
			$data['whoid']=$lottery_id;
			$data['type']="scorelist";
			$data['addtime']=time();
			$rel=$user_behaviour_mod->add($data);
			if($rel){
				$this->ajaxReturn(1,"success",1);
			}else{
				$this->ajaxReturn(0,"操作失败",0);
			}
		}
	}


	//上一期达人风云榜名单
	public function getLastUserScoreList(){
		$scoreinfo='a:50:{i:1;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"24157";s:6:"uscore";s:4:"1801";s:8:"nickname";s:10:"满满2013";}i:2;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"27689";s:6:"uscore";s:4:"1569";s:8:"nickname";s:13:"gugubutterfly";}i:3;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1227";s:6:"uscore";s:4:"1558";s:8:"nickname";s:12:"莫小七儿";}i:4;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"22660";s:6:"uscore";s:4:"1554";s:8:"nickname";s:14:"4加6天狼星";}i:5;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"2229";s:6:"uscore";s:4:"1440";s:8:"nickname";s:9:"蒙七七";}i:6;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"28718";s:6:"uscore";s:4:"1390";s:8:"nickname";s:9:"保护色";}i:7;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1356";s:6:"uscore";s:4:"1382";s:8:"nickname";s:14:"彼岸流年__";}i:8;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"2382";s:6:"uscore";s:4:"1266";s:8:"nickname";s:7:"小语P";}i:9;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"29052";s:6:"uscore";s:4:"1229";s:8:"nickname";s:5:"MiTu_";}i:10;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"26341";s:6:"uscore";s:4:"1200";s:8:"nickname";s:9:"yui大淳";}i:11;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23856";s:6:"uscore";s:4:"1115";s:8:"nickname";s:18:"王炳川王炳川";}i:12;a:4:{s:4:"sign";i:0;s:6:"userid";s:3:"943";s:6:"uscore";s:4:"1111";s:8:"nickname";s:15:"部落小牧牧";}i:13;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"27056";s:6:"uscore";s:4:"1033";s:8:"nickname";s:9:"傻丫丫";}i:14;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23773";s:6:"uscore";s:4:"1025";s:8:"nickname";s:18:"泡泡jennifer2009";}i:15;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1368";s:6:"uscore";s:3:"960";s:8:"nickname";s:10:"saylove涛";}i:16;a:4:{s:4:"sign";i:0;s:6:"userid";s:2:"50";s:6:"uscore";s:3:"930";s:8:"nickname";s:15:"回家采草药";}i:17;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"22265";s:6:"uscore";s:3:"921";s:8:"nickname";s:11:"ELAIN桃子";}i:18;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"13317";s:6:"uscore";s:3:"904";s:8:"nickname";s:6:"喵漫";}i:19;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1229";s:6:"uscore";s:3:"897";s:8:"nickname";s:6:"木木";}i:20;a:4:{s:4:"sign";i:0;s:6:"userid";s:3:"757";s:6:"uscore";s:3:"888";s:8:"nickname";s:9:"雨袭儿";}i:21;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"25206";s:6:"uscore";s:3:"879";s:8:"nickname";s:9:"好狗922";}i:22;a:4:{s:4:"sign";i:0;s:6:"userid";s:2:"37";s:6:"uscore";s:3:"859";s:8:"nickname";s:12:"叮当嘻嘻";}i:23;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"22996";s:6:"uscore";s:3:"837";s:8:"nickname";s:13:"亮晶晶1290";}i:24;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23383";s:6:"uscore";s:3:"763";s:8:"nickname";s:13:"不了不了c";}i:25;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"24253";s:6:"uscore";s:3:"761";s:8:"nickname";s:5:"Aman_";}i:26;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"25327";s:6:"uscore";s:3:"757";s:8:"nickname";s:18:"你柒柒柒大爷";}i:27;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"26799";s:6:"uscore";s:3:"726";s:8:"nickname";s:9:"妮妮安";}i:28;a:4:{s:4:"sign";i:0;s:6:"userid";s:2:"20";s:6:"uscore";s:3:"712";s:8:"nickname";s:6:"简单";}i:29;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"28333";s:6:"uscore";s:3:"706";s:8:"nickname";s:9:"多多345";}i:30;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23029";s:6:"uscore";s:3:"695";s:8:"nickname";s:15:"实习真辛苦";}i:31;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"25779";s:6:"uscore";s:3:"684";s:8:"nickname";s:11:"sunny同学";}i:32;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"28992";s:6:"uscore";s:3:"681";s:8:"nickname";s:9:"DL欣_998";}i:33;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"21973";s:6:"uscore";s:3:"657";s:8:"nickname";s:7:"lalaw00";}i:34;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23054";s:6:"uscore";s:3:"645";s:8:"nickname";s:14:"1宝宝贝贝1";}i:35;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"28314";s:6:"uscore";s:3:"627";s:8:"nickname";s:9:"熊宝宝";}i:36;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"32991";s:6:"uscore";s:3:"617";s:8:"nickname";s:9:"瑞轩轩";}i:37;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"26575";s:6:"uscore";s:3:"613";s:8:"nickname";s:7:"xiaozhu";}i:38;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1738";s:6:"uscore";s:3:"600";s:8:"nickname";s:12:"花花悟空";}i:39;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"24062";s:6:"uscore";s:3:"535";s:8:"nickname";s:8:"lvdandan";}i:40;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"28428";s:6:"uscore";s:3:"498";s:8:"nickname";s:15:"若从未遇见";}i:41;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3761";s:6:"uscore";s:3:"461";s:8:"nickname";s:6:"冰月";}i:42;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3125";s:6:"uscore";s:3:"429";s:8:"nickname";s:9:"haiyu1231";}i:43;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1810";s:6:"uscore";s:3:"401";s:8:"nickname";s:7:"kikiyip";}i:44;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1901";s:6:"uscore";s:3:"395";s:8:"nickname";s:4:"tian";}i:45;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"25615";s:6:"uscore";s:3:"383";s:8:"nickname";s:9:"欧罗仔";}i:46;a:4:{s:4:"sign";i:0;s:6:"userid";s:3:"649";s:6:"uscore";s:3:"354";s:8:"nickname";s:12:"我爱夏天";}i:47;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23344";s:6:"uscore";s:3:"345";s:8:"nickname";s:6:"欧罗";}i:48;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"29240";s:6:"uscore";s:3:"324";s:8:"nickname";s:18:"华山派小尼姑";}i:49;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"29121";s:6:"uscore";s:3:"212";s:8:"nickname";s:15:"小明的夏天";}i:50;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"27480";s:6:"uscore";s:3:"203";s:8:"nickname";s:12:"mmjj20060303";}}';
		$userscore_list=unserialize($scoreinfo);
		return $userscore_list;
	}

	//夺宝乐翻天，活动结束够领取盒子
	public function get_happy_box(){
		$lottery_id=$_POST['lottery_id'];
		$userid=$this->getUserid();

		$sdate="2013-06-27 00:00:00";
		$edate="2013-06-30 23:59:59";
		$cdate=date("Y-m-d H:i:s");
		if(empty($userid) || !$this->isAjax() || empty($lottery_id)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		if($sdate>$cdate){
			$this->ajaxReturn(0,"活动还没开始",0);
		}
		if($edate<$cdate){
			$this->ajaxReturn(0,"活动已经结束",0);
		}
		//判断用户是否已经领取过盒子，如果领取过，则不能再领取
		$behaviour_mod=M("UserBehaviourRelation");
		$data['userid']=$userid;
		$data['whoid']=$lottery_id;
		$data['type']="apply_box";
		$if_getbox=$behaviour_mod->where($data)->find();
		if($if_getbox){
			$this->ajaxReturn(0,"已经兑换过盒子",0);
		}
		//积分榜中的名单
		$list=$this->get_happybox_score_list();
		$user_list=$list["userlist"];
		$user_score_list=$list["scorelist"];
		//判断用户是否在前50名内
		if(!in_array($userid, $user_list)){
			$this->ajaxReturn(0,"您不具备兑奖资格",0);
		}
		//判断用户在活动期间赚的的积分是否超过500，不足，不满足兑换盒子的条件
		if((int)$user_score_list[$userid]<500){
			$this->ajaxReturn(0,"您不满足兑奖条件",0);
		}
		//查看用户当前的积分是否够500，因为领取盒子时会扣去用户500积分，不够，则不能领取盒子
		$userinfo=D("Users")->getUserInfo($userid);
		if((int)$userinfo['score']<500){
			$this->ajaxReturn(0,"您的积分不足，无法兑换",0);
		}
		//如果以上条件都满足，则用户符合领取盒子的条件
		$data['status']=1;
		$data['addtime']=time();
		$ret=$behaviour_mod->add($data);
		if($ret){
			//领取成功，扣去用户的积分
			D( "UserCreditStat" )->addUserCreditStat ($userid,"兑换萝莉盒（第".$lottery_id."期达人风云榜）",-500);
			$this->ajaxReturn(1,"success",1);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	}

	/**
	 * 用户分享信息到微博
	 */
	public function sinaweibo(){
		if(!$this->check_login()) {
			$this->error("您没有登录 ，无法分享！",U("user/reglogin"));exit;
		}
		$uid=$this->getUserid();
		$backurl=U("activity/happybox");
		$content_url="http://www.lolitabox.com/activity/happybox.html";
		$content="#LOLITABOX达人积分风云榜#我在LOLITABOX参加达人积分风云榜活动，进入风云榜前50名就能免费获得精致萝莉盒哦，你也快来参加吧~";
		$postweiboid=$this->postSinaWeibo($uid,$content,"",$content_url);//同步信息到微博
		if($postweiboid!==false){//检测到会话状态，可以发表微博
			if($postweiboid==null){//由于很短时间内发表的内容相同，所以发表失败
				$this->error("操作太频繁，请您稍后再试！",$backurl);
			}
			$credit_score=D ( "UserCreditStat" )->optCreditSet ($uid, 'sina_weibo_share' );
			//$credit_score=1时说明当前用户当天第一次发表，增加积分，为0时说明已经发表过分享，可以分享，但无积分增加
			if($credit_score['score']==0){
				$this->success("已分享过，不能重复获得积分",$backurl);
			}else{
				$this->success("恭喜您获得了1个积分",$backurl);
			}
		}else{
			$this->error("分享失败！",$backurl);
		}
	}

	/**
	 * 贝玲妃寻氧活动----活动页面start、search、info、end
	 */
	public function benefit_xunyang(){
		$type=$_REQUEST['type'];
		if(empty($type)){
			$type="start";
		}
		$active_mod=M("ActivityBenefit");
		$userid=$this->getUserid();
		if(empty($userid)){
			$ifcheck=0;
			$user_active_num="";
			$user_question_list="";
		}else{
			$ifhave_active_message=$active_mod->where("userid=$userid")->find();//判断当前用户是否有 有关此活动的数据
			$user_active_num=$active_mod->where("userid=$userid AND bottletype='碎片' AND status=1")->count();
			$ifhave_checkbox=$active_mod->where("userid=$userid AND TYPE='box_gift'  AND status=1")->find();
			if(!$ifhave_checkbox){
				$ifhave_checkbox="";
			}else{
				$ifhave_checkbox=$ifhave_checkbox[id];
			}

			$ifhavefirst_sp=$active_mod->where("userid=$userid AND type='everyone' AND bottletype='碎片' AND status=1")->find();//判断用户是否领取见者有份的碎片
			if($ifhavefirst_sp){
				$ifcheck=1;
			}else{
				$ifcheck=0;
			}
			if(!$user_active_num){
				$user_active_num="";
			}
		}
		$pageinfo['title']="Benefit 阿贝贝寻氧记-萝莉盒";
		$this->assign("type",$type);
		$this->assign("user_active_num",$user_active_num);
		$this->assign("ifcheck",$ifcheck);
		$this->assign("userid",$userid);
		$this->assign("pageinfo",$pageinfo);
		$this->assign("ifhave_checkbox",$ifhave_checkbox);
		$this->display("start");
	}

	/**
	 * 38女人节活动
	 */
	public function womanholiday(){
		//实时获奖信息
		$activity_mod=M("UserActivity");
		$activitytype="38缤纷女人节";
		$gift_list=$activity_mod->field("gifttype,giftinfo,remark,userid")->where("activitytype='$activitytype' AND gifttype!='nothing'")->order("addtime DESC")->limit(50)->select();
		$gift_count=count($gift_list);
		for($i=0;$i<$gift_count;$i++){
			$u_info=D("Users")->getUserInfo($gift_list[$i]["userid"],"nickname");
			$gift_list[$i]['nickname']=$u_info['nickname'];
			$gift_list[$i]['userid']=$u_info['userid'];
		}
		$product_list=array(
		0=>149,
		1=>62933,
		2=>62953,
		3=>115,
		4=>62893,
		5=>62954,
		6=>152,
		7=>43,
		);
		for($i=0;$i<count($product_list);$i++){
			$pro_mod=M("products");
			$pro_info=$pro_mod->field("pimg,pname,pid")->where("pid=".$product_list[$i])->find();
			$pro_list[$i]['pimg']=$pro_info['pimg'];
			$pro_list[$i]['pname']=$pro_info['pname'];
			$pro_list[$i]['pid']=$pro_info['pid'];
		}

		$userid=$this->getUserid();
		$userinfo=D("Users")->getUserInfo($userid);
		$pageinfo['title']="三月缤纷女人节活动";
		$pageinfo['keywords']="三月 女人节";
		$this->assign("pageinfo",$pageinfo);
		$this->assign("userinfo",$userinfo);
		$this->assign("gift_list",$gift_list);
		$this->assign("pro_list",$pro_list);
		$this->assign("gift_count",$gift_count);
		$this->display();
	}

	//免费得萝莉盒第一期
	public function freebox(){
		//需要评测的单品列表
		$products_arr=array(
		'0'=>'15509',
		'1'=>'37327',
		'2'=>'9447',
		'3'=>'62920',
		'4'=>'62919',
		'5'=>'62922',
		'6'=>'62923',
		'7'=>'62900',
		'8'=>'62901',
		'9'=>'62902',
		'10'=>'62903',
		'11'=>'62895',
		);
		$pageinfo['title']="夺宝乐翻天 30个萝莉盒免费送-萝莉盒";
		$this->getFreePublicBoxInfo($products_arr,$pageinfo);//夺宝乐翻天公共部分
		$scoreinfo='a:30:{i:1;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"22806";s:6:"uscore";s:4:"1401";s:8:"nickname";s:12:"红袖添香";}i:2;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1560";s:6:"uscore";s:4:"1220";s:8:"nickname";s:21:"爱睡懒觉的小虎";}i:3;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"2022";s:6:"uscore";s:4:"1188";s:8:"nickname";s:4:"mkii";}i:4;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1368";s:6:"uscore";s:4:"1122";s:8:"nickname";s:10:"saylove涛";}i:5;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1802";s:6:"uscore";s:4:"1119";s:8:"nickname";s:11:"terry789555";}i:6;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23205";s:6:"uscore";s:4:"1104";s:8:"nickname";s:10:"fxxkingirl";}i:7;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3294";s:6:"uscore";s:4:"1092";s:8:"nickname";s:12:"妮妮888888";}i:8;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"4587";s:6:"uscore";s:4:"1083";s:8:"nickname";s:9:"Lemonle12";}i:9;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"5188";s:6:"uscore";s:4:"1077";s:8:"nickname";s:6:"九九";}i:10;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"12154";s:6:"uscore";s:4:"1076";s:8:"nickname";s:9:"潘朵拉";}i:11;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"21359";s:6:"uscore";s:4:"1068";s:8:"nickname";s:15:"西瓜的珂珂";}i:12;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"24062";s:6:"uscore";s:4:"1063";s:8:"nickname";s:8:"lvdandan";}i:13;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3434";s:6:"uscore";s:4:"1009";s:8:"nickname";s:8:"lissisyl";}i:14;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3125";s:6:"uscore";s:4:"1001";s:8:"nickname";s:9:"haiyu1231";}i:15;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1738";s:6:"uscore";s:3:"998";s:8:"nickname";s:12:"花花悟空";}i:16;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3035";s:6:"uscore";s:3:"998";s:8:"nickname";s:4:"Nabo";}i:17;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1564";s:6:"uscore";s:3:"997";s:8:"nickname";s:5:"qinmu";}i:18;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3316";s:6:"uscore";s:3:"996";s:8:"nickname";s:5:"lissi";}i:19;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"2382";s:6:"uscore";s:3:"988";s:8:"nickname";s:7:"小语P";}i:20;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"21973";s:6:"uscore";s:3:"977";s:8:"nickname";s:7:"lalaw00";}i:21;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"1810";s:6:"uscore";s:3:"970";s:8:"nickname";s:7:"kikiyip";}i:22;a:4:{s:4:"sign";i:0;s:6:"userid";s:3:"933";s:6:"uscore";s:3:"930";s:8:"nickname";s:9:"李小笔";}i:23;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"4965";s:6:"uscore";s:3:"916";s:8:"nickname";s:9:"doubleyan";}i:24;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23213";s:6:"uscore";s:3:"916";s:8:"nickname";s:9:"sakuraitt";}i:25;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"22035";s:6:"uscore";s:3:"908";s:8:"nickname";s:9:"ivysunbin";}i:26;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"24496";s:6:"uscore";s:3:"899";s:8:"nickname";s:9:"醉八仙";}i:27;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"23431";s:6:"uscore";s:3:"885";s:8:"nickname";s:6:"cicely";}i:28;a:4:{s:4:"sign";i:0;s:6:"userid";s:5:"21982";s:6:"uscore";s:3:"817";s:8:"nickname";s:7:"s_apple";}i:29;a:4:{s:4:"sign";i:0;s:6:"userid";s:3:"997";s:6:"uscore";s:3:"816";s:8:"nickname";s:15:"草莓小柚柚";}i:30;a:4:{s:4:"sign";i:0;s:6:"userid";s:4:"3008";s:6:"uscore";s:3:"804";s:8:"nickname";s:15:"邻家颖小白";}}';
		$userscore_list=unserialize($scoreinfo);
		$this->assign("userscore_list",$userscore_list);
		$this->display();
	}

	/**
	 * 周年庆活动
	 */
	public function oneyear(){
		$userid=$this->getUserid();
		if($userid){
			$userinfo=D("Users")->getUserInfo($userid);
		}else{
			$userinfo="";
		}
		$u_credit_mod=M("UserCreditStat");
		$ntime=date("Y-m-d");
		if($userid){
			$rel=$u_credit_mod->where("action_id='user_sign' AND userid=$userid AND add_datetime like '".$ntime."%'")->find();
			if($rel){
				$if_sign=1;
			}else{
				$if_sign=0;
			}
		}else{
			$if_sign=0;
		}
		//周年庆活动获奖信息
		$activity_mod=M("UserActivity");
		$zadan_type="周年庆-砸金蛋";
		$zhuanpan_type="周年庆-转盘";
		$list=$activity_mod->where("gifttype!='nothing' AND (activitytype='".$zadan_type."' OR activitytype='".$zhuanpan_type."')")->order("addtime DESC")->group("userid,giftinfo")->limit(50)->select();
		$count=count($list);
		if($list){
			for($i=0;$i<$count;$i++){
				$u_info=D("Users")->getUserInfo($list[$i]['userid'],"nickname");
				$gift_list[$i]['userid']=$list[$i]['userid'];
				$gift_list[$i]['nickname']=$u_info['nickname'];
				$gift_list[$i]['giftinfo']=$list[$i]['remark'];
				$gift_list[$i]['url']="";
			}
			$not_true_list=array(
			0=>array("userid"=>"2656","nickname"=>"狐狸小姐","giftinfo"=>"Benefit（贝玲妃）花漾胭脂水","url"=>"43"),
			2=>array("userid"=>"565","nickname"=>"找乐天使","giftinfo"=>"欧珀莱臻白抗斑赋弹系列净透矿物泥洁面膏","url"=>"60"),
			3=>array("userid"=>"1221","nickname"=>"夏末之恋","giftinfo"=>"肌醇悦妍营养精华液","url"=>"62901"),
			4=>array("userid"=>"1479","nickname"=>"绵羊骑士","giftinfo"=>"CUB茉莉清新身体牛油","url"=>"62891"),
			);
			$gift_list=array_merge($gift_list,$not_true_list);
			shuffle($gift_list);
		}else{
			$gift_list="";
		}

		if($userid){
			//我是最粉丝 1.完善基本信息 2.激活邮箱 3.绑定新浪微博
			//活动有效期为4月15日00:00:00-4月21日23:59:59
			$start_date="2013-04-15 00:00:00";
			$end_date="2013-04-21 23:59:59";
			if($userinfo['addtime']<$start_date || $userinfo['addtime']>$end_date){
				$active_arr="";
				$if_new=0;
			}else{
				$lottery_id=4;
				$lottery_mod=M("UserLottery");
				$lottery_info=$lottery_mod->where("lottery_id=$lottery_id AND userid=$userid")->find();
				//当用户已抽取过时，显示已领取
				if($lottery_info){
					$if_new=2;
				}else{
					//用户没有领取过
					$active_arr=$this->getNewUserInfo($userid);
					//1.还没有完成任务
					if($active_arr[1]==0 || $active_arr[2]==0 || $active_arr[3]==0){
						$if_new=0;
					}else{
						//2.完成任务还没领取
						$if_new=1;
					}
				}
			}
		}else{
			$if_new=0;
		}
		$pageinfo["title"]="LOLITABOX一周年庆 万千好礼大放送-萝莉盒";
		$pageinfo["description"]="LOLITABOX缤纷周年庆，全民狂欢嘉年华开始啦，三大活动精彩纷呈，4月1日-4月21日，超多惊喜不容错过，心动的就赶快行动吧！";
		$pageinfo["keywords"]="LOLITABOX周年庆 萝莉盒";
		$this->assign("pageinfo",$pageinfo);
		$this->assign("userinfo",$userinfo);
		$this->assign("if_sign",$if_sign);
		$this->assign("gift_list",$gift_list);
		$this->assign("active_arr",$active_arr);
		$this->assign("if_new",$if_new);
		$this->assign("lottery_id",$lottery_id);
		$this->assign("count",$count);
		$this->assign("userid",$userid);
		$this->display();
	}


	//免费抽取萝莉盒第二期
	public function lottery2(){
		$lottery_id=2;
		$this->getLotteryPublic($lottery_id);
		$return['title']="LOLITABOX超级萝莉盒第二期-萝莉盒";
		$this->assign("return",$return);
		$this->assign("lottery_id",$lottery_id);
		$this->display();
	}

	public function lottery3(){
		$lottery_id=3;
		$this->getLotteryPublic($lottery_id);
		$return['title']="LOLITABOX超级萝莉盒第三期-萝莉盒";
		$this->assign("return",$return);
		$this->assign("lottery_id",$lottery_id);
		$this->display();
	}

	//免费抽萝莉盒公共部分
	private function getLotteryPublic($lottery_id){
		$userinfo=D("Users")->getUserInfo($this->getUserId());
		$lottery_mod=M("UserLottery");
		$lottery_count=$lottery_mod->where("lottery_id=$lottery_id")->count();
		$this->assign("userinfo",$userinfo);
		$this->assign("lottery_count",$lottery_count);
	}
	
	/**
	 * 获取美丽任务完成情况列表
	 * @return array $task_list [1-头像上传,2-基本信息完善,3-美丽档案]
	 */
	public function getFinishTaskStatus() {
		if($this->userid){
			$array_mytask = array ();
			// 1、头像是否已经填充过
			$array_mytask [1] ["uploadface"] = "1";
			if (empty ( $this->userinfo ["userface"] )) {
				$array_mytask [1] = 0;
			} else {
				$array_mytask [1] = 1;
			}
			// 2、判断当前个人基本信息
			$array_mytask [2] = 1;
			$basefield = array (
					"province",
					"city",
					"description",
					"edu",
					"years",
					"months",
					"days",
					"skin_property",
					"hair_property",
					"profession"
			);
			reset ( $basefield );
			while ( list ( $key, $val ) = each ( $basefield ) ) {
				if (empty ( $this->userinfo [$val] ) || ! $this->userinfo [$val]) {
					$array_mytask [2] = 0;
					break;
				}
			}
			if ($array_mytask [2]) {
				// 当前用户已经完善了个人信息
				$user_credit_stat_model = D ( "UserCreditStat" );
				$user_credit_stat_model->optCreditSet ( $this->userid, 'user_profile_complete' );
			}
			// 3、判断美丽档案完成比例
			if ($this->userinfo ["scaleBeautyRecord"] < 100) {
				$array_mytask [3] = 0;
			} else {
				$array_mytask [3] = 1;
			}
		}else{
			$array_mytask="";
		}
		return $array_mytask;
	}
	
	/**
	 * 判断用户user_address中的地址
	 *@author penglele
	 */
	public function ishaveuseraddress() {
		$userid = $this->getUserid ();
		$useraddress_mod = M ( "userAddress" );
		$address_list = $useraddress_mod->where ( "userid=$userid AND if_del=0" )->order ( "addtime DESC" )->select ();
		return $address_list;
	}
	
	/**
	 * 专题--9月活动
	 * @author penglele
	 */
	public function lolihighv5(){
		$userid=$this->userid;
		$userinfo=$this->userinfo;
		$return=$this->check_ifbind($userid);
		$return['userinfo']=$userinfo;
		$type=$_GET['type'];
		$type=!$type ? 1 : $type;
		$type=$type>3 ? 1 : $type;
		$user_mod=D("Users");
		if($type==1){
			$url=U("activity/lolihighv5");
			$return['url']=urlencode($url);
			if($userid){
				$code =R("task/inviteidauth_encode",array($userid));
				$return['inviteurl'] = "http://" . $_SERVER ["SERVER_NAME"] . U ( "user/reglogin", array (
						"u" => $code
				) );
			}
			$return['if_sign']=$this->check_ifsign($userid);
			$return['newlist']=$user_mod->getNewUserList();
			$othercondition["addtime"]=array("egt","2013-09-09 10:00:00");
			$return['invitelist']=$user_mod->getInviteUserList($userid,3,$othercondition);
			$return['num']=$user_mod->getInviteUserCount($userid,$othercondition);			
			$display="lolihighv5";
		}else if($type==2){
			$boxid=$this->returnDiscountBoxid();
			$sname=getBoxSessName($boxid, $userid);
			$timelist=$this->getLolihighv5TimeList();
			$return=array_merge($return,$timelist);
			if($return['if_act']==0 && isset($_SESSION[$sname])){
				unset($_SESSION[$sname]);
			}
			if($return['if_act']==1){
				$prolist=$this->score_discount($userid);
				$return=array_merge($return,$prolist);
			}
			$return['select']=$this->getUserCostOfDiscount($userid);
			$display="loliv5-2";
		}elseif($type==3){
			if($userid){
				$user_act_mod=D("UserActivity");
				$name="萝莉盒新颜大奖";
				//用户的邀请总数
				$return['totalnum']=$user_mod->getUserInviteNumByLoliv5($userid);
				//用户已经兑换的总数
				$return['usenum']=$user_act_mod->getGiftNum($userid,$name);
				$return['leftnum']=(int)$return['totalnum']-(int)$return['usenum'];
			}
			$stime=strtotime("2013-09-27 10:00:00",time());
			$etime=strtotime("2013-09-27 17:00:00",time());
			$ntime=time();
			$return['if_start']=1;
			if($ntime<$stime){
				$return['if_start']=0;
			}else if($ntime>$etime){
				$return['if_start']=2;
			}
			if($userid && $return['if_start']<2){
				$return['if_show']=1;
			}
			$display="loliv5-3";
		}
		$return['type']=$type;
		$return['title']="连HIGH三周 萝莉盒换新颜送豪礼-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display($display);
	}
	
	/**
	 * 判断用户是否绑定过手机、新浪微博、腾讯微博
	 * @param unknown_type $userid
	 */
	public function check_ifbind($userid){
		$mod=D("UserCreditStat");
		$return['bind_tel']=$mod->getUserTaskStat($userid,"mobile");
		$return['bind_sina']=$mod->getUserTaskStat($userid,"sina");
		$return['bind_qq']=$mod->getUserTaskStat($userid,"qq");
		return $return;
	}
	
	public function check_ifsign($userid){
		$state=0;
		if(!$userid){
			return $state;
		}
		$stime=date("Y-m-d")." 00:00:00";
		$etime=date("Y-m-d")." 23:59:59";
		$where['action_id']="user_sign";
		$where['add_datetime']=array(array('elt',$etime),array('egt',$stime),'AND'); 
		$where['userid']=$userid;
		$info=M("UserCreditStat")->where($where)->find();
		if($info){
			$state=1;
		}
		return $state;
	}
	
	/**
	 * 活动--签到后转发到第三方
	 * @author penglele
	 */
	public function sign_shareto(){
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"还没登录",0);
		}
		$qq=$_POST['qq_type'];
		$sina=$_POST['sina_type'];
		if($qq!=1 && $sina!=1){
			$this->ajaxReturn(0,"还没有选择任何转发方式",0);
		}
		if(!$this->isAjax()){
			$this->ajaxReturn(0,"非法操作",0);
		}
		//签到才能转发
		$if_sign=$this->check_ifsign($userid);
		if($if_sign==0){
			$this->ajaxReturn(0,"您需要先签到才能进行转发哦",0);
		}
		$pic="./public/images/lolihighv5.jpg";
		$content="我刚刚在#连HIGH三周 萝莉盒换新颜送豪礼# 活动完成签到任务并获得了10积分奖励！在萝莉盒，积分可以用来兑换自己喜欢的美妆品哦！萝莉盒，就是化妆品试用，这里有更多惊喜等着你！不想OUT，就快到盒子里来吧~";
		$url="http://www.lolitabox.com/activity/lolihighv5.html";
		//腾讯微博转发
		$m=0;
		$score=0;
		$activity_mod=D("Activity");
		if($qq==1){
			$qq_share=$this->postQQWeibo($content,$pic,$url);
			if($qq_share['ret']==0){
				$add_qq=$activity_mod->addV5SpreadInfo($userid,"qq");
				if($add_qq==true){
					$score=$score+10;
				}
				$m++;
			}
		}
		//新浪微博转发
		if($sina==1){
			$sina_share=$this->postSinaWeibo($userid,$content,$pic,$url);
			if($sina_share!=0){
				$add_sina=$activity_mod->addV5SpreadInfo($userid,"sina");
				if($add_sina==true){
					$score=$score+10;
				}
				$m++;
			}
		}
		if($m>0){
			$this->ajaxReturn($score,"success",1);
		}else{
			$this->ajaxReturn(0,"转发失败，您可以稍后重试",0);
		}
	}
	
	
	/**
	 * 活动限时抢
	 */
	public function score_discount($userid){
		//显示抢活动的boxid
		$boxid=$this->returnDiscountBoxid();
		$sname=getBoxSessName($boxid,$userid);
		$return=D("Box")->getZixuanDetails($boxid,$userid,1,$sname,1);
		return $return;
	}
	
	public function returnDiscountBoxid(){
		return D("Box")->getDiscountBoxid();
	}
	
	/**
	 * 活动lolihighv5 检测选择的产品是否符合规则
	 * @author penglele
	 */
	public function ajaxv5_select_product(){
		//判断当前是否在活动期间
		$act_time=$this->checkTimeOfLolihighv5();
		if($act_time==0){
			$this->ajaxReturn(0,"活动还没开始！",0);
		}else if($act_time==2){
			$this->ajaxReturn(0,"活动已经结束！",0);
		}
		$userid=$this->userid;
		$pid=$_POST["pid"];//pid为inventory_item下的id
		$type=$_POST['type'];//当前单品所属分类
		$boxid=$this->returnDiscountBoxid();
		if(!$userid || !$this->isAjax() || !$pid){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$userid=$this->getUserid();
		if(!isset($userid) || empty($userid)){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		
		$sname=getBoxSessName($boxid,$userid);
		$box_pro_mod=M("BoxProducts");
		$box_pro_info=$box_pro_mod->where("pid=$pid AND boxid=$boxid AND ishidden=0")->find();
		if(!isset($_SESSION[$sname])){
			$this->ajaxReturn(0,"产品已售罄",0);
		}else{//如果session已存在
			if(in_array($pid,$_SESSION[$sname][$type])){
				$this->ajaxReturn(0,"您已经选择过该产品",0);
			}
			//判断产品库存是否>0
			$inventory_realnum=D("InventoryItem")->getProductInventory($pid,$boxid);
			if(!$box_pro_info || $inventory_realnum<0){
				$this->ajaxReturn(0,"您选择的产品已售完",0);
			}
			if(!empty($_SESSION[$sname][$type])){
				$select_num=0;
				for($i=1;$i<=$type;$i++){
					if($_SESSION[$sname][$type][$i]!=""){
						$select_num++;
					}
				}
				if($select_num>=$type){
					$this->ajaxReturn(0,"该类产品可选数量已达上限",0);
				}
			}
		}
		for($i=1;$i<=$type;$i++){
			if($_SESSION[$sname][$type][$i]==""){
				$_SESSION[$sname][$type][$i]=$pid;
				break;
			}
		}
		$product_info=D("BoxProducts")->getProductsInfo($pid);
		$product_info['discount_score']=(int)(round($product_info['credit']*$box_pro_info['discount']))*$box_pro_info['pquantity'];
		$this->ajaxReturn(1,$product_info,1);
	}
	
	/**
	 * 判断lolihighv5活动的限时抢的活动开始结束时间
	 * @return int [0:还没开始 1：正在进行中 2：已结束]
	 * @author penglele
	 */
	public function checkTimeOfLolihighv5(){
		$day=(int)date("d");
		$mon=date("m");
		$year=date("Y");
		if($day<16){
			return 0;
		}
		if($day>27 || (int)$year!=2013 || (int)$mon!=9){
			return 2;
		}
		//中秋节不在活动范围内
		$not_day=$this->getLolihighv5NotDay();
		if(in_array($day,$not_day)){
			return 0;
		}
		$nowtime=time();
		$stime=strtotime(date("Y-m-d")." 13:00:00");
		$etime=strtotime(date("Y-m-d")." 13:59:59");
		if($nowtime<$stime){
			return 0;
		}
		if($nowtime>$etime){
			return 2;
		}
		return 1;
	}
	
	/**
	 * 根据用户已选列表和用户的积分，计算用户可以支付的方式
	 * @author penglele
	 */
	public function getUserCostOfDiscount($userid){
		$boxid=$this->returnDiscountBoxid();
		$total_price=0;
		$score=0;
		$products_score=0;
		$num=0;
		$return['num']=$num;
		$return['total_score']=$score;
		$return['products_score']=$products_score;
		if(!$userid){
			return $return;
		}
		$sname=getBoxSessName($boxid,$userid);
		$sessionlist=$_SESSION[$sname];
		if(!$sessionlist){
			return $return;
		}
		$item_mod=D("InventoryItem");
		$box_pro_mod=M("BoxProducts");
		foreach($sessionlist as $key=>$val){
			foreach($val as $ikey=>$ival){
				if($ival!=""){
					$info=$item_mod->getInventoryItemInfo($ival,"price");
					$box_pro_info=$box_pro_mod->field("pquantity,discount")->where("boxid=$boxid AND pid=$ival")->find();
					$per_score=0;
					if($box_pro_info['pquantity']>0){
						$per_score=(int)(round($info['price']*$box_pro_info['discount']*10))*$box_pro_info['pquantity'];
					}
					$score=$score+$per_score;
					$num++;
				}
			}
		}
		$userinfo=$this->userinfo;
		$return['user_score']=(int)$userinfo['score'];//用户积分
		$return['postage_score']=300;//邮费的积分
		$return['products_score']=$score;//产品的积分
		$return['total_score']=$score+$return['postage_score'];//用户需要支付的全部积分
		$return['num']=$num;
		return $return;
	}
	
	/**
	 * 活动-删除已选的产品
	 * @author penglele
	 */
	public function del_actv5_product(){
		$userid=$this->userid;
		$boxid=$this->returnDiscountBoxid();
		$pid=$_POST['id'];
		$sname=getBoxSessName($boxid, $userid);
		if(!$userid || !$boxid || !$this->isAjax() || !$pid || !$_SESSION[$sname]){
			$this->ajaxReturn(0,"非法操作1",0);
		}
		$i=0;
		foreach($_SESSION[$sname] as $key_one=>$value){
			foreach($value as $key_two=>$pid_value){
				if($pid_value==$pid){
					$_SESSION[$sname][$key_one][$key_two]="";
					$i++;
					$this->ajaxReturn($key_one,"success",1);
				}
			}
		}
		if($i==0){
			$this->ajaxReturn(0,"非法操作2",0);
		}
	}
	
	/**
	 * 根据用户已选产品判断用户可以选择的支付方式ajax
	 * @author penglele
	 */
	public function get_actv5_pay_type(){
		$boxid=$this->returnDiscountBoxid();
		$userid=$this->userid;
		if(!$boxid || !$userid){
			$this->ajaxReturn(0,"fail",0);
		}
		$sname=getBoxSessName($boxid, $userid);
		$list=$this->getUserCostOfDiscount($userid);
		$this->ajaxReturn(1,$list,1);
	}
	
	
	/**
	 * lolihighv5活动-检测已选产品是否符合要求
	 */
	public function check_actv5_productslist(){
		$boxid=$this->returnDiscountBoxid();
		$userid=$this->userid;
		//判断是否在活动期间
		$act_time=$this->checkTimeOfLolihighv5();
		if($act_time==0){
			$this->ajaxReturn(0,"活动还没开始",0);
		}
		if($act_time==2){
			$this->ajaxReturn(0,"活动已结束",0);
		}
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$sname=getBoxSessName($boxid, $userid);
		if(!$boxid || !$_SESSION[$sname]){
			$this->ajaxReturn(0,"非法操作",0);
		}
// 		$userinfo=$this->userinfo;
		
		$session_list=D("Box")->getSessionProductsList($_SESSION[$sname]);
		//如果session中没有任何数据，不符合规则
		if(!isset($session_list) || count($session_list)==0){
			$this->ajaxReturn(0,"您还没有选择任何产品",0);
		}
		if(count($session_list)>10){
			$this->ajaxReturn(0,"您选择的产品不符合规则",0);
		}
		//如果已选产品中有已售完的，则不能继续提交
		$not_list=R("try/getExchangeOutProductList",array($session_list,$boxid));
		if($not_list!=""){
			$this->ajaxReturn(100,$not_list,0);
		}
		//用户所需积分等信息
		$return['scorelist']=$this->getUserCostOfDiscount($userid);
		if($return['scorelist']['user_score']<$return['scorelist']['total_score']){
			$this->ajaxReturn(200,$return['scorelist'],0);
		}
		$return['selectlist']=D("BoxProducts")->getExchangeProductList($session_list,$boxid);
		$this->ajaxReturn(100,$return,1);
	}
	
	
	/**
	 * 限时抢生成订单
	 * @author penglele
	 */
	public function actv5_confirm(){
		$addressid=$_POST["aid"];//地址
		$boxid=$this->returnDiscountBoxid();//盒子ID
		//判断用户是否有资格购买当前盒子
		$userid=$this->userid;
		$boxinfo=D("Box")->getBoxInfo($boxid);
		if(!$userid){
			$this->ajaxReturn(0,"非法操作",0);
		}
	
		$act_time=$this->checkTimeOfLolihighv5();
		if($act_time==0){
			$this->ajaxReturn(0,"活动还没开始",0);
		}
		if($act_time==2){
			$this->ajaxReturn(0,"活动已经结束",0);
		}		
		
		if($boxinfo['state']!=1){
			$this->ajaxReturn(0,"活动已结束",0);
		}
		if($boxinfo['category']!=C("BOX_TYPE_EXCHANGE_PRODUCT")){
			$this->ajaxReturn(0,"操作失败，请重新选择",0);
		}
	
		//判断用户的地址信息
		$address_info=D("UserAddress")->getUserAddressInfo($addressid);
		if($address_info==false || $address_info['if_del']==1 || $address_info['userid']!=$userid){
			$this->ajaxReturn(0,"您的地址信息有误，请确认后再兑换",0);
		}
	
		$sname=getBoxSessName($boxid, $userid);
		$session_list=D("Box")->getSessionProductsList($_SESSION[$sname]);
		$list=R("try/getExchangeOutProductList",array($session_list,$boxid));
		if($list){
			$this->ajaxReturn(100,$list,0);
		}
		$user_cost=$this->getUserCostOfDiscount($userid);
		if($user_cost['user_score']<$user_cost['total_score']){
			$this->ajaxReturn(0,"您的积分不足以支付此订单，请重新选择",0);
		}
		$score=$user_cost['total_score'];
		$price=0;
		//生成订单
		$order_mod=D("UserOrder");
		$if_order=$order_mod->addOrder($userid,$boxid,"",$addressid,"","",$sname,$score,$price);
		if($if_order==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			if(isset($_SESSION[$sname])){
				unset($_SESSION[$sname]);
			}
				
			$order_info=$order_mod->getOrderInfo($if_order);
			$array_return=R("pay/doByBuySuccess",array($order_info['ordernmb'],$order_info['addtime']));
				
			$info['orderid']=$if_order;
			$info['score']=$score;
				
			$this->ajaxReturn(1,$info,1);
	
		}
	}
	
	/**
	 * 判断活动是否正在进行
	 * @author penglele
	 */
	public function getLolihighv5TimeList(){
		$nday=date("d");
		$nyear=date("Y");
		$nmon=date("m");
		$list=array();
		$nowtime=time();
		$if_act=0;
		$if_end=0;//活动是否全部结束【1：是】
		$s_time=date("Y/m/d H:i:s");
		$not_day=$this->getLolihighv5NotDay();
		for($i=16;$i<=27;$i++){
			$info=array();
			$info['day']=$i;
			if((int)$nyear!=2013 || (int)$nmon!=9){
				//活动已结束
				$if_end=1;
				$info['state']=2;
			}else{
					if($nday<$i){
						//活动还没开始
						if($i==16){
							$ee_time="Y/m/"."16";
							$e_time=date($ee_time)." 13:00:00";
						}
						$info['state']=0;
					}else if($nday>$i){
						//活动已结束
						if($i==27){
							$if_end=1;
						}
						$info['state']=2;
					}else{
						//当在法定节假日中，活动暂停
						if(in_array($nday,$not_day)){
							$if_act=0;
							$e_time="2013/9/22 13:00:00";
						}else{
							$stime=strtotime(date("Y-m-d")." 13:00:00");
							$etime=strtotime(date("Y-m-d")." 13:59:59");
							$info['state']=1;
							if($nowtime<$stime){
								//1点之前活动还没开始
								$e_time=date("Y/m/d")." 13:00:00";
								$info['state']=0;
							}
							if($nowtime>$etime){
								//2点之后活动已经结束
								if($i==18){
									$e_time="2013/9/22 13:00:00";
								}else{
									if($i==27){
										$if_end=1;
									}
									$sstime="Y/m/".($i+1);
									$e_time=date($sstime)." 13:00:00";									
								}
								$info['state']=2;
							}
							if($info['state']==1){
								$if_act++;
								$e_time=date("Y/m/d")." 13:59:59";
							}							
						}
					}					
			}
			if(!in_array($i,$not_day)){
				$list[]=$info;
			}
		}
		$return['timelist']=$list;
		$return['if_act']=$if_act;
		$return['starttime']=$s_time;
		$return['endtime']=$e_time;
		$return['if_end']=$if_end;
		return $return;
	}
	
	/**
	 * 活动-节假日时间
	 * @author penglele
	 */
	public function getLolihighv5NotDay(){
		return array(19,20,21);
	}
	
	/**
	 * loliv5活动3规则
	 * @author penglele
	 */
	public function check_loliv5_ajax(){
		$userid=$this->userid;
		$userinfo=$this->userinfo;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		if(!$this->isAjax()){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$ntime=time();
		$stime=strtotime("2013-09-27 10:00:00",time());
		$etime=strtotime("2013-09-27 17:00:00",time());
		//判断活动的起止时间
		if($ntime<$stime){
			$this->ajaxReturn(0,"活动还没开始，还请您再等一下下哦~",0);
		}
		if($ntime>=$etime){
			$this->ajaxReturn(0,"活动已结束~",0);
		}
		
		$user_act_mod=D("UserActivity");
		$name="萝莉盒新颜大奖";
		
		//当距离上次抽奖时间少于10S时，暂停抽奖
		$act_time=$this->checkActivityTimeByLast($userid,$name);
		if($act_time==false){
			$this->ajaxReturn(100,"fail",0);
		}
		
		//用户的邀请总数
		$invite_num=D("Users")->getUserInviteNumByLoliv5($userid);
		if($invite_num<=0){
			$this->ajaxReturn(0,"<p>额~，在活动期间您还没有成功邀请好友，</p><p>所以暂时不能进行抽奖哦~</p><p><a href='/task/index/id/10.html' target='_blank' class='A_line3'>马上邀请好友注册</a></p>",0);
		}
		//用户已经兑换的总数
		$total_gift_num=$user_act_mod->getGiftNum($userid,$name);
		if($total_gift_num>=$invite_num){
			$this->ajaxReturn(0,"<p>额~，您的抽奖机会已经用完了，</p><p>所以暂时不能进行抽奖哦~</p><p><a href='/task/index/id/10.html' target='_blank' class='A_line3'>马上邀请好友注册</a></p>",0);
		}
		$key=2;
		$arr1=array("",1,"","","");
		$arr2=array(
				"1"=>array(
						'title'=>"萝莉活跃奖",
						'num'=>12,
						'per'=>1
				),
				"2"=>array(
						'title'=>"",
						'num'=>'',
						'per'=>''
				),
				"3"=>array(
						'title'=>"萝莉阳光奖",
						'num'=>100,
						'per'=>5
				),
				"4"=>array(
						'title'=>"萝莉新颜大奖",
						'num'=>0,
						'per'=>0
				),
			);
		$num1=rand(0,4);
		$per_num=0;
		if($arr1[$num1]!=""){
			$i=1;
			while($i<=100){
				$num2=rand(1,3);
				//当用户应该转到的奖品是萝莉新颜大奖或萝莉活跃奖时，
				//判断用户是否已经得到过这个奖品了，如果是，pass
				if($num2!=2){
					$per_num=$user_act_mod->getGiftNum($userid,$name,$num2);
					$per_totalnum=$user_act_mod->getGiftNum('',$name,$num2);
					//对于当前类型的奖品，如果用户个人所得的总量小于规定的总量，
					//且当前产品已发出去的总量小于当前的总量时，用户才能得到该产品
					if((int)$per_num<(int)$arr2[$num2]['per'] && $per_totalnum<(int)$arr2[$num2]['num']){
							$key=$num2;
							break;
					}
				}else{
					break;
				}		
				$i++;
			}
		}
		if($key==4){
			$key=2;
		}
		$ret=$user_act_mod->addUserActivity($userid,$name,$key);
		if($ret==false){
			$this->ajaxReturn(0,"操作失败",0);
		}else{
			//成功后给用户发私信
			if($key==1){
				$message="亲爱的".$userinfo['nickname']."，恭喜获得萝莉活跃奖！奖品为精美萝莉盒1份，将于10月送到您的手中，还请期待哦！快到个人中心完善收货信息吧~";
				D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$message);
			}else if($key==3){
				//当用户抽到优惠券时，给用户发优惠券
				$title=$name;
				D("Coupon")->addCoupon(10,$title,$userid,"","","恭喜获得10元优惠券一张，已经发放到您的账户中，");
			}
			
// 			else if($key==4){
// 				$message="亲爱的".$userinfo['nickname']."，幸运女神降临，恭喜获得萝莉新颜大奖！奖品为12期萝莉盒，将于10月开始分为12期陆续送到您的手中，还请期待哦！快到个人中心完善收货信息吧~";
// 				D("Msg")->addMsg(C("LOLITABOX_ID"),$userid,$message);
// 			}
			
			
			$this->ajaxReturn($key,"success",1);
		}
	}
	
	/**
	 * loliv5活动-转盘
	 * @author penglele
	 */
	public function zhuanpan_loliv5_ajax(){
		$userid=$this->getUserid();
		$key=$_POST['key'];
		$key = $key >3 ? 2 : $key;
		$jiangpin_list = array(
				1 => array('hudu'=>45,'name'=>'萝莉活跃奖'),
				2 => array('hudu'=>135,'name'=>''),
				3 => array('hudu'=>225,'name'=>'萝莉阳光奖'),
				4 => array('hudu'=>315,'name'=>'萝莉新颜大奖'),
		);
		
		if ( $_POST['act'] == 'turnPlate')
		{
			if(!$this->isAjax() || empty($userid)){
				echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="460" height="460" id="turnplate">
			<param name="allowScriptAccess" value="always" />
			<param name="FlashVars" id="FlashVars" value="fvar=0&tips=0">
			<param name="movie" value="public/special/20130906/zp.swf">
			<param name="menu" value="false">
			<param name="quality" value="high">
			<param name="wmode" value="transparent">
			<embed src="public/special/20130906/zp.swf" FlashVars="fvar=0&tips=" id="FlashVars"  width="460" height="460"  quality="high" id="turnplate" name="turnplate" wmode="transparent" allowScriptAccess="always"  pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash">
			</embed>
			</object> ';
				exit();
			}
			if($key==2){
				$name="呃，手气不咋滴~~您这次啥也米获得";
			}else{
				$name='恭喜您获得: '.$jiangpin_list[$key]['name'];
			}
			$hudu	= 1440 + $jiangpin_list[$key]['hudu'];   //随机选一种弧度，弧度你可以自己控制，前面4表是在原来基础上多加两圈
			$tips	= $name;
				echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="460" height="460" id="turnplate">
			<param name="allowScriptAccess" value="always" />
			<param name="FlashVars" id="FlashVars" value="fvar='.$hudu.'&tips='.$tips.'">
			<param name="movie" value="public/special/20130906/zp.swf">
			<param name="menu" value="false">
			<param name="quality" value="high">
			<param name="wmode" value="transparent">
			<embed src="public/special/20130906/zp.swf" FlashVars="fvar='.$hudu.'&tips='.$tips.'" id="FlashVars"  width="460" height="460"  quality="high" id="turnplate" name="turnplate" wmode="transparent" allowScriptAccess="always"  pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash">
			</embed>
			</object> ';
			exit();
		}
		else if( $_POST['act'] == 'load' )
		{
				echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="460" height="460" id="turnplate">
			<param name="allowScriptAccess" value="always" />
			<param name="FlashVars" id="FlashVars" value="fvar=0&tips=0">
			<param name="movie" value="public/special/20130906/zp.swf">
			<param name="menu" value="false">
			<param name="quality" value="high">
			<param name="wmode" value="transparent">
			<embed src="public/special/20130906/zp.swf" FlashVars="fvar=0&tips=" id="FlashVars"  width="460" height="460"  quality="high" id="turnplate" name="turnplate" wmode="transparent" allowScriptAccess="always"  pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash">
			</embed>
			</object> ';
			exit();
		}		
	}
	
	/**
	 * 萝莉high5活动-判断距上一次的时间
	 * @author penglele
	 */
	public function checkActivityTimeByLast($userid,$name){
		if(!$userid || !$name){
			return false;
		}
		$info=M("UserActivity")->where("userid=$userid AND activitytype='".$name."'")->find();
		if(!$info){
			return true;
		}
		$ltime=strtotime($info['addtime'],time());
		$ntime=time();
		if($ntime-$ltime<=10){
			return false;
		}
		return true;
	}
	
	/**
	 * ++++++++++++++++++与OK杂志的合作start++++++++++++++++++++++
	 */
	
	/**
	 * okgirl 主页
	 * @author penglele
	 */
	public function okgirl(){
		$article_mod=D("ArticleOkgirl");
		$return['list']=$article_mod->getOKgirlList();
		$schoollist=$article_mod->getOkgirlToSchoolList();
		$return['schoollist']=$schoollist['list'];
		$return['type']=$schoollist['type'];
		$return['title']="OK! Girl 遇见未知的自己2013全国挑战赛 - 萝莉盒";
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * okgirl 投票-ajax
	 * @author penglele
	 */
	public function okgirl_vote(){
		$id=$_POST['id'];
		$cate_id=$_POST['cid'];
		$cate_arr=array(
				757=>"北京",
				759=>"武汉",
				758=>"上海",
				760=>"成都"
				);
		$article_mod=M("Article");
		//判断被选用户是否存在
		$article_info=$article_mod->where("id=$id AND cate_id=".$cate_id)->find();
		if(!$article_info || $article_info['status']==0 || !array_key_exists($cate_id,$cate_arr)){
			$this->ajaxReturn(0,"投票失败，请稍后重试",0);
		}
		$ip=get_client_ip();
		$vote_mod=M("ActivityOkgirlVotestat");
		$where['city']=$cate_arr[$cate_id];
		$where['girlid']=$id;
		$where['vote_ip']=$ip;
		//判断用户是否对当前用户投过票
		$if_vote=$vote_mod->where($where)->find();
		if($if_vote){
			$this->ajaxReturn(0,"同一个IP地址对同一用户只能投一次票",0);
		}
		$data=array();
		$data=$where;
		$userid=$this->userid;
		$data['userid']=$userid;
		$data['vote_datetime']=date("Y-m-d H:i:s");
		//生成投票数据
		$rel=$vote_mod->add($data);
		if($rel){
			$article_mod->where("id=".$id)->setInc("info",1);
			$this->ajaxReturn(1,"投票成功",1);
		}else{
			$this->ajaxReturn(0,"投票失败，请稍后重试",0);
		}
		
	}
	
	/**
	 *  ok_girl报名页
	 *  @author penglele
	 */
	public function okgirl_join(){
		$return['title']="OK!girl 在线报名 - 萝莉盒";
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * okgirl 报名-ajax
	 * @author penglele
	 */
	public function get_okgirl_in(){
		$data=$_POST;
		//判断几个不为空的选项是否为空
		if($data['name']==""){
			$this->ajaxReturn(0,"姓名不能为空",0);
		}
		if($data['target_city']==""){
			$this->ajaxReturn(0,"报名城市不能为空",0);
		}
		if($data['height']==""){
			$this->ajaxReturn(0,"身高不能为空",0);
		}
		if($data['weight']==""){
			$this->ajaxReturn(0,"体重不能为空",0);
		}
		if($data['telphone']==""){
			$this->ajaxReturn(0,"联系电话不能为空",0);
		}		
		if($data['email']==""){
			$this->ajaxReturn(0,"邮箱不能为空",0);
		}		
		if($data['img1']=="" && $data['img2']==""){
			$this->ajaxReturn(0,"请上传1张-2张清晰正面全身照",0);
		}		
		$userid=$this->userid;
		//判断用户是否存在
		if(!$userid){
			$userinfo=M("Users")->where("usermail='".$data['email']."'")->find();
			if($userinfo){
				$this->ajaxReturn(0,"非常抱歉，您的邮箱地址无法使用，请更改后再提交报名信息！",0);
			}
			$data['is_loliuser']=0;
		}else{
			$info=M("ActivityOkgirlProfile")->where("userid=$userid")->find();
			if($info){
				$this->ajaxReturn(0,"非常抱歉，您已经报过名了！",0);
			}
			$data['is_loliuser']=1;
			$data['userid']=$userid;
		}
		$data['apply_datetime']=date("Y-m-d H-i-s");
		//生成报名信息
		$rel=$this->addProfile($data,$userid);
		if($rel){
			if(!$userid){
				//设置会话状态
				$user_session=array(
						"username"=>$rel["usermail"],
						"nickname"=>$rel["nickname"],
						"userid"=>$rel['userid']
				);
				$user_session['usermail'] = $rel['usermail'];
				R("user/set_user_session",array($user_session));
				//新用户生成user_profile数据
				R("user/promoteInformationAdd",array($rel['userid']));
				//更新user_profile信息
				M("UserProfile")->where("userid=".$rel['userid'])->save(array("telphone"=>$data['telphone']));
				$this->ajaxReturn(100,$rel,1);
			}
			$this->ajaxReturn(1,"success",1);
		}else{
			$this->ajaxReturn(0,"操作失败，请稍后重试",0);
		}
	}
	
	/**
	 * 增加okgirl报名数据
	 * @param array $data
	 * @param int $userid
	 * @author penglele
	 */
	public function addProfile($data,$userid){
		if(!$data){
			return false;
		}
		$activity_okgirl_mod=M("ActivityOkgirlProfile");
		$rel=$activity_okgirl_mod->add($data);
		$coupon_msg="报名OK!girl 2013全国挑战赛成功";//报名成功给用户发送优惠券
		if($rel){
			if(!$userid){
				//非LOLITABOX用户
				$user_data['usermail']=$data['email'];
				$user_data['password']=md5(trim($data['telphone']));
				$user_data['nickname']=R("user/autoLolitaNickname",array($data['name']));
				$user_data['addtime']=date("Y-m-d H-i-s");
				$usr_mod=M("Users");
				$ret=$usr_mod->add($user_data);
				if($ret){
					$usreinfo=D("Users")->getUserInfo($ret);
					$activity_okgirl_mod->where("id=$rel")->save(array('userid'=>$usreinfo['userid']));
					D("Coupon")->addCoupon(10,$coupon_msg,$usreinfo['userid']);
					return $usreinfo;
				}
			}else{
				D("Coupon")->addCoupon(10,$coupon_msg,$userid);
			}
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * okgirl奖品早知道
	 * @author penglele
	 */
	public function okgirl_prize(){
		$return['title']="OK!Girl 奖品早知道-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * ++++++++++++++++++与OK杂志的合作end++++++++++++++++++++++
	 */	
	
	/**
	 * OK!girl调查问卷显示
	 * 
	 */
	public function okgirl_survey(){

		if($_POST["submit"]) {
			//提交调查表单需要判断
			//1，是否登录；2，是否提交过；
			$userid=$this->userid;
			if(!$userid) {
				$this->ajaxReturn(0,"请先登录再参与调查问卷！\r\n（参与调查问卷成功后可以获得30积分！）",0);
			}
			$surveystat_mod=M("activity_okgirl_surveystat");
			if($surveystat_mod->where("userid=".$userid)->find()) {
				$this->ajaxReturn(0,"对不起，您已经参与过调查问卷，再次感谢您的参与！",0);
			}
			//处理调查表单提交请求
			//最大选项20
			$result=array();
			$result_other=array();
			$max_question_num=20;
			$survey_config=$this->getOkgirlSurveyConfig();
			$answer_data=array();
			$add_datetime=date("Y-m-d H:i:s");
			for($i=1;$i<=20;$i++) {
				$result[$i]="";
				$result_other[$i]="";
				$question_item="question_".$i;
				$question_item_other=$question_item."_other";
				$result[$i]=$_POST[$question_item];
				if($i!=8 AND empty($result[$i])) {
					$this->ajaxReturn($i,"请选择【".$survey_config[$i]["title"]."】",0);
					exit;
				}
				if($result[$i]=="other") {
					$result_other[$i]=$_POST[$question_item_other];
					if(empty($result_other[$i])) {
						$this->ajaxReturn($i,"【".$survey_config[$i]["title"]."】请输入内容",0);
						exit;
					}
				}
				$answer_data[]=array(
										"questionid"=>$i,
										"answer"=>$result[$i],
										"answer_other"=>$result_other[$i],
										"userid"=>$userid,
										"add_datetime"=>$add_datetime
				);
			}
			$surveystat_mod->addAll($answer_data);
			D("UserCreditStat")->addUserCreditStat($userid,"完成OK!girl挚爱榜单问卷调查",50);
			$this->ajaxReturn(1,"<p>您的问卷已经填写完成，非常感谢您的参与！（我们刚刚奖励了您50个积分）<p>幸运的您还有机会得到萝莉盒礼品卡用于直接兑换萝莉盒哦～更多信息请关注此活动新闻聚焦。</p>",1);
			//答完问卷给用户发积分
			exit;
		}
		$return['title']="OK!girl 挚爱榜单 - 萝莉盒";
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 *  获取OK!girl调查问卷配置信息
	 */
	private function getOkgirlSurveyConfig(){
		$survey_config=array(
				"1"=>array(
						"title"=>"最常使用的护肤品牌",
						"options"=>array(
								"a"=>"倩碧Clinique",
								"b"=>"兰蔻Lancôme",
								"c"=>"碧欧泉 Biotherm",
								"other"=>"其他"
						),
				),
				"2"=>array(
						"title"=>"性价比最高的护肤品",
						"options"=>array(
								"a"=>"露得清Neutrogena",
								"b"=>"巴黎欧莱雅 L'Oréal Paris",
								"c"=>"Olay",
								"other"=>"其他"
						),
				),
				"3"=>array(
						"title"=>"最想推荐给闺蜜的护肤产品",
						"options"=>array(
								"a"=>"雅诗兰黛“红石榴精粹水”",
								"b"=>" 悦木之源“泥娃娃面膜”",
								"c"=>"兰蔻 “小黑瓶”",
								"other"=>"其他"
						),
				),
				"4"=>array(
						"title"=>"最渴望拥有的护肤品牌",
						"options"=>array(
								"a"=>"香奈儿Chanel",
								"b"=>"迪奥Dior",
								"c"=>"海蓝之谜 La Mer",
								"other"=>"其他"
						),
				),
				"5"=>array(
						"title"=>"最让你信赖的护肤品牌",
						"options"=>array(
								"a"=>"雅漾Avène",
								"b"=>"欧舒丹L’occitane",
								"c"=>"Fancl",
								"other"=>"其他"
						),
				),
				"6"=>array(
						"title"=>"最想送给BF的护肤产品",
						"options"=>array(
								"a"=>"碧欧泉男士水动力洁面啫喱",
								"b"=>"欧莱雅男士火山岩 控油清痘洁面膏",
								"c"=>"科颜氏白鹰轻便剃须膏",
								"other"=>"其他"
						),
				),
				"7"=>array(
						"title"=>"最信任的护肤意见领袖",
						"options"=>array(
								"a"=>"牛尔",
								"b"=>"宝拉·培冈Paula Begoun",
								"c"=>"小P老师",
								"other"=>"其他"
						),
				),
				"8"=>array(
						"title"=>"最令小伙伴们失望的护肤产品",
				),
				"9"=>array(
						"title"=>"最常使用的彩妆品牌",
						"options"=>array(
								"a"=>"贝玲妃Benefit",
								"b"=>"美宝莲Maybelline",
								"c"=>"妙巴黎 Bourjois",
								"other"=>"其他"
						),
				),
				"10"=>array(
						"title"=>"性价比最高的彩妆品牌",
						"options"=>array(
								"a"=>"蜜丝佛陀MAX FACTOR",
								"b"=>"丝芙兰SEPHORA",
								"c"=>"贝玲妃Benefit",
								"other"=>"其他"
						),
				),
				"11"=>array(
						"title"=>"最期待新品发布的彩妆品牌",
						"options"=>array(
								"a"=>"玫珂菲MAKE UP FOR EVER",
								"b"=>"芭比•波朗Bobbi Brown",
								"c"=>"魅可MAC",
								"other"=>"其他"
						),
				),
				"12"=>array(
						"title"=>"出门一定要用的彩妆产品",
						"options"=>array(
								"a"=>"美宝莲精纯矿物BB霜",
								"b"=>"贝玲妃蒲公英蜜粉",
								"c"=>"倩碧“蜡笔小胖”水漾滋润唇膏笔",
								"other"=>"其他"
						),
				),
				"13"=>array(
						"title"=>"最想推荐给闺蜜的彩妆产品",
						"options"=>array(
								"a"=>"香奈儿Le Vernis 指甲油 601号神秘",
								"b"=>"迪奥Mystic Metallics 系列5色眼影组合",
								"c"=>"Burberry柔感雾盈 润唇膏210号粉石南花",
								"other"=>"其他"
						),
				),
				"14"=>array(
						"title"=>"看到包装就想拥有的彩妆产品",
						"options"=>array(
								"a"=>"兰蔻“大明星”限量睫毛膏",
								"b"=>"美宝莲“飞箭”睫毛膏",
								"c"=>"CK one color双色腮红",
								"other"=>"其他"
						),
				),
				"15"=>array(
						"title"=>"最想收到的香水",
						"options"=>array(
								"a"=>"香奈儿Chanel 邂逅清新淡香水系列淡香水",
								"b"=>"马克•雅可布 Marc Jacobs Honey香氛",
								"c"=>"菲拉格慕 Salvatore Ferragamo 伊人女士淡香水",
								"other"=>"其他"
						),
				),
				"16"=>array(
						"title"=>"最想送给BF的香水",
						"options"=>array(
								"a"=>"卡文•克莱Calvin Klein 飞男士淡香水",
								"b"=>"大卫杜夫DAVIDOFF 王者之风男士淡香水",
								"c"=>"GIORGIO ARMANI 阿玛尼寄情男士香水",
								"other"=>"其他"
						),
				),
				"17"=>array(
						"title"=>"最常使用的香调",
						"options"=>array(
								"a"=>"花香调",
								"b"=>"果香调",
								"c"=>"木质香调",
								"d"=>"海洋香调",
								"other"=>"其他"
						),
				),
				"18"=>array(
						"title"=>"最期待新品发布的香水品牌",
						"options"=>array(
								"a"=>"马克•雅可布Marc Jacobs",
								"b"=>"宝格丽Bvlgari",
								"c"=>"纪梵希 Givenchy",
								"other"=>"其他"
						),
				),
				"19"=>array(
						"title"=>"最想收藏的香水瓶",
						"options"=>array(
								"a"=>"迪奥 Dior 真我纯香香氛",
								"b"=>"娇兰 Guerlain 小黑裙淡香水",
								"c"=>"王维拉VERA WANG 梦幻公主淡香水",
								"other"=>"其他"
						),
				),
				"20"=>array(
						"title"=>"印象最深的香水平面广告",
						"options"=>array(
								"a"=>"巴黎世家Balenciaga 花之密语女士香氛",
								"b"=>"古驰Gucci 经典奢华香水",
								"c"=>"香奈儿Chanel邂逅柔情淡香水",
								"other"=>"其他"
						),
				),
		);
		return $survey_config;
	}
	
	
	
	/**
	 * okgirl报名数据导出
	 * @author penglele
	 */
	public function get_okgirl_join_info(){
		//城市
		$arr=array(
					'1'=>array(
							'title'=>'beijing',
							'info'=>'北京'
							),
					'21'=>array(
							'title'=>'wuhan',
							'info'=>'武汉'
					),
					'3'=>array(
							'title'=>'shanghai',
							'info'=>'上海'
					),
					'4'=>array(
							'title'=>'chengdu',
							'info'=>'成都'
					)
				);
		$path="okgirl/";
		$profile_mod=M("ActivityOkgirlProfile");
		foreach($arr as $key=>$val){
			$dir=$path.$val['title'];
			$joinlist=array();
			$joinlist=$profile_mod->where("FIND_IN_SET( '".$val['info']."', target_city )")->select();
			if($joinlist){
				foreach($joinlist as $ikey=>$ival){
					//如果路径不存在，创建一个文件夹
					if(!file_exists($dir)){
						mkdir ($dir,0777);
					}
					$html_name=mb_convert_encoding($ival['name'],'gb2312','utf-8')."_".$ival['email'].".html";
					$name_path=$dir."/".$html_name;
					//如果文件存在，有可能是重名+++
					if(file_exists($name_path)){
						echo $ival['id']."<br />";
					}else{
						fopen($name_path, "w+");
						//将出生年月转换
						if($ival['birthday']!="0000-00-00"){
							$birth_arr=explode("-",$ival['birthday']);
							$ival['year']=$birth_arr[0];
							$ival['mon']=$birth_arr[1];
							$ival['day']=$birth_arr[2];
						}
						//将用户上传的图片拼出完整的路径
						if($ival[img1]){
							$ival['img1']="http://www.lolitabox.com".$ival['img1'];
						}
						if($ival[img2]){
							$ival['img2']="http://www.lolitabox.com".$ival['img2'];
						}						
						$return=$ival;
						$this->assign("return",$return);
						$info=$this->fetch($path."index.html");
						file_put_contents($name_path,$info);
					}
				}
			}
		}
		exit("end");
	}
	
	/**
	 * 获取okgirl问卷调查结果
	 * @author penglele
	 */
	public function get_okgirl_survey_result(){
		$titlelist=$this->getOkgirlSurveyConfig();
		$survey_mod=M("ActivityOkgirlSurveystat");
		if($titlelist){
			$model =M();
			$sql="SELECT COUNT(DISTINCT(userid)) AS num FROM activity_okgirl_surveystat";
			$query=$model->query($sql);
			$totalnum=$query[0]['num'];
			$str="";
			$title="";
			foreach($titlelist as $key=>$val){
				if($key!=8){
					$li=array();
					$info="";
					$other=array();
					foreach($val['options'] as $ikey=>$ival){
						$num=$survey_mod->where("questionid=".$key." AND answer='".$ikey."'")->count();
						$num=(int)$num;
						$per=round( ($num/$totalnum) * 100 , 2)."%";
						//dump($ival);exit;
						$per_info="<span style='width:350px;'>".$ival."</span>　　　　<span style='float:right;'>".$per."　　【票数：".$num."】</span><br />";
						$info=$info.$per_info;
						if($ikey=="other"){
							$other=$survey_mod->where("answer='".$ikey."' AND questionid=".$key)->select();
							if($other){
								$other_info="";
								foreach($other as $ekey=>$eval){
									$other_info=$other_info.$eval['answer_other']."\r\n";
								}
								if($other_info){
									$other_info="<br /><textarea>".$other_info."</textarea><br />";
								}
								$info=$info.$other_info;
							}
						}
					}
					$info="<div style='margin-left:30px;width:500px;'>".$info."</div>";
					$title="<br />".$key."、".$val['title']."<br>";
					$str=$str.$title.$info;
				}
			}
			$str="<div style='margin-left:200px;margin-top:30px;margin-bottom:50px;'>".$str."</div>";
			echo $str;exit;
		}
	}
	
	/**
	 * 推荐活动
	 * @author penglele
	 */
	public function task(){
		$type=$_GET['type'];
		if(empty($type) || $type>2){
			$type=1;
		}
		$return['type']=$type;
		$userid=$this->userid;
		switch($type){
			case 1:
				$code =encodeNum($userid);
				$return['inviteurl'] = "http://" . $_SERVER ["SERVER_NAME"] . U ( "user/reglogin", array (
						"s" => $code
				) );
				$return['title']="巨划算积分任务-邀请好友注册立即获得50积分-".C("SITE_NAME");
				break;
			case 2:
				$list=D("Task")->getShareProductsListOfTask("relationid");
				if($list){
					$pro_mod=M("Products");
					foreach($list as $key=>$val){
						$info=array();
						$proinfo=$pro_mod->field("pid,pimg,pname,goodssize")->where("pid=".$val['relationid'])->find();
						$info=$proinfo;
						$info["url"]=getProductUrl($proinfo['pid']);
						$list[$key]=$info;
					}
				}
				$return['list']=$list;
				$return['title']="巨划算积分任务-新品分享及精彩分享转发立即获得20积分-".C("SITE_NAME");
				break;
		}
		$return['userinfo']=$this->userinfo;
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 分享注册专题-分享给好友
	 * @author penglele
	 */
	public function share_tofriend(){
		$verify=$_POST['verify'];
		$mail=$_POST['mail'];
		if($verify==""){
			$this->ajaxReturn(0,"验证码不能为空",0);
		}
		if(md5($verify)!=$_SESSION['verify']){
			$this->ajaxReturn(0,"验证码错误",0);
		}
		if($mail==""){
			$this->ajaxReturn(0,"邮箱号不能为空",0);
		}
		$code =encodeNum($this->userid);
		$inviteurl = "http://" . $_SERVER ["SERVER_NAME"] . U ( "user/reglogin", array ("s" => $code) );
		$title="亲爱的，您有一位好友正在邀您到【萝莉盒】试用当下最新、最时尚的化妆品，快去看看吧！";
		$mail_content="亲爱的：<br><br>&nbsp;&nbsp;我注册了萝莉盒，发现这里有很多主流品牌化妆品正在提供试用，强烈推荐你来啊！<br><br>
		 &nbsp;&nbsp;链接地址：{$inviteurl} <br><br>
		&nbsp;&nbsp;【萝莉盒】致力于帮助爱美女性发现并找到真正适合自己的美容产品，倡导最时尚的生活消费理念：先试用后购买！她不仅为广大爱美女性提供风靡时尚的订阅试用方式，同时还将优质的真实产品试用分享及最新最潮的时尚信息呈现给每个用户，使每一位用户享受轻松、快乐试用的同时，找到真正适合自己的产品。";
		$send_mail=sendtomail($mail,$title,$mail_content);
		if($send_mail==false){
			$this->ajaxReturn(0,"操作失败，请稍后重试",0);
		}
		$this->ajaxReturn(1,"success",1);
	}
}	