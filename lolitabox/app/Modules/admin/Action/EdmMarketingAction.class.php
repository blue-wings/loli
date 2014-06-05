<?php

class EdmMarketingAction extends CommonAction{

	/**
       +----------------------------------------------------------
       * index  			EDM邮件相关操作(增删改查)
       +----------------------------------------------------------
       * @access public
       +-----------------------------------------------------------
       * @author zhaoxiang
       */	
	function index(){
		$index_mod=M("EdmIndex");
		$stat_mod=M("EdmStat");
		import("@.ORG.Page");

		//添加新EDM
		if($this->_post('action') == 'cdata'){
			$data=array(
			'name'=>trim($this->_post('martek')),
			'tourl'=>trim($this->_post('tourl')),
			'c_datetime'=>date('Y-m-d H:i:s',time())
			);

			$this->edmOperate($data,'add');

		}else if($this->_post('action') == 'save'){    //修改

			$savedata = array(
			'edm_no'=>$this->_post('eid'),
			'name'=>$this->_post('martek'),
			'tourl'=>$this->_post('tourl')
			);

			$this->edmOperate($savedata,'save');

		}else if($this->_get('action') == 'delete'){   //删除

			$this->edmOperate($this->get('eid'),'delete');

		}else if($this->_get('id') || $this->_get("queryid")){

			$queryid = $this->_get('id')?$this->_get('id'):$this->_get('queryid');

			$where['eid'] = $queryid;

			if($this->_get("queryid")){
				$start = $this->_get("from");
				$end   = $this->_get("to");

				if($start && $end){
					$where['etime'] = array(array('egt',$start),array('elt',$end),'AND');
				}else if(empty($start) && $end){
					$where['etime'] = array('elt',$end);
				}else if($start &&empty($end)){
					$where['etime'] = array('egt',$start);
				}
			}

			$list = M("EdmStatYmd")->where($where)->order("etime DESC")->select();

			$edm=$index_mod->where(array('edm_no'=>$queryid))->find();

			$url=$this->edmUrl($edm['edm_no'],$edm['tourl']);
			$arr=explode('aaa',$url);
			$edm['email']=$arr[0];
			$edm['img']=$arr[1];
			$edm['edm_no']=$queryid;
			$this->assign('message',$edm);
		}else{
			$where['name']=array('LIKE',"%".trim($this->_post('emailname'))."%");

			$list=$index_mod->where($where)->order('edm_no DESC')->select();
		}

		$this->assign('list',$list);
		$this->display();
	}


	//封装CURD
	private function edmOperate($data,$action){

		$index_mod=M("EdmIndex");

		if($action == 'add'){
			$result = $index_mod->add($data);
		}else if($action == 'save'){
			$result = $index_mod->save($data);
		}else{
			$result=$index_mod->where(array('edm_no'=>$data))->delete();
		}
	
		if($result !== false){
			$this->success("操作成功!");
		}else{
			$this->error("操作失败,请检查问题!");
		}
		exit();
	}


	/**
       +----------------------------------------------------------
       * 统计每月edm的信息   先清空数据库,再从新添加数据,
       * 这样就不需要先查询,设条件,再更新了,速度会更快,本地测试大约10s
       +----------------------------------------------------------
       * @access public
       +-----------------------------------------------------------
       * @author zhaoxiang
       * update 2013.5.30
       */		
	public function updateEDMData(){

		$index_mod = M("EdmIndex");
		$edm_list = $index_mod->order("edm_no ASC")->field("edm_no")->select();

		M("EdmStatYmd")->query("TRUNCATE TABLE  `edm_stat_ymd`");

		foreach ($edm_list as $key => $value){

			$this->insertStatisticalData($value['edm_no']);

			//统计总数 更新 edm_index
			$this->collectDataToIndexEDM($value['edm_no']);

		}
		$this->success("更新成功!");
	}

