<?php

/**
 * 判断是否包含敏感词
 * @param string $string 要搜索的字符串
 * @param bool $ifall  判断是否全部扫描(如果全部扫描，则返回包含的敏感词列表)
 * @return bool
 * @author litingting
 */
function filterwords($string,$ifall=false){
	mb_internal_encoding (  'UTF-8' );
	$filter_words_list = require_once ('./data/filter.words.php');
	$count = mb_strlen ( strip_tags($string) );
	$res  = array();
	$i = 0;
	for ($i=0;$i < $count;$i++ ) {
		$child = mb_substr ( $string, $i, 1 );
		$second = mb_substr ( $string, $i + 1, 1 );
		if (isset ( $filter_words_list [$child] [$second] )) {
			if (is_array (  $filter_words_list [$child] [$second] )) {

				foreach ( $filter_words_list [$child] [$second] as $key => $val ) {
					if ($val == 1) {
						 
						if($ifall ==false){    //如果不全部扫描则直接返回
							return $child . $second;
							 
						}else
							$res[] = $child . $second;
					}
					$len = mb_strlen ( $val );
					$str = mb_substr ( $string, $i + 2, $len );
					if ($str == $val) {

						if($ifall ==false){
							return $child . $second . $str;
						}else
							$res[] = $child . $second . $str ;
					}

				}
			}
		}

	}
	return empty($res) ? false : $res ;
}

/**
 * 发送邮件
 */
function sendtomail($address,$title,$message)
{
	vendor('PHPMailer.class#phpmailer');
	$mail=new PHPMailer();
	// 设置PHPMailer使用SMTP服务器发送Email
	$mail->IsSMTP();
	// 设置邮件的字符编码，若不指定，则为'UTF-8'
	$mail->CharSet='UTF-8';
	// 添加收件人地址，可以多次使用来添加多个收件人
	$mail->AddAddress($address);
	// 设置邮件正文
	$mail->Body=$message;
	// 设置邮件头的From字段。
	$mail->From=C('MAIL_ADDRESS');
	// 设置发件人名字
	$mail->FromName='LOLITABOX';
	// 设置邮件标题
	$mail->Subject=$title;
	$mail->WordWrap = 50; // 设定 word wrap
	//$mail->AddAttachment("/var/tmp/file.tar.gz"); // 附件1
	//$mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // 附件2
	$mail->IsHTML(true); // 以HTML发送

	// 设置SMTP服务器。
	$mail->Host=C('MAIL_SMTP');
	// 设置为“需要验证”
	$mail->SMTPAuth=true;
	// 设置用户名和密码。
	$mail->Username=C('MAIL_LOGINNAME');
	$mail->Password=C('MAIL_PASSWORD');

	// 发送邮件。
	return($mail->Send());
}

/**
 * 发送短信
 */
function sendtomess($dest,$content){
	return newSendtomess($dest,$content);
	if(!$dest) return false;
	if(!$content)  return false;
	$name=urlencode(C('MSG_NAME'));
	$pwd=urlencode(C('MSG_PWD'));
	$http=C('MSG_HTTP_IMPLEMENT');
	if(is_array($dest))
	{
		$dest=implode(",", $dest);
		if(count($dest)>50)   return false;
	}
	$content=iconv("UTF-8","GBK", $content);
	$content=strip_tags($content);
	$content=urlencode($content);
	$http.="?name=".$name."&pwd=".$pwd."&dest=".$dest."&content=".$content;
	$info=file_get_contents($http);
	$info=explode(":", $info);
	if($info[0]=='error')
	{
		return false;

	}
	if($info[0]=='success')
		return true;
}

/**
 * 新的发送短信方法[支付批量发送--在100以内]
 * @param int|array $dest
 * @param string $message
 */
function newSendtomess($dest,$message){
	if(empty($message)){
		return false;
	}
	$username = "lolitabox";
	$passwd = md5("O5cC7BsF");
	$message=iconv("UTF-8","GBK", $message);
	if(is_array($dest)){
		$p = implode(",",$dest);
	}else{
		$p=$dest;
	}
	$url = "http://api.app2e.com/smsSend.api.php?username=".$username."&pwd=".$passwd."&p=".$p."&msg=".urlencode($message);
	$info = curlPost(array(),$url);
	$info_arr = json_decode($info,true);
	Log::write("==================发送短信详情===================".date("Y-m-d H:i:s")."\r",INFO);
	Log::write(var_export($info_arr,true),INFO);
	if($info_arr['status']==100){
		return true;
	}else{
		if($info_arr['status']== 105){
			sendtomail("zhenghong@lolitabox.com","警告：企信通管理平台全额不足","企信通余额不足,请马上充值，以免影响");
		}
		return false;
	}
	
}

