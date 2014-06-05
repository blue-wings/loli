<?php
class UserAccessTraceAction extends CommonAction {

	/**
     * 生成index分组下控制器中所有类的各个所有方法的数组文件
     */
	public function createClassMethodsFile() {
		$classdir= APP_NAME."/Modules/index/Action";
		$classFileArray = scandir ( $classdir );
		$array = array ();
		$str = "<?php \n \$class_method_list=array(";
		for($i = 0; $i < count ( $classFileArray ); $i ++) {
			if (strrchr($classFileArray [$i], ".")=='.php') {
				$classname = str_replace ( "Action.class.php", "", $classFileArray [$i] );
				$code = file_get_contents ( $classdir."/" . $classFileArray [$i] );
				preg_match_all ( "/function +([^\(]+)/", $code, $arr );
				$str .= "'$classname'=> array( \n";
				for($j = 0; $j < count ( $arr [1] ); $j ++) {
					$str .= "'{$arr[1][$j]}' => '{$arr[1][$j]}', \n";
				}
				if ($i == count ( $classFileArray ) - 1)
				$str .= ")) \n";
				else
				$str .= "),   \n";
			}
		}
		$str .= "?>";
		$a=file_put_contents (APP_NAME."/Modules/".GROUP_NAME. "/Conf/index_class_method.php", $str );
	}

	/**
     * 类方法列表
     */
	public function methodCredit() {
		$conf_filename=APP_NAME."/Modules/".GROUP_NAME. "/Conf/index_class_method.php";
		if(! is_file($conf_filename))
		$this->createClassMethodsFile();
		include $conf_filename;
		if($_POST['type']=='edit')
		{
			$module=$_POST['module'];
			$action=$_POST['action'];
			$class_method_list[$module][$action]=$_POST['value'];
			$str=var_export($class_method_list,true);
			$str= "<?php \n \$class_method_list=".$str."\n  ?>";
			file_put_contents($conf_filename,$str);
			$this->ajaxReturn($class_method_list,"更改成功",1);
		}
		if($_POST['type']=='find')
		{
			$arr=array();
			if($module=$_POST['find_module'])  {
				// 				echo $module;
				if($action=$_POST['find_action'])
				{
					$arr[$module][$action]=$class_method_list[$module][$action];
				}
				else
				$arr[$module]=$class_method_list[$module];
			}
			else{
				if($action=$_POST['find_action'])  {
					foreach($class_method_list as $key =>$value)
					{
						if($value[$action])  {
							$arr[$key][$action]=$value[$action];
						}
					}
				}
			}
			if($arr)
			$class_method_list=$arr;

		}
		if($_POST['type']=="update")
		{
			$classdir= APP_NAME."/Modules/index/Action";
			$classFileArray = scandir ( $classdir);
			$array = array ();
			$str = "<?php \n \$class_method_list=";
			for($i = 0; $i < count ( $classFileArray ); $i ++) {
				if ($classFileArray [$i] != '.' && $classFileArray [$i] != '..') {
					$classname = str_replace ( "Action.class.php", "", $classFileArray [$i] );
					$code = file_get_contents ( $classdir."/" . $classFileArray [$i] );
					preg_match_all ( "/function\s+([^\(]+)/", $code, $arr );
					for($j = 0; $j < count ( $arr [1] ); $j ++) {
						$method=$arr[1][$j];
						if(!isset($class_method_list[$classname][$method]))
						$class_method_list[$classname][$method]="(".$method.")";
					}
				}
			}
			$str=$str.var_export($class_method_list,true)."\n  ?>";
			file_put_contents($conf_filename, $str);
		}
		$this->assign("class_method_list",$class_method_list);
		$this->display();
	}


