<?php
class UserBlogViewModel extends ViewModel{
	
	protected $viewFields=array(
		'UserBlogIndex'=>array(
			'*',
			'_type'=>'LEFT'
		),
			
		'UserBlogCategory'=>array(
			'id'=>'cid',
			'name',	
			'_on'=>'UserBlogIndex.cateid = UserBlogCategory.id',
			'_type'=>'LEFT'
		),
		
		'Users'=>array(
			'nickname',
			'_on'=>"UserBlogIndex.userid = Users.userid",
			'_type'=>'LEFT'
		)
	);
	
	
	/**
      +----------------------------------------------------------
     * 查询日志博客列表WHERE条件
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return Array
      +----------------------------------------------------------
     * @type GET
      +----------------------------------------------------------
     *author  zhaoxiang 2013/03/05
     */
	public function blogListSelectWhere($arguments){
		
		if($arguments['userid']){
			$where['userid'] = $arguments['userid'];
		}

		if($arguments['nickname']){
			$where["userid"]=M('Users')->where(array('nickname'=>$arguments['nickname']))->getField('userid');
		}

		if($arguments['blogid']){
			$where['id'] = $arguments['blogid'];
		}

		if($arguments['keywords']){
			$where['title'] = array('LIKE',"%".$arguments['keywords']."%");;
		}

		if($arguments['cateid']){
			$where['cateid'] = $arguments['cateid'];
		}
		
		//精华
		if($arguments['contentlevel']){
			$where['contentlevel'] = $arguments['contentlevel'] != 2?$arguments['contentlevel']:0; 
		}

		//置顶
		if($arguments['stickies'] || $arguments['stickies'] === '0'){
			$where['toptime'] = empty($arguments['stickies'])?array('eq','0'):array('gt','0');
		}

		//日志类型普通:1  图片:2
		if($arguments['blog_type']){
			$where['type'] = $arguments['blog_type'];
		}

		//用户来源
		if($arguments['utype']){
			$where["userid"] = array('exp',"IN(SELECT uid FROM `user_openid` WHERE type = '{$arguments['utype']}')");
		}
		
		//终端类型
		if($arguments['is_mobile'] || $arguments['is_mobile'] === '0'){
			$where['is_mobile'] = $arguments['is_mobile'];
		}

		//是否需要审核
		if($arguments['is_check'] || $arguments['is_check'] === '0'){
			$where['is_check'] = $arguments['is_check'];
		}
			
		//评测时间
		if($arguments['from'] && $arguments['to']){
			$where["postdate"]=array(array('egt',strtotime($arguments['from'])),array('elt',strtotime($arguments['to'].' 23:59:59')),'AND');
		}else if($arguments['from']){
			$where["postdate"]=array('egt',strtotime($arguments['from']));
		}else if($arguments['to']){
			$where["postdate"]=array('elt',strtotime($arguments['to'].' 23:59:59'));
		}		
		
		return $where;
	}
}
?>