<?php
class  UserExchangeListViewModel extends ViewModel{
	protected  $viewFields = array(
	'userGift'=>array(
	'id',
	'userid'=>'gid',
	'type',
	'giftinfo',
	'giftid',
	'status',
	'addtime'=>'cashtime',
	'_type'=>'LEFT',
	),
	'Users' => array(
	'nickname',
	'usermail',
	'invite_uid',
	'addtime',
	'_on'=>'userGift.userid=Users.userid',
	'_type'=>'LEFT',
	)
	);
}
?>