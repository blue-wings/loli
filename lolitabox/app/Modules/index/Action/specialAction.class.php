<?php
/**
 * 专题控制器
 */

class specialAction extends commonAction {
	
	function _empty(){
		$specialname=ACTION_NAME;
		$this->show($specialname);
	}


	/**
       +----------------------------------------------------------
       * 专题列表页(新改版)
       +----------------------------------------------------------
       * @access public
       +----------------------------------------------------------
       * @param  Array  focusImg   			专题焦点图
       * @param  Array  special_list  	    专题信息
       +-----------------------------------------------------------
       * @author zhaoxiang 2012.12.1 14:00
       */		
	public function index(){
		header("location:".U("user/welcome"));
		$this->display();
		exit;
	}

	/**
	 * 显示专题详情页
	 * @see Action::show()
	 * @author zhenghong@lolitabox.com
	 */
	protected  function show($specialname){
			$return=$this->getSpecialInfo($specialname);
			$this->assign("return",$return);
			$this->display($specialname);
	}

	/**
	 * 萝莉大比武2分享列表
	 * @param $shareid_list 分享ID组成的数组
	 */
	public function getShareListPk($shareid_list){
		if(!$shareid_list)
		return false;
		$arr_id=implode(",",$shareid_list);
		$share_mod=D("UserShare");
		$list=$share_mod->getShareList("id in ($arr_id)",10,"agreenum DESC");
		foreach($list as $key=>$val){
			$share_list[$key]['id']=$val['id'];
			$arr=$share_mod->getShareShortContent($val['content_all'],$val['posttime'],80);
			$share_list[$key]['content']=$arr['content'];
			$share_list[$key]['action_date']=$arr['action_date'];
			$share_list[$key]['more']=$arr['more'];
			$share_list[$key]['nickname']=$val['nickname'];
			$share_list[$key]['userface']=$val['userface'];
			$share_list[$key]['spaceurl']=$val['spaceurl'];
			$share_list[$key]['shareurl']=$val['shareurl'];
			$share_list[$key]['agreenum']=$val['agreenum'];
			$share_list[$key]['commentnum']=$val['commentnum'];
		}
		return $share_list;
	}


