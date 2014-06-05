<?php

/**
 * 品牌活动控制器
 * @author penglele
 */

class clubAction extends commonAction{
	
	/**
	 * benefit活动首页
	 * @author penglele
	 */
	public function benefit(){
		$userid=$this->userid;
		$club_benefit_mod=D("ClubBenefit");
		$article_mod=D("Article");
		$club_benefit_mod->getUserOfFollowBenefitInfo($userid);
		$return['userinfo']=$this->userinfo;
		//贝粉积分排行榜
		$return['scorelist']=$club_benefit_mod->getUserListByBrandScore(8);
		//首页焦点图
		$focuslist=$article_mod->getArticleList(780,1);
		$return['focus_info']=$focuslist[0];
		//首页推荐产品-最新
		$return['new_info']=$club_benefit_mod->getBenefitRecommendInfo(781);
		//首页推荐产品-最热
		$return['hot_info']=$club_benefit_mod->getBenefitRecommendInfo(782);
		//用户排行信息
		if($userid){
			$return['benefit_info']=$club_benefit_mod->getUserStatusOfBenefit($userid);
			$if_follow=M("Follow")->where("userid=".$userid." AND whoid=1 AND type=3")->find();
			$return['if_follow']=$if_follow ? 1 : 0 ;
		}
		$club_benefit_mod->getBenefitTotalScore($userid);
		
		//品牌资讯
		$return['brand_list']=$article_mod->getBrandInfoList(1,8);
		$return['title']="贝玲妃品牌俱乐部-".C("SITE_NAME");
		$return['returnurl']=urlencode(U("club/benefit"));
		$this->assign("return",$return);
		$this->display("benefit_new");
	}
	
	/**
	 * benefit活动-完美任务
	 * @author penglele
	 */
	public function benefit_task(){
		$type =$_GET['type'];
		$type =$type ? $type :3 ;
		$type = $type>3 ? 1 : $type ; 
		$club_benefit_mod=D("ClubBenefit");
		$userid=$this->userid;
		$club_benefit_mod->getUserOfFollowBenefitInfo($userid);
		$return['returnurl']=urlencode(U("club/benefit_task",array('type'=>$type)));
		switch ($type){
			case 1 :
				$sdate="2014-01-08";
				$edate="2014-01-27";
				$ndate=date("Y-m-d");
				//用户是否可以参加第二阶段的任务
				if($ndate>=$sdate && $ndate<=$edate){
					$return['if_can']=1;
				}else{
					$return['if_can']=0;
				}
				$return['list']=$club_benefit_mod->getBenefitVoteList(777,$userid,1);
				$user_stat=M("Article")->where("orig=$userid AND cate_id=779 AND add_time<='2014-01-27 23:59:59"."'")->find(); //是否参加过第一阶段上传任务
				$return['if_join']=0;
				if($user_stat){
					$return['if_join'] = $user_stat['status']==1 ? 1 : 2 ;
				}
				$template="benefit_task";
				break;
			case 2 :
				$sdate="2014-01-28";
				$edate="2014-02-21";
				$ndate=date("Y-m-d");
				//用户是否可以参加第二阶段的任务
				if($ndate>=$sdate && $ndate<=$edate){
					$return['if_can']=1;
				}else{
					$return['if_can']=0;
				}
				$return['if_join']=0;
				$user_stat=M("Article")->where("orig=$userid AND cate_id=779 AND add_time<='2014-02-21 23:59:59'")->find();
				if($user_stat){
					if($user_stat["status"]==1)
						$return['if_join']=1; //已经参加第二阶段，并通过审核
					else
						$return['if_join']=2; //已经参加第二阶段，未通过审核
				}
				$template="benefit_task_2";
				break;
			case 3 :
				$sdate="2014-02-24";
				$edate="2014-03-07";
				$ndate=date("Y-m-d");
				$return['list']=$club_benefit_mod->getBenefitVoteList(778,$userid,2);//投票列表
				$return['toplist']=$club_benefit_mod->getUserVoteListOfTop10();//投票排行榜
				if($ndate>=$sdate && $ndate<=$edate){
					$return['if_can']=1; //2014-3-8 放开
				}else{
					$return['if_can']=1;
				}
				$template="benefit_task_3";
				break;
		}
		$return['title']="完美任务-贝玲妃品牌俱乐部-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display($template);
	}
	
