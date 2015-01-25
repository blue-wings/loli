 <?php

class ProductAction extends CommonAction{


	public function index(){

		$where = array();
		$pname = trim($this->_request("pname"));
		$pid=trim($this->_request("pid"));
		$brandcid = $_REQUEST['brandcid'];
		$product=new ProductModel();

		if (!empty($pname)) {
			$where['pname'] = array('like',"%".$pname."%");
		}
		if($_GET['bid']){
			$where['brandcid']=filterVar($_GET['bid']);
		}
		if (!empty($brandcid)) {
			$where['brandcid'] = $brandcid;
		}
		if($_REQUEST['status']!==null && $_REQUEST['status']!=='')
		{
			$where['status']=$_REQUEST['status'];
		}
		if($pid){
			$where['pid']=$pid;
		}

		if($this->_get('brandtype')){
			$where['brandcid']=array('exp',"IN(SELECT id FROM products_brand WHERE brandtype ='".$this->_get('brandtype')."')");
		}

		if($this->_get('cooperation')){
			if($this->_get('cooperation')==1){
				$where['pid']=array('exp',"IN(SELECT relation_id FROM `inventory_item`)");
			}else{
				$where['pid']=array('exp',"NOT IN(SELECT relation_id FROM `inventory_item`)");
			}
		}

		//筛选加V
		if($this->_get("channelv")=== '0'){
			$where['if_super']=0;
		}else if($this->_get("channelv")==1){
			$where['if_super']=1;
		}

		//如果是导出excel添加导出代码
		if(isset($_REQUEST['outputexcel'])){
			$list=$product->order('pid desc')->where($where)->select();
			//整理列表数据,根据CID获取CNAME
			foreach($list as $key=>$value){
				$list[$key]['firstcname']= $this->getCname($list[$key]['firstcid']);
				$list[$key]['secondcname']= $this->getCname($list[$key]['secondcid']);
				$list[$key]['brandname']= $this->getCname($list[$key]['brandcid']);
			}
			$str = "ID,产品名称,分类,子类,品牌名称,容量,价格,库存\n";
			foreach ($list as $key=>$value){
				$str .= $value['pid'].",".$value['pname'].",".$value['firstcname'].",".$value['secondcname'].",".$value['brandname'].",".$value['goodssize'].",".$value['goodsprice'].",".$value['inventory']."\n";
			}
			outputExcel(iconv("UTF-8","GBK",date("Y-m-d")."查询产品列表"),$str);
			exit;
		}

		if($this->_get('order')){
			$order=$this->returnOrdertype($_GET);
		}else{
			$order= "pid DESC";
		}


		import("@.ORG.Page"); //导入分页类库
		$count=$product->where($where)->count(); //记录总数
		$p = new Page($count, 25); //每页显示25条记录
		$list=$product->where($where)->limit($p->firstRow . ',' . $p->listRows)->field("product_alias",true)->order($order)->select();
		$page = $p->show();

		//整理列表数据,根据CID获取CNAME
		foreach($list as $key=>$value){
			$list[$key]['firstcname']= $this->getCname($list[$key]['firstcid']);
			$list[$key]['secondcname']= $this->getCname($list[$key]['secondcid']);
			$list[$key]['brandname']= M("ProductsBrand")->where("id={$list[$key]['brandcid']}")->getField("name");
		}

		//获取品牌名称的下拉列表
		$brandname=$_REQUEST['brandname'];
		$brandlist=M("ProductsBrand")->where("name like '%$brandname%'")->select();
		$categoryModel=M("Category");
		$this->assign("brandcid", $brandcid);
		$this->assign("brandlist", $brandlist);
		$this->assign("page", $page);
		$this->assign('list',$list);
		$this->display();
	}
	
	
	/**
	 * 产品图片管理
	 * @param string $ac [add--增加，top--置顶,del---删除]
	 * @param string $pid 产品id
	 * @author litingting
	 */
	public function picManager(){
		$ac=$_REQUEST['ac'];
        $pid = $_REQUEST['pid'];
        $products_pic = M("ProductsPic");
		switch($ac){
			case "add":
				$_POST['toptime'] = time();
				if(empty($_POST['pid']) || empty($_POST['pic_url']) ){
					$this->ajaxReturn(1,"缺少参数",0);
				}
				if( $products_pic->add($_POST)){
					$this->ajaxReturn(1,"增加图片成功",1);
				}else{
					$this->ajaxReturn(1,"增加图片失败",0);
				}
				break;
			case "top":
				 $flag=$products_pic->where("id=".$_POST['id'])->setField("toptime",time());
				 if($flag){
				 	$this->ajaxReturn(1,"置顶成功",1);
				 }else{
				 	$this->ajaxReturn(0,"置顶失败",0);
				 }
				break;
			case "del":
				if( $products_pic->delete($_POST['id'])){
					$this->ajaxReturn(1,"删除图片成功",1);
				}else{
					$this->ajaxReturn(1,"删除图片失败",0);
				}
				break;
		}
		if(empty($pid)){
			echo "缺少参数，请重试";
		}
		
		$list =$products_pic->where("pid=".$pid)->order("toptime desc")->select();
		$this->assign("list",$list);
		$this->display();
		
 	}

	//根据CID获取CNAME
	public function getCname($cid){
		if(empty($cid)) return null;
		$category=M("Category");
		return $category->where("cid=".$cid)->getField('cname');

	}

	public function add(){
		$this->display();
	}

	/**
	 * 执行添加产品信息操作
	 */
	public function addproduct(){
		//print_r($_POST);exit;
		$product=new ProductModel();
		
		if($data=$product->create()){
            $data["need_postage"]=$_POST['need_postage'] ? $_POST['need_postage'] : C("PRODUCT_NOT_NEED_POSTAGE") ;
            $data["need_pre_share"]=$_POST['need_pre_share'] ? $_POST['need_pre_share'] : C("PRODUCT_NOT_NEED_PRE_SHARE") ;
			$data['sort_num']=$_POST['sort_num'] ? $_POST['sort_num'] : 0 ;
            $data['pre_share_sort_num']=$_POST['pre_share_sort_num'] ? $_POST['sort_num'] : 0 ;
            $data["price"]=$data["price"]*100;
            $data["member_price"]=$data["member_price"]*100;
			
			$pid=$product->add($data);
			if(pid){
				if($data["inventory_item_id"] && $data["inventory"]){
					try {
						D("InventoryItem")->shelveProductInventory($pid, $data["inventory_item_id"], $data["inventoryInc"]);
					}catch(Exception $e){
						$this->error($e->getMessage());	
					}
				}
                $this->success("产品添加成功!ID为:{$pid}",U("Product/index"));
			}else{
				$this->error("产品添加失败：".$product->getDbError());
			}
		}else {
			$this->error('数据验证( '.$product->getError().' )');
		}
	}

