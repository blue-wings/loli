<?php
/**
 * 网站文章模型
 * table name: article
 * 
 *
 * @example:
	$article_model=D("Article");
	获取列表
	$list=$article_model->getArticleListUnChecked(544,1,10);
	var_dump($list);
	获取详细内容
	$article=$article_model->getArticleInfoById(728);
 * 
 */
class ArticleModel extends Model {

     
	/**
	 * 通过分类ID获取文章列表
	 * @param int $cid
	 * @param string $limit
	 * @author litingting
	 */
     public function getArticleList($cid,$limit="",$cache=false){
     	 if(empty($cid)){
     	 	return false;
     	 }
     	 $where ['status']  = 1;
     	 $where ['cate_id'] = $cid;
     	 $expire = $cache ? 3600:null;
     	 return $this->where($where)->cache($cache,$expire)->order ( "ordid desc,id desc" )->limit($limit)->select();
     } 
	
	
     /**
      * 获取首页产品列表
      * @param string $limit
      * @param unknown_type $me
      * @author litingting
      */
     public function getRemmendProductList($limit=""){
     	$list = $this->getArticleList(733,$limit);
     	$products = D("Products");
     	$inventory_item = M("InventoryItem");
     	foreach($list as $key =>$val){
			$pinfo=$products->getSimpleInfoByItemid($val['orig']);
     		if(empty($pinfo)){
     			unset($list[$key]);
     		}else{
     			$list[$key] = $pinfo;
     		}
     	}
     	return $list;
     }
     
     
     
     /**
      * 获取首页焦点图
      * @param string $limit
      * @return mixed
      * @author litingting
      */
     public function getIndexFocusPicList($limit=3){
     	return $this->getArticleList(730,$limit);
     }
     
     
     /**
      * 获取首页萝莉公告
      * @param string $limit
      * @author litingting
      */
     public function getLoliNoticeList($limit=5){
     	$list=$this->getArticleList(731,$limit);
     	if($list){
     		foreach($list as $key=>$val){
     			if($val['info']){
     				$list[$key]['url']=U("info/article",array('aid'=>$val['id']));
     			}
     		}
     	}
     	return $list;
     }
     
     
     /**
      * 获取首页最新活动列表
      * @param stirng $limit
      * @return mixed
      * @author litingting
      */
     public function getNewActivityList($limit=2){
     	return $this->getArticleList(732,$limit);
     }
	
     
     /**
      * 获取滚动品牌LOGO列表
      * @param unknown_type $limit
      * @return boolean
      * @author litingting
      */
     public function getBrandLogoList($limit=""){
     	return $this->getArticleList(586,$limit);
     }
      
     /**
      * 获取综合品牌排行榜
      * @param stirng $limit
      * @return mixed
      * @author litingting
      */
     public function getComplexBrandTop($limit=5){
     	$list =  $this->getArticleList(111,$limit);
     	$brand_mod = M("ProductsBrand");
     	foreach($list as $key =>$val){
     		$brandinfo = $brand_mod ->getById($val['orig'],"id,name,logo_url,fans_num");
     		$list [$key] =$brandinfo;
     	}
     	return $list;
     }
     
     /**
      * 获取护肤品牌排行榜
      * @param stirng $limit
      * @return mixed
      * @author litingting
      */
     public function getSkincareBrandTop($limit=5){
     	return $this->getArticleList(111,$limit);
     }
     
     /**
      * 获取彩妆品牌排行榜
      * @param stirng $limit
      * @return mixed
      * @author litingting
      */
     public function getMakeupBrandTop($limit=5){
     	return $this->getArticleList(111,$limit);
     }
      
     
     
