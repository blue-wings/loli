<?php

class ProductsEvaluateAction extends CommonAction{


	/**
	 * 后台留言列表监控
	 */
	public function productEvaluateList(){
		//判断条件

		//评测关键字
		if(trim($this->_get("keywords"))) {
			$where["content"]=array("like","%".trim($this->_get("keywords"))."%");
		}
		//评测ID
		if($this->_get("evaluateid")) {
			$where["evaluateid"]=trim($this->_get("evaluateid"));
		}


		//用户ID
		if(trim($this->_get("userid"))) {
			$where["ProductsEvaluate.userid"]=trim($this->_get("userid"));
		}
        
		//用户ID范围查询
		if($this->_get("userid_from") && $this->_get("userid_to")==null){
			$where["ProductsEvaluate.userid"]=array("egt",trim($this->_get("userid_from")));
		}else if($this->_get("userid_from")==null && $this->_get("userid_to")){
			$where["ProductsEvaluate.userid"]=array("elt",trim($this->_get("userid_to")));
		}else  if($this->_get("userid_from")&& $this->_get("userid_to")){
			$where["ProductsEvaluate.userid"]=array('between',array(trim($this->_get("userid_from")),trim($this->_get("userid_to"))));
		}

		//产品
		if(trim($this->_get("pname"))){
			$where['Products.pname']=array("like","%".trim($this->_get("pname"))."%");
		}
		if($this->_get("productid")) {
			$where["ProductsEvaluate.productid"]=trim($this->_get("productid"));
		}
		

		//产品品牌
		$product_mod=M('Products');
		if($this->_get("brandcid")){
			$brand_list=$product_mod->field('pid')->where("brandcid=".$this->_get('brandcid'))->select();
			$count=count($brand_list);
			for($i=0;$i<$count;$i++){
				$arr_pid[]=$brand_list[$i]['pid'];
			}
			$where['ProductsEvaluate.productid']=array('in',implode(',',$arr_pid));
		}
		//评测数据
		if($this->_get("usertype")){
			$user_mod=M("Users");
			//提取合作方USERID列表
			$uid_list=$user_mod->field(userid)->where(array("usermail"=>array('LIKE',"%lolitabox.com%")))->select();
			foreach($uid_list as $array_uid){
				$uid[]=$array_uid["userid"];
			}

			switch($this->_get("usertype")){
				case 100:
					//echo "100";
					//提取合作方数据
					$where['ProductsEvaluate.userid']=array('in',$uid);
					break;

				case 10:
					$where['ProductsEvaluate.userid']=array('not in',$uid);
					break;
			}
		}

		//评测日期
		if($this->_get("from") && $this->_get("to")==null){
			$where["ProductsEvaluate.postdate"]=array("egt",trim($this->_get("from"))." 00:00:00");
		}else if($this->_get("from")==null && $this->_get("to")){
			$where["ProductsEvaluate.postdate"]=array("elt",trim($this->_get("to"))." 23:59:59");
		}else  if($this->_get("from")&& $this->_get("to")){
			$where["ProductsEvaluate.postdate"]=array('between',array(trim($this->_get("from"))." 00:00:00",trim($this->_get("to"))." 23:59:59"));
		}

		//内容级别
		if($this->_get("contentlevel")){
			$where["ProductsEvaluate.contentlevel"]=$this->_get("contentlevel");
		}

		if($this->_get("stickies")){
			if($this->_get("stickies")==1){
				$where["ProductsEvaluate.toptime"]=array('gt',0);
			}else if($this->_get("stickies")==2){
				$where["ProductsEvaluate.toptime"]=array('eq',0);
			}
		}


		if($this->_get("weibo")==2){
			$where['ProductsEvaluate.if_sync_weibo']=0;
		}else if($this->_get("weibo")==1){
			$where['ProductsEvaluate.if_sync_weibo']=1;
		}

		$ProductEvaluate=D("ProductsEvaluateView");
		$ProductsEvaluateImg=M("ProductsEvaluateImg");
		$productsEvaluate=M('ProductsEvaluate');
		import("@.ORG.Page"); //导入分页类库
		$count=$ProductEvaluate->where($where)->count(); //记录总数
		$p = new Page($count, 25); //每页显示25条记录
		$list=$ProductEvaluate->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('evaluateid desc')->select();
		import("ORG.Util.String");

		foreach($list as $key=>$item){
			$item["content"]=strip_tags($item["content"]);
			$list[$key]["summary"]=trim(String::msubstr($item["content"],0,200));

			$evaluate_img=$ProductsEvaluateImg->where("evaluateid=".$item["evaluateid"])->select();
			if($evaluate_img){
				$list[$key]["evaluate_img"]=$evaluate_img;
			}
		}
		$brand_list=M('ProductsBrand')->field('id,name')->select();
		$page = $p->show();
		$this->assign("page", $page);
		$this->assign('list',$list);
		$this->assign('brand_list',$brand_list);
		$this->display("productevaluate");

	}