	//改变产品加V状态
	function changevStatus(){
		if($this->_post("productid")){
			$result = M("Products")->where(array('pid'=>$this->_post('productid')))->setField("status",$this->_post('status'));
		}else if($this->_post("brandid")){
			$result = M("ProductsBrand")->where(array('id'=>$this->_post('brandid')))->setField("if_super",$this->_post('status'));
		}

		if($result){
			$this->ajaxReturn($this->_post("status"),'success',1);
		}else{
			$this->ajaxReturn($this->_post("status"),'fail',0);
		}
		exit();
	}

	public function edit(){
		$pid=$_REQUEST["pid"];
		$product=new ProductModel();
		$productinfo=$product->getByPid($pid);

        $productinfo["price"]=bcdiv($productinfo["price"], 100, 2);
        $productinfo["member_price"]=bcdiv($productinfo["member_price"], 100, 2);
		$this->assign('usertype_list_selected',D("Products")->getForUserDefine());

		$this->assign("productinfo",$productinfo);
		$this->display("add");
	}


	public function editproduct(){
        $_REQUEST["price"]=$_REQUEST["price"]*100;
        $_REQUEST["member_price"]=$_REQUEST["member_price"]*100;
		$productModel=new ProductModel();
		$data=$productModel->create();
        if(false!==$productModel->where("pid=".$_REQUEST['pid'])->save($data)){
            $this->success("操作成功");
        }else{
            $this->error("操作失败");
        }
	}


	/**
	 * 删除产品信息
	 * 附件删除（略）
	 */
	public function del(){
		$pid=$_REQUEST["pid"];
		if(!empty($pid)){
			$product=new ProductModel();
			if(false!==$product->delete($pid)){
				$this->success('操作成功');
			}else{
				$this->error('操作失败：'.$product->getDbError());
			}
		}else{
			$this->error('错误请求');
		}
	}


	/**
     *文件上传
     */
	protected function _upload() {
		import("@.ORG.UploadFile");
		//导入上传类
		$upload = new UploadFile();
		//设置上传文件大小
		$upload->maxSize = 3292200;
		//设置上传文件类型
		$upload->allowExts = explode(',', 'jpg,gif,png,jpeg');
		//设置附件上传目录
		$upload->savePath = PRODUCTIMG_DIR_ROOT;
		//设置上传文件规则
		$upload->saveRule = uniqid;
		if (!$upload->upload()) {
			//捕获上传异常
			$this->error($upload->getErrorMsg());
		} else {
			//取得成功上传的文件信息
			$uploadList = $upload->getUploadFileInfo();
			return $uploadList[0]["savename"];
		}
	}

	/*
	* 被客户端的AJAX调用
	* 参数：cid
	*/
	public function ajaxGetProductList(){
		$cid=$_REQUEST["cid"];
		if(!$cid) return;
		$inventory_item_mod=M("InventoryItem");
		$where="(firstcid=$cid OR secondcid=$cid) AND (`inventory_estimated`>0)"; //条件是分类及库存为>0的正数
		$list=$inventory_item_mod->where($where)->select();
		//echo $inventory_item_mod->getLastSql();
		foreach($list as $key=>$value){
			$availd=$value['inventory_estimated'];
			$outstr.="<option value='".$value['id']."'>".$value['name']."(估存:$availd)(价：".$value['price'].")</option>";
		}
		echo $outstr;
	}
	/*商品品牌AJAX调用*/
	public function ajaxGetbrandlist(){
		$cid=$_REQUEST["brandid"];
		if(!$cid) return;
		$inventory_item_mod=M("InventoryItem");
		$where="(brandid=$cid) AND (`inventory_estimated`>0)"; //条件是分类及库存为>0的正数
		$list=$inventory_item_mod->where($where)->select();
		// 		echo $inventory_item_mod->getLastSql()
		foreach($list as $key=>$value){
			$availd=$value['inventory_estimated'];
			$outstr.="<option value='".$value['id']."'>".$value['name']."(估存:$availd)(价：".$value['price'].")</option>";
		}
		echo $outstr;
	}

	/*
	* 被客户端的AJAX调用
	* 参数：产品名称关键字
	*/
	public function ajaxGetProductListByKey(){
		$kw=$_REQUEST["searchkeyword"];
		if(empty($kw)) return;
		$inventory_item_mod=M("InventoryItem");
		$where="(name like '%$kw%') AND (`inventory_estimated`>0)"; //条件是分类及库存为>0的正数
		$list=$inventory_item_mod->where($where)->select();
		foreach($list as $key=>$value){
			$availd=$value['inventory_estimated'];
			$outstr.="<option value='".$value['id']."'>".$value['name']."(估存:$availd)(价：".$value['price'].")</option>";
		}
		echo $outstr;
	}

