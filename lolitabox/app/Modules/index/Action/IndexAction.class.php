<?php
/**
 * 首页控制嚣 【THINKPHP 3.1.3】echo THINK_VERSION;
 */
class IndexAction extends commonAction {
	/**
	 * 首页方法
	 */
	public function index() {
		$return ['title'] = "萝莉盒就是化妆品试用-" . C ( "SITE_NAME" );
		$article_mod = D ( "Article" );
		$cookie_name = "index_guide";
		$cookie_val = $_COOKIE [$cookie_name];
		if (( int ) $cookie_val != 1) {
			$return ['if_guide'] = 1;
		}
		
		$return['linklist'] = D("Article")->getFriendLinks();
		
		if ($_GET ["sv"] == "old") {
			// ?sv=old 进入旧版
			$return ['brand_list'] = D ( "ProductsBrand" )->getRemmendBrandList (10 ); // 1
			$return ['products_list'] = $article_mod->getRemmendProductList ( 16 ); // 2
			$return ['focus_list'] = $article_mod->getIndexFocusPicList ( 1 );
			$return ['focus_num'] = count ( $return ['focus_list'] );
			$return ['count_products'] = count ( $return ['products_list'] );
			$return ['notice_list'] = $article_mod->getLoliNoticeList ( 5 );
			$return ['activity_list'] = $article_mod->getNewActivityList ( 1 );
			$return ['try_list'] = D ( "TryoutStat" )->getTryoutListOfProducts ( 3 );
			$return ['showbox_list'] = $article_mod->getShowBoxList ( 6 );
			$return ['boxarticle_list'] = $article_mod->getBoxArticleList ();
			$return ['rightAD'] = $article_mod->getBoxArticleRightAD ();
			$return ['cheaplist'] = $article_mod->getCheapList ();
			$return ['zhangcao'] = $article_mod->getZhangCao ();
			$this->assign ( "return", $return );
			$this->display ( "index" );
		} else {
			// 2013年12月4日新版
			$return ['brand_list'] = D ( "ProductsBrand" )->getRemmendBrandList ( 7 ); // 1
			$return ['products_list'] = $article_mod->getRemmendProductList ( 3 ); // 2
			$art_mod=M("Article");
			$return['llmz']=$art_mod->where("cate_id=784 AND status=1")->order("ordid DESC,id DESC")->find();
			$return['lltc']=$art_mod->where("cate_id=785 AND status=1")->order("ordid DESC,id DESC")->find();
			$return['llzx']=$art_mod->where("cate_id=786 AND status=1")->order("ordid DESC,id DESC")->find();
			$return["ilist"]=$article_mod->getBottomIntest();
			$this->assign ( "return", $return );
			$this->display ( "index_new" );
		}
	}
}

?>