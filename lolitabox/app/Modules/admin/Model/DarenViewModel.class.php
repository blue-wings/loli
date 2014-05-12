<?php
class DarenViewModel extends ViewModel{
	protected $viewFields=array(
	  'Users'=>array(
	     'userid',	  
	     'usermail',
	     'nickname',
	     'if_super',
	     '_table'=>'users'
	  ),
	  
	  'UserDarenApply'=>array(
	    'userid'=>'uid',	
	    'blog_url',
        'weibo_url',	    
	    'qq',
	    'expert',
	    'update_current',
	    'apply_datetime',
	    'status',
	    '_table'=>'user_daren_apply',
	    '_on'=>'UserDarenApply.userid=Users.userid'
	  )
  );
	
	
	
	
}
?>