	/**
     * 用户访问页面轨迹
     */
	public function userTraceList(){
		$user_trace_mod=M("UserBehaviourTrace");
		if($url=trim($_REQUEST['url']))
		{
			$where['url']=array('like',"$url%");
		}
		if($module=trim($_REQUEST['module']))
		{
			$where['module']=$module;
		}
		if($action=trim($_REQUEST['action']))
		{
			$where['action']=$action;
		}
		if($userid=trim($_REQUEST['userid']))
		{
			$where['userid']=$userid;
		}
		if($time=trim($_REQUEST['time']))
		{
			$time=$time*60;
			$where['optime']=array('exp',"between UNIX_TIMESTAMP()-$time and UNIX_TIMESTAMP()");
		}

		if($ip=$_REQUEST['userip']){
			$where['ip'] = $ip;
		}

		if($this->_get("from") && $this->_get("to")){
			$where['optime']=array(array('egt',strtotime($this->_get("from").' 00:00:00')),array('elt',strtotime($this->_get("to").' 23:59:59'),'AND'));
		}else if($this->_get("from")){
			$where['optime']=array('egt',strtotime($this->_get("from").' 00:00:00'));
		}else if($this->_get("to")){
			$where['optime']=array('egt',strtotime($this->_get("to").' 23:59:59'));
		}

		$count=$user_trace_mod->where($where)->count();
		import("@.ORG.Page");
		$p = new Page($count,20);
		$user_trace_list=$user_trace_mod->where($where)->order("optime DESC")->limit($p->firstRow . ',' . $p->listRows)->select();

		$page=$p->show();
		include APP_NAME."/Modules/".GROUP_NAME. "/Conf/index_class_method.php";
		vendor('QQwry.class#QQwry');
		$QQWry=new QQwry();
		for($i=0;$i<count($user_trace_list);$i++)
		{
			$user_trace_list[$i]['nickname']=M("Users")->where("userid=".$user_trace_list[$i]['userid'])->getField('nickname');
			$action=$user_trace_list[$i]['action'];
			$module=$user_trace_list[$i]['module'];
			$user_trace_list[$i]['introduce']=$class_method_list[$module][$action];
			$ifErr = $QQWry->QQWry($user_trace_list[$i]['ip']);
			if($ifErr==1)  {
				$user_trace_list[$i]['address']='FileOpenError';
			}
			elseif($ifErr===0)
			$user_trace_list[$i]['address']=$QQWry->Country;
			else
			$user_trace_list[$i]['address']=iconv('gbk','utf-8',$QQWry->Country . $QQWry->Local);
		}
		if($url)
		$exp['url']=$url;
		$time=$_REQUEST['time']?$_REQUEST['time']*60:600;
		$exp['optime']=array('exp',"between UNIX_TIMESTAMP()-$time and UNIX_TIMESTAMP()");
		$total_count=$user_trace_mod->field("count(distinct userid) as total")->where($exp)->select();
		$this->assign("user_trace_list",$user_trace_list);
		$this->assign("total_line_user",count($trace_list));
		$this->assign("page",$page);
		$this->display();
	}