	/**
       +----------------------------------------------------------
       * 根据where条件查询插入edm_statistical_data
       +----------------------------------------------------------
       * @access private
       +-----------------------------------------------------------
       + @access $where  查询条件
       +-----------------------------------------------------------
       * @author zhaoxiang
       * 2013.5.24
       */	
	private function insertStatisticalData($enum){

		$stat_mod=M("EdmStat");
		$day_mod =M("EdmStatYmd");

		$where['edm_no'] = $enum;
		$where['type'] = 1;

		$showAmount = $stat_mod->where($where)->field("COUNT( DISTINCT ipaddress ) as uv,COUNT( ipaddress ) as pv,date( c_datetime ) AS dtime")->group("dtime")->order("dtime DESC")->select();

		$where['type'] = 2;
		$clickAmount=$stat_mod->where($where)->field("COUNT( DISTINCT ipaddress ) as unique_hits,COUNT( ipaddress ) as hits,date( c_datetime ) AS dtime")->group('dtime')->order('dtime DESC')->select();


		//确定循环哪个数组
		if(count($showAmount) > count($clickAmount)){
			$marray = $showAmount;
			$sarray = $clickAmount;
		}else{
			$marray = $clickAmount;
			$sarray = $showAmount;
		}

		//确定字段
		$key_arr = !empty($sarray[0])?array_keys($sarray[0]):'';

		foreach ($marray as $keys => $val){
			if($sarray){
				foreach ($sarray as $ck => $cv){
					//如果相等,组装数据,然后$sarray里面对应的的数据删除
					if($cv['dtime'] == $val['dtime']){
						$val[$key_arr[0]]=$cv[$key_arr[0]];
						$val[$key_arr[1]]=$cv[$key_arr[1]];
						unset($sarray[$ck]);
						break;
					}
				}
			}
			$val['eid'] = $where['edm_no'];
			$val['etime']=$val['dtime'];
			unset($val['dtime']);
			$day_mod->add($val);
		}
	}


	/**
       +----------------------------------------------------------
       * 按照edm_no统计总和 插入index
       +----------------------------------------------------------
       * @access private
       +-----------------------------------------------------------
       + @access $enum  EDMid
       +-----------------------------------------------------------
       * @author zhaoxiang
       * 2013.5.24
       */
	private function  collectDataToIndexEDM($enum){

		$data = M("EdmStatYmd")->where(array('eid'=>$enum))->field("SUM(`pv`) as pv,SUM(`uv`) as uv,SUM(`hits`) as click,SUM(`unique_hits`) as unique_click")->find();

		$data['click_rate']=  empty($data['pv'])?0:round(($data['click'] / $data['pv']),4)*100;
		$data['unique_click_rate']= empty($data['uv'])?0:round(($data['unique_click'] / $data['uv']),4)*100;

		return M("EdmIndex")->where(array('edm_no'=>$enum))->save($data);
	}


	/**
       +----------------------------------------------------------
       * edmUrl  				邮箱和图片的链接地址
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string   num   编号
       * @param  string   url 	URL地址
       +-----------------------------------------------------------
       * @author zhaoxiang
       */	
	private function edmUrl($num,$url){
		return 'http://www.lolitabox.com/public/eclick/n/'.$num.'/s/'.base64_encode($url).'aaa'."<img src='http://www.lolitabox.com/public/imgload/n/".$num."'>";
	}


