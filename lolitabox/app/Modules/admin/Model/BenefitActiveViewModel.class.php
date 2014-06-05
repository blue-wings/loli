<?php
class  BenefitActiveViewModel extends ViewModel{
	protected  $viewFields = array(
		'activityBenefit'=>array(
			'id',
			'userid',
			'type',
//			'placeinfo',
			'bottletype',
			'bottleinfo',
			'status',
			'postdate',
			'_type'=>'LEFT'
		),
		'Users' => array(
			'nickname',
			'usermail',
			'invite_uid',
			'addtime',
			'_on'=>'activityBenefit.userid=Users.userid',
			'_type'=>'LEFT'
		)
	);
}
?>