/**
 * 获取短信平台余额
 * @author lit
 */
function getMessBalance(){
	$username = "lolitabox";
	$passwd = md5("O5cC7BsF");
	$url = "http://api.app2e.com/getBalance.api.php?username=".$username."&pwd=".$passwd;
	$info = curlPost(array(),$url);
	$info_arr = json_decode($info,true);
	if($info_arr['status']!==100){
		return false;  //代表请求失败
	}
	unset($info_arr['status']);
	return $info_arr['balance'];
	
}

/**
 * 调用SENDMAIL指令发送邮件
 * @param unknown_type $address
 * @param unknown_type $title
 * @param unknown_type $message
 */
function systemSendmail($address, $title, $message){
	vendor('PHPMailer.class#phpmailer');
	$mail=new PHPMailer();
	$mail->IsSendmail();
	$mail->CharSet='UTF-8';
	$mail->From='service@lolitabox.com';
	$mail->FromName='LOLITABOX';
	$mail->Body=$message;
	$mail->Subject=$title;
	$mail->IsHTML(true);
	$mail->AddAddress($address);
	return ($mail->Send ());
}


function fileext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

function dir_path($path) {
	$path = str_replace('\\', '/', $path);
	if(substr($path, -1) != '/') $path = $path.'/';
	return $path;
}

function dir_create($path, $mode = 0777) {
	if(is_dir($path)) return TRUE;
	$ftp_enable = 0;
	$path = dir_path($path);
	$temp = explode('/', $path);
	$cur_dir = '';
	$max = count($temp) - 1;
	for($i=0; $i<$max; $i++) {
		$cur_dir .= $temp[$i].'/';
		if (@is_dir($cur_dir)) continue;
		@mkdir($cur_dir, 0777,true);
		@chmod($cur_dir, 0777);
	}
	return is_dir($path);
}

function dir_copy($fromdir, $todir) {
	$fromdir = dir_path($fromdir);
	$todir = dir_path($todir);
	if (!is_dir($fromdir)) return FALSE;
	if (!is_dir($todir)) dir_create($todir);
	$list = glob($fromdir.'*');
	if (!empty($list)) {
		foreach($list as $v) {
			$path = $todir.basename($v);
			if(is_dir($v)) {
				dir_copy($v, $path);
			} else {
				copy($v, $path);
				@chmod($path, 0777);
			}
		}
	}
	return TRUE;
}

function dir_list($path, $exts = '', $list= array()) {
	$path = dir_path($path);
	$files = glob($path.'*');
	foreach($files as $v) {
		$fileext = fileext($v);
		if (!$exts || preg_match("/\.($exts)/i", $v)) {
			$list[] = $v;
			if (is_dir($v)) {
				$list = dir_list($v, $exts, $list);
			}
		}
	}
	return $list;
}

function dir_tree($dir, $parentid = 0, $dirs = array()) {
	if ($parentid == 0) $id = 0;
	$list = glob($dir.'*');
	foreach($list as $v) {
		if (is_dir($v)) {
			$id++;
			$dirs[$id] = array('id'=>$id,'parentid'=>$parentid, 'name'=>basename($v), 'dir'=>$v.'/');
			$dirs = dir_tree($v.'/', $id, $dirs);
		}
	}
	return $dirs;
}

function dir_delete($dir) {
	$dir = dir_path($dir);
	if (!is_dir($dir)) return FALSE;
	$list = glob($dir.'*');
	foreach((array)$list as $v) {
		is_dir($v) ? dir_delete($v) : @unlink($v);
	}
	return @rmdir($dir);
}

