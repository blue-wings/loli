<?php
// +----------------------------------------------------------------------
// | 简易中文分词系统
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.ftphp.com/scws All rights reserved.
// +----------------------------------------------------------------------

class ScwsModel extends Model {
	
	public function getTagsByContent($data)
	{
		vendor('pscws4.pscws4', '', '.class.php');
		$pscws = new PSCWS4();
		$pscws->set_dict(SCWS_ROOT.'dict.utf8.xdb'); //设置分词引擎所采用的词典文件
		$pscws->set_rule(SCWS_ROOT.'rules.utf8.ini'); //设定分词所用的新词识别规则集（用于人名、地名、数字时间年代等识别）
		$pscws->set_ignore(true); //设定分词返回结果时是否去除一些特殊的标点符号之类
		$pscws->send_text($data); //发送设定分词所要切割的文本
		$words = $pscws->get_tops(10); //获取权重前10个标签
		$tags = array();
		foreach ($words as $val) {
			$tags[] = $val['word'];
		}
		$pscws->close();
		return $tags;
	}
	
}