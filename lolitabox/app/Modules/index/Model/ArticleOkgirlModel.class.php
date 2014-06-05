<?php

/**
 +------------------------------------------------------------------------------
 * okgirl活动数据模型
 +------------------------------------------------------------------------------
 * @author    penglele
 +------------------------------------------------------------------------------
 */
 class ArticleOkgirlModel extends Model {

 	/**
 	 * okgirl 投票列表
 	 * @author penglele
 	 */
 	public function getOKgirlList(){
 		$cateid_arr=array(
 				'1'=>array('id'=>757,'title'=>'北京'),
 				'2'=>array('id'=>759,'title'=>'武汉'),
 				'3'=>array('id'=>758,'title'=>'上海'),
 				'4'=>array('id'=>760,'title'=>'成都')
 		);
 		$list=array();
 		$article=M("Article");
 		foreach($cateid_arr as $key=>$val){
 			$info=$article->where("cate_id=".$val['id']." AND status=1")->order("ordid DESC")->select();
 			if($info){
 				foreach($info as $ikey=>$ival){
 					$info[$ikey]['info']=(int)$ival['info']+(int)$ival['abst'];
 				}
 			}
 			$list[$key]['title']=$val['title'];
 			$list[$key]['info']=$info;
 		}
 		return $list;
 	}
 	
 	/**
 	 * okgirl活动--走进校园
 	 * @author penglele
 	 */
 	public function getOkgirlToSchoolList(){
 		$cate_id=761;
 		$article=M("Article");
 		$c_mod=M("Category");
 		$type=0;
 		$return=array(
 				'type'=>$type,
 				'list'=>''
 				);
 		$type_list=$c_mod->where("pcid=".$cate_id)->order("sortid DESC,cid DESC")->select();
 		//先从大类下面找小分类
 		if(!$type_list){
 			return $return;
 		}
 		$list=array();
 		foreach($type_list as $key=>$val){
 			$info=array();
 			$info['title']=$val['cname'];
 			$info['alist']=$article->where("cate_id=".$val['cid']." AND status=1")->order("ordid DESC,id DESC")->select();
 			$list[]=$info;
 			if($info['alist']){
 				$type++;
 			}
 		}
 		$return['type']=$type;
 		$return['list']=$list;
 		return $return;
 	}
 	
 	
 	
 	
 }
 ?>