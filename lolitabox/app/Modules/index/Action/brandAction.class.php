<?php
/**
 * 品牌库控制器
 * @author litingting
 */
class brandAction extends commonAction{
     
    
	/**
	 * (non-PHPdoc)品牌库首页
	 * @see CommonAction::index()
	 * @author litingting
	 */
    public function index(){
    	$article_mod = D("Article");
    	$brand_mod = D("ProductsBrand");
    	$ac=$_GET['ac'] ? $_GET['ac'] : 2;
		if($ac==1){
			//新品试用
			$template="products_ajaxlist";
			$list=$article_mod->getNewProductsList();
			$total_num=count($list);
		}else if($ac==2){
			//品牌
			$template="index_ajaxlist";
			$list = $article_mod->getRemmendAdBrandList();
			$total_num=$article_mod->getRemmendAdBrandNum();			
		}
    	$param = array(
    			"total" =>$total_num,
    			'result'=>$list,			//分页用的数组或sql
    			'listvar'=>'list',			//分页循环变量
    			'listRows'=>$total_num,			//每页记录数
    			'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
    			'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
    			'template'=>'brand:'.$template,//ajax更新模板
    			"parameter" =>"ac={$ac}"
    	);
    	$this->assign("template",$template);
    	$this->page($param);    	
    	$return['title'] = "合作品牌,品牌俱乐部-".C("SITE_NAME");
    	$this->assign("return",$return);
	    $this->display();
	}
    
	
	/**
	 * 搜索品牌页
	 */
	public function search(){
		$tag = trim($this->_request('tag'));
		$tag = str_replace("%","\%",$tag);
		$tag = str_replace("_", "\_", $tag);
		if(empty($tag)){
			$this -> error("关键字不能为空");
			die;
		}
		$order = $_GET['order'] ? $_GET['order']:"";
		$brand_mod = D("ProductsBrand");
		$count = $brand_mod->getBrandCountByTag($tag);
		if($count){
			$list = $brand_mod->getBrandListByTag($tag,$this->getLimit(15),'',$order);
		}
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>15,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'brand:search_ajaxlist',//ajax更新模板
		);
		$this->assign("count",$count);
		$this->page($param);
		$return['title'] = "品牌搜索-".C("SITE_NAME");
		$return['interest_brand'] = $brand_mod->getInterestBrandList(5);
		$this ->assign("return",$return);
		$this ->display();
	}
	
	
	
	/**
	 * 美妆库品牌搜索页【按条件搜索】
	 * @author litingting
	 */
	public function lists(){
	
		$firstchar = trim($_GET['firstchar']);
		$area = trim($_GET['area']);
		$order = trim($_GET['order']) ? trim($_GET['order'])." DESC":"id DESC";
	
		$brand_mod = D("ProductsBrand");
		$return ['firstchar'] = $brand_mod ->getBrandFirstchar($area);
		$pagesize=15;
		
		$list = $brand_mod ->getBrandListByFirstchar($firstchar,$area,$this->getLimit($pagesize),'',$order);
		$count = $brand_mod->getBrandCount($area,$firstchar);
	    
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'brand:list_ajaxlist',//ajax更新模板
		);
		$this->assign("return",$return);
		$this->page($param);
		$return['title'] = "品牌集中营-".C("SITE_NAME");
		$this->assign("return",$return);
		$this ->display();
	}
	
	
	/**
	 * 品牌详情页
	 * @see CommonAction::index()
	 * @author litingting
	 */
	public function detail(){
		$brandid = $_GET['brandid'];
		$ac = $this->_get("ac") ? $this->_get("ac"):1;
		$products_brand_mod =D("ProductsBrand");
		if($brandid==1){
			header("location:".U("club/benefit"));exit;
		}
		$return['info']=$products_brand_mod->getBrandInfo($brandid,'',$this->userid);
		if(empty($return['info'])){
			$this->error("品牌不存在");
			exit();
		}
		
		$products_mod = D("Products");
        if($ac==2){//关注页
        	$follow_mod = D("Follow");
        	$pagesize=24;
	     	$list= $follow_mod ->getFansListByBrandid($brandid,$this->getlimit($pagesize),$this->userid);
		    $count = $follow_mod->getFansNumByBrandid($brandid);
		    $template = "brand:brand_fans_ajaxlist";
        }else{     //产品页
        	$pagesize = 15;
        	$list = $products_mod ->getProductsListByBrandid($brandid,$this->getlimit($pagesize));
        	$count = $products_mod ->getProductNumByBrandid($brandid);
        	$return['info']['product_num']=$count;
        	$template = "brand:brand_pro_ajaxlist";
        }
		$param = array(
				"total" =>$count ,
				'result'=>$list ,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>$template,//ajax更新模板
				"parameter" =>"ac={$ac}&brandid=".$_REQUEST['brandid'],
		);
		$this->page($param);
		$return['pro_list'] = $products_mod->getCooperateProductsList($brandid,'');
		$return['brand_infolist'] = D("Article")->getBrandInfoList($brandid,10);
		$return['title']=$return['info']['name']."-".$return['info']['name']."全系产品试用_付邮试用_积分试用"."-".C("SITE_NAME");
		$return['keywords']=$return['info']['name']."全部化妆品试用,".$return['info']['name']."的介绍,".$return['info']['name']."的资讯,".$return['info']['name']."的故事,".$return['info']['name']."的所有化妆品试用评测,".$return['info']['name']."的化妆品产品功效,".$return['info']['name']."的所有化妆品图片,".$return['info']['name']."的所有化妆品使用方法,".$return['info']['name']."的所有化妆品购买方式,".$return['info']['name']."的化妆品的价格";
		$des="萝莉盒提供".$return['info']['name']."的化妆品试用装（小样）或化妆品正装试用，包括付邮试用、积分试用等多种化妆品试用方式供你选择.";
		$des=$return['info']['category'] ? $des."".$return['info']['name']."属于".$return['info']['category'] : $des ;
		$des=$return['info']['founders'] ? $des.",由".$return['info']['founders']."创建" : $des ;
		if($return['info']['founders'] && $return['info']['found_time']){
			$des=$des."于".$return['info']['found_time']."年";
		}
		$totalnum=$products_mod ->getProductNumByBrandid($brandid);
		if($totalnum>0){
			$des=$des."，现有产品".$totalnum."个.";
		}
		$return['description']=$des;
		$this->assign("template",$template);
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 产品页
	 * @author litingting
	 */
	public function products(){
		$pid = $_REQUEST['pid'];
		if(empty($pid)){
			$this->error("缺少参数");
			die;
		}
		$products_mod = D("Products");
		$return['info'] = $products_mod->getProductInfo($pid);
		if(empty($return['info'])){
			$this->error("产品不存在");
			exit();
		}
		$return['returnurl']=urlencode("http://".$_SERVER["SERVER_NAME"]."/products/".$pid.".html");
		$products_brand_mod =D("ProductsBrand");
		$share_mod = D("UserShare");
		$list = $share_mod ->getShareListByPid($pid,$this->getlimit(20));
		$count = $share_mod->getShareCountByPid($pid);
		$share_total = $share_mod ->getShareTotalByPid($pid);
		$minus_num=$share_total-$count;
		$this->assign("share_total",$share_total);
		$this->assign("minus_num",$minus_num);
		$param = array(
				"total" =>$count,
				'result'=>$list ,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>20,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>'home:share_waterfall',//ajax更新模板
		);
		$this->page($param);
		
		$return['sale_list'] = $products_mod ->getProductsOnSelling($pid);
		$sale_num=0;
		foreach($return['sale_list'] as $val){
			if($val){
				$sale_num++;
			}
		}
		$return['sale_num']=$sale_num;
		$return['brand_infolist'] = D("Article")->getBrandInfoList($return['info']['brandcid'],3);
		
		//seo title
		$return['title']=$return['info']['pname']."-".$return['info']['pname']."试用装_付邮试用_积分试用"."-".C("SITE_NAME");
		//seo keywords
		$return['keywords']=$return['info']['pname']."的评测,".$return['info']['pname']."的分享,".$return['info']['pname']."的功效,".$return['info']['pname']."的图片,".$return['info']['pname']."的使用方法,".$return['info']['pname']."的购买方式,".$return['info']['pname']."的价格,".$return['info']['brandname'];
		//seo description
		$des="萝莉盒正在提供".$return['info']['pname']."试用装或正装试用,包括付邮试用、积分试用等多种化妆品试用方式供你选择.";
		//产品功效
		$des=$return['info']['effect'][2] ? $des.$return['info']['pname']."的功效是".$return['info']['effect'][2]."," : $des ;
		//适用人群
		$des=$return['info']['for_people'] ? $des."适用人群:".$return['info']['for_people']."," : $des ;
		//正装价格
		$des=$return['info']['goodsprice'] ? $des."正装价格:".$return['info']['goodsprice']."元," : $des;
		//正装规格
		$des=$return['info']['goodssize'] ? $des."正装规格:".$return['info']['goodssize'] : $des;
		$return['description']=$des;
		$this->assign("return",$return);
		$this->display();
		
	}
	
	/**
	 * 弹出层
	 * @author litingting
	 */
	public function dialog_products(){
		$pid = $_REQUEST['pid'];
		if(empty($pid)){
			echo "产品不存在";
			exit();
		}
		$products_mod = D("Products");
		$products_brand_mod =D("ProductsBrand");
		$share_mod = D("UserShare");
		$return['info'] = $products_mod->getProductInfo($pid);
		$return['sale_list'] = $products_mod ->getProductsOnSelling($pid);
		$sale_num=0;
		foreach($return['sale_list'] as $val){
			if($val){
				$sale_num++;
			}
		}
		$return['sale_num']=$sale_num;
		$return['share_list'] = $share_mod ->getHotShareByPid($pid,5);
		$total = $share_mod->getShareCountByPid($pid);
		$this ->assign("total",$total);
		$this->assign("return",$return);
		echo $this->fetch();
	}
	
	/**
	 * 品牌资讯
	 * @author penglele
	 */
	public function info(){
		$id=$_GET['id'];
		if(!$id){
			$this->error("请求信息不存在");exit;
		}
		//信息详情
		$info=D("Article")->getArticleInfoById($id);
		//判断其是否属于品牌资讯
		if(!$info || $info['status']!=1 || $info['cate_id']!=737 || !$info['orig']){
			$this->error("请求信息不存在");exit;
		}
		$return['ainfo']=$info;
		$products_brand_mod =D("ProductsBrand");
		$return['info']=$products_brand_mod->getBrandInfo($info['orig'],'',$this->userid);
		//判断品牌信息是否存在
		if(!$return['info']){
			$this->error("请求信息不存在");exit;
		}
		$return['zixun_list']=D("Article")->getBrandInfoList($info['orig']);
		$return['title']=$info['title']."-品牌资讯-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 增加产品购买渠道点击数
	 * @author penglele
	 */
	public function get_buychannel_hit(){
		$id=$_POST['id'];
		D("Products")->addBuychannelHit($id);
		$this->ajaxReturn(1,"success",1);
	}
	
}

?>