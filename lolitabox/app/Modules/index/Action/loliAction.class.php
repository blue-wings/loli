<?php

/**
 * 萝莉推荐控制器
 * @author lit
 *
 */
class loliAction extends commonAction{
	
	/**
	 * 萝莉推荐首页
	 * @see CommonAction::index()
	 * @author litingting
	 */
	public function index(){
		$ac = $_REQUEST['ac'] ? $_REQUEST['ac'] : 1;
		if($ac==3) {
			header("location:".U("loli/daren"));
		}
		$aid= $_GET['aid'];
		$type=$_GET['type'] ? $_GET['type'] : 1 ;
		$size = 16;
		$user_share = D("UserShare");
		$article_mod = D("Article");
		switch($ac){
			case "1":    //晒盒
				$template="loli:index_ajaxlist";
				$timelist=$article_mod->getLoliShareTimeList();
				$list=$user_share->getShareListByDate($aid,$type,$this->getlimit($size));
				$num[1]=$user_share -> getShareCountByDate($aid,1);
				if($aid){
					$num[2]=$user_share -> getShareCountByDate($aid,2);
					$num[3]=$user_share -> getShareCountByDate($aid,3);
					$count=$num[$type];
				}else{
					$count=$num[1];
				}
				break;
			case "2":    //重榜策划
				$template="loli:special_ajaxlist";
				$timelist=$article_mod->getSpecialTimeList();
				$count=$article_mod->getSpecialCountByDate($aid);
				$size = $aid ? $count : 6 ;
				$list=$article_mod->getSpecialListByDate($aid,$this->getlimit($size));
				break;
		}
		$this->assign("loli_timelist",$timelist);
		$this->assign("aid",$aid);
		$this->assign("num",$num);
		$this->assign("ac",$ac);
		$param = array(
				"total" =>$count,
				'result'=>$list,			//分页用的数组或sql
				// 					'parameter' => "dynamic_type=".$_GET["dynamic_type"],
				'listvar'=>'list',				//分页循环变量
				'listRows'=>$size,					//每页记录数
				'target'=>'ajax_content',		//ajax更新内容的容器id，不带#
				'pagesId'=>'page',				//分页后页的容器id不带# target和pagesId同时定义才Ajax分页
				'template'=>$template,//ajax更新模板
				'parameter' =>"aid=".$aid."&ac=".$ac."&type=".$type,
		);
		$this->page($param);
		$return['title']="萝莉俱乐部,汇集所有产品试用分享,晒盒分享,精彩美肤专题-".C("SITE_NAME");
		$this->assign("return",$return);
		$this->assign("template",$template);
		$this->display();
	}
	
	public function daren(){
		$return['title']="达人订制_".C("SITE_NAME");
		$this->assign("return",$return);
		$this->display();
	}
	/**
	 * 获取晒盒时间列表
	 * @author penglele
	 */
	public function get_boxshare_timelist(){
		$box_mod=D("Box");
		$box_timelist=$box_mod->getBoxDateList("sdate DESC");
		$time1=array();
		$time2=array();
		if($box_timelist){
			foreach($box_timelist as $key=>$val){
				$arr=explode("-",$val['sdate']);
				if($key<=8){
					$time1[]=$arr;
				}else{
					$time2[]=$arr;
				}
			}
		}
		$list=array(1=>$time1,2=>$time2);
		return $list;
	}
	
	
	
	
	
	
	
	
	
	
	
}


?>