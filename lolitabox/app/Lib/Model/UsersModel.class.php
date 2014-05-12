<?php
//关于用户模型类
class UsersModel extends Model {

	protected $_validate = array(
	array('usermail','require','注册邮箱必须填写！'),
	array('usermail','email','邮箱格式不正确！',1),
	array('usermail','','该邮箱已经注册过！',0,'unique',1),
	array('usermail','checkUsermail','非常抱歉，您的邮箱地址无效！',1,'callback',1),
	array('nickname','require','昵称必须填写！'),
	array('nickname','','该昵称已经存在！',0,'unique',1),
	array('nickname','checkNickname','昵称格式不正确！',1,'callback',1),
	array('password2','password1','确认密码不正确',0,'confirm'),
	array('ifright','checkRight','您的链接地址不正确,请重试！',1,'callback',1),

	);

	protected $_auto = array (
	array('state',0),
	array('password','md5Passwd',1,'callback'),
	array('addtime','returnDateTime',1,'callback'),
	array('invite_uid','returnInviteuid',1,'callback'),
	);

	function returnInviteuid(){
		return 	empty($_POST['inviteuserid'])?0:$_POST['inviteuserid'];
	}

	function md5Passwd(){
		return md5($_POST['password1']);
	}

	function returnDateTime(){
		return date("Y-m-d H:i:s",time());
	}


	function checkRight($data){
		return ($data == 'is wro')?false:true;
	}