	//ajax操作 修改内容级别
	//add by zhaoxiang
	public function  editcontentlevel(){
		if($_POST['productid'])
		{
			$productsEvaluate=M('productsEvaluate');

			$where['productid']=$this->_post("productid");
			$where['evaluateid']=$this->_post("evaluateid");
			$ret=$productsEvaluate->where($where)->getField('contentlevel');
			$userid=$productsEvaluate->where($where)->getField('userid');
			if($ret==1){
				$data['contentlevel']=5;
			}else{
				$data['contentlevel']=1;
			}
			$result=$productsEvaluate->where($where)->save($data);

			if($result){
				$user_credit_stat_model = D("index://UserCreditStat");
				if($ret==1){
					$user_credit_stat_model ->optCreditSet($userid,'evaluate_to_digest');
				}else{
					$user_credit_stat_model ->optCreditSet($userid,'evaluate_cancl_digest');
				}
				$this->ajaxReturn($data['contentlevel'],$result,1);
			}else{
				$this->ajaxReturn($data['contentlevel'],'no failed!',0);
			}


		}else{
			$this->ajaxReturn('参数不正确','参数不正确',0);
		}
	}






	/**
	 * 删除评测信息
	 */
	public function delEvaluate(){
		$id=$_REQUEST["id"];
		if($id){
			$ProductsEvaluate=M("ProductsEvaluate");
			if(is_array($id)){
				//多选删除
				$delcount=count($id);
				for($i=0;$i<$delcount;$i++){
					$b_result=$this->delEvaluateExtend($id[$i]);
					if(false!==$b_result) {
						$delid[]=$id[$i];
					}
				}
				if(count($delid)<$delcount){
					$this->error('删除操作完成部分,ID范围：'.implode(",",$delid));
				}
				else {
					$this->success('操作成功，ID范围：'.implode(",",$delid));
				}
			}
			else{
				//删除
				if(false!==$this->delEvaluateExtend($id)){
					$this->success('操作成功');
				}
				else {
					$this->error('操作失败：'.$ProductsEvaluate->getDbError());
				}
			}
		}
		else{
			$this->error('没有选择要删除的信息');
		}
	}

	/*
	* 	修改置顶状态
	*	0为不置顶
	*	置顶存当前时间戳
	*	add by zhaoxiang
	*/

	public function stickies(){
		if($_POST['evaluateid']){
			$evaluate_mod=M('ProductsEvaluate');
			$where['evaluateid']=$_POST['evaluateid'];
			$toptime=$evaluate_mod->where($where)->getField('toptime');
			if($toptime==0){
				$data['toptime']=time();
			}else{
				$data['toptime']='0';
			}
			$result=$evaluate_mod->where($where)->data($data)->save($data);
			$this->ajaxReturn($data['toptime'],$result,1);
		}
	}





	/**
	 * 评测回复管理
	 */
	public function replylist(){

		//判断条件
		$keywords=$this->_get("keywords");
		if(trim($keywords)) {
			$where["replycontent"]=array("like","%$keywords%");
		}

		$evaluateid=$this->_get("evaluateid");
		if(trim($userid)){
			$where["ProductsEvaluateReply.evaluateid"]=$evaluateid;
		}

		$userid=$this->_get("userid");
		if(trim($userid)){
			$where["ProductsEvaluateReply.userid"]=$userid;
		}

		$evaluateid=$this->_get("evaluateid");
		if(trim($evaluateid)) {
			$where["ProductsEvaluateReply.evaluateid"]=$evaluateid;
		}

		$ProductsEvaluateReply=D("ProductsEvaluateReplyView");
		import("@.ORG.Page"); //导入分页类库
		$count=$ProductsEvaluateReply->where($where)->count(); //记录总数
		$p = new Page($count, 15); //每页显示25条记录
		$list=$ProductsEvaluateReply->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('replyid desc')->select();
		$page = $p->show();
		$this->assign("page", $page);
		$this->assign('list',$list);
		$this->display("productevaluate_reply");

	}


