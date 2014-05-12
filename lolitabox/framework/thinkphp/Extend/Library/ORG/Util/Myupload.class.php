<?php
require_once('JSON.php');
//上传类
class Myupload {

	//文件保存目录路径
	public $save_path = "";
	//文件保存目录URL
	public $save_url = '/data/userdata/';
	//定义允许上传的文件扩展名
	public $ext_arr = array(
	'image' => array('gif', 'jpg', 'jpeg', 'png'),
	'flash' => array(),
	'media' => array(),
	'file' => array()
	);
	//最大文件大小默认5M
	public $max_size = 5242880;

	//最大宽度(超出500做缩略图)
	public $pic_max_width = 500;
	public $pic_max_height = 500;
	
	public function __construct()
	{
		
	}

	//检测图片上传
	public function checkImg()
	{
		if (!empty($_FILES['imgFile']['error']))
		{
			switch($_FILES['imgFile']['error']){
				case '1':
					$error = '超过php.ini允许的大小。';
					break;
				case '2':
					$error = '超过表单允许的大小。';
					break;
				case '3':
					$error = '图片只有部分被上传。';
					break;
				case '4':
					$error = '请选择图片。';
					break;
				case '6':
					$error = '找不到临时目录。';
					break;
				case '7':
					$error = '写文件到硬盘出错。';
					break;
				case '8':
					$error = 'File upload stopped by extension。';
					break;
				case '999':
				default:
					$error = '未知错误。';
			}
			$this->_alert($error);
		}else{
			return true;
		}
	}

	//检测文件上传
	public  function uploadfile()
	{
		if (empty($_FILES) === false)
		{
			//原文件名
			$file_name = $_FILES['imgFile']['name'];
			//服务器上临时文件名
			$tmp_name = $_FILES['imgFile']['tmp_name'];
			//文件大小
			$file_size = $_FILES['imgFile']['size'];
			$imginfo=getimagesize($tmp_name);
			$max_width = $imginfo [0];
			$max_height = $imginfo [1];
			if ($max_width > 500) {
				$this->pic_max_height = $max_height * (500 / $max_width);
			} else {
				$this->pic_max_height = $max_height;
			}
			
			//检查文件名
			if (!$file_name) {
				$this->_alert("请选择文件。");
			}
			//检查目录
			if (@is_dir($this->save_path) === false) {
				$this->_alert($this->save_path);
				$this->_alert("上传目录不存在。");
			}
			//检查目录写权限
			if (@is_writable($this->save_path) === false) {
				$this->_alert("上传目录没有写权限。");
			}
			//检查是否已上传
			if (@is_uploaded_file($tmp_name) === false) {
				$this->_alert("上传失败。");
			}
			//检查文件大小
			if ($file_size > $this->max_size) {
				$size=($this->max_size)/1024/1024;
				$this->_alert("图片最大不能超过".$size."M");
			}
			//检查目录名
			$dir_name = empty($_REQUEST['dir']) ? 'image' : trim($_REQUEST['dir']);
			if (empty($this->ext_arr[$dir_name])) {
				$this->_alert("目录名不正确。");
			}
			//获得文件扩展名
			$temp_arr = explode(".", $file_name);
			$file_ext = array_pop($temp_arr);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);
			//检查扩展名
			if (in_array($file_ext, $this->ext_arr[$dir_name]) === false) {
				$this->_alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $this->ext_arr[$dir_name]) . "格式。");
			}
			//创建文件夹

			$this->save_path .= date("Y").DIRECTORY_SEPARATOR.date("m").DIRECTORY_SEPARATOR.date("d").DIRECTORY_SEPARATOR;
			$this->save_url .= date("Y").'/'.date("m")."/".date("d")."/";
			if (!file_exists($this->save_path)) {
				mkdir($this->save_path);
			}
			//新文件名
			$new_file_name = "loli_".time(). '.' . $file_ext;
			//移动文件
			$file_path = $this->save_path . $new_file_name;
			if (move_uploaded_file($tmp_name, $file_path) === false) {
				$this->_alert("上传文件失败。");
			}
			@chmod($file_path, 0644);
			
			import("ORG.Util.Image");
			
			Image::thumb($file_path,$file_path,"",$this->pic_max_width,$this->pic_max_height);
			
			$file_url = $this->save_url . $new_file_name;

			header('Content-type: text/html; charset=UTF-8');
			$json = new Services_JSON();
			echo $json->encode(array('error' => 0, 'url' => $file_url));
			exit;
		}
	}
	//提示信息
	private function _alert($msg)
	{
		header('Content-type: text/html; charset=UTF-8');
		$json = new Services_JSON();
		echo $json->encode(array('error' => 1, 'message' => $msg));
		exit;
	}
}
?>
