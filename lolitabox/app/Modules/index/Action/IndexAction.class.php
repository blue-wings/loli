<?php
/**
 * 首页控制嚣 【THINKPHP 3.1.3】echo THINK_VERSION;
 */
class IndexAction extends commonAction {
	/**
	 * 首页方法
	 */
	public function index() {
		$postage = D("PostageStandard")->calculatePostage(array(63140), 0, 802);
		if($this->userid){
			$this->redirect('index/home', null, 1, '页面跳转中...');	
		}
		$this->redirect('index/user/login', null, 1, '页面跳转中...');
	}
}

?>