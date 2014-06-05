<?php 
	/**
	 *关于发送私信的模型
	 */
	class MsgModel extends Model {

		/**
		 * 给用户发送私信
		*/
		public function addMsg($fromuid,$touserid,$message){
            $content['content']=$message;
            $dataid=M("MsgData")->add($content);
			if($dataid && $fromuid && $touserid){
				$data["from_uid"]=$fromuid;
				$data["to_uid"]=$touserid;
				$data['k'] = abs($data["from_uid"]-$data["to_uid"]);
				$data['dataid']=$dataid;
				$data["addtime"]=time();
				if($this->add($data)){
					if($fromuid==C("LOLITABOX_ID")){
						D("UserData")->addUserData($touserid,'notice_num');
					}else{
						D("UserData")->addUserData($touserid,'newmsg_num');
					}
					return true;
				}else{
					return false;
				}
					
			}
			return false;
		} 
		
		/**
		 * 对收录的分享作者发私信
		 * @param int $userid
		 * @author litingting
		 */
		public function addMsgByCollect($userid,$id){
			$msg ="您的分享很精彩，已被收录！并且获得了积分奖励。<a href='/home/score.html' class='WB_info' target='_blank'>【查看我的积分】</a> <a href='".getShareUrl($id)."' class='WB_info'>【分享详情】</a>";
			$dataid=M("MsgData")->where("content=".$msg)->getField("id");
			if($dataid){
				$msg_id = $dataid;
			}else{
				$msg_id = M("MsgData")->add(array("content" =>$msg));
			}
			if($msg_id){
				$data['from_uid'] = C("LOLITABOX_ID");
				$data['to_uid'] = $userid;
				$data['k'] = abs($data['from_uid']-$data['to_uid']);
				$data['dataid'] = $msg_id;
				$data['addtime'] = time();
				if($this->add($data)){
					D("UserData")->addUserData($userid,'notice_num');
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

		/**
		 * 给指定用户ID发送私信
		 * @param unknown_type $userid
		 * @param unknown_type $msg
		 * @return boolean true/false
		 * @author zhenghong 2013-09-11
		 */
		public function addMsgFromLolitabox($userid,$msg){
			if(!$userid || empty($msg)) {
				return false;
			}
			$dataid=M("MsgData")->where("content=".$msg)->getField("id");
			if($dataid){
				$msg_id = $dataid;
			}else{
				$msg_id = M("MsgData")->add(array("content" =>$msg));
			}
			if($msg_id){
				$data['from_uid'] = C("LOLITABOX_ID");
				$data['to_uid'] = $userid;
				$data['k'] = abs($data['from_uid']-$data['to_uid']);
				$data['dataid'] = $msg_id;
				$data['addtime'] = time();
				if($this->add($data)){
					D("UserData")->addUserData($userid,'notice_num');
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		
		
        /**
         * 批量发私信
         * @param unknown_type $fromid
         * @param unknown_type $where
         * @param unknown_type $content
         */
		public function datAddMsg($where,$content,$fromid=""){
			$fromid =$fromid ? $fromid : C("LOLITABOX_ID");
			$contents['content']=$content;
			$dataid= M("MsgData")->add($contents);
			if(!$dataid) {
				return false;
			}
			if(empty($where)){
				$data["from_uid"]=$fromid;
				$data["to_uid"]=0;
				$data['k'] = abs($data["from_uid"]-$data["to_uid"]);
				$data['dataid']=$dataid;
				$data["addtime"]=time();
				$this->add($data);
				
			}else{
				$userlist = M("Users")->field("userid")->where($where)->select();
				if($userlist){
					$user_data_mod = D("UserData");
					foreach($userlist as $key =>$val){
						$data = array();
						$data["from_uid"]=$fromid;
						$data["to_uid"]=$val['userid'];
						$data['k'] = abs($data["from_uid"]-$data["to_uid"]);
						$data['dataid']=$dataid;
						$data["addtime"]=time();
						if($this->add($data))
				    		$user_data_mod -> addUserData($data['to_uid'],'newmsg_num');
					}
				}else{
					return false;
				}
			}
			
		}
		
		/**
		 * 通过用户id获取私信列表
		 * @param int $userid
		 * @param string $p 分页
		 */
		public  function getMsgListByUserid($userid,$p=null){
			if(!$userid)
				return false;
			if($p)
				$limit = " LIMIT ".$p;
			$sql = "SELECT * FROM (select * from msg WHERE ( from_uid=".$userid." AND from_status=1 OR (to_uid=".$userid." AND to_status=1) ) order by addtime desc ) temp GROUP BY k  ORDER BY addtime desc ".$limit; 
			$list = $this->query($sql);
			foreach($list as $key =>$val){
					$to_userid = $val['from_uid']==$userid ? $val['to_uid']:$val['from_uid'];
					$list[$key]['type'] = $val['from_uid']==$userid ? 1 : 2;
					$userinfo = D("Users")->getUserInfo($to_userid,"userface,nickname");
					$list[$key]['userid']=$to_userid;
					$list[$key]['nickname']=$userinfo['nickname'];
					$list[$key]['userface']=$userinfo['userface_65_65'];
					$list[$key]['content'] = D("MsgData")-> where("id=".$val['dataid']) ->getField("content");
					$list[$key]['count'] = $this->getMsgDialogueCount($userid, $to_userid);
					$list[$key]['addtime'] = $val['addtime'] ;
					$list[$key]['spaceurl']=getSpaceUrl($list[$key]['userid']);
			}
            return $list;
		}
		
		/**
		 * 根据用户ID获取私信总数
		 * @param int $userid
		 * @param string $p
		 */
		public  function getMsgCountByUserid($userid){
			if(!$userid)
				return false;
			$list = $this->field("k")->where("from_uid=".$userid." AND from_status=1 OR (to_uid=$userid AND to_status=1)")->group("k")->order("id desc")->select();
			return count($list);
		}
		
		/**
		 * 获取用户之前的私信对话
		 * @param int $userid
		 * @param int $to_userid
		 */
		public function getMsgDialogue($from_userid,$to_userid,$limit="10"){
			 $list = $this->where("from_uid=".$from_userid." AND to_uid=$to_userid AND from_status=1 OR (to_uid=$from_userid AND from_uid=$to_userid AND to_status=1)")->limit($limit)->order("id desc")->select();
			 foreach($list as $key=>$val){
			 	 $userinfo = D("Users")->getUserInfo($val['from_uid']);
			 	 $list[$key]['nickname']=$userinfo['nickname'];
			 	 $list[$key]['userface']=$userinfo['userface_65_65'];
			 	 $list[$key]['content'] = D("MsgData")-> where("id=".$val['dataid']) ->getField("content");
			 }
			 return $list;
		}
		
		/**
		 * 获取用户对话的条数
		 * @param unknown_type $from_uid
		 * @param unknown_type $to_userid
		 */
		public function getMsgDialogueCount($from_uid,$to_uid){
			 $count = $this->where("from_uid=".$from_uid." AND to_uid=$to_uid AND from_status=1 OR (to_uid=$from_uid AND from_uid=$to_uid AND to_status=1)")->count();
			 return $count;
		}
		
		/**
		 * 删除某一条私信
		 * @param $userid 删除信息的userid
		 * @param 要删除的信息ID
		 */
		public function deletDialogueMsg($userid,$msgid){
			if(empty($userid) || empty($msgid)) return false;
			$msg_info=$this->where("id=$msgid")->getById($msgid);
			if($msg_info['from_uid']!=$userid && $msg_info['to_uid']!=$userid) 
				return false;
			if($msg_info['from_uid']==$userid) {
				$data['from_status']=0;
				if($msg_info['to_uid']==0){
					$data['to_status']=0;
				}
			}
			if($msg_info['to_uid']==$userid) 
				$data['to_status']=0;
			$res=$this->where("id=$msgid")->save($data);
			if($res===false) 
				return false;
			else 
				return true;
		}
		
		
		/**
		 * 获取用户收件箱
		 * @author lit
		 */
		public function getReceverMsgListByUserid($userid,$p=null){
			$where['_string'] = "(to_uid=$userid OR to_uid=0)";
			$where['to_status'] = 1;
			$where['addtime'] = array("egt",strtotime(M("Users")->where("userid=".$userid)->getField("addtime")));
			$where['from_uid']=array("exp","!=".C("LOLITABOX_ID")."");
			$list = $this->where($where)->order("addtime DESC")->limit($p)->select();
			foreach($list as $key =>$val){
				$userinfo = D("Users")->getUserInfo($val['from_uid'],"userface,nickname");
				$list[$key]['userid']=$val['from_uid'];
				$list[$key]['if_del'] = $list[$key]['to_uid']==0 ? 0:1;
				$list[$key]['nickname']=$userinfo['nickname'];
				$list[$key]['userface']=$userinfo['userface_65_65'];
				$list[$key]['content'] = D("MsgData")-> where("id=".$val['dataid']) ->getField("content");
				if($userinfo['userid']==2375){
					$list[$key]['content']=text2links($list[$key]['content']);
				}
				$list[$key]['addtime'] = $val['addtime'] ;
				$list[$key]['spaceurl']=getSpaceUrl($list[$key]['userid']);
			}
			return $list;
		}
		
		
		/**
		 * 获取用户发件箱
		 * @author lit
		 */
		public function getPostMsgListByUserid($userid,$p=null){
			$where['_string'] = "(from_uid=$userid)";
			$where['from_status'] = 1;
			$list = $this->where($where)->order("addtime DESC")->limit($p)->select();
			foreach($list as $key =>$val){
				$userinfo = D("Users")->getUserInfo($val['to_uid'],"userface,nickname");
				$list[$key]['userid']=$val['to_uid'];
				$list[$key]['if_del'] =1;
				$list[$key]['nickname']=$userinfo['nickname'];
				$list[$key]['userface']=$userinfo['userface_65_65'];
				$list[$key]['content'] = D("MsgData")-> where("id=".$val['dataid']) ->getField("content");
				$list[$key]['addtime'] = $val['addtime'] ;
				$list[$key]['spaceurl']=getSpaceUrl($list[$key]['userid']);
			}
			return $list;
		}
		
		/**
		 * 获取发件箱私信总数
		 * @author lit
		 */
		public function getPostMsgCount($userid){
			$where['_string'] = "(from_uid=$userid)";
			$where['from_status'] = 1;
			return $this->where($where)->count();
		}
		
		
		/**
		 * 获取收件箱私信总数
		 * @author lit
		 */
		public function getReceverMsgCount($userid){	
			$where['_string'] = "(to_uid=$userid OR to_uid=0)";
			$where['to_status'] = 1;
			$where['addtime'] = array("egt",strtotime(M("Users")->where("userid=".$userid)->getField("addtime")));
			$where['from_uid']=array("exp","!=".C("LOLITABOX_ID")."");
			return $this->where($where)->count();
		}
		
		/**
		 * 获取用户收到的官网发的私信
		 * @param int $userid
		 * @author penglele
		 */
		public function getMsgListByLolitabox($userid,$limit=""){
			if(!$userid)
				return false;
			$where['_string'] = "(to_uid=$userid OR to_uid=0)";
			$where['to_status'] = 1;
			$where['addtime'] = array("egt",strtotime(M("Users")->where("userid=".$userid)->getField("addtime")));
			$where['from_uid']=C("LOLITABOX_ID");
			$list = $this->where($where)->order("addtime DESC")->limit($limit)->select();
			foreach($list as $key =>$val){
				$userinfo = D("Users")->getUserInfo($val['from_uid'],"userface,nickname");
				$list[$key]['userid']=$val['from_uid'];
				$list[$key]['if_del'] = $list[$key]['to_uid']==0 ? 0:1;
				$list[$key]['nickname']=$userinfo['nickname'];
				$list[$key]['userface']=$userinfo['userface_65_65'];
				$list[$key]['content'] = D("MsgData")-> where("id=".$val['dataid']) ->getField("content");
				$list[$key]['content']=text2links($list[$key]['content']);
				$list[$key]['addtime'] = $val['addtime'] ;
				$list[$key]['spaceurl']=getSpaceUrl($list[$key]['userid']);
			}
			return $list;
		}
		
		/**
		 * 获取用户收到的官网发的私信总数
		 * @author penglele
		 */
		public function getMsgCountByLolitabox($userid){
			$where['_string'] = "(to_uid=$userid OR to_uid=0)";
			$where['to_status'] = 1;
			$where['addtime'] = array("egt",strtotime(M("Users")->where("userid=".$userid)->getField("addtime")));
			$where['from_uid']=C("LOLITABOX_ID");
			return $this->where($where)->count();
		}
		
	}	
	?>