	/**
      +----------------------------------------------------------
      * 活跃用户查询
      +----------------------------------------------------------  
      * @access public   
      +----------------------------------------------------------
      * @param  from     开始时间		
      * @param  to       结束时间  
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.7.11
     */	
	public function userHotSearch(){

		if($this->_get("from") || $this->_get("to")){

			import("@.ORG.Page");

			$user_trace_mod = M("UserBehaviourTrace");

			if($this->_get("from") && $this->_get("to")){
				$where['optime']=array(array('egt',strtotime($this->_get("from").' 00:00:00')),array('elt',strtotime($this->_get("to").' 23:59:59'),'AND'));
			}else if($this->_get("from")){
				$where['optime']=array('egt',strtotime($this->_get("from").' 00:00:00'));
			}else if($this->_get("to")){
				$where['optime']=array('egt',strtotime($this->_get("to").' 23:59:59'));
			}

			$where['userid'] = array('exp',"IS NOT NULL");

			$count = $user_trace_mod->where($where)->field("COUNT(DISTINCT(userid)) as count")->find();

			$pape = new Page($count['count'],20);

			$userlist =$user_trace_mod->where($where)->field("userid,COUNT('id') as cot")->group("userid")->order("cot DESC")->limit($pape->firstRow . ',' . $pape->listRows)->select();

			$Infolist = array();
			foreach ($userlist as $key=>$val){
				$info = M("Users")->where(array('userid'=>$val['userid']))->field("userid,nickname,score")->find();
				$info['count']=$val['cot'];
				$info['np'] = M("UserOrder")->where(array('userid'=>$val['userid'],'state'=>0,'ifavalid'=>1))->count("ordernmb");
				$info['rp'] = M("UserOrder")->where(array('userid'=>$val['userid'],'state'=>1,'ifavalid'=>1))->count("ordernmb");
				$addressinfo = M("UserProfile")->where(array('userid'=>$val['userid']))->field("linkman,province,city,district,address")->find();
				$info['linkman'] = $addressinfo['linkman'];
				$info['address'] = $addressinfo['province'].$addressinfo['district'].$addressinfo['address'];

				array_push($Infolist,$info);
			}

			$this->assign('dinfo',$Infolist);
			$this->assign("tracepage",$pape->show());
		}else if($this->_post('userid')){

			$where = array(
			'userid'=>$this->_post('userid'),
			'optime'=>array(array('egt',strtotime($this->_post("start").' 00:00:00')),array('elt',strtotime($this->_post("end").' 23:59:59'),'AND'))
			);


			$list = M("UserBehaviourTrace")->where($where)->group("url")->order("count DESC,url ASC")->field("url,COUNT(url) as count")->select();
			echo json_encode($list);
			exit();
		}
		$this->display();
	}



	//api用户访问日志查询
	public function apiAccessList(){
		$api_access_mod=M("ApiAccessLog");

		if($_REQUEST['from'] && $_REQUEST['to']){
			$where["addtime"]=array(array('egt',strtotime($_REQUEST['from'].' 00:00:00')),array('elt',strtotime($_REQUEST['to'].' 23:59:59')),'AND');
		}else if($_REQUEST['from']){
			$where["addtime"]=array('egt',strtotime($_REQUEST['from'].' 00:00:00'));
		}else if($arguments['to']){
			$where["addtime"]=array('elt',strtotime($_REQUEST['to'].' 23:59:59'));
		}

		if($_REQUEST['ip'])
		{
			$where['ip']=$_REQUEST['ip'];
		}
		if(trim($_REQUEST['agent']))
		{
			$where['agent']=array('like', "%".trim($_REQUEST['agent'])."%");
		}
		if($_REQUEST['module'])
		$where['module'] = $_REQUEST['module'];
		if($_REQUEST['action'])
		$where['action'] = $_REQUEST['action'];
		$count=$api_access_mod->where($where)->count();
		import("@.ORG.Page");
		$p = new Page($count,20);
		$list=$api_access_mod->where($where)->order("addtime DESC")->limit($p->firstRow . ',' . $p->listRows)->select();

		$module_list=$api_access_mod->field("distinct module")->select();
		$action_list=$api_access_mod->field("distinct action")->select();
		$page=$p->show();
		$this->assign("list",$list);
		$this->assign("page",$page);
		$this->assign("module_list",$module_list);
		$this->assign("action_list",$action_list);
		$this->display();
	}

