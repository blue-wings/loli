<?php
//用户活动兑奖专属控制器
class ActivityIndexAction extends CommonAction{

	private $scoredatafile='topscore.txt';

	/**
       +----------------------------------------------------------
       * 活动列表首页  数据不全,暂时只支持静态
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string draw   		Benefit抽奖活动获奖人数
       * @param  string find   		Benefit寻找氧气瓶活动获奖人数
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.20
       */
	function index(){
		$mod=M();

		$draw_count=$mod->cache(true)->query("SELECT count( id ) AS count FROM `user_gift` WHERE `status` =1 AND userid NOT IN (SELECT userid FROM user_blacklist )");

		$draw=$draw_count[0]['count'];

		$coupon_count=$mod->cache(true)->query("SELECT count(id) AS count FROM `activity_benefit` WHERE `bottletype` = '优惠券' AND `statstatus`=1");

		$box_count=$mod->cache(true)->query("SELECT count(id) AS count FROM `type` WHERE `bottletype` = 'box_gift'");

		$find=(int)$coupon_count[0]['count'] + (int)count($box_count);

		$scorebroad=$mod->query("SELECT COUNT(type) AS score  FROM `user_behaviour_relation` WHERE type='scorelist' AND status=1");

		$activityManage=$mod->query("SELECT count(id) AS acount FROM `user_activity` WHERE remark <> '没有抽到'");

		$this->assign('find',$find);
		$this->assign('draw',$draw);
		$this->assign('score',$scorebroad[0]['score']);
		$this->assign('activityManage',$activityManage[0]['acount']);
		$this->display();
	}