	//判断用户邮箱是否在黑名单中
	function checkUsermail($usermail){
		$array_disable_domain=array("chuaizi.com","600mail.com","yy369.com","chuaizai.com",'wiseie.net','wow8.net','qianbao666.net','bccto.me');

		$result = null;
		foreach ($array_disable_domain as $key=>$value){
			if(stristr($usermail,$value)){
				$result.=$value;
			}
		}
		
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	
	
	
	
	
	function checkNickname($data){

		$pattern1 = '/[\x{4e00}-\x{9fa5}]/siu';
		preg_match_all($pattern1, $data, $r);

		if($r[0]){
			foreach ($r['0'] as $key => $value){
				$totlen.=$value;
			}

			$length = (int)strlen($data) -  strlen($totlen) + count($r[0]);

			if($length > 10){
				return false;
			}

		}else{
			if(strlen($data) > 10){
				return false;
			}
		}
	}

	/**
	 * 通过用户id获取用户信息
	 * +--------------------------------+
	 * sex:  0表示女   1表示男
	 * +--------------------------------+
	 * @param int $userid
	 * @param stirng $field 所需返回字段
	 */
	public function getUserInfo($userid,$field=null)
	{
		if(!$userid) return false;
		$userinfo = $this->field($field)->where("users.userid=".$userid)->join("user_profile on user_profile.userid=users.userid")->find();
		if(!$userinfo) return false;
		
		
		if($userinfo['userface']){
			$array_userface = explode ( ".", $userinfo ["userface"] );
			$userface_path = $this->getUserfacePrefix ( $userid );
		}
		if (empty ( $userinfo ['userface'] ) || !file_exists ( $userface_path ["dir"] . $array_userface [0] . ".".$array_userface [1] )) {
			$end_num = substr ( $userid, "-1" );
			if (( int ) $end_num == 0) {
				$end_num = 10;
			}
			$system_userface = "/public/images/userface/" . $end_num . ".jpg";
			
			$userinfo ["userface_180_180"] = $system_userface;
			$userinfo ["userface_100_100"] = $system_userface;
			$userinfo ["userface_85_85"] = $system_userface;
			$userinfo ["userface_65_65"] = $system_userface;
			$userinfo ["userface_55_55"] = $system_userface;
			$userinfo ["userface_50_50"] = $system_userface;
			$userinfo ["userface_45_45"] = $system_userface;
			$userinfo ["userface_40_40"] = $system_userface;
			$userinfo ["userface"] = $system_userface;
		} else {
// 			$array_userface = explode ( ".", $userinfo ["userface"] );
// 			$userface_path = $this->getUserfacePrefix ( $userid );
			
			// 180x180
			if (file_exists ( $userface_path ["dir"] . $array_userface [0] . "_180_180." . $array_userface [1] )) {
				$userinfo ["userface_180_180"] = $userface_path ["url"] . $array_userface [0] . "_180_180." . $array_userface [1];
			} else {
				// $userinfo["userface_180_180"]="/data/userimg/u180.jpg";
				$userinfo ["userface_180_180"] = $system_userface;
			}
			// 100x100,85x85,65x65 =>100x100
			if (file_exists ( $userface_path ["dir"] . $array_userface [0] . "_100_100." . $array_userface [1] )) {
				$userinfo ["userface_100_100"] = $userface_path ["url"] . $array_userface [0] . "_100_100." . $array_userface [1];
				$userinfo ["userface_85_85"] = $userface_path ["url"] . $array_userface [0] . "_100_100." . $array_userface [1];
				$userinfo ["userface_65_65"] = $userface_path ["url"] . $array_userface [0] . "_100_100." . $array_userface [1];
			} else {
				$userinfo ["userface_100_100"] = $system_userface;
				$userinfo ["userface_85_85"] = $system_userface;
				$userinfo ["userface_65_65"] = $system_userface;
			}
			// 55x55
			if (file_exists ( $userface_path ["dir"] . $array_userface [0] . "_55_55." . $array_userface [1] )) {
				$userinfo ["userface_55_55"] = $userface_path ["url"] . $array_userface [0] . "_55_55." . $array_userface [1];
				$userinfo ["userface_50_50"] = $userface_path ["url"] . $array_userface [0] . "_55_55." . $array_userface [1];
				$userinfo ["userface_45_45"] = $userface_path ["url"] . $array_userface [0] . "_55_55." . $array_userface [1];
				$userinfo ["userface_40_40"] = $userface_path ["url"] . $array_userface [0] . "_55_55." . $array_userface [1];
			
			} else {
				$userinfo ["userface_55_55"] = $system_userface;
				$userinfo ["userface_50_50"] = $system_userface;
				$userinfo ["userface_45_45"] = $system_userface;
				$userinfo ["userface_40_40"] = $system_userface;
			}
			if (file_exists ( $userface_path ["dir"] . $userinfo ['userface'] ))
				$userinfo ['userface'] = $userface_path ['url'] . $userinfo ['userface'];
		}

		if(isset($userinfo["experience"])){
			if($userinfo["if_super"]==1){
				$score=9;
				$userinfo['levelname']='萝莉认证达人';
			}else{
				if ($userinfo['experience']<100) {
					$score=1;
					$userinfo['levelname']='V1萝莉新生';
				}elseif ($userinfo['experience']<500) {
					$score=2;
					$userinfo['levelname']='V2萝莉之初';
				}elseif ($userinfo['experience']<1500) {
					$score=3;
					$userinfo['levelname']='V3萝莉女孩';
				}elseif ($userinfo['experience']<3500) {
					$score=4;
					$userinfo['levelname']='V4萝莉小妖';
				}elseif ($userinfo['experience']<8000) {
					$score=5;
					$userinfo['levelname']='V5萝莉精灵';
				}elseif ($userinfo['experience']<16000) {
					$score=6;
					$userinfo['levelname']='V6萝莉天使';
				}elseif ($userinfo['experience']<30000) {
					$score=7;
					$userinfo['levelname']='V7萝莉仙女';
				}else{
					$score=8;
					$userinfo['levelname']='V8萝莉女王';
				}
			}
			$userinfo['level']=$score;
		}
		if($userinfo['skin_property']){
			$userinfo['skin_property']=$userinfo['skin_property']=='皮肤' || $userinfo['skin_property']=='请选择' ? '':$userinfo['skin_property'];
		}
		$userinfo['userid']=$userid;
		return $userinfo;
	}

	/**
	 * 更新基本信息
	 * @param int $userid
	 * @param mixed $data
	 */
	public function updateUserProfileData($userid,$data){
		M("UserProfile")->where("userid=".$userid)->save($data);
	}

	/**
	 * 获取用户收货地址列表
	 * @param int $userid
	 */
	public function getAddressList($userid){
		$list= M("UserAddress")->where("userid=".$userid." AND if_del=0")->select();
		foreach($list as $key=>$val){
			$list[$key]['json'] = json_encode($val);
		}
		return $list;
	}


	//获取用户的订单数(mybox)
	public function updateOrderNum($userid){
		$order_mod=M("UserOrder");
		$user_mod=M("users");
		$order_num=$order_mod->where("userid=$userid AND  state=1")->count();
		$data['order_num']=$order_num;
		$ret=$user_mod->where("userid=$userid")->save($data);
		return $order_num;
	}

	//获取用户的粉丝数
	public function updateFansNum($userid){
		$rel_mod=M("UserBehaviourRelation");
		$user_mod=M("users");
		$fans_num=$rel_mod->where("whoid=$userid AND  type='follow_uid'")->count();
		$data['fans_num']=$fans_num;
		$ret=$user_mod->where("userid=$userid")->save($data);
	}

	//获取用户的关注的人数
	public function updateFollowNum($userid){
		$rel_mod=M("UserBehaviourRelation");
		$user_mod=M("users");
		$follow_num=$rel_mod->where("userid=$userid AND  type='follow_uid'")->count();
		$data['follow_num']=$follow_num;
		$ret=$user_mod->where("userid=$userid")->save($data);
	}



	//获取头像的url
	public function getUserfacePrefix($userid)
	{
		if(empty($userid)) return "";
		$dir_id=ceil($userid/300); //每300个ID的用户头像保存在一个文件夹中
		$ret['dir']=PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'userimg'.DIRECTORY_SEPARATOR.$dir_id.DIRECTORY_SEPARATOR; //保存物理路径
		$ret["url"]="/data/userimg/".$dir_id."/";
		return $ret;
	}


	//更新用户的优惠券数
	public function updateCouponNum($userid){
		$rel_mod=M("coupon");
		$user_mod=M("users");
		$coupon_num =$rel_mod->where("owner_uid=$userid")->count();
		$data['coupon_num']=$coupon_num;
		$ret=$user_mod->where("userid=$userid")->save($data);
	}

	/**
	 * 判断用户是否为解决方案
	 * @param unknown_type $userid
	 */
	public function checkSolution($userid){
		$flag=$this ->where("userid=".$userid." AND is_solution=1")->find();
		return $flag ? true : false;
	}

	/**
	 * 获取解决方案列表
	 * @param unknown_type $where
	 * @param unknown_type $limit
	 * @param unknown_type $me
	 */
	public function getSolutionList($limit="0,5",$me="",$where=array(),$order="userid DESC"){
		$where ['is_solution'] = array("gt",0);
		$list = $this ->field("userid")->where($where) ->limit($limit) ->order($order)->select();
		$follow_mod =M("Follow");
		foreach ($list as $key =>$val){
			$info = $this ->getUserInfo($val['userid'],"nickname,userface,fans_num,description as alias");
			$list[$key]['nickname'] = $info['nickname'];
			$list[$key]['userface'] = $info['userface_65_65'];
			$list[$key]['fans_num'] = $info['fans_num'];
			$list[$key]['alias'] = $info['alias'];
			$list[$key]['solutionurl'] =  getSolutionUrl($val['userid']);
			if($me){
				$type =$follow_mod ->where("userid=".$me." AND whoid=".$val['userid']." AND type=1")->find();
				$list[$key]['type'] = $type ? 1:0;
			}

		}
		return $list;
	}


	/**
	 * 获取推荐解决方案
	 * @param unknown_type $limit
	 * @param unknown_type $me
	 */
	public function getRemmendSolutionList($limit="3",$me=""){
		$where ['is_solution'] = 2;
		return $this ->getSolutionList($limit,$me,$where);
	}


	/**
     * 通过用户ID获取你感兴趣的人
     * @param int $userid
     * @param int $num
     */
	public function getInterestUser($num="5",$me=""){
		$follow_mod = M("Follow");
		$where ['if_super'] =1 ;
        if($me){
        	$where['userid'] = array("exp","not in(SELECT whoid FROM follow WHERE  userid=$me AND type=1)");
        }
		$list = $this ->field("userid")->where($where) ->order("rand()")->limit($num)->select();
		foreach($list as $key =>$val){
			$info = $this ->getUserInfo($val['userid'],"nickname,userface,fans_num,blog_num,is_solution,if_super");
			$list [$key]['nickname'] = $info['nickname'];
			$list [$key]['userface'] = $info['userface_55_55'];
			$list [$key]['fans_num'] = $info['fans_num'];
			$list [$key]['share_num'] = $info['blog_num'];
			$list [$key]['spaceurl'] = getSpaceUrl($val['userid']);
			$list [$key]['if_super'] = $info['is_solution'] ? 2:$info['if_super'] ;
			if($me){
				$type =$follow_mod ->where("userid=".$me." AND whoid=".$val['userid']." AND type=1")->find();
				$list[$key]['type'] = $type ? 1:0;
			}
		}
		return $list;

	}

	/**
	 * 获取解决方案详情
	 * @param int $solutionid
	 * @param int $me
	 */
	public function getSolutionInfo($solutionid,$me=""){
		$info = $this ->getUserInfo($solutionid,"userface,nickname");
		if($me){
			$type = M("Follow")->where("userid=".$me." AND whoid=".$solutionid." AND type=1")->find();
			$info['type'] = $type ? 1:0;
		}
		$info['fans_num'] = D("Follow") ->getFansNumBySolutionid($solutionid);
		return $info;
	}

	/**
	 * 通过条件查询用户信息
	 * @param array $where 查询条件
	 * @param string $field 需要查询的内容
	 * @return array $userlist
	 * @param $if_userface 是否需要返回头像信息$if_userface=0不返 $if_userface=1返
	 * @author penglele
	 */
	public function getUserInfoByData($where,$field="*",$limit="1",$order,$if_userface=0){
		$userlist=$this->where($where)->field($field)->limit($limit)->order("$order")->select();
		if(!$userlist)
		return false;
		if($if_userface>0){
			foreach($userlist as $key=>$value){
				$u_info=$this->getUserInfo($value[userid],"userface");
				$userlist[$key]['userface']=$u_info['userface_100_100'];
				$userlist[$key]['userface_55_55']=$u_info['userface_55_55'];
			}
		}
		return $userlist;
	}

	/**
	 * 通过关键字匹配用户
	 * @param unknown_type $tagname
	 * @param unknown_type $limit
	 * @param unknown_type $order
	 * @param unknown_type $me
	 */
	public function getUserListByTag($tagname,$limit="10",$order="",$me=""){
		//$where ['is_solution'] = 0;
		$where ['nickname'] = array("like","%$tagname%");
		$list = $this ->where($where)->field("userid")->order($order)->limit($limit)->select();
		$follow_mod =M("Follow");
		foreach($list as $key =>$val){

			$userinfo =$this ->getUserInfo($val['userid'],"nickname,userface,fans_num,if_super,is_solution");
			$list [$key] = $userinfo;
			$list [$key] ['spaceurl'] =getSpaceUrl($val['userid']);
			if($me){
				$type = $follow_mod->where("userid=".$me." AND whoid=".$val['userid']." AND type=1")->find();
				$list[$key]['type'] = $type ? 1:0;
			}
			//$list[$key]['nickname'] = str_replace($tagname, "<span class='S_txt3'>$tagname</span>", $list[$key]['nickname']);
            
			$info['if_super'] = $info['is_solution'] ? 2:$info['if_super'] ;
		}
		return $list;
	}


	/**
	 * 通过关键字匹配用户
	 * @param unknown_type $tag
	 */
	public function getUserCountByTag($tag){
		//$where ['is_solution'] = 0;
		$where ['nickname'] = array("like","%$tag%");
		return $this ->where($where)->count("userid");
	}

	/*
	查询用户是否存在
	zhaoxiang
	*/
	function returnUserInfoIfExists($where){
		$info = $this->where($where)->find();
		return empty($info)?false:$info;
	}

	/**
	 * 增加地址
	 * @param int  $userid
	 * @param mixed $data
	 */
	public function addAddress($data){
		if(empty($data['userid']))
		return 0;
		$user_address = M("UserAddress");
		$list = $this->getAddressList($data['userid']);
		if(count($list) >= 3){
			return -1;
		}
		if($data['if_active']==1){
			$data1['if_active']=0;
			$a=$user_address->where("userid={$data['userid']} AND if_del=0")->save($data1);
			if($a===false){
				return -2;
			}
		}
		$data ['addtime'] = date ( "Y-m-d H:i:s" );
		return  $user_address->add($data);

	}

	/**
	 * 设置默认地址
	 */
	public function setDefaultAddress($id,$userid){
		$address_mod=M("userAddress");
		$data['if_active']=0;
		$address_up=$address_mod->where("userid=$userid AND if_del=0")->save($data);
		if($address_up!==false){
			$where['if_active']=1;
			$ret=$address_mod->where("userid=$userid AND id=$id AND if_del=0")->save($where);
			if($ret !==false){
				return true;
			}
		}
		return false;
	}

	/**
	 * 更新地址
	 * @param unknown_type $id
	 * @param unknown_type $data
	 * @return boolean|Ambigous <boolean, unknown>
	 */
	public function updateAddress($id,$data){
		$user_address = M("userAddress");
		if($data['if_active']==1){
			$data1['if_active']=0;
			$a=$user_address->where("userid=".$data['uid']." AND if_del=0")->save($data1);
			if($a===false){
				return false;
			}
		}
		return $user_address ->where("id=".$id)->save($data);
	}

	/**
	 * 检索用户信息是否存在（完全匹配）
	 * @param fieldkey 字段名
	 * @param fieldname 字段值
	 */
	public function searchUserinfoByField($fieldkey,$fieldvalue){
		if(empty($fieldkey) || empty($fieldvalue)) return null;
		$user_m=M(Users);
		$where[$fieldkey]=$fieldvalue;
		if($user_m->where($where)->find()) {
			return true;
		}
		else {
			return false;
		}

	}

	/**
	 * 登录框里头右侧十个头像
	 */
	public function getUserface(){
		$useridlist = array(12154,3008,14230,2950,199,22098,209,27689,23205,3433);
		$user_mod = D("Users");
		foreach($useridlist as $key =>$val){
			$list [$key]['userid'] = $val;
			$userinfo = $this ->getUserInfo($val,"userface");
			$list [$key]['userface'] = $userinfo ['userface_50_50'];
			$list [$key]['spaceurl'] = getSpaceUrl($val);
		}
		return $list;
	}


	/**
	 * 获取邀请列表
	 * @param int $userid
	 */
	public function getInviteList($userid,$limit="10"){
		$list = $this ->field("userid,nickname,order_num,addtime")->where("invite_uid=".$userid)->limit($limit)->order("userid DESC")->select();
		$credit_mod=D("UserCreditStat");
		foreach($list as $key=>$val){
			$if_check=$credit_mod->getUserTaskStat($list[$key]['userid'],"mobile");
			if($if_check==1){
				$list[$key]['state'] = "已验证";
				$list[$key]['score'] = "50";
			}else{
				$list[$key]['state'] = "未验证";
				$list[$key]['score'] = "0";
			}
		}
		return $list;
	}

	/**
	 * 获取邀请总数【全部的】
	 * @param unknown_type $userid
	 */
	public function getInviteCount($userid){
		$list = $this ->field("userid")->where("invite_uid=".$userid)->select();
		return count($list);
	}
	
	/**
	 * 判断用户是否是内部账号
	 * @param $userid 用户ID
	 * @author penglele
	 */
	public function checkUserIfInner($userid){
		$sql="SELECT usermail FROM users WHERE userid=$userid AND (usermail LIKE 'nbceshi%lolitabox.com' OR usermail LIKE 'pingce%lolitabox.com')";
		$userinfo=$this->query($sql);
		if($userinfo[0])
			return true;
		else 
			return false;
	}
	
	/**
	 * 记录用户手机验证的验证码
	 * @param $userid 用户ID
	 * @param $tel 手机号码
	 */
	public function addUserTelCode($userid,$tel){
		$check_mod=M("UserTelphone");
		if(!$userid || !$tel)
			return false;
		$ntime=time();
		$code=rand(100000,999999);
		$data["code"]=$code;
		$data["addtime"]=$ntime;
		$data['userid']=$userid;
		//dump($data);exit;
		//通过手机号，查询手机绑定信息
		$if_tel=$check_mod->where("tel='".$tel."'")->find();
		//通过userid获取用户未绑定状态的信息
		$check_info=$check_mod->where("userid=$userid AND if_check=0")->find();
		//通过userid获取用户已绑定状态的信息
		$is_bind=$check_mod->where("userid=$userid AND if_check=1")->find();
		//该手机号已与其他账号绑定过
		if($is_bind['tel']==$tel){
			return 300;
		}
		if($if_tel && $if_tel['userid']!=$userid && $if_tel['if_check']!=0){
			return 200;
		}
		//同一用户10分钟内不可多次请求
		if(($ntime-$if_tel['addtime']<10*60) || ($ntime-$check_info['addtime']<10*60)){
			return 100;
		}
		//如果此号码不存在且用户没有未绑定的状态，则新生成一条未绑定状态的信息
		if(!$if_tel && !$check_info){
			$data["tel"]=$tel;
			$res=$check_mod->add($data);
			if($res===false){
				return false;
			}
		}
		if($if_tel){
			//当通过电话号码查找信息存在时，
			$res_save=$check_mod->where("tel='".$if_tel['tel']."'")->save($data);
			if($res_save===false){
				return false;
			}else{
				if($check_info && $check_info['tel']!=$if_tel['tel']){
					$check_mod->where("tel='".$check_info['tel']."'")->delete();
				}
			}
		}else{
			if($check_info){
				//当通过电话查找不存在，但用户的未验证信息存在时
				$data["tel"]=$tel;
				$check_mod->where("tel='".$check_info['tel']."'")->save($data);
			}
		}
		
		$content="您申请的手机验证码是：".$code."，请您妥善保管并在10分钟内进行验证【萝莉盒】";
		sendtomess($tel,$content);
		//up user_profile表中的手机号码
		M("userProfile")->where("userid=$userid")->save(array("telphone"=>$tel));
		$edate=$data["addtime"]+10*60;
		$date_arr=explode("-",date("Y-m-d-H-i-s",$edate));
		$arr['mon']=$date_arr[0];
		$arr['year']=$date_arr[1];
		$arr['day']=$date_arr[2];
		$arr['hour']=$date_arr[3];
		$arr['min']=$date_arr[4];
		$arr['sec']=$date_arr[5];
		return $arr;
	}
	
	/**
	 * 验证用户手机验证的验证码
	 */
	public function checkUserTelCode($userid,$code){
		if(!$userid || !$code)
			return false;
		$check_mod=M("UserTelphone");
		$code_info=$check_mod->where("userid=$userid AND if_check!=1")->order("addtime DESC")->find();
		if(!$code_info){
			//用户验证信息不存在
			return 100;
		}
		if($code!=$code_info['code']){
			//验证码错误
			return 101;
		}
		$ntime=time();
		if($ntime-$code_info['addtime']>24*60*60){
			//验证码已过期
			return 102;
		}
		$data['if_check']=1;
		$check=$check_mod->where("id='".$code_info['id']."'")->save($data);
		if(!$check){
			return false;
		}
		
		$if_have=$check_mod->where("userid=$userid AND if_check=1 AND tel!='".$code_info['tel']."'")->find();
		//如果用户已成功绑定过其他手机，此为再次绑定，将以前绑定状态设为2
		if($if_have){
			$check_mod->where("tel='".$if_have['tel']."'")->save(array("if_check"=>2));
		}
		$this->where("userid=$userid")->save(array("tel_status"=>1));//在用户表中用户的绑定状态
		D("Task")->addUserTask($userid,3);//记录用户绑定手机的任务完成
		$credit_mod=D("UserCreditStat");
		$userinfo=$this->getUserInfo($userid,"invite_uid");
		if($userinfo['invite_uid']>0){
			//如果该用户是被邀请用户,仅第一次验证成功送积分
			if(!M("userCreditStat")->where("userid=$userid AND action_id ='user_verify_mobile'")->find()){
				D("Task")->addUserTask($userinfo['invite_uid'],10,0,$userid);//增加邀请者已邀请成功的记录
				$credit_mod->optCreditSet($userinfo['invite_uid'],"user_invite_reg");//给邀请者发送积分
			}
		}
		//用验证手机成功发送优惠券
		$mess="恭喜您手机验证成功并获得<b>10</b>元优惠券，优惠券可用于<a href='/buy/index.html' target='_blank' class='WB_info'>购买萝莉盒</a>，";
		$if_coupon=D("Coupon")->addCoupon(10,"完成验证手机",$userid,"","",$mess);
		if($if_coupon!=false){
			$phone_msg="恭喜您手机验证成功并获得10元优惠券，优惠券可用于购买萝莉盒，赶紧去看看都有什么萝莉盒吧【萝莉盒】";
			sendtomess($if_have['tel'],$phone_msg);
		}
		$credit_mod->optCreditSet($userid,"user_verify_mobile");//用户绑定手机发送积分
		return true;
	}
	
	
	/**
	 * 获取最新注册用户列表 FOR V5high活动
	 * @param int $limit 
	 * @return array $userlist
	 * @author zhenghong
	 * 2013-09-06
	 */
	public function getNewUserList($limit=50){
		$userlist = $this ->field("userid,nickname")->order("userid DESC")->limit($limit)->select();
		return $userlist;
	}
	
	/**
	 * 获取指定用户ID的邀请用户列表
	 * @param unknown_type $userid
	 * @param unknown_type $limit
	 * @param unknown_type $othercondition
	 * @return $list 用户列表
	 */
	public function getInviteUserList($userid,$limit=10,$othercondition=""){
		$where["invite_uid"]=$userid;
		if( is_array($othercondition)){
			$where=array_merge($where,$othercondition);
		}
		$list = $this ->field("userid,nickname,order_num,addtime,tel_status")->where($where)->limit($limit)->order("userid DESC")->select();
		foreach($list as $key=>$val){
			if($list[$key]['tel_status']==1){
				$list[$key]['state'] = "已验证";
				$list[$key]['score'] = "100";
			}else{
				$list[$key]['state'] = "未验证";
				$list[$key]['score'] = "0";
			}
		}
		return $list;
	}
	
	/**
	 * 获取指定用户ID的邀请用户总数
	 * @param unknown_type $userid
	 * @param unknown_type $othercondition
	 * @return $count 
	 */
	public function getInviteUserCount($userid,$othercondition=""){
		$where["invite_uid"]=$userid;
		if( is_array($othercondition)){
			$where=array_merge($where,$othercondition);
		}
		$count = $this ->field("userid")->where($where)->count();
		return $count;
	}
	
	/**
	 * 获取loliv5活动期间用户成功邀请注册的总数
	 * @author penglele
	 */
	public function getUserInviteNumByLoliv5($userid){
		if(!$userid){
			return 0;
		}
		$stime="2013-09-09 00:00:00";
		$etime="2013-09-27 17:00:00";
		$where['addtime']=array("between",array($stime,$etime));
		$where['invite_uid']=$userid;
		$where['tel_status']=1;
		$num=$this->where($where)->count();
		return (int)$num;
	}
	
	/**
	 * 获取用户个人中心
	 */
	public function getUserDataNum($userid){
		//需要显示数字的内容
		$arr=array(
				'ordernum'=>0,
				'ordernum_y'=>0,
				'ordernum_n'=>0,
				'trynum'=>0,
				'trynum_y'=>0,
				'trynum_n'=>0,
				'boxnum'=>0,
				'trypronum'=>0,
				'trypronum_y'=>0,
				'trypronum_n'=>0,
				'sharenum'=>0,
				'sharenum_box'=>0,
				'sharenum_try'=>0,
				'zannum'=>0,
				'treadnum'=>0,
				'replynum'=>0,
				'replynum_r'=>0,
				'replynum_s'=>0,
				'ntasknum'=>0,
				'ytasknum'=>0,
				'fbrandnum'=>0,
				'couponnum'=>0,
				'giftcardnum'=>0,
				'score'=>0,
				'rmsgnum'=>0,
				'smsgnum'=>0,
				'lmsgnum'=>0,
				'shareoutnum'=>0
				);
		if(!$userid){
			return $arr;
		}
		$order_mod=D("UserOrder");
		$share_mod=D("UserShare");
		$task_mod=D("Task");
		$msg_mod=D("Msg");
		$pro_mod=D("Products");
		$arr['ordernum']=$order_mod->getUserOrderNumByStat($userid);//萝莉盒订单
		$arr['ordernum_y']=$order_mod->getUserOrderNumByStat($userid,1);//萝莉盒订单-已支付
		$arr['ordernum_n']=$order_mod->getUserOrderNumByStat($userid,0);//萝莉盒订单-未支付
		$arr['trynum']=$order_mod->getUserTryOrderNumByStat($userid);//试用订单
		$arr['trynum_y']=$order_mod->getUserTryOrderNumByStat($userid,1);//试用订单-已支付
		$arr['trynum_n']=$order_mod->getUserTryOrderNumByStat($userid,0);//试用订单-未支付
		$arr['boxnum']=$order_mod->getUserOrderCount($userid);//我入手的萝莉盒
		$arr['trypronum']=$pro_mod->getUserOrderProductsCount($userid);//我试用的美妆品
		$arr['trypronum_y']=$pro_mod->getOrderProductCountOfShare($userid);
		$arr['trypronum_n']=$pro_mod->getOrderProductNumOfNotShare($userid);
		$arr['sharenum']=$share_mod->getMyShareNum($userid,0);//我的分享
		$arr['sharenum_box']=$share_mod->getMyShareNum($userid,4);//我的分享--晒盒
		$arr['sharenum_try']=$share_mod->getMyShareNum($userid,1);//我的分享-试用
		$arr['zannum']=$share_mod->getShareNumByAction($userid,2);//我赞的分享
		$arr['treadnum']=$share_mod->getShareNumByAction($userid,1);//我踩的分享
		$receive_num=$share_mod->getReceiverCommentNum(array('to_uid'=>$userid));
		$send_num=$share_mod->getReceiverCommentNum(array('userid'=>$userid));
		$arr['replynum']=$receive_num+$send_num;//我的评论
		$arr['replynum_r']=$receive_num;//我的评论-收到的
		$arr['replynum_s']=$send_num;//我的评论--发出的
		$arr['ntasknum']=$task_mod->getUnfinishedNum($userid);//未完成的任务的总数
		$arr['ytasknum']=$task_mod->getFinishedNum($userid);//已完成的任务的总数
		$arr['fbrandnum']=D("Follow")->getFollowNumByUserid($userid,3);
		$arr['couponnum']=M("coupon")->where("owner_uid=$userid")->count();//用户优惠券总数
		$arr['giftcardnum']=D("Giftcard")->getUserGiftcardNum($userid);
		$userinfo=$this->getUserInfo($userid,"score");
		$arr['score']=$userinfo['score'];//用户积分总数
		$arr['rmsgnum']=$msg_mod->getReceverMsgCount($userid);//收到的私信总数
		$arr['smsgnum']=$msg_mod->getPostMsgCount($userid);//发出的私信总数
		$arr['lmsgnum']=$msg_mod->getMsgCountByLolitabox($userid);//萝莉官网的私信总数
		$arr['shareoutnum']=$share_mod->getUserShareOutShareNum($userid);
		return $arr;
	}
	
	
}

?>