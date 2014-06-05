<?php
class ThinkNodeAction extends CommonAction {
	public function _filter(&$map)
	{
        if(!empty($_GET['group_id'])) {
            $map['group_id'] =  $_GET['group_id'];
            $this->assign('nodeName','分组');
        }elseif(empty($_POST['search']) && !isset($map['pid']) ) {
			$map['pid']	=	0;
		}
		if($_GET['pid']!=''){
			$map['pid']=$_GET['pid'];
		}
		$_SESSION['currentNodeId']	=	$map['pid'];
		//获取上级节点
		$node  = M("ThinkNode");
        if(isset($map['pid'])) {
            if($node->getById($map['pid'])) {
                $this->assign('level',$node->level+1);
                $this->assign('nodeName',$node->name);
            }else {
                $this->assign('level',1);
            }
        }
	}

	public function _before_index() {
		$model	=	M("ThinkGroup");
		$list	=	$model->where('status=1')->getField('id,title');
		$this->assign('groupList',$list);
	}

	// 获取配置类型
	public function _before_add() {
		$model	=	M("ThinkGroup");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
		$node	=	M("ThinkNode");
		$node->getById($_SESSION['currentNodeId']);
        $this->assign('pid',$node->id);
		$this->assign('level',$node->level+1);
	}

    public function _before_patch() {
		$model	=	M("ThinkGroup");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
		$node	=	M("ThinkNode");
		$node->getById($_SESSION['currentNodeId']);
        $this->assign('pid',$node->id);
		$this->assign('level',$node->level+1);
    }
	public function _before_edit() {
		$model	=	M("ThinkGroup");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
	}

    /**
     +----------------------------------------------------------
     * 默认排序操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function sort()
    {
		$node = M('ThinkNode');
        if(!empty($_GET['sortId'])) {
            $map = array();
            $map['status'] = 1;
            $map['id']   = array('in',$_GET['sortId']);
            $sortList   =   $node->where($map)->order('sort asc')->select();
        }else{
            if(!empty($_GET['pid'])) {
                $pid  = $_GET['pid'];
            }else {
                $pid  = $_SESSION['currentNodeId'];
            }
            if($node->getById($pid)) {
                $level   =  $node->level+1;
            }else {
                $level   =  1;
            }
            $this->assign('level',$level);
            $sortList   =   $node->where('status=1 and pid='.$pid.' and level='.$level)->order('sort asc')->select();
        }
        $this->assign("sortList",$sortList);
        $this->display();
        return ;
    }
    
    //是否菜单显示
    public function isShow()
    {
        $model = D('ThinkNode');
        $id = $_REQUEST ['id'];
        $pid = $_REQUEST ['pid'];
        $type = $_REQUEST ['type'];
        $condition = array('id' => array('in', $id),'type'=>$type);
        $settype= $type==1 ? 0 : 1;
        $list = $model->where($condition)->setField('type',$settype);
        if ($list !== false) {
            $this->assign("jumpUrl",__URL__.'/index/pid/'.$pid);
            $this->success();
        } else {
            $this->error();
        }
    }
}
?>