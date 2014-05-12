<?php
/**
 * 用户信息模型
*/
class UserDataModel extends Model {
	
	/**
	 * 向用户数据表中添加数据
	 * @param $userid 用户ID
	 * @param $type 数据类型
	 * @param $value 数据内容
	 * @param $state=1,数据类型为num,$state=2,数据类型为string,
	 * @author penglele
	 */
	public function addUserData($userid,$type,$value="",$state=1){
		$data['userid']=$userid;
		$data['type']=$type;
		$if_data=$this->where($data)->find();
		if($if_data){
			$updata['mtime']=date("Y-m-d H:i:s");
			if($state==1){
				$v = $if_data['value'] ? ((int)$if_data['value']+1):1;
				$updata['value'] = "$v";
			}else{
				$updata['value']=$value;
			}
			$up_data=$this->where("id=".$if_data['id'])->save($updata);
			if($up_data!==false)
				return true;
			else 
				return false;
		}else{
			$value= $value ? $value:1;
			$data['value']=$value;
			$data['mtime']=date("Y-m-d H:i:s");
			$inser_data=$this->add($data);
			if(!$inser_data)
				return false;
			else
				return true;
		}
	}
	
	/**
	 * 将用户信息清空
	 * @author penglele
	 */
	public function updataUserData($userid,$type){
		$if_userinfo=$this->where("userid=$userid AND type='".$type."'")->find();
		if ($if_userinfo) {
			$data ['value'] = 0;
			$data ['mtime'] = date ( "Y-m-d H:i:s" );
			$res = $this->where ( "id=" . $if_userinfo ['id'] )->save ( $data );
			if ($res !== false)
				return false;
			else
				return true;
		} else {
			$dat ['userid'] = $userid;
			$dat ['type'] = $type;
			$dat ['mtime'] = date ( "Y-m-d H:i:s" );
			$dat ['value'] = 0;
			if($this->add ( $dat )){
				return true;
			}else{
				return false;
			}
			
		}
		
	}
	
	/**
	 * @param $userid 用户ID
	 * @param  $type 数据类型
	 * @author penglele
	 */
	public function getUserDataInfo($userid,$type){
		$user_data=$this->where("userid=$userid AND type='".$type."'")->find();
		if(!$user_data)
			return 0;
		else
			return $user_data['value'];
	}
	
	/**
	 * 通过某用户获取到新信息动态列表
	 * @param unknown_type $userid
	 */
	public function getUserDatalistByUserid($userid){
		$data['type'] = array("in","notice_num,unread_comment,newmsg_num,brandinfo_num");
		$data['userid'] = $userid;
		$list=$this -> where($data) ->field("type,value,mtime")->select();
		$return = array();
		foreach($list as $key =>$val){
			$k = $val['type'];
			$return[$k] = $val['value'];
			if($k=="notice_num"){
				$time=strtotime($val['mtime']);
			}
			if($k=="brandinfo_num"){
				$brand_time = $val['mtime'];
			}
		}
		$regdate = M("Users")->where("userid=".$userid)->getField("addtime");
		
		//系统私信处理
    	$time = $time ? $time:0;
    	$regtime = strtotime($regdate);
		$addtime = $time ? $time : strtotime("2013-08-30");
		$where['to_uid'] = 0;
		$where['to_status'] = 1;
		$where['addtime'] = array("gt",$addtime);
		$count = D("Msg")->where($where)->count();
		if($count){
			$num=$return['notice_num']?$return['notice_num']:0;
			$return['notice_num'] = $num+$count;
		}
		
		//品牌资讯数
		$brandtime = $brand_time ? $brand_time:$regdate;
		$count_brand = D("Article")->getBrandInfoNumByUserid($userid,'',$brandtime);
// 		if($count_brand){
// 			$this->addUserData($userid, "brandinfo_num",$count_brand,0);
// 		}
		$return['brandinfo_num'] = $count_brand;
		return $return;
	}
	
	
	
	
}