<?php
class InventoryAction extends CommonAction {
	/*
	库存管理
	*/
	/**
	/**
       +----------------------------------------------------------
       * 单品库存管理
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string id   	  	  查询商品名称
       * @param  string subincrease   增加/减少操作
       * @param  string proid   	  单品列表传单品ID查询库存
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.11 18:23
       */	
	public function aloneProductMessage(){
		$inventory=M('InventoryItem');
		$send=M('user_order_send_productdetail');
		$InventoryStat=M('InventoryStat');
		$products=M('products');

		if($_GET['id']||$_GET['proid']){    //单品ID查找
			if($_GET['id']){
				$inventory_data=$inventory->where(array('id'=>$_GET['id']))->field('id,name,relation_id,inventory_out')->find();
			}else{
				$inventory_data=$inventory->where(array('relation_id'=>$_GET['proid']))->field('id,name,relation_id,inventory_out')->find();
			}
			$inventory_data['inventoryreduced']=$products->where(array('pid'=>$inventory_data['relation_id']))->getField('inventoryreduced');
			$stat=$InventoryStat->where(array('itemid'=>$inventory_data['id'],'status'=>1))->sum('quantity');
			$inventory_data['quantity']=(int)$stat-(int)$inventory_data['inventoryreduced']-(int)$inventory_data['inventory_out'];
			$inventory_data['list']=$InventoryStat->where(array('status'=>1,'itemid'=>$inventory_data['id']))->order("id desc")->select();
		}else if($this->_post('subincrease')){//增加/减少库存
			if($InventoryStat->create()){
				$InventoryStat->operator=$_SESSION['loginUserName'];
				$InventoryStat->add_time=time();
				if($InventoryStat->add()){
					$this->success("添加成功!");
				}else{
					$this->error("添加失败");
				}
			}else{
				$this->error($user->getError());
			}
			exit();
		}else if($this->_post('search')){
			// 一天的秒数
			$secondsad = (86400 - 1);

			if($_POST['status']==1){
				$where['quantity']=array('lt',0);
			}elseif($_POST['status']==0){
				$where['quantity']=array('gt',0);
			}

			$where['status']='1';
			$where['itemid']=$_POST['itemid'];

			$staorder=(int)strtotime(trim($this->_POST("staorder")));
			$endorder=(int)strtotime(trim($this->_POST("endorder")));

			if($staorder || $endorder){
				if($staorder>0&&$endorder==0){
					$where ['add_time'] = array ('egt',$staorder);
				}elseif($staorder==0&&$endorder>0){
					$where ['add_time'] = array ('elt',$endorder+$secondsad);
				}else{
					$where ['add_time'] = array(array ('egt',$staorder),array ('elt',$endorder+$secondsad),'AND');
				}
			}

			$inventory_data=$inventory->where(array('id'=>$_POST['itemid']))->field('id,name,relation_id,inventory_out')->find();
			$inventory_data['list']=$InventoryStat->where($where)->order("id DESC")->select();
			$inventory_data['inventoryreduced']=$products->where(array('pid'=>$inventory_data['relation_id']))->getField('inventoryreduced');
			$stat=$InventoryStat->where($where)->sum('quantity');
			$inventory_data['quantity']=(int)$stat-(int)$inventory_data['inventoryreduced']-(int)$inventory_data['inventory_out'];
		}

		$pid=$_GET['id']?$_GET['id']:$this->_post('pid');
		$this->assign('pid',$pid);
		$this->assign('stat_list',$stat_list);
		$this->assign('inventory_data',$inventory_data);
		$this->display();
	}

	//逻辑删除
	function fdel(){
		$InventoryStat=M('InventoryStat');
		if($_POST['del']){
			$data['status']=0;
			$res=$InventoryStat->where(array('id'=>$_POST['del']))->save($data);
			if($res){
				$this->ajaxReturn(1,1,1);
			}else{
				$this->ajaxReturn(0,0,0);
			}
		}
	}


	public function consumables(){
		$inventory=M('InventoryItem');
		$InventoryStat=M('InventoryStat');
		import ( "@.ORG.Page" ); // 导入分页类库
		if($_POST['determine']){
			$data['name']=trim($_POST['pname']);
			$conditions=$inventory->where($data)->find();

			if(!$conditions){
				$data['intro']=trim($_POST['message']);
				$data['relation_id']='0';
				$data['status']='1';
				$res=$inventory->add($data);
				if($res){
					$this->success("添加成功!");
				}else{
					$this->error("添加失败!");
				}
			}else{
				$this->error('抱歉,已经有相同名称的物品!');
			}
			exit();
		}elseif($_GET['id']&&empty($_POST['search'])){
			$id=$_GET['id'];
			$inventory_data=$inventory->where(array('id'=>$id))->field('id,name,intro')->find();
			$inventory_data['quantity']=$InventoryStat->where(array('itemid'=>$id,'status'=>1))->sum('quantity');
			$count=$InventoryStat->where(array('itemid'=>$id,'status'=>1))->count();
			$p = new Page ( $count, 15);
			$product_list=$InventoryStat->where(array('itemid'=>$id,'status'=>1))->order('add_time DESC')->limit ($p->firstRow.','.$p->listRows )->select();
			$page = $p->show ();

		}elseif($_POST['subincrease']){

			if($InventoryStat->create()){
				$InventoryStat->operator=$_SESSION['loginUserName'];
				$InventoryStat->add_time=time();

				if($InventoryStat->add()){
					$this->success("添加成功!");
				}else{
					$this->error("添加失败");
				}
			}else{
				$this->error($user->getError());
			}
			exit();
		}else if($_POST['search']){                   //搜索条件查找
			// 一天的秒数
			$secondsad = (86400 - 1);

			if($_POST['status']==1){
				$where['quantity']=array('lt',0);
			}elseif($_POST['status']==0){
				$where['quantity']=array('gt',0);
			}else{

			}

			$where['itemid']=$_POST['itemid'];

			if(!empty($_POST['staorder'])&&!empty($_POST['endorder'])){
				$where ['add_time']=array(array('egt',strtotime(trim($_POST['staorder']))),
				array('elt',strtotime(trim($_POST['staorder']))+$secondsad),
				'And');
			}elseif(!empty($_POST['staorder'])&&empty($_POST['endorder'])){
				$where ['add_time'] = array ('egt',strtotime (trim($_POST ['staorder'])));
			}elseif(empty($_POST['staorder'])&&!empty($_POST['endorder'])){
				$where ['add_time'] = array ('egt',strtotime(trim($_POST['endorder']))+$secondsad);
			}
			$where['status']='1';
			$product_list=$InventoryStat->where($where)->order("id DESC")->select();

			$inventory_data=$inventory->where(array('id'=>$_POST['itemid'],'status'=>1))->field('id,name,intro,relation_id')->find();
			$statquan=$InventoryStat->where(array('itemid'=>$_POST['itemid'],'status'=>1))->sum('quantity');
			$inventory_data['quantity']=(int)$inventory_data['intro']+(int)$statquan;

		}elseif ($_POST['inquires']){            //单品名称模糊查找
			$where['name']=array('like',"%".$_POST['products']."%");
			$count=$inventory->where($where)->count();
			$p = new Page ( $count, 15);
			$product_list=$inventory->where($where)->limit ($p->firstRow . ',' . $p->listRows )->order('id ASC')->field('id,name,intro,relation_id')->select();
			foreach($product_list as $key=>$value){
				$statquan=$InventoryStat->where(array('itemid'=>$product_list[$key]['id']))->sum('quantity');
				$product_list[$key]['quantity']=(int)$product_list[$key]['intro']-(int)$statquan;
			}
			$page = $p->show ();
		}else{
			$count=$inventory->where(array('relation_type'=>'inventory','status'=>1))->count();
			$p = new Page ( $count, 15);
			$product_list=$inventory->where(array('relation_type'=>'inventory','status'=>1))->field('id,name,intro')->order('id DESC')->limit ($p->firstRow.','.$p->listRows )->select();
			foreach($product_list as $key=>$value){
				$product_list[$key]['quantity']=$InventoryStat->where(array('itemid'=>$product_list[$key]['id'],'status'=>1))->sum('quantity');
			}
			$page = $p->show ();
		}

		$this->assign ( "page", $page );
		$this->assign('inventory_data',$inventory_data);
		$this->assign('product_list',$product_list);
		$this->display();
	}