/**
 * @param string $string 原文或者密文
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
 *
 * @example
 *
 *  $a = authcode('abc', 'ENCODE', 'key');
 *  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
 *
 *  $a = authcode('abc', 'ENCODE', 'key', 3600);
 *  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	// 随机密钥长度 取值 0-32;
	// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
	// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
	// 当此值为 0 时，则不产生随机密钥

	$keya = md5 ( substr ( $key, 0, 16 ) );
	$keyb = md5 ( substr ( $key, 16, 16 ) );
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';

	$cryptkey = $keya . md5 ( $keya . $keyc );
	$key_length = strlen ( $cryptkey );

	$string = $operation == 'DECODE' ? base64_decode ( substr ( $string, $ckey_length ) ) : sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
	$string_length = strlen ( $string );

	$result = '';
	$box = range ( 0, 255 );

	$rndkey = array ();
	for($i = 0; $i <= 255; $i ++) {
		$rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
	}

	for($j = $i = 0; $i < 256; $i ++) {
		$j = ($j + $box [$i] + $rndkey [$i]) % 256;
		$tmp = $box [$i];
		$box [$i] = $box [$j];
		$box [$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i ++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box [$a]) % 256;
		$tmp = $box [$a];
		$box [$a] = $box [$j];
		$box [$j] = $tmp;
		$result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
	}

	if ($operation == 'DECODE') {
		if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
			return substr ( $result, 26 );
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace ( '=', '', base64_encode ( $result ) );
	}
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
	if(function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif(function_exists('iconv_substr')) {
		$slice = iconv_substr($str,$start,$length,$charset);
		if(false === $slice) {
			$slice = '';
		}
	}else{
		$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("",array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice.'...' : $slice;
}

/**
 * 根据ID获取用户的空间首页地址
 * @param int	userid用户ID
 * @return string	http://xxxx/space/123.html 指定用户ID的空间首页地址
 */
function getSpaceUrl($userid){
	if(!$userid) return false;
	return PROJECT_URL_ROOT.getShortUrl("index", $userid);
}

/**
 * 快速重定向到指定用户主页方法
 * @param int $userid
 * @author zhenghong
 */
function gotoHome($userid){
	header("location:".U('home/index'));
}

function getProductUrl($pid){
	if(!$pid) return false;
	return PROJECT_URL_ROOT."products/".$pid.".html";
}

function getBrandUrl($id){
	if(!$id) return false;
	return PROJECT_URL_ROOT."brand/".$id.".html";
}

function getSolutionUrl($id){
	if(!$id) return false;
	return PROJECT_URL_ROOT."solution/{$id}.html";
}
function getShareUrl($id,$userid=""){
	if(empty($id))  return false;
	//return U('share/'.$id);
	return PROJECT_URL_ROOT."share/{$id}.html"; //满足后台调用此方法需求
}

function getSolutionDetailUrl($shareid){
	if(empty($shareid)) return false;
	return PROJECT_URL_ROOT."solution_share/{$shareid}";
}

/**
 * 盒子详情链接
 * @param int $boxid
 * @author penglele
 */
function getBoxUrl($boxid){
	if(!$boxid)
		return false;
	return PROJECT_URL_ROOT."buy/show/boxid/{$boxid}.html";
}

/**
 * 订单详情链接
 * @author penglele
 */
function getOrderDetailUrl($id){
	if(!$id)
		return false;
	return PROJECT_URL_ROOT."home/order_detail/id/{$id}.html";
}

/**
 * 对于图文混排的内容去掉所有html标签,何留img标签
 * 并将图片和文本进行分离
 * @param unknown_type $string
 * @return mixed
 */
function sliceGraphic($string)
{
	$string=strip_tags($string,"<img>"); //去掉html标记，保留img标签
	$string=str_replace('"', "'", $string);
	$string=str_replace("\r\n","",$string);
	$string=str_replace("&nbsp;"," ",$string);
	//$string= preg_replace("|<\s*img\s*.*src='([^\']*)'[^>]*>|","<img src='http://$host\${1}'>", $string);   //对所有的img的内容加上域名
	preg_match_all("|<\s*img.*src='([^']*)'[^>]*>|Ui", $string, $matches);
	$content_array=preg_split ('|<\s*img[^>]*>|i',$string);
	$count=count($content_array)>count($matches[1]) ? count($content_array):count($matches[1]);
	$return=array();
	for($i=0;$i<$count;$i++)
	{
		/* 		if(strpos($matches[1][$i], "http://")===false && $matches[1][$i])
			$matches[1][$i]="http://".$_SERVER['SERVER_NAME'].$matches[1][$i]; */
		$return[$i]['img']=$matches[1][$i];
		$return[$i]['content']=$content_array[$i];
	}
	return $return;
}