	//点击监测  面包屑,产品搜索
	//author  zhao
	function clickMonitor(){

		$click_mod = M("UrlClick");

		//根据时间查询 ?:通用
		if($this->_get('from')&& $this->_get('to')){
			$where['clicktime'] = array(array('egt',strtotime($this->_get('from').' 00:00:00')),array('elt',strtotime($this->_get('to').' 23:59:59')));
		}else if($this->_get('from')){
			$where['clicktime'] = array('egt',strtotime($this->_get('from').' 00:00:00'));
		}else if($this->_get('to')){
			$where['clicktime'] =array('elt',strtotime($this->_get('to').' 23:59:59'));
		}

		if($this->_get('type')){

			$where['type']=$this->_get('type');
			$datelist = $this->returnClickName($click_mod->where($where)->field("id,type,FROM_UNIXTIME( `clicktime`, '%Y年%m月%d日' )  AS dtime,COUNT('clicktime') cquantity ")->group("dtime,type")->order("dtime,id")->select());

		}else{
			$list =  $this->returnClickName($click_mod->where($where)->group("type")->field("id,type,COUNT('clicktime') as countclick")->order('id')->select());
		}

		$this->assign('ulist',$list);
		$this->assign('datelist',$datelist);
		$this->display();
	}

	function returnClickName($list){
		$namearray = array(
		1=>array(
		'home'=>'品牌/产品页导航条昵称点击',
		'bread'=>'品牌/产品页面包屑点击'
		),
		2=>array(
		'box_img'=>'个人首页-产品图片',
		'box_info'=>'个人首页-详情按钮',
		'box_buy'=>'个人首页-立即订购按钮'
		),
		3=>array(
		'index_activity'=>'个人首页-活动广场',
		'index_buy'=>'个人首页-订购萝莉盒',
		'index_beauty'=>'个人首页-美妆库',
		'index_buy'=>'个人首页-订购萝莉盒',
		'index_allplan'=>'个人首页-查看全部试用计划',
		'index_more'=>'个人首页-了解计划详情'
		),
		4=>array(
		'share_index'=>'个人中心-发表分享按钮',
		'share_beauty'=>'产品页-发表分享按钮',
		),
		5=>array('products_box'=>'产品页-萝莉盒试用','products_try'=>'产品页--试用中心'),
		6=>array('space_try'=>'他的主页-试用中心','space_sharedetail'=>'他/我的主页-分享详情')
		);
		foreach ($list as $key=>$value){
			$list[$key]['name'] = $namearray[$value['id']][$value['type']];
		}
		return $list;
	}

	//用户私信
	function UserPrivateLetter(){

		import("@.ORG.Page");

		if($this->_get('srh')){

			if($fid = $this->_get('fid')){
				$where['msg.from_uid']	= 	$fid;
			}

			if($this->_get('rid') || $this->_get('rid') === '0'){
				$where['msg.to_uid']	= 	$this->_get('rid');
			}

			if($cont = $this->_get('cont')){
				$where['msgd.content'] = array('LIKE','%'.$cont.'%');
			}


			if($this->_get('from') && $this->_get('to')){
				$where['msg.addtime'] = array(array('egt',strtotime($this->_get('from').' 00:00:00')),array('elt',strtotime($this->_get('to').' 23:59:59')),'AND');
			}else if($this->_get('from')){
				$where['msg.addtime'] = array('egt',strtotime($this->_get('from').' 00:00:00'));
			}else if($this->_get('to')){
				$where['msg.addtime'] = array('elt',strtotime($this->_get('to').' 23:59:59'));
			}
		}
		
		$count = M("Msg")->where($where)->join("msg_data as msgd ON  msg.dataid = msgd.id")->count("msgd.id");

		$p = new Page($count,20);

		$list = M("Msg")->where($where)->join("msg_data as msgd ON  msg.dataid = msgd.id")->field("msg.from_uid,msg.to_uid,msg.addtime,msg.dataid,msgd.id,msgd.message_id,msgd.content")->limit($p->firstRow . ',' . $p->listRows)->order('msg.addtime DESC')->select();

		foreach ($list as $key => $val){
			$list[$key]['fname'] = M("Users")->where(array('userid'=>$val['from_uid']))->getField('nickname');
			
			$list[$key]['tname'] = $val['to_uid'] === '0'?'全部':M("Users")->where(array('userid'=>$val['to_uid']))->getField('nickname');
		}

		$page=$p->show();
		$this->assign('mlist',$list);
		$this->assign('page',$page);
		$this->display();
	}
}
?>