    public function add(){
        $category=M("Category");
        //调用产品分类，CTYPE=1
        $clist=$category
            ->field("cid,cname,pcid,ctype,cpath,sortid,concat(cpath,'-',cid) as bpath")
            ->order("bpath,cid")
            ->where("ctype=1")->select();
        foreach($clist as $key=>$value){
            $clist[$key]['signnum']= count(explode('-',$value['bpath']))-1;
            $clist[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
        }
        //调用效果分类,CTYPE=2   功效分类
        $elist=$category
            ->field("cid,cname")
            ->order("cid")
            ->where("ctype=2")->select();

        //调用品牌分类,CTYPE=3
        $blist=M('ProductsBrand')->field('id as cid,name as cname')->select();

        $product_mod=new ProductsModel();
        $for_skin=$product_mod->getForSkinDefine();
        $for_people=$product_mod->getForPeopleDefine();
        $for_hair=$product_mod->getForHairDefine();
        $this->assign('skin_list',$for_skin);
        $this->assign('people_list',$for_people);
        $this->assign('hair_list',$for_hair);
        $this->assign('clist',$clist);
        $this->assign('elist',$elist);
        $this->assign('blist',$blist);
        $this->display("productCreate");
    }

    public function addProduct(){
        //print_r($_POST);exit;
        $inventoryItem=new InventoryItemModel();

        if($data=$inventoryItem->create()){

            $cid = $_POST["cid"];
            $data["firstcid"]=split("-", $cid)[0];
            $data["secondcid"]=split("-", $cid)[1];
            if(empty($data['secondcid'])){
                $this->error('请选择二级分类!');
            }
            $data["goodsprice"]=$data["goodsprice"]*100;
            $data["trialprice"]=$data["trialprice"]*100;
            $data['pintro'] = $this->remoteimg($_POST['pintro']);
            $data['readme'] = $this->remoteimg($_POST['readme']);

            $inventoryItemId=$inventoryItem->add($data);

            if($inventoryItemId){
                //建立产品ID与功效ID对应关系
                $product_effect=new InventoryItemEffectModel();
                $effectcid=$_REQUEST["effectcid"];
                $acount=count($effectcid);
                for($i=0;$i<$acount;$i++){
                    $adata[$i]=array(
                        "inventoryItemId"=>$inventoryItemId,
                        "effectcid"=>$effectcid[$i]
                    );
                }
                $rss=$product_effect->addALL($adata);
                if($acount==$rss){
                    $this->success("产品添加成功!ID为:{$inventoryItemId}",U("Inventory/index"));
                }else{
                    $this->error("产品功效表,未完整填充!请联系管理员检查");
                }
            }else{
                $this->error("添加库存单品失败：".$inventoryItem->getDbError());
            }
        }else {
            $this->error('数据验证( '.$inventoryItem->getError().' )');
        }
    }

    public function editProduct(){
        $item=M("InventoryItem")->getById($_REQUEST['id']);
        $item["goodsprice"]=bcdiv($item["goodsprice"], 100, 2);
        $item["trialprice"]=bcdiv($item["trialprice"], 100, 2);
        $this->assign("item",$item);

        //调用产品与效果分类对应关系
        $inventoryEffect=new InventoryItemEffectModel();
        $where="inventoryItemId=".$_REQUEST['id'];
        $inventoryEffectlist=$inventoryEffect->field('effectcid')->where($where)->select();
        while(list($k,$v)=each($inventoryEffectlist)){
            $tlist[]=$inventoryEffectlist[$k]['effectcid'];
        }
        $this->assign('producteffectcidlist',$tlist); //产品与功效关系

        $category=M("Category");
        //调用产品分类，CTYPE=1
        $clist=$category
            ->field("cid,cname,pcid,ctype,cpath,sortid,concat(cpath,'-',cid) as bpath")
            ->order("bpath,cid")
            ->where("ctype=1")->select();
        foreach($clist as $key=>$value){
            $clist[$key]['signnum']= count(explode('-',$value['bpath']))-1;
            $clist[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
        }
        //调用效果分类,CTYPE=2   功效分类
        $elist=$category
            ->field("cid,cname")
            ->order("cid")
            ->where("ctype=2")->select();

        //调用品牌分类,CTYPE=3
        $blist=M('ProductsBrand')->field('id as cid,name as cname')->select();

        $product_mod=new ProductsModel();
        $for_skin=$product_mod->getForSkinDefine();
        $for_people=$product_mod->getForPeopleDefine();
        $for_hair=$product_mod->getForHairDefine();
        $this->assign('skin_list',$for_skin);
        $this->assign('skin_list_selected',explode(",",$item["for_skin"]));
        $this->assign('people_list',$for_people);
        $this->assign('people_list_selected',explode(",",$item["for_people"]));
        $this->assign('hair_list',$for_hair);
        $this->assign('hair_list_selected',explode(",",$item["for_hair"]));
        $this->assign('clist',$clist);
        $this->assign('elist',$elist);
        $this->assign('blist',$blist);
        $this->display("productCreate");
    }

    public function updateProduct(){
        if($_REQUEST['cid']){
            $cids=explode("-", $_REQUEST['cid']);
            if($cids[1]){
                $_REQUEST['secondcid']=$cids[1];
                $_REQUEST['firstcid']=$cids[0];
            }else
                $this->error("请选择二级分类");
        }
        $_REQUEST["goodsprice"]=$_REQUEST["goodsprice"]*100;
        $_REQUEST["trialprice"]=$_REQUEST["trialprice"]*100;
        $inventoryItem=new InventoryItemModel();
        $data=D("inventoryItem")->create();
        if(false!==$inventoryItem->where("id=".$_REQUEST['id'])->save($data))
            $this->success("操作成功");
        else
            $this->error("操作失败");
    }

	/**
	 * 库存单品列表
	 * @author litingting
	 */
	public function productList(){

		$item_mod=M("InventoryItem");

		if($_REQUEST['ac']=='delete'){
			$id=$_REQUEST['id'];
			if($id){
				if(M("InventoryItem")->delete($id)){
					$this->ajaxReturn(1,"删除成功",1);die;
				}
			}
			$this->ajaxReturn(0,"删除失败",0);die;
		}

		if($this->_post('selectP')){
			$total=$item_mod->SUM("inventory_real * price");
			if($total){
				$this->AjaxReturn($total,'成功返回!',1);
			}else{
				$this->AjaxReturn('','返回失败!',0);
			}
		}
		$where=array();
		if($_REQUEST['id']) {
			$where['id']=$_REQUEST['id'];

		}
		if($_REQUEST['name']) {
			$where['name']=array('like',"%".$_REQUEST['name']."%");
		}
		if($_REQUEST['brandid']) {
			$where['brandid']=$_REQUEST['brandid'];
		}
		if($_REQUEST['category']) {
			$where['category']=$_REQUEST['category'];
		}
		if($_REQUEST['cid']){
			$cids=explode("-", $_REQUEST['cid']);
			if($cids[1]){
				$where['secondcid']=$cids[0];
			}else
			$where['firstcid']=$cids[0];
		}

		if($this->_get('brandtype')){
			$where['relation_id']=array('exp',"IN(SELECT pid FROM products WHERE brandcid IN(SELECT id FROM products_brand WHERE brandtype ='".$this->_get('brandtype')."'))");
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

		
		if($this->_get('export')){
			$list=$item_mod->where($where)->order($order)->field('intro,firstcid,secondcid,relation_type,relation_out_quantity,inventory_in,inventory_out,status',true)->select();
		}else{
			$count=$item_mod->where($where)->count();
			import("@.ORG.Page");
			$p = new Page($count,25);
			$list=$item_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
			$page = $p->show();
		}

		if($this->_get('submit')){
			//价格
			$where['inventory_real']=array('gt',0);
			$total=$item_mod->where($where)->SUM("inventory_real * price");
			$this->assign('total',$total);
		}

		if($list){
			for($i=0;$i<count($list);$i++){
				$list[$i]['brandname']=M("ProductsBrand")->where("id=".$list[$i]['brandid'])->getField("name");
				$list[$i]['brandgrade']=M("ProductsBrand")->where("id=".$list[$i]['brandid'])->getField("grade");
				$list[$i]['planOut']=$list["$i"]['inventory_real']-$list["$i"]['inventory_estimated'];
			}
		}

		if($this->_get('export')){
			$this->exportProductsDetail($list);
		}
		$clist=M("Category")->field("cid,cname,pcid,ctype,cpath,sortid,concat(cpath,'-',cid) as bpath")
		->order("bpath,cid")->where("ctype=1")->select();
		$brand_list=M("ProductsBrand")->field("id,name")->select();
		//品牌库存产品数量列表
		$mod = M();
		$brandInventoryProductsNum = $mod->query("SELECT  pb.id,pb.`name`,COUNT(ii.id) as countid FROM `products_brand`as pb  LEFT JOIN inventory_item AS ii ON pb.id = ii.brandid  WHERE pb.status =1  GROUP BY pb.id HAVING countid >0 ORDER BY countid DESC");
		$this->assign("brandInventoryProductsNum",$brandInventoryProductsNum);
		$this->assign("brandlist",$brand_list);
		$this->assign("clist",$clist);
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->display();
	}


	private function exportProductsDetail($list){
		$str="单品ID,单品名称,所属品牌,等级,规格,类别,理论库存量,实际库存量,关联单品ID,价格,过期时间,货架号,备注\n";
		foreach ($list AS $key => $value){
			$str.=$value['id'].','.$value['name'].','.$value['brandname'].','.$value['brandgrade'].','.$value['norms'].','.$value['category'].','.
			$value['inventory_estimated'].','.$value['inventory_real'].','.$value['relation_id'].','.$value['price'].','.
			$value['validdate'].','.$value['shelfinfo'].','.$value['remark']."\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK",date ( "Y-m-d" ).'库存单品明细'), $str );
		exit();
	}

	/**
       +----------------------------------------------------------
       * 查询此单品都在哪个订单中出现
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  pid 	单品ID
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/2/7
       */
	public function selectProcuctInOrder(){
		$pid=filterVar($this->_get('pid'));
		if($pid){
			$detail_mod=M("userOrderSendProductdetail");
			$orderlist=$detail_mod->distinct('true')->where(array('productid'=>$pid))->field("orderid")->order('orderid')->select();

			$list=$this->filterOrder($orderlist);

			$list=$this->OrderProducts($list);

			//			$this->yanzheng($list);
			$this->assign('orderlist',$list);
			$this->display();
		}else{
			$this->error("参数不正确,请检查!");
		}
	}

	/**
       +----------------------------------------------------------
       * (验证之用)查看已经匹配完单品的订单的单品数是否和productnum相等
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  orderlist  要验证的数组
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/2/7
       */
	/*	private function yanzheng($orderlist){
	$send_mod=M("userOrderSend");
	$result=array();
	foreach ($orderlist as $k => $v){
	$count=$send_mod->where("orderid=".$v['orderid'])->getField("productnum");

	$sd=count($v['pinfo']);

	if($count==$sd){
	$result[$k]='YES';
	}else{
	$result[$k]='NO';
	}
	}
	dump($result);
	}*/


	/**
       +----------------------------------------------------------
       * 查找已经过滤完成的订单号都有哪些单品
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  orderlist  已经完成过滤的订单数组,匹配订单的单品
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/2/7
       */
	private function OrderProducts($orderlist){
		$detail_mod=M("userOrderSendProductdetail");
		$item_mod=M("inventoryItem");

		foreach ($orderlist as $key =>$value){
			$pidlist=$detail_mod->where(array('orderid'=>$value['orderid']))->field('productid')->order("productid")->select();

			foreach ($pidlist as $ck=>$cv){
				$pname=$item_mod->where(array('id'=>$cv['productid']))->getField('name');
				if($pname){
					$pidlist[$ck]['pname']=$pname;
				}
			}
			$orderlist[$key]['pinfo']=$pidlist;
			unset($pidlist);
		}
		return $orderlist;
	}

	/**
       +----------------------------------------------------------
       * 过滤订单,
       * 只返回
       * userOrder state=1 AND inventory_out_status = 0 AND
       * userOrderSend  senddate IS NULL productnum > 0	的数据
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  orderlist  要过滤的订单数组
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/2/7
       */
	private function filterOrder($orderlist){
		$send_mod=M("userOrderSend");
		$order_mod=M("userOrder");

		$where['senddate']=array('exp',"IS NULL");
		$where['productnum']=array('gt',"0");

		foreach ($orderlist AS $key=>$value){
			$res=$order_mod->where(array('ordernmb'=>$value['orderid'],'state'=>1,'inventory_out_status'=>0))->find();

			if($res){
				$where['orderid']=$value['orderid'];
				$result=$send_mod->where($where)->find();

				if($result){
					$return[]['orderid']=$value['orderid'];
				}
			}
		}
		return $return;
	}

	/**
       +----------------------------------------------------------
       * 创建入库单
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string $_SESSION['loginUserName']  当前操作人的名称	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	public	function createInOrder(){
		$in_mod=M("inventoryIn");

		if($this->_post('smit')){
			$data=array(
			'title'=>trim($this->_post('ordername')),
			'customer'=>trim($this->_post('clientname')),
			'arrivaldate'=>trim($this->_post('aogtime')),
			'area'=>trim($this->_post('province')),
			'description'=>trim($this->_post('remark')),
			'operator'=>trim($_SESSION['loginUserName']),
			'cdatetime'=>date("Y-m-d H:i:s"),
			'status'=>1,
			'ifconfirm'=>0,
			'confirmoperator'=>'',
			'confirmdatetime'=>''
			);
			$this->assign("jumpUrl","inOrderEntry");
			if($this->_post('iid')){
				$data['id']=$this->_post('iid');
				if($in_mod->save($data)){
					$this->success("更新成功!");
				}else{
					$this->error($in_mod->getError());
				}
			}else{
				if($in_mod->add($data)){
					$this->success("入库单添加成功!");
				}else{
					$this->error($in_mod->getError());
				}
			}
		}else{
			if($this->_get('id')){
				$result=$in_mod->where(array('id'=>trim($this->_get('id'))))->find();
				if(($result['operator']==$_SESSION['loginUserName']) || $_SESSION['account']=='admin'){
					$result['tips']='编辑';
					$this->assign('info',$result);
				}else{
					$this->error("您没有权限编辑!");
				}
			}
			$area_mod=M("area");
			$area_list=$area_mod->where(array('pid'=>0))->field('title')->select();
			$this->assign('area',$area_list);
			$this->display();
		}
	}

	/**
       +----------------------------------------------------------
       * 入库单列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string  id    status=0  表示已删除的
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	function inOrderEntry(){
		$in_mod=M("inventoryIn");
		import ( "@.ORG.Page" ); // 导入分页类库

		if($this->_get('id')){
			$operator=$in_mod->where(array('id'=>trim($this->_get('id'))))->getField('operator');

			if(($operator==$_SESSION['loginUserName']) || $_SESSION['account']=='admin'){
				$result=$in_mod->where(array('id'=>trim($this->_get('id'))))->setField('status',0); //0表示已删除
				if($result){
					$this->success('删除成功!');
				}else{
					$this->error('删除失败!');
				}
			}else{
				$this->error('您没有删除权限!!');
			}
			exit();
		}else if($this->_get('search')){

			extract ($_GET);

			//入库单名称
			if($ordername){
				$where['title']=array('like',"%".trim($this->_get('ordername'))."%");
			}

			//客户名称
			if($clientname){
				$where['customer']=array('like',"%".trim($this->_get('clientname'))."%");
			}

			//提交时间
			if($startdate && $enddate){
				$where['cdatetime']=array(array('egt',$this->_get('startdate').' 00:00:00'),array('elt',$this->_get('enddate').' 23:59:59'));
			}elseif($startdate){
				$where['cdatetime']=array('egt',$this->_get('startdate').' 00:00:00');
			}elseif($enddate){
				$where['cdatetime']=array('elt',$this->_get('enddate').' 23:59:59');
			}

			//申请人查询
			if($proposer){
				$where['operator']=$proposer;
			}
		}

		//入库单状态
		if($this->_get('ormstatus')==='0'){
			$where['ifconfirm']=0;
			$where['status']=1;
		}else if($this->_get('ormstatus')==1){
			$where['ifconfirm']=1;
			$where['status']=1;
		}else if($this->_get('ormstatus')==2){
			$where['status']=0;
		}else{
			$where['status']=1;
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


		$humanlist=$in_mod->distinct(true)->field("operator")->select();

		$count=$in_mod->where($where)->count();
		$p = new Page ( $count, 15);
		$list=$in_mod->where($where)->field('description,confirmoperator,confirmdatetime',true)->order("id DESC")->limit ($p->firstRow.','.$p->listRows )->order($order)->select();
		$page = $p->show ();
		$this->assign ( "page", $page );
		$this->assign('list',$list);
		$this->assign('human',$humanlist);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 入库单详情
       * remark:已删除的入库单无法进行入库操作
       * remark:已确认入库的无法删除
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string  id    status=0  表示已删除的
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	function orderParticular(){
		$in_mod=M("inventoryIn");
		$stat_mod=M("inventoryStat");
		$item_mod=D("InventoryItem");

		if($this->_get('id')){
			$result=$in_mod->where(array('id'=>$this->_get('id')))->find();
			if($result['ifconfirm']==1){
				$where['in_out_id']=$result['id'];
				$where['quantity']=array('gt',0);
				$result['list']=$stat_mod->where($where)->field("itemid,quantity")->select();

				foreach ($result['list'] AS $k => $v){
					if($v['itemid'] !=0){
						$iteminfo=$item_mod->where(array('id'=>$v['itemid']))->field('name,validdate,shelfinfo')->find();
						$result['list'][$k]['name']=$iteminfo['name'];
						$result['list'][$k]['validdate']=$iteminfo['validdate'];
						$result['list'][$k]['shelfinfo']=$iteminfo['shelfinfo'];
					}
				}
			}

			$this->assign('alonemessage',$result);
			$this->display();
		}else if($this->_post('bid')){

			$data=array(
			'id'=>$this->_post("bid"),
			'ifconfirm'=>1,
			'confirmoperator'=>trim($_SESSION['loginUserName']),
			'confirmdatetime'=>date("Y-m-d H:i:s")
			);

			if($in_mod->save($data)){

				$pid=$this->_post("pid");
				$quantity=$this->_post("quantity");
				$maxtime=$this->_post("maxtime");

				foreach ($pid AS $key => $value ){
					$data1=array(
					'itemid'=>trim($value),
					'message'=>'',					//库管不能备注
					'operator'=>trim($_SESSION['loginUserName']),
					'quantity'=>trim($quantity[$key]),
					'add_time'=>time(),
					'status'=>1,
					'in_out_id'=>trim($this->_post('bid'))
					);
					if($stat_mod->add($data1)){
						//判断有效期  更新最小的有效期
						$validate=$item_mod->where(array('id'=>$value))->getField('validdate');
						$vdata=strtotime($validate);

						if((strtotime($maxtime[$key])  < $vdata) || empty($vdata)){
							$result2[]=$item_mod->where(array('id'=>$value))->setField('validdate',$maxtime[$key]);
						}
						$item_mod->IncInventoryInLock($value, $quantity[$key]);
					}else{
						$this->error($stat_mod->getError());
					}
				}
				$this->assign("jumpUrl","inOrderEntry");
				$this->success('操作完成,返回列表页!');
			}else{
				$this->error($in_mod->getError());
			}
		}
	}

	/**
       +----------------------------------------------------------
       * 确认入库,需要判断权限
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string  type  in 为确认入库 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	function inInventory(){
		if($this->_post('type')=='in'){
			$this->ajaxReturn(1,1,1);  //此处应进行权限判断,再返回
		}
	}


	/**
       +----------------------------------------------------------
       * remark:出库确认      
       * id  出库表ID  
       * type=out 出库标识   
       * status=confirm 确认   
       * 需要返回 确认人名称  和  确认时间
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string  type  out 为确认出库
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */	
	function outConfirm(){
		$out_mod=M('inventoryOut');
		$stat_mod=M("inventoryStat");
		$order_mod=M("userOrderSend");
        $item_mod=D("InventoryItem");

		$time=date('Y-m-d H:i:s');
		$da=trim($_SESSION['loginUserName']);

		$data=array(
		'ifconfirm'=>1,
		'confirmoperator'=>$da,
		'confirmdatetime'=>$time
		);
		$where['in_out_id']=$this->_post('id');
		$where['quantity']=array('lt',0);   //update to liting--> 将quantity作为查询条件，而不是保存的值
		$saveData['status']=1;
		$saveData['operator']=$da;
		$saveData['add_time']=time();

		$stat_result=$stat_mod->where($where)->save($saveData);

        //系统出库，出库单生成之前减去库存，人工出库和虚拟出库：确认之后减库存
		if($this->_post('type')==1){
            $order_result=$order_mod->where(array('inventory_out_id'=>$this->_post('id')))->setField('inventory_out_status',1);
        }else{
            $stat_out_info = $stat_mod->where($where)->select();
            foreach($stat_out_info as $stat){
                $item_mod->updateAbnormalInventoryOutLock($stat['itemid'],-$stat['quantity']);
            }
        }
		if($this->_post('type')==3)     //如果是虚拟出库
		{
			$rel_mod=M("InventoryVirtualRelation");
			$in_id=$rel_mod->where("out_id=".$this->_post('id'))->getField("in_id");
			$where['in_out_id']=$in_id;
			$where['quantity']=array('gt',0);   //update to liting--> 将quantity作为查询条件，而不是保存的值
			$saveData['status']=1;
			$saveData['operator']=$da;
			$saveData['add_time']=time();
			$stat_result=$stat_mod->where($where)->save($saveData);
            $stat_in_info = $stat_mod->where($where)->select();
            foreach($stat_in_info as $stat){
                $item_mod->IncInventoryInLock($stat['itemid'],$stat['quantity']);
            }
		}

		$result=$out_mod->where(array('id'=>$this->_post('id')))->setField($data);

		if($result){
			$this->ajaxReturn($da,$time,1);
		}else{
			$this->ajaxReturn(0,0,0);
		}
	}

	/**
       +----------------------------------------------------------
       * 主管审核人工出库单
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  AJAX
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/7
       */
	function verifyOutInventoryHuman(){

		$result=$this->verify($_POST);

		if($result['da'] && $result['time']){
			$this->ajaxReturn($result['da'],$result['time'],1);
		}else{
			$this->ajaxReturn(0,0,0);
		}
	}

	/**
       +----------------------------------------------------------
       * 主管审核系统出库单
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param   AJAX
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/7
       */
	function verifyOutInventorySystem(){
		$result=$this->verify($_POST);

		if($result['da'] && $result['time']){
			$this->ajaxReturn($result['da'],$result['time'],1);
		}else{
			$this->ajaxReturn(0,0,0);
		}
	}

	/**
	 +----------------------------------------------------------
	 * 主管审核虚拟出库单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param   AJAX
	 +-----------------------------------------------------------
	 * @author litingting 2013/1/7
	 */
	function verifyOutInventoryVirtual(){
		$result=$this->verify($_POST);

		if($result['da'] && $result['time']){
			$this->ajaxReturn($result['da'],$result['time'],1);
		}else{
			$this->ajaxReturn(0,0,0);
		}
	}

	/**
       +----------------------------------------------------------
       * 审核出库单的公共部分 (封装)
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       + return
       * @param  string  da  	审核人名称
       * @param  string  time   审核时间
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/7
       */
	private function verify($outData){

		if($outData['type']=='out'){
			if($outData['status']=='agreeoperator'){
				$out_mod=M('inventoryOut');

				$time=date('Y-m-d H:i:s');
				$da=trim($_SESSION['loginUserName']);

				$data=array(
				'ifagree'=>1,
				'agreeoperator'=>$da,
				'agreedatetime'=>$time
				);

				$result=$out_mod->where(array('id'=>$this->_post('id')))->setField($data);
				if($result){
					$return['time']=$time;
					$return['da']=$da;
				}
			}
		}
		return $return;
	}
	/**
       +----------------------------------------------------------
       * 创建人工出库单
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       * update zhaoxiang 2013/1/7
       */
	function outOrderHuman(){

		if($this->_post('sub')){

			$return=$this->outFillData($_POST);;

			//人工出库单发邮件
			$string="亲爱的Maxiao：<br><br><br>
			&nbsp;&nbsp;&nbsp;&nbsp;出库单<span style='font-weight:bolder'>".$return['title']."</span>需要您的审核。请您登陆<a href='www.lolitabox.com/admin/'>www.lolitabox.com/admin/</a> 进行审核。<br><br>&nbsp;&nbsp;&nbsp;&nbsp;谢谢！<br><br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;此致<br><br>Lolitabox管理后台.<br>
			<a href='http://www.lolitabox.com/admin/'>http://www.lolitabox.com/admin/</a>";
			if($_SERVER['SERVER_ADDR']=='42.121.84.129')
			sendtomail("maxiao@lolitabox.com", "人工出库提醒—【".$return['title']."】",$string);
			else
			sendtomail("litingting@lolitabox.com", "人工出库提醒—【".$return['title']."】",$string);

			$this->assign("jumpUrl","outOrderEntry");
			if(count($return)==3){
				$this->success("数据全部添加成功");
			}else{
				$this->error("数据添加失败,请检查");
			}
			exit();
		}else{
			$this->display();
		}

	}

	/**
       +----------------------------------------------------------
       * 创建系统出库单
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       * update zhaoxiang 2013/1/7
       */
	function outOrderSystematic(){

		if($this->_post('sub')){

			$return=$this->outFillData($_POST);

			if(count($return)==3){
				if($this->_post('ordernum')){
					$send_order_mod=M("userOrderSend");
					foreach ($this->_post('ordernum') AS $ck => $cv){
						$order_arr=explode("-",$cv);
						$where['orderid']=$order_arr[0];
						$where['child_id']=$order_arr[1];
						$order_success[]=$send_order_mod->where($where)->setField('inventory_out_id',$return['LastId']);
					}
				}
			}
			$this->assign("jumpUrl","outOrderEntry");
			if(count($order_success)){
				$this->success("数据全部添加成功");
			}else{
				$this->error("数据添加失败,请检查");
			}
		}else{
			$this->display('outOrderHuman');
		}
	}


	/**
       +----------------------------------------------------------
       * 人工,系统出库公共部分封装
       +----------------------------------------------------------
       * @access private   
       +----------------------------------------------------------
       * @param   string	outData $_POST值
       * @return  string	stat表添加成功的数量
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/7
       */
	private function outFillData($outData){

		$out_mod=M('inventoryOut');

		$data=array(
		'type'=>$outData['type'],
		'title'=>trim($outData['outname']),
		'outdate'=>trim($outData['outtime']).' 00:00:00',
		'description'=>trim($outData['remark']),
		'cdatetime'=>date('Y-m-d H:i:s'),
		'operator'=>trim($_SESSION['loginUserName']),
		'status'=>1,
		'ifagree'=>0,
		'agreeoperator'=>'',
		'agreedatetime'=>'',
		'ifconfirm'=>0,
		'confirmoperator'=>'',
		'confirmdatetime'=>''
		);
		$in_out_id=$out_mod->add($data);

		if($in_out_id){

			$stat_mod=M("inventoryStat");

			$pid=$outData['pid'];
			$quantity=$outData['quantity'];

			foreach ($pid AS $key => $value){
				if(!empty($quantity[$key])){
					$data1=array(
					'itemid'=>$value,
					'message'=>'',
					'operator'=>'',
					'quantity'=>-$quantity[$key],
					'add_time'=>'',
					'status'=>0,
					'in_out_id'=>$in_out_id
					);
					$result[]=$stat_mod->add($data1);
				}
			}
		}
		$return_array['title']=$data['title'];
		$return_array['count']=count($result);
		$return_array['LastId']=$in_out_id;
		return $return_array;
	}
	/**
       +----------------------------------------------------------
       * 出库单列表  包括人工,系统
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	function outOrderEntry(){
		$out_mod=M('inventoryOut');
		import ( "@.ORG.Page" ); // 导入分页类库

		extract ($_GET);
		//入库单名称
		if($ordername){
			$where['title']=array('like',"%".trim($this->_get('ordername'))."%");
		}

		//提交时间
		if($startdate && $enddate){
			$where['cdatetime']=array(array('egt',$this->_get('startdate').' 00:00:00'),array('elt',$this->_get('enddate').' 23:59:59'));
		}elseif($startdate){
			$where['cdatetime']=array('egt',$this->_get('startdate').' 00:00:00');
		}elseif($enddate){
			$where['cdatetime']=array('elt',$this->_get('enddate').' 23:59:59');
		}

		//确认出库时间
		if($from && $to)
		{
			$where['confirmdatetime']=array(array('egt',$from.' 00:00:00'),array('elt',$to.' 23:59:59'));
		}elseif($from)
		$where['confirmdatetime']=array('egt',$from.' 00:00:00');
		elseif($to)
		$where['confirmdatetime']=array('egt',$to.' 00:00:00');

		//申请人查询
		if($proposer){
			$where['operator']=$proposer;
		}

		//出库单类型
		if($type)
		$where['type']=$type;
		else
		$where['type']=array("in","1,2");

		//出库单状态查询
		$where['status']=1;
		if($this->_get('ormstatus')=='2'){
			$where['status']=0;
		}

		if($_REQUEST['export']){
			$out_list=$out_mod->where($where)->field("id,operator,confirmdatetime,type,title")->order("id desc")->select();
			$stat_mod=M("InventoryStat");
			$export_list=array();
			for($i=0;$i<count($out_list);$i++){

				$type=$out_list[$i]['type']==1 ? "库统出库" :"人工出库";
				$item_list=$stat_mod->field("inventory_item .id ,name,price,quantity,'".$out_list[$i]['title']."' as '出库单名称','".$out_list[$i]['operator']."' as '申请人','".$out_list[$i]['confirmdatetime']."' as '确认出库时间','".$type."' as '出库单类型'")->where("quantity <0 AND in_out_id=".$out_list[$i]['id'])->join("inventory_item on inventory_item.id= inventory_stat.itemid")->select();
				if(is_array($item_list)){
					$export_list=array_merge($export_list,$item_list);
				}

			}
			$str="库存单品ID,单品名称,单品价格,单品数量,出库单名称,申请人,确认出库时间,出库单类型\n";
			for($i=0;$i<count($export_list);$i++)
			{
				$export_list[$i]['quantity']=abs($export_list[$i]['quantity']);
				$str.=implode(",", $export_list[$i])."\n";
			}
			outputExcel ( iconv ( "UTF-8", "GBK", "出库单列表" ), $str );die;
		}


		if($this->_get('exportHumanOrderData')){
			$hlist = $out_mod->where($where)->field('id,title,description,confirmdatetime')->order("ID DESC")->select();
			$this->exportHumanOrderData($hlist);   //导出人工出库报表
		}

		$humanlist=$out_mod->distinct(true)->field("operator")->select();

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


		$count=$out_mod->where($where)->count();
		$p = new Page ( $count, 15);
		$list=$out_mod->where($where)->field('description,confirmdatetime',true)->limit ($p->firstRow.','.$p->listRows )->order($order)->select();
		$page = $p->show ();
		$this->assign ( "page", $page );
		$this->assign('list',$list);
		$this->assign('human',$humanlist);
		$this->display();
	}


	/**
       +----------------------------------------------------------
       * 出库单详情  包括人工,系统
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	 price 计算出总价
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4  
       */
	function outOrderInfo(){
		$item_mod=M("inventoryItem");
		$out_mod=M('inventoryOut');
		$stat_mod=M("inventoryStat");

		$info=$out_mod->where(array('id'=>$this->_get('id')))->find();
		$where['in_out_id']=$this->_get('id');
		$where['quantity']=array('lt',0);
		$info['list']=$stat_mod->where($where)->field('itemid,quantity')->select();
		//如果是虚拟出库，刚加上入库信息
		if($info['type']==C("INVENTORY_OUT_TYPE_VIRTUAL"))
		{
			$inventory_rel_mod=M("InventoryVirtualRelation");
			$in_id=$inventory_rel_mod->where("out_id=".$this->_get('id'))->getField("in_id");
			$where['in_out_id']=$in_id;
			$where['quantity']=array('gt',0);
			if($in_id){
				$info['in_list']=$stat_mod->where($where)->field('itemid,quantity')->select();
			}

		}
		foreach ($info['in_list'] AS $key => $value){
			$information=$item_mod->where(array('id'=>$value['itemid']))->field('name,price,shelfinfo')->find();

			$info['in_list'][$key]['name']=$information['name'];
			$info['in_list'][$key]['shelfinfo']=$information['shelfinfo'];
			$info['in_list'][$key]['quantity']=$value['quantity'];

			$price_array[]=(int)(abs($value['quantity']) * $information['price']);
		}

		foreach ($info['list'] AS $key => $value){
			$information=$item_mod->where(array('id'=>$value['itemid']))->field('name,price,shelfinfo')->find();

			$info['list'][$key]['name']=$information['name'];
			$info['list'][$key]['shelfinfo']=$information['shelfinfo'];
			$info['list'][$key]['quantity']=(-$value['quantity']);

			$price_array[]=abs($value['quantity']) * $information['price'];
		}
		$info['price']=number_format(array_sum($price_array)/100,2);

        if($info['type'] == C("INVENTORY_OUT_TYPE_SYSTEM") && $info['ifselfpackage'] == 1){
            $order = M("UserSelfPackageOrder")->where("inventory_out_id=".$where['in_out_id'])->field("ordernmb")->find();
        }else{
            $order = M("UserOrder")->where("inventory_out_id=".$where['in_out_id'])->field("ordernmb")->find();
        }

        //快递信息
        $proxy_mod = M("UserOrderProxy");
        $proxy=$proxy_mod->where(array("orderid"=>$order['ordernmb']))->find();
        if($proxy){
            $userorderinfo=M("UserOrderSend")->where("orderid=".$order['ordernmb'])->find();
            $proxyInfo['proxysender'] = $proxy['proxysender'];
            $proxyInfo['proxyorderid'] = $proxy['proxyorderid'];
            $proxyInfo['senddate'] = $userorderinfo['senddate'];
            $info['proxyinfo'] = $proxyInfo;
        }
		$this->assign('alonemessage',$info);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * //删除出库单操作  包括人工,系统
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	 //注意权限
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       * 
       * update zhaoxiang 2013/1/7
       */	
	function deleteorder(){
		if($this->_get('model')){
			if($this->_get('model')=='out'){
				$out_mod=M("inventoryOut");
				$operator=$out_mod->where(array('id'=>$this->_get('id')))->getField('operator');

				$order_mod=M("userOrder");
				$result=$out_mod->where(array('id'=>$this->_get('id')))->setField('status',0);
				$orderresult=$order_mod->where(array('inventory_out_id'=>$this->_get('id')))->setField('inventory_out_id',0);

				if($result){
					$this->success('删除成功!');
				}else{
					$this->error("删除失败!");
				}
			}
		}
	}


	private function orderSystemWhere($arguments){

		if($arguments['userid']){
			$where['userid']=$arguments['userid'];
		}

		if($arguments['ordernum']){
			$where['orderid']=$arguments['ordernum'];
		}

		if($arguments['linkname']){
			$where['userid']=M("userAddress")->where(array('linkman'=>$arguments['linkname']))->getField('userid');
		}

		if($arguments['tel']){
			$where['userid']=$address_mod->where(array('telphone'=>$arguments['tel']))->getField('userid');
		}

		if($arguments['bname']){
			$where['boxid']=$arguments['bname'];
		}

		//提交时间
		if($arguments['startdate'] && $arguments['enddate']){
			$where['atime']=array(array('egt',$arguments['startdate'].' 00:00:00'),array('elt',$arguments['enddate'].' 23:59:59'));
		}elseif($arguments['startdate']){
			$where['atime']=array('egt',$arguments['startdate'].' 00:00:00');
		}elseif($arguments['enddate']){
			$where['atime']=array('elt',$arguments['enddate'].' 23:59:59');
		}

		if($arguments['outid']){
			$where['UserOrderSend.inventory_out_id']=$arguments['outid'];
		}
		
		if($arguments['child_id']){
			$where['child_id']=$arguments['child_id'];
		}

		return $where;
	}


	/**
       +----------------------------------------------------------
       * 查看订单 (分配权限用)
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  string	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/7
       */
	function getOrderInfo(){
		if($this->_get('outid')){
            $outInfo = M("inventoryOut")->where(array('id'=>trim($this->_get('outid'))))->find();
            $isSelfPackageOrder = $outInfo['type'] == C("INVENTORY_OUT_TYPE_SYSTEM") && $outInfo['ifselfpackage'] == 1;
			$order_mod=$isSelfPackageOrder?D("InventoryUserSelfPackageOrderView"):D("InventoryUserOrderView");

			$where=$this->orderSystemWhere(array_map('filterVar',$_GET));

			$list=$order_mod->where($where)->field('orderid,child_id,ordernmb,userid,boxid,boxname,productnum,paytime,inventory_out_id')->order('UserOrderSend.orderid DESC')->select();
			//echo $order_mod->getLastSql(); exit;

			$where['box.name']=array('exp','IS NOT NULL');
			$boxlist=$order_mod->where($where)->distinct('boxid')->field("boxid,boxname")->order('box.endtime DESC')->select();

			$this->assign ( "list", $list );
			$this->assign ( "boxlist", $boxlist );
			$this->assign("resour",'outOrderEntry');
			$this->display('outOrderSystem');
		}
	}

	/**
       +----------------------------------------------------------
       * 统计系统出库单品数量
       * 从系统待出库列表过来的  需要计算出一个订单或多个订单每个单品的数量
       +----------------------------------------------------------
       * @access public 
       +----------------------------------------------------------
       * @param  string	 
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/4
       */
	function orderStatistics(){

		if($_POST['sub']){
			$detail_mod=M("userOrderSendProductdetail");
			$item_mod=M("inventoryItem");

			foreach ($this->_post('order') AS $key => $value){
				$order_arr=explode("-",$value);
				$orderid=$order_arr[0];
				$childid=$order_arr[1];
				$info=$detail_mod->where(array('orderid'=>$orderid,'child_id'=>$childid))->field('productid,productprice')->order('productid ASC')->select();
				foreach ($info AS $k => $val){
					$item_mes = $item_mod->where(array('id'=>$val['productid']))->field('name,inventory_estimated')->find();

					$total[$val['productid']]['pname']=$item_mes['name'];
					$total[$val['productid']]['total']+=1;
					$total[$val['productid']]['pid']=$val['productid'];
					$total[$val['productid']]['estimated']=$item_mes['inventory_estimated'];

					//系统出库单超过理论库存,特殊标识
					if($total[$val['productid']]['total'] > $item_mes['inventory_estimated']){
						$total[$val['productid']]['flag']='outnumber';
					}
				}
			}
		}
		$list['products']=$total;
		$list['flag']='系统';
		$list['ordernmb']=$this->_post('order');
		$this->assign('list',$list);
		$this->display('outOrderHuman');
	}


	/**
       +----------------------------------------------------------
       * 统计单品出入库明细
       +----------------------------------------------------------
       * @access public 
       +----------------------------------------------------------
       * @param  string	  pid  单品id
       * @param  string	  reality 	实际库存
       * @param  string	  plan	 	计划库存
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/5
       */
	function inOutInfo(){
		if($this->_get('pid')){
			$stat_mod=M("inventoryStat");
			$item_mod=M("inventoryItem");
			$out_mod=M("inventoryOut");
			$in_mod=M("inventoryIn ");
			$info=$item_mod->where(array('id'=>trim($this->_get('pid'))))->field('inventory_real,inventory_estimated')->find();
			$list['reality']=$info['inventory_real'];
			$list['plan']=$info['inventory_estimated'];

			$where['itemid']=trim($this->_get('pid'));
			$where['status']=1;
			$list['info']=$stat_mod->where($where)->field('add_time,message,operator,quantity,in_out_id')->select();

			foreach ($list['info'] AS $key => $value){

				if($value['quantity']<0){
					$outinfo=$out_mod->where(array('id'=>$value['in_out_id']))->field('description,agreeoperator')->find();
					$list['info'][$key]['agreeoperator']=$outinfo['agreeoperator'];
					$list['info'][$key]['operator'].='=>出库';
					if(empty($value['message'])){
						$list['info'][$key]['message']=$outinfo['description'];
					}
				}else{
					$list['info'][$key]['operator'].='=>入库';
					if(empty($value['message'])){
						$list['info'][$key]['message']=$in_mod->where(array('id'=>$value['in_out_id']))->getField('description');
					}
				}
			}
		}

		$this->assign('list',$list);
		$this->display();
	}


	/**
       +----------------------------------------------------------
       * 获取单品名称或PID 
       +----------------------------------------------------------
       * @access public   AJAX
       +----------------------------------------------------------
       * @param  string	  pid  单品id
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/5
       */	
	function getProductsData(){
		$item_mod=M("inventoryItem");

		if($this->_post('pid')){
			$pid=trim($this->_post('pid'));
			$result=$item_mod->where(array('id'=>$pid))->field('name,inventory_estimated')->find();

		}
		if($result){
			$this->ajaxReturn($result['name'],$result['inventory_estimated'],1);
		}else{
			$result=$item_mod->where(array('name'=>$this->_post('pname')))->getField('id');
		}
	}

	/**
	 * 查看盒子详细信息
	 */
	public function getBoxMessage() {

		if($this->_get('orderid')){
			$item_mod=M("inventoryItem");
			$outid=trim($this->_get('outid'));

			$pname_array=array();
			$order_mod=M("userOrderSendProductdetail");
			$order_arr=explode("-",trim($this->_get('orderid')));
			$orderid=$order_arr[0];
			$childid=$order_arr[1];
			
			$procudtsInfo=$order_mod->where(array('orderid'=>$orderid,'child_id'=>$childid))->field('productid,productprice')->select();
			foreach ($procudtsInfo AS $key=>$value){
				if(array_key_exists($value['productid'],$pname_array)){
					$procudtsInfo[$key]['pname']=$pname_array[$value['productid']];
				}else{
					$pname_array[$value['productid']]=$item_mod->where(array('id'=>$value['productid']))->getField('name');
					$procudtsInfo[$key]['pname']=$pname_array[$value['productid']];
				}
				$procudtsInfo[$key]['quantity']=1;
				$procudtsInfo['total']+=$value['productprice'];
			}


			echo '<table border="0">';
			echo '<th>单品ID</th><th>数量</th><th>单品名称</th><th>价格</th>';
			foreach ($procudtsInfo AS $k => $v){
				echo '<tr align="center">';
				echo '<td>'.$v['productid'].'</td>';
				echo '<td>'.$v['quantity'].'</td>';
				echo '<td><div style="color:#004040;font-size:14px;">'.$v['pname'].'</div></td>';
				echo '<td>'.$v['productprice'].'</td>';
				echo '</tr>';
			}
			echo "<tr><td colspan='4'><div style='color:red'>总价为".$procudtsInfo['total']."</div></td></tr>";
			echo '</table>';
		}else{
			$this->error("参数不正确,请检查!");
		}
	}



	/**
       +----------------------------------------------------------
       * 导出已出库订单列表
       +----------------------------------------------------------
       * @access type   GET
       +----------------------------------------------------------
       * @param  string	  outid   出库单id
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/5
       * update zhaoxiang 2013/1/10  tips:导出excel增加单品数量
       */	
	function exportOrder(){

		$address_mod=M("userOrderAddress");
		$order_mod=M("userOrderSend");
		$item_mod=M("inventoryItem");
		$detail_mod=M("userOrderSendProductdetail");
		$out_mod=M("inventoryOut");

		$outid=filterVar($this->_get('outid'));

		if($outid){
			$name=$out_mod->where(array('id'=>$outid))->getField("title");
		}else{return false;}

		$orderList=$order_mod->where(array('inventory_out_id'=>$outid))->field('orderid,child_id')->select();

		foreach ($orderList AS $key => $value){
			$ptotal=array();
			$address=$address_mod->where(array('orderid'=>$value['orderid']))->field('linkman,telphone,province,city,district,address')->find();

			$list=$detail_mod->where(array('orderid'=>$value['orderid'],'child_id'=>$value['child_id']))->field('productid')->order('productid')->select();

			$str.='T'.$value['orderid']."_".$value["child_id"]."\n";
			$str.=$address['linkman']."\n";
			$str.=$address['province'].$address['city'].$address['district'].$address['address']."\n";
			$str.=$address['telphone']."\n";
			$str.='P'.count($list)."\n";

			foreach ($list AS $k => $v){

				$plist=$item_mod->where(array('id'=>$v['productid']))->getField('name');

				$ptotal[$v['productid']]['total']+=1;
				$ptotal[$v['productid']]['name']=$plist;
			}

			foreach ($ptotal AS $ck => $cv){
				if($cv['total']>1){
					$str.='P'.'['.$cv['total'].']'.$cv['name']."\n";
				}else{
					$str.='P'.$cv['name']."\n";
				}
			}
		}
		$this->outputExcel($str,$name);
	}

	//更新单品数量
	private function updateInventory(){
		$itemModel=D("InventoryItem");
		$itemModel->updateInventoryByInOut();
	}

	/**
       +----------------------------------------------------------
       * excel导出出库单(捡货)
       +----------------------------------------------------------
       * @access str 	要进行导出的数组
       * @access name	导出的文件名	
       +----------------------------------------------------------
       * @param  private
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/29
       */	
	private function outputExcel($str,$name){
		$export=explode("\n",$str);

		import('@.ORG.Util.PHPExcel.PHPExcel');

		$objPHPExcel = new PHPExcel();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$this->convertGBK($name).'.xlsx"');
		header('Cache-Control: max-age=0');

		$objActSheet = $objPHPExcel->getActiveSheet();  //得到当前活动的表
		$objActSheet->getColumnDimension( 'A')->setWidth(100);

		foreach ($export as $key=>$val){
			$num=$key+1;
			if(substr($val,0,1)=='T'){
				$objActSheet->setCellValue('A'.$num,' '.substr($val,1));
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->getStartColor()->setARGB('FFFF00');
			}else if(substr($val,0,1)=='P'){
				$objActSheet->setCellValue('A'.$num,' '.substr($val,1));
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->getStartColor()->setARGB('FF8000');
			}else{
				$objActSheet->setCellValue('A'.$num,$val);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFill()->getStartColor()->setARGB('008080');
			}
			$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$num)->getFont()->setSize(15);
		}


		$objWriter = PHPExcel_IOFactory:: createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save( 'php://output');
		exit;
	}

	/**
       +----------------------------------------------------------
       * 转换编码
       +----------------------------------------------------------
       * @type private
       +----------------------------------------------------------
       * @param  string	  str  要转换的字符串
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/29
       */
	private function convertGBK($str){
		if(empty($str)) return '';
		return iconv('utf-8','gb2312', $str);
	}

	/**
       +----------------------------------------------------------
       * 导出已出库订单列表(快递)
       +----------------------------------------------------------
       * @access type   GET
       +----------------------------------------------------------
       * @param  string	  outid   出库单id
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/1/5
       * update zhaoxiang 2013/1/10  tips:导出excel增加单品数量
       */	
	function exportExpressOrder(){

		$address_mod=M("UserOrderAddress");
		$order_mod=M("userOrderSend");
		$item_mod=M("inventoryItem");
		$detail_mod=M("userOrderSendProductdetail");
		$out_mod=M("inventoryOut");

		if($this->_get('outid')){
			$name=$out_mod->where(array('id'=>$this->_get('outid')))->getField("title");
		}else{return false;}

		$orderList=$order_mod->where(array('inventory_out_id'=>trim($this->_get('outid'))))->field('orderid,child_id')->select();

		$str="订单号,子订单号,收货人姓名,地址,电话,配货单品数量,单品列表\n";

		foreach ($orderList AS $key => $value){
			$ptotal=array();
			$address=$address_mod->where(array('orderid'=>$value['orderid']))->find();

			$list=$detail_mod->where(array('orderid'=>$value['orderid'],'child_id'=>$value['child_id']))->field('productid')->order('productid')->select();

			$str.='T'.$value['orderid'].",".$value['child_id'].",".$address['linkman'].','.$address['province'].$address['city'].$address['district'].$address['address'].','.$address['telphone'].','.count($list);

			foreach ($list AS $k => $v){

				$plist=$item_mod->where(array('id'=>$v['productid']))->getField('name');

				$ptotal[$v['productid']]['total']+=1;
				$ptotal[$v['productid']]['name']=$plist;
			}

			foreach ($ptotal AS $ck => $cv){
				if($cv['total']>1){
					$str.=','.'['.$cv['total'].']'.$cv['name'];
				}else{
					$str.=','.$cv['name'];
				}
			}
			$str.="\n";
		}
		outputExcel ( iconv ( "UTF-8", "GBK",$name.'-'.date ( "Y-m-d" )), $str );
		exit();
	}


	//判断是否有该产品pid和是否小于理论库存数
	private function returnInventoryitemData($where){
		return M('inventoryItem')->where($where)->getField("inventory_estimated");
	}


	/**
	 * 创建虚拟出库单
	 * @author litingting
	 * update zhaoxiang 2013.5.16
	 */
	public function createVirtualExport(){
		if($_POST['sub']){

			$pid = $this->_post('pid');
			$quantity = $this->_post('quantity');

			//创建虚拟出库 flag type=3
			$add_out_data=array(
			'type'=>3,
			'title'=>$this->_post('outname'),
			'outdate'=> $this->_post('outtime'),
			'description'=>$this->_post('remark'),
			'cdatetime'=>date('Y-m-d H:i:s'),
			'operator'=>trim($_SESSION['loginUserName']),
			'status'=>1,
			'ifagree'=>0,
			'agreeoperator'=>'',
			'agreedatetime'=>'',
			'ifconfirm'=>0,
			'confirmoperator'=>'',
			'confirmdatetime'=>''
			);

			//step1
			//出库单的lastid
			$out_last_id = M("InventoryOut")->add($add_out_data);

			$inventory_stat_mod=M("InventoryStat");

			foreach ($pid as $key => $value){

				$estimated = $this->returnInventoryitemData(array('id'=>$pid[$key]));
				if(empty($estimated) || ($quantity[$key] > $estimated)){
					continue;    //id不存在
				}else{
					$data =  array(
					'itemid'=>$value,
					'message'=>$this->_post('remark'),
					'operator'=>$_SESSION['loginUserName'],
					'quantity'=>-$quantity[$key],
					'add_time'=>strtotime(date('Y-m-d H:i:s')),
					'status'=>1,
					'in_out_id'=>$out_last_id
					);

					//利用out_last_id完成出库单数据
					$inventory_stat_mod->add($data);
				}
			}

			//step2  入库单数据统计
			//创建虚拟入库
			$add_in_data=array(
			'title'=>"虚拟入库",
			'customer'=>"",
			'arrivaldate'=>date("Y-m-d H:i:s"),
			'area'=>"",
			'description'=>"虚拟入库",
			'operator'=>trim($_SESSION['loginUserName']),
			'cdatetime'=>date("Y-m-d H:i:s"),
			'status'=>0,
			'ifconfirm'=>0,
			'confirmoperator'=>'',
			'confirmdatetime'=>''
			);

			$in_last_id = M("InventoryIn")->add($add_in_data);

			$cpid = $this->_post('cpid');
			$pakagenum = $this->_post('pakagenum');

			foreach ($cpid as $ky => $val){

				$stat_data = array(
				'itemid'=>$cpid[$ky],
				'message'=>$this->_post('remark'),
				'operator'=>$_SESSION['loginUserName'],
				'quantity'=>$pakagenum[$ky],
				'add_time'=>strtotime(date('Y-m-d H:i:s')),
				'status'=>1,
				'in_out_id'=>$in_last_id
				);
				$inventory_stat_mod->add($stat_data);
			}

			$virtual_data = array('in_id'=>$in_last_id,'out_id'=>$out_last_id);

			if(M("InventoryVirtualRelation")->add($virtual_data)){
				$this->success("操作成功!",U("Inventory/virtualOutList"));
			}else{
				$this->error("操作失败!");
			}
			exit();
		}
		$this->display();
	}

	/**
	 * 虚拟出库单列表
	 * @author litingting
	 */
	public function virtualOutList(){
		$out_mod=M('InventoryOut');
		import ( "@.ORG.Page" ); // 导入分页类库
		extract ($_GET);
		//出库单名称
		if($ordername){
			$where['title']=array('like',"%".trim($this->_get('ordername'))."%");
		}

		//提交时间
		if($startdate && $enddate){
			$where['cdatetime']=array(array('egt',$this->_get('startdate').' 00:00:00'),array('elt',$this->_get('enddate').' 23:59:59'));
		}elseif($startdate){
			$where['cdatetime']=array('egt',$this->_get('startdate').' 00:00:00');
		}elseif($enddate){
			$where['cdatetime']=array('elt',$this->_get('enddate').' 23:59:59');
		}


		//申请人查询
		if($proposer){
			$where['operator']=$proposer;
		}

		//出库单类型
		$where['type']=3;
		$where['status']=1;

		if(isset($ifagree)){
			$where['ifagree']=$ifagree;
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

		$humanlist=$out_mod->distinct(true)->field("operator")->where("type=3")->select();
		$count=$out_mod->where($where)->count();
		$p = new Page ( $count, 15);
		$list=$out_mod->where($where)->field('description,confirmdatetime',true)->limit ($p->firstRow.','.$p->listRows )->order($order)->select();
		$page = $p->show ();
		$this->assign ( "page", $page );
		$this->assign('list',$list);
		$this->assign('human',$humanlist);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 按查询条件导出出库报表
       +----------------------------------------------------------
       * @access public   
       +----------------------------------------------------------
       * @param  Array  $list  按条件查询出来的数组
       +-----------------------------------------------------------
       * @author zhaoxiang 2013/3/13  
       */	
	public  function exportHumanOrderData($list){

		$stat_mod=M("inventoryStat");
		$item_mod=M("inventoryItem");

		$str="出库单名称,出库单备注,出库确认时间,出库单总金额\n";

		foreach ($list as $key => $value){
			$itemidList=array();
			$total = 0;
			$itemidList=$stat_mod->where(array('in_out_id'=>$value['id'],'quantity'=>array('lt',0)))->field('itemid,quantity')->select();

			foreach ($itemidList AS $k =>$v){
				$price = 0;
				$price = $item_mod->where(array('id'=>$v['itemid']))->getField('price');

				$total+=(-$v['quantity']) * $price;
			}

			$str.=$value['title'].",".preg_replace("'([\r\n])[\s]+'", "，", $value['description']).",".$value['confirmdatetime'].",".(int)$total."\n";
		}

		outputExcel ( iconv ( "UTF-8", "GBK",'出库单报表'), $str );
		exit();
	}

	//搜索套装产品的子产品列表
	function searchProductChildrenList(){

		$where['pid'] = array('exp',"IN(select relation_id FROM `inventory_item` WHERE id = ".$this->_post("productid").")");

		$clist = M("Products")->where($where)->getField("productlist");

		$list = explode(',',$clist);

		foreach ($list as $key => $val){
			$ilist[$key]['pid'] = $val;
			$ilist[$key]['pname'] = M("products")->where(array('pid'=>$val))->getField("pname");
		}

		if($ilist){
			$this->ajaxReturn($ilist,'查询成功!',1);
		}else{
			$this->ajaxReturn(0,'未找到相关产品的子产品列表',0);
		}
	}
	
	
	/**
	 * 导出订单详情
	 * @author penglele    
	 */
	public function exportOrderInfo(){
		$outid=$_GET['outid'];
// 		$filename=M("InventoryOut")->where("id=".$outid)->getfield("title");
// 		$filename=$filename.".xls";
// 		header('Cache-Control: no-cache, must-revalidate');
// 		header('Content-type: application/vnd.ms-excel');
// 		header('Content-Disposition: filename='.$filename);
		
		$orderList=M("UserOrderSend")->where(array('inventory_out_id'=>trim($outid)))->field("orderid,child_id")->select();
		if(!$orderList){
			exit;
		}
		$list=array();
		$order_send_mod=M("UserOrderSendProductdetail");
		$item_mod=M("InventoryItem");
		$pro_mod=M("Products");
		$brand_mod=M("ProductsBrand");
		$productmod=D("Products");
		$order_mod=M("UserOrder");
		$order_address_mod=M("UserOrderAddress");
        $out_mod = M("inventoryOut");
        $outInfo = $out_mod->where(array('id'=>trim($outid)))->find();
        $isSelfPackageOrder = $outInfo['type'] == C("INVENTORY_OUT_TYPE_SYSTEM") && $outInfo['ifselfpackage'] == 1;
		if($orderList){
			$get_tips=D("Article")->getArticleList(773,1);
			$tips=$get_tips[0];
			foreach($orderList as $key=>$val){
                if($isSelfPackageOrder){
                    $itemid_list=$order_send_mod->field("productid")->distinct(true)->where("self_package_order_id=".$val['orderid'])->select();
                }else{
                    $itemid_list=$order_send_mod->field("productid")->distinct(true)->where("orderid=".$val['orderid'])->select();
                }
				if($itemid_list){
					$info=array();
					$per_arr=array();
					$price=0;
					foreach($itemid_list as $ikey=>$ival){
						$product_arr=array();
                        $proinfo = $pro_mod->getByPid($ival['productid']);
						$item_info=$item_mod->where("id=".$proinfo['inventory_item_id'])->find();
						$product_arr['price']=bcdiv($item_info['price'], 100, 2);
						$product_arr['pname']=$item_info['name'];
						$product_arr['norms']=$item_info['norms'];
						$product_arr['enddate']=(empty($item_info['validdate']) || $item_info['validdate']=="0000-00-00") ? "" : $item_info['validdate'] ;
						$product_arr['cname']=$brand_mod->where("id=".$proinfo['brandcid'])->getField("name");
						$effect_arr=$productmod->getProductsEffectByPid($ival['productid']);
						$product_arr['effect']=$effect_arr[2];
						//$type=$category_mod->where("cid=".$proinfo['secondcid'])->getfield("cname");
						//$product_arr['type']= $type ? $type : "" ;
                        if($isSelfPackageOrder){
                            $product_arr['num']=$order_send_mod->where("self_package_order_id=".$val['orderid']." AND productid=".$ival['productid'])->count();
                        }else{
                            $product_arr['num']=$order_send_mod->where("orderid=".$val['orderid']." AND productid=".$ival['productid'])->count();
                        }

						$price=$price+($item_info['price']*$product_arr['num']);
						$per_arr[]=$product_arr;
					}
					if($per_arr){
                        if($isSelfPackageOrder){
                            $orderinfo=M("UserSelfPackageOrder")->where("ordernmb=".$val['orderid'])->find();
                        }else{
                            $orderinfo=$order_mod->where("ordernmb=".$val['orderid'])->find();
                        }
						$info['list']=$per_arr;
						$info['totalprice']=bcdiv($price, 100, 2);
						$info['orderid']=$val['orderid'];
						if($val['child_id']) $info['orderid']=$info['orderid']."(".$val['child_id'].")";
						$info['addtime']=$orderinfo['addtime'];
						$info['linkman']=$order_address_mod->getById($orderinfo["address_id"])["linkman"];
						$list[]=$info;
					}
				}
			}

		}
		$count=count($list);
		$this->assign("count",$count);
		$this->assign("list",$list);
		$this->assign("title","发货明细单");
		$this->assign("tips",$tips);
		$this->display();
// 		$info=$this->fetch("exportOrderInfo");
// 		echo $info;		
	}
	
	public function getInfo(){
		$inventoryItemId = $_POST['inventoryItemId'];
		$inventoryItem = M("InventoryItem")->where("id=".$inventoryItemId)->find();
		if($inventoryItem){
        	$this->ajaxReturn(1,$inventoryItem,1);
        }else{
        	$this->ajaxReturn(0,'未找到相关信息',0);
        }
	}

    /**
    +----------------------------------------------------------
     * 修改订单发送信息
    +----------------------------------------------------------
     * @access  publiv
    +----------------------------------------------------------
     * @param   outid    出库单id
    +-----------------------------------------------------------
     * @update zhaoxiang 2013.1.25
     */
    public function editOrderSendInfo() {
        $outid = $_REQUEST ["outid"];
        if (!isset( $outid )){
            return false;
        }else{
            $userorderinfo=M("UserOrderSend")->where("inventory_out_id=".$outid)->find();
            $userorderinfo['linkman']=M("UserOrderAddress")->where("orderid=".$userorderinfo['orderid'])->getfield("linkman");
            $userorderinfo['orderid']=$userorderinfo['orderid'];
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
    public function editSendPostInfo() {
        $orderid= $_POST ["orderid"];
        $where['orderid']=$orderid;

        $data ["proxysender"] = $_POST ["proxysender"];
        $data ["proxyorderid"] = $_POST ["proxyorderid"];
        $send_data=$data;
        $send_data ["senddate"] = $_POST ["senddate"];
        $ordersend=M ( "UserOrderSend" );
        if (false !==$ordersend->where ( $where )->save ( $send_data )) {
            $proxy_mod = M("UserOrderProxy");
            $if_proxy=$proxy_mod->where($where)->find();
            if($if_proxy){
                $proxy_mod->where($where)->save($data);
            }else{
                $send_data['orderid'] = $orderid;
                $send_data['status'] = 0;
                $proxy_mod->add($send_data);
            }
            $this->success ( '操作成功' );
        } else {
            $this->error ( '操作失败' . $ordersend->getDbError () );
        }
    }
}
?>
