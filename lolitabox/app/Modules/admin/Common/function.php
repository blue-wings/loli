<?php

//导出excel数据
function outputExcel($filename,$str,$ext=".csv") {
	header('Content-type: application/vnd.ms-excel');
	header('Content-Disposition: attachment; filename="'.$filename.$ext.'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
	header('Content-Transfer-Encoding: binary');
	header('Pragma: public');
	header('Pragma: no-cache');
	echo iconv("UTF-8", "GBK", $str);
	return false;
}

//读取csv文件
function  get_csv_data($file)
{
	if (file_exists($file)) {
		$rs =array();
		$handle = fopen ($file,"r");
		while ($data = fgetcsv ($handle)) {
			$data =  array_map("_gbkToUtf8",$data);
			$data[0]=substr($data[0],1);
			$rs[]=$data;
		}
		fclose ($handle);
		array_shift($rs);
		return $rs;
	}else{
		return null;
	}
}

//gbk转成utf8
function _gbkToUtf8($data){
	return iconv ("GBK","UTF-8",trim($data));
}

// 缓存文件
function cmssavecache($name = '', $fields = '') {
	$Model = D ( $name );
	$list = $Model->select ();
	$data = array ();
	foreach ( $list as $key => $val ) {
		if (empty ( $fields )) {
			$data [$val [$Model->getPk ()]] = $val;
		} else {
			// 获取需要的字段
			if (is_string ( $fields )) {
				$fields = explode ( ',', $fields );
			}
			if (count ( $fields ) == 1) {
				$data [$val [$Model->getPk ()]] = $val [$fields [0]];
			} else {
				foreach ( $fields as $field ) {
					$data [$val [$Model->getPk ()]] [] = $val [$field];
				}
			}
		}
	}
	$savefile = cmsgetcache ( $name );
	// 所有参数统一为大写
	$content = "<?php\nreturn " . var_export ( array_change_key_case ( $data, CASE_UPPER ), true ) . ";\n?>";
	file_put_contents ( $savefile, $content );
}


function getStatus($status, $imageShow = false) {
	switch ($status) {
		case 0 :
			$showText = '禁用';
			$showImg = '<IMG SRC="__PUBLIC__/images/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="禁用">';
			break;
		case 2 :
			$showText = '待审';
			$showImg = '<IMG SRC="__PUBLIC__/images/prected.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="待审">';
			break;
		case - 1 :
			$showText = '删除';
			$showImg = '<IMG SRC="__PUBLIC__/images/del.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="删除">';
			break;
		case 1 :
		default :
			$showText = '正常';
			$showImg = '<IMG SRC="__PUBLIC__/images/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="正常">';

	}
	return ($imageShow === true) ?  $showImg  : $showText;

}

function getNodeName($id) {
	if (Session::is_set ( 'nodeNameList' )) {
		$name = Session::get ( 'nodeNameList' );
		return $name [$id];
	}
	$Group = D ( "Node" );
	$list = $Group->getField ( 'id,name' );
	$name = $list [$id];
	Session::set ( 'nodeNameList', $list );
	return $name;
}

function getNodeGroupName($id) {
	if (empty ( $id )) {
		return '未分组';
	}
	if (isset ( $_SESSION ['nodeGroupList'] )) {
		return $_SESSION ['nodeGroupList'] [$id];
	}
	$Group = D ( "Group" );
	$list = $Group->getField ( 'id,title' );
	$_SESSION ['nodeGroupList'] = $list;
	$name = $list [$id];
	return $name;
}

function getCardStatus($status) {
	switch ($status) {
		case 0 :
			$show = '未启用';
			break;
		case 1 :
			$show = '已启用';
			break;
		case 2 :
			$show = '使用中';
			break;
		case 3 :
			$show = '已禁用';
			break;
		case 4 :
			$show = '已作废';
			break;
	}
	return $show;

}

function showStatus($status, $id) {
	switch ($status) {
		case 0 :
			$info = "<a href=".__URL__."/resume/id/".$id.">恢复</a>";
			break;
		case 2 :
			$info = "<a href=".__URL__."/pass/id/".$id.">批准</a>";
			break;
		case 1 :
			$info = "<a href=".__URL__."/forbid/id/".$id.">禁用</a>";
			break;
		case - 1 :
			$info = "<a href=".__URL__."/recycle/id/".$id.">还原</a>";
			break;
	}
	return $info;
}


function getGroupName($id) {
	if ($id == 0) {
		return '无上级组';
	}
	if ($list = F ( 'groupName' )) {
		return $list [$id];
	}
	$dao = D ( "Role" );
	$list = $dao->select( array ('field' => 'id,name' ) );
	foreach ( $list as $vo ) {
		$nameList [$vo ['id']] = $vo ['name'];
	}
	$name = $nameList [$id];
	F ( 'groupName', $nameList );
	return $name;
}
function sort_by($array, $keyname = null, $sortby = 'asc') {
	$myarray = $inarray = array ();
	# First store the keyvalues in a seperate array
	foreach ( $array as $i => $befree ) {
		$myarray [$i] = $array [$i] [$keyname];
	}
	# Sort the new array by
	switch ($sortby) {
		case 'asc' :
			# Sort an array and maintain index association...
			asort ( $myarray );
			break;
		case 'desc' :
		case 'arsort' :
			# Sort an array in reverse order and maintain index association
			arsort ( $myarray );
			break;
		case 'natcasesor' :
			# Sort an array using a case insensitive "natural order" algorithm
			natcasesort ( $myarray );
			break;
	}
	# Rebuild the old array
	foreach ( $myarray as $key => $befree ) {
		$inarray [] = $array [$key];
	}
	return $inarray;
}

/**
	 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
	 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
	 +----------------------------------------------------------
 * @return string
	 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat ( '0123456789', 3 );
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) { //位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
	}
	if ($type != 4) {
		$chars = str_shuffle ( $chars );
		$str = substr ( $chars, 0, $len );
	} else {
		// 中文随机字
		for($i = 0; $i < $len; $i ++) {
			$str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
		}
	}
	return $str;
}
function pwdHash($password, $type = 'md5') {
	return hash ( $type, $password );
}


function js_location($url,$self_or_top='top'){
	if (!in_array($self_or_top,array('top','self'))) $self_or_top='top';
	echo "<script language='javascript'>{$self_or_top}.location.href='$url';</script>";
}



//发送邮件和短信的任务列表[用户列表]
function tasklist($art_id,$where,$type){

	$article_mod=M("article");
	$article_info=$article_mod->field("title,info")->getById($art_id);
	$title=$article_info['title'];
	$content=$article_info['info'];
	$usermodel=D("User");

	if(empty($where)){
		return false;//防止用户全部选中
	}else{
		$list=$usermodel->where($where)->select();
	}

	$sendlog=M("UserSendTask");
	$data=array();
	for($i=0;$i<count($list);$i++)
	{
		$data ['content'] = preg_replace("'([\r\n])[\s]+'", "", $content);
		if($type==1)
		$data ['receiver'] = $list [$i]['usermail'];
		if($type==2){
			if($list[$i]['telphone'] && strlen($list[$i]['telphone'])==11)
			$data['receiver'] = $list[$i]['telphone'];
			else continue;
		}
		$data['userid']= $list[$i]['userid'];
		$data['title'] = $title;
		$data['type'] = $type;
		$data['add_time'] = time ();
		$data['status'] = 0;
		$data['operator'] = $_SESSION["loginUserName"];
		$sendlog->add ( $data );
		unset($data);
	}
	return true;
}
//发送邮件和短信的任务列表[订单列表]
//$list代表结果集的一行数据
function ordertasklist($list,$type){

	$proxy=$list['proxyorderid'];

	if(empty($proxy)){
		return ;
	}else{
		$sender=$list['proxysender'];
		$title=$list['boxname']?$list['boxname']:$list['name'];
		$orderid=$list['orderid']?$list['orderid']:$list['ordernmb'];
		$content="您订购的".$title."已发出,快递单号为$sender$proxy,可登录个人中心查看进度";
		if($type==2)
		$content.="【萝莉盒】";
		$data ['content'] = $content;
		if ($type == 1)
		$data ['receiver'] = $list ['usermail'];
		if ($type == 2) {
			if ($list ['telphone'] && strlen($list['telphone'])==11)
			$data ['receiver'] = $list ['telphone'];
			else
			echo $list['userid']."<br>";
			//continue;
		}
		$data ['userid'] = $list ['userid'];
		$data ['title'] = $title."-订单号".$orderid;
		$data ['type'] = $type;
		$data ['add_time'] = time ();
		$data ['status'] = 0;
		$data ['operator'] = $_SESSION["loginUserName"];
		$sendlog=M('UserSendTask');
		$sendlog->add ( $data );
		//echo $sendlog->getLastSql().";";
	}
}


//查看发货详情
function proxy_info($typeCom,$typeNu,$volicode=''){
	$company=array('中通'=>'zhongtong','申通'=>'shentong','圆通'=>'yuantong','cces'=>'cces','顺丰'=>'shunfeng','韵达'=>'yunda');
	if(isset($company[$typeCom])){
		$typeCom=$company[$typeCom];
	}
	$get_content=@file_get_contents("http://www.kuaidi100.com/query?type=$typeCom&postid=$typeNu&id=1&volicode=$volicode");
	$get_content=json_decode(str_replace("'",'"',$get_content),true);
	return $get_content;
}
/**
   +----------------------------------------------------------
   * 过滤变量
   +----------------------------------------------------------  
   * @access  public    return  string    返回过滤完成的变量
   +-----------------------------------------------------------
   * @author zhaoxiang 2013.1.18
 */
function filterVar($value){
	return htmlspecialchars(addslashes(trim($value)));
}
?>