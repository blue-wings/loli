<?php
/**
 * 萝莉礼品卡
 * @author zhenghong
 * 2013-09-22
 */
class CouponAction extends CommonAction{

	public function index(){
		import("@.ORG.Page");
		$where = array();
		if (!empty($_GET['code'])) {
			$where['code'] = trim($this->_get("code"));
		}else{
			$where['adminid']=array('gt',0);
		}
		if($_GET['details']==1){
			$where['adminid']=array('gt',0);
		}else if($_GET['details']==2){
			$where['adminid']=array('eq',0);
		}
		$CouponModel = M('Coupon');
		$count = $CouponModel->where($where)->count();
		$p = new Page($count, 20);
		$list = $CouponModel->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
		$adminModel = M('ThinkAdmin');
		$user_mod=D("Users");
		foreach ($list as $key=>$value){
			$list[$key]['user'] = $adminModel->where("id=".$value['adminid'])->getField('id,account,nickname');
			if($list[$key]["owner_uid"]){
				$owner_userinfo=$user_mod->getUserInfo($list[$key]["owner_uid"],"nickname");
				$list[$key]["owner_nickname"]=$owner_userinfo["nickname"];
			}
		}
		$page = $p->show();
		$this->assign("page", $page);
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 生成优惠劵
	 */
	public function createCoupon(){
		//增加多少个邀请码
		$num = intval($_REQUEST['num']);
		$isAjax = intval($_REQUEST['isajax']);
		if(empty($num)) $num = 1;
		if(empty($isAjax)) $isAjax = 0;
		$couponMode = M("Coupon");
		$codearr = array();
		$code="";
		for($i=1;$i<=$num;$i++){
			$code = rand("5000000","9999999");
			$findcode = $couponMode -> getByCode($code);
			if(!$findcode){
				$codearr[] = $code;
			}else{
				$i--;
			}
		}
		if($isAjax==1) echo json_encode($codearr);
		else return $codearr;
	}

	//单个或批量添加优惠劵
	public function addCoupon(){
		$num = intval($_REQUEST['num']);
		if(empty($num)) return false;
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$addtime = date("Y-m-d H:i:s");
		$starttime = $_REQUEST['starttime1']." 00:00:00";
		$endtime = $_REQUEST['endtime1']." 23:59:59";
		$price = intval($_REQUEST['price1']);
		$data = array();
		$couponMode = M("Coupon");
		$result=""; 
		for ($i=0;$i<$num;$i++){
			$msg="";
			$data= array(
			'code'=>intval($_REQUEST["code".$i]),
			'starttime'=>$starttime,
			'endtime'=>$endtime,
			'status'=>1, //1为未使用
			'price'=>$price,
			"addtime"=>$addtime,
			"adminid"=>$id,
			"owner_uid"=>intval($_REQUEST["owner_uid".$i]), //增加接受优惠券的用户ID
			'remark'=>strval($_REQUEST["remark".$i])
			);
			$ret = $couponMode->add($data);
			if($ret) {
				if($ret){
					//当优惠券被指定分配给某人时，需要在分配后发私信给他
					if($data["owner_uid"]) {
						$userinfo=D("Users")->getUserInfo($data["owner_uid"],"nickname");
						if(!empty($userinfo["nickname"])) {
							$msg=$userinfo["nickname"]."你好，由于".$data["remark"]."，获得 <b>".$price."</b>元优惠券，已经发放到您的账户中，有效期为<b>".$_REQUEST['starttime1']."</b>至<b>".$_REQUEST['endtime1']."</b>，快到<a href='/home/coupon.html' class='WB_info' target='_blank'>【我的优惠】</a>中查看吧~";
							D("Msg")->addMsgFromLolitabox($data["owner_uid"],$msg); //发私信
						}
					}
					$result.=$data["code"]."---优惠券创建成功。<br/>";
				}else{
					$result.=$data["code"]."---优惠券创建失败。<br/>";
				}
			}
		}
		$this->success($result);

	}

	//添加并下载优惠劵
	public function addCouponAndDown()
	{
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$addtime = date("Y-m-d H:i:s");
		$starttime = $_REQUEST['starttime']." 00:00:00";
		$endtime = $_REQUEST['endtime']." 23:59:59";
		$price = intval($_REQUEST['price']);
		$codearr=$this->createCoupon();
		$data = array();
		foreach ($codearr as $key=>$value){
			$data[] = array(
			'code'=>$value,
			'starttime'=>$starttime,
			'endtime'=>$endtime,
			'status'=>1, //1为未使用
			'price'=>$price,
			"addtime"=>$addtime,
			"adminid"=>$id,
			'remark'=>""
			);
		}
		$couponMode = M("Coupon");
		$ret = $couponMode->addAll($data);
		if($ret !== false){
			$outputstr = "金额,开始时间,结束时间,优惠劵\n";
			foreach ($data as $value) {
				$outputstr.=$value['price'].",".$value['starttime'].",".$value['endtime'].",".$value['code'].","."\n";
			}
			outputExcel($addtime.'导出的优惠劵',$outputstr);
			//$this->redirect('/Coupon/index/');
		}else{
			$this->error("操作出错。bug");
		}
	}

	/**
       +----------------------------------------------------------
       * 购盒卡管理
       +----------------------------------------------------------  
       * @access public   
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.26
     */	
	function buyBoxCardManage(){

		$card_mod = M("userBoxcard");
		$user_mod=M("users");
		import("@.ORG.Page");

		if($this->_get('search')){
			$where=$this->CardManageWhere(array_map('filterVar',$_GET));
			
			$count = $card_mod->where($where)->count('id');
			$p = new Page($count,15);

			$list = $card_mod->order('id DESC')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
		}else{
			$count = $card_mod->count('id');
			$p = new Page($count,15);
			$list = $card_mod->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		}

		$page = $p->show();

		foreach ($list AS $key => $value){
			$userinfo=$user_mod->where(array('userid'=>$value['userid']))->find();
			if($userinfo){
				$list["$key"]['nickname']=$userinfo['nickname'];
				$list["$key"]['usermail']=$userinfo['usermail'];
			}
		}

		$priceList = $card_mod->DISTINCT(true)->field('price')->select();

		$this->assign("page",$page);
		$this->assign('priceList',$priceList);
		$this->assign('cardList',$list);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 购盒卡管理查询参数
       +----------------------------------------------------------  
       * @access private  
       +----------------------------------------------------------
       * @param  array  $arguments   页面传递的查询参数  		
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.26
     */	
	private function CardManageWhere($arguments){

		$where = array();

		//订单ID
		if($arguments['orderid']){
			$where['orderid'] = $arguments['orderid'];
		}

		//用户ID
		if($arguments['userid']){
			$where['userid'] = $arguments['userid'];
		}

		//昵称查询用户id
		if($arguments['nickname']){
			$where['userid'] = M("users")->where(array('nickname'=>$arguments['nickname']))->getField('userid');
		}

		//邮箱查询用户ID
		if($arguments['usermail']){
			$where['userid'] = M("users")->where(array('usermail'=>$arguments['usermail']))->getField('userid');
		}

		//购盒卡生成时间
		if($arguments['from'] && $arguments['to']){
			$where["addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}

		//购盒卡的状态
		if($arguments['cardstatus']){
			$where['status']=1;
		}else if($arguments['cardstatus'] === '0'){
			$where['status']=0;
		}

		//购盒卡面值
		if($arguments['price']){
			$where['price']=$arguments['price'];
		}

		return $where;
	}
}
?>