     /**
      * 获取热门推荐内容
      * @param string $limit
      * @author litingting
      */
     public function getHotContent($limit = "6"){
     	return $this->getArticleList(C("HOT_CONTENT_CATEID"),$limit);
     }
     
     
     /**
      * 获取试用排行榜列表
      * @param string $limit
      * @author litingitng
      */
     public function getTryList($limit=10){
     	$list = $this->getArticleList(735,$limit);
     	$products = D("Products");
     	$inventory_item = M("InventoryItem");
     	foreach($list as $key =>$val){
//      		if($pid=$inventory_item->where("id=".$val['orig'])->getField("relation_id")){
//      			$pinfo = $products ->getSimpleInfo($pid);
//      		}
     		$pinfo=$products->getSimpleInfoByItemid($val['orig']);
     		if(empty($pinfo)){
     			unset($list[$key]);
     		}else{
     			$pinfo['num'] = $val['abst'];
     			$pinfo["info"]=$val['info'];
     			$list[$key] = $pinfo;
     		}
     	}
     	return $list;
     }
     
     
     /**
      * 获取晒盒列表
      * @param string $limit
      * @author litingitng
      */
     public function getShowBoxList($limit=6){
     	$list= $this->getArticleList(734,$limit);
     	$share = D("UserShare");
     	foreach($list as $key =>$val){
     		$shareinfo  = $share ->getShareInfo($val['orig'],40);
     		if($shareinfo){
     			if($val['img']){
     				list($shareinfo['img_w'],$shareinfo['img_h']) = getimagesize(ltrim($val['img'],"/"));
     				if($shareinfo['img_w']>200){
     					$shareinfo['img_h']=(int)($shareinfo['img_h']*(200/$shareinfo['img_w']));
     					$shareinfo['img_w']=200;
     				}
     				$shareinfo['img']=$val['img'];
     			}
     			$list[$key] = $shareinfo;
     		}else{
     			unset($list[$key]);
     		}
     	}
     	return $list;
     }
      
     
     
     /**
      * 获取全部专题列表
      * @param string $limit
      * @author litingting
      */
     public function getSpecialList($limit=9,$type=0){
     	if(empty($type)){
     		$cid=array("IN","649,650,742");
     	}else if($type==1){
     		$cid=649;
     	}else if($type==2){
     		$cid=650;
     	}else if($type==3){
     		$cid=742;
     	}
     	return $this->getArticleList($cid,$limit);
     }
     
     /**
      * 获取萝莉智慧团专题列表
      * @param string $limit
      * @author litingting
      */
     public function getWisdomSpecialList($limit=9){
     	return $this->getArticleList(649,$limit);
     }
     
     
     /**
      * 获取萝莉实验社专题列表
      * @param stirng $limit
      * @author litingting
      */
     public function getExperimentSpecialList($limit=9){
     	return $this->getArticleList(650,$limit);
     }
     
     
     /**
      * 获取专题总数
      * @param unknown_type $limit
      * @param int $type  类型
      * @author litingting
      */
     public function getSpecialCount($type=0){
     	if(empty($type)){
     		$cid=array("IN","649,650,742");
     	}else if($type==1){
     		$cid=649;
     	}else if($type==2){
     		$cid=650;
     	}else if($type==3){
     		$cid=742;
     	}
     	$where['cate_id'] = $cid;
     	$where['status'] = 1;
     	$count= $this->where($where)->count();
        return $count;
     }
     
     
     /**
      * 获取友情链接列表
      * @param string $limit
      * @author litingting
      */
     public function getFriendLinks(){
     	return $this->getArticleList(738);
     }
     
     
     /**
      * 获取萝莉试用推荐列表
      * @param string $limit
      * @author litingting
      */
     public function getLoliTryList($cid=0,$limit=50){
     	if(empty($cid)){
     	    $clist  = M("Category")->field("cid,cname")->where("ctype=12 AND cstatu=1")->select();
     	    foreach($clist as $key =>$val){
     	    	$clist[$key] = $val['cid'];
     	    }
     	    $cid = array("IN",$clist);
     	}
     	return $this->getArticleList($cid,$limit);
     }
     
     
     /**
      * 获取文章总数
      * @param string $cid
      * @author litingting
      */
     public function getLoliTryCount($cid=0){
        if(empty($cid)){
     	    $clist  = M("Category")->field("cid,cname")->where("ctype=12 AND cstatu=1")->select();
     	    foreach($clist as $key =>$val){
     	    	$clist[$key] = $val['cid'];
     	    }
     	    $cid = array("IN",$clist);
     	}
     	$where['cid'] = $cid;
     	$where['status'] = 1;
     	return $this->where($where)->count();
     }
     
     
     
