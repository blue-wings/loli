<?php
/**
 * 美丽档案模型
 * @author penglele
 */
class UserVoteModel extends Model {
	
	/**
	 * 获取美丽档案完善基本信息的题号
	 * @author penglele
	 */
	public function getUserProfileQID(){
		return 1;
	}
	
	/**
	 * 获取用户喜爱的品牌的题号
	 * @author penglele
	 */
	public function getUserLikeBrandQID(){
		return 26;
	}
	
	/**
	 * 美丽档案完善信息赠送积分
	 * @author penglele
	 */
	public function returnVoteScore(){
		return 2;
	}
	
	/**
	 * 获取美丽档案问题列表
	 * type 代表此题的答案类型【type=1单选，type=2多选，type=3select，type=4填空】
	 * stat  代表多选题的一种特殊类型
	 * @author penglele
	 */
	public function getQuestionList($userid){
		$userpro_qid=$this->getUserProfileQID();
		$like_qid=$this->getUserLikeBrandQID();
		$list=array(
				"$userpro_qid"=>array(
						"title"=>"完善基本信息"
						),
				"3"=>array(
						"title"=>"您的肤色是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"白皙",
								"B"=>"泛红",
								"C"=>"偏黄",
								"D"=>"暗沉",
								"E"=>"黑珍珠"
								)
						),
				"28"=>array(
						"title"=>"您的肤质是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"中性皮肤",
								"B"=>"油性皮肤",
								"C"=>"干性皮肤",
								"D"=>"混合性皮肤",
								"E"=>"敏感性皮肤"
						),
						"from_info"=>array(0=>"UserProfile",1=>"skin_property",2=>array("userid"=>$userid))
				),
				"6"=>array(
						"title"=>"您的肌肤问题有哪些？",
						"type"=>2,
						"answer"=>array(
								"A"=>"皮肤暗沉疲倦",
								"B"=>"干燥毛孔粗大",
								"C"=>"轻熟肌，出现细、幼纹",
								"D"=>"熟龄肌，松弛，出现皱纹",
								"E"=>"色斑肤色不均",
								"F"=>"我根本没有皮肤问题"
						)
				),
				"7"=>array(
						"title"=>"您没有使用过哪些护肤用品？",
						"type"=>2,
						"answer"=>array(
								"A"=>"美白",
								"B"=>"祛斑",
								"C"=>"控油",
								"D"=>"保养",
								"E"=>"去皱"
						)
				),				
				"29"=>array(
						"title"=>"您的发质是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"受损发质",
								"B"=>"干枯发质",
								"C"=>"油性发质",
								"D"=>"中性发质"
						),
						"from_info"=>array(0=>"UserProfile",1=>"hair_property",2=>array("userid"=>$userid))
				),				
				"10"=>array(
						"title"=>"您对香水的使用理念是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"每天使用，深知不使用香水的女人没钱途",
								"B"=>"偶尔使用，总是出门那一秒忘记",
								"C"=>"仅特定场合使用，为了吸引他或其它原因",
								"D"=>"不使用，那你完蛋了(Lolitabox敬上)"
						)
				),
				"11"=>array(
						"title"=>"您选择化妆品的主要依据是什么？",
						"type"=>2,
						"answer"=>array(
								"A"=>"使用效果",
								"B"=>"品牌知名度",
								"C"=>"口碑推荐",
								"D"=>"产品价格",
								"E"=>"产品包装",
								"F"=>"代言人或广告"
						)
				),				
				"13"=>array(
						"title"=>"您经常使用哪些化妆品？",
						"type"=>2,
						"answer"=>array(
								"A"=>"隔离霜/BB霜",
								"B"=>"粉底",
								"C"=>"腮红",
								"D"=>"眼影",
								"E"=>"眼线",
								"F"=>"睫毛膏",
								"G"=>"唇膏唇彩"
						)
				),				
				"15"=>array(
						"title"=>"您每月购置化妆品的费用是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"200以下",
								"B"=>"200-500",
								"C"=>"500-1000",
								"D"=>"1000以上"
						)
				),
				"18"=>array(
						"title"=>"您是如何知道LOLITABOX萝莉盒的？",
						"type"=>1,
						"answer"=>array(
								"A"=>"家人朋友",
								"B"=>"书刊杂志",
								"C"=>"网络",
								"D"=>"其他"
						)
				),
				"19"=>array(
						"title"=>"您的时尚潮流信息通常来自哪里？",
						"type"=>2,
						"answer"=>array(
								"A"=>"家人朋友",
								"B"=>"电视",
								"C"=>"平面广告",
								"D"=>"时尚杂志",
								"E"=>"品牌专柜",
								"F"=>"Blog/BBS/网络"
						)
				),
				"20"=>array(
						"title"=>"您在购买前希望试用哪些产品？",
						"type"=>2,
						"answer"=>array(
								"A"=>"护肤品",
								"B"=>"彩妆",
								"C"=>"指甲油",
								"D"=>"护发造型",
								"E"=>"香水盒香薰"
						)
				),
				"22"=>array(
						"title"=>"您最喜欢哪种穿衣风格？",
						"type"=>2,
						"answer"=>array(
								"A"=>"欧美范儿",
								"B"=>"日韩范儿",
								"C"=>"乡村范儿",
								"D"=>"本土范儿",
								"E"=>"港台范儿",
								"F"=>"混搭一气"
						)
				),
				"24"=>array(
						"title"=>"您购买化妆品最常用的途径是？",
						"type"=>1,
						"answer"=>array(
								"A"=>"商场专柜",
								"B"=>"第三方售货网站（如京东）",
								"C"=>"品牌电子商务官方网站",
								"D"=>"淘宝",
								"E"=>"女性社区（如美丽说）",
								"E"=>"电视购物",
								"E"=>"开放式化妆品选购店（如丝芙兰）"
						)
				),
				"$like_qid"=>array(
						"title"=>"您最喜欢的品牌是？",
						"type"=>2,
						"stat"=>1
				),
				"27"=>array(
						"title"=>"分享您最想实现的小愿望给我们吧，没准就能实现了哦（*^_^*）",
						"type"=>4
				)
			);
		if($userid){
			//++++++++++++第一题，用户信息start++++++++++
			$userinfo=M("UserProfile")->field("sex,province,city,district,years,months,days,edu")->where("userid=$userid")->find();
			
			$list[$userpro_qid]['result']=$userinfo;
			//++++++++++++第一题，用户信息end++++++++++
			
			//++++++++++++第26题，用户信息start++++++++++
			$brandlist=D("Article")->getRemmendAdBrandList();
			$brand_arr=array();
			if($brandlist){
				foreach($brandlist as $val){
					$brand_arr[]=$val['name'];
				}
			}
			$list[$like_qid]['answer']=$brand_arr;
			
			//如果用户美丽档案中没有这一项的信息时，动态添加
			$followlist=M("Follow")->where("userid=$userid AND type=3")->select();
			$follow_arr=array();
			$brand_mod=M("ProductsBrand");
			foreach($followlist as $ekey=>$eval){
				$brand_name=$brand_mod->where("id=".$eval['whoid'])->getField("name");
				if(in_array($brand_name,$brand_arr)){
					$follow_arr[]=$brand_name;
				}
			}
			$list[$like_qid]['result']=$follow_arr;
			//++++++++++++第26题，用户信息end++++++++++
		}
		return $list;
	}
	
	/**
	 * 获取用户答题的完成情况【可以跳跃答题】
	 * @param int $userid
	 * @author penglele
	 */
	public function getUserIfFinished($userid){
		$questionlist=$this->getQuestionList($userid);//问题列表
		$keylist=array_keys($questionlist);//题号列表
		$start=$keylist[0];
		$finished=1;
		if($userid){
			$userpro_qid=$this->getUserProfileQID();
			$follow_qid=$this->getUserLikeBrandQID();
			foreach($keylist as $key=>$val){
				if($val==$userpro_qid){
					foreach($val['result'] as $ival){
						if(!$ival){
							exit("1");
							$finished=0;
							$start=$val;
							break;
						}
					}
				}elseif($val==$follow_qid){
					if(!$questionlist[$val]['result']){
						$finished=0;
						$start=$val;
						break;
					}
				}else{
					if($questionlist[$val]['from_info']){
						$model=$questionlist[$val]['from_info'][0];
						$info=M("$model")->where($questionlist[$val]['from_info'][2])->getField($questionlist[$val]['from_info'][1]);
						if(!$info){
							$finished=0;
							$start=$val;
							break;
						}
					}else{
						$voteinfo=$this->where("userid=$userid AND question=".$val)->find();
						
						if(!$voteinfo){
							$finished=0;
							$start=$val;
							break;
						}
					}
				}
			}
		}
		$return=array(
				"finished"=>$finished,
				"start"=>$start
				);
		return $return;
	}
	
	/**
	 * 获取题目的信息
	 * @param int $qid
	 * @return $return
	 * @author penglele
	 */
	public function getQuestionInfo($qid,$userid){
		$quetionlist=$this->getQuestionList($userid);
		$key_list=array_keys($quetionlist);
		$qid = in_array($qid,$key_list) ? $qid : $key_list[0] ;
		$return=array(
				"before"=>0,
				"after"=>0,
				"info"=>$quetionlist[$qid],
				"if_answer"=>0
				);
		$key=array_search($qid,$key_list);
		$before=$key-1;
		$after=$key+1;
		if($key!=0){
			$return['before']=$key_list[$before];
		}
		if(key_exists($after,$key_list)){
			$return['after']=$key_list[$after];
		}
		$anser_arr=$this->getVoteInfo($qid, $userid,$return['info']['type'],$return['info']['result'],$return['info']['from_info']);
		if($anser_arr['result']){
			$return['info']['result']=$anser_arr['result'];
		}
		$return['if_answer']=$anser_arr['if_answer'];
		if($anser_arr['other']){
			$return['other']=$anser_arr['other'];
		}
		return $return;
	}
	
	/**
	 * 获取美丽档案的答案
	 * @param int $qid
	 * @param int $userid
	 */
	public function getVoteInfo($qid,$userid,$type,$result,$from_info=array()){
		$userpro_qid=$this->getUserProfileQID();
		$follow_qid=$this->getUserLikeBrandQID();
		if($qid==$userpro_qid){
			$if_answer=1;
			foreach($result as $ikey=>$ival){
				if($ikey!="sex" && !$ival){
					$if_answer=0;
				}
			}
			$return['if_answer']=$if_answer;
			if($result){
				$result['years']=$result['years'] ? $result['years'] : 1988 ;
				$result['months']=$result['months'] ? $result['months'] : 1 ;
				$result['days']=$result['days'] ? $result['days'] : 1 ;
				$return['result']=$result;
			}
			$start_year=(int)date("Y");
			for($i=$start_year;$i>1960;$i--){
				$return['other']['yearlist'][]=$i;
			}
		}elseif($qid==$follow_qid){
			$return['if_answer'] = $result ? 1 : 0 ;
		}else{
			if($from_info){
				$model=$from_info[0];
				$field=$from_info[1];
				$where=$from_info[2];
				$get_info=M("$model")->where($where)->getField($field);
				if($get_info){
					$return['if_answer']=1;
					$return['result']=$get_info;
				}else{
					$return['if_answer']=0;
				}
			}else{
				$voteinfo=$this->where("userid=$userid AND question=".$qid)->getField("answer");
				$return['if_answer']=0;
				if($voteinfo){
					if($type!=2){
						//单选、select、填空
						$return['result']=$voteinfo;
					}else{
						$return['result']=explode(",",$voteinfo);
					}
					$return['if_answer']=1;
				}	
			}
		}
		return $return;	
	}
	
	
	
	/**
	 * 美丽档案提交答案
	 * @param int $qid
	 * @param int $userid
	 * @param  $answer
	 * @author penglele
	 */
	public function addUserVote($qid,$userid,$answer){
		$userpro_qid=$this->getUserProfileQID();//第一题的题号
		$follow_qid=$this->getUserLikeBrandQID();
		$return=0;
		if($qid==$userpro_qid){
			unset($answer['qid']);
			$data=$answer;
		}else{
			$data=$answer['data'];
		}
		if(!$qid || !$data || !$userid){
			return $return;
		}
		$score=$this->returnVoteScore();//默认积分值
		$question_info=$this->getQuestionInfo($qid,$userid);
		$score = $question_info['if_answer'] == 1 ? 0 : $score ;//用户答题完赠送的积分数
		
		if($qid==$userpro_qid){
			//如果是完善个人信息，同步到user_profile中
			$res=M("UserProfile")->where("userid=".$userid)->save($data);
			if($res!==false){
				$return=$question_info['if_answer']==1 ? 2 : 1 ;
			}
		}elseif($qid==$follow_qid){
			//我喜欢的品牌
			$bname_arr=explode(",",$answer['data']);
			if($bname_arr){
				$follow_mod=D("Follow");
				$brand_mod=M("ProductsBrand");
				
				//用户已选的品牌列表，添加到用户follow中
				foreach($bname_arr as $val){
					$bid=$brand_mod->where("name='".$val."'")->getField("id");
					if($bid){
						$follow_mod->addFollow($userid,$bid,3);
					}
				}
				
				//如果之前选择过品牌，但是此次修改时将其取消，则动态删除已关注状态[]
				if($question_info[info][result]){
					foreach($question_info[info][result] as $ival){
						if(!in_array($ival,$bname_arr)){
							$o_bid=$brand_mod->where("name='".$ival."'")->getField("id");
							$follow_mod->delFollow($userid,$o_bid,3);
						}
					}
				}
				
			}
			$return=$question_info['if_answer']==1 ? 2 : 1 ;
		}else{
			//其他情况
			if($question_info['info']['from_info']){
				$where=array($question_info['info']["from_info"][1]=>$data);
				$model=$question_info['info']["from_info"][0];
				$ree=M("$model")->where($question_info['info']["from_info"][2])->save($where);
				if($ree!==false){
					//修改成功
					$return=$question_info['if_answer']==1 ? 2 : 1 ;
				}
			}else{
				$where['answer']=$data;
				$if_voteinfo=$this->where("userid=$userid AND question=".$qid)->find();
				if($if_voteinfo){
					$ret=$this->where("id=".$if_voteinfo['id'])->save($where);
					if($ret!==false){
						//修改成功
						$return=2;
					}
				}else{
					$where['question']=$qid;
					$where['userid']=$userid;
					$rel=$this->add($where);
					if($rel){
						//添加成功
						$return=1;
					}
				}				
			}
		}
		if($score>0 && $return==1){
			//用户完成美丽档案赠送积分
			D("UserCreditStat")->addUserCreditStat($userid,"完善萝莉档案【".$question_info['info']['title']."】",$score);
		}
		return $return;
	}
	
	
	/**
	 * 整理美丽档案以前的多选数据
	 * @author penglele
	 */
	public function setUserVote(){
		$questionlist=$this->getQuestionList();
		foreach($questionlist as $key=>$val){
			if($val['type']==2){
				$list=$this->where("question=".$key)->select();
				foreach($list as $ikey=>$ival){
					$answer_arr=explode(",",$ival['answer']);
					$arr=array();
					foreach($answer_arr as $eval){
						if($eval){
							$arr[]=$eval;
						}
					}
					$answer_arr=implode(",",$arr);
					$this->where("id=".$ival['id'])->save(array("answer"=>$answer_arr));
				}
			}
		}
		echo "end";exit;
	}
	
	
	/**
	 * 获取美丽档案列表
	 * @author penglele
	 */
	public function getUserVoteList($userid){
		$list=$this->getQuestionList($userid);
		$num=0;
		foreach($list as $key=>$val){
			$vote_info=$this->getVoteInfo($key, $userid, $val['type'], $val['result'],$val["from_info"]);
			$list[$key]['if_answer']=$vote_info['if_answer'];
			if($vote_info['if_answer']==0){
				$num++;
			}
			if($vote_info['result']){
				$list[$key]['result']=$vote_info['result'];
			}
			if($vote_info['other']){
				$list[$key]['other']=$vote_info['other'];
			}
		}
		$return['list']=$list;
		$return['num']=$num;
		$return['score']=$this->returnVoteScore();
		return $return;
	}
	
	
	
	
	
}
?>