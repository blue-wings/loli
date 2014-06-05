<?php 
	/**
	 *关于生产优惠券的模型
	 */
class CouponModel extends Model {
		
	/**
	 * 随机产生优惠劵并加入到表中
	 * @param unknown_type $price
	 * @param unknown_type $title
	 * @param unknown_type $userid
	 * @param unknown_type $ordernum
	 * @param unknown_type $etime
	 * @return string|boolean
	 */
	public function addCoupon($price,$title="",$userid="",$ordernum="",$etime="",$mess=""){
		import ( "ORG.Util.String" );
		$coupon = String::randString ( 12, 5 );
		if (! $this->getByCode ( $coupon )) {
			$data ['code'] = $coupon;
			$data ['starttime'] = date ( "Y-m-d " )."00:00:00";
			if(!empty($etime)){
				$data ['endtime'] =$etime;
			}else{
				$data ['endtime'] = date ( "Y-m-d "."23:59:59", strtotime ( "3 month" ) );
			}
			$data ['status'] = 1;
			$data ['addtime'] = date("Y-m-d H:i:s");
			if(!empty($ordernum)){
				$data ['ordernmb'] = $ordernum;
			}
			$data ['price'] = $price;
			$data ['remark'] = $title;
			$data['owner_uid']=$userid;
			$this->add ( $data );
			$coupon_id = $this->getLastInsID ();
			
			//给用户发私信
			if($userid){
				$userinfo=D("Users")->getUserInfo($userid,"nickname");
				if(!empty($userinfo["nickname"])) {
					$stime=substr($data ['starttime'], 0,10);
					$etime=substr($data ['endtime'], 0,10);
					if(!$mess){
						$mess=$userinfo["nickname"]."你好，由于".$title."，获得 <b>".$price."</b> 元优惠券，已经发放到您的账户中，";
					}
					$msg=$mess."优惠券有效期为<b>".$stime."</b>至<b>".$etime."</b>，快到<a href='/home/coupon.html' class='WB_info' target='_blank'>我的优惠</a>中查看吧~";
					D("Msg")->addMsgFromLolitabox($userid,$msg); //发私信
				}				
			}
			return $coupon;
		}
		return false;
	}
	
	/**
	 * 获取用户id获取优惠劵列表
	 * @param unknown_type $userid
	 * @param string $p 分页
	 * @return mixed
	 */
	public function getCouponListByUserid($userid,$where=null,$p=null){
		$where['owner_uid']=$userid;
		return $this->order("id DESC")->getCouponList($where,$p,"id DESC");
	}
	
	
	/**
	 * 通过条件获取优惠劵列表
	 * @param mixed $where
	 * @param string $p
	 * @return mixed 
	 */
	public function getCouponList($where=array(),$p=null,$order=""){
		if($p)
	    	$list=$this->where($where)->limit($p)->order($order)->select();
		else
			$list=$this->where($where)->order($order)->select();
		return $list;
	}
	
	/**
	 * 获取当前用户有效的优惠券列表【订购】
	 * @param $userid 用户ID 
	 * @param $limit 限定条数
	 * @param $if_timesub 是否对时间截取 $if_timesub=0不截；$if_timesub=1截取
	 * @param $order 排序
	 */
	public function getUserCouponListByBuy($userid,$limit,$if_timesub=0,$order=""){
			$ntime=date("Y-m-d H:i:s");
			$where_coupon["endtime"]=array("exp",">='$ntime'");
			$where_coupon["status"]=1;
			$where_coupon["owner_uid"]=$userid;
			$order= $order=="" ? "endtime ASC":$order;
			$couponlist=$this->getCouponList($where_coupon,$limit,$order);
			if($couponlist && $if_timesub!=0){
				foreach($couponlist as $key=>$value){
					$arr=explode(" ",$value['endtime']);
					$couponlist[$key]['endtime']=$arr[0];
				}
			}else{
				$couponlist="";
			}
			return $couponlist;
	}
	
	/**
	 * 根据盒子ID与优惠券，得到折扣金额
	 * @param boxid 盒子ID
	 * @param coupon 优惠券代码
	 * @return discount 折扣金额
	 * @author penglele
	 */
	public function getDiscountByCoupon($boxid,$couponcode){
		$return['discount']=0;
		$return['code']="";
		if(!$boxid) return $return;
		$box_info=D("Box")->getBoxInfo($boxid,"if_use_coupon,category");
		if($box_info["if_use_coupon"]==1){
			//当前的盒子可以使用优惠券时
			$discount=$this->getCouponPrice($couponcode);//判断优惠券是否可用
			if($discount>0){
				$return['code']=$couponcode;				
			}
		}
		if($box_info['category']==C("BOX_TYPE_SOLO")){
			$return['discount']=$discount+20;
		}else{
			$return['discount']=$discount;
		}
		return $return;
	}
	
	/**
	 * 通过优惠券ID获取优惠券的金额
	 * @param $code 优惠券代码
	 * @author penglele
	 */
	public function getCouponPrice($code){
		$price=0;
		if(empty($code))
			return $price;
		$ndate=date("Y-m-d H:i:s");
		$where["code"]=$code;
		$where["endtime"]=array("exp",">='$ndate'");
		$where["status"]=1;
		$coupon_info=$this->where($where)->find();
		if(!$coupon_info)
			return $price;
		else 
			return $coupon_info["price"];
	}
	
	
	
	
}
?>