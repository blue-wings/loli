<?php

/**
 * 空方法
 * @author Administrator
 *
 */
class EmptyAction extends Action{
	
	
	public function _empty(){
		$this->display("public:nofind");
	}
}

?>