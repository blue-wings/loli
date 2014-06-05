<?php
// 本类由系统自动生成，仅供测试用途
class IndexAction extends CommonAction  {

	// 检查用户是否登录
	protected function checkUser() {
		if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
			// 			$this->assign('jumpUrl',__URL__.'/login');
			// 			$this->error('没有登录');
			$this->redirect('Index/login');
		}
	}

	public function index(){
		//如果通过认证跳转到首页
		//		$this->redirect('Index/index');
		$this->checkUser();
		$this->display();
	}

	public function top(){
		$this->display();
	}

	public function left(){
		$this->checkUser();
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			//显示菜单项
			$menu  = array();
			if(isset($_SESSION['menu'.$_SESSION[C('USER_AUTH_KEY')]])) {

				//如果已经缓存，直接读取缓存
				$menu   =   $_SESSION['menu'.$_SESSION[C('USER_AUTH_KEY')]];
			}else {
				//读取数据库模块列表生成菜单项
				$node    =   M("ThinkNode");
				$id	=	$node->getField("id");
				$where['type']=0;
				$where['level']=2;
				$where['status']=1;
				$where['pid']=$id;
				$list	=	$node->where($where)->field('id,name,group_id,title')->order('sort asc')->select();

				$accessList = $_SESSION['_ACCESS_LIST'];

				foreach($list as $key=>$module) {
					//公共模块不弄到菜单中
					if($module['name']=="Public") continue;
					if(isset($accessList[strtoupper(GROUP_NAME)][strtoupper($module['name'])]) || $_SESSION['administrator']) {
						//设置模块访问权限
						$module['access'] =  1;
						$menu[$key]  = $module;
					}
					$sun_list=$node->where(array('type'=>0,'level'=>3,'status'=>1,'pid'=>$module['id']))->field('id,name,group_id,title')->order('sort asc')->select();
					if($sun_list) {
						foreach ($sun_list as $k=>$action){
							if(isset($accessList[strtoupper(GROUP_NAME)][strtoupper($module['name'])][strtoupper($action['name'])]) || $_SESSION['administrator']) {
								//设置模块访问权限
								$action['access'] =  1;
								$menu[$key]['action'][$k]  = $action;
							}
						}
					}
				}
				//缓存菜单访问
				$_SESSION['menu'.$_SESSION[C('USER_AUTH_KEY')]]	=	$menu;
			}
			if(!empty($_GET['tag'])){
				$this->assign('menuTag',$_GET['tag']);
			}
			$this->assign('menu',$menu);
		}
		$this->display();
	}

	public function down(){
		$this->display();
	}

	public function center(){
		$this->display();
	}


	/**
	 *判断用户身份,增加未审核的出库单数量
	 *@update zhaoxiang   2013/3/1
	 *@update zhaoxiang   2013/4/3
	 */
	public function indexblank(){

		$where=array(
		'status' =>1,
		'ifagree' =>0,
		'agreeoperator'=>''
		);
			
		if($_SESSION['account'] == 'maxiao'){
			
			$where['type']=2;
			$count=M("inventoryOut")->where($where)->count('id');

		}else if($_SESSION['account'] == 'gaorixin'){
			
			$where['type']=1;
			$count=M("inventoryOut")->where($where)->count('id');
			
		}
		$this->assign('type',$where['type']);
		$this->assign('number',$count);
		$this->display();
	}

	/**
	 * 用户登录
	 */
	public function login(){
		if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
			$this->display();
		}else{
			$this->redirect('Index/index');
		}
	}

	// 登录检测
	public function checkLogin() {
		if(empty($_POST['account'])) {
			$this->error('帐号错误！','__URL__/login/');
		}elseif (empty($_POST['password'])){
			$this->error('密码必须！','__URL__/login/');
		}
		//		elseif (empty($_POST['verify'])){
		//			$this->error('验证码必须！');
		//		}
		//生成认证条件
		$map            =   array();
		// 支持使用绑定帐号登录
		$map['account']	= $_POST['account'];
		$map["status"]	=	array('gt',0);
		//		if($_SESSION['verify'] != md5($_POST['verify'])) {
		//			$this->error('验证码错误！');
		//		}

		import ( '@.ORG.Util.RBAC' );
		$authInfo = RBAC::authenticate($map);
		//使用用户名、密码和状态的方式进行认证
		if(false == $authInfo) {
			$this->error('帐号不存在或已禁用！','__URL__/login/');
		}else {
			if($authInfo['password'] != md5($_POST['password'])) {
				$this->error('密码错误！','__URL__/login/');
			}
			$_SESSION[C('USER_AUTH_KEY')]	=	$authInfo['id'];
			$_SESSION['email']				=	$authInfo['email'];
			$_SESSION['account']			=	$authInfo['account'];
			$_SESSION['loginUserName']		=	$authInfo['nickname'];
			$_SESSION['lastLoginTime']		=	$authInfo['last_login_time'];
			$_SESSION['login_count']		=	$authInfo['login_count'];
			if($authInfo['account']=='admin') {
				$_SESSION['administrator']		=	true;
			}
			//保存登录信息
			$Admin	=	M('ThinkAdmin');
			$ip		=	get_client_ip();
			$time	=	time();
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_login_time']	=	$time;
			$data['login_count']	=	array('exp','login_count+1');
			$data['last_login_ip']	=	$ip;
			$Admin->save($data);

			// 缓存访问权限
			RBAC::saveAccessList();
			$this->success('登录成功！');
		}
	}

	/**
     * 登出
     */
	public function logout(){
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			unset($_SESSION[C('USER_AUTH_KEY')]);
			unset($_SESSION);
			session_destroy();
			$this->success('登出成功！',U('Index/login'));
		}else {
			$this->error('已经登出！',U('Index/login'));
		}
	}

	// 更换密码
	public function changePwd()
	{
		$this->checkUser();
		//对表单提交处理进行处理或者增加非表单数据
		//		if(md5($_POST['verify'])	!= $_SESSION['verify']) {
		//			$this->error('验证码错误！');
		//		}

		if (empty($_POST['password']) || empty($_POST['repassword'])) {
			$this->error("密码不能为空");
		}
		if ($_POST['password'] !== $_POST['repassword']) {
			$this->error("两次密码输入不匹配，请重新输入");
		}
		$map	=	array();
		$map['password']= pwdHash($_POST['oldpassword']);
		if(isset($_POST['account'])) {
			$map['account']	 =	 $_POST['account'];
		}elseif(isset($_SESSION[C('USER_AUTH_KEY')])) {
			$map['id']		=	$_SESSION[C('USER_AUTH_KEY')];
		}
		//检查用户
		$User    =   M("ThinkAdmin");
		if(!$User->where($map)->field('id')->find()) {
			$this->error('旧密码不符或者用户名错误！');
		}else {
			$User->password	=	pwdHash($_POST['password']);
			$User->save();
			$this->success('密码修改成功！');
		}
	}
	public function profile() {
		$this->checkUser();
		$User	 =	 M("ThinkAdmin");
		$vo	=	$User->getById($_SESSION[C('USER_AUTH_KEY')]);
		$this->assign('vo',$vo);
		$this->display();
	}

	public function verify()
	{
		$type	 =	 isset($_GET['type'])?$_GET['type']:'gif';
		import("@.ORG.Util.Image");
		Image::buildImageVerify(4,1,$type);
	}
	// 修改资料
	public function change() {
		$this->checkUser();
		$User	 =	 D("ThinkAdmin");
		if(!$User->create()) {
			$this->error($User->getError());
		}
		$result	=	$User->save();
		if(false !== $result) {
			$this->success('资料修改成功！');
		}else{
			$this->error('资料修改失败!');
		}
	}

	/**
	 * 统计发送盒子的会员数
	 */
	public function countUserBoxNum(){
		//统计用户的订购盒子数

	}

	public function fixCity(){
		$PCity=M("TsArea");
		$rs=$PCity->select();
		unlimit::$subIdField="pid";
		unlimit::$parentIdField='area_id';
		unlimit::$reSortKey=true;
		$subs=unlimit::toSub($rs);
		dump($subs);

	}


	public function eachCity($pid=0){
		$PCity=M("TsArea");
		$rs=$PCity->select();
		unlimit::$subIdField="area_id";
		unlimit::$parentIdField='pid';
		unlimit::$reSortKey=true;
		$subs=unlimit::toSub($rs);
		dump($subs);
	}

	/**
	 * 遍历用户的美容档案，将以前JSON_ENCODE的BUG处理掉
	 */
	public function fixUserAnswer(){
		$Useranswer=M("UserVote");
		$list=$Useranswer->where("question=24")->select();
		foreach($list as $listitem){
			$answer=$listitem["answer"];
			$answer=str_replace("u","%u",$answer);
			$answer=str_replace("prod%uct","product",$answer);
			$answer_decode=json_decode($answer,true);
			foreach($answer_decode as $k=>$str){
				$answer_decode[$k]=$this->utf8RawUrlDecode($str);
			}
			$str_encode=json_encode($answer_decode);
			$data["answer"]=$str_encode;
			$Useranswer->where("id=".$listitem["id"])->save($data);

			//echo $this->utf8RawUrlDecode($answer);
		}

	}

	function utf8RawUrlDecode ($source) {
		$decodedStr = "";
		$pos = 0;
		$len = strlen ($source);
		while ($pos < $len) {
			$charAt = substr ($source, $pos, 1);
			if ($charAt == '%') {
				$pos++;
				$charAt = substr ($source, $pos, 1);
				if ($charAt == 'u') {
					// we got a unicode character
					$pos++;
					$unicodeHexVal = substr ($source, $pos, 4);
					$unicode = hexdec ($unicodeHexVal);
					$entity = "&#". $unicode . ';';
					$decodedStr .= utf8_encode ($entity);
					$pos += 4;
				}
				else {
					// we have an escaped ascii character
					$hexVal = substr ($source, $pos, 2);
					$decodedStr .= chr (hexdec ($hexVal));
					$pos += 2;
				}
			} else {
				$decodedStr .= $charAt;
				$pos++;
			}
		}
		return $decodedStr;
	}

}


