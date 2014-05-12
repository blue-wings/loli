<?php
/**
 * lolitabox用户管理
 */
class UserAction extends CommonAction{

	/**
      +----------------------------------------------------------
      * 所有会员列表
      +----------------------------------------------------------  
      * @access public   
      +----------------------------------------------------------
      * @param  string aid   	  查询地区信息  			
      * @param  string sid  	  查询需要编辑的某条记录   
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.1.18
     */
	public function userList(){
		$User=M("Users");
		import("@.ORG.Page");
		$where=$this->userListSelectWhere(array_map('filterVar',$_GET));  //查询条件
		////批量加入邮件发送列表
		if($this->_get('sendmail')){
			$this->addBatchSendmail($_GET['remark'][0],$this->_get('mailmoban'),$where);
		}
		//批量加入短信发送列表
		if($this->_get('sendmess')){
			$this->addBatchSendSMS($_GET['remark'][1],$this->_get('messmoban'),$where);
		}
		if($this->_get('exportemail')){
			$condi = array(
			'state'=>2,
			'userid'=>array('exp',"NOT IN (SELECT userid FROM `user_blacklist`)"),
			'usermail'=>array('exp',"NOT IN (SELECT email FROM  `email_blacklist`)")
			);
			$emaillist = $User->where($condi)->field("DISTINCT (`usermail`)")->order("usermail")->select();
			$this->exportActiveEmail($emaillist);
		}

		if($this->_get('do')=='createuser'){
			$this->createUser();
		}

		//排序
		if($this->_get('order')){
			if($this->_get('by') ==1){
				$order = $this->_get('order').' DESC';
			}else {
				$order = $this->_get('order').' ASC';
			}
		}else{
			$order = 'userid DESC';
		}

		$count=$User->where($where)->count();

		$p = new Page($count,15);

		if($_GET['ac'] == 'exportAllWhere'){
			$list=$User->where($where)->order($order)->select();
		}else{
			$list=$User->where($where)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
		}

		$user_telphone_mod=M("UserTelphone");
		
		foreach ($list as $key => $val){
			$list[$key]['invitenum'] = $User->where(array('invite_uid'=>$val['userid']))->count('userid');
			$user_tel_valid=$user_telphone_mod->field('tel,addtime')->where(array('userid'=>$val['userid'],'if_check'=>1))->find();
			$list[$key]['telphone']=$user_tel_valid["tel"];
		}

		$list=$this->addAddressAndOpentypeToList($list);  //添加用户地址和第三方类型
		//导出符合条件的数据
		if($_GET['ac'] == 'exportAllWhere'){
			$this->exportAllWhereData($list);
		}

		$artInfo=$this->mailAndMSGInfoToList();			  //添加邮件和短信模版

		$page = $p->show();
		$prolist=M('promotion')->field('code,name')->select(); //用户推广管理列表

		$this->assign('plist',$prolist);
		$this->assign("mailmoban",$artInfo['mail']);
		$this->assign("messmoban",$artInfo['msg']);
		$this->assign("userlist",$list);
		$this->assign("page",$page);
		$this->display("user");
	}


	//群发消息
	function sendAllPersonmsg(){
		if($this->_post('ac')){
			if($this->_post('ac')=='sendPersonalMess'){
				if($this->_post('content')){
					$content = R('Article/remoteimg',array($_POST['content']));
					D("Msg")->datAddMsg($where,$content);
					$this->success("批量发送成功",U('UserAccessTrace/UserPrivateLetter'));
				}else{
					$this->error("内容不能为空!");
				}
			}else{
				$this->error("参数错误!");
			}
		}else if($_POST['id']){

			$content = $content = R('Article/remoteimg',array($_POST['content']));

			if(false !== D("MsgData")->where(array('id'=>$this->_post('id')))->setField('content',$content)){
				$this->success("修改成功",U('UserAccessTrace/UserPrivateLetter'));
			}else{
				$this->error("修改失败!");
			}
		}else{
			if($this->_get('editid')){
				$msgdata = D("MsgData")->where(array('id'=>$this->_get('editid')))->find();


				$this->assign('data',$msgdata);
			}else{

				$shortlist =  D("Msg")->where(array('msg.to_uid'=>0))->join("msg_data AS dt ON msg.dataid = dt.id")->field("msg.id, msg.from_uid, msg.to_uid, dt.id AS dit, dt.content")->order("msg.id DESC")->limit(5)->select();

				$this->assign("slist",$shortlist);
			}
			$this->display();
		}
	}

