<?php
/**
 * 网站专题模型
 @example:
 $special_mod=D("Special");
 $special_mod->getSpecialList();
 
 * @author zhenghong
 */
class SpecialModel extends Model {

	/**
	 * 获取专题某个分类下的专题列表[cate_id=0]时返回所有专题
	 * @param cate_id 专题分类ID
	 * @param pageno
	 * @param pagesize
	 * @author zhenghong
	 */
	public function getSpecialList($cate_id,$pageno=1,$pagesize=10){
		$where["status"]=1;
		if(!$cate_id || empty($cate_id)) {
			//获取所有子分类ID
			$catelist=$this->getSpecialCateList();
			$category_array=array();
			foreach ($catelist as $cate) {
				$category_array[]=$cate["cid"];
			}
			$where["cate_id"]=array('in',$category_array);
		}
		else {
			$where["cate_id"]=$cate_id;
		}
		if($pageno>0 && $pagesize>0) {
			$offset=($pageno-1)*$pagesize;
		}
		else {
			$offset=0;
		}
		$article_m=M("Article");
		return $article_m->where($where)->limit($offset,$pagesize)->select();
		
	}
	
	/**
	 * 获取专题分类列表
	 * @return array
	 * @author zhenghong
	 */
	public function getSpecialCateList(){
		$root_id=C('INDEX_SPECIAL_CATEID');
		$category_m=M("Category");
		return $category_m->field("cid,cname")->where("pcid=$root_id")->select();
	}

}