/**
 * 给一段带有网址的内容加上链接
 */
function text2links($str='') {
	$pattern="/(http\:\/\/|www\.)([0-9a-zA-Z\@\%\-\.\/\_\?\=(&amp\;)]*)/u";
	preg_match_all($pattern,$str,$arr);
	$arr=array_unique($arr[0]);
	if($arr){
		foreach($arr as $key=>$val){
			$val2=getPregUrl($val);
			$pattern2="/(\'|\")+($val2)/";
			if(!preg_match($pattern2,$str)){
				$new_val=getPregUrl($val);
				$replacement="<a href=\"".$val."\" target=\"_blank\" class=\"A_line3\">".$val."</a>";
				$str=preg_replace("/$new_val/", $replacement, $str);
			}
		}
	}
	return $str;
}

/**
 * 将一些特殊符号，如：【./_】等处理成正则匹配可以使用的方式
 * @param string $str
 * @author penglele
 */
function getPregUrl($str){
	if(!$str){
		return $str;
	}
	$part="/[\.\/\_\?\=\@\%\-]/u";
	preg_match_all($part,$str,$arr);
	$arr=array_unique($arr[0]);
	if($arr){
		foreach($arr as $key=>$val){
			$par="/\\".$val."/";
			$str=preg_replace($par, "\\".$val, $str);
		}
	}
	return $str;
}


/**
 * 生成短链接
 * @param unknown_type $long_url
 * @return multitype:string
 */
function shortUrl($url,$all=false){
   $base32 ="abcdefghijklmnopqrstuvwxyz0123456789";
      
   $hex = md5($url);
      
   $hexLen = strlen($hex);
      
   $subHexLen = $hexLen / 8;
      
   $output = array();
      
   for($i = 0; $i < $subHexLen; $i++) {
      
        $subHex = substr ($hex, $i * 8, 8);
      
        $int = 0x3FFFFFFF & (1 * ('0x'.$subHex));
      
        $out = '';
      
        for ($j = 0; $j < 6; $j++) {
      
             $val = 0x0000001F & $int;
             
             $out .= $base32[$val];
             
             $int = $int >> 5;
         }
         
         if($all===false){
         	return $out;
         }
         $output[] = $out;
      
   }
   return $output;
}


/**
 * 通过action_name和userid值获取短链接
 * @param unknown_type $url
 * @param unknown_type $userid
 * @param string $string 参数 格式为"/id/2"
 */
function getShortUrl($url,$userid,$string=''){
	$shorturl = shortUrl($url);
	$int=base_convert($userid, 10, 32);
	return "space/".substr($shorturl,0,3).$int.substr($shorturl,3).$string;
}



/**
 * 解密短链接
 * @param unknown_type $string
 */
function encodeShortUrl($string){
	$array= array("index","follow","fans","share");
	foreach($array as $key =>$val){
		$key = shortUrl($val);
		$list[$key]=$val;
	}
	$url = substr($string, 0,3).substr($string,-3);
	$userid = substr($string,3,-3);
	$userid = base_convert($userid,32,10);
	if($list[$url]){
		return array("url"=>$list[$url],"userid" =>$userid);
	}else{
		return false;
	}
}

/**
 * 获取并解析loli_from cookie
 * @return mixed $data,含有from_id和from_info等键值
 */
function getPromotionCookie(){
	$from_info=cookie("from");
	$from_arr=explode("_||_",$from_info);
	$data['from_id']=$from_arr[0];
	unset($from_arr[0]);
	$data['from_info']=trim(implode(" ",$from_arr));
	return $data;
}