	private function getSpecialInfo($filename){

		$return = array();
		switch ($filename){
			case 'bsswd':
				$return['title']='补水保湿是保持肌肤漂亮滋润的王道-萝莉盒';
				$return['keywords']='补水保湿是保持肌肤漂亮滋润的王道';
				$return['description']='补水保湿是保持肌肤漂亮滋润的王道，水做的女人，水养的肌肤，才不会出现各种问题。补水基本可以分为四步骤，先拍化妆水，然后涂抹保湿润肤霜，之后再经常喷保湿喷雾，最后擦隔离霜和修颜霜。';
				break;
			case 'blmygtsmf':
				$return['title']='贝玲妃阳光天使蜜粉 轻松演绎天使般魔力妆容-萝莉盒';
				$return['keywords']='贝玲妃阳光天使蜜粉 轻松演绎天使般魔力妆容';
				$return['description']='轻肤裸透底妆与粉嫩自然双颊是2012春夏妆主流，加强粉嫩气色于苹果肌更能让魅力加倍流露， 而日前新上市的一款Benefit贝玲妃阳光天使蜜粉将为你带来一副天使般迷人的粉嫩双颊。';
				break;
			case 'qxdjs':
				$return['title']='七夕情人节约会技巧-打造约会水嫩肌-萝莉盒';
				$return['keywords']='七夕情人节 约会技巧 打造约会水嫩肌';
				$return['description']='七夕情人节的约会技巧和攻略是什么？LOLITABOX特别携时尚圈知名美妆师-吴淼教你轻松打造约会裸妆。此妆容技巧在于使用水润粉底打造无暇底妆，轻薄修容品凸显好肤色，自然分明眉眼增加神韵，侧刷双唇彩显亲切。';
				break;
			case '6ylxgwj':
				$return['title']='参加#LOLITABOX理性购物季#活动 开启6月神秘萝莉盒体验之旅-萝莉盒';
				$return['keywords']='萝莉盒 性感 美丽 时尚潮流资讯 分享化妆品试用美丽心得 参加#LOLITABOX理性购物季#活动 开启6月神秘萝莉盒体验之旅 活动 护肤 购物 星座';
				$return['description']='现在参加#LOLITABOX理性购物季#活动，赢取邀请码，参与产品评测赢取优惠券，开启6月神秘萝莉盒体验之旅！（小编偷偷透露本期礼盒大牌繁多，专为夏天定制你的清爽美丽！）如果你已经是LOLITABOX会员还有机会获得二次试用机会或优惠劵哟（5月优惠券订购仍然有效，但不能重复使用哦）';
				break;
			case 'gbcdxf':
				$return['title']='预防抵制初老症状，告别冲动消费-萝莉盒';
				$return['keywords']='预防抵制初老症状 告别冲动消费';
				$return['description']='预防抵制初老症状，是有技巧的，可以先从护肤开始，将自己的护肤品换成具有抗皱紧致、润肤锁水功效的。想看看自己是不是具有初老症，小编为大家整理出的50条初老症状，以便更好地认清自己是否真实处于初老前期，及时补救还来得及。';
				break;
			case 'xrqshf':
				$return['title']='夏日清爽大作战 护肤步骤4步曲-萝莉盒';
				$return['keywords']='夏日清爽大作战 护肤步骤4步曲';
				$return['description']='紫外线在夏季变得越来越强，我们的肌肤也需要来一场夏日大作战。夏季肌肤最容易出现的问题有暗淡无光、烈日灼伤、毛孔粗大、油水不均，如果你正在被这些肌肤问题困扰，就跟小编一来学习一下夏季护肤的基本步骤吧！';
				break;
			case 'dzzslh':
				$return['title']='LOLITABOX 6月神秘盒揭晓，定制你的专属礼盒-萝莉盒';
				$return['keywords']='LOLITABOX 6月神秘盒揭晓 定制你的专属礼盒';
				$return['description']='LOLITABOX 6月神秘盒终于揭晓啦，本期的萝莉盒必定会让大家眼前一亮，不仅有奢华美妆盒，同时还有benefit专属礼盒，Benefit三重防晒清透乳液、Benefit 反孔精英脸部底霜、Benefit 妆前保湿柔肤水等新品通通包含在其中。';
				break;
			case 'zsmk':
				$return['title']='毛孔粗怎么办-LOLITABOX 7月强力推荐战胜毛孔方案-萝莉盒';
				$return['keywords']='毛孔粗怎么办 LOLITABOX 7月强力推荐战胜毛孔方案';
				$return['description']='毛孔粗怎么办？LOLITABOX 7月强力为您推荐如何解决毛孔粗大、皮肤缺水已经是皮肤问题中的顽疾，如果没有及时做好皮肤护理，清洁、补水不够，会加重毛孔问题。所以爱漂亮的MM一定要加强对毛孔的重视。';
				break;
			case 'pswxnw':
				$return['title']='秋季护肤小常识 美白保湿不能少-萝莉盒';
				$return['keywords']='秋季护肤小常识 美白保湿不能少';
				$return['description']='天气渐渐转凉，LOLITABOX特意为大家准备了秋季护肤小常识，仔细学习就能让你轻松拥有美白滋润的肌肤。在初秋里锦衣夜行，无论是要彰显个性，还是追求华贵，抑或强调性感，都能让大家一眼就能发现你。';
				break;
			case 'qxtmjx':
				$return['title']='七夕节甜蜜惊喜，轻松虏获TA的心-萝莉盒';
				$return['keywords']='七夕节甜蜜惊喜 轻松虏获TA的心';
				$return['description']='浪漫七夕节，空气中都蔓延着情侣之间的甜蜜味道。Lolitabox特别为你量身打造美丽升级计划，让TA眼前一亮，并在下一秒虏获TA的心，让彼此都留下超乎想象的美好回忆吧！';
				break;
			case 'wmlzdzs':
				$return['title']='裸妆怎么画 知名美妆师吴淼教你裸妆打造术-萝莉盒';
				$return['keywords']='裸妆怎么画 知名美妆师吴淼教你裸妆打造术';
				$return['description']='裸妆怎么画？毫无打造痕迹，却比平时精致通透的秘密到底在哪里？就让知名美妆师吴淼为你找到奥秘所在，步步为营让你掌握裸妆打造术，做个完美裸色系美女！';
				break;
			case 'fzcs':
				$return['title']='Lolitagirls 肤质测试 -萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 肤质测试';
				$return['description']='想知道你的肌肤到底属于哪种肤质吗？中性、干性、油性、混合性、敏感性，到底是哪一种，赶紧来Lolitabox来测试一下吧';
				break;
			case 'lljm1':
				$return['title']='萝莉解码第一期 分享有礼暨8月萝莉盒美文大赏-萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 晒单 美文 晒盒子 达人';
				$return['description']='秋风初凉的浪漫8月，LOLITABOX一共发布了3期不同主题的萝莉盒，爱时尚爱分享的Lolitagirls也在收到盒子后进行了晒货分享。但由于每一期的萝莉盒数量都有限，不少Lolitagirls都没能订到萝莉盒！为了让喜欢并支持LOLITABOX的Lolitagirls能更直观更真实了解萝莉盒，小编特意搜集了部分Lolitagirls分享的精美图文，与大家一起欣赏，一同探索8月萝莉盒的秘密……';
				break;
			case 'szllj':
				$return['title']='做好护肤细节，你也可以拥有着水嫩的萝莉肌-萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 水嫩肌肤';
				$return['description']='做好护肤细节，你也可以像明星一样，拥有着水嫩的萝莉肌，青春有多长，由你自己来决定！金秋，LOLITABOX为大家带来最有范儿的萝莉盒，由清洁到保养，关注护肤细节，强势阻挡岁月的脚步，轻松锁住吹弹可破的年轻萝莉肌。';
				break;
			case 'llzht1':
				$return['title']='怎么瘦脸？LOLITABOX让你饼脸变V脸—萝莉智慧团第1期-萝莉盒';
				$return['keywords']='怎么瘦脸 瘦脸的方法 娇韵诗V脸精华';
				$return['description']='怎么瘦脸？怎么样让饼脸变成V脸？巧妙利用发型、彩妆、按摩、运动甚至是食疗等方法都能塑造小脸效果！萝莉智慧团第一期，将为Lolitagirls推荐4式靠谱塑脸法，让你华丽变身，轻松拥有上镜小V脸！';
				break;
			case 'llzht2':
				$return['title']='萝莉智慧团第2期 国庆中秋百搭造型 魅惑出行抢占皓月之光—萝莉盒';
				$return['keywords']='出游穿搭 中国好声音 2013春夏时装周';
				$return['description']='在如潮水般的出行人群里，要如何穿搭，才不会让自己在人海中被淹没？别担心！本期萝莉智慧团将为Lolitagirls带来百变时尚造型参考，让你魅惑出行，比皓月之光更加光芒四射，抢尽众人眼球独领风骚！';
				break;
			case 'szwmn':
				$return['title']='LOLITABOX教你男士护肤打造术，塑造你的完美男人-萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 男士护肤';
				$return['description']='LOLITABOX将教Lolitagirls如何进行男士护肤，体贴他的&quot;面子&quot;，塑造体贴男友，他有&quot;面子&quot;之时你也有face！';
				break;
			case 'ljdg':
				$return['title']='立即订购-萝莉盒，性感、美丽、时尚潮流资讯、分享化妆品试用美丽心得';
				$return['keywords']='化妆品试用、化妆品盒子、萝莉盒化妆品试用网';
				$return['description']='化妆品试用，萝莉盒化妆品试用网根据您的个性化需求，为您定制专属您的服务，每月免费配送5-8款品牌化妆品试用装。晒单、体验、分享、反馈获得积分奖励， 您可以用来免费换取新的盒子。';
				break;
			case 'hgyl1':
				$return['title']='盒该有礼第一期 发表商品评测，赢取免费萝莉盒-萝莉盒';
				$return['keywords']='免费萝莉盒 LOLITABOX';
				$return['description']='盒该有礼第一期，只要根据活动规则完成指定任务，就有机会获得免费的神秘萝莉美妆盒，所有费用一应全免！你心动了木有？心动就赶快行动起来吧！';
				break;
			case 'mfhf':
				$return['title']='LOLITABOX金秋十月护肤 “膜”法护肤养出通透美肌-萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 国货 秋冬护肤 面膜 补水保湿';
				$return['description']='秋季护肤主要有两大任务：一是尽快弥补夏季强烈的紫外线对肌肤的伤害；二是为应对冬季恶劣的环境聚集能量。金秋十月，肌肤养护任重道远！本期LOLITABOX精心为大家带来护肤“膜”法，解决肌肤麻烦事，轻松养出通透美肌……';
				break;
			case 'llzht3':
				$return['title']='萝莉智慧团第3期 长假过后快速恢复光鲜亮人形象-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 长假过后护肤';
				$return['description']='长假过后，黑眼圈、痘痘、晒斑等脸上肌肤问题和身体肌肤问题接踵而来！长假过后如何快速恢复光鲜亮人形象，萝莉智慧团第三期，小编为你支招！';
				break;
			case 'llzht4':
				$return['title']='萝莉智慧团第四期  细数国货护肤品-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 国货护肤品';
				$return['description']='国货护肤品从小到大一直陪伴着我们，双妹、百雀羚、友谊、相宜本草、昭贵、芳草集等等，你肯定都知道这些国货。今天，萝莉智慧团第四期为大家温馨盘点那年今日，我们一起用过的好用的国货护肤品，看看有没有那么几款也是你奉为至宝的！';
				break;
			case 'qdbskl':
				$return['title']='秋冬护肤头等事 补水抗老甩暗沉-萝莉盒';
				$return['keywords']='LOLITABOX萝莉盒 秋冬护肤 补水保湿 抗老';
				$return['description']='秋冬季节，肌肤问题最明显的是缺水干燥！秋冬护肤，补水抗老甩暗沉都是头等大事，一点也不能马虎！本期LOLITABOX帮大家一一扫除各个肌肤问题，让肌肤焕发如同婴儿般的细腻亮白状态！';
				break;
			case 'llzht5':
				$return['title']='光棍节画个漂亮的妆容去HAPPY吧—萝莉盒';
				$return['keywords']='萝莉智慧团 LOLITABOX萝莉盒 光棍节';
				$return['description']='今年的光棍节又到了，不要急，化个漂亮的妆，穿上最得瑟的服饰，去shopping，去看电影，去旅行……一个人的生活也可以很阳光灿烂哟！';
				break;
			case 'shfbh':
				$return['title']='Lolitabox平台发布会 走进我们感受不一样的LOLITABOX—萝莉盒';
				$return['keywords']='Lolitabox平台发布会 萝莉盒';
				$return['description']='Lolitabox平台发布会在上海安达仕酒店隆重举行，盛会邀请了来自于知名媒体、时尚名企、知名化妆品品牌方的六十多位嘉宾共同探索LOLITABOX的神秘力量，见证LOLITABOX网站新功能及APP客户端的发布。';
				break;
			case 'hrjh':
				$return['title']='HR赫莲娜 精华女皇 经典传奇 美容界的科学先驱-萝莉盒';
				$return['keywords']='HR赫莲娜 萝莉盒 LOLITABOX';
				$return['description']='真正懂得护肤的女人，在适合的时候就会开始抗老养护，让肌肤保持闪耀动人的光彩。作为世界顶级奢侈美容品牌的HR赫莲娜，在抗老养护上一直都有着卓越功效，如果你也认同女人就应该宠爱自己的道理，这个品牌你大概会很喜欢……';
				break;
			case 'llzht6':
				$return['title']='萝莉智慧团第六期 与你一起芳香养肤，乐享芳香慢生活—萝莉盒';
				$return['keywords']='精油护肤 芳香护肤 LOLITABOX萝莉盒';
				$return['description']='精油护肤特别适合在秋冬季节强效滋润干燥、粗糙的肌肤，而其怡人的天然植物精萃香味更让人轻松愉悦！这个冬天，萝莉智慧团与你一起芳香养肤，乐享芳香慢生活……';
				break;
			case 'qdyf':
				$return['title']='干燥寒冬 养肤你用什么？-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 冬季护肤 双妹 贝玲妃';
				$return['description']='气候在变，低温干燥，再加上从西伯利亚刮来的彪悍冷风，单靠一瓶保湿乳，又怎么能满足你那娇嫩、脆弱的肌肤呢？寒冬养肤你需要全方位的保护。本期LOLITABOX为你带来寒冬养肤术，让你轻松HOLD住精雕玉琢般的粉嫩容颜……';
				break;
			case 'jpllh':
				$return['title']='《精品》萝莉盒强势登陆-萝莉盒';
				$return['keywords']='LOLITABOX 精品萝莉盒 施丹兰 圣诞节';
				$return['description']='浪漫圣诞节，LOLITABOX倾情推出首款全正装规格萝莉盒，精选施丹兰品牌香薰及身体护理产品配以浪漫时光花果茶，给予你最芬芳的享受与惊喜！';
				break;
			case 'llzht7':
				$return['title']='萝莉智慧团第七期 派对月 精装打扮闪亮起来-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 圣诞妆容';
				$return['description']='既然到了12月份，我们当然要毫不手软的渲染助涨节日的欢乐气氛啦~~~打酱油过个圣诞，紧接着是新年和春节，用什么LOOK迎接节日扎堆的这段派对季呢？不管怎样风格的LOOK，菇凉们，关键是要让自己闪亮起来！';
				break;
			case 'blfzsllh':
				$return['title']='贝玲妃专属萝莉盒璀璨登场';
				break;
			case 'pdnw':
				$return['title']='4招改善肤质 小心机变身派对女王-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 派对女王 冬季护肤 改善肤质';
				$return['description']='每个mm都想成为party宴会的女王，可是你真的准备好了么？只要肌肤出现一些例如爆痘掉皮等小问题，就会让精致的派对妆容黯然失色。小编这就教大家4招改善肤质各种问题，获得细滑亮白容易上妆的完美肌肤，变身闪耀的派对女王……';
				break;
			case 'llzht8':
				$return['title']='萝莉智慧团第八期 养出滋润水嫩肌肤-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 萝莉智慧团第八期';
				$return['description']='缺水粗糙、起皮屑、干痒泛红、干纹横生……来到2013年新纪元，却因格外不同的严寒，肌肤还在继续闹各种情绪！想要安抚它，你需要的是全方位+全天候给予肌肤保湿和滋润。';
				break;
			case 'llzht9':
				$return['title']='萝莉智慧团第9期 打造零瑕疵年会妆-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 萝莉智慧团第9期';
				$return['description']='又到了一年总结的时候，想在公司的年会中给领导和同事留下好印象，低调奢华又得体的美妆才是明智的选择。知性、熟女的零瑕疵气质妆容，在公司年会中一定会提升你的魅力度。';
				break;
			case 'smcq':
				$return['title']='雙妹传奇—东情西韵 尽态极妍-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 雙妹玉容霜';
				$return['description']='东方的西方的、民族的国际的、摩登的经典的、传统的时尚的、内敛的开放的。是游离在东与西、经典与摩登之间。含蓄是东方意态的传统表达，是骨子里的文化根基；而热烈和奔放，则来自于对西方风潮的倾慕。——这就是雙妹所代表的上海名媛文化。';
				break;
			case 'loccitane':
				$return['title']='真实的天然 来自普罗旺斯的芳香';
				$return['keywords']='LOLITABOX 萝莉盒 欧舒丹 普罗旺斯 芳香';
				$return['description']='也许很难用一个词语来囊括普罗旺斯弥漫的所有芳香与幸福，而L&acute;OCCITANE欧舒丹却将普罗旺斯的气息原原本本地装进了一款款产品中，在涂抹的瞬间，让人仿佛徜徉在浪漫的普罗旺斯秘密花园中……';
				break;
			case 'pureskin':
				$return['title']='爱肌肤，醇自然 赶走不安定肌';
				$return['keywords']='LOLITABOX 萝莉盒 肌醇 调理型护肤 零刺激';
				$return['description']='因工作压力和环境的影响，你的皮肤是否已逐渐变得脆弱、不安定起来？痘痘、毛孔、脱皮、过敏泛红……一波接一波的轮番袭击，该如何应对？关键就在于减少刺激！而想要减少刺激，你需要的是最纯净的力量——无添加，零刺激，零负担的调理型护肤品……';
				break;
			case 'fymy':
				$return['title']='蜂言蜜语-彩虹花田下的清新与纯净-萝莉盒';
				$return['keywords']='LOLITABOX 萝莉盒 蜂言蜜语';
				$return['description']='一份女孩践行梦想的坚持，一个来自彩虹花田下纯净的有机护肤品牌——蜂言蜜语。当百花绽放时，不妨带上最清新与纯净的梦想与护肤品，一起来一段甜蜜旅程吧……';
				break;
			case 'velds':
				$return['title']="Veld's苇芝 源自原生态的力量-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 苇芝 Veld&rsquo;s 抗衰老';
				$return['description']="&quot;Veld&rsquo;s，一个以南非自然草原来命名的品牌，其创始人的美丽之梦就是要让每一位女性尽可能保持或恢复肌肤年轻时的肤质、肤色和光泽，恢复肌肤原有的美丽。";
				break;
			case 'llsys16':
				$return['title']="萝莉实验社第16期 新年换新颜-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 新春护肤';
				$return['description']="又是一年春来到，不管你是人前人后两个样子的干物女，还是长期素颜示人的女屌丝，新的一年就应该给肌肤送上一份厚厚的年终奖，让肌肤以最完美的姿态重新出发!";
				break;
			case 'llzht10':
				$return['title']="萝莉智慧团第10期-节后完美护肤走起来-萝莉盒";
				$return['keywords']='节后护肤 LOLITABOX 萝莉盒';
				$return['description']="春节长假进行到这里，该吃的吃足了，该玩的玩够了。一觉睡到中午对着镜子一看，吓了自己一跳：肤色暗黄、痘痘、眼圈黑黑接踵而来！总不能这个样子去上班吧？美肤，刻不容缓！节后疲惫的肌肤怎样拯救，快来和小编一起看看吧！";
				break;
			case 'origins':
				$return['title']="Origins悦木之源 纯净宣言 天然为本-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 悦木之源 Origins 天然';
				$return['description']="大自然的力量是浩瀚伟大的。你知道么？自然界存于地球已逾38亿年历史。而现代科学呢？不过367年。较之于自然巨人，人类科学还去之甚远。所以我们相信源于大自然的可靠力量，深谙其背后蕴藏的无穷宝藏！……";
				break;
			case 'sonoko':
				$return['title']="SONOKO荘能子 零负担 熠生美 源自银座的美颜传奇-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 荘能子 SONOKO 零负担';
				$return['description']="SONOKO荘能子，一个来源于日本最繁华中心区的品牌，倡导尽量减轻肌肤负担，用自身力量来解决现有问题，让肌肤在5年、10年、20年后，依然保持健康与美丽！肌肤要减压，还要永葆美丽？相信这个品牌能帮到你……";
				break;
			case 'tsubaki':
				$return['title']="丝蓓绮 闪耀吧，更美的自己-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 丝蓓绮 奢耀';
				$return['description']="闪亮、丰盈的头发不仅显得五官小巧，更能表达你的灵性与生命力。美丽动人的你，如何让自己愈发闪耀？让丝蓓绮来激发你的美丽潜能，闪耀出最美的自己吧！那么，生命也将无比闪亮与丰盈。";
				break;
			case 'llsys17':
				$return['title']="萝莉实验社 第17期 三月不美白 夏季徒伤悲";
				$return['keywords']='LOLITABOX 萝莉盒 三月护肤 美白 春季美白';
				$return['description']="春季，就是美白的第一步，在还没有晒黑、紫外线还不是很强烈的时候抢占先机，你便在整年的美白工作中抢到了主动。不想在2013年整个夏季里徒伤悲，立马跟着小编展开全方位美白大计吧！";
				break;
			case 'kerastase':
				$return['title']="相约卡诗，奢享尖端护发-萝莉盒";
				$return['keywords']='LOLITABOX 萝莉盒 卡诗';
				$return['description']="拥有一头光泽亮丽滑润的秀发，已经不只是每个女人都期望的事了，当下追求时尚的标准也已经不止穿着这么简单，越来越多的男男女女走在时尚发型前沿，因此人们更注重头发的质量，护发也就随着当下的时尚潮流而更受关注。";
				break;
			case 'cyllh':
				$return['title']="小长假出游 随身必备萝莉盒-LOLITABOX萝莉盒";
				$return['keywords']='五一小长假 LOLITABOX 萝莉盒';
				$return['description']="五一小长假即将到来，出游化妆包准备好了吗？不妨带上LOLITABOX精心打造的小长假出游随身必备萝莉盒吧，一站式解决旅途护肤问题。送自己，送闺蜜，送家人，轻松出行！我们还可以帮你手写祝福卡片哦~";
				break;
			case 'llsys18':
				$return['title']="萝莉实验社 第18期 美丽全攻略 赢在初夏";
				$return['keywords']='LOLITABOX 萝莉盒 初夏护肤 美白 防晒 控油';
				$return['description']="灿烂的阳光不打招呼就已到来，气温上升，紫外线明显，皮肤的防御机能随之大幅波动。清洁、控油、防晒…在这美丽与魅力重现的季节里，不管技巧，还是产品，都力求一击即中！";
				break;
			case 'llpk1':
				$return['title']="萝莉夏季美容护肤大PK—LOLITABOX萝莉盒";
				$return['keywords']='LOLITABOX萝莉盒 夏季美容护肤';
				$return['description']="即将到来的酷暑骄阳，让各种问题也随之而来，例如：恼人体味、肌肤问题、干枯干燥等秀发问题……这些问题如何应对？快来加入萝莉大比武，分享你的独特见解吧，见解得到大家一致的赞同有机会获得超值萝莉盒哦！";
				break;
			case 'iphoneapp':
				$return['title']="萝莉盒iphone手机APP客户端下载—LOLITABOX萝莉盒";
				$return['keywords']='LOLITABOX萝莉盒 夏季美容护肤';
				$return['description']="萝莉盒iphone客户端上线啦，最心水的萝莉盒，随时发现，随时下单哦！";
				break;
			case 'llpk2':
				$return['title']="萝莉夏季美容护肤大PK—LOLITABOX萝莉盒";
				$return['keywords']='LOLITABOX萝莉盒 夏季美容护肤';
				$return['description']="即将到来的酷暑骄阳，让各种问题也随之而来，例如：恼人体味、肌肤问题、干枯干燥等秀发问题……这些问题如何应对？快来加入萝莉大比武，分享你的独特见解吧，见解得到大家一致的赞同有机会获得超值萝莉盒哦！";
				break;
			case 'fwbz':
				$return['title']="萝莉盒服务保障-LOLITABOX萝莉盒";
				$return['keywords']='';
				$return['description']="";
				break;
			case 'qxllh':
				$return['title']="迎接夏日时光，清香随行萝莉盒-LOLITABOX萝莉盒";
				$return['keywords']="夏日 清香 萝莉盒 LOLITABOX";
				$return['description']="夏日穿香，萝莉盒首推清香随行礼盒，能够助你由内而外，由上而下都散发出迷人芬芳，与香为伴~";
				break;
			case 'xrhch':
				$return['title']="吴淼老师推荐夏日清爽焕采盒-LOLITABOX萝莉盒";
				$return['keywords']="吴淼 萝莉盒 清爽控油 夏日护肤";
				$return['description']="要让肌肤度过一个清爽动人的夏日，有没有给力武器？答案就在这里——资深彩妆专家吴淼老师推荐的夏日清爽焕采盒，从清洁到润颜，从护肤到彩妆，让你的美丽大计不再存在难题，轻松打造100%完美女孩形象~";
				break;
			case 'mzkbb':
				$return['title']="2012-2013萝莉盒美妆口碑榜-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 美妆口碑 贝玲妃 娇韵诗";
				$return['description']="2012年4月—2013年4月，萝莉盒一岁啦~感谢众多的Lolitagirls一直以来对萝莉盒的大力支持和喜爱，你们的支持是我们进步的最大动力，感谢一路有你，我们在奔跑的路上也是美美哒~如果你曾经错过了我们，那么跟随小编的自豪之心一起来看看我们这一年的经验和故事，一定让你大有收获。";
				break;
			case 'aft':
				$return['title']="萝莉盒-励志音乐剧《纳斯尔丁•阿凡提》-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 美妆口碑 纳斯尔丁•阿凡提";
				$return['description']="励志音乐剧《纳斯尔丁•阿凡提》";
				break;
			case 'lxaml':
				$return['title']="旅行爱美丽-浪漫陪你走天涯-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒  LOLITABOX  国庆旅行";
				$return['description']="国庆出游时护肤品可不能少，但是需要带的东西太多，箱子容量又有限，怎么办呢？别急，简单几样旅行装，就能让你在旅行的留念照片中呈现最完美动人的一面哦！";
				break;
			case 'kh':
				$return['title']="狂欢派对季-变身时尚Party Queen-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 LOLITABOX  狂欢派对季";
				$return['description']="派对季is coming！派对女孩们是不是已经蠢蠢欲动了呢？身着美服，画上美妆，将partytime提上日程！小萝莉想说，我们会帮你找到想要但不知道的一切美丽武器，从此告别&quot;壁花小姐&quot;，成为众人的焦点！";
				break;
			case 'jtqz':
				$return['title']="击退秋燥 坐拥水润净透美肌-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 LOLITABOX 秋季护肤";
				$return['description']="秋季如何做好补水保湿呢？又需要用到哪些补水保湿产品呢？别急，跟随小萝莉就能轻轻松松做个水嫩润泽的水美人！巧帮肌肤&quot;饮水止渴&quot;，然后傲娇的面对秋天大声说：&quot;HEY!COME ON&quot;！";
				break;
			case 'xszn':
				$return['title']="萝莉盒 新手指南-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 LOLITABOX 新手指南 常见问题 使用帮助";
				break;
			case 'mldc':
				$return['title']="Lolitagirls美丽调查 防晒知多少";
				$return['keywords']="萝莉盒 LOLITABOX 防晒 菲诗小铺 宝琪兰";
				$return['description']="夏日已逝，但防晒这个话题不是只活跃在夏季哦！你知道吗？室内室外、春夏秋冬，防晒都在继续，不能忽略！小萝莉对亲爱的萝莉盒会员展开了防晒调查，让我们一起来了解大家的防晒习惯，科普防晒知识，将美丽事业进行到底吧！";
				break;
			case 'chh':
				$return['title']="萝莉吃货盒 闪亮登场";
				$return['keywords']="萝莉盒 LOLITABOX 早餐 午餐 零食";
				$return['description']="";
				break;					
			case 'llxty':
				$return['title']="萝莉“心”体验 之 新会员独享神秘盒——LOLITABOX  萝莉盒";
				$return['keywords']="萝莉盒  LOLITABOX  神秘体验";
				$return['description']="你是否在玲琅满目的萝莉盒中，找不到先要体验的那个？新会员独享神秘盒内含多款明星产品，带你抢先体验“别人”的好用私藏单品！";
				break;
			case 'zcl':
				$return['title']="你敢长草“我”就敢除！-LOLITABOX 萝莉盒";
				break;
			case 'yklsh':
				$return['title']="伊卡璐丝焕正装萝莉盒-LOLITABOX 萝莉盒";
				$return['keywords']="伊卡璐 萝莉盒  LOLITABOX";
				$return['description']="萝莉盒和宝洁联手，打造网上正装试用最低价，来自欧洲原装进口，伊卡璐丝焕CLAIROL正装三件套，3.9折试用，加20元还可获得价值128元的玉兰油新生修纹紧致弹力面膜3片。";
				break;
			case 'cub':
				$return['title']="CUB携手萝莉盒，给你最温暖的滋润-LOLITABOX 萝莉盒";
				$return['keywords']="CUB 身体牛油 萝莉盒  LOLITABOX";
				$return['description']="关于CUB身体牛油的护肤探秘，给予身体肌肤的营养大餐。";
				break;
			case 'member':
				$return['title']="萝莉盒特权会员闪亮登场-LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 LOLITABOX  特权会员";
				$return['description']="萝莉盒特权会员闪亮登场啦，现在加入月度/年度会员，还有特别优惠~";
				break;
			case 'qjqshh':
				$return['title']="超值全身呵护萝莉盒 -LOLITABOX萝莉盒";
				$return['keywords']="萝莉盒 超值呵护 LOLITABOX  特权会员 补水 洁肤 防晒 睡前护肤 全身护理";
				$return['description']=" LOLITABOX精心为你打造秋季必备-超值全身呵护萝莉盒，各种好用单品推荐，从上到下一站式帮你解决秋季全身护肤问题！";
				break;
			case 'gdzx':
				$return['title']="萝莉盒限量购之高端尊享萝莉盒—LOLITABOX  萝莉盒";
				$return['keywords']="萝莉盒  LOLITABOX  秋季护肤";
				$return['description']=" 秋冬时节，季节交替，空气变冷变干燥，需要你特别注意肌肤的保养，这次萝莉盒特别隆重推出【高端尊享萝莉盒】，带你体验国际一线高端品牌产品，让你的肌肤尊享奢华护肤感受。";
				break;	
			case 'tqhhzyq':
				$return['title']="特权会员限时自由抢萝莉盒—LOLITABOX  萝莉盒";
				$return['keywords']="萝莉盒 LOLITABOX 特权会员";
				$return['description']="特权会员推出限时自由抢萝莉盒啦，只要你是萝莉盒特权会员，只要你眼疾手快，就可以挑选到你想要的众多不同好用产品！就在这个专门为特权会员所精心准备的萝莉盒里哦！只有你想不到，没有你找不到！";
				break;
			case 'ndsy':
				$return['title']="抢先订阅2014年度试用萝莉盒，赠送全年特权会员费！-LOLITABOX 萝莉盒";
				$return['keywords']="萝莉盒  LOLITABOX  年度试用萝莉盒";
				$return['description']="为免去您的众多烦恼和担心，萝莉盒特别重磅推出3款2014限量年度试用萝莉盒，无论是犒赏自己，还是送给亲朋好友， 2014限量年度试用萝莉盒都是您的不二之选！现订阅已正式开始，赶紧来抢定吧，来晚了或者手慢了都还要再等一年哦！";
				break;
			case '1111':
				$return['title']="萝莉盒双十一优惠特卖，享双倍优惠，5.8折购买-LOLITABOX 萝莉盒";
				break;
			case 'novemberhd':
				$return['title']="萝莉盒化妆品免费试用 特权会员，优惠折扣 -LOLITABOX 萝莉盒";
				$return['keywords']="化妆品免费试用，推出会员特权，优惠打折，付邮试用，积分试用，化妆品试用装购买优惠";
				$return['description']="化妆品试用网站萝莉盒lolitabox推出会员特权，购买萝莉盒4折，还能付邮试用，积分试用超低打折，加入特权会员立即超低价购超值萝莉盒，试用装全部正品授权";
				break;		
			case 'chh2':
				$return['title']="萝莉吃货盒第二季 -LOLITABOX 萝莉盒";
				$return['keywords']="吃货盒,萝莉盒";
				$return['description']="萝莉吃货盒第二弹再度来袭，提供最方便最节约的享用甜点、下午茶、美食的方法，你再也不用在超市里顾左右而言他了";
				break;		
			case 'sdlh':
				$return['title']="温情圣诞限量神秘盒 -LOLITABOX 萝莉盒";
				$return['keywords']="萝莉盒  LOLITABOX  温情圣诞";
				$return['description']="LOLITABOX特别推出温情圣诞限量神秘盒，多款好用产品，和你一起过圣诞。让这个冬天邂逅浪漫，让寒冷的圣诞不再寒冷！";
				break;
			case 'qnwn':
				$return['title']="一次下单 全年温暖 -LOLITABOX 萝莉盒";
				$return['keywords']="新年送什么 礼物,圣诞节送什么;创意礼物,2014元旦送什么";
				$return['description']="圣诞节送什么礼物？2014年元旦送什么礼物？送她全年萝莉盒！每个月，精美的礼盒都会带着满满的祝福和爱意，伴随着沉甸甸的神秘惊喜被送到Ta的手中，2014年12个月的坚持足以鉴证你的绵长心意，如此创意礼物还等什么？";
				break;
			case 'tlf':
				$return['title']="人参肽赋活修颜蚕丝面膜_人参肽美白极润蚕丝面膜-".C("SITE_NAME");
				break;
			case "saylove":
				$return["title"]="2014年2月“SAY LOVE”美妆盒，萝莉盒替你大声说爱！情人盒,情人节";
				$return['keywords']="情人节送什么?2月14日有什么可以送人?";
				$return['description']="情人节送什么礼物？2014年情人节送什么礼物？";
				break;
			}
		//吃货盒的活动时间
		if($filename=="chh"){
			$return['starttime']=date("Y/m/d H:i:s");
			$return['endtime']="2013/11/10 00:00:00";
			if($return['starttime']>$return['endtime']){
				$return['if_end']=1;
			}
		}else if($filename=="zcl"){
			$pro_mod=D("TryoutStat");
			$return['prolist']=$pro_mod->getTryoutListOfProducts(5);
			$return['userlist']=$pro_mod->getTryoutListOfUser(20);
		}else if($filename=="yklsh"){
			$return['starttime']=date("Y/m/d H:i:s");
			$return['endtime']="2013/11/8 00:00:00";
			if($return['starttime']>$return['endtime']){
				$return['if_end']=1;
			}
		}else if($filename=="cub"){
			$ntime=date("Y-m-d");
			if($ntime>="2013-11-01"){
				$return['if_buy']=1;
			}
		}else if($filename=="tqhhzyq"){
			$nday=date("d");
			if($nday==20){
				$return['if_time']=0;
			}else{
				$return['if_time']=1;
				$return['starttime']=date("Y/m/d H:i:s");
				$return['endtime']=date("Y/m")."/20 00:00:00";
				if($nday>20){
					$return['endtime']=date("Y/m/d H:i:s",strtotime($return['endtime']."1 month"));
				}
			}
		}else if($filename=="1111"){
			$return['starttime']=date("Y/m/d H:i:s");
			$return['endtime']="2013/11/13 23:59:59";
			if($return['starttime']>$return['endtime']){
				$return['if_end']=1;
			}
		}
		
		return $return;
	}

}
?>
