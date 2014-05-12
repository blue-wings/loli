<?php
/**
 * 盒子后台管理控制器
 */

class BoxAction extends  CommonAction{

	public function index(){
		$this->display("index");
	}

	/**
	 * 创建盒子
	 */
	public function add(){
		$Box=M("Box");
		if(!empty($_POST["boxname"])){
			$data=array(
			"name"=>$_POST["boxname"],
			"category"=>$_POST["boxcat"],
			"pic"=>$_POST["pic"],
			"pic_big"=>$_POST['pic_big'],
			"quantity"=>$_POST["quantity"],
			"starttime"=>$_POST["startdate"],
			"endtime"=>$_POST["enddate"],
			"addtime"=>date("Y-m-d H:i:s"),
			"box_price"=>$_POST["box_price"],
			"box_intro"=>R('Article/remoteimg',array($_POST['box_intro'])),
			"box_remark"=>R('Article/remoteimg',array($_POST['box_remark'])),
			'box_senddate'=>$_POST["sendbox"],
			"state"=>1,
			"if_repeat" => $_POST['if_repeat']? $_POST['if_repeat']:0,
			"only_newuser"=>$_POST["only_newuser"],
			"if_use_coupon"=>$_POST["use_coupon"],
			"if_give_coupon"=>trim($_POST["give_coupon"]),
			"ifshowtime"=>$this->_post("ifshowtime"),
			"coupon_valid_date"=>trim($_POST["coupon_date"]).' 23:59:59',
			"boxcost"=>R('Article/remoteimg',array($_POST['boxcost'])),
			"icontype"=>$this->_post('icontype'),
			"name_modifier"=>$this->_post('tagname'),
			"special_url"=>$this->_post('special_url'),
			"if_give_member" => $_POST['if_give_member']? $_POST['if_give_member']:0,
			"member_price"=>$_POST['member_price'],
			"only_member"=>$_POST['if_member_buy'],
			"if_hidden"=>$_POST['if_hidden_box']
			);

			if(false!==$Box->add($data)){
				$this->success('操作成功，新增加的盒子ID为：'.$Box->getLastInsID());
			}
			else{
				$this->error('操作失败：addcategory'.$Box->getDbError());
			}
			exit();
		}else{
			$box_mod=M('feeProduct');
			$feeproductlist=$box_mod->field('id,name')->select();
			$this->assign("feeproductlist",$feeproductlist);
		}
		$this->display("addbox");
	}


