<?php
class infoAction extends commonAction {
	
	/**
	 * 关于我们
	 */
	public function about() {
		$article_mod = D ( 'Article' );
		if ($this->_get ( 'aid' )) {
			if($this->_get("aid")==1160){
				header("location:".U("info/lolitabox",array("aid"=>1160)));
			}
			$article_info = $article_mod->getArticleInfoById ( $this->_get ( 'aid' ) );
			if (empty ( $article_info )) {
				$this->error ( "未找到您想看的信息", "/" );
				exit ();
			}
		} else {
			$this->error ( "无效ID", "/" );
			exit ();
		}
		$userid = $this->getUserid ();
		if ($userid) {
			$return ['userinfo'] = D ( "Users" )->getUserInfo ( $userid );
		}
		$return ["title"] = $article_info ["seo_title"];
		$return ["keywords"] = $article_info ["seo_keys"];
		$return ["description"] = $article_info ["seo_desc"];
		$this->assign ( 'return', $return );
		$this->assign ( 'article_info', $article_info );
		$this->display ();
	}
	
	/**
	 * 玩转萝莉盒
	 */
	public function lolitabox() {
		if (! $this->_get ( 'aid' )) {
			$aid = 1147;
		} else {
			$aid = $this->_get ( 'aid' );
			if($aid==1160){
				$aid=1237;
			}else if($aid==11){
				$aid=1236;
			}
		}
		$article_mod = D ( 'Article' );
		if ($aid) {
			$article_info = $article_mod->getArticleInfoById ( $aid );
			if (empty ( $article_info )) {
				$this->error ( "未找到您想看的信息", "/" );
				exit ();
			}
		} else {
			$this->error ( "无效ID", "/" );
			exit ();
		}
		$userid = $this->getUserid ();
		if ($userid) {
			$return ['userinfo'] = D ( "Users" )->getUserInfo ( $userid );
		}
		$return ["title"] = !empty($article_info ["seo_title"]) ? $article_info ["seo_title"] : $article_info['title']."-".C("SITE_NAME");
		$return ["keywords"] = $article_info ["seo_keys"];
		$return ["description"] = $article_info ["seo_desc"];
		$return['aid']=$aid;
		$return['titlelist']=$this->getLolitaboxTitleList();
		$this->assign ( 'return', $return );
		$this->assign ( 'article_info', $article_info );
		$this->display ();
	}
	
	/**
	 * 友情链接
	 * 
	 * CATEID=738
	 * @author zhenghong
	 */
	public function friendlink(){
		
	}
	
	public function getLolitaboxTitleList(){
		$title_list=array(
				array(
						'title'=>"走进萝莉盒",
						'info'=>array(
								array(
										'aid'=>'1147',
										'title'=>'What is 萝莉盒'
								),
								array(
										'aid'=>'1234',
										'title'=>'常见问题'
								),
								array(
										'aid'=>'1484',
										'title'=>'会员政策说明'		
								),
								array(
										'aid'=>'1459',
										'title'=>'礼品卡使用帮助'
										)
								)
				),
				array(
						'title'=>"积分与经验值",
						'info'=>array(
								array(
										'aid'=>'1236',
										'title'=>'积分与经验值说明'
								),
								array(
										'aid'=>'1235',
										'title'=>'积分与经验值攻略'
								),
								array(
										'aid'=>'1237',
										'title'=>'积分试用问题'
								)								
								)					
				),		
				array(
						'title'=>"小萝莉秘笈",
						'info'=>array(
								array(
										'aid'=>'1245',
										'title'=>'晒盒秘笈'
								),
								array(
										'aid'=>'1273',
										'title'=>'分享秘笈'
								)								
								)
				)	
		);
		return $title_list;
	}
	
	/**
	 * 承接号外 号外 的内容
	 * @author penglele
	 */
	public function article() {
		$aid=$_GET["aid"];
		$article_mod = D ( 'Article' );
		if ($aid) {
			$article_info = $article_mod->getArticleInfoById ( $aid );
			if (empty ( $article_info ) || $article_info['cate_id']!=731) {
				$this->error ( "未找到您想看的信息", "/" );
				exit ();
			}
		} else {
			$this->error ( "无效ID", "/" );
			exit ();
		}
		if(!$article_info['info']){
			header("location:".$article_info['url']);exit;
		}
		$return['articlelist']=$this->getArticleTitleList();
		if(!$return['articlelist']){
			$this->error ( "未找到您想看的信息", "/" );exit;
		}
		$userid = $this->getUserid ();
		if ($userid) {
			$return ['userinfo'] = D ( "Users" )->getUserInfo ( $userid );
		}
		$return['leftAD']=$article_mod->getBoxArticleRightAD();
		$return ["title"] = $article_info ["seo_title"] ? $article_info['seo_title'] : $article_info['title'];
		$return ["title"] = strip_tags($return ["title"]);
		$return ["keywords"] = $article_info ["seo_keys"];
		$return ["description"] = $article_info ["seo_desc"];
		$return['aid']=$aid;
		$this->assign ( 'return', $return );
		$this->assign ( 'article_info', $article_info );
		$this->display ();
	}
	
	/**
	 * 号外！号外！的title列表
	 * @author penglele
	 */
	public function getArticleTitleList(){
		$titlelist=array();
		$list=D("Article")->getLoliNoticeList();
		if($list){
			foreach($list as $key=>$val){
				$info['title']=strip_tags($val['title']);
				$info['url']=$val['url'];
				$info['tar']= $val['info'] ? '' : "target='_blank'";
				$titlelist[]=$info;
			}
		}
		return $titlelist;
	}
	
	
	public function faq(){
		$faq_categoryid=794;
		$article_mod = D ( 'Article' );
		$faq_artile_list=$article_mod->where("cate_id=".$faq_categoryid)->select();	
		//dump($faq_artile_list);
		
		$return ["title"] = "常见问题";
		$return ["keywords"] ="";
		$return ["description"] ="";
		$this->assign ( 'return', $return );
		$this->assign ( 'FaqList', $faq_artile_list );
		$this->display();
	}
	
	
}