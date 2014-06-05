<?php
/**
 * 特权会员控制器
 * @author penglele
 */
class memberAction extends commonAction{
	
	/**
	 * 购买特权会员权限--执行支付请求操作
	 * @author penglele
	 */
	public function gopay(){
		header("Content-type: text/html; charset=utf-8");
			//正常去支付
		$member_mod=D("MemberOrder");
		$pay_bank=$_POST['pay_bank'];
		$type=$_POST["memberid"];
		$id=$member_mod->addOrder($this->userid,$pay_bank,$type);
		$info=$member_mod->getOrderDetail($id);
		if(!$info){
			$this->error("操作失败，请稍后重试~~",U("member/index"));exit;
		}
		
		$ordernmb=$info['ordernmb'];
		$name=$info['name'];
		$pay_bank=$info['pay_bank'];
		$price=$info['price'];
		
		echo "<form name=\"form1\" method=\"post\" id=\"form1\" action=\"".U('memberPay/alipayto')."\" >\r\n";
		echo "<input type=\"hidden\" name=\"ordernmb\" value=\"".$ordernmb."\"/>\r\n";
		echo "<input type=\"hidden\" name=\"total_fee\" value=\"".$price."\"/>\r\n";
		echo "<input type=\"hidden\" name=\"subject\" value=\"".$name."\"/>\r\n";
		echo "<input type=\"hidden\" name=\"body\" value=\"".$name."\"/>\r\n";
		echo "<input type=\"hidden\" name=\"pay_bank\" value=\"".$pay_bank."\"/>\r\n";
		echo "<input type=\"submit\" name=\"submit1\" style=\"display:none\"/>";
		echo "</form>\r\n";
		echo "<script>\r\n";
		echo " if ((navigator.userAgent.indexOf('MSIE') >= 0) && (navigator.userAgent.indexOf('Opera') < 0)){ \r\n";
		echo "	document.form1.submit(); \r\n";
		echo "}else if (navigator.userAgent.indexOf('Firefox') >= 0){ \r\n";
		echo "	document.form1.submit1.click(); \r\n";
		echo "}else if (navigator.userAgent.indexOf('Opera') >= 0){ \r\n";
		echo "	document.form1.submit();";
		echo "}else{";
		echo "	document.form1.submit();";
		echo "}";
		echo "</script>";
		exit();
	}
	
	/**
	 * 特权会员主页
	 * @author penglele
	 */
	public function index(){
		$userid=$this->userid;
		$return['if_first']=0;
		if($userid){
			$member_mod=D("Member");
			$return['userinfo']=$this->userinfo;
			$return['member']=$member_mod->getUserMemberInfo($userid);
			$return['member_info']=$this->get_member_date($return['member'],$userid);
			$ndate=date("Y-m-d");
			//用户是否能购买月度会员
			if($return['member']['state']!=0 && $ndate<"2014-01-01"){
				$first_member=M("MemberOrder")->where("userid=$userid AND state=1 AND ifavalid=1")->order("ordernmb DESC")->find();
				$return['if_first']=$first_member['m_type'];
			}
		}
		$return['title']="开通萝莉盒特权会员-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 用户不同特权状态列表
	 * @param unknown_type $data
	 * @param unknown_type $userid
	 * @author penglele
	 */
	public function get_member_date($data,$userid){
		$arr=array(1,6,12);
		$list=array();
		$mem_order_mod=D("MemberOrder");
		$starttime="2014-01-01 00:00:00";
		$ntime=date("Y-m-d H:i:s");
		$memberlist=D("Member")->getUserMemberDateOfType($userid);
		foreach($memberlist as $key=>$val){
			$info=array();
			$info['title']=$val['name'];
			$info['price']=$val['price'];
			$stime_arr=explode("-",$val['sdate']);
			$etime_arr=explode("-",$val['edate']);
			$info['sdate']=$stime_arr[0]."年".$stime_arr[1]."月".$stime_arr[2]."日";
			$info['edate']=$etime_arr[0]."年".$etime_arr[1]."月".$etime_arr[2]."日";
			$list[$key]=$info;
		}
		return $list;
	}
	
	/**
	 * 购买特权会员-确认订单
	 * @author penglele
	 */
// 	public function confirm(){
// 		$member_mod=D("MemberOrder");
// 		$pay_bank="directPay";
// 		$type=$_POST["memberid"];
// 		$id=$member_mod->addOrder($this->userid,$pay_bank,$type);
// 		$info=$member_mod->getOrderDetail($id);
// 		if(!$info){
// 			$this->error("操作失败，请稍后重试~~",U("member/index"));exit;
// 		}
// 		$this->display();
// 	}
	
	/**
	 * 用户购买特权会员的判断-ajax
	 * @author penglele
	 */
	public function check_if_membere(){
		$type=$_POST['type'];
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$typelist=D("MemberOrder")->getMemberTypeList();
		if(!$type || !array_key_exists($type,$typelist)){
			$this->ajaxReturn(0,"非法操作",0);
		}
		if($type==1){
			$if_member=M("Member")->where("userid=".$userid)->find();
			$ndate=date("Y-m-d");
			if($if_member && $ndate<"2014-01-01"){
				$first_member=M("MemberOrder")->where("userid=$userid AND state=1 AND ifavalid=1")->order("ordernmb DESC")->find();
				$this->ajaxReturn(100,$first_member['m_type'],0);
			}
		}
		$this->ajaxReturn(1,"succss",1);
	}
	
	/**
	 * 支付状态
	 * @author penglele
	 */
	public function result(){
		$order_mod=D("MemberOrder");
		$userid=$this->userid;
		//用户最新的一条订单
		$info=$order_mod->where("userid=".$userid." AND ifavalid=1")->order("ordernmb DESC")->find();
		if(!$info){
			$this->error("您还没有购买过特权会员");exit;
		}
		//特权列表
		$typelist=$order_mod->getMemberTypeList();
		$type=$info['m_type'];
		$info['name']=$typelist[$type]['title'];
		$return['info']=$info;
		$this->assign("return",$return);
		$this->display();
	}
	
	
	
	
	
}
?>