<?php
/**
 * LOLITABOX信息管理
 * @author zhenghong@sohu.com
 *
 */
class ArticleAction extends CommonAction
{
	/**
	 * 信息列表+搜索
	 */
	//调用信息分类
	public function index(){
		
		import("ORG.Util.Page");
		$article_mod = D('article');
		$category=M('Category');
		$where=array();
		$clist=$this->comm($_REQUEST['ctype']);
		$order='add_time DESC';
		$cate_id=$_REQUEST['cate_id'];
		if(empty($_REQUEST['time_start'])&&empty($_REQUEST['time_end'])&&empty($_REQUEST['cate_id'])&&empty($_REQUEST['keyword'])){
			foreach($clist as $key=>$val)
			{
				$artid[$key]=$clist[$key]['cid'].' ';
			}
			$where['cate_id']=array('in',implode(',',$artid));
		}else if($_GET['ctype']&&empty($_GET['cate_id'])&&empty($_POST['listorders'])){
			foreach($clist as $key=>$val)
			{
				$artid[$key]=$clist[$key]['cid'].' ';
			}
			$where['cate_id']=array('in',implode(',',$artid));
		}elseif(!empty($_REQUEST['ctype'])&&!empty($_REQUEST['cate_id'])&&empty($_POST['listorders'])||!empty($_REQUEST['keyword'])){
			if($_REQUEST['ctype']){
				$pageparam['ctype']=trim($_REQUEST['ctype']);
			}

			if (!empty($_REQUEST['keyword'])) {
				$where['title']  = array('like',trim($_REQUEST['keyword']));
				$pageparam["title"]=trim($_REQUEST['keyword']);
			}

			if (!empty($_REQUEST['cate_id'])) {
				$where['cate_id']  = array('eq',trim($_REQUEST['cate_id']));
				$pageparam['cate_id']=trim($_REQUEST['cate_id']);
			}

			if(!empty($_REQUEST['time_start'])){
				$where['add_time']  = array('egt',trim($_REQUEST['time_start']));
				$pageparam["time_start"]=trim($_REQUEST['time_start']);
			}

			if(!empty($_REQUEST['time_end'])){
				$where['add_time']  = array('elt',trim($_REQUEST['time_end']));
				$pageparam["time_end"]=trim($_REQUEST['time_end']);
			}

			if(!empty($_REQUEST['time_start']) && !empty($_REQUEST['time_end'])){
				$where['add_time']=array(array('egt',trim($_REQUEST['time_start'])),array('elt',trim($_REQUEST['time_end']),'And'));
				$pageparam["time_start"]=trim($_REQUEST['time_start']);
				$pageparam["time_end"]=trim($_REQUEST['time_end']);
			}
			$pageparamcount=count($pageparam);
			if($pageparamcount>0){
				while(list($param,$value)=each($pageparam)){
					$arraypageparam[]="$param=$value";
				}
				$strpageparam=implode('&',$arraypageparam);
			}
			$order='ordid desc,add_time desc';
		}

		if(($this->_post("dosubmit")&&$this->_post("listorders"))){

			foreach ($_POST['listorders'] as $id=>$sort_order){
				$data['ordid'] = $sort_order;
				$article_mod->where('id='.$id)->save($data);
			}
			if($_REQUEST['get_cateid']){
				$where['cate_id']=$_REQUEST['get_cateid'];
				$cate_id=$_REQUEST['get_cateid'];
			}else{
				foreach($clist as $key=>$val)
				{
					$artid[$key]=$clist[$key]['cid'].' ';
				}
				$where['cate_id']=array('in',implode(',',$artid));
			}

			
			$strpageparam="ctype=".$_REQUEST['ctype']."&cate_id=".$_REQUEST['get_cateid'];
			
			
			$order='ordid desc,add_time desc';
		}

		$count = $article_mod->where($where)->count();
		$p = new Page($count,15,$strpageparam);
		$article_list = $article_mod->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		foreach($article_list as $k=>$val){
			$article_list[$k]['cate_name'] = $category->where('cid='.$val['cate_id'])->getField('cname');
		}
		
		if($_REQUEST['cate_id']){
			$get_cateid=$_REQUEST['cate_id'];
		}
		
		$page = $p->show();
		$this->assign('ctype',$_REQUEST['ctype']);
		$this->assign('clist',$clist);
		$this->assign('article_list',$article_list);
		$this->assign("get_cateid",$get_cateid);
		$this->assign("cate_id",$cate_id);
		$this->assign('page',$page);
		$this->display();
	}

