<?php

class CommonAction extends Action {
	function _initialize(){
		set_time_limit(0);
		//header("Content-Type:text/html; charset=utf-8");
		import('@.ORG.Util.Cookie');
        // 用户权限检查
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            import('@.ORG.Util.RBAC');
            if (!RBAC::AccessDecision()) {
                //检查认证识别号
                if (!$_SESSION [C('USER_AUTH_KEY')]) {
                    //跳转到认证网关
                    redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    redirect(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->assign('jumpUrl', PHP_FILE . C('USER_AUTH_GATEWAY'));
                    }
                    // 提示错误信息
                    $this->error(L('_VALID_ACCESS_'));
                }
            }
        }
        
        //记录日志
        ThinkSimplelogAction::write();
	}

 public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $name = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->display();
        return;
    }

    /**
      +----------------------------------------------------------
     * 取得操作成功后要返回的URL地址
     * 默认返回当前模块的默认操作
     * 可以在action控制器中重载
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    function getReturnUrl() {
        return __URL__ . '?' . C('VAR_MODULE') . '=' . MODULE_NAME . '&' . C('VAR_ACTION') . '=' . C('DEFAULT_ACTION');
    }

    /**
      +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
      +----------------------------------------------------------
     * @access protected
      +----------------------------------------------------------
     * @param string $name 数据对象名称
      +----------------------------------------------------------
     * @return HashMap
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    protected function _search($name = '') {
        //生成查询条件
        if (empty($name)) {
            $name = $this->getActionName();
        }
        $name = $this->getActionName();
        $model = D($name);
        $map = array();
        
        foreach ($model->getDbFields() as $key => $val) {
            if (isset($_REQUEST [$val]) && $_REQUEST [$val] != '') {
                $map [$val] = $_REQUEST [$val];
            }
        }
        return $map;
    }

    /**
      +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
      +----------------------------------------------------------
     * @access protected
      +----------------------------------------------------------
     * @param Model $model 数据对象
     * @param HashMap $map 过滤条件
     * @param string $sortBy 排序
     * @param boolean $asc 是否正序
      +----------------------------------------------------------
     * @return void
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    protected function _list($model, $map, $sortBy = '', $asc = false) {
        //排序字段 默认为主键名
        if (isset($_REQUEST ['_order'])) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = !empty($sortBy) ? $sortBy : $model->getPk();
        }
        //排序方式默认按照倒序排列
        //接受 sost参数 0 表示倒序 非0都 表示正序
        if (isset($_REQUEST ['_sort'])) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        } else {
            $sort = $asc ? 'asc' : 'desc';
        }
        //取得满足条件的记录数
        $count = $model->where($map)->count('id');
        if ($count > 0) {
            import("ORG.Util.Page");
            //创建分页对象
            if (!empty($_REQUEST ['listRows'])) {
                $listRows = $_REQUEST ['listRows'];
            } else {
                $listRows = '';
            }
            $p = new Page($count, $listRows);
            //分页查询数据
			$map['status']=1;
            $voList = $model->where($map)->order("`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select();
            //echo $model->getlastsql();
            //分页跳转的时候保证查询条件
            foreach ($map as $key => $val) {
                if (!is_array($val)) {
                    $p->parameter .= "$key=" . urlencode($val) . "&";
                }
            }
            //分页显示
            $page = $p->show();
            //列表排序显示
            $sortImg = $sort; //排序图标
            $sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
            $sort = $sort == 'desc' ? 1 : 0; //排序方式
            //模板赋值显示
            $this->assign('list', $voList);
            $this->assign('sort', $sort);
            $this->assign('order', $order);
            $this->assign('sortImg', $sortImg);
            $this->assign('sortType', $sortAlt);
            $this->assign("page", $page);
        }
        Cookie::set('_currentUrl_', __SELF__);
        return;
    }

    function insert() {
        //B('FilterString');
        $name = $this->getActionName();
        $model = D($name);
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        //保存当前数据对象
        $list = $model->add();
        if ($list !== false) { //保存成功
            $this->assign('jumpUrl', Cookie::get('_currentUrl_'));
            $this->success('新增成功!');
        } else {
            //失败提示
            $this->error('新增失败!');
        }
    }
    
    public function add() {
        $this->display();
    }

    function read() {
        $this->edit();
    }

    function edit() {
        $name = $this->getActionName();
        $model = M($name);
        $id = $_REQUEST [$model->getPk()];
        $vo = $model->getById($id);
        $this->assign('vo', $vo);
        $this->display();
    }

    function update() {
        //B('FilterString');
        $name = $this->getActionName();
        $model = D($name);
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //成功提示
            $this->assign('jumpUrl', Cookie::get('_currentUrl_'));
            $this->success('编辑成功!');
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    /**
      +----------------------------------------------------------
     * 默认删除操作
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    public function delete() {
        //删除指定记录
        $name = $this->getActionName();
        $model = M($name);
        
        if (!empty($model)) {
            $id = $_GET[$model->getPk()];
            if ($id){
                $where['id']=floatval($id);
                $list = $model->where($where)->setField('status',0);

                if ($list !== false) {
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    public function foreverdelete() {
        //删除指定记录
        $name = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            $pk = $model->getPk();
            $id = $_REQUEST [$pk];
            if (isset($id)) {
                $condition = array($pk => array('in', explode(',', $id)));
                if (false !== $model->where($condition)->delete()) {
                    //echo $model->getlastsql();
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
//        $this->forward();
    }

    public function clear() {
        //删除指定记录
        $name = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            if (false !== $model->where('status=1')->delete()) {
                $this->assign("jumpUrl", $this->getReturnUrl());
                $this->success(L('_DELETE_SUCCESS_'));
            } else {
                $this->error(L('_DELETE_FAIL_'));
            }
        }
        $this->forward();
    }

    /**
      +----------------------------------------------------------
     * 默认禁用操作
     *
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws FcsException
      +----------------------------------------------------------
     */
    public function forbid() {
        $name = $this->getActionName();
        $model = D($name);
        $pk = $model->getPk();
        $id = $_REQUEST [$pk];
        $condition = array($pk => array('in', $id));
        $list = $model->forbid($condition);
        if ($list !== false) {
            $this->assign("jumpUrl", $this->getReturnUrl());
            $this->success('状态禁用成功');
        } else {
            $this->error('状态禁用失败！');
        }
    }

    public function checkPass() {
        $name = $this->getActionName();
        $model = D($name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->checkPass($condition)) {
            $this->assign("jumpUrl", $this->getReturnUrl());
            $this->success('状态批准成功！');
        } else {
            $this->error('状态批准失败！');
        }
    }

    public function recycle() {
        $name = $this->getActionName();
        $model = D($name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->recycle($condition)) {

            $this->assign("jumpUrl", $this->getReturnUrl());
            $this->success('状态还原成功！');
        } else {
            $this->error('状态还原失败！');
        }
    }

    public function recycleBin() {
        $map = $this->_search();
        $map ['status'] = - 1;
        $name = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->display();
    }

    /**
      +----------------------------------------------------------
     * 默认恢复操作
     *
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws FcsException
      +----------------------------------------------------------
     */
    function resume() {
        //恢复指定记录
        $name = $this->getActionName();
        $model = D($name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->resume($condition)) {
            $this->assign("jumpUrl", $this->getReturnUrl());
            $this->success('状态恢复成功！');
        } else {
            $this->error('状态恢复失败！');
        }
    }

    function saveSort() {
        $seqNoList = $_POST ['seqNoList'];
        if (!empty($seqNoList)) {
            //更新数据对象
            $name = $this->getActionName();
            $model = D($name);
            $col = explode(',', $seqNoList);
            //启动事务
            $model->startTrans();
            foreach ($col as $val) {
                $val = explode(':', $val);
                $model->id = $val [0];
                $model->sort = $val [1];
                $result = $model->save();
                if (!$result) {
                    break;
                }
            }
            //提交事务
            $model->commit();
            if ($result !== false) {
                //采用普通方式跳转刷新页面
                $this->success('更新成功');
            } else {
                $this->error($model->getError());
            }
        }
    }
    
    /**
      +----------------------------------------------------------
     * photoStorage根据参数来获得表单中的图片并按照$prevUrl/yyyy
     * /mm/dd/time().exp存储起来，返回照片存储之后的路径
      +----------------------------------------------------------
     * @param $name 表单的name   $prevUrl 存储照片的前缀路径
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string  photo的路径
      +----------------------------------------------------------
        
     */
    public function photoStorage($name,$prevUrl)
    {
    	if($photo=$_FILES[$name]['name'])
    	{
    		$temp=explode(".", $photo);
    		$exp=array_pop($temp);
    		$filename=time().rand(100,999).".".$exp;
    		$path=$prevUrl."/".date("Y/m/d");
    		$photoUrl=$path.'/'.$filename; //返回去的照片路径
    		$path=str_replace("//", "/", "./".$path);  //兼容传过来的路径，如"/aa/dd"和"aa/dd"
    		dir_create($path);
    		$photodir=$path."/".$filename; 	//存储时的路径   		  
    		if(copy($_FILES[$name]['tmp_name'],$photodir))
    		   return $photoUrl;
    		return '';   		
    	}
    	else 
    		return '';
    }
    
    //无刷新上传图片
    public function uploadPic(){
    	if($_POST['upload_pic_submit'])  {
    	   $url=$this->photoStorage('photoname', '/data/userdata');
    	   $this->assign('url',$url);
    	   $this->assign('inputname',$_POST['inputname']);
    	}
    	$this->display("Public:uploadPic");
    }
    
    /**
     * 通过品牌名搜索符合条件的纪录并ajax返回
     * @param string name品牌名
     * @author litingting
     */
    public function searchBrand(){
    	$pro_brand_mod=M("ProductsBrand");
    	$name=trim($_REQUEST['name']);
    	if($name)
    	{
    		$where['name']=array('like',"%$name%");
    	}
    	$list=$pro_brand_mod->field("name,id")->where($where)->select();
    	$this->ajaxReturn($list,"成功",1);
    	
    }
    
    /**
     * 更新tag_index表
     * @author litingting
     */
    public function updateTagIndex(){
    	$model=M();
    	//更新品牌分类
    	$sql1="insert into tag_index(sid,tagname,tagcategory) 
select id,name,11 from products_brand where not exists(select * from tag_index 
where tagname=name and sid=id and tagcategory=11)";
    	$model->query($sql1);
    	//更新产品分类
    	$sql2="insert into tag_index(sid,tagname,tagcategory) 
select cid,cname,12 from category where ctype=1 and (not exists(select * from 
tag_index where tagname=cname and sid=cid and tagcategory=12))";
    	$model->query($sql2);
    	//更新产品名称
    	$sql3="insert into tag_index(sid,tagname,tagcategory) 
select pid,pname,13 from products where  (not exists(select * from tag_index 
where tagname=pname and sid=pid and tagcategory=13))";
    	$model->query($sql3);
    	//更新产品功效分类
    	$sql4="insert into tag_index(sid,tagname,tagcategory) 
select cid,cname,14 from category where ctype=2 and (not exists(select * from 
tag_index where tagname=cname and sid=cid and tagcategory=14))";
    	$model->query($sql4);
    	echo "成功";
    	
    }
    
    
    /**
     +----------------------------------------------------------
     * 添加粉丝,可以完成批量添加
     +----------------------------------------------------------
     * @param string userid  用户ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @author litingting
     */
    function addFans(){
    	extract($_POST);
    	$add_fansnum = (int)$add_fansnum;
    	if($add_fansnum <=0 || empty($type)){
    		$this ->ajaxReturn("0","参数错误",0);
    	}
    	if(trim($userid_list)){
    		$userid_list=explode(",", $userid_list);
    		$follow_mod = D("Follow");
    		$array=array();
    		for($i=0;$i<count($userid_list)-1;$i++){
    			$flag=$follow_mod->datAddFollow($userid_list[$i],$type,$add_fansnum);
    			if($flag===false){
    				$this->ajaxReturn(1,"操作失败",0);die;
    			}
    		}
    		$this->ajaxReturn(1,"添加粉丝成功",1);die;
    			
    	}else if($add_userid){
    		$follow_mod = D("Follow");
    		$follow_mod->datAddFollow($add_userid,$type,$add_fansnum);
    			
    	}else{
    		$this->ajaxReturn(1,"参数错误",0);die;
    	}
    }
 
    
	/**
       +----------------------------------------------------------
       * 返回品牌列表
       +----------------------------------------------------------
       * @access private
       +----------------------------------------------------------
       * @param  string pname     有值则返回符合条件的某条记录
       * 						  空值则返回品牌列表
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.5.13
       */		
	function plist(){

		$p_mod=M("productsBrand");
		$pname = $this->_post('brandname');
		if($pname){
			$where['name']=array('LIKE','%'.$pname.'%');
			$where['status']=1;
			$prolist=$p_mod->where($where)->field('id,name')->select();
		}else{
			$prolist=$p_mod->field('id,name')->select();
		}

		$this->ajaxReturn($prolist,'查询成功!',1);
	}


    /**
    +----------------------------------------------------------
     * JS:kindeditor插件提交的文本中的远程图片转存到本地
     * 存储目录:/data/userdata/年/月/日/
    +----------------------------------------------------------
     * @access protected
    +----------------------------------------------------------
     * @param string $data 文本
    +----------------------------------------------------------
     * @return void 返回过滤之后的文本
    +----------------------------------------------------------
     * @throws ThinkExecption
    +----------------------------------------------------------
     */
    public  function remoteimg($data,$create_time=null){
        if(!empty($create_time)){
            $time_array=array();
            $time_array=explode('-',$create_time);
            //文件保存目录路径
            $imgPath = USER_DATA_DIR_ROOT.DIRECTORY_SEPARATOR.$time_array[0].DIRECTORY_SEPARATOR.$time_array[1].DIRECTORY_SEPARATOR.$time_array[2].DIRECTORY_SEPARATOR;
            $imgUrl_one="/data/userdata/".$time_array[0]."/".$time_array[1].'/'.$time_array[2].'/';
        }else{
            $imgPath = USER_DATA_DIR_ROOT.DIRECTORY_SEPARATOR.date("Y").DIRECTORY_SEPARATOR.date("m").DIRECTORY_SEPARATOR.date("d").DIRECTORY_SEPARATOR;
            $imgUrl_one ="/data/userdata/".date("Y")."/".date("m").'/'.date("d").'/';
        }

        import("ORG.Util.Image");
        //日期名
        $milliSecond = time();
        $img_arr = array();

        $data=html_entity_decode($data);

        $pattern='/<[img|IMG].*?src=[\'|\"](http.*?[gif|jpg|jpeg|bmp|png])[\'|\"].*?[\/]?>/';
        preg_match_all($pattern,$data,$img_array);

        $img_arr=array_unique($img_array[1]);

        if(empty($img_array[1]))
        {
            return $data;
        }
        $arr=array();
        foreach($img_arr as $key =>$value){
            $get_file = @file_get_contents($value);
            $arr=explode('.',$value);
            $count=count($arr);

            $rand = rand(1,1000);

            $fileurl = $imgPath.$milliSecond.$key.$rand.'.'.$arr[$count-1];
            $imgUrl=$imgUrl_one.$milliSecond.$key.$rand.'.'.$arr[$count-1];

            if($get_file)
            {
                dir_create($imgPath);
                $fp = @fopen($fileurl,'w');
                @fwrite($fp,$get_file);
                @fclose($fp);
                Image::thumb($fileurl,$fileurl,"",500,500);
            }
            $data=str_replace($value,$imgUrl,$data);
        }
        return $data;
    }
}
?>