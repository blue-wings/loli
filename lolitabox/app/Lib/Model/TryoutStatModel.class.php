<?php
/**
 * 我要试用模型
 */
class TryoutStatModel extends Model {
	
	/**
	 * 添加用户试用数据
	 * @param unknown_type $userid
	 * @param unknown_type $id
	 * @param unknown_type $type
	 * @author penglele
	 */
	public function addTryout($userid,$id,$type){
		if(!$userid || !$id || !$type){
			return false;
		}
		$data['userid']=$userid;
		$data['resourcetype']=$type;
		$data['resourceid']=$id;
		$if_tryout=$this->where($data)->find();
		if($if_tryout){
			$updata['addtime']=time();
			$this->where($data)->save($updata);
		}else{
			$data['addtime']=time();
			$this->add($data);
		}
	}
	
	/**
	 * 获取我要试用的产品榜
	 * @author penglele
	 */
	public function getTryoutListOfProducts($limit=""){
		if($limit){
			$limit="LIMIT $limit";
		}
		$sql="SELECT resourceid,count(resourceid) as num FROM tryout_stat WHERE resourcetype=1 GROUP BY resourceid ORDER BY num DESC,addtime DESC $limit";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$pro_mod=M("Products");
			foreach($query as $key=>$val){
				$info=$pro_mod->field("pid,pname,pimg")->where("pid=".$val['resourceid'])->find();
				if($info){
					$info['url']=getProductUrl($info['pid']);
					$list[]=$info;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取试用用户的榜单
	 * @author penglele
	 */
	public function getTryoutListOfUser($limit=""){
		if($limit){
			$limit="LIMIT $limit";
		}
		$sql="SELECT userid,resourceid FROM tryout_stat WHERE resourcetype=1 GROUP BY userid ORDER BY addtime DESC $limit";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$user_mod=D("Users");
			$pro_mod=M("Products");
			foreach($query as $key=>$val){
				$info=array();
				$pro_info=$pro_mod->field("pid,pname")->where("pid=".$val['resourceid'])->find();
				$user_info=$user_mod->getUserInfo($val['userid'],"nickname");
				if($user_info){
					$info['nickname']=$user_info['nickname'];
					$info['userface']=$user_info['userface_50_50'];
					$info['spaceurl']=getSpaceUrl($user_info['userid']);
					$info['pname']=$pro_info['pname'];
					$info['purl']=getProductUrl($pro_info['pid']);
					$list[]=$info;
				}
			}
		}
		return $list;
	}
	
	
	
	
	
	
}
?>