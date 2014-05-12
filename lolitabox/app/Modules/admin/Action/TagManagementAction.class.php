<?php
class  TagManagementAction extends  Action{

	/**
       +----------------------------------------------------------
       * 分词库,分词管理,增删改查
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  type $_GET  $_POST ajax   		
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.7
       */	
	function participleLibrary(){
		import("@.ORG.Page");
		$tag_mod = M("tagIndex");

		//删除记录
		if($this->_get('dele')){
			$result = $tag_mod->delete($this->_get('dele'));
			if($result){
				$this->success("tagid:{$this->_get('dele')},删除成功:");
			}else{
				$this->error("删除失败!");
			}
			exit();
		}

		//修改状态
		if($this->_post('changestatus')){
			$result = $tag_mod->where(array('tagid'=>$this->_post('tagid')))->setField('status',$this->_post('status'));
			
			if($result){
				$this->ajaxReturn(1,'操作成功!',1);
			}else{
				$this->ajaxReturn(0,'操作失败!',0);
			}
			exit();
		}
		//新增的时候tid为空
		if($this->_post('ttype')){
			$data = array(
			'tagid'=>$this->_post('tid'),
			'tagname'=>$this->_post('tname'),
			'tagcategory'=>$this->_post('ttype'),
			'sid'=>$this->_post('reid'),
			'status'=>$this->_post('status')
			);

			$result = $tag_mod->add($data,$options=array(),$replace=true);

			if($result){
				$this->ajaxReturn($this->_post('status'),'操作成功!',1);
			}else{
				$this->ajaxReturn(0,'操作失败!',0);
			}
			exit();
		}

		if($this->_get('sub')){
			$where = $this->participleLibraryWhere(array_map('filterVar',$_GET));
		}

		if($this->_get("order")){
			$order=$this->_get("order");
		}

		$count=$tag_mod->where($where)->count();
		$p = new Page($count,15);
		$list=$tag_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
		
		$page = $p->show();

		$typeArray= array('11'=>'productsBrand','12'=>'category','13'=>'products','14'=>'category');

		foreach ($list AS $key => $value){
			$mod = $typeArray["{$value['tagcategory']}"];
			$info = array();

			if($value['tagcategory'] == '11'){

				$info = M("$mod")->field('name')->getById($value['sid']);
				$list["{$key}"]['relevance']=$info['name'];
				$list["{$key}"]['tagmsg']='产品品牌';

			}else if($value['tagcategory'] == '12' || $value['tagcategory'] == '14'){

				$map['ctype'] = $value['tagcategory'] == '12'?1:2;
				$info = M("$mod")->where($map)->field('cname')->getByCid($value['sid']);
				$list["{$key}"]['relevance']=$info['cname'];
				$list["{$key}"]['tagmsg']= $value['tagcategory'] == '12'?'产品分类':'产品功效';

			}else if($value['tagcategory'] == '13'){

				$info = M("$mod")->field('pname')->getByPid($value['sid']);
				$list["{$key}"]['relevance']=$info['pname'];
				$list["{$key}"]['tagmsg']='产品名称';
			}else{
				$list[$key]['tagmsg']='其他';
			}
		}

		$this->assign("page",$page);
		$this->assign("taglist",$list);
		$this->display();
	}

	/**
       +----------------------------------------------------------
       * 提交participleLibrary 查询条件
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  Array    arguments   		页面传递的GET查询参数
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.3.7
       */
	private function participleLibraryWhere($arguments){
		$where = array();
		if($arguments['tagname']){
			$where['tagname'] = array('LIKE',"%{$arguments['tagname']}%");
		}

		if($arguments['tstatus']){
			$where['status'] = 1;
		}else if($arguments['tstatus']==='0'){
			$where['status'] = 0;
		}

		if($arguments['tagtype']){
			$where['tagcategory'] = $arguments['tagtype'];
		}
		return $where;
	}


	/**
	 +----------------------------------------------------------
	 * 某tagid所对应的日志和评测列表
	 +----------------------------------------------------------
	 * @access private
	 +----------------------------------------------------------
	 * @param  int  tagid
	 +-----------------------------------------------------------
	 * @author litingting 2013.3.15
	 */
	public function tagRelationList(){
		if($tagid=$_REQUEST['tagid'])
		{
			$tag_rel_mod=M("TagRelation");
			$blog_mod=M("UserBlogIndex");
			$evaluate_mod=M("ProductsEvaluate");
			$tagname=M("TagIndex")->where("tagid=".$tagid)->getField("tagname");
			$count=$tag_rel_mod->where("tagid=".$tagid)->count();
			import("@.ORG.Page");
			$p = new Page($count,15);
			$list=$tag_rel_mod->where("tagid=".$tagid)->order("createtime desc")->limit($p->firstRow . ',' . $p->listRows)->select();
			$page = $p->show();
			$total=count($list);
			for($i=0;$i<$total;$i++)
			{
				if($list[$i]['relationtype']==1)
				$info=$blog_mod->where("id=".$list[$i]['relationid'])->find();
				else if($list[$i]['relationtype']==2)
				$info=$evaluate_mod->where("evaluateid=".$list[$i]['relationid'])->find();
				$list[$i]['title']=$info['title'];
			}
			$this->assign("tagname",$tagname);
			$this->assign("list",$list);
			$this->assign("page",$page);
			$this->display();
		}else {
			$this->error("没有参数");
		}
	}
}
?>