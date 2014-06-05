<?php
/**
 * CRONTAB控制器
* @author litingting
*/

class crontabAction extends commonAction {
	
	function _initialize(){
		set_time_limit(0);
	}
	/**
	 * 创建程序进程文件
	 */
	public function createPid($pid_filename) {
		// 检查pid文件
		$pidfile_savedir = "crontab/";
		$pid_filename = $pidfile_savedir . $pid_filename;
		if (file_exists ( $pid_filename )) {
			Log::write ( $pid_filename . "---runing，please wait!!" );
			exit();
		}
		Log::write ( $pid_filename . "=======================开始 create pid file=======================", INFO );
		// 创建pid文件
		$src_pid = fopen ( $pid_filename, "w" );
		if ($src_pid === false) {
			Log::write ( $pid_filename . "---create pid file error" );
			exit();
		}
		fclose ( $src_pid );
		file_put_contents ( $pid_filename, date ( "Y-m-d H:i:s" ) );
	}
	
	/**
	 * 程序运行结束后销毁PID文件
	 *
	 * @param unknown_type $pid_filename
	 */
	function destoryPid($pid_filename) {
		$pidfile_savedir = "crontab/";
		$pid_filename = $pidfile_savedir . $pid_filename;
		Log::write ( $pid_filename . "=======================结束 delete pid file =======================", INFO );
		if (file_exists ( $pid_filename )) {
			unlink ( $pid_filename );
		}
	}
	
	
	//////////////////////////////////////////////////////常规数据统计//////////////////////////////////////////////////////
	/**
	 * 常规数据修正统计
	 * 运行时间 ：每2分钟运行一次
	 * 用户粉丝数、解决方案粉丝数
	 * 用户关注数
	 * 产品粉丝数
	 * 品牌粉丝数
	 * 统计每个用户分享数
	 * 分享赞数
	 * 分享踩数
	 * 
	 */
	public function DataFix(){
		$this->createPid(__FUNCTION__);
		$model =M();
		//用户粉丝数、解决方案粉丝数 (touid=userid)
// 		$model ->query("UPDATE users SET fans_num = ( SELECT COUNT( whoid ) FROM follow WHERE whoid = users.userid AND type=1 AND userid <> users.userid)");
// 		Log::write ( ' 用户粉丝数、解决方案粉丝数 stat success..', INFO );
		
		//用户关注数 (fromid=userid)
// 		$model->query("UPDATE users SET follow_num = ( SELECT COUNT( userid ) FROM follow WHERE userid = users.userid AND !(whoid = users.userid AND type = 1))");
// 		Log::write ( ' 用户关注数 stat success..', INFO );
		
		//产品粉丝数
// 		$model->query("UPDATE products SET fans_num = ( SELECT COUNT( userid ) FROM follow WHERE whoid = products.pid AND TYPE =2 )");
// 		Log::write ( ' 产品粉丝数 stat success..', INFO );
		
		//品牌粉丝数
		$model->query("UPDATE products SET fans_num = ( SELECT COUNT( userid ) FROM follow WHERE whoid = products.pid AND TYPE =2 ))");
		Log::write ( ' 品牌粉丝数 stat success..', INFO );
		
		// 统计每个用户分享数
		$sql = "SELECT userid, count( id ) AS T FROM `user_share`  WHERE status>0 GROUP BY `userid`";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['userid']) {
				$updatesql = "UPDATE users SET blog_num =" . $item ['T'] . " WHERE userid=" . $item ['userid'];
				$model->query ( $updatesql );
			}
		}
		Log::write ( ' 统计每个用户分享数 stat success..', INFO );

		//统计每个产品分享数
		$sql = "SELECT resourceid, count(resourceid) AS T FROM `user_share` WHERE (resourcetype=1)  AND (status>0) GROUP BY resourceid";
		$list = $model->query ( $sql );
		$array = array(); 
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['resourceid']) {
				$updatesql = "UPDATE products SET sharenum=" . $item ['T'] . ",evaluatenum =" . $item ['T'] . " WHERE pid=" . $item ['resourceid'];
				$model->query ( $updatesql );
			}
		}
		Log::write ( ' 统计每个产品分享数 stat success..', INFO );
		
		//统计套装产品分享数
	    $sql = "SELECT pid,productlist FROM products WHERE productlist !=''";
	    $plist = $model->query($sql);
	    foreach($plist as $key =>$val){
	    	$pid = $val['pid']; //套装产品 ID
	    	$child_plist = explode(",",$val['productlist']);
	    	$total = 0;    // 套装自己本身的分享数
	    	foreach($child_plist as $i =>$p){
	    		$child_sharenum=M("Products")->getFieldByPid($p,"sharenum"); //获取每个套装产品内子产品的分享数
	    		$total += $child_sharenum ;    //累加分享数
	    	}
	    	$updatesql = "UPDATE products SET sharenum=" . $total . " , evaluatenum =" . $total . " WHERE pid=" . $pid;
	    	$model->query($updatesql);
	    }
		Log::write ( ' 统计套装产品分享数 stat success..', INFO );
		
		// 统计每个用户的优惠券数
		$sql = "SELECT owner_uid, count(owner_uid) AS T FROM `coupon` WHERE status=1 GROUP BY `owner_uid`";
		$list =$model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['owner_uid']) {
				$updatesql = "UPDATE users SET coupon_num=" . $item ['T'] . " WHERE userid=" . $item ['owner_uid'];
				$model->execute ( $updatesql );
			}
		}
		Log::write ( ' 统计每个用户优惠券数 stat success..', INFO );

		// 统计每个用户购买盒子（订单）的总数
		$user_model = M ( "Users" );
		$user_order_model = M ( "UserOrder" );
		$order_user_list = $user_order_model->distinct ( true )->field ( "userid" )->where ( "state>0" )->order ( "userid ASC" )->select ();
		for($i = 0; $i < count ( $order_user_list ); $i ++) {
			$order_user_total = $user_order_model->where ( "userid=" . $order_user_list [$i] ["userid"] . " AND state=1 AND ifavalid=1" )->count ();
			$order_user_list [$i] ["order_count"] = $order_user_total;
			$user_model->where ( "userid=" . $order_user_list [$i] ["userid"] )->save ( array (
					"order_num" => $order_user_total
			) );
		}
		Log::write ( ' 统计每个用户订单数 stat success..', INFO );
		
		
		//统计每个分享的转发数
		$sql = "SELECT shareid, count(id) AS T FROM `user_share_out` GROUP BY shareid";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['shareid']) {
				$updatesql = "UPDATE user_share SET outnum =" . $item ['T'] . " WHERE id=" . $item ['shareid'];
				$model->query ( $updatesql );
			}
		}
		Log::write ( ' 统计每个分享的转发数 stat success..', INFO );
		
		
		//统计每条分享赞数
		//$model->query("UPDATE user_share SET agreenum = (SELECT count(shareid) FROM user_share_action WHERE shareid=user_share.id AND type=2 AND status=1) WHERE sharetype=1");
		$sql="SELECT shareid,COUNT(shareid) AS T FROM `user_share_action` WHERE type=2 AND status=1 GROUP BY shareid";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['shareid']) {
				$updatesql = "UPDATE user_share SET agreenum =" . $item ['T'] . " WHERE shareid=" . $item ['shareid'];
				echo $updatesql;echo "<br>";
				$model->execute ( $updatesql );
			}
		}
		Log::write ( ' 统计分享赞数 stat success..', INFO );
		
		//统计每条分享踩数
		//$model->query("UPDATE user_share SET treadnum = (SELECT count(shareid) FROM user_share_action WHERE shareid=user_share.id AND type=1 AND status=1) WHERE sharetype=1");
		$sql="SELECT shareid,COUNT(shareid) AS T FROM `user_share_action` WHERE type=1 AND status=1 GROUP BY shareid";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['shareid']) {
				$updatesql = "UPDATE user_share SET treadnum =" . $item ['T'] . " WHERE shareid=" . $item ['shareid'];
				$model->execute ( $updatesql );
			}
		}
		Log::write ( ' 统计分享踩数 stat success..', INFO );

		//统计每条分享评论数
		//$model->query("UPDATE user_share SET commentnum = (SELECT count(shareid) FROM user_share_comment WHERE shareid=user_share.id AND isdel=0 AND ischeck=1) WHERE sharetype=1");
		$sql="SELECT shareid,COUNT(shareid) AS T FROM `user_share_comment` WHERE  isdel=0 AND ischeck=1 GROUP BY shareid";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['shareid']) {
				$updatesql = "UPDATE user_share SET commentnum =" . $item ['T'] . " WHERE shareid=" . $item ['shareid'];
				$model->execute ( $updatesql );
			}
		}	
		Log::write ( ' 统计分享评论数 stat success..', INFO );
		$this->destoryPid(__FUNCTION__);
	}
	
	/**
	 * 库存数统计
	 * 运行时间：5分钟一次
	 */
	public function InventoryStat(){
		$this->createPid(__FUNCTION__);
		$model =M();
		//统计库存数
		/*1 汇总单品出库数*/
		//先清0
		$sql= "UPDATE `inventory_item` SET relation_out_quantity=0 WHERE 1";
		$model->execute ( $sql );
		//汇总库存单品预计出库数
		$sql="SELECT productid,count(productid) AS T  FROM `user_order_send_productdetail` WHERE orderid IN (SELECT A.orderid FROM `user_order_send` AS A,user_order AS B WHERE (A.orderid=B.ordernmb) AND (A.productnum>0 AND A.senddate IS NULL AND A.proxysender IS NULL AND A.proxyorderid IS NULL) AND (B.state=1 AND B.ifavalid=1 AND B.inventory_out_status=0)) GROUP BY productid ORDER BY NULL";
		$list = $model->query ( $sql );
		while ( list ( $key, $item ) = each ( $list ) ) {
			if ($item ['productid']) {
				$updatesql = "UPDATE inventory_item SET relation_out_quantity=" . $item ['T'] . " WHERE id=" . $item ['productid'];
				$model->execute ( $updatesql );
			}
		}
		/*2 汇总库存表stat数据*/
		$sql= "UPDATE `inventory_item` SET inventory_in=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity>0 AND  status=1) ,inventory_out=(SELECT SUM(quantity) FROM inventory_stat WHERE inventory_item.id=itemid AND quantity<0 AND  status=1)";
		$model->execute ( $sql );
		
		$sql= "UPDATE `inventory_item` SET inventory_real=inventory_in+inventory_out,inventory_estimated=inventory_in+inventory_out-relation_out_quantity WHERE 1";
		$model->execute ( $sql );
		Log::write ( ' 统计库存数 stat success..', INFO );
		
		$this->destoryPid(__FUNCTION__);
	}	
	
	
