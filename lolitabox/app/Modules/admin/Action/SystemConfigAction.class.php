<?php

  /**
   * 系统常量定义控制器
   * @author litingting
   */
  class SystemConfigAction extends CommonAction{
  	 
  	 /**
  	  * 根据条件获取满足常量配置列表
  	  * @param key 配置名称
  	  * @param type 配置类型
  	  * @author litingting
  	  */
  	  public function constantConfig(){
  	  	   extract($_REQUEST);
  	  	   $system_config_model=M("SystemConfig");
  	  	   if($key){
  	  	   	   $where['key']=array("like","%$key%");
  	  	   }
  	  	   if($type){
  	  	   	   $where['type']=$type;
  	  	   }
  	  	   $count=$system_config_model->where($where)->count();
  	       import("@.ORG.Page");
		   $p = new Page($count,25);
		   $list=$system_config_model->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
		   $page=$p->show();
		   $typelist=$system_config_model->field("distinct type ")->select();
		   $this->assign("page",$page);
		   $this->assign("typelist",$typelist);
		   $this->assign("list",$list);
		   $this->display();
  	  }
  	  
  	  /**
  	   * 增加或编辑常量
  	   * @param id  常量ID 如果有id代码编辑，没有刚是增加
  	   * @param key 配置名称
  	   * @param val 配置值
  	   * @param remark 配置描述
  	   * @param type 配置类型
  	   * @author litingting 
  	   */
  	  public function updConstant(){
  	  	 if(!$_REQUEST['submit']){
  	  	 	if($_REQUEST['id'])   {
  	  	 		$info=M("SystemConfig")->getById($_REQUEST['id']);
  	  	 		$this->assign("info",$info);
  	  	 	}
  	  	 	$typelist=M("SystemConfig")->field("distinct type ")->select();
  	  	 	$this->assign("typelist",$typelist);
  	  	 	$this->display();die;
  	  	 }
  	  	 $system_config_model=M("SystemConfig");
  	  	 $_REQUEST['type']=$_REQUEST['type']?$_REQUEST['type']:$_REQUEST['type1'];
  	  	 if($_REQUEST['id']){
  	  	 	if($system_config_model->where("id=".$_REQUEST['id'])->save($_REQUEST)!==false)
  	  	 		$this->success("编辑成功");die;
  	  	 }else{
  	  	 	if(false!==$system_config_model->add($_REQUEST))
  	  	 		$this->success("添加成功");die;
  	  	 }
  	  	 $this->error("操作失败");
  	  }
  	  
  	  /**
  	   * 删除常量
  	   * @param id  配置常量ID 
  	   * @author litingting
  	   */
  	  public function delConstant(){
  	  	$system_config_model=M("SystemConfig");
  	  	if($_REQUEST['id']){
  	  		if($system_config_model->where("id=".$_REQUEST['id'])->delete($_REQUEST)!==false)
  	  			$this->ajaxReturn(0,"删除成功",1
  	  					);
  	  		else{
  	  			$this->ajaxReturn(0,"删除失败",0);
  	  		}
  	  	}else{
  	  		$this->ajaxReturn(0,"没有参数",0);
  	  	}
  	  }
  	  
  	  /**
  	   * ajax更新系统配置文件
  	   * @author  litingting
  	   */
  	  public function updConfigFile(){
  	      $file="config_global.inc.php";
  	      $system_config_model=M("SystemConfig");
  	      $list=$system_config_model->select();
  	      $string="<?php \n  return  array(  \n";
  	      for($i=0;$i<count($list);$i++){
  	      	  $key=$list[$i]['key'];
  	      	  $string.="    '".$key."'=>'".$list[$i]['val']."',   //" .$list[$i]['remark']."\n";
  	      }
  	      $string.=");";
  	      $flag=file_put_contents($file, $string);
  	      if($flag)
  	      	$this->ajaxReturn(0,"更新成功",1);
  	      else 
  	      	$this->ajaxReturn(0,"更新失败",0);
  	  }
  }