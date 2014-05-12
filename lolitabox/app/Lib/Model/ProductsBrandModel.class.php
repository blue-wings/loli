<?php
/**
 * 品牌模型类
 */
class ProductsBrandModel extends Model{
	
	/**
	 * 获取品牌列表
	 * @param mixed $where
	 * @param string $p
	 */
	public function getBrandList($where=array(),$p="",$me="",$order="firstchar ASC",$field=""){
		$where ['status'] = 1;
		$list=$this->where($where)->limit($p)->order($order)->field($field)->select();
		$follow_mod =M("Follow");
		foreach ($list as  $key => $val){
			if($val['if_super']==1){
				$list[$key]['if_super']=2;
			}else{
				$list[$key]['if_super']=0;
			}
			$list [$key]['brandurl'] = getBrandUrl($val['id']);
			if($me){
				$type = $follow_mod->where("userid=".$me." AND whoid=".$val['id']." AND type=3")->find();
				$list[$key]['type'] = $type ? 1:0;
			}
		}
		return $list;
	}
	
    /**
     * 通过品牌首字母及地区获取品牌总数
     * @param unknown_type $where
     */
	public function getBrandCount($area="",$firstchar=""){
	   if($area)
	   	   $where ['area'] =$area;
	   if($firstchar)
	   	   $where['_string']="firstchar='".$firstchar."' OR foreign_firstchar='".$firstchar."'";
	   $where ['status'] =1;
	   $count = $this ->where($where) ->count("id");
	   return $count;
	}
	
	/**
	 * 获取推荐品牌列表
	 * @param unknown_type $limit
	 * @param unknown_type $me
	 */
	public function getRemmendBrandList($limit="",$me){
		$where['iscommend'] = array("gt",0);
		return $this ->getBrandList($where,$limit,$me,"iscommend DESC,id DESC");
	}
	
	/**
	 * 通过首字母获取品牌列表
	 * @param string $firstchar
	 * @param mixed $p
	 */
	public function getBrandListByFirstchar($firstchar,$area,$p,$me="",$order){
		if($firstchar)
     		$where['_string']="firstchar='".$firstchar."' OR foreign_firstchar='".$firstchar."'";
		if($area)
			$where['area'] = $area;
		return $this->getBrandList($where, $p,$me,$order);
	}
	
	/**
	 * 通过tag获取品牌列表
	 * @param string $tag
	 * @param mixed $p
	 */
	public function getBrandListByTag($tag,$p="",$me="",$order=""){
		$xs = new XunSouModel("brand");
		$list = $xs ->search($tag,$order,$p);
		$follow_mod = M("Follow");
		foreach($list as $key =>$val){
			$list[$key]['brandurl'] = getBrandUrl($val['id']);
			$list[$key]['if_super']= $val['if_super']?2:0;
			$list[$key]['name'] = str_replace("'","\\'",$val['name']);
			if($me){
				$type = $follow_mod->where("userid=".$me." AND whoid=".$val['id']." AND type=3")->find();
				$list[$key]['type'] = $type ? 1:0;
			}
		}
		return $list;
	}
	
	/**
	 * 获取感兴趣的品牌列表
	 * @param string $limit
	 * @param string $field
	 */
    public function getInterestBrandList($limit,$me=''){
    	$order = "fans_num,share_num DESC";
    	return $this ->getBrandList(array(),$limit,$me,$order,'id,name,logo_url,fans_num');
    }
	
	/**
	 * 通过tag获取品牌总数
	 * @param string $tag
	 */
	public function getBrandCountByTag($tag){
		$xs = new XunSouModel("brand");
		return $xs->count($tag);
	}
	