//////////////////////////////////////////////////////用户个性化信息定制发送//////////////////////////////////////////////////////
	/**
	 * 【用户信息个性化（适用于不同用户收到不同的内容）通知】
	 * 目前主要实现功能：用户订单快递信息通知发送[短信]
	 * 对于已经发出快递的订单进行短信通知
	 * 运行时间：上午11点，下午18点
	 */
	public function UserSendTask() {
		$this->createPid(__FUNCTION__);
		$user_send_task_mod = M ( "UserSendTask" );
		// 发送任务列表总数
		$send_list_count = $user_send_task_mod->where ( "status=0" )->count ();
		$messagelist = $user_send_task_mod->where ( "status=0" )->order ( "add_time ASC" )->select ();
		if ($messagelist) {
			// 处理信息列表
			for($i = 0; $i < count ( $messagelist ); $i ++) {
				$sendlog = "发送信息-类型";
				$sendFlag = true;
				switch ($messagelist [$i] ["type"]) {
					case "1" :
						$sendtype = "【邮件】";
						$sendlog .= $sendtype . ",收件人：" . $messagelist [$i] ["receiver"];
						$sendFlag=systemSendmail($messagelist [$i] ["receiver"], $messagelist [$i] ["title"], $messagelist [$i] ["content"]);
						if($sendFlag) {
							$interval_send=rand(1200,1300); //发送邮件间隔
							usleep($interval_send);
						}
						break;
					case "2" :
						$sendtype = "【短信】";
						$sendlog .= $sendtype . ",手机号：" . $messagelist [$i] ["receiver"];
						$sendFlag = sendtomess ( $messagelist [$i] ["receiver"], $messagelist [$i] ["content"] );
						usleep (300,500); //发送短信间隔
						break;
					default :
						break;
				}
				if ($sendFlag) {
					$data ["status"] = 1;
					$data ["send_time"] = time ();
					$user_send_task_mod->where ( "id=" . $messagelist [$i] ["id"] )->save ( $data );
					Log::write ( $sendlog . "___".$messagelist [$i] ["title"]."___【SUCCESS】\r", INFO );
				} else {
					Log::write ( $sendlog . "___".$messagelist [$i] ["title"]."___【FAIL】\r" );
				}
			}
		}
		Log::write ( "发送任务列表【正常结束】", INFO );
		$this->destoryPid(__FUNCTION__);
	}
	

