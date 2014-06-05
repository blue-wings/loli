<?php
class  SystemSetAction extends  CommonAction{



	/**
       +----------------------------------------------------------
       * areaManage  				  省市区列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string second   	  二级列表
       +-----------------------------------------------------------
       * @author zhaoxiang
       */	

	function areaManage(){
		$area_mod=M("area");

		$original_list=$area_mod->where(array('pid'=>0,status=>1))->
		field("area_id,title,pid,concat(pid,'-',area_id) as path")->order('area_id ASC')->select();

		foreach ($original_list as $key => $value){
			$second_list = array();
			$list[$key]['first']=$value;
			$second_list=$area_mod->where(array('pid'=>$value['area_id'],'status'=>1))->
			field("area_id,title,pid,concat('".$value['path']."','-',area_id) as spath")->order('spath')->select();

			foreach ($second_list as $k => $val){
				$list[$key]['second'][$k]['superior']= $val;

				$list[$key]['second'][$k]['subordinate'] = $area_mod->where(array('pid'=>$val['area_id'],'status'=>1))->
				field("area_id,title,pid,concat('".$val['spath']."','-',area_id) as tpath")->order('tpath')->select();
			}
		}

		$this->assign('alist',$list);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * areaManipulate  省市区的操作
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string del_area   	  逻辑删除
       * @param  string add_area      增加地区(省,市,区)
       * @param  string edit_area     修改地区名称
       +-----------------------------------------------------------
       * @author zhaoxiang
       */		
	function areaManipulate(){
		$area_mod=M("area");
		if($this->_post('action') == 'del_area'){
			$where['area_id']=$this->_post("areaid");
			$result=$area_mod->where($where)->setField('status','0');
			$this->ajaxReturn('','',$result);
		}else if($this->_post('action') == 'add_area' || $this->_post("action") == 'addProvince'){
			$data['title']=$this->_post("newname");
			$data['pid']=$this->_post("oldname");
			$result=$area_mod->add($data);
			if($result){
				$this->success("添加成功!");
			}
		}else if($this->_post("action") == 'edit_area'){
			$where['area_id']=$this->_post("oldname");
			$result=$area_mod->where($where)->setField('title',trim($this->_post("newname")));
			if($result){
				$this->success("修改成功");
			}else{
				$this->error("修改失败");
			}
		}
	}

	/**
       +----------------------------------------------------------
       * 省市区列表导出JSON格式文件
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  string exportType   	导出文件类型
       +-----------------------------------------------------------
       * @author zhaoxiang
       */			
	public function createAreaJsonFile(){
		if(filterVar($_GET['exportType'])=='JS'){
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="city.lolitabox.js"');
			$area_mod=M("area");
			$array_json=array();
			$province_list=$area_mod->cache(true)->where("pid=0 AND status=1")->order("area_id ASC")->select();
			for($i=0;$i<count($province_list);$i++){
				$array_json["citylist"][$i]["p"]=$province_list[$i]["title"];
				$city_list=$area_mod->cache(true)->where("pid=".$province_list[$i]["area_id"]." AND status=1")->order("area_id ASC")->select();
				for($m=0;$m<count($city_list);$m++) {
					$array_json["citylist"][$i]["c"][$m]["n"]=$city_list[$m]["title"];
					$district_list=$area_mod->cache(true)->where("pid=".$city_list[$m]["area_id"]." AND status=1")->order("area_id ASC")->select();
					for($k=0;$k<count($district_list);$k++) {
						$array_json["citylist"][$i]["c"][$m]["a"][$k]["s"]=$district_list[$k]["title"];
					}
				}
			}
			echo(json_encode($array_json));
		}
	}

	/**
       +----------------------------------------------------------
       * 敏感词列表
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  mixed $_REQUEST  查询条件
       +-----------------------------------------------------------
       * @author litingting
       */
	public function filterwordList(){
		
		import("@.ORG.Page");
		$sensitive_words_mod=M("Filterword");
		
		$where = $this->filterListWhere(array_map('filterVar',$_GET));
		
		$count = $sensitive_words_mod->where($where)->count();
		
		$p = new Page($count,15);
		
		$list=$sensitive_words_mod->where($where)->order("id desc")->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$page = $p->show();
		
		$this->assign("page",$page);
		$this->assign("list",$list);
		$this->display();
	}


	/**
       +----------------------------------------------------------
       * 敏感词列表查询条件
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  Array arguments $_GET参数
       +-----------------------------------------------------------
       * @author zhaoxiang
       */
	private function filterListWhere($arguments){

		if($arguments['words']){
			$where['words']=array('like','%'.$arguments['words']."%");
		}

		if($arguments['status'] || $arguments['status'] === '0'){
			$where['status']=$arguments['status'];
		}

		return $where;
	}


	/**
       +----------------------------------------------------------
       * 敏感词操作(编辑，删除，添加)
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  mixed $_REQUEST
       +-----------------------------------------------------------
       * @author litingting
       */
	public function filterwordOperating(){
		$ac=$_REQUEST['ac'];
		$sensitive_words_mod=M("Filterword");
		if($ac=='add'){
			if($_GET['submit'])
			{
				if($sensitive_words_mod->add($_GET))
				$this->success("操作成功");
				else
				echo $sensitive_words_mod->getLastSql();die;
				$this->error("操作失败");

			}
			$this->display("add");

		}elseif($ac=='edit'){
			$id=$_GET['id'];
			if(!$id)
			$this->error("缺少参数");
			if($_GET['submit'])
			{

				if(false!==$sensitive_words_mod->where("id=".$id)->save($_GET))
				$this->success("操作成功");
				else
				$this->error("操作失败");
				die;
			}
			$info=$sensitive_words_mod->getById($id);
			$this->assign("info",$info);
			$this->display("add");

		}elseif($ac=='del'){

			$id=$_GET['id'];
			$status=$_GET['status'];
			if(empty($id) || !isset($status))
			$this->ajaxReturn(0,"缺少参数",0);
			$change=abs($status-1);
			if($sensitive_words_mod->where("id=".$id)->save(array('status' =>$change))){
				$this->ajaxReturn($change,"操作成功",1);
			}else{
				$this->ajaxReturn($change,"操作失败",0);
			}
		}
	}

	/**
	 * 生成词库文件
	 * @author ltiingting
	 */
	public function createFilterwordFile(){
		mb_internal_encoding( 'UTF-8');
		$mod=M("filterword");
		$list=$mod->field("words")->select();
		$count=count($list);
		$rec=array();
		for($i=0;$i<$count;$i++){
			$word=$list[$i]['words'];
			$start = mb_substr($word,0,1);
			$len= mb_strlen($word);
			if($len > 1)
			{
				$second = mb_substr($word,1,1);
				if($len >2)
				$last = mb_substr($word,2);
				else
				$last=1;
				$rec [$start][$second][]  =  $last;

			}else{
				if(!isset($rec [$start]))
				$rec [$start] =1;
			}

		}
		$string = "<?php return ".var_export($rec,true).";";
		file_put_contents("./data/filter.words.php",$string);
		$this->ajaxReturn(1,"操作成功",1);

	}

	/**
	 * 测试敏感词搜索
	 */
	public function test_filterwords(){
		if ($_POST ['content']) {
			$filter_words = require_once ('./data/filter.words.php');
			$string = strip_tags($_POST ['content']);
			mb_internal_encoding ( 'UTF-8' );
			$count = mb_strlen ( $string );
			$i = 0;
			$flag = 0;
			while ( $i < $count ) {
				$child = mb_substr ( $string, $i, 1 );
				$second = mb_substr ( $string, $i + 1, 1 );
				if (isset ( $filter_words [$child] [$second] )) {
					if (is_array ( $filter_words [$child] [$second] )) {
						foreach ( $filter_words [$child] [$second] as $key => $val ) {
							if ($val == 1) {
								echo "敏感词:".$child . $second ."<br>";
								$flag = 1;
								break;
							}
							$len = mb_strlen ( $val );
							$str = mb_substr ( $string, $i + 2, $len );
							if ($str == $val) {
								echo "敏感词:".$child . $second . $str."<br>";
								$flag = 1;
								break;
							}

						}
					}
				}
				if ($flag == 1)
				break;
				$i ++;
			}
			if($flag==0)
			echo "没有敏感词<br><br>";

		}
		echo "<BR><BR><form action='' method='POST'>
		<textarea name='content' cols='40' rows='4'>".$_POST['content']."</textarea>
		<input type='submit' name='submit'  value='查询'></form>";
		die ();
	}


	/**
	 +----------------------------------------------------------
	 * 首页预览
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author litingting
	 */
	public function previewIndex(){
		header("location:/index.php/Index/index/do/preview");
	}


	/**
	  +----------------------------------------------------------
	  * 首页生成
	  +----------------------------------------------------------
	  * @access public
	  +----------------------------------------------------------
	  * @author litingting
	  */
	public function updateIndex(){
		header("location:/index.php/Index/index/do/create");
	}
}
?>