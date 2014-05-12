<?php

class UserShareAction extends CommonAction{

	/**
       +----------------------------------------------------------
       * 用户分享列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string  search  	  提交查询
       * @param  string  field		  修改某个字段的值	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.10
       */
	function index(){
		import("@.ORG.Page");
		import("ORG.Util.String");
		$share_mod = D("UserShare");
		if($this->_post('ps') == 'searchp'){
			$this->searchProdcutsName($this->_post('pname'));
		}
		if($this->_post('field')){
			$this->changeFieldValue(array_map('filterVar',$_POST));
		}
		if($this->_post('id')){
			$this->delShareInfo($this->_post('id'));
		}
		if($this->_get('transition')){
			$this->updateShareAndAtme($this->_get('transition'));
		}
		//排序
		if($this->_get('order')){
			if($this->_get('by') ==1){
				$order = $this->_get('order').' DESC';
			}else {
				$order = $this->_get('order').' ASC';
			}
		}else{
			$order = 'id DESC';
		}
			
		$where = $this->shareListWhere(array_map('filterVar',$_GET));
		$count = $share_mod->where($where)->count("id");
		$p = new Page($count,15);
		$share_list = $share_mod->getShareList($where,$p->firstRow.",".$p->listRows,$order,true);
		//分享列表
		$share_list = $this->returnShareList($share_list);
		//$this->outputShareList($share_list);exit;
		
		//分享权重值例表
		$status_list = D("UserShare")->DISTINCT(true)->field('status')->order('status')->select();
		$page = $p->show();
		$this->assign('page',$page);
		$this->assign('statuslist',$status_list);
		$this->assign('slist',$share_list);
		//2013-08-24 添加收录到盒子中
		$box_list=M("Box")->field("boxid,name")->order("boxid DESC")->select();
        $this->assign('BoxList',$box_list);
		$this->display();
	}
	
	
	/**
	 * 导出历史分享记录给解立明
	 * @author zhenghong@lolitabox.com
	 * 
	 */
	function exportUserShare(){
		import("@.ORG.Page");
		import("ORG.Util.String");
		$share_mod = D("UserShare");
		$order = 'id DESC';
		$current_page=$_GET["p"];
		$fn=$current_page.".xls";
		$st= mktime(0,0,0,10,1,2013);
		$et=mktime(0,0,0,10,31,2013);
		$where["posttime"] =array(array('egt',$st),array('elt',$et));
		$count = $share_mod->where($where)->count("id");
		//echo $share_mod->getLastSql();exit;
		$p = new Page($count,5000);
		if($current_page>17) {
			exit("生成完毕！");
		}
		$share_list = $share_mod->getShareList($where,$p->firstRow.",".$p->listRows,$order,true);
		//分享列表
		$share_list = $this->returnShareList($share_list);
		$this->outputShareList($share_list,$fn);
		echo($fn."----ok");
		$nextpage_no=$current_page+1;
		$nexturl=__ACTION__."/p/".$nextpage_no;
		header("location:$nexturl");
	}

