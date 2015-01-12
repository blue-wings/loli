<?php
/**
 * 后台-订单管理 [重要]
 * @author zhenghong
 */
class OrderManagementAction extends CommonAction {

	/**
	 * 订单列表
	 * @author zhenghong
	 * update by zhaoxiang 2013-01-25 优化
	 */
	public function orderList() {
		import ( "@.ORG.Page" ); // 导入分页类库

        $m = $_GET["orderType"]==C("ORDER_TYPE_NORMAL")?D ( "UserOrderAddr" ):D("UserSelfPackageOrderAddr");
		$where=$this->orderListWhereParam(array_map('filterVar',$_GET));  //查询条件

		// 如果是导出excel添加导出代码
		if ($_GET['outputexcel']){
			$list =$m->order ( "UserOrder.addtime desc" )->where ( $where ) -> select();
			$this->exportUserOrder($list);
		}

		$count = $m->where ( $where )->count ();
		$p = new Page ( $count, 15);
		$list = $m->limit ( $p->firstRow . ',' . $p->listRows )->order ( 'UserOrder.addtime DESC' )->where ( $where )->group("ordernmb")->select ();
		foreach($list as $key=>$val){
			$realCost = bcdiv($val['cost']+$val["postage"]-$val["giftcard"], 100, 2);
			$val["realCost"]=$realCost;
			$cost = bcdiv($val["cost"], 100, 2);
			$val["cost"]=$cost;
			$giftcard = bcdiv($val["giftcard"], 100, 2);
			$val["giftcard"]=$giftcard;
			$postage = bcdiv($val["postage"], 100, 2);
			$val["postage"]=$postage;
            $userWorkOrder = M('UserWorkOrder')->where ( array ('order' => $val["ordernmb"]) )->find ();
            $val["uord"]=$userWorkOrder;
            $list[$key]=$val;
		}
		$page = $p->show ();

        $this->assign ("orderType", $_GET["orderType"] );
		$this->assign ("userlist", $list );
		$this->assign ("page", $page );
		$this->display();
	}

	//修改订单状态
    public function changeState(){
        $m = $_POST["orderType"]==C("ORDER_TYPE_NORMAL")?M( "UserOrder" ):M("UserSelfPackageOrder");
        $result = $m->where(array('ordernmb' => $this->_post('ordernum')))->setField("state", $this->_post('val'));
        if ($result) {
            if ($this->_post('val') == C("USER_ORDER_STATUS_REFUNDED")) {
                $this->cleanOrderDetail($this->_post('ordernum'), $_POST["orderType"]);
            }
            $this->ajaxReturn($this->_post('val'), 'success', 1);
        } else {
            $this->ajaxReturn($this->_post('val'), 'fail', 0);
        }
        exit();
    }
	
	/**
	 * 清除订单相关信息
	 * @author penglele
	 */
	public function cleanOrderDetail($orderid, $orderType){
		if(!$orderid || !$orderType){
			return false;
		}
        $m = $orderType==C("ORDER_TYPE_NORMAL")?M( "UserOrder" ):M("UserSelfPackageOrder");
		$order_state=$m->where("ordernmb=".$orderid)->getField("state");
		//只针对已退款订单
		if($order_state==C("USER_ORDER_STATUS_REFUNDED")){
			//用户订单-发送表
			M("UserOrderSend")->where("orderid=".$orderid)->delete();
			//用户订单-物流表
			M("UserOrderProxy")->where("orderid=".$orderid)->delete();
			//用户订单-赠言表
			M("UserOrderSendword")->where("orderid=".$orderid)->delete();
		}
	}
	