/**
* 图片合成
* @param int $size 画布大小
* @param arr $filenamearr  图片路径数组
* @param int $rows 每排摆多少个图片
*/
function imageSynthesis($filenamearr, $order = "", $size = 500, $rows = 3, $exp = "jpg", $margin = 3) {
	if (empty ( $size )) {
		return false;
	}
	// 创建画布
	$img = imagecreatetruecolor ( $size, $size );
	$grey = imagecolorallocate ( $img, 255, 255, 255 );
	imagefill ( $img, 0, 0, $grey );
	
	$imgwidth = ($size - 2 * $margin) / $rows;
	$x = 0;
	$y = 0;
	$j = 0;
	for($i = 0; $i < count ( $filenamearr ); $i ++) {
		
		$filenamearr[$i] = str_replace("//", "/", "./".$filenamearr[$i]);
		if (! file_exists ( $filenamearr [$i] )) {
			continue;
		}
		$j ++;
		$image_p = imagecreatefromjpeg ( $filenamearr [$i] );
		
		$img_h = ($imgwidth / imagesx ( $image_p )) * imagesy ( $image_p );
		$img_w = $imgwidth;
		imagecopyresampled ( $img, $image_p, $x, $y, 0, 0, $img_w, $img_h, imagesx ( $image_p ), imagesy ( $image_p ) );
		
		$x += $imgwidth+$margin;
		if ($j % $rows == 0) {
			$y += $imgwidth + $margin;
			$x = 0;
		}
	}
	
	if (empty ( $order )) {
		$orderid = time()/300;
		
	}else{
		$orderid = substr($order,14);
	}
	$dir = "data/userdata/orderimg/" .$orderid;
	if (! is_dir ( $dir )) {
		dir_create ( $dir );
	}
	$filename = $dir ."/". $order . ".jpg";
	imagejpeg ( $img, $filename, 100 );
	return $filename;

}

/**
 * 生成订单图片
 * @param unknown_type $order
 * @author litingting
 */
function orderImgCreate($order){
	$order_product = M("UserOrderSendProductdetail");
	$product = M("Products");
	$item = M("InventoryItem");
	$plist = $order_product->where("orderid=".$order)->group("productid")->select();
	if(empty($plist)){
		return false;
	}
	$parr = array();
	foreach($plist as $key =>$val){
		$pid = $item ->where("id=".$val['productid'])->getField("relation_id");
		if($pid){
			if($pimg = $product->where("pid=".$pid)->getField("pimg")){
				$parr[] = $pimg;
			}
		}
	}
	$img=imageSynthesis($parr,$order);
	return $img;
}



/**
 * 通过订单ID获取图片地址
 * @param unknown_type $orderid
 * @author lit
 */
function getOrderImg($orderid){
   return  "data/userdata/orderimg/" .substr($orderid,14)."/".$orderid.".jpg";
}

/**
 * 模拟POST请求方法
 * @param unknown_type $post_data
 * @param unknown_type $post_url
 */
function curlPost($post_data,$post_url){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $post_url);
	curl_setopt($curl, CURLOPT_POST, 1 );
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0");
	$result = curl_exec($curl);
	$error = curl_error($curl);
	return $error ? $error : $result;
}

/**
 * 数字加密
 * @param int $num 要加密的数字
 * @param unknown_type $base
 * @param unknown_type $index
 */
function encodeNum($num, $base = 62, $index = false) {
	if (! $base) {
		$base = strlen ( $index );
	} elseif (! $index) {
		$index = substr ( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base );
	}
	$out = "";
	for($t = floor ( log10 ( $num ) / log10 ( $base ) ); $t >= 0; $t --) {
		$a = floor ( $num / pow ( $base, $t ) );
		$out = $out . substr ( $index, $a, 1 );
		$num = $num - ($a * pow ( $base, $t ));
	}
	return $out;
}

/**
 * 数字解密
 * @param unknown_type $num
 * @param unknown_type $base
 * @param unknown_type $index
 * @return number
 */

function decodeNum($num, $base = 62, $index = false) {
	if (! $base) {
		$base = strlen ( $index );
	} elseif (! $index) {
		$index = substr ( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base );
	}
	$out = 0;
	$len = strlen ( $num ) - 1;
	for($t = 0; $t <= $len; $t ++) {
		$out = $out + strpos ( $index, substr ( $num, $t, 1 ) ) * pow ( $base, $len - $t );
	}
	return $out;
}

function eLog($tag, $userId, $result, $reason, $level){
    Log::write("[".$tag."]"."[".$userId."]"."[".$result."]"."-".$reason, $level);
    if($level == ERROR){

    }
}

