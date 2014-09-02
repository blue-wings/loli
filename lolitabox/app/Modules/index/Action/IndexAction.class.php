<?php
/**
 * 首页控制嚣 【THINKPHP 3.1.3】echo THINK_VERSION;
 */
class IndexAction extends commonAction {
	/**
	 * 首页方法
	 */
	public function index() {
        $brand_count = D("ProductsBrand")->where("status=1")->count();
        $return['brand_count'] = $brand_count;
        $this->assign("return",$return);
        $this->display("index");
	}
}

?>