	public function outputShareList($list,$savefilename){
		$num=count($list);
		$user_share_out_mod=D("UserShareOut");
		import ( "@.ORG.Util.PHPExcel.PHPExcel" ); // 导入PHPEXCEL类库
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("Lolitabox后台管理员")
		->setLastModifiedBy("Lolitabox后台管理员")
		->setTitle("Office 2007 XLSX Test Document")
		->setSubject("Office 2007 XLSX Test Document")
		->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Test result file");
		
        //设置列宽
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(8);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(8);
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '分享ID')
		->setCellValue('B1', '用户ID')
		->setCellValue('C1', '赞数')
		->setCellValue('D1', '踩数')
		->setCellValue('E1', '评论数')
		->setCellValue('F1', '转发数')
		->setCellValue('G1', '转发用户')
		->setCellValue('H1', '目标类型')
		->setCellValue('I1', '目标ID')
		->setCellValue('J1', '是否收录')
		->setCellValue('K1', '终端类型')
		->setCellValue('L1', '分享时间')
		->setCellValue('M1', '正负')
		->setCellValue('N1', '是否为试用用户');
		for ($i=0; $i < $num; $i++) {
			//整理转发分享的用户列表
			$userid_list=array();
			$out_userid="";
			if($list[$i]['outnum']>0) {
				$shareout_userlist=$user_share_out_mod->Distinct(true)->field('userid')->where("shareid=".$list[$i]['id'])->select();
				foreach($shareout_userlist as $userinfo){
					$userid_list[]=$userinfo["userid"];
				}
				$out_userid=implode(",",$userid_list);
			}
			$n=$i+2;
			$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$n, $list[$i]['id'])
			->setCellValue('B'.$n, $list[$i]['userid'])
			->setCellValue('C'.$n, $list[$i]['agreenum'])
			->setCellValue('D'.$n, $list[$i]['treadnum'])
			->setCellValue('E'.$n, $list[$i]['commentnum'])
			->setCellValue('F'.$n, $list[$i]['outnum'])
			->setCellValue('G'.$n, $out_userid)
			->setCellValue('H'.$n, $list[$i]['resourcetype'])
			->setCellValue('I'.$n, $list[$i]['resourceid'])
			->setCellValue('J'.$n, $list[$i]['pick_status']?"收录":"未收录")
			->setCellValue('K'.$n, $list[$i]['clienttype'])
			->setCellValue('L'.$n, date("Y-m-d H:i:s",$list[$i]['posttime']))
			->setCellValue('M'.$n, $list[$i]['pk'])
			->setCellValue('N'.$n, $list[$i]['try']['orderid']?'是':'否');
		}
		$objPHPExcel->getActiveSheet()->setTitle('用户分享统计数据');
		$objPHPExcel->setActiveSheetIndex(0);
		spl_autoload_register(array('Think','autoload'));
		
		if($savefilename) {
			//生成本地文件
			$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);  
			$objWriter->save($savefilename);
		}
		else {
			//浏览器输出
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="文件名.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
	}
	
	/**
       +----------------------------------------------------------
       * 批量转换分享,@正负面 只把中性的转换为正
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string  id	  	产品ID	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.6.7
       */
	private function updateShareAndAtme($id){
		M("UserShare")->where(array('resourcetype'=>1,'resourceid'=>$id,'pk'=>0))->setField("pk",1);
		$this->success("转换成功!");
		exit();
	}

	/**
	 * 整理分享列表数据
	 * @param unknown_type $list
	 */
	private function returnShareList($list){
		foreach($list as $key =>$val){
			//如果资源判断为产品，需要判断用户是否试用过这款产品
			if($val['resourcetype'] ==1){
				$map = array(
				'userid'=>$val['userid'],
				'productid'=>array('exp',"IN(SELECT id FROM `inventory_item` WHERE relation_id= {$val['resourceid']})")
				);
				$list[$key]['try']=M("UserOrderSendProductdetail")->where($map)->field('orderid')->find(); //返回是否试用的记录
				$list[$key]['resource_title']=M("Products")->getFieldByPid($val['resourceid'],"pname"); //返回产品名称
			}
			if($val['resourcetype'] ==4){
				$list[$key]['resource_title']=M("Box")->getFieldByBoxid($val['boxid'],"name"); //返回盒子名称
			}			
			//返回该分享评论列表 
			//$list[$key]['replylist'] = $this ->returnReplyList($val['id']);
			//列表分享内容
			$list[$key]['settlecontent'] =preg_replace("/[\s]+/", "",String::msubstr(strip_tags($list[$key]['content']),0,500));
			//返回收录列表【不同类型】 STOP BY 2013-08-27
			//$list[$key]['shoulu'] = $this->returnSingleAtmeList($val['id']);
			//内容序列化整理
			if($val['details']){
				foreach ($val['details'] as $k=>$v){
					$list[$key]['details'][$k]['settlecontent'] = preg_replace("/[\s]+/", "",String::msubstr(strip_tags($v['content']),0,280));
				}
			}
			//用户的分享总数
			$list[$key]['blog_num']=M("Users")->where(array('userid'=>$val['userid']))->getField("blog_num");
			//用户对相同资源的分享数，用于判断用户对分享的历史，便于收录管理
			$list[$key]['resource_same_num']=D("UserShare")->getShareListBySameType($val["userid"],$val['resourcetype'],$val['resourceid'],"count");
        }
		return $list;
	}



	//返回产品名
	private function getProductsName($id){
		return M("products")->where('pid='.$id)->getField('pname');
	}

	//返回品牌名
	private function getBrandName($id){
		return M("productsBrand")->where('id='.$id)->getField('name');
	}


	/**
	 * 获取资源名称
	 * @param unknown_type $id
	 * @param unknown_type $type
	 */
	private function getName($id,$type,$boxid=""){
		if(empty($id))
		return false;
		switch($type){
			case 1:
				$name = $this->getProductsName($id);
				$url = getProductUrl($id);
				break;
			case 4:
				$name = M("Box")->where("boxid=".$boxid)->getField("name");
				$url = getBoxUrl($boxid);
				break;
			default:
				return false;
		}
		return array("name"=>$name,"url"=>$url);
		return false;
	}

	/**
	 * 修改收录状态[取消收录]
	 * @author litingting
	 */
	public  function changeStatus(){
		$id = $_POST['id'];
		if(empty($id)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		$share_mod = M('UserShare');
		$info = $share_mod->getById($id);
		if(empty($info)){
			$this->ajaxReturn(1,"没有此条分享",0);
		}
		if(false!==$share_mod->where("id=".$id)->setField("pick_status",0)){
			if($info['resourcetype']==4){
				D("UserCreditStat")->optCreditSet($info['userid'],"user_show_box_cancel");
			}else if($info['resourcetype']==1){
				M("TaskStat")->where(" taskid=9 AND relationid={$id}")->setField("status",3);    
				D("UserCreditStat")->optCreditSet($info['userid'],"user_share_unpick");
			}
			$this->ajaxReturn(1,"修改成功",1);
		}
		$this->ajaxReturn(1,"修改失败",1);
		
	}

	/**
	 * 执行指定收录的操作
	 * 
	 */
	public  function addShareData(){
		$array_where["id"]= $_POST["addshareid"];
		//V5后台收录新增 START
		if( $_POST["boxid"]){
			//当前收录是将分享收录到某个指定的盒子中
			$array_data["boxid"]= $_POST["boxid"];
			$array_data["resourcetype"]=4;
			$array_data["resourceid"]=0;
		}
		$array_data["pick_status"]=1;
		M("UserShare")->where($array_where)->save($array_data);     //收录
		$credit_mod=D("UserCreditStat");
		$shareinfo=D("UserShare")->getShareInfo($_POST["addshareid"]); //获取分享数据
		$userid=$shareinfo['userid'];
		$sourceid = $shareinfo['resourceid'];
		$sourcetype = $shareinfo['resourcetype'];
		if($sourcetype==4){
			//$time = time();
			//M("TaskStat")->execute("REPLACE INTO task_stat(userid,taskid,addtime,status)  values({$userid},8,{$time},1)");           //加入任务已完成列表
			$credit_mod->optCreditSet($userid,"user_show_box");
		}
		else if($sourcetype==1){
			M("TaskStat")->where("userid={$userid} AND taskid=9 AND relationid={$shareinfo['id']}")->setField("status",1);
			D("Msg")->addMsgByCollect($shareinfo['userid'],$shareinfo['id']); //发私信
			$if_task=D("Task")->inTaskByProductID($sourceid);
			if($if_task==false){
				$credit_mod->optCreditSet($userid,"user_share_pick");
			}else{
				$credit_mod->optCreditSet($userid,"assign_post_share");
			}
		}
		
		//分享自动推荐到首页update by penglele 2013-11-7 10:48:05
		if($sourcetype==1 || $sourcetype==4){
			if($_POST['to_index'] && $_POST['to_index']==1){
				$data['title']="首页晒单";
				$data['orig']=$_POST["addshareid"];
				$data['status']=1;
				$data['cate_id']=734;
				$data['add_time']=date("Y-m-d H:i:s");
				$rel=M("Article")->add($data);
				if($rel!=false){
					if($sourcetype==1){
						$content="您发表的“".$shareinfo['boxname']."”试用分享非常精彩，被推荐到了萝莉盒首页";
						$score=20;
					}else if($sourcetype==4){
						$content="您发表的“".$shareinfo['boxname']."”晒盒分享非常精彩，被推荐到了萝莉盒首页";
						$score=30;
					}
					$credit_mod->addUserCreditStat($userid,$content,$score,30);
				}
			}			
		}
		
		$this->success("收录成功!");
	}


	/**
       +----------------------------------------------------------
       * 修改分享正负面属性
       +----------------------------------------------------------
       * @access public  单独节点
       +----------------------------------------------------------
       * @param  array POST        调用公共私有修改字段值的方法
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.5.15
       */
	public	function changeSharePK(){
		$this->changeFieldValue($_POST);
	}

	/**
       +----------------------------------------------------------
       * 更新某字段的值(私有)
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string  shareid	  分享ID
       * @param  string  field		  数据库某个字段
       * @param  string  val	  	  要替换的值   	  
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.10
       * update  zhaoxiang 2013.5.10 
       * 	修改有效状态的时候 同时更新atme表level字段值
       */
	private function changeFieldValue($param){
		$data = array(
		'id'=>$param['shareid'],
		"{$param['field']}"=>$param['val']
		);

		//5-10
		if($param['field'] == 'status'){
			M("userAtme")->where(array('relationid'=>$param['shareid'],"relationtype" =>1))->setField("level",$param['val']);
		}else if($param['field'] == 'pk'){
			M("userAtme")->where(array('relationid'=>$param['shareid'],"relationtype" =>1))->setField("pk",$param['val']);
		}

		if(M('UserShare')->save($data)){
			$this->ajaxReturn($param['val'],'更新成功!',1);
		}else{
			$this->ajaxReturn(0,'更新失败!',0);
		}
		exit();
	}



	private function delShareInfo($id_array){
		if(empty($id_array)){
			$this->error("请选择删除信息,再提交!");
		}else{
			$rn = 0;
			foreach ($id_array AS $k => $v){

				if(!M('UserShare')->where(array('id'=>$v))->setField('status',0)){
					$rn+=1;
				}
			}

			if($rn){
				$this->error("删除失败");
			}else{
				$this->success("恭喜您,删除成功!");
			}
		}
		exit();
	}

	/**
       +----------------------------------------------------------
       * 返回where查询条件
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string  arguments	  $_GET['search']提交的查询参数
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.10
       */	
	private function shareListWhere($arguments){

		if($arguments['userid']){
			$where['userid'] = $arguments['userid'];
		}

		if($arguments['nickname']){
			$where['userid'] = M("Users")->where(array('nickname'=>$arguments['nickname']))->getField('userid');
		}

		if($arguments['iscommend']){
			$where['iscommend'] =(int) $arguments['iscommend']-1;
		}

		if($arguments['shareid']){
			$where['id'] = $arguments['shareid'];
		}

		if($arguments['keywords']){
			$where['id']=array("exp","IN(SELECT `shareid` FROM `user_share_data` WHERE `content` LIKE '%".$arguments['keywords']."%' OR `details` LIKE '%".$arguments['keywords']."%')");
		}


		if($arguments['sharetype'] || $arguments['sharetype'] === '0'){
			$where['sharetype'] = $arguments['sharetype'];
		}

		if($arguments['check'] || $arguments['check'] === '0'){
			$where['ischeck'] = $arguments['check'];
		}

		if($arguments['status'] || $arguments['status'] === '0'){
			$where['status'] = $arguments['status'];
		}

		if($arguments['is_mobile'] || $arguments['is_mobile'] === '0'){
			$where['clienttype'] = $arguments['is_mobile'];
		}

		//分享正负面属性
		if($arguments['selectpk'] === '0'){
			$where['pk'] = 0;
		}else if($arguments['selectpk']){
			$where['pk'] = $arguments['selectpk'];
		}

		//是否置顶
		if($arguments['istoptime'] === '0'){
			$where['toptime']= 0;
		}else if($arguments['istoptime'] == 1){
			$where['toptime']= array("gt",0);
		}

		//资源类型
		if($arguments['resourcetype']){
			$where['resourcetype'] =$arguments['resourcetype'];
			//资源id
			if($arguments['resourceid']){
				$where['resourceid'] =$arguments['resourceid'];
			}
		}


		//收录状态
		if($arguments['pick_status']!="") {
			switch ($arguments['pick_status']) {
				case 0:
					$where['pick_status']=$arguments['pick_status'];
					break;
				case 1:
					$where['pick_status']=$arguments['pick_status'];
					break;
			}
		}
		
		//发表时间
		if($arguments['from'] && $arguments['to']){
			$where["posttime"]=array(array('egt',strtotime($arguments['from'].' 00:00:00')),array('elt',strtotime($arguments['to'].' 23:59:59')),'AND');
		}else if($arguments['from']){
			$where["posttime"]=array('egt',strtotime($arguments['from'].' 00:00:00'));
		}else if($arguments['to']){
			$where["posttime"]=array('elt',strtotime($arguments['to'].' 23:59:59'));
		}

		//用户类型
		if($arguments['usertype'] =='interior'){
			$where['userid']=array("exp",'IN(SELECT userid FROM `users` WHERE  usermail LIKE "nbceshi%lolitabox.com" OR usermail LIKE "pingce%lolitabox.com")');
		}else if($arguments['usertype'] =='real'){
			$where['userid']=array("exp",'NOT IN(SELECT userid FROM `users` WHERE  usermail LIKE "nbceshi%lolitabox.com" OR usermail LIKE "pingce%lolitabox.com")');
		}
		return $where;
		
		
	}


	/**
       +----------------------------------------------------------
       * 返回回复该分享的列表
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string  	shareid	  分享ID
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.10
       */		
	private function returnReplyList($shareid){

		$replylist= D("UserShare")->getCommentListByShareid($shareid,'');

		$str = '';

		if($replylist){
			foreach ($replylist as $ck => $cv){
				$str .= "<p><font color='#0080C0'>(".$cv['userid'].')'.$cv['nickname'].'</font>'.' 回复 ';
				$str .= "<font color='#8000FF'>(".$cv['to_uid'].')'.$cv['to_nickname'].'</font> : '.$cv['content'];
				$str .= "&nbsp;&nbsp;&nbsp;&nbsp;<font color='#8080C0' size='3'>".date('Y-m-d H:i:s',$cv['posttime']).'</font>';
				$str.='&nbsp;&nbsp;&nbsp;<a style="color:red" href="javascript:void(0)" onclick="delcomment('.$cv['id'].','.$cv['shareid'].',this)">删除该评论</a></p><hr>';
			}
		}
		return $str;
	}


	/**
       +----------------------------------------------------------
       * 返回用户的昵称
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string  	userid    用户ID
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.10
       */		
	private function returnUserNickName($userid){
		return M('Users')->where(array('userid'=>$userid))->getField('nickname');
	}


	//搜索产品名称,ID
	private   function searchProdcutsName($pname){
		$result = M('products')->where(array('pname'=>array('LIKE','%'.$pname.'%')))->field('pid,pname')->limit("0,20")->select();
		if($result){
			$this->ajaxReturn($result,'查询成功!',1);
		}else{
			$this->ajaxReturn(0,'查询失败!',0);
		}
		exit();
	}

	/**
	 * 分享任务列表
	 */
	public function shareTask(){

		if($_REQUEST['ac']=='create'){      //创建任务
			$this->createShareTask($_POST);
		}

		if($_REQUEST['ac']=='display_create'){  //打开创建任务模板
			$this->display("create");
			die;
		}

		if($_REQUEST['ac']=='del'){       //删除任务
			$this->delShareTask($_GET['id']);
		}

		if($_REQUEST['ac'] =='display_edit'){
			$this->editTaskDisplay($_GET['id']);    //打开编辑任务模板
		}

		if($_REQUEST['ac'] =='edit'){    //编辑任务
			$this->editShareTask($_POST);
		}

		if($_REQUEST['ac']=='find'){     //通过ID查找用户
			$this->getNickname();
		}

		if($_REQUEST['ac']=='addAgreeTask'){
			$this->addAgreeTask();
		}

		$share_task = M("shareTask");
		if($_REQUEST['status'] ){
			$where['status'] = $_REQUEST['status']-1;
		}
		if(trim($_REQUEST['from']) && trim($_REQUEST['to'])){
			$where['sendtime']= array("exp",'BETWEEN '.strtotime(trim($_REQUEST['from']))." AND ".strtotime(trim($_REQUEST['to'])));

		}elseif(trim($_REQUEST['from'])){
			$where['sendtime'] = array("elt",strtotime(trim($_REQUEST['from'])));
		}elseif(trim($_REQUEST['to'])){
			$where['sendtime'] = array("egt",strtotime(trim($_REQUEST['to'])));
		}

		if($_REQUEST['userid']){
			$where['userid'] = $_REQUEST['userid'];
		}

		import("ORG.Util.Page");

		$count = $share_task->where($where)->count("id");

		$p = new Page($count,10);

		$task = $share_task->where($where)->limit($p->firstRow.",".$p->listRows)->order("id DESC")->select();
		//var_dump($task);die;
		$user_mod = M("Users");
		foreach($task as $key=>$val){
			$task[$key]['data'] = unserialize($val['data']);
			$task[$key]['data']['img'] = str_replace("//","/","/".$task[$key]['data']['img']);
			$task[$key]['nickname'] = $user_mod ->where("userid=".$val['userid'])->getField("nickname");
		}
		$page = $p->show();
		$this->assign("page",$page);
		$this->assign("tasklist",$task);
		$this->display();
	}

	/**
	 * 创建任务
	 * @param unknown_type $data
	 */
	private function createShareTask($data){
		if(empty($data['userid'])){
			$this->ajaxReturn(0,"用户ID不能为空",0);
		}

		if(empty($data['content'])){
			$this->ajaxReturn(0,"内容不能为空",0);
		}

		if(empty($data['sendtime'])){
			$this->ajaxReturn(0,"定时时间不能为空",0);
		}

		$add['userid'] = $data['userid'];
		$add['sendtime'] = strtotime($data['sendtime']);
		if($data['resourceid'] && $data['resourcetype']){
			$seri['resourceid'] = $data['resourceid'];
			$seri['resourcetype'] = $data['resourcetype'];
		}else{
			$seri['resourceid']=0;
			$seri['resourcetype']=0;
		}
		$seri['content'] = $data['content'];
		$seri['img'] = trim($data['img']);
		$add['data'] = serialize($seri);
		if(M("ShareTask")->add($add)){
			$this->ajaxReturn(0,"操作成功",1);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	}

	/**
    * 编辑任务
    * @param unknown_type $data
    */
	private function editShareTask($data){
		$id = $data['taskid'];
		if(empty($id)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		if(empty($data['userid'])){
			$this->ajaxReturn(0,"用户ID不能为空",0);
		}

		if(empty($data['content'])){
			$this->ajaxReturn(0,"内容不能为空",0);
		}

		if(empty($data['sendtime'])){
			$this->ajaxReturn(0,"定时时间不能为空",0);
		}

		$add['userid'] = $data['userid'];
		$add['sendtime'] = strtotime($data['sendtime']);
		if($data['resourceid'] && $data['resourcetype']){
			$seri['resourceid'] = $data['resourceid'];
			$seri['resourcetype'] = $data['resourcetype'];
		}else{
			$seri['resourceid']=0;
			$seri['resourcetype']=0;
		}
		$seri['content'] = $data['content'];
		$seri['img'] = trim($data['img']);
		$add['data'] = serialize($seri);
		if(false!==M("ShareTask")->where("id=".$id)->save($add)){
			$this->ajaxReturn(0,"操作成功",1);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	}

	/**
	 * 显示编辑任务模板
	 * @param int $id
	 */
	private function editTaskDisplay($id){
		if($id){
			$info = M("ShareTask")->getById($id);
			if($info){
				$info['data'] = unserialize($info['data']);
				$this->assign("info",$info);
			}else{
				echo "记录不存在";
				die;
			}
		}else{
			echo "缺少参数";
			die;
		}
		$this->assign("ac","edit");
		$this->display("create");
		die;
	}


	/**
	 * 缺少参数
	 * @param unknown_type $id
	 */
	private function delShareTask($id){
		if(empty($id)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		if(M("ShareTask")->delete($id)){
			$this->ajaxReturn(0,"删除成功",1);
		}else{
			$this->ajaxReturn(0,"删除失败",0);
		}
	}

	/**
	 * (通过ID获取昵称，兼容用户，产品，品牌
	 * @see commonAction::getNickname()
	 */
	public function getNickname(){
		if($_REQUEST['userid'] && $_REQUEST['type']){
			$type = $_REQUEST['type'];
			$userid = $_REQUEST['userid'];
			switch ($type){
				case 1:
					$nickname=M("Users")->where("userid=".$userid)->getField("nickname");
					break;
				case 2:
					$nickname = $this->getProductsName($userid);
					break;
			}
			if(empty($nickname)){
				$nickname = "记录不存在";
			}
			echo $nickname;
			exit();
		}
	}

	//修改分享置顶状态
	function changeShareTopTime(){

		$val = $this->_post('status') != 0?time():0;

		$res = M("UserAtme")->where(array('relationid'=>$this->_post('shareid')))->setField("level",$val);
		$result = M("UserShare")->where(array('id'=>$this->_post("shareid")))->setField("toptime",$val);

		if($result && $res){
			$this->ajaxReturn($val,'修改成功!',1);
		}else{
			if(empty($res) || empty($result)){
				$tips = "操作失败,请刷新页面从新操作!";
			}else{
				$tips = "修改失败!";
			}
			$this->ajaxReturn(0,$tips,0);
		}
	}

	/**
	 * 加赞任务
	 */
	public function addAgreeTask(){
		$endtime=strtotime($_POST['endtime']);
		if(empty($endtime) || empty( $_POST['shareid']) || empty($_POST['totalnum'])){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		$data['endtime'] = strtotime($_POST['endtime']);
		$data['shareid'] = $_POST['shareid'];
		$data['addtime'] = time();
		$data['totalnum'] = $_POST['totalnum'];
		$data['unfinished'] = $_POST['totalnum'];
		if(M("AgreeTask")->add($data)){
			$this->ajaxReturn(1,"成功",1);
		}
		$this->ajaxReturn(0,"失败",0);
	}

	/**
       +----------------------------------------------------------
       * 分享数据统计
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string  search  	  提交查询
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.5.23
       */	
	function shareDataStatistics(){
		import("@.ORG.Page"); //导入分页类库


		if($this->_post('query')){
			if($this->_post('query') == 'reply_list'){
				$this->rReplyList();
			}else{
				$this->showQueryList();
			}
		}

		if($this->_get('search')){
			$where = $this->selectWhereShareData(array_map('filterVar',$_GET));
		}

		$tip = $where['tip'];
		unset($where['tip']);

		$sharestat_mod = M("UserShareStat");

		$count = $sharestat_mod->where($where)->count('id');

		$p = new Page($count, 20);

		//排序
		if($this->_get('order')){
			if($this->_get('by') ==1){
				$order = $this->_get('order').' DESC';
			}else {
				$order = $this->_get('order').' ASC';
			}
		}else{
			$order = 'id DESC';
		}


		$list = $sharestat_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();

		$total_list=$sharestat_mod->field("MAX( stat_date )  as maxtime ,MIN( stat_date )  as mintime ,SUM( inner_post_num ) as tipn, SUM( real_post_num ) as trpn, SUM( real_user_num ) as trun, SUM( reply_num ) as trn , SUM( reply_user_num ) as trunn, SUM( post_total ) as tpt, SUM( user_total) as tut")->where($where)->find();

		$page = $p->show();
		$total_list['tip'] = $tip;
		$this->assign("total",$total_list);
		$this->assign("page",$page);
		$this->assign('list',$list);
		$this->display();
	}


	//分享统计查询条件
	private function selectWhereShareData($arguments){
		if($arguments['starttime'] && $arguments['endtime']){
			$where['stat_date']	= array(array('egt',$arguments['starttime']),array('elt',$arguments['endtime']),'AND');
			$where['tip']=$arguments['starttime'].'到'.$arguments['endtime'].'之间';
		}else if(empty($arguments['starttime']) && $arguments['endtime']){
			$where['stat_date']	= array('elt',$arguments['endtime']);
			$where['tip']=$arguments['endtime'].'之前';
		}else if($arguments['starttime'] &&empty($arguments['endtime'])){
			$where['stat_date']	= array('egt',$arguments['starttime']);
			$where['tip']=$arguments['starttime'].'之后';
		}
		return $where;
	}


	//返回分享统计查询的用户名和用户id
	//zhao
	private function showQueryList(){
		$user_share_mod = M ( "UserShare" );
		$user_mod = M("Users");

		$from = strtotime($this->_post('time'));
		$to = strtotime($this->_post('time')."23:59:59");
		if($this->_post('query') == 'real_total'){
			//"真实参与发布分享人数
			$list = $user_share_mod->query ( "SELECT DISTINCT u.userid as userid FROM user_share pe,users u WHERE pe.userid=u.userid AND (u.usermail  NOT LIKE 'nbceshi%lolitabox.com' AND u.usermail NOT LIKE 'pingce%lolitabox.com') AND  posttime BETWEEN {$from} AND {$to} ORDER BY userid");
		}else if(
		$this->_post('query') == 'reply'){
			//"评论分享用户数
			$list = $user_share_mod->query ( "SELECT distinct userid as userid FROM user_share_comment WHERE posttime BETWEEN {$from} AND {$to} order by userid" );
		}else if($this->_post('query') == 'user_total'){
			$list = $user_share_mod->where(array('posttime'=>array('between',array($from,$to))))->field('DISTINCT(userid)')->order("userid")->select();
		}

		$reply = array();
		foreach ($list as $k=>$v){
			$reply[] = $v['userid'];
		}

		$json_list = $user_mod->where(array('userid'=>array('IN',implode(',',$reply))))->field("nickname,userid")->select();
		echo json_encode($json_list);
		exit();
	}

	//分享评论数
	//zhao
	private function rReplyList(){

		$from = strtotime($this->_post('time'));
		$to = strtotime($this->_post('time')."23:59:59");

		$list = M("userShareComment")->where(array('posttime'=>array('between',array($from,$to))))->field("id,shareid,userid,content,posttime")->select();

		foreach ($list as $key =>$val){
			$list[$key]['nickname'] =M("Users")->where(array("userid"=>$val['userid']))->getField("nickname");
			$list[$key]['originaltitle'] = M("UserShareData")->where(array("shareid"=>$val['shareid']))->getField("content");
		}

		$this->assign('rcount',count($list));
		$this->assign("rlist",$list);
		echo $this->fetch("dateUsersReplyList");
		exit();
	}


	/**
       +----------------------------------------------------------
       * 删除该分享的一条记录
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string  cid    	  评论ID	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.7.25
       */
	function delcomment(){
		if($this->_post("cid")){
			if(false === M("UserShareComment")->delete($this->_post("cid"))){
				$this->ajaxReturn(0,'删除失败!',0);
			}else{
				M('UserShare')->where(array('id'=>$this->_post('shareid')))->setDec('commentnum'); 
				$this->ajaxReturn(1,'删除成功!',1);
			}
		}else{
			$this->ajaxReturn(0,'参数不全!',0);
		}
	}

	
	
	
	
	/**
	 *share_sametype
	 *@param userid 用户ID
	 *@param resourcetype 资源类型 
	 *@param $resourceid 资源ID
	 *@param $returnType 返回类型（"count"=>返回相同类型的分享数，"list"=>返回相同类型的分享记录集）
	 * 为了运营更好的对内容进行收录
	 * 显示同一类资源分享数及分享列表
	 * 对于某一条分享，无论是基于产品，基于订单（盒子），需要将同一ID的同一类型分享调取出来，供运营人员参照
	 * @author zhenghong 2013-08-24
	 */
	public function share_sametype(){
		//$userid,$resourcetype,$resourceid,$returnType="list"
		print_r(D("UserShare")->getShareListBySameType(214,1,34,"list"));
	}
	
}
?>