	/**
	 * 删除评测信息
	 */
	public function delEvaluateReply(){
		$id=$_REQUEST["id"];
		if($id){
			$ProductsEvaluateReply=M("ProductsEvaluateReply");
			if(is_array($id)){
				//多选删除
				$delcount=count($id);
				for($i=0;$i<$delcount;$i++){
					$b_result=$ProductsEvaluateReply->delete($id[$i]);
					if(false!==$b_result) {
						$delid[]=$id[$i];
					}
				}
				if(count($delid)<$delcount){
					$this->error('删除操作完成部分,ID范围：'.implode(",",$delid));
				}
				else {
					$this->success('操作成功，ID范围：'.implode(",",$delid));
				}
			}
			else{
				//连接删除
				if(false!==$ProductsEvaluateReply->delete($id)){
					$this->success('操作成功');
				}
				else {
					$this->error('操作失败：'.$ProductsEvaluate->getDbError());
				}
			}
		}
		else{
			$this->error('没有选择要删除的信息');
		}
	}

	/**
	 * 评论图片管理
	 */
	public function imglist(){
		//判断条件

		$userid=$_REQUEST["userid"];
		if($userid) {
			$where["ProductsEvaluateReply.userid"]=$userid;
			$pageparam["userid"]=$userid;
		}
		$evaluateid=$_REQUEST["evaluateid"];
		if($evaluateid) {
			$where["ProductsEvaluateReply.evaluateid"]=$evaluateid;
			$pageparam["evaluateid"]=$evaluateid;
		}

		//组装分页时的页面参数
		$pageparamcount=count($pageparam);
		if($pageparamcount>0){
			while(list($param,$value)=each($pageparam)){
				$arraypageparam[]="$param=$value";
			}
			$strpageparam=implode('&',$arraypageparam);
		}

		$ProductsEvaluateImg=D("ProductsEvaluateImgView");
		import("@.ORG.Page"); //导入分页类库
		$count=$ProductsEvaluateImg->where($where)->count(); //记录总数
		$p = new Page($count, 10,$strpageparam); //每页显示25条记录
		$list=$ProductsEvaluateImg->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('imgid desc')->select();
		$page = $p->show();
		$this->assign("page", $page);
		$this->assign('list',$list);
		$this->display("productevaluate_img");
	}

	/**
	 * 删除评测图片信息
	 */
	public function delEvaluateImg(){
		$id=$_REQUEST["id"];
		if($id){
			$ProductsEvaluateImg=M("ProductsEvaluateImg");
			if(is_array($id)){
				//多选删除
				$delcount=count($id);
				for($i=0;$i<$delcount;$i++){
					$imgpath=$ProductsEvaluateImg->where("imgid=".$id[$i])->getField("imgpath");
					$b_result=$ProductsEvaluateImg->delete($id[$i]);
					if(false!==$b_result) {
						$delid[]=$id[$i];
						$this->delImg($imgpath);
					}
				}
				if(count($delid)<$delcount){
					$this->error('删除操作完成部分,ID范围：'.implode(",",$delid));
				}
				else {
					$this->success('操作成功，ID范围：'.implode(",",$delid));
				}
			}
			else{
				//连续删除
				$imgpath=$ProductsEvaluateImg->where("imgid=".$id)->getField("imgpath");
				if(false!==$ProductsEvaluateImg->delete($id)){
					$this->delImg($imgpath);
					$this->success('操作成功');
				}
				else {
					$this->error('操作失败：'.$ProductsEvaluateImg->getDbError());
				}
			}
		}
		else{
			$this->error('没有选择要删除的信息');
		}
	}