class unLimit {
	/**
      * 设置子字段
      * @param string
      */
	public static $subIdField='subId';
	/**
      * 设置父字段
      * @param string
      */
	public static $parentIdField='parentId';
	/**
      * 设子字段值的键值
      * @param string
      */
	public static $subField='sub';
	/**
      * 是否重新分配KEY
      * @param boolean
      */
	public static $reSortKey=false;
	/**
      * 处理分级数组并返回
      * @param array $array
      * @return array
      */
	public static function toSub($array){
		if(is_array($array)){
			$proarr=array();
			foreach($array as $row){
				$proarr [$row [self::$parentIdField]] = $row;
				$proarr [$row [self::$subIdField]] [self::$subField] [$row [self::$parentIdField]] = $row;
			}
			$proarr=self::search_sub($proarr,0);
			if(self::$reSortKey){
				$proarr=self::re_sort_key($proarr);
			}

			return $proarr;
		}else
		return $array;
	}
	/**
      * 关键算法函数
      * @param array $array
      * @param string $key
      * @return array
      */
	private static function search_sub(array $array,$key){
		$return = array ();
		$subs = isset ( $array [$key] [self::$subField] ) ? $array [$key] [self::$subField] : array ();
		foreach ( $subs as $k => $v ) {
			$temp=$v;
			$temp[self::$subField]=self::search_sub ( $array, $k );
			$return [$k] = $temp;
		}
		return $return;
	}
	/**
      * 重新排序KEY
      * @param array $array
      * @return array
      */
	private static function re_sort_key($array){
		$array=array_values($array);
		foreach($array as $k=>$v){
			if(is_array($v[self::$subField])&&!empty($v[self::$subField])){
				$array[$k][self::$subField]=self::re_sort_key($v[self::$subField]);
			}
		}
		return $array;
	}
}