	/**
       +----------------------------------------------------------
       * 导出用户订单
       +----------------------------------------------------------  
       * @access private   
       +----------------------------------------------------------
       * @param  list		要导出的数据	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.23
     */
	private function exportUserOrder($list){
		$str = "用户ID,订单号,折扣金额,Email,用户名,TEL,地址,邮编\n";
		foreach ( $list as $key => $value ) {
			$str .= $value ['userid'] . ",T" . $value ['ordernmb'] . ",". $value ['usermail'] . "," . $value ['linkman'] . "," . $value ['telphone'] . "," . $value ['province'] . $value ['city'] . $value ['district'] . $value ['address'] . "," . $value ['postcode']."\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK", date ( "Y-m-d" ) . "订单列表" ), $str );
		exit ();
	}


	/**
       +----------------------------------------------------------
       * 订单列表参数整理
       +----------------------------------------------------------  
       * @access private   
       +----------------------------------------------------------
       * @param  arguments		  $_GET查询参数  			
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.1.23
     */
	private  function orderListWhereParam($arguments){

		$where=array();

		//订单号
		if($arguments['orderid']){
			$where ["UserOrder.ordernmb"] = $arguments['orderid'];
		}

		//用户id
		if($arguments['userid']){
			$where ["UserOrder.userid"] = $arguments['userid'];
		}

		//联系人
		if($arguments['linkman']){
			$where ["UserOrderAddress.linkman"] =array('LIKE','%'.$arguments['linkman'].'%');
		}

		//手机号
		if($arguments['telphone']){
			$where ["UserOrderAddress.telphone"] =array('LIKE','%'.$arguments['telphone'].'%');
		}

        //单品id
        if($arguments['inventoryItemId']){
            $where ["UserOrderSendProductDetail.inventory_item_id"] =$arguments['inventoryItemId'];
        }

        //投递批次
        if($arguments['productId']){
            $where ["UserOrderSendProductDetail.productId"] =$arguments['productId'];
        }

		//订单日期
		if($arguments['from'] && $arguments['to']){
			$where["UserOrder.addtime"]=array(array('egt',$arguments['from'].' 00:00:00'),array('elt',$arguments['to'].' 23:59:59'),'AND');
		}else if($arguments['from']){
			$where["UserOrder.addtime"]=array('egt',$arguments['from'].' 00:00:00');
		}else if($arguments['to']){
			$where["UserOrder.addtime"]=array('elt',$arguments['to'].' 23:59:59');
		}

		//订单状态  有效,无效
		if($arguments['ifavalid']=='0'){
			$where ["UserOrder.ifavalid"]=C("ORDER_IFAVALID_OVERDUE");
		}else{
			$where ["UserOrder.ifavalid"]=C("ORDER_IFAVALID_VALID");
		}

		//订单状态  已付款1,未付款0,退款2
		if($arguments['orderstate']=='0'){
			$where ["UserOrder.state"]=C("USER_ORDER_STATUS_NOT_PAYED");
		}else if($arguments['orderstate']=='2'){
			$where ["UserOrder.state"]=C("USER_ORDER_STATUS_REFUNDED");
		}else{
			$where ["UserOrder.state"]=C("USER_ORDER_STATUS_PAYED");
		}

		//发货状态  post已发货,nopost未发货
		if($arguments['ifpost']=='post'){
			$where ["UserOrderSend.senddate"] =array("exp","IS NOT NULL");
		}else if($arguments['ifpost']=='nopost'){
			$where ["UserOrderSend.senddate"] =array("exp","IS NULL");
		}
		return $where;
	}

	/**
       +----------------------------------------------------------
       * 通过订单ID获得产品列表
       +----------------------------------------------------------  
       * @access public   
       +----------------------------------------------------------
       * @param  orderid		订单号
       +-----------------------------------------------------------
       * @author litingting
       * @update zhaoxiang 2013.1.23
     */	
	public function productlist(){
        $orderid = trim($_GET["orderid"]);
        $orderType = $_GET["orderType"];

		/* 提取订单单品列表 */
		$productsModel=M("Products");
		$UserOrderSendProductdetail = M ( "UserOrderSendProductdetail" );
        if($orderType == C("ORDER_TYPE_NORMAL")){
            $where['orderid']=$orderid;
        }else{
            $where['self_package_order_id']=$orderid;
        }

		$orderproductlist = $UserOrderSendProductdetail->where ($where)->order('productid ASC')->select ();
		echo '<table border="1">';
        echo '<tr heighht="20">';
        echo '<td>投递批次</td>';
        echo '<td>单品ID</td>';
        echo '<td>单品名称</td>';
        echo '<td>订购数量</td>';
        echo '<td>订阅价格</td>';
        echo '</tr>';
        $total = 0;
		foreach($orderproductlist as $key =>$value){
			$product = $productsModel->getById ( $value ['productid'] );
			echo '<tr>';
			echo '<td>'.$value['productid'].'</td>';
            echo '<td>'.$value['inventory_item_id'].'</td>';
			echo '<td>'.$product ["pname"].'</td>';
            echo '<td>'.$value['product_num'].'</td>';
            $price = bcdiv($value['product_price'], 100, 2);
            $total += $value['product_price']*$value['product_num'];
			echo '<td>￥'.$price.'</td>';
			echo '</tr>';
		}
        $total = bcdiv($total, 100, 2);
		echo "<td colspan='4'><span style='color:red'>总价为:￥".$total."</span></td>";
		echo '</table>';
		die;
	}

	/**
       +----------------------------------------------------------
       * 为用户添加产品   
       +----------------------------------------------------------  
       * @access public   
       +----------------------------------------------------------
       * @param  
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	public function addproduct() {
		$userorder =trim($_GET['orderid']);
		if (empty ( $userorder ))return false;
		$order_arr=explode("-",$userorder);
		$orderid=$order_arr[0];
		$childid=$order_arr[1];
		
		$userorderaddr = D ( "UserOrderAddr" );
		$userorderinfo=$userorderaddr->getByOrdernmb ( $orderid );
		$userorderinfo['child_id']=$childid;
		if(!$childid){
			/* 触发创建user_order_send表中的记录 */
			$this->createDataToUserOrderSend($userorderinfo);
		}
		$userorderinfo['ordernmb']=$userorder;
