<?php

require_once '/usr/local/xunsearch/sdk/php/lib/XS.php';
class XunSouModel extends Model{
	
	var $xs = null;
	
	public function __construct($name){
		
		$this->xs = new XS($name);
	}
	
	/**
	 * 搜索
	 */
	public function search($query,$order,$limit="10",$where=array(),$all=false){
		$search = $this->xs->search;
		if($query){
			$res = $this->createquery($where);
			if($res){
				$query = $res.$query; 
			}
			$search->setQuery($query); // 设置搜索语句
		}
		if($order){
			$search->setSort($order);
		}
		$limits = explode(",",$limit); 
		if(count($limits)>1){
			if($limits[0]){
				$search ->setLimit($limits[1],$limits[0]);
			}else{
				$search ->setLimit($limits[1]);
			}
		    	
		}else{
			$search->setLimit($limits[0]);
		}
	
	    $docs = $search->search();
	    $list = array();
	    if($all===false){
	    	foreach($docs as $key =>$doc){
	    		$array = array();
	    		foreach($doc as $k =>$v){
	    			$array[$k]= $v;
	    		}
	    		$list[] = $array;
	    	}
	    }else{
	    	$list = $docs;
	    }
	    return $list;
	}
	
	
	/**
	 * 总数
	 * @param unknown_type $query
	 * @param array $addweight
	 */
	public function count($query,$where=array(),$addweight=null){
		$search = $this->xs->search;
		$res = $this->createquery($where);
		if($res){
			$query = $res.$query;
		}
		$search->setQuery($query);
		if($addweight){
			foreach($addweight as $key =>$val){
				$search ->addWeight($key,$val);
			}
		}
		return $search->count();
	}
	
	
	/**
	 * 热词搜索
	 * @param unknown_type $query
	 * @param unknown_type $limit
	 */
	public function hot($limit=10,$query=''){
		$search = $this->xs->search;
		$words = $search->getHotQuery($limit, $query);
		return $words;
	}
	
	
	/**
	 * 分词
	 * @param unknown_type $string
	 * @param unknown_type $limit
	 */
	public function scws($string,$limit,$all=false){
		$tokenizer = new XSTokenizerScws;
		$tops = $tokenizer->getTops($string, 10, 'n,v,vn');
		if($all===false){
			$words = array();
			foreach($tops as $key =>$val){
				$words[] = $val['word'];
			}
		}else{
			$words = $tops;
		}
		return $words;
	
	}
	
	/**
	 * 构建搜索语句
	 * $where array 只支持等于$where['id'] = 123;
	 */
	private function createquery($where){
		$res = "";
		foreach($where as $key=>$val){
			$res .="(".$key.":".$val.") ";
		}
		return $res;
		
	}
}

?>