	/**
	 * 根据评测ID删除评测，评测的回复及图片，用户发布评测行为
	 * update to litingting 2013-01-30
	 */
	 protected function delEvaluateExtend($id){
		if(!$id) return false;
		$userid=M("ProductsEvaluate")->where("evaluateid=".$id)->getField("userid");
		//删除评测
		if(false ===M("ProductsEvaluate")->delete($id))
			return false;
		
		//删除用户相关行为
		if($userid)
		{
			$where['userid']=$userid;
			$where['type']="post_evaluateid";
			$where['whoid']=$id;
			M("UserBehaviourRelation")->where($where)->save(array('status'=>0));
		}
	
		//删除回复
		$ProductsEvaluateReply=M("ProductsEvaluateReply");
		$ProductsEvaluateReply->where("evaluateid=".$id)->delete();
		//删除评测的附属图片
		$ProductsEvaluateImg=M("ProductsEvaluateImg");
		$imglist=$ProductsEvaluateImg->field('imgpath')->where("evaluateid=".$id)->select();
		while(list($k,$v)=each($imglist)){
			$this->delImg($imglist[$k]["imgpath"]);
		}
		$ProductsEvaluateImg->where("evaluateid=".$id)->delete();
		$user_credit_mod=D("index://UserCreditStat");
	    $user_credit_mod->optCreditSet($userid,"evaluate_deletepost");
		return true;
	}

	/**
	 * 删除评测图片物理文件
	 */
	protected function delImg($path){
		//删除物理文件
		@unlink(USER_DATA_DIR_ROOT.$path);
	}




	/**
	 * 自动发评测信息
	 */
	public function autoPostEvaluate(){
		$ProductsEvaluate=M("ProductsEvaluate");
		$filedata_dir=DATA_DIR_ROOT."/adata/0601/";
		$arrayfilelist=$this->scanFileNameRecursivly($filedata_dir);
		while(list($k,$fileitem)=each($arrayfilelist)){
			//遍历文件，进行数据导入前准备
			$array_fileinfo=pathinfo($fileitem);
			//print_r($array_fileinfo);
			$productid=$array_fileinfo["filename"];  //分析得到产品ID
			//echo $productid;
			//做基本的逻辑判断
			if(!$productid) break; //如果得到的文件名不是一个数字，则说明文件符合要求
			$arrayfilecontent=file($fileitem);
			$postcount=count($arrayfilecontent); //统计需要发布的内容数
			$userlist=$this->getPostUser($productid,$postcount);
			//print_r($userlist);exit;
			$fromdate=time()-60*60*24*3;
			for($n=0;$n<$postcount;$n++){
				$t_star=rand(60,3600);
				$t_end=rand($t_star,rand($t_star,1800));
				$time_i+=rand(180,300);
				$postdate=date("Y-m-d H:i:s",$fromdate+$time_i);
				$arraycontent=explode("|||",$arrayfilecontent[$n]);
				$title=$arraycontent[0];
				$content=$arraycontent[1];
				if(empty($title) || empty($content)) {
					break;
				}
				$userid=trim($userlist[$n]['userid']);
				$data=array(
				"productid"=>$productid,
				"title"=>$title,
				"content"=>$content,
				"userid"=>$userid,
				"postdate"=>$postdate
				);
				if($ProductsEvaluate->add($data)){
					var_dump($data);
				}
			}
		}
	}

	/**
	 *提取当前产品所允许的评测用户列表
	 */
	public function getPostUser($productid=49,$icount=50){
		$max_userid=584; //评测用户ID最大
		$min_userid=484; //评测用户ID最小
		for($i=$min_userid;$i<=$max_userid;$i++){
			$userid[]=$i;
		}
		$str_userid=join($userid,",");
		//echo $str_userid;
		$Model=new Model();
		$sql="SELECT userid FROM users WHERE (userid NOT IN (SELECT userid FROM products_evaluate WHERE productid=$productid)) AND userid>=$min_userid AND userid<=$max_userid ORDER BY rand() LIMIT 0,$icount";
		$userlist=$Model->query($sql);
		return $userlist;
	}

