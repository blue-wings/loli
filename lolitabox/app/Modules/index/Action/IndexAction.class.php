<?php
/**
 * 首页控制嚣 【THINKPHP 3.1.3】echo THINK_VERSION;
 */
class IndexAction extends commonAction {
	/**
	 * 首页方法
	 */
	public function index() {
		if($this->userid){
			$this->redirect('Index/home', null, 1, '页面跳转中...');	
		}
		$this->redirect('Index/user/login', null, 1, '页面跳转中...');
	}
}

?>