     /**
      * 获取美妆库首页中的广告位品牌列表
      * @param unknown_type $limit
      * @param unknown_type $me
      */
     public function getRemmendAdBrandList($limit=""){
     	$list = $this ->getArticleList(C("BRAND_COMMEND_CATEID"),$limit);
     	$brand_mod = D("ProductsBrand");
     	foreach($list as $key =>$val){
     		$brandinfo = $brand_mod ->getBrandInfo($val['orig'],"id,name,logo_url,fans_num,if_super");
     		$list [$key] =$brandinfo;
     	}
     	return $list;
     }
     
     /**
      * 获取美妆库首页中的广告位品牌总数
      * @param unknown_type $limit
      * @param unknown_type $me
      */
     public function getRemmendAdBrandNum(){
     	$list=$this->getRemmendAdBrandList();
     	return count($list);
     }     
     
     
	/**
	 * 获取未审核通过的文章列表【用于后台】
	 * @param cid 文章分类ID[若为0，则表示取所有的文章列表]
	 * @param pageno 第几页（次）
	 * @param pagesize 一页（次）取几条数据 
	 * @author zhenghong
	 *
	 */
	public function getArticleListUnChecked($cid,$pageno=1,$pagesize=10){
		if($cid) $otherwhere["cate_id"]=$cid;
		$otherwhere["status"]=0;
		return $this->getArticleListCommon($pageno=1,$pagesize=10,$otherwhere);
	}
	
	
	/**
	 * 获取Article详细内容
	 * @param $id 文章ID
	 * @return array 返回文章详细内容
	 * @author zhenghong
	 */
	public function getArticleInfoById($id){
		if(empty($id) || !$id) {
			return null;
		}
		return $this->getById($id);
	}
	
	
	/**
	 * 通过品牌ID获取品牌资讯
	 * @param unknown_type $brandid
	 * @param unknown_type $limit
	 * @author litingting
	 */
	public function getBrandInfoList($brandid,$limit=""){
		$where ['status']  = 1;
		$where ['cate_id'] = 737;
		$where ['orig'] = $brandid;
		$list= $this->where($where)->order ( "ordid desc,id desc" )->limit($limit)->select();
		foreach($list as $key =>$val){
			$list[$key]['info'] = json_encode(array("data"=>$val['info']));
			$list[$key]['url'] = $val['url'] ? $val['url'] : U("brand/info",array('id'=>$val['id']));
		}
		return $list;
	}
	
	/**
	 * 通过品牌ID获取资讯数
	 * @param unknown_type $brandid
	 * @author litingting
	 */
	public function getBrandInfoNum($brandid){
		$where ['status']  = 1;
		$where ['cate_id'] = 737;
		$where ['orig'] = $brandid;
		$count= $this->where($where)->count();
		return $count;
	}
	
	
	/**
	 * 通过用户ID获取资讯列表
	 * @param unknown_type $userid
	 * @param unknown_type $limit
	 */
	public function getBrandInfoByUserid($userid,$brandid='',$limit=10){
		$sql="SELECT * From article WHERE cate_id=737 AND status=1 ";
		if($brandid){
			$sql .=" AND orig=".$brandid;
		}else{
			$sql .=" AND orig IN (SELECT whoid FROM follow WHERE userid={$userid} AND type=3 )";
		}
		$sql .=" ORDER BY ordid desc,id desc";
		if($limit){
			$sql.=" LIMIT ".$limit;
		}
		$list = $this->query($sql);
		$brand_mod =M("ProductsBrand");
		foreach($list as $key=>$val){
			$info = $brand_mod ->where("id=".$val['orig'])->field("logo_url,name,id as brandid")->find();
			if($info){
				$val = $info + $val;
			}
			import("ORG.Util.String");
			$val['info']=strip_tags($val['info']);
			$val['info']=String::msubstr($val['info'],0,90,'utf-8');
			$val['spaceurl'] = getBrandUrl($val['orig']);
			$val['url']=U("brand/info",array('id'=>$val['id']));
			$list[$key]  = $val;
		}
		return $list;
	}
	
