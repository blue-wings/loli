<?php
// 本类由系统自动生成，仅供测试用途
class CategoryAction extends CommonAction {

	public function index(){

	}


	public function add(){
		$category=M("Category");
		$ctype=$_REQUEST["ctype"];

		if(empty($ctype)) {
			$this->error();
		}else{
			$list = $this->publicCategoryList($ctype,$this->_get('type'));
		}
				
		$firstcidlist=$category->field("cid,cname,pcid,sortid")->order("cname ASC")->where("pcid=0 AND ctype=1")->select();
		$secondcidlist=$category->field("cid,cname,pcid,sortid")->order("pcid ASC")->where("pcid>0 AND ctype=1")->select();
		$this->assign('pcid',$_REQUEST["lastpcid"]); //根据上次提交记录的父ID，定位表单分类列表中的可能父级ID
		$this->assign('clist',$list);
		$this->assign('firstcidlist',$firstcidlist);
		$this->assign('secondcidlist',$secondcidlist);
		$this->display();
	}


	private function publicCategoryList($ctype,$type){

		$category=M("Category");
		
		if($ctype==5) {
			$ctype=1;
			$where['_string'] = "ctype=1 OR ctype=5";
		}else {
			$where['ctype'] = $ctype;
		}

		if($type == 'all'){
			$list=$category
			->field("cid,cname,pcid,ctype,sortid,cstatu,concat(cpath,'-',cid) as bpath")->where($where)
			->order("bpath,cid")
			->select();
		}else{
			$where['cstatu']=0;
			$list=$category
			->field("cid,cname,pcid,ctype,sortid,cstatu,concat(cpath,'-',cid) as bpath")
			->order("bpath,cid")
			->where($where)->select();
		}

		foreach($list as $key=>$value){
			$list[$key]['signnum']= count(explode('-',$value['bpath']))-1;
			$list[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
		}
		return $list;
	}


	/**
	 * 添加分类操作
	 */
	public function addcategory(){
		//print_r($_REQUEST);exit;
		$addcategory=new CategoryModel();
		if($data=$addcategory->create()){
			if(false!==$addcategory->add()){
				$this->assign('jumpUrl',__URL__.'/add/ctype/'.$_REQUEST["ctype"].'/lastpcid/'.$data['pcid']);
				$this->success('操作成功，插入数据编号为：'.$addcategory->getLastInsID());
			}
			else{
				$this->error('操作失败：addcategory'.$addcategory->getDbError());
			}
		}
		else {
			$this->error('操作失败：数据验证( '.$addcategory->getError().' )');
		}
	}

	/**
	 * 删除分类操作
	 */
	public function delcategory(){
		$cid=$_REQUEST["cid"];
		$ctype=$_REQUEST["ctype"];
		if(!empty($ctype) && !empty($cid)){
			$category=new CategoryModel();
			if(false!==$category->delete($cid)){
				$this->success('操作成功');
			}else{
				$this->error('操作失败：'.$category->getDbError());
			}
		}else{
			$this->error('错误请求');
		}
	}

	/**
	 * 修改分类
	 */
	public function edit(){
		$cid=$_REQUEST["cid"];
		$ctype=$_REQUEST["ctype"];
		$category=M("Category");
		if($ctype==5) {
			$ctype=1;
			$where="ctype=1 OR ctype=5";
		}
		else {
			$where="ctype=".$ctype;
		}
		$list=$category
		->field("cid,cname,pcid,ctype,concat(cpath,'-',cid) as bpath")
		->order("bpath,cid")
		->where($where)->select();
		foreach($list as $key=>$value){
			$list[$key]['signnum']= count(explode('-',$value['bpath']))-1;
			$list[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
		}
		$this->assign('clist',$list);
		$categoryinfo=$category->getByCid($cid);
		if(false!==$categoryinfo){
			$this->assign('categoryinfo',$categoryinfo);
		}
		else {
			$this->error('信息不存在');
		}
		$this->display();
	}

	/*切换显示状态*/
	public  function editstatus(){
		if($_GET['cid']){
			$cid=$_GET['cid'];
			$category_mod=M('category');
			$status=$category_mod->where(array('cid'=>$cid))->getField('cstatu');
			$data['cid']=$cid;
			$data['cstatu']=(int)!$status;
			$res=$category_mod->save($data);
			if($res){
				$this->success('修改成功!');
			}
		}
	}




	/**
	  * 执行修改操作
	  */
	public function editcategory(){
		$category=new CategoryModel();
		if($data=$category->create()){
			if(false!==$category->save()){
				$this->assign('jumpUrl',__URL__.'/add/ctype/'.$_REQUEST["ctype"]);
				$this->success('操作成功');
			}
			else{
				$this->error('操作失败'.$category->getDbError());
			}
		}
		else {
			$this->error('操作失败：数据验证( '.$category->getError().' )');
		}
	}

	public function _empty($name)
	{
		$str="";
		switch ($name) {
			case "catInfo":
				$str = '/Category/add/ctype/10';
				break;
			case "catProduct":
				$str = '/Category/add/ctype/1';
				break;
			case "catFunc":
				$str = '/Category/add/ctype/2';
				break;
			case "catBrand":
				$str = '/Category/add/ctype/3';
				break;
			case "catBox":
				$str = '/Category/add/ctype/4';
				break;
			case "catEffect":
				$str = '/Category/add/ctype/5';
				break;
			case "catShare":
				$str = '/Category/add/ctype/11';
				break;
			case "boxShare":
				$str = '/Category/add/ctype/12';
				break;
			default:
				break;
		}
		$this->redirect($str);
		return ;
	}
}