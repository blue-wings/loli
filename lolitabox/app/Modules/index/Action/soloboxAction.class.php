<?php
/**
 * SOLOBOX控制器
 * @auth zhenghong@sohu.com
 */

class soloboxAction extends commonAction {
	
	const ENCRYPT_KEY = 'sohu_nmc_women';
	
	/**
	 * 模拟SOHU生成授权信息
	 */
	public function auth_test(){
		$rand_uid=rand(100000,999990);
		$array=array("username"=>"模拟SOHU用户".$rand_uid,"uid"=>$rand_uid);
		$authinfo=$this->encrypt($array);
		$authinfo=base64_encode($authinfo);
		echo "<a href='/solobox/order?auth=$authinfo' target='_blank'><b>".$array["username"]."</b>--测试购买SOLOBOX</a><br><br>";
		echo "<br><a href='/solobox/order'>无参数URL请求错误模拟</a><br><br>";
		echo "<br><a href='/solobox/order?auth=asdfjaskjflasdfj'>无效参数URL请求错误模拟</a><br><br>";
	}
	
	
	/**
	 * 供SOHU化妆品合作方下订单URL
	 * http://www.lolitabox.com/solobox/order？auth=xxxxxxx
	 * GET方式传入协商好的加密串，加密串为一维用户数组的序列化加密串
	 * 一维数组：array("username"=>"郑宏","uid"=>"12345");
	 * 
	 * 原则上认为只要是带参数请求到本URL地址的用户信息均为SOHU合作方用户数据
	 */
	public function order(){
		$authinfo=$_GET["auth"];
		if(empty($authinfo)) {
			$this->error("参数错误");
		}
		$authinfo=base64_decode($authinfo);
		$de_authinfo=$this->decrypt($authinfo);
		$order_url=$this->getSoloboxOrderUrl();
		if(is_array($de_authinfo) && count($de_authinfo)==2) {
			
			$sohu_username=$de_authinfo["username"]; //SOHU用户呢称
			$sohu_uid=$de_authinfo["uid"];//SOHU用户ID
			
			if($this->check_login()) {
				//判断当前用户是否为LOLITABOX的登录状态
				$loli_uid=$this->getUserid();
				if(!$this->ifLoliSohuUser($loli_uid, $sohu_uid)){
					//自动建立当前登录用户与SOHU用户的关系
					$this->createLoliSohuRel($loli_uid, $sohu_uid, $sohu_username);
				}
				
				/***跳转到SOLOBOX登录注册页URL***/
				
				header("location:$order_url");
			}
			else {
				//引导用户去登录并完善信息
				//echo $sohu_username.",你好，为了您可以顺利订购我们的盒子，请完善您的信息";
				$user_open_model=M("UserOpenid");
				$data["type"]="sohu";
				$data["openid"]=$sohu_uid;
				$if_sohu=$user_open_model->where($data)->find();
				if(!$if_sohu || !$if_sohu['uid']){
					//如果sohu用户已经存在
					$data["info"]=$sohu_username;
					$data["logindate"]=date("Y-m-d H:i:s");
					$user_open_model->add($data); //预先保存合作用户数据
					//如果当前没有登录的状态，则新创建一个账号
					$open_info=array("openname"=>$sohu_username,"openid"=>$data["openid"],"opentype"=>"sohu");
					$new_userid=R("user/createLolitaboxAccount",array($open_info));
					$user_open_model->where("openid='".$sohu_uid."' AND type='sohu'")->save(array("uid"=>$new_userid,"isbind"=>1));		
					
					session("open_username",$sohu_username);
					session("open_userid",$sohu_uid);
					session("openid_type","sohu");
					session("solobox_url",$this->getSoloboxOrderUrl());
					$reg_url=U("solobox/solobox_user_reg");
					header("location:$reg_url");
				}else{
					$userinfo=D("Users")->getUserInfo($if_sohu['uid'],"usermail,nickname");
					$set_session=array(
							"username"=>$userinfo["usermail"],
							"nickname"=>$userinfo["nickname"],
							"userid"=>$userinfo["userid"]							
							);
					R("user/set_user_session",array($set_session));
					header("location:$order_url");
				}
				
				//$this->success($sohu_username.",你好，为了您可以顺利订购我们的盒子，请完善您的信息",U("solobox/solobox_user_reg"));
				
			}
		}
		else {
			$this->error("用户授权信息错误");
		}
	}
	
	
	/**
	 * SOLO用户完善信息
	 */
	public function solobox_user_reg(){
		//进行SESSION会话判断
		if(session("open_username") && cookie("nickname")) {
			//如果存在SOHU用户会话状态，则进行SOHU用户信息完善工作
			$this->display("solobox_user_reg");
		}
		else {
			echo "非法用户请求！";
		}
	}
	
	/**
	 * 获取当前可能正在售卖的SOLO盒地址
	 * @author zhenghong
	 */
	public function getSoloboxOrderUrl(){
		$box_solo=C("BOX_TYPE_SOLO");
		$box_mod=M("box");
		$curdate=date("Y-m-d");
		$boxlist=$box_mod->where("starttime<='$curdate' AND endtime>='$curdate' AND category=$box_solo")->order("boxid DESC")->limit(0,1)->select();
		if($boxlist){
			$boxid=$boxlist[0]["boxid"];
			$order_url=getBoxUrl($boxid);
		}
		else {
			$order_url=U("buy/index");
		}
		return $order_url;
	}
	
	
	/**
	 * 根据LOLITABOX的UID和SOHU的UID判断是否已经是SOLO的合作用户
	 * @param unknown_type $loli_uid
	 * @param unknown_type $sohu_uid
	 */
	public function ifLoliSohuUser($loli_uid,$sohu_uid){
		$user_openid_mod=M("UserOpenid");
		$where["type"]="sohu";
		$where["uid"]=$loli_uid;
		$where["openid"]=$sohu_uid;
		$user=$user_openid_mod->where($where)->find();
		return $user;
	}
	
	/**
	 * 创建LOLITABOX与SOHU用户的关系
	 * @param unknown_type $loli_uid
	 * @param unknown_type $sohu_uid
	 */
	private function createLoliSohuRel($loli_uid,$sohu_uid,$sohu_username){
		$user_openid_mod=M("UserOpenid");
		$where["type"]="sohu";
		$where["uid"]=$loli_uid;
		if($user_openid_mod->where($where)->find()){
			//如果当前登录用户已经具有SOHU的属性，则不做处理
			return true;
		}
		$data["uid"]=$loli_uid;
		$data["openid"]=$sohu_uid;
		$data["type"]="sohu";
		$data["info"]=$sohu_username;
		$data["logindate"]=date("Y-m-d H:i:s",time());
		return $user_openid_mod->add($data);
	}
	
	
	
	
	//SOHU提供密文编码方法
	public static function encrypt($content)
	{
		$content = serialize($content);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$cryptedpass = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::ENCRYPT_KEY, $content, MCRYPT_MODE_ECB, $iv);
		$chart = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$random = $chart[mt_rand(0,51)];
		$pass = urlencode(base64_encode($random.$cryptedpass));
		return $pass;
	}
	
	//SOHU提供密文解码方法
	public static function decrypt($pass)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$str = substr(base64_decode(urldecode($pass)),1);
		$answer = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::ENCRYPT_KEY, $str, MCRYPT_MODE_ECB, $iv);
		$na = unserialize($answer);
		return $na;
	}
	
	

}