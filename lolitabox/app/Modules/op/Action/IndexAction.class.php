<?php
/**
 * op url options
 * Enter description here ...
 * @author root
 *
 */
class IndexAction extends Action  {
	
	public function deploy(){
		
		system("/home/op/deploy", $return);
		$this->assign($return);
		
	}
	
}