	/**
       +----------------------------------------------------------
       * 推广用户管理	     增删改查
       +----------------------------------------------------------
       * @access 	publib
       +----------------------------------------------------------
       * @param  
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.24
       */		
	public function promotion(){
		$pro_mod=D("promotion");

		if($this->_post('getpromotiondata') && $this->isAjax()){
			$codelist = $pro_mod->field('code,name')->select();
			if(empty($codelist)){
				$this->ajaxReturn(0,'数据有误',0);
			}else{
				$this->ajaxReturn($codelist,'返回成功!',1);
			}
			exit();
		}
		if($this->_post('submit')){

			$pro_mod->create();

			if($this->_post('status')=='add'){
				if($pro_mod->add()){$this->success('添加成功!');}else{$this->error('添加失败!');}
			}else{
				if($pro_mod->save()){$this->success('更新成功!');	}else{$this->error('更新失败!');}
			}
			exit();
		}else if($_POST['action']=='getproinfo'){
			$result=$pro_mod->where(array('code'=>floatval($_POST['code'])))->find();
			if($result){$this->ajaxReturn($result,'查询成功!',1);}else{$this->ajaxReturn(0,'查询失败,请检查参数!',0);}
			exit();
		}else if($this->_get('action')=='delete'){
			$answer=$pro_mod->delete(floatval($this->_get('codenum')));
			if($answer){$this->success('删除成功!');}else{$this->error('删除失败!');}
			exit();
		}else{

			if($this->_post('search')){
				$where['name']=array('LIKE','%'.$this->_post('pmname').'%');
			}

			import("@.ORG.Page");

			$count = $pro_mod->where ( $where )->count ();
			$p = new Page ( $count, 15);

			$list=$pro_mod->where($where)->limit ( $p->firstRow . ',' . $p->listRows )->select();

			$page = $p->show ();
		}

		$this->assign ( "page", $page );
		$this->assign('list',$list);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 接收推广用户管理数据,返回订单数
       +----------------------------------------------------------
       * @access 	publib
       +----------------------------------------------------------
       * @param  Array
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.15
       */	
	function  returnPromotionOrderNum(){

		if($this->_post('sendcode')){

			$where=$this->returnSelectWhere(array_map('filterVar',$_POST));  //查询条件

			$model = M("{$where['mod']}");

			unset($where['mod']);

			if($model == 'userOrder'){
				$count = $model ->where($where)->count('ordernmb');

			}else{
				unset($where['addtime']);
				$count = $model ->where($where)->count();
			}

			if($count !== false){
				$this->ajaxReturn($count,'操作成功!',1);
			}else{
				$this->ajaxReturn($count,'操作失败!',0);
			}

		}else if($this->_post('changeCode')){
			if($_POST['type'] && $_POST['type']=="userlist"){
				$select_mod=M("UserProfile");
			}else{
				$select_mod=M("UserOrder");
			}
			$paramlist = $select_mod->DISTINCT('frominfo')->where(array('fromid'=>$this->_post('changeCode')))->field('frominfo')->select();
			
			if($paramlist !== false){
				$this->ajaxReturn($paramlist,'操作成功!',1);
			}else{
				$this->ajaxReturn($paramlist,'操作失败!',0);
			}
		}
	}

	/**
       +----------------------------------------------------------
       * 返回查询订单数条件
       +----------------------------------------------------------
       * @access 	private
       +----------------------------------------------------------
       * @param  Array $arguments
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.15
       */		
	private function returnSelectWhere($arguments){

		$where = array();

		if($arguments['selecttype'] == 'userOrderNum'){
			$where['mod'] = 'userOrder';
		}else{
			$where['mod'] = 'userProfile';
		}

		if($arguments['sendcode']){
			if($arguments['sendcode'] == 'all'){
				$where['fromid'] = array('neq','');
			}else{
				$where['fromid']=$arguments['sendcode'];
			}
		}

		if($arguments['param']){
			if($arguments['param'] == 'all'){
				$where['frominfo'] = array('neq','');
			}else{
				$where['frominfo']=$arguments['param'];
			}
		}

		if($arguments['from'] && $arguments['to']){
			$where["addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}

		return $where;
	}


	/**
	 +----------------------------------------------------------
	 * 推广任务列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param  Array $_REQUEST 查询参数
	 +-----------------------------------------------------------
	 * @author  litingting
	 */
	public function promotionTaskList(){
		$send_mod=M("SendTask");
		$art_mod=M("Article");

		if($_REQUEST['ac']=='createedmfile'){
			$flag=$this->createTaskFile('edm_file_txt');
			echo "<script>parent.callback($flag);</script>";
			die;
		}

		if($_REQUEST['type'])
		{
			$where['type']=$_REQUEST['type'];
		}

		if(isset($_REQUEST['status']))
		{
			$where['status']=$_REQUEST['status'];
		}
		$count = $send_mod->where ( $where )->count ();

		import("@.ORG.Page");
		$p = new Page ( $count, 15);

		$page=$p->show();

		$list = $send_mod->where( $where) -> order ("id desc")->limit ( $p->firstRow . ',' . $p->listRows ) ->select();

		for($i=0;$i<count($list);$i++)
		{
			$info=$art_mod->where("id=".$list[$i]['artid'])->find();
			$list[$i] ['title'] =$info ['title'];
			$list[$i] ['content'] =$info ['info'];
		}

		$this->assign("list",$list);
		$this->assign("page",$page);
		$this->display();
	}

	/**
	 * 上传edm推广文件
	 */
	public function createTaskFile($name,$prevUrl='/data/task',$expr='txt'){
		if($photo=$_FILES[$name]['name'])
		{
			$temp=explode(".", $photo);
			$exp=array_pop($temp);
			if($expr != $exp){
				return -1;
			}
			$filename="task.".$exp;
			$path=$prevUrl;
			$photoUrl=$path.'/'.$filename; //返回去的照片路径
			$path=str_replace("//", "/", "./".$path);  //兼容传过来的路径，如"/aa/dd"和"aa/dd"
			dir_create($path);
			$photodir=$path."/".$filename; 	//存储时的路径
			if(copy($_FILES[$name]['tmp_name'],$photodir))
			return 1;
			return 0;
		}
		else
		return -3;
	}

	/**
	 +----------------------------------------------------------
	 * 推广日志列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param  Array $_REQUEST 查询参数
	 +-----------------------------------------------------------
	 * @author  litingting
	 */
	public function promotionLogList(){
		if($_REQUEST['taskid'])
		{
			$where['taskid']=$_REQUEST['taskid'];
		}

		if($_REQUEST['target'])
		{
			$where['target']=array('like','%'.$_REQUEST['target']."%");
		}

		if($_REQUEST['userid'])
		{
			$where['userid'] = $_REQUEST['userid'];
		}
		$send_log_mod=M("SendLog");
		$art_mod=M("Article");
		$user_mod=M("Users");
		$count = $send_log_mod ->where ( $where )->count ();

		import("@.ORG.Page");
		$p = new Page ( $count, 15);

		$page=$p->show();

		$list = $send_log_mod->where( $where) -> order ("id desc")->limit ( $p->firstRow . ',' . $p->listRows ) ->select();

		$this->assign("list",$list);
		$this->assign("page",$page);
		$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 改变推广任务状态
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param  Array $_REQUEST 查询参数
	 +-----------------------------------------------------------
	 * @author  litingting
	 */
	public function changeTaskStatus(){

		if(empty($_POST['id']) ) {
			$this->ajaxReturn(0,"缺少参数",0);
		}

		$send_mod=M("SendTask");

		if(!$info=$send_mod->getById($_POST['id'])){
			$this->ajaxReturn(0,"记录不存在",0);
		}

		$status=abs($info['status']-1);
		if(false!==$send_mod->where("id=".$_POST['id'])->save(array("status" => $status)))
		$this->ajaxReturn($status,"成功",1);
		else
		$this->ajaxReturn($status,"操作失败",0);
	}


	/**
	 +----------------------------------------------------------
	 * 推广任务操作（删除和编辑）
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param  Array $_REQUEST 查询参数
	 +-----------------------------------------------------------
	 * @author  litingting
	 */
	public function promotionTaskOperating(){
		$ac=$_REQUEST['ac'];
		$id=$_REQUEST['id'];

		if(!$id){
			if($this->isAjax())
			$this->ajaxReturn(0,"缺少参数",0);
			$this->error("缺少参数");
			die;
		}

		$send_mod=M("SendTask");

		if(!$info=$send_mod->getById($id)){
			if($this->isAjax())
			$this->ajaxReturn(0,"记录不存在",0);
			$this->error("记录不存在");
			die;
		}
		switch ($ac){

			case 'del' :          //删除操作

			if($info['totalnum'] >0 ){
				$this->ajaxReturn($id,"不能删除",0);
			}
			if($send_mod->delete($id))
			$this->ajaxReturn($id,"成功",1);
			else
			$this->ajaxReturn($id,"删除失败",0);
			break;

			case "edit" :         //编辑操作

			if($_POST['submit'])    //保存编辑
			{
				if($send_mod->where("id=".$id)->save($_POST)) {
					$this->success("操作成功");
					die;
				}else{
					$this->error("操作失败");
					die;
				}
			}

			if($info['type']==1)
			$cid=C('MAIL_CATEID');
			else
			$cid=C('MSG_CATEID');

			$art_mod=M("Article");
			$artlist=$art_mod->field('id,title,cate_id')->where("cate_id=".$cid)->order("add_time desc")->select();
			$this->assign("info",$info);
			$this->assign("artlist",$artlist);
			$this->display("editPromotionTask");
		}
	}
}
?>