	/**
	 * 通过用户ID或品牌获取资讯数
	 * @param string $userid
	 * @param string $brandid
	 * @param string $time 时间 格式为0000-00-00 00:00:00
	 * @author litingting
	 */
	public function getBrandInfoNumByUserid($userid,$brandid="",$time=0){
		$sql="SELECT count(id) as t From article WHERE cate_id=737 AND status=1 ";
		if($brandid){
			$sql .=" AND orig=".$brandid;
		}else{
			$sql .=" AND orig IN (SELECT whoid FROM follow WHERE userid={$userid} AND type=3 )";
		}
		if($time){
			$sql.=" AND add_time >='{$time}'";
		}
		//file_put_contents("1.txt", $sql);
        $res = $this->query($sql);	
        return $res[0]['t']	;
	}
	
	/**
	 * 通过cate_id获取后台管理的内容
	 * @author penglele
	 */
	public function getArticleInfoByCateid($cate_id,$field="title,img,url,id"){
		if(!$cate_id)
			return "";
		$info=$this->where("cate_id=$cate_id AND status=1")->order("id DESC")->field($field)->find();
		if($info && $info['img'] && $info['id']){
			$info['type']=$this->getCloseADType($info['img'],$info['id']);
		}
		return $info;
	}
	
	/**
	 * v5版页面广告
	 * @author penglele
	 */
	public function getADList($mod){
		$ad=array(
				"head"=>"",
				"foot"=>""
				);
		if($mod){
			$module_arr=array("buy","try","loli","brand");
			if(in_array($mod, $module_arr)){
				if($mod=="buy"){
					$ad['head']=$this->getArticleInfoByCateid("744");
					$ad['foot']=$this->getArticleInfoByCateid("745");
				}else if($mod=="try"){
					$ad['head']=$this->getArticleInfoByCateid("746");
					$ad['foot']=$this->getArticleInfoByCateid("747");
				}else if($mod=="loli"){
					$ad['head']=$this->getArticleInfoByCateid("748");
					$ad['foot']=$this->getArticleInfoByCateid("749");
				}else if($mod=="brand"){
					$ad['head']=$this->getArticleInfoByCateid("750");
					$ad['foot']=$this->getArticleInfoByCateid("751");
				}
			}
		}
		if($ad){
			$cookie_name="loli_closead";
			foreach($ad as $key=>$val){
				$cookie_info=$_COOKIE[$cookie_name];
				if($cookie_info){
					$cookie_arr=explode(",",$cookie_info);
					if(in_array($val['type'],$cookie_arr)){
						unset($ad[$key]);
					}
				}
			}
		}
		return $ad;
	}
	
	/**
	 * 关闭广告组合条件
	 * @author penglele
	 */
	public function getCloseADType($img,$id){
		if(!$img || !$id){
			return '';
		}
		$arr=explode("/",$img);
		$num=count($arr)-1;
		$pimg_info=$arr[$num];
		$pimg_arr=explode(".",$pimg_info);
		return $id."_".$pimg_arr[0];
	}
	