	/**
	 * 修改信息
	 */
	function edit(){
		
		if(isset($_POST['dosubmit'])){
			$article_mod = D('article');
			
			$data = $article_mod->create();
			
			$data['info']=str_replace('<br />','',$data['info']);

			if($data['cate_id']==0){
				$this->error('请选择资讯分类');
			}
			
			$result = $article_mod->save($data);
			if(false !== $result){
				$this->success('修改完成 ');
			}else{
				$this->error('修改失败');
			}
		}else{
			$article_mod = D('article');
			if( isset($_GET['id']) ){
				$article_id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : $this->error(L('please_select'));
			}

			$article_info = $article_mod->where('id='.$article_id)->find();

			//调用信息分类，CTYPE=10
			//萝莉盒 ctype=11
			$category=M("Category");
			$clist=$category
			->field("cid,cname,pcid,ctype,cpath,sortid,concat(cpath,'-',cid) as bpath")
			->order("bpath,cid")
			->where(array('ctype'=>$_GET['ctype']))->select();

			foreach($clist as $key=>$value){
				$clist[$key]['signnum']= count(explode('-',$value['bpath']))-1;
				$clist[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
			}

			$this->assign('clist',$clist);
			$this->assign('article',$article_info);
			$this->assign('ctype',$_GET['ctype']);
			$this->display();
		}
	}

	/**
	 * 添加信息
	 */
	function add()
	{

		//调用信息分类，CTYPE=10
		//萝莉社分类信息,CTYPE=11
		//神秘盒分类信息,CTYPE=12
		if(isset($_POST['dosubmit'])){
			
			$_POST['info']=str_replace('<br />','',$this->remoteimg($_POST['info']));
		
			$result=$this->addcommon($_POST,$_FILES);
			
			if($result){
				$this->assign('jumpUrl',"__URL__/index/ctype/".$this->_post('ctype'));
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
		}else{
			$clist=$this->comm($_GET['ctype']);
			$this->assign('clist',$clist);
			$this->display('add');
		}
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

	/**
      +----------------------------------------------------------
     * 转换数据库 
     * article.info信息  
     * products_evaluate.content信息 
     * user_blog.content信息 
     * 远程图片转存到本地
     * 存储目录:/data/userdata/年/月/日/
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @param $table
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */		

	function batchImgRemote2Local(){
		switch(trim($this->_get("table"))){
			case 'article':
				$mod=M("article");
				$result=$mod->field('id,info,add_time')->select();
				foreach ($result as $key=>$value){
					$data['id']=$value['id'];
					$time=substr($value['add_time'],0,10);
					$data['info']=$this->remoteimg($value['info'],$time);
					$mod->save($data);
				}
				break;
			case 'productsEvaluate':
				$mod=M("productsEvaluate");
				$result=$mod->field('evaluateid,content,postdate')->select();
				foreach ($result as $key=>$value){
					$data['evaluateid']=$value['evaluateid'];
					$time=date('Y-m-d',strtotime($value['postdate']));
					$data['content']=$this->remoteimg($value['content'],$time);
					//$mod->save($data);
				}
				break;
			case 'userBlog':
				$mod=M("userBlog");
				$result=$mod->field('id,content,postdate')->select();
				foreach ($result as $key=>$value){
					$data['id']=$value['id'];
					$time=date('Y-m-d',$value['postdate']);
					$data['content']=$this->remoteimg($value['content'],$time);
					$mod->save($data);
				}
				break;

		}
		//$this->success("转换完成");
	}

	//实例化查询信息方法公用
	private function comm($ctype){
		$category=M("Category");
		
		$where = array(
			'ctype'=>$ctype,
			'cstatu'=>0
		);
		
		$clist=$category
		->field("cid,cname,pcid,ctype,cpath,sortid,concat(cpath,'-',cid) as bpath")
		->order("bpath,cid")
		->where($where)->select();
		
		foreach($clist as $key=>$value){
			$clist[$key]['signnum']= count(explode('-',$value['bpath']))-1;
			$clist[$key]['marginnum']= (count(explode('-',$value['bpath']))-1)*20;
		}
		return $clist;
	}


	//公用:信息添加方法
	private  function addcommon($datainfo='',$datafiles=''){
		$article_mod = D('article');
		/*		dump($datainfo);
		dump($datafiles);*/

		if($datainfo['title']==''){
			$this->error(L('input').L('article_title'));
		}

		if(false === $data = $article_mod->create()){
			$this->error($article_mod->error());
		}

		/*
		if ($datafiles['img']['name']!=''||$datafiles['attachment']['name'][0]!='') {
			if ($datafiles['img']['name']!=''&&$datafiles['attachment']['name'][0]!='') {
				$upload_list = $this->_upload();
				$data['img'] = $upload_list['time'].$upload_list['0']['savename'];
				array_shift($upload_list);
				$aid_arr = array();
				foreach ($upload_list as $att) {
					$file['title'] = $att['name'];
					$file['filetype'] = $att['extension'];
					$file['filesize'] = $att['size'];
					$file['url'] = $att['savename'];
					$file['uptime'] = date('Y-m-d H:i:s');
				}
			} elseif ($datafiles['img']['name'][0]!='') {
				$upload_list = $this->_upload();
				$data['img'] =$upload_list['time'].$upload_list['0']['savename'];
			} else {
				//$upload_list = $this->_upload();
				$aid_arr = array();
				foreach ($upload_list as $att) {
					$file['title'] = $att['name'];
					$file['filetype'] = $att['extension'];
					$file['filesize'] = $att['size'];
					$file['url'] = $att['savename'];
					$file['uptime'] = date('Y-m-d H:i:s');
				}
			}
		}
		

		if($upload_list['1']['savename']!=''){
			$data['bigimg']=$upload_list['time'].$upload_list['1']['savename'];
		}
        */
		$data['add_time']=date('Y-m-d H:i:s',time());
		$result = $article_mod->add($data);
		return $result;
	}

	/**
	 * 删除信息
	 */
	function delete()
	{
		$article_mod = D('article');
		if((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_POST['id']) || empty($_POST['id']))) {
			$this->error('请选择要删除的资讯！');
		}
		if( isset($_POST['id'])&&is_array($_POST['id']) ){
			$cate_ids = implode(',',$_POST['id']);
			foreach( $_POST['id'] as $val ){
				$article = $article_mod->field("id,cate_id")->where("id=".$val)->find();
			}
			$article_mod->delete($cate_ids);
		}else{
			$cate_id = intval($_GET['id']);
			$article = $article_mod->field("id,cate_id")->where("id=".$cate_id)->find();
			$article_mod->where('id='.$cate_id)->delete();
		}
		$this->success(L('operation_success'));
	}

	/**
     * 上传信息附件
     */
	private function _upload()
	{
		import("ORG.Net.UploadFile");
		$upload = new UploadFile();
		//设置上传文件大小
		$upload->maxSize = 3292200;
		//$upload->allowExts = explode(',', 'jpg,gif,png,jpeg');
		$time=date("Y_m",time())."/".date("d",time())."/";
		$upload->savePath = './data/article/'.$time;
		dir_create($upload->savePath);
		$upload->saveRule = uniqid;
		if (!$upload->upload()) {
			//捕获上传异常
			$this->error($upload->getErrorMsg());
		} else {
			//取得成功上传的文件信息
			$uploadList = $upload->getUploadFileInfo();
		}

		$uploadList['time']=$time;
		return $uploadList;
	}

	/**
     * 人工排序
     */
	function sort_order()
	{
		$article_mod = D('article');
		if (isset($_POST['listorders'])){
			foreach ($_POST['listorders'] as $id=>$sort_order){
				$data['ordid'] = $sort_order;
				$article_mod->where('id='.$id)->save($data);
			}
		}
		// $this->success("排序完成!");
	}

	/**
     * 修改审核状态
     */
	function status()
	{
		$article_mod = D('article');
		$id=$_POST['id'];
		$type=$_POST['type'];
		$result=$article_mod->where(array('id'=>$id))->getField($type);
		$data[$type]=(int)!$result;
		$rut=$article_mod->where(array('id'=>$id))->save($data);

		if($rut)
		{
			$this->ajaxReturn($data[$type],$rut,1);
		}else{
			$this->ajaxReturn(0,0,0);
		}
	}
	/**
	 +----------------------------------------------------------
	 * 分不同文件夹存储图片
	 +----------------------------------------------------------
	 * @param str  $imgPath       定义本地存储图片的绝对路径
	 * @param str  $imgUrl_one    定义本地存储图片的相对路径
	 * @param str  $fileurl       本地存储图片的绝对路径+图片名称
	 * @param str  $imgUrl        存到数据库里面的相对地址
	 * @param str  $img_name      随机数 定义:10
	 +----------------------------------------------------------
	 * @return str   相对路径的图片地址
	 +----------------------------------------------------------
	 */	
	function store_img($img,$con=0,$dir='products',$dir2='data'){
		$arr=array();
		import("ORG.Util.String");
		$imgPath = PROJECT_ROOT_PATH.DIRECTORY_SEPARATOR.$dir2.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$con.DIRECTORY_SEPARATOR;
		$imgUrl_one='/'.$dir2.'/'.$dir.'/'.$con.'/';
		$get_file = @file_get_contents($img);
		$suffix=substr($img,-4,4);
		$img_name=String::randString('10');
		$fileurl = $imgPath.$img_name.$suffix;
		$imgUrl=$imgUrl_one.$img_name.$suffix;

		dir_create($imgPath);
		$fp = @fopen($fileurl,'w');
		@fwrite($fp,$get_file);
		@fclose($fp);
		return $imgUrl;
	}
}
?>