// 		$array_answer=$this->getUserVote($userorderinfo ["userid"]);	//获取用户美丽档案25题答案

		// 提取用户曾经购买盒子的记录
		$where['UserOrder.userid']=$userorderinfo ["userid"];
		$where['UserOrder.state']=1;
		$where['UserOrder.ifavalid']=1;
		$boxlist=$userorderaddr->distinct('boxid')->where($where)->field('boxname')->select();

		foreach ($boxlist AS $key => $value){
			if($value['boxname']){
				$buybox_list[]=$value['boxname'];
			}
		}

		$inventory_item_mod=D("InventoryItem");
		$UserOrderSendProductdetail = M ( "UserOrderSendProductdetail" );
		$orderproductlist = $UserOrderSendProductdetail->where ( "orderid=" . $orderid." AND child_id=".$childid )->select ();
		//已加入的产品
		if($orderproductlist){
			$orderproduct = "";
			foreach ( $orderproductlist as $key => $value ) {
				$productname = $inventory_item_mod->getById ( $value ['productid'] );
				$orderproduct .= "<option value='" . $value ["productid"] . "'>" . $productname [name] . "</option>";
			}
		}

		$meal=$this->mealPublic();//产品分类和套餐

		$this->assign ( 'clist', $meal['list']);
		$this->assign ( 'firstcidlist',$meal['firstcidlist']);
		$this->assign ( 'secondcidlist',$meal['secondcidlist']);
		$this->assign ( 'packageList', $meal['packageList']);

		$this->assign ( 'orderproductlist', $orderproduct );
		$this->assign ( 'buyboxlist', $buybox_list );
		$this->assign ( 'userorderinfo', $userorderinfo ); // 用户订单详细数据
		$this->display ();
	}

	/**
       +----------------------------------------------------------
       * 获取用户美丽档案第25题答案,并json_decode解密
       +----------------------------------------------------------  
       * @access   private   
       +----------------------------------------------------------
       * @param    userid    用户ID 
       +-----------------------------------------------------------
       * @author   zhaoxiang 2013.1.23
     */	
	private function  getUserVote($userid){
		$Uservote = M ( "UserVote" );
		$voters = $Uservote->where (array('question'=>'25','userid'=>$userid))->find();

		if ($voters["answer"]) {
			$useranswer = json_decode ( $voters["answer"], true );

			foreach ( $useranswer as $k => $v ) {
				$array_answer [] = stripcslashes($v);
			}
		}

		return $array_answer;
	}

	// 多个订单记录
	public function addproductMuch() {
		$orderids = $_REQUEST ["orderid"];
		if (empty ( $orderids ))	die("没有选择订单");
		$userordersend = M("UserOrderSend");
		$sql="SELECT CONCAT(orderid,'-',child_id) AS id FROM user_order_send WHERE CONCAT(orderid,'-',child_id) IN ($orderids)";
		$list=$userordersend->query($sql);
		if(!$list)	die("您选择的订单己经发货,不能再添加产品");
		/* 添加批量的数据 */
		$userorderaddr = D ( "UserOrderAddr" );
		$Uservote = M ( "UserVote" );
		$userorder_info = array ();
		$orderlist=array();
		foreach ( $list as $value ) {
			$orderid_arr=explode("-",$value['id']);
			$orderid=$orderid_arr[0];
			$childid=$orderid_arr[1];
			$orderlist[]=$value['id'];
			$userorderinfo = $userorderaddr->getByOrdernmb ( $orderid);
			$userorderinfo['child_id']=$childid;
			if($userorderinfo){
				if(!$childid){
					/* 触发创建user_order_send表中的记录 */
					$this->createDataToUserOrderSend($userorderinfo);
				}
				$userorder_info [] = array (
				'userid' => $userorderinfo ["userid"],
				"orderid" => $value['id']	//oderid."-".child_id
				);
			}
		}
		$order_str=implode(",",$orderlist);
		$meal=$this->mealPublic();//产品分类和套餐

		$this->assign ( 'clist', $meal['list']);
		$this->assign ( 'firstcidlist',$meal['firstcidlist']);
		$this->assign ( 'secondcidlist',$meal['secondcidlist']);
		$this->assign ( 'packageList', $meal['packageList']);
		$this->assign ( 'orderid', $order_str );
		$this->assign ( 'count_orderid', count ( $userorder_info ) );
		$this->assign ( 'userorder_info', json_encode ( $userorder_info ) );
		$this->display ();
	}

	/**
       +----------------------------------------------------------
       * 触发创建user_order_send表中的记录		(提取公共部分)
       +----------------------------------------------------------  
       * @access  private
       +----------------------------------------------------------
       * @param   userorderinfo  用户订单数据
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	private function createDataToUserOrderSend($userorderinfo){
		$data=array();
		$data ["orderid"] = $userorderinfo ["ordernmb"];
		$data ["boxid"] = $userorderinfo ["boxid"];
		$data ["boxtype"] = $userorderinfo ["type"];
		$data ["userid"] = $userorderinfo ["userid"];
		if($userorderinfo ["child_id"]){
			$data ["child_id"] = $userorderinfo ["child_id"];
		}
		M("UserOrderSend")->add ( $data );
	}

	/**
       +----------------------------------------------------------
       * 提取一级分类,二级分类,套餐列表数据		(提取公共部分)
       +----------------------------------------------------------  
       * @access  private
       +----------------------------------------------------------
       * @return  Array		一二级分类和套餐列表的数据集合
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	private function mealPublic(){
		$category = M ( "Category" );
		$returnArray=array();

		$list = $category->field ( "cid,cname,pcid,ctype,sortid,concat(cpath,'-',cid) as bpath" )
		->order ( "bpath,cid" )->where ("ctype=1")->select ();

		foreach ( $list as $key => $value ){
			$list [$key] ['signnum'] = count ( explode ( '-', $value ['bpath'] ) ) - 1;
			$list [$key] ['marginnum'] = (count ( explode ( '-', $value ['bpath'] ) ) - 1) * 20;

		}

		$returnArray['list']=$list;
		$returnArray['firstcidlist'] = $category->field ( "cid,cname,pcid,sortid" )->order ( "cname ASC" )->where ( "pcid=0 AND ctype=1" )->select ();
		$returnArray['secondcidlist'] = $category->field ( "cid,cname,pcid,sortid" )->order ( "pcid ASC" )->where ( "pcid>0 AND ctype=1" )->select ();

		// 添加套餐选项
		$packageModel = M ( 'ProductsPackage' );
		$returnArray['packageList'] = $packageModel->where ("status=1")->order('id DESC')->select();
		return $returnArray;
	}

	/**
       +----------------------------------------------------------
       * 批量清除订单上的产品信息
       +----------------------------------------------------------  
       * @access  publiv
       +----------------------------------------------------------
       * @return  AJAX  删除的消息
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	public function clearOrderProductinfo(){
		$orderlist=$_POST["listcheckbox"];
		$orderid_str=implode("','",$orderlist);
		$orderid_str="'".$orderid_str."'";
		if(count($orderlist)>0){
			//删除订单中的商品 user_order_send_productdetail
			$user_order_send_product_mod=M("UserOrderSendProductdetail");
			$sql="DELETE FROM user_order_send_productdetail WHERE CONCAT(orderid,'-',child_id) IN ($orderid_str)";
			$del_flag1=$user_order_send_product_mod->query($sql);

			//删除订单发送表中的商品发送汇总信息
			$user_order_send_mod=M("UserOrderSend");
			$sql2="UPDATE user_order_send SET productnum=0,productprice=0 WHERE  CONCAT(orderid,'-',child_id) IN ($orderid_str)";
			$del_flag2=$user_order_send_mod->query($sql2);
			if($del_flag1 && $del_flag2) {
				$this->ajaxReturn(1,"已经清空当前选择的订单产品信息！",1);
			}else {
				$this->ajaxReturn(0,"当前所选订单中的单品信息已经完成清空操作了！",0);
			}
		}else {
			$this->ajaxReturn(0,"没有选择任何订单，无法进行清空操作！",0);
		}
	}


	/**
       +----------------------------------------------------------
       * 订单列表批量发送邮件
       +----------------------------------------------------------  
       * @access  publiv
       +----------------------------------------------------------
       * @return  AJAX  返回是否加入批量发送列表的信息
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */		
	public function sendMail(){
		$boxname=$_REQUEST['listname'];
		$orderlist=$_POST['listcheckbox'];
		if(!$orderlist)   $this->ajaxReturn(0,"请选择纪录",0);
		$order_mod=D("UserOrderAddr");
		$where['ordernmb']=array('in',$orderlist);
		$where['UserOrderSend.senddate']=array("exp","is not null");
		$where['UserOrderSend.proxysender']=array("exp","is not null");
		$where['UserOrderSend.proxyorderid']=array("exp","is not null");
		$where['UserOrderSend.productnum']=array("gt","0");
		if(!$res=$order_mod->where($where)->select())
		$this->ajaxReturn(0,"没有纪录，请查看是否添加产品和快递单号",0);
		$j=0;
		for($i=0;$i<count($res);$i++)
		{
			ordertasklist($res[$i],1);
			$j++;
		}
		$this->ajaxReturn(1,"有".$j."条纪录成功添加到邮件列表中",1);
	}


	/**
       +----------------------------------------------------------
       * 批量发送短信
       +----------------------------------------------------------  
       * @access  publiv
       +----------------------------------------------------------
       * @return  AJAX  返回是否加入批量发送列表的信息
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */
	public function sendShortMess(){
		$orderlist=$_POST['listcheckbox'];
		if(empty($orderlist) || !is_array($orderlist))
		$this->ajaxReturn(0,"请选择纪录",0);
		$order_mod = D ( "UserOrderAddr" );
		$where['ordernmb']=array('in',$orderlist);
		$where['UserOrderSend.senddate']=array("exp","is not null");
		$where['UserOrderSend.proxysender']=array("exp","is not null");
		$where['UserOrderSend.proxyorderid']=array("exp","is not null");
		$where['UserOrderSend.productnum']=array("gt","0");
		$orders=$order_mod->where($where)->select();
		//	echo $order_mod->getLastSql();
		if(empty($orders)){
			$this->ajaxReturn(0,"没有纪录，请查看是否添加产品和快递单号",0);
		}
		$j=0;
		for($i=0;$i<count($orders);$i++){
			ordertasklist($orders[$i],2);
			$j++;
		}
		$this->ajaxReturn(1,"有".$j."条纪录成功添加到任务列表中",1);
	}

	/**
       +----------------------------------------------------------
       * 修改订单发送信息
       +----------------------------------------------------------  
       * @access  publiv
       +----------------------------------------------------------
       * @param   orderid    订单号
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	public function editOrderSendInfo() {
		$userorder = $_REQUEST ["orderid"];

		if (empty ( $userorder )){
			return false;
		}else{
			$order_arr=explode("-",$userorder);
			$userorderinfo=M("UserOrderSend")->where("orderid=".$order_arr[0]." AND child_id=".$order_arr[1])->find();
			$userorderinfo['linkman']=M("UserOrderAddress")->where("orderid=".$order_arr[0])->getfield("linkman");
			$userorderinfo['orderid']=$userorder;
			$this->assign ( 'userorderinfo', $userorderinfo ); // 用户订单详细数据
			$this->display ( 'editordersendinfo' ); // 指定模板文件
		}
	}

	/**
       +----------------------------------------------------------
       * 执行修改的动作
       +----------------------------------------------------------  
       * @access  publiv
       +----------------------------------------------------------
       * @param   orderid    		订单号
       * @param   proxysender    	快递公司名称
       * @param   proxyorderid      快递单号
       * @param   senddate    		发送日期
       +-----------------------------------------------------------
       * @update zhaoxiang 2013.1.25
     */	
	public function editInfo() {
		$orderinfo= $_POST ["orderid"];
		$order_arr=explode("-",$orderinfo);
		$where['orderid']=$order_arr[0];
		$where['child_id']=$order_arr[1];
		
		$data ["proxysender"] = $_POST ["proxysender"];
		$data ["proxyorderid"] = $_POST ["proxyorderid"];
		$send_data=$data;
		$send_data ["senddate"] = $_POST ["senddate"];
		$ordersend=M ( "UserOrderSend" );
		if (false !==$ordersend->where ( $where )->save ( $send_data )) {
			M("UserOrderProxy")->where($where)->save($data);
			$this->success ( '操作成功' );
		} else {
			$this->error ( '操作失败' . $ordersend->getDbError () );
		}
	}

	/**
       +----------------------------------------------------------
       * 导入快递单号主函数
       +----------------------------------------------------------
       * @access   exerror 		导出问题单号
       +----------------------------------------------------------
       * @param    public	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/4/4
       */		
	public function importExpressNum() {
		if($this->_post('upfile')){
			if($_FILES){
				unset($_SESSION['exportFail']);
				$this->checkErrorDate($_FILES);
			}
		}else if($this->_get('exerror')){
			$this->exportErrorData();
		}else{
			$this->display ();
		}
	}

	/**
       +----------------------------------------------------------
       * 设置上传文件相关参数及验证
       +----------------------------------------------------------
       * @access  $info		上传文件的信息
       +----------------------------------------------------------
       * @param   private	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/4/4
       */		
	private function checkErrorDate($info){
		import('ORG.Net.UploadFile');
		$upload = new UploadFile();														// 实例化上传类
		$upload->maxSize  = pow(2,20) ;													// 设置附件上传大小
		$upload->allowExts  = array('csv','txt');												// 设置附件上传类型
		$upload->savePath = DATA_DIR_ROOT . DIRECTORY_SEPARATOR . "importexpress" . DIRECTORY_SEPARATOR;
		$upload->saveRule = uniqid;  			 										// 设置上传文件规则
		$upload->uploadReplace = true; 			 										// 覆盖同名文件
		if(!$upload->upload()) {
			$this->error($upload->getErrorMsg());										// 上传错误提示错误信息
		}else{
			if (! file_exists ( $upload->savePath )) {
				dir_create ( $upload->savePath );
			}
			$uploadInfo =  $upload->getUploadFileInfo();	// 上传成功 获取上传文件信息
			$this->checkOrderAndIndert(get_csv_data($upload->savePath.$uploadInfo[0]['savename']));
		}
	}

	/**
       +----------------------------------------------------------
       * 保存提交的数据,区分导入成功和失败的数据,失败的放入session以便导出
       +----------------------------------------------------------
       * @access  $list 上传文件的读取的内容
       +----------------------------------------------------------
       * @param   private	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/4/4
       */		
	private function  checkOrderAndIndert($list){
		foreach ($list as $key => $value){
			$data = array(
				'proxysender'=>$value[1],
				'proxyorderid'=>$value[2],
				'senddate'=>$value[3]
			);
			$result = M ( "UserOrderSend" )->where(array('orderid'=>$value[0]))->save($data);
			if($result  != false){
				$info['succ'][] = $value;
			}else{
				$info['fail'][] = $value;
			}
		}
		$_SESSION['exportFail'] = $info['fail'];
		$info['succcount']=count($info['succ']);
		$info['failcount']=count($info['fail']);
		$info['total']=$info['succcount'] + $info['failcount'];
		$this->assign('info',$info);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 导出快递单号出现问题的单号
       +----------------------------------------------------------
       * @access $_SESSION['exportFail']   有问题的快递单号的列表
       +----------------------------------------------------------
       * @param   private	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/4/4
       */	
	private function exportErrorData(){
		$str="订单号,快递公司,运单号,发货日期\n";

		foreach ($_SESSION['exportFail'] as $key => $value){
			$str.='T'.$value[0].','.$value[1].','.$value[2].','.$value[3]."\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK",'导入快递单号有问题的数据-'.date ( "Ymd" )), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 工单处理(订单列表,工单管理公共)
       +----------------------------------------------------------
       * @access type   GET
       +----------------------------------------------------------
       * @param  string	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/2/17
       */	
	public function workorder() {

		$address_mod=M('userOrderAddress');
		$userorder_mod=$_GET["orderType"]==C("ORDER_TYPE_NORMAL")?M('userOrder'):M("UserSelfPackageOrder");

		$users_mod=M('Users');
		$work_mod=M('UserWorkOrder');
		$infomation_mod=M ('UserWorkOrderInfomation');

		if($this->_post('sub')){

			$ordernum=filterVar($this->_post('hiddenOrderNum'));

			$workOrderResult=$work_mod->where(array('order'=>$ordernum))->find();

			if(!$workOrderResult){
				$data=array(
				'order'=>$ordernum,
                'order_type'=>$this->_post('orderType'),
				'email'=>$this->_post('email'),
				'linkman'=>$this->_post('linkman'),
				'telephone'=>$this->_post('telephone'),
				'orderdate'=>time(),
				'status'=>0,
				'cpeople'=>$_SESSION ['loginUserName']
				);
				$result=$work_mod->add($data);

				$map['pid']=0;
				$map['ordernmb']=$ordernum;
                $map['order_type']=$this->_post('orderType');
				$map['reason']=$this->_post('reason');
				$map['note']=$this->_post('des');
				$map['cpeople']=$_SESSION ['loginUserName'];
				$map['time']=time();
				$map['status']=0;

				$result1=$infomation_mod->add($map);

				if($result && $result1){
					$this->success("工单添加成功!","__URL__/checkorder/");
				}else{
					$this->error("工单添加失败!");
				}
			}
			exit();
		}else if($this->_post('subb')){
			$data=array(
			'reason'=>$this->_post('resson'),
			'note'=>$this->_post('des'),
			'cpeople'=>$_SESSION ['loginUserName'],
			'time'=>time(),
			'status'=>$this->_post('status')
			);

			if($this->_post('pid')==0){
				$result=$infomation_mod->where(array('pid'=>0,'ordernmb'=>$this->_post('order')))->save($data);
			}else{
				$result=$infomation_mod->where(array('pid'=>1,'ordernmb'=>$this->_post('order')))->save($data);
			}

			$work_mod->where(array('order'=>$this->_post('order')))->setField('status',$this->_post('status'));

			if($result){
				$this->success("修改成功!");
			}else{
				$this->error("修改失败,请检查!");
			}
			exit();
		}else if ($_POST ['checksub']){

			$data=array(
			'ordernmb'=>$this->_post('order'),
			'reason'=>$this->_post('reason'),
			'note'=>$this->_post('des'),
			'cpeople'=>$_SESSION ['loginUserName'],
			'time'=>time(),
			'status'=>$this->_post('status')
			);

			$result=$infomation_mod->where(array('pid'=>1,'ordernmb'=>$this->_post('order')))->save($data);

			$work_mod->where(array('order'=>$this->_post('order')))->setField('status',$this->_post('status'));

			if($result){
				$this->success("添加成功!","__URL__/checkorder/");
			}else{
				$this->error("添加失败!");
			}
			exit();
		}else if (($_GET ['ordernmb'] && $_GET ['userid']) || $_GET['number']){

            $ordernmb = $_GET ['ordernmb'];
            $userid =  $this->_get('userid');

            if($_GET['number']){
                $where["id"]=$_GET['number'];
            }else{
                $where["order"]=$ordernmb;
            }
            $orderInfo = $work_mod->where ($where)->find ();

            if($orderInfo){
                $orderInfo['undone']=$infomation_mod->where ( array ('ordernmb' => $orderInfo["order"],'pid' => 0) )->find ();
                $orderInfo['complete']=$infomation_mod->where ( array ('ordernmb' =>$orderInfo["order"],'pid' => 1) )->find ();
            }else{
                //用户订单默认地址ID和购买盒子的ID
                $orderInfo=$userorder_mod->where(array('ordernmb'=>$ordernmb))->field('address_id')->find();

                if($orderInfo['address_id']){
                    $userInfo=$address_mod->where(array('id'=>$orderInfo['address_id']))->field('linkman,telphone')->find();
                }else{
                    //如何没有订单默认地址ID 则取第一条
                    $userInfo=$address_mod->where(array('userid'=>$userid))->field('linkman,telphone')->find();
                }

                $orderInfo['linkman']=$userInfo['linkman'];
                $orderInfo['telephone']=$userInfo['telphone'];
                $orderInfo['email']=$users_mod->where (array('userid'=>$userid))->getField ( 'usermail' );

                $orderInfo['order'] = $ordernmb;
            }
            $this->assign ( 'orderInfo', $orderInfo );
            $this->assign("orderType", $_GET["orderType"]);

        }
        $this->display ();
	}

	/**
       +----------------------------------------------------------
       * 工单查询列表
       +----------------------------------------------------------  
       * @access public  
       +----------------------------------------------------------
       * @param  
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.17
     */	
	public function checkorder() {
		$workorder_mod = M ( 'UserWorkOrder' );
		$infomation_mod = M ( 'UserWorkOrderInfomation' );
		import ( "@.ORG.Page" ); // 导入分页类库

		if ($this->_get('tijiao') || $this->_get('export')){
			$where=$this->checkOrderWhere(array_map('filterVar',$_GET));

			if($this->_get('export')){
				$list=$workorder_mod->where ( $where )->order ( array ('orderdate' => 'desc') )->select();
				$this->exportWorkOrder($list);
			}else{
				$count = $workorder_mod->where ( $where )->count ();
				$Page = new Page ( $count, 15 );
				$list = $workorder_mod->where ( $where )->order ( array ('orderdate' => 'desc') )->limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
				$show = $Page->show ();
			}

		} else if ($this->_get('del')) {

			$result=$workorder_mod->where(array('order'=>filterVar($this->_get('del'))))->delete();

			$result1=$infomation_mod->where(array('ordernmb'=>filterVar($this->_get('del'))))->delete();

			if($result && $result1){
				$this->success("删除成功!");
			}else{
				$this->error("删除失败,请检查!");
			}
			exit();
		} else {
			$count = $workorder_mod->count ();
			$Page = new Page ( $count, 15 );

			$list = $workorder_mod->order (array('orderdate' =>'desc'))->limit ( $Page->firstRow . ',' . $Page->listRows )->select();
			$show = $Page->show ();
		}

		foreach($list AS $k => $v){
			$list[$k]['lastpeople']=$infomation_mod->where(array('pid'=>1,'ordernmb'=>$v['order']))->getField('cpeople');
		}
		$this->assign ( 'show', $show );
		$this->assign ( 'data', $list );
		$this->display ();
	}

	/**
       +----------------------------------------------------------
       * 整理工单查询列表参数
       +----------------------------------------------------------  
       * @access public   $arguments   模版传递的GET参数
       +----------------------------------------------------------
       * @param  Array
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.17
     */	
	private function checkOrderWhere($arguments){

		if($arguments['order']){
			$where['order']=$arguments['order'];
		}

		if($arguments['linkman']){
			$where['linkman']=$arguments['linkman'];
		}

		if($arguments['telephone']){
			$where['telephone']=$arguments['telephone'];
		}

		if($arguments['email']){
			$where['email']=$arguments['email'];
		}

		//注册时间
		if($arguments['staorder'] && $arguments['endorder']){
			$where["orderdate"]=array(array('egt',strtotime($arguments['staorder'].'00:00:00')),array('elt',strtotime($arguments['endorder'].'23:59:59')),'AND');
		}else if($arguments['staorder']){
			$where["addtime"]=array('egt',strtotime($arguments['staorder'].'00:00:00'));
		}else if($arguments['endorder']){
			$where["addtime"]=array('elt',strtotime($arguments['endorder'].'23:59:59'));
		}

		if($arguments['status']!=2){
			$where['status']=$arguments['status'];
		}

		if($arguments['problem'] || $arguments['reason']){
			$map=empty($arguments['problem'])?$arguments['reason']:$arguments['problem'];
			$orderArray=M('UserWorkOrderInfomation')->where(array('reason'=>$map,'pid'=>1))->field('ordernmb')->select();

			foreach ($orderArray as $key=>$value){
				$in[]=$value['ordernmb'];
			}
			$where['order']=array('exp','IN('.implode(',',$in).')');
		}

		return $where;
	}

	/**
       +----------------------------------------------------------
       *锁定,是否可以修改工单
       +----------------------------------------------------------  
       * @access public   只允许第一个人修改,其他人同时间不能修改
       +----------------------------------------------------------
       * @param  Array
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.17
       * @update zhaoxiang 2013.2.20
     */		
	public function isChanngeWorkOrder(){
		$infomation_mod=M ('UserWorkOrderInfomation');
        $ordernmb =$this->_post('ordernum');
		if($ordernmb){
			$isTrue=$infomation_mod->where(array('ordernmb'=>$ordernmb,'pid'=>1))->find();
			if(empty($isTrue)){
				$data=array(
				'pid'=>1,
				'cpeople'=>$_SESSION ['loginUserName'],
				'time'=>time(),
				'status'=>2,
                'ordernmb	'=>$ordernmb
				);
				$result=$infomation_mod->add($data);
                $infomation_mod->where(array("id"=>$result))->save(array("ordernmb"=>$ordernmb));
				if($result){
					$this->ajaxReturn(1,'OK',1);
				}else{
					$this->ajaxReturn(0,'ERROR',0);
				}
			}else{
				if(($_SESSION ['loginUserName']==$isTrue['cpeople']) || ($isTrue['status'] != 2)){
					$infomation_mod->where(array('ordernmb'=>$this->_post('ordernum'),'pid'=>1))->setField('status',2);
					$this->ajaxReturn(1,'OK',1);
				}else{
					$this->ajaxReturn(0,$isTrue['cpeople'],0);
				}
			}
		}
	}

	/**
       +----------------------------------------------------------
       *导出工单列表
       +----------------------------------------------------------  
       * @access private
       +----------------------------------------------------------
       * @param  Array
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.2.17
     */			
	private function exportWorkOrder($list){
		$infomation=M('UserWorkOrderInfomation');
		$str="订单号,邮箱,联系人,盒子信息,问题描述,创建人,创建时间,处理结果,处理人\n";

		foreach ($list as $key => $value){
			$str.='T'.$value['order'].",".$value['email'].",".$value['linkman'].",";
			$undone=$infomation->where(array('pid'=>0,'ordernmb'=>$value['order']))->getField('reason');
			$complete=$infomation->where(array('pid'=>1,'ordernmb'=>$value['order']))->field('reason,cpeople')->find();
			$str.=$undone.",".$value['cpeople'].",".date('Y/m/d H:i',$value['orderdate']).",".$complete['reason'].",".$complete['cpeople']."\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK",'工单导出-'.date ( "Ymd" )), $str );
		exit();
	}

	/**
	 * 获取用户地址本数据
	 * 用于修改订单用户地址信息
	 * 
	 */
    function getAddress()
    {
        $user_address = M('userOrderAddress');
        $where['id'] = $_GET['userOrderAddressId'];
        $userOrderAddress = $user_address->where($where)->field('id,linkman,telphone,province_area_id,city_area_id,district_area_id,address,postcode,if_active')->find();
        $userOrderAddress["provinceName"] = M("area")->field("title")->getByAreaId($userOrderAddress["province_area_id"]);
        $userOrderAddress["provinceName"] = $userOrderAddress["provinceName"]["title"];
        $userOrderAddress["cityName"] = M("area")->field("title")->getByAreaId($userOrderAddress["city_area_id"]);
        $userOrderAddress["cityName"] = $userOrderAddress["cityName"]["title"];
        $userOrderAddress["districtName"] = M("area")->field("title")->getByAreaId($userOrderAddress["district_area_id"]);
        $userOrderAddress["districtName"] = $userOrderAddress["districtName"]["title"];
        $this->assign("oldAdress",$userOrderAddress);
        $this->assign("orderId", $_GET["orderid"]);
        $this->assign("selfPackageOrderId", $_GET["selfPackageOrderId"]);
        $this->assign("userid", $_GET["userid"]);
        $this->display();
    }

    public function saveAddress(){
        $orderId = $_POST["orderId"];
        $selfPackageOrderId=$_POST["selfPackageOrderId"];
        $userid = $_POST["userid"];
        if((!$orderId && !$selfPackageOrderId) || !$userid){
            $this->ajaxReturn(array("status"=>"n","info"=>"添加失败"), "JSON");
        }
        $count = M("UserOrderAddress")->where(array("userid"=>$this->userid))->count();
        if($count >= C("MAX_ADDRESS_NUM_PER_USER")){
            $this->ajaxReturn(array("status"=>"n","info"=>"超出限定数量，添加失败"), "JSON");
        }
        if(!isset($_POST["province_area_id"]) || !isset($_POST["city_area_id"]) || !isset($_POST["district_area_id"])){
            $this->ajaxReturn(array("status"=>"n","info"=>"请选择省市区，添加失败"), "JSON");
        }
        if( !isset($_POST["linkman"]) || !isset($_POST["telphone"]) || !isset($_POST["address"]) || !isset($_POST["postcode"])){
            $this->ajaxReturn(array("status"=>"n","info"=>"请填写完整信息，添加失败"), "JSON");
        }
        $address["userid"]=$userid;
        $address["linkman"]=$_POST["linkman"];
        $address["telphone"]=$_POST["telphone"];
        $address["province_area_id"]=$_POST["province_area_id"];
        $address["city_area_id"]=$_POST["city_area_id"];
        $address["district_area_id"]=$_POST["district_area_id"];
        $address["address"]=$_POST["address"];
        $address["postcode"]=$_POST["postcode"];
        $address["if_active"] = $_POST["if_active"]? C("USER_ORDER_ADDRESS_ACTIVE"):C("USER_ORDER_ADDRESS_NOT_ACTIVE");
        $address["addtime"]=date("Y-m-d H:i:s",time());
        if($address["if_active"] && $address["if_active"] == C("USER_ORDER_ADDRESS_ACTIVE")){
            M("UserOrderAddress")->where(array("userid"=>$userid))->save(array("if_active"=>C("USER_ORDER_ADDRESS_NOT_ACTIVE")));
        }
        $addressId = M("UserOrderAddress")->add($address);
        if($orderId){
            M("UserOrder")->where(array("ordernmb"=>$orderId))->save(array("address_id"=>$addressId));
        }
        if($selfPackageOrderId){
            M("UserSelfPackageOrder")->where(array("ordernmb"=>$orderId))->save(array("address_id"=>$addressId));
        }
        $this->ajaxReturn(array("status"=>"y","info"=>"添加成功"), "JSON");
    }

		/**
	 * 用于获取用户的发货进度
	 * */
		function send_proxy_info(){
			$order_arr=explode("-",$_REQUEST['orderid']);
			$orderid=$order_arr[0];
			$childid=$order_arr[1];
			$childid=$_REQUEST['childid'];
			$where['orderid']=$orderid;
			if($childid) $where['child_id']=$childid;
			$proxyinfo=M("UserOrderProxy")->where($where)->find();
			echo "订单发货状态：<br>";
			if(!proxyinfo)
			{
				echo "订单不存在或者未发货";
			}else{
				echo "<span style='font-size:14px'>";
				if($proxyinfo['proxyinfo']=="")
				{
					echo "订单己经完成";
				}else{
					$proxyinfo=explode("\r\n", $proxyinfo['proxyinfo']);
					foreach ($proxyinfo as $val)
					{
						echo nl2br($val)."<br>";
					}
				}

				echo "</span>";
			}

		}

		/**
       +----------------------------------------------------------
       * 返回订单来源参数
       +----------------------------------------------------------  
       * @access public
       +----------------------------------------------------------
       * @type	ajax
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.30
     */		
		public function returnOrderResour(){
			$result = M("userOrder")->DISTINCT(true)->where(array('fromid'=>$this->_post('fromid')))->field('frominfo')->select();
			if($result !== false){
				$this->ajaxReturn($result,'返回成功!',1);
			}else{
				$this->ajaxReturn(null,'返回失败!',0);
			}
		}
}
?>