	/**
	 * 统计产品的数量及价格
	 */
	public function ajaxCalculateProductList(){

		$cids=$_REQUEST["cids"];
		$userid=$_REQUEST["userid"];
		$orderid_info=$_REQUEST["orderid"];
		
		//给子订单配货 update by penglele 2013-12-18 9:38:05
		$orderid_arr=explode("-",$orderid_info);
		$orderid=$orderid_arr[0];
		$child_id=$orderid_arr[1];
		
		
		$delid= $_REQUEST['delid'][0];
		//if(empty($cids)) return;
		$arraycid=explode(",",rtrim($cids,','));

		$inventory_item_mod=M("InventoryItem");
		$totalgoodsprice=0.0;
		/**动态入库**/
		$UserOrderSendProductdetail=M("UserOrderSendProductdetail");
		//在更新用户产品表时先清除以前的记录update by penglele 2013-12-18 9:38:05
		$where="orderid=".$orderid." AND child_id=".$child_id;
		
		$UserOrderSendProductdetail->where($where)->delete();
		$totalgoodsprice=0;
		$productcount=0;



		for($i=0;$i<count($arraycid);$i++){
			//			$where="pid=".$arraycid[$i];
			if($productinfo=$inventory_item_mod->getById($arraycid[$i])){
				//$result[]=$productinfo;
				$productcount++;
				/*根据已经选择的产品修改订单产品关系表*/
				$send_product_data["userid"]=$userid;
				
				//update by penglele 2013-12-18 9:38:05
				$send_product_data["orderid"]=$orderid;
				$send_product_data['child_id']=$child_id;
				
				$send_product_data["productid"]=$productinfo["id"];
				$send_product_data["productprice"]=$productinfo["price"];
				$UserOrderSendProductdetail->add($send_product_data);
				$totalgoodsprice+=$productinfo['price'];
				//$totaltrialprice+=$productinfo['trialprice'];
			}
		}
		//		$productcount=count($result); //得到产品列表总数



		//更新user_send_order表中的producenum,productprice字段

		$UserOrderSend=M("UserOrderSend");
		$data=array(
		"productnum"=>$productcount,
		"productprice"=>$totalgoodsprice
		);
		//update by penglele 2013-12-18 9:38:05
		$UserOrderSend->where("orderid=".$orderid." AND child_id=".$child_id)->save($data);

		//更新产品库存减少数
		$this->updateProductInventoryreduced($cids,$delid);

		echo "已选数量：<b>".$productcount."</b> 件<br><br>总价: <font color=red>".$totalgoodsprice." </font>元";
		exit;
	}

	/**
	 * 统计产品的数量及价格(批量)
	 */
	public function ajaxCalculateProductListMuch(){
//exit("123");

		$userorder = $_REQUEST['userorder'];
		$delid= $_REQUEST['delid'][0];
		//转义
		$userorder_arr = json_decode(stripslashes($userorder),true);

		$cids=$_REQUEST["cids"];
		$arraycid=explode(",",$cids);
		$inventory_item_mod=M("InventoryItem");
		$totalgoodsprice=0.0;
		/**动态入库**/
		$UserOrderSendProductdetail=M("UserOrderSendProductdetail");

		$UserOrderSend=M("UserOrderSend");

		$totalnum=$totalprice=0;
		//批量操作
		foreach ($userorder_arr as $v){
			//在更新用户产品表时先清除以前的记录update by penglele 2013-12-18 9:38:05
			$orderid_arr=explode("-",$v['orderid']);
			$orderid=$orderid_arr[0];
			$child_id=$orderid_arr[1];
			
			
			$UserOrderSendProductdetail->where("orderid=".$orderid." AND child_id=".$child_id)->delete();
			$totalgoodsprice=0;
			$totaltrialprice=$productcount=0;
			for($i=0;$i<count($arraycid);$i++){
				//$where="pid=".$arraycid[$i];
				if($productinfo=$inventory_item_mod->getById($arraycid[$i])){
					$productcount++;
					/*根据已经选择的产品修改订单产品关系表*/
					$send_product_data["userid"]=$v['userid'];
					
					//update by penglele 2013-12-18 9:38:05
					$send_product_data["orderid"]=$orderid;
					$send_product_data['child_id']=$child_id;
					
					$send_product_data["productid"]=$productinfo["id"];
					$send_product_data["productprice"]=$productinfo["price"];
					$UserOrderSendProductdetail->add($send_product_data);
					$totalgoodsprice+=$productinfo['price']; //正品价格总和
					//	$totaltrialprice+=$productinfo['trialprice']; //试验品价格总和
				}
			}
			//			$productcount=count($result); //得到产品列表总数

			//更新user_send_order表中的producenum,productprice字段
			$data=array(
			"productnum"=>$productcount,
			"productprice"=>$totalgoodsprice
			);
			$UserOrderSend->where("orderid=".$orderid." AND child_id=".$child_id)->save($data);
		}

		//更新产品库存减少数
		$this->updateProductInventoryreduced($cids,$delid);
		echo "订单数量：".count($userorder_arr)."<br />已选数量：<b>".$productcount."</b> 件<br><br>总价: <font color=red>".$totalgoodsprice." </font>元";
		exit;
	}



	/**
	 *根据当前UserOrderSendProductdetail中的情况，更新库存减少的数据
	 */
	public function updateProductInventoryreduced($ids,$delid){
		if($delid)
		$ids.=$delid;
		else
		$ids=rtrim($ids,",");
		//	echo $ids;
		//$UserOrderSendProductdetail=M("UserOrderSendProductdetail");
		//$productlist=$UserOrderSendProductdetail->query("SELECT productid, COUNT( productid ) AS PT FROM `user_order_send_productdetail` GROUP BY `productid`");
		D("InventoryItem")->updateInventoryByIds($ids);

	}


	public function trunBuyUrl(){
		$product_model=M("Products");
		$product_list=$product_model->where("buyurl!=''")->select();
		//print_r($product_list);
		$rstotal=count($product_list);
		$buychannel_model=M("ProductsBuyChannel");
		for($i=0;$i<$rstotal;$i++){
			$pid=$product_list[$i]["pid"];
			$buyurl=$product_list[$i]["buyurl"];
			$array_buyurl=explode("\n",$buyurl);
			foreach($array_buyurl as $item){
				$item=trim($item);
				if(!empty($item)){
					//在非空行的判断基础上做处理
					$array_item=explode("|||",$item);
					if(count($array_item)==2){
						$data["pid"]=$pid;
						$data["basehit"]=0;
						$data["realhit"]=rand(50,200);
						$data["channelname"]=$array_item[1];
						$data["url"]=$array_item[0];
						$data["addtime"]=date("Y-m-d H:i:s");
						$buychannel_model->add($data);
					}
				}
			}
		}
	}



	//套餐列表
	public function packageList()
	{
		$packageModel=M('ProductsPackage');
		$this->_list($packageModel);
		$this->display();
	}

