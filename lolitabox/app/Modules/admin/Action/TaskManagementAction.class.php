<?php
class TaskManagementAction extends CommonAction{

    /**
      +----------------------------------------------------------
      * 子任务管理  及增删改查于一身
      +----------------------------------------------------------  
      * @access public   
      +----------------------------------------------------------
      * @param  create       添加		
      * @param  edit         修改	
      * @param  changestatus 修改状态		
      * @param  del          删除	
      +-----------------------------------------------------------
      * @author zhaoxiang 2013.7.18
     */
	function  tasklist(){

		$task_mod = M("TaskChild");

		if($this->_post('action') == 'create'){

			$data = array(
			'taskid'=>$this->_post('parentid'),
			'relationid'=>$this->_post('relationid'),
			'title'=>$this->_post('taskname'),
			'from'=>strtotime($this->_post('form').' 00:00:00'),
			'to'=>strtotime($this->_post('to').' 23:59:59'),
			'rules'=>$this->_post('note'),
			'credit'=>$this->_post('score'),
			'status'=>1
			);
			
			
			if($this->_post('oldid')){
				if(false !== $task_mod->where(array('id'=>$this->_post('oldid')))->save($data)){
					$this->success("修改成功!");
				}else{
					$this->error("修改失败!");					
				}
			}else{
				if($task_mod->add($data)){
					$this->success("添加成功!");
				}else{
					$this->error("添加失败!");
				}
			}

		}else if($this->_post('action') == 'changestatus'){
			$result = M("TaskChild")->where(array('id'=>$this->_post('taskid')))->setField('status',$this->_post('val'));
			if($result){
				$this->ajaxReturn(1,1,1);
			}else{
				$this->ajaxReturn(0,0,0);
			}
			exit();
		}else if($this->_get('action') == 'del'){
			if($task_mod->delete($this->_get('taskid'))){
				$this->success("删除成功!");
			}else{
				$this->error("删除失败!");
			}
		}else if($this->_post('action') == 'edit'){
            
            $this->ajaxReturn($task_mod->find($this->_post('id')),1,1);
        
        }else if($this->_post('action') == 'selectpro'){
                
            $returninfo = M()->query("SELECT pid,pname,pimg,evaluatenum,id FROM products as p LEFT JOIN inventory_item as i ON p.pid = i.relation_id WHERE pid = {$this->_post('rid')} ");

            if($returninfo[0]){
                $this->ajaxReturn('查询成功!',$returninfo[0],1);
            }else{
                $this->ajaxReturn(0,'查询失败!',0);
            }
        }else{

			if($this->_get('parentid')){
				$where['task_child.taskid']=$this->_get('parentid');
			}

			if($this->_get('taskname')){
				$where['task_child.title'] = array('like',"%{$this->_get('taskname')}%");
			}

			if($this->_get('startdate') && $this->_get('enddate')){
				$where['task_child.from'] = array('egt',strtotime($this->_get('startdate')));
				$where['task_child.to'] = 	array('elt',strtotime($this->_get('enddate')));
			}else if($this->_get('startdate')){
				$where['task_child.from'] = array('egt',strtotime($this->_get('startdate')));
			}else if($this->_get('enddate')){
				$where['task_child.to'] = 	array('elt',strtotime($this->_get('enddate')));
			}


			if($this->_get('status') === '0'){
				$where['task_child.status']  = 0;
			}elseif($this->_get('status') == 2){
			}else{
				$where['task_child.status']  = 1;
			}

			import("@.ORG.Page");
			$count=$task_mod->where($where)->count();

			$p = new Page($count,15);
			$list = $task_mod->where($where)->join("task ON task.id=task_child.taskid")->field("task_child.*,task.name")->limit($p->firstRow . ',' . $p->listRows)->order('id DESC')->select();
			
			$page = $p->show();

			$tasklist = $this->getTaskList();

			$this->assign('page',$page);
			$this->assign("tlist",$list);
			$this->assign("tasklist",$tasklist);
			$this->display();
		}
	}


	//获取父级任务列表
	private  function getTaskList($where){
		$where['status'] = array('neq',0);
		$where['id'] = array('in',array(7,9));
		return 	M("Task")->where($where)->select();
	}
}
?>
