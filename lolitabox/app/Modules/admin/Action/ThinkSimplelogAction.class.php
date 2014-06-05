<?php
class ThinkSimplelogAction extends Action {

	//简单的日志写入
	static function write()
	{
		$account=$_SESSION['account'];
		$pathinfo = $_SERVER['PATH_INFO'];
		$arrinfo=explode('/',$pathinfo);
		$module = $arrinfo[1];
		$action = $arrinfo[2];
		$param = serialize($_REQUEST);
		$param = $param == "" ? '' : $param;
		$optime=time();
		$username=$account;
		$ip = get_client_ip();
		$data=compact('username','module','action','param','ip','optime');
//		print_r($data);exit;	
		$simplelog = M('ThinkSimplelog');
		$simplelog->add($data);
	}

	//显示日志
	public function index()
	{
		if (!empty($_REQUEST['account'])) {
			$username = trim($_REQUEST['account']);
			$where['username']  = array('like','%'.$username.'%');
		}
		if (!empty($_REQUEST['module'])) {
			$module = trim($_REQUEST['module']);
			$where['module']  = array('like','%'.$module.'%');
		}
		if (!empty($_REQUEST['action'])) {
			$action = trim($_REQUEST['action']);
			$where['action']  = array('like','%'.$action.'%');
		}
		if (!empty($_REQUEST['from']) && !empty($_REQUEST['to'])) {
			$from=strtotime($_REQUEST['from']);
			$to=strtotime($_REQUEST['to']);
			$where['optime']  = array('between',array($from,$to));
		}
		if(empty($where)) $where ="";
		
		if($this->_get('keywords')){
			$keys_array=explode(' ',$this->_get('keywords'));

			$str='';
			for($i=0;$i<count($keys_array);$i++){
				$str.="param LIKE '%".$keys_array[$i]."%' AND ";
			}

			$where['_string']=substr($str,0,-5);
		}
		$simplelog = M('ThinkSimplelog');
        import("ORG.Util.Page");
        $count = $simplelog->where($where)->count('id');
        $p = new Page($count, 20);
        $list = $simplelog->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();

		$page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display(); 
	}
	
}

?>