	//添加套餐
	public function addPackage()
	{
		if (!empty($_REQUEST['isshow'])) {
			$name = $_REQUEST['name'];
			$package = $_REQUEST['cids'];
			$arraycid = explode(',', $_REQUEST['cids']);
			$num = count($arraycid);

			$inventory_item_mod = M('InventoryItem');
			for($i=0;$i<$num;$i++){
				$productinfo=$inventory_item_mod->getById($arraycid[$i]);
				if(!empty($productinfo)){
					$price+=$productinfo['price'];
				}
			}
			$status = intval($_REQUEST['status']);
			$addtime = time();
			$data=compact('name','num','price','package','status','addtime');
			$packagemodel = M('ProductsPackage');
			$ret=$packagemodel->add($data);
			//			$this->assign('jumpUrl', '/Product/packageList');
			if(false===$ret){
				$this->error('添加失败。');
			}else {
				$this->success('添加成功。');
			}
		}else {
			$category=M("Category");
			$where="ctype=1";
			$list=$category
			->field("cid,cname,pcid,ctype,sortid,concat(cpath,'-',cid) as bpath")
			->order("bpath,cid")
			->where($where)->select();
			$brand_list=M('ProductsBrand')->field('id,name')->select();
			//$brand_list=$category->field("cid,cname,pcid,ctype,sortid,concat(cpath,'-',cid) as bpath")->where('ctype=3')->select();
			foreach($list as $key=>$value){
				$list[$key]['signnum']= count(explode('-',$value['bpath']))-1;
				$list[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
			}
			$this->assign('id',"");
			$this->assign('clist',$list);
			$this->assign('brand_list',$brand_list);
			$this->assign('action',__URL__."/addPackage/");
			$this->display('editpackage');
		}
	}
	//修改套餐
	public function updPackage()
	{
		if (!empty($_REQUEST['isshow'])) {
			$packagemodel = M('ProductsPackage');

			$packagemodel ->id = $_REQUEST['id'];
			$packagemodel ->name = $_REQUEST['name'];
			$packagemodel ->package = $_REQUEST['cids'];
			$arraycid = explode(',', $_REQUEST['cids']);
			$packagemodel ->num = count($arraycid);

			$inventory_item_mod = M('InventoryItem');
			for($i=0;$i<count($arraycid);$i++){
				$productinfo=$inventory_item_mod->getById($arraycid[$i]);
				if(!empty($productinfo)){
					$price+=$productinfo['price'];
				}
			}
			$packagemodel->price = $price;
			$packagemodel->status = intval($_REQUEST['status']);
			$packagemodel->addtime = time();
			$ret=$packagemodel->save();
			if(false===$ret){
				$this->error('更新失败。');
			}else {
				$this->success('更新成功。');
			}
		}else {
			$id = intval($_REQUEST['id']);

			$category=M("Category");
			$where="ctype=1";
			$list=$category
			->field("cid,cname,pcid,ctype,sortid,concat(cpath,'-',cid) as bpath")
			->order("bpath,cid")
			->where($where)->select();
			$brand_list=M('ProductsBrand')->field('id,name')->select();
			foreach($list as $key=>$value){
				$list[$key]['signnum']= count(explode('-',$value['bpath']))-1;
				$list[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
			}

			$packagemodel = M('ProductsPackage');
			$packagelist = $packagemodel->getById($id);
			$package_arr = explode(',',$packagelist['package']);
			$inventory_item_mod = M('InventoryItem');
			for($i=0;$i<count($package_arr);$i++){
				$own_product[] = array('id'=>$package_arr[$i],'name'=>$inventory_item_mod->where('id='.$package_arr[$i])->getField('name'));
			}

			$this->assign('id',$id);
			$this->assign('name',$packagelist['name']);
			$this->assign('brand_list',$brand_list);
			$this->assign('status',$packagelist['status']);
			$this->assign('own_product',$own_product);
			$this->assign('clist',$list);
			$this->assign('action',__URL__."/updPackage/");
			$this->display('editpackage');
		}
	}
	//更改套餐状态
	public function setPackageStatus()
	{
		$id = intval($_REQUEST['id']);
		$status = intval($_REQUEST['status']);
		if(empty($id) || !isset($status)){
			$this->error("参数不全。");
		}
		$status = $status == 1 ? 0 : 1;
		$options = array('id' => $id);
		$packagemodel = M('ProductsPackage');
		$result = $packagemodel->where($options)->setField('status',$status);
		if ($result !== false) {
			$this->success('状态更改成功');
		} else {
			$this->error('状态更改失败！');
		}
	}

	//套餐产品
	public function packageProduct()
	{
		$packagemodel = M('ProductsPackage');
		if(!empty($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$packagelist = $packagemodel->getById($id);
			$package_arr = explode(',',$packagelist['package']);
		}
		if(!empty($_REQUEST['cids'])){
			$cids = $_REQUEST['cids'];
			$package_arr = explode(',',$cids);
		}
		if(empty($package_arr)) return false;
		$count = empty($_REQUEST['count']) ? 0 : intval($_REQUEST['count']);
		$inventory_item_mod = M('InventoryItem');
		$no_product =array();
		for($i=0;$i<count($package_arr);$i++){

			$inventoryInfo = $inventory_item_mod->where(array('id'=>$package_arr[$i]))->field('inventory_estimated,name')->find();

			$ret_temp = array('id'=>$package_arr[$i],'name'=>$inventoryInfo['name'],'estimated'=>$inventoryInfo['inventory_estimated']);

			if($inventoryInfo['inventory_estimated'] <= $count){
				$no_product[] = $ret_temp;
			}else{
				$own_product[] = $ret_temp;
			}
		}

		if($this->isAjax()){
			echo json_encode(array($own_product,$no_product));
			exit;
		}else {
			return array($own_product,$no_product);
		}
	}

	public function channel()
	{
		$pid=$_GET["pid"]?$_GET["pid"]:$_REQUEST["pid"];
		if(empty($pid)) {
			$this->error("参数错误");
			exit;
		}
		$cha=M("ProductsBuyChannel");
		$products_model=M("Products");
		$productinfo=$products_model->getByPid($pid);
		$list=$cha->where("pid=".$pid)->order("sortnum DESC,id ASC")->select();
		$this->assign('list',$list);
		$this->assign("productinfo",$productinfo);
		$this->display();
	}

	public  function  channeladd()
	{

		if($this->isPost())
		{
			$cha=M("ProductsBuyChannel");
			$data['pid']=$_POST['pid'];
			$data['channelname']=$_POST['cha'];
			$data['url']=$_POST['url'];
			$data['basehit']=(int)$_POST['dea'];
			$data['realhit']=(int)$_POST['real'];
			$data['sortnum']=(int)$_POST['sortnum'];
			$data['addtime']=date("Y-m-d H:i:s");
			$data['price']=$_POST['price'];
			if(false!=$cha->add($data)){
				$this->success("insert ok！");
			}
			else {
				$this->error("insert fail!");
			}
		}
	}

	public  function  channeldel()
	{
		if(isset($_GET['id']))
		{
			$cha=M("ProductsBuyChannel");
			$data['id']=$_GET['id'];
			$res=$cha->where($data)->delete();
			$pid=$_GET['pid'];
		}

		$this->redirect("Product/channel",array('pid'=>$pid));
	}


	public function channelupdate()
	{
		if($_REQUEST['id'])
		{
			$cha=M("ProductsBuyChannel");
			$data['id']=(int)$_REQUEST['id'];
			$list=$cha->where($data)->find();
			$this->assign('list',$list);
			$this->display();
		}

	}


	public function updatecha()
	{
		if(isset($_POST['sub']))
		{
			$cha=M("ProductsBuyChannel");
			$data['id']=$_POST['cid'];
			$data['pid']=$_POST['pid'];
			$data['channelname']=$_POST['channelname'];
			$data['url']=$_POST['url'];
			$data['basehit']=$_POST['basehit'];
			$data['realhit']=$_POST['realhit'];
			$data['sortnum']=$_POST['sortnum'];
			$data['price']=$_POST['price'];
			$list=$cha->save($data);
			$this->redirect("Product/channel",array('pid'=>$data['pid']));
		}
	}

	public function tryoutInfo(){
		$try_mod=M('productsTryoutInfo');
		$users_mod=M('users');
		$products_mod=M('products');
		import("@.ORG.Page"); //导入分页类库

		if(!empty($_REQUEST["userid"])) {
			$where["userid"]=trim($_REQUEST["userid"]);
		}else if(!empty($_REQUEST["nickname"])) {
			$where["userid"]=$users_mod->where(array("nickname"=>trim($_REQUEST["nickname"])))->getField('userid');
		}
		if(!empty($_REQUEST["productid"])) {
			$where["pid"]=trim($_REQUEST["productid"]);
		}else if(!empty($_REQUEST["pname"])) {
			$where["pid"]=$products_mod->where(array("pname"=>trim($_REQUEST["pname"])))->getField('pid');
		}

		$count=$try_mod->where($where)->count(); //记录总数
		$p = new Page($count, 15,$strpageparam); //每页显示15条记录
		$list=$try_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('addtime DESC')->select();


		foreach($list as $key=>$value){
			$list[$key]['username']=$users_mod->where(array('userid'=>$list[$key]['userid']))->getField('nickname');//users表 nickname用户昵称
			$list[$key]['pname']=$products_mod->where(array('pid'=>$list[$key]['pid']))->getField('pname');//products表 pname产品名
		}

		$page = $p->show();
		$this->assign("page", $page);
		$this->assign('list',$list);
		$this->display();

	}

	//购买渠道管理  add  by zhaoxiang
	public  function buyChannelList(){
		$channel=M('productsBuyChannel');
		$products=M('products');
		import("ORG.Util.Page");


		if(trim($this->_get("url"))){
			$where['url']=array('like',"%".trim($this->_get("url"))."%");
		}

		if(trim($this->_get("channelname"))){
			$where['channelname']=array('like',"%".trim($this->_get("channelname"))."%");
		}

		if(trim($this->_get("pid"))){
			$where['pid']=trim($this->_get("pid"));
		}

		if(trim($this->_get("pname"))){
			$where['pid']=$products->where(array('pname'=>trim($this->_get("pname"))))->getField('pid');
		}

		$count = $channel->where($where)->count();

		$p = new Page($count,15);

		$list=$channel->where($where)->order('url ASC')->limit($p->firstRow.','.$p->listRows)->select();

		foreach($list as $key=>$value){
			$list[$key]['pname']=$products->where(array('pid'=>$list[$key]['pid']))->getField('pname');
			$list[$key]['difference']=(int)$list[$key]['realhit']-(int)$list[$key]['basehit'];
		}
		$page = $p->show();
		$this->assign('page',$page);
		$this->assign('list',$list);
		$this->display();
	}


	/**
	 * 问卷调查列表
	 */
	function productsSurveyList(){
		extract($_REQUEST);
		$param="";    //分页参数
		$productsSurveyAnswerModel=M("ProductsSurveyAnswer");
		if($pid)
		{
			$where['pid']=$pid;
			$param.='&pid='.$pid;
		}
		if($userid)
		{
			$where['userid']=$userid;
			$param.='&userid='.$userid;
		}

		if($starttime && $endtime)
		{
			$where['addtime']=array("exp","between " .strtotime($starttime)." and  ". strtotime("$endtime 23:59:59"));
			$param="&starttime=$starttime&endtime=$endtime";
		}
		$param=substr($param,1);
		$count=$productsSurveyAnswerModel->where($where)->count();
		import("@.ORG.Page"); //导入分页类库
		$p = new Page($count, 20,$param); //每页显示30条记录
		$list=$productsSurveyAnswerModel->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('addtime desc')->select();
		$page = $p->show();
		for($i=0;$i<count($list);$i++)
		{
			$list[$i]['userinfo']=M("Users")->getByUserid($list[$i]['userid']);
			$list[$i]['productinfo']=M("Products")->getByPid($list[$i]['pid']);
			$question_array = $this->getSurvey ( $list [$i] ['surveyid'] );
			$result_array = unserialize ( $list [$i] ['surveyresult'] );
			$list [$i] ['result'] = '';
			for($j = 1; $j <= count ( $question_array ['question'] ); $j ++) {
				$opt = $result_array [$j];
				$list [$i] ['result'] .= $j . "," . $question_array ['question'] [$j] ['title'] . "<br>&nbsp;&nbsp;&nbsp;<font color='red'>" . $opt . "&nbsp;" . $question_array ['question'] [$j] ['items'] [$opt] . "</font><br>";

			}
		}
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign("survey",$this->getSurvey(1));
		$this->display();

	}

	/**
	* 调查问卷的问题
	*/
	public function getSurvey($id){
		$survey_list = array (
		// 第一个调查
		"1" => array (
		"name" => "第一个调查主题",
		"question" => array (
		// 第一题
		"1" => array (

		"title" => "本产品是否正是您需要的美容产品？",
		"items" => array (
		"A" => "是的，我正有相关问题，产品刚好可以帮我解决",
		"B" => "看了产品介绍，发现自己也有相关问题，正好适用",
		"C" => "一般，本产品并不能帮我解决目前急于改善的问题",
		"D" => "不是，自己并不存在产品功能对应的美容问题"
		),
		"type" => "radio"
		),
		// 第二题
		"2" => array (
		"title" => "您试用本品之后是否会考虑购买正装？",
		"items" => array (
		"A" => "肯定会，这本来就是我订LOLITABOX的初衷",
		"B" => "对于受用的产品肯定会购买",
		"C" => "可能会，喜欢的产品先列入欲望清单",
		"D" => "不会，我只是想多体验一下",
		"E" => "不会，正装价格超出预算"
		),
		"type" => "radio"
		),

		"3" => array (
		"title" => "您觉得这款产品的性价比如何？",
		"items" => array (
		"A" => "奢侈昂贵，荷包不满不要购买",
		"B" => "比起产品的品质，只是有点小贵，可以购买宠爱自己",
		"C" => "性价比非常高，绝对适合理性购买",
		"D" => "低价中的战斗机，可以长期持有",
		"E" => "价格便宜，但是品质一般般"
		),
		"type" => "radio"
		),

		"4" => array (
		"title" => "您最喜欢本产品的哪个方面？",
		"items" => array (
		"A" => "味道",
		"B" => "质地",
		"C" => "效果",
		"D" => "包装",
		"E" => "价格",
		"F" => "品牌形象"
		),
		"type" => "radio"
		),

		"5" => array (
		"title" => "您对本产品的功效是否满意？",
		"items" => array (
		"A" => "非常满意，即刻见效",
		"B" => "比较满意，还有待观察",
		"C" => "一般",
		"D" => "不满意"
		),
		"type" => "radio"
		),

		"6" => array (
		"title" => "您是否喜欢本产品的味道？",
		"items" => array (
		"A" => "非常喜欢",
		"B" => "比较喜欢",
		"C" => "一般",
		"D" => "无所谓，不关心味道",
		"E" => "不喜欢"
		),
		"type" => "radio"
		),

		"7" => array (
		"title" => "您是否喜欢本产品的质地？",
		"items" => array (
		"A" => "非常喜欢",
		"B" => "比较喜欢",
		"C" => "一般",
		"D" => "不喜欢"
		),
		"type" => "radio"
		),

		"8" => array (
		"title" => "您是否喜爱本产品的包装？",
		"items" => array (
		"A" => "非常喜欢",
		"B" => "比较喜欢",
		"C" => "一般",
		"D" => "不喜欢",
		"E" => "无所谓"
		),
		"type" => "radio"
		),

		"9" => array (
		"title" => "您是否会对旁人提起本次本品的试用经历及感受？",
		"items" => array (
		"A" => "试用经历很完美，一定会向大家推荐",
		"B" => "会对最亲密的闺蜜提起本次试用经历",
		"C" => "产品一般般，也许会在闲聊中提起此产品",
		"D" => "这种产品实在没什么好提的"
		),
		"type" => "radio"
		),

		"10" => array (
		"title" => "如果您打算购买正装，您会选择哪种渠道购买本产品？",
		"items" => array (
		"A" => "网购",
		"B" => "品牌专柜",
		"C" => "美容院/沙",
		"D" => "化妆品经销店",
		"E" => "海外代购"
		),
		"type" => "radio"
		),

		"11" => array (
		"title" => "您有什么悄悄话想对品牌说？",
		"type" => "text"
		)
		)
		)

		)
		;

		return $survey_list[$id];
	}

	//修改品牌状态iscommed
	private function changeBrandCommStatus($postinfo){
		$status = $postinfo['status'];
		if( false !==M("productsBrand")->where(array('id'=>$postinfo['brandid']))->setField('iscommend',$status)){
			$this->ajaxReturn($status,'修改成功!',1);
		}else{
			$this->ajaxReturn(0,'修改失败',0);
		}
	}

	//产品品牌管理
	public function brandList(){
		if($this->_post('action') == 'changeiscomme'){
			$this->changeBrandCommStatus($_POST);
		}
		extract($_GET);
		$pro_brand_mod=M("ProductsBrand");
		if($pid){
			$where['id']=filterVar($pid);
		}
		if($name_foreign) {
			$where['name_foreign']=array("like","%$name_foreign%");
		}
		if($name) {
			$where['name']=array("like","%$name%");
		}
		if($area)  {
			$where['area']=$area;
		}
		if($grade){
			$where['grade']=$grade;
		}
		if($status=='0'){
			$where['status']=0;
		}else if($status==1){
			$where['status']=1;
		}
		if($recommend==='0'){
			$where['iscommend'] = 0;
		}else if($recommend==1){
			$where['iscommend'] = array("gt",0);
		}
		//筛选加V
		if($this->_get("channelv") === '0'){
			$where['if_super'] = 0;
		}else if($this->_get("channelv") == 1){
			$where['if_super'] = 1;
		}
		$order=$this->returnOrdertype($_GET);
		$count=$pro_brand_mod->where($where)->count();
		import("@.ORG.Page"); //导入分页类库
		$p = new Page($count,15); //每页显示10条记录
		$list=$pro_brand_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
		$page = $p->show();
		$this->assign("list",$list);
		$this->assign("page",$page);
		$this->assign("type","show");
		$this->display();
	}


	//1为倒序
	//2为正序
	private function returnOrdertype($arguments){

		if($arguments['order']){
			if($arguments['by'] ==1){
				$order = $arguments['order'].' DESC';
			}else {
				$order = $arguments['order'].' ASC';
			}
		}
		return $order;
	}

	/**
	 * 选择明星产品列表
	 * @param string product_list 明星产品
	 * @param int brandid 品牌ID
	 * @author litingting
	 */
	public function selectStarProduct(){
		extract($_REQUEST);
		$category = M ( "Category" );
		$clist = $category->field ( "cid,cname,pcid,ctype,sortid,concat(cpath,'-',cid) as bpath" )->order ( "bpath,cid" )->where ( "ctype=1" )->select ();
		// 			foreach ( $clist as $key => $value ) {
		// 				$clist [$key] ['signnum'] = count ( explode ( '-', $value ['bpath'] ) ) - 1;
		// 				$clist [$key] ['marginnum'] = (count ( explode ( '-', $value ['bpath'] ) ) - 1) * 20;
		// 			}
		if($product_list)
		{
			$select_productlist=M("Products")->field("pid,pname")->where("pid IN($product_list)")->order("field(pid,$product_list)")->select();
		}
		if($brandid)
		{
			$brand_productlist=M("Products")->field("pid,pname")->where("brandcid =$brandid")->select();
		}
		$this->assign("select_productlist",$select_productlist);
		$this->assign("brand_productlist",$brand_productlist);
		$this->assign('clist',$clist);
		$this->display("addproductList");
		die;
	}
	/**
	 * 删除品牌
	 * @param int id 品牌ID
	 * @author litingting
	 */
	public function delBrand(){
		$id=$_REQUEST['id'];
		$pro_brand_mod=M("ProductsBrand");
		if($id)  {
			if($pro_brand_mod->where(array('id' => $id))->delete())   {
				$this->success("删除成功",U("Product/brandList"));die;
			}
			else
			$this->error("删除失败");die;
		}
		else{
			$this->error("没有参数");die;
		}
	}

	/**
	 * 增加品牌
	 * @param string name 
	 * @param string name_foreign
	 * @param string area
	 * @param string founders
	 * @param string product_list
	 * @param string found_time
	 * @param string description
	 * @param string website_url
	 * @param string firstchar
	 * @author litingting
	 */
	public function addBrand(){
		extract($_REQUEST);
		$pro_brand_mod=M("ProductsBrand");
		if ($_REQUEST['submit']) {
			if ($_FILES ['logo_url'] ['name'])
			$_REQUEST ['logo_url'] = $this->photoStorage ( 'logo_url', "/data/productimg/brand" );
			if ($_FILES ['pic_url'] ['name'])
			$_REQUEST ['pic_url'] = $this->photoStorage ( 'pic_url', "/data/productimg/brand" );
			
			if (false!==$pro_brand_mod->add( $_REQUEST )) {
				$this->success ( "添加成功", U ( "Product/brandList" ) );
				die ();
			} else {
				$this->error ( "添加失败" );
				die ();
			}
		} else {
			$this->assign("type","add");
			$this->display("brandList");
		}
	}


	/**
	 * 编辑品牌
	 * @param int id
	 * @param string name
	 * @param string name_foreign
	 * @param string area
	 * @param string founders
	 * @param string product_list
	 * @param string found_time
	 * @param string description
	 * @param string website_url
	 * @author litingting
	 */
	public function editBrand(){
		$pro_brand_mod=M("ProductsBrand");
		if ($_REQUEST['submit']) {
			extract($_REQUEST);
			if ($_FILES ['logo_url'] ['name'])
			$_REQUEST ['logo_url'] = $this->photoStorage ( 'logo_url', "/data/productimg/brand" );
			//if ($_FILES ['pic_url'] ['name'])
			//$_REQUEST ['pic_url'] = $this->photoStorage ( 'pic_url', "/data/productimg/brand" );
			$_REQUEST['area'] = $_REQUEST['areac'];
			
			if (false !== $pro_brand_mod->where ( "id=".$_REQUEST['id'] )->save ( $_REQUEST )) {
				$this->success ( "编辑成功" );
				die ();
			} else {
				$this->error ( "编辑失败" );
				die ();
			}
		} else {
			$brand_info = $pro_brand_mod->getById ( $_REQUEST['id'] );
			$this->assign ( "brandinfo", $brand_info );
			$this->assign("type","edit");
			$this->display("brandList");
		}
	}


	/**
     +----------------------------------------------------------
     * 产品管理,改变产品信息发布状态
     +----------------------------------------------------------
     * @param string status  0为未发布 1为已发布
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @update zhaoxiang 2013.1.21 
     */		
	function changeProductStatus(){
		$product=new ProductModel();
		if($_POST['prolist']){
			$plist=rtrim($_POST['prolist'],",");
			$result=$product->where(array("pid" =>array("in",$plist)))->setField('status',$_POST['status']);
			if($result!==false)
			$this->ajaxReturn($result,"操作成功",1);
			$this->ajaxReturn($result,"操作失败",0);
		}
		if($_POST['pid']){
			$result=$product->where(array('pid'=>filterVar($_POST['pid'])))->setField('status',floatval(!$_POST['status']));

			if($result){
				$this->ajaxReturn($result,"操作成功",1);
			}else{
				$this->ajaxReturn($result,"操作失败",0);
			}
		}else{
			$this->ajaxReturn(0,'参数不正确,请检查!',0);
		}
	}


	/**
	 * 改变品牌开放状态
	 * @param int productid
	 * @author litingting 
	 * update 优化:去除无用的代码   zhaoxiang   2013/1/16
	 */
	public function changeBrandStatus(){
		$brand_mod=M("productsBrand");
		$where['id']=trim($this->_post('brandid'));
		$status=$brand_mod->where($where)->getField('status');

		$data['status']=(int)!$status;
		$result=$brand_mod->where($where)->save($data);
		if($result){
			$this->ajaxReturn(1,'修改成功!',$result);
		}else{
			$this->ajaxReturn(0,'修改未成功,请检查!',$result);
		}
	}
   
	/**
	 * 重复产品排重
	 */
	public function filterRepeat(){
		
		$products_mod = D("Products");
		//ajax返回匹配的产品名列表
		if($_GET['ac']=="select"){
			$tag=trim($_POST['tag']);
			if(empty($tag)){
				$this->ajaxReturn("","没有参数",0);
			}
			$tag = str_replace("'", "\'", $tag);
			$productslist = $products_mod->field("pid,pname")->where("pname like '%".$tag."%'")->select();
            if($id=(int)$tag){
            	$pid_info = $products_mod->where("pid=".$id)->select();
            	if($pid_info){
            		$productslist = empty($productslist) ? array():$productslist;
            		$productslist = array_merge($pid_info,$productslist);
            	}
            }
			$html="<option value=''>请选择</option>";
			foreach($productslist as $key =>$val){
				$html.="<option value='".$val['pid']."'>".$val['pid']."----".$val['pname']."</option>";
			}
			$this->ajaxReturn($html,"成功",1);
		}
		
		$saveid = trim($_REQUEST['saveid']);
		$deleteid = trim($_REQUEST['deleteid']);
		if(empty($saveid) || empty($deleteid)){
			$this->ajaxReturn(0,"缺少参数",0);
		}
		
		$productinfo = $products_mod->getByPid($saveid);
		if(empty($productinfo)){
			$this->ajaxReturn(0,"保留ID不正确",0);
		}
		$deletearr= explode(" ",$deleteid);
		$deletearr= array_unique($deletearr);
		foreach($deletearr as $key =>$val){
			$products_mod ->filterProduct($saveid,$deleteid);
		}
		$this->ajaxReturn(1,"操作成功",1);
	}

	/**
       +----------------------------------------------------------
       * 合并重复的品牌,并将所属的产品改成合并之后的id
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.21 13:30
       */
	public function mergeProductsData(){
		$p_mod=M();
		$list=$p_mod->query("SELECT id FROM `products_brand`
		WHERE name IN ( SELECT name FROM products_brand GROUP BY name 
		HAVING COUNT( * ) >1 ORDER BY id ASC ) ORDER BY name,id");


		//	    file_put_contents('data1.txt',serialize($list));
		//		$list=unserialize(file_get_contents('data1.txt'));

		$brand_mod=M("productsBrand");
		$pro_mod=M("products");

		foreach ($list AS $key => $value)
		{
			if($key %2 ==0){
				$save_arr[]=$value['id'];
			}else{
				$delete_arr[]=$value['id'];
			}
		}

		foreach ($delete_arr as $k => $v)
		{
			$result=$brand_mod->delete($v);
			//			echo $brand_mod->getLastSql().';<br/>';
			$result_arr[]=$v;
			if($result){
				$presult=$pro_mod->query("UPDATE `products` SET `brandcid`=$save_arr[$k] WHERE `brandcid` = $v");
				//				echo $pro_mod->getLastSql().';<br/>';
			}

			$save_result=$pro_mod->where(array('brandcid'=>$v))->field('id')->select();
			if(empty($save_result)){
				$save[]=$save_arr[$k];
			}
		}

		echo '产品表ID'.implode(',',$result_arr).'删除成功!<br/>';
		echo '品牌表所属id'.implode(',',$save).'已转换完成';


	}

	//自此以下方法获取首字母
	function getfirst(){
		$mod=M("productsBrand");
		for($i=1;$i<=3000;$i++){
			$where['id']=$i;
			$mes=$mod->where($where)->getField('name');

			if(preg_match('/^([\x81-\xfe][\x40-\xfe])+/' , $mes)){
				$first=$this->pinyin($mes);
				$data['firstchar']=strtoupper(substr($first,0,1));
			}else{
				$data['firstchar']=strtoupper(substr($mes,0,1));
			}

			$mod->where($where)->setField($data);
		}
	}

	function pinyin($_String) {
		$_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha".
		"|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|".
		"cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er".
		"|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui".
		"|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang".
		"|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang".
		"|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue".
		"|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne".
		"|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen".
		"|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang".
		"|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|".
		"she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|".
		"tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu".
		"|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you".
		"|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|".
		"zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
		$_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990".
		"|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725".
		"|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263".
		"|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003".
		"|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697".
		"|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211".
		"|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922".
		"|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468".
		"|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664".
		"|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407".
		"|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959".
		"|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652".
		"|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369".
		"|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128".
		"|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914".
		"|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645".
		"|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149".
		"|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087".
		"|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658".
		"|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340".
		"|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888".
		"|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585".
		"|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847".
		"|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055".
		"|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780".
		"|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274".
		"|-10270|-10262|-10260|-10256|-10254";
		$_TDataKey   = explode('|', $_DataKey);
		$_TDataValue = explode('|', $_DataValue);
		$_Data =  array_combine($_TDataKey, $_TDataValue);
		arsort($_Data);
		reset($_Data);
		$_String= $this->auto_charset($_String,'utf-8','gbk');
		$_Res = '';
		for($i=0; $i<strlen($_String); $i++) {
			$_P = ord(substr($_String, $i, 1));
			if($_P>160) { $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536; }
			$_Res .= $this->_Pinyin($_P, $_Data);
		}
		return preg_replace("/[^a-z0-9]*/", '', $_Res);
	}
	// 自动转换字符集 支持数组转换
	function auto_charset($fContents, $from='gbk', $to='utf-8') {
		$from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
		$to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
		if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
			//如果编码相同或者非字符串标量则不转换
			return $fContents;
		}
		if (is_string($fContents)) {
			if (function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($fContents, $to, $from);
			} elseif (function_exists('iconv')) {
				return iconv($from, $to, $fContents);
			} else {
				return $fContents;
			}
		} elseif (is_array($fContents)) {
			foreach ($fContents as $key => $val) {
				$_key = auto_charset($key, $from, $to);
				$fContents[$_key] = auto_charset($val, $from, $to);
				if ($key != $_key)
				unset($fContents[$key]);
			}
			return $fContents;
		}
		else {
			return $fContents;
		}
	}
	function _Pinyin($_Num, $_Data) {
		if    ($_Num>0      && $_Num<160   ) return chr($_Num);
		elseif($_Num<-20319 || $_Num>-10247) return '';
		else {
			foreach($_Data as $k=>$v){ if($v<=$_Num) break; }
			return $k;
		}
	}
}
?>
