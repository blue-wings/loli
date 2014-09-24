<?php
/**
 * 全局功能控制器
 * @author zhenghong
 */
class publicAction extends commonAction {


	//验证码
	Public function verify(){
		import('ORG.Util.Image');
		Image::buildImageVerify();
	}
	
	//检查验证码
	function CheckVerify(){
		$verify=trim($this->_post('param'));
		if(md5($verify) == $_SESSION['verify']){
			echo "y";
		}else{
			echo '验证码不正确';
		}
	}
}
?>