//////////////////////////////////////////////////////更新订单的物流信息//////////////////////////////////////////////////////

		/**
		 * 【更新已经有快递单号的订单物流信息】
		 *  运行时间：每天3点运行一次
		 * 将新的己经发货的数据插入到user_order_proxy并批量读取快递信息
		 * @author litingting
		 */
		public function UpdateProxyInfo()
		{
			$this->createPid(__FUNCTION__);
			//将需要取快递信息的订单号加到数据表user_order_proxy
			$proxy_mod=M("UserOrderProxy");
			$sql="insert into user_order_proxy(orderid,proxysender,proxyorderid)
			select orderid,proxysender,proxyorderid from user_order_send
			where proxysender is not null and proxyorderid  is not null
			and not exists(select orderid from  user_order_proxy where orderid=user_order_send.orderid)";
			$proxy_mod->query($sql);
		
			$date=date("Y-m-d");
			$list=$proxy_mod->field("orderid,proxysender,proxyorderid,counter,requesttotal")->where("status=0 AND counter<4 AND  DATE_FORMAT(lasttime,  '%Y-%m-%d' ) < '".$date."' AND requesttotal < 15")->order("orderid desc")->limit(30)->select();
			$company=array('中通'=>'zhongtong','申通'=>'shentong','圆通'=>'yuantong','cces'=>'cces','顺丰'=>'shunfeng','韵达'=>'yunda',"邮政" => "ems","顺风"=>"shunfeng");
			$count=count($list);
			for($i=0;$i<$count;$i++)
			{
			$data = array();
			$typeCom=$list[$i]['proxysender'];
			$typeNu=$list[$i]['proxyorderid'];
			if(isset($company[$typeCom])){
			$typeCom=$company[$typeCom];
			}
			$typeCom=strtolower($typeCom);
			$url="http://www.kuaidi100.com/query?type=$typeCom&postid=$typeNu&id=1";
			$curl = curl_init();
			curl_setopt ($curl, CURLOPT_URL, $url);
			curl_setopt ($curl, CURLOPT_HEADER,0);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
			curl_setopt ($curl, CURLOPT_TIMEOUT,5);
			$get_contents = curl_exec($curl);
			curl_close ($curl);
			$get_content_arr=json_decode(str_replace("'", '"', $get_contents),true);
			if(isset($get_content_arr['state']) && ($get_content_arr['state'] =='4' || $get_content_arr['state']=='3'))     //物流状态，3为己签收，4为己退款
			{
			$data['status']=1;
			}
				
			$proxyinfo="";
			if($get_content_arr['data'])      //如果有物流信息
			{
			foreach($get_content_arr['data'] as $key => $val)
				{
				$proxyinfo.=$val['time']." ".$val['context']."\r\n";
				}
				}else{
				$proxyinfo=$get_content_arr['message'];   //如果没有物流信息，则将返回的错误信息给保存下来
				}
					
				if(isset($get_content_arr['status']) && $get_content_arr['status']!='200') {     //请求状态，200代表成功
				$data['counter']=$list[$i]['counter']+1;
				if($data['counter']>=3)
				{
				$data['status']=1;
				}
				}
				$data['proxyinfo']=$proxyinfo;
				$data['requesttotal']=$list[$i]['requesttotal']+1;
				if($data['requesttotal'] >= 15)
					$data ['status']=1;    //如果请求次数 >=15，则直接将状态强制改为1
						
					$data['lasttime']=date("Y-m-d H:i:s");
					$proxy_mod->where("orderid=".$list[$i]['orderid'])->save($data);
					$a=rand(15,30);
					sleep($a);
						
			}
			$this->destoryPid(__FUNCTION__);
		}
	
