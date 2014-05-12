<?php
//用户积分与统计的模型(user_credit_stat,user_credit_set)
class ScoreViewModel extends ViewModel {
    protected $viewFields = array(
        'UserCreditStat' => array (
			'*',
			'_table' => 'user_credit_stat',
			'_type' => 'LEFT'
		),
		'UserCreditSet' => array (
			'action_name','score','experience',
			'_on' => 'UserCreditStat.action_id=UserCreditSet.action_id',
			'_table' => 'user_credit_set',
		),
		//_on 是表的关联，如果两个表有相同字段的话，如果User表也定义title，
        //应该'title'=>'category_name'给它起个别名
    );
} 
?>