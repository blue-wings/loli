<?php

class UserAtmeModel extends Model {
	
	/**
	 * 插入一条@
	 * @param unknown_type $relationid
	 * @param unknown_type $relationtype
	 * @param unknown_type $sourceid
	 * @param unknown_type $sourcetype
	 */
	public function addAt($relationid,$relationtype=1,$sourceid,$sourcetype=1,$state=0,$time=0){
		$data_at['relationid'] = $relationid;
		$data_at['relationtype'] = $relationtype;
		$data_at['sourceid'] = $sourceid;
		$data_at['sourcetype'] = $sourcetype;
		if($this->where($data_at)->find()){
			return false;
		}
		$data_at['state'] = $state;
		$data_at['addtime'] = $time ? $time:time();
		$ret = $this->add($data_at);
		if($state ==1){
			$this -> shareTaskStatus($relationid,$sourceid,$sourcetype);
		}
		return $ret;
	
	}
	
	/**
	 * 改变@状态
	 * @param unknown_type $relationid
	 * @param unknown_type $relationtype
	 * @param unknown_type $sourceid
	 * @param unknown_type $sourcetype
	 */
	public function changeState($relationid,$relationtype=1,$sourceid,$sourcetype=1,$state=1){
		$data_at['relationid'] = $relationid;
		$data_at['relationtype'] = $relationtype;
		$data_at['sourceid'] = $sourceid;
		$data_at['sourcetype'] = $sourcetype;
		if($this->where($data_at)->find()){
			$data['state'] = $state;
			$data['addtime'] = time();
			$ret= $this->where($data_at)->save($data);
			if($state==1){
				$this->shareTaskStatus($relationid,$sourceid,$sourcetype);
			}
			return $ret;
		}else{
			return -1;
		}
	}
	
	
	/**
	 * 改变任务分享状态
	 * @param int shareid
	 */
	public function shareTaskStatus($shareid,$sourceid,$sourcetype){
		$task_stat = M("TaskStat");
		$task_order_stat = M("TaskOrderStat");
		$task_stat->where("taskid=9 AND relationid=".$shareid)->setField("status", 1);
		if($sourceid==C("SHOW_BOX_USERID") && $sourcetype==4){
			$task_order_stat ->where("shareid=".$shareid)->setField("status", 1);   //只有当此分享收录到晒盒记中，才会改变任务状态
		}
		
	}
	
	
	/**
	 * 通过分享ID获取@产品的列表
	 * @param int $shareid 分享ID
	 * @author penglele
	 */
	public function getPidListAtShare($shareid){
		$source_list=array();
		if(!$shareid)
			return $source_list;
		//通过shareid查看user_atme表中用户有没有@产品
		$at_list=$this->field("sourceid")->where("relationid=$shareid AND relationtype=1 AND sourcetype=2")->select();
		if($at_list){
			foreach($at_list as $key=>$val){
				$source_list[]=$val['sourceid'];
			}			
		}
		//通过shareid查看user_share，用户是不是对某一产品发的分享
		$share_info=M("UserShare")->field("resourceid")->where("id=$shareid AND resourcetype=1")->find();
		if($share_info['resourceid']!=0 && !in_array($share_info['resourceid'],$source_list)){
			$source_list[]=$share_info['resourceid'];
		}
		return $source_list;
	}
	
	/**
	 * 获取标签列表
	 * @param int $shareid
	 * @author litingting
	 */
	public function getTagListByShareid($shareid){
		$at_list=M("UserAtme")->field("sourceid,sourcetype")->where("relationid=".$shareid." AND relationtype=1 AND state=1")->select();
		$a=0;
		$public_mod = D("Public");
		foreach($at_list as $k =>$v){
			$taginfo = $public_mod ->getSourceInfo($v['sourceid'],$v['sourcetype']);
			if($taginfo){
				$taglist[$a]['name']= $taginfo['nickname'];
				$taglist[$a]['spaceurl']= $taginfo['spaceurl'];
				$a++;
			}
		}
		return $taglist;
	}
}
?>