<?php
/**
 * 邀请码管理
 * @author changyuan
 */
class UsersInviteAction  extends CommonAction {

	public function index()
	{
		$UsersInviteModel = M('UsersInvite');
		$where = array();
		$where['owner_uid']=0;
		if ($this->_post('code')){
			$where['code'] = intval($this->_post('code'));
		}
		import("@.ORG.Util.Page");
		$count = $UsersInviteModel->where($where)->count();
		$p = new Page($count, 20);
		$list = $UsersInviteModel->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
		$adminModel = M('ThinkAdmin');
		foreach ($list as $key=>$value){
			$list[$key]['user'] = $adminModel->where("id=".$value['adminid'])->getField('id,account,nickname');
		}
		$page = $p->show();
		$this->assign("page", $page);
		$this->assign("list", $list);
		$this->display();
	}
	/**
	 * 创建用户邀请码操作
	 */
	public function createInviteCode(){
		//增加多少个邀请码
		$num = intval($_REQUEST['num']);
		$isAjax = intval($_REQUEST['isajax']);
		if(empty($num)) $num = 1;
		if(empty($isAjax)) $isAjax = 0;
		$UserInviteMode = M("UsersInvite");
		$codearr = array();
		$code="";
		for($i=1;$i<=$num;$i++){
			$code = rand("100000","999999");
			$findcode = $UserInviteMode -> getByCode($code);
			if(!$findcode){
				$codearr[] = $code;
			}else{
				$i--;
			}
		}
		if($isAjax==1) echo json_encode($codearr);
		else return $codearr;
	}

	//单个或批量添加验证码
	public function addCode()
	{
		$num = intval($_REQUEST['num']);
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$time = date("Y-m-d H:i:s");
		$data = array();
		for ($i=0;$i<$num;$i++){
			$data[] = array(
			'code'=>intval($_REQUEST["code".$i]),
			'status'=>1, //1为未使用
			'remark'=>strval($_REQUEST["remark".$i]),
			"adminid"=>$id,
			"addtime"=>$time
			);
		}
		$usersInviteMode = M("UsersInvite");
		$ret = $usersInviteMode->addAll($data);
		if($ret){
			$this->redirect('/UsersInvite/index/');
		}else{
			$this->error("操作出错。bug");
		}
	}
	
	//添加并下载验证码
	public function addCodeAndDown()
	{
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$time = date("Y-m-d H:i:s");
		$codearr=$this->createInviteCode();
		$data = array();
		foreach ($codearr as $key=>$value){
			$data[] = array(
			'code'=>$value,
			'status'=>1, //1为未使用
			'remark'=>"",
			"adminid"=>$id,
			"addtime"=>$time
			);
		}
		$userInviteMode = M("UsersInvite");
		$ret = $userInviteMode->addAll($data);
		if($ret !== false){
			$outputstr = "";
			foreach ($codearr as $value) {
				$outputstr.=$value."\n";
			}
			outputExcel($time.'导出的邀请码',$outputstr);
			//$this->redirect('/UsersInvite/index/');
		}else{
			$this->error("操作出错。bug");
		}
		
	}
	
}


?>