	/**
       +----------------------------------------------------------
       * Benefit寻找氧气瓶
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string status   			状态为1
       * @param  string bottletype  	    碎片，优惠券
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.20
       */
	function benefitFindO2(){
		$benefit_mod=D("BenefitActiveView");
		import ( "@.ORG.Page" );
		$where['activityBenefit.status']=1;

		if($this->_get('search') || $this->_get('export')){
			if($this->_get('email')){
				$where['Users.usermail']=trim($this->_get('email'));
			}

			if($this->_get('nickname')){
				$where['Users.nickname']=trim($this->_get('nickname'));
			}

			if($this->_get('userid')){
				$where['activityBenefit.userid']=trim($this->_get('userid'));
			}

			$from=$this->_get("from").' 00:00:00';
			$to=$this->_get("to").' 23:59:59';
			if($this->_get("from") && $this->_get("to"))
			{
				$where["activityBenefit.postdate"]=array(array("egt","$from"),array("elt","$to"),'AND');
			}else if($this->_get("from") && $this->_get("to")==''){
				$where["activityBenefit.postdate"]=array("egt","$from");
			}else if($this->_get("from")=='' && $this->_get("to")){
				$where["activityBenefit.postdate"]=array("elt","$to");
			}

			if($this->_get('type')){
				if($this->_get('type')=='box'){
					$where['type']='box_gift';
				}else if($this->_get('type')=='all'){
					$where['bottletype'] != '';
				}else{
					$where['bottletype']=$this->_get('type');
				}
			}
		}

		if(count($where)==1){
			$where['_string']="activityBenefit.bottletype='优惠券' OR  activityBenefit.type='box_gift'";
		}

		if($this->_get('export')){
			$exportList=$benefit_mod->where($where)->order('activityBenefit.postdate DESC,id DESC')->select();
			$this->BenefitFindexport($this->selectDataFlite($exportList));
			exit();
		}else{
			$count=$benefit_mod->where($where)->count('activityBenefit.id');
			$p = new Page ( $count, 20);

			$list=$benefit_mod->where($where)->order('activityBenefit.postdate DESC,id DESC')->limit ($p->firstRow.','.$p->listRows )->select();
			$list=$this->multisort($this->selectDataFlite($list));
			$page = $p->show ();
		}

		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 对数组进行倒叙排列
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string 		list   	数据库查出的数据,需要倒序处理
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.21
       */
	private function multisort($list){
		foreach ($list AS $key => $value){
			$sort[$key]=strtotime($value['postdate']);
		}
		array_multisort($sort,SORT_DESC,$list,SORT_DESC);
		return $list;
	}

	/**
       +----------------------------------------------------------
       * 备注:查询时间,如果查询的是碎片,如果满足盒子的要求则合成盒子显示
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string 		list   		以按时间查询完成的列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.20
       */
	private function selectDataFlite($list){
		$user_arr=$return_arr=$adress_arr=array();
		foreach ($list AS $key => $value){
			if($value['bottletype'] == '优惠券' || $value['type'] == 'box_gift')
			{
				$list_arr[]=$val;
				$return_arr[]=$value;
			}
		}
		return $return_arr;
	}

	/**
       +----------------------------------------------------------
       * 导出excel 需要关联用户的地址表  多个地址同时导出
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string 	$exportList   	需要导出的列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.20
       */	

	private function BenefitFindexport($exportList){
		$address_mod=M("userAddress");
		$pro_mod=M("userProfile");
		$str="邮箱,奖品,兑奖时间,姓名,电话,地址,邮编,其余地址\n";

		foreach ($exportList AS $key => $value){

			if($value['bottletype'] == '优惠券'){
				$exportList[$key]['bottletype']='Benefit优惠券';
			}else{
				$exportList[$key]['bottletype']='Benefit专属萝莉盒';
			}

			$site_array=$address_mod->where(array('userid'=>$value['userid']))->select();

			if(empty($site_array)){
				$pro_address=$pro_mod->where(array('userid'=>$value['userid']))->find();

				$str.=$exportList[$key]['usermail'].','.$exportList[$key]['bottletype'].','.substr($exportList[$key]['postdate'],0,10).','.$pro_address['linkman'].','.$pro_address['telphone'].','.$pro_address['province'].$pro_address['city'].$pro_address['district'].$pro_address['address'].','.$pro_address['postcode']."\n";

			}else{
				foreach($site_array as $skey => $current){
					$exportList[$key]['linkman']=$current['linkman'];
					$exportList[$key]['telphone']=$current['telphone'];
					$exportList[$key]['addres']=$current['province'].$current['city'].$current['district'].$current['address'];
					$exportList[$key]['postcode']=$current['postcode'];

					if($skey==0){
						$str .=$exportList[$key]['usermail'].",".$exportList[$key]['bottletype'].",".substr($exportList[$key]['postdate'],0,10).','.$exportList[$key]['linkman'].",".$exportList[$key]['telphone'].",".$exportList[$key]['addres'].",".$exportList[$key]['postcode'];
					}else{
						$str.=','.$exportList[$key]['linkman'].'-'.$exportList[$key]['telphone'].'-'.$exportList[$key]['addres'].'-'.$exportList[$key]['postcode'];
					}
				}
				$str.="\n";
			}
		}
		outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . " Benefit寻找氧气瓶兑奖名单" ), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 按查询时间查询用户积分数据
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string 	show		ajax 取值
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.27
       */	
	public function getUserScoreData(){
		if($this->_post('show') =='user'){

			$user_mod=M('users');
			$mod=new model();

			$where['start']=filterVar($this->_post('starttime')).' 00:00:00';
			$where['end']=filterVar($this->_post('endtime')).' 23:59:59';

			$list=$mod->query("SELECT userid, SUM( `credit_value` ) AS uscore FROM `user_credit_stat` WHERE userid NOT IN (SELECT userid FROM user_blacklist) AND `credit_type`=1 AND credit_value>0 AND `add_datetime` >='".$where['start']."' AND  `add_datetime` <='".$where['end']."'  GROUP BY userid ORDER BY uscore DESC,userid ASC  LIMIT 0,30");

			foreach ($list AS $key => $value){
				$list[$key]['nickname']=$user_mod->where(array('userid'=>$value['userid']))->getField('nickname');
			}

			if($list){
				$this->ajaxReturn($list,'请求成功!',1);
			}else{
				$this->ajaxReturn(0,'请求失败!',0);
			}
		}
	}
	/**
       +----------------------------------------------------------
       * 积分排行榜
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.31
       */	
	public function  scoreBoard(){

		$user_mod = M('users');
		$add_mod  = M("userAddress");
		$stat_mod = M('userCreditStat');
		$relation_mod = M('userBehaviourRelation');
		import ( "@.ORG.Page" ); // 导入分页类库

		$where['type']='scorelist';
		$where['status']=1;
		if($this->_get('number') || $this->_get('search') || $this->_get('export')){

			if($this->_get('search') || $this->_get('export')){
				$retunWhere=$this->ScoreBoardWhere(array_map('filterVar',$_GET));
				$where=$retunWhere['where'];
				$where['type']='scorelist';
				$where['status']=1;
			}else{
				$where['whoid']=filterVar($this->_get('number'));

				$user_arr=$user_mod->query("SELECT userid FROM `user_credit_stat`
			WHERE 	userid NOT IN (SELECT userid FROM user_blacklist) 	AND userid IN (SELECT userid FROM user_behaviour_relation WHERE type='scorelist' AND status=1) AND `credit_type`=1 AND credit_value>0 AND add_datetime >='2013-02-01 00:00:00' AND add_datetime <='2013-02-28 23:59:59'	GROUP BY userid ORDER BY SUM( `credit_value` ) DESC");

				foreach ($user_arr as $k=>$v){
					$user[]=(int)$v['userid'];
				}
				$imp=implode(',',$user);
				$where['userid']=array('exp',"IN($imp)");
			}

			if($retunWhere['count']){
				$userlist=$relation_mod->where($where)->limit (0,$retunWhere['count'])->field('userid,addtime')->select();
			}else{
				$count=$relation_mod->where($where)->count();
				$p = new Page ( $count, 15);
				$userlist=$relation_mod->where($where)->limit ( $p->firstRow . ',' . $p->listRows )->field('userid,addtime')->
				order("find_in_set(userid,'{$imp}')")->select();

				$las=$p->firstRow+1;
				$page = $p->show ();
			}
			if($userlist){
				foreach ($userlist as $key => $val){
					$userid=$val['userid'];
					$cwhere['userid']=$userid;
					$cwhere['credit_type']=1;
					$cwhere['credit_value']=array('gt',0);
					$cwhere['add_datetime']=array(array('egt','2013-02-01 00:00:00'),array('elt','2013-02-28 23:59:59'),'AND');

					$score=$stat_mod->where($cwhere)->SUM('credit_value');
					$userlist[$key]['score']=$score;

					$addressInfo=$add_mod->where(array('userid'=>$userid,'if_active'=>1))->field('linkman,telphone,province,city,district,address,postcode')->find();

					if($addressInfo){
						$userlist[$key]['linkman']=$addressInfo['linkman'];
						$userlist[$key]['telphone']=$addressInfo['telphone'];
						$userlist[$key]['address']=$addressInfo['province'].$addressInfo['city'].$addressInfo['district'].$addressInfo['address'];
						$userlist[$key]['postcode']=$addressInfo['postcode'];
					}

					$userInfo=$user_mod->where(array('userid'=>$userid))->field('nickname,usermail')->find();
					if($userInfo){
						$userlist[$key]['nickname']=$userInfo['nickname'];
						$userlist[$key]['mail']=$userInfo['usermail'];
					}

					$userlist[$key]['index']=$las+$key;
				}
			}

			if($this->_get('export')){
				$this->exportExcel($userlist);
			}

			$number=$this->_get('number')?$this->_get('number'):$where['whoid'];
			$this->assign('number',$number);
			$this->assign('userlist',$userlist);
		}else{
			$count=$relation_mod->where($where)->where($where)->count('distinct whoid');

			$p = new Page ( $count, 15);

			//榜期列表
			$boardList=$relation_mod->distinct('whoid')->where($where)->order('whoid ASC')->field('whoid')->
			limit ( $p->firstRow . ',' . $p->listRows )->select();
			$page = $p->show ();
			$this->assign('scoreboard',$boardList);
		}

		$this->assign ( "page", $page );
		$this->display();
	}

	//整理查询条件
	private function ScoreBoardWhere($arguments){
		$user_mod=M('users');
		$returnArray=array();

		$where['whoid']=$arguments['cnum'];

		if($arguments['userid']){
			$where['userid']=$arguments['userid'];
		}

		if($arguments['nickname']){
			$map['nickname']=array('LIKE','%'.$arguments['nickname'].'%');
			$where['userid']=$user_mod->where($map)->getField('userid');
		}

		if($arguments['email']){
			$where['userid']=$user_mod->where(array('usermail '=>$arguments['email']))->getField('userid');
		}

		if($arguments['count']){
			$human_count=$arguments['count'];
		}

		if($arguments['from'] && $arguments['to']){
			$where['addtime']=array(array('egt',strtotime($arguments['from'].' 00:00:00')),array('elt',strtotime($arguments['to'].' 23:59:59')),'AND');
		}else if($arguments['from']){
			$where['addtime']=array('egt',strtotime($arguments['from'].' 00:00:00'));
		}else if($arguments['to']){
			$where['addtime']=array('elt',strtotime($arguments['to'].' 23:59:59'));
		}

		$returnArray['where']=$where;
		$returnArray['count']=$human_count;
		return $returnArray;
	}

	/**
       +----------------------------------------------------------
       * 积分排行榜导出
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.31
       */	
	private function exportExcel($list){
		$str="昵称,姓名,手机,地址,邮编\r\n";
		foreach ($list AS $key=>$value){
			$str.=$value['nickname'].",".$value['linkman'].",".$value['telphone'].",".$value['address'].",".$value['postcode']."\r\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK","积分排行榜" ), $str );
		exit();
	}

	
	/**
	 * 显示用户积分排行榜
	 * @author zhenghong
	 */
	public function showScoreList(){
		$pageline=$_REQUEST["amount"]; //每页显示记录数
		$where['whoid']=$_REQUEST["no"];
		$where['start']=$_REQUEST["sdate"];
		$where['end']=$_REQUEST["edate"];
		$mod=M();
		$user_mod=M("Users");
		$user_address_mod=M("UserAddress");
		$user_behaviour_rel_mod=M("UserBehaviourRelation");

		//条件筛选
		if(!empty($_REQUEST["condition_userid"])){
			$condition["userid"]=$_REQUEST["condition_userid"];
		}
		if(!empty($_REQUEST["condition_nickname"])) {
			$condition["nickname"]=array('LIKE','%'.$_REQUEST["condition_nickname"].'%');
		}
		if(!empty($_REQUEST["condition_usermail"])){
			$condition["usermail"]=array('LIKE','%'.$_REQUEST["condition_usermail"].'%');
		}
		//用户属性条件
		$user_in=$user_activity_in=array();
		if(count($condition)>0) {
			$user_in_list=$user_mod->field("userid")->where($condition)->select();
			for($i=0;$i<count($user_in_list);$i++){
				$user_in[]=$user_in_list[$i]["userid"];
			}
		}
		//用户参与活动条件
		if(!empty($_REQUEST["from"]) && !empty($_REQUEST["to"])) {
			$stime=strtotime($_REQUEST["from"]." 00:00:00");
			$etime=strtotime($_REQUEST["to"]." 23:59:59");
			$activitycondition["whoid"]=$where['whoid'];
			$activitycondition["type"]='scorelist';

			$activitycondition["addtime"] = array('BETWEEN',array($stime,$etime));
			$user_activity_in_list=$user_behaviour_rel_mod->field("userid")->where($activitycondition)->select();
			for($i=0;$i<count($user_activity_in_list);$i++){
				$user_activity_in[]=$user_activity_in_list[$i]["userid"];
			}
		}
		//合并用户属性与参与活动的条件
		if(count($user_in)>0 && count($user_activity_in)>0) 	$user_list=array_intersect($user_in,$user_activity_in);
		else $user_list=array_merge($user_in,$user_activity_in);
		if($_REQUEST["search"] || $_REQUEST["export"]) {
			if(count($condition) || count($activitycondition)) $user_in_where="0 AND ";
			if(count($user_list)>0) {
				$user_in_str=implode(",",$user_list);
				$user_in_where="userid IN ($user_in_str) AND ";
			}
		}
		//得到参与活动总人数
		$rscount=$user_behaviour_rel_mod->where("$user_in_where whoid='".$where['whoid']."' AND type='scorelist'")->count();
		import("@.ORG.Page");

		if($_REQUEST["export"]){
			$pageline=$rscount;
		}

		$p = new Page($rscount,$pageline);
		//获取主数据列表
		$scorelist=$mod->query("SELECT userid, SUM( `credit_value` ) AS uscore FROM `user_credit_stat` WHERE userid IN (SELECT userid FROM user_behaviour_relation WHERE $user_in_where whoid=".$where['whoid']." AND type='scorelist') AND `credit_type`=1  AND `action_id` NOT LIKE '%user_score_exchange%'  AND `add_datetime` >='".$where['start'].' 00:00:00'."' AND  `add_datetime` <='".$where['end'].' 23:59:59'."' GROUP BY userid ORDER BY uscore DESC,userid ASC LIMIT $p->firstRow,$p->listRows");

		//整合输出数据
		for($i=0;$i<count($scorelist);$i++){
			//整合报名时间
			$activityinfo=$user_behaviour_rel_mod->field("addtime")->where("userid=".$scorelist[$i]["userid"]."  AND whoid='".$where['whoid']."' AND type='scorelist'")->find();

			$scorelist[$i]=array_merge($scorelist[$i], $activityinfo);
			//整合用户昵称及邮件地址
			$userinfo=$user_mod->field("nickname,usermail")->where("userid=".$scorelist[$i]["userid"])->find();
			if($userinfo)	$scorelist[$i]=array_merge($scorelist[$i], $userinfo);
			//整合用户默认地址信息
			$addressinfo=$user_address_mod->field("linkman,telphone,CONCAT(province,city,district,address,'\(',postcode,'\)') AS uaddress")->where("userid=".$scorelist[$i]["userid"]." AND if_active=1 AND if_del=0")->find();
			if($addressinfo) $scorelist[$i]=array_merge($scorelist[$i],$addressinfo);
			//计算排名
			$scorelist[$i]['index'] =  $p->firstRow+1+$i;
		}
	
		//导出用户积分排行榜数据
		if($this->_get('export')){
			$this->exportUserScoreListData($scorelist,$where['whoid']);
		}
		$page = $p->show();
		$this->assign("scorelist",$scorelist);
		$this->assign("page",$page);
		$this->display("scorelist");
	}

	/**
       +----------------------------------------------------------
       * 导出本期用户积分排行榜数据
       +----------------------------------------------------------  
       * @access private   
       +----------------------------------------------------------
       * @param  array  scorelist    查询出来的本期用户排行榜数据 		
       * @param  string no			 本期的期数 		
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.5.6
     */
	private function exportUserScoreListData($scorelist,$no){
		
		$user_behaviour_rel_mod=M("UserBehaviourRelation");
		
		$str="昵称,姓名,手机,地址,是否兑奖\r\n";
		
		foreach ($scorelist AS $key=>$value){
			
			$condition = array(
				'userid'=>$value['userid'],
				'whoid'=>$no,
				'type'=>'apply_box'
			);
			
			$tips = $user_behaviour_rel_mod->where($condition)->find();
			
			if(ord($value['nickname']) != 226){
				$str.=$value['nickname'].",".$value['linkman'].",".$value['telphone'].",".$value['uaddress'].",";
				if($tips){
					$str .="已申请";
				}else{
					$str .="未申请";
				}
			}
			$str.="\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK","积分排行榜" ), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 用户参加活动管理
       +----------------------------------------------------------  
       * @access public   
       +----------------------------------------------------------
       * @param  string activityName  	 活动名称  			
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.26
     */
	function  userJoinActivityManage(){
		if($this->_get('atype') || $this->_get('search')){

			$ac_mod=M("userActivity");
			$user_mod=M("users");
			import("@.ORG.Page");

			if($this->_get('search')){
				$where=$this->ActivityManageWhere(array_map('filterVar',$_GET));

				$count = $ac_mod->where($where)->count('id');
				$p = new Page($count,15);

				$list=$ac_mod->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

			}else if($this->_get('export')){

				$map = "`activitytype` = '{$this->_get('atype')}' AND `remark` <> '没有抽到'  AND  `remark` <> '没有转到' AND `remark` <> '没有砸到'";
				$list = $ac_mod->where($map)->field('userid,remark')->order('addtime DESC')->select();

				$this->exportUserJoinActivityInfo($list);

			}else{

				$count = $ac_mod->count('id');
				$p = new Page($count,15);

				$list=$ac_mod->where(array('activitytype'=>$this->_get('atype')))->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
			}

			$page = $p->show();

			foreach ($list AS $key => $value){
				$userinfo=$user_mod->where(array('userid'=>$value['userid']))->find();
				if($userinfo){
					$list["$key"]['nickname']=$userinfo['nickname'];
					$list["$key"]['usermail']=$userinfo['usermail'];
				}
			}

			//活动类型列表
			$activityType=$ac_mod->DISTINCT(true)->field('activitytype')->select();

			//奖品类型列表
			$prizeType=$ac_mod->DISTINCT(true)->where(array('activitytype'=>$this->_get('atype')))->field('remark')->select();

			$this->assign("page",$page);
			$this->assign('atype',$activityType);
			$this->assign('ptype',$prizeType);
			$this->assign('aclist',$list);
			$this->display();
		}else{
			$this->error('参数有误!');
		}
	}


	/**
       +----------------------------------------------------------
       * 用户参加活动管理查询参数
       +----------------------------------------------------------  
       * @access private   
       +----------------------------------------------------------
       * @param  array  $arguments   页面传递的查询参数  			
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.26
     */
	private function ActivityManageWhere($arguments){

		$where=array();

		if($arguments['atype']){
			$where['activitytype']=	$arguments['atype'];
		}

		//用户id
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

		//抽奖时间
		if($arguments['from'] && $arguments['to']){
			$where["addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}

		if($arguments['ptype']){
			$where['remark']=$arguments['ptype'];
		}
		return $where;
	}


	/**
       +----------------------------------------------------------
       * 用户参加活动获奖用户导出
       +----------------------------------------------------------  
       * @access private   
       +----------------------------------------------------------
       * @param  array  $list 符合条件的数据数组  			
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.20
     */
	private function exportUserJoinActivityInfo($list){
		$address_mod = M("userAddress ");
		$str="用户ID,姓名,手机,地址,奖品明细\r\n";

		foreach ($list AS $key => $value){

			$addressInfo = $address_mod->where(array('userid'=>$value['userid'],'if_active'=>1))->field('linkman,telphone,province,city,district,address')->find();

			if($addressInfo['province'] == $addressInfo['city']){
				$city = $addressInfo['province'];
			}else{
				$city = $addressInfo['province'].$addressInfo['city'];
			}

			$str .= $value['userid'].",".$addressInfo['linkman'].",".$addressInfo['telphone'].",".$city.$addressInfo['district'].$addressInfo['address'].",".$value['remark']."\r\n";

		}
		outputExcel ( iconv ( "UTF-8", "GBK","用户参加活动获奖用户信息" ), $str );
		exit();
	}
	
	
	/**
	 * 
	 */
	public function lolihighv5_spread(){
		
	}
}
?>