	/**
      +----------------------------------------------------------
      * 导出符合当前查询条件的所有记录
      +----------------------------------------------------------  
      * @access private				用户列表按钮过来 
      +----------------------------------------------------------
      * @param  Array   list  	    用户详细资料 			
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.1.18
     */	
	private function exportAllWhereData($list){

		$str="用户ID,邮箱,联系人,手机,邀请人,邀请数,地址信息,手机验证,邮件激活,订单数,关注数,粉丝数,积分,经验值,分享数,达人级别,注册时间\n";

		foreach ($list as $key=>$value){

			$str.=$value['userid'].','.$value['usermail'].','.$value['personalInfo']['linkman'].','.$value['personalInfo']['telphone'].','.$value['invite_uid'].','.$value['invitenum'].',';

			if($value['personalInfo']['province'] == $value['personalInfo']['city']){
				$str.=$value['personalInfo']['province'];
			}else{
				$str.=$value['personalInfo']['province'].$value['personalInfo']['city'];
			}

			$str.=$value['personalInfo']['district'].$value['personalInfo']['address'].",";

			$str.=$value['tel_status'] ==1?"已验证":"未验证";
			$str.=",";
			$str.=$value['state'] ==2?"已验证":"未验证";
			$str.=",".$value['order_num'].",".$value['follow_num'].",".$value['fans_num'].",".$value['score'].",".$value['experience'].",";
			$str.=$value['blog_num'].",";
			$str.=$value['if_super'] != 0?'达人':'普通';
			$str.=",".$value['addtime']."\n";
		}

		outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "用户详细资料" ), $str );
		exit();
	}


	/**
     * 创建帐号
     */
	private function createUser(){
		$file_url=$this->photoStorage("nickname_txt","/data");
		if(empty($file_url)){
			return false;
		}else{
			$file_url=".".$file_url;
		}

		$fp = @fopen($file_url, 'r');
		$user_mod = M("Users");
		$follow_mod =  D("Follow");
		$user_profile_model=M("UserProfile");

		$max_user_mail = $user_mod->field("usermail") ->where("usermail like 'pingce%lolitabox.com'")->order("usermail DESC")->find();
		$a = str_replace("pingce","",$max_user_mail['usermail']);
		$a =  str_replace("@lolitabox.com", "", $a);    //获取内部帐号最大数
		$success_num =0;
		while($str=trim(fgets($fp))){
			$data = array();
			$data1 = array();
			if($user_mod->getByNickname($str)){
				$str = $this -> autoLolitaNickname($str);
			}
			$data['nickname'] = $str;
			$data['usermail'] = 'pingce'.($a+1)."@lolitabox.com";
			$data['password'] = md5("abc123");
			$data["addtime"]=date("Y-m-d H:i:s");
			$data["state"]=0;
			$new_id = $user_mod ->add($data);
			if($new_id){
				$follow_mod ->addFollow($new_id,C('LOLITABOX_ID'),1);   //关注官网

				$data1 = array("userid"=>$new_id);
				$user_profile_model->add($data1);           //user_profile数据
				$a++;
				$success_num ++;
			}
		}
		fclose($fp);
		unlink($file_url);
		echo "<script>parent.create_user_handle($success_num);</script>";
		die;
	}



	//自动创建LOLITABOX用户昵称【适用于第三方登录】
	private function autoLolitaNickname($nickname){
		if(D("Users")->searchUserinfoByField("nickname",$nickname)) {
			return $this->autoLolitaNickname($nickname."_".rand(100,999));
		}
		else {
			return $nickname;
		}
	}

	private function exportActiveEmail($list){

        $array_disable_domain=array("chuaizi.com","600mail.com","yy369.com","chuaizai.com",'wiseie.net','wow8.net','qianbao666.net','bccto.me');
        
		$str="邮箱\n";

        foreach ($list as $value){


            $mail = substr($value['usermail'],strpos($value['usermail'],'@')+1);
            
            if(in_array($mail,$array_disable_domain)){
               continue;
            }else{
               $pattern = "#\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*#";

               preg_match($pattern,$value['usermail'],$arr);    
            
               if($arr){
	        		if(stristr($value['usermail'],'qq')){
			        	$have[] = $value['usermail'];
		            }else{
				        $nullqq[] = $value['usermail'];
                     }
               }      
            }
		}
        
        
		$arr = array_merge($have,$nullqq);

		foreach ($arr as $vl){
			$str.=$vl."\n";
		}

		outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "所有激活用户邮箱地址" ), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 整理所有用户列表查询参数
       +----------------------------------------------------------  
       * @access public   $arguments   模版传递的POST OR GET参数
       +----------------------------------------------------------
       * @param  string aid   	  查询地区信息  			
       * @param  string sid  	  查询需要编辑的某条记录   
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function   userListSelectWhere($arguments){

		$address_mod=M("userAddress");
		$where=array();

		//用户注册邮箱
		if($arguments['email']){
			$where["usermail"]=array('like',"%".$arguments['email']."%");
		}

		//用户ID：
		if($arguments['userid']){
			$where["userid"]=$arguments['userid'];
		}

		//联系人
		if($arguments['linkman']){
			$where["userid"]=$address_mod->where("linkman LIKE '%".$arguments['linkman']."%'")->getField('userid');
		}

		//昵称
		if($arguments['nickname']){
			$where["nickname"]=array('like',"%".$arguments['nickname']."%");
		}

		//手机号
		if($arguments['telphone']){
			$where["userid"]=$address_mod->where("telphone = ".$arguments['telphone'])->getField('userid');
		}

		//订单数
		if($arguments['order_num'] || $arguments['order_num'] === '0'){       //订单数有可能为0
			$where["order_num"]=array('eq',$arguments['order_num']);
		}

		//地址
		if($arguments['flag'] && $arguments['province']) {

			$flag=$arguments['flag'];
			$province=$arguments['province'];

			if($flag==1){
				$where["userid"]=array('exp',"IN(SELECT userid FROM `user_profile` WHERE province='{$province}')");
			}else{
				$where["userid"]=array('exp',"NOT IN(SELECT userid FROM `user_profile` WHERE province='{$province}')");
			}
		}

		//用户级别
		if($arguments['stickies']){
			if($arguments['stickies']==1){
				$where["if_super"]=array('eq',0);
			}else if($arguments['stickies']==2){
				$where["if_super"]=array('eq',1);
			}
		}

		//用户属性
		if($arguments['userattr']){
			$where["userid"]=array('exp',"IN(SELECT uid FROM `user_openid` WHERE uid <>0 AND type='".$arguments['userattr']."')");
		}

		//内部用户 OR 真实用户
		if($arguments['usertype']){
			if($arguments['usertype']==100){
				$where['_string'] = "usermail LIKE 'nbceshi%lolitabox.com' OR usermail LIKE 'pingce%lolitabox.com'";
			}if($arguments['usertype']==10){
				$where['_string'] = "usermail NOT LIKE 'nbceshi%lolitabox.com' OR usermail NOT LIKE 'pingce%lolitabox.com'";
			}
		}

		//是否完善地址
		if($arguments['perfect']==10){				//不完善
			$where['userid']=array('exp',"NOT IN (SELECT userid FROM user_address)");
		}else if($arguments['perfect']==100){		//完善
			$where['userid']=array('exp',"IN (SELECT userid FROM user_address)");
		}

		//注册时间
		if($arguments['from'] && $arguments['to']){
			$where["addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}


		if($arguments['resour']){
			if($arguments['param']){
				$where['userid']=array('exp',"IN (SELECT userid FROM user_profile WHERE fromid='{$arguments['resour']}' AND frominfo='{$arguments['param']}')");
			}else{
				$where['userid']=array('exp',"IN (SELECT userid FROM user_profile WHERE fromid='{$arguments['resour']}')");
			}
		}

		//终端类型
		if(isset($arguments['is_mobile']) && $arguments['is_mobile']!=''){
			$where ['is_mobile'] =  $arguments['is_mobile'];
		}

		if(isset($arguments['emailstatus'])){
			if($arguments['emailstatus'] == 2){
				$where['state']=2;
			}else{
				$where['state']= array('neq','2');
			}
		}

		//邀请人查询
		if($arguments['invite']){
			$where['invite_uid'] = $arguments['invite'];
		}

		if($this->_get('telchk') === '0'){
			$where['tel_status']	 = 0;
		}else if($this->_get('telchk') ==1){
			$where['tel_status']	 = 1;
		}

		return $where;
	}

	/**
       +----------------------------------------------------------
       * 整理所有用户列表查询参数
       +----------------------------------------------------------  
       * @access public   $arguments   模版传递的POST OR GET参数
       +----------------------------------------------------------
       * @param  string  $byorder        		 排序类型
       * @param  return  $order_array Array  	 返回排序类型和排序值
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private  function sortType ($byorder){
		$order_array=array();
		switch ($byorder){//排序判断条件
			case byexperience:
				$order_array['sortType']="experience DESC";
				$order_array['sortValue']="byexperience";
				break;
			case byscore:
				$order_array['sortType']="score DESC";
				$order_array['sortValue']="byscore";
				break;
			case evaluate:
				$order_array['sortType']="evaluate_num DESC";
				$order_array['sortValue']="evaluate";
				break;
			case blog:
				$order_array['sortType']="blog_num DESC";
				$order_array['sortValue']="blog";
				break;
			default:
				$order_array['sortType']="userid DESC";
				$order_array['sortValue']="byuserid";
		}
		return  $order_array;
	}


	/**
       +----------------------------------------------------------
       * 添加邮件模版和短信模版到用户列表
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param  return  Array   邮件,短信模版列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function mailAndMSGInfoToList(){
		$art_mod=M("article");
		import("@.ORG.Util.String");

		$mailNum=C('MAIL_CATEID');
		$msgNum=C('MSG_CATEID');
		$returnArray=array();
		$where['_string']='cate_id="'.$mailNum.'" OR  cate_id="'.$msgNum.'"';

		$mobanlist=$art_mod->field('id,title,cate_id')->where($where)->order("add_time desc")->select();

		foreach ($mobanlist AS $key => $value){
			$value['title']=String::msubstr($mobanlist[$key]['title'],0,15);
			if($value['cate_id']==$mailNum){
				$returnArray['mail'][]=$value;
			}else{
				$returnArray['msg'][]=$value;
			}
		}
		return $returnArray;
	}

	/**
       +----------------------------------------------------------
       * 添加用户地址和第三方类型
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param  Array   $list	 			   用户列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function addAddressAndOpentypeToList($list){

		foreach ($list as $key=>$value){
			$user_type=array();

			$exs=M("userOpenid")->where(array('uid'=>$value['userid']))->field('type')->select();

			if(!empty($exs)){
				foreach ($exs as $k=>$v){
					$user_type[]=$v['type'];
				}
				$list[$key]['usertype']=implode('<br/>',$user_type);
			}

			$exp =array(
				'type'=>'follow_uid',
				'status'=>1,
				'userid'=>$value['userid']
			);

			//关注数
			$list[$key]['attention_num'] = M("UserBehaviourRelation")->where ( $exp )->count ();

			//个人信息:包括联系人,电话,地址,邮编等等
			$list["$key"]['personalInfo'] = M("userAddress")->where(array('userid'=>$value['userid'],'if_del'=>0))->find();
		}
		return $list;
	}

	/**
       +----------------------------------------------------------
       * 批量加入邮件发送列表
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param  string  $mailMasterplateID    邮件模版ID
       * @param  Array   $where	 			   userlist传递的查询条件
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function addBatchSendmail($remark,$mailMasterplateID,$where){
		if($mailMasterplateID && $remark){
			if($this->addtask($mailMasterplateID, $where, 1,$remark)){
				$this->success("己加入到推广任务列表中");die;
			}else{
				$this->error("加入推广任务列表失败");die;
			}
		}else{
			$this->error( "请选择模板和填写条件描述" );
		}
	}


	/**
     * 加入到发送任务列表中
     * @param int $artid
     * @param int $where
     * @param int $type
     * @author litingting 
     */
	public function addtask($artid,$where,$type,$remark=""){
		if(empty($artid) || empty($type))
		{
			return false;
		}
		$send_mod=M("SendTask");
		$data['artid']=$artid;
		$data['filtersql']=json_encode($where);
		$data['type']=$type;
		$data['remark']=$remark;
		$data['addtime']=time();
		return $send_mod->add($data);
	}


	/**
       +----------------------------------------------------------
       * 批量加入短信发送列表
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param  string  $mailMasterplateID    邮件模版ID
       * @param  Array   $where	 			   userlist传递的查询条件
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function addBatchSendSMS($remark,$SMSMasterplateID,$where){
		if($remark && $SMSMasterplateID){
			if($this->addtask($SMSMasterplateID, $where, 2,$remark)){
				$this->success("己加入到推广任务列表中");die;
			}else{
				$this->error("加入推广任务列表失败");die;
			}
		}else{
			$this->error("请选择模板和填写条件描述");
		}
	}

	/**
       +----------------------------------------------------------
       * 站内群发消息
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param  Array   $list	 	 群发消息的用户列表
       * @param  Array   $info	 	 群发消息的标题和内容
       * @param  Array   $flag	 	 $where有值为1,空为0
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.18
     */
	private function addBatchSendPersonalMess($list,$info,$flag){
		if($list){
			$Message_mod=M("UserMessage");
			$data['from_uid']=C('LOLITABOX_ID');
			$data['title']=$info['title'];
			$data['content']=$info['content'];
			$data['addtime']=time();
			if($flag==0){
				$data['to_uid']=0;
				if($Message_mod->add($data))
				$this->success("操作成功");
				else
				$this->error("操作失败");
				exit;
			}else{
				for($i=0;$i<count($list);$i++){
					$data['from_uid']=C('LOLITABOX_ID');
					$data['title']=$info['title'];
					$data['content']=$info['content'];
					$data['addtime']=time();
					$data['to_uid']=$list[$i]['userid'];
					$Message_mod->add($data);
				}
				$this->success("操作成功");  exit;
			}
		}else{
			$this->error('群发列表为空,请检查!');
		}
	}

	/**
       +----------------------------------------------------------
       * 所有会员列表查询某个用户美丽档案填写情况
       +----------------------------------------------------------  
       * @access  publib 
       +----------------------------------------------------------
       * @param  NULL    NULL
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.21
     */	
	public function useranswer(){
		$usermodel=M('userVote');

		$useranswer=$usermodel->where(array('userid'=>filterVar($this->_get('userid'))))->field('userid',true)->select();
		//判断是否存在第25题
		if(count($useranswer)==24){
			$useranswer[23]['answer']=json_decode($useranswer[23]['answer'],true);
		}

		$userVoteInfo=$this->userVotegAther();

		$this->assign("useranswer",$useranswer);
		$this->assign('userVoteInfo',$userVoteInfo);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 统计所有用户美丽档案填写详情
       +----------------------------------------------------------  
       * @access  private 
       +----------------------------------------------------------
       * @param  NULL    NULL
       * @return $returnArray	 所有用户的美丽档案信息列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.21
     */		
	private function userVotegAther(){

		$usermodel=M('userVote');
		$user_sex=M('user_profile');
		$returnArray=array();

		//查询填写美容档案的用户数
		$returnArray['userCount']=$usermodel->count('distinct userid');


		//查询填写美容档案100%用户数
		$returnArray['userAll']=$usermodel->where(array('question'=>'25'))->count('distinct userid');


		//美丽档案是女的用户数量
		$returnArray[0][0]=$user_sex->field('sex')->where('sex=0')->count();
		//美丽档案是男的用户数量
		$returnArray[0][1]=$user_sex->field('sex')->where('sex=1')->count();
		//美丽档案是中的用户数量
		$returnArray[0][2]=$user_sex->field('sex')->where('sex=2')->count();


		//美丽档案中关于肤质问题的用户数
		//中性
		$returnArray[1][0]=$usermodel->where(array('question'=>'4','answer'=>'中性'))->count('distinct userid');

		//油性
		$returnArray[1][1]=$usermodel->where(array('question'=>'4','answer'=>'油性'))->count('distinct userid');

		//干性
		$returnArray[1][2]=$usermodel->where(array('question'=>'4','answer'=>'干性'))->count('distinct userid');

		//混合性
		$returnArray[1][3]=$usermodel->where(array('question'=>'4','answer'=>'混合性'))->count('distinct userid');

		//敏感性
		$returnArray[1][4]=$usermodel->where(array('question'=>'4','answer'=>'敏感性'))->count('distinct userid');


		//美丽档案中关于发质用户数的统计
		//受损发质
		$returnArray[2][0]=$usermodel->where(array('question'=>'9','answer'=>'受损发质'))->count('distinct userid');

		//干枯发质
		$returnArray[2][1]=$usermodel->where(array('question'=>'9','answer'=>'干枯发质'))->count('distinct userid');

		//油性发质
		$returnArray[2][2]=$usermodel->where(array('question'=>'9','answer'=>'油性发质'))->count('distinct userid');

		//中性发质
		$returnArray[2][3]=$usermodel->where(array('question'=>'9','answer'=>'中性发质'))->count('distinct userid');


		//美丽档案中每月购置化妆品费用的各个答案的数量的统计
		//200以下
		$returnArray[3][0]=$usermodel->where(array('question'=>'15','answer'=>'200以下'))->count('distinct userid');

		//200-500
		$returnArray[3][1]=$usermodel->where(array('question'=>'15','answer'=>'200-500'))->count('distinct userid');

		//500-1000
		$returnArray[3][2]=$usermodel->where(array('question'=>'15','answer'=>'500-1000'))->count('distinct userid');

		//1000以上
		$returnArray[3][3]=$usermodel->where(array('question'=>'15','answer'=>'1000以上'))->count('distinct userid');


		//美丽档案中希望试用哪些产品的选择数量
		// 护肤品
		$returnArray[4][0]=$usermodel->where(array('question'=>'20','answer'=>'护肤品'))->count('distinct userid');

		//彩妆
		$returnArray[4][1]=$usermodel->where(array('question'=>'20','answer'=>'彩妆'))->count('distinct userid');

		//指甲油
		$returnArray[4][2]=$usermodel->where(array('question'=>'20','answer'=>'指甲油'))->count('distinct userid');

		//护发造型
		$returnArray[4][3]=$usermodel->where(array('question'=>'20','answer'=>'护发造型'))->count('distinct userid');

		//香水和香薰
		$returnArray[4][4]=$usermodel->where(array('question'=>'20','answer'=>'香水和香薰'))->count('distinct userid');

		return $returnArray;
	}

	/**
     +----------------------------------------------------------
     * 改变用户邮箱状态   AJAX
     +----------------------------------------------------------
     * @param string userid     用户ID
     * @param string status     用户当前邮箱的状态值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @author zhaoxiang 2013.1.21
     */
	public function changeUserEmailStatus(){

		if($this->_post('userid')){
			$user_mod=M("Users");
			$where['userid']=$this->_post('userid');

			if($this->_post('status')==2){
				$status=0;
			}else{
				$status=2;
			}
			$result=$user_mod->where($where)->setField('state',$status);

			if($result){
				if($status == 2 ){
					//给当前激活用户加积分
					D("UserCreditStat") ->optCreditSet($this->_post('userid'),'user_verify_email');
				}
				$this->ajaxReturn(1,'成功!',1);
			}else{
				$this->ajaxReturn(0,$status,0);
			}

		}
	}

	/**
     +----------------------------------------------------------
     * userlist页面表单下部的加入短信发送列表
     +----------------------------------------------------------
     * @param string ac			选择模版+加入任务列表
     * @param string ids    	短信
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @author zhaoxiang 2013.1.21  源:李婷婷
     */	
	public function sendMess(){
		$ac=filterVar($_GET['ac']);
		$ids=filterVar($_GET['ids']);
		if($ac=='open'){
			$art=M("article");
			$where['cate_id']=C('MSG_CATEID');
			$list=$art->field('id,title,info')->where($where)->order("add_time desc")->select();
			$this->assign("ids",$ids);
			$this->assign("moban",$list);
			$this->display("Public:selectTpl");
		}
		if($ac=='send'){
			$ids=$_POST['ids'];
			$art_id=$_POST['article_select'];
			$ids=substr($ids,0,strlen($ids)-1);
			$where['Users.userid']=array('in',$ids);
			if(tasklist($art_id, $where, 2))
			echo "己经插入到任务列表中";
			else
			echo "加入到任务列表失败";
		}
	}

	/**
     +----------------------------------------------------------
     * userlist页面表单下部的加入邮件发送列表
     +----------------------------------------------------------
     * @param string ac			选择模版+加入任务列表
     * @param string ids    	邮件
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @author zhaoxiang 2013.1.21  源:李婷婷
     */	
	public function sendMail(){
		$ac=$_GET['ac'];
		$ids=$_REQUEST['ids'];
		if(! $ids)     die( "您没有选中任何记录,请选中后再进行操作") ;
		if($ac=='open')
		{
			$art=M("article");
			$where['cate_id']=C("MAIL_CATEID");
			$list=$art->field('id,title,info')->where($where)->order("add_time desc")->select();
			$this->assign("ids",$ids);
			$this->assign("moban",$list);
			$this->display("Public:selectTpl");
			exit;
		}
		if($ac=='send'){
			$article_id=$_REQUEST["article_select"];
			if(!$article_id) exit("没有选择模板！");
			$ids=$_POST['ids'];
			$ids=substr($ids,0,strlen($ids)-1);
			$where['Users.userid']=array('in',$ids);
			if(tasklist($article_id, $where, 1))
			echo "己经插入到任务列表中";
			else
			echo "加入到任务列表失败";
		}
	}

	/**
     +----------------------------------------------------------
     * 空方法:对应userlist 每月新用户和每天新用户
     +----------------------------------------------------------
     * @param string  name  空方法的模块名称 
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     * @update zhaoxiang 2013.4.3
     */	
	public function _empty($name){

		if($name=="monthUserList"){

			$this->redirect('User/userList', array('from' =>date("Y-m-01",time()),'to'=>date("Y-m-31",time())));

		}elseif($name=="todayUserList"){

			$this->redirect('User/userList', array('from' =>date("Y-m-d",time()),'to'=>date("Y-m-d",time())));
		}
	}


	/**
     +----------------------------------------------------------
     * userlist切换达人状态    0为普通,1为达人
     +----------------------------------------------------------
     * @param string userid  用户ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     */	
	public function stickies(){
		if($_POST['userid']){
			$user_mod=M('users');
			$where['userid']=filterVar($_POST['userid']);
			$spuer=$user_mod->where($where)->getField('if_super');

			$result=$user_mod->where($where)->setField('if_super',!(int)$spuer);

			if($result){
				$this->ajaxReturn($spuer,$result,1);
			}else{
				$this->ajaxReturn($spuer,$result,0);
			}
		}
	}

	/**
     +----------------------------------------------------------
     * 粉丝列表 和 关注列表
     +----------------------------------------------------------
     * @param string userid  用户ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     */
	function fansList(){
		$relation_mod=M("UserBehaviourRelation");
		$user_mod=M("Users");

		if($this->_get('userid')){
			$where['type']='follow_uid';

			$ids='';
			if($this->_get('type')==1){ //被关注列表

				$where['whoid']=filterVar($this->_get('userid'));

				$list=$relation_mod->field('userid')->where($where)->select();

				if($list){
					for($i=0;$i<count($list);$i++){
						$ids.=$list[$i]['userid'].",";
					}
					$ids=substr($ids,0,strlen($ids)-1);
				}
			}else{   //关注列表
				$where['userid']=filterVar($this->_get('userid'));
				$list=$relation_mod->field('whoid')->where($where)->select();
				if($list){
					for($i=0;$i<count($list);$i++){
						$ids.=$list[$i]['whoid'].",";
					}
					$ids=substr($ids,0,strlen($ids)-1);
				}
			}
			$map['userid']=array('in', $ids);
		}

		$count=$user_mod->where($map)->count();

		import("@.ORG.Page");
		$p = new Page($count,15);
		$opt['userid']=array('in', $ids);
		$userlist=$user_mod->where($opt)->limit($p->firstRow . ',' . $p->listRows)->order("fans_num desc")->select();

		//关注数
		$exp['type']='follow_uid';
		$exp['status']=1;
		for($i=0;$i<count($userlist);$i++){
			$exp['userid']=$userlist[$i]['userid'];
			$userlist[$i]['attention_num']=$relation_mod->where($exp)->count();
		}

		$page = $p->show();
		$userinfo=$user_mod->getByUserid($userid);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->assign("userinfo",$userinfo);
		$this->display();
	}




	/**
     +----------------------------------------------------------
     * 将添加粉丝插入数据库及相关数据库操作
     +----------------------------------------------------------
     * @param string add_userid    要添加粉丝的用户
     * @param string add_fansnum   要添加的数量
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     */
	private function insert_addfans($add_userid,$add_fansnum){
		if (! $add_fansnum) {
			$this->error ( "添加粉丝数量不能为0" );die();
		}
		$user_behaviour_relation_mod=M("UserBehaviourRelation");
		$inner_users = M ( "Users" )->cache(true)->field ( "userid" )->where ( array ('usermail' => array ('like','%lolitabox.com'),"userid" =>array("neq",$add_userid)) )->select ();
		$data ['whoid'] = $add_userid;
		$data ['type'] = 'follow_uid';
		$data ['status'] = 0;
		$data ['addtime'] = time ();

		$where ['whoid'] = $add_userid;
		$where ['type'] = 'follow_uid';
		$total_insert = 0;

		for($i = 0; $i < count ( $inner_users ); $i ++){
			$where ['userid'] = $inner_users [$i] ['userid'];
			if ($add_fansnum <= $total_insert)break;
			if (! $user_behaviour_relation_mod->where ( $where )->select ()){
				$data ['userid'] = $where ['userid'];
				$user_behaviour_relation_mod->add ( $data );
				$total_insert ++;
			}
		}

		//更新users表中的粉丝数
		unset($where['userid']);
		$count=$user_behaviour_relation_mod->where($where)->count();
		M("Users")->where("userid=".$add_userid)->setField('fans_num',$count);
		return $total_insert;
	}

	/**
     +----------------------------------------------------------
     * 达人申请信息列表
     +----------------------------------------------------------
     * @param  NULL NULL
     +----------------------------------------------------------
     * @return list  查询列表
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     */
	public function daConfirm(){
		$apply_mod=D("DarenView");
		import("ORG.Util.Page");

		$where=$this->daWhereParam(array_map('filterVar',$_GET));

		$count=$apply_mod->where($where)->count();
		$p=new Page($count,15);
		$list=$apply_mod->where($where)->order('apply_datetime DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$page=$p->show();
		$this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
	}

	/**
     +----------------------------------------------------------
     * 达人申请信息列表查询参数
     +----------------------------------------------------------
     * @param string userid  查询用户ID
     * @param string eamil   查询用户邮箱
     * @param string nickname查询用户昵称 
     * @param string form,to 查询达人申请时间
     * @param string status  审核状态
     +----------------------------------------------------------
     * @return list  查询列表
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21  
     */
	private function daWhereParam($arguments){
		$where=array();
		if($arguments['email']){
			$where['Users.usermail']=$arguments['email'];
		}

		if($arguments['userid']){
			$where['UserDarenApply.userid']=$arguments['userid'];
		}

		if($arguments['nickname']){
			$where['Users.nickname']=$arguments['nickname'];
		}

		if($arguments['from'] && $arguments['to']){
			$where['UserDarenApply.apply_datetime']=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where['UserDarenApply.apply_datetime']=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where['UserDarenApply.apply_datetime']=array('elt',$arguments['to'].' 23:59:59');
		}

		if($arguments['status']==='0'){
			$where['UserDarenApply.status']=0;
		}else if($arguments['status']=='1'){
			$where['UserDarenApply.status']=11;
		}else if($arguments['status']=='2'){
			$where['UserDarenApply.status']=10;
		}
		return $where;
	}
	/**
     +----------------------------------------------------------
     * 删除达人申请信息
     +----------------------------------------------------------
     * @param string userid  用户ID
     +----------------------------------------------------------
     * @return Ajax  返回删除结果
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21  
     */	

	public function dele_darenApply(){
		if($this->_post("userid")){
			$apply_mod=M("userDarenApply");
			$user_mod=M("Users");

			$where['userid']=filterVar($this->_post("userid"));

			$result=$apply_mod->where($where)->delete();

			if($result){
				$rel=$user_mod->where($where)->setField('if_super',0);
				if(false === $rel){
					$this->ajaxReturn(0,'users更新失败!',0);
				}else{
					$this->ajaxReturn(1,'删除成功!',1);
				}
			}else{
				$this->ajaxReturn(0,'删除失败',0);
			}
		}
	}

	/**
     +----------------------------------------------------------
     * 更新达人申请级别
     +----------------------------------------------------------
     * @param string userid  用户ID
     * @param string super   达人级别   0为未审核,11为已通过,10为已拒绝
     +----------------------------------------------------------
     * @return Ajax  返回更新结果
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21  
     */	
	public function changeSuperLevel(){
		if($this->_post("super")){

			$user_mod=M("users");
			$apply=M("userDarenApply");

			$where['userid']=filterVar($this->_post("userid"));

			$super=$this->_post("super");
			$res=$apply->where($where)->getField("status");
			$sup=$user_mod->where($where)->getField("if_super");

			if($res != $super){
				$data['status']=$super;
				$result=$apply->where($where)->save($data);
				if($super=='10'){
					$da['if_super']=0;
				}else{
					$da['if_super']=1;
				}
				if($da['if_super'] !=$sup){
					$chk=$user_mod->where($where)->save($da);
					if($da['if_super']==1){
						$this->sendM($where['userid'],11);
					}else{
						$this->sendM($where['userid'],10);
					}
				}
				if($result){
					$this->ajaxReturn(1,1,1);
				}else{
					$this->ajaxReturn(0,0,0);
				}
			}
		}
	}

	/**
     +----------------------------------------------------------
     * 批量更新达人申请级别
     +----------------------------------------------------------
     * @param string way   through:通过   refused:拒绝
     * @param string if_super   达人级别  0为未审核,1为已通过,2为已拒绝
     +----------------------------------------------------------
     * @return $res  返回批量更新结果
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.22 
     */	
	public function changeCheckbox(){
		if($this->_post("listcheckbox")){

			$user_mod=M("users");
			$apply=M("userDarenApply");

			$res=array();
			if($this->_post("way")=='through'){
				$data['if_super']=1;
				$app_data['status']=11;
			}else if($this->_post("way")=='refused'){
				$data['if_super']=0;
				$app_data['status']=10;
			}

			foreach($this->_post("listcheckbox") as $key=>$value){
				$where['userid']=$value;
				$user_da=$user_mod->where($where)->getField('if_super');
				$status=$apply->where($where)->getField('status');
				if($user_da != $data['if_super']){
					$result=$user_mod->where($where)->save($data);
					if($data['if_super']==1){
						$this->sendM($value,11);
					}else{
						$this->sendM($value,10);
					}
				}
				if($status != $app_data['status']){
					$chk=$apply->where($where)->save($app_data);
				}

				if($status || $result){
					$res[]=$value;
				}
			}

			$this->success('修改成功!ID:'.implode(',',$res));
		}else{
			$this->error("请选择,再提交!");
		}
	}

	/**
     +----------------------------------------------------------
     * 判断用户是否通过达人申请  向用户发送站内信
     +----------------------------------------------------------
     * @param string   to  		通过的用户名
     * @param string   status   11为通过
     +----------------------------------------------------------
     * @return $res  返回批量更新结果
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.22 
     */	
	public function sendM($to,$status){
		$Message_mod=M("UserMessage");
		$data['from_uid']=C('LOLITABOX_ID');
		$data['to_uid']=$to;
		if($status==11){
			$data['title']='恭喜您，您的达人申请审核通过啦!';
			$data['content']='恭喜您，成为LOLITABOX认证达人！期待分享给Lolitagirls更多精品日志及评测。您将享有我们的重点曝光、线上线下活动及品牌合作活动优先参与等特权！一切尽在LOLITABOX！';
		}else{
			$data['title']='很遗憾，您的达人申请未被审核通过!';
			$data['content']='很抱歉，您暂时不能成为LOLITABOX认证达人，请更加精心耕耘您的个人空间吧！日志+精华评测不少于10条以上且须保证图文精美哦~一起努力加油吧！';
		}
		$data['addtime']=time();
		$result=$Message_mod->add($data);
		return $result;
	}

	/**
       +----------------------------------------------------------
       * 用户活动兑奖列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param string arr_prolist  配置文件中的奖品列表信息
       * @param string email  		邮箱
       * @param string nickname     昵称
       * @param string userid       用户ID
       * @param string type         查询奖品类型
       * @param string from         查询兑奖起始时间
       * @param string to           查询兑奖截至时间
       +-----------------------------------------------------------
       * @return array list			邀请记录列表	
       +-----------------------------------------------------------
       * @author zhaoxiang
     */
	public function userExchangeList(){

		$resour=D("UserExchangeListView");

		import ( "@.ORG.Page" ); // 导入分页类库

		$where=$this->userExchangeListWhereParam(array_map('filterVar',$_GET));//查询参数

		$count=$resour->where($where)->count('userGift.id');
		$p = new Page ( $count, 20);

		$list=$resour->where($where)->order('userGift.addtime DESC')->limit ($p->firstRow.','.$p->listRows )->select();

		$list=$this->productsPrizeClassify($list);//分类

		$page = $p->show ();

		if($_GET['export']){
			$arr_prolist=$this->returnplist();
			$list=$resour->where($where)->order('userGift.addtime DESC')->select();
			$this->exportGift($list,$arr_prolist); //导出excel
			exit();
		}

		$trophyList=M("userGift")->distinct(true)->field('type')->select();
		$this->assign ( "page", $page );
		$this->assign('trophylist',$trophyList);
		$this->assign('list',$list);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 用户活动兑奖列表查询参数
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param string email  		邮箱
       * @param string nickname     昵称
       * @param string userid       用户ID
       * @param string type         查询奖品类型
       * @param string from         查询兑奖起始时间
       * @param string to           查询兑奖截至时间
       +-----------------------------------------------------------
       * @return array where		查询参数数组	
       +-----------------------------------------------------------
       * @author zhaoxiang
     */
	private function userExchangeListWhereParam($arguments){

		$where=array();

		if($arguments['email']){
			$where['Users.usermail']=$arguments['email'];
		}

		if($arguments['nickname']){
			$where['Users.nickname']=$arguments['nickname'];
		}

		if($arguments['userid']){
			$where['userGift.userid']=array('exp','NOT IN(SELECT userid FROM user_blacklist)  AND userGift.userid='.$arguments['userid']);
		}else{
			$where['userGift.userid']=array('exp','NOT IN(SELECT userid FROM user_blacklist)');
		}

		if($arguments['type']){
			if($arguments['type'] == 'benefit'){
				$where['userGift.type']='product';
				$where['userGift.giftid']=0;
			}else if($arguments['type'] == 'product'){
				$where['userGift.type']='product';
				$where['userGift.giftid']!=0;
			}else{
				$where['type']=$arguments['type'];
			}
		}

		if($arguments['from'] && $arguments['to']){
			$where["userGift.addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["userGift.addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["userGift.addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}

		$where['userGift.status']=1;

		return $where;
	}

	/**
       +----------------------------------------------------------
       *  用户邀请记录列表信息分类显示
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param array list          用户邀请记录列表信息
       +-----------------------------------------------------------
       * @return array list			分类完的邀请记录列表	
       +-----------------------------------------------------------
       * @author zhaoxiang
     */
	private function productsPrizeClassify($list){
		$giftItem_mod=M("user_gift_item");

		foreach($list as $key =>$value){
			if($value['type']=='product'){
				if($value['giftid']!=0){
					foreach ($arr_prolist as $k => $v){
						if($v['pid']==$value['giftid']){
							$list[$key]['type']=$v['name'];
						}
					}
				}else{
					$list[$key]['type']=$giftItem_mod->where(array('code'=>$value['giftinfo']))->getField('type');
				}
			}elseif($value['type']=='box'){
				$list[$key]['type']='LOLITABOX';
			}
		}
		return $list;
	}

	/**
       +----------------------------------------------------------
       *  奖品列表
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  NULL   NULL 
       +-----------------------------------------------------------
       * @return array  $arr_prolist		导出excel的奖品列表
       +-----------------------------------------------------------
       * @author zhaoxiang
     */
	private function returnplist(){
		$arr_prolist=array (
		1 =>array (
		'pid' => '53',
		'name' => 'CUB面部喷雾套组',
		'count' => '45'
		),
		2 =>array (
		'pid' => '121',
		'name' => '施丹兰覆盆子黑莓皂',
		'count' => '237'
		),
		3 =>array (
		'pid' => '150',
		'name' => '施丹兰欲望都市精油球	',
		'count' => '74'
		),
		4 =>array (
		'pid' => '152',
		'name' => '欧珀莱时光锁紧实弹润系列抗皱精萃眼霜',
		'count' => 75
		),
		5 =>array (
		'pid' => '106',
		'name' => '贝玲妃以真乱假睫毛膏',
		'count' => 65
		),
		6 =>array (
		'pid' => '40',
		'name' => '贝玲妃恰恰胭脂水',
		'count' => 29
		),
		7 =>array (
		'pid' => '95',
		'name' => '倩碧持久透亮唇彩',
		'count' => '60'
		),
		8 =>array (
		'pid' => '83',
		'name' => '蒂珂 角质调理凝露',
		'count' => '40'
		),
		9 =>array (
		'pid' => '149',
		'name' => '双妹夜来香袭人皂',
		'count' => '75'
		),
		10 =>array (
		'pid' => '115',
		'name' => '艾曦媤男士清透洁面磨砂',
		'count' => '100'
		),
		11 =>array (
		'pid' => '63',
		'name' => '欧珀莱臻白抗斑赋弹系列醒活柔肤乳（滋润型）',
		'count' => '100'
		),
		12 =>array (
		'pid' => '141',
		'name' => '郎仕LAB SERIES 男用瞬透保湿凝胶',
		'count' => '150'
		)
		);
		return $arr_prolist;
	}

	/**
       +----------------------------------------------------------
       * UserExchangeList => 导出兑奖记录 
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string $list   	  导出用户邀请列表的数据
       * @param  string $arr_prolist  奖品列表
       +-----------------------------------------------------------
       * @author zhaoxiang
     */		
	private function exportGift($list,$arr_prolist){
		$address_mod=M("userAddress");
		$pro_mod=M("userProfile");

		$giftItem_mod=M("user_gift_item");
		$str="邮箱,奖品,兑奖时间,姓名,电话,地址,邮编,其余地址\n";
		$query=array();

		$list=$this->productsPrizeClassify($list);//分类
		foreach($list as $key =>$value){

			$site_array=$address_mod->where(array('userid'=>$value['gid']))->select();

			if(empty($site_array)){
				$pro_address=$pro_mod->where(array('userid'=>$value['gid']))->field('linkman,telphone,province,city,district,postcode')->find();
				$str.=$list[$key]['usermail'].",".$list[$key]['type'].",".substr($list[$key]['cashtime'],0,10).','.$pro_address['linkman'].','.$pro_address['telphone'].','.$pro_address['province'].$pro_address['city'].$pro_address['district'].','.$pro_address['postcode']."\n";
			}else{
				foreach($site_array as $skey => $current)
				{
					$list[$key]['linkman']=$current['linkman'];
					$list[$key]['telphone']=$current['telphone'];
					$list[$key]['addres']=$current['province'].$current['city'].$current['district'].$current['address'];
					$list[$key]['postcode']=$current['postcode'];
					if($skey === 0){
						$str .=$list[$key]['usermail'].",".$list[$key]['type'].",".substr($list[$key]['cashtime'],0,10).','.$list[$key]['linkman'].",".$list[$key]['telphone'].",".$list[$key]['addres'].",".$list[$key]['postcode'];
					}else{
						$str.=','.$list[$key]['linkman'].'-'.$list[$key]['telphone'].'-'.$list[$key]['addres'].'-'.$list[$key]['postcode'];
					}
				}
				$str.="\n";
			}
		}
		outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "盒该有礼兑奖" ), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 返回被邀请人列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string $userid  邀请人ID
       +-----------------------------------------------------------
       * @return array  返回被邀请人昵称和注册时间
       +-----------------------------------------------------------
       * @author zhaoxiang
     */	
	function returnExchangeData()
	{
		$userid=trim($_POST['userid']);
		if(is_numeric($userid))
		{
			$user_mod=M("users");
			$where['invite_uid']=$userid;
			$where['addtime']=array(array('egt','2012-11-18 00:00:00'),array('elt','2012-12-18 23:59:59'),'AND');
			$result=$user_mod->where($where)->order("addtime DESC")->field('userid,nickname,addtime,state')->select();
			if($result){
				$this->ajaxReturn(1,$result,1);
			}else{
				$this->ajaxReturn(0,'查询结果为空!',0);
			}
		}else{
			$this->ajaxReturn(0,'参数不正确!',0);
		}
	}



	/**
       +----------------------------------------------------------
       * 黑名单用户列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param NULL  NULL 
       +-----------------------------------------------------------
       * @return array  返回被邀请人昵称和注册时间
       +-----------------------------------------------------------
       * @author zhaoxiang
     */	
	public function userBlackList(){
		$user_mod=M();
		$blist_mod=M("UserBlacklist");

		import ( "@.ORG.Page" ); // 导入分页类库
		if($this->_post('searth')){
			$uid=$blist_mod->where(array('userid'=>trim($this->_post('searth'))))->find();
			if(!empty($uid)){
				$userlist=$user_mod->query("SELECT DISTINCT userid,nickname,usermail,state,addtime,score,experience,order_num,evaluate_num,collect_num,follow_num,fans_num,blog_num,if_super FROM users WHERE userid=".trim($this->_post('searth')));
			}
		}else if($this->_post('addbuser')){
			$data['userid']=trim($this->_post('addbuser'));
			$result=$blist_mod->add($data);
			if($result){$this->success('添加成功!');}else{$this->error('添加失败');}
			exit();
		}else if($this->_post('del')){
			natsort($_POST['deluser']);
			$where['userid']=array('IN',implode(',',$_POST['deluser']));
			$result=$blist_mod->where($where)->delete();
			if($result){$this->success('删除成功!');}else{$this->error('删除失败');}
			exit();
		}else{
			$count=$blist_mod->count('userid');
			$p = new Page ( $count, 15);
			$userlist=$user_mod->query("SELECT userid,nickname,usermail,state,addtime,score,experience,order_num,evaluate_num,collect_num,follow_num,fans_num,blog_num,if_super FROM users WHERE userid IN (SELECT userid FROM user_blacklist) ORDER BY userid DESC LIMIT ".$p->firstRow.','.$p->listRows);
			$page = $p->show ();
		}
		$this->assign ( "page", $page );
		$this->assign('userlist',$userlist);
		$this->display();
	}


	/**
       +----------------------------------------------------------
       * 用户动态列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param NULL  NULL 
       +-----------------------------------------------------------
       * @author litingting
     */	
	public function userDynamic(){
		$user_relation_mod=M("UserBehaviourRelation");
		$user_action_type=array(
		"follow_uid"   => "关注用户",
		"buy_boxid"   => "购买loli盒",
		"collect_pid"  => "收藏单品",
		"post_evaluateid" => "评测单品",
		"reply_replyid"  => "回复评测",
		"post_blogid"  => "发表日志",
		"bound_sina"  => "绑定新浪",
		);


		if($nickname = $this->_get('nickname')){
			$where['userid']=M("Users")->where("nickname='".trim($nickname)."'")->getField("userid");
		}

		if($userid = $this->_get('userid')){
			$where['userid']  = trim($userid);
		}

		if($whoid=$this->_get('whoid')){
			$where['whoid'] = $whoid;
		}

		if($type=$this->_get('type')){
			$where['type'] = trim($type);
		}else{
			$where['type']=array('in',"follow_uid,buy_boxid,collect_pid,post_evaluateid,reply_replyid,post_blogid,bound_sina");
		}


		if($this->_get('from') && $this->_get('to')){
			$where['addtime'] = array(array('egt',strtotime($this->_get('from').' 00:00:00')),array('elt',strtotime($this->_get('to').' 23:59:59')));
		}else if($this->_get('from')){
			$where['addtime'] =array('egt',strtotime($this->_get('from').' 00:00:00'));
		}else if($this->_get('to')){
			$where['addtime'] =array('elt',strtotime($this->_get('to').' 23:59:59'));
		}

		$where['status']=1;
		$count=$user_relation_mod->where($where)->count();
		import ( "@.ORG.Page" );
		$p = new Page($count,25);
		$dynamic_list=$user_relation_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order("addtime desc")->select();
		$user_mod=M("Users");
		for($i=0;$i<count($dynamic_list);$i++)
		{
			$type=$dynamic_list[$i]['type'];
			$dynamic_list[$i]['nickname']=$user_mod->where("userid=".$dynamic_list[$i]['userid'])->getField("nickname");
			$dynamic_list[$i]['describe']=$user_action_type[$type];
		}
		$this->assign("dynamic_list",$dynamic_list);
		$this->assign("user_action_type",$user_action_type);
		$this->assign("page",$p->show());
		$this->display();

	}

	//检查邮箱地址
	function checkemaila(){

		$black_mod =M('email_blacklist');
		$user_mod = M('users');

		$dirname="email";
		$dir=opendir($dirname);

		readdir($dir);
		readdir($dir);

		while($fileName=readdir($dir)){

			if($fileName == 'return.csv' || $fileName == 'rubbish.csv'){

				$row = 1;
				$handle = fopen($dirname.'/'.$fileName,"r");
				while ($data = fgetcsv($handle, 1000, ",")) {

					if($row > 1){
						$black_mod->add(array('email'=>$data[3]));
					}
					$row++;
				}
				fclose($handle);

			}else if($fileName == 'invalid.csv'){

				$row = 1;
				$handle = fopen($dirname.'/'.$fileName,"r");
				while ($data = fgetcsv($handle, 1000, ",")) {

					if($row > 1){
						$where = array(
						'usermail'=>$data[3],
						'state'=>2
						);
						$rel = $user_mod->where($where)->find();

						if(empty($rel)){
							$unknown[]=$data[3];
						}else{
							if(strpos($data[3],"wiseie.net")){
								$black_mod->add(array('email'=>$data[3]));
							}else{
								$send[] = $data[3];
							}
						}
					}
					$row++;
				}

				if($send){
					foreach(array_unique($send) as $key => $val){
						$str.=$val."\n";
					}
					outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "邮件地址" ), $str );
					exit();
				}
			}
		}
	}

	function checkoutemail(){
		$black_mod =M('email_blacklist');
		$handle = fopen('email.csv',"r");
		while ($data = fgetcsv($handle, 1000, ",")) {
			if($row > 1  && $data[3]){
				$black_mod->add(array('email'=>$data[3]));
			}
			$row++;
		}
		fclose($handle);
	}



	/**
      +----------------------------------------------------------
      * 积分TOP
      +----------------------------------------------------------  
      * @access public   
      +----------------------------------------------------------
      * @param  form   开始时间 			
      * @param  to     结束时间   
      * @param  num    要取出的数据条数          
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.7.23
     */
	function scoreTop(){

		import("@.ORG.Page");

		$score_mod = M("UserCreditStat");

		if($this->_get('from') && $this->_get('to')){
			$where['add_datetime'] = array(array('egt',$this->_get('from').' 00:00:00'),array('elt',$this->_get('to').' 23:59:59'));
		}else if($this->_get('from')){
			$where['add_datetime'] =array('egt',$this->_get('from').' 00:00:00');
		}else if($this->_get('to')){
			$where['add_datetime'] =array('elt',$this->_get('to').' 23:59:59');
		}

		$where['credit_type'] = 1;

		$count = $score_mod->where($where)->group("userid")->order("score DESC,userid ASC")->field('userid,SUM(credit_value) as score')->select();

		$p = new Page(count($count),25);

		if($this->_get('num')){
			$list = $score_mod->where($where)->group("userid")->order("score DESC,userid ASC")->field('userid,SUM(credit_value) as score')->limit(0,$this->_get('num'))->select();
		}else{
			$list = $score_mod->where($where)->group("userid")->order("score DESC,userid ASC")->field('userid,SUM(credit_value) as score')->limit($p->firstRow . ',' . $p->listRows)->select();
		}

		foreach ($list as $key => $val){
			$list[$key]['nickname'] = M("Users")->where(array('userid'=>$val['userid']))->getField('nickname');
		}
		$page = $p->show();
		$this->assign("list",$list);
		$this->assign('page',$page);
		$this->display();
	}

	/**
      +----------------------------------------------------------
      * 导出用户手机号  排重
      +----------------------------------------------------------  
      * @access public        
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.7.23
     */	
	function exportUserTelphone(){

		$tel  = M("UserAddress")->field("telphone")->select();
		$tel2 = M("UserProfile")->field("telphone")->select();
		$tel3 = M("UserTelphone")->field("tel as telphone")->select();

		foreach ($tel2 as $key => $val){

			if(isset($val['telphone']) && strlen($val['telphone']) == 11){
				$data[] = $val['telphone'];
			}

			if(isset($tel[$key]['telphone']) && strlen($tel[$key]['telphone']) == 11){
				$data[] = $tel[$key]['telphone'];
			}
			if(isset($tel3[$key]['telphone']) && strlen($tel3[$key]['telphone']) == 11){
				$data[] = $tel3[$key]['telphone'];
			}
		}

		$out = implode("\r\n",array_unique($data));
		header('Content-type: application/txt');
		header('Content-Disposition: attachment; filename="用户手机号.txt"');
		echo iconv("UTF-8", "GBK", $out);
		exit();
	}
	
	
	/**
	 * 用户动态信息管理
	 * @param string $ac [add--增加，edit--编辑]
	 * @author litingting
	 */
	public function dynamicList(){
		$ac = $_REQUEST['ac'];
		$dynamic_mod = D("UserDynamic");
		if($ac=="add"){      //增加
			$userid = $_POST['userid'];
			$remark = $_POST['content'];
			if($dynamic_mod->addDynamic($userid,$remark)){
				$this->success("操作成功");
			}else{
				$this->success("操作失败");
			}
			exit();
		}else if($ac=="edit"){
			if($_POST['submit']){
				if($dynamic_mod->addDynamic($_POST['userid'],$_POST['content'])){
					echo "<center>操作成功</center>";
				}else{
					echo "<center>操作失败</center>";
				}
			}else{
				$info = $dynamic_mod->where("userid=".$_GET['userid'])->find();
				$this->assign("info",$info);
				$this->display("editDynamic"); 
			}
			exit;
		}else if($ac=="showName"){
			if($userid= $_POST['userid']){
				echo M("Users")->where("userid=".$userid)->getField("nickname");
			}
			exit;
		}else if($ac=="del"){
			if($userid=$_POST['userid']){
				$flag=$dynamic_mod ->where("userid=".$userid)->delete();
				if($flag){
					$this->ajaxReturn(1,"操作成功",1);
				}else{
					$this->ajaxReturn(0,"操作失败",0);
				}
			}else{
				$this->ajaxReturn(0,"缺少参数",0);
			}
		}
		
		$list = M("UserDynamic")->order("addtime DESC")->limit(5)->select();
		$this->assign("list",$list);
		$this->display();
	}
	
	/**
	 * 特权会员列表
	 * @author penglele
	 */
	public function memberList(){
		import("@.ORG.Page");
		$userid=$_GET['userid'];
		if($userid){
			$where['userid']=$userid;
		}
		if($_GET['if_member']){
			$ntime=date("Y-m-d");
			if($_GET['if_member']==1){
				$where['endtime']=array("exp",">='".$ntime."'");
			}else if($_GET['if_member']==2){
				$where['endtime']=array("exp","<'".$ntime."'");
			}
		}
		$member_mod=D("Member");
		$order="";
		if($this->_get('order')){
			if($this->_get('by') ==1){
				$order = $this->_get('order').' DESC';
			}else {
				$order = $this->_get('order').' ASC';
			}
		}
		if($_GET['type']=="exportList"){
			$list=$member_mod->getMemberList($where,$order);
			$this->exportMemberList($list);
		}
		
		$count=$member_mod->getMemberCount($where);
		$p = new Page($count,15);
		$limit=$p->firstRow . ',' . $p->listRows;
		$return['list']=$member_mod->getMemberList($where,$order,$limit);
		$page = $p->show();
		$this->assign("return",$return);
		$this->assign("page",$page);
		$this->display();
	}
	
	/**
	 * 特权会员订单列表
	 * @author penglele
	 */
	public function memberOrderList(){
		import("@.ORG.Page");
		$mem_order_mod=D("MemberOrder");
		$where=array();
		$order="";
		//查询条件
		//订单ID
		if($_GET['orderid']){
			$where['ordernmb']=$_GET['orderid'];
		}
		
		//用户ID
		if($_GET['userid']){
			$where['userid']=$_GET['userid'];
		}		
		
		//订单支付时间
		if($_GET['from'] && $_GET['to']){
			$where['addtime']=array(array('egt',$_GET['from'].' 00:00:00'),array('elt',$_GET['to'].' 23:59:59'),'AND');
		}	
			
		//订单是否有效
		$where['ifavalid']=$_GET['ifavalid']=="" ? 1 : $_GET['ifavalid'];
		
		//订单状态
		$where['state']=$_GET['orderstate']=="" ? 1 : $_GET['orderstate'] ;
		
		//订单来源
		if($_GET['resour']!=""){
			$where['fromid'] =$_GET['resour'];
		}
		
		if(!empty($_GET['m_type']) && $_GET['m_type']!=0){
			$where['m_type'] =$_GET['m_type'];
		}
		
		//查询总数
		$count=$mem_order_mod->getMemberOrderCount($where);
		$p = new Page($count,15);
		$limit=$p->firstRow . ',' . $p->listRows;
		$return['list']=D("MemberOrder")->getMemberOrderList($where,$order,$limit);
		$return['plist']=M('promotion')->field('code,name')->select(); //用户推广管理列表
		$page = $p->show();
		$this->assign("return",$return);
		$this->assign("page",$page);
		$this->display();
	}
	

	/**
	 +----------------------------------------------------------
	 * 返回订单来源参数
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @type	ajax
	 +-----------------------------------------------------------
	 * @author penglele
	 */
	public function returnOrderResour(){
		$result = M("MemberOrder")->DISTINCT(true)->where(array('fromid'=>$this->_post('fromid')))->field('frominfo')->select();
		if($result !== false){
			$this->ajaxReturn($result,'返回成功!',1);
		}else{
			$this->ajaxReturn(null,'返回失败!',0);
		}
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 用户手机验证码查询
	 +-----------------------------------------------------------
	 * @author penglele
	 */	
	public function userTelCode(){
		$tel=$_GET['tel'];
		$info="";
		if($tel){
			$info=M("UserTelphone")->where("tel='".$tel."'")->find();
			if(!$info){
				$info=1;
			}
		}
		$this->assign("info",$info);
		$this->display();
	}
	
	/**
	 * 获取用户美丽档案的信息
	 * @author penglele
	 */
	public function get_user_vote(){
		$userid=$_POST['userid'];
		$alist=D("UserVote")->getUserVoteList($userid);
		$list=array();
		if($alist){
			foreach($alist['list'] as $key=>$val){
					$list[$key]['state']=$val['if_answer']==1 ? "已完成" : "未完成";
					$list[$key]['title']=$val['title'];
					$answer="";
					if($key==1){
						$sex=$val['result']['sex']==1 ? "男" : "女" ;
						$answer=$answer."性别：".$sex."　";
						if($val['result']['years']){
							$answer=$answer."生日：".$val['result']['years']."年".$val['result']['months']."月".$val['result']['days']."日　";
						}
						if($val['result']['province']){
							$answer=$answer."所在地：".$val['result']['province'].",".$val['result']['city'].",".$val['result']['district']."　";
						}
						if($val['result']['edu']){
							$edu="";
							switch($val['result']['edu']){
								case 1:
									$edu="高中";
									break;
								case 2:
									$edu="大专";
									break;
								case 3:
									$edu="本科";
									break;
								case 4:
									$edu="硕士";
									break;
								case 5:
									$edu="博士";
									break;
							}
							if($edu){
								$answer=$answer."学历：".$edu."　";
							}
						}
					}else{
						if($val['if_answer']==1){
							if($val['type']==1 || $val['type']==4){
								$answer=$val['result'];
							}else{
								$answer=implode(",",$val['result']);
							}
						}else{
							$answer="";
						}
					}
					$list[$key]['result']=$answer;
			}			
		}
		$this->ajaxReturn(1,$list,1);
	}
	
	/**
	 * 导出特权会员列表
	 * @author penglele
	 */
	public function exportMemberList($list){
		$str="用户昵称,ID,手机号码,特权会员类型,特权有效期,订单数,分享数\n";
		$member_order_mod=M("MemberOrder");
		foreach ($list as $key => $value){
			$type=$member_order_mod->where("userid=".$value['userid']." AND state=1 AND ifavalid=1")->order("paytime DESC")->getField("m_type");
			$str.=$value['nickname'].",".$value['userid'].",".$value['telphone'].",".$type.", ".$value['endtime'].",".$value['order_num'].",".$value['blog_num']."\n";
		}
		$str=" ".$str;
		outputExcel ( iconv ( "UTF-8", "GBK",'特权会员列表'), $str );
		exit();
	}
	
	
	
	
}
?>
