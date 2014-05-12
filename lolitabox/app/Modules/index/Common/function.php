<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: common.php 2601 2012-01-15 04:59:14Z liu21st $

/**
 * 根据boxid、userid给用户自选盒子产品 定义session名
 * @param boxid 盒子id
 * @param  userid 用户id
 * @return  string [session 名]
 */
function getBoxSessName($boxid,$userid){
	if(!$boxid || !$userid){
		return false;
	}
	return "boxid".$boxid."_".$userid;
}

/**
 * 根据boxid、userid给用户选择加价购的产品 定义session名
 * @param boxid 盒子id
 * @param  userid 用户id
 * @return  string [session 名]
 */
function getAddBoxSessName($boxid,$userid){
	if(!$boxid || !$userid){
		return false;
	}
	return "add".$boxid."_".$userid;
}

/**
 * 根据boxid,userid给用户选择积分兑换产品 定义session名
 * @param boxid 盒子ID
 * @param userid 用户ID
 * @return  string [session 名]
 */
function getExchangeProductSessName($boxid,$userid){
	if(!$boxid || !$userid){
		return false;
	}
	return "exchange".$boxid."_".$userid;	
}


function getSoloJumpUrl(){
	return U("buy/jump_sohu");	
}

?>