//////////////////////////////////////////////////////运营的用户及销售数据统计//////////////////////////////////////////////////////
		/**
		 * 【运营-每日盒子销售数据统计】统计一个星期的数据
		 *  统计每天的注册数，订单数，折扣额，应收总额，销售额
		 * 运行时间：每天统计一次，默认统计前一天的订单相关数据
		 * @author litingting
		 */
		public function LolitaboxDataStat() {
			$this->createPid ( __FUNCTION__ );
			$start=date("Y-m-d",strtotime("-3 week"));
			$end = date ( "Y-m-d", strtotime ( "-1 day" ) );
			while ( $start <= $end ) {
				$this->orderDataStat ( $start );
				$date = date_create ( $start );
				date_add ( $date, date_interval_create_from_date_string ( "1 days" ) );
				$start = date_format ( $date, 'Y-m-d' );
			}
			$this->destoryPid ( __FUNCTION__ );
		}
		
		
		
		
		/**
		 * 订单数据统计
		 */
		public function orderDataStat($starttime = null){
			// 默认统计昨天的数据
			$starttime = $starttime ? $starttime : date ( "Y-m-d", strtotime ( "-1 day" ) );
			$user_mod = M ( "Users" );
			$order_mod = M ( "UserOrder" );
			$order_pro_mod = M ( "UserOrderSendProductdetail" );
			$stat_order_mod = M ( "StatUserOrder" );
			$data ['registernum'] = $user_mod->where ( "DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "'" )->count (); // 注册数
			$data ['ordernum'] = $order_mod->where ( "(type NOT IN (16,18,19,21))  AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->count (); // 订单数
			$data ['ordernum_exchange'] = $order_mod->where ( "(type=18) AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->count (); // 积分兑换订单数
			$data ['ordernum_postage'] = $order_mod->where ( "(type=19) AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->count (); // 付邮试用订单数
			$data ['discount'] = $order_mod->where ( "(type NOT IN (16,18,19,21))  AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->sum ( "discount" ); // 折扣额
			$data ['totalprice'] = $order_mod->where ( "DATE_FORMAT(addtime,'%Y-%m-%d') = '" . $starttime . "' AND state=1" )->sum ( "boxprice" ); // 应收总额	
			$data ['sales'] = $order_mod->where ( "(type NOT IN (16,18,19,21))  AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->sum ( "boxprice - discount" ); // 销售额
			$data ['sales_exchange'] = $order_mod->where ( "(type=18) AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->sum ( "boxprice - discount" ); // 积分兑换销售额
			$data ['sales_postage'] = $order_mod->where ( "(type=19) AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->sum ( "boxprice - discount" ); // 积分兑换销售额
			$data ['addtime'] = time ();
			
			// 计算所有萝莉盒订单总价值
			$sum = 0;
			$temp=0;
			$orderidlist = $order_mod->field ( "ordernmb" )->where ("(type NOT IN (16,18,19,21))  AND DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->select ();
			foreach ( $orderidlist as $key => $val ) {
				$temp = $order_pro_mod->where ( "orderid=" . $val ['ordernmb'] )->sum ( "productprice" );
				$temp = $temp ? $temp : 0;
				$sum += $temp;
			}
			$data ['totalvalue'] = $sum;
			
			// 计算所有订单总价值
			$sum = 0;
			$temp=0;
			$orderidlist = $order_mod->field ( "ordernmb" )->where (" DATE_FORMAT(addtime,'%Y-%m-%d') ='" . $starttime . "' AND state=1" )->select ();
			foreach ( $orderidlist as $key => $val ) {
				$temp = $order_pro_mod->where ( "orderid=" . $val ['ordernmb'] )->sum ( "productprice" );
				$temp = $temp ? $temp : 0;
				$sum += $temp;
			}
			$data ['totalvalue_all'] = $sum;
			
			// 对null值进行处理
			foreach ( $data as $key => $val ) {
				if ($val == null)
					$data [$key] = 0;
			}
				
			// 将数据加入到统计表中
			if ($stat_order_mod->where ( "statdate= '" . $starttime . "'" )->find ()) {
				$stat_order_mod->where ( "statdate= '" . $starttime . "'" )->save ( $data );
			} else {
				$data ['statdate'] = $starttime;
				$stat_order_mod->add ( $data );
			}
		}
		
		/**
		 * 【批量进行运营数据统计(兼容统计之前的数据)】
		 * @author litingting
		 */
		public function batchLolitaboxDataStat() {
			$start = "2012-05-01";
			$end = date ( "Y-m-d", strtotime ( "-1 day" ) );
			while ( $start <= $end ) {
				$this->orderDataStat ( $start );
				$date = date_create ( $start );
				date_add ( $date, date_interval_create_from_date_string ( "1 days" ) );
				$start = date_format ( $date, 'Y-m-d' );
			}
		}
		
//////////////////////////////////////////////////////定时发布分享任务//////////////////////////////////////////////////////
		/**
		 * 【定时发布分享任务】
		 * 运行时间：每分钟运行一次
		 */
		public function SchedulingShareTask(){
			$this->createPid ( __FUNCTION__ );
			$user_share_mod = D("UserShare");
			$current= time();
			$where['sendtime'] = array("exp","BETWEEN ".(time()-60)." AND ".(time()+60));   //小于当前时间加上十分钟之内
			$where['status'] = 0;
			$share_task = M("ShareTask");
			$tasklist = $share_task->where($where)->select();
			foreach ($tasklist as $key => $val){
				$data = unserialize($val['data']);
				if(empty($val['userid']) || empty($data['content']) ){
					continue;
				}
				$sourceid = $data['resourceid'] ?$data['resourceid']:0 ;
				$sourcetype = $data['resourcetype']? $data['resourcetype']:0;
				$flag=$user_share_mod->addShare($val['userid'],$data['content'],$data['img'],0,array(),$sourceid,$sourcetype);
				if($flag >0){
					$share_task ->where("id=".$val['id'])->save(array("status"=>1,"shareid" =>$flag));
				}
			}
			$this->destoryPid ( __FUNCTION__ );
		}
		

		
//////////////////////////////////////////////////////分享统计//////////////////////////////////////////////////////
		/**
		 * 分享统计
		 */
		public function batchShareDataStat(){
			$this->createPid ( __FUNCTION__ );
			$this->ShareDataStat("2013-01-01","2013-05-15");
			$this->destoryPid ( __FUNCTION__ );
		}
		
		
		
		/**
		 * 1、当两个参数为空时统计前一天用户评测数
		 * 2、当只有$start时,就会统计从它开始到昨天的数据
		 * 3、当有两个参数时，就会统计从$start到$end的数据
		 * 若只有$end参数时，$end无效，它会执行第1种
		 * @author litingting
		 * *
		 */
		public function ShareDataStat($start = null, $end = null) {
			$datecount = date ( "Y-m-d", strtotime ( "-1 day" ) ); // 表示昨天的时间
			if (empty($start))
				$this->addUserShareStatData ( $datecount );
			else {
				if ($end == null) {
					$end = $datecount;
				}
				while ( $end >= $start ) { // 用不等于来判断易出现死循环,
					$this->addUserShareStatData ( $start );
					$date = date_create ( $start );
					date_add ( $date, date_interval_create_from_date_string ( "1 days" ) );
					$start = date_format ( $date, 'Y-m-d' );
				}
			}
		
		}
		
		/**
		 * 汇总指定日期的用户分享数据到统计数据表中
		 */
		public function addUserShareStatData($datecount) { // 传日期，如1990-09-10
			$user_share_mod = M ( "UserShare" );
			$user_share_stat_mod = M ( "UserShareStat" );
			// 判断是否己经加过
			if (! $user_share_stat_mod->getByStat_date ( $datecount )) {
				$data ['stat_date'] = $datecount;
				$from = strtotime($datecount);
				$to = strtotime($datecount." 23:59:59");
				
				$list = $user_share_mod->query ( "SELECT count(pe.id) as total FROM user_share pe,users u WHERE pe.userid=u.userid AND (u.usermail LIKE 'nbceshi%lolitabox.com'  OR u.usermail LIKE 'pingce%lolitabox.com') AND posttime BETWEEN ".$from." AND ".$to );
   				$data ['inner_post_num'] = $list [0] ['total'];  //内部发分享数量
				
				$list = $user_share_mod->query ( "SELECT count(pe.id) as total FROM user_share pe,users u WHERE pe.userid=u.userid AND (u.usermail NOT LIKE 'nbceshi%lolitabox.com' AND u.usermail NOT LIKE 'pingce%lolitabox.com') AND posttime BETWEEN ".$from." AND ".$to );
				$data ['real_post_num'] = $list [0] ['total'];    //真实发分享数量
				
				$list = $user_share_mod->query ( "SELECT count( DISTINCT u.userid) as total FROM user_share pe,users u WHERE pe.userid=u.userid AND (u.usermail  NOT LIKE 'nbceshi%lolitabox.com' AND u.usermail NOT LIKE 'pingce%lolitabox.com') AND  posttime BETWEEN ".$from." AND ".$to );
				$data ['real_user_num'] = $list [0] ['total'];    //真实用户人数
				
				$list = $user_share_mod->query ( "SELECT count(id) as total FROM user_share_comment WHERE  posttime BETWEEN ".$from." AND ".$to );
				$data ['reply_num'] = $list [0] ['total'];          //回复总数
				
				
				$list = $user_share_mod->query ( "SELECT count(distinct userid) as total FROM user_share_comment WHERE posttime BETWEEN ".$from." AND ".$to );
				$data ['reply_user_num'] = $list [0] ['total'];       //回复用户总数
				
				$list = $user_share_mod->query ( "SELECT COUNT(id) as total FROM user_share WHERE posttime BETWEEN ".$from." AND ".$to);
				$data ['post_total'] = $list [0] ['total'];          //分享总数
				
				$list = $user_share_mod->query ( "SELECT COUNT(DISTINCT userid) as total FROM user_share WHERE posttime BETWEEN ".$from." AND ".$to );
				$data ['user_total'] = $list [0] ['total'];        //分享人数统计
				
				$user_share_stat_mod->add($data );
			}
		
		}



//////////////////////////////////////////////////////EDM发送//////////////////////////////////////////////////////
		
		/**
		 * 【通过读取send_task数据表中的推广任务形式发送推广邮件或短信】
		 * 只针对LOLITABOX注册会员中已经激活邮件的用户，可以是满足某类条件的用户，如性别，订单数
		 * 由于是根据事先定义好的发送任务执行EDM，会记录每个发送的记录到send_log中
		 */
		public function EdmBySendTask(){
			$this->createPid(__FUNCTION__);
			$send_mod=M("SendTask");
			$send_log_mod=M("SendLog");
			$user_mod=M("Users");
			$user_order_address_mod=M("UserOrderAddress");
			$user_order_send_mod=M("UserOrderSend");
			$user_address_mod=M("UserAddress");
			$user_profile_mod=M("UserProfile");
		
			$list=$send_mod->where("status = 1 ")->order("id desc")->select();
			foreach($list as $key => $val){
				$where=json_decode($val['filtersql'],true);
					
				if($val['type']==1) {
					//EDM-邮件方式，获取所有已经激活的邮件地址
					$where['state']=2; //用户的邮箱地址必须是激活的
					$userlist=$user_mod->field("userid,usermail as target")->where($where)->select();
				}else {
					//EDM-短信方式，获取user_order_address中的手机号
					$useridlist=$user_mod->field ("userid")->where($where)->select();
					$a=0;
					for($i=0;$i<count($useridlist);$i++){
						//取user_order_address表中的数据
						$orderidlist=$user_order_send_mod->field("orderid")->where("userid=".$useridlist[$i]['userid'])->select();
						foreach($orderidlist as $k => $v){
							$telphone=$user_order_address_mod->where("orderid=".$v['orderid'])->getField("telphone");
							if(! $telphone)
								continue;
							$userlist[$a]['userid'] = $useridlist[$i]['userid'];
							$userlist[$a]['target'] = $telphone;
							$a++;
						}
						//取user_address中的数据
						$telphonelist=$user_address_mod->field("telphone")->where("userid=".$useridlist[$i]['userid'])->select();
						foreach($telphonelist as $v){
							$userlist[$a]['userid'] = $useridlist[$i]['userid'];
							$userlist[$a]['target'] = $v['telphone'];
							$a++;
						}
						//取user_profile中的数据
						if($telphone=$user_profile_mod->where("userid=".$useridlist[$i]['userid'])->getField("telphone")){
							$userlist[$a]['userid'] = $useridlist[$i]['userid'];
							$userlist[$a]['target'] = $telphone;
							$a++;
						}
					}
				}
				foreach($userlist as $k => $v){
					$data = array ();
					if( $val['type']==2){        //如果为手机，正则匹配是否为手机号
						if(!preg_match('/^1(3|5|8|4)\d{9}$/',$v['target']) ) {
							continue;
						}
					}
					$data ['taskid'] = $val ['id'];
					$data ['target'] = $v ['target'];
					if($info=$send_log_mod->where($data)->find())
					{
						if($info['status']==1){
							continue;
						}
		
					}
					$data ['artid'] = $val ['artid'];
					$data ['userid'] = $v ['userid'];
		
					if($this->sendMess($data ['target'], $data ['artid'] , $val['type'])){
						$data ['status'] =1;
					}else{
						$data ['status'] =0;
					}
					$data ['sendtime'] =time();
					$send_log_mod->query("REPLACE INTO send_log VALUES(null,".$data['taskid'].",".$data['artid'].",".$data['userid'].",'".$data['target']."',".$data['status'].",".$data['sendtime'].")");
					usleep(rand(1200,1300));
				}
				//统计发送成功的数目
				$count=$send_log_mod->where("taskid=".$val['id']." AND status=1")->count();
				$send_mod->where("id=".$val['id'])->save(array("totalnum" =>$count));
			}
			$this->destoryPid(__FUNCTION__);
		}
		
		/**
		 * 【通过读取外部文件的形式发送推广邮件】
		 * 邮件列表文件位置：项目根目录下，文件名：mlist.txt
		 * 文件内容：每行一个邮件地址，第一行必须指定为Article中对应的邮件模版文章ID
		 * 处理流程：1)通过后台管理维护EDM内容信息，2)根据需要拉出邮件列表，3)在服务器中通过CRONTAB或命令行方式执行本方法
		 * #!/bin/sh
		 cd /data/lolitabox/www
		 /usr/bin/php /data/lolitabox/www/crontab.php crontab EdmByFile
		 */
		public function EdmByFile(){
			$this->createPid(__FUNCTION__);
			$filename = "./mlist.txt";
			if(file_exists($filename)){
				$fp = @fopen($filename,"r");
				$artid = trim(fgets($fp));
				if(empty($artid)){
					return;
				}
				while($target=trim(fgets($fp))){
					if (preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i",$target)){
						$this->sendMess($target, $artid, 3);
					}
					usleep(rand(1200,1300));
				}
			}
			$this->destoryPid(__FUNCTION__);
		}
		
		
		
		/**
		 * 发送信息
		 * @param string $target  发送对象
		 * @param int $artid  article关联id
		 * @param int $type  类型（邮件或短信）
		 */
		private function sendMess($target,$artid,$type)
		{
			$art_mod=M("Article");
			if($info=M("Article")->where("id=".$artid)->find())
			{
				if($type==1)
				{
					if(preg_match("/^.*(lolitabox.com|qq.com|foxmail.com)$/",$target))
						$flag = sendtomail($target, $info['title'], $info['info']);
					else
						$flag = systemSendmail ($target,$info['title'], $info['info']);
				}
				elseif($type==2)
				{
					$flag = sendtomess($target, $info['info']);
				}
				elseif($type==3){
					$flag=$this->sendMailBySendcloud($target,$info['title'], $info['info']);
				}
				if($flag)
					Log::write($target."__".$info['title']."__【SUCCESS】\r",INFO);
				else
					Log::write($target."__".$info['title']."__【FAIL】\r",INFO);
				return $flag;
					
			}else {
				Log::write($target."__没有artid对应的信息_【FAIL】\r");
				return false;
			}
		}
		
		/**
		 * sendcloud邮件发送接口方式的邮件发送
		 * 还未正式启用，待启用
		 */
		public function sendMailBySendcloud($to,$subject,$content){
				//当用户邮箱为QQ、lolitabox、foxmail时，用SMTP方式发送
				/*
				if(preg_match("/^.*(lolitabox.com|qq.com|foxmail.com)$/",$to)) {
					$flag = sendtomail($to,$subject,$content);
					return $flag;
				}
				*/
			    
				$url = 'https://sendcloud.sohu.com/webapi/mail.send.json';
				// 不同于登录SendCloud站点的帐号，您需要登录后台创建发信域名，获得对应发信域名下的帐号和密码才可以进行邮件的发送。
				$param = array('api_user' => 'postmaster@lolitabox.sendcloud.org',
						'api_key' => 'CQSaJt2V',
						'from' => 'lolitabox@ems.t360.cn',
						'fromname' => '萝莉盒',
						'to' =>$to,
						'subject' =>$subject,
						'html' => $content);
				$options = array('http' => array('method'  => 'POST','content' => http_build_query($param)));
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);
				 if(preg_match("/success/is",$result)){
			   		return true;
			   	}
				else 
					return false;
		}

//////////////////////////////////////////////////////产品排重//////////////////////////////////////////////////////
		
		/**
		 * 【产品数据排重与合并】
		 * 只运行一次，完成后不再运行
		 */
		public function productsFilter(){
			$this->createPid ( __FUNCTION__ );
			set_time_limit(0);
			$products_mod = M("Products");
			$follow = M("Follow");
			$user_atme = M("UserAtme");
			$user_behaviour_relation_mod = M("UserBehaviourRelation");
			$filename = "./productsfilter.txt";
			if(!file_exists($filename)){
				return false;    //如果文件不存在，则直接返回
			}
			$fp = @fopen($filename, "r");
			while($str = trim(fgets($fp))){
				$a = substr($str,0,1);
				if($a=='#'){        //逻辑删除产品
					$pid_list = substr($str,1);
					$where['pid'] = array("IN",$pid_list);
					$products_mod ->where($where)->setField("status",0);
					continue;
				}
				$array = explode("\t",$str);
				$save_pid = $array[0];
				$child_list_str = $array[1];
				$child_list = explode(",", $child_list_str);
				//$child_list 重复，需要删除的ID列表
				for($i=0;$i<count($child_list);$i++){
					echo $child_list[$i];
					//关注相关纪录变更
					//每个重复的ID下的关注列表
					$follow_list = $follow ->field("userid")    ->where("whoid=".$child_list[$i]." AND type=2")->select();
					if($follow_list){
						foreach($follow_list as $key =>$val){
							//查看关注重复ID的用户有没有关注正确的ID
							if(!$follow->where("userid=".$val['userid']." AND type=2 AND whoid=".$save_pid)->find()){
								//如果没有，将关注重复的whoid改为正确的ID
								$follow->where("userid=".$val['userid']." AND type=2 AND whoid=".$child_list[$i])->setField("whoid",$save_pid);
							}
						}
					}
					$follow->where("whoid=".$child_list[$i]." AND type=2")->delete();
						
					//user_atme相关纪录变更
					$user_atme_list = $user_atme ->where("sourceid=".$child_list[$i]." AND sourcetype=2")->select();
					foreach($user_atme_list as $key=>$val){
						if(!$user_atme->where("relationid=".$val['relationid']." AND relationtype=".$val['relationtype']." AND sourcetype=2 AND sourceid=".$save_pid)->find()){
							$user_atme->where("relationid=".$val['relationid']." AND relationtype=".$val['relationtype']." AND sourcetype=2 AND sourceid=".$child_list[$i])->setField("sourceid",$save_pid);
						}
					}
					$user_atme ->where("sourceid=".$child_list[$i]." AND sourcetype=2")->delete();
						
					//动态相关纪录变更
					$behaviour_list = $user_behaviour_relation_mod ->where("(userid=".$child_list[$i]." AND usertype=2) OR (whoid=".$child_list[$i]." AND usertype=1 AND type='follow_pid')")->select();
					foreach($behaviour_list as $key=>$val){
						if(!$user_behaviour_relation_mod->where("whoid=".$save_pid." AND type='".$val['type']."' AND usertype=".$val['usertype']." AND userid=".$val['userid'])->find()){
							$user_behaviour_relation_mod->where("whoid=".$val['whoid']." AND type='".$val['type']."' AND usertype=".$val['usertype']." AND userid=".$val['userid'])->setField("whoid",$save_pid);
						}
					}
					$user_behaviour_relation_mod->where("(userid=".$child_list[$i]." AND usertype=2) OR (whoid=".$child_list[$i]." AND usertype=1 AND type='follow_pid')")->delete();
					$sql = "update user_share set resourceid=".$save_pid." WHERE resourceid=".$child_list[$i]." AND resourcetype=1";
					$products_mod->query($sql);
					$sql = "update inventory_item set relation_id=".$save_pid." WHERE relation_id=".$child_list[$i];
					$products_mod->query($sql);
					$products_mod ->where("pid=".$child_list[$i])->delete();
					echo $products_mod->getLastSql();
				}
			}
			$this->destoryPid ( __FUNCTION__ );
		}
		

		/**
		 * 【商品订单即将过期的私信提醒】
		 * （暂未使用）
		 */
		public function OrderNopayWarn(){
			$yesterday_time = strtotime("-2 day");
			$date= date("Y-m-d",$yesterday_time);
			$from = $date." 00:00:00";
			$to = $date." 23:59:59";
			$where["_string"] = "addtime >='".$from."' AND addtime <= '".$to."'";
			$where['ifavalid'] = 1;
			$list = M("UserOrder")->field("userid,ordernmb")->where($where)->select();
			//	$order_address = M("UserOrderAddress");
			$msg_mod = D("Msg");
			$lolitabox_id = C("LOLITABOX_ID");
			foreach($list as $key=>$val){
				/* $telphone = $order_address ->where("orderid=".$val['ordernmb'])->getField("telphone");
				 if(preg_match('/^1(3|5|8|4)\d{9}$/',$telphone) ) {
				$content = "亲，为了及时拿到精美的萝莉盒，请您在24小时内登录LOLITABOX进行支付，否则订单将会自动失效，抓紧吧~【萝莉盒】";
				$flag=sendtomess($telphone, $content);
				if($flag){
				Log::write($telphone."订单失效短信提醒成功【success】\r",INFO);
				}else{
				Log::write($telphone."订单失效短信提醒成功【fail】\r",INFO);
				}
				} */
				$content = "亲，为了及时拿到精美的萝莉盒，请您在24小时内登录LOLITABOX进行支付，否则订单将会自动失效，抓紧吧~【萝莉盒】";
				$msg_mod->addMsg($lolitabox_id,$val['userid'],$content);
			}
				
		}
		
		
		/**
		 * 执行赞任务
		 */
		public function agreeTask(){
			$where['unfinished'] = array("gt",0);
			$where['endtime'] = array("egt",time()-60);
		//	echo date("Y-m-d H:i:s",1369275734);die;
			$list = M("AgreeTask")->where($where)->select();
	
			foreach($list as $key =>$val){
// 				echo date("Y-m-d H:i:s",$val['nexttime']);
// 				echo date("Y-m-d H:i:s");
// 				var_dump($val['nexttime']< time());
				if(empty($val['nexttime']) || $val['nexttime']< time()){
					$this->autoAgree($val['shareid'], $val['id']);
				}
				
			}
		}
		
		/**
		 * 自动加赞操作
		 * @param unknown_type $shareid
		 * @param unknown_type $taskid
		 */
		private function autoAgree($shareid,$taskid){
			$share_mod = M("UserShare");
			
			$userids = $share_mod ->field("userid")->where("parentid=".$shareid." AND status >0")->select();
			foreach($userids as $key =>$val){
				if($val['userid'])
			    	$userlist[] = $val['userid'];
			}
			if($userlist){
				$map = " AND userid NOT IN(".implode(",",$userlist).")";
			}else{
				$map = "";
			}
			//每次运行赞的次数
			$agree_num = rand(1,2);
			for($i=0;$i<$agree_num;$i++){
				$flag = $this->addAgree($shareid, $taskid, $map);
				switch($flag){
					case 1:
				    	log::write("任务ID为".$taskid."-------分享id:".$shareid."--SUCCESS【".date("Y-m-d H:i:s")."】",INFO);
				    	break;
					case 0:
						log::write("任务ID为".$taskid."-------分享id:".$shareid."-保存数据失败--ERROR【".date("Y-m-d H:i:s")."】",INFO);
						break;
					case -1:
						log::write("任务ID为".$taskid."-------分享id:".$shareid."--内部帐号不足-ERROR【".date("Y-m-d H:i:s")."】",INFO);
						break;
					case -2:
						log::write("任务ID为".$taskid."-------分享id:".$shareid."--参数错误-ERROR【".date("Y-m-d H:i:s")."】",INFO);
						break;
					case -3:
						log::write("任务ID为".$taskid."-------分享id:".$shareid."--赞操作失败-ERROR【".date("Y-m-d H:i:s")."】",INFO);
						break;
					
				}
				
				//var_dump($flag);
			}
			
		}
		
		private function addAgree($shareid,$taskid,$map){
			$user_mod = M("Users");
			$share_mod = D("UserShare");
			$agree_task_mod = M("AgreeTask");
			$userid = $user_mod->where("usermail like 'pingce%lolitabox.com' OR usermail like 'nbceshi%lolitabox.com' ".$map)->order("rand()")->limit(1)->getField("userid");
			if(empty($userid)){
				return -1; //表示内部帐号不足
			}
				
			if($share_mod->addAgree($userid,$shareid,$shareid)>0){
				$taskinfo = $agree_task_mod->where("id=".$taskid)->find();
				if(empty($taskinfo)){
					return -2;    //参数错误
				}
				$rand_time =time()+ rand(1,floor(($taskinfo['endtime'] -time())/(($taskinfo['unfinished']-1)*60)))*60;
				
				if(false!==$agree_task_mod->query("update agree_task set unfinished=unfinished-1,nexttime=".$rand_time)){
					return 1;     //操作成功
				}else{
					return 0;   //保存数据失败
				}
			}else{
				return -3; //赞失败
			}
		}
		
		
		/**
		 * 内部帐号粉丝数随机增加或减少
		 */
		public function randFansByInnerUser(){
			set_time_limit(0);
			$user_mod = M("Users");
			$inner_userlist = $user_mod->field("userid,fans_num")->where("(usermail like 'nbceshi%lolitabox.com' OR usermail LIKE 'pingce%lolitabox.com' OR usermail LIKE 'guimi%lolitabox.com' OR usermail LIKE 'onlylady%lolitabox.com') AND fans_num > 0")->select();
		//	echo $user_mod->getLastSql();die;
			$follow_mod = D("Follow");
			foreach($inner_userlist as $key =>$val){
				$this->changeFans($val['userid'], 1, $val['fans_num']);
			}
		}
		
		
		/**
		 * 改变粉丝数
		 * @param unknown_type $id
		 * @param unknown_type $type
		 * @param unknown_type $fans_num
		 */
	    private function changeFans($id,$type,$fans_num){
	    	$follow_mod = D("Follow");
	    	$user_mod = M("Users");
	    	$rand_num = rand(-$fans_num,50);
	    	if($rand_num < 0){
	    		$limit = abs($rand_num);
	    		$follow_mod -> query("delete from follow  where  userid IN(SELECT userid FROM users WHERE usermail LIKE 'nbceshi%lolitabox.com' OR usermail like 'pingce%lolitabox.com' ) AND whoid=".$id." AND type=$type limit ".$limit);
	    		$user_mod ->where("userid=".$id)->setDec("fans_num",$limit);
	    		//echo $follow_mod->getLastSql();
	    		log::write("删除粉丝SQL：".$follow_mod->getLastSql(),INFO);
	    	}
	    	
	    	if($rand_num >0){
	    		$a=$follow_mod->datAddFollow($id,$type,$rand_num);
	    		log::write("加粉ID：".$id."，加粉类型；".$type."，加粉数：".$rand_num,INFO);
	    	}
	    }
		
		
		/**
		 * 产品粉丝数随机
		 */
		public function randFansByProducts(){
			set_time_limit(0);
			$pro_list = M("Products")->field("pid,fans_num")->where("fans_num > 0")->select();
			foreach($pro_list as $key =>$val){
				$this->changeFans($val['pid'], 2, $val['fans_num']);
			}
		}
		
		/**
		 * 品牌粉丝数随机
		 */
		public function randFansByBrand(){
			set_time_limit(0);
			$pro_list = M("ProductsBrand")->field("id,fans_num")->where("fans_num > 0")->select();
			foreach($pro_list as $key =>$val){
				$this->changeFans($val['id'], 3, $val['fans_num']);
			}
		}
		
		
		/**
		 * 内部帐号分滩关注数
		 */
		public function avgFollowByInnerUser(){
			$user_mod = M("Users");
			$follow_mod = M("Follow");
			$follow_num = 1980; 
			//$base_num = 20;
			//要处理的内部用户ID
			$inner_userlist = $user_mod->field("userid,follow_num")->where("(usermail like '%lolitabox.com' ) AND follow_num >".$follow_num)->select();
			//echo $user_mod->getLastSql();die;
			//获取关注数较少的用户列表
			$userlist = $user_mod->field("userid,follow_num")->cache(true)->where ( "(usermail like 'nbceshi%lolitabox.com' OR usermail like 'pingce%lolitabox.com') AND follow_num < ".$follow_num)->order ( "follow_num ASC" )->select ();
			//组装成哈希数组
			$hash_userlist = array();
			foreach($userlist as $key=>$val){
				$userid = $val['userid'];
				$hash_userlist[$userid] = $val['follow_num'];
			}
			foreach($inner_userlist as $key =>$val){
				$log="";
				$log="TARGET---userid:".$val['userid']." follow_num:".$val["follow_num"];
				log::write("正在处理数据：".$log);
				
				$start_num = $val['follow_num']-$follow_num;
				if($val['follow_num']-$follow_num-100 > 0){
					$start_num = $val['follow_num']-$follow_num-100;
				}
				$total_num =rand($start_num, $val['follow_num']-$follow_num+100);
				$follow_list = $follow_mod->where("userid=".$val['userid'])->limit($total_num)->select();
		    	foreach ( $follow_list as $k => $v ) {
				   // 获取一个有效的内部用户ID
			        foreach($hash_userlist as $n =>$a){
			        	if($a >=$follow_num - rand(-100,198)){
			        		unset($hash_userlist[$n]);
			        		continue;
			        	}
			        	if($follow_mod->where("whoid=".$v['whoid']." AND userid=".$n." AND  type=".$v['type'])->find()){
			        		continue;
			        	}
			        	$follow_mod->where ( "whoid=" . $v ['whoid'] . " AND userid=" . $v ['userid'] . " AND type=" . $v ['type'] )->setField ( "userid", $n );
			        	$hash_userlist[$n]= $a+1;
			        }
			        reset($hash_userlist);
		    	}
 		    	break;
			}
		}
		
		
		
		/**
		 * 内部帐号分滩关注数
		 * 第二种解决方案
		 */
		public function avgFollowBy2(){
			$this->createPid(__FUNCTION__);
			$follow_model = M("Follow");
			$filename = "./data/followtemp.txt";
		    log::write("更新用户关注数",INFO);
		    //加关注
		    $fp = @fopen($filename,"r");
		    log::write("开始加关注.....",INFO);
		    while($str=fgets($fp)){
		    	$arr=explode(",",$str);
		    	$rand_num = rand(ceil($arr[2]*0.8),ceil($arr[2]*1.2));
		    	$follow_model->query("REPLACE INTO follow(userid,whoid,type,addtime) SELECT userid,".$arr[0].",".$arr[1].",".rand(1346418365,1369360379)." FROM users WHERE (usermail like 'nbceshi%lolitabox.com' OR usermail like 'pingce%lolitabox.com') ORDER BY rand() limit ".$rand_num);
		    }
		    log::write("结束加关注.....",INFO);
		    fclose($fp);
		    
		    $this->destoryPid ( __FUNCTION__ );
		}
		
		/**
		 * xs分享
		 */
		public function xs_share(){
			//搜索
			$xs = new XunSouModel("share");
			$docs =$xs-> search("欧珀莱","shareid",10,array("shareid"=>69461));

			//组合搜索
			$docs =$xs-> search("shareid:69461 欧珀莱","shareid",10);
			var_dump($docs);
			
			
			//热词
	        $list = $xs->hot(10);
	        var_dump($list);
	        
	        //分词
	        $begin = microtime();
	        $list = $xs->scws("第一款美容仪 Pro-X微晶亮肤洁面仪 OLAY新出的一款洁面仪 只需一分钟就可以在家享受美容院级微晶亮肤护理的美容仪 搭配微晶热能按摩啫喱，旋转时释放热能，深层清洁的同时，细致毛孔，平滑肤质 原先看网络的图片感觉是蛮大的一个仪器 实际上Pro-X微晶亮肤洁面仪袖珍可爱 Pro-X微晶亮肤洁面仪RMB360/套，价格亲民 （含一支机身，1个棉头，1个刷头，2个AA电池，1支20ml微晶热能按摩啫喱以及1支20ml去暗哑亮泽洁面乳） 主机拥有防水流线设计的双频电动手柄，不仅方便手持，利于清洁，也无惧沐潮湿环境 之前一直挺犹豫的 那个", 10);
	        echo "运行时间：".(microtime()-$begin);
	        var_dump($list);
		}
		
		
		/**
		 * 集合发分享的所有文本，将其放在alltext字段中
		 */
		public function concatShareText(){
			set_time_limit(0);
			$share_data = M("UserShareData");
			$list = $share_data ->where("details != '' and alltext=''")->select();
			foreach($list as $key=>$val){
				$details_arr = unserialize($val['details']);
				$string = "";
				foreach($details_arr as $k =>$v){
					$string.=$v['content']."。";
				}
				$share_data->where("shareid=".$val['shareid'])->setField("alltext", $string);
			}
		}
		
		
		/**
		 * 积分回滚
		 */
		public function scoreRollback(){
	     	$this->createPid(__FUNCTION__);
			$where['credit'] = array("gt",0);
			$where['type'] = 18;
			$where['state'] =0;
			$where['addtime']= array("elt",date("Y-m-d H:i:s",strtotime("-15 days")));
			$where['ifavalid'] = 1;
			$credit_stat = D("UserCreditStat");
			$user_mod=M("UserOrder");
			$list = $user_mod->field("ordernmb,userid,credit")->where($where)->select();
			foreach($list as $key =>$val){
				$credit_stat ->addUserCreditStat($val['userid'],"因订单（".$val['ordernmb']."）未支付，积分退还",$val['credit']);
				$user_mod ->where("ordernmb=".$val['ordernmb'])->setField("ifavalid", 0);
				
			}
			$this->destoryPid ( __FUNCTION__ );
			
		}
		
		
		
		/**
		 * 巳经完成的调查问卷转移到任务统计表
		 * @author lit
		 * 只跑一次
		 */
		public function TaskSurveyDataTranslate(){
		    $db = $db=C("DB_TYPE")."://".C("DB_USER").":".C("DB_PWD")."@".C("DB_HOST").":".C("DB_PORT")."/lolitabox_survey";
		    $list = M()->db(1,$db)->query("SELECT * FROM result");
		    $task_mod =D("Task");
		    M()->db(0);
		    foreach($list as $key =>$val){
		    	$info = $task_mod->ifCurrentSurveyTask($val['surveyid']);
		    	if($info){
		    		$task_mod->addUserTask($val['userid'],7,$info['id']);
		    	}
		    }
			
		}
		
		
		/**
		 * 晒盒批量生成图片
		 * @author lit
		 */
		public function BatchShowBoxImgCreate(){
	    	$this->createPid(__FUNCTION__);
			$sql = "SELECT user_share.userid,user_share.id AS shareid,user_share.resourceid AS orderid FROM  `user_share` WHERE resourcetype =4 AND resourceid >0 AND status>0 AND NOT EXISTS (
                    SELECT * 
                    FROM user_share_attach
                    WHERE shareid = user_share.id)";
			$user_attach_mod = M("UserShareAttach");
			$list = $user_attach_mod->query($sql);
			foreach($list as $key =>$val){
					$val['imgpath'] = orderImgCreate($val['orderid']);
					if($val['imgpath']){
						$val['status'] = 1;
						$val['title'] = "晒盒";
						$val['imgpath'] = "/".$val['imgpath'];
						$user_attach_mod->add($val);
					}
			}
			$this->destoryPid ( __FUNCTION__ );
		}
		
		
		/**
		 * 批量导入V3晒盒数据[分享并收录,保留原有时间]
		 * @author lit
		 */
		public function BatchImportV3ShowBox(){
			$this->createPid(__FUNCTION__);
			$category_mod = M("Category");
			$catelist = $category_mod ->where("ctype=11 AND cstatu=1")->select();
			foreach($catelist as $key =>$val){
				if($val['cid']!=609 && $val['cid']!=603){
					$catelist[$key]=$val['cid'];
				}else{
					unset($catelist[$key]);
				}
				
			}
			$where['cate_id'] = array("IN",$catelist);
			$list = M("Article") ->where($where)->select();
			$products_mod = M("Products");
			$user_share_attach_mod = M("UserShareAttach");
			$user_share_mod = M("UserShare");
			$user_share_data_mod = M("UserShareData");
			$atme_mod = D("UserAtme");
			foreach($list as $key =>$val){
				$data = array ();
				// 存分享主表
				$data ['blogid'] = $val ['id'];
				$data ['blogtype'] = 3;
				if ($user_share_mod->where ( $data )->find ()){
					continue;
				}
				
				$data ['userid'] = C("SHOW_BOX_USERID");
				$data ['posttime'] = strtotime ( $val ['add_time'] );
				$data ['commentnum'] = 0;
				//$data ['resourceid'] = $val['url'];
				//$data ['resourcetype'] = 1;
				$data ['status'] = $val['is_best'] ? 5:$val['status'];
				$share_id = $user_share_mod->add ( $data );
				
				// 分享内容数据
				$content_data = array ();
			//	$contents = strip_tags( $val ['info'] );
				$content_data ['shareid'] = $share_id;
				$content_data ['content'] = '#晒盒记# '.strip_tags( $val ['info'] );
				$content_data ['sharedata'] = '';
				$user_share_data_mod->add ( $content_data );
				
				
				// 分享附件数据
				$img_data = array ();
				$img_data ['imgpath'] = $val ['bigimg'];
				$img_data ['shareid'] = $share_id;
				$img_data ['userid'] = C("SHOW_BOX_USERID");
				$img_data ['title'] = $val ['orig'];
				$user_share_attach_mod->add($img_data);
				
				$atme_mod ->addAt($share_id,1,C("SHOW_BOX_USERID"),4,1,strtotime ( $val ['add_time'] ));
		
			}
			
			$this->destoryPid ( __FUNCTION__ );
		}
		
	   
				
		
}