	/**
	 * 网站首页盒子专题推荐列表
	 * @author penglele
	 */
	public function getBoxArticleList(){
		$cate_id=752;
		$list=$this->field("title,url,img,info,orig")->where("cate_id=$cate_id AND status=1")->order("ordid DESC,id DESC")->limit(3)->select();
		if($list){
			$box_mod=M("Box");
			$boxmod=D("Box");
			foreach($list as $key=>$val){
				$boxinfo=$box_mod->field("box_price,member_price,boxid,name_modifier")->where("boxid=".$val['orig'])->find();
				$list[$key]['boxprice']=$boxinfo['member_price'] ? $boxinfo['member_price'] : $boxinfo['box_price'];
				$list[$key]['boxurl']=getBoxUrl($boxinfo['boxid']);
				$list[$key]['if_out']=0;
				$tag="";
				$if_out=$boxmod->checkBoxIfOut($boxinfo['boxid']);
				if($if_out==1){
					$tag="t_soldOut r_t";
					$list[$key]['if_out']=1;
				}else{
					switch($boxinfo['name_modifier']){
						case 1:
							//new
							$tag= "t_news r_t";
							break;
						case 2:
							///推荐
							$tag= "t_recommend r_t";
							break;
						case 3:
							//预售
							$tag= "t_sale r_t";
							break;
						case 4:
							//售罄
							$tag= "t_soldOut r_t";
							break;
					}					
				}
				$list[$key]['tag']=$tag;
			}
		}
		return $list;
	}
	
	/**
	 * 萝莉盒专题推荐右侧广告
	 * @author penglele
	 */
	public function getBoxArticleRightAD(){
		$cate_id=753;
		$info=$this->field("title,url,img")->where("cate_id=$cate_id AND status=1")->order("ordid DESC,id DESC")->find();
		return $info;
	}
	
	/**
	 * 初始化数据--通过盒子年月统计的盒子信息
	 * @author penglele
	 */
	public function getBoxlistInfoByDate(){
		$cate_id=763;
		$box_datelist=D("Box")->getBoxDateList();
		if(!$box_datelist){
			return false;
		}
		foreach($box_datelist as $key=>$val){
			$date=$val['sdate'];
			$data=array("title"=>$date,"abst"=>"","info"=>"");
			$box_mod=D("Box");
			$boxpro_mod=D("BoxProducts");
			$box_article=$this->where("cate_id=".$cate_id." AND title='".$date."' AND status=1")->find();
			//查看当前月份下的盒子列表信息是否存在
			if(!$box_article){
				//盒子列表
				$data['abst']=$box_mod->getBoxidListByDate($date);
				//用户已购买的产品列表
				$data['info']=$boxpro_mod->getProductIDByDate($date);
				
				$data['cate_id']=$cate_id;
				$data['status']=1;
				$rel=$this->add($data);
				if($rel==false){
					echo $date."<br />";
				}
			}
		}
		echo "end";
	}
	
	/**
	 * 通过文章 ID获取 信息
	 * @author penglele
	 */
	public function getBoxInfoByArticleId($id){
		$arr=array("boxid_arr"=>'',"pid_arr"=>"");
		if($id){
			$where['id']=$id;
		}
		$where['cate_id']=763;
		$where['status']=1;
		$boxid_arr=array();//盒子ID的数组
		$pid_arr=array();//产品ID的数组
		$list=$this->where($where)->select();
		if($list){
			foreach($list as $key=>$val){
				//获取盒子ID的集合
				if($val['abst']){
					$abst_arr=explode(",",$val['abst']);
					foreach($abst_arr as $ival){
						if(!in_array($ival,$boxid_arr)){
							$boxid_arr[]=$ival;
						}
					}
				}
				//获取产品ID的集合
				if($val['info']){
					$info_arr=explode(",",$val['info']);
					foreach($info_arr as $eval){
						if(!in_array($eval,$pid_arr)){
							$pid_arr[]=$eval;
						}
					}
				}				
			}
		}
		$boxid_str=implode(",",$boxid_arr);
		$pid_str=implode(",",$pid_arr);
		$arr['boxid_arr']=$boxid_str;
		$arr['pid_arr']=$pid_str;
		return $arr;
	}
	
	
	/**
	 * 获取每月新品信息
	 * @author penglele
	 */
	public function getNewProductsList(){
		$cate_id=764;
		$arr=array();
		$list=array();
		$info=$this->getArticleInfoByCateid($cate_id,"info");
		if(!$info){
			return $list;
		}
		$arr=explode(",",$info['info']);
		if(!$arr){
			return $list;
		}
		$pro_mod=D("Products");
		foreach($arr as $val){
			$proinfo=$pro_mod->getSimpleInfoByItemid($val);
			if($proinfo){
				$list[]=$proinfo;
			}
		}
		return $list;
	}
	