	/**
       +----------------------------------------------------------
       * 盒子记录列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @author zhaoxiang 
       */
	public function  boxList(){

		if($this->_post('boxid')){

			$val = $this->_post('stats')?time():0;
			if(false !==M("Box")->where(array('boxid'=>$this->_post('boxid')))->setField('toptime',$val)){
				$this->ajaxReturn(1,$this->_post('stats'),1);
			}else{
				$this->ajaxReturn(0,$this->_post('stats'),0);
			}
			exit();
		}

		$BoxView=D("BoxView");
		$user_order_mod=M("userOrder");
		$boxp_mod=M("boxProducts");
		import("ORG.Util.Page");

		if($this->_get("order")){
			if($this->_get('by') == 1){
				$order = $this->_get('order').' DESC';
			}else{
				$order = $this->_get('order').' ASC';
			}
		}else{
			$order = 'Box.boxid DESC';
		}

		$count=$BoxView->count();
		$p = new Page($count,20);
		$boxset_mod=M("BoxOrderSet");
		$order_mod=M("UserOrder");
		$boxlist=$BoxView->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		foreach ($boxlist AS $key => $value){
			
			$if_setbox=$boxset_mod->where("boxid=".$value['boxid'])->find();
			$if_setbox = $if_setbox ? 1 : 0 ;
			
			$if_order=$order_mod->where("boxid=".$value['boxid']." AND state=1 AND ifavalid=1")->find();
			$if_order= $if_order ? 1 : 0;
			$boxlist[$key]['if_order']=$if_order;
			$boxlist[$key]['if_setbox']=$if_setbox;
			$where['boxid']=$value['boxid'];
			$where['state']=1;
			$where['ifavalid']=1;
			$boxlist[$key]['payper']=$user_order_mod->where($where)->count('ordernmb');
			$ptotal=$boxp_mod->where(array('boxid'=>$value['boxid']))->getField('ptotal');
			if($ptotal){$boxlist[$key]['ptotal']=$ptotal;}else{$boxlist[$key]['ptotal']='';}
		}

		$page=$p->show();
		$this->assign('page',$page);
		$this->assign("list",$boxlist);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 创建盒子自选单品清单
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       +@param  string sub		判断是否提交
       +-----------------------------------------------------------
       * @author zhaoxiang 
       * update:自选单添加推荐功能! addBy zhaoxiang 2013.1.22
       */
	public function boxChooseOrder(){
		//print_r($_POST);exit;
		$boxp_mod=M("boxProducts");
		$item_mod=M("inventoryItem");

		if($this->_post('sub')){

			foreach ($_POST['quantity'] AS $key => $value){

				$data=array(
				'boxid'=>$this->_post('bid'),
				'pid'=>trim($_POST['pid'][$key]),
				'pquantity'=>trim($value),
				'ptotal'=>trim($_POST['total'][$key]),
				'maxquantitytype'=>trim($_POST['max'][$key]),
				"sortnum" =>trim($_POST['sortnum'][$key]),
				);

				//$data['iscommend']=in_array($_POST['pid'][$key],$_POST['recommend'])?1:0;
				//$data['ishidden']=in_array($_POST['pid'][$key],$_POST['ish'])?1:0;

				$isexist=$boxp_mod->where(array('boxid'=>$this->_post('bid'),'pid'=>trim($_POST['pid'][$key])))->find();

				if(empty($isexist)){
					$result1=$boxp_mod->add($data);
				}else{
					$where['id']=$_POST['id'][$key];
					$result=$boxp_mod->where($where)->save($data);
				}

				if(($result === false) || ($result1===false)){
					$error[]='mistake';
				}
			}

			if(empty($error)){
				$this->success("操作成功!");
			}else{
				$this->error("操作失败!");
			}

		}else if($this->_post('optional')){ //修改自选单名称

			$name_mod = M("boxProductsCname");
			$data=array(
			'boxid'=>$this->_post('boxid'),
			'maxquantity'=>$this->_post('optional'),
			'title'=>$this->_post('title')
			);
			$result = $name_mod->add($data,$options=array(),$replace=true);

			if($result){
				$this->ajaxReturn(1,'OK',1);
			}else{
				$this->ajaxReturn(0,'FAIL',0);
			}

		}else if($this->_post("ac")=="sort"){    //修改排序值
		    $id = $this->_post("id");
			$sortnum = $this->_post("sortnum");
			if(!is_numeric($sortnum) || empty($id)){
			   $this->ajaxReturn(0,"缺少参数或参数错误",0);
			}
			if(false !== $boxp_mod ->where("id=".$id)->setField("sortnum",$sortnum)){
			   $this->ajaxReturn(0,"修改成功",1);
			}else{
			   $this->ajaxReturn(0,"修改失败",0);
			}


		}else{
			$name_mod = M("boxProductsCname");

			$nameArr=$name_mod->where(array('boxid'=>$this->_get('boxid')))->group('maxquantity')->select();

			$nameArray =$this->zhengli($nameArr);

			$list=$boxp_mod->where(array('boxid'=>$this->_get('boxid')))->order('maxquantitytype ASC,sortnum DESC')->select();
     
			$inputArray=array();

			if(!empty($list)){
				foreach ($list AS $k => $v){
					$inventory_info=$item_mod->where(array('id'=>$v['pid']))->field('name,inventory_estimated')->find();
					$list[$k]['pname']=$inventory_info['name'];
					$list[$k]['estimated']=$inventory_info['inventory_estimated'];

					if($v['maxquantitytype'] > 0){
						$list[$k]['title']=$nameArray["{$v['maxquantitytype']}"];
						$inputArray['optional']["{$v['maxquantitytype']}"][]=$list[$k];
					}else{
						$inputArray['welfare'][]=$list[$k];
					}
				}
			}
			unset($list);
			$this->assign('oplist',$inputArray);
			$this->assign('boxname',$this->_get('boxname'));
			$this->display();
		}
	}

	//筛选数组,num=>title
	private function zhengli($list){
		$returnArray=array();
		foreach ($list AS $key=>$value){
			$returnArray["{$value['maxquantity']}"]=$value['title'];
		}
		return $returnArray;
	}

	/**
       +----------------------------------------------------------
       * 删除库存自选单品记录
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       +@param  string sub		提交
       +-----------------------------------------------------------
       * @author zhaoxiang 
       */

	function delboxProducts(){
		$boxp_mod=M("boxProducts");

		if($this->_post('opid')){

			$isTrue=$boxp_mod->where(array('id'=>trim($this->_post('opid'))))->find();

			if(empty($isTrue)){
				$this->ajaxReturn(1,1,1);
			}else{

				if($boxp_mod->delete(trim($this->_post('opid')))){

					$count = $boxp_mod->where(array('boxid'=>$isTrue['boxid'],'maxquantitytype'=>$isTrue['maxquantitytype']))->count('id');

					if($count == 1){
						$name_mod = M("boxProductsCname");
						$name_mod->where(array('boxid'=>$isTrue['boxid'],'maxquantity'=>$isTrue['maxquantitytype']))->delete();
						$this->ajaxReturn(1,'only',1);
					}else{
						$this->ajaxReturn(1,1,1);
					}
				}else{
					$this->ajaxReturn(0,0,0);
				}
			}
		}else{
			$result=$boxp_mod->where(array('boxid'=>$this->_get('boxid')))->delete();
			if($result){$this->success('删除成功!');}else{$this->error('删除失败!');}
		}
	}

	/**
	 * 根据ID获取库存单品信息
	 * comment by zhenghong 2013-01-10
	 */
	function getProductsData(){
		$item_mod=M("inventoryItem");
		if($this->_post('pid')){
			$result=$item_mod->where(array('id'=>$this->_post('pid')))->field('name,inventory_estimated')->find();
		}else{
			$result=$item_mod->where(array('name'=>$this->_post('pname')))->field('id')->find();
		}

		if($result){
			$this->ajaxReturn($result['name'],$result['inventory_estimated'],1);
		}
	}

	/**
	 * 编辑盒子表单显示
	 */
	public function editBox(){
		$boxid=$_REQUEST["boxid"];
		if($boxid){
			$Box=M("Box");
			$boxinfo=$Box->getByBoxid($boxid);
			$this->assign("boxinfo",$boxinfo);
			$FeeProduct=M("FeeProduct");
			$feeproductlist=$FeeProduct->select();
			$this->assign("feeproductlist",$feeproductlist);
			$this->display("editbox");
		}
		else {
			$this->error('操作失败:缺少参数');
		}
	}

	/**
	 * 执行盒子修改请求
	 */
	public function edit(){
		$boxid=$_POST["boxid"];
		if($boxid) {
			$Box=M("Box");
			$data=array(
			//增加修改盒子价格和描述、购买条件
			"name"=>$_POST["boxname"],
			"category"=>$_POST["boxcat"],
			"pic"=>$_POST["pic"],
			"pic_big"=>$_POST["pic_big"],
			"quantity"=>$_POST["quantity"],
			"starttime"=>$_POST["startdate"],
			"endtime"=>$_POST["enddate"],
			"addtime"=>date("Y-m-d H:i:s"),
			"box_price"=>$_POST["box_price"],
			"box_intro"=>R('Article/remoteimg',array($_POST['box_intro'])),
			"box_remark"=>R('Article/remoteimg',array($_POST['box_remark'])),
			"box_senddate"=>$_POST["sendbox"],
			"only_newuser"=>$_POST["only_newuser"],
			"if_repeat" => $_POST['if_repeat']? $_POST['if_repeat']:0,
			"if_use_coupon"=>$_POST["use_coupon"],
			"if_give_coupon"=>trim($_POST["give_coupon"]),
			"coupon_valid_date"=>trim($_POST["coupon_date"]).' 23:59:59',
			'boxcost'=>R('Article/remoteimg',array($_POST['boxcost'])),
			'icontype'=>$this->_post('icontype'),
			'name_modifier'=>$this->_post('tagname'),
			'ifshowtime'=>$this->_post('ifshowtime'),
			"special_url"=>$this->_post('special_url'),
			"if_give_member"=>$_POST['if_give_member']? $_POST['if_give_member']:0,
			"member_price"=>$_POST['member_price'],
			"only_member"=>$_POST['if_member_buy'],
			"if_hidden"=>$_POST['if_hidden_box']
			);

			if(false!==$Box->where("boxid=".$boxid)->save($data)){
				$this->success('操作成功');
			}else{
				$this->error('操作失败'.$Box->getError());
			}
		}
		else {
			$this->error('操作失败');
		}
	}

	/**
	 * 删除盒子记录
	 * update zhaoxiang 2013/4/8
	 */
	public function delBox(){
		if($this->_get('boxid')){

			$result = M("boxDetail")->where(array('boxid'=>$this->_get('boxid')))->delete();

			if($result){
				$rult = M("Box")->where(array('boxid'=>$this->_get('boxid')))->delete();
				if(false === $rult){
					$this->error("Box表boxid=".$this->_get('boxid').'删除出错!');
				}else{
					$this->success("删除成功!");
				}
			}else{
				$this->error('boxDetail表boxid='.$this->_get('boxid').'删除出错!');
			}
		}else{
			$this->error("错误请求!");
		}
	}


	/*盒子类别管理*/
	public function feeProductList()
	{
		$feeProductModel = M("FeeProduct");
		$this->_list($feeProductModel);
		$this->display();
	}

	//操作盒子类别管理
	public function optFee(){
		$id = intval($_REQUEST ["id"]);
		$feeProductModel = M("FeeProduct");
		if(isset($_REQUEST['isshow'])){
			if (false === $feeProductModel->create()) {
				$this->error($feeProductModel->getError());
			}
			//保存当前数据对象
			if (empty($id)) {
				$list = $feeProductModel->add();
			}else{
				$list = $feeProductModel->save();
			}
			if ($list !== false) {
				$this->success('操作成功!');
			} else {
				$this->error('操作失败!');
			}
		}else {
			if (!empty($id)) {
				$vo=$feeProductModel->getById($id);
				$this->assign('vo',$vo);
				$this->assign('id',$id);
			}else {
				$this->assign('id','');
			}
			$this->display("optFeeProduct");
		}

	}
	//删除
	public function delFee()
	{
		$id = intval($_REQUEST ["id"]);
		$feeProductModel = M("FeeProduct");
		if (isset($id)) {
			if (false !== $feeProductModel->where("id=$id")->delete()) {
				$this->success('删除成功！');
			} else {
				$this->error('删除失败！');
			}
		} else {
			$this->error('非法操作');
		}
	}

	//加入邮件或短信列表
	public function addTasklist(){
		if(empty($_REQUEST ["boxid"]))
		$this->error("没有选择盒子");
		$where['address_id']=array("gt",0);
		$where ["UserOrder.boxid"] = trim ( $_REQUEST ["boxid"] );
		$where ["ifavalid"] = 1;
		$where ["UserOrder.state"] = 1;
		$where['UserOrderSend.senddate']=array("exp","is not null");
		$where['UserOrderSend.proxysender']=array("exp","is not null");
		$where['UserOrderSend.proxyorderid']=array("exp","is not null");
		//$where['UserOrderSend.productnum']=array("gt","0");
		//2013-11-13 由于推出的吃货盒不在订单中添加产品信息，所以只判断快递信息是否为空即可
		$user_order_mod = D ( "UserOrderAddr" );
		$list= $user_order_mod->where ( $where )->select();
		if(!$list)
		$this->error("没有添加产品和订单号");
		else{
			$boxname=$_REQUEST['listname'];
			if($_REQUEST['type']==1)
			{
				for($i=0;$i<count($list);$i++){
					$list[$i]["name"]=$boxname;
					ordertasklist($list[$i], 1);
				}
				$this->success("加入邮件列表成功");
			}
			if($_REQUEST['type']==2)
			{
				for($i=0;$i<count($list);$i++){
					$list[$i]["name"]=$boxname;
					ordertasklist($list[$i], 2);
				}
				$this->success("加入短信列表成功");
			}
		}
	}


	/**
       +----------------------------------------------------------
       * 添加,编辑,更新,盒子详情页
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string  res    1 表示更新或添加成功!
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/9
       */
	public function detail(){
		$mod=M("boxDetail");
		if($this->_post('bid')){
			$where['boxid']=trim($this->_post('bid'));
			$data['instruction']=trim($_POST['abst']);//    $this->_post("abst");
			$data['product_list']=trim($this->_post("prolist"));
			$data['details']=R('Article/remoteimg',array($this->_post("info")));

			$result=$mod->where($where)->find();

			if($result){
				$res=$mod->where($where)->save($data);
			}else{
				$data['boxid']=$where['boxid'];
				$res=$mod->add($data);
			}

			$this->assign('jumpUrl',U('Box/boxList'));
			if($res){
				$this->success("保存成功");
			}else{
				$this->error($mod->getError);
			}
		}else{
			$box=array();
			if($_GET['key']!=null){
				$box=$mod->where(array('boxid'=>trim($this->_get("id"))))->find();
			}else{
				$box['boxid']=trim($this->_get("id"));
			}
			$box['name']=trim($this->_get("name"));
			$this->assign('box',$box);
			$this->display();
		}
	}

	function deltail(){
		if($this->_post("boxid")){
			$mod=M("boxDetail");
			$result=$mod->where(array('boxid'=>trim($this->_post("boxid"))))->delete();
			if($result){
				$this->ajaxReturn(1,1,1);
			}else{
				$this->ajaxReturn(0,0,0);
			}
		}
	}

	/**
       +----------------------------------------------------------
       * 增值方案列表
       +----------------------------------------------------------
       * @access  public   
       +----------------------------------------------------------
       * @param   null
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/3/27
       */	
	public function boxRelationList (){

		if($this->_get('id')){
			$this->changestatus($this->_get('id'),$this->_get('changestatus'));
		}else{

			import("@.ORG.Page");

			if($this->_get('sub')){
				if($this->_get('reid')){
					$where['id']=$this->_get('reid');
				}

				if($this->_get('rename')){
					$where['projectname'] =  array('like','%'.$this->_get('rename').'%');
				}
			}

			$count = M('boxProject')->where($where)->count('id');

			$p = new Page($count,15);

			$list = M('boxProject')->where($where)->order('id DESC')->select();

			$page = $p->show();
		}

		$this->assign("page",$page);
		$this->assign('list',$list);
		$this->display();
	}

	private function returnMessage($result){
		if($result){
			$this->ajaxReturn(1,'操作成功!',1);
		}else{
			$this->ajaxReturn(0,'操作失败!',0);
		}
		exit();
	}

	//选择增值方案
	//zhaoxiang 2013/3/28
	function selectBoxRelation(){

		if($this->_post('action')){
			$data = array(
			'boxid'=>$this->_post('boxid'),
			'projectid'=>$this->_post('value')
			);

			if($this->_post('action') == 'add'){

				$result = M('boxProjectRelation')->add($data);

			}else if($this->_post('action') == 'delete'){

				$result =  M('boxProjectRelation')->where($data)->delete();

			}else if($this->_post('action') == 'clear'){
				$result = M('boxProjectRelation')->where(array('boxid'=>$this->_post('boxid')))->delete();
			}

			$this->returnMessage($result);
		}else{
			$list = M('boxProject')->where(array('status'=>1))->field('id,projectname')->select();

			$selected = M('boxProjectRelation')->where(array('boxid'=>$this->_get('boxid')))->
			join('LEFT JOIN box_project as bp ON box_project_relation.projectid = bp.id')->field('id,projectname')->select();

			$this->assign('selected',$selected);
			$this->assign('list',$list);
			$this->display();
		}
	}

	/**
       +----------------------------------------------------------
       * 增值关系,增删改查
       +----------------------------------------------------------
       * @access  public   
       +----------------------------------------------------------
       * @param   NULL
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/3/27
       */		
	function boxRelationManage(){

		$name = M("boxProject")->where(array('id'=>$this->_get('id')))->getField('projectname');

		if($this->_post('action') == 'delete'){

			$this->appDelete($_POST);

		}else if($this->_post('action') == 'add'){

			$this->appAddOrUpdate($_POST);

		}else if($this->_get('action') == 'lookup'){

			$info = M("boxProject")->where(array('id'=>$this->_get('id')))->find();

			$info['list'] =  M('boxProjectList')->where(array('projectid'=>$this->_get('id')))->
			join("LEFT JOIN inventory_item ON box_project_list.pid=inventory_item.id")->
			field('box_project_list.pid,inventory_item.name')->select();

			$this->assign('info',$info);

		}else if($this->_post('action') == 'edit'){

			$this->appAddOrUpdate($_POST);
		}

		$this->assign('name',$name);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 改变状态
       +----------------------------------------------------------
       * @access  private   
       +----------------------------------------------------------
       * @param   修改增值单状态
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/3/27
       */
	private function changestatus($id,$value){

		$result = M("boxProject")->where(array('id'=>$this->_get('id')))->setField('status',$this->_get('changestatus'));

		if($result){
			$this->success("修改状态成功!");
		}else{
			$this->error("修改状态失败,请检查!");
		}
		exit();
	}

	//添加或修改
	private function appAddOrUpdate($list){

		$data = array(
		'projectname'=>$list['pname'],
		'price'=>$list['price'],
		'remark'=>$list['remark'],
		'status'=>$list['state']
		);

		if($list['id']){
			$upresult = M("boxProject")->where(array('id'=>$list['id']))->save($data);
		}else{
			$lastid = M("boxProject")->add($data);
		}

		if($lastid){
			foreach ($list['itemid'] AS $key => $value){
				$insertData = array(
				'projectid'=>$lastid,
				'pid'=>$value
				);


				$isc = $this->iscreate($value);

				if(empty($isc)){
					continue;
				}else{
					if(!M("boxProjectList")->add($insertData)){
						$error[]=$value;
					}
				}
			}

			if($error){
				$this->error(implode(',',$error)." 没有添加成功!");
			}else{
				$this->success("创建增值方案成功!","__URL__/boxRelationList");
			}
			exit();
		}else if($upresult !== false){
			foreach ($this->_post('itemid') AS $key => $value){
				$insertData = array(
				'projectid'=>$this->_post('id'),
				'pid'=>$value
				);


				$inresult = M("boxProjectList")->add($insertData,$options=array(),$replace=true);

				if($inresult === false){
					$this->error('boxProject数据添加失败!');
					break;
				}
			}
			$this->assign('jumpUrl',"__URL__/boxRelationList");
			$this->success("数据修改成功!");
			exit();
		}else{
			$this->error('boxProject数据操作失败!');
		}
	}

	//判断是否可以添加本条数据
	private  function iscreate($pid){
		return M("InventoryItem")->where(array('id'=>$pid,'inventory_estimated'=>array('gt',0)))->find();
	}

	//AJAX删除单品数据
	private function appDelete($data){

		$where = array(
		'projectid'=>$data['projectid'],
		'pid'=>$data['pid']
		);

		$result = M("boxProjectList")->where($where)->delete();

		if($result){
			$this->ajaxReturn(1,'删除成功!',1);
		}else{
			$this->ajaxReturn(0,'删除失败',0);
		}
	}
	
	
	
	
	/**
	 * 修改自选单隐藏状态
	 * @author zhenghong
	 */
	public function ajax_change_hidden(){
		$id=$_POST["id"];
		$where[id]=$id;
		$hidden_val=M("BoxProducts")->where($where)->getFieldById($id,'ishidden'); //获取当前ID的ishidden值
		if($hidden_val>0)
		{
			$data["ishidden"]=0;
		}
		else {
			$data["ishidden"]=1;
		}
		$result=M("BoxProducts")->where($where)->save($data);
		
		if($result){
			//修改成功后，通过AJAX形式返回修改后的隐藏状态值
			$this->ajaxReturn(1,$data["ishidden"],1);
		}else{
			$this->ajaxReturn(0,'修改失败',0);
		}
	}
	
	
	/**
	 * 修改自选单中显示量值
	 */
	public function ajax_change_settotal(){
		$id=$_POST["id"];
		$settotal=$_POST["settotal"];
		$where[id]=$id;
		$data["settotal"]=$settotal;
		$result=M("BoxProducts")->where($where)->save($data); 
		if($result){
			$this->ajaxReturn(1,"修改成功",1);
		}else{
			$this->ajaxReturn(0,'修改失败',0);
		}
	}
	
	/**
	 * 修改自选单中折扣率值
	 */
	public function ajax_change_discount(){
		$id=$_POST["id"];
		$discount=$_POST["discount"];
		$where[id]=$id;
		$data["discount"]=$discount;
		$result=M("BoxProducts")->where($where)->save($data);
		if($result){
			$this->ajaxReturn(1,"修改成功",1);
		}else{
			$this->ajaxReturn(0,'修改失败',0);
		}
	}
	
	/**
	 * 创建/修改盒子属性
	 * @author penglele
	 */
	public function add_boxset(){
		$boxid=$_GET["boxid"];
		$return['boxid']=$boxid;
		$bid=$_POST['bid'];
		if($bid){
			$if_mon=$_POST['if_mon'];
			if($if_mon==0){
				$data['months']=0;
				$data["if_quarter"]=0;
				$data['post_day']=0;
				$data['post_date']="";
			}else{
				$data['months']=$_POST['boxset_mon'];
				$if_quarter=$_POST['if_quarter'];
				$if_quarter= $if_quarter ? $if_quarter : 0 ;
				$data['if_quarter']=$if_quarter;
				$post_day=$_POST['boxset_postday'];
				if($post_day!=1){
					$data['post_day']=$post_day;
				}else{
					$data['post_day']=0;
				}
				$post_date=$_POST['start_time'];
				if($post_date){
					$data['post_date']=$post_date;
				}else{
					$data['post_date']="";
				}
			}
			$boxset_mod=M("BoxOrderSet");
			$if_boxset=$boxset_mod->where("boxid=".$bid)->find();
			if($if_boxset){
				//如果当前盒子已有用户下单，则不能修改
				$rel=$boxset_mod->where("boxid=".$bid)->save($data);
				$msg="修改";
			}else{
				$data['boxid']=$bid;
				$rel=$boxset_mod->add($data);
				$msg="创建";
			}
			
			$this->assign('jumpUrl',U('Box/add_boxset',array('boxid'=>$bid)));
			if($rel){
				$this->success($msg."成功");
			}else{
				$this->error($boxset_mod->getError);
			}
			
		}else{
			$return['boxset']=M("BoxOrderSet")->where("boxid=".$boxid)->find();
			$this->assign("return",$return);
			$this->display();
		}
	}
	
	/**
	 * 删除盒子的属性
	 * @author penglele
	 */
	function del_box_order_set(){
		$boxid=$_POST['boxid'];
		if(!$boxid){
			$this->ajaxReturn(0,"盒子不存在",0);
		}
		$ret=M("BoxOrderSet")->where("boxid=".$boxid)->delete();
		if($ret){
			$this->ajaxReturn(1,"success",1);
		}else{
			$this->ajaxReturn(0,"操作失败",0);
		}
	}
	
	
	/**
	 * 订单配货
	 * @author penglele
	 */
	public function orderAllocation(){
		$userorderaddr = D ( "UserOrderAddr" );
		$send_mod=M("UserOrderSend");
		import ( "@.ORG.Page" ); // 导入分页类库
		$listbox=$userorderaddr->distinct('bid')->field('bid,boxname')->order('bid DESC')->select();  //盒子列表
		$where=array();
		
		if($_GET['ifpost']=="post"){
			$where['senddate']=array("exp","IS NOT NULL");
			$where['proxysender']=array("exp","IS NOT NULL");
		}
		if($_GET['ifpost']=="nopost"){
			$where['senddate']=array("exp","IS NULL");
			$where['proxysender']=array("exp","IS NULL");
		}
		
		//盒子ID 
		if($_GET['boxid']){
			$where['boxid']=$_GET['boxid'];
		}
		if($_GET['orderid']){
			$where['orderid']=$_GET['orderid'];
		}
		if($_GET['userid']){
			$where['userid']=$_GET['userid'];
		}
		if($_GET['child_id']){
			$where['child_id']=$_GET['child_id'];
		}
		$count = $send_mod->where ( $where )->count ();
		$p = new Page ( $count, 15);
		$list=$send_mod->limit ( $p->firstRow . ',' . $p->listRows )->order("orderid DESC")->where($where)->select();
		$page = $p->show ();
		if($list){
			$address_mod=M("UserOrderAddress");
			$user_mod=M("Users");
			$box_mod=M("Box");
			$user_order_mod=M("UserOrder");
			$sendword_mod=M("UserOrderSendword");
			$box_project_mod=M('BoxProject');
			foreach($list as $key=>$val){
					$address=$address_mod->where("orderid=".$val['orderid'])->find();
					$usermail=$user_mod->where("userid=".$val['userid'])->getField("usermail");
					$address['usermail']=$usermail;
					$address['spaceurl']=getSpaceUrl($val['userid']);
					$address['userid']=$val['userid'];
					$list[$key]['boxname']=$box_mod->where("boxid=".$val['boxid'])->getField("name");
					$list[$key]['address']=$address;
					$orderinfo=$user_order_mod->field("address_id,sendword,projectid")->where("ordernmb=".$val['orderid'])->find();
					$list[$key]['address_id']=$orderinfo['address_id'];
					
					if($orderinfo['projectid']) {
						$boxproject=$box_project_mod->where(array('id'=>$orderinfo['projectid']))->find();
						$list[$key]['addproject']="（加价:".$boxproject["price"]."，增值方案名称：".$boxproject['projectname']."，备注：".$boxproject['remark']."）";
					}
					
					if($val['child_id']){
						$list[$key]['sendword']=$sendword_mod->where("orderid=".$val['orderid']." AND child_id=".$val['child_id'])->getField("content");
					}else{
						$list[$key]['sendword']=$orderinfo['sendword'];
					}
					
					if(in_array($val['boxid'],C("SKIP_ADDPRODUCT_BOXID_LIST"))){
						$list[$key]['if_can']=1;
					}
			}
		}
		$ndate=date("Ym");
		$timelist=array();
		for($i=12;$i>=1;$i--){
			$timelist[]=date("Ym",strtotime($ndate." -$i months"));
		}
		$timelist[]=$ndate;
		for($j=1;$j<=3;$j++){
			$timelist[]=date("Ym",strtotime($ndate." $j months"));
		}
		
		$this->assign("userlist",$list);
		$this->assign("listbox",$listbox);
		$this->assign ("page", $page );
		$this->assign("timelist",$timelist);
		$this->display();
	}
	
	
}
?>
