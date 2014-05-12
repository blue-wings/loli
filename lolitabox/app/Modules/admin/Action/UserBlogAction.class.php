<?php
class UserBlogAction extends CommonAction{
	/**
      +----------------------------------------------------------
     * 日志列表   user_blog 
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     * @parameters  
     * page:分页  
     * list:列表 
      +----------------------------------------------------------
     */	
	public	function blogList(){

		import("ORG.Util.Page");
		import("ORG.Util.String");

		$blog_mod = D("UserBlogView");

		$where = $blog_mod->blogListSelectWhere(array_map('filterVar',$_GET));

		$count=$blog_mod->where($where)->count('UserBlogIndex.id');

		$p = new Page($count,10);

		$userBlogList = $blog_mod->where($where)->order('postdate DESC')->limit($p->firstRow.','.$p->listRows)->select();

		//开放合作
		$userBlogList = $this->addOpenidToBlogList($userBlogList);

		//分类名称和开放合作的方式
		$li = $this->returnBlogTypeAndResour();

		$page=$p->show();
		$this->assign('li',$li);
		$this->assign('page',$page);
		$this->assign('list',$userBlogList);
		$this->display();
	}
	
	/**
       +----------------------------------------------------------
       * 用户博客分类名称+开放合作来源类型 sina,sohu,qq
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param   NULL  			
       +-----------------------------------------------------------
       * @author zhaoxiang 2013.4.7
     */
	private function returnBlogTypeAndResour(){
		$li['blogtype'] = D("UserBlogView")->DISTINCT(true)->field('cid,name')->order('cid ASC')->select();
		$li['userresour']= M("UserOpenid")->DISTINCT(true)->field('type')->select();
		return $li;
	}

	/**
       +----------------------------------------------------------
       * 为用户博客列表增加开放合作类型 sina,sohu,qq
       +----------------------------------------------------------  
       * @access  private   
       +----------------------------------------------------------
       * @param   Array  userBlogList   	  用户博客列表  			
       +-----------------------------------------------------------
       * @author  zhaoxiang 2013.4.7
     */
	private function addOpenidToBlogList($userBlogList){

		foreach ($userBlogList as $key=>$value){
			$getData=M('userBlogContent')->where(array('parentid'=>$value['id']))->field('img,content')->find();

			$userBlogList[$key]['img']=$getData['img'];
			$userBlogList[$key]['content']=preg_replace("'([\r\n])[\s]+'", "", strip_tags($getData['content']));
			$userBlogList[$key]['summary']=trim(String::msubstr(strip_tags($userBlogList[$key]['content']),0,50));

			$type=M('UserOpenid')->where(array('uid'=>$value['userid']))->field('type')->select();

			if($type){
				$user_type=array();
				foreach ($type as $k=>$v){
					$user_type[]=$v['type'];
				}
				$userBlogList[$key]['usertype']=implode(',',$user_type);
			}else{
				$userBlogList[$key]['usertype']='';
			}
		}
		return $userBlogList;
	}

	/**
      +----------------------------------------------------------
     * 修改日志列表状态   user_blog 
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @type ajax
      +----------------------------------------------------------      
     * @parameters  
     * contentlevel:内容级别   
     * toptime:置顶时间戳  
     *  status:日志状态 
      +----------------------------------------------------------
     */

	public function setBlogMessage(){
		$blog=M('userBlogIndex');
		if($this->_post("id")){
			$where['id']=$this->_post("id");
			$type=$this->_post('type');
			$res=$blog->where($where)->getField("$type");

			if($type=='contentlevel'){
				$userid=$blog->where($where)->getField("userid");
				$user_credit_stat_model = D("index://UserCreditStat");
				if($res==5){
					$data["$type"]=1;
					$user_credit_stat_model ->optCreditSet($userid,'blog_cancl_digest');
				}else{
					$data["$type"]=5;
					$user_credit_stat_model ->optCreditSet($userid,'blog_to_digest');
				}
			}else{
				$data["$type"]=!(int)$res;
			}
			$result=$blog->where($where)->save($data);

			if($result){
				$this->ajaxReturn($data["$type"],'OK',1);
			}else{
				$this->ajaxReturn(0,'failed',1);
			}
		}else if($this->_post("cid")){
			$where['id']=$this->_post("cid");
			$result=$blog->where($where)->getField("content");
			$this->ajaxReturn($result,'OK',1);
		}else{
			$this->error('参数错误');
		}
	}

