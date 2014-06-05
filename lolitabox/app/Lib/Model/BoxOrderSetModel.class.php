<?php
/**
 * 盒子属性模型
 * @author penglele
*/
class BoxOrderSetModel extends Model {

	/**
	 * 判断用户的订单中是否含有子订单
	 * @param int  $boxid     盒子ID
	 * @param $paytime        支付的时间【完整】
	 * @param       $tday       支付的天[date("d)]
	 * @author penglele
	 */
	public function getBoxIfMonths($boxid,$paytime,$tday){
		$return['if_mon']=0;
		if(!$tday){
			//获取支付的天
			$tday=D("Public")->getPerDate($paytime,2);
		}
		$tday=(int)$tday;
		if($boxid){
			$info=$this->where("boxid=".$boxid)->find();
			//dump($info);exit;
			if($info && $info['months']!=0){
				
				//盒子订单属性的月份定义了数值
				$return['if_mon']=$info['months'];
				if($info['months']==12 && $info['if_quarter']==1){
					$return['if_mon']=4;
				}
				$post_day=$info['post_day']?$info['post_day']-5:0;//发货日期（day）
				$post_date=$info['post_date'];//自定义发货时间（date("Y-m-d")）
				
				$times=$info['months'];//月份数（季度数）转变成发货次数
				$list=array();
				$stime="";
				$smon=0;
				
				//**优化部分 add by zhenghong 2014-01-04 未完成待续 优化下面FOR循环**//
				switch($times){
					//年度订购订单
					case "12";
						if(!empty($post_date) && $info['if_quarter']==1){
							//按季度发货1，4，7，10，必须有$post_date
						}
						break;
					case "6";
						break;
					case "3":
						break;
					case "1":
						break;
					default:
						break;
				}
				//**优化部分 add by zhenghong 2014-01-04**//
				
				for($i=0;$i<$times;$i++){
					//当且仅当$times=12，并且if_quarter=1时才有按季度发货
					if($times==12 && $info['if_quarter']==1){
						//没有自定义发货日期
						if($post_day){
							if($post_day>=$tday){
								//支付时间在自定义发送日前
								if(!$stime){
									$stime=date("Y-m");//初始化开始日期
								}
								if($i%3==0){
									$smon=$i+3;//推断距离下一次时间的月数
								}
							}else{
								//支付时间在自定义发送日后
								if(!$stime){
									$stime=date("Y-m",strtotime("1 months"));//初始化开始日期
								}
								if($i%3==0){
									$smon=$i+4;//推断距离下一次时间的月数
								}
							}
							if($i%3==0){
								$list[]=$stime;
								$stime=date("Y-m",strtotime($smon." months"));//下一次的时间
							}
						}
						else
						{
							//自定义了发货日期
							if(!$stime){
								$postday_arr=explode("-",$post_date);
								$stime=$postday_arr[0]."-".$postday_arr[1];
								$start_date=$stime;
							}
							if($i%3==0){
								$smon=$i+3;//推断距离下一次时间的月数
								$list[]=$stime;
								$stime=date("Y-m",strtotime($start_date.$smon." months"));//下一次的时间
							}
						}
					}else{
						//定义发送日
						if($post_day){
							if($tday>$post_day){
								//支付时间在自定义发送日后
								$smon=$i+1;
							}else{
								$smon=$i;
							}
							$list[]=date("Y-m",strtotime($smon." months"));
						}else{
							//自定义发货日期
							if(!$stime){
								$postday_arr=explode("-",$post_date); //拆分自定义日期
								$stime=$postday_arr[0]."-".$postday_arr[1];
							}
							$list[]=$stime;
							$stime=date("Y-m",strtotime($stime."1 months"));
						}
					}
				}
				$return['list']=$list;
			}
		}
		return $return;
	}
	
	/**
	 * 获取订单的子订单中的详细信息
	 * @param array $tlist       订单时间的列表
	 * @param int $orderid     订单号
	 * @author penglele
	 */
	public function getBoxOrderList($tlist,$orderid){
		$list=array();
		if($tlist){
			$wordsend_mod=M("UserOrderSendword");
			$send_mod=M("userOrderSend");
			foreach($tlist as $val){
				$info=array();
				$time_arr=explode("-",$val);
				$info['tkey']=implode("",$time_arr);
				$info['tname']=$time_arr[0]."年".$time_arr[1]."月";
				$if_sendword=$wordsend_mod->where("orderid=$orderid AND child_id=".$info['tkey'])->find();//用户是否有赠言
				if($if_sendword && $if_sendword['content']){
					$info['if_sw']=1;
				}else{
					$info['if_sw']=0;
				}
				$send_info=$send_mod->where("orderid=".$orderid." AND child_id=".$info['tkey'])->find();
				//订单是否已发货
				if($send_info && $send_info['senddate'] && $send_info['proxysender']){
					$info['if_send']=1;
				}else{
					$info['if_send']=0;
				}
				$list[]=$info;
			}
		}
		return $list;
	}


}
?>