	/**
	 * 通过brandid获取品牌详情
	 * @param int $brandid
	 */
	public function getBrandInfo($brandid,$field="",$me="",$flag=1){
		if($flag==1)
	    	$where ['status'] =1;
		$info = $this->where($where)->field($field)->getById($brandid);
		if($info){
			if($info['if_super']==1){
				$info['if_super'] = 2;
			}
			$info['description_br']=nl2br($info['description']);//文本内容换行
			
			$info['description_all'] = str_replace("'", "\\'", $info['description']);
			$info['description_all'] = str_replace('"', '\\"', $info['description']);
			switch ($info['area']){
				case 1:
					$info['category'] = "欧美品牌";
					break;
				case 2:
					$info['category'] = "日韩品牌";
					break;
				case 3:
					$info['category'] = "国产品牌";
					break;
				default:
					$info ['category'] ="其他品牌";
			}
			if($me){
				$type = M("Follow")->where("userid=".$me." AND whoid=".$info['id']." AND type=3")->find();
				$info['type'] = $type ? 1:0;
			}
			$info['brandurl'] = getBrandUrl($info['id']);
		}
		return $info;
	}
	
	/**
	 * 获取首字母列表
	 */
	public function getBrandFirstchar($area=""){
		$where ="";
		if($area){
			$where .="area=".$area." AND ";
		}
		$where .= "status = 1";
		$list = $this ->where($where)->field("distinct firstchar as firstchar")->order("firstchar")->select();
		$foreign_list = $this->where($where) ->field("distinct foreign_firstchar as firstchar")->order("foreign_firstchar")->select();
		$list = $list ? $list:array();
		$foreign_list = $foreign_list ? $foreign_list:array();
		$total = array_merge($list,$foreign_list);
		foreach ($total as $key =>$val){
			if($val['firstchar'] != "")
		    	$return [] = $val['firstchar'];
		}
		$return = array_unique($return);
		 sort($return);
		return $return;
	}
	
	/**
	 * 查找品牌[用户注册不能与表同名]
	 * @param unknown_type $name
	 * @return null|mixed
	 * @author zhaoxiang
	 */
	function searchName($name){
		return $this->where(array('name'=>$name))->find();
	}
	
    /**
     * 删除brand
     * @param unknown_type $id
     */
	public function delBrand($id){
		if($this->where("id=".$id)->delete()){
			M("Follow")->where("whoid=".$id." AND type=3")->delete();
			return true;
		}else{
			return false;
		}
	}
	
	
	/**
	 * 通过品牌ID获取明星产品列表
	 * @param int $brandid
	 * @access public
	 * @uses 用于品牌页
	 * @author litingting
	 */
	public function getStarProductsListByBrandid($brandid){
		$pro_list = $this->where("id=".$brandid)->getField("product_list");
		$where['pid'] = array("IN",$pro_list);
		$where['status'] = 1;
		return M("Products") ->where($where)->field("pid,pname,pimg,evaluate_num")->select();
	}
	
	
	/**
	 * 通过品牌ID获取同类品牌
	 * @param unknown_type $brandid
	 * @access public 
	 * @author litingting
	 */
	public function getSameBrandList($brandid,$limit=4){
		$where['area'] = $this->where("id=".$brandid)->getField("area");
		$where['status'] = 1;
		return $this->field("id,name,name_foreign,logo_url,area,product_num")->where($where)->order("rand()")->limit($limit)->select();
	}
	
	/**
	 * 通过产品ID获取品牌微博账号
	 * @author penglele
	 */
	public function getBrandWeiboAccountByPid($pid){
		$weibo_account="";
		if(!$pid){
			return $weibo_account;
		}
		$pro_info=M("Products")->where("pid=".$pid)->getField("brandcid");
		if(!$pro_info){
			return $weibo_account;
		}
		$brand_info=$this->where("id=".$pro_info)->find();
		if(!$brand_info || !$brand_info['weibo_account']){
			return $weibo_account;
		}
		return $brand_info['weibo_account'];
	}
	
	/**
	 * 通过产品ID 获取品牌信息
	 * @param unknown_type $pid
	 * @param unknown_type $field
	 * @author penglele
	 */
	public function getBrandInfoByPid($pid,$field="*"){
		if(!$pid){
			return false;
		}
		//产品信息
		$cid=M("Products")->where("pid=".$pid)->getField("brandcid");
		if(!$cid){
			return false;
		}
		//品牌信息
		$brand_info=$this->field($field)->where("id=".$cid)->find();
		return $brand_info;
	}
	

}