	/**
	 * benefit活动--伪装宝贝
	 * @author penglele
	 */
	public function benefit_products(){
		$userid=$this->userid;
		// 全部产品
		$pagesize = 10;
		$pro_mod = D ( "Products" );
		$count = $pro_mod->getProductNumByBrandid ( 1 );
		$list = $pro_mod->getProductsListByBrandid ( 1, $this->getlimit ( $pagesize ), "sort_num DESC,pid DESC" );
		$template = "club:benefit_products_ajaxlist";

		$param = array(
				"total" =>$count ,
				'result'=>$list ,			//分页用的数组或sql
				'listvar'=>'list',			//分页循环变量
				'listRows'=>$pagesize,			//每页记录数
				'target'=>'ajax_content',	//ajax更新内容的容器id，不带#
				'pagesId'=>'page',		//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>$template,//ajax更新模板
				);
		$this->page($param);
		$return['title']="伪妆宝贝-贝玲妃品牌俱乐部-".C("SITE_NAME");
		$return['ac']=$ac;
		$this->assign("return",$return);
		$this->display();
	}

	/**
	 * benefit活动--品牌信息
	 * @author penglele
	 */
	public function benefit_detail(){
		$userid=$this->userid;
		$club_mod=D("ClubBenefit");
		$article_mod=D("Article");
		$club_mod->getUserOfFollowBenefitInfo($userid);
		//品牌资讯
		$return['brand_list']=$article_mod->getBrandInfoList(1,8);
		$return['list']=$this->getFollowListOfBrand(1);
		$return['num']=M("Follow")->where("whoid=1 AND type=3")->count();//总人数
		$return['title']="品牌信息-贝玲妃品牌俱乐部-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * 新品上市（add by zhenghong 2014-01-02)
	 * @author zhenghong
	 */
	public function benefit_market(){
		$user_share_mod=D("UserShare");
		$shareid_list=array(86582,86583,86584,86585,86586,86587,86588,87640,87641,87638);
		$share_data=$user_share_mod->getShareListByIdList($shareid_list);
		$return['title']="新品上市-贝玲妃品牌俱乐部-".C("SITE_NAME");
		$this->assign("list",$share_data);
		$this->assign("return",$return);
		$this->display();
	}
	
	
	/**
	 * 获取关注品牌的列表
	 * @param  $bid 品牌ID
	 */
	public function getFollowListOfBrand($bid,$limit=16){
		if(!$bid){
			$bid=$_POST['bid'];
		}
		if(!$limit){
			$limit=$_POST['lim'] ? $_POST['lim'] : 16 ;
		}
		$list=D("Follow")->getFansUserList(array("whoid"=>$bid,"type"=>3),$limit,"","userid","rand()");
		if($this->isAjax()){
			$this->ajaxReturn(1,$list,1);
		}else{
			return $list;
		}
	}
	
	/**
	 * benefit活动--我的足迹
	 * @author penglele
	 */
	public function benefit_track(){
		$userid=$this->userid;
		if(!$userid){
			header("location:".U("club/benefit"));exit;
		}
		$club_mod=D("ClubBenefit");
		$club_mod->getUserOfFollowBenefitInfo($userid);
		//用户对benefit产品发的分享
		$return['sharelist']=$club_mod->getUserShareListOfBrand($userid);
		//用户赞的分享（to贝玲妃）
		$return['agreelist']=$club_mod->getUserAgreeShareListOfBrand($userid);
		//用户试用过的benefit产品
		$return['trylist']=$club_mod->getProductsListOfUserHaveTry($userid);
		
		$return['benefit_info']=$club_mod->getUserStatusOfBenefit($userid);//用户排名情况
		$return['userinfo']=$this->userinfo;
		$return['title']="我的足迹-贝玲妃品牌俱乐部-".C("SITE_NAME");
		$return['task']=$club_mod->getUserTaskList($userid);
		$if_follow=M("Follow")->where("userid=".$userid." AND whoid=1 AND type=3")->find();
		$return['if_follow']=$if_follow ? 1 : 0 ;
		$club_mod->getBenefitTotalScore($userid);
		$this->assign("return",$return);
		$this->display();
	}
	
	/**
	 * benefit投票
	 * @author penglele
	 */
	public function benefit_vote(){
		$userid=$this->userid;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$bind=D("UserOpenid")->getBindDetail($userid);
		//当用户绑定新浪微博时
		if((int)$bind['sina']!=1){
			$backurl=urlencode(U("club/benefit_task"));
			$this->ajaxReturn(0,"对不起，参与投票请先绑定新浪微博！<br/>现在就<a href='/user/sina_lock.html?returnurl=$backurl' class='A_line3'>绑定微博</a>吧",0);
		}
		$club_mod=D("ClubBenefit");
		$type=$_POST['type'];// ++++++++++++++++++++++++++++++++++++
		$aid=$_POST['aid'];//++++++++++++++++++++++++++++++++++++++
		$if_weibo=$_POST['weibo'];//是否需要转发到微博 ++++++++++++++++++++
		$time_arr=$club_mod->getVoteTime($type);
		$club_mod->getUserFollowBeneft($userid);
		if(!$aid || !$time_arr){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$article_mod=M("Article");
		$cate_id=$time_arr['id'];
		//查看投票的对象是否存在
		$if_info=$article_mod->where("id=".$aid." AND cate_id=".$cate_id." AND status=1")->find();
		if(!$if_info){
			$this->ajaxReturn(0,"非法操作",0);
		}
		
		$ndate=date("Y-m-d");
		//判断活动是否有效
		if($ndate<$time_arr['time']['s']){
			$this->ajaxReturn(0,"活动还没开始",0);
		}
		if($ndate>$time_arr['time']['e']){
			$this->ajaxReturn(0,"投票活动已经结束了，感谢您的参与！",0);
		}
		$vote_mod=M("ClubBenefitVotestat");
		$data['userid']=$userid;
		$data['target_id']=$aid;
		$data['cate_id']=$cate_id;
		$if_vote=$vote_mod->where($data)->find();
		if($if_vote){
			$this->ajaxReturn(0,"您已投过票，不能重复操作",0);
		}
		
		$data["if_weibo"]=0;
		$data['vote_datetime']=date("Y-m-d");
		if($type==1 && $if_weibo==0){
			$data["if_weibo"]=-1;
		}
		$rel=$vote_mod->add($data);
		if($rel){
			//第一次投票，给用户加100积分
			$times=$vote_mod->where("userid=$userid AND cate_id=".$cate_id)->count();
			if($times==1){
				//第一次投票时给用户加100积分
				D("UserCreditStat")->addUserCreditStat($userid,"完成贝玲妃俱乐部投票任务",100);
				$where['userid']=$userid;
				$where['name']="benefit";
				$club_user_mod=M("ClubUsers");
				$if_user=$club_user_mod->where($where)->find();
				if($if_user){
					$club_user_mod->where($where)->setInc("score",100);
				}else{
					$where['score']=100;
					$where['addtime']=time();
					$club_user_mod->add($where);
				}
			}
			
			if($type==2){
				//第二次投票需要加到article表中+++++++++++++++++++++++++++++
				$article_mod->where("id=".$if_info['id'])->setInc("abst",1);
			}
			$bind=D("UserOpenid")->getBindDetail($userid);
			$this->ajaxReturn($bind['sina'],"success",1);
		}else{
			$this->ajaxReturn(0,"投票失败，请稍后重试！",0);
		}
	}
	
	/**
	 * 投票转发到微博
	 * @author penglele
	 */
	public function vote_to_weibo(){
		$userid=$this->userid;
		if($userid){
			$bind=D("UserOpenid")->getBindDetail($userid);
			//当用户绑定新浪微博时
			if((int)$bind['sina']==1){
				$data['cate_id']=$_POST['cate_id'];//++++++++++++++++++++++++++++++
				$data['target_id']=$_POST['aid'];// +++++++++++++++++++++++++++++++
				$data['userid']=$userid;
				$vote_mod=M("ClubBenefitVotestat");
				$if_vote=$vote_mod->where($data)->find();
				if($if_vote && $if_vote['if_weibo']==0){
					$type=$_POST['type'];
					$time_arr=D("ClubBenefit")->getVoteTime($type);
					$ndate=date("Y-m-d");
					if($ndate<$time_arr['time']['s']){
						$this->ajaxReturn(0,"活动还没开始",0);
					}
					if($ndate>$time_arr['time']['e']){
						$this->ajaxReturn(0,"活动已结束",0);
					}
					$img=$_POST['img'];//此处内容需要填充
					if(!$img){
						$img=PROJECT_URL_ROOT."public/images/weibo.jpg";
					}else{
						$img=PROJECT_URL_ROOT.$img;
					}
					if($type==1){
						$title=M("Article")->where("id=".$data['target_id'])->getField("title");
						$content="#gimme brow挑战丰盈靓女#稀疏的眉毛？or扁平的胸部？NONONO！我认为".$title."，你呢？快来“投”你所好，贝玲妃gimme brow眉梦成真丰眉膏正品等你拿！上传眉毛稀疏和丰盈对比照更有千元大奖！@Lolitabox @benefit贝玲妃的微博 ";
					}else{
						//第二阶段转发到微博的内容
						$title=M("Article")->where("id=".$data['target_id'])->getField("title");
						$content="#gimme brow眉梦成真#快来看看gimme brow神奇丰盈术!我认为".$title."的眉型更加丰盈立体.快来为你心中的丰盈靓女投上一票,帮她眉梦成真! @Lolitabox @benefit贝玲妃的微博。新的一年,告别暗淡稀疏眉,让benefit眉梦成真丰眉膏帮你实现丰盈立体的好运眉!";
					}

					import("ORG.Util.String");
					import("ORG.Util.Input");
					$weibo_content=Input::deleteHtmlTags($content);
					$weibo_content=String::msubstr($weibo_content,0,300);
					$to_sina=$this->postSinaWeibo($userid, $weibo_content,$img,U("club/benefit_task",array('type'=>'3')));
					if($to_sina){
						$vote_mod->where($data)->save(array("if_weibo"=>1));
						$weibo_num=$vote_mod->where("userid=$userid AND cate_id=".$data['cate_id']." AND if_weibo=1")->count();//已转发到微博的总数，积分赠送不超过5次
						if($weibo_num<=5){
							D("UserCreditStat")->addUserCreditStat($userid,"完成贝玲妃俱乐部转发任务",5);
							M("ClubUsers")->where("userid=$userid AND name='benefit'")->setInc("score",5);
						}
						$this->ajaxReturn(1,"success",1);
					}
				}else{
					$this->ajaxReturn(0,"fail",0);
				}
			}else{
				$this->ajaxReturn(100,"fail",0);
			}
		}else{
			$this->ajaxReturn(0,"您还没有登录",0);
		}
	}
	
	/**
	 * benefit活动-我要参加
	 * @author penglele
	 */
	public function join_benefit(){
		$userid=$this->userid;
		$userinfo=$this->userinfo;
		if(!$userid){
			$this->ajaxReturn(0,"您还没有登录",0);
		}
		$time_arr=array(
				1=>array(
						's'=>"2013-01-08",
						'e'=>"2014-01-27"
						),
				2=>array(
						's'=>"2014-01-28",
						'e'=>"2014-02-21"
						)
				);
		$key=$_POST['key'];//活动的阶段
		$cate_id=779;
		
		if(!key_exists($key,$time_arr) || !$key){
			$this->ajaxReturn(0,"非法操作",0);
		}
		$time_info=$time_arr[$key];
		$ndate=date("Y-m-d");
		$time=date("Y-m-d H:i:s");
		//对活动时间的判断
		if($ndate<$time_info['s']){
			$this->ajaxReturn(0,"活动还没开始",0);
		}
		if($ndate>$time_info['e']){
			$this->ajaxReturn(0,"活动已结束",0);
		}
		$data['img']=$_POST['img1'];
		$data['bigimg']=$_POST['img2'];
		if(!$data['img'] || !$data['bigimg']){
			$this->ajaxReturn(0,"请先上传图片",0);
		}
		D("ClubBenefit")->getUserFollowBeneft($userid);
		$article_mod=M("Article");
		//第一阶段的报名信息
		$if_join1=$article_mod->where("orig=$userid AND cate_id=".$cate_id." AND add_time<='".$time_arr[1]['e']."23:59:59"."'")->find();
		//对于第一阶段的报名活动
		if($key==1){
			if($if_join1){
				//第一阶段已有“我要参加”信息存在
				if($if_join1['status']==1){
					//已审核，不能重复提交
					$this->ajaxReturn(100,"fail",0);
				}else{
					$rel=$article_mod->where("id=".$if_join1['id'])->save($data);
					if($rel){
						$this->ajaxReturn(1,"success",1);
					}else{
						$this->ajaxReturn(0,"操作失败，请稍后重试",0);
					}
				}
			}else{
				//没有参加过第一阶段的报名
				$data['orig']=$userid;
				$data['cate_id']=$cate_id;
				$data['add_time']=$time;
				$data['title']=$userinfo['nickname']."-贝玲妃修眉资料，ID：".$userid;
				$rel=$article_mod->add($data);
				if($rel){
					$this->ajaxReturn(1,"success",1);
				}else{
					$this->ajaxReturn(0,"操作失败，请稍后重试",0);
				}
			}
		}else{
			//第二阶段的报名
			if($if_join1 && $if_join1['status']==1){
				//对于已参加第一阶段的活动且已审核通过的则不能再参加第二阶段的报名活动
				$this->ajaxReturn(200,"已参加第一阶段的活动且已审核通过的则不能再参加第二阶段的报名",0);
			}else{
				//用户是否有第二阶段的报名
				$if_join2=$article_mod->where("orig=$userid AND cate_id=".$cate_id." AND add_time>='".$time_arr[2]['s']." 00:00:00"."'")->find();
				if($if_join2){
					//第二阶段已报名
					if($if_join2['status']==1){
						//已审核，不能重复提交
						$this->ajaxReturn(100,"已经参加过第二阶段活动，并通过审核",0);
					}else{
						$rel=$article_mod->where("id=".$if_join2['id'])->save($data);
						if($rel){
							$this->ajaxReturn(1,"success",1);
						}else{
							$this->ajaxReturn(0,"操作失败，请稍后重试",0);
						}
					}
				}else{
					//没有参加过第二阶段的报名
					$data['orig']=$userid;
					$data['cate_id']=$cate_id;
					$data['add_time']=$time;
					$data['title']=$userinfo['nickname']."-贝玲妃修眉资料，ID：".$userid;
					$rel=$article_mod->add($data);
					if($rel){
						$this->ajaxReturn(1,"success",1);
					}else{
						$this->ajaxReturn(0,"操作失败，请稍后重试",0);
					}
				}
			}
		}
	}
	
	function user_follow_benefit(){
		$userid=$this->userid;
		$rel=D("ClubBenefit")->getUserFollowBeneft($userid);
		$this->ajaxReturn(1,"success",1);
	}
	
	
	
	/***
	 * 伪妆清单-benefit stay flawless
	 * 活动入口 
	 */
	public function  benefit_myflawless(){
		$step=$this->checkMyflawlessStep();
		switch ($step) {
			case 1:
				//第一步：选择分享伪妆清单
				//伪妆选择产品范围
				$product_range="47,62893,42,6676,12,63118,6979,6982";
				$product_mod=M("Products");
				$where="pid IN (" . $product_range . ")";
				$product_list=$product_mod->field("pid,pname,pimg")->where($where)->select();
				$this->assign("SelectList",$product_list); //显示我的伪妆清单
				break;
				
			case 2:
				//第二步：宝盒开奖
				$userid=$this->userid;
				$club_benefit_flawless_mod=M("ClubBenefitFlawless");
				$where["userid"]=$userid;
				$myflawless_info=$club_benefit_flawless_mod->where($where)->find();
				$mylist=$myflawless_info["mylist"];
				$product_mod=M("Products");
				$where="pid IN (" . $mylist . ")";
				$product_list=$product_mod->field("pid,pname,pimg")->where($where)->select();
				$this->assign("MyList",$product_list); //显示我的伪妆清单
				
				unset($where);
				
				$club_prize_mod=M("ClubPrize");
				$where["groupname"]="伪妆清单";
				$where["userid"]=$userid;
				//$where["ifwin"]=1;
				$prizelist=$club_prize_mod->where($where)->order("dtime DESC")->select();
				$opennum=count($prizelist); //用户开盒次数
				$where["prizeid"]=2;
				$realprize_count=$club_prize_mod->where($where)->count(); //判断是否有实物奖品
				$this->assign("OpenNum",$opennum); //显示用户开奖次数
				$this->assign("PrizeList",$prizelist); //显示我的开奖记录
				$this->assign("RealPrizeCount",$realprize_count); //统计实物奖品数量
				break;
				
		}
		$this->assign("STEP",$step);
		$this->display();
	}
	
	//分享我的伪妆清单到新浪微博--AJAX
	public function myflawless_share(){
		if(date("Y-m-d")<'2014-03-16'){
			$this->ajaxReturn(0,"哇哦！活动还没开始呢！活动将在2014年3月16日开始，敬请您的参与！",0);
		}
		
		if(date("Y-m-d")>'2014-03-31'){
			$this->ajaxReturn(0,"哇哦！活动已经结束啦！",0);
		}
		
		$mylist=$_REQUEST["mylist"];
		//需要判断伪妆清单是否为空
		if(empty($mylist) || count(explode(",",$mylist))<1) {
			$this->ajaxReturn(0,"您还没有选择自己的伪妆清单！",0);
		}
		$userid=$this->userid;
		if($userid){
			$bind=D("UserOpenid")->getBindDetail($userid);
			//当用户绑定新浪微博时
			if((int)$bind['sina']==1){
				$content="#订制持久伪妆宝盒 完美一整天#眼看夏天离我们越来越近，最担心的脱妆问题该如何解决？萝莉盒Lolitabox联手伪妆教主贝玲妃教你持久伪妆秘笈，快来开启你的伪妆宝盒，200份贝玲妃新品Stay flawless无瑕持久定妆底霜等你来拿！更有持久伪妆套装大奖以及海量萝莉盒积分送哦！";
				import("ORG.Util.String");
				import("ORG.Util.Input");
				$weibo_content=Input::deleteHtmlTags($content);
				$weibo_content=String::msubstr($weibo_content,0,300);
				$img="http://www.lolitabox.com/data/userdata/2014/03/12/1394609412551.jpg"; //分享伪妆清单时的配图2
				$to_sina=$this->postSinaWeibo($userid, $weibo_content,$img,U("club/benefit_myflawless"));
				if($to_sina){
					//成功转发到新浪微博
					$club_benefit_flawless_mod=M("ClubBenefitFlawless");
					$where["userid"]=$userid;
					if(!$club_benefit_flawless_mod->where($where)->find()){
						D("UserCreditStat")->addUserCreditStat($userid,"完成贝玲妃俱乐部分享伪妆清单任务",100); //发送积分给用户
						M("ClubUsers")->where("userid=$userid AND name='benefit'")->setInc("score",100); //重新统计用户BENEFIT CLUB积分
						$data["userid"]=$userid;
						$data["mylist"]=$mylist;
						$data["dtime"]=date("Y-m-d H:i:s");
						$club_benefit_flawless_mod->add($data);
					}
					$this->ajaxReturn(1,"完成贝玲妃俱乐部分享伪妆清单任务",1);
				}
				else {
					$this->ajaxReturn(0,"非常抱歉，可能是新浪微博平台故障或您的账号有问题，暂时无法完成分享转发操作！",0);
				}
			}
			else{
				$this->ajaxReturn(0,"您未绑定新浪微博或绑定授权已经过期！",0);
			}
		}else{
			$this->ajaxReturn(0,"您还没有登录",0);
		}
	}

	//开启宝盒操作--AJAX
	public function myflawless_openbox(){
		if(date("Y-m-d")>'2014-03-31'){
			$this->ajaxReturn(0,"哇哦！活动已经结束啦！",0);
		}
		$userid=$this->userid;
		if($userid){
			$prize_count=$this->getUserPrizeRsCount("伪妆清单",$userid); //当前用户抽奖次数
			if($prize_count<1) {
				//第一次抽奖不扣积分
			}
			else {
				//判断用户积分是否够扣减
				$userinfo=$this->userinfo;
				$usercredit=$userinfo["score"]; //用户当前积分
				if($usercredit<50) {
					$this->ajaxReturn(0,"<p align=center>对不起，您目前没有足够的积分用于开启宝盒，先去赚些积分吧。<br><br>了解一下：<a href='/info/lolitabox/aid/1235.hthm' target='_blank'>如何获得积分 </p>",0);
				}
				D("UserCreditStat")->addUserCreditStat($userid,"贝玲妃俱乐部-完美任务-开启宝盒扣减积分",-50); //扣减积分
			}
			$prize_arr=$this->getMyflawlessPrizeSet();
			
			$club_prize_mod=M("ClubPrize");
			
			foreach ($prize_arr as $key => $val) {
				$arr[$val['id']] = $val['v'];
			}
			/*根据已经抽出的奖品数对比计划出奖数，动态调整出奖概率，当已出奖数>=计划出将数将，将其出奖率设置为0，不现出奖*/
			if($this->getOutPrizeRsCount("伪妆清单","2")>=50) {
				$arr[2]=0; //贝玲妃Stay Flawless无瑕持久    定妆底霜试用装
			}
			if($this->getOutPrizeRsCount("伪妆清单","3")>=1000) {
				$arr[3]=0; //萝莉盒积分
			}
			/*根据已经抽出的奖品数对比计划出奖数，动态调整出奖概率，当已出奖数>=计划出将数将，将其出奖率设置为0，不现出奖*/

			$rid = $this->getPrizeRand($arr); //根据概率获取奖项id
			$res['id'] = $prize_arr[$rid-1]['id']; //中奖ID
			$res['prizename'] = $prize_arr[$rid-1]['prizename']; //中奖奖品
			if($res["id"]==2 || $res["id"]==3) {
				$return_result="哇哦！太幸运了！恭喜您获得<b>".$res["prizename"]."</b>";
				if($res["id"]==2) {
					$return_result.="<br>请填写并设置默认收货地址，奖品将在活动结束后统一寄出(<a href='".U('home/address')."' target='_blank'>现在填写</a>)。";
				}
				if($res["id"]==3) {
					D("UserCreditStat")->addUserCreditStat($userid,"参与贝玲妃俱乐部完美任务活动，开启宝盒后幸运获得50个积分。",50); //奖励积分
				}
				$return_result.="<br>再试一次？只需50个萝莉盒积分。<br>";
			}
			else {
				$return_result="啊哦！手气差了一点点，宝盒是空的！<br>再试一次？只需50个萝莉盒积分。<br>";
			}
			
			/**开盒记录入库**/
			$user_prize_data["userid"]=$userid;
			$user_prize_data["groupname"]="伪妆清单";
			$user_prize_data["prizeid"]=$res['id'];
			$user_prize_data["prizetitle"]=$res['prizename'];
			$user_prize_data["remark"]="参与".$user_prize_data["groupname"];
			if($res['id']==4) {
				$user_prize_data["ifwin"]=0;
			}
			else {
				$user_prize_data["ifwin"]=1;
			}
			$user_prize_data["dtime"]=date("Y-m-d H:i:s");
			
			$club_prize_mod->add($user_prize_data);
			/**开盒记录入库**/
			//$this->myflawless_openbox_share();
			$this->ajaxReturn(1,$return_result,1);
			
		}
		else
		{
			$this->ajaxReturn(0,"您还没有登录",0);
		}
	}
	
	//将打开盒子的动作转发传播到微博，无返回--AJAX
	public function myflawless_openbox_share(){
		$userid=$this->userid;
		$bind=D("UserOpenid")->getBindDetail($userid);
		//当用户绑定新浪微博时
		if((int)$bind['sina']==1){
			//$content="#订制持久伪妆宝盒 完美一整天#伪妆教主贝玲妃最新宝贝Stay flawless无瑕持久定妆底霜闪亮登场,不再为脱妆而烦恼!快来萝莉盒转发微博即可开启伪妆宝盒,赢benefit新品!3月16日起,到贝玲妃官网商城注册成为会员,就可享受全网包邮;购买美妆礼盒,不仅有优惠还可免费送礼!";
			$content="#订制持久伪妆宝盒 完美一整天#伪妆教主贝玲妃最新宝贝Stay flawless无瑕持久定妆底霜闪亮登场,不再为脱妆而烦恼！快来萝莉盒进行玩美任务,赢benefit新品! 现在进入benefit官网私人定制,精心挑选你的style,搭配最合心意的礼盒,同时购买享有更多优惠和好礼,登录购买即享包邮!";
			import("ORG.Util.String");
			import("ORG.Util.Input");
			$weibo_content=Input::deleteHtmlTags($content);
			$weibo_content=String::msubstr($weibo_content,0,300);
			$img="http://www.lolitabox.com/data/userdata/2014/03/12/1394609457983.jpg"; //分享伪妆清单时的配图2
			$to_sina=$this->postSinaWeibo($userid, $weibo_content,$img,U("club/benefit_myflawless"));
		}
	}
	
	//获取奖品记录数
	private function getUserPrizeRsCount($groupname,$userid) {
		$where["groupname"]=$groupname;
		$where["userid"]=$userid;
		$club_prize_mod=M("ClubPrize");
		return $club_prize_mod->where($where)->count();
	}

	//获取已出奖品数
	private function getOutPrizeRsCount($groupname,$prizeid) {
		$where["groupname"]=$groupname;
		$where["prizeid"]=$prizeid;
		$club_prize_mod=M("ClubPrize");
		return $club_prize_mod->where($where)->count();
	}	
	
	//检测用户应该停留的页面步骤
	private function checkMyflawlessStep(){
		$userid=$this->userid;
		if(!$userid) return 1;
		$club_benefit_flawless_mod=M("ClubBenefitFlawless");
		$where["userid"]=$userid;
		if($club_benefit_flawless_mod->where($where)->find()){
			return 2;
		}
		else {
			return 1;
		}
	}
	
	//获取奖品设置列表
	private function getMyflawlessPrizeSet(){
		$prize_set=array(
				"0"=>array("id"=>1,"prizename"=>"贝玲妃Flawless持久伪妆套装","v"=>0,"showcount"=>5,"plancount"=>0,"ifobject"=>1),
				"1"=>array("id"=>2,"prizename"=>"贝玲妃Stay Flawless无瑕持久定妆底霜试用装","v"=>10,"showcount"=>200,"plancount"=>50,"ifobject"=>1),
				"2"=>array("id"=>3,"prizename"=>"萝莉盒50积分","v"=>30,"showcount"=>"不限","plancount"=>200,"ifobject"=>0),
				"3"=>array("id"=>4,"prizename"=>"手气差了一点点，宝盒是空的！","v"=>60,"showcount"=>"不限","plancount"=>1000,"ifobject"=>0),
				);
		
		//这里缺少动态调整中奖
		return $prize_set;
	}
	
	//获奖概率
	function getPrizeRand($proArr) {
		$result = '';
		//概率数组的总概率精度
		$proSum = array_sum($proArr);
		//概率数组循环
		foreach ($proArr as $key => $proCur) {
			$randNum = mt_rand(1, $proSum);
			if ($randNum <= $proCur) {
				$result = $key;
				break;
			} else {
				$proSum -= $proCur;
			}
		}
		unset ($proArr);
		return $result;
	}
	
}
?>