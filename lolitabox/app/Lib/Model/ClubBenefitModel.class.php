<?php
/**
 * benefit活动模型
 */
class ClubBenefitModel extends Model {
	
	/**
	 * 获取benefit活动投票列表
	 * @param  $cate_id 文章分类id
	 * @param  $userid   用户ID
	 * @param  $type 
	 */
	public function getBenefitVoteList($cate_id,$userid,$type=1){
		if(!$cate_id){
			return "";
		}
		$vote_mod=M("ClubBenefitVotestat");
		$article_mod=M("Article");
		//$list=D("Article")->getArticleList($cate_id);
		$list=$article_mod->where("cate_id=".$cate_id)->order("ordid DESC")->select();
		//echo $article_mod->getLastSql();
		//dump($list);
		if($list){
			$useri_mod=M("Users");
			$pro_mod=M("Products");
			foreach($list as $key=>$val){
				$list[$key]['order']=$val['info']==1 ? 1 : 0 ;
				if($type==2){
					$list[$key]['nickname']=$useri_mod->where("userid=".$val['orig'])->getField("nickname");
					$list[$key]['spaceurl']=getSpaceUrl($val['orig']);
					if($val['url']){
						$pro_info=$pro_mod->field("pimg,pname")->where("pid=".$val['url'])->find();
						$list[$key]['pimg']=$pro_info['pimg'];
						$list[$key]['pname']=$pro_info['pname'];
						$list[$key]['purl']=getProductUrl($val['url']);
					}
				}
				$list[$key]['total_num']=(int)$val['abst']+(int)$val['info'];
				/*根据刘雪苗需求，需要将第三期投票数改为成功转发微博的数量，这里做如下修改：2014-2-27*/
				$target_id=$list[$key]['id'];
				$where["target_id"]=$target_id;
				$where["if_weibo"]=1;
				$list_weibo_vote_num=$vote_mod->where($where)->count();
				$list[$key]['total_num']=$list_weibo_vote_num+(int)$val['info'];
				/*根据刘雪苗需求，需要将第三期投票数改为成功转发微博的数量，这里做如下修改：2014-2-27*/
				if($userid){
					$if_vote=$vote_mod->where("userid=$userid AND target_id=".$val['id'])->find();
					$list[$key]['to_weibo']=0;
					if(!$if_vote){
						//用户没有投票过
						$list[$key]['if_vote']=0;
					}else{
						if($type==1){
							//第一阶段的投票
							if($if_vote['if_weibo']==-1){
								$list[$key]['if_vote']=1;
							}else{
								$list[$key]['if_vote']=2;
							}
						}else{
							//第二阶段的投票
							$list[$key]['if_vote']=1;
						}
						if($if_vote['if_weibo']==0){
							$if_time=$this->getUserIfToWeibo($type);
							if($if_time==true){
								$list[$key]['to_weibo']=1;//用户是否可以转发到微博
							}
						}
					}
				}else{
					$list[$key]['if_vote']=0;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取benefit活动 “眉”来运转 美梦成真 的列表
	 * @author penglele
	 */
	public function getBenefitList($cid){
		$list=D("Article")->getArticleList($cid);
		if($list){
			$user_mod=M("Users");
			foreach($list as $key=>$val){
				$list[$key]['nickname']=$user_mod->where("userid=".$val['orig'])->getField("nickname");
				$list[$key]['spaceurl']=getSpaceUrl($val['orig']);
				$list[$key]['total_num']=(int)$val['abst']+(int)$val['info'];
			}
		}
		return $list;
	}
	
	
	/**
	 * 通过benefit积分获取用户列表
	 * @author penglele
	 */
	public function getUserListByBrandScore($limit=""){
		$list=M("ClubUsers")->where("name='benefit'")->order("score DESC,addtime ASC")->limit($limit)->select();
		if($list){
			$user_mod=D("Users");
			$club_mod=M("ClubUsers");
			foreach($list as $key=>$val){
				$userinfo=$user_mod->getUserInfo($val['userid'],"nickname,userface");
				$list[$key]['nickname']=$userinfo['nickname'];
				$list[$key]['userface']=$userinfo['userface_55_55'];
				$list[$key]['spaceurl']=getSpaceUrl($val['userid']);
				$list[$key]['score']=$club_mod->where("userid=".$val['userid']." AND name='benefit'")->getField("score");
			}
		}
		return $list;
	}
	
	
	/**
	 * 关注benefit的用户如果进入benefit活动时，如果在此活动中没有信息，则自动添加记录
	 * @author penglele
	 */
	public function getUserOfFollowBenefitInfo($userid){
		if($userid){
			$where['userid']=$userid;
			$where['whoid']=1;
			$where['type']=3;
			//判断用户是否已经关注benefit
			$if_follow=M("follow")->where($where)->find();
			if($if_follow){
				$club_where['userid']=$userid;
				$club_where['name']="benefit";
				$club_mod=M("ClubUsers");
				//判断用户是否在club_users中有记录
				$if_club_user=$club_mod->where($club_where)->find();
				if(!$if_club_user){
					//如果没有，则创建一条新记录
					$club_where['score']=0;
					$club_where['addtime']=time();
					$club_mod->add($club_where);
				}
			}
		}
	}
	
	/**
	 * 获取用户在benefit中的排名
	 * @param  $userid
	 * @author penglele
	 */
	public function getUserStatusOfBenefit($userid){
		if(!$userid){
			return false;
		}
		$this->getUserOfFollowBenefitInfo($userid);
		$club_mod=M("ClubUsers");
		$user_score=$club_mod->where("userid=$userid AND name='benefit'")->getField("score");
		$user_score=$user_score ? $user_score : 0 ;
		$return[2]=M("Follow")->where("whoid=1 AND type=3")->count();//总人数
		$num=$club_mod->where("name='benefit' AND score>".$user_score)->count();
		$return[1]=(int)$num+1;//排名
		$return['score']=$user_score;//用户benefit积分
		return $return;
	}
	
	/**
	 * 投票时间
	 * @param  $id
	 * @author penglele
	 */
	public function getVoteTime($id){
		$list=array(
				"1"=>array(
						'id'=>"777",
						'time'=>array(
								's'=>"2014-01-08",
								'e'=>"2014-01-27"
						)
				),
				"2"=>array(
						'id'=>"778",
						'time'=>array(
								's'=>"2014-02-24",
								'e'=>"2014-03-07"
						)
				)
		);
		if(key_exists($id, $list)){
			return $list[$id];
		}else{
			return false;
		}
	}
	
	/**
	 * 判断用户是否可以转发到微博
	 * @author penglele
	 */
	public function getUserIfToWeibo($type){
		$time_arr=$this->getVoteTime($type);
		if($time_arr){
			$ndate=date("Y-m-d");
			//判断活动是否有效
			if($ndate<$time_arr['time']['s']){
				return false;
			}
			if($ndate>$time_arr['time']['e']){
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * 获取用户第二阶段投票的排序列表
	 * @author penglele
	 */
	public function getUserVoteListOfTop10(){
		$sql="SELECT * , (info + abst) AS num FROM `article`  WHERE cate_id =778 AND status=1 ORDER BY (info +abst) DESC LIMIT 10";
		$alist=$this->query($sql);
		$list=array();
		if($alist){
			$user_mod=M("Users");
			foreach($alist as $key=>$val){
				$list[$key]['nickname']=$user_mod->where("userid=".$val['orig'])->getField("nickname");
				$list[$key]['spaceurl']=getSpaceUrl($val['orig']);
				$list[$key]['num']=(int)$val['abst']+(int)$val['info'];
			}
		}
		return $list;
	}
	
	/**
	 * 获取【品牌】所有正在试用的列表
	 */
	public function getBrandProductsListOnselling($cid){
		$list=array();
		$pid_str=M("article")->where("cate_id=".$cid." AND status=1")->order("ordid DESC,id DESC")->getfield("info");//当前正在售卖的所有产品列表
		if($pid_str){
			$pid_arr=explode(",",$pid_str);
			if($pid_arr){
				$pro_mod=M("Products");
				foreach($pid_arr as $val){
					$info=$pro_mod->where("pid=".$val)->field("pname,pimg,goodssize,goodsprice")->find();
					$info["prourl"]=getProductUrl($val);
					$list[]=$info;
				}
			}
		}
		return $list;
	}
	
	/**
	 * 获取品牌 往期试用产品
	 * @author penglele
	 */
	public function getBrandProductsListBefore($cid,$limit){
		$list=array();
		$limit_arr=explode(",",$limit);
		$pid_str=M("article")->where("cate_id=".$cid." AND status=1")->order("ordid DESC,id DESC")->getfield("info");//当前正在售卖的所有产品列表
		$num=0;
		if($pid_str){
			$pid_arr=explode(",",$pid_str);
			$num=count($pid_arr);
			if($pid_arr){
				$pro_mod=M("Products");
				$i=0;
				foreach($pid_arr as $key=>$val){
					if($key>=$limit_arr[0] && $i<$limit_arr[1]){
						$info=$pro_mod->where("pid=".$val)->field("pname,pimg,goodssize,goodsprice")->find();
						$info["prourl"]=getProductUrl($val);
						$list[]=$info;
						$i++;
					}
				}
			}
		}
		$return['num']=$num;
		$return['list']=$list;
		return $return;
	}
	
	/**
	 * 获取用户对【品牌】产品发表的分享
	 * @param $userid
	 * @author penglele
	 */
	public function getUserShareListOfBrand($userid,$bid=1){
		if(!$userid){
			return "";
		}
		$sql="SELECT s.id FROM user_share s,products p WHERE s.userid=$userid AND s.sharetype=1 AND s.resourcetype=1 AND s.resourceid=p.pid AND p.brandcid=$bid AND s.status>0 ORDER BY pick_status DESC, s.id DESC LIMIT 4";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$share_mod=D("UserShare");
			foreach($query as $val){
				$shareinfo=$share_mod->getShareInfo($val['id'],30);
				$shareinfo['date']=date("Y.m.d",$shareinfo['posttime']);
				$list[]=$shareinfo;
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户对【品牌】产品赞的分享
	 * @author penglele
	 */
	public function getUserAgreeShareListOfBrand($userid,$bid=1){
		if(!$userid){
			return "";
		}
		$sql="SELECT a.shareid AS id,addtime FROM user_share_action a,user_share s,products p WHERE a.userid=$userid AND a.shareid=s.id AND s.sharetype=1 AND s.resourcetype=1 AND s.resourceid=p.pid AND p.brandcid=$bid AND s.status>0 ORDER BY a.addtime DESC LIMIT 6";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$share_mod=D("UserShare");
			foreach($query as $val){
				$shareinfo=$share_mod->getShareInfo($val['id']);
				$shareinfo['date']=date("Y.m.d",$val['addtime']);
				$list[]=$shareinfo;
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户试用过的【品牌】产品
	 * @author penglele
	 */
	public function getProductsListOfUserHaveTry($userid,$bid=1){
		if(!$userid){
			return "";
		}
		$sql="SELECT DISTINCT(i.id) FROM products p,inventory_item i,user_order o,user_order_send_productdetail s WHERE p.brandcid=$bid AND p.pid=i.relation_id AND s.orderid=o.ordernmb AND o.state=1 AND o.ifavalid=1 AND o.userid=$userid AND s.productid=i.id LIMIT 4";
		$query=$this->query($sql);
		$list=array();
		if($query){
			$pro_mod=D("Products");
			$user_order_mod=D("UserOrder");
			foreach($query as $key=>$val){
				$pro=$pro_mod->getSimpleInfoByItemid($val['id']);
				$if_share=$user_order_mod->getUserIfShareToProdu($userid,$pro['pid']);
				$pro['if_share']=$if_share==false ? 0 : 1 ;
				$list[]=$pro;
			}
		}
		return $list;
	}
	
	/**
	 * 获取用户对某一品牌 长草了的产品列表
	 * @param $userid
	 * @param $bid
	 * @author penglele
	 */
	public function getUserTryToProductsByBrand($userid,$bid=1){
		if(!$userid){
			return "";
		}
		$sql="SELECT t.resourceid AS id FROM tryout_stat t,products p WHERE t.resourceid=p.pid AND t.resourcetype=1 AND t.userid=$userid AND p.brandcid=$bid ORDER BY addtime DESC LIMIT 4";	
		$query=$this->query($sql);
		$list=array();
		if($query){
			$pro_mod=D("Products");
			foreach($query as $val){
				$list[]=$pro_mod->getProductInfo($val['id']);
			}
		}
		return $list;
	}
	
	/**
	 * 用户任务列表
	 * @param  $userid
	 * @param  $type
	 */
	public function getUserTaskList($userid){
		$list=array();
		$club_vote_mod=M("ClubBenefitVotestat");
		$article_mod=M("Article");
		$share_mod=M("UserShare");
		//第一阶段投票任务
		$info_1=$club_vote_mod->where("userid=$userid AND cate_id=777 AND if_weibo=1")->find();
		$list[1]=$info_1 ? 1 : 0 ;
		//第一阶段上传图片
		$info_2=$article_mod->where("cate_id=779 AND add_time<='2014-01-27' AND orig=$userid AND status=1")->find();
		$list[2]=$info_2 ? 1 : 0 ;
		//提交试用报告
		$share_data['userid']=$userid;
		$share_data['sharetype']=1;
		$share_data['resourcetype']=1;
		$share_data['resourceid']=63118;
		$share_data['pick_status']=array("exp",">0");
		$if_share=$share_mod->where($share_data)->find();
		$list[3]=$if_share ? 1 : 0 ;
		//第二阶段上传图片
		$info_4=$article_mod->where("cate_id=779 AND orig=$userid AND status=1")->find();
		$list[4]=$info_4 ? 1 : 0 ;
		//第二阶段投票
		$info_5=$club_vote_mod->where("userid=$userid AND cate_id=778 AND if_weibo=1")->find();
		$list[5]=$info_5 ? 1 : 0 ;
		$return=array("finished"=>array(),"unfinished"=>array());
		foreach($list as $key=>$val){
			if($val==1){
				$return['finished'][]=$key;
			}else{
				$return['unfinished'][]=$key;
			}
		}
		return $return;
	}
	
	/**
	 * 关注benefit
	 * @author penglele
	 */
	public function getUserFollowBeneft($userid){
		if(!$userid){
			return false;
		}
		$follow_mod=M("Follow");
		$where['userid']=$userid;
		$where['whoid']=1;
		$where['type']=3;
		$if_follow=$follow_mod->where($where)->find();
		if(!$if_follow){
			$where['addtime']=time();
			$follow_mod->add($where);
		}
		return true;
	}
	
	/**
	 * 获取benefit首页推荐产品
	 * @param  $cid
	 */
	public function getBenefitRecommendInfo($cid){
		$info=M("Article")->where("cate_id=$cid AND status=1")->order("ordid DESC,id DESC")->find();
		if($info){
			$pro_info=M("Products")->field("pimg,goodssize,goodsprice")->where("pid=".$info['orig'])->find();
			$effect=D("Products")->getProductsEffectByPid($info['orig']);
			$info['img']=$pro_info['pimg'];
			$info['price']=$pro_info['goodsprice'];
			$info['psize']=$pro_info['goodssize'];
			$info['effect']=$effect[1];
			$info['url']=getProductUrl($info['orig']);
		}
		return $info;
	}
	
	/**
	 * 更新用户benefit活动的总积分
	 * @author penglele
	 */
	public function getBenefitTotalScore($userid){
		if($userid){
			$sql="SELECT SUM(c.credit_value) AS score FROM user_credit_stat c,user_credit_stat_extend e WHERE c.userid=$userid AND credit_type=1 AND c.id=e.id AND e.remark LIKE '完成贝玲妃俱乐部%'";
			$query=$this->query($sql);
			$score= $query[0]['score'] ? $query[0]['score'] : 0 ;
			$score=(int)$score;
			if($score>0){
				$club_mod=M("ClubUsers");
				$where['userid']=$userid;
				$where['name']="benefit";
				$if_club=$club_mod->where($where)->find();
				if($if_club){
					$club_mod->where($where)->save(array("score"=>$score));
				}else{
					$where['score']=$score;
					$where['addtime']=time();
					$club_mod->add($where);
				}
			}
		}
	}
	
	
}?>