	/**
	 * 用户评测统计
	 */
	public function userEvaluateStat(){
		$starttime=$_REQUEST['starttime'];
		$endtime=$_REQUEST['endtime'];
		if($starttime && $endtime)
		{
			$sql="stat_date between '".$starttime."' and '".$endtime."'";
			$stat_date=$starttime."到".$endtime;
			$param['starttime']=$starttime;
			$param['endtime']=$endtime;
		}
		elseif($starttime && !$endtime){
			$sql="stat_date >= '".$starttime."'";
			$param['starttime']=$starttime;
			$stat_date=$starttime."之后";
		}
		elseif(!$starttime && $endtime)
		{
			$sql="stat_date <= '".$endtime."'";
			$param['endtime']=$endtime;
			$stat_date=$endtime."之前";
		}
		else{
			$sql='';
			$stat_date="所有纪录";
		}
		$strpageparam='';
		if($param) {
			while(list($p,$value)=each($pageparam)){
				$arraypageparam[]="$p=$value";
			}
			$strpageparam=implode('&',$arraypageparam);
		}

		$evaluate_stat_mod=M("ProductsEvaluateStat");
		$count=$evaluate_stat_mod->where($sql)->count();
		import("@.ORG.Page"); //导入分页类库
		$p = new Page($count, 20,$strpageparam); //每页显示30条记录
		$list=$evaluate_stat_mod->where($sql)->limit($p->firstRow . ',' . $p->listRows)->order('stat_date desc')->select();
		$total_list=$evaluate_stat_mod->field("MAX( stat_date )  as max ,MIN( stat_date )  as min ,SUM( inner_post_num ) as b, SUM( real_post_num ) as c, SUM( real_user_num ) as d, SUM( reply_num ) as e , SUM( reply_user_num ) as f, SUM( post_total ) as g, SUM( user_total) as h")->where($sql)->select();
		$page = $p->show();
		$this->assign("list",$list);
		$this->assign("page",$page);
		$this->assign("total_list",$total_list);
		$this->assign("stat_date",$stat_date);
		$this->display();
	}


	function exportSohuEvaluateContent(){
		$open_mod=M("userOpenid");
		$evaluate_mod=M("productsEvaluate");

		$where['type']='sohu';
		$where['uid']=array('exp','<> 0');

		$sohuUserList=$open_mod->where($where)->Distinct('uid')->field("uid,openid,info")->order('uid')->select();

		$str="搜狐用户id,搜狐用户昵称,综合评分,报告标题,报告内容,提交时间\n";
		foreach ($sohuUserList AS $key=>$value){
			$list=array();
			$evaluate_array=array();
			$evaluate_array=$evaluate_mod->where(array('userid'=>$value['uid']))->field('title,content,postdate,score_avg')->select();

			if(empty($evaluate_array)){
				continue;
			}else{
				foreach ($evaluate_array AS $v){
					$con=trim(strip_tags($v['content'],'<img>'));
					$cont=preg_replace("'([\r\n])[\s]+'", "", $con);
					$str.=$value['openid'].','.$value['info'].','.$v['score_avg'].','.$v['title'].','.$cont.','.$v['postdate']."\n";
				}
			}
		}
		outputExcel ( iconv ( "UTF-8", "GBK","报告" ), $str );
		exit();
	}

	function exportSohulogList(){
		$index_mod=M("userBlogIndex");
		$content_mod=M("user_blog_content");
		$open_mod=M("userOpenid");
		$str="搜狐用户id,搜狐用户昵称,报告标题,报告内容,提交时间\n";
		$list=array(1515,1477,1467,1461,1450,1406,1395,1372);

		natcasesort($list);

		foreach ($list AS $key => $value)
		{
			$index_arr=$index_mod->where(array('id'=>$value))->field('id,userid,title,postdate')->find();

			$userdata=$open_mod->where(array('uid'=>$index_arr['userid'],'type'=>'sohu'))->field('openid,info')->find();

			$cont_arr=$content_mod->where(array('parentid'=>$index_arr['id']))->field('img,content')->select();
			$conetent='';
			foreach ($cont_arr AS $ck => $cv)
			{
				
				$con=trim(strip_tags($cv['content'],'<img>'));
				$conn=preg_replace("','", "", $con);
				
				$cont=preg_replace("'([\r\n])[\s]+'", "", $conn);
				
				if(empty($cv['img'])){
					$conetent.=$cont;
				}else{
					$conetent.=$cont_arr['img'].$cont;
				}
			}

			$userdata['title']=$index_arr['title'];
	
			$userdata['postdate']=date('Y-m-d H:i:s',$index_arr['postdate']);
			$userdata['content']=$conetent;

			$str.=$userdata['openid'].','.$userdata['info'].','.$userdata['title'].','.$userdata['content'].','.$userdata['postdate']."\n";
		}

		outputExcel ( iconv ( "UTF-8", "GBK","报告" ), $str );
		exit();
	}
}
?>