	/**
	 * 萝莉俱乐部-精彩分享的时间导航
	 * @author penglele
	 */
	public function getLoliShareTimeList(){
		$cate_id=763;
		$alist=$this->getArticleList($cate_id);
		$time1=array();
		$time2=array();
		if($alist){
			foreach($alist as $key=>$val){
				$arr['id']=$val['id'];
				$arr['year']=substr($val['title'],0,4);
				$arr['mon']=substr($val['title'],4,2);
				if($key<=8){
					$time1[]=$arr;
				}else{
					$time2[]=$arr;
				}
			}
		}
		$list=array(1=>$time1,2=>$time2);
		return $list;
	}
	
	/**
	 * 萝莉俱乐部-重磅策划--时间列表
	 * @author penglele
	 */
	public function getSpecialTimeList(){
		$sql="SELECT abst FROM `article` WHERE cate_id=649 OR cate_id=650 OR cate_id=742 AND abst!='' GROUP BY abst DESC";
		$query=$this->query($sql);
		$time1=array();
		$time2=array();
		if($query){
			foreach($query as $key=>$val){
				$arr['year']=substr($val['abst'],0,4);
				$arr['mon']=substr($val['abst'],4,2);
				$arr['id']=$val['abst'];
				if($key<=8){
					$time1[]=$arr;
				}else{
					$time2[]=$arr;
				}
			}
		}
		$list=array(1=>$time1,2=>$time2);
		return $list;
	}
	
	/**
	 * 萝莉俱乐部-重磅策划--专题列表
	 * @author penglele
	 */
	public function getSpecialListByDate($aid,$limit=""){
		if($aid){
			$where['abst']=$aid;
		}
		$where['cate_id']=array("IN","649,650,742");
		$where['status']=1;
		$list=$this->where($where)->order("ordid DESC,id DESC")->limit($limit)->select();
		return $list;
	}
	
	/**
	 * 萝莉俱乐部-重磅策划--专题总数
	 * @author penglele
	 */
	public function getSpecialCountByDate($aid){
		if($aid){
			$where['abst']=$aid;
		}
		$where['cate_id']=array("IN","649,650,742");
		$where['status']=1;
		$list=$this->where($where)->count();
		return $list;
	}	
	
	/**
	 * 首页-聚划算列表
	 * @author penglele
	 */
	public function getCheapList(){
		$list=$this->getArticleList(768,3);
		return $list;
	}
	
	/**
	 * 首页-最长草推荐位
	 * @author penglele
	 */
	public function getZhangCao(){
		$info=$this->where("cate_id=769 AND status=1")->order("ordid DESC,id DESC")->find();
		return $info;
	}
	
	/**
	 * 个人中心首页推荐
	 * @author penglele
	 */
	public function getHomeRecommendList(){
		$list=$this->getArticleList(771,3);
		return $list;
	}
	
	/**
	 * 获取首页底部特别关注
	 * @author penglele
	 */
	public function getBottomIntest($limit=""){
		$list=$this->getArticleList(774,$limit);
		return $list;
	}
	
	
	/**
	 * 获取往期贪吃盒分月数据
	 * @param n 取多少条月份记录
	 * @return datalist
	 */
  	public function getTchHistoryData($n=12){
  		$root_cid=789;
  		$category_mod=M("Category");
  		$where["pcid"]=$root_cid;
  		$where["cstatu"]=0; //分类状态属于“非隐藏”状态
  		$category_list=$category_mod->where($where)->order("sortid DESC,cname DESC")->limit(0,$n)->select();
  		$return_data=array();
  		for($i=0;$i<count($category_list);$i++){
  			$return_data[$i]["title"]=$category_list[$i]["cname"];
  			$return_data[$i]["imglist"]=$this->where("cate_id=".$category_list[$i]["cid"]." AND status=1")->order("ordid DESC")->select();
  		}
  		return $return_data;
  		
  	}
}