	/**
  +----------------------------------------------------------
 * 更新单条日志信息 user_blog
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @return Array
  +----------------------------------------------------------  
 * @PS:用的时候把private改成public(暂时不用)
  +----------------------------------------------------------        
 * @parameters  
 * id:日志ID
  +----------------------------------------------------------
 */

	private    function delMessage(){

		if($this->_post("id")){
			$blog=M('user_blog');
			$where['id']=$this->_post("id");
			$res=$blog->where($where)->getField('status');
			$data['status']=!(int)$res;
			$result=$blog->where($where)->save($data);
			if($result){
				$this->ajaxReturn($result,'ok!',1);
			}else{
				$this->ajaxReturn(0,'no failed!!',0);
			}
		}
	}

	/**
  +----------------------------------------------------------
 * 删除多条日志信息 user_blog
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @return Array
  +----------------------------------------------------------  
 * @PS:用的时候把private改成public(前台接口没开)
  +----------------------------------------------------------        
 * @parameters  
 * id:多条日志ID
  +----------------------------------------------------------
 */
	private  function delms(){
		$id=$this->_post("id");
		if($id){
			$blog=M('user_blog');
			foreach($id as $key=>$value){
				$del=$blog->where(array('id'=>$value))->delete();
				if($del){
					$count[]=$value;
				}
			}
		}
		$this->success('删除成功!ID范围:'.implode(',',$count));
	}

	/**
  +----------------------------------------------------------
 * 个人日志回复列表 user_blog_reply
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @return Array
  +----------------------------------------------------------  
 * @parameters  
 * id:日志id
  +----------------------------------------------------------
 */
	function showreply(){

		$reply=M('userBlogReply');
		$user=M('users');
		$blog=M('userBlog');
		$where['blogid']=$this->_get('id');
		$strpageparam='&id='.$this->_get('id');
		$count=$reply->where($where)->count();
		import("ORG.Util.Page");
		$p = new Page($count,15,$strpageparam);

		$relist=$reply->where($where)->limit($p->firstRow.','.$p->listRows)->select();

		import("ORG.Util.String");
		import("ORG.Util.Input");
		$content=Input::deleteHtmlTags($content);
		$summary=String::msubstr($content,0,100);

		foreach($relist as $key=>$value){
			$where['userid']=$value['userid'];
			$relist[$key]['delHtmlTags']=Input::deleteHtmlTags($relist[$key]["replycontent"]);
			$relist[$key]['summary']=trim(String::msubstr($relist[$key]['delHtmlTags'],0,20));
			$relist[$key]['nickname']=$user->where($where)->getField('nickname');
		}

		$page = $p->show();
		$this->assign('page',$page);
		$this->assign("relist",$relist);
		$this->display();

	}

	/**
  +----------------------------------------------------------
 * 删除日志列表单条信息  user_blog_reply
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 * @type ajax
  +----------------------------------------------------------      
 * @parameters  
 * id:日志id
      +----------------------------------------------------------
     */
	public function delreply(){
		if($this->_post('id')){
			$reply=M('userBlogReply');
			$where['replyid']=$this->_post('id');
			$result=$reply->where($where)->delete();
			if($result){
				$this->ajaxReturn($result,'ok',1);
			}else{
				$this->ajaxReturn(0,'failed',0);
			}
		}
	}

	
}
?>
