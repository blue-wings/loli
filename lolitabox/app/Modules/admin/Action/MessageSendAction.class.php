<?php
    class MessageSendAction extends CommonAction{
    	public function taskList(){

    		if($this->_get('orderby')){
    			$orderby=$this->_get('orderby');
    		}else{
    			$orderby="status asc,add_time asc";
    		}

    		if($this->_get("userid")){$where['userid']=$this->_get("userid");}
    		
    		if($this->_get("title")){$where['title']=array('like',"%".trim($this->_get("title"))."%");}
    		
    	    if($this->_get("type")){$where['type']=$this->_get("type");}
    	    
    		if($this->_get("status")=='1'){
    			$where['status']=$this->_get("status");
    		}else if($this->_get("status")==='0'){
    			$where['status']=0;
    		}
    	
    		$user_send_task=M("UserSendTask");
    		if($this->_get("delete")){
    			$flag=$user_send_task->where($where)->delete();
    			if($flag) 
    			{
    				$this->success("删除成功");die;
    			}else{
    				$this->error("删除失败");die;
    			}
    		}

    		$count=$user_send_task->where($where)->order($orderby)->count();    		
    		import("@.ORG.Page");
    		$p = new Page($count,25);
    		$list=$user_send_task->where($where)->limit($p->firstRow . ',' . $p->listRows)->order("$orderby")->select();
    		$user_mod=M("Users");
    		for($i=0;$i<count($list);$i++)
    		{
    			$list[$i]['nickname']=$user_mod->where("userid=".$list[$i]['userid'])->getField("nickname");
    			$list[$i]['add_time']=date("Y-m-d H:i:s",$list[$i]['add_time']);
    			if($list[$i]['send_time'])
    			   $list[$i]['send_time']=date("Y-m-d H:i:s",$list[$i]['send_time']);
    		}
    		$page = $p->show();
    		$smsinfo="";
    		$sms_yue = getMessBalance();
    		$smsinfo.="短信平台可用余额为 <span id='sms_yue' style='color:red;font-size:14'>".$sms_yue."</span> 条,";
    		$sms_count=$user_send_task->where("type=2 AND status=0")->count();
    		$smsinfo.="待发短信为 ".$sms_count." 条。";
    		if($sms_yue < $sms_count)
    			$smsinfo.='<span style="color:red">您的短信平台余额不足，请尽快充值</span>';
    		$this->assign("list",$list);
    		$this->assign("smsinfo",$smsinfo);
    		$this->assign("orderby",$orderby);
    		$this->assign("page",$